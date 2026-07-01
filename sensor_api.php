<?php
// ============================================================
// sensor_api.php — REST API for Sensor Readings
// Handles: GET (fetch history/latest) and POST (insert reading)
// ============================================================
declare(strict_types=1);

require_once 'config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Router ────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'latest';

// Allow hardware insert to use API key instead of login session
$isHardwareInsert = ($method === 'POST' && $action === 'insert');

// Everything except hardware insert requires dashboard login
if (!$isHardwareInsert) {
    require_once 'auth_check.php';
}

try {
    $pdo = getDB();
    $pdo->exec("SET time_zone = '+08:00'");

    if ($method === 'GET' && $action === 'latest') {
    handleLatest($pdo);
} elseif ($method === 'GET' && $action === 'history') {
    handleHistory($pdo);
} elseif ($method === 'GET' && $action === 'chart') {
    handleChart($pdo);
} elseif ($method === 'GET' && $action === 'stats') {
    handleStats($pdo);
} elseif ($method === 'POST' && $action === 'insert') {
    handleInsert($pdo);
} elseif ($method === 'POST' && $action === 'manual') {
    handleManualInsert($pdo);
} elseif ($method === 'DELETE') {
    handleDelete($pdo);
} else {
    jsonError(405, 'Method or action not allowed.');
}

} catch (PDOException $e) {
    error_log('sensor_api PDO error: ' . $e->getMessage());
    jsonError(500, 'Database error. Check server logs.');
}

// ── Handlers ──────────────────────────────────────────────

/**
 * GET ?action=latest
 * Returns the most recent reading for each sensor node.
 */
function handleLatest(PDO $pdo): void {
    $stmt = $pdo->query(
        'SELECT 
            r.*,
            COALESCE(r.reading_time, r.recorded_at) AS display_time
           FROM sensor_readings r
     INNER JOIN (
            SELECT 
                sensor_node, 
                MAX(COALESCE(reading_time, recorded_at)) AS max_time
              FROM sensor_readings
          GROUP BY sensor_node
          ) latest ON r.sensor_node = latest.sensor_node
                  AND COALESCE(r.reading_time, r.recorded_at) = latest.max_time
         ORDER BY r.sensor_node'
    );

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

/**
 * GET ?action=history[&node=NODE-01][&limit=100][&from=2024-01-01][&to=2024-12-31]
 * Returns paginated historical readings.
 */
function handleHistory(PDO $pdo): void {
    $node   = $_GET['node']   ?? 'all';
    $range  = $_GET['range']  ?? 'all';
    $status = $_GET['status'] ?? 'all';

    $limit  = min((int)($_GET['limit'] ?? 100), 1000);
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $allowedRanges = ['all', '24h', '7d', '30d'];
    $allowedStatus = ['all', 'normal', 'warning', 'critical'];

    if (!in_array($range, $allowedRanges, true)) {
        $range = 'all';
    }

    if (!in_array($status, $allowedStatus, true)) {
        $status = 'all';
    }

    $timeExpr = "COALESCE(reading_time, recorded_at)";
    $statusExpr = "COALESCE(risk_level, status)";

    $where  = [];
    $params = [];

    if ($range === '24h') {
        $where[] = "$timeExpr >= NOW() - INTERVAL 24 HOUR";
    } elseif ($range === '7d') {
        $where[] = "$timeExpr >= NOW() - INTERVAL 7 DAY";
    } elseif ($range === '30d') {
        $where[] = "$timeExpr >= NOW() - INTERVAL 30 DAY";
    }

    if ($node !== 'all' && $node !== '') {
        $where[] = "sensor_node = :node";
        $params[':node'] = $node;
    }

    if ($status === 'normal') {
        $where[] = "(
            LOWER($statusExpr) LIKE '%normal%' OR
            LOWER($statusExpr) LIKE '%low%'
        )";
    } elseif ($status === 'warning') {
        $where[] = "(
            LOWER($statusExpr) LIKE '%warning%' OR
            LOWER($statusExpr) LIKE '%moderate%'
        )";
    } elseif ($status === 'critical') {
        $where[] = "(
            LOWER($statusExpr) LIKE '%critical%' OR
            LOWER($statusExpr) LIKE '%high%'
        )";
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            *,
            $timeExpr AS display_time,
            $statusExpr AS final_status
        FROM sensor_readings
        $whereSql
        ORDER BY $timeExpr DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countSql = "
        SELECT COUNT(*)
        FROM sensor_readings
        $whereSql
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data'    => $rows,
        'meta'    => [
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => max(1, (int)ceil($total / $limit)),
        ],
        'filters' => [
            'range'  => $range,
            'status' => $status,
            'node'   => $node,
        ]
    ]);
}

/**
 * GET ?action=chart[&node=NODE-01][&range=24h|7d|30d]
 * Returns downsampled readings suitable for charting.
 */
function handleChart(PDO $pdo): void {
    $node  = $_GET['node']  ?? null;
    $range = $_GET['range'] ?? '24h';

    if ($range === '7d') {
        $interval = '7 DAY';
        $maxPoints = 84;
    } elseif ($range === '30d') {
        $interval = '30 DAY';
        $maxPoints = 90;
    } else {
        $interval = '24 HOUR';
        $maxPoints = 60;
    }

    $where  = "COALESCE(reading_time, recorded_at) >= NOW() - INTERVAL $interval";
    $params = [];

    if ($node) {
        $where .= ' AND sensor_node = :node';
        $params[':node'] = $node;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sensor_readings WHERE $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $nth = max(1, (int)floor($total / $maxPoints));

    $stmt = $pdo->prepare(
        "SELECT 
            id,
            sensor_node,
            temperature,
            turbidity,
            tds,
            ph,
            status,
            recorded_at,
            reading_time,
            COALESCE(reading_time, recorded_at) AS display_time
           FROM sensor_readings
          WHERE $where
          ORDER BY COALESCE(reading_time, recorded_at) ASC"
    );

    $stmt->execute($params);
    $all = $stmt->fetchAll();

    $sampled = [];

    foreach ($all as $i => $row) {
        if ($i % $nth === 0) {
            $sampled[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $sampled,
        'range' => $range
    ]);
}

/**
 * GET ?action=stats
 * Returns summary statistics (avg, min, max) for each parameter.
 */
function handleStats(PDO $pdo): void {
    $range = $_GET['range'] ?? '24h';

    if ($range === '7d') {
        $interval = '7 DAY';
    } elseif ($range === '30d') {
        $interval = '30 DAY';
    } else {
        $interval = '24 HOUR';
    }

    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*)                   AS total_readings,
            ROUND(AVG(temperature), 2) AS avg_temp,
            ROUND(MIN(temperature), 2) AS min_temp,
            ROUND(MAX(temperature), 2) AS max_temp,
            ROUND(AVG(turbidity),   2) AS avg_turb,
            ROUND(MIN(turbidity),   2) AS min_turb,
            ROUND(MAX(turbidity),   2) AS max_turb,
            ROUND(AVG(tds),         2) AS avg_tds,
            ROUND(MIN(tds),         2) AS min_tds,
            ROUND(MAX(tds),         2) AS max_tds,
            ROUND(AVG(ph),          2) AS avg_ph,
            ROUND(MIN(ph),          2) AS min_ph,
            ROUND(MAX(ph),          2) AS max_ph,
            SUM(status = 'warning')    AS warning_count,
            SUM(status = 'critical')   AS critical_count
         FROM sensor_readings
        WHERE COALESCE(reading_time, recorded_at) >= NOW() - INTERVAL $interval"
    );

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetch(),
        'range' => $range
    ]);
}

/**
 * POST ?action=insert
 * For hardware/IoT devices to push sensor readings.
 * Expects JSON: { sensor_node, temperature, turbidity, tds, ph }
 * Secured by a shared API key (set in config.php or via header).
 */
function handleInsert(PDO $pdo): void {
    // API key check (hardware devices bypass session auth)
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (!defined('SENSOR_API_KEY') || $apiKey !== SENSOR_API_KEY) {
        jsonError(401, 'Invalid or missing API key.');
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    insertReading($pdo, $body);
}

/**
 * POST ?action=manual
 * For dashboard manual data entry by logged-in users.
 */
function handleManualInsert(PDO $pdo): void {
    // Only admins can insert manually
    if (($GLOBALS['currentUser']['role'] ?? 'viewer') !== 'admin') {
        jsonError(403, 'Only admins can insert sensor readings manually.');
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    insertReading($pdo, $body);
}

/**
 * DELETE ?action= (any) &id=123
 * Soft-deletes a reading (admin only).
 */
function handleDelete(PDO $pdo): void {
    if (($GLOBALS['currentUser']['role'] ?? 'viewer') !== 'admin') {
        jsonError(403, 'Only admins can delete readings.');
    }
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError(400, 'Missing reading id.');

    $stmt = $pdo->prepare('DELETE FROM sensor_readings WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) jsonError(404, 'Reading not found.');
    echo json_encode(['success' => true, 'message' => 'Reading deleted.']);
}

// ── Shared insert logic ────────────────────────────────────
function insertReading(PDO $pdo, array $body): void {
    $node  = substr(trim($body['sensor_node'] ?? 'NODE-01'), 0, 50);
    $temp  = isset($body['temperature']) ? (float)$body['temperature'] : null;
    $turb  = isset($body['turbidity'])   ? (float)$body['turbidity']   : null;
    $tds   = isset($body['tds'])         ? (float)$body['tds']         : null;
    $ph    = isset($body['ph'])          ? (float)$body['ph']          : null;

    // Derive status
    $status = 'normal';
    if (
        ($turb !== null && $turb > 5.0) ||
        ($ph   !== null && ($ph < 6.0 || $ph > 9.0)) ||
        ($tds  !== null && $tds > 600) ||
        ($temp !== null && ($temp < 5 || $temp > 35))
    ) {
        $status = 'critical';
    } elseif (
        ($turb !== null && $turb > 2.0) ||
        ($ph   !== null && ($ph < 6.5 || $ph > 8.5)) ||
        ($tds  !== null && $tds > 500) ||
        ($temp !== null && ($temp < 10 || $temp > 30))
    ) {
        $status = 'warning';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO sensor_readings (sensor_node, temperature, turbidity, tds, ph, status)
         VALUES (:node, :temp, :turb, :tds, :ph, :status)'
    );
    $stmt->execute([
        ':node'   => $node,
        ':temp'   => $temp,
        ':turb'   => $turb,
        ':tds'    => $tds,
        ':ph'     => $ph,
        ':status' => $status,
    ]);

    $id = (int)$pdo->lastInsertId();
    echo json_encode([
        'success'  => true,
        'message'  => 'Reading saved.',
        'id'       => $id,
        'status'   => $status,
    ]);
}

// ── Utility ───────────────────────────────────────────────
function jsonError(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
