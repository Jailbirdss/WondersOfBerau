<?php
require_once 'auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account-settings.php');
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token, 'account_update')) {
    header('Location: account-settings.php?error=csrf');
    exit;
}

$action = $_POST['action'] ?? '';
$admin_id = $_SESSION['admin_id'];

if ($action === 'change_username') {
    $new_username = trim($_POST['new_username'] ?? '');
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_username) || empty($confirm_password)) {
        header('Location: account-settings.php?error=empty_fields');
        exit;
    }

    $query = mysqli_query($conn, "SELECT password FROM admin_users WHERE id = '$admin_id'");
    $admin = mysqli_fetch_assoc($query);

    $password_valid = false;
    if (password_verify($confirm_password, $admin['password'])) {
        $password_valid = true;
    } elseif (md5($confirm_password) === $admin['password']) {
        $password_valid = true;
    }

    if (!$password_valid) {
        header('Location: account-settings.php?error=wrong_password');
        exit;
    }

    $check_username = mysqli_query($conn, "SELECT id FROM admin_users WHERE username = '$new_username' AND id != '$admin_id'");
    if (mysqli_num_rows($check_username) > 0) {
        header('Location: account-settings.php?error=username_exists');
        exit;
    }

    $update = mysqli_query($conn, "UPDATE admin_users SET username = '$new_username' WHERE id = '$admin_id'");

    if ($update) {
        $_SESSION['admin_username'] = $new_username;
        header('Location: account-settings.php?success=username');
    } else {
        header('Location: account-settings.php?error=update_failed');
    }
    exit;
}

elseif ($action === 'change_password') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';


    if (empty($old_password) || empty($new_password) || empty($confirm_new_password)) {
        header('Location: account-settings.php?error=empty_fields');
        exit;
    }

    if (strlen($new_password) < 6) {
        header('Location: account-settings.php?error=password_too_short');
        exit;
    }

    if ($new_password !== $confirm_new_password) {
        header('Location: account-settings.php?error=password_mismatch');
        exit;
    }

    $query = mysqli_query($conn, "SELECT password FROM admin_users WHERE id = '$admin_id'");
    $admin = mysqli_fetch_assoc($query);

    $password_valid = false;
    if (password_verify($old_password, $admin['password'])) {
        $password_valid = true;
    } elseif (md5($old_password) === $admin['password']) {
        $password_valid = true;
    }

    if (!$password_valid) {
        header('Location: account-settings.php?error=wrong_password');
        exit;
    }

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);

    $update = mysqli_query($conn, "UPDATE admin_users SET password = '$hashed_password' WHERE id = '$admin_id'");

    if ($update) {
        header('Location: account-settings.php?success=password');
    } else {
        header('Location: account-settings.php?error=update_failed');
    }
    exit;
}

else {
    header('Location: account-settings.php');
    exit;
}
?>