<?php
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

// ===============================
// AquaIntelX hardware_push.php
// Saves 15-minute average readings
// Accepts online and SD-card synced data
// ===============================

$EXPECTED_API_KEY = "change-this-secret-key-here";

$headers = getallheaders();
$receivedApiKey = "";

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

    $pdo->exec("SET time_zone = '+08:00'");
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
// READ JSON
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
// GET VALUES
// ===============================

$record_id = $data["record_id"] ?? null;
$sensor_node = $data["sensor_node"] ?? "NODE-01";

$system_state = $data["system_state"] ?? "READING";
$message = $data["message"] ?? "15-minute average water reading";
$seconds_remaining = isset($data["seconds_remaining"]) ? intval($data["seconds_remaining"]) : 0;

$temperature = array_key_exists("temperature", $data) && $data["temperature"] !== null
    ? floatval($data["temperature"])
    : null;

$temperature_valid = isset($data["temperature_valid"])
    ? intval($data["temperature_valid"])
    : ($temperature === null ? 0 : 1);

$ph = isset($data["ph"]) ? floatval($data["ph"]) : null;
$ph_raw = isset($data["ph_raw"]) ? floatval($data["ph_raw"]) : $ph;
$ph_valid = isset($data["ph_valid"]) ? intval($data["ph_valid"]) : 1;

$turbidity = isset($data["turbidity"]) ? floatval($data["turbidity"]) : null;
$tds = isset($data["tds"]) ? floatval($data["tds"]) : null;

$risk_level = $data["risk_level"] ?? null;
$confidence = isset($data["confidence"]) ? floatval($data["confidence"]) : null;
$ai_confidence_status = $data["ai_confidence_status"] ?? null;

$data_source = $data["data_source"] ?? "online";
$offline_timestamp = $data["offline_timestamp"] ?? null;
$time_valid = isset($data["time_valid"]) ? intval($data["time_valid"]) : 1;

$reading_time = $data["timestamp"] ?? date("Y-m-d H:i:s");

if ($offline_timestamp === null && $data_source === "sd_card") {
    $offline_timestamp = $reading_time;
}

// ===============================
// VALIDATION
// Temperature is allowed to be NULL.
// pH is allowed even if outside 0-14 because ESP32 sends ph_valid.
// ===============================

if ($ph === null || $turbidity === null || $tds === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Missing required sensor values",
        "received" => $data
    ]);
    exit;
}

if ($record_id === null || trim($record_id) === "") {
    $safeNode = preg_replace("/[^A-Za-z0-9_-]/", "", $sensor_node);
    $record_id = $safeNode . "_" . date("Ymd_His") . "_" . uniqid();
}

$sensor_node = substr($sensor_node, 0, 50);
$system_state = substr($system_state, 0, 50);
$message = substr($message, 0, 255);
$data_source = substr($data_source, 0, 20);

if ($risk_level === null || trim($risk_level) === "") {
    if ($turbidity <= 10) {
        $risk_level = "LOW RISK";
    } elseif ($turbidity <= 50) {
        $risk_level = "MODERATE";
    } else {
        $risk_level = "CRITICAL";
    }
}

$riskUpper = strtoupper($risk_level);
$sensor_status = "normal";

if (strpos($riskUpper, "CRITICAL") !== false) {
    $sensor_status = "critical";
} elseif (strpos($riskUpper, "MODERATE") !== false || strpos($riskUpper, "WARNING") !== false) {
    $sensor_status = "warning";
}

// ===============================
// INSERT
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
            time_valid,
            reading_time,
            temperature_valid,
            ph_raw,
            ph_valid
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
            :time_valid,
            :reading_time,
            :temperature_valid,
            :ph_raw,
            :ph_valid
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
        ":time_valid" => $time_valid,
        ":reading_time" => $reading_time,
        ":temperature_valid" => $temperature_valid,
        ":ph_raw" => $ph_raw,
        ":ph_valid" => $ph_valid
    ]);

    $wasDuplicate = $stmt->rowCount() === 0;

    echo json_encode([
        "status" => "success",
        "message" => $wasDuplicate ? "Duplicate record ignored" : "Sensor data saved",
        "record_id" => $record_id,
        "sensor_node" => $sensor_node,
        "temperature" => $temperature,
        "temperature_valid" => $temperature_valid,
        "ph" => $ph,
        "ph_raw" => $ph_raw,
        "ph_valid" => $ph_valid,
        "turbidity" => $turbidity,
        "tds" => $tds,
        "sensor_status" => $sensor_status,
        "risk_level" => $risk_level,
        "data_source" => $data_source,
        "offline_timestamp" => $offline_timestamp,
        "reading_time" => $reading_time,
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