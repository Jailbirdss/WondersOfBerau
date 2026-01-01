<?php
require_once __DIR__ . '/../config.php';

$logout_admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
if ($logout_admin_id > 0) {
    $now = date('Y-m-d H:i:s');
    mysqli_query($conn, "INSERT INTO admin_activity (admin_id, last_logout) VALUES ($logout_admin_id, '$now')
        ON DUPLICATE KEY UPDATE last_logout = '$now'");
}

session_destroy();
header("Location: login.php");
exit();
?>