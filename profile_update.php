<?php
// ============================================================
// profile_update.php — Update logged-in user's profile
// Allows user/admin to update name, email, and profile image.
// ============================================================
declare(strict_types=1);

require_once 'auth_check.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

function profileJson(bool $success, string $message, array $extra = []): never {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

function profileColumnExists(PDO $pdo, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = :column
    ");
    $stmt->execute([':column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureProfileImageColumn(PDO $pdo): void {
    if (!profileColumnExists($pdo, 'profile_image')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER role");
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    profileJson(false, 'Method not allowed.');
}

$userId = (int)($currentUser['id'] ?? $_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    profileJson(false, 'Unauthenticated. Please log in again.');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));

if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    http_response_code(422);
    profileJson(false, 'Name must be between 2 and 100 characters.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    http_response_code(422);
    profileJson(false, 'Please enter a valid email address.');
}

$currentPassword = (string)($_POST['current_password'] ?? '');
$newPassword = (string)($_POST['new_password'] ?? '');
$confirmNewPassword = (string)($_POST['confirm_new_password'] ?? '');

$wantsPasswordChange = $currentPassword !== '' || $newPassword !== '' || $confirmNewPassword !== '';

if ($wantsPasswordChange) {
    if ($currentPassword === '' || $newPassword === '' || $confirmNewPassword === '') {
        http_response_code(422);
        profileJson(false, 'To change password, fill in current password, new password, and confirmation.');
    }

    if (strlen($newPassword) < 8) {
        http_response_code(422);
        profileJson(false, 'New password must be at least 8 characters.');
    }

    if ($newPassword !== $confirmNewPassword) {
        http_response_code(422);
        profileJson(false, 'New password and confirmation do not match.');
    }

    if ($currentPassword === $newPassword) {
        http_response_code(422);
        profileJson(false, 'New password must be different from your current password.');
    }
}

try {
    $pdo = getDB();
    ensureProfileImageColumn($pdo);

    $stmt = $pdo->prepare("SELECT id, password, profile_image FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        http_response_code(404);
        profileJson(false, 'User account was not found.');
    }

    $newPasswordHash = null;

    if ($wantsPasswordChange) {
        $storedPasswordHash = (string)($existingUser['password'] ?? '');

        if (!password_verify($currentPassword, $storedPasswordHash)) {
            http_response_code(403);
            profileJson(false, 'Current password is incorrect.');
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1");
    $emailCheck->execute([
        ':email' => $email,
        ':id' => $userId,
    ]);

    if ($emailCheck->fetch()) {
        http_response_code(409);
        profileJson(false, 'That email is already used by another account.');
    }

    $profileImagePath = trim((string)($existingUser['profile_image'] ?? ''));

    if (isset($_FILES['profile_image']) && is_array($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_image'];

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            http_response_code(422);
            profileJson(false, 'Image upload failed. Please choose another image.');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            http_response_code(422);
            profileJson(false, 'Profile image must be 2 MB or smaller.');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            http_response_code(422);
            profileJson(false, 'Invalid uploaded image.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            http_response_code(422);
            profileJson(false, 'Only JPG, PNG, GIF, and WebP images are allowed.');
        }

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_images';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            profileJson(false, 'Could not create profile image folder.');
        }

        $extension = $allowed[$mime];
        $filename = 'user_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            http_response_code(500);
            profileJson(false, 'Could not save profile image.');
        }

        $newRelativePath = 'uploads/profile_images/' . $filename;

        // Remove old local profile image after successful upload.
        if ($profileImagePath !== '' && str_starts_with($profileImagePath, 'uploads/profile_images/')) {
            $oldPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $profileImagePath);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $profileImagePath = $newRelativePath;
    }

    $updateSql = "
        UPDATE users
        SET name = :name,
            email = :email,
            profile_image = :profile_image";

    $updateParams = [
        ':name' => $name,
        ':email' => $email,
        ':profile_image' => $profileImagePath !== '' ? $profileImagePath : null,
        ':id' => $userId,
    ];

    if ($newPasswordHash !== null) {
        $updateSql .= ",
            password = :password";
        $updateParams[':password'] = $newPasswordHash;
    }

    $updateSql .= "
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($updateSql);
    $stmt->execute($updateParams);

    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['profile_image'] = $profileImagePath;

    profileJson(true, 'Profile updated successfully.', [
        'name' => $name,
        'email' => $email,
        'profile_image' => $profileImagePath,
        'password_changed' => $newPasswordHash !== null,
    ]);
} catch (Throwable $e) {
    error_log('profile_update error: ' . $e->getMessage());
    http_response_code(500);
    profileJson(false, 'Could not update profile. Check server logs.');
}
