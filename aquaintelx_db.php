<?php
// AquaIntelX DB helper for Railway/local MySQL.
// Upload this file beside hardware_push.php, hardware_live.php, get_latest.php, and get_live.php.

function aquaintelx_pdo() {
    $host = getenv("MYSQLHOST") ?: getenv("DB_HOST") ?: "localhost";
    $port = getenv("MYSQLPORT") ?: getenv("DB_PORT") ?: "3306";
    $dbname = getenv("MYSQLDATABASE") ?: getenv("DB_NAME") ?: "railway";
    $username = getenv("MYSQLUSER") ?: getenv("DB_USER") ?: "root";
    $password = getenv("MYSQLPASSWORD") ?: getenv("DB_PASSWORD") ?: "";

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Use Philippine time for date functions and TIMESTAMP display.
    date_default_timezone_set("Asia/Manila");
    $pdo->exec("SET time_zone = '+08:00'");

    return $pdo;
}

function aquaintelx_json_headers() {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, X-Api-Key");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function aquaintelx_check_api_key() {
    // Must match ESP32 apiKey value.
    $EXPECTED_API_KEY = getenv("SENSOR_API_KEY") ?: "change-this-secret-key-here";

    if ($EXPECTED_API_KEY === "") return;

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $receivedApiKey = "";

    foreach ($headers as $key => $value) {
        if (strtolower($key) === "x-api-key") {
            $receivedApiKey = $value;
            break;
        }
    }

    if ($receivedApiKey !== $EXPECTED_API_KEY) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Unauthorized: Invalid API key"
        ]);
        exit;
    }
}

function aquaintelx_read_json_body() {
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

    return $data;
}

function aquaintelx_sensor_status($risk_level) {
    $risk = strtoupper(trim((string)$risk_level));

    if (strpos($risk, "CRITICAL") !== false) return "critical";
    if (strpos($risk, "MODERATE") !== false) return "warning";
    if (strpos($risk, "WARNING") !== false) return "warning";

    return "normal";
}

function aquaintelx_risk_from_turbidity($turbidity) {
    if ($turbidity <= 10) return "LOW RISK";
    if ($turbidity <= 50) return "MODERATE";
    return "CRITICAL";
}

function aquaintelx_format_display_time($dt) {
    if (!$dt) return null;
    $ts = strtotime($dt);
    if (!$ts) return null;
    return date("n/j/y, g:i:s A", $ts);
}
?>
