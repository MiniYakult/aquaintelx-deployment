<?php
// ============================================================
// sensor_api.php — REST API for Sensor Readings
// Handles: GET (fetch history/latest) and POST (insert reading)
// ============================================================
declare(strict_types=1);

require_once 'config.php';
require_once 'auth_check.php'; // Blocks unauthenticated requests

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Router ────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'latest';

try {
    $pdo = getDB();

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
        'SELECT r.*
           FROM sensor_readings r
     INNER JOIN (
            SELECT sensor_node, MAX(recorded_at) AS max_time
              FROM sensor_readings
          GROUP BY sensor_node
          ) latest ON r.sensor_node = latest.sensor_node
                  AND r.recorded_at = latest.max_time
         ORDER BY r.sensor_node'
    );
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}

/**
 * GET ?action=history[&node=NODE-01][&limit=100][&from=2024-01-01][&to=2024-12-31]
 * Returns paginated historical readings.
 */
function handleHistory(PDO $pdo): void {
    $node  = $_GET['node']  ?? null;
    $limit = min((int)($_GET['limit'] ?? 100), 1000);
    $from  = $_GET['from']  ?? null;
    $to    = $_GET['to']    ?? null;
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $where  = [];
    $params = [];

    if ($node) {
        $where[] = 'sensor_node = :node';
        $params[':node'] = $node;
    }
    if ($from) {
        $where[] = 'recorded_at >= :from';
        $params[':from'] = $from . ' 00:00:00';
    }
    if ($to) {
        $where[] = 'recorded_at <= :to';
        $params[':to'] = $to . ' 23:59:59';
    }

    $sql = 'SELECT * FROM sensor_readings'
         . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
         . ' ORDER BY recorded_at DESC'
         . " LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Total count for pagination
    $countSql = 'SELECT COUNT(*) FROM sensor_readings'
              . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
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
            'total_pages' => (int)ceil($total / $limit),
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

    $where  = "recorded_at >= NOW() - INTERVAL $interval";
    $params = [];
    if ($node) {
        $where .= ' AND sensor_node = :node';
        $params[':node'] = $node;
    }

    // Count total rows in range for downsampling
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sensor_readings WHERE $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Downsample: pick every Nth row
    $nth = max(1, (int)floor($total / $maxPoints));

    $stmt = $pdo->prepare(
        "SELECT id, sensor_node, temperature, turbidity, tds, ph, status, recorded_at
           FROM sensor_readings
          WHERE $where
          ORDER BY recorded_at ASC"
    );
    $stmt->execute($params);
    $all = $stmt->fetchAll();

    // Apply downsampling
    $sampled = [];
    foreach ($all as $i => $row) {
        if ($i % $nth === 0) $sampled[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $sampled, 'range' => $range]);
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
        WHERE recorded_at >= NOW() - INTERVAL $interval"
    );
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetch(), 'range' => $range]);
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
