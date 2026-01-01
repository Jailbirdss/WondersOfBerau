<?php
require_once __DIR__ . '/../config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

function require_admin() {
    if (!is_admin_logged_in()) {
        redirect('login.php');
    }
}
?>