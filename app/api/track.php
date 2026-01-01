<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$page_url = isset($data['page_url']) ? clean_input($data['page_url']) : '';
$page_title = isset($data['page_title']) ? clean_input($data['page_title']) : '';
$referrer = isset($data['referrer']) ? clean_input($data['referrer']) : '';

$ip_address = get_client_ip();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$browser = get_browser_name($user_agent);
$os = get_os_name($user_agent);
$device_type = get_device_type($user_agent);


session_start();
if (!isset($_SESSION['analytics_session_id'])) {
    $_SESSION['analytics_session_id'] = generate_session_id();
}
$session_id = $_SESSION['analytics_session_id'];

$query = "INSERT INTO analytics_pageviews 
          (page_url, page_title, referrer, ip_address, user_agent, browser, device_type, os, session_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'sssssssss', $page_url, $page_title, $referrer, $ip_address, $user_agent, $browser, $device_type, $os, $session_id);

if (mysqli_stmt_execute($stmt)) {
    update_visitor($conn, $ip_address, $session_id, $user_agent);

    echo json_encode(['success' => true, 'message' => 'Tracked successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to track']);
}

mysqli_stmt_close($stmt);

function get_client_ip()
{
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (
                    filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    ) !== false
                ) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function generate_session_id()
{
    return bin2hex(random_bytes(16)) . '_' . time();
}

function get_browser_name($user_agent)
{
    $browsers = [
        'Edge' => '/Edg/i',
        'Opera' => '/Opera|OPR/i',
        'Chrome' => '/Chrome/i',
        'Firefox' => '/Firefox/i',
        'Safari' => '/Safari/i',
        'Internet Explorer' => '/MSIE|Trident/i'
    ];

    foreach ($browsers as $browser => $pattern) {
        if (preg_match($pattern, $user_agent)) {
            return $browser;
        }
    }
    return 'Unknown';
}
function get_os_name($user_agent)
{
    $os_array = [
        'Windows 11' => '/Windows NT 10.0.*Win64.*x64/i',
        'Windows 10' => '/Windows NT 10.0/i',
        'Windows 8.1' => '/Windows NT 6.3/i',
        'Windows 8' => '/Windows NT 6.2/i',
        'Windows 7' => '/Windows NT 6.1/i',
        'Mac OS X' => '/Mac OS X/i',
        'iOS' => '/iPhone|iPad|iPod/i',
        'Android' => '/Android/i',
        'Linux' => '/Linux/i',
        'Ubuntu' => '/Ubuntu/i'
    ];

    foreach ($os_array as $os => $pattern) {
        if (preg_match($pattern, $user_agent)) {
            return $os;
        }
    }
    return 'Unknown';
}

function get_device_type($user_agent)
{
    if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile/i', $user_agent)) {
        if (preg_match('/ipad|tablet|playbook/i', $user_agent)) {
            return 'tablet';
        }
        return 'mobile';
    }
    return 'desktop';
}

function update_visitor($conn, $ip_address, $session_id, $user_agent)
{
    $check_query = "SELECT id, total_pageviews FROM analytics_visitors WHERE session_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, 's', $session_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $new_count = $row['total_pageviews'] + 1;

        $update_query = "UPDATE analytics_visitors SET total_pageviews = ?, last_visit = NOW() WHERE session_id = ?";
        $stmt2 = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt2, 'is', $new_count, $session_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    } else {
        $insert_query = "INSERT INTO analytics_visitors (ip_address, session_id, user_agent) VALUES (?, ?, ?)";
        $stmt2 = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt2, 'sss', $ip_address, $session_id, $user_agent);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }

    mysqli_stmt_close($stmt);
}
?>