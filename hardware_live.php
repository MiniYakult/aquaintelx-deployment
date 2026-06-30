<?php
require_once __DIR__ . "/aquaintelx_db.php";

aquaintelx_json_headers();
aquaintelx_check_api_key();

try {
    $pdo = aquaintelx_pdo();
    $data = aquaintelx_read_json_body();

    $sensor_node = substr($data["sensor_node"] ?? "NODE-01", 0, 50);
    $system_state = substr($data["system_state"] ?? "READING", 0, 50);
    $message = substr($data["message"] ?? "Live preview reading", 0, 255);
    $seconds_remaining = isset($data["seconds_remaining"]) ? intval($data["seconds_remaining"]) : 0;

    $temperature = array_key_exists("temperature", $data) && $data["temperature"] !== null
        ? floatval($data["temperature"])
        : null;

    $temperature_valid = isset($data["temperature_valid"])
        ? intval($data["temperature_valid"])
        : ($temperature === null ? 0 : 1);

    $ph = isset($data["ph"]) ? floatval($data["ph"]) : null;
    $ph_raw = isset($data["ph_raw"]) ? floatval($data["ph_raw"]) : $ph;
    $ph_valid = isset($data["ph_valid"])
        ? intval($data["ph_valid"])
        : (($ph !== null && $ph >= 0 && $ph <= 14) ? 1 : 0);

    $turbidity = isset($data["turbidity"]) ? floatval($data["turbidity"]) : null;
    $tds = isset($data["tds"]) ? floatval($data["tds"]) : null;

    if ($ph === null || $turbidity === null || $tds === null) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Missing required live values: ph, turbidity, tds"
        ]);
        exit;
    }

    $risk_level = $data["risk_level"] ?? aquaintelx_risk_from_turbidity($turbidity);
    $risk_level = substr($risk_level, 0, 50);

    $reading_time = $data["timestamp"] ?? date("Y-m-d H:i:s");
    $data_type = $data["data_type"] ?? "live";

    $sql = "
        INSERT INTO live_readings
        (
            sensor_node,
            temperature,
            temperature_valid,
            ph,
            ph_raw,
            ph_valid,
            turbidity,
            tds,
            risk_level,
            system_state,
            message,
            seconds_remaining,
            reading_time,
            data_type
        )
        VALUES
        (
            :sensor_node,
            :temperature,
            :temperature_valid,
            :ph,
            :ph_raw,
            :ph_valid,
            :turbidity,
            :tds,
            :risk_level,
            :system_state,
            :message,
            :seconds_remaining,
            :reading_time,
            :data_type
        )
        ON DUPLICATE KEY UPDATE
            temperature = VALUES(temperature),
            temperature_valid = VALUES(temperature_valid),
            ph = VALUES(ph),
            ph_raw = VALUES(ph_raw),
            ph_valid = VALUES(ph_valid),
            turbidity = VALUES(turbidity),
            tds = VALUES(tds),
            risk_level = VALUES(risk_level),
            system_state = VALUES(system_state),
            message = VALUES(message),
            seconds_remaining = VALUES(seconds_remaining),
            reading_time = VALUES(reading_time),
            data_type = VALUES(data_type),
            updated_at = CURRENT_TIMESTAMP
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":sensor_node" => $sensor_node,
        ":temperature" => $temperature,
        ":temperature_valid" => $temperature_valid,
        ":ph" => $ph,
        ":ph_raw" => $ph_raw,
        ":ph_valid" => $ph_valid,
        ":turbidity" => $turbidity,
        ":tds" => $tds,
        ":risk_level" => $risk_level,
        ":system_state" => $system_state,
        ":message" => $message,
        ":seconds_remaining" => $seconds_remaining,
        ":reading_time" => $reading_time,
        ":data_type" => $data_type
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Live preview updated",
        "sensor_node" => $sensor_node,
        "temperature" => $temperature,
        "temperature_valid" => $temperature_valid,
        "ph" => $ph,
        "ph_raw" => $ph_raw,
        "ph_valid" => $ph_valid,
        "turbidity" => $turbidity,
        "tds" => $tds,
        "risk_level" => $risk_level,
        "system_state" => $system_state,
        "seconds_remaining" => $seconds_remaining,
        "reading_time" => $reading_time,
        "display_time" => aquaintelx_format_display_time($reading_time)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Live preview update failed",
        "details" => $e->getMessage()
    ]);
}
?>
