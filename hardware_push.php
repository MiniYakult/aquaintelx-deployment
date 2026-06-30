<?php
header("Content-Type: application/json");

echo json_encode([
    "status" => "success",
    "version" => "hardware_push_test_v1",
    "message" => "hardware_push.php is updated and running"
]);
?>