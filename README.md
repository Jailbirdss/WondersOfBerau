# Wonders of Berau

**Tourism Information System - Expo Informatics 2026**

Website pariwisata interaktif untuk Kabupaten Berau yang menampilkan destinasi wisata, kuliner, akomodasi, dan event lokal. Sistem ini dilengkapi dengan CMS (Content Management System) di Panel Admin, review system, multi-language support, dan dashboard analytics.

Project ini dikembangkan sebagai bagian dari Expo Informatics untuk menunjukkan implementasi web development dalam sektor pariwisata / ETourism.

## Fitur Utama

- **Destinasi Wisata** - Katalog lengkap tempat wisata di Berau
- **Kuliner & UMKM** - Daftar makanan khas dan produk lokal
- **Akomodasi** - Informasi hotel dan penginapan
- **Event & Festival** - Kalender acara budaya dan wisata
- **Review System** - Rating dan komentar dari pengunjung
- **Sistem Laporan** - Pengunjung bisa kirim komplain dengan tracking ticket
- **Multi-bahasa** - Mendukung 8 bahasa (Indonesia, English, 日本語, 한국어, 中文, Español, Français, Deutsch)
- **Admin Dashboard** - Panel admin dengan analytics dan management konten
- **Gallery** - Galeri foto untuk setiap destinasi/kuliner/event

## Requirements

- PHP 7.4+
- MySQL/MariaDB 5.7+
- Apache Web Server
- PHP Extensions: mysqli, curl, gd

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/Jailbirdss/WondersOfBerau.git
cd WondersOfBerau
```

### 2. Database Setup

Buat database baru dan import schema:

```sql
CREATE DATABASE wondersofberauu CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

Import file SQL:
```bash
mysql -u root -p wondersofberauu < database/wondersofberauu.sql
```

Atau lewat phpMyAdmin:
- Buka phpMyAdmin
- Buat database `wondersofberauu`
- Import file `database/wondersofberauu.sql`

### 3. Environment Configuration

Buat file `.env` di root directory:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=wondersofberauu

# DeepL API Key (optional - untuk fitur translate)
DEEPL_API_KEY=your_api_key_here

# Database Setup Toggle
RUN_DB_SETUP=0
```

### 4. Configure .htaccess

Edit file `.htaccess` dan sesuaikan `RewriteBase` dengan lokasi project:

```apache
RewriteBase /wondersofberau/
```

Jika project di root directory:
```apache
RewriteBase /
```

### 5. Setup Upload Directory

Pastikan folder upload ada dan writable:

```bash
mkdir -p public/uploads/{destinasi,akomodasi,kuliner,events,gallery,reports,reviews,report-evidence}
chmod -R 755 public/uploads
```

Di Windows (XAMPP), folder akan auto-create dengan permission yang sesuai.

## Accessing the Application

### Frontend
```
http://localhost/wondersofberau/
```

### Admin Panel
```
http://localhost/wondersofberau/admin/
```

**Default Admin Credentials:**
- Username: `berauadmin`
- Password: `admin123`

⚠️ **Penting:** Ganti password default setelah login pertama kali

## Project Structure

```
wondersofberau/
├── app/
│   ├── config.php              # Main configuration
│   ├── admin/                  # Admin backend files
│   │   ├── index.php
│   │   ├── destinasi.php
│   │   ├── kuliner.php
│   │   └── ...
│   └── api/                    # API endpoints
│       ├── translate.php
│       ├── review.php
│       └── track.php
├── public/
│   ├── index.php               # Homepage
│   ├── destinasi.php
│   ├── akomodasikuliner.php
│   ├── event.php
│   ├── admin/                  # Admin frontend (redirects)
│   ├── api/                    # API frontend (redirects)
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   ├── partials/
│   └── uploads/                # User uploads (gitignored)
├── database/
│   └── wondersofberauu.sql     # Database schema & sample data
├── .env                        # Environment variables (gitignored)
├── .gitignore
├── .htaccess
└── README.md
```

## Tech Stack

- **Backend:** PHP (native)
- **Database:** MySQL 
- **Frontend:** Vanilla JavaScript, CSS3
- **Security:** CSRF protection, prepared statements
- **API Integration:** DeepL Translation API
- **Session Management:** PHP native sessions

## Features Documentation

### Review System
- One review per device per item (device fingerprinting)
- 5-star rating system
- Optional photo upload
- Admin moderation (hide/show reviews)

### Report System
- Generate unique ticket ID (format: LPR-YYMMDD-XXXXXX)
- Secret code for verification
- Status tracking: Baru → Diproses → Selesai/Ditolak
- Evidence upload for admin responses

### Translation System
- Powered by DeepL API
- Client-side caching
- Can be disabled from admin settings
- Preserves local names (Berau, Derawan, etc)

### Admin Features
- Dashboard with analytics
- CRUD operations for all content types
- User management with role system (admin/super admin)
- Audit logging
- CSV export for analytics and all main content

## Development Notes

### Local Development
- Gunakan XAMPP, WAMP, atau Laravel Valet
- PHP development server: `php -S localhost:8000 -t public`
- Set `error_reporting(E_ALL)` di config.php untuk debugging

### Database Migrations
Set `RUN_DB_SETUP=1` di `.env` untuk auto-create tables yang hilang. Setelah selesai, kembalikan ke `0`.

### File Upload Limits
Default max upload: 5MB. Untuk mengubah, edit di php.ini:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### API Keys
- DeepL API: Daftar gratis di https://www.deepl.com/pro-api
- Free tier: 500,000 characters/month

## Troubleshooting

**Database connection error:**
- Cek credentials di `.env`
- Pastikan MySQL service running
- Verify database name sudah dibuat

**URL rewrite not working:**
- Pastikan Apache mod_rewrite enabled
- Cek `AllowOverride All` di Apache config
- Restart Apache setelah perubahan

**Upload gagal:**
- Cek permission folder `public/uploads/`
- Verify `upload_max_filesize` di php.ini
- Pastikan disk space cukup

**Translation not working:**
- Verify DEEPL_API_KEY di `.env`
- Cek quota API belum habis
- Check translator enabled di admin settings

## Project Information

**Expo:** Informatics Exhibition 2026  
**Category:** Web Development - Tourism Information System  
**Tech Stack:** PHP Native, MySQL, JavaScript, DeepL API  
**Development Period:** 2025-2026

### Project Goals
- Menyediakan platform informasi pariwisata yang komprehensif
- Implementasi sistem review dan feedback pengunjung
- Integrasi multi-bahasa untuk wisatawan internasional
- Dashboard analytics untuk monitoring engagement
- Sistem pelaporan dan tracking untuk improve tourism quality

### Key Features Showcase
1. **Dynamic Content Management** - Full CRUD operations dengan admin panel
2. **Review System** - User-generated content dengan moderation
3. **Multi-language Support** - 8 bahasa dengan DeepL API integration
4. **Analytics Dashboard** - Real-time visitor tracking dan reporting
5. **Responsive Design** - Mobile-friendly interface
6. **Security Implementation** - CSRF protection, prepared statements, session management

## Team & Contact

Untuk pertanyaan mengenai project ini, silakan hubungi melalui GitHub repository atau buat issue.

## Acknowledgments

Terima kasih kepada:
- Kabupaten Berau untuk data dan informasi pariwisata
- DeepL untuk API translation service
- Open source community untuk references dan inspirasi

---

**Wonders of Berau** - Explore the Beauty of Berau 🏝️  
*Informatics Exhibition 2026*
