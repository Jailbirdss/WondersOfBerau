<?php
require_once 'auth.php';
require_admin();

$success_message = '';
$error_message = '';

$translator_enabled_query = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'translator_enabled'");
$translator_enabled = '1'; // default aktif
if ($translator_enabled_query && mysqli_num_rows($translator_enabled_query) > 0) {
    $setting = mysqli_fetch_assoc($translator_enabled_query);
    $translator_enabled = $setting['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'admin_settings')) {
        $error_message = 'Sesi kadaluarsa. Muat ulang halaman dan coba lagi.';
    } else {
        $new_translator_status = isset($_POST['translator_enabled']) ? '1' : '0';

        $check_query = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = 'translator_enabled'");

        if (mysqli_num_rows($check_query) > 0) {
            $update_query = "UPDATE settings SET setting_value = '$new_translator_status' WHERE setting_key = 'translator_enabled'";
            if (mysqli_query($conn, $update_query)) {
                $translator_enabled = $new_translator_status;
                $success_message = 'Pengaturan berhasil disimpan!';
            } else {
                $error_message = 'Gagal menyimpan pengaturan: ' . mysqli_error($conn);
            }
        } else {
            $insert_query = "INSERT INTO settings (setting_key, setting_value, description) VALUES ('translator_enabled', '$new_translator_status', 'Aktifkan/nonaktifkan fitur penerjemah bahasa (1 = aktif, 0 = nonaktif)')";
            if (mysqli_query($conn, $insert_query)) {
                $translator_enabled = $new_translator_status;
                $success_message = 'Pengaturan berhasil disimpan!';
            } else {
                $error_message = 'Gagal menyimpan pengaturan: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - Wonders of Berau</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-cog"></i> Pengaturan Sistem</h1>
                <div class="admin-user">
                    <span>Selamat datang, <strong><?php echo $_SESSION['admin_username']; ?></strong></span>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="settings-card settings-single">
                <div class="settings-title-row">
                    <h2><i class="fas fa-sliders-h"></i> Pengaturan Fitur Website</h2>
                </div>

                <form method="POST" action="" id="settingsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('admin_settings')); ?>">
                    <div class="setting-item">
                        <div class="setting-header">
                            <div class="setting-title">
                                <i class="fas fa-language"></i>
                                <div class="setting-texts">
                                    <span class="setting-name">Fitur Penerjemah Bahasa</span>
                                    <span class="setting-caption">Kontrol penerjemah otomatis menggunakan DeepL API.</span>
                                </div>
                                <span class="status-badge <?php echo $translator_enabled == '1' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $translator_enabled == '1' ? 'AKTIF' : 'NONAKTIF'; ?>
                                </span>
                            </div>
                            <label class="toggle-switch setting-toggle">
                                <input type="checkbox" name="translator_enabled" id="translatorToggle" <?php echo $translator_enabled == '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-description">
                            <p>Mengaktifkan atau menonaktifkan fitur penerjemah bahasa otomatis menggunakan DeepL API.</p>
                            <p>Ketika dinonaktifkan, pengunjung website tidak akan bisa mengganti bahasa dan API key tidak akan terpakai.</p>

                            <div class="info-box">
                                <div class="info-title">
                                    <i class="fas fa-info-circle"></i> Informasi:
                                </div>
                                <ul class="info-list">
                                    <li>Fitur ini menggunakan API DeepL gratis dengan batasan penggunaan.</li>
                                    <li>Menonaktifkan fitur ini akan menghemat kuota API key Anda.</li>
                                    <li>Perubahan langsung berlaku setelah tombol simpan diklik.</li>
                                    <li>Website otomatis kembali ke Bahasa Indonesia saat fitur dinonaktifkan.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="setting-actions">
                        <button type="submit" name="update_settings" class="btn-save">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showPopup(type, message) {
            const popup = document.createElement('div');
            popup.className = `popup-notification ${type}`;

            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            const title = type === 'success' ? 'Berhasil!' : 'Error!';

            popup.innerHTML = `
                <div class="popup-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="popup-content">
                    <strong>${title}</strong>
                    <span>${message}</span>
                </div>
                <button class="popup-close" onclick="closePopup(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;

            document.body.appendChild(popup);

            setTimeout(() => {
                closePopup(popup.querySelector('.popup-close'));
            }, 5000);
        }

        function closePopup(button) {
            const popup = button.closest('.popup-notification');
            popup.classList.add('hiding');
            setTimeout(() => {
                popup.remove();
            }, 400);
        }

        <?php if ($success_message): ?>
            showPopup('success', '<?php echo addslashes($success_message); ?>');
        <?php endif; ?>

        <?php if ($error_message): ?>
            showPopup('error', '<?php echo addslashes($error_message); ?>');
        <?php endif; ?>
    </script>
</body>

</html>