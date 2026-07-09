<?php
// ============================================================
// register.php — Creates a non-admin AquaIntelX user account
// ============================================================
declare(strict_types=1);

require_once 'config.php';

function jsonResponse(bool $success, string $message, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function cleanName(string $name): string {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return substr($name, 0, 100);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

$isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
$body = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;

if (!is_array($body)) {
    jsonResponse(false, 'Invalid request body.');
}

$name = cleanName((string)($body['name'] ?? ''));
$email = strtolower(trim((string)($body['email'] ?? '')));
$password = (string)($body['password'] ?? '');
$confirmPassword = (string)($body['confirm_password'] ?? '');

if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
    jsonResponse(false, 'Name, email, password, and confirmation are required.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format.');
}

if (strlen($password) < 8) {
    jsonResponse(false, 'Password must be at least 8 characters.');
}

if ($password !== $confirmPassword) {
    jsonResponse(false, 'Passwords do not match.');
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        jsonResponse(false, 'This email is already registered. Please sign in instead.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, role, is_active)
         VALUES (:name, :email, :password, :role, :is_active)'
    );

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $passwordHash,
        ':role' => 'viewer',
        ':is_active' => 1,
    ]);

    jsonResponse(true, 'Account created successfully.', [
        'redirect' => 'login.html?created=1',
        'role' => 'viewer',
    ]);
} catch (PDOException $e) {
    error_log('DB error (register): ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        jsonResponse(false, 'This email is already registered. Please sign in instead.');
    }

    jsonResponse(false, 'A database error occurred. Please check your users table and try again.');
}
