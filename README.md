# Chibicon Event Management System

Chibicon adalah sistem manajemen event terpadu yang dirancang untuk menangani operasional event pop-kultur Jepang berskala besar. Sistem ini mencakup manajemen pengunjung, panitia, katalog tiket, transaksi pembayaran, jadwal acara (rundown), hingga penyewaan booth bazaar.

## Fitur Utama
- **Landing Page Publik**: Menampilkan Guest Star, Jadwal Acara, dan Katalog Tiket.
- **Self-Service Ticketing**: Pengunjung dapat membeli tiket secara mandiri dan mengecek status pesanan mereka menggunakan nomor identitas (NIK).
- **Manajemen Akun Pengunjung**: Otomatisasi pendaftaran akun pengunjung saat transaksi pertama kali dilakukan.
- **Admin Dashboard**: Ringkasan data real-time (pendapatan, tiket terjual, dll).
- **Manajemen Tiket (Admin)**: CRUD tiket dan pencatatan transaksi oleh staf panitia.
- **Manajemen Acara**: Penjadwalan rundown dengan relasi banyak-ke-banyak untuk Guest Star.
- **Manajemen Bazaar**: Pemetaan tenant ke booth dengan lokasi indoor/outdoor.
- **Reporting**: Export laporan transaksi ke format PDF.
- **Integritas Data**: Menggunakan Trigger MySQL untuk validasi kuota tiket secara otomatis.

## Struktur Project
- `admin/`: Halaman administrasi dan pengelolaan data internal.
- `assets/`: File CSS, JS, dan aset visual.
- `components/`: Komponen UI modular (header, sidebar, toast).
- `config/`: Konfigurasi sistem (database, auth, CSRF, OAuth).
- `database/`: Skema database SQL dan logic triggers.
- `services/`: Business logic layer (Service classes).
- `auth/`: Handler untuk OAuth/Google Login callback.

## Persyaratan Sistem
- PHP 8.2+
- MySQL 8.0+
- Extension: PDO, PDO_MySQL, CURL

## Setup Manual
1. Clone repository ini.
2. Import `database/chibicon_db.sql` dan `database/sql/triggers.sql` ke dalam database MySQL Anda.
3. Sesuaikan konfigurasi database di file `config/db.php`.
4. Setup Google OAuth di `config/google.php` (gunakan `google.php.example` sebagai referensi).
5. Jalankan menggunakan web server pilihan Anda (Apache/Nginx).

## Tech Stack
- **Backend**: Native PHP 8.2
- **Database**: MySQL 8.0
- **Frontend**: Tailwind CSS 3.4
- **Auth**: Google OAuth 2.0
- **Reporting**: Native HTML/CSS for Print PDF
