<?php
if (!defined('APP_NAME')) {
    exit;
}
$user = currentUser();

// Ambil Session TA, jika belum ada (misal Guru baru login), set default.
$active_ta = $_SESSION['tahun_ajaran'] ?? '2024/2025 Ganjil';

$svgData = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-8 -8 40 40" fill="#a855f7"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
$defaultAvatar = 'data:image/svg+xml;base64,' . base64_encode($svgData);
$avatarPath = !empty($user['avatar']) ? BASE_URL . escape_html($user['avatar']) : $defaultAvatar;
?>
<!DOCTYPE html>
<html lang="en" class="has-scrollbar-gutter">

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

    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>../assets/images/favicon_io/apple-touch-icon.png">
    <link rel="shortcut icon" href="<?= BASE_URL ?>../assets/images/favicon_io/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>../assets/images/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>../assets/images/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="<?= BASE_URL ?>../assets/images/favicon_io/site.webmanifest">
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
                    <img src="<?= BASE_URL ?>../assets/images/hamburger_menu.png" alt="Toggle Menu"
                        class="app-header-icon" id="sidebarToggle" title="Buka/Tutup Menu">
                    <span class="app-header-title">Tahun Pelajaran : <?= escape_html($active_ta) ?></span>
                    <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="<?= BASE_URL ?>pilih_ta.php" class="btn-ganti-ta" title="Ganti Tahun Ajaran">Ganti TA</a>
                    <?php endif; ?>
                </div>
                <div class="app-header-right">
                    <div class="user-greeting-wrapper" id="userGreetingWrapper">
                        <div class="user-greeting" onclick="toggleProfileMenu()" title="Pengaturan Profil">
                            <img src="<?= $avatarPath ?>" alt="Avatar" class="user-avatar-img"
                                onerror="this.src='<?= $defaultAvatar ?>'">
                            <span class="nav-user">Halo, <?= escape_html($user['nama'] ?? '') ?></span>
                            <img src="<?= BASE_URL ?>../assets/images/vektor_sidebar_data.png" alt="Dropdown"
                                class="profile-dropdown-icon" onerror="this.style.display='none'">
                        </div>
                        <div class="profile-dropdown-menu" id="profileMenu">
                            <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; text-align: center;">
                                <img src="<?= $avatarPath ?>" alt="Avatar"
                                    style="width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid #a855f7; margin-bottom: 8px; background-color: #f3e8ff;"
                                    onerror="this.src='<?= $defaultAvatar ?>'">
                                <div style="font-weight: bold; color: #0f172a; font-size: 16px;">
                                    <?= escape_html($user['nama'] ?? '') ?></div>
                                <div style="color: #64748b; font-size: 13px;">
                                    @<?= escape_html($user['username'] ?? '') ?></div>
                            </div>
                            <a href="#" onclick="openProfileModal(); return false;" style="padding-top: 12px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path
                                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                                    </path>
                                </svg>
                                Pengaturan Profil
                            </a>
                            <a href="<?= BASE_URL ?>logout.php" class="btn-logout-dropdown"
                                style="color: #dc2626; padding-bottom: 12px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- MODAL PENGATURAN PROFIL -->
            <div id="modalProfil" class="modal">
                <div class="modal-content" style="max-width: 450px;">
                    <div class="modal-header">
                        <h2>Pengaturan Profil</h2>
                        <button type="button" class="modal-close" onclick="closeProfileModal()">✕</button>
                    </div>
                    <form method="POST" action="<?= BASE_URL ?>profil.php" enctype="multipart/form-data">
                        <input type="hidden" name="update_profil" value="1">
                        <div class="modal-form-group" style="text-align: center; margin-bottom: 24px;">
                            <div class="avatar-preview-wrapper">
                                <img id="profilPreview" src="<?= $avatarPath ?>" alt="Preview"
                                    class="avatar-preview-img" onerror="this.src='<?= $defaultAvatar ?>'">
                                <label for="avatar_upload" class="avatar-upload-label" title="Ubah Poto Profil">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </label>
                                <input type="file" id="avatar_upload" name="avatar" accept="image/jpeg,image/png"
                                    style="display: none;" onchange="previewAvatar(this)">
                            </div>
                        </div>
                        <div class="modal-form-group">
                            <label for="profil_nama">Nama Lengkap</label>
                            <input type="text" id="profil_nama" name="nama"
                                value="<?= escape_html($user['nama'] ?? '') ?>" required>
                        </div>
                        <div class="modal-form-group">
                            <label for="profil_username">Username</label>
                            <input type="text" id="profil_username" name="username"
                                value="<?= escape_html($user['username'] ?? '') ?>" required>
                        </div>
                        <div class="modal-form-group">
                            <label for="profil_password">Password Baru</label>
                            <input type="password" id="profil_password" name="password"
                                placeholder="Kosongkan jika tidak ingin mengubah password">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn-simpan">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>

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
                    document.body.style.overflow = 'hidden';
                }

                function closeConfirm() {
                    confirmModal.classList.remove('show');
                    confirmAction = null;
                    document.body.style.overflow = 'auto';
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
                const btnLogouts = document.querySelectorAll('.btn-logout-sidebar, .btn-logout-dropdown');
                btnLogouts.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        window.showCustomConfirm('Konfirmasi Logout',
                            'Apakah Anda yakin ingin keluar dari sistem?', 'logout',
                            function() {
                                window.location.href = href;
                            });
                    });
                });

                // 4. LOGIKA DROPDOWN PROFIL
                window.toggleProfileMenu = function() {
                    document.getElementById('userGreetingWrapper').classList.toggle('open');
                }
                window.openProfileModal = function() {
                    document.getElementById('userGreetingWrapper').classList.remove('open');
                    document.getElementById('modalProfil').classList.add('show');
                    document.body.style.overflow = 'hidden';
                }

                window.closeProfileModal = function() {
                    document.getElementById('modalProfil').classList.remove('show');
                    document.body.style.overflow = 'auto';
                }
                window.previewAvatar = function(input) {
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('profilPreview').src = e.target.result;
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }
                document.addEventListener('click', function(e) {
                    const wrapper = document.getElementById('userGreetingWrapper');
                    if (wrapper && !wrapper.contains(e.target)) {
                        wrapper.classList.remove('open');
                    }
                });
            });
            </script>

            <main class="page-content">
                <?php if (hasFlash('success')) : ?>
                <div class="alert success"><?= escape_html(flash('success')) ?></div>
                <?php endif; ?>
                <?php if (hasFlash('error')) : ?>
                <div class="alert error"><?= escape_html(flash('error')) ?></div>
                <?php endif; ?>