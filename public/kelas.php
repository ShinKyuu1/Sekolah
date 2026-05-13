<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';
requireLogin();

$user = currentUser();

// --- Proses Tambah Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kelas'])) {
    $id_kelas = trim($_POST['id_kelas'] ?? '');
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid = trim($_POST['jilid'] ?? '');
    $hal = trim($_POST['hal'] ?? '');
    $guru_id = (int)($_POST['guru_id'] ?? 0);

    if ($siswa_id <= 0 || $guru_id <= 0) {
        flash('error', 'Siswa dan Guru Pengajar harus dipilih.');
        header('Location: ' . BASE_URL . 'kelas.php');
        exit;
    }

    $stmt = db()->prepare('INSERT INTO kelas (id_kelas, siswa_id, kelas, jilid, hal, guru_id, created_at) VALUES (:id_kelas, :siswa_id, :kelas, :jilid, :hal, :guru_id, NOW())');
    $stmt->execute([
        'id_kelas' => $id_kelas,
        'siswa_id' => $siswa_id,
        'kelas' => $kelas,
        'jilid' => $jilid,
        'hal' => $hal,
        'guru_id' => $guru_id,
    ]);

    flash('success', 'Data kelas berhasil ditambahkan.');
    header('Location: ' . BASE_URL . 'kelas.php');
    exit;
}

// --- Proses Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_kelas'])) {
    $id = (int)($_POST['id'] ?? 0);
    $id_kelas = trim($_POST['id_kelas'] ?? '');
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid = trim($_POST['jilid'] ?? '');
    $hal = trim($_POST['hal'] ?? '');
    $guru_id = (int)($_POST['guru_id'] ?? 0);

    if ($id <= 0 || $siswa_id <= 0 || $guru_id <= 0) {
        flash('error', 'Siswa dan Guru Pengajar harus diisi.');
        header('Location: ' . BASE_URL . 'kelas.php');
        exit;
    }

    $stmt = db()->prepare('UPDATE kelas SET id_kelas = :id_kelas, siswa_id = :siswa_id, kelas = :kelas, jilid = :jilid, hal = :hal, guru_id = :guru_id WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'id_kelas' => $id_kelas,
        'siswa_id' => $siswa_id,
        'kelas' => $kelas,
        'jilid' => $jilid,
        'hal' => $hal,
        'guru_id' => $guru_id,
    ]);

    flash('success', 'Data kelas berhasil diperbarui.');
    header('Location: ' . BASE_URL . 'kelas.php');
    exit;
}

// --- Proses Hapus Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = db()->prepare('DELETE FROM kelas WHERE id = :id');
    $stmt->execute(['id' => $deleteId]);
    flash('success', 'Data kelas berhasil dihapus.');
    header('Location: ' . BASE_URL . 'kelas.php');
    exit;
}

// Persiapan Opsi Dropdown untuk Form (Siswa & Guru)
$siswa_list = db()->query('SELECT id, nama_siswa FROM siswa ORDER BY nama_siswa ASC')->fetchAll();
$guru_list = db()->query('SELECT id, nama FROM users WHERE role="guru" ORDER BY nama ASC')->fetchAll();

// Parameter Pagination & Pencarian
$search = trim($_GET['q'] ?? '');
$filter_kelas = trim($_GET['filter_kelas'] ?? '');
$show_entries = isset($_GET['show_entries']) ? max(1, (int)$_GET['show_entries']) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$queryCondition = 'WHERE (s.nama_siswa LIKE :keyword1 OR u.nama LIKE :keyword2)';
if ($filter_kelas !== '') {
    $queryCondition .= ' AND k.kelas = :kelas';
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM kelas k JOIN siswa s ON k.siswa_id = s.id JOIN users u ON k.guru_id = u.id $queryCondition");
if ($filter_kelas !== '') {
    $countStmt->bindValue(':kelas', $filter_kelas, PDO::PARAM_STR);
}
$countStmt->bindValue(':keyword1', "%$search%", PDO::PARAM_STR);
$countStmt->bindValue(':keyword2', "%$search%", PDO::PARAM_STR);
$countStmt->execute();
$total_records = (int) $countStmt->fetchColumn();

$total_pages = ceil($total_records / $show_entries);
$page = max(1, min($page, $total_pages > 0 ? $total_pages : 1));
$offset = ($page - 1) * $show_entries;

// Fetch Data Kelas
$stmt = db()->prepare("
    SELECT k.id, k.id_kelas, k.siswa_id, k.kelas, k.jilid, k.hal, k.guru_id, s.nama_siswa, u.nama as nama_guru 
    FROM kelas k 
    JOIN siswa s ON k.siswa_id = s.id 
    JOIN users u ON k.guru_id = u.id 
    $queryCondition 
    ORDER BY k.id DESC 
    LIMIT :limit OFFSET :offset
");
if ($filter_kelas !== '') {
    $stmt->bindValue(':kelas', $filter_kelas, PDO::PARAM_STR);
}
$stmt->bindValue(':keyword1', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':keyword2', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $show_entries, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$kelas_records = $stmt->fetchAll();

$pageTitle = 'Data Kelas';
require_once __DIR__ . '/../app/views/layout/header.php';
?>
<section class="content-section guru-page-content">
    <div class="guru-card">
        <!-- TOOLBAR -->
        <div class="guru-toolbar">
            <button type="button" class="btn-tambah-guru" onclick="openModal()">
                <span>+</span> Tambah Data
            </button>
            <div class="guru-search-wrapper">
                <form method="GET" action="<?= BASE_URL ?>kelas.php"
                    style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                    <input type="hidden" name="filter_kelas" value="<?= escape_html($filter_kelas) ?>">
                    <div class="guru-search-container" style="width: 260px; position: relative;">
                        <img src="<?= BASE_URL ?>../assets/images/vektor%20search.png" alt="Search"
                            class="guru-search-icon" onerror="this.style.display='none'">
                        <input type="text" name="q" placeholder="Search..." value="<?= escape_html($search) ?>"
                            style="width: 100%;">
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
                <form method="GET" action="<?= BASE_URL ?>kelas.php"
                    style="display: flex; align-items: center; gap: 8px;">
                    <span>Show</span>
                    <select name="show_entries" onchange="this.form.submit()">
                        <option value="10" <?= $show_entries == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $show_entries == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $show_entries == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $show_entries == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                    <span>entries</span>
                    <input type="hidden" name="filter_kelas" value="<?= escape_html($filter_kelas) ?>">
                    <input type="hidden" name="q" value="<?= escape_html($search) ?>">
                    <input type="hidden" name="page" value="1">
                </form>
            </div>

            <!-- FILTERS WRAPPER -->
            <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                <!-- FILTER KELAS -->
                <div class="guru-filter">
                    <form method="GET" action="<?= BASE_URL ?>kelas.php"
                        style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; font-size: 15px; color: #333; white-space: nowrap;">Filter
                            Kelas:</span>
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
                        <input type="hidden" name="show_entries" value="<?= escape_html($show_entries) ?>">
                        <input type="hidden" name="q" value="<?= escape_html($search) ?>">
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="guru-table-wrapper">
            <table class="guru-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jilid</th>
                        <th>Hal</th>
                        <th>Nama Guru</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kelas_records) > 0): ?>
                        <?php foreach ($kelas_records as $index => $row): ?>
                            <tr>
                                <td data-label="No"><?= $offset + $index + 1 ?></td>
                                <td data-label="Nama Siswa"><?= escape_html($row['nama_siswa']) ?></td>
                                <td data-label="Kelas"><?= escape_html($row['kelas'] ?? '-') ?></td>
                                <td data-label="Jilid"><?= escape_html($row['jilid'] ?? '-') ?></td>
                                <td data-label="Hal"><?= escape_html($row['hal'] ?? '-') ?></td>
                                <td data-label="Nama Guru"><?= escape_html($row['nama_guru'] ?? '-') ?></td>
                                <td data-label="Aksi">
                                    <div class="action-buttons-claude">
                                        <a href="#" class="btn-edit-claude" title="Edit Data"
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>); return false;">
                                            <img src="<?= BASE_URL ?>../assets/images/pencil%20edit.png" alt="Edit"
                                                onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\'></path><path d=\'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\'></path></svg>'">
                                        </a>
                                        <form method="post" action="<?= BASE_URL ?>kelas.php" style="display:flex; margin:0;"
                                            onsubmit="return confirm('Hapus data ini?')">
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
                            <td colspan="7"
                                style="text-align: center; padding: 32px; color: #999;">Tidak ada data yang
                                ditemukan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER INFO -->
        <div class="guru-footer-info">
            Menampilkan <?= count($kelas_records) > 0 ? ($offset + 1) : 0 ?> sampai
            <?= $offset + count($kelas_records) ?> dari
            <?= $total_records ?> entries
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="guru-pagination">
                <?php if ($page > 1): ?>
                    <a
                        href="?page=1&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">«
                        Pertama</a>
                    <a
                        href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">‹
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
                            href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor;

                if ($end_page < $total_pages): ?>
                    <span>...</span>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a
                        href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">Selanjutnya
                        ›</a>
                    <a
                        href="?page=<?= $total_pages ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_kelas=<?= urlencode($filter_kelas) ?>">Terakhir
                        »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- MODAL TAMBAH DATA -->
<div id="modalTambahData" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Tambah Data Kelas</h2>
            <button type="button" class="modal-close" onclick="closeModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>kelas.php" id="formTambahData">
            <input type="hidden" name="add_kelas" value="1">

            <div class="modal-form-group">
                <label for="id_kelas">Nomor Data Kelas</label>
                <input type="text" id="id_kelas" name="id_kelas" placeholder="Masukan ID">
            </div>

            <div class="modal-form-group">
                <label for="siswa_id">Masukan Nama Siswa</label>
                <select id="siswa_id" name="siswa_id" required>
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($siswa_list as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= escape_html($s['nama_siswa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="kelas">Masukan Kelas Siswa</label>
                <input type="text" id="kelas" name="kelas" placeholder="Masukan Kelas Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="jilid">Masukan Jilid Siswa</label>
                <input type="text" id="jilid" name="jilid" placeholder="Masukan Jilid Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="hal">Masukan Halaman</label>
                <input type="text" id="hal" name="hal" placeholder="Masukan Halaman Terakhir" required>
            </div>

            <div class="modal-form-group">
                <label for="guru_id">Masukan Nama Guru Pengajar</label>
                <select id="guru_id" name="guru_id" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php foreach ($guru_list as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= escape_html($g['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT DATA -->
<div id="modalEditData" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Data Kelas</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>kelas.php" id="formEditData">
            <input type="hidden" name="edit_kelas" value="1">
            <input type="hidden" name="id" id="edit_id" value="">

            <div class="modal-form-group">
                <label for="edit_id_kelas">Nomor Data Kelas</label>
                <input type="text" id="edit_id_kelas" name="id_kelas" placeholder="Masukan ID">
            </div>

            <div class="modal-form-group">
                <label for="edit_siswa_id">Masukan Nama Siswa</label>
                <select id="edit_siswa_id" name="siswa_id" required>
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($siswa_list as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= escape_html($s['nama_siswa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_kelas_field">Masukan Kelas Siswa</label>
                <select id="edit_kelas_field" name="kelas" required>
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
                <label for="edit_jilid">Masukan Jilid Siswa</label>
                <input type="text" id="edit_jilid" name="jilid" placeholder="Masukan Jilid Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_hal">Masukan Halaman</label>
                <input type="text" id="edit_hal" name="hal" placeholder="Masukan Halaman Terakhir" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_guru_id">Masukan Nama Guru Pengajar</label>
                <select id="edit_guru_id" name="guru_id" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php foreach ($guru_list as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= escape_html($g['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
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

    function openEditModal(kelasData) {
        document.getElementById('edit_id').value = kelasData.id || '';
        document.getElementById('edit_id_kelas').value = kelasData.id_kelas || '';
        document.getElementById('edit_siswa_id').value = kelasData.siswa_id || '';
        document.getElementById('edit_kelas_field').value = kelasData.kelas || '';
        document.getElementById('edit_jilid').value = kelasData.jilid || '';
        document.getElementById('edit_hal').value = kelasData.hal || '';
        document.getElementById('edit_guru_id').value = kelasData.guru_id || '';

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
            closeModal();
            closeEditModal();
        }
    });
</script>
<?php require_once __DIR__ . '/../app/views/layout/footer.php';
