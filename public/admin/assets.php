<?php
require_once __DIR__ . '/../../app/config.php';

$allowed_files = [
    'admin-style.css' => __DIR__ . '/../../app/admin/assets/admin-style.css',
];

$file = $_GET['file'] ?? '';
if (!isset($allowed_files[$file])) {
    http_response_code(404);
    exit('Not found');
}

$referer_ok = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/admin/') !== false;
$logged_in = !empty($_SESSION['admin_logged_in']);
if (!$referer_ok && !$logged_in) {
    http_response_code(403);
    exit('Forbidden');
}

$path = $allowed_files[$file];
if (!file_exists($path)) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=86400');
readfile($path);
exit;
?>