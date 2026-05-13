<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';
require_once __DIR__ . '/../app/models/Siswa.php';
require_once __DIR__ . '/../app/models/Kelas.php';
require_once __DIR__ . '/../app/models/HasilTes.php';
require_once __DIR__ . '/../app/models/Arsip.php';

requireLogin();

$active_ta = $_SESSION['tahun_ajaran'] ?? '2024/2025 Ganjil';

$totalGuru = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "guru"')->fetchColumn();

$stmtSiswaCount = db()->prepare('SELECT COUNT(DISTINCT siswa_id) FROM kelas WHERE tahun_ajaran = :ta');
$stmtSiswaCount->execute(['ta' => $active_ta]);
$totalSiswa = (int) $stmtSiswaCount->fetchColumn();

$stmtTes = db()->prepare('SELECT COUNT(*) FROM hasil_tes WHERE tahun_ajaran = :ta');
$stmtTes->execute(['ta' => $active_ta]);
$totalHasilTes = (int) $stmtTes->fetchColumn();

$stmtArsip = db()->prepare('SELECT COUNT(*) FROM arsip WHERE tahun_ajaran = :ta');
$stmtArsip->execute(['ta' => $active_ta]);
$totalArsip = (int) $stmtArsip->fetchColumn();

$recentStudents = array_slice(Siswa::all(), 0, 5);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../app/views/layout/header.php';
?>

<section class="content-section">
    <!-- Dua Kartu Utama -->
    <div class="dashboard-custom-grid">
        <div class="custom-card card-blue">
            <div class="card-bg-overlay" style="background-image: url('<?= BASE_URL ?>../assets/images/v20_51.png');">
            </div>
            <h3 class="card-title">Jumlah Siswa Tes</h3>
            <div class="card-data">
                <span class="card-number"><?= $totalHasilTes ?></span>
                <span class="card-unit">Orang</span>
            </div>
            <img src="<?= BASE_URL ?>../assets/images/v20_47.png" class="card-icon" alt="Icon Tes">
        </div>

        <div class="custom-card card-pink">
            <div class="card-bg-overlay" style="background-image: url('<?= BASE_URL ?>../assets/images/v85_109.png');">
            </div>
            <h3 class="card-title">Data Siswa</h3>
            <div class="card-data">
                <span class="card-number"><?= $totalSiswa ?></span>
                <span class="card-unit">Orang</span>
            </div>
            <img src="<?= BASE_URL ?>../assets/images/v85_112.png" class="card-icon" alt="Icon Siswa">
        </div>
    </div>

    <!-- Grafik Batang Interaktif (Replikasi CSS) -->
    <?php
    // 1. Ambil total siswa per tingkatan kelas dari tabel KELAS untuk TA aktif
    $stmtSiswa = db()->prepare("SELECT SUBSTRING(kelas, 1, 1) as tingkat, COUNT(*) as total FROM kelas WHERE tahun_ajaran = :ta GROUP BY tingkat");
    $stmtSiswa->execute(['ta' => $active_ta]);
    $totalSiswaPerTingkat = [];
    foreach ($stmtSiswa->fetchAll() as $row) {
        $totalSiswaPerTingkat[$row['tingkat']] = (int)$row['total'];
    }

    // 2. Ambil data siswa yang 'Lulus' per jilid dan tingkatan kelas untuk TA aktif
    $stmtLulus = db()->prepare("SELECT jilid, SUBSTRING(kelas, 1, 1) as tingkat, COUNT(DISTINCT siswa_id) as total_lulus FROM hasil_tes WHERE nilai = 'Lulus' AND tahun_ajaran = :ta GROUP BY jilid, tingkat");
    $stmtLulus->execute(['ta' => $active_ta]);

    // 3. Set struktur nilai default grafik ke 0
    $chartData = [
        'Buku 1'    => ['k7' => 0, 'k8' => 0, 'k9' => 0],
        'Buku 2'    => ['k7' => 0, 'k8' => 0, 'k9' => 0],
        'Buku 3'    => ['k7' => 0, 'k8' => 0, 'k9' => 0],
        'Al-Qur\'an' => ['k7' => 0, 'k8' => 0, 'k9' => 0],
        'Gharib'    => ['k7' => 0, 'k8' => 0, 'k9' => 0],
        'Tajwid'    => ['k7' => 0, 'k8' => 0, 'k9' => 0],
    ];

    // 4. Hitung persentase
    foreach ($stmtLulus->fetchAll() as $row) {
        $jilid = $row['jilid'];
        $tingkat = $row['tingkat'];
        $lulus = (int)$row['total_lulus'];

        if (isset($chartData[$jilid]) && in_array($tingkat, ['7', '8', '9'])) {
            $totalSiswa = $totalSiswaPerTingkat[$tingkat] ?? 0;
            $persentase = $totalSiswa > 0 ? round(($lulus / $totalSiswa) * 100) : 0;
            $chartData[$jilid]["k{$tingkat}"] = min(100, $persentase); // Capping di angka 100%
        }
    }
    ?>
    <div class="chart-panel">
        <div class="chart-header">
            <h2 class="chart-title">Jumlah Kemajuan Siswa</h2>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-color color-7"></div> Kelas 7
                </div>
                <div class="legend-item">
                    <div class="legend-color color-8"></div> Kelas 8
                </div>
                <div class="legend-item">
                    <div class="legend-color color-9"></div> Kelas 9
                </div>
            </div>
        </div>

        <div class="chart-container">
            <?php foreach ($chartData as $label => $data): ?>
                <div class="chart-group">
                    <div class="chart-bar color-7" style="height: <?= $data['k7'] ?>%;" data-tooltip="Kelas 7 : <?= $data['k7'] ?>%">
                        <span class="chart-value"><?= $data['k7'] ?>%</span>
                    </div>
                    <div class="chart-bar color-8" style="height: <?= $data['k8'] ?>%;" data-tooltip="Kelas 8 : <?= $data['k8'] ?>%">
                        <span class="chart-value"><?= $data['k8'] ?>%</span>
                    </div>
                    <div class="chart-bar color-9" style="height: <?= $data['k9'] ?>%;" data-tooltip="Kelas 9 : <?= $data['k9'] ?>%">
                        <span class="chart-value"><?= $data['k9'] ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chart-labels">
            <?php foreach ($chartData as $label => $data): ?>
                <div class="chart-label"><?= $label ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../app/views/layout/footer.php';
