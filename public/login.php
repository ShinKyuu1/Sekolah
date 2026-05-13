<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';

$pageTitle = 'Login Guru';
$hasError = hasFlash('error'); // Cek apakah ada pesan error dari sistem

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
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
</head>

<body class="login-page">
    <!-- Preloader Animasi Loading -->
    <div class="preloader-overlay <?= $hasError ? 'hidden' : '' ?>" id="preloader"
        <?= $hasError ? 'style="transition: none;"' : '' ?>>
        <!-- Menggunakan Tema 2: Spinning Rings -->
        <div class="preloader-logos-ring">
            <div class="preloader-logo-wrapper">
                <svg class="preloader-ring ring-cw" viewBox="0 0 50 50">
                    <circle class="path path-blue" cx="25" cy="25" r="20" fill="none" stroke-width="2"></circle>
                </svg>
                <img src="<?= BASE_URL ?>../assets/images/logo%20kujang.png" class="preloader-logo kujang"
                    alt="Logo Kujang">
            </div>
            <div class="preloader-logo-wrapper">
                <svg class="preloader-ring ring-ccw" viewBox="0 0 50 50">
                    <circle class="path path-purple" cx="25" cy="25" r="20" fill="none" stroke-width="2"></circle>
                </svg>
                <img src="<?= BASE_URL ?>../assets/images/logo%20qiroati.png" class="preloader-logo" alt="Logo Qiroati">
            </div>
        </div>
    </div>

    <div class="login-container">
        <!-- Left side image -->
        <div class="login-left">
            <!-- Logo Badges Overlay on Background -->
            <div class="logo-badges-overlay">
                <img src="<?= BASE_URL ?>../assets/images/logo kujang.png" alt="Logo 1" class="badge-overlay">
                <img src="<?= BASE_URL ?>../assets/images/logo qiroati.png" alt="Logo 2" class="badge-overlay">
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
                    <!-- Username -->
                    <div class="form-group">
                        <div class="input-group">
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
                        <div class="input-group">
                            <input type="password" id="password" name="password" placeholder="Password" required
                                class="input-field-main">
                            <div class="input-divider"></div>
                            <div class="input-icon-wrapper" onclick="togglePassword()">
                                <svg class="input-icon-svg" viewBox="0 0 24 24">
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
        function togglePassword() {
            const pass = document.getElementById('password');
            if (!pass) return;
            pass.type = pass.type === 'password' ? 'text' : 'password';
        }

        // Sembunyikan layar loading ketika halaman selesai dimuat
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            const hasError = <?= $hasError ? 'true' : 'false' ?>; // Baca status error dari PHP

            if (preloader && !hasError) {
                setTimeout(function() {
                    preloader.classList.add('hidden');
                }, 3500); // Jeda 3.5 detik agar terlihat seperti memuat banyak data
            }
        });

        // Tahan pengiriman data dan munculkan layar loading saat tombol submit (Login) ditekan
        function processLogin(event) {
            event.preventDefault(); // Mencegah sistem langsung berpindah halaman
            document.activeElement.blur(); // Menutup keyboard di HP secara otomatis saat tombol Enter ditekan

            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.transition = ''; // Kembalikan efek transisi jika sebelumnya dimatikan
                preloader.classList.remove('hidden');
            }

            setTimeout(function() {
                document.getElementById('loginForm').submit(); // Lanjutkan proses login setelah jeda 3.5 detik
            }, 3500);
        }
    </script>
</body>

</html>