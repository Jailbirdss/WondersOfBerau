<?php
require_once __DIR__ . '/../app/config.php';

$categories = $report_categories ?? array(
    'Kebersihan & Fasilitas',
    'Pelayanan Petugas',
    'Keamanan & Ketertiban',
    'Harga/Tiket',
    'Akses & Transportasi',
    'Lainnya'
);

$location_options = array();
$location_groups = array();
$location_sources = array(
    array('label' => 'Destinasi', 'sql' => "SELECT nama FROM destinasi"),
    array('label' => 'Akomodasi', 'sql' => "SELECT nama FROM akomodasi"),
    array('label' => 'Kuliner', 'sql' => "SELECT nama FROM kuliner"),
    array('label' => 'Event', 'sql' => "SELECT nama FROM events")
);
foreach ($location_sources as $source) {
    $items = array();
    $res = @mysqli_query($conn, $source['sql']);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['nama'])) {
                $items[] = $row['nama'];
                $location_options[] = $row['nama'];
            }
        }
    }
    if (!empty($items)) {
        sort($items);
        $location_groups[] = array(
            'label' => $source['label'],
            'items' => $items
        );
    }
}
$location_options = array_values(array_unique($location_options));
sort($location_options);

$errors = array();
$success_info = null;

if (isset($_SESSION['report_success'])) {
    $success_info = $_SESSION['report_success'];
    unset($_SESSION['report_success']);
}

$input = array(
    'category' => '',
    'location' => '',
    'description' => '',
    'name' => '',
    'contact' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'report_form')) {
        $errors['general'] = 'Sesi kadaluarsa, silakan muat ulang halaman dan coba lagi.';
    }

    $input['category'] = trim($_POST['category'] ?? '');
    $input['location'] = trim($_POST['location'] ?? '');
    $input['description'] = trim($_POST['description'] ?? '');
    $input['name'] = trim($_POST['name'] ?? '');
    $input['contact'] = trim($_POST['contact'] ?? '');

    if (empty($input['category']) || !in_array($input['category'], $categories)) {
        $errors['category'] = 'Pilih kategori laporan.';
    }

    if (empty($input['location']) || !in_array($input['location'], $location_options)) {
        $errors['location'] = 'Pilih lokasi/objek wisata yang tersedia.';
    }

    if (strlen($input['description']) < 20) {
        $errors['description'] = 'Deskripsi minimal 20 karakter agar kami bisa menindaklanjuti.';
    }

    if (empty($input['name'])) {
        $errors['name'] = 'Nama wajib diisi.';
    }

    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_report_file($_FILES['attachment'], 'reports');
        if (!$upload['success']) {
            $errors['attachment'] = $upload['message'];
        } else {
            $attachment_path = $upload['path'];
        }
    }

    if (empty($errors)) {
        $ticket_id = generate_ticket_id();
        $secret_code = generate_secret_code(8);
        $secret_hash = password_hash($secret_code, PASSWORD_DEFAULT);
        $note = 'Laporan dikirim oleh pengunjung.';

        $stmt = mysqli_prepare($conn, "INSERT INTO reports (ticket_id, secret_code_hash, category, location, description, attachment, name, contact, last_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssss',
            $ticket_id,
            $secret_hash,
            $input['category'],
            $input['location'],
            $input['description'],
            $attachment_path,
            $input['name'],
            $input['contact'],
            $note
        );

        if (mysqli_stmt_execute($stmt)) {
            $report_id = mysqli_insert_id($conn);
            $log_stmt = mysqli_prepare($conn, "INSERT INTO report_status_logs (report_id, old_status, new_status, note) VALUES (?, 'baru', 'baru', ?)");
            mysqli_stmt_bind_param($log_stmt, 'is', $report_id, $note);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            $_SESSION['report_success'] = array(
                'ticket_id' => $ticket_id,
                'secret_code' => $secret_code,
                'category' => $input['category'],
                'location' => $input['location']
            );
            header("Location: laporan.php?sent=1");
            exit;
        } else {
            $errors['general'] = 'Terjadi kesalahan saat menyimpan laporan. Coba lagi.';
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan / Komplain Wisata - Wonders of Berau</title>
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
                <h1 class="page-title">Laporan / Komplain Wisata</h1>
                <p class="section-subtitle">Sampaikan kendala atau masukan Anda. Kami gunakan untuk meningkatkan
                    pengalaman wisata di Berau.</p>
            </div>
        </section>

        <section class="container section page-animate">
            <?php if (!empty($success_info)): ?>
                <div class="report-alert success">
                    <h3>Laporan terkirim!</h3>
                    <p>Catat informasi berikut untuk cek progres:</p>
                    <div class="ticket-meta">
                        <div>
                            <span class="label">Ticket ID</span>
                            <strong><?php echo htmlspecialchars($success_info['ticket_id']); ?></strong>
                        </div>
                        <div>
                            <span class="label">Kode Unik</span>
                            <strong><?php echo htmlspecialchars($success_info['secret_code']); ?></strong>
                        </div>
                    </div>
                    <p class="ticket-note">Kode unik hanya ditampilkan sekali. Simpan baik-baik untuk cek status
                        laporan.</p>
                    <a class="btn-primary" href="status-laporan.php?ticket_id=<?php echo urlencode($success_info['ticket_id']); ?>">Cek
                        Status</a>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="report-alert error">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

                    <div class="report-layout">
                <div class="report-card">
                    <h3>Kirim Laporan</h3>
                    <form method="POST" enctype="multipart/form-data" class="report-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('report_form')); ?>">
                        <div class="form-group <?php echo isset($errors['category']) ? 'has-error' : ''; ?>">
                            <label for="category">Kategori <span class="required">*</span></label>
                            <select name="category" id="category" required>
                                <option value="">Pilih kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $input['category'] === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category'])): ?>
                                <p class="field-error"><?php echo htmlspecialchars($errors['category']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['location']) ? 'has-error' : ''; ?>">
                            <label for="location">Lokasi / Objek Wisata <span class="required">*</span></label>
                            <select name="location" id="location" required>
                                <option value="">Pilih lokasi/objek</option>
                                <?php foreach ($location_groups as $group): ?>
                                    <optgroup label="<?php echo htmlspecialchars($group['label']); ?>">
                                        <?php foreach ($group['items'] as $loc): ?>
                                            <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $input['location'] === $loc ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($loc); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['location'])): ?>
                                <p class="field-error"><?php echo htmlspecialchars($errors['location']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['description']) ? 'has-error' : ''; ?>">
                            <label for="description">Deskripsi <span class="required">*</span></label>
                            <textarea name="description" id="description" rows="5" minlength="20" required
                                placeholder="Jelaskan situasi, waktu kejadian, dan detail lain."><?php echo htmlspecialchars($input['description']); ?></textarea>
                            <div class="helper-text">Minimal 20 karakter.</div>
                            <?php if (isset($errors['description'])): ?>
                                <p class="field-error"><?php echo htmlspecialchars($errors['description']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group <?php echo isset($errors['name']) ? 'has-error' : ''; ?>">
                                <label for="name">Nama <span class="required">*</span></label>
                                <input type="text" name="name" id="name" placeholder="Nama lengkap"
                                    value="<?php echo htmlspecialchars($input['name']); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors['name']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="contact">Kontak (opsional)</label>
                                <input type="text" name="contact" id="contact" placeholder="Email / WhatsApp"
                                    value="<?php echo htmlspecialchars($input['contact']); ?>">
                            </div>
                        </div>

                        <div class="form-group <?php echo isset($errors['attachment']) ? 'has-error' : ''; ?>">
                            <label for="attachment">Lampiran (opsional)</label>
                            <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf">
                            <div class="helper-text">Foto/PDF maks. 5MB.</div>
                            <?php if (isset($errors['attachment'])): ?>
                                <p class="field-error"><?php echo htmlspecialchars($errors['attachment']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary report-submit">Kirim Laporan</button>
                        </div>
                    </form>
                </div>

                <div class="report-card info-card">
                    <h3>Tips Laporan Cepat Ditangani</h3>
                    <ul class="report-tips">
                        <li>Sertakan waktu kejadian dan lokasi spesifik.</li>
                        <li>Unggah foto pendukung jika ada.</li>
                        <li>Gunakan kontak aktif agar tim bisa menghubungi Anda bila perlu.</li>
                        <li>Kode unik dibutuhkan untuk memantau progres tiket.</li>
                    </ul>
                    <div class="status-sample">
                        <span class="status-chip status-baru">Baru</span>
                        <span class="status-chip status-diproses">Diproses</span>
                        <span class="status-chip status-selesai">Selesai</span>
                        <span class="status-chip status-ditolak">Ditolak</span>
                    </div>
                    <div class="status-actions">
                        <a href="status-laporan.php" class="btn-secondary status-check-btn">Cek Status</a>
                    </div>
                </div>
            </div>
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







