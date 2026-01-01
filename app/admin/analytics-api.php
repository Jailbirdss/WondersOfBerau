<?php
require_once 'auth.php';
require_admin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'log_export' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
    $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
    $action_label = $format === 'json' ? 'Download JSON Analytics' : 'Download CSV Analytics';
    $target_label = "Analytics (" . strtoupper($format) . ")";
    log_audit($action_label, "Export analytics ({$days} hari, format {$format}).", null, $target_label);
    echo json_encode(['status' => 'ok']);
    exit;
}

switch ($action) {
    case 'overview':
        echo json_encode(get_overview_stats());
        break;

    case 'daily_traffic':
        $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
        echo json_encode(get_daily_traffic($days));
        break;

    case 'popular_pages':
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        echo json_encode(get_popular_pages($limit));
        break;

    case 'browser_stats':
        echo json_encode(get_browser_stats());
        break;

    case 'device_stats':
        echo json_encode(get_device_stats());
        break;

    case 'os_stats':
        echo json_encode(get_os_stats());
        break;

    case 'referrer_stats':
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        echo json_encode(get_referrer_stats($limit));
        break;

    case 'hourly_traffic':
        echo json_encode(get_hourly_traffic());
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function get_overview_stats()
{
    global $conn;

    $stats = [];

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM analytics_pageviews");
    $stats['total_pageviews'] = mysqli_fetch_assoc($result)['total'];

    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM analytics_visitors");
    $stats['total_visitors'] = mysqli_fetch_assoc($result)['total'];

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM analytics_pageviews WHERE DATE(visited_at) = CURDATE()");
    $stats['today_pageviews'] = mysqli_fetch_assoc($result)['total'];

    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM analytics_pageviews WHERE DATE(visited_at) = CURDATE()");
    $stats['today_visitors'] = mysqli_fetch_assoc($result)['total'];

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM analytics_pageviews WHERE DATE(visited_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $stats['yesterday_pageviews'] = mysqli_fetch_assoc($result)['total'];

    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM analytics_pageviews WHERE DATE(visited_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $stats['yesterday_visitors'] = mysqli_fetch_assoc($result)['total'];

    if ($stats['yesterday_pageviews'] > 0) {
        $stats['pageviews_growth'] = round((($stats['today_pageviews'] - $stats['yesterday_pageviews']) / $stats['yesterday_pageviews']) * 100, 1);
    } else {
        $stats['pageviews_growth'] = $stats['today_pageviews'] > 0 ? 100 : 0;
    }

    if ($stats['yesterday_visitors'] > 0) {
        $stats['visitors_growth'] = round((($stats['today_visitors'] - $stats['yesterday_visitors']) / $stats['yesterday_visitors']) * 100, 1);
    } else {
        $stats['visitors_growth'] = $stats['today_visitors'] > 0 ? 100 : 0;
    }

    if ($stats['total_visitors'] > 0) {
        $stats['avg_pages_per_session'] = round($stats['total_pageviews'] / $stats['total_visitors'], 2);
    } else {
        $stats['avg_pages_per_session'] = 0;
    }

    return $stats;
}

function get_daily_traffic($days = 7)
{
    global $conn;

    $query = "SELECT 
                DATE(visited_at) as date,
                COUNT(*) as pageviews,
                COUNT(DISTINCT session_id) as visitors
              FROM analytics_pageviews 
              WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              GROUP BY DATE(visited_at)
              ORDER BY date ASC";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $days);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

function get_popular_pages($limit = 10)
{
    global $conn;

    $query = "SELECT 
                page_url,
                page_title,
                COUNT(*) as pageviews,
                COUNT(DISTINCT session_id) as unique_visitors
              FROM analytics_pageviews 
              GROUP BY page_url, page_title
              ORDER BY pageviews DESC
              LIMIT ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

function get_browser_stats()
{
    global $conn;

    $query = "SELECT 
                browser,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM analytics_pageviews), 2) as percentage
              FROM analytics_pageviews 
              GROUP BY browser
              ORDER BY count DESC";

    $result = mysqli_query($conn, $query);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

function get_device_stats()
{
    global $conn;

    $query = "SELECT 
                device_type,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM analytics_pageviews), 2) as percentage
              FROM analytics_pageviews 
              GROUP BY device_type
              ORDER BY count DESC";

    $result = mysqli_query($conn, $query);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

function get_os_stats()
{
    global $conn;

    $query = "SELECT 
                os,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM analytics_pageviews), 2) as percentage
              FROM analytics_pageviews 
              GROUP BY os
              ORDER BY count DESC";

    $result = mysqli_query($conn, $query);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

function get_referrer_stats($limit = 10)
{
    global $conn;

    $query = "SELECT 
                CASE 
                    WHEN referrer = '' OR referrer = 'direct' THEN 'Direct Traffic'
                    ELSE referrer
                END as referrer,
                COUNT(*) as count
              FROM analytics_pageviews 
              GROUP BY referrer
              ORDER BY count DESC
              LIMIT ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

function get_hourly_traffic()
{
    global $conn;

    $query = "SELECT 
                HOUR(visited_at) as hour,
                COUNT(*) as pageviews
              FROM analytics_pageviews 
              WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              GROUP BY HOUR(visited_at)
              ORDER BY hour ASC";

    $result = mysqli_query($conn, $query);

    $data = array_fill(0, 24, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['hour']] = intval($row['pageviews']);
    }

    return array_values($data);
}
?>