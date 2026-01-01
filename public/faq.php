<?php

require_once __DIR__ . '/../app/config.php';

?>

<!DOCTYPE html>

<html lang="id">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FAQ - Wonders of Berau</title>

    <link rel="stylesheet" href="assets/css/style.css">

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

        <section class="faq-hero page-header">

            <div class="container">


                <h1 class="page-title">FAQ Umum</h1>

                <p class="section-subtitle">Informasi dasar seputar destinasi, akses, tiket, hingga keamanan yang sering

                    ditanyakan pengunjung Wonders of Berau.</p>

            </div>

        </section>



        <section class="section faq-section">

            <div class="container faq-layout">

                <div class="faq-left">

                    <div class="faq-intro">

                        <p class="faq-kicker">Pertanyaan Populer</p>

                        <h2 class="section-title">Mulai dari dasar, sampai tips berkunjung</h2>

                        <p class="section-subtitle">Klik setiap pertanyaan untuk membuka jawaban. Kami rangkum poin

                            penting supaya Anda bisa menyiapkan perjalanan dengan tenang.</p>

                    </div>



                    <div class="faq-accordion">

                        <div class="faq-item open">

                            <button class="faq-question" type="button" aria-expanded="true">

                                <div>

                                    <span class="faq-tag">Pencarian Destinasi</span>

                                    <h3>Cara menemukan destinasi sesuai minat saya?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Gunakan kolom pencarian di kanan atas untuk mengetik nama tempat, kuliner, atau

                                    event. Untuk inspirasi cepat, buka halaman <a href="destinasi.php">Destinasi</a> dan

                                    pilih destinasi unggulan, atau cek <a href="akomodasikuliner.php">Akomodasi &

                                        Kuliner</a> bila ingin mencari tempat menginap dan kuliner lokal.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Jam Operasional</span>

                                    <h3>Jam operasional destinasi wisata secara umum?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Sebagian besar destinasi alam dibuka sekitar 07.00&ndash;17.00 WITA. Beberapa area pulau

                                    dapat dikunjungi lebih pagi untuk mengejar sunrise, sedangkan tur laut biasanya

                                    mengikuti jadwal kapal (pagi&ndash;sore). Pastikan mengecek detail jadwal pada halaman

                                    destinasi atau hubungi pengelola sebelum berangkat.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Tiket & Biaya</span>

                                    <h3>Bagaimana informasi tiket masuk atau biaya tur?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Harga tiket dapat berbeda antar lokasi. Untuk gambaran umum:</p>

                                <ul>

                                    <li>Destinasi alam: terdapat tiket masuk/kontribusi konservasi di pintu masuk atau

                                        dermaga.</li>

                                    <li>Tur laut/pulau: biaya per kapal atau per orang, tergantung rute dan fasilitas.

                                    </li>

                                    <li>Event/festival: cek detail harga pada halaman <a href="event.php">Event</a>

                                        atau di loket resmi.</li>

                                </ul>

                                <p>Sebaiknya siapkan uang tunai secukupnya karena beberapa titik belum mendukung

                                    pembayaran digital.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Akses & Transport</span>

                                    <h3>Bagaimana akses menuju Berau dan berpindah antar destinasi?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <ul>

                                    <li><strong>Bandara Kalimarau (BEJ) &ndash; pusat kota:</strong> sekitar 20&ndash;30 menit naik taksi,

                                        travel, atau sewa mobil.</li>

                                    <li><strong>Arah pulau Derawan/Maratua:</strong> menuju Tanjung Batu, lalu sambung

                                        speedboat sesuai jadwal setempat.</li>

                                    <li><strong>Dalam kota:</strong> gunakan sewa kendaraan, ojek/ride-hailing lokal,

                                        atau charter kendaraan wisata.</li>

                                </ul>

                                <p>Pastikan memesan transportasi lebih awal pada musim ramai atau saat ada event

                                    besar.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Parkir</span>

                                    <h3>Apakah tersedia area parkir?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Area parkir tersedia di dermaga utama, pusat kota, dan beberapa gerbang destinasi.

                                    Kapasitas bisa terbatas saat akhir pekan atau musim liburan, jadi datang lebih pagi

                                    disarankan. Ikuti rambu petugas parkir dan hindari memarkir di zona konservasi.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Fasilitas Umum</span>

                                    <h3>Fasilitas apa yang tersedia untuk pengunjung?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <ul>

                                    <li>Toilet, mushola, dan ruang ganti umumnya tersedia di dermaga/pintu masuk

                                        destinasi populer.</li>

                                    <li>ATM tersedia di pusat kota; bawa uang tunai saat menuju pulau/daerah terpencil.

                                    </li>

                                    <li>Beberapa destinasi menyediakan loker sederhana; bawa tas kedap air untuk tur

                                        laut.</li>

                                </ul>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Waktu Terbaik</span>

                                    <h3>Kapan waktu kunjungan yang direkomendasikan?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <ul>

                                    <li><strong>Pagi (07.00&ndash;10.00):</strong> cocok untuk jelajah pantai, snorkeling,

                                        atau trekking ringan.</li>

                                    <li><strong>Sore (15.00&ndash;17.30):</strong> cuaca lebih teduh untuk menikmati sunset

                                        dan kuliner.</li>

                                    <li><strong>Musim kemarau:</strong> umumnya April&ndash;Oktober dengan gelombang laut lebih

                                        tenang.</li>

                                </ul>

                                <p>Hindari jadwal ketat saat musim hujan; sisakan waktu cadangan untuk perubahan cuaca

                                    atau jadwal kapal.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Cuaca & Musim</span>

                                    <h3>Bagaimana kondisi cuaca di Berau?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Suhu harian berkisar 26&ndash;32&deg;C dengan kelembapan tinggi. Bawa topi, sunscreen,

                                    kacamata hitam, serta jas hujan ringan di musim penghujan. Selalu pantau prakiraan

                                    cuaca dan informasi gelombang sebelum beraktivitas laut.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Keamanan</span>

                                    <h3>Apa yang perlu diperhatikan soal keamanan & etika?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <ul>

                                    <li>Ikuti instruksi pemandu dan gunakan pelampung saat tur laut.</li>

                                    <li>Jaga kebersihan: bawa pulang sampah, hindari menyentuh terumbu karang.</li>

                                    <li>Hormati budaya lokal dan wilayah konservasi; hindari membuat kebisingan di area

                                        satwa.</li>

                                </ul>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Event</span>

                                    <h3>Bagaimana cara ikut event atau festival?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Lihat jadwal lengkap di halaman <a href="event.php">Event</a>. Tiap acara memuat

                                    tanggal, lokasi, dan tautan pendaftaran/tiket jika diperlukan. Datang lebih awal

                                    untuk menghindari antrean dan siapkan uang tunai untuk stan lokal.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Lapor & Komplain</span>

                                    <h3>Bagaimana cara melapor atau menyampaikan komplain?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Buka halaman <a href="laporan.php">Laporan</a> untuk mengirimkan kendala, kehilangan,

                                    atau saran. Sertakan detail lokasi, waktu, foto/dukungan lain agar tim kami bisa

                                    menindaklanjuti lebih cepat.</p>

                            </div>

                        </div>



                        <div class="faq-item">

                            <button class="faq-question" type="button" aria-expanded="false">

                                <div>

                                    <span class="faq-tag">Kontak Bantuan</span>

                                    <h3>Siapa yang bisa dihubungi saat membutuhkan bantuan?</h3>

                                </div>

                                <span class="faq-icon" aria-hidden="true"></span>

                            </button>

                            <div class="faq-answer">

                                <p>Hubungi kami melalui:</p>

                                <ul>

                                    <li>Email: <a href="mailto:info@wondersofberau.id">info@wondersofberau.id</a></li>

                                    <li>Telepon/WhatsApp: <a href="tel:+62554234567">+62 554 234 567</a> (Senin&ndash;Jumat,

                                        09.00&ndash;17.00 WITA)</li>

                                    <li>Form <a href="contact.php">Kontak</a> untuk pertanyaan kemitraan atau

                                        dukungan perjalanan.</li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>



                <aside class="faq-right">

                    <div class="faq-card">

                        <p class="faq-kicker">Butuh jawaban cepat?</p>

                        <h3 class="faq-card-title">Tim kami siap membantu</h3>

                        <p class="faq-card-desc">Gunakan kanal di bawah ini jika jawaban belum ditemukan. Kami berusaha

                            merespons secepatnya pada jam operasional.</p>

                        <div class="faq-contact-list">

                            <div class="faq-contact-item">

                                <span class="faq-contact-label">WhatsApp/Telepon</span>

                                <a href="tel:+62554234567">+62 554 234 567</a>

                                <small>Senin&ndash;Jumat, 09.00&ndash;17.00 WITA</small>

                            </div>

                            <div class="faq-contact-item">

                                <span class="faq-contact-label">Email</span>

                                <a href="mailto:info@wondersofberau.id">info@wondersofberau.id</a>

                                <small>Balasan maksimal 1x24 jam kerja</small>

                            </div>

                        </div>

                        <div class="faq-actions">

                            <a href="contact.php" class="faq-cta">Form Contact</a>

                            <a href="laporan.php" class="faq-cta ghost">Laporan & Komplain</a>

                        </div>

                        <div class="faq-note">

                            <span class="dot"></span>

                            <p>Untuk keadaan darurat, hubungi petugas setempat di lokasi destinasi atau pos keamanan

                                terdekat.</p>

                        </div>

                    </div>

                </aside>

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

    <script src="assets/js/analytics.js"></script>

</body>



</html>















