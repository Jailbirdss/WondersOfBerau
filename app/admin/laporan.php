<?php
require_once 'auth.php';
require_admin();

$status_list = array('baru', 'diproses', 'selesai', 'ditolak');
$categories = $report_categories ?? array(
    'Kebersihan & Fasilitas',
    'Pelayanan Petugas',
    'Keamanan & Ketertiban',
    'Harga/Tiket',
    'Akses & Transportasi',
    'Lainnya'
);

$transitions = array(
    'baru' => array('baru', 'diproses', 'ditolak'),
    'diproses' => array('diproses', 'selesai', 'ditolak'),
    'selesai' => array('selesai'),
    'ditolak' => array('ditolak')
);

$errors = array();
$csrf_token_report = csrf_token('report_status');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'report_status')) {
        $_SESSION['error_message'] = 'Sesi kadaluarsa, silakan muat ulang halaman.';
        header("Location: laporan.php");
        exit;
    }

    $report_id = intval($_POST['report_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');

    $report_query = mysqli_query($conn, "SELECT * FROM reports WHERE id = $report_id LIMIT 1");
    $report_data = $report_query ? mysqli_fetch_assoc($report_query) : null;

    if (!$report_data) {
        $_SESSION['error_message'] = 'Laporan tidak ditemukan.';
        header("Location: laporan.php");
        exit;
    }

    $old_status = $report_data['status'];

    if (!in_array($new_status, $status_list) || !in_array($new_status, $transitions[$old_status])) {
        $errors[] = 'Perpindahan status tidak valid.';
    }

    if ($new_status === 'selesai' && strlen($note) < 3) {
        $errors[] = 'Catatan wajib diisi untuk status selesai.';
    }

    $evidence_path = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_report_file($_FILES['evidence'], 'report-evidence');
        if (!$upload['success']) {
            $errors[] = $upload['message'];
        } else {
            $evidence_path = $upload['path'];
        }
    }

    if (empty($note)) {
        $note = $report_data['last_note'] ?: 'Update status tanpa catatan';
    }

    if (empty($errors)) {
        $update_stmt = mysqli_prepare($conn, "UPDATE reports SET status = ?, last_note = ?, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, 'ssi', $new_status, $note, $report_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        $admin_id = $_SESSION['admin_id'] ?? null;
        $admin_username = $_SESSION['admin_username'] ?? '';
        $log_stmt = mysqli_prepare($conn, "INSERT INTO report_status_logs (report_id, old_status, new_status, note, evidence, admin_id, admin_username) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($log_stmt, 'issssis', $report_id, $old_status, $new_status, $note, $evidence_path, $admin_id, $admin_username);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);

        log_audit('update_report_status', "Ticket {$report_data['ticket_id']} dari {$old_status} ke {$new_status}.", $report_id, $report_data['ticket_id']);

        $_SESSION['success_message'] = "Status tiket {$report_data['ticket_id']} berhasil diperbarui.";
        header("Location: laporan.php?view={$report_id}#detail");
        exit;
    } else {
        $_SESSION['error_message'] = implode(' ', $errors);
        header("Location: laporan.php?view={$report_id}#detail");
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$view_id = isset($_GET['view']) ? intval($_GET['view']) : 0;

$where = array();

if (!empty($search)) {
    $search_sql = mysqli_real_escape_string($conn, $search);
    $where[] = "ticket_id LIKE '%$search_sql%'";
}

if (!empty($status_filter) && in_array($status_filter, $status_list)) {
    $status_sql = mysqli_real_escape_string($conn, $status_filter);
    $where[] = "status = '$status_sql'";
}

if (!empty($category_filter)) {
    $cat_sql = mysqli_real_escape_string($conn, $category_filter);
    $where[] = "category = '$cat_sql'";
}

if (!empty($date_from)) {
    $from_sql = mysqli_real_escape_string($conn, $date_from);
    $where[] = "DATE(created_at) >= '$from_sql'";
}

if (!empty($date_to)) {
    $to_sql = mysqli_real_escape_string($conn, $date_to);
    $where[] = "DATE(created_at) <= '$to_sql'";
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$total_rows = 0;
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM reports $where_sql");
if ($count_query) {
    $total_rows = intval(mysqli_fetch_assoc($count_query)['total']);
}

$total_pages = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

$reports = mysqli_query($conn, "SELECT * FROM reports $where_sql ORDER BY created_at DESC LIMIT $offset, $per_page");

$view_report = null;
$view_logs = array();
if ($view_id) {
    $detail_query = mysqli_query($conn, "SELECT * FROM reports WHERE id = $view_id");
    $view_report = $detail_query ? mysqli_fetch_assoc($detail_query) : null;

    if ($view_report) {
        $logs_query = mysqli_query($conn, "SELECT * FROM report_status_logs WHERE report_id = $view_id ORDER BY created_at DESC");
        if ($logs_query) {
            while ($row = mysqli_fetch_assoc($logs_query)) {
                $view_logs[] = $row;
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
    <title>Kelola Laporan Wisata - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <div>
                    <h1>Laporan / Komplain Wisata</h1>
                    <p class="muted">Pantau tiket, ubah status, dan simpan log penanganan.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="filter-section report-filters">
                <form method="GET" class="filter-form report-filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" class="filter-input" placeholder="Cari Ticket ID..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status" class="filter-input">
                            <option value="">Semua Status</option>
                            <?php foreach ($status_list as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo $status_filter === $st ? 'selected' : ''; ?>>
                                    <?php echo format_report_status($st); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="category" class="filter-input">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="date-field">
                            <span class="input-label">Mulai</span>
                            <input type="date" name="date_from" class="filter-input" value="<?php echo htmlspecialchars($date_from); ?>" aria-label="Tanggal mulai">
                        </div>
                        <div class="date-field">
                            <span class="input-label">Selesai</span>
                            <input type="date" name="date_to" class="filter-input" value="<?php echo htmlspecialchars($date_to); ?>" aria-label="Tanggal selesai">
                        </div>
                    </div>
                    <div class="filter-actions-row">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                        <a href="laporan.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </form>
            </div>

            <?php if ($view_report): ?>
                <div class="card report-detail-card" id="detail">
                    <div class="card-header">
                        <div>
                            <h3><?php echo htmlspecialchars($view_report['ticket_id']); ?></h3>
                            <p class="muted">Kategori: <?php echo htmlspecialchars($view_report['category']); ?> | Lokasi:
                                <?php echo htmlspecialchars($view_report['location']); ?></p>
                        </div>
                        <span class="status-pill status-<?php echo htmlspecialchars($view_report['status']); ?>">
                            <?php echo format_report_status($view_report['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="report-detail-grid">
                            <div class="detail-pane">
                                <div class="detail-meta-row">
                                    <span class="meta-chip">Dibuat: <?php echo date('d M Y H:i', strtotime($view_report['created_at'])); ?></span>
                                    <span class="meta-chip">Update: <?php echo date('d M Y H:i', strtotime($view_report['updated_at'])); ?></span>
                                    <?php if (!empty($view_report['attachment'])): ?>
                                        <a href="../<?php echo htmlspecialchars($view_report['attachment']); ?>" target="_blank" class="meta-chip link-chip">Lampiran</a>
                                    <?php endif; ?>
                                </div>
                                <div class="description-box">
                                    <div class="detail-label">Deskripsi</div>
                                    <p class="detail-text"><?php echo nl2br(htmlspecialchars($view_report['description'])); ?></p>
                                </div>
                                <div class="detail-meta-grid">
                                    <div class="detail-item">
                                        <span class="detail-label">Nama</span>
                                        <p class="detail-value"><?php echo $view_report['name'] ? htmlspecialchars($view_report['name']) : 'Anonim'; ?></p>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Kontak</span>
                                        <p class="detail-value"><?php echo $view_report['contact'] ? htmlspecialchars($view_report['contact']) : '-'; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="status-pane">
                                    <div class="status-box">
                                        <div class="status-box-title">Ubah Status</div>
                                        <form method="POST" enctype="multipart/form-data" class="status-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="report_id" value="<?php echo $view_report['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_report); ?>">
                                            <div class="form-row">
                                                <label>Status Baru</label>
                                                <select name="status" class="input-control" required>
                                                    <?php foreach ($transitions[$view_report['status']] as $allowed): ?>
                                                        <option value="<?php echo $allowed; ?>"><?php echo format_report_status($allowed); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-row">
                                            <label>Catatan</label>
                                            <textarea name="note" rows="3" class="input-control" placeholder="Catatan penanganan"><?php echo htmlspecialchars($view_report['last_note']); ?></textarea>
                                        </div>
                                        <div class="form-row">
                                            <label>Lampirkan Bukti (opsional)</label>
                                            <input type="file" name="evidence" class="input-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
                                        </div>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="logs">
                        <h4>Riwayat Status</h4>
                        <?php if (empty($view_logs)): ?>
                            <p class="muted">Belum ada log.</p>
                        <?php else: ?>
                            <table class="data-table compact">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                        <th>Admin</th>
                                        <th>Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($view_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                                            <td><span class="status-pill status-<?php echo htmlspecialchars($log['new_status']); ?>">
                                                    <?php echo format_report_status($log['new_status']); ?>
                                                </span></td>
                                            <td><?php echo nl2br(htmlspecialchars($log['note'])); ?></td>
                                            <td><?php echo $log['admin_username'] ? htmlspecialchars($log['admin_username']) : '-'; ?></td>
                                            <td>
                                                <?php if (!empty($log['evidence'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($log['evidence']); ?>" target="_blank" class="btn btn-sm btn-info">Lihat</a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card report-list-card">
                <div class="card-header">
                    <h3>Daftar Laporan (<?php echo $total_rows; ?>)</h3>
                    <span class="muted small-note">Menampilkan tiket terbaru terlebih dulu • pilih Detail untuk buka halaman lengkap</span>
                </div>
                <div class="table-container report-table-container">
                    <table class="data-table report-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Kategori</th>
                                <th>Lokasi</th>
                                <th class="status-col">Status</th>
                                <th>Dibuat</th>
                                <th class="action-col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reports && mysqli_num_rows($reports) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($reports)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['ticket_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td class="status-col"><span class="status-pill status-<?php echo htmlspecialchars($row['status']); ?>"><?php echo format_report_status($row['status']); ?></span>
                                        </td>
                                        <td><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="action-col">
                                    <div class="action-buttons">
                                        <a href="laporan-detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Detail</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada laporan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <?php
                    $query_base = array();
                    if (!empty($search))
                        $query_base['search'] = $search;
                    if (!empty($status_filter))
                        $query_base['status'] = $status_filter;
                    if (!empty($category_filter))
                        $query_base['category'] = $category_filter;
                    if (!empty($date_from))
                        $query_base['date_from'] = $date_from;
                    if (!empty($date_to))
                        $query_base['date_to'] = $date_to;
                    ?>
                    <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>"
                        href="<?php echo $page > 1 ? '?' . http_build_query(array_merge($query_base, ['page' => 1])) : '#'; ?>">&#171;</a>
                    <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>"
                        href="<?php echo $page > 1 ? '?' . http_build_query(array_merge($query_base, ['page' => $page - 1])) : '#'; ?>">&#8249;</a>
                    <span class="page-info">Halaman <?php echo $page; ?> / <?php echo $total_pages; ?></span>
                    <a class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"
                        href="<?php echo $page < $total_pages ? '?' . http_build_query(array_merge($query_base, ['page' => $page + 1])) : '#'; ?>">&#8250;</a>
                    <a class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"
                        href="<?php echo $page < $total_pages ? '?' . http_build_query(array_merge($query_base, ['page' => $total_pages])) : '#'; ?>">&#187;</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>