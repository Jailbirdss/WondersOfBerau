<?php
require_once __DIR__ . '/../app/config.php';

$state = null;
$errors = array();
$report = null;
$logs = array();

$ticket_input = trim($_GET['ticket_id'] ?? ($_POST['ticket_id'] ?? ''));
$secret_input = trim($_POST['secret_code'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($ticket_input)) {
        $errors['ticket_id'] = 'Ticket ID wajib diisi.';
    }

    if (empty($secret_input)) {
        $errors['secret_code'] = 'Kode unik wajib diisi.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM reports WHERE ticket_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $ticket_input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $report = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$report) {
            $state = 'not_found';
        } elseif (!password_verify($secret_input, $report['secret_code_hash'])) {
            $state = 'invalid_code';
            $report = null;
        } else {
            $state = 'success';
            $log_stmt = mysqli_prepare($conn, "SELECT * FROM report_status_logs WHERE report_id = ? ORDER BY created_at ASC");
            mysqli_stmt_bind_param($log_stmt, 'i', $report['id']);
            mysqli_stmt_execute($log_stmt);
            $log_result = mysqli_stmt_get_result($log_stmt);
            while ($row = mysqli_fetch_assoc($log_result)) {
                $logs[] = $row;
            }
            mysqli_stmt_close($log_stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Laporan - Wonders of Berau</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body>

    <header>
        <nav class="navbar">
            <a href="index.php" class="nav-logo">Wonders of Berau</a>
            <ul class="nav-menu">
                <li class="nav-item"><a href="destinasi.php" class="nav-link">Destinasi</a></li>
                <li class="nav-item"><a href="akomodasikuliner.php" class="nav-link">Akomodasi & Kuliner</a></li>
                <li class="nav-item"><a href="event.php" class="nav-link">Event</a></li>
            </ul>
            <div class="nav-actions">
                <form action="search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Cari destinasi, kuliner, event..." class="search-input"
                        required>
                    <button type="submit" class="search-btn">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5" />
                            <path d="M12.5 12.5L17 17" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                    </button>
                </form>
                <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">
                    <svg class="sun-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="10" cy="10" r="4" stroke="currentColor" stroke-width="1.5" />
                        <path
                            d="M10 1V3M10 17V19M19 10H17M3 10H1M16.071 3.929L14.657 5.343M5.343 14.657L3.929 16.071M16.071 16.071L14.657 14.657M5.343 5.343L3.929 3.929"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                    <svg class="moon-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="page-header">
            <div class="container">
                <h1 class="page-title">Cek Status Laporan</h1>
                <p class="section-subtitle">Masukkan Ticket ID dan kode unik yang Anda dapat saat submit laporan.</p>
            </div>
        </section>

        <section class="container section">
            <?php if ($state === 'not_found'): ?>
                <div class="report-alert error">Ticket ID tidak ditemukan.</div>
            <?php elseif ($state === 'invalid_code'): ?>
                <div class="report-alert error">Kode unik tidak sesuai.</div>
            <?php endif; ?>

            <div class="report-layout">
                <div class="report-card">
                    <h3>Masukkan Ticket ID</h3>
                    <form method="POST" class="report-form" novalidate>
                        <div class="form-group <?php echo isset($errors['ticket_id']) ? 'has-error' : ''; ?>">
                            <label for="ticket_id">Ticket ID</label>
                            <input type="text" id="ticket_id" name="ticket_id" placeholder="Contoh: LPR-250101-1A2B3C"
                                value="<?php echo htmlspecialchars($ticket_input); ?>" required>
                            <?php if (isset($errors['ticket_id'])): ?>
                                <p class="field-error"><?php echo htmlspecialchars($errors['ticket_id']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['secret_code']) ? 'has-error' : ''; ?>">
                            <label for="secret_code">Kode Unik</label>
                            <input type="text" id="secret_code" name="secret_code" placeholder="6-8 huruf/angka"
                                value="<?php echo htmlspecialchars($secret_input); ?>" required>
                            <?php if (isset($errors['secret_code'])): ?>
                                <p class="field-error"><?php echo htmlspecialchars($errors['secret_code']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary report-status-submit">Cek Status</button>
                        </div>
                    </form>
                </div>

                <div class="report-card info-card">
                    <h3>Status Tiket</h3>
                    <ul class="report-tips">
                        <li><span class="status-chip status-baru">Baru</span> laporan diterima.</li>
                        <li><span class="status-chip status-diproses">Diproses</span> tim sedang menindaklanjuti.</li>
                        <li><span class="status-chip status-selesai">Selesai</span> penanganan selesai, cek catatan.</li>
                        <li><span class="status-chip status-ditolak">Ditolak</span> laporan tidak valid/duplikat.</li>
                    </ul>
                    <div class="status-actions">
                        <a href="laporan.php" class="btn-secondary report-status-btn">Kirim Laporan Baru</a>
                    </div>
                </div>
            </div>

            <?php if ($state === 'success' && $report): ?>
                <div class="report-card result-card">
                    <div class="result-header">
                        <div>
                            <p class="label">Ticket ID</p>
                            <h3><?php echo htmlspecialchars($report['ticket_id']); ?></h3>
                            <p class="label">Kategori: <?php echo htmlspecialchars($report['category']); ?></p>
                            <p class="label">Lokasi: <?php echo htmlspecialchars($report['location']); ?></p>
                        </div>
                        <span class="status-badge-large <?php echo htmlspecialchars($report['status']); ?>">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" />
                                <path d="M9 12.5L11 14.5L15 10.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <?php echo htmlspecialchars(format_report_status($report['status'])); ?>
                        </span>
                    </div>

                    <div class="result-body">
                        <div class="detail-row">
                            <span class="label">Deskripsi</span>
                            <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                        </div>

                        <div class="detail-row grid">
                            <div>
                                <span class="label">Kontak</span>
                                <p><?php echo $report['contact'] ? htmlspecialchars($report['contact']) : 'Tidak dicantumkan'; ?>
                                </p>
                            </div>
                            <div>
                                <span class="label">Nama Pengirim</span>
                                <p><?php echo $report['name'] ? htmlspecialchars($report['name']) : 'Anonim'; ?></p>
                            </div>
                        </div>

                        <div class="detail-row grid">
                            <div>
                                <span class="label">Dibuat</span>
                                <p><?php echo date('d M Y H:i', strtotime($report['created_at'])); ?></p>
                            </div>
                            <div>
                                <span class="label">Update Terakhir</span>
                                <p><?php echo date('d M Y H:i', strtotime($report['updated_at'])); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($report['attachment'])): ?>
                            <div class="detail-row">
                                <span class="label">Lampiran</span>
                                <a class="link" href="<?php echo htmlspecialchars($report['attachment']); ?>" target="_blank">Unduh
                                    lampiran</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="status-history">
                        <h4>Riwayat Status</h4>
                        <?php if (empty($logs)): ?>
                            <p class="label">Belum ada riwayat.</p>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="history-item">
                                    <div class="history-top">
                                        <span class="status-badge-large <?php echo htmlspecialchars($log['new_status']); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" />
                                                <path d="M9 12.5L11 14.5L15 10.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <?php echo htmlspecialchars(format_report_status($log['new_status'])); ?>
                                        </span>
                                        <span class="history-date"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></span>
                                    </div>
                                    <?php if (!empty($log['note'])): ?>
                                        <p><?php echo nl2br(htmlspecialchars($log['note'])); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($log['evidence'])): ?>
                                        <a class="link" href="<?php echo htmlspecialchars($log['evidence']); ?>" target="_blank">Lihat
                                            bukti</a>
                                    <?php endif; ?>
                                    <?php if (!empty($log['admin_username'])): ?>
                                        <p class="label">Oleh admin: <?php echo htmlspecialchars($log['admin_username']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>


    <footer>
        <div class="container footer-container">
            <div class="footer-col footer-info">
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo has-image" aria-label="Kembali ke beranda">
                    <img class="footer-logo-img" src="assets/img/logo2.png" alt="Logo Wonders of Berau" loading="lazy">
                    <span class="footer-logo-text">WB</span>
                </a>
                    <div>
                        <h3>Wonders of Berau</h3>
                        <p class="footer-tagline">Menghadirkan keindahan alam dan budaya Berau ke setiap pengunjung.</p>
                    </div>
                </div>
            </div>
            <div class="footer-col footer-links">
                <h4>Link Berguna</h4>
                <div class="footer-links-list">
                    <a href="about.php">Tentang Kami</a>
                    <a href="faq.php">FAQ</a>
                    <a href="contact.php">Kontak</a>
                </div>
            </div>
            <div class="footer-col footer-contact">
                <h4>Hubungi Kami</h4>
                <div class="footer-contact-list">
                    <div class="contact-item">
                        <span class="contact-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </span>
                        <a href="mailto:info@wondersofberau.id">info@wondersofberau.id</a>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.88 19.88 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.88 19.88 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.81.37 1.6.72 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.24a2 2 0 0 1 2.11-.45c.74.35 1.53.6 2.34.72A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </span>
                        <a href="tel:+62554234567">+62 554 234 567</a>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 5.25-9 12-9 12S3 15.25 3 10a9 9 0 1 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </span>
                        <span class="contact-text">Jl. APT Pranoto No. 1, Tanjung Redeb</span>
                    </div>
                </div>
                <span class="footer-hours">Senin - Jumat, 09.00 - 17.00 WITA</span>
            </div>
            <div class="footer-col footer-social-col">
                <h4>Ikuti Kami</h4>
                <div class="footer-social">
                    <a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="4" ry="4"></rect>
                            <circle cx="12" cy="12" r="3.5"></circle>
                            <circle cx="17.5" cy="6.5" r="0.75"></circle>
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13.5 9H15V6.5h-1.5c-1.1 0-2 .9-2 2V10H10v2.5h1.5V20h2.5v-7.5H15L15.5 10H13.5V8.7c0-.4.3-.7.7-.7z"></path>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21.6 8.2s-.2-1.5-.8-2.2c-.8-.8-1.6-.8-2-0.9C16.1 4.8 12 4.8 12 4.8h0s-4.1 0-6.8.3c-.4.1-1.2.1-2 .9-.6.7-.8 2.2-.8 2.2S2 9.9 2 11.6v1c0 1.7.2 3.4.2 3.4s.2 1.5.8 2.2c.8.8 1.9.8 2.4.9 1.8.2 7.6.3 7.6.3s4.1 0 6.8-.3c.4-.1 1.2-.1 2-.9.6-.7.8-2.2.8-2.2s.2-1.7.2-3.4v-1c0-1.7-.2-3.4-.2-3.4zM10 14.7V8.9l5.3 2.9L10 14.7z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-left">&copy; 2025 Wonders of Berau. All Rights Reserved.</div>
            <div class="footer-bottom-links">
                <a href="privacy-policy.php">Privacy Policy</a>
                <span class="divider"></span>
                <a href="terms-and-conditions.php">Terms and Conditions</a>
            </div>
        </div>
    </footer>


    <script src="assets/js/translator.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>







