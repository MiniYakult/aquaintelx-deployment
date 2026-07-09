<?php

// ============================================================
// config.php — Local + Railway Compatible Configuration
// ============================================================

// Database
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT') ?: 3306);
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: (getenv('RAILWAY_ENVIRONMENT') ? 'railway' : 'aquaintelx'));
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Application
define('APP_NAME', 'AquaIntelX');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080/FinalAquaintelX-backend-Test1/backend-test');

// Hardware / ESP32 API Key
define('SENSOR_API_KEY', getenv('SENSOR_API_KEY') ?: 'change-this-secret-key-here');

// Session lifetime: 8 hours
define('SESSION_LIFETIME', 28800);

// Database connection
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}