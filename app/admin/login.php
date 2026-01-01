<?php
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'admin_login')) {
        $error = 'Sesi kadaluarsa, silakan muat ulang halaman.';
    } else {
        $username = clean_input($_POST['username']);
        $password = $_POST['password'];

        $query = "SELECT * FROM admin_users WHERE username = '$username'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);

            $password_valid = false;

            if (password_verify($password, $admin['password'])) {
                $password_valid = true;
            }
            elseif (md5($password) === $admin['password']) {
                $password_valid = true;
            }

            if ($password_valid) {
                if (isset($admin['status']) && $admin['status'] === 'nonaktif') {
                    $error = 'Akun admin ini dinonaktifkan. Hubungi admin lain.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $aid = intval($admin['id']);
                    mysqli_query($conn, "INSERT INTO admin_activity (admin_id, last_login) VALUES ($aid, NOW())
                        ON DUPLICATE KEY UPDATE last_login = NOW()");
                    $_SESSION['admin_logged_in'] = true;
                    redirect('index.php');
                }
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Wonders of Berau</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>Admin Panel</h1>
            <h2>Wonders of Berau</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('admin_login')); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
        </div>
    </div>
</body>

</html>