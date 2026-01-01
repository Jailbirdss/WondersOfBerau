<?php
require_once __DIR__ . '/../app/config.php';

function wb_count($sql)
{
    global $conn;
    $result = mysqli_query($conn, $sql);
    if ($result && ($row = mysqli_fetch_row($result))) {
        return (int) $row[0];
    }
    return 0;
}

$dest_count = wb_count("SELECT COUNT(*) FROM destinasi WHERE status = 'aktif'");
$akom_count = wb_count("SELECT COUNT(*) FROM akomodasi WHERE status = 'aktif'");
$kuliner_count = wb_count("SELECT COUNT(*) FROM kuliner WHERE status = 'aktif'");
$event_count = wb_count("SELECT COUNT(*) FROM events WHERE status = 'aktif'");
$umkm_count = wb_count("SELECT COUNT(*) FROM kuliner WHERE status = 'aktif' AND kategori = 'umkm'");

$total_akom_kuliner = $akom_count + $kuliner_count;
$total_mitra_umkm = $umkm_count + $akom_count;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Wonders of Berau</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/about.css?v=<?php echo time(); ?>">
</head>

<body class="about-page">
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
        <section class="about-hero page-animate animate-on-scroll" data-delay="0"
            style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.55)), url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1800&q=80');">
            <div class="container about-hero-inner">
                <p class="about-hero-kicker">Tentang Wonders of Berau</p>
                <h1>Tentang Kami</h1>
                <div class="about-hero-taglines">
                    <p class="about-hero-tagline">Portal destinasi, event, akomodasi, dan kuliner Berau dalam satu
                        halaman.</p>
                    <p class="about-hero-tagline alt">Panduan tepercaya menjelajah laut, hutan, budaya, hingga tips
                        perjalanan.</p>
                </div>
            </div>
        </section>

        <section class="section about-overview animate-on-scroll" data-delay="50">
            <div class="container">
                <div class="about-overview-grid">
                    <div class="about-copy animate-on-scroll" data-delay="120">
                        <p class="eyebrow">Mengapa kami ada</p>
                        <h2 class="section-title-left">About Wonders of Berau</h2>
                        <p class="about-lead">Wonders of Berau adalah teman perjalanan digital yang merangkum info resmi
                            dan cerita lapangan tentang destinasi, event, akomodasi, dan kuliner di Berau.</p>
                        <p class="about-detail">Data kami diverifikasi dan diperbarui rutin, lalu disajikan lewat
                            pencarian, tab Akomodasi & Kuliner, kalender event, kanal laporan, serta FAQ. Tujuannya
                            sederhana: perjalananmu lebih jelas, praktis, dan memberi manfaat balik bagi warga lokal.
                        </p>
                        <ul class="about-benefits">
                            <li>
                                <span class="benefit-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="m9 12 2 2 4-4"></path>
                                    </svg>
                                </span>
                                Rekomendasi destinasi unggulan lengkap dengan akses, peta, dan tips singkat.
                            </li>
                            <li>
                                <span class="benefit-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="m9 12 2 2 4-4"></path>
                                    </svg>
                                </span>
                                Tab penginapan & kuliner pilihan lokal plus kontak yang bisa dihubungi.
                            </li>
                            <li>
                                <span class="benefit-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="m9 12 2 2 4-4"></path>
                                    </svg>
                                </span>
                                Kalender event terkini, laporan pengunjung, dan FAQ responsif untuk kebutuhan cepat.
                            </li>
                        </ul>
                    </div>
                    <div class="about-highlight-card animate-on-scroll" data-delay="180">
                        <div class="about-highlight">
                            <p class="eyebrow">Fokus utama</p>
                            <h3>Memberi satu pintu informasi yang praktis untuk wisatawan dan mitra lokal.</h3>
                            <p class="about-highlight-desc">Dari inspirasi destinasi, jadwal event, hingga akses kontak
                                dan form laporan, semuanya diringkas agar keputusan perjalanan jadi cepat dan terarah.
                            </p>
                            <div class="about-mini-pills">
                                <span>Destinasi pilihan</span>
                                <span>Info terverifikasi</span>
                                <span>Kolaborasi lokal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section about-gallery animate-on-scroll" data-delay="80">
            <div class="container">
                <div class="about-section-header animate-on-scroll" data-delay="120">
                    <p class="eyebrow">Galeri Unggulan</p>
                    <h2 class="section-title-left">Ragam Pesona Berau</h2>
                    <p class="section-subtitle">Sapuan laut, danau jernih, pasar kuliner, festival kampung, sampai
                        senyum ramah warga semuanya dirangkum di sini.</p>
                </div>
                <div class="about-gallery-grid">
                    <div class="gallery-card animate-on-scroll" data-delay="140"
                        style="background-image: linear-gradient(180deg, rgba(6, 12, 24, 0.32), rgba(6, 12, 24, 0.52)), url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1400&q=85');">
                        <span>Laut & Pulau</span>
                    </div>
                    <div class="gallery-card animate-on-scroll" data-delay="180"
                        style="background-image: linear-gradient(180deg, rgba(6, 12, 24, 0.32), rgba(6, 12, 24, 0.52)), url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1400&q=85');">
                        <span>Danau & Sungai</span>
                    </div>
                    <div class="gallery-card animate-on-scroll" data-delay="220"
                        style="background-image: linear-gradient(180deg, rgba(6, 12, 24, 0.32), rgba(6, 12, 24, 0.52)), url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=85');">
                        <span>Kuliner Lokal</span>
                    </div>
                    <div class="gallery-card animate-on-scroll" data-delay="260"
                        style="background-image: linear-gradient(180deg, rgba(6, 12, 24, 0.32), rgba(6, 12, 24, 0.52)), url('assets/img/festivalevent.png');">
                        <span>Festival & Event</span>
                    </div>
                    <div class="gallery-card animate-on-scroll" data-delay="300"
                        style="background-image: linear-gradient(180deg, rgba(6, 12, 24, 0.32), rgba(6, 12, 24, 0.52)), url('assets/img/keramahan.png');">
                        <span>Keramahan Lokal</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section about-values animate-on-scroll" data-delay="80">
            <div class="container">
                <div class="about-section-header animate-on-scroll" data-delay="120">
                    <p class="eyebrow">Nilai Kami</p>
                    <h2 class="section-title-left">4 Hal yang Kami Junjung</h2>
                </div>
                <div class="values-grid">
                    <div class="value-card animate-on-scroll" data-delay="140">
                        <div class="value-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 11l9-9 9 9"></path>
                                <path d="M4 10v10a1 1 0 0 0 1 1h4v-5h6v5h4a1 1 0 0 0 1-1V10"></path>
                            </svg>
                        </div>
                        <h3>Destinasi Pilihan</h3>
                        <p>Destinasi unggulan dikurasi tim lapangan agar rute perjalanan lebih ringkas, aman, dan tetap
                            bernilai.</p>
                    </div>
                    <div class="value-card animate-on-scroll" data-delay="180">
                        <div class="value-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l3 3"></path>
                            </svg>
                        </div>
                        <h3>Info Terkini</h3>
                        <p>Pembaruan berkala mencakup akses transportasi, musim kunjungan terbaik, hingga panduan etika
                            dan keamanan.</p>
                    </div>
                    <div class="value-card animate-on-scroll" data-delay="220">
                        <div class="value-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"></path>
                                <circle cx="12" cy="12" r="9"></circle>
                            </svg>
                        </div>
                        <h3>Kerja Bareng Lokal</h3>
                        <p>Kami bermitra dengan UMKM, pemandu, dan penyelenggara lokal supaya manfaat wisata kembali ke
                            masyarakat.</p>
                    </div>
                    <div class="value-card animate-on-scroll" data-delay="260">
                        <div class="value-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="15" rx="3"></rect>
                                <path d="M7 8h10M7 12h6"></path>
                            </svg>
                        </div>
                        <h3>Pengalaman Mudah</h3>
                        <p>Antarmuka ringkas dengan peta jelas, tombol aksi tegas, mode gelap, dan terjemahan otomatis
                            siap digunakan.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section about-team animate-on-scroll" data-delay="100">
            <div class="container about-team-grid">
                <div class="animate-on-scroll" data-delay="140">
                    <p class="eyebrow">Cerita Kami</p>
                    <h2 class="section-title-left">Dibangun oleh komunitas</h2>
                    <p class="about-detail">Wonders of Berau tumbuh bersama pelaku wisata, UMKM, content creator, dan EO
                        di Berau. Kami berbagi data lapangan, menyiapkan panduan singkat di setiap detail destinasi,
                        serta merespons pertanyaan lewat FAQ dan formulir kontak agar wisatawan merasa diterima sejak
                        pertama kali mengklik.</p>
                </div>
                <div class="about-stats animate-on-scroll" data-delay="180">
                    <div class="stat-item animate-on-scroll" data-delay="160">
                        <span class="stat-number" data-target="<?php echo $dest_count; ?>" data-suffix="+">0+</span>
                        <span class="stat-label">Destinasi & spot aktif</span>
                    </div>
                    <div class="stat-item animate-on-scroll" data-delay="190">
                        <span class="stat-number" data-target="<?php echo $total_akom_kuliner; ?>"
                            data-suffix="+">0+</span>
                        <span class="stat-label">Kuliner & akomodasi terpilih</span>
                    </div>
                    <div class="stat-item animate-on-scroll" data-delay="220">
                        <span class="stat-number" data-target="<?php echo $event_count; ?>" data-suffix="+">0+</span>
                        <span class="stat-label">Event & festival aktif</span>
                    </div>
                    <div class="stat-item animate-on-scroll" data-delay="250">
                        <span class="stat-number" data-target="<?php echo $total_mitra_umkm; ?>"
                            data-suffix="+">0+</span>
                        <span class="stat-label">Mitra lokal & UMKM</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-cta">
            <div class="container about-cta-inner">
                <div>
                    <p class="eyebrow">Kolaborasi</p>
                    <h2>Ingin bermitra atau butuh info wisata?</h2>
                    <p class="about-cta-desc">Kami terbuka untuk kolaborasi bersama brand, media, EO, dan komunitas
                        perjalanan.</p>
                </div>
                <div class="about-cta-actions">
                    <a class="btn-primary" href="contact.php">Hubungi Kami</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-container">
            <div class="footer-col footer-info">
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo has-image" aria-label="Kembali ke beranda">
                        <img class="footer-logo-img" src="assets/img/logo2.png" alt="Logo Wonders of Berau"
                            loading="lazy">
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
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                </path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </span>
                        <a href="mailto:info@wondersofberau.id">info@wondersofberau.id</a>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.88 19.88 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.88 19.88 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.81.37 1.6.72 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.24a2 2 0 0 1 2.11-.45c.74.35 1.53.6 2.34.72A2 2 0 0 1 22 16.92z">
                                </path>
                            </svg>
                        </span>
                        <a href="tel:+62554234567">+62 554 234 567</a>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="4" ry="4"></rect>
                            <circle cx="12" cy="12" r="3.5"></circle>
                            <circle cx="17.5" cy="6.5" r="0.75"></circle>
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M13.5 9H15V6.5h-1.5c-1.1 0-2 .9-2 2V10H10v2.5h1.5V20h2.5v-7.5H15L15.5 10H13.5V8.7c0-.4.3-.7.7-.7z">
                            </path>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M21.6 8.2s-.2-1.5-.8-2.2c-.8-.8-1.6-.8-2-0.9C16.1 4.8 12 4.8 12 4.8h0s-4.1 0-6.8.3c-.4.1-1.2.1-2 .9-.6.7-.8 2.2-.8 2.2S2 9.9 2 11.6v1c0 1.7.2 3.4.2 3.4s.2 1.5.8 2.2c.8.8 1.9.8 2.4.9 1.8.2 7.6.3 7.6.3s4.1 0 6.8-.3c.4-.1 1.2-.1 2-.9.6-.7.8-2.2.8-2.2s.2-1.7.2-3.4v-1c0-1.7-.2-3.4-.2-3.4zM10 14.7V8.9l5.3 2.9L10 14.7z">
                            </path>
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