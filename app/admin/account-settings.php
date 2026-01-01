<?php
require_once 'auth.php';
require_admin();

$admin_id = $_SESSION['admin_id'];
$query = mysqli_query($conn, "SELECT * FROM admin_users WHERE id = '$admin_id'");
$admin = mysqli_fetch_assoc($query);

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - Wonders of Berau</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-user-cog"></i> Pengaturan Akun</h1>
                <div class="admin-user">
                    <span>Selamat datang, <strong><?php echo $_SESSION['admin_username']; ?></strong></span>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="settings-container">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php
                        if ($_GET['success'] == 'username') {
                            echo 'Username berhasil diubah!';
                        } elseif ($_GET['success'] == 'password') {
                            echo 'Password berhasil diubah!';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php
                        if ($_GET['error'] == 'username_exists') {
                            echo 'Username sudah digunakan!';
                        } elseif ($_GET['error'] == 'wrong_password') {
                            echo 'Password lama tidak sesuai!';
                        } elseif ($_GET['error'] == 'password_mismatch') {
                            echo 'Konfirmasi password tidak cocok!';
                        } elseif ($_GET['error'] == 'empty_fields') {
                            echo 'Harap isi semua field yang diperlukan!';
                        } elseif ($_GET['error'] == 'password_too_short') {
                            echo 'Password minimal 6 karakter!';
                        } elseif ($_GET['error'] == 'csrf') {
                            echo 'Sesi kedaluwarsa atau token tidak valid. Silakan muat ulang halaman.';
                        } else {
                            echo 'Terjadi kesalahan. Silakan coba lagi!';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="settings-card">
                    <h2><i class="fas fa-user"></i> Ubah Username</h2>
                    <form method="POST" action="update-account.php">
                        <input type="hidden" name="action" value="change_username">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('account_update')); ?>">

                        <div class="form-group">
                            <label>Username Saat Ini</label>
                            <input type="text" value="<?php echo $admin['username']; ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label>Username Baru *</label>
                            <input type="text" name="new_username" required placeholder="Masukkan username baru">
                            <small>Username harus unik dan belum digunakan</small>
                        </div>

                        <div class="form-group">
                            <label>Password (untuk konfirmasi) *</label>
                            <div class="password-toggle">
                                <input type="password" name="confirm_password" id="confirm_pass_1" required
                                    placeholder="Masukkan password Anda">
                                <i class="fas fa-eye toggle-icon" onclick="togglePassword('confirm_pass_1', this)"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Simpan Username Baru
                        </button>
                    </form>
                </div>

                <div class="settings-card">
                    <h2><i class="fas fa-lock"></i> Ubah Password</h2>
                    <form method="POST" action="update-account.php">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('account_update')); ?>">

                        <div class="form-group">
                            <label>Password Lama *</label>
                            <div class="password-toggle">
                                <input type="password" name="old_password" id="old_pass" required
                                    placeholder="Masukkan password lama">
                                <i class="fas fa-eye toggle-icon" onclick="togglePassword('old_pass', this)"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Password Baru *</label>
                            <div class="password-toggle">
                                <input type="password" name="new_password" id="new_pass" required
                                    placeholder="Masukkan password baru" minlength="6">
                                <i class="fas fa-eye toggle-icon" onclick="togglePassword('new_pass', this)"></i>
                            </div>
                            <small>Minimal 6 karakter</small>
                        </div>

                        <div class="form-group">
                            <label>Konfirmasi Password Baru *</label>
                            <div class="password-toggle">
                                <input type="password" name="confirm_new_password" id="confirm_pass_2" required
                                    placeholder="Ulangi password baru">
                                <i class="fas fa-eye toggle-icon" onclick="togglePassword('confirm_pass_2', this)"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Simpan Password Baru
                        </button>
                    </form>
                </div>

                <div class="settings-card">
                    <h2><i class="fas fa-info-circle"></i> Informasi Akun</h2>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" value="<?php echo $admin['email']; ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Akun Dibuat Pada</label>
                        <input type="text" value="<?php echo date('d F Y H:i', strtotime($admin['created_at'])); ?>"
                            disabled>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>