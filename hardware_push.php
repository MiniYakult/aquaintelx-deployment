<?php
header("Content-Type: application/json");

// ===============================
// AquaIntelX hardware_push.php
// Accepts:
// - Online ESP32 readings
// - Offline SD card synced readings
// ===============================

// OPTIONAL API KEY CHECK
// This must match the ESP32 apiKey value.
$EXPECTED_API_KEY = getenv("SENSOR_API_KEY") ?: "change-this-secret-key-here";

$headers = getallheaders();
$receivedApiKey = "";

// Some servers return lowercase header names.
if (isset($headers["X-Api-Key"])) {
    $receivedApiKey = $headers["X-Api-Key"];
} elseif (isset($headers["x-api-key"])) {
    $receivedApiKey = $headers["x-api-key"];
}

if ($EXPECTED_API_KEY !== "" && $receivedApiKey !== $EXPECTED_API_KEY) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized: Invalid API key"
    ]);
    exit;
}

// ===============================
// DATABASE CONNECTION
// Change these if needed.
// For Railway, use your Railway MySQL credentials.
// ===============================

$host = getenv("MYSQLHOST") ?: getenv("DB_HOST") ?: "localhost";
$port = getenv("MYSQLPORT") ?: getenv("DB_PORT") ?: "3306";
$dbname = getenv("MYSQLDATABASE") ?: getenv("DB_NAME") ?: "railway";
$username = getenv("MYSQLUSER") ?: getenv("DB_USER") ?: "root";
$password = getenv("MYSQLPASSWORD") ?: getenv("DB_PASSWORD") ?: "";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "details" => $e->getMessage()
    ]);
    exit;
}

// ===============================
// READ JSON BODY
// ===============================

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON body",
        "raw" => $raw
    ]);
    exit;
}

// ===============================
// GET VALUES FROM ESP32
// ===============================

$record_id = $data["record_id"] ?? null;

$sensor_node = $data["sensor_node"] ?? "NODE-01";
$system_state = $data["system_state"] ?? "READING";
$message = $data["message"] ?? "Water quality reading";
$seconds_remaining = isset($data["seconds_remaining"]) ? intval($data["seconds_remaining"]) : 0;

$temperature = isset($data["temperature"]) ? floatval($data["temperature"]) : null;
$ph = isset($data["ph"]) ? floatval($data["ph"]) : null;
$turbidity = isset($data["turbidity"]) ? floatval($data["turbidity"]) : null;
$tds = isset($data["tds"]) ? floatval($data["tds"]) : null;

$risk_level = $data["risk_level"] ?? null;
$confidence = isset($data["confidence"]) ? floatval($data["confidence"]) : null;
$ai_confidence_status = $data["ai_confidence_status"] ?? null;

$data_source = $data["data_source"] ?? "online";
$offline_timestamp = $data["offline_timestamp"] ?? null;
$time_valid = isset($data["time_valid"]) ? intval($data["time_valid"]) : 1;

// Some ESP32 code sends timestamp instead of offline_timestamp.
if ($offline_timestamp === null && isset($data["timestamp"]) && $data_source === "sd_card") {
    $offline_timestamp = $data["timestamp"];
}

// ===============================
// VALIDATION
// ===============================

if ($temperature === null || $ph === null || $turbidity === null || $tds === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Missing required sensor values",
        "required" => [
            "temperature",
            "ph",
            "turbidity",
            "tds"
        ],
        "received" => $data
    ]);
    exit;
}

// Generate record_id if missing.
if ($record_id === null || trim($record_id) === "") {
    $safeNode = preg_replace("/[^A-Za-z0-9_-]/", "", $sensor_node);
    $record_id = $safeNode . "" . date("Ymd_His") . "" . uniqid();
}

// Clean values.
$sensor_node = substr($sensor_node, 0, 50);
$system_state = substr($system_state, 0, 50);
$message = substr($message, 0, 255);
$data_source = substr($data_source, 0, 20);

if ($risk_level !== null) {
    $risk_level = substr($risk_level, 0, 50);
}

if ($ai_confidence_status !== null) {
    $ai_confidence_status = substr($ai_confidence_status, 0, 50);
}

// Basic sensor status.
$sensor_status = "normal";

if ($risk_level !== null) {
    $riskUpper = strtoupper($risk_level);

    if (strpos($riskUpper, "CRITICAL") !== false) {
        $sensor_status = "critical";
    } elseif (strpos($riskUpper, "MODERATE") !== false || strpos($riskUpper, "WARNING") !== false) {
        $sensor_status = "warning";
    }
}

// If risk level was not sent, fallback using turbidity.
if ($risk_level === null || trim($risk_level) === "") {
    if ($turbidity <= 10) {
        $risk_level = "LOW RISK";
        $sensor_status = "normal";
    } elseif ($turbidity <= 50) {
        $risk_level = "MODERATE";
        $sensor_status = "warning";
    } else {
        $risk_level = "CRITICAL";
        $sensor_status = "critical";
    }
}

// ===============================
// INSERT INTO DATABASE
// ===============================

try {
    $sql = "
        INSERT INTO sensor_readings
        (
            record_id,
            sensor_node,
            temperature,
            turbidity,
            tds,
            ph,
            status,
            risk_level,
            confidence,
            ai_confidence_status,
            data_source,
            offline_timestamp,
            time_valid
        )
        VALUES
        (
            :record_id,
            :sensor_node,
            :temperature,
            :turbidity,
            :tds,
            :ph,
            :status,
            :risk_level,
            :confidence,
            :ai_confidence_status,
            :data_source,
            :offline_timestamp,
            :time_valid
        )
        ON DUPLICATE KEY UPDATE
            record_id = record_id
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":record_id" => $record_id,
        ":sensor_node" => $sensor_node,
        ":temperature" => $temperature,
        ":turbidity" => $turbidity,
        ":tds" => $tds,
        ":ph" => $ph,
        ":status" => $sensor_status,
        ":risk_level" => $risk_level,
        ":confidence" => $confidence,
        ":ai_confidence_status" => $ai_confidence_status,
        ":data_source" => $data_source,
        ":offline_timestamp" => $offline_timestamp,
        ":time_valid" => $time_valid
    ]);

    $wasDuplicate = $stmt->rowCount() === 0;

    echo json_encode([
        "success" => true
        "status" => "success",
        "message" => $wasDuplicate ? "Duplicate record ignored" : "Sensor data saved",
        "record_id" => $record_id,
        "sensor_node" => $sensor_node,
        "temperature" => $temperature,
        "ph" => $ph,
        "turbidity" => $turbidity,
        "tds" => $tds,
        "sensor_status" => $sensor_status,
        "risk_level" => $risk_level,
        "confidence" => $confidence,
        "ai_confidence_status" => $ai_confidence_status,
        "data_source" => $data_source,
        "offline_timestamp" => $offline_timestamp,
        "time_valid" => $time_valid
    ]);

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed",
        "details" => $e->getMessage()
    ]);
}
?>