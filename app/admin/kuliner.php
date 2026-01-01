<?php
require_once 'auth.php';
require_admin();

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $current = mysqli_query($conn, "SELECT nama FROM kuliner WHERE id = $id LIMIT 1");
    $current_row = $current ? mysqli_fetch_assoc($current) : null;
    mysqli_query($conn, "DELETE FROM gallery WHERE ref_id = $id AND ref_type = 'kuliner'");
    mysqli_query($conn, "DELETE FROM kuliner WHERE id = $id");
    if ($current_row && isset($current_row['nama'])) {
        log_audit('delete_kuliner', "Menghapus kuliner \"{$current_row['nama']}\".", $id, $current_row['nama']);
    }
    $_SESSION['success_message'] = "Kuliner berhasil dihapus!";
    redirect('kuliner.php');
}

if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = mysqli_query($conn, "SELECT nama, status FROM kuliner WHERE id = $id");
    $data = mysqli_fetch_assoc($query);
    $new_status = ($data['status'] == 'aktif') ? 'nonaktif' : 'aktif';
    mysqli_query($conn, "UPDATE kuliner SET status = '$new_status' WHERE id = $id");
    if ($data && isset($data['nama'])) {
        $status_msg = $new_status === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';
        log_audit('toggle_kuliner', "Status kuliner \"{$data['nama']}\" $status_msg.", $id, $data['nama']);
    }
    $_SESSION['success_message'] = "Status kuliner berhasil diubah!";
    redirect('kuliner.php');
}

$where = [];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$kategori_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

if (!empty($search)) {
    $where[] = "(nama LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $where[] = "status = '$status_filter'";
}

if (!empty($kategori_filter)) {
    $where[] = "kategori = '$kategori_filter'";
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

if (isset($_GET['action']) && $_GET['action'] == 'export') {
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
    $filename = "kuliner-" . date('Ymd-His') . "." . ($format === 'json' ? 'json' : 'csv');
    $export_query = "SELECT id, nama, kategori, deskripsi, deskripsi_lengkap, gambar_utama, cita_rasa, bahan_utama, harga_kisaran, tempat_rekomendasi, status, created_at, updated_at FROM kuliner $where_sql ORDER BY created_at DESC";
    $export_result = mysqli_query($conn, $export_query);
    $rows = [];
    if ($export_result) {
        while ($r = mysqli_fetch_assoc($export_result)) {
            $rows[] = $r;
        }
    }
    $count = count($rows);
    $format_label = strtoupper($format);
    $action = $format === 'json' ? 'Download JSON Kuliner' : 'Download CSV Kuliner';
    $target_label = "Kuliner ($format_label)";
    log_audit($action, "Export kuliner ($count data, format $format).", null, $target_label);

    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($rows);
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['id', 'nama', 'kategori', 'deskripsi', 'deskripsi_lengkap', 'gambar_utama', 'cita_rasa', 'bahan_utama', 'harga_kisaran', 'tempat_rekomendasi', 'status', 'created_at', 'updated_at']);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$total_rows = 0;
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM kuliner $where_sql");
if ($total_query) {
    $total_rows = intval(mysqli_fetch_assoc($total_query)['total']);
}
$total_pages = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

$query = "SELECT * FROM kuliner $where_sql ORDER BY $sort $order LIMIT $offset, $per_page";
$result = mysqli_query($conn, $query);

function sortUrl($column)
{
    global $sort, $order, $search, $status_filter, $kategori_filter;
    $newOrder = ($sort == $column && $order == 'ASC') ? 'DESC' : 'ASC';
    $params = [
        'sort' => $column,
        'order' => $newOrder
    ];
    if (!empty($search))
        $params['search'] = $search;
    if (!empty($status_filter))
        $params['status'] = $status_filter;
    if (!empty($kategori_filter))
        $params['kategori'] = $kategori_filter;
    return '?' . http_build_query($params);
}
function sortIcon($column)
{
    global $sort, $order;
    if ($sort != $column)
        return '<i class="fas fa-sort"></i>';
    return $order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}
function exportUrl($format = 'csv')
{
    global $search, $status_filter, $kategori_filter;
    $params = [
        'action' => 'export',
        'format' => $format
    ];
    if (!empty($search))
        $params['search'] = $search;
    if (!empty($status_filter))
        $params['status'] = $status_filter;
    if (!empty($kategori_filter))
        $params['kategori'] = $kategori_filter;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kuliner - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Kelola Kuliner & UMKM</h1>
                <a href="kuliner-form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Kuliner</a>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <div class="filter-section filter-with-export">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Cari kuliner..."
                            value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                        <a href="kuliner.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </form>
                <div class="filter-export">
                    <a href="<?php echo exportUrl('csv'); ?>" class="btn-download"><i class="fas fa-file-csv"></i> Export CSV</a>
                    <a href="<?php echo exportUrl('json'); ?>" class="btn-download"><i class="fas fa-file-code"></i> Export JSON</a>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table content-table">
                    <thead>
                        <tr>
                            <th><a href="<?php echo sortUrl('id'); ?>" class="sort-link">No
                                    <?php echo sortIcon('id'); ?></a></th>
                            <th>Gambar</th>
                            <th><a href="<?php echo sortUrl('nama'); ?>" class="sort-link">Nama
                                    <?php echo sortIcon('nama'); ?></a></th>
                            <th><a href="<?php echo sortUrl('kategori'); ?>" class="sort-link">Kategori
                                    <?php echo sortIcon('kategori'); ?></a></th>
                            <th>Harga</th>
                            <th><a href="<?php echo sortUrl('status'); ?>" class="sort-link">Status
                                    <?php echo sortIcon('status'); ?></a></th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $display_no = ($order === 'DESC')
                                ? ($total_rows - ($offset + $no - 1))
                                : ($offset + $no);
                            $no++;
                            ?>
                            <tr>
                                <td><?php echo $display_no; ?></td>
                                <td><img src="../<?php echo htmlspecialchars($row['gambar_utama']); ?>" alt=""
                                        class="table-image"></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td><?php echo ucfirst($row['kategori']); ?></td>
                                <td><?php echo htmlspecialchars($row['harga_kisaran']); ?></td>
                                <td>
                                    <span
                                        class="badge badge-<?php echo $row['status'] == 'aktif' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="gallery.php?ref_id=<?php echo $row['id']; ?>&ref_type=kuliner"
                                        class="btn btn-sm btn-gallery" title="Kelola Galeri"><i
                                            class="fas fa-images"></i></a>
                                    <a href="kuliner-form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info"
                                        title="Edit"><i class="fas fa-edit"></i></a>
                                    <?php $is_active = ($row['status'] === 'aktif'); ?>
                                    <a href="?action=toggle&id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-toggle <?php echo $is_active ? 'on' : 'off'; ?>"
                                        title="Toggle Status" data-confirm="Ubah status item ini?">
                                        <i class="fas <?php echo $is_active ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                        <?php echo $is_active ? 'Aktif' : 'Nonaktif'; ?>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"
                                        title="Hapus" data-confirm="Yakin hapus item ini?"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <?php
                $query_base = [];
                if (!empty($search))
                    $query_base['search'] = $search;
                if (!empty($status_filter))
                    $query_base['status'] = $status_filter;
                if (!empty($kategori_filter))
                    $query_base['kategori'] = $kategori_filter;
                if (!empty($sort))
                    $query_base['sort'] = $sort;
                if (!empty($order))
                    $query_base['order'] = $order;
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
</body>

</html>