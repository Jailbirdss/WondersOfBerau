<?php
require_once 'auth.php';
require_admin();

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
$current_admin_id = intval($_SESSION['admin_id']);
$current_admin_is_super = 0;
$current_admin_username = $_SESSION['admin_username'] ?? '';
$card_anchor = '#list-card';
$allowed_tabs = ['admin', 'activity', 'audit'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs, true) ? $_GET['tab'] : 'admin';
$csrf_token_admin = csrf_token('admin_users');

$create_activity = "CREATE TABLE IF NOT EXISTS admin_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL UNIQUE,
    last_login DATETIME DEFAULT NULL,
    last_logout DATETIME DEFAULT NULL,
    last_password_change DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id)
)";
@mysqli_query($conn, $create_activity);
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM admin_activity LIKE 'last_logout'");
if ($col_check && mysqli_num_rows($col_check) === 0) {
    @mysqli_query($conn, "ALTER TABLE admin_activity ADD COLUMN last_logout DATETIME DEFAULT NULL AFTER last_login");
}

$current_admin_query = mysqli_query($conn, "SELECT is_super FROM admin_users WHERE id = $current_admin_id LIMIT 1");
if ($current_admin_query && mysqli_num_rows($current_admin_query) > 0) {
    $row_admin = mysqli_fetch_assoc($current_admin_query);
    $current_admin_is_super = intval($row_admin['is_super']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'admin_users')) {
        $_SESSION['error_message'] = 'Sesi kadaluarsa, silakan muat ulang halaman.';
        header('Location: admin-users.php');
        exit;
    }

    if ($current_admin_is_super !== 1) {
        $_SESSION['error_message'] = 'Hanya super admin yang boleh mengubah status admin.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_id = intval($_POST['admin_id'] ?? 0);
    $current_admin_id = intval($_SESSION['admin_id']);

    if ($admin_id <= 0) {
        $_SESSION['error_message'] = 'Admin tidak valid.';
        header('Location: admin-users.php');
        exit;
    }

    if ($admin_id === $current_admin_id) {
        $_SESSION['error_message'] = 'Tidak dapat menonaktifkan atau mengaktifkan akun yang sedang login.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_check = mysqli_query($conn, "SELECT username, status, is_super FROM admin_users WHERE id = $admin_id LIMIT 1");
    if (!$admin_check || mysqli_num_rows($admin_check) === 0) {
        $_SESSION['error_message'] = 'Akun admin tidak ditemukan atau sudah dihapus.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_data = mysqli_fetch_assoc($admin_check);

    if (intval($admin_data['is_super']) === 1) {
        $_SESSION['error_message'] = 'Akun super admin tidak boleh diubah statusnya.';
        header('Location: admin-users.php');
        exit;
    }

    $new_status = ($admin_data['status'] === 'aktif') ? 'nonaktif' : 'aktif';
    $toggle_ok = mysqli_query($conn, "UPDATE admin_users SET status = '$new_status' WHERE id = $admin_id");

    if ($toggle_ok) {
        $status_msg = $new_status === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';
        $toggled_name = htmlspecialchars($admin_data['username'], ENT_QUOTES, 'UTF-8');
        $_SESSION['success_message'] = "Akun admin \"$toggled_name\" berhasil $status_msg.";

        log_audit('toggle_admin_status', "Status admin \"{$admin_data['username']}\" $status_msg.", $admin_id, $admin_data['username']);
    } else {
        $_SESSION['error_message'] = 'Gagal mengubah status admin: ' . mysqli_error($conn);
    }

    header('Location: admin-users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_super'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'admin_users')) {
        $_SESSION['error_message'] = 'Sesi kadaluarsa, silakan muat ulang halaman.';
        header('Location: admin-users.php');
        exit;
    }

    if ($current_admin_is_super !== 1) {
        $_SESSION['error_message'] = 'Hanya super admin yang boleh mengubah hak super.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_id = intval($_POST['admin_id'] ?? 0);
    $current_admin_id = intval($_SESSION['admin_id']);
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($admin_id <= 0 || $admin_id === $current_admin_id) {
        $_SESSION['error_message'] = 'Akun target tidak valid.';
        header('Location: admin-users.php');
        exit;
    }

    if (empty($confirm_password)) {
        $_SESSION['error_message'] = 'Harap masukkan password Anda untuk konfirmasi.';
        header('Location: admin-users.php');
        exit;
    }

    $current_admin_query = mysqli_query($conn, "SELECT password FROM admin_users WHERE id = $current_admin_id LIMIT 1");
    if (!$current_admin_query || mysqli_num_rows($current_admin_query) === 0) {
        $_SESSION['error_message'] = 'Gagal memverifikasi password saat ini.';
        header('Location: admin-users.php');
        exit;
    }
    $current_admin = mysqli_fetch_assoc($current_admin_query);
    $password_valid = false;
    if (password_verify($confirm_password, $current_admin['password'])) {
        $password_valid = true;
    } elseif (md5($confirm_password) === $current_admin['password']) {
        $password_valid = true;
    }

    if (!$password_valid) {
        $_SESSION['error_message'] = 'Password konfirmasi salah.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_check = mysqli_query($conn, "SELECT username, is_super FROM admin_users WHERE id = $admin_id LIMIT 1");
    if (!$admin_check || mysqli_num_rows($admin_check) === 0) {
        $_SESSION['error_message'] = 'Akun admin tidak ditemukan atau sudah dihapus.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_data = mysqli_fetch_assoc($admin_check);
    $is_super_now = intval($admin_data['is_super']) === 1;
    $new_is_super = $is_super_now ? 0 : 1;

    if ($is_super_now && $admin_id !== $current_admin_id) {
        $_SESSION['error_message'] = 'Tidak boleh mencabut hak super admin lain.';
        header('Location: admin-users.php');
        exit;
    }

    if ($is_super_now) {
        $super_count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_users WHERE is_super = 1");
        $super_count_row = $super_count_query ? mysqli_fetch_assoc($super_count_query) : ['total' => 0];
        $super_count = intval($super_count_row['total']);
        if ($super_count <= 1) {
            $_SESSION['error_message'] = 'Tidak bisa mencabut hak super admin terakhir.';
            header('Location: admin-users.php');
            exit;
        }
    }

    $toggle_super_ok = mysqli_query($conn, "UPDATE admin_users SET is_super = $new_is_super WHERE id = $admin_id");

    if ($toggle_super_ok) {
        $target_name = htmlspecialchars($admin_data['username'], ENT_QUOTES, 'UTF-8');
        if ($new_is_super === 1) {
            $_SESSION['success_message'] = "Hak super admin diberikan ke \"$target_name\".";
            log_audit('grant_super_admin', "Memberikan hak super admin ke \"{$admin_data['username']}\".", $admin_id, $admin_data['username']);
        } else {
            $_SESSION['success_message'] = "Hak super admin dicabut dari \"$target_name\".";
            log_audit('revoke_super_admin', "Mencabut hak super admin dari \"{$admin_data['username']}\".", $admin_id, $admin_data['username']);
        }
    } else {
        $_SESSION['error_message'] = 'Gagal mengubah hak super admin: ' . mysqli_error($conn);
    }

    header('Location: admin-users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'admin_users')) {
        $_SESSION['error_message'] = 'Sesi kadaluarsa, silakan muat ulang halaman.';
        header('Location: admin-users.php');
        exit;
    }

    if ($current_admin_is_super !== 1) {
        $_SESSION['error_message'] = 'Hanya super admin yang boleh menghapus admin.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_id = intval($_POST['admin_id'] ?? 0);
    $current_admin_id = intval($_SESSION['admin_id']);
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($admin_id <= 0) {
        $_SESSION['error_message'] = 'Admin tidak valid.';
        header('Location: admin-users.php');
        exit;
    }

    if ($admin_id === $current_admin_id) {
        $_SESSION['error_message'] = 'Tidak dapat menghapus akun yang sedang login.';
        header('Location: admin-users.php');
        exit;
    }

    if (empty($confirm_password)) {
        $_SESSION['error_message'] = 'Harap masukkan password Anda untuk konfirmasi penghapusan.';
        header('Location: admin-users.php');
        exit;
    }

    $current_admin_query = mysqli_query($conn, "SELECT password FROM admin_users WHERE id = $current_admin_id LIMIT 1");
    if (!$current_admin_query || mysqli_num_rows($current_admin_query) === 0) {
        $_SESSION['error_message'] = 'Gagal memverifikasi password saat ini.';
        header('Location: admin-users.php');
        exit;
    }

    $current_admin = mysqli_fetch_assoc($current_admin_query);
    $password_valid = false;
    if (password_verify($confirm_password, $current_admin['password'])) {
        $password_valid = true;
    } elseif (md5($confirm_password) === $current_admin['password']) {
        $password_valid = true;
    }

    if (!$password_valid) {
        $_SESSION['error_message'] = 'Password konfirmasi salah.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_check = mysqli_query($conn, "SELECT username, is_super FROM admin_users WHERE id = $admin_id LIMIT 1");
    if (!$admin_check || mysqli_num_rows($admin_check) === 0) {
        $_SESSION['error_message'] = 'Akun admin tidak ditemukan atau sudah dihapus.';
        header('Location: admin-users.php');
        exit;
    }

    $admin_data = mysqli_fetch_assoc($admin_check);

    if (intval($admin_data['is_super']) === 1) {
        $_SESSION['error_message'] = 'Akun super admin tidak boleh dihapus.';
        header('Location: admin-users.php');
        exit;
    }
    $delete_ok = mysqli_query($conn, "DELETE FROM admin_users WHERE id = $admin_id");

    if ($delete_ok) {
        $deleted_name = htmlspecialchars($admin_data['username'], ENT_QUOTES, 'UTF-8');
        $_SESSION['success_message'] = "Akun admin \"$deleted_name\" berhasil dihapus.";

        log_audit('delete_admin', "Menghapus admin \"{$admin_data['username']}\".", $admin_id, $admin_data['username']);
    } else {
        $_SESSION['error_message'] = 'Gagal menghapus admin: ' . mysqli_error($conn);
    }

    header('Location: admin-users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'admin_users')) {
        $_SESSION['error_message'] = 'Sesi kadaluarsa, silakan muat ulang halaman.';
        header('Location: admin-users.php');
        exit;
    }

    if ($current_admin_is_super !== 1) {
        $_SESSION['error_message'] = 'Hanya super admin yang boleh membuat akun admin baru.';
        header('Location: admin-users.php');
        exit;
    }

    $username = clean_input(trim($_POST['username'] ?? ''));
    $email_input = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email_input) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_message'] = 'Harap lengkapi semua field yang wajib diisi.';
        header('Location: admin-users.php');
        exit;
    }

    if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Format email tidak valid.';
        header('Location: admin-users.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['error_message'] = 'Password minimal 6 karakter.';
        header('Location: admin-users.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = 'Konfirmasi password tidak cocok.';
        header('Location: admin-users.php');
        exit;
    }

    $email = clean_input($email_input);

    $check_username = mysqli_query($conn, "SELECT id FROM admin_users WHERE username = '$username' LIMIT 1");
    if ($check_username && mysqli_num_rows($check_username) > 0) {
        $_SESSION['error_message'] = 'Username sudah digunakan. Gunakan username lain.';
        header('Location: admin-users.php');
        exit;
    }

    $check_email = mysqli_query($conn, "SELECT id FROM admin_users WHERE email = '$email' LIMIT 1");
    if ($check_email && mysqli_num_rows($check_email) > 0) {
        $_SESSION['error_message'] = 'Email sudah terdaftar untuk akun admin lain.';
        header('Location: admin-users.php');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    $insert = mysqli_query($conn, "INSERT INTO admin_users (username, password, email, status, is_super) VALUES ('$username', '$hashed_password', '$email', 'aktif', 0)");

    if ($insert) {
        $_SESSION['success_message'] = 'Akun admin baru berhasil dibuat.';

        log_audit('create_admin', "Membuat akun admin \"$username\".", mysqli_insert_id($conn), $username);
    } else {
        $_SESSION['error_message'] = 'Gagal membuat akun admin baru: ' . mysqli_error($conn);
    }

    header('Location: admin-users.php');
    exit;
}

$search = isset($_GET['search']) ? clean_input(trim($_GET['search'])) : '';
$status_filter = isset($_GET['status']) ? clean_input(trim($_GET['status'])) : '';
$order = 'ASC';
$activity_search = isset($_GET['activity_search']) ? clean_input(trim($_GET['activity_search'])) : '';
$activity_role = isset($_GET['activity_role']) ? clean_input(trim($_GET['activity_role'])) : '';
if (!in_array($activity_role, ['super', 'admin'], true)) {
    $activity_role = '';
}
$activity_sort = 'username';
$activity_order = 'ASC';

$audit_search = isset($_GET['audit_search']) ? clean_input(trim($_GET['audit_search'])) : '';
$audit_role = isset($_GET['audit_role']) ? clean_input(trim($_GET['audit_role'])) : '';
if (!in_array($audit_role, ['super', 'admin'], true)) {
    $audit_role = '';
}

$query_base_params = [];
if (!empty($search)) {
    $query_base_params['search'] = $search;
}
if (!empty($status_filter)) {
    $query_base_params['status'] = $status_filter;
}
if (!empty($activity_search)) {
    $query_base_params['activity_search'] = $activity_search;
}
if (!empty($activity_role)) {
    $query_base_params['activity_role'] = $activity_role;
}
if (!empty($audit_search)) {
    $query_base_params['audit_search'] = $audit_search;
}
if (!empty($audit_role)) {
    $query_base_params['audit_role'] = $audit_role;
}
$query_base_params['tab'] = $tab;

$admin_has_filter = (!empty($search) || !empty($status_filter));
$activity_has_filter = (!empty($activity_search) || !empty($activity_role));
$audit_has_filter = (!empty($audit_search) || !empty($audit_role));

$admin_reset_link = 'admin-users.php?' . http_build_query(array_merge(array_diff_key($query_base_params, ['search' => '', 'status' => '']), ['tab' => 'admin'])) . $card_anchor;
$activity_reset_link = 'admin-users.php?' . http_build_query(array_merge(array_diff_key($query_base_params, ['activity_search' => '', 'activity_role' => '']), ['tab' => 'activity'])) . $card_anchor;
$audit_reset_link = 'admin-users.php?' . http_build_query(array_merge(array_diff_key($query_base_params, ['audit_search' => '', 'audit_role' => '']), ['tab' => 'audit'])) . $card_anchor;

$count_active = 0;
$count_inactive = 0;
$count_query = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM admin_users GROUP BY status");
if ($count_query) {
    while ($c = mysqli_fetch_assoc($count_query)) {
        if ($c['status'] === 'aktif') {
            $count_active = intval($c['total']);
        } elseif ($c['status'] === 'nonaktif') {
            $count_inactive = intval($c['total']);
        }
    }
}

$where = [];
if (!empty($search)) {
    $where[] = "(username LIKE '%$search%' OR email LIKE '%$search%')";
}
if (!empty($status_filter) && in_array($status_filter, ['aktif', 'nonaktif'])) {
    $where[] = "status = '$status_filter'";
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$total_rows = 0;
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_users $where_sql");
if ($total_query) {
    $total_rows = intval(mysqli_fetch_assoc($total_query)['total']);
}
$total_pages = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

$admins = mysqli_query($conn, "SELECT u.id, u.username, u.email, u.created_at, u.status, u.is_super, a.last_login, a.last_password_change
FROM admin_users u
LEFT JOIN admin_activity a ON a.admin_id = u.id
$where_sql
ORDER BY u.is_super DESC, u.created_at $order
LIMIT $offset, $per_page");

$activity_per_page = 10;
$activity_page = isset($_GET['activity_page']) ? max(1, intval($_GET['activity_page'])) : 1;

$activity_total_rows = 0;
$activity_sort_sql = "COALESCE(a.last_login, '1970-01-01 00:00:00')";
$activity_order = 'DESC';

$activity_where = [];
if (!empty($activity_search)) {
    $search_like = "'%" . mysqli_real_escape_string($conn, $activity_search) . "%'";
    $activity_where[] = "u.username LIKE $search_like";
}
if (in_array($activity_role, ['super', 'admin'], true)) {
    $activity_where[] = "COALESCE(u.is_super, 0) = " . ($activity_role === 'super' ? 1 : 0);
}
$activity_where_sql = !empty($activity_where) ? 'WHERE ' . implode(' AND ', $activity_where) : '';

$activity_total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_users u LEFT JOIN admin_activity a ON a.admin_id = u.id $activity_where_sql");
if ($activity_total_query) {
    $activity_total_rows = intval(mysqli_fetch_assoc($activity_total_query)['total']);
}
$activity_total_pages = max(1, ceil($activity_total_rows / $activity_per_page));
if ($activity_page > $activity_total_pages) {
    $activity_page = $activity_total_pages;
}
$activity_offset = ($activity_page - 1) * $activity_per_page;

$audit_per_page = 10;
$audit_page = isset($_GET['audit_page']) ? max(1, intval($_GET['audit_page'])) : 1;

$audit_search_sql = mysqli_real_escape_string($conn, $audit_search);
$audit_where = [];
if (!empty($audit_search_sql)) {
    $like = "'%" . $audit_search_sql . "%'";
    $audit_where[] = "(actor_username LIKE $like OR target_username LIKE $like OR action LIKE $like OR details LIKE $like)";
}
if (in_array($audit_role, ['super', 'admin'], true)) {
    $audit_where[] = "COALESCE(u1.is_super, 0) = " . ($audit_role === 'super' ? 1 : 0);
}
$audit_where_sql = !empty($audit_where) ? 'WHERE ' . implode(' AND ', $audit_where) : '';

$audit_total_rows = 0;
$audit_total_query = mysqli_query($conn, "SELECT COUNT(*) as total
    FROM admin_audit_logs l
    LEFT JOIN admin_users u1 ON l.actor_admin_id = u1.id
    $audit_where_sql");
if ($audit_total_query) {
    $audit_total_rows = intval(mysqli_fetch_assoc($audit_total_query)['total']);
}
$audit_total_pages = max(1, ceil($audit_total_rows / $audit_per_page));
if ($audit_page > $audit_total_pages) {
    $audit_page = $audit_total_pages;
}
$audit_offset = ($audit_page - 1) * $audit_per_page;

$audit_logs = mysqli_query($conn, "SELECT l.actor_username, l.target_username, l.action, l.details, l.created_at,
    COALESCE(u1.is_super, 0) as actor_is_super, COALESCE(u2.is_super, 0) as target_is_super
    FROM admin_audit_logs l
    LEFT JOIN admin_users u1 ON l.actor_admin_id = u1.id
    LEFT JOIN admin_users u2 ON l.target_admin_id = u2.id
    $audit_where_sql
    ORDER BY l.created_at DESC, COALESCE(u1.is_super,0) DESC
    LIMIT $audit_offset, $audit_per_page");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - Wonders of Berau</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-users-cog"></i> Kelola Admin</h1>
                <div class="admin-user">
                    <span>Selamat datang, <strong><?php echo $_SESSION['admin_username']; ?></strong></span>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <?php if ($tab === 'admin'): ?>
                <?php if ($current_admin_is_super === 1): ?>
                    <div class="settings-card">
                        <h2><i class="fas fa-user-plus"></i> Tambah Akun Admin Baru</h2>
                        <p style="margin-bottom: 15px; color: #6b7280;">Gunakan form ini untuk membuat akun admin baru.
                            Password akan disimpan dengan enkripsi Bcrypt.</p>

                        <form method="POST" action="admin-users.php">
                            <input type="hidden" name="create_admin" value="1">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" required placeholder="Masukkan username unik">
                            </div>

                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" required placeholder="contoh@email.com">
                            </div>

                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" name="password" required minlength="6"
                                    placeholder="Minimal 6 karakter">
                            </div>

                            <div class="form-group">
                                <label>Konfirmasi Password *</label>
                                <input type="password" name="confirm_password" required placeholder="Ulangi password">
                            </div>

                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Buat Akun Admin
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="settings-card" id="filter-card">
                    <h2><i class="fas fa-filter"></i> Filter Akun Admin</h2>
                    <form method="GET" action="admin-users.php<?php echo $card_anchor; ?>" class="filter-form">
                        <div class="form-group">
                            <label>Cari Username / Email</label>
                            <input type="text" name="search" placeholder="Ketik username atau email"
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">Semua</option>
                                <option value="aktif" <?php echo $status_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo $status_filter === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                        </div>
                        <input type="hidden" name="tab" value="admin">
                        <div style="display:flex; gap:10px; margin-top:10px;">
                            <button type="submit" class="btn btn-success"><i class="fas fa-search"></i> Cari </button>
                            <a href="<?php echo htmlspecialchars($admin_reset_link); ?>" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                <div class="settings-card" id="list-card">
                    <div class="tab-header-row">
                        <?php if ($tab === 'admin'): ?>
                            <h2><i class="fas fa-users"></i> Daftar Akun Admin</h2>
                        <?php elseif ($tab === 'activity'): ?>
                            <h2><i class="fas fa-user-check"></i> Aktivitas Login / Password</h2>
                        <?php else: ?>
                            <h2><i class="fas fa-clipboard-list"></i> Audit Log</h2>
                        <?php endif; ?>
                        <div class="tab-switch">
                            <?php
                            $admin_tab_link = '?' . http_build_query(array_merge($query_base_params, ['tab' => 'admin', 'page' => 1])) . $card_anchor;
                            $activity_tab_link = '?' . http_build_query(array_merge($query_base_params, ['tab' => 'activity', 'activity_page' => 1])) . $card_anchor;
                            $audit_tab_link = '?' . http_build_query(array_merge($query_base_params, ['tab' => 'audit', 'audit_page' => 1])) . $card_anchor;
                            ?>
                            <a href="<?php echo $admin_tab_link; ?>" class="tab-button <?php echo $tab === 'admin' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Data Admin</a>
                            <a href="<?php echo $activity_tab_link; ?>" class="tab-button <?php echo $tab === 'activity' ? 'active' : ''; ?>"><i class="fas fa-user-check"></i> Aktivitas Login/Password</a>
                            <a href="<?php echo $audit_tab_link; ?>" class="tab-button <?php echo $tab === 'audit' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Audit Log</a>
                        </div>
                    </div>

                    <?php if ($tab === 'admin'): ?>
                    <div class="mini-stats" style="margin-bottom: 15px;">
                        <div class="mini-stat-card" style="flex: 0 0 200px;">
                            <div class="mini-stat-icon" style="background: linear-gradient(135deg,#34d399,#10b981);">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="mini-stat-info">
                                <h4><?php echo $count_active; ?></h4>
                                <p>Admin Aktif</p>
                            </div>
                        </div>
                        <div class="mini-stat-card" style="flex: 0 0 200px;">
                            <div class="mini-stat-icon" style="background: linear-gradient(135deg,#f6ad55,#ed8936);">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <div class="mini-stat-info">
                                <h4><?php echo $count_inactive; ?></h4>
                                <p>Admin Nonaktif</p>
                            </div>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Dibuat</th>
                                    <th>Status</th>
                                    <?php if ($current_admin_is_super === 1): ?>
                                        <th style="width: 160px;">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($admins && mysqli_num_rows($admins) > 0): ?>
                                    <?php $row_no = 1; ?>
                                    <?php while ($admin = mysqli_fetch_assoc($admins)): ?>
                                            <?php
                                            $display_no = ($order === 'DESC')
                                                ? ($total_rows - ($offset + $row_no - 1))
                                                : ($offset + $row_no);
                                            $row_no++;
                                            ?>
                                            <tr>
                                                <td><?php echo $display_no; ?></td>
                                            <td>
                                                <div class="admin-username-wrap">
                                                    <?php if (isset($admin['is_super']) && intval($admin['is_super']) === 1): ?>
                                                        <span class="badge-super"><i class="fas fa-crown"></i> Super Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge-admin"><i class="fas fa-user-shield"></i> Admin</span>
                                                    <?php endif; ?>
                                                    <div class="admin-name-row">
                                                        <span class="admin-name"><?php echo htmlspecialchars($admin['username']); ?></span>
                                                        <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                            <span class="badge badge-success badge-anda">Anda</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($admin['created_at'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $admin['status'] === 'aktif' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($admin['status']); ?>
                                                </span>
                                            </td>
                                            <?php if ($current_admin_is_super === 1): ?>
                                                <td class="action-buttons">
                                                    <?php
                                                    $target_is_super = isset($admin['is_super']) && intval($admin['is_super']) === 1;
                                                    $is_self = $admin['id'] == $_SESSION['admin_id'];
                                                    ?>
                                                    <?php if (!$is_self): ?>
                                                        <div class="action-stack">
                                                            <?php if (!$target_is_super): ?>
                                                                <form method="POST" action="admin-users.php"
                                                                    data-confirm="<?php echo $admin['status'] === 'aktif' ? 'Nonaktifkan admin ini?' : 'Aktifkan admin ini?'; ?>">
                                                                    <input type="hidden" name="toggle_status" value="1">
                                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">
                                                                    <button type="submit" class="btn btn-sm <?php echo $admin['status'] === 'aktif' ? 'btn-warning' : 'btn-success'; ?>">
                                                                        <i class="fas fa-toggle-on"></i>
                                                                        <?php echo $admin['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                                                    </button>
                                                                </form>

                                                                <form method="POST" action="admin-users.php"
                                                                    data-confirm="Jadikan super admin?"
                                                                    data-require-password="1">
                                                                    <input type="hidden" name="toggle_super" value="1">
                                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">
                                                                    <input type="password" name="confirm_password" class="confirm-password-input" placeholder="Password Anda" style="display:none;">
                                                                    <button type="submit" class="btn btn-sm btn-info">
                                                                        <i class="fas fa-crown"></i> Jadikan Super
                                                                    </button>
                                                                </form>

                                                                <form method="POST" action="admin-users.php"
                                                                    data-confirm="Hapus admin ini?" class="delete-admin-form"
                                                                    data-require-password="1">
                                                                    <input type="hidden" name="delete_admin" value="1">
                                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">
                                                                    <input type="password" name="confirm_password" class="confirm-password-input" placeholder="Password Anda" style="display:none;">
                                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                                        <i class="fas fa-trash"></i> Hapus
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <div class="action-info">
                                                                    <span class="badge badge-info"><i class="fas fa-crown"></i> Super admin</span>
                                                                    <small class="muted-text">Tidak bisa dicabut oleh super admin lain.</small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="action-info">
                                                            <span class="badge badge-info"><i class="fas fa-shield-alt"></i> Akun Anda</span>
                                                            <small class="muted-text">Anda tidak dapat mengubah atau menghapus akun sendiri.</small>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <?php
                                    $admin_empty_message = 'Belum ada akun admin lain.';
                                    if ($admin_has_filter) {
                                        if (!empty($search)) {
                                            $admin_empty_message = 'Tidak ada akun admin dengan nama atau email "' . htmlspecialchars($search) . '".';
                                        } else {
                                            $admin_empty_message = 'Tidak ada akun admin sesuai filter.';
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="<?php echo $current_admin_is_super === 1 ? '6' : '5'; ?>" style="text-align:center;">
                                            <div class="empty-state">
                                                <p><?php echo $admin_empty_message; ?></p>
                                                <a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars($admin_reset_link); ?>"><i class="fas fa-redo"></i> Reset Filter</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination">
                        <?php
                        $query_base = array_merge($query_base_params, ['tab' => 'admin']);
                        ?>
                        <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>"
                            href="<?php echo $page > 1 ? '?' . http_build_query(array_merge($query_base, ['page' => 1])) . $card_anchor : '#'; ?>">&#171;</a>
                        <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>"
                            href="<?php echo $page > 1 ? '?' . http_build_query(array_merge($query_base, ['page' => $page - 1])) . $card_anchor : '#'; ?>">&#8249;</a>
                        <span class="page-info">Halaman <?php echo $page; ?> / <?php echo $total_pages; ?></span>
                        <a class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"
                            href="<?php echo $page < $total_pages ? '?' . http_build_query(array_merge($query_base, ['page' => $page + 1])) . $card_anchor : '#'; ?>">&#8250;</a>
                        <a class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"
                            href="<?php echo $page < $total_pages ? '?' . http_build_query(array_merge($query_base, ['page' => $total_pages])) . $card_anchor : '#'; ?>">&#187;</a>
                    </div>
                <?php elseif ($tab === 'activity'): ?>
                        <form method="GET" action="admin-users.php<?php echo $card_anchor; ?>" class="filter-form" style="margin-bottom: 15px;">
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Cari Username</label>
                                <input type="text" name="activity_search" placeholder="Cari username"
                                    value="<?php echo htmlspecialchars($activity_search); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Tipe Admin</label>
                                <select name="activity_role">
                                    <option value="">Semua</option>
                                    <option value="super" <?php echo $activity_role === 'super' ? 'selected' : ''; ?>>Super Admin</option>
                                    <option value="admin" <?php echo $activity_role === 'admin' ? 'selected' : ''; ?>>Admin Biasa</option>
                                </select>
                            </div>
                            <input type="hidden" name="tab" value="activity">
                            <input type="hidden" name="page" value="<?php echo $page; ?>">
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success btn-equal"><i class="fas fa-search"></i> Cari</button>
                            <a href="<?php echo htmlspecialchars($activity_reset_link); ?>"
                                class="btn btn-secondary btn-equal"><i class="fas fa-redo"></i> Reset</a>
                        </form>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                    <th>Username</th>
                                    <th>Last Login</th>
                                    <th>Last Logout</th>
                                    <th>Last Password Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $activity_res = mysqli_query($conn, "SELECT u.username, u.is_super, a.last_login, a.last_logout, a.last_password_change FROM admin_users u LEFT JOIN admin_activity a ON a.admin_id = u.id $activity_where_sql ORDER BY u.is_super DESC, COALESCE(a.last_login, '1970-01-01 00:00:00') DESC, u.username ASC LIMIT $activity_offset, $activity_per_page");
                                    if ($activity_res && mysqli_num_rows($activity_res) > 0):
                                        $a_no = $activity_offset + 1;
                                        while ($act = mysqli_fetch_assoc($activity_res)):
                                    ?>
                                            <tr>
                                                <td><?php echo $a_no++; ?></td>
                                                <td>
                                                    <?php if (isset($act['is_super']) && intval($act['is_super']) === 1): ?>
                                                        <span class="badge-super"><i class="fas fa-crown"></i> Super Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge-admin"><i class="fas fa-user-shield"></i> Admin</span>
                                                    <?php endif; ?>
                                                    <strong><?php echo htmlspecialchars($act['username']); ?></strong>
                                                </td>
                                                <td><?php echo !empty($act['last_login']) ? date('d M Y H:i', strtotime($act['last_login'])) : '-'; ?></td>
                                                <td><?php echo !empty($act['last_logout']) ? date('d M Y H:i', strtotime($act['last_logout'])) : '-'; ?></td>
                                                <td><?php echo !empty($act['last_password_change']) ? date('d M Y H:i', strtotime($act['last_password_change'])) : '-'; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <?php
                                        $activity_empty_message = 'Belum ada data aktivitas.';
                                        if ($activity_has_filter) {
                                            if (!empty($activity_search)) {
                                                $activity_empty_message = 'Tidak ada aktivitas untuk username "' . htmlspecialchars($activity_search) . '".';
                                            } else {
                                                $activity_empty_message = 'Tidak ada aktivitas sesuai filter ini.';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center;">
                                                <div class="empty-state">
                                                    <p><?php echo $activity_empty_message; ?></p>
                                                    <a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars($activity_reset_link); ?>"><i class="fas fa-redo"></i> Reset Filter</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">
                            <?php
                            $activity_query_base = array_merge($query_base_params, ['page' => $page, 'tab' => 'activity']);
                            ?>
                            <a class="page-link <?php echo $activity_page <= 1 ? 'disabled' : ''; ?>"
                                href="<?php echo $activity_page > 1 ? '?' . http_build_query(array_merge($activity_query_base, ['activity_page' => 1])) . $card_anchor : '#'; ?>">&#171;</a>
                            <a class="page-link <?php echo $activity_page <= 1 ? 'disabled' : ''; ?>"
                                href="<?php echo $activity_page > 1 ? '?' . http_build_query(array_merge($activity_query_base, ['activity_page' => $activity_page - 1])) . $card_anchor : '#'; ?>">&#8249;</a>
                            <span class="page-info">Halaman <?php echo $activity_page; ?> / <?php echo $activity_total_pages; ?></span>
                            <a class="page-link <?php echo $activity_page >= $activity_total_pages ? 'disabled' : ''; ?>"
                                href="<?php echo $activity_page < $activity_total_pages ? '?' . http_build_query(array_merge($activity_query_base, ['activity_page' => $activity_page + 1])) . $card_anchor : '#'; ?>">&#8250;</a>
                            <a class="page-link <?php echo $activity_page >= $activity_total_pages ? 'disabled' : ''; ?>"
                                href="<?php echo $activity_page < $activity_total_pages ? '?' . http_build_query(array_merge($activity_query_base, ['activity_page' => $activity_total_pages])) . $card_anchor : '#'; ?>">&#187;</a>
                        </div>
                    <?php else: ?>
                        <form method="GET" action="admin-users.php<?php echo $card_anchor; ?>" class="filter-form" style="margin-bottom: 15px;">
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Cari Audit Log</label>
                                <input type="text" name="audit_search" placeholder="Cari aktor, target, aksi, atau detail"
                                    value="<?php echo htmlspecialchars($audit_search); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Tipe Aktor</label>
                                <select name="audit_role">
                                    <option value="">Semua</option>
                                    <option value="super" <?php echo $audit_role === 'super' ? 'selected' : ''; ?>>Super Admin</option>
                                    <option value="admin" <?php echo $audit_role === 'admin' ? 'selected' : ''; ?>>Admin Biasa</option>
                                </select>
                            </div>
                            <input type="hidden" name="tab" value="audit">
                            <input type="hidden" name="page" value="<?php echo $page; ?>">
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success btn-equal"><i class="fas fa-search"></i> Cari</button>
                            <a href="<?php echo htmlspecialchars($audit_reset_link); ?>"
                                class="btn btn-secondary btn-equal"><i class="fas fa-redo"></i> Reset</a>
                        </form>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Waktu</th>
                                        <th>Aktor</th>
                                        <th>Target</th>
                                        <th>Aksi</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($audit_logs && mysqli_num_rows($audit_logs) > 0): ?>
                                        <?php $log_no = $audit_offset + 1; ?>
                                        <?php while ($log = mysqli_fetch_assoc($audit_logs)): ?>
                                            <tr>
                                                <td><?php echo $log_no++; ?></td>
                                                <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td>
                                                    <?php if (!empty($log['actor_is_super'])): ?>
                                                        <span class="badge-super"><i class="fas fa-crown"></i> Super Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge-admin"><i class="fas fa-user-shield"></i> Admin</span>
                                                    <?php endif; ?>
                                                    <strong><?php echo htmlspecialchars($log['actor_username'] ?? ''); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $target_badge_class = '';
                                                    $target_badge_icon = '';
                                                    $target_badge_label = '';

                                                    if (!empty($log['target_username'])) {
                                                        $action_lower = strtolower($log['action']);
                                                        $target_lower = strtolower($log['target_username']);
                                                        if (strpos($action_lower, 'report') !== false || strpos($action_lower, 'lapor') !== false) {
                                                            $target_badge_class = 'badge-report';
                                                            $target_badge_icon = 'fa-ticket-alt';
                                                            $target_badge_label = 'Laporan';
                                                        } elseif (strpos($action_lower, 'analytics') !== false || strpos($target_lower, 'analytics') !== false) {
                                                            $target_badge_class = 'badge-analytics';
                                                            $target_badge_icon = 'fa-chart-line';
                                                            $target_badge_label = 'Analytics';
                                                        } elseif (strpos($action_lower, 'destinasi') !== false || strpos($target_lower, 'destinasi') !== false) {
                                                    $target_badge_class = 'badge-destinasi';
                                                    $target_badge_icon = 'fa-map-marker-alt';
                                                    $target_badge_label = 'Destinasi';
                                                } elseif (strpos($action_lower, 'akomodasi') !== false || strpos($target_lower, 'akomodasi') !== false) {
                                                    $target_badge_class = 'badge-akomodasi';
                                                    $target_badge_icon = 'fa-bed';
                                                    $target_badge_label = 'Akomodasi';
                                                } elseif (strpos($action_lower, 'event') !== false || strpos($target_lower, 'event') !== false) {
                                                            $target_badge_class = 'badge-event';
                                                            $target_badge_icon = 'fa-calendar-alt';
                                                            $target_badge_label = 'Event';
                                                        } elseif (strpos($action_lower, 'kuliner') !== false || strpos($target_lower, 'kuliner') !== false) {
                                                            $target_badge_class = 'badge-kuliner';
                                                            $target_badge_icon = 'fa-utensils';
                                                            $target_badge_label = 'Kuliner';
                                                        } elseif (!empty($log['target_is_super'])) {
                                                            $target_badge_class = 'badge-super';
                                                            $target_badge_icon = 'fa-crown';
                                                            $target_badge_label = 'Super Admin';
                                                        } else {
                                                            $target_badge_class = 'badge-admin';
                                                            $target_badge_icon = 'fa-user-shield';
                                                            $target_badge_label = 'Admin';
                                                        }
                                                    }
                                                    ?>
                                                    <?php if (!empty($log['target_username'])): ?>
                                                        <div class="badge-stack">
                                                            <?php if (!empty($target_badge_class)): ?>
                                                                <span class="<?php echo $target_badge_class; ?>"><i class="fas <?php echo $target_badge_icon; ?>"></i> <?php echo $target_badge_label; ?></span>
                                                            <?php endif; ?>
                                                            <?php
                                                            $target_display = $log['target_username'] ?? '';
                                                            if (strpos($target_display, ' - ') !== false) {
                                                                $target_display = preg_replace('/^[^\\-]*-\\s*/', '', $target_display);
                                                            }
                                                            ?>
                                                            <strong class="audit-target-name"><?php echo htmlspecialchars($target_display); ?></strong>
                                                        </div>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                <?php
                                                $action_label = $log['action'];
                                                if ($log['action'] === 'create_admin') {
                                                    $action_label = 'Buat Admin';
                                                } elseif ($log['action'] === 'delete_admin') {
                                                    $action_label = 'Hapus Admin';
                                                } elseif ($log['action'] === 'toggle_admin_status') {
                                                    $action_label = 'Ubah Status';
                                                } elseif ($log['action'] === 'create_akomodasi') {
                                                    $action_label = 'Tambah Akomodasi';
                                                } elseif ($log['action'] === 'update_akomodasi') {
                                                    $action_label = 'Ubah Akomodasi';
                                                } elseif ($log['action'] === 'delete_akomodasi') {
                                                    $action_label = 'Hapus Akomodasi';
                                                } elseif ($log['action'] === 'toggle_akomodasi') {
                                                    $action_label = 'Ubah Status Akomodasi';
                                                } elseif ($log['action'] === 'create_destinasi') {
                                                    $action_label = 'Tambah Destinasi';
                                                } elseif ($log['action'] === 'update_destinasi') {
                                                    $action_label = 'Ubah Destinasi';
                                                } elseif ($log['action'] === 'delete_destinasi') {
                                                    $action_label = 'Hapus Destinasi';
                                                } elseif ($log['action'] === 'toggle_destinasi') {
                                                    $action_label = 'Ubah Status Destinasi';
                                                } elseif ($log['action'] === 'create_event') {
                                                    $action_label = 'Tambah Event';
                                                } elseif ($log['action'] === 'update_event') {
                                                    $action_label = 'Ubah Event';
                                                } elseif ($log['action'] === 'delete_event') {
                                                    $action_label = 'Hapus Event';
                                                } elseif ($log['action'] === 'toggle_event') {
                                                    $action_label = 'Ubah Status Event';
                                                } elseif ($log['action'] === 'create_kuliner') {
                                                $action_label = 'Tambah Kuliner';
                                            } elseif ($log['action'] === 'update_kuliner') {
                                                $action_label = 'Ubah Kuliner';
                                            } elseif ($log['action'] === 'delete_kuliner') {
                                                $action_label = 'Hapus Kuliner';
                                            } elseif ($log['action'] === 'toggle_kuliner') {
                                                $action_label = 'Ubah Status Kuliner';
                                            } elseif (stripos($log['action'], 'download csv kuliner') !== false) {
                                                $action_label = 'Download CSV Kuliner';
                                            } elseif (stripos($log['action'], 'download json kuliner') !== false) {
                                                $action_label = 'Download JSON Kuliner';
                                            } elseif (stripos($log['action'], 'download csv destinasi') !== false) {
                                                $action_label = 'Download CSV Destinasi';
                                            } elseif (stripos($log['action'], 'download json destinasi') !== false) {
                                                $action_label = 'Download JSON Destinasi';
                                            } elseif (stripos($log['action'], 'download csv akomodasi') !== false) {
                                                $action_label = 'Download CSV Akomodasi';
                                            } elseif (stripos($log['action'], 'download json akomodasi') !== false) {
                                                $action_label = 'Download JSON Akomodasi';
                                            } elseif (stripos($log['action'], 'download csv event') !== false) {
                                                $action_label = 'Download CSV Event';
                                            } elseif (stripos($log['action'], 'download json event') !== false) {
                                                $action_label = 'Download JSON Event';
                                            } elseif ($log['action'] === 'hide_review') {
                                                $action_label = 'Sembunyikan Review';
                                            } elseif ($log['action'] === 'unhide_review') {
                                                $action_label = 'Tampilkan Review';
                                            } elseif ($log['action'] === 'delete_review') {
                                                $action_label = 'Hapus Review';
                                            } elseif ($log['action'] === 'grant_super_admin') {
                                                $action_label = 'Beri Hak Super Admin';
                                            } elseif ($log['action'] === 'revoke_super_admin') {
                                                $action_label = 'Cabut Hak Super Admin';
                                            } elseif ($log['action'] === 'update_report_status') {
                                                $action_label = 'Ubah Status Laporan';
                                            }
                                        echo htmlspecialchars($action_label);
                                        ?>
                                        </td>
                                        <td>
                                            <?php
                                            $detail_text = $log['details'] ?? '';
                                            if (in_array($log['action'], ['hide_review', 'unhide_review', 'delete_review'], true)) {
                                                $detail_text = preg_replace('/Review\\s+#(\\d+)\\s+Review\\s+/i', 'Review #$1 ', $detail_text);
                                                $detail_text = preg_replace('/\\([A-Z]+#\\d+\\s*-\\s*/i', '(', $detail_text);
                                            }
                                            ?>
                                            <?php echo htmlspecialchars($detail_text); ?>
                                        </td>
                                    </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <?php
                                        $audit_empty_message = 'Belum ada audit log.';
                                        if ($audit_has_filter) {
                                            if (!empty($audit_search)) {
                                                $audit_empty_message = 'Tidak ada audit log yang cocok dengan "' . htmlspecialchars($audit_search) . '".';
                                            } else {
                                                $audit_empty_message = 'Tidak ada audit log sesuai filter ini.';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center;">
                                                <div class="empty-state">
                                                    <p><?php echo $audit_empty_message; ?></p>
                                                    <a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars($audit_reset_link); ?>"><i class="fas fa-redo"></i> Reset Filter</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">
                            <?php
                            $audit_query_base = array_merge($query_base_params, ['page' => $page, 'tab' => 'audit']);
                            ?>
                            <a class="page-link <?php echo $audit_page <= 1 ? 'disabled' : ''; ?>"
                                href="<?php echo $audit_page > 1 ? '?' . http_build_query(array_merge($audit_query_base, ['audit_page' => 1])) . $card_anchor : '#'; ?>">&#171;</a>
                            <a class="page-link <?php echo $audit_page <= 1 ? 'disabled' : ''; ?>"
                                href="<?php echo $audit_page > 1 ? '?' . http_build_query(array_merge($audit_query_base, ['audit_page' => $audit_page - 1])) . $card_anchor : '#'; ?>">&#8249;</a>
                            <span class="page-info">Halaman <?php echo $audit_page; ?> / <?php echo $audit_total_pages; ?></span>
                            <a class="page-link <?php echo $audit_page >= $audit_total_pages ? 'disabled' : ''; ?>"
                                href="<?php echo $audit_page < $audit_total_pages ? '?' . http_build_query(array_merge($audit_query_base, ['audit_page' => $audit_page + 1])) . $card_anchor : '#'; ?>">&#8250;</a>
                            <a class="page-link <?php echo $audit_page >= $audit_total_pages ? 'disabled' : ''; ?>"
                                href="<?php echo $audit_page < $audit_total_pages ? '?' . http_build_query(array_merge($audit_query_base, ['audit_page' => $audit_total_pages])) . $card_anchor : '#'; ?>">&#187;</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>