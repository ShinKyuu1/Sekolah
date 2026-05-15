# Sistem Informasi Sekolah - PHP Native

Proyek ini adalah implementasi aplikasi Sistem Informasi Sekolah berbasis web menggunakan PHP Native. Aplikasi ini dirancang untuk memudahkan manajemen data sekolah, nilai ujian, serta arsip dengan sistem autentikasi multi-role (Admin dan Guru).

## 🚀 Fitur Utama

- **Multi-Role Authentication**: Akses spesifik dan aman yang terpisah untuk Admin dan Guru.
- **Dashboard Interaktif**: Ringkasan data akademik dan statistik interaktif dengan tampilan modern.
- **Manajemen Pengguna & Data Master**:
  - Modul Data Guru (dilengkapi dengan filter, pencarian cerdas, dan pagination).
  - Modul Data Siswa.
  - Modul Data Kelas.
- **Manajemen Akademik**:
  - Input, kelola, dan rekap Hasil Tes/Ujian.
  - Manajemen Tahun Ajaran (Fitur Pilih TA).
- **Manajemen Arsip**: Upload, simpan, dan kelola dokumen digital secara terpusat.
- **UI/UX Modern & Responsif**:
  - Antarmuka yang mulus di berbagai ukuran layar (Desktop & Mobile).
  - Operasi Tambah/Edit menggunakan _Modal Form_ (Popup) tanpa harus memuat ulang halaman.
  - Animasi transisi halaman yang elegan.
- **Keamanan Optimal**:
  - Menggunakan PDO (PHP Data Objects) _prepared statements_ guna mencegah ancaman _SQL Injection_.
  - Validasi input yang ketat dan implementasi _password hashing_ untuk keamanan kredensial.

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP (Native)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3 (Desain kustom eksklusif), Vanilla JavaScript

## 📋 Prasyarat Sistem

Sebelum memulai, pastikan perangkat atau server lokal Anda telah terpasang:

- Web Server (Apache/Nginx) melalui aplikasi seperti XAMPP, Laragon, atau MAMP.
- PHP versi 7.4 atau lebih baru.
- MySQL atau MariaDB.

## ⚙️ Panduan Instalasi (Setup)

1. **Persiapkan Direktori**
   Letakkan folder proyek ini di dalam direktori utama web server Anda (contoh: `htdocs` untuk XAMPP atau `www` untuk Laragon).
2. **Buat Database**
   - Buka phpMyAdmin (atau _database client_ favorit Anda) lalu buat database baru (misal: `db_sekolah`).
   - Impor kerangka tabel dari file `sql/schema.sql` ke dalam database tersebut.
   - _(Opsional)_ Impor file `sql/seed.sql` jika Anda ingin mencoba aplikasi dengan langsung memuat data demo (guru, admin, siswa).
3. **Konfigurasi Database**
   - Buka file `config/database.php`.
   - Ubah konfigurasi akses kredensial (host, username, password, dan nama database) menyesuaikan dengan _environment_ lokal Anda.
4. **Jalankan Aplikasi**
   - Akses aplikasi lewat browser melalui URL: `http://localhost/[nama_folder_proyek]/`
   - Aplikasi secara otomatis akan mengarahkan Anda ke _interface_ halaman login.

## 🔐 Akses Akun Demo

Aplikasi ini menyediakan akun demo untuk pengujian fungsionalitas:

**1. Akun Admin (Akses Penuh + Pilih Tahun Ajaran)**

- **Username**: `admin`
- **Password**: `admin123`

**2. Akun Guru (Akses Langsung ke Dashboard)**

- **Username**: `guru_ali`
- **Password**: `admin123`
  > _Catatan: Tersedia juga opsi simulasi untuk akun guru lain seperti `guru_siti`, `guru_rizqi`, `guru_ahmad`, dan lainnya dengan password yang sama, yaitu `admin123`._

## 📂 Struktur Direktori

```text
Sekolah/
├── app/               # Folder Logika utama (Controller, Helper, Models)
├── assets/            # File Statis pendukung Frontend (CSS, Fonts, Gambar)
├── config/            # File Konfigurasi (Koneksi Database, dsb)
├── public/            # Endpoint publik halaman aplikasi untuk User
│   └── uploads/       # Media penampung file unggahan arsip sekolah
├── sql/               # Script query untuk struktur dan data awal Database
├── index.php          # Entry-point web (otomatis redirect ke sistem Login)
└── README.md          # Dokumen panduan implementasi aplikasi ini
```

## 📝 Catatan Tambahan

- **Hak Akses Folder**: Harap pastikan folder `public/uploads/` mempunyai _permission write_ agar proses unggah file arsip dapat berjalan lancar tanpa kendala sistem operasi.
- **Efisiensi Alur Sistem**: File awalan `index.php` telah dirancang untuk mempercepat navigasi pengguna dengan secara langsung meredirect sesi kosong ke `public/login.php`.
- **Manajemen Sesi Akhir**: _Endpoint_ `public/logout.php` berfungsi secara otomatis dalam pemusnahan total data _session_ sehingga akun terkunci secara aman sebelum pengguna dikembalikan ke beranda login.
- **Pembaruan Struktur**: Telah dilakukan penyesuaian fungsional pada arsitektur _database_ terkait pembaruan entitas, contohnya tambahan spesifik kolom pendataan nomor kontak HP guru.
