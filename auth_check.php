<?php
// ============================================================
// auth_check.php — Session Guard
// Include at the very top of every protected page:
//   require_once 'auth_check.php';
// ============================================================
declare(strict_types=1);

require_once 'config.php';

// ── Check session ─────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    // AJAX / JSON requests get a 401 instead of a redirect
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    $wantsJson = strpos($accept, 'application/json') !== false
            || strpos($contentType, 'application/json') !== false;

    if ($wantsJson) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['success' => false, 'message' => 'Unauthenticated. Please log in.']);
        exit;
    }

    header('Location: login.html');
    exit;
}

// ── Session timeout check (idle for SESSION_LIFETIME seconds) ──
if (isset($_SESSION['logged_in_at'])) {
    $idle = time() - (int)$_SESSION['logged_in_at'];
    if ($idle > SESSION_LIFETIME) {
        session_destroy();
        header('Location: login.html?timeout=1');
        exit;
    }
    // Refresh timestamp on each request
    $_SESSION['logged_in_at'] = time();
}

// ── Convenience: current user info ───────────────────────
// After including this file, use these anywhere in the page:
//   $currentUser['id'], $currentUser['name'], $currentUser['role']
$currentUser = [
    'id'    => $_SESSION['user_id'],
    'name'  => $_SESSION['user_name']  ?? 'User',
    'email' => $_SESSION['user_email'] ?? '',
    'role'  => $_SESSION['user_role']  ?? 'viewer',
];
