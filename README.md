# Sistem Informasi Sekolah - PHP Native

Proyek ini adalah implementasi native PHP untuk website sekolah dengan autentikasi multi-role (Admin dan Guru), manajemen data, dan modul CRUD.

## Struktur Direktori

- `public/`: halaman yang dapat diakses oleh browser
- `assets/`: file CSS dan JS
- `app/`: logika aplikasi, helper, dan model
- `config/`: konfigurasi database dan konstanta aplikasi
- `sql/`: skrip pembuatan database
- `public/uploads/`: folder file arsip yang diunggah

## Setup

1. Buat database MySQL baru.
2. Impor `sql/schema.sql` ke database MySQL.
3. (Opsional) Impor `sql/seed.sql` untuk menambahkan data demo.
4. Pastikan konfigurasi koneksi di `config/database.php` cocok dengan server MySQL Anda.
5. Arahkan root web server ke folder aplikasi sekolah Anda.
6. Buka `http://localhost/[path_folder_anda]/` di browser (akan otomatis diarahkan ke halaman login).

## Akun Admin Awal (Akses: Pilih TA -> Dashboard)

- Username: `admin`
- Password: `admin123`

## Akun Demo Guru (Akses: Langsung Dashboard)

- Username: `guru_ali`
- Password: `admin123`

_(Tersedia juga akun demo lainnya seperti `guru_siti`, `guru_rizqi`, `guru_ahmad`, dll. Semua menggunakan password: `admin123`)_

## Fitur Utama

- Login autentikasi 2 Role (Admin dan Guru)
- Dashboard ringkasan data dengan Grafik Interaktif
- Data Guru, Data Siswa, Data Kelas
- Input Hasil Tes dan Arsip
- Pagination, Filter jumlah baris (Show Entries), dan Pencarian (Search) Data
- UI/UX Modern dengan Modal Form (Popup) untuk operasi CRUD
- Keamanan dasar dengan PDO prepared statements, validasi input, dan password hashing
- Desain modern, elegan, dan responsif

## Catatan

- Halaman utama (`index.php`) kini secara otomatis melakukan _redirect_ ke halaman login untuk mempercepat alur aplikasi.
- `public/logout.php` mengakhiri sesi dan mengarahkan kembali ke halaman login.
- Upload arsip disimpan di `public/uploads/`.
- Struktur database telah disempurnakan (penambahan kolom nomor HP untuk data Guru).
