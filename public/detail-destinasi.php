<?php
require_once __DIR__ . '/../app/config.php';

if (!isset($_GET['id'])) {
    redirect('destinasi.php');
}

$id = intval($_GET['id']);
$destinasi = get_destinasi($id);

if (!$destinasi) {
    redirect('destinasi.php');
}

$gallery = get_gallery($id, 'destinasi');

$dos_list = !empty($destinasi['dos']) ? explode('|', $destinasi['dos']) : [];
$donts_list = !empty($destinasi['donts']) ? explode('|', $destinasi['donts']) : [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Destinasi: <?php echo htmlspecialchars($destinasi['nama']); ?> - Wonders of Berau</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

    <header>
        <nav class="navbar">
            <a href="index.php" class="nav-logo">Wonders of Berau</a>
            <ul class="nav-menu">
                <li class="nav-item"><a href="destinasi.php" class="nav-link active">Destinasi</a></li>
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
        <section class="detail-hero"
            style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo htmlspecialchars($destinasi['gambar_utama']); ?>');">
            <div class="container">
                <h1 class="page-title"><?php echo htmlspecialchars($destinasi['nama']); ?></h1>
                <p class="section-subtitle" style="color: #fff;">
                    <?php echo htmlspecialchars($destinasi['deskripsi']); ?>
                </p>
            </div>
        </section>

        <section class="container section">
            <div class="detail-grid">
                <div class="detail-content">
                    <h2 class="section-title" style="text-align: left; font-size: 2rem;">Deskripsi</h2>
                    <p><?php echo nl2br(htmlspecialchars($destinasi['deskripsi_lengkap'])); ?></p>
                    <button class="btn-secondary share-btn"
                        data-title="<?php echo htmlspecialchars($destinasi['nama']); ?>"
                        data-text="Yuk cek destinasi ini!" data-url="detail-destinasi.php?id=<?php echo $id; ?>"
                        aria-label="Bagikan halaman ini">Bagikan
                    </button>
                </div>
                <div class="detail-gallery">
                    <h2 class="section-title" style="text-align: left; font-size: 2rem;">Galeri Foto</h2>
                    <div class="gallery-grid">
                        <?php if (count($gallery) > 0): ?>
                            <?php foreach ($gallery as $foto): ?>
                                <img src="<?php echo htmlspecialchars($foto['gambar']); ?>"
                                    alt="<?php echo htmlspecialchars($foto['caption']); ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x300.png?text=Galeri+1" alt="Galeri 1">
                            <img src="https://via.placeholder.com/400x300.png?text=Galeri+2" alt="Galeri 2">
                            <img src="https://via.placeholder.com/400x300.png?text=Galeri+3" alt="Galeri 3">
                            <img src="https://via.placeholder.com/400x300.png?text=Galeri+4" alt="Galeri 4">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="container section info-section">
            <h2 class="section-title">Informasi Kunjungan</h2>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fa-solid fa-ticket"></i>
                    <h3>Tiket Masuk</h3>
                    <p><?php echo htmlspecialchars($destinasi['tiket_masuk']); ?></p>
                </div>
                <div class="info-item">
                    <i class="fa-solid fa-bell-concierge"></i>
                    <h3>Fasilitas</h3>
                    <p><?php echo htmlspecialchars($destinasi['fasilitas']); ?></p>
                </div>
                <div class="info-item">
                    <i class="fa-solid fa-clock"></i>
                    <h3>Jam Buka</h3>
                    <p><?php echo htmlspecialchars($destinasi['jam_buka']); ?></p>
                </div>
            </div>
        </section>

        <?php if (!empty($dos_list) || !empty($donts_list)): ?>
            <section class="container section">
                <h2 class="section-title">Panduan Do's & Don'ts</h2>
                <div class="dos-donts-grid">
                    <?php if (!empty($dos_list)): ?>
                        <div class="dos-list">
                            <h3><i class="fa-solid fa-circle-check"></i> Yang Boleh Dilakukan (Do's)</h3>
                            <ul>
                                <?php foreach ($dos_list as $dos): ?>
                                    <li><?php echo htmlspecialchars($dos); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($donts_list)): ?>
                        <div class="donts-list">
                            <h3><i class="fa-solid fa-circle-xmark"></i> Yang Tidak Boleh Dilakukan (Don'ts)</h3>
                            <ul>
                                <?php foreach ($donts_list as $dont): ?>
                                    <li><?php echo htmlspecialchars($dont); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($destinasi['maps_embed'])): ?>
            <section class="container section">
                <h2 class="section-title">Lokasi & Peta Digital</h2>
                <div class="map-container">
                    <iframe src="<?php echo htmlspecialchars($destinasi['maps_embed']); ?>" width="100%" height="450"
                        style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </section>
        <?php endif; ?>

        <?php
        $refType = 'destinasi';
        $itemId = $id;
        $itemName = $destinasi['nama'];
        include __DIR__ . '/partials/review-section.php';
        ?>
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
    <script src="assets/js/reviews.js"></script>
    <script src="assets/js/analytics.js"></script>
</body>

</html>







