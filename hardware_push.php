<?php
// ============================================================
// hardware_push.php — Public endpoint for IoT hardware/Arduino
// Your microcontroller POSTs JSON here; no login session needed.
// Protected by X-Api-Key header instead.
//
// Example Arduino/ESP32 HTTP POST:
//   POST http://localhost/aquaintelx/hardware_push.php
//   Headers: Content-Type: application/json
//            X-Api-Key: your-secret-key-here
//   Body:    {"sensor_node":"NODE-01","temperature":24.5,"turbidity":1.2,"tds":320,"ph":7.2}
// ============================================================
declare(strict_types=1);

require_once 'config.php';

header('Content-Type: application/json');

// ── API Key verification ──────────────────────────────────
if (!defined('SENSOR_API_KEY')) {
    define('SENSOR_API_KEY', 'change-this-secret-key-in-config');
}

$provided = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals(SENSOR_API_KEY, $provided)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// ── Parse body ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body.']);
    exit;
}

// ── Sanitize & validate ───────────────────────────────────
$node  = substr(trim($body['sensor_node'] ?? 'NODE-01'), 0, 50);
$temp  = isset($body['temperature']) ? (float)$body['temperature'] : null;
$turb  = isset($body['turbidity'])   ? (float)$body['turbidity']   : null;
$tds   = isset($body['tds'])         ? (float)$body['tds']         : null;
$ph    = isset($body['ph'])          ? (float)$body['ph']          : null;

// Sanity range checks (reject garbage values)
if ($temp !== null && ($temp < -10 || $temp > 100)) $temp = null;
if ($turb !== null && ($turb < 0   || $turb > 1000)) $turb = null;
if ($tds  !== null && ($tds  < 0   || $tds  > 9999)) $tds  = null;
if ($ph   !== null && ($ph   < 0   || $ph   > 14))   $ph   = null;

// Must have at least one metric
if ($temp === null && $turb === null && $tds === null && $ph === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid sensor values provided.']);
    exit;
}

// ── Derive status ─────────────────────────────────────────
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

// ── Insert ────────────────────────────────────────────────
try {
    $pdo  = getDB();
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

    echo json_encode([
        'success'     => true,
        'id'          => (int)$pdo->lastInsertId(),
        'status'      => $status,
        'recorded_at' => date('Y-m-d H:i:s'),
    ]);

} catch (PDOException $e) {
    error_log('hardware_push error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
