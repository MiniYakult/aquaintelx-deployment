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
    $timeExpr = sensorTimeExpr($pdo, 'r.');

    $where  = [];
    $params = [];
    addUserScopeFilter($pdo, $where, $params, 'r.');

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare(
        "SELECT
            r.*,
            $timeExpr AS display_time
           FROM sensor_readings r
           $whereSql
          ORDER BY $timeExpr DESC"
    );

    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $latestByNode = [];
    foreach ($rows as $row) {
        $node = (string)($row['sensor_node'] ?? 'NODE-01');
        if (!isset($latestByNode[$node])) {
            $latestByNode[$node] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => array_values($latestByNode)]);
}

function handleHistory(PDO $pdo): void {
    $node   = $_GET['node']   ?? 'all';
    $range  = $_GET['range']  ?? 'all';
    $status = $_GET['status'] ?? 'all';

    $requestedLimit = (int)($_GET['limit'] ?? 10);
    $limit  = max(1, min($requestedLimit, 10));
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

    $timeExpr = sensorTimeExpr($pdo);
    $statusExpr = sensorStatusExpr($pdo);

    $where  = [];
    $params = [];

    addUserScopeFilter($pdo, $where, $params);

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
            'scope'       => currentDashboardUserRole() === 'admin' ? 'all_users' : 'current_user',
        ],
        'filters' => [
            'range'  => $range,
            'status' => $status,
            'node'   => $node,
        ]
    ]);
}

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

    $timeExpr = sensorTimeExpr($pdo);
    $selectReadingTime = sensorHasColumn($pdo, 'reading_time') ? 'reading_time' : 'NULL AS reading_time';
    $selectRecordedAt = sensorHasColumn($pdo, 'recorded_at') ? 'recorded_at' : 'NULL AS recorded_at';
    $selectStatus = sensorStatusExpr($pdo) . ' AS status';

    $where = ["$timeExpr >= NOW() - INTERVAL $interval"];
    $params = [];

    addUserScopeFilter($pdo, $where, $params);

    if ($node) {
        $where[] = 'sensor_node = :node';
        $params[':node'] = $node;
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sensor_readings WHERE $whereSql");
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
            $selectStatus,
            $selectRecordedAt,
            $selectReadingTime,
            $timeExpr AS display_time
           FROM sensor_readings
          WHERE $whereSql
          ORDER BY $timeExpr ASC"
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
        'range' => $range,
        'scope' => currentDashboardUserRole() === 'admin' ? 'all_users' : 'current_user'
    ]);
}

function handleStats(PDO $pdo): void {
    $node   = $_GET['node']   ?? 'all';
    $range  = $_GET['range']  ?? 'all';
    $status = $_GET['status'] ?? 'all';

    $allowedRanges = ['all', '24h', '7d', '30d'];
    $allowedStatus = ['all', 'normal', 'warning', 'critical'];

    if (!in_array($range, $allowedRanges, true)) {
        $range = 'all';
    }

    if (!in_array($status, $allowedStatus, true)) {
        $status = 'all';
    }

    $timeExpr = sensorTimeExpr($pdo);
    $statusExpr = sensorStatusExpr($pdo);
    $statusLowerExpr = "LOWER(COALESCE($statusExpr, ''))";

    $where = [];
    $params = [];

    addUserScopeFilter($pdo, $where, $params);

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
            $statusLowerExpr LIKE '%normal%' OR
            $statusLowerExpr LIKE '%low%'
        )";
    } elseif ($status === 'warning') {
        $where[] = "(
            $statusLowerExpr LIKE '%warning%' OR
            $statusLowerExpr LIKE '%moderate%'
        )";
    } elseif ($status === 'critical') {
        $where[] = "(
            $statusLowerExpr LIKE '%critical%' OR
            $statusLowerExpr LIKE '%high%'
        )";
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
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
            SUM(CASE WHEN $statusLowerExpr LIKE '%normal%' OR $statusLowerExpr LIKE '%low%' THEN 1 ELSE 0 END) AS low_count,
            SUM(CASE WHEN $statusLowerExpr LIKE '%warning%' OR $statusLowerExpr LIKE '%moderate%' THEN 1 ELSE 0 END) AS moderate_count,
            SUM(CASE WHEN $statusLowerExpr LIKE '%critical%' OR $statusLowerExpr LIKE '%high%' THEN 1 ELSE 0 END) AS critical_count
        FROM sensor_readings
        $whereSql
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'data'    => $stats,
        'stats'   => $stats,
        'scope'   => currentDashboardUserRole() === 'admin' ? 'all_users' : 'current_user',
        'filters' => [
            'range'  => $range,
            'status' => $status,
            'node'   => $node,
        ],
    ]);
}

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
function normalizeRiskLabel($risk): string {
    $risk = strtolower(trim((string)$risk));

    if (in_array($risk, ['normal', 'low', 'low risk', 'safe', 'optimal'], true)) {
        return 'Low Risk';
    }

    if (in_array($risk, ['warning', 'moderate', 'moderate risk', 'caution'], true)) {
        return 'Moderate Risk';
    }

    if (in_array($risk, ['critical', 'critical risk', 'high', 'high risk', 'danger', 'unsafe'], true)) {
        return 'Critical Risk';
    }

    return 'Moderate Risk';
}

function legacyStatusFromRisk(string $risk): string {
    $risk = normalizeRiskLabel($risk);

    if ($risk === 'Low Risk') {
        return 'normal';
    }

    if ($risk === 'Moderate Risk') {
        return 'warning';
    }

    return 'critical';
}

function deriveRiskFromSensorValues(?float $temp, ?float $turb, ?float $tds, ?float $ph): string {
    if (
        ($turb !== null && $turb > 5.0) ||
        ($ph   !== null && ($ph < 6.0 || $ph > 9.0)) ||
        ($tds  !== null && $tds > 600) ||
        ($temp !== null && ($temp < 5 || $temp > 35))
    ) {
        return 'Critical Risk';
    }

    if (
        ($turb !== null && $turb > 2.0) ||
        ($ph   !== null && ($ph < 6.5 || $ph > 8.5)) ||
        ($tds  !== null && $tds > 500) ||
        ($temp !== null && ($temp < 10 || $temp > 30))
    ) {
        return 'Moderate Risk';
    }

    return 'Low Risk';
}

function insertReading(PDO $pdo, array $body): void {
    $node  = substr(trim($body['sensor_node'] ?? 'NODE-01'), 0, 50);
    $temp  = isset($body['temperature']) ? (float)$body['temperature'] : null;
    $turb  = isset($body['turbidity'])   ? (float)$body['turbidity']   : null;
    $tds   = isset($body['tds'])         ? (float)$body['tds']         : null;
    $ph    = isset($body['ph'])          ? (float)$body['ph']          : null;

    if (isset($body['risk_level']) && trim((string)$body['risk_level']) !== '') {
        $riskLevel = normalizeRiskLabel($body['risk_level']);
    } elseif (isset($body['status']) && trim((string)$body['status']) !== '') {
        $riskLevel = normalizeRiskLabel($body['status']);
    } else {
        $riskLevel = deriveRiskFromSensorValues($temp, $turb, $tds, $ph);
    }

    $status = legacyStatusFromRisk($riskLevel);

    $columns = [
        'sensor_node'  => ':node',
        'temperature'  => ':temp',
        'turbidity'    => ':turb',
        'tds'          => ':tds',
        'ph'           => ':ph',
    ];

    $params = [
        ':node' => $node,
        ':temp' => $temp,
        ':turb' => $turb,
        ':tds'  => $tds,
        ':ph'   => $ph,
    ];

    if (sensorHasColumn($pdo, 'status')) {
        $columns['status'] = ':status';
        $params[':status'] = $status;
    }

    if (sensorHasColumn($pdo, 'risk_level')) {
        $columns['risk_level'] = ':risk_level';
        $params[':risk_level'] = $riskLevel;
    }

    if (sensorHasColumn($pdo, 'owner_user_id')) {
        $ownerId = ownerUserIdFromBodyOrSession($body);
        if ($ownerId !== null) {
            $columns['owner_user_id'] = ':owner_user_id';
            $params[':owner_user_id'] = $ownerId;
        }
    }

    if (sensorHasColumn($pdo, 'owner_email')) {
        $ownerEmail = ownerEmailFromBodyOrSession($body);
        if ($ownerEmail !== '') {
            $columns['owner_email'] = ':owner_email';
            $params[':owner_email'] = $ownerEmail;
        }
    }

    $columnSql = implode(', ', array_keys($columns));
    $valueSql  = implode(', ', array_values($columns));

    $stmt = $pdo->prepare(
        "INSERT INTO sensor_readings ($columnSql)
         VALUES ($valueSql)"
    );

    $stmt->execute($params);

    $id = (int)$pdo->lastInsertId();

    echo json_encode([
        'success'       => true,
        'message'       => 'Reading saved.',
        'id'            => $id,
        'status'        => $status,
        'risk_level'    => $riskLevel,
        'owner_user_id' => $params[':owner_user_id'] ?? null,
    ]);
}

function currentDashboardUserRole(): string {
    $user = $GLOBALS['currentUser'] ?? [];

    $role = strtolower(trim((string)(
        $user['role']
        ?? $user['account_role']
        ?? $user['user_role']
        ?? $_SESSION['role']
        ?? $_SESSION['account_role']
        ?? $_SESSION['user_role']
        ?? 'viewer'
    )));

    // Accept role values such as "admin", "Admin Account", or "administrator".
    if (str_contains($role, 'admin')) {
        return 'admin';
    }

    return $role !== '' ? $role : 'viewer';
}

function currentDashboardUserId(): ?int {
    $user = $GLOBALS['currentUser'] ?? [];
    $id = $user['id']
        ?? $user['user_id']
        ?? $user['account_id']
        ?? $_SESSION['id']
        ?? $_SESSION['user_id']
        ?? $_SESSION['account_id']
        ?? null;

    if ($id === null || $id === '') {
        return null;
    }

    return (int)$id;
}

function currentDashboardUserEmail(): string {
    $user = $GLOBALS['currentUser'] ?? [];
    return trim((string)(
        $user['email']
        ?? $user['user_email']
        ?? $_SESSION['email']
        ?? $_SESSION['user_email']
        ?? ''
    ));
}

function addUserScopeFilter(PDO $pdo, array &$where, array &$params, string $prefix = ''): void {
    if (currentDashboardUserRole() === 'admin') {
        return;
    }

    if (sensorHasColumn($pdo, 'owner_user_id')) {
        $userId = currentDashboardUserId();

        if ($userId !== null) {
            $paramName = ':scope_owner_user_id';
            $where[] = "{$prefix}owner_user_id = $paramName";
            $params[$paramName] = $userId;
            return;
        }
    }

    if (sensorHasColumn($pdo, 'owner_email')) {
        $email = currentDashboardUserEmail();

        if ($email !== '') {
            $paramName = ':scope_owner_email';
            $where[] = "{$prefix}owner_email = $paramName";
            $params[$paramName] = $email;
            return;
        }
    }

    $where[] = '1 = 0';
}

function ownerUserIdFromBodyOrSession(array $body): ?int {
    foreach (['owner_user_id', 'user_id', 'account_id'] as $key) {
        if (isset($body[$key]) && $body[$key] !== '') {
            return (int)$body[$key];
        }
    }

    return currentDashboardUserId();
}

function ownerEmailFromBodyOrSession(array $body): string {
    foreach (['owner_email', 'user_email', 'email'] as $key) {
        if (isset($body[$key]) && trim((string)$body[$key]) !== '') {
            return trim((string)$body[$key]);
        }
    }

    return currentDashboardUserEmail();
}


function jsonError(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function sensorColumns(PDO $pdo): array {
    static $columns = null;

    if ($columns === null) {
        $stmt = $pdo->query('SHOW COLUMNS FROM sensor_readings');
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return $columns;
}

function sensorHasColumn(PDO $pdo, string $column): bool {
    return in_array($column, sensorColumns($pdo), true);
}

function sensorTimeExpr(PDO $pdo, string $prefix = ''): string {
    $hasReadingTime = sensorHasColumn($pdo, 'reading_time');
    $hasRecordedAt = sensorHasColumn($pdo, 'recorded_at');

    if ($hasReadingTime && $hasRecordedAt) {
        return "COALESCE({$prefix}reading_time, {$prefix}recorded_at)";
    }

    if ($hasReadingTime) {
        return "{$prefix}reading_time";
    }

    return "{$prefix}recorded_at";
}

function sensorStatusExpr(PDO $pdo): string {
    $hasRiskLevel = sensorHasColumn($pdo, 'risk_level');
    $hasStatus = sensorHasColumn($pdo, 'status');

    if ($hasRiskLevel && $hasStatus) {
        return "COALESCE(NULLIF(TRIM(risk_level), ''), NULLIF(TRIM(status), ''))";
    }

    if ($hasRiskLevel) {
        return "risk_level";
    }

    return "status";
}