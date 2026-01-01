<?php
require_once __DIR__ . '/../app/config.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Wonders of Berau</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="legal-page">
    <header>
        <nav class="navbar">
            <a href="index.php" class="nav-logo">Wonders of Berau</a>
            <ul class="nav-menu">
                <li class="nav-item"><a href="destinasi.php" class="nav-link">Destinasi</a></li>
                <li class="nav-item"><a href="akomodasikuliner.php" class="nav-link">Akomodasi &amp; Kuliner</a></li>
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
        <section class="legal-hero page-animate animate-on-scroll" data-delay="0"
            style="background-image: linear-gradient(135deg, rgba(7, 18, 40, 0.72), rgba(10, 28, 58, 0.6)), url('https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=1600&q=80');">
            <div class="container legal-hero-inner">
                <h1 class="animate-on-scroll" data-delay="80">Privacy Policy</h1>
                <p class="legal-lead animate-on-scroll" data-delay="140">Bagaimana kami mengumpulkan, memakai, dan menjaga data pribadi saat Anda
                    menjelajahi Wonders of Berau.</p>
                <div class="legal-meta animate-on-scroll" data-delay="200">
                    <span>Efektif untuk seluruh pengguna Wonders of Berau</span>
                </div>
            </div>
        </section>

        <section class="section legal-content animate-on-scroll" data-delay="60">
            <div class="container legal-grid">
                <article class="legal-card animate-on-scroll" data-delay="0">
                    <div class="legal-card-head">
                        <span class="legal-chip">01</span>
                        <div>
                            <p class="legal-card-label">Ruang Lingkup</p>
                            <h2>Informasi yang Kami Kumpulkan</h2>
                        </div>
                    </div>
                    <p class="legal-subtitle">Hanya data seperlunya untuk menjalankan layanan.</p>
                    <ul class="legal-list">
                        <li>Formulir: nama, email/telepon, dan detail laporan/pesan.</li>
                        <li>Penggunaan dasar: perangkat, browser, dan interaksi untuk optimasi performa.</li>
                        <li>Cookies fungsional: bahasa dan mode tampilan agar pengalaman tetap nyaman.</li>
                    </ul>
                </article>

                <article class="legal-card animate-on-scroll" data-delay="80">
                    <div class="legal-card-head">
                        <span class="legal-chip">02</span>
                        <div>
                            <p class="legal-card-label">Pemakaian</p>
                            <h2>Pemakaian &amp; Berbagi Data</h2>
                        </div>
                    </div>
                    <p class="legal-subtitle">Agar layanan tetap aman, responsif, dan relevan.</p>
                    <ul class="legal-list">
                        <li>Menjawab pertanyaan, memproses laporan, dan menindaklanjuti bila perlu.</li>
                        <li>Memantau performa serta mencegah penyalahgunaan atau aktivitas mencurigakan.</li>
                        <li>Tidak menjual data pribadi; berbagi hanya dengan mitra teknologi yang terikat kerahasiaan.</li>
                    </ul>
                </article>

                <article class="legal-card animate-on-scroll" data-delay="160">
                    <div class="legal-card-head">
                        <span class="legal-chip">03</span>
                        <div>
                            <p class="legal-card-label">Keamanan</p>
                            <h2>Penyimpanan &amp; Keamanan</h2>
                        </div>
                    </div>
                    <p class="legal-subtitle">Dijaga dengan kontrol akses terbatas.</p>
                    <ul class="legal-list">
                        <li>Data disimpan di server terawasi dengan kontrol akses berlapis.</li>
                        <li>Akses dibatasi hanya untuk tim berwenang sesuai kebutuhan operasional.</li>
                        <li>Bila ada insiden, kami akan memberi tahu dan memperbaiki secepatnya.</li>
                    </ul>
                </article>

                <article class="legal-card animate-on-scroll" data-delay="240">
                    <div class="legal-card-head">
                        <span class="legal-chip">04</span>
                        <div>
                            <p class="legal-card-label">Hak &amp; Kontak</p>
                            <h2>Hak Anda &amp; Kontak</h2>
                        </div>
                    </div>
                    <p class="legal-subtitle">Anda tetap memegang kendali.</p>
                    <ul class="legal-list">
                        <li>Meminta salinan atau pembaruan data yang pernah dikirim.</li>
                        <li>Meminta penghapusan data sesuai ketentuan yang berlaku.</li>
                        <li>Mengirim pertanyaan privasi via email atau formulir kontak.</li>
                    </ul>
                    <div class="legal-cta">
                        <span class="legal-cta-label">Butuh klarifikasi?</span>
                        <a class="btn" href="contact.php">Hubungi Kami</a>
                    </div>
                </article>
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
                <a href="privacy-policy.php" class="active">Privacy Policy</a>
                <span class="divider"></span>
                <a href="terms-and-conditions.php">Terms and Conditions</a>
            </div>
        </div>
    </footer>

    <script src="assets/js/translator.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>






