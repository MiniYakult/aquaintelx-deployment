<?php
// ============================================================
// export_csv.php — Download sensor readings as CSV
// Usage: export_csv.php?range=24h   (or 7d, 30d, all)
// ============================================================
declare(strict_types=1);

require_once 'config.php';
require_once 'auth_check.php';

$range = $_GET['range'] ?? '24h';
$node  = $_GET['node']  ?? null;

$interval = match($range) {
    '7d'  => '7 DAY',
    '30d' => '30 DAY',
    'all' => null,
    default => '24 HOUR',
};

$where  = [];
$params = [];

if ($interval) {
    $where[] = "recorded_at >= NOW() - INTERVAL $interval";
}
if ($node) {
    $where[] = 'sensor_node = :node';
    $params[':node'] = $node;
}

$sql = 'SELECT recorded_at, sensor_node, temperature, turbidity, tds, ph, status
          FROM sensor_readings'
      . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
      . ' ORDER BY recorded_at DESC';

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
}

$filename = 'aquaintelx_readings_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, ['Timestamp', 'Sensor Node', 'Temperature (°C)', 'Turbidity (NTU)', 'TDS (ppm)', 'pH', 'Status']);

foreach ($rows as $row) {
    fputcsv($out, [
        $row['recorded_at'],
        $row['sensor_node'],
        $row['temperature'] ?? '',
        $row['turbidity']   ?? '',
        $row['tds']         ?? '',
        $row['ph']          ?? '',
        $row['status'],
    ]);
}

fclose($out);
exit;
