<?php
require_once 'auth.php';
require_admin();

$total_destinasi = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM destinasi WHERE status = 'aktif'"));
$total_akomodasi = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM akomodasi WHERE status = 'aktif'"));
$total_kuliner = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM kuliner WHERE status = 'aktif'"));
$total_events = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM events WHERE status = 'aktif'"));

$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'analytics_pageviews'");
$analytics_exists = mysqli_num_rows($table_check) > 0;

$views_today = 0;
$unique_today = 0;
$views_week = 0;
$avg_pages_per_session = 0;
$top_content = [];
$hourly_traffic = array_fill(0, 24, 0);
$max_hourly = 1;

if ($analytics_exists) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM analytics_pageviews");
    $total_pageviews = mysqli_fetch_assoc($result)['total'] ?? 0;

    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM analytics_visitors");
    $total_visitors = mysqli_fetch_assoc($result)['total'] ?? 0;

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM analytics_pageviews WHERE DATE(visited_at) = CURDATE()");
    if ($result) {
        $views_today = mysqli_fetch_assoc($result)['total'] ?? 0;
    }

    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM analytics_pageviews WHERE DATE(visited_at) = CURDATE()");
    if ($result) {
        $unique_today = mysqli_fetch_assoc($result)['total'] ?? 0;
    }

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    if ($result) {
        $views_week = mysqli_fetch_assoc($result)['total'] ?? 0;
    }

    if ($total_visitors > 0) {
        $avg_pages_per_session = round($total_pageviews / $total_visitors, 1);
    }

    $top_pages = mysqli_query($conn, "SELECT page_url, COUNT(*) as views FROM analytics_pageviews WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY page_url ORDER BY views DESC LIMIT 3");

    if ($top_pages) {
        while ($page = mysqli_fetch_assoc($top_pages)) {
            $url = $page['page_url'];
            $views = $page['views'];
            $title = 'Homepage';

            if (strpos($url, 'detail-destinasi.php') !== false) {
                preg_match('/id=(\d+)/', $url, $matches);
                if (isset($matches[1])) {
                    $result = mysqli_query($conn, "SELECT nama FROM destinasi WHERE id = {$matches[1]}");
                    if ($row = mysqli_fetch_assoc($result)) {
                        $title = $row['nama'];
                    }
                }
            } elseif (strpos($url, 'detail-kuliner.php') !== false) {
                preg_match('/id=(\d+)/', $url, $matches);
                if (isset($matches[1])) {
                    $result = mysqli_query($conn, "SELECT nama FROM kuliner WHERE id = {$matches[1]}");
                    if ($row = mysqli_fetch_assoc($result)) {
                        $title = $row['nama'];
                    }
                }
            } elseif (strpos($url, 'detail-akomodasi.php') !== false) {
                preg_match('/id=(\d+)/', $url, $matches);
                if (isset($matches[1])) {
                    $result = mysqli_query($conn, "SELECT nama FROM akomodasi WHERE id = {$matches[1]}");
                    if ($row = mysqli_fetch_assoc($result)) {
                        $title = $row['nama'];
                    }
                }
            } elseif (strpos($url, 'detail-event.php') !== false) {
                preg_match('/id=(\d+)/', $url, $matches);
                if (isset($matches[1])) {
                    $result = mysqli_query($conn, "SELECT nama FROM events WHERE id = {$matches[1]}");
                    if ($row = mysqli_fetch_assoc($result)) {
                        $title = $row['nama'];
                    }
                }
            } elseif (strpos($url, 'destinasi.php') !== false) {
                $title = 'Halaman Destinasi';
            } elseif (strpos($url, 'kuliner') !== false) {
                $title = 'Halaman Kuliner';
            } elseif (strpos($url, 'akomodasi') !== false) {
                $title = 'Halaman Akomodasi';
            } elseif (strpos($url, 'event.php') !== false) {
                $title = 'Halaman Event';
            }

            $top_content[] = [
                'title' => $title,
                'views' => $views,
                'url' => $url
            ];
        }
    }

    $result = mysqli_query($conn, "SELECT HOUR(visited_at) as hour, COUNT(*) as pageviews FROM analytics_pageviews WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY HOUR(visited_at) ORDER BY hour ASC");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $hour = intval($row['hour']);
            $hourly_traffic[$hour] = intval($row['pageviews']);
        }
    }

    $max_hourly = max($hourly_traffic);
    if ($max_hourly == 0) {
        $max_hourly = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Wonders of Berau</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Selamat datang, <strong><?php echo $_SESSION['admin_username']; ?></strong></span>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_destinasi; ?></h3>
                        <p>Destinasi Wisata</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_akomodasi; ?></h3>
                        <p>Akomodasi</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_kuliner; ?></h3>
                        <p>Kuliner & UMKM</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #9C27B0;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_events; ?></h3>
                        <p>Event</p>
                    </div>
                </div>
            </div>

            <div class="analytics-preview">
                <div class="analytics-grid">
                    <div class="analytics-section">
                        <h2><i class="fas fa-chart-line"></i> Statistik Kunjungan</h2>
                        <div class="mini-stats">
                            <div class="mini-stat-card">
                                <div class="mini-stat-icon" style="background: #00BCD4;">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="mini-stat-info">
                                    <h4><?php echo number_format($views_today); ?></h4>
                                    <p>Views Hari Ini</p>
                                </div>
                            </div>
                            <div class="mini-stat-card">
                                <div class="mini-stat-icon" style="background: #E91E63;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="mini-stat-info">
                                    <h4><?php echo number_format($unique_today); ?></h4>
                                    <p>Unique Visitor</p>
                                </div>
                            </div>
                            <div class="mini-stat-card">
                                <div class="mini-stat-icon" style="background: #3F51B5;">
                                    <i class="fas fa-chart-area"></i>
                                </div>
                                <div class="mini-stat-info">
                                    <h4><?php echo number_format($views_week); ?></h4>
                                    <p>Views 7 Hari</p>
                                </div>
                            </div>
                            <div class="mini-stat-card">
                                <div class="mini-stat-icon" style="background: #FF5722;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="mini-stat-info">
                                    <h4><?php echo $avg_pages_per_session; ?></h4>
                                    <p>Rata-rata Pages/Session</p>
                                </div>
                            </div>
                        </div>

                        <div class="mini-chart">
                            <h3>Traffic Per Jam (24 Jam Terakhir)</h3>
                            <div style="height: 180px; position: relative;">
                                <canvas id="dashboardHourlyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="analytics-section">
                        <h2><i class="fas fa-fire"></i> Konten Paling Populer</h2>
                        <p class="section-subtitle">7 hari terakhir</p>

                        <?php if (empty($top_content)): ?>
                            <div class="no-data">
                                <i class="fas fa-chart-bar"></i>
                                <p>Belum ada data kunjungan</p>
                            </div>
                        <?php else: ?>
                            <div class="top-content-list">
                                <?php foreach ($top_content as $index => $content): ?>
                                    <div class="top-content-item">
                                        <div class="content-rank"><?php echo $index + 1; ?></div>
                                        <div class="content-info">
                                            <h4><?php echo htmlspecialchars($content['title']); ?></h4>
                                            <p><?php echo htmlspecialchars($content['url']); ?></p>
                                        </div>
                                        <div class="content-views">
                                            <i class="fas fa-eye"></i>
                                            <strong><?php echo number_format($content['views']); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="quick-actions" style="margin-top: 2rem;">
                            <a href="analytics.php" class="analytics-soft-btn">
                                <i class="fas fa-chart-line"></i> Lihat Analytics Lengkap
                            </a>
                        </div>
                    </div>
                </div>

                <div class="dashboard-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-grid">
                        <a href="destinasi.php" class="action-btn"><i class="fas fa-plus"></i> Tambah Destinasi</a>
                        <a href="akomodasi.php" class="action-btn"><i class="fas fa-plus"></i> Tambah Akomodasi</a>
                        <a href="kuliner.php" class="action-btn"><i class="fas fa-plus"></i> Tambah Kuliner</a>
                        <a href="events.php" class="action-btn"><i class="fas fa-plus"></i> Tambah Event</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hourlyData = <?php echo json_encode(array_values($hourly_traffic)); ?>;
            const labels = Array.from({ length: 24 }, (_, i) => i + ':00');

            const ctx = document.getElementById('dashboardHourlyChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pageviews per Jam',
                        data: hourlyData,
                        backgroundColor: 'rgba(138, 155, 200, 0.6)',
                        borderColor: '#8a9bc8',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 750,
                        easing: 'easeInOutQuart'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.parsed.y + ' pageviews';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 12,
                                callback: function (value, index) {
                                    return index % 2 === 0 ? this.getLabelForValue(value) : '';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>