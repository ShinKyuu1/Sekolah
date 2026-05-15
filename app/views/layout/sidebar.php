<?php
if (!defined('APP_NAME')) {
    exit;
}
$user = currentUser();
// Get the current script name to set the active class
$current_page = basename($_SERVER['SCRIPT_NAME']);
$rekap_jilid = $_GET['jilid'] ?? '';
$is_rekap_open = ($current_page === 'rekap.php');
?>
<aside class="main-sidebar">
    <div class="sidebar-header">
        <img src="<?= BASE_URL ?>../assets/images/logo_qiroati.png" alt="Logo Qiroati" class="sidebar-logo">
        <h1 class="sidebar-title">Qiroati SMP <br> Pupuk Kujang</h1>
    </div>
    <div class="sidebar-divider"></div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            Beranda
            <img src="<?= BASE_URL ?>../assets/images/vektor_sidebar_data.png" alt="" class="nav-icon"
                onerror="this.style.display='none'">
        </a>
        <a href="<?= BASE_URL ?>guru.php" class="<?= $current_page === 'guru.php' ? 'active' : '' ?>">
            Data Guru
            <img src="<?= BASE_URL ?>../assets/images/vektor_sidebar_data.png" alt="" class="nav-icon"
                onerror="this.style.display='none'">
        </a>
        <a href="<?= BASE_URL ?>siswa.php" class="<?= $current_page === 'siswa.php' ? 'active' : '' ?>">
            Data Siswa
            <img src="<?= BASE_URL ?>../assets/images/vektor_sidebar_data.png" alt="" class="nav-icon"
                onerror="this.style.display='none'">
        </a>
        <a href="<?= BASE_URL ?>kelas.php" class="<?= $current_page === 'kelas.php' ? 'active' : '' ?>">
            Data Kelas
            <img src="<?= BASE_URL ?>../assets/images/vektor_sidebar_data.png" alt="" class="nav-icon"
                onerror="this.style.display='none'">
        </a>
        <a href="<?= BASE_URL ?>hasil_tes.php" class="<?= $current_page === 'hasil_tes.php' ? 'active' : '' ?>">
            Data Tes
            <img src="<?= BASE_URL ?>../assets/images/vektor_sidebar_data.png" alt="" class="nav-icon"
                onerror="this.style.display='none'">
        </a>
    </nav>
    <div class="sidebar-recap">
        <h2 class="sidebar-subtitle <?= $is_rekap_open ? 'open' : '' ?>" id="rekapToggle"
            title="Klik untuk melipat menu">
            <span style="line-height: 1.5;">Rekap <br> Tingkatan</span>
            <img src="<?= BASE_URL ?>../assets/images/vektor_sidebar_data.png" alt="" class="nav-icon-title"
                onerror="this.style.display='none'">
        </h2>
        <nav class="sidebar-nav-recap <?= $is_rekap_open ? '' : 'collapsed' ?>" id="rekapMenu">
            <a href="<?= BASE_URL ?>rekap.php?jilid=Buku+1"
                class="<?= $rekap_jilid === 'Buku 1' ? 'active' : '' ?>"><img
                    src="<?= BASE_URL ?>../assets/images/vektor_sidebar_child_rekap.png" alt="" class="nav-icon-child"
                    onerror="this.style.display='none'"> <span>Buku 1</span></a>
            <a href="<?= BASE_URL ?>rekap.php?jilid=Buku+2"
                class="<?= $rekap_jilid === 'Buku 2' ? 'active' : '' ?>"><img
                    src="<?= BASE_URL ?>../assets/images/vektor_sidebar_child_rekap.png" alt="" class="nav-icon-child"
                    onerror="this.style.display='none'"> <span>Buku 2</span></a>
            <a href="<?= BASE_URL ?>rekap.php?jilid=Buku+3"
                class="<?= $rekap_jilid === 'Buku 3' ? 'active' : '' ?>"><img
                    src="<?= BASE_URL ?>../assets/images/vektor_sidebar_child_rekap.png" alt="" class="nav-icon-child"
                    onerror="this.style.display='none'"> <span>Buku 3</span></a>
            <a href="<?= BASE_URL ?>rekap.php?jilid=Al-Qur%27an"
                class="<?= $rekap_jilid === "Al-Qur'an" ? 'active' : '' ?>"><img
                    src="<?= BASE_URL ?>../assets/images/vektor_sidebar_child_rekap.png" alt="" class="nav-icon-child"
                    onerror="this.style.display='none'"> <span>Al-Qur’an</span></a>
            <a href="<?= BASE_URL ?>rekap.php?jilid=Gharib"
                class="<?= $rekap_jilid === 'Gharib' ? 'active' : '' ?>"><img
                    src="<?= BASE_URL ?>../assets/images/vektor_sidebar_child_rekap.png" alt="" class="nav-icon-child"
                    onerror="this.style.display='none'"> <span>Gharib</span></a>
            <a href="<?= BASE_URL ?>rekap.php?jilid=Tajwid"
                class="<?= $rekap_jilid === 'Tajwid' ? 'active' : '' ?>"><img
                    src="<?= BASE_URL ?>../assets/images/vektor_sidebar_child_rekap.png" alt="" class="nav-icon-child"
                    onerror="this.style.display='none'"> <span>Tajwid</span></a>
        </nav>
    </div>

    <div class="sidebar-logout">
        <a href="<?= BASE_URL ?>logout.php" class="btn-logout-sidebar">
            <svg viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>
</aside>