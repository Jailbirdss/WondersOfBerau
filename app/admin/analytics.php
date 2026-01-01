<?php
require_once 'auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Analytics - Wonders of Berau</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-chart-line"></i> Web Analytics</h1>
            </div>

            <div class="page-subtitle">
                <p>Monitor traffic dan aktivitas pengunjung website</p>
            </div>

            <div class="date-filter-wrapper">
                <div class="date-filter">
                    <label for="dayFilter">Tampilkan:</label>
                    <select id="dayFilter" onchange="changeDateRange()">
                        <option value="7">7 Hari Terakhir</option>
                        <option value="14">14 Hari Terakhir</option>
                        <option value="30">30 Hari Terakhir</option>
                        <option value="90">90 Hari Terakhir</option>
                    </select>
                </div>
                <div class="download-buttons">
                    <button class="btn-download" onclick="downloadData('csv', this)">
                        <i class="fas fa-file-csv"></i> Download CSV
                    </button>
                    <button class="btn-download" onclick="downloadData('json', this)">
                        <i class="fas fa-file-code"></i> Download JSON
                    </button>
                </div>
            </div>

            <div class="analytics-overview" id="overviewStats">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Memuat data...</p>
                </div>
            </div>

            <div class="analytics-grid">
                <div class="analytics-card full-width">
                    <h3><i class="fas fa-chart-area"></i> Traffic Harian</h3>
                    <div class="chart-container">
                        <canvas id="dailyTrafficChart"></canvas>
                    </div>
                </div>

                <div class="browser-device-center">
                    <div class="analytics-card">
                        <h3><i class="fas fa-desktop"></i> Browser</h3>
                        <div class="chart-container">
                            <canvas id="browserChart"></canvas>
                        </div>
                    </div>

                    <div class="analytics-card">
                        <h3><i class="fas fa-mobile-alt"></i> Device</h3>
                        <div class="chart-container">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="analytics-card full-width">
                    <h3><i class="fas fa-clock"></i> Traffic per Jam (24 Jam Terakhir)</h3>
                    <div class="chart-container">
                        <canvas id="hourlyTrafficChart"></canvas>
                    </div>
                </div>

                <div class="browser-device-center">
                    <div class="analytics-card">
                        <h3><i class="fas fa-file-alt"></i> Halaman Populer</h3>
                        <div class="table-container" id="popularPages">
                            <div class="loading">
                                <i class="fas fa-spinner"></i>
                            </div>
                        </div>
                    </div>

                    <div class="analytics-card">
                        <h3><i class="fas fa-laptop"></i> Operating System</h3>
                        <div id="osStats">
                            <div class="loading">
                                <i class="fas fa-spinner"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let dailyChart, browserChart, deviceChart, hourlyChart;
        let currentDays = 7;

        document.addEventListener('DOMContentLoaded', function () {
            loadOverviewStats();
            loadDailyTraffic(currentDays);
            loadBrowserStats();
            loadDeviceStats();
            loadPopularPages();
            loadOSStats();
            loadHourlyTraffic();

            setInterval(function () {
                loadOverviewStats();
                loadDailyTraffic(currentDays);
            }, 300000);
        });

        function changeDateRange() {
            currentDays = parseInt(document.getElementById('dayFilter').value);
            loadDailyTraffic(currentDays);
        }

        function loadOverviewStats() {
            fetch('analytics-api.php?action=overview')
                .then(response => response.json())
                .then(data => {
                    const html = `
                        <div class="overview-card">
                            <h3>Total Pageviews</h3>
                            <div class="value">${formatNumber(data.total_pageviews)}</div>
                            <div class="growth ${data.pageviews_growth >= 0 ? 'positive' : 'negative'}">
                                <i class="fas fa-arrow-${data.pageviews_growth >= 0 ? 'up' : 'down'}"></i>
                                ${Math.abs(data.pageviews_growth)}% dari kemarin
                            </div>
                        </div>
                        <div class="overview-card blue">
                            <h3>Unique Visitors</h3>
                            <div class="value">${formatNumber(data.total_visitors)}</div>
                            <div class="growth ${data.visitors_growth >= 0 ? 'positive' : 'negative'}">
                                <i class="fas fa-arrow-${data.visitors_growth >= 0 ? 'up' : 'down'}"></i>
                                ${Math.abs(data.visitors_growth)}% dari kemarin
                            </div>
                        </div>
                        <div class="overview-card orange">
                            <h3>Hari Ini</h3>
                            <div class="value">${formatNumber(data.today_pageviews)}</div>
                            <div class="growth">
                                ${formatNumber(data.today_visitors)} visitors
                            </div>
                        </div>
                        <div class="overview-card purple">
                            <h3>Rata-rata Pages/Session</h3>
                            <div class="value">${data.avg_pages_per_session}</div>
                            <div class="growth">
                                Total ${formatNumber(data.total_pageviews)} halaman
                            </div>
                        </div>
                    `;
                    document.getElementById('overviewStats').innerHTML = html;
                })
                .catch(error => console.error('Error:', error));
        }

        function loadDailyTraffic(days) {
            fetch(`analytics-api.php?action=daily_traffic&days=${days}`)
                .then(response => response.json())
                .then(data => {
                    const labels = data.map(item => formatDate(item.date));
                    const pageviews = data.map(item => parseInt(item.pageviews));
                    const visitors = data.map(item => parseInt(item.visitors));

                    if (dailyChart) {
                        dailyChart.destroy();
                    }

                    const ctx = document.getElementById('dailyTrafficChart').getContext('2d');
                    dailyChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Pageviews',
                                data: pageviews,
                                borderColor: '#4CAF50',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                tension: 0.4,
                                fill: true
                            }, {
                                label: 'Visitors',
                                data: visitors,
                                borderColor: '#2196F3',
                                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                                tension: 0.4,
                                fill: true
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
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        padding: 15,
                                        font: {
                                            size: 13,
                                            family: "'Poppins', sans-serif",
                                            weight: '500'
                                        },
                                        color: '#4a5568',
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        boxHeight: 8
                                    },
                                    onClick: function (e, legendItem, legend) {
                                        const index = legendItem.datasetIndex;
                                        const chart = legend.chart;
                                        const meta = chart.getDatasetMeta(index);

                                        meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
                                        chart.update();
                                    },
                                    onHover: function (e) {
                                        e.native.target.style.cursor = 'pointer';
                                    },
                                    onLeave: function (e) {
                                        e.native.target.style.cursor = 'default';
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        function loadBrowserStats() {
            fetch('analytics-api.php?action=browser_stats')
                .then(response => response.json())
                .then(data => {
                    const labels = data.map(item => item.browser);
                    const values = data.map(item => parseInt(item.count));

                    const ctx = document.getElementById('browserChart').getContext('2d');
                    browserChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: values,
                                backgroundColor: [
                                    '#4CAF50',
                                    '#2196F3',
                                    '#FF9800',
                                    '#9C27B0',
                                    '#F44336',
                                    '#607D8B'
                                ]
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
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        font: {
                                            size: 12,
                                            family: "'Poppins', sans-serif",
                                            weight: '500'
                                        },
                                        color: '#4a5568',
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 10,
                                        boxHeight: 10
                                    },
                                    onHover: function (e) {
                                        e.native.target.style.cursor = 'pointer';
                                    },
                                    onLeave: function (e) {
                                        e.native.target.style.cursor = 'default';
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        function loadDeviceStats() {
            fetch('analytics-api.php?action=device_stats')
                .then(response => response.json())
                .then(data => {
                    const labels = data.map(item => {
                        const type = item.device_type;
                        return type.charAt(0).toUpperCase() + type.slice(1);
                    });
                    const values = data.map(item => parseInt(item.count));

                    const ctx = document.getElementById('deviceChart').getContext('2d');
                    deviceChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: values,
                                backgroundColor: [
                                    '#2196F3',
                                    '#4CAF50',
                                    '#FF9800'
                                ]
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
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        font: {
                                            size: 12,
                                            family: "'Poppins', sans-serif",
                                            weight: '500'
                                        },
                                        color: '#4a5568',
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 10,
                                        boxHeight: 10
                                    },
                                    onHover: function (e) {
                                        e.native.target.style.cursor = 'pointer';
                                    },
                                    onLeave: function (e) {
                                        e.native.target.style.cursor = 'default';
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        function loadHourlyTraffic() {
            fetch('analytics-api.php?action=hourly_traffic')
                .then(response => response.json())
                .then(data => {
                    const labels = Array.from({ length: 24 }, (_, i) => i + ':00');

                    const ctx = document.getElementById('hourlyTrafficChart').getContext('2d');
                    hourlyChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Pageviews per Jam',
                                data: data,
                                backgroundColor: 'rgba(76, 175, 80, 0.6)',
                                borderColor: '#4CAF50',
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
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        function loadPopularPages() {
            fetch('analytics-api.php?action=popular_pages&limit=10')
                .then(response => response.json())
                .then(data => {
                    let html = '<table class="analytics-table">';
                    html += '<thead><tr><th>Halaman</th><th>Pageviews</th><th>Unique Visitors</th></tr></thead>';
                    html += '<tbody>';

                    data.forEach(item => {
                        const url = new URL(item.page_url);
                        const path = url.pathname;
                        html += `<tr>
                            <td><a href="${item.page_url}" class="page-url" target="_blank" title="${item.page_title}">${item.page_title || path}</a></td>
                            <td><strong>${formatNumber(item.pageviews)}</strong></td>
                            <td>${formatNumber(item.unique_visitors)}</td>
                        </tr>`;
                    });

                    html += '</tbody></table>';
                    document.getElementById('popularPages').innerHTML = html;
                })
                .catch(error => console.error('Error:', error));
        }

        function loadOSStats() {
            fetch('analytics-api.php?action=os_stats')
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    const maxCount = Math.max(...data.map(item => parseInt(item.count)));

                    data.forEach(item => {
                        const percentage = (parseInt(item.count) / maxCount * 100);
                        html += `
                            <div class="stat-bar">
                                <div class="stat-label">${item.os}</div>
                                <div class="stat-progress">
                                    <div class="stat-fill" style="width: ${percentage}%">
                                        ${item.count} (${item.percentage}%)
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    document.getElementById('osStats').innerHTML = html;
                })
                .catch(error => console.error('Error:', error));
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { day: 'numeric', month: 'short' };
            return date.toLocaleDateString('id-ID', options);
        }

        function downloadData(format, button) {
            const days = parseInt(document.getElementById('dayFilter').value);

            const originalContent = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';

            Promise.all([
                fetch('analytics-api.php?action=overview').then(r => r.json()),
                fetch(`analytics-api.php?action=daily_traffic&days=${days}`).then(r => r.json()),
                fetch('analytics-api.php?action=popular_pages&limit=20').then(r => r.json()),
                fetch('analytics-api.php?action=browser_stats').then(r => r.json()),
                fetch('analytics-api.php?action=device_stats').then(r => r.json()),
                fetch('analytics-api.php?action=os_stats').then(r => r.json()),
                fetch('analytics-api.php?action=referrer_stats').then(r => r.json()),
                fetch('analytics-api.php?action=hourly_stats').then(r => r.json())
            ]).then(([overview, dailyTraffic, popularPages, browsers, devices, os, referrers, hourly]) => {
                const data = {
                    generated_at: new Date().toISOString(),
                    period: `${days} hari terakhir`,
                    overview: overview,
                    daily_traffic: dailyTraffic,
                    popular_pages: popularPages,
                    browser_stats: browsers,
                    device_stats: devices,
                    os_stats: os,
                    referrer_stats: referrers,
                    hourly_stats: hourly
                };

                if (format === 'json') {
                    downloadJSON(data);
                    showPopup('success', 'File JSON berhasil diunduh!');
                } else if (format === 'csv') {
                    downloadCSV(data);
                    showPopup('success', 'File CSV berhasil diunduh!');
                }

                fetch(`analytics-api.php?action=log_export&format=${format}&days=${days}`, { method: 'POST' }).catch(() => {});

                button.disabled = false;
                button.innerHTML = originalContent;
            }).catch(error => {
                console.error('Error:', error);
                showPopup('error', 'Gagal mengunduh data. Silakan coba lagi.');
                button.disabled = false;
                button.innerHTML = originalContent;
            });
        }

        function showPopup(type, message) {
            const popup = document.createElement('div');
            popup.className = `popup-notification ${type}`;

            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            const title = type === 'success' ? 'Berhasil!' : 'Error!';

            popup.innerHTML = `
                <div class="popup-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="popup-content">
                    <strong>${title}</strong>
                    <span>${message}</span>
                </div>
                <button class="popup-close" onclick="closePopup(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;

            document.body.appendChild(popup);

            setTimeout(() => {
                closePopup(popup.querySelector('.popup-close'));
            }, 5000);
        }

        function closePopup(button) {
            const popup = button.closest('.popup-notification');
            popup.classList.add('hiding');
            setTimeout(() => {
                popup.remove();
            }, 400);
        }

        function downloadJSON(data) {
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `analytics-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function downloadCSV(data) {
            let csv = 'LAPORAN WEB ANALYTICS - WONDERS OF BERAU\n';
            csv += `Generated: ${new Date().toLocaleString('id-ID')}\n`;
            csv += `Period: ${data.period}\n\n`;

            csv += '=== OVERVIEW STATISTIK ===\n';
            csv += 'Metric,Value\n';
            csv += `Total Pageviews,${data.overview.total_pageviews}\n`;
            csv += `Total Visitors,${data.overview.total_visitors}\n`;
            csv += `Today Pageviews,${data.overview.today_pageviews}\n`;
            csv += `Today Visitors,${data.overview.today_visitors}\n`;
            csv += `Avg Pages/Session,${data.overview.avg_pages_per_session}\n`;
            csv += `Pageviews Growth,${data.overview.pageviews_growth}%\n`;
            csv += `Visitors Growth,${data.overview.visitors_growth}%\n\n`;

            csv += '=== TRAFFIC HARIAN ===\n';
            csv += 'Date,Pageviews,Visitors\n';
            data.daily_traffic.forEach(item => {
                csv += `${item.date},${item.pageviews},${item.visitors}\n`;
            });
            csv += '\n';

            csv += '=== HALAMAN POPULER ===\n';
            csv += 'Title,URL,Pageviews,Unique Visitors\n';
            data.popular_pages.forEach(item => {
                const title = (item.page_title || '').replace(/,/g, ';');
                const url = item.page_url.replace(/,/g, ';');
                csv += `"${title}","${url}",${item.pageviews},${item.unique_visitors}\n`;
            });
            csv += '\n';

            csv += '=== STATISTIK BROWSER ===\n';
            csv += 'Browser,Count\n';
            data.browser_stats.forEach(item => {
                csv += `${item.browser},${item.count}\n`;
            });
            csv += '\n';

            csv += '=== STATISTIK DEVICE ===\n';
            csv += 'Device Type,Count\n';
            data.device_stats.forEach(item => {
                csv += `${item.device_type},${item.count}\n`;
            });
            csv += '\n';

            csv += '=== STATISTIK OPERATING SYSTEM ===\n';
            csv += 'OS,Count,Percentage\n';
            data.os_stats.forEach(item => {
                csv += `${item.os},${item.count},${item.percentage}%\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `analytics-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>

</html>