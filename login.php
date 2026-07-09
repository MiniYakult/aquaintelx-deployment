<?php
// ============================================================
// login.php — Authentication Handler
// ============================================================
declare(strict_types=1);

require_once 'config.php';

// ── Helpers ───────────────────────────────────────────────
function jsonResponse(bool $success, string $message, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function logLoginAttempt(PDO $pdo, ?int $userId, string $email, string $status): void {
    $stmt = $pdo->prepare(
        'INSERT INTO login_logs (user_id, email, ip_address, user_agent, status)
         VALUES (:uid, :email, :ip, :ua, :status)'
    );
    $stmt->execute([
        ':uid'    => $userId,
        ':email'  => $email,
        ':ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ':status' => $status,
    ]);
}

// ── Handle POST (AJAX or form submit) ─────────────────────
// Important: do NOT redirect POST requests just because a previous
// admin/viewer session still exists. If the user opened login.html
// while already logged in, the old behavior returned index.php HTML
// to fetch(), which caused the browser message: "Could not reach the server."
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Support both JSON body (fetch) and normal form POST
    $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    $body     = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;

    $email    = trim($body['email']    ?? '');
    $password = trim($body['password'] ?? '');

    // ── Basic validation ──────────────────────────────────
    if (empty($email) || empty($password)) {
        jsonResponse(false, 'Email and password are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Invalid email format.');
    }

    // ── Rate limiting (simple: max 5 failed attempts / 15 min per IP) ──
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS attempts
               FROM login_logs
              WHERE ip_address = :ip
                AND status     = 'failed'
                AND created_at > NOW() - INTERVAL 15 MINUTE"
        );
        $stmt->execute([':ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        $row = $stmt->fetch();

        if ((int)$row['attempts'] >= 5) {
            jsonResponse(false, 'Too many failed attempts. Please wait 15 minutes.');
        }
    } catch (PDOException $e) {
        error_log('DB error (rate-limit): ' . $e->getMessage());
        jsonResponse(false, 'A database error occurred. Please try again.');
    }

    // ── Look up user ──────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password, role, is_active
               FROM users
              WHERE email = :email
              LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Constant-time path: verify even if no user found (prevents timing attack)
        $hash = $user['password'] ?? '';
        $passwordOk = password_verify($password, $hash);

        if (!$user || !$passwordOk) {
            logLoginAttempt($pdo, null, $email, 'failed');
            jsonResponse(false, 'Invalid email or password.');
        }

        if (!(bool)$user['is_active']) {
            logLoginAttempt($pdo, (int)$user['id'], $email, 'failed');
            jsonResponse(false, 'Your account has been deactivated. Contact support.');
        }

        // ── Success ───────────────────────────────────────
        logLoginAttempt($pdo, (int)$user['id'], $email, 'success');

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['logged_in_at'] = time();

        jsonResponse(true, 'Login successful.', [
            'name'     => $user['name'],
            'role'     => $user['role'],
            'redirect' => 'index.php',
        ]);

    } catch (PDOException $e) {
        error_log('DB error (login): ' . $e->getMessage());
        jsonResponse(false, 'A database error occurred. Please try again.');
    }
}

// ── Non-POST behavior ─────────────────────────────────────
// Visiting login.php directly while already logged in should still
// go to the dashboard, but AJAX login POST requests must return JSON.
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

header('Location: login.html');
exit;
