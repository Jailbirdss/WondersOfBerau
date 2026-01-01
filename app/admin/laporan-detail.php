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
$view_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $view_id = intval($_POST['report_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');

    $report_query = mysqli_query($conn, "SELECT * FROM reports WHERE id = $view_id LIMIT 1");
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
        mysqli_stmt_bind_param($update_stmt, 'ssi', $new_status, $note, $view_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        $admin_id = $_SESSION['admin_id'] ?? null;
        $admin_username = $_SESSION['admin_username'] ?? '';
        $log_stmt = mysqli_prepare($conn, "INSERT INTO report_status_logs (report_id, old_status, new_status, note, evidence, admin_id, admin_username) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($log_stmt, 'issssis', $view_id, $old_status, $new_status, $note, $evidence_path, $admin_id, $admin_username);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);

        log_audit('update_report_status', "Ticket {$report_data['ticket_id']} dari {$old_status} ke {$new_status}.", $view_id, $report_data['ticket_id']);

        $_SESSION['success_message'] = "Status tiket {$report_data['ticket_id']} berhasil diperbarui.";
        header("Location: laporan-detail.php?id={$view_id}#detail");
        exit;
    } else {
        $_SESSION['error_message'] = implode(' ', $errors);
        header("Location: laporan-detail.php?id={$view_id}#detail");
        exit;
    }
}

if (!$view_id) {
    $_SESSION['error_message'] = 'Ticket ID tidak valid.';
    header("Location: laporan.php");
    exit;
}

$view_report = null;
$view_logs = array();
$detail_query = mysqli_query($conn, "SELECT * FROM reports WHERE id = $view_id");
$view_report = $detail_query ? mysqli_fetch_assoc($detail_query) : null;

if (!$view_report) {
    $_SESSION['error_message'] = 'Laporan tidak ditemukan.';
    header("Location: laporan.php");
    exit;
}

$logs_query = mysqli_query($conn, "SELECT * FROM report_status_logs WHERE report_id = $view_id ORDER BY created_at DESC");
if ($logs_query) {
    while ($row = mysqli_fetch_assoc($logs_query)) {
        $view_logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header detail-hero">
                <div>
                    <h1>Detail Laporan</h1>
                    <p class="muted">Tinjau tiket, ubah status, dan cek riwayat penanganan.</p>
                </div>
                <div class="header-actions">
                    <a href="laporan.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
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
                                <div class="description-box">
                                    <div class="detail-label">Deskripsi</div>
                                    <p class="detail-text"><?php echo nl2br(htmlspecialchars($view_report['description'])); ?></p>
                                </div>
                        </div>
                        <div class="status-pane">
                            <div class="status-box">
                                <div class="status-box-title">Ubah Status</div>
                                <form method="POST" enctype="multipart/form-data" class="status-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="report_id" value="<?php echo $view_report['id']; ?>">
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
                        <div class="logs-table-wrapper">
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
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>