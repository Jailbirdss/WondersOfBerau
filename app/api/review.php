<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

function json_response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function normalize_device_token($token)
{
    $token = $token ?? '';
    $token = preg_replace('/[^A-Za-z0-9_\-]/', '', $token);
    return substr($token, 0, 120);
}

function ensure_content_exists($type, $id)
{
    switch ($type) {
        case 'destinasi':
            return get_destinasi($id) ? true : false;
        case 'akomodasi':
            return get_akomodasi($id) ? true : false;
        case 'kuliner':
            return get_kuliner($id) ? true : false;
        case 'event':
            return get_events($id) ? true : false;
        default:
            return false;
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $ref_type = validate_review_type($_GET['type'] ?? '');
    $ref_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $device_token = normalize_device_token($_GET['device_token'] ?? '');
    $sort = $_GET['sort'] ?? 'newest';
    $rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : null;
    $limit = isset($_GET['limit']) ? max(1, min(20, intval($_GET['limit']))) : 10;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    if (!$ref_type || $ref_id <= 0) {
        json_response(array('success' => false, 'message' => 'Parameter tidak lengkap'), 400);
    }

    $summary = get_review_summary($ref_type, $ref_id);
    $reviews_raw = get_reviews($ref_type, $ref_id, $limit, $offset, $sort, $rating_filter);

    $count_query = "SELECT COUNT(*) as total FROM reviews WHERE ref_type = ? AND ref_id = ? AND is_hidden = 0";
    $types = 'si';
    $params = array(&$ref_type, &$ref_id);
    if ($rating_filter && $rating_filter >= 1 && $rating_filter <= 5) {
        $count_query .= " AND rating = ?";
        $types .= 'i';
        $params[] = &$rating_filter;
    }
    $stmtCount = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmtCount, $types, ...$params);
    mysqli_stmt_execute($stmtCount);
    $countResult = mysqli_stmt_get_result($stmtCount);
    $countRow = mysqli_fetch_assoc($countResult);
    mysqli_stmt_close($stmtCount);
    $total_filtered = (int) ($countRow['total'] ?? 0);

    $reviews = array();
    foreach ($reviews_raw as $rev) {
        $reviews[] = array(
            'name' => $rev['name'],
            'rating' => (int) $rev['rating'],
            'comment' => $rev['comment'] ?? '',
            'photo' => $rev['photo'] ?? null,
            'created_at' => $rev['created_at'],
            'date_label' => date('d M Y', strtotime($rev['created_at']))
        );
    }

    $device_review = null;
    if ($device_token) {
        $found = get_review_by_device($ref_type, $ref_id, $device_token);
        if ($found) {
            $device_review = array(
                'name' => $found['name'],
                'rating' => (int) $found['rating'],
                'comment' => $found['comment'] ?? '',
                'photo' => $found['photo'],
                'created_at' => $found['created_at']
            );
        }
    }

    json_response(array(
        'success' => true,
        'data' => array(
            'summary' => array(
                'average' => $summary['average'],
                'count' => $summary['count']
            ),
            'reviews' => $reviews,
            'pagination' => array(
                'page' => $page,
                'per_page' => $limit,
                'total' => $total_filtered,
                'total_pages' => $total_filtered > 0 ? (int) ceil($total_filtered / $limit) : 1
            ),
            'has_reviewed' => $device_token ? has_device_reviewed($ref_type, $ref_id, $device_token) : false,
            'your_review' => $device_review
        )
    ));
}

if ($method === 'POST') {
    $ref_type = validate_review_type($_POST['type'] ?? '');
    $ref_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $comment = trim($_POST['comment'] ?? '');
    $device_token = normalize_device_token($_POST['device_token'] ?? '');
    $remove_photo = isset($_POST['remove_photo']) ? (bool) $_POST['remove_photo'] : false;

    if ($comment !== '') {
        $comment = strip_tags($comment);
    }
    $name = strip_tags($name);
    if ($name === '') {
        json_response(array('success' => false, 'message' => 'Nama wajib diisi'), 400);
    }
    $name = mb_substr($name, 0, 150);

    if (!$ref_type || $ref_id <= 0 || $rating < 1 || $rating > 5 || empty($device_token)) {
        json_response(array('success' => false, 'message' => 'Data review tidak valid'), 400);
    }

    if (!ensure_content_exists($ref_type, $ref_id)) {
        json_response(array('success' => false, 'message' => 'Konten tidak ditemukan'), 404);
    }

    if (strlen($comment) > 1000) {
        $comment = substr($comment, 0, 1000);
    }

    $existing = get_review_by_device($ref_type, $ref_id, $device_token);

    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_review_media($_FILES['photo']);
        if (!$upload['success']) {
            json_response(array('success' => false, 'message' => $upload['message'] ?? 'Upload gagal'), 422);
        }
        $photo_path = $upload['path'];
    } elseif ($existing && !$remove_photo) {
        $photo_path = $existing['photo'];
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

    if ($existing) {
        $query = "UPDATE reviews SET name = ?, rating = ?, comment = ?, photo = ?, ip_address = ?, user_agent = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sissssi', $name, $rating, $comment, $photo_path, $ip, $user_agent, $existing['id']);
        $action = 'updated';
    } else {
        $query = "INSERT INTO reviews (ref_type, ref_id, name, rating, comment, photo, device_token, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sisisssss', $ref_type, $ref_id, $name, $rating, $comment, $photo_path, $device_token, $ip, $user_agent);
        $action = 'created';
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        json_response(array('success' => false, 'message' => 'Gagal menyimpan review'), 500);
    }

    mysqli_stmt_close($stmt);

    $summary = get_review_summary($ref_type, $ref_id);

    json_response(array(
        'success' => true,
        'message' => $action === 'updated' ? 'Review kamu diperbarui.' : 'Terima kasih! Review kamu sudah diterima.',
        'data' => array(
            'summary' => array(
                'average' => $summary['average'],
                'count' => $summary['count']
            ),
            'your_review' => array(
                'name' => $name,
                'rating' => $rating,
                'comment' => $comment,
                'photo' => $photo_path
            ),
            'action' => $action
        )
    ));
}

json_response(array('success' => false, 'message' => 'Method not allowed'), 405);