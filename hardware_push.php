<?php
header("Content-Type: application/json");

echo json_encode([
    "status" => "success",
    "version" => "hardware_push_test_v1",
    "message" => "This is the real root hardware_push.php"
]);
?>