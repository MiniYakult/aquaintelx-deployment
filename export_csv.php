<?php
// ============================================================
// export_csv.php — Download filtered AquaIntelX readings as CSV
// Supports:
// ?range=all|24h|7d|30d
// ?status=all|normal|warning|critical
// ?node=all|NODE-01
// ============================================================

declare(strict_types=1);

require_once 'config.php';
require_once 'auth_check.php';

$range  = $_GET['range']  ?? 'all';
$status = $_GET['status'] ?? 'all';
$node   = $_GET['node']   ?? 'all';

$allowedRanges  = ['all', '24h', '7d', '30d'];
$allowedStatus  = ['all', 'normal', 'warning', 'critical'];

if (!in_array($range, $allowedRanges, true)) {
    $range = 'all';
}

if (!in_array($status, $allowedStatus, true)) {
    $status = 'all';
}

try {
    $pdo = getDB();
    $pdo->exec("SET time_zone = '+08:00'");

    // Detect available columns for compatibility
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM sensor_readings");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

    $hasReadingTime = in_array('reading_time', $columns, true);
    $hasRecordedAt  = in_array('recorded_at', $columns, true);
    $hasRiskLevel   = in_array('risk_level', $columns, true);
    $hasStatus      = in_array('status', $columns, true);

    if ($hasReadingTime && $hasRecordedAt) {
        $timeExpr = "COALESCE(reading_time, recorded_at)";
    } elseif ($hasReadingTime) {
        $timeExpr = "reading_time";
    } else {
        $timeExpr = "recorded_at";
    }

    if ($hasRiskLevel && $hasStatus) {
        $statusExpr = "COALESCE(risk_level, status)";
    } elseif ($hasRiskLevel) {
        $statusExpr = "risk_level";
    } else {
        $statusExpr = "status";
    }

    $where = [];
    $params = [];

    // Date range filter
    if ($range === '24h') {
        $where[] = "$timeExpr >= NOW() - INTERVAL 24 HOUR";
    } elseif ($range === '7d') {
        $where[] = "$timeExpr >= NOW() - INTERVAL 7 DAY";
    } elseif ($range === '30d') {
        $where[] = "$timeExpr >= NOW() - INTERVAL 30 DAY";
    }

    // Status filter
    if ($status === 'normal') {
        $where[] = "(LOWER($statusExpr) LIKE '%normal%' OR LOWER($statusExpr) LIKE '%low%')";
    } elseif ($status === 'warning') {
        $where[] = "(LOWER($statusExpr) LIKE '%warning%' OR LOWER($statusExpr) LIKE '%moderate%')";
    } elseif ($status === 'critical') {
        $where[] = "(LOWER($statusExpr) LIKE '%critical%' OR LOWER($statusExpr) LIKE '%high%')";
    }

    // Sensor node filter
    if ($node !== 'all' && $node !== '') {
        $where[] = "sensor_node = :node";
        $params[':node'] = $node;
    }

    $sql = "
        SELECT
            $timeExpr AS display_time,
            sensor_node,
            temperature,
            turbidity,
            tds,
            ph,
            $statusExpr AS final_status
        FROM sensor_readings
        " . ($where ? "WHERE " . implode(" AND ", $where) : "") . "
        ORDER BY $timeExpr DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error while exporting CSV.');
}

$filename = 'aquaintelx_readings_' . $range . '_' . $status . '_' . $node . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header row
fputcsv($out, [
    'Timestamp',
    'Sensor Node',
    'Temperature (C)',
    'Turbidity (NTU)',
    'TDS (ppm)',
    'pH',
    'Status'
]);

foreach ($rows as $row) {
    $statusText = $row['final_status'] ?? '';

    if (strtolower($statusText) === 'high risk') {
        $statusText = 'Critical Risk';
    }

    fputcsv($out, [
        $row['display_time'] ?? '',
        $row['sensor_node'] ?? '',
        $row['temperature'] ?? '',
        $row['turbidity'] ?? '',
        $row['tds'] ?? '',
        $row['ph'] ?? '',
        $statusText,
    ]);
}

fclose($out);
exit;