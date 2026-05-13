<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/validation.php';
require_once __DIR__ . '/../app/helpers/flash.php';

requireLogin();

$active_ta = $_SESSION['tahun_ajaran'] ?? '2024/2025 Ganjil';
$user = currentUser();

// Tangkap jilid dari URL (misal: rekap.php?jilid=Buku 1)
$jilid = $_GET['jilid'] ?? 'Buku 1';

// Proses Hapus Data dari tabel arsip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = db()->prepare('DELETE FROM arsip WHERE id = :id');
    $stmt->execute(['id' => $deleteId]);
    flash('success', 'Data berhasil dihapus.');
    header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
    exit;
}

// Proses Tambah Data ke tabel arsip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_arsip'])) {
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid_add = trim($_POST['jilid'] ?? '');
    $asal_sekolah = trim($_POST['asal_sekolah'] ?? '');

    if ($siswa_id <= 0 || $kelas === '' || $jilid_add === '') {
        flash('error', 'Siswa, Kelas, dan Jilid harus diisi.');
        header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
        exit;
    }

    if (empty($_FILES['poto']['name']) || $_FILES['poto']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Unggah file poto arsip terlebih dahulu.');
        header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
        exit;
    }

    $file = $_FILES['poto'];
    $fileType = mime_content_type($file['tmp_name']);
    $fileSize = $file['size'];

    if (!in_array($fileType, ALLOWED_UPLOAD_TYPES, true)) {
        flash('error', 'Jenis file tidak diperbolehkan. Gunakan PDF atau gambar.');
        header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
        exit;
    }

    if ($fileSize > MAX_UPLOAD_SIZE) {
        flash('error', 'Ukuran file terlalu besar. Maksimal 3 MB.');
        header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
        exit;
    }

    $filename = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($file['name']));
    $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $fileUrl = 'uploads/' . $filename;
        $stmt = db()->prepare('INSERT INTO arsip (poto, siswa_id, kelas, jilid, asal_sekolah, tahun_ajaran, created_at) VALUES (:poto, :siswa_id, :kelas, :jilid, :asal_sekolah, :tahun_ajaran, NOW())');
        $stmt->execute(['poto' => $fileUrl, 'siswa_id' => $siswa_id, 'kelas' => $kelas, 'jilid' => $jilid_add, 'asal_sekolah' => $asal_sekolah, 'tahun_ajaran' => $active_ta]);
        flash('success', 'Data arsip berhasil ditambahkan.');
    } else {
        flash('error', 'Gagal menyimpan file arsip.');
    }
    header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
    exit;
}

// Proses Edit Data dari tabel arsip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_arsip'])) {
    $id = (int)($_POST['id'] ?? 0);
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid_edit = trim($_POST['jilid'] ?? '');
    $asal_sekolah = trim($_POST['asal_sekolah'] ?? '');

    if ($id <= 0 || $kelas === '' || $jilid_edit === '') {
        flash('error', 'Kelas dan Jilid harus diisi.');
        header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
        exit;
    }

    $updateQuery = 'UPDATE arsip SET kelas = :kelas, jilid = :jilid, asal_sekolah = :asal_sekolah WHERE id = :id';
    $params = ['id' => $id, 'kelas' => $kelas, 'jilid' => $jilid_edit, 'asal_sekolah' => $asal_sekolah];

    if (!empty($_FILES['poto']['name']) && $_FILES['poto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['poto'];
        $fileType = mime_content_type($file['tmp_name']);
        $fileSize = $file['size'];

        if (!in_array($fileType, ALLOWED_UPLOAD_TYPES, true)) {
            flash('error', 'Jenis file tidak diperbolehkan. Gunakan PDF atau gambar.');
            header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
            exit;
        }
        if ($fileSize > MAX_UPLOAD_SIZE) {
            flash('error', 'Ukuran file terlalu besar. Maksimal 3 MB.');
            header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
            exit;
        }

        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($file['name']));
        $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $updateQuery = 'UPDATE arsip SET kelas = :kelas, jilid = :jilid, asal_sekolah = :asal_sekolah, poto = :poto WHERE id = :id';
            $params['poto'] = 'uploads/' . $filename;
        }
    }

    $stmt = db()->prepare($updateQuery);
    $stmt->execute($params);
    flash('success', 'Data arsip berhasil diperbarui.');
    header('Location: ' . BASE_URL . 'rekap.php?jilid=' . urlencode($jilid));
    exit;
}

// Parameter Pagination, Pencarian & Filter Kelas
$search = trim($_GET['q'] ?? '');
$filter_kelas = trim($_GET['filter_kelas'] ?? '');
$show_entries = isset($_GET['show_entries']) ? max(1, (int)$_GET['show_entries']) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$queryCondition = 'WHERE a.jilid = :jilid AND a.tahun_ajaran = :ta';
if ($filter_kelas !== '') {
    $queryCondition .= ' AND a.kelas = :kelas';
}
if ($search !== '') {
    $queryCondition .= ' AND (s.nama_siswa LIKE :keyword OR a.kelas LIKE :keyword)';
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM arsip a JOIN siswa s ON a.siswa_id = s.id $queryCondition");
$countStmt->bindValue(':jilid', $jilid, PDO::PARAM_STR);
$countStmt->bindValue(':ta', $active_ta, PDO::PARAM_STR);
if ($filter_kelas !== '') {
    $countStmt->bindValue(':kelas', $filter_kelas, PDO::PARAM_STR);
}
if ($search !== '') {
    $countStmt->bindValue(':keyword', "%$search%", PDO::PARAM_STR);
}
$countStmt->execute();
$total_records = (int) $countStmt->fetchColumn();

$total_pages = ceil($total_records / $show_entries);
$page = max(1, min($page, $total_pages > 0 ? $total_pages : 1));
$offset = ($page - 1) * $show_entries;

$stmt = db()->prepare("SELECT a.*, s.nama_siswa FROM arsip a JOIN siswa s ON a.siswa_id = s.id $queryCondition ORDER BY a.id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':jilid', $jilid, PDO::PARAM_STR);
$stmt->bindValue(':ta', $active_ta, PDO::PARAM_STR);
if ($filter_kelas !== '') {
    $stmt->bindValue(':kelas', $filter_kelas, PDO::PARAM_STR);
}
if ($search !== '') {
    $stmt->bindValue(':keyword', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $show_entries, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll();

$stmtSiswa = db()->query('SELECT id, nama_siswa FROM siswa ORDER BY nama_siswa ASC');
$siswa_list = $stmtSiswa->fetchAll();

$pageTitle = 'Rekap ' . $jilid;
require_once __DIR__ . '/../app/views/layout/header.php';
?>

<section class="content-section guru-page-content">

    <!-- Title Section dengan Icon Statistik -->
    <div style="margin-top: 32px; margin-bottom: 24px;">
        <h2
            style="margin: 0 0 16px 0; font-family: 'Jomolhari', serif; font-size: 36px; display: flex; align-items: center; gap: 12px; color: #000;">
            <img src="<?= BASE_URL ?>../assets/images/statistik.png" alt="Icon Statistik"
                style="width: 64px; height: 64px; object-fit: contain;">
            Arsip Data : <?= escape_html($jilid) ?>
        </h2>
        <div style="width: 100%; height: 4px; background-color: rgba(13, 193, 199, 0.97); border-radius: 2px;"></div>
    </div>

    <?php if (hasFlash('success')) : ?>
        <div class="alert success"><?= escape_html(flash('success')) ?></div>
    <?php endif; ?>

    <div class="guru-card" style="margin-top: 0;">
        <!-- TOOLBAR PENCARIAN -->
        <div class="guru-toolbar">
            <button type="button" class="btn-tambah-guru" onclick="openModal()">
                <span>+</span> Tambah Data
            </button>
            <div class="guru-search-wrapper">
                <form method="GET" action="<?= BASE_URL ?>rekap.php"
                    style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                    <input type="hidden" name="jilid" value="<?= escape_html($jilid) ?>">
                    <input type="hidden" name="filter_kelas" value="<?= escape_html($filter_kelas) ?>">
                    <div class="guru-search-container" style="width: 260px; position: relative;">
                        <img src="<?= BASE_URL ?>../assets/images/vektor%20search.png" alt="Search"
                            class="guru-search-icon" onerror="this.style.display='none'">
                        <input type="text" name="q" placeholder="Cari nama atau kelas..."
                            value="<?= escape_html($search) ?>" style="width: 100%;">
                    </div>
                    <input type="hidden" name="show_entries" value="<?= $show_entries ?>">
                    <button type="submit"
                        style="padding: 12px 20px; background-color: #a855f7; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; white-space: nowrap;">Cari</button>
                </form>
            </div>
        </div>

        <!-- ENTRIES & FILTER SELECTOR (SEJAJAR) -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 16px;">
            <!-- ENTRIES SELECTOR -->
            <div class="guru-entries" style="margin-bottom: 0;">
                <form method="GET" action="<?= BASE_URL ?>rekap.php"
                    style="display: flex; align-items: center; gap: 8px;">
                    <span>Show</span>
                    <select name="show_entries" onchange="this.form.submit()">
                        <option value="10" <?= $show_entries == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $show_entries == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $show_entries == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $show_entries == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                    <span>entries</span>
                    <input type="hidden" name="jilid" value="<?= escape_html($jilid) ?>">
                    <input type="hidden" name="filter_kelas" value="<?= escape_html($filter_kelas) ?>">
                    <input type="hidden" name="q" value="<?= escape_html($search) ?>">
                    <input type="hidden" name="page" value="1">
                </form>
            </div>

            <!-- FILTER KELAS -->
            <div class="guru-filter">
                <form method="GET" action="<?= BASE_URL ?>rekap.php"
                    style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: 600; font-size: 15px; color: #333; white-space: nowrap;">Filter
                        Berdasarkan Kelas :</span>
                    <select name="filter_kelas" onchange="this.form.submit()"
                        style="border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 15px; cursor: pointer; transition: border-color 0.3s; background: #fff; outline: none;">
                        <option value="">Semua Kelas</option>
                        <option value="71" <?= $filter_kelas === '71' ? 'selected' : '' ?>>71</option>
                        <option value="72" <?= $filter_kelas === '72' ? 'selected' : '' ?>>72</option>
                        <option value="73" <?= $filter_kelas === '73' ? 'selected' : '' ?>>73</option>
                        <option value="74" <?= $filter_kelas === '74' ? 'selected' : '' ?>>74</option>
                        <option value="81" <?= $filter_kelas === '81' ? 'selected' : '' ?>>81</option>
                        <option value="82" <?= $filter_kelas === '82' ? 'selected' : '' ?>>82</option>
                        <option value="83" <?= $filter_kelas === '83' ? 'selected' : '' ?>>83</option>
                        <option value="84" <?= $filter_kelas === '84' ? 'selected' : '' ?>>84</option>
                        <option value="91" <?= $filter_kelas === '91' ? 'selected' : '' ?>>91</option>
                        <option value="92" <?= $filter_kelas === '92' ? 'selected' : '' ?>>92</option>
                        <option value="93" <?= $filter_kelas === '93' ? 'selected' : '' ?>>93</option>
                        <option value="94" <?= $filter_kelas === '94' ? 'selected' : '' ?>>94</option>
                    </select>
                    <input type="hidden" name="jilid" value="<?= escape_html($jilid) ?>">
                    <input type="hidden" name="show_entries" value="<?= escape_html($show_entries) ?>">
                    <input type="hidden" name="q" value="<?= escape_html($search) ?>">
                    <input type="hidden" name="page" value="1">
                </form>
            </div>
        </div>

        <!-- TABLE -->
        <div class="guru-table-wrapper">
            <table class="guru-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Poto</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jilid</th>
                        <th>Asal Sekolah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php foreach ($results as $index => $row): ?>
                            <tr>
                                <td data-label="No"><?= $offset + $index + 1 ?></td>
                                <td data-label="Poto">
                                    <img src="<?= BASE_URL . escape_html($row['poto']) ?>" alt="Poto"
                                        style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;"
                                        onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23cbd5e1\'><path d=\'M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z\'/></svg>'">
                                </td>
                                <td data-label="Nama Siswa"><?= escape_html($row['nama_siswa']) ?></td>
                                <td data-label="Kelas"><?= escape_html($row['kelas']) ?></td>
                                <td data-label="Jilid"><?= escape_html($row['jilid']) ?></td>
                                <td data-label="Asal Sekolah"><?= escape_html($row['asal_sekolah'] ?? '-') ?></td>
                                <td data-label="Aksi">
                                    <div class="action-buttons-claude">
                                        <a href="#" class="btn-edit-claude" title="Edit Data"
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>); return false;">
                                            <img src="<?= BASE_URL ?>../assets/images/pencil%20edit.png" alt="Edit"
                                                onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\'></path><path d=\'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\'></path></svg>'">
                                        </a>
                                        <form method="post" action="<?= BASE_URL ?>rekap.php?jilid=<?= urlencode($jilid) ?>"
                                            style="display:flex; margin:0;" onsubmit="return confirm('Hapus data ini?')">
                                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn-delete-claude" title="Hapus Data">
                                                <img src="<?= BASE_URL ?>../assets/images/trash%20delete.png" alt="Hapus"
                                                    onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'3 6 5 6 21 6\'></polyline><path d=\'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2\'></path></svg>'">
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 32px; color: #999;">Belum ada data
                                arsip/siswa untuk <?= escape_html($jilid) ?> yang cocok.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER INFO -->
        <div class="guru-footer-info">
            Menampilkan <?= count($results) > 0 ? ($offset + 1) : 0 ?> sampai <?= $offset + count($results) ?> dari
            <?= $total_records ?> entries
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="guru-pagination">
                <?php if ($page > 1): ?>
                    <a
                        href="?jilid=<?= urlencode($jilid) ?>&page=1&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">«
                        Pertama</a>
                    <a
                        href="?jilid=<?= urlencode($jilid) ?>&page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">‹
                        Sebelumnya</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1): ?>
                    <span>...</span>
                <?php endif;

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a
                            href="?jilid=<?= urlencode($jilid) ?>&page=<?= $i ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor;

                if ($end_page < $total_pages): ?>
                    <span>...</span>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a
                        href="?jilid=<?= urlencode($jilid) ?>&page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">Selanjutnya
                        ›</a>
                    <a
                        href="?jilid=<?= urlencode($jilid) ?>&page=<?= $total_pages ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">Terakhir
                        »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- MODAL TAMBAH DATA ARSIP -->
<div id="modalTambahData" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Tambah Data Arsip</h2>
            <button type="button" class="modal-close" onclick="closeModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>rekap.php?jilid=<?= urlencode($jilid) ?>" id="formTambahData"
            enctype="multipart/form-data">
            <input type="hidden" name="add_arsip" value="1">

            <div class="modal-form-group">
                <label for="siswa_id">Nama Siswa</label>
                <select id="siswa_id" name="siswa_id" required>
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($siswa_list as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= escape_html($s['nama_siswa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="kelas">Kelas</label>
                <select id="kelas" name="kelas" required>
                    <option value="">-- Pilih Kelas --</option>
                    <option value="71">71</option>
                    <option value="72">72</option>
                    <option value="73">73</option>
                    <option value="74">74</option>
                    <option value="81">81</option>
                    <option value="82">82</option>
                    <option value="83">83</option>
                    <option value="84">84</option>
                    <option value="91">91</option>
                    <option value="92">92</option>
                    <option value="93">93</option>
                    <option value="94">94</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="jilid">Jilid</label>
                <select id="jilid" name="jilid" required>
                    <option value="">-- Pilih Jilid --</option>
                    <option value="Buku 1">Buku 1</option>
                    <option value="Buku 2">Buku 2</option>
                    <option value="Buku 3">Buku 3</option>
                    <option value="Al-Qur'an">Al-Qur'an</option>
                    <option value="Gharib">Gharib</option>
                    <option value="Tajwid">Tajwid</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="asal_sekolah">Asal Sekolah</label>
                <input type="text" id="asal_sekolah" name="asal_sekolah" placeholder="Masukan Asal Sekolah">
            </div>

            <div class="modal-form-group">
                <label for="poto">Poto (Wajib)</label>
                <input type="file" id="poto" name="poto" accept="application/pdf,image/jpeg,image/png" required>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT DATA ARSIP -->
<div id="modalEditData" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Data Arsip</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>rekap.php?jilid=<?= urlencode($jilid) ?>" id="formEditData"
            enctype="multipart/form-data">
            <input type="hidden" name="edit_arsip" value="1">
            <input type="hidden" name="id" id="edit_id" value="">

            <div class="modal-form-group">
                <label for="edit_nama_siswa">Nama Siswa</label>
                <input type="text" id="edit_nama_siswa" readonly
                    style="background-color: #f3f4f6; cursor: not-allowed; color: #666;">
            </div>

            <div class="modal-form-group">
                <label for="edit_kelas">Kelas</label>
                <select id="edit_kelas" name="kelas" required>
                    <option value="">-- Pilih Kelas --</option>
                    <option value="71">71</option>
                    <option value="72">72</option>
                    <option value="73">73</option>
                    <option value="74">74</option>
                    <option value="81">81</option>
                    <option value="82">82</option>
                    <option value="83">83</option>
                    <option value="84">84</option>
                    <option value="91">91</option>
                    <option value="92">92</option>
                    <option value="93">93</option>
                    <option value="94">94</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_jilid">Jilid</label>
                <select id="edit_jilid" name="jilid" required>
                    <option value="">-- Pilih Jilid --</option>
                    <option value="Buku 1">Buku 1</option>
                    <option value="Buku 2">Buku 2</option>
                    <option value="Buku 3">Buku 3</option>
                    <option value="Al-Qur'an">Al-Qur'an</option>
                    <option value="Gharib">Gharib</option>
                    <option value="Tajwid">Tajwid</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_asal_sekolah">Asal Sekolah</label>
                <input type="text" id="edit_asal_sekolah" name="asal_sekolah" placeholder="Masukan Asal Sekolah">
            </div>

            <div class="modal-form-group">
                <label for="edit_poto">Ubah Poto (Opsional)</label>
                <input type="file" id="edit_poto" name="poto" accept="application/pdf,image/jpeg,image/png">
                <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">Biarkan kosong jika tidak
                    ingin mengubah poto saat ini.</small>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-simpan">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalTambahData').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modalTambahData').classList.remove('show');
        document.body.style.overflow = 'auto';
        document.getElementById('formTambahData').reset();
    }

    function openEditModal(data) {
        document.getElementById('edit_id').value = data.id || '';
        document.getElementById('edit_nama_siswa').value = data.nama_siswa || '';
        document.getElementById('edit_kelas').value = data.kelas || '';
        document.getElementById('edit_jilid').value = data.jilid || '';
        document.getElementById('edit_asal_sekolah').value = data.asal_sekolah || '';
        document.getElementById('modalEditData').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('modalEditData').classList.remove('show');
        document.body.style.overflow = 'auto';
        document.getElementById('formEditData').reset();
    }

    window.addEventListener('click', function(event) {
        const modalTambah = document.getElementById('modalTambahData');
        const modalEdit = document.getElementById('modalEditData');
        if (event.target === modalTambah) {
            closeModal();
        } else if (event.target === modalEdit) {
            closeEditModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (typeof closeModal === 'function') closeModal();
            if (typeof closeEditModal === 'function') closeEditModal();
        }
    });
</script>

<?php require_once __DIR__ . '/../app/views/layout/footer.php'; ?>