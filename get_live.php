<?php
require_once __DIR__ . "/aquaintelx_db.php";

aquaintelx_json_headers();

try {
    $pdo = aquaintelx_pdo();

    $sensor_node = isset($_GET["sensor_node"]) ? substr($_GET["sensor_node"], 0, 50) : null;

    if ($sensor_node) {
        $stmt = $pdo->prepare("SELECT * FROM live_readings WHERE sensor_node = :sensor_node LIMIT 1");
        $stmt->execute([":sensor_node" => $sensor_node]);
    } else {
        $stmt = $pdo->query("SELECT * FROM live_readings ORDER BY updated_at DESC LIMIT 1");
    }

    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode([
            "status" => "empty",
            "message" => "No live data yet"
        ]);
        exit;
    }

    $row["status"] = "success";
    $row["display_time"] = aquaintelx_format_display_time($row["reading_time"] ?? $row["updated_at"] ?? null);
    $row["server_display_time"] = aquaintelx_format_display_time($row["updated_at"] ?? null);

    $row["temperature_display"] = intval($row["temperature_valid"] ?? 0) === 1 && $row["temperature"] !== null
        ? number_format((float)$row["temperature"], 1)
        : "NA";

    $row["ph_display"] = intval($row["ph_valid"] ?? 0) === 1 && $row["ph"] !== null
        ? number_format((float)$row["ph"], 2)
        : "INVALID";

    echo json_encode($row);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch live data",
        "details" => $e->getMessage()
    ]);
}
?>
