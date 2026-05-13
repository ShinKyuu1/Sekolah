<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/validation.php';

requireLogin();

// Pastikan hanya admin yang bisa mengakses halaman ini
$user = currentUser();
if (!isset($user['role']) || $user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$pageTitle = 'Pilih Tahun Ajaran';

// Simpan TA yang dipilih lalu redirect ke dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tahun_ajaran'])) {
    $_SESSION['tahun_ajaran'] = trim($_POST['tahun_ajaran']);
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
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

<body class="ta-page-body">
    <!-- Preloader Animasi Loading -->
    <div class="preloader-overlay" id="preloader">
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

    <!-- Logo Kujang & Qiroati di tengah gerbang putih -->
    <div class="ta-center-logos">
        <img src="<?= BASE_URL ?>../assets/images/logo%20kujang.png" alt="Logo Kujang" class="ta-logo-img logo-kujang"
            onerror="this.style.display='none'">
        <img src="<?= BASE_URL ?>../assets/images/logo%20qiroati.png" alt="Logo Qiroati" class="ta-logo-img"
            onerror="this.style.display='none'">
    </div>

    <!-- Sisi Kanan (Form TA) -->
    <div class="ta-card-container">
        <div class="login-card ta-card">
            <h2 class="ta-subheading">Silahkan Pilih TA</h2>
            <div class="ta-divider"></div>

            <form method="POST" action="<?= escape_html($_SERVER['PHP_SELF']) ?>" class="ta-form" id="taForm"
                onsubmit="processTa(event)">
                <div class="form-group">
                    <div class="custom-select-wrapper" id="taSelectWrapper">
                        <div class="input-group">
                            <div class="input-icon-wrapper">
                                <!-- Fallback: Jika gambar vektor tidak ditemukan, akan muncul icon search bawaan -->
                                <img src="<?= BASE_URL ?>../assets/images/vektor searchTA.png" alt="Search"
                                    class="ta-search-icon"
                                    onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'black\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><circle cx=\'11\' cy=\'11\' r=\'8\'></circle><line x1=\'21\' y1=\'21\' x2=\'16.65\' y2=\'16.65\'></line></svg>'">
                            </div>
                            <div class="input-divider"></div>
                            <input type="text" name="tahun_ajaran" id="tahun_ajaran" placeholder="Silahkan Pilih TA"
                                class="input-field-main" required autocomplete="off" onclick="openTaDropdown()"
                                onkeyup="filterTaOptions()">
                            <div class="input-icon-wrapper" onclick="toggleTaDropdown()" style="cursor: pointer;">
                                <img src="<?= BASE_URL ?>../assets/images/vektor sidebar data.png" alt="Dropdown"
                                    class="ta-dropdown-icon" onerror="this.style.display='none'">
                            </div>
                        </div>

                        <div class="custom-options-container" id="taOptions">
                            <div class="custom-option" onclick="selectTa('2023/2024 Ganjil')">2023/2024 Ganjil</div>
                            <div class="custom-option" onclick="selectTa('2023/2024 Genap')">2023/2024 Genap</div>
                            <div class="custom-option" onclick="selectTa('2024/2025 Ganjil')">2024/2025 Ganjil</div>
                            <div class="custom-option" onclick="selectTa('2024/2025 Genap')">2024/2025 Genap</div>
                            <div class="custom-option" onclick="selectTa('2025/2026 Ganjil')">2025/2026 Ganjil</div>
                        </div>
                    </div>
                </div>

                <div class="ta-footer">
                    <button type="submit" class="btn-proses-green">
                        <div class="btn-icon-wrapper">
                            <img src="<?= BASE_URL ?>../assets/images/vektor centang.png" alt="Centang"
                                class="btn-icon-img" onerror="this.style.display='none'">
                        </div>
                        <div class="btn-divider"></div>
                        <span class="btn-text">Proses</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTaDropdown() {
            document.getElementById('taSelectWrapper').classList.add('open');
        }

        function toggleTaDropdown() {
            document.getElementById('taSelectWrapper').classList.toggle('open');
        }

        function selectTa(value) {
            document.getElementById('tahun_ajaran').value = value;
            document.getElementById('taSelectWrapper').classList.remove('open');
        }

        function filterTaOptions() {
            const input = document.getElementById('tahun_ajaran').value.toLowerCase();
            const options = document.querySelectorAll('.custom-option');
            options.forEach(opt => {
                if (opt.innerText.toLowerCase().includes(input)) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
        }

        // Menutup dropdown otomatis ketika area luar diklik
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('taSelectWrapper');
            if (!wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });

        // Sembunyikan layar loading ketika halaman selesai dimuat
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                setTimeout(function() {
                    preloader.classList.add('hidden');
                }, 3500); // Jeda 3.5 detik agar terlihat seperti memuat banyak data
            }
        });

        function processTa(event) {
            event.preventDefault(); // Mencegah sistem langsung berpindah halaman
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.transition = '';
                preloader.classList.remove('hidden');
            }
            setTimeout(function() {
                document.getElementById('taForm').submit();
            }, 3500);
        }
    </script>
</body>

</html>