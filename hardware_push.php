<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, X-Api-Key");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

date_default_timezone_set("Asia/Manila");

$API_VERSION = "hardware_push_fixed_v3";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Browser test
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    echo json_encode([
        "status" => "success",
        "version" => $API_VERSION,
        "message" => "hardware_push.php is running. Use POST from ESP32 to upload average or SD sync data."
    ]);
    exit;
}

// ===============================
// API KEY
// ===============================

$EXPECTED_API_KEY = getenv("SENSOR_API_KEY") ?: getenv("AQUAINTELX_API_KEY") ?: "change-this-secret-key-here";

function get_header_value($name) {
    $headers = [];

    if (function_exists("getallheaders")) {
        $headers = getallheaders();
    }

    foreach ($headers as $key => $value) {
        if (strtolower($key) === strtolower($name)) {
            return $value;
        }
    }

    $serverKey = "HTTP_" . strtoupper(str_replace("-", "_", $name));
    return $_SERVER[$serverKey] ?? "";
}

$receivedApiKey = get_header_value("X-Api-Key");

if ($EXPECTED_API_KEY !== "" && $receivedApiKey !== $EXPECTED_API_KEY) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "version" => $API_VERSION,
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
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "version" => $API_VERSION,
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

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "version" => $API_VERSION,
        "message" => "Invalid JSON body",
        "raw" => $raw
    ]);
    exit;
}

// ===============================
// HELPER FUNCTIONS
// ===============================

function value_or_null($array, $key) {
    if (!array_key_exists($key, $array)) {
        return null;
    }

    if ($array[$key] === null || $array[$key] === "") {
        return null;
    }

    return $array[$key];
}

function safe_string($value, $maxLength) {
    $value = (string)$value;
    return substr($value, 0, $maxLength);
}

function get_table_columns($pdo, $tableName) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName`");
    $rows = $stmt->fetchAll();

    $columns = [];

    foreach ($rows as $row) {
        $columns[$row["Field"]] = true;
    }

    return $columns;
}

// ===============================
// GET VALUES FROM ESP32
// ===============================

$sensor_node = safe_string($data["sensor_node"] ?? "NODE-01", 50);

$timestamp = $data["timestamp"] ?? date("Y-m-d H:i:s");
$reading_time = $timestamp;

$record_id = $data["record_id"] ?? null;

if ($record_id === null || trim($record_id) === "") {
    $safeNode = preg_replace("/[^A-Za-z0-9_-]/", "", $sensor_node);
    $record_id = $safeNode . "_" . date("Ymd_His") . "_" . uniqid();
}

$system_state = safe_string($data["system_state"] ?? "READING", 50);
$message = safe_string($data["message"] ?? "15-minute average water reading", 255);
$seconds_remaining = isset($data["seconds_remaining"]) ? intval($data["seconds_remaining"]) : 0;

$temperatureRaw = value_or_null($data, "temperature");
$temperature = $temperatureRaw === null ? null : floatval($temperatureRaw);

$temperature_valid = isset($data["temperature_valid"])
    ? intval($data["temperature_valid"])
    : ($temperature === null ? 0 : 1);

$phRawValue = value_or_null($data, "ph");
$turbidityRawValue = value_or_null($data, "turbidity");
$tdsRawValue = value_or_null($data, "tds");

$ph = $phRawValue === null ? null : floatval($phRawValue);
$turbidity = $turbidityRawValue === null ? null : floatval($turbidityRawValue);
$tds = $tdsRawValue === null ? null : floatval($tdsRawValue);

$ph_raw_value = value_or_null($data, "ph_raw");
$ph_raw = $ph_raw_value === null ? $ph : floatval($ph_raw_value);

$ph_valid = isset($data["ph_valid"]) ? intval($data["ph_valid"]) : 1;

$risk_level = $data["risk_level"] ?? null;
$confidence = isset($data["confidence"]) ? floatval($data["confidence"]) : null;
$ai_confidence_status = $data["ai_confidence_status"] ?? null;

$data_source = safe_string($data["data_source"] ?? "online", 20);
$offline_timestamp = $data["offline_timestamp"] ?? null;
$time_valid = isset($data["time_valid"]) ? intval($data["time_valid"]) : 1;

if ($offline_timestamp === null && $data_source === "sd_card") {
    $offline_timestamp = $reading_time;
}

// ===============================
// VALIDATION
// Temperature is allowed to be NULL.
// pH is allowed outside 0-14 because ESP32 sends ph_valid.
// ===============================

if ($ph === null || $turbidity === null || $tds === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "version" => $API_VERSION,
        "message" => "Missing required sensor values: ph, turbidity, or tds",
        "received" => $data
    ]);
    exit;
}

// ===============================
// RISK / STATUS
// ===============================

if ($risk_level === null || trim($risk_level) === "") {
    if ($turbidity <= 10) {
        $risk_level = "LOW RISK";
    } elseif ($turbidity <= 50) {
        $risk_level = "MODERATE";
    } else {
        $risk_level = "CRITICAL";
    }
}

$risk_level = safe_string($risk_level, 50);

$riskUpper = strtoupper($risk_level);
$sensor_status = "normal";

if (strpos($riskUpper, "CRITICAL") !== false) {
    $sensor_status = "critical";
} elseif (
    strpos($riskUpper, "MODERATE") !== false ||
    strpos($riskUpper, "WARNING") !== false
) {
    $sensor_status = "warning";
}

// ===============================
// INSERT INTO DATABASE
// This version adapts to your existing columns.
// If a column does not exist, it will skip it.
// ===============================

try {
    $columnsAvailable = get_table_columns($pdo, "sensor_readings");

    $fieldMap = [
        "record_id" => $record_id,
        "sensor_node" => $sensor_node,
        "temperature" => $temperature,
        "turbidity" => $turbidity,
        "tds" => $tds,
        "ph" => $ph,
        "status" => $sensor_status,
        "risk_level" => $risk_level,
        "confidence" => $confidence,
        "ai_confidence_status" => $ai_confidence_status,
        "data_source" => $data_source,
        "offline_timestamp" => $offline_timestamp,
        "time_valid" => $time_valid,
        "reading_time" => $reading_time,
        "temperature_valid" => $temperature_valid,
        "ph_raw" => $ph_raw,
        "ph_valid" => $ph_valid,
        "system_state" => $system_state,
        "message" => $message,
        "seconds_remaining" => $seconds_remaining
    ];

    $insertColumns = [];
    $placeholders = [];
    $params = [];

    foreach ($fieldMap as $column => $value) {
        if (isset($columnsAvailable[$column])) {
            $insertColumns[] = "`$column`";
            $placeholders[] = ":$column";
            $params[":$column"] = $value;
        }
    }

    if (count($insertColumns) === 0) {
        throw new Exception("No matching columns found in sensor_readings table.");
    }

    $sql = "INSERT INTO sensor_readings (" .
        implode(", ", $insertColumns) .
        ") VALUES (" .
        implode(", ", $placeholders) .
        ")";

    if (isset($columnsAvailable["record_id"])) {
        $sql .= " ON DUPLICATE KEY UPDATE record_id = record_id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $wasDuplicate = $stmt->rowCount() === 0;

    echo json_encode([
        "status" => "success",
        "version" => $API_VERSION,
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
        "time_valid" => $time_valid,
        "inserted_columns" => array_map(function ($col) {
            return str_replace("`", "", $col);
        }, $insertColumns)
    ]);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "version" => $API_VERSION,
        "message" => "Database insert failed",
        "details" => $e->getMessage()
    ]);
}
?>