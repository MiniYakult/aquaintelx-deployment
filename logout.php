<?php
// ============================================================
// logout.php — Destroys session and redirects to login
// ============================================================
declare(strict_types=1);

require_once 'config.php';

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: login.html');
exit;
