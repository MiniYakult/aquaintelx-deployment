<?php
header('Content-Type: application/json');

$temperature = $_GET['temperature'] ?? null;
$ph = $_GET['ph'] ?? null;
$turbidity = $_GET['turbidity'] ?? null;
$tds = $_GET['tds'] ?? null;

if ($temperature === null || $ph === null || $turbidity === null || $tds === null) {
    echo json_encode([
        "success" => false,
        "message" => "Missing sensor values"
    ]);
    exit;
}

if (!is_numeric($temperature) || !is_numeric($ph) || !is_numeric($turbidity) || !is_numeric($tds)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid sensor values"
    ]);
    exit;
}

$aiFolder = __DIR__ . DIRECTORY_SEPARATOR . 'ai_model';

if (!is_dir($aiFolder)) {
    echo json_encode([
        "success" => false,
        "message" => "AI model folder not found"
    ]);
    exit;
}

$python = getenv('PYTHON_BIN');

if (!$python) {
    if (PHP_OS_FAMILY === 'Windows') {
        $localVenvPython = __DIR__ . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';

        if (file_exists($localVenvPython)) {
            $python = $localVenvPython;
        } else {
            $python = 'python';
        }
    } else {
        if (file_exists('/opt/venv/bin/python')) {
            $python = '/opt/venv/bin/python';
        } else {
            $python = 'python3';
        }
    }
}

$cdCommand = PHP_OS_FAMILY === 'Windows'
    ? 'cd /d ' . escapeshellarg($aiFolder)
    : 'cd ' . escapeshellarg($aiFolder);

$cmd = $cdCommand . ' && '
    . escapeshellarg($python) . ' predict.py '
    . escapeshellarg($temperature) . ' '
    . escapeshellarg($ph) . ' '
    . escapeshellarg($turbidity) . ' '
    . escapeshellarg($tds) . ' 2>&1';

$output = shell_exec($cmd);

if ($output === null) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to run AI prediction command"
    ]);
    exit;
}

$risk = trim($output);

$validRisks = ["Low Risk", "Moderate Risk", "High Risk"];

if (!in_array($risk, $validRisks, true)) {
    echo json_encode([
        "success" => false,
        "message" => "AI prediction failed",
        "debug" => $risk
    ]);
    exit;
}

$suggestion = "Continue monitoring water quality.";

if ($risk === "Low Risk") {
    $suggestion = "Water quality appears stable. Continue regular monitoring.";
} elseif ($risk === "Moderate Risk") {
    $suggestion = "Some readings show possible irregularity. Re-test the water before drinking.";
} elseif ($risk === "High Risk") {
    $suggestion = "Possible anomaly detected. Avoid drinking and inspect the water source immediately.";
}

echo json_encode([
    "success" => true,
    "risk" => $risk,
    "suggestion" => $suggestion
]);