<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';

$pageTitle = 'Login Guru';
$hasError = hasFlash('error'); // Cek apakah ada pesan error dari sistem

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verifyCsrfToken($csrf_token)) {
        flash('error', 'Token keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.');
        $hasError = true;
    } elseif ($username === '' || $password === '') {
        flash('error', 'Username dan password harus diisi.');
        $hasError = true;
    } else {
        $user = authenticate($username, $password);

        if ($user) {
            loginUser($user);
            if (isset($user['role']) && $user['role'] === 'admin') {
                header('Location: ' . BASE_URL . 'pilih_ta.php');
            } else {
                // Jika yang login adalah guru, otomatis set Tahun Ajaran ke yang terbaru di database
                $stmtTa = db()->query("SELECT MAX(tahun_ajaran) FROM (SELECT tahun_ajaran FROM kelas UNION SELECT tahun_ajaran FROM hasil_tes) as gabungan");
                $ta_terbaru = $stmtTa->fetchColumn();
                $_SESSION['tahun_ajaran'] = $ta_terbaru ?: '2024/2025 Ganjil';

                header('Location: ' . BASE_URL . 'dashboard.php');
            }
            exit;
        }

        flash('error', 'Username atau password salah.');
        $hasError = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape_html($pageTitle) ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css?family=JejuMyeongjo|Kameron|Kaisei+Opti|Kaisei+Tokumin&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">

    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>../assets/images/favicon_io/apple-touch-icon.png">
    <link rel="shortcut icon" href="<?= BASE_URL ?>../assets/images/favicon_io/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>../assets/images/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>../assets/images/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="<?= BASE_URL ?>../assets/images/favicon_io/site.webmanifest">
</head>

<body class="login-page">
    <!-- Preloader Animasi Loading -->
    <div class="preloader-overlay <?= $hasError ? 'hidden' : '' ?>" id="preloader"
        <?= $hasError ? 'style="transition: none;"' : '' ?>>
        <!-- Menggunakan Tema 2: Spinning Rings -->
        <div class="preloader-logos-ring">
            <div class="preloader-logo-wrapper left-logo">
                <svg class="preloader-ring ring-cw" viewBox="0 0 50 50">
                    <circle class="path path-blue" cx="25" cy="25" r="20" fill="none" stroke-width="2"></circle>
                </svg>
                <img src="<?= BASE_URL ?>../assets/images/logo_kujang.png" class="preloader-logo kujang"
                    alt="Logo Kujang">
            </div>
            <div class="preloader-logo-wrapper right-logo">
                <svg class="preloader-ring ring-ccw" viewBox="0 0 50 50">
                    <circle class="path path-purple" cx="25" cy="25" r="20" fill="none" stroke-width="2"></circle>
                </svg>
                <img src="<?= BASE_URL ?>../assets/images/logo_qiroati.png" class="preloader-logo" alt="Logo Qiroati">
            </div>
        </div>
    </div>

    <div class="login-container">
        <!-- Left side image -->
        <div class="login-left">
            <!-- Logo Badges Overlay on Background -->
            <div class="logo-badges-overlay">
                <img src="<?= BASE_URL ?>../assets/images/logo_kujang.png" alt="Logo 1" class="badge-overlay">
                <img src="<?= BASE_URL ?>../assets/images/logo_qiroati.png" alt="Logo 2" class="badge-overlay">
            </div>
        </div>

        <!-- Right side form -->
        <div class="login-right">
            <div class="login-card">
                <!-- Title -->
                <h2 class="login-title">Manajemen Program <br> Keagamaan (Qiroati)</h2>
                <h1 class="login-heading">Login</h1>

                <?php if (hasFlash('error')): ?>
                <div class="alert error"><?= escape_html(flash('error')) ?></div>
                <?php endif; ?>

                <!-- Form -->
                <form method="post" action="<?= escape_html($_SERVER['PHP_SELF']) ?>" class="login-form" id="loginForm"
                    onsubmit="processLogin(event)">
                    <!-- CSRF Token (Hidden) -->
                    <input type="hidden" name="csrf_token" value="<?= escape_html(generateCsrfToken()) ?>">

                    <!-- Username -->
                    <div class="form-group">
                        <div class="input-group" onclick="document.getElementById('username').focus()">
                            <div class="input-icon-wrapper">
                                <svg class="input-icon-svg" viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="input-divider"></div>
                            <input type="text" id="username" name="username" placeholder="Username"
                                value="<?= escape_html($_POST['username'] ?? '') ?>" required class="input-field-main">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <div class="input-group" onclick="document.getElementById('password').focus()">
                            <input type="password" id="password" name="password" placeholder="Password" required
                                class="input-field-main">
                            <div class="input-divider"></div>
                            <div class="input-icon-wrapper password-toggle-btn" onclick="togglePassword(event)">
                                <!-- Mata Tertutup (Default) -->
                                <svg class="input-icon-svg eye-closed" viewBox="0 0 24 24">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                    </path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                                <!-- Mata Terbuka -->
                                <svg class="input-icon-svg eye-open" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="login-btn">Login</button>

                    <!-- Forget Password -->
                    <div class="login-footer">
                        <a href="#" class="forget-link">Forget Password</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(event) {
        if (event) event.stopPropagation(); // Mencegah trigger onclick dari kotak input-group
        const pass = document.getElementById('password');
        const toggleBtn = document.querySelector('.password-toggle-btn');
        if (!pass) return;

        if (pass.type === 'password') {
            pass.type = 'text';
            toggleBtn.classList.add('show-password');
        } else {
            pass.type = 'password';
            toggleBtn.classList.remove('show-password');
        }
        pass.focus(); // Pastikan kursor tetap aktif di kolom input setelah klik icon
    }

    // Sembunyikan layar loading ketika halaman selesai dimuat
    window.addEventListener('load', function() {
        const preloader = document.getElementById('preloader');
        const hasError = <?= $hasError ? 'true' : 'false' ?>; // Baca status error dari PHP

        if (preloader && !hasError) {
            setTimeout(function() {
                preloader.classList.add('hidden');
            }, 5000); // Jeda 5 detik agar animasi portal selesai dengan mulus
        }
    });

    // Tahan pengiriman data dan munculkan layar loading saat tombol submit (Login) ditekan
    function processLogin(event) {
        event.preventDefault(); // Mencegah sistem langsung berpindah halaman
        document.activeElement.blur(); // Menutup keyboard di HP secara otomatis saat tombol Enter ditekan

        const preloader = document.getElementById('preloader');
        if (preloader) {
            // Teknik Clone Node: Hancurkan yang lama, buat baru agar CSS Animation merestart 100% dari detik 0
            const newPreloader = preloader.cloneNode(true);
            preloader.parentNode.replaceChild(newPreloader, preloader);

            newPreloader.classList.remove('hidden');
        }

        setTimeout(function() {
            document.getElementById('loginForm').submit();
        }, 5000);
    }
    </script>
</body>

</html>