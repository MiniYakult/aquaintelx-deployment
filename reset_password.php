<?php
// ============================================================
// reset_password.php — Verified password reset handler
// Flow:
//   1) request_code: user enters email, system sends a code by SMTP
//   2) reset_password: user enters code + new password
// Railway-ready: configure SMTP env vars in Railway Variables.
// ============================================================
declare(strict_types=1);

require_once 'config.php';
require_once 'smtp_mailer.php';

function jsonResponse(bool $success, string $message, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function resetRequestBody(): array {
    $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    $body = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
    return is_array($body) ? $body : [];
}

function createResetTableIfMissing(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_codes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        email VARCHAR(100) NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL DEFAULT NULL,
        request_ip VARCHAR(64) NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_email_created (email, created_at),
        INDEX idx_email_expires (email, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function publicResetResponse(): void {
    // Do not reveal whether the email exists. This prevents account enumeration.
    jsonResponse(true, 'If the email is registered, a verification code has been sent. Check your inbox or spam folder.', [
        'next' => 'verify',
    ]);
}

function handleRequestCode(PDO $pdo, array $body): void {
    $email = strtolower(trim((string)($body['email'] ?? '')));

    if ($email === '') {
        jsonResponse(false, 'Email address is required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Invalid email format.');
    }

    createResetTableIfMissing($pdo);

    // Rate-limit by email and IP to reduce abuse.
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 64);

    $limitStmt = $pdo->prepare("SELECT COUNT(*) FROM password_reset_codes
        WHERE (email = :email OR request_ip = :ip)
          AND created_at > NOW() - INTERVAL 15 MINUTE");
    $limitStmt->execute([':email' => $email, ':ip' => $ip]);

    if ((int)$limitStmt->fetchColumn() >= 5) {
        jsonResponse(false, 'Too many reset requests. Please wait 15 minutes before trying again.');
    }

    $stmt = $pdo->prepare('SELECT id, name, email, is_active FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Return the same public response if no account exists or account is inactive.
    if (!$user || !(bool)$user['is_active']) {
        publicResetResponse();
    }

    $code = (string)random_int(100000, 999999);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 10 * 60); // 10 minutes

    // Invalidate older unused codes for this email.
    $markOld = $pdo->prepare('UPDATE password_reset_codes SET used_at = NOW() WHERE email = :email AND used_at IS NULL');
    $markOld->execute([':email' => $email]);

    $insert = $pdo->prepare('INSERT INTO password_reset_codes (user_id, email, code_hash, expires_at, request_ip)
        VALUES (:user_id, :email, :code_hash, :expires_at, :request_ip)');
    $insert->execute([
        ':user_id' => (int)$user['id'],
        ':email' => $email,
        ':code_hash' => $codeHash,
        ':expires_at' => $expiresAt,
        ':request_ip' => $ip,
    ]);

    $appUrl = rtrim((string)(defined('APP_URL') ? APP_URL : ''), '/');
    $loginUrl = $appUrl !== '' ? $appUrl . '/login.html' : 'login.html';
    $safeName = htmlspecialchars((string)($user['name'] ?? 'AquaIntelX User'), ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

    $subject = 'AquaIntelX password reset verification code';
    $html = "
        <div style=\"font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;max-width:560px;margin:auto;\">
            <h2 style=\"color:#0891b2;\">AquaIntelX Password Reset</h2>
            <p>Hello {$safeName},</p>
            <p>Your verification code is:</p>
            <div style=\"font-size:28px;font-weight:700;letter-spacing:6px;background:#ecfeff;border:1px solid #67e8f9;color:#0e7490;padding:16px 20px;border-radius:12px;text-align:center;\">
                {$safeCode}
            </div>
            <p>This code will expire in <strong>10 minutes</strong>. If you did not request a password reset, you can ignore this email.</p>
            <p><a href=\"{$safeLoginUrl}\" style=\"color:#0891b2;\">Open AquaIntelX Login</a></p>
            <p style=\"font-size:12px;color:#64748b;\">This is an automated security email from AquaIntelX.</p>
        </div>
    ";

    $text = "AquaIntelX Password Reset\n\nHello {$user['name']},\n\nYour verification code is: {$code}\n\nThis code expires in 10 minutes. If you did not request this, ignore this email.\n\n{$loginUrl}";

    try {
        aquaintelx_send_mail((string)$user['email'], (string)$user['name'], $subject, $html, $text);
    } catch (Throwable $e) {
        error_log('SMTP reset email failed: ' . $e->getMessage());
        jsonResponse(false, 'Could not send the verification email. Check Railway SMTP variables and Gmail App Password.');
    }

    publicResetResponse();
}

function handleResetPassword(PDO $pdo, array $body): void {
    $email = strtolower(trim((string)($body['email'] ?? '')));
    $code = trim((string)($body['code'] ?? ''));
    $newPassword = (string)($body['password'] ?? '');
    $confirmPassword = (string)($body['confirm_password'] ?? '');

    if ($email === '' || $code === '' || $newPassword === '' || $confirmPassword === '') {
        jsonResponse(false, 'Email, verification code, new password, and confirmation are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Invalid email format.');
    }

    if (!preg_match('/^\d{6}$/', $code)) {
        jsonResponse(false, 'Verification code must be 6 digits.');
    }

    if (strlen($newPassword) < 8) {
        jsonResponse(false, 'New password must be at least 8 characters.');
    }

    if ($newPassword !== $confirmPassword) {
        jsonResponse(false, 'Passwords do not match.');
    }

    createResetTableIfMissing($pdo);

    $stmt = $pdo->prepare('SELECT id, is_active FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !(bool)$user['is_active']) {
        jsonResponse(false, 'Invalid or expired verification code.');
    }

    $codeStmt = $pdo->prepare('SELECT id, code_hash FROM password_reset_codes
        WHERE email = :email
          AND user_id = :user_id
          AND used_at IS NULL
          AND expires_at > NOW()
        ORDER BY id DESC
        LIMIT 1');
    $codeStmt->execute([
        ':email' => $email,
        ':user_id' => (int)$user['id'],
    ]);
    $reset = $codeStmt->fetch();

    if (!$reset || !password_verify($code, (string)$reset['code_hash'])) {
        jsonResponse(false, 'Invalid or expired verification code.');
    }

    try {
        $pdo->beginTransaction();

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $update->execute([
            ':password' => $passwordHash,
            ':id' => (int)$user['id'],
        ]);

        $markUsed = $pdo->prepare('UPDATE password_reset_codes SET used_at = NOW() WHERE id = :id');
        $markUsed->execute([':id' => (int)$reset['id']]);

        // Invalidate all remaining unused codes for this email.
        $markOthers = $pdo->prepare('UPDATE password_reset_codes SET used_at = NOW() WHERE email = :email AND used_at IS NULL');
        $markOthers->execute([':email' => $email]);

        // Optional cleanup: prevent old failed login attempts from blocking the user after reset.
        try {
            $clearLogs = $pdo->prepare("DELETE FROM login_logs WHERE email = :email AND status = 'failed'");
            $clearLogs->execute([':email' => $email]);
        } catch (Throwable $ignored) {}

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    // Clear any currently active session after changing a password.
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    jsonResponse(true, 'Password reset successfully. You may now sign in.', [
        'redirect' => 'login.html?reset=1',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reset_password.html');
    exit;
}

$body = resetRequestBody();
$action = (string)($body['action'] ?? ($_GET['action'] ?? ''));

try {
    $pdo = getDB();
    $pdo->exec("SET time_zone = '+08:00'");

    if ($action === 'request_code') {
        handleRequestCode($pdo, $body);
    }

    if ($action === 'reset_password') {
        handleResetPassword($pdo, $body);
    }

    jsonResponse(false, 'Invalid reset action.');
} catch (PDOException $e) {
    error_log('DB error (reset_password): ' . $e->getMessage());
    jsonResponse(false, 'Database error. Check users and password_reset_codes tables.');
} catch (Throwable $e) {
    error_log('Reset password error: ' . $e->getMessage());
    jsonResponse(false, 'Password reset failed. Check server logs for details.');
}
