<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Sistem Informasi Sekolah');

// Menggunakan path relatif (absolute dari root domain)
// Trik ini 100% kebal terhadap masalah Ngrok, Localhost, HTTPS, maupun saat dihosting sungguhan!
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('BASE_URL', $basePath . '/');

define('UPLOAD_DIR', realpath(__DIR__ . '/../public/uploads'));
define('UPLOAD_URL', BASE_URL . 'uploads/');

define('ALLOWED_UPLOAD_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);
define('MAX_UPLOAD_SIZE', 3 * 1024 * 1024);