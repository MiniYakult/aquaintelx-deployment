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

$python = getenv('PYTHON_BIN');

if (!$python) {
    if (PHP_OS_FAMILY === 'Windows') {
        $python = 'C:\\Users\\alexandra nicolas\\AppData\\Local\\Python\\bin\\python.exe';
        $aiFolder = __DIR__ . '\\ai_model';

        $cmd = 'cd /d ' . escapeshellarg($aiFolder) . ' && '
            . escapeshellarg($python) . ' predict.py '
            . escapeshellarg($temperature) . ' '
            . escapeshellarg($ph) . ' '
            . escapeshellarg($turbidity) . ' '
            . escapeshellarg($tds) . ' 2>&1';
    } else {
        $python = '/opt/venv/bin/python';
        $aiFolder = __DIR__ . '/ai_model';

        $cmd = 'cd ' . escapeshellarg($aiFolder) . ' && '
            . escapeshellarg($python) . ' predict.py '
            . escapeshellarg($temperature) . ' '
            . escapeshellarg($ph) . ' '
            . escapeshellarg($turbidity) . ' '
            . escapeshellarg($tds) . ' 2>&1';
    }
} else {
    $aiFolder = PHP_OS_FAMILY === 'Windows'
        ? __DIR__ . '\\ai_model'
        : __DIR__ . '/ai_model';

    $cmdPrefix = PHP_OS_FAMILY === 'Windows'
        ? 'cd /d ' . escapeshellarg($aiFolder)
        : 'cd ' . escapeshellarg($aiFolder);

    $cmd = $cmdPrefix . ' && '
        . escapeshellarg($python) . ' predict.py '
        . escapeshellarg($temperature) . ' '
        . escapeshellarg($ph) . ' '
        . escapeshellarg($turbidity) . ' '
        . escapeshellarg($tds) . ' 2>&1';
}

$output = shell_exec($cmd);
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