<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';
requireLogin();

$active_ta = $_SESSION['tahun_ajaran'] ?? '2024/2025 Ganjil';
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_arsip']) && $user['role'] === 'admin') {
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $kelas = sanitize_input($_POST['kelas'] ?? '');
    $jilid = sanitize_input($_POST['jilid'] ?? '');
    $asal_sekolah = sanitize_input($_POST['asal_sekolah'] ?? '');

    if ($siswa_id === 0 || $kelas === '' || $jilid === '') {
        flash('error', 'Siswa ID, kelas, dan jilid harus diisi.');
        header('Location: ' . BASE_URL . 'arsip.php');
        exit;
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Unggah file arsip terlebih dahulu.');
        header('Location: ' . BASE_URL . 'arsip.php');
        exit;
    }

    $file = $_FILES['file'];
    $fileType = mime_content_type($file['tmp_name']);
    $fileSize = $file['size'];

    if (!in_array($fileType, ALLOWED_UPLOAD_TYPES, true)) {
        flash('error', 'Jenis file tidak diperbolehkan. Gunakan PDF atau gambar.');
        header('Location: ' . BASE_URL . 'arsip.php');
        exit;
    }

    if ($fileSize > MAX_UPLOAD_SIZE) {
        flash('error', 'Ukuran file terlalu besar. Maksimal 3 MB.');
        header('Location: ' . BASE_URL . 'arsip.php');
        exit;
    }

    $filename = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($file['name']));
    $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        flash('error', 'Gagal menyimpan file arsip.');
        header('Location: ' . BASE_URL . 'arsip.php');
        exit;
    }

    $fileUrl = 'uploads/' . $filename;
    $stmt = db()->prepare('INSERT INTO arsip (poto, siswa_id, kelas, jilid, asal_sekolah, tahun_ajaran, created_at) VALUES (:poto, :siswa_id, :kelas, :jilid, :asal_sekolah, :tahun_ajaran, NOW())');
    $stmt->execute([
        'poto' => $fileUrl,
        'siswa_id' => $siswa_id,
        'kelas' => $kelas,
        'jilid' => $jilid,
        'asal_sekolah' => $asal_sekolah,
        'tahun_ajaran' => $active_ta,
    ]);

    flash('success', 'Arsip berhasil ditambahkan.');
    header('Location: ' . BASE_URL . 'arsip.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $user['role'] === 'admin') {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = db()->prepare('SELECT poto FROM arsip WHERE id = :id');
    $stmt->execute(['id' => $deleteId]);
    $archive = $stmt->fetch();
    if ($archive) {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . '../public/' . $archive['poto'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
    $stmt = db()->prepare('DELETE FROM arsip WHERE id = :id');
    $stmt->execute(['id' => $deleteId]);
    flash('success', 'Data arsip berhasil dihapus.');
    header('Location: ' . BASE_URL . 'arsip.php');
    exit;
}

$keyword = '%' . trim($_GET['q'] ?? '') . '%';
$stmt = db()->prepare('SELECT a.*, s.nama_siswa FROM arsip a LEFT JOIN siswa s ON a.siswa_id = s.id WHERE a.tahun_ajaran = :ta AND (s.nama_siswa LIKE :keyword OR a.kelas LIKE :keyword) ORDER BY a.id DESC');
$stmt->execute(['ta' => $active_ta, 'keyword' => $keyword]);
$archives = $stmt->fetchAll();

$pageTitle = 'Data Arsip';
require_once __DIR__ . '/../app/views/layout/header.php';
?>
<section class="content-section">
    <div class="content-header">
        <h2>Data Arsip</h2>
        <form method="get" action="<?= BASE_URL ?>arsip.php" class="search-form">
            <input type="text" name="q" placeholder="Cari nama siswa atau kelas..."
                value="<?= escape_html(trim($_GET['q'] ?? '')) ?>">
            <button type="submit">Cari</button>
        </form>
    </div>
    <div class="grid-two-column" <?= $user['role'] !== 'admin' ? 'style="display: block;"' : '' ?>>
        <div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Poto</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jilid</th>
                        <th>Asal Sekolah</th>
                        <?php if ($user['role'] === 'admin'): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archives as $index => $archive): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><a class="button small" target="_blank"
                                    href="<?= BASE_URL . escape_html($archive['poto']) ?>">Lihat</a></td>
                            <td>
                                <img src="<?= BASE_URL . escape_html($archive['poto']) ?>" alt="Poto"
                                    style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23cbd5e1\'><path d=\'M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z\'/></svg>'">
                            </td>
                            <td><?= escape_html($archive['nama_siswa'] ?? 'Unknown') ?></td>
                            <td><?= escape_html($archive['kelas']) ?></td>
                            <td><?= escape_html($archive['jilid']) ?></td>
                            <td><?= escape_html($archive['asal_sekolah']) ?></td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="btn-icon-action" title="Edit Data">
                                            <img src="<?= BASE_URL ?>../assets/images/pencil_edit.png" alt="Edit"
                                                onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%231f88eb\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\'></path><path d=\'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\'></path></svg>'">
                                        </a>
                                        <form method="post" action="<?= BASE_URL ?>arsip.php" style="display:inline;"
                                            onsubmit="return confirm('Hapus arsip ini?')">
                                            <input type="hidden" name="delete_id" value="<?= $archive['id'] ?>">
                                            <button type="submit" class="btn-icon-action" title="Hapus Data">
                                                <img src="<?= BASE_URL ?>../assets/images/trash_delete.png" alt="Hapus"
                                                    onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23ef4444\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'3 6 5 6 21 6\'></polyline><path d=\'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2\'></path></svg>'">
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
            <div class="form-panel">
                <h3>Tambah Arsip</h3>
                <form method="post" action="<?= BASE_URL ?>arsip.php" enctype="multipart/form-data">
                    <input type="hidden" name="add_arsip" value="1">
                    <label>ID Siswa</label>
                    <input type="number" name="siswa_id" required>
                    <label>Kelas</label>
                    <input type="text" name="kelas" required>
                    <label>Jilid</label>
                    <input type="text" name="jilid" required>
                    <label>Asal Sekolah</label>
                    <input type="text" name="asal_sekolah">
                    <label>Poto (File Gambar)</label>
                    <input type="file" name="file" accept="application/pdf,image/jpeg,image/png" required>
                    <button type="submit" class="button">Unggah</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../app/views/layout/footer.php';
