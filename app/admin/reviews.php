<?php
require_once 'auth.php';
require_admin();
ensure_review_hidden_column();

$csrf_token = csrf_token('review_actions');
$visibility_options = array('visible', 'hidden', 'all');
$sort_options = array(
    'newest' => 'Terbaru',
    'oldest' => 'Terlama',
    'highest' => 'Rating tertinggi',
    'lowest' => 'Rating terendah',
    'visibility' => 'Status tampil'
);

function review_ref_title($ref_type, $ref_id)
{
    global $conn;
    $ref_type = validate_review_type($ref_type);
    $id = intval($ref_id);
    if (!$ref_type || $id <= 0) {
        return '';
    }

    $map = array(
        'destinasi' => 'destinasi',
        'akomodasi' => 'akomodasi',
        'kuliner' => 'kuliner',
        'event' => 'events'
    );
    $table = $map[$ref_type] ?? null;
    if (!$table) {
        return '';
    }

    $query = mysqli_query($conn, "SELECT nama FROM {$table} WHERE id = {$id} LIMIT 1");
    $row = $query ? mysqli_fetch_assoc($query) : null;
    return $row['nama'] ?? '';
}

function review_public_link($ref_type, $ref_id)
{
    $ref_type = validate_review_type($ref_type);
    $id = intval($ref_id);
    if (!$ref_type || $id <= 0) {
        return '';
    }

    $map = array(
        'destinasi' => "../detail-destinasi.php?id={$id}",
        'akomodasi' => "../detail-akomodasi.php?id={$id}",
        'kuliner' => "../detail-kuliner.php?id={$id}",
        'event' => "../detail-event.php?id={$id}",
    );

    return $map[$ref_type] ?? '';
}

function redirect_back_with_message()
{
    $back_to = 'reviews.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $back_to .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: {$back_to}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'review_actions')) {
        $_SESSION['error_message'] = 'Sesi kadaluarsa, silakan muat ulang halaman.';
        redirect_back_with_message();
    }

    $action = $_POST['action'];
    $review_id = intval($_POST['review_id'] ?? 0);

    if ($review_id <= 0) {
        $_SESSION['error_message'] = 'Review tidak ditemukan.';
        redirect_back_with_message();
    }

    $stmt = mysqli_prepare($conn, "SELECT id, ref_type, ref_id, name, rating, comment, photo, is_hidden FROM reviews WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $review_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $review = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$review) {
        $_SESSION['error_message'] = 'Review tidak ditemukan.';
        redirect_back_with_message();
    }

    $ref_title = review_ref_title($review['ref_type'], $review['ref_id']);
    $label = strtoupper($review['ref_type']) . '#' . $review['ref_id'];
    if (!empty($ref_title)) {
        $label .= ' - ' . $ref_title;
    }

    if ($action === 'hide' || $action === 'unhide') {
        $new_status = $action === 'hide' ? 1 : 0;
        $stmtUpdate = mysqli_prepare($conn, "UPDATE reviews SET is_hidden = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmtUpdate, 'ii', $new_status, $review_id);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);

        $message = $new_status === 1 ? 'Review disembunyikan.' : 'Review ditampilkan kembali.';
        $_SESSION['success_message'] = $message;
        $log_action = $new_status === 1 ? 'hide_review' : 'unhide_review';
        $log_detail = "Review #{$review_id} {$message} ({$label}).";
        log_audit($log_action, $log_detail, $review_id, $label);
        redirect_back_with_message();
    }

    if ($action === 'delete') {
        if (!empty($review['photo'])) {
            delete_image($review['photo']);
        }
        $stmtDelete = mysqli_prepare($conn, "DELETE FROM reviews WHERE id = ?");
        mysqli_stmt_bind_param($stmtDelete, 'i', $review_id);
        mysqli_stmt_execute($stmtDelete);
        mysqli_stmt_close($stmtDelete);

        $_SESSION['success_message'] = 'Review berhasil dihapus.';
        $log_detail = "Menghapus review #{$review_id} ({$label}).";
        log_audit('delete_review', $log_detail, $review_id, $label);
        redirect_back_with_message();
    }

    $_SESSION['error_message'] = 'Aksi tidak dikenal.';
    redirect_back_with_message();
}

$search = trim($_GET['search'] ?? '');
$type_filter = validate_review_type($_GET['type'] ?? '') ?: '';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : null;
if ($rating_filter !== null && ($rating_filter < 1 || $rating_filter > 5)) {
    $rating_filter = null;
}
$visibility = $_GET['visibility'] ?? 'visible';
if (!in_array($visibility, $visibility_options, true)) {
    $visibility = 'visible';
}
$sort = $_GET['sort'] ?? 'newest';
if (!array_key_exists($sort, $sort_options)) {
    $sort = 'newest';
}

$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$stats = array('total' => 0, 'visible' => 0, 'hidden' => 0, 'avg' => 0);
$stats_query = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN is_hidden = 1 THEN 1 ELSE 0 END) as hidden, AVG(CASE WHEN is_hidden = 0 THEN rating END) as avg_visible FROM reviews");
if ($stats_query) {
    $stats_row = mysqli_fetch_assoc($stats_query);
    $stats['total'] = intval($stats_row['total'] ?? 0);
    $stats['hidden'] = intval($stats_row['hidden'] ?? 0);
    $stats['visible'] = max(0, $stats['total'] - $stats['hidden']);
    $stats['avg'] = $stats_row['avg_visible'] !== null ? round((float) $stats_row['avg_visible'], 1) : 0;
}

$conditions = array();
$ref_title_sql = "CASE 
    WHEN r.ref_type = 'destinasi' THEN (SELECT nama FROM destinasi WHERE id = r.ref_id LIMIT 1)
    WHEN r.ref_type = 'akomodasi' THEN (SELECT nama FROM akomodasi WHERE id = r.ref_id LIMIT 1)
    WHEN r.ref_type = 'kuliner' THEN (SELECT nama FROM kuliner WHERE id = r.ref_id LIMIT 1)
    WHEN r.ref_type = 'event' THEN (SELECT nama FROM events WHERE id = r.ref_id LIMIT 1)
    ELSE ''
END";

if ($type_filter) {
    $type_sql = mysqli_real_escape_string($conn, $type_filter);
    $conditions[] = "r.ref_type = '{$type_sql}'";
}

if ($rating_filter !== null) {
    $conditions[] = "r.rating = " . intval($rating_filter);
}

if ($visibility === 'visible') {
    $conditions[] = "r.is_hidden = 0";
} elseif ($visibility === 'hidden') {
    $conditions[] = "r.is_hidden = 1";
}

if ($search !== '') {
    $search_sql = mysqli_real_escape_string($conn, $search);
    $conditions[] = "(r.name LIKE '%{$search_sql}%' OR r.comment LIKE '%{$search_sql}%' OR {$ref_title_sql} LIKE '%{$search_sql}%')";
}

$where_sql = '';
if (!empty($conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $conditions);
}

$order_map = array(
    'newest' => 'r.created_at DESC',
    'oldest' => 'r.created_at ASC',
    'highest' => 'r.rating DESC, r.created_at DESC',
    'lowest' => 'r.rating ASC, r.created_at DESC',
    'visibility' => 'r.is_hidden DESC, r.created_at DESC'
);
$order_sql = $order_map[$sort] ?? $order_map['newest'];

$total_rows = 0;
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM reviews r {$where_sql}");
if ($count_query) {
    $total_rows = intval(mysqli_fetch_assoc($count_query)['total'] ?? 0);
}

$total_pages = max(1, (int) ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

$list_query = "SELECT r.*, {$ref_title_sql} AS ref_title FROM reviews r {$where_sql} ORDER BY {$order_sql} LIMIT {$per_page} OFFSET {$offset}";
$list_result = mysqli_query($conn, $list_query);
$reviews = array();
if ($list_result) {
    while ($row = mysqli_fetch_assoc($list_result)) {
        $reviews[] = $row;
    }
}

function build_review_query(array $overrides = array())
{
    global $search, $type_filter, $rating_filter, $visibility, $sort, $page;
    $params = array();
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($type_filter) {
        $params['type'] = $type_filter;
    }
    if ($rating_filter !== null) {
        $params['rating'] = $rating_filter;
    }
    if ($visibility !== 'visible') {
        $params['visibility'] = $visibility;
    }
    if ($sort !== 'newest') {
        $params['sort'] = $sort;
    }
    $params['page'] = $page;

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
            continue;
        }
        $params[$key] = $value;
    }

    $query = http_build_query($params);
    return $query ? '?' . $query : '';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Review - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <div>
                    <p class="subheading">Pengelolaan review pengunjung</p>
                    <h1>Review Pengguna</h1>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon soft-blue"><i class="fas fa-star-half-stroke"></i></div>
                    <div>
                        <p class="stat-label">Rata-rata</p>
                        <h3 class="stat-value"><?php echo number_format($stats['avg'], 1); ?></h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon soft-green"><i class="fas fa-eye"></i></div>
                    <div>
                        <p class="stat-label">Tampil</p>
                        <h3 class="stat-value"><?php echo $stats['visible']; ?></h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon soft-orange"><i class="fas fa-eye-slash"></i></div>
                    <div>
                        <p class="stat-label">Disembunyikan</p>
                        <h3 class="stat-value"><?php echo $stats['hidden']; ?></h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon soft-purple"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <p class="stat-label">Total Review</p>
                        <h3 class="stat-value"><?php echo $stats['total']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="filter-section review-monitor-filters">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" class="filter-input" placeholder="Cari nama, komentar, atau judul konten"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <select name="type" class="filter-select">
                            <option value="">Semua tipe</option>
                            <option value="destinasi" <?php echo $type_filter === 'destinasi' ? 'selected' : ''; ?>>Destinasi</option>
                            <option value="akomodasi" <?php echo $type_filter === 'akomodasi' ? 'selected' : ''; ?>>Akomodasi</option>
                            <option value="kuliner" <?php echo $type_filter === 'kuliner' ? 'selected' : ''; ?>>Kuliner</option>
                            <option value="event" <?php echo $type_filter === 'event' ? 'selected' : ''; ?>>Event</option>
                        </select>
                        <select name="rating" class="filter-select">
                            <option value="">Semua rating</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $rating_filter === $i ? 'selected' : ''; ?>><?php echo $i; ?> bintang</option>
                            <?php endfor; ?>
                        </select>
                        <select name="visibility" class="filter-select">
                            <option value="visible" <?php echo $visibility === 'visible' ? 'selected' : ''; ?>>Tampil</option>
                            <option value="hidden" <?php echo $visibility === 'hidden' ? 'selected' : ''; ?>>Disembunyikan</option>
                            <option value="all" <?php echo $visibility === 'all' ? 'selected' : ''; ?>>Semua</option>
                        </select>
                        <select name="sort" class="filter-select">
                            <?php foreach ($sort_options as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Terapkan</button>
                        <a href="reviews.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-container review-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Konten</th>
                            <th>Review</th>
                            <th>Komentar</th>
                            <th>Tautan</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th style="min-width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 22px;">Belum ada data yang cocok.</td>
                            </tr>
                        <?php endif; ?>
                        <?php $row_number = $offset + 1; ?>
                        <?php foreach ($reviews as $row): ?>
                            <?php
                            $is_hidden = (int) ($row['is_hidden'] ?? 0);
                            $ref_title = $row['ref_title'] ?: 'Konten tidak ditemukan';
                            $badge_map = array(
                                'destinasi' => array('class' => 'badge-destinasi', 'icon' => 'fa-map-marker-alt', 'label' => 'Destinasi'),
                                'akomodasi' => array('class' => 'badge-akomodasi', 'icon' => 'fa-bed', 'label' => 'Akomodasi'),
                                'kuliner' => array('class' => 'badge-kuliner', 'icon' => 'fa-utensils', 'label' => 'Kuliner'),
                                'event' => array('class' => 'badge-event', 'icon' => 'fa-calendar-alt', 'label' => 'Event'),
                            );
                            $badge = $badge_map[$row['ref_type']] ?? array('class' => 'badge-admin', 'icon' => 'fa-layer-group', 'label' => strtoupper($row['ref_type']));
                            $badge_class = $is_hidden ? 'status-inactive' : 'status-active';
                            $public_link = review_public_link($row['ref_type'], $row['ref_id']);
                            $photo_link = !empty($row['photo']) ? '../' . $row['photo'] : '';
                            ?>
                            <tr>
                                <td><?php echo $row_number++; ?></td>
                                <td>
                                    <div class="review-meta">
                                        <span class="review-pill <?php echo $badge['class']; ?>">
                                            <i class="fas <?php echo $badge['icon']; ?>"></i>
                                            <?php echo htmlspecialchars($badge['label']); ?>
                                        </span>
                                        <p class="review-title"><?php echo htmlspecialchars($ref_title); ?></p>
                                    </div>
                                </td>
                                <td>
                                    <div class="rating-row">
                                        <span class="rating-badge"><i class="fas fa-star"></i> <?php echo intval($row['rating']); ?>/5</span>
                                        <p class="reviewer-name"><?php echo htmlspecialchars($row['name'] ?: 'Anonim'); ?></p>
                                    </div>
                                </td>
                                <td>
                                    <div class="comment-snippet">
                                        <?php echo htmlspecialchars($row['comment'] ?: 'Tidak ada komentar.'); ?>
                                    </div>
                                    <?php if (!empty($row['photo'])): ?>
                                        <div class="review-photo">
                                            <img src="<?php echo '../' . htmlspecialchars($row['photo']); ?>" alt="Lampiran review">
                                            <span>Lampiran</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="link-stack">
                                        <?php if ($public_link): ?>
                                            <a class="content-link" href="<?php echo $public_link; ?>" target="_blank" rel="noopener">
                                                <i class="fas fa-link"></i> Buka halaman
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($photo_link): ?>
                                            <a class="content-link" href="<?php echo htmlspecialchars($photo_link); ?>" target="_blank" rel="noopener">
                                                <i class="fas fa-image"></i> Lihat foto
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$public_link && !$photo_link): ?>
                                            <span class="content-link muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $badge_class; ?>">
                                        <?php echo $is_hidden ? 'Disembunyikan' : 'Tampil'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="timestamp"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <form method="POST" class="inline-form" data-confirm="<?php echo $is_hidden ? 'Tampilkan kembali review ini?' : 'Sembunyikan review dari publik?'; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="review_id" value="<?php echo intval($row['id']); ?>">
                                            <input type="hidden" name="action" value="<?php echo $is_hidden ? 'unhide' : 'hide'; ?>">
                                            <button type="submit" class="btn btn-ghost">
                                                <i class="fas <?php echo $is_hidden ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                                <?php echo $is_hidden ? 'Tampilkan' : 'Sembunyikan'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline-form" data-confirm="Hapus review ini secara permanen?">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="review_id" value="<?php echo intval($row['id']); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $prev_page = max(1, $page - 1);
                    $next_page = min($total_pages, $page + 1);
                    ?>
                    <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page > 1 ? build_review_query(array('page' => $prev_page)) : '#'; ?>"><i class="fas fa-chevron-left"></i></a>
                    <span class="page-link disabled" style="width: auto; padding: 0 12px;"><?php echo $page; ?> / <?php echo $total_pages; ?></span>
                    <a class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" href="<?php echo $page < $total_pages ? build_review_query(array('page' => $next_page)) : '#'; ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>