<?php
if (!defined('APP_NAME')) {
    exit;
}
$user = currentUser();

// Ambil Session TA, jika belum ada (misal Guru baru login), set default.
$active_ta = $_SESSION['tahun_ajaran'] ?? '2024/2025 Ganjil';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? escape_html($pageTitle) . ' | ' . APP_NAME : APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css?family=Jomolhari|Inter|Inria+Serif&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css?v=<?= time() ?>">

    <!-- Menyembunyikan menu Data Guru jika yang login bukan admin -->
    <?php if (isset($user['role']) && $user['role'] !== 'admin'): ?>
    <style>
    .sidebar-nav a[href*="guru.php"] {
        display: none !important;
    }
    </style>
    <?php endif; ?>
</head>

<body>

    <!-- CUSTOM CONFIRMATION MODAL (PERINGATAN GLOBAL) -->
    <div id="customConfirmModal" class="modal">
        <div class="modal-content">
            <div id="confirmIcon">⚠️</div>
            <h2 id="confirmTitle">Konfirmasi</h2>
            <p id="confirmMessage"></p>
            <div class="confirm-actions">
                <button id="btnConfirmCancel" class="btn-confirm btn-confirm-cancel">Batal</button>
                <button id="btnConfirmOk" class="btn-confirm btn-confirm-ok">Ya</button>
            </div>
        </div>
    </div>

    <div class="app-layout">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="app-content-wrapper">
            <header class="app-header">
                <div class="app-header-left">
                    <img src="<?= BASE_URL ?>../assets/images/v19_37.png" alt="Toggle Menu" class="app-header-icon"
                        id="sidebarToggle" title="Buka/Tutup Menu">
                    <span class="app-header-title">Tahun Pelajaran : <?= escape_html($active_ta) ?></span>
                    <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="<?= BASE_URL ?>pilih_ta.php" class="btn-ganti-ta" title="Ganti Tahun Ajaran">Ganti TA</a>
                    <?php endif; ?>
                </div>
                <div class="app-header-right">
                    <div class="user-greeting">
                        <div class="user-avatar"><?= strtoupper(substr(escape_html($user['nama'] ?? 'U'), 0, 1)) ?>
                        </div>
                        <span class="nav-user">Halo, <?= escape_html($user['nama'] ?? '') ?></span>
                    </div>
                </div>
            </header>

            <!-- Overlay Sidebar Mobile -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <script>
            // Script untuk menutup sidebar jika area gelap (overlay) diklik

            document.addEventListener('DOMContentLoaded', function() {
                var overlay = document.getElementById('sidebarOverlay');
                var layout = document.querySelector('.app-layout');
                if (overlay && layout) {
                    overlay.addEventListener('click', function() {
                        layout.classList.remove('sidebar-open');
                    });
                }

                // --- Custom Confirmation Modal Logic (Script Pencegat Cerdas) ---
                const confirmModal = document.getElementById('customConfirmModal');
                const btnCancel = document.getElementById('btnConfirmCancel');
                const btnOk = document.getElementById('btnConfirmOk');
                const titleEl = document.getElementById('confirmTitle');
                const messageEl = document.getElementById('confirmMessage');
                const iconEl = document.getElementById('confirmIcon');

                let confirmAction = null;

                // Fungsi untuk menampilkan modal dengan dinamis
                window.showCustomConfirm = function(title, message, type, onConfirm) {
                    titleEl.textContent = title;
                    messageEl.textContent = message;

                    if (type === 'danger') {
                        iconEl.textContent = '🗑️';
                        btnOk.style.backgroundColor = '#ef4444'; // Merah
                        btnOk.style.color = '#fff';
                        btnOk.textContent = 'Hapus';
                    } else if (type === 'warning') {
                        iconEl.textContent = '⚠️';
                        btnOk.style.backgroundColor = '#facc15'; // Kuning
                        btnOk.style.color = '#000';
                        btnOk.textContent = 'Lanjutkan';
                    } else if (type === 'logout') {
                        iconEl.textContent = '🚪';
                        btnOk.style.backgroundColor = '#ef4444'; // Merah
                        btnOk.style.color = '#fff';
                        btnOk.textContent = 'Keluar';
                    }

                    confirmAction = onConfirm;
                    confirmModal.classList.add('show');
                }

                function closeConfirm() {
                    confirmModal.classList.remove('show');
                    confirmAction = null;
                }

                btnCancel.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeConfirm();
                });
                btnOk.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirmAction) confirmAction();
                    closeConfirm();
                });

                // Tutup modal jika area gelap diklik
                window.addEventListener('click', function(event) {
                    if (event.target === confirmModal) closeConfirm();
                });

                // 1. PENCEGAT TOMBOL HAPUS (Membajak atribut bawaan onsubmit="return confirm()")
                document.querySelectorAll('form').forEach(form => {
                    const onsubmitAttr = form.getAttribute('onsubmit');
                    if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                        const match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
                        const message = match ? match[1] :
                            'Apakah Anda yakin ingin melanjutkan aksi ini?';

                        form.removeAttribute('onsubmit'); // Matikan popup bawaan browser
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            window.showCustomConfirm('Konfirmasi Hapus', message, 'danger',
                                function() {
                                    form.submit(); // Lanjutkan hapus data secara nyata
                                });
                        });
                    }
                });

                // 2. PENCEGAT TOMBOL GANTI TA
                const btnGantiTa = document.querySelector('.btn-ganti-ta');
                if (btnGantiTa) {
                    btnGantiTa.addEventListener('click', function(e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        window.showCustomConfirm('Ganti Tahun Ajaran?',
                            'Anda akan diarahkan kembali ke halaman Pilih TA. Lanjutkan?',
                            'warning',
                            function() {
                                window.location.href = href;
                            });
                    });
                }

                // 3. PENCEGAT TOMBOL LOGOUT
                const btnLogout = document.querySelector('.btn-logout-sidebar');
                if (btnLogout) {
                    btnLogout.addEventListener('click', function(e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        window.showCustomConfirm('Konfirmasi Logout',
                            'Apakah Anda yakin ingin keluar dari sistem?', 'logout',
                            function() {
                                window.location.href = href;
                            });
                    });
                }
            });
            </script>

            <main class="page-content">
                <?php if (hasFlash('success')) : ?>
                <div class="alert success"><?= escape_html(flash('success')) ?></div>
                <?php endif; ?>
                <?php if (hasFlash('error')) : ?>
                <div class="alert error"><?= escape_html(flash('error')) ?></div>
                <?php endif; ?>