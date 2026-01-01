<?php
date_default_timezone_set('Asia/Jakarta');
ini_set('default_charset', 'UTF-8');

if (!headers_sent() && !isset($_SERVER['HTTP_CONTENT_TYPE'])) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script_name, '/api/') === false) {
        header('Content-Type: text/html; charset=utf-8');
    }
}

$base_path = dirname(__DIR__);

function load_env_file($path)
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env($key, $default = null)
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

load_env_file($base_path . '/.env');

define('BASE_PATH', $base_path);
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('UPLOAD_URL_BASE', 'uploads');

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'wondersofberau'));

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$cookieParams = array(
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
);
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params($cookieParams);
    session_start();
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

function csrf_token($key = 'default')
{
    if (empty($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = array();
    }
    if (!isset($_SESSION['csrf_tokens'][$key])) {
        $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_tokens'][$key];
}

function verify_csrf_token($token, $key = 'default')
{
    if (empty($token) || empty($_SESSION['csrf_tokens'][$key])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_tokens'][$key], $token);
}

$should_run_db_setup = env('RUN_DB_SETUP', '0') === '1';

if ($should_run_db_setup) {
    $create_reports_table = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id VARCHAR(30) NOT NULL UNIQUE,
        secret_code_hash VARCHAR(255) NOT NULL,
        category VARCHAR(150) NOT NULL,
        location VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        attachment VARCHAR(255) DEFAULT NULL,
        name VARCHAR(150) DEFAULT NULL,
        contact VARCHAR(150) DEFAULT NULL,
        status ENUM('baru','diproses','selesai','ditolak') DEFAULT 'baru',
        last_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    mysqli_query($conn, $create_reports_table);

    $create_report_logs_table = "CREATE TABLE IF NOT EXISTS report_status_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_id INT NOT NULL,
        old_status ENUM('baru','diproses','selesai','ditolak') DEFAULT 'baru',
        new_status ENUM('baru','diproses','selesai','ditolak') NOT NULL,
        note TEXT,
        evidence VARCHAR(255) DEFAULT NULL,
        admin_id INT DEFAULT NULL,
        admin_username VARCHAR(150) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_report (report_id),
        INDEX idx_status_new (new_status),
        CONSTRAINT fk_report_logs_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $create_report_logs_table);

    $create_admin_audit_table = "CREATE TABLE IF NOT EXISTS admin_audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        actor_admin_id INT DEFAULT NULL,
        actor_username VARCHAR(150) DEFAULT NULL,
        target_admin_id INT DEFAULT NULL,
        target_username VARCHAR(150) DEFAULT NULL,
        action VARCHAR(80) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )";
    mysqli_query($conn, $create_admin_audit_table);

    $create_reviews_table = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_type ENUM('destinasi','akomodasi','kuliner','event') NOT NULL,
        ref_id INT NOT NULL,
        name VARCHAR(150) DEFAULT NULL,
        rating TINYINT NOT NULL,
        comment TEXT,
        photo VARCHAR(255) DEFAULT NULL,
        device_token VARCHAR(120) NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_review_device (ref_type, ref_id, device_token),
        INDEX idx_ref (ref_type, ref_id),
        INDEX idx_created (created_at)
    )";
    mysqli_query($conn, $create_reviews_table);
}

function ensure_review_hidden_column()
{
    global $conn;
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
    if (!$table_exists || mysqli_num_rows($table_exists) === 0) {
        return;
    }

    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'is_hidden'");
    if ($column_check && mysqli_num_rows($column_check) === 0) {
        @mysqli_query($conn, "ALTER TABLE reviews ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER comment, ADD INDEX idx_reviews_hidden (is_hidden)");
    }
}

ensure_review_hidden_column();

function log_audit($action, $details, $target_id = null, $target_username = null, $actor_id = null, $actor_username = null)
{
    global $conn;
    $actor_id = $actor_id !== null ? intval($actor_id) : (isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null);
    $actor_username = $actor_username !== null ? $actor_username : ($_SESSION['admin_username'] ?? '');
    $target_id_sql = $target_id !== null ? intval($target_id) : 'NULL';
    $actor_id_sql = $actor_id !== null ? intval($actor_id) : 'NULL';
    $actor_username_sql = $actor_username !== '' ? "'" . mysqli_real_escape_string($conn, $actor_username) . "'" : "NULL";
    $target_username_sql = $target_username !== null && $target_username !== '' ? "'" . mysqli_real_escape_string($conn, $target_username) . "'" : "NULL";
    $action_sql = "'" . mysqli_real_escape_string($conn, $action) . "'";
    $details_sql = "'" . mysqli_real_escape_string($conn, $details) . "'";

    $insert_audit = "INSERT INTO admin_audit_logs (actor_admin_id, actor_username, target_admin_id, target_username, action, details)
        VALUES ($actor_id_sql, $actor_username_sql, $target_id_sql, $target_username_sql, $action_sql, $details_sql)";
    @mysqli_query($conn, $insert_audit);
}

function clean_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

function generate_ticket_id()
{
    global $conn;
    do {
        $random = strtoupper(bin2hex(random_bytes(3)));
        $ticket_id = 'LPR-' . date('ymd') . '-' . $random;
        $stmt = mysqli_prepare($conn, "SELECT id FROM reports WHERE ticket_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $ticket_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $exists = $res && mysqli_num_rows($res) > 0;
        mysqli_stmt_close($stmt);
    } while ($exists);

    return $ticket_id;
}

function generate_secret_code($length = 6)
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function upload_report_file($file, $folder = 'reports')
{
    if (!isset($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('success' => true, 'filename' => null, 'path' => null);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('success' => false, 'message' => 'Gagal mengunggah file. Silakan coba lagi.');
    }

    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
    $max_size = 5 * 1024 * 1024;

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        return array('success' => false, 'message' => 'Lampiran harus berupa gambar atau PDF (max 5MB).');
    }

    if ($file['size'] > $max_size) {
        return array('success' => false, 'message' => 'Ukuran lampiran maksimal 5MB.');
    }

    $base_dir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
    if (!is_dir($base_dir)) {
        @mkdir($base_dir, 0755, true);
    }

    $new_filename = uniqid('report_', true) . '.' . $extension;
    $target_path = $base_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return array(
            'success' => true,
            'filename' => $new_filename,
            'path' => UPLOAD_URL_BASE . '/' . $folder . '/' . $new_filename
        );
    }

    return array('success' => false, 'message' => 'Terjadi kesalahan saat menyimpan lampiran.');
}

function format_report_status($status)
{
    $map = array(
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak'
    );
    return $map[$status] ?? ucfirst($status);
}

function get_destinasi($id = null, $limit = null)
{
    global $conn;
    $query = "SELECT * FROM destinasi WHERE status = 'aktif'";

    if ($id) {
        $query .= " AND id = " . intval($id);
    }

    $query .= " ORDER BY created_at DESC";

    if ($limit) {
        $query .= " LIMIT " . intval($limit);
    }

    $result = mysqli_query($conn, $query);

    if ($id) {
        $item = mysqli_fetch_assoc($result);
        if ($item) {
            $summary = get_review_summary('destinasi', $item['id']);
            $item['review_count'] = $summary['count'];
            $item['review_average'] = $summary['average'];
        }
        return $item;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return add_review_meta_to_items($data, 'destinasi');
}

function get_akomodasi($id = null, $limit = null)
{
    global $conn;
    $query = "SELECT * FROM akomodasi WHERE status = 'aktif'";

    if ($id) {
        $query .= " AND id = " . intval($id);
    }

    $query .= " ORDER BY created_at DESC";

    if ($limit) {
        $query .= " LIMIT " . intval($limit);
    }

    $result = mysqli_query($conn, $query);

    if ($id) {
        $item = mysqli_fetch_assoc($result);
        if ($item) {
            $summary = get_review_summary('akomodasi', $item['id']);
            $item['review_count'] = $summary['count'];
            $item['review_average'] = $summary['average'];
        }
        return $item;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return add_review_meta_to_items($data, 'akomodasi');
}

function get_kuliner($id = null, $kategori = null, $limit = null)
{
    global $conn;
    $query = "SELECT * FROM kuliner WHERE status = 'aktif'";

    if ($id) {
        $query .= " AND id = " . intval($id);
    }

    if ($kategori) {
        $query .= " AND kategori = '" . clean_input($kategori) . "'";
    }

    $query .= " ORDER BY created_at DESC";

    if ($limit) {
        $query .= " LIMIT " . intval($limit);
    }

    $result = mysqli_query($conn, $query);

    if ($id) {
        $item = mysqli_fetch_assoc($result);
        if ($item) {
            $summary = get_review_summary('kuliner', $item['id']);
            $item['review_count'] = $summary['count'];
            $item['review_average'] = $summary['average'];
        }
        return $item;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return add_review_meta_to_items($data, 'kuliner');
}

function get_events($id = null, $limit = null)
{
    global $conn;
    $query = "SELECT * FROM events WHERE status = 'aktif'";

    if ($id) {
        $query .= " AND id = " . intval($id);
    }

    $query .= " ORDER BY tanggal ASC";

    if ($limit) {
        $query .= " LIMIT " . intval($limit);
    }

    $result = mysqli_query($conn, $query);

    if ($id) {
        $item = mysqli_fetch_assoc($result);
        if ($item) {
            $summary = get_review_summary('event', $item['id']);
            $item['review_count'] = $summary['count'];
            $item['review_average'] = $summary['average'];
        }
        return $item;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return add_review_meta_to_items($data, 'event');
}

function get_gallery($ref_id, $ref_type)
{
    global $conn;
    $query = "SELECT * FROM gallery 
              WHERE ref_id = " . intval($ref_id) . " 
              AND ref_type = '" . clean_input($ref_type) . "' 
              ORDER BY urutan ASC";

    $result = mysqli_query($conn, $query);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function format_rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

function is_admin_logged_in()
{
    return isset($_SESSION['admin_id']) && $_SESSION['admin_logged_in'] === true;
}

function redirect($url)
{
    header("Location: " . $url);
    exit();
}

function upload_image($file, $folder = 'general')
{
    $target_dir = rtrim(UPLOAD_PATH, '/\\') . "/" . $folder . "/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return array('success' => false, 'message' => 'File bukan gambar!');
    }

    if ($file["size"] > 5000000) {
        return array('success' => false, 'message' => 'File terlalu besar! Maksimal 5MB');
    }

    if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
        return array('success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diizinkan!');
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return array('success' => true, 'filename' => $new_filename, 'path' => UPLOAD_URL_BASE . '/' . $folder . '/' . $new_filename);
    } else {
        return array('success' => false, 'message' => 'Terjadi error saat upload file!');
    }
}

function delete_image($filepath)
{
    if (file_exists(PUBLIC_PATH . '/' . $filepath)) {
        return unlink(PUBLIC_PATH . '/' . $filepath);
    }
    return false;
}

function search_all($keyword)
{
    global $conn;
    $keyword = clean_input($keyword);
    $results = array(
        'destinasi' => [],
        'akomodasi' => [],
        'kuliner' => [],
        'events' => []
    );

    $query = "SELECT * FROM destinasi 
              WHERE status = 'aktif' 
              AND (nama LIKE '%$keyword%' OR deskripsi LIKE '%$keyword%' OR lokasi LIKE '%$keyword%')
              ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $results['destinasi'][] = $row;
    }

    $query = "SELECT * FROM akomodasi 
              WHERE status = 'aktif' 
              AND (nama LIKE '%$keyword%' OR deskripsi LIKE '%$keyword%' OR lokasi LIKE '%$keyword%')
              ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $results['akomodasi'][] = $row;
    }

    $query = "SELECT * FROM kuliner 
              WHERE status = 'aktif' 
              AND (nama LIKE '%$keyword%' OR deskripsi LIKE '%$keyword%' OR kategori LIKE '%$keyword%')
              ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $results['kuliner'][] = $row;
    }

    $query = "SELECT * FROM events 
              WHERE status = 'aktif' 
              AND (nama LIKE '%$keyword%' OR deskripsi LIKE '%$keyword%' OR lokasi LIKE '%$keyword%')
              ORDER BY tanggal ASC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $results['events'][] = $row;
    }

    $results['destinasi'] = add_review_meta_to_items($results['destinasi'], 'destinasi');
    $results['akomodasi'] = add_review_meta_to_items($results['akomodasi'], 'akomodasi');
    $results['kuliner'] = add_review_meta_to_items($results['kuliner'], 'kuliner');
    $results['events'] = add_review_meta_to_items($results['events'], 'event');

    return $results;
}

function validate_review_type($type)
{
    $allowed = array('destinasi', 'akomodasi', 'kuliner', 'event');
    return in_array($type, $allowed, true) ? $type : null;
}

function get_review_summaries_bulk($ref_type, array $ids)
{
    global $conn;
    $ref_type = validate_review_type($ref_type);
    if (!$ref_type) {
        return array();
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
        return $id > 0;
    })));

    if (empty($ids)) {
        return array();
    }

    $id_list = implode(',', $ids);
    $query = "SELECT ref_id, COUNT(*) as total, AVG(rating) as avg_rating FROM reviews WHERE is_hidden = 0 AND ref_type = '$ref_type' AND ref_id IN ($id_list) GROUP BY ref_id";
    $result = mysqli_query($conn, $query);

    $summaries = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $summaries[(int) $row['ref_id']] = array(
            'count' => (int) ($row['total'] ?? 0),
            'average' => $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 1) : 0
        );
    }

    return $summaries;
}

function add_review_meta_to_items(array $items, $ref_type)
{
    if (empty($items)) {
        return $items;
    }

    $summaries = get_review_summaries_bulk($ref_type, array_column($items, 'id'));

    foreach ($items as &$item) {
        $summary = $summaries[(int) ($item['id'] ?? 0)] ?? array('count' => 0, 'average' => 0);
        $item['review_count'] = $summary['count'];
        $item['review_average'] = $summary['average'];
    }
    unset($item);

    return $items;
}

function get_review_summary($ref_type, $ref_id)
{
    global $conn;
    $ref_type = validate_review_type($ref_type);
    if (!$ref_type) {
        return array('count' => 0, 'average' => 0);
    }

    $query = "SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM reviews WHERE is_hidden = 0 AND ref_type = ? AND ref_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'si', $ref_type, $ref_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return array(
        'count' => (int) ($data['total'] ?? 0),
        'average' => $data['avg_rating'] !== null ? round((float) $data['avg_rating'], 1) : 0
    );
}

function get_reviews($ref_type, $ref_id, $limit = 12, $offset = 0, $order = 'newest', $rating_filter = null, $include_hidden = false)
{
    global $conn;
    $ref_type = validate_review_type($ref_type);
    if (!$ref_type) {
        return array();
    }

    $limit = max(1, min(50, intval($limit)));
    $offset = max(0, intval($offset));

    $order_map = array(
        'newest' => 'created_at DESC',
        'oldest' => 'created_at ASC',
        'highest' => 'rating DESC, created_at DESC',
        'lowest' => 'rating ASC, created_at DESC'
    );
    $order_sql = $order_map[$order] ?? $order_map['newest'];

    $query = "SELECT id, name, rating, comment, photo, created_at, is_hidden FROM reviews WHERE ref_type = ? AND ref_id = ?";
    $types = 'si';
    $params = array(&$ref_type, &$ref_id);

    if (!$include_hidden) {
        $query .= " AND is_hidden = 0";
    }

    if ($rating_filter && $rating_filter >= 1 && $rating_filter <= 5) {
        $query .= " AND rating = ?";
        $types .= 'i';
        $params[] = &$rating_filter;
    }

    $query .= " ORDER BY {$order_sql} LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    $types .= 'ii';
    $params[] = &$limit;
    $params[] = &$offset;

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $reviews = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = array(
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'rating' => (int) $row['rating'],
            'comment' => $row['comment'],
            'photo' => $row['photo'],
            'created_at' => $row['created_at'],
            'is_hidden' => (int) ($row['is_hidden'] ?? 0)
        );
    }

    mysqli_stmt_close($stmt);
    return $reviews;
}

function has_device_reviewed($ref_type, $ref_id, $device_token)
{
    global $conn;
    $ref_type = validate_review_type($ref_type);
    if (!$ref_type || empty($device_token)) {
        return false;
    }

    $query = "SELECT id FROM reviews WHERE ref_type = ? AND ref_id = ? AND device_token = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'sis', $ref_type, $ref_id, $device_token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $has = $result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);
    return $has;
}

function get_review_by_device($ref_type, $ref_id, $device_token)
{
    global $conn;
    $ref_type = validate_review_type($ref_type);
    if (!$ref_type || empty($device_token)) {
        return null;
    }

    $query = "SELECT id, name, rating, comment, photo, created_at FROM reviews WHERE ref_type = ? AND ref_id = ? AND device_token = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'sis', $ref_type, $ref_id, $device_token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$data) {
        return null;
    }
    return array(
        'id' => (int) $data['id'],
        'name' => $data['name'],
        'rating' => (int) $data['rating'],
        'comment' => $data['comment'],
        'photo' => $data['photo'],
        'created_at' => $data['created_at']
    );
}

function upload_review_media($file)
{
    if (!isset($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('success' => true, 'path' => null);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('success' => false, 'message' => 'Gagal mengunggah file.');
    }

    $allowed_extensions = array('jpg', 'jpeg', 'png', 'webp');
    $max_size = 3 * 1024 * 1024; // 3MB

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions, true)) {
        return array('success' => false, 'message' => 'Format foto harus JPG, PNG, atau WEBP.');
    }

    if ($file['size'] > $max_size) {
        return array('success' => false, 'message' => 'Ukuran foto maksimal 3MB.');
    }

    $base_dir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'reviews' . DIRECTORY_SEPARATOR;
    if (!is_dir($base_dir)) {
        @mkdir($base_dir, 0755, true);
    }

    $new_filename = uniqid('review_', true) . '.' . $extension;
    $target_path = $base_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return array(
            'success' => true,
            'path' => UPLOAD_URL_BASE . '/reviews/' . $new_filename
        );
    }

    return array('success' => false, 'message' => 'Terjadi kesalahan saat menyimpan foto.');
}
?>