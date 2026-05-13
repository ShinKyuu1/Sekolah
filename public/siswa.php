<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';
requireLogin();

$user = currentUser();

// --- Proses Tambah Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_siswa']) && $user['role'] === 'admin') {
    $id_siswa = trim($_POST['id_siswa'] ?? '');
    $nis = trim($_POST['nis'] ?? '');
    $nama_siswa = trim($_POST['nama_siswa'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid = trim($_POST['jilid'] ?? '');
    $halaman = trim($_POST['halaman'] ?? '');

    if ($nama_siswa === '') {
        flash('error', 'Nama Siswa harus diisi.');
        header('Location: ' . BASE_URL . 'siswa.php');
        exit;
    }

    $stmt = db()->prepare('INSERT INTO siswa (id_siswa, nis, nama_siswa, kelas, jilid, halaman, created_at) VALUES (:id_siswa, :nis, :nama_siswa, :kelas, :jilid, :halaman, NOW())');
    $stmt->execute([
        'id_siswa' => $id_siswa,
        'nis' => $nis,
        'nama_siswa' => $nama_siswa,
        'kelas' => $kelas,
        'jilid' => $jilid,
        'halaman' => $halaman,
    ]);

    flash('success', 'Data siswa berhasil ditambahkan.');
    header('Location: ' . BASE_URL . 'siswa.php');
    exit;
}

// --- Proses Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_siswa']) && $user['role'] === 'admin') {
    $id = (int)($_POST['id'] ?? 0);
    $id_siswa = trim($_POST['id_siswa'] ?? '');
    $nis = trim($_POST['nis'] ?? '');
    $nama_siswa = trim($_POST['nama_siswa'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid = trim($_POST['jilid'] ?? '');
    $halaman = trim($_POST['halaman'] ?? '');

    if ($id <= 0 || $nama_siswa === '') {
        flash('error', 'Nama Siswa harus diisi.');
        header('Location: ' . BASE_URL . 'siswa.php');
        exit;
    }

    $stmt = db()->prepare('UPDATE siswa SET id_siswa = :id_siswa, nis = :nis, nama_siswa = :nama_siswa, kelas = :kelas, jilid = :jilid, halaman = :halaman WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'id_siswa' => $id_siswa,
        'nis' => $nis,
        'nama_siswa' => $nama_siswa,
        'kelas' => $kelas,
        'jilid' => $jilid,
        'halaman' => $halaman,
    ]);

    flash('success', 'Data siswa berhasil diperbarui.');
    header('Location: ' . BASE_URL . 'siswa.php');
    exit;
}

// --- Proses Hapus Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $user['role'] === 'admin') {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = db()->prepare('DELETE FROM siswa WHERE id = :id');
    $stmt->execute(['id' => $deleteId]);
    flash('success', 'Data siswa berhasil dihapus.');
    header('Location: ' . BASE_URL . 'siswa.php');
    exit;
}

// Parameter Pagination & Pencarian
$search = trim($_GET['q'] ?? '');
$filter_jilid = trim($_GET['filter_jilid'] ?? '');
$show_entries = isset($_GET['show_entries']) ? max(1, (int)$_GET['show_entries']) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$queryCondition = 'WHERE nama_siswa LIKE :keyword';
if ($filter_jilid !== '') {
    $queryCondition .= ' AND jilid = :jilid';
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM siswa $queryCondition");
if ($filter_jilid !== '') {
    $countStmt->bindValue(':jilid', $filter_jilid, PDO::PARAM_STR);
}
$countStmt->execute(['keyword' => "%$search%"]);
$total_records = (int) $countStmt->fetchColumn();

$total_pages = ceil($total_records / $show_entries);
$page = max(1, min($page, $total_pages > 0 ? $total_pages : 1));
$offset = ($page - 1) * $show_entries;

// Fetch Data Siswa
$stmt = db()->prepare("SELECT id, id_siswa, nis, nama_siswa, kelas, jilid, halaman FROM siswa $queryCondition ORDER BY id DESC LIMIT :limit OFFSET :offset");
if ($filter_jilid !== '') {
    $stmt->bindValue(':jilid', $filter_jilid, PDO::PARAM_STR);
}
$stmt->bindValue(':keyword', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $show_entries, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();

$pageTitle = 'Data Siswa';
require_once __DIR__ . '/../app/views/layout/header.php';
?>
<section class="content-section guru-page-content">
    <div class="guru-card">
        <!-- TOOLBAR -->
        <div class="guru-toolbar">
            <?php if ($user['role'] === 'admin'): ?>
                <button type="button" class="btn-tambah-guru" onclick="openModal()">
                    <span>+</span> Tambah Data
                </button>
            <?php endif; ?>
            <div class="guru-search-wrapper">
                <form method="GET" action="<?= BASE_URL ?>siswa.php"
                    style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                    <input type="hidden" name="filter_jilid" value="<?= escape_html($filter_jilid) ?>">
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
                <form method="GET" action="<?= BASE_URL ?>siswa.php"
                    style="display: flex; align-items: center; gap: 8px;">
                    <span>Show</span>
                    <select name="show_entries" onchange="this.form.submit()">
                        <option value="10" <?= $show_entries == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $show_entries == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $show_entries == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $show_entries == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                    <span>entries</span>
                    <input type="hidden" name="filter_jilid" value="<?= escape_html($filter_jilid) ?>">
                    <input type="hidden" name="q" value="<?= escape_html($search) ?>">
                    <input type="hidden" name="page" value="1">
                </form>
            </div>

            <!-- FILTER JILID -->
            <div class="guru-filter">
                <form method="GET" action="<?= BASE_URL ?>siswa.php"
                    style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: 600; font-size: 15px; color: #333; white-space: nowrap;">Filter
                        Berdasarkan Jilid :</span>
                    <select name="filter_jilid" onchange="this.form.submit()"
                        style="border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 15px; cursor: pointer; transition: border-color 0.3s; background: #fff; outline: none;">
                        <option value="">Semua Jilid</option>
                        <option value="Buku 1" <?= $filter_jilid === 'Buku 1' ? 'selected' : '' ?>>Buku 1</option>
                        <option value="Buku 2" <?= $filter_jilid === 'Buku 2' ? 'selected' : '' ?>>Buku 2</option>
                        <option value="Buku 3" <?= $filter_jilid === 'Buku 3' ? 'selected' : '' ?>>Buku 3</option>
                        <option value="Al-Qur'an" <?= $filter_jilid === "Al-Qur'an" ? 'selected' : '' ?>>Al-Qur'an
                        </option>
                        <option value="Gharib" <?= $filter_jilid === 'Gharib' ? 'selected' : '' ?>>Gharib</option>
                        <option value="Tajwid" <?= $filter_jilid === 'Tajwid' ? 'selected' : '' ?>>Tajwid</option>
                    </select>
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
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jilid</th>
                        <th>Halaman</th>
                        <?php if ($user['role'] === 'admin'): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td data-label="No"><?= $offset + $index + 1 ?></td>
                                <td data-label="Nama Siswa"><?= escape_html($student['nama_siswa']) ?></td>
                                <td data-label="Kelas"><?= escape_html($student['kelas']) ?></td>
                                <td data-label="Jilid"><?= escape_html($student['jilid']) ?></td>
                                <td data-label="Halaman"><?= escape_html($student['halaman'] ?? '-') ?></td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <td data-label="Aksi">
                                        <div class="action-buttons-claude">
                                            <a href="#" class="btn-edit-claude" title="Edit Data"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8') ?>); return false;">
                                                <img src="<?= BASE_URL ?>../assets/images/pencil%20edit.png" alt="Edit"
                                                    onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\'></path><path d=\'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\'></path></svg>'">
                                            </a>
                                            <form method="post" action="<?= BASE_URL ?>siswa.php" style="display:flex; margin:0;"
                                                onsubmit="return confirm('Hapus siswa ini?')">
                                                <input type="hidden" name="delete_id" value="<?= $student['id'] ?>">
                                                <button type="submit" class="btn-delete-claude" title="Hapus Data">
                                                    <img src="<?= BASE_URL ?>../assets/images/trash%20delete.png" alt="Hapus"
                                                        onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'3 6 5 6 21 6\'></polyline><path d=\'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2\'></path></svg>'">
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user['role'] === 'admin' ? '6' : '5' ?>"
                                style="text-align: center; padding: 32px; color: #999;">Tidak ada data yang
                                ditemukan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER INFO -->
        <div class="guru-footer-info">
            Menampilkan <?= count($students) > 0 ? ($offset + 1) : 0 ?> sampai <?= $offset + count($students) ?> dari
            <?= $total_records ?> entries
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="guru-pagination">
                <?php if ($page > 1): ?>
                    <a
                        href="?page=1&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_jilid=<?= urlencode($filter_jilid) ?>">«
                        Pertama</a>
                    <a
                        href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_jilid=<?= urlencode($filter_jilid) ?>">‹
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
                            href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_jilid=<?= urlencode($filter_jilid) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor;

                if ($end_page < $total_pages): ?>
                    <span>...</span>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a
                        href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_jilid=<?= urlencode($filter_jilid) ?>">Selanjutnya
                        ›</a>
                    <a
                        href="?page=<?= $total_pages ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>&filter_jilid=<?= urlencode($filter_jilid) ?>">Terakhir
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
            <h2>Tambah Data Siswa</h2>
            <button type="button" class="modal-close" onclick="closeModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>siswa.php" id="formTambahData">
            <input type="hidden" name="add_siswa" value="1">

            <div class="modal-form-group">
                <label for="id_siswa">Nomor Data Siswa</label>
                <input type="text" id="id_siswa" name="id_siswa" placeholder="Masukan ID">
            </div>

            <div class="modal-form-group">
                <label for="nis">NIS Data Siswa</label>
                <input type="text" id="nis" name="nis" placeholder="Masukan Nis Siswa">
            </div>

            <div class="modal-form-group">
                <label for="nama_siswa">Nama Data Siswa</label>
                <input type="text" id="nama_siswa" name="nama_siswa" placeholder="Masukan Nama Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="kelas">Kelas Data Siswa</label>
                <input type="text" id="kelas" name="kelas" placeholder="Masukan Kelas Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="jilid">Jilid Data Siswa</label>
                <input type="text" id="jilid" name="jilid" placeholder="Masukan Jilid Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="halaman">Halaman Data Siswa</label>
                <input type="text" id="halaman" name="halaman" placeholder="Masukan Halaman Siswa" required>
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
            <h2>Edit Data Siswa</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>siswa.php" id="formEditData">
            <input type="hidden" name="edit_siswa" value="1">
            <input type="hidden" name="id" id="edit_id" value="">

            <div class="modal-form-group">
                <label for="edit_id_siswa">Nomor Data Siswa</label>
                <input type="text" id="edit_id_siswa" name="id_siswa" placeholder="Masukan ID">
            </div>

            <div class="modal-form-group">
                <label for="edit_nis">NIS Data Siswa</label>
                <input type="text" id="edit_nis" name="nis" placeholder="Masukan Nis Siswa">
            </div>

            <div class="modal-form-group">
                <label for="edit_nama_siswa">Nama Data Siswa</label>
                <input type="text" id="edit_nama_siswa" name="nama_siswa" placeholder="Masukan Nama Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_kelas">Kelas Data Siswa</label>
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
                <label for="edit_jilid">Jilid Data Siswa</label>
                <input type="text" id="edit_jilid" name="jilid" placeholder="Masukan Jilid Siswa" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_halaman">Halaman Data Siswa</label>
                <input type="text" id="edit_halaman" name="halaman" placeholder="Masukan Halaman Siswa" required>
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

    function openEditModal(student) {
        document.getElementById('edit_id').value = student.id || '';
        document.getElementById('edit_id_siswa').value = student.id_siswa || '';
        document.getElementById('edit_nis').value = student.nis || '';
        document.getElementById('edit_nama_siswa').value = student.nama_siswa || '';
        document.getElementById('edit_kelas').value = student.kelas || '';
        document.getElementById('edit_jilid').value = student.jilid || '';
        document.getElementById('edit_halaman').value = student.halaman || '';

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
