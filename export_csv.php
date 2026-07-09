<?php
// ============================================================
// export_csv.php — Download filtered AquaIntelX readings as CSV
// Supports old and new labels safely:
// ?range=all|24h|7d|30d
// ?status=all|normal|warning|critical|low|moderate|critical
// ?node=all|NODE-01
// ============================================================

declare(strict_types=1);

require_once 'config.php';
require_once 'auth_check.php';

$range  = $_GET['range']  ?? 'all';
$status = $_GET['status'] ?? 'all';
$node   = $_GET['node']   ?? 'all';

$allowedRanges = ['all', '24h', '7d', '30d'];

// Keep old frontend filter values working.
// Also allow newer naming if you update the dropdown later.
$allowedStatus = [
    'all',
    'normal',
    'warning',
    'critical',
    'low',
    'moderate',
    'high',
    'low risk',
    'moderate risk',
    'high risk',
    'critical risk'
];

if (!in_array($range, $allowedRanges, true)) {
    $range = 'all';
}

$status = strtolower(trim((string)$status));

if (!in_array($status, $allowedStatus, true)) {
    $status = 'all';
}

function normalize_risk_label($risk): string
{
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

    // Safe fallback for unknown/blank values
    return 'Moderate Risk';
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

    // Prefer AI/database risk_level over old status.
    // NULLIF handles empty string values.
    if ($hasRiskLevel && $hasStatus) {
        $statusExpr = "COALESCE(NULLIF(TRIM(risk_level), ''), NULLIF(TRIM(status), ''))";
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

    // Status/risk filter.
    // Keep old filter names compatible with new risk labels.
    if ($status === 'normal' || $status === 'low' || $status === 'low risk') {
        $where[] = "(LOWER($statusExpr) LIKE '%normal%' OR LOWER($statusExpr) LIKE '%low%' OR LOWER($statusExpr) LIKE '%safe%')";
    } elseif ($status === 'warning' || $status === 'moderate' || $status === 'moderate risk') {
        $where[] = "(LOWER($statusExpr) LIKE '%warning%' OR LOWER($statusExpr) LIKE '%moderate%' OR LOWER($statusExpr) LIKE '%caution%')";
    } elseif ($status === 'critical' || $status === 'high' || $status === 'high risk' || $status === 'critical risk') {
        $where[] = "(LOWER($statusExpr) LIKE '%critical%' OR LOWER($statusExpr) LIKE '%high%' OR LOWER($statusExpr) LIKE '%danger%' OR LOWER($statusExpr) LIKE '%unsafe%')";
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
    'Risk Level'
]);

foreach ($rows as $row) {
    $riskLevel = normalize_risk_label($row['final_status'] ?? '');

    fputcsv($out, [
        $row['display_time'] ?? '',
        $row['sensor_node'] ?? '',
        $row['temperature'] ?? '',
        $row['turbidity'] ?? '',
        $row['tds'] ?? '',
        $row['ph'] ?? '',
        $riskLevel,
    ]);
}

fclose($out);
exit;