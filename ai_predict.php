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

$python = getenv('PYTHON_BIN');

if (!$python) {
    if (PHP_OS_FAMILY === 'Windows') {
        // Support both possible local virtual environment folder names
        $localVenvPython1 = __DIR__ . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
        $localVenvPython2 = __DIR__ . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';

        if (file_exists($localVenvPython1)) {
            $python = $localVenvPython1;
        } elseif (file_exists($localVenvPython2)) {
            $python = $localVenvPython2;
        } else {
            $python = 'python';
        }
    } else {
        // Keep Railway/Linux behavior
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

if ($risk === "High Risk") {
    $risk = "Critical Risk";
}

$validRisks = ["Low Risk", "Moderate Risk", "Critical Risk"];

if (!in_array($risk, $validRisks, true)) {
    echo json_encode([
        "success" => false,
        "message" => "AI prediction failed",
        "debug" => $risk
    ]);
    exit;
}

function build_ai_reasons_and_recommendations($temperature, $ph, $turbidity, $tds, $risk): array {
    $reasons = [];
    $recommendations = [];

    $temp = is_numeric($temperature) ? (float)$temperature : null;
    $phValue = is_numeric($ph) ? (float)$ph : null;
    $turb = is_numeric($turbidity) ? (float)$turbidity : null;
    $tdsValue = is_numeric($tds) ? (float)$tds : null;

    // pH explanation
    if ($phValue === null) {
        $reasons[] = "pH reading is missing, so the assessment is incomplete.";
    } elseif ($phValue < 6.5) {
        $reasons[] = "pH is below the PNSDW 2017 acceptable range of 6.5 to 8.5.";
    } elseif ($phValue > 8.5) {
        $reasons[] = "pH is above the PNSDW 2017 acceptable range of 6.5 to 8.5.";
    } else {
        $reasons[] = "pH is within the PNSDW 2017 acceptable range of 6.5 to 8.5.";
    }

    // Turbidity explanation
    if ($turb === null) {
        $reasons[] = "Turbidity reading is missing, so suspended particles cannot be fully assessed.";
    } elseif ($turb > 5) {
        $reasons[] = "Turbidity is above the PNSDW 2017 reference value of 5 NTU, which may indicate suspended particles or cloudiness.";
    } elseif ($turb > 1) {
        $reasons[] = "Turbidity is below the PNSDW 2017 reference value of 5 NTU but shows a slight increase; continued monitoring or re-sampling is recommended.";
    } else {
        $reasons[] = "Turbidity is within the PNSDW 2017 reference value of 5 NTU.";
    }

    // TDS explanation
    if ($tdsValue === null) {
        $reasons[] = "TDS reading is missing, so dissolved solids cannot be fully assessed.";
    } elseif ($tdsValue <= 20 && $phValue !== null && ($phValue < 6.5 || $phValue > 8.5)) {
        $reasons[] = "TDS is very low while pH is abnormal, so the pH reading may be unstable due to low conductivity.";
    } elseif ($tdsValue > 600) {
        $reasons[] = "TDS is above the PNSDW 2017 reference value of 600 mg/L, indicating elevated dissolved solids.";
    } elseif ($tdsValue >= 500) {
        $reasons[] = "TDS is approaching the PNSDW 2017 reference value of 600 mg/L and should be monitored.";
    } else {
        $reasons[] = "TDS is within the PNSDW 2017 reference value of 600 mg/L.";
    }

    // Temperature explanation
    if ($temp === null) {
        $reasons[] = "Temperature reading is unavailable, so the reading is marked as incomplete.";
    } elseif ($temp > 35) {
        $reasons[] = "Temperature is high and may affect sensor behavior or water condition.";
    } else {
        $reasons[] = "Temperature reading is available and included in the assessment.";
    }

    // Sensor combination explanations
    if ($phValue !== null && $turb !== null && $phValue > 8.5 && $turb >= 5) {
        $reasons[] = "The combination of high pH and elevated turbidity strengthens the anomaly indication.";
    }

    if ($phValue !== null && $tdsValue !== null && $tdsValue <= 20 && ($phValue < 6.5 || $phValue > 8.5)) {
        $reasons[] = "Abnormal pH with very low TDS may indicate unstable pH measurement and should be verified through re-sampling.";
    }

    if ($tdsValue !== null && $turb !== null && $tdsValue >= 500 && $turb >= 5) {
        $reasons[] = "Both dissolved solids and suspended particles appear elevated, increasing the water quality concern.";
    }

    // Recommendations
    if ($risk === "Low Risk") {
        $recommendations[] = "Continue regular monitoring.";
        $recommendations[] = "Keep the scheduled sampling interval active.";
    } elseif ($risk === "Moderate Risk") {
        $recommendations[] = "Re-test the water before drinking.";
        $recommendations[] = "Flush the chamber and compare the next processed reading.";
        $recommendations[] = "Check sensor calibration if the same pattern continues.";
    } elseif ($risk === "Critical Risk") {
        $recommendations[] = "Avoid drinking the water.";
        $recommendations[] = "Inspect the water source immediately.";
        $recommendations[] = "Perform a re-sample after flushing.";
        $recommendations[] = "Check pH, turbidity, and TDS sensors for possible calibration issues.";
    }

    return [
        "reasons" => $reasons,
        "recommendations" => $recommendations
    ];
}

$suggestion = "Continue monitoring water quality.";

if ($risk === "Low Risk") {
    $suggestion = "Water quality appears stable. Continue regular monitoring.";
} elseif ($risk === "Moderate Risk") {
    $suggestion = "Some readings show possible irregularity. Re-test the water before drinking.";
} elseif ($risk === "Critical Risk") {
    $suggestion = "Possible anomaly detected. Avoid drinking and inspect the water source immediately.";
}

$analysis = build_ai_reasons_and_recommendations($temperature, $ph, $turbidity, $tds, $risk);

echo json_encode([
    "success" => true,
    "risk" => $risk,
    "suggestion" => $suggestion,
    "reasons" => $analysis["reasons"],
    "recommendations" => $analysis["recommendations"]
]);