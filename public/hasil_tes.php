<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';
require_once __DIR__ . '/../app/models/Siswa.php';
require_once __DIR__ . '/../app/models/User.php';
requireLogin();

$active_ta = $_SESSION['tahun_ajaran'] ?? '2024/2025 Ganjil';
$user = currentUser();

// --- Proses Tambah Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hasil']) && $user['role'] === 'admin') {
    $tanggal = trim($_POST['tanggal'] ?? '');
    $siswaId = (int) ($_POST['siswa_id'] ?? 0);
    $guruId = (int) ($_POST['guru_id'] ?? 0);
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid = trim($_POST['jilid'] ?? '');
    $nilai = trim($_POST['nilai'] ?? '');
    $naikKeJilid = trim($_POST['naik_ke_jilid'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($siswaId <= 0 || $guruId <= 0 || $tanggal === '' || $nilai === '') {
        flash('error', 'Lengkapi data hasil tes dengan benar.');
        header('Location: ' . BASE_URL . 'hasil_tes.php');
        exit;
    }

    $stmt = db()->prepare('INSERT INTO hasil_tes (tanggal, siswa_id, guru_id, kelas, jilid, nilai, tahun_ajaran, naik_ke_jilid, keterangan, created_at) VALUES (:tanggal, :siswa_id, :guru_id, :kelas, :jilid, :nilai, :tahun_ajaran, :naik_ke_jilid, :keterangan, NOW())');
    $stmt->execute([
        'tanggal' => $tanggal,
        'siswa_id' => $siswaId,
        'guru_id' => $guruId,
        'kelas' => $kelas,
        'jilid' => $jilid,
        'nilai' => $nilai,
        'tahun_ajaran' => $active_ta,
        'naik_ke_jilid' => $naikKeJilid,
        'keterangan' => $keterangan,
    ]);

    flash('success', 'Hasil tes berhasil disimpan.');
    header('Location: ' . BASE_URL . 'hasil_tes.php');
    exit;
}

// --- Proses Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hasil']) && $user['role'] === 'admin') {
    $id = (int)($_POST['id'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $siswaId = (int) ($_POST['siswa_id'] ?? 0);
    $guruId = (int) ($_POST['guru_id'] ?? 0);
    $kelas = trim($_POST['kelas'] ?? '');
    $jilid = trim($_POST['jilid'] ?? '');
    $nilai = trim($_POST['nilai'] ?? '');
    $naikKeJilid = trim($_POST['naik_ke_jilid'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($id <= 0 || $siswaId <= 0 || $guruId <= 0 || $tanggal === '' || $nilai === '') {
        flash('error', 'Lengkapi data hasil tes dengan benar.');
        header('Location: ' . BASE_URL . 'hasil_tes.php');
        exit;
    }

    $stmt = db()->prepare('UPDATE hasil_tes SET tanggal = :tanggal, siswa_id = :siswa_id, guru_id = :guru_id, kelas = :kelas, jilid = :jilid, nilai = :nilai, naik_ke_jilid = :naik_ke_jilid, keterangan = :keterangan WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'tanggal' => $tanggal,
        'siswa_id' => $siswaId,
        'guru_id' => $guruId,
        'kelas' => $kelas,
        'jilid' => $jilid,
        'nilai' => $nilai,
        'naik_ke_jilid' => $naikKeJilid,
        'keterangan' => $keterangan,
    ]);

    flash('success', 'Hasil tes berhasil diperbarui.');
    header('Location: ' . BASE_URL . 'hasil_tes.php');
    exit;
}

// --- Proses Hapus Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $user['role'] === 'admin') {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = db()->prepare('DELETE FROM hasil_tes WHERE id = :id');
    $stmt->execute(['id' => $deleteId]);
    flash('success', 'Data hasil tes berhasil dihapus.');
    header('Location: ' . BASE_URL . 'hasil_tes.php');
    exit;
}

// Parameter Pagination & Pencarian
$search = trim($_GET['q'] ?? '');
$show_entries = isset($_GET['show_entries']) ? max(1, (int)$_GET['show_entries']) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$queryCondition = 'WHERE h.tahun_ajaran = :ta AND (s.nama_siswa LIKE :keyword1 OR u.nama LIKE :keyword2)';

$countStmt = db()->prepare("SELECT COUNT(*) FROM hasil_tes h JOIN siswa s ON h.siswa_id = s.id JOIN users u ON h.guru_id = u.id $queryCondition");
$countStmt->bindValue(':ta', $active_ta, PDO::PARAM_STR);
$countStmt->bindValue(':keyword1', "%$search%", PDO::PARAM_STR);
$countStmt->bindValue(':keyword2', "%$search%", PDO::PARAM_STR);
$countStmt->execute();
$total_records = (int) $countStmt->fetchColumn();

$total_pages = ceil($total_records / $show_entries);
$page = max(1, min($page, $total_pages > 0 ? $total_pages : 1));
$offset = ($page - 1) * $show_entries;

// Fetch Data Tes
$stmt = db()->prepare("
    SELECT h.*, s.nama_siswa, u.nama AS nama_guru 
    FROM hasil_tes h 
    JOIN siswa s ON h.siswa_id = s.id 
    JOIN users u ON h.guru_id = u.id 
    $queryCondition 
    ORDER BY h.tanggal DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':ta', $active_ta, PDO::PARAM_STR);
$stmt->bindValue(':keyword1', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':keyword2', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $show_entries, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll();

$students = Siswa::all();
$teachers = db()->query("SELECT id, nama FROM users WHERE role='guru'")->fetchAll();

$pageTitle = 'Data Tes';
require_once __DIR__ . '/../app/views/layout/header.php';
?>
<section class="content-section guru-page-content">

    <!-- Title Section (Garis horizontal memanjang menyesuaikan panjang tabel) -->
    <div style="margin-top: 32px; margin-bottom: 24px;">
        <h2
            style="margin: 0 0 16px 0; font-family: 'Jomolhari', serif; font-size: 36px; display: flex; align-items: center; gap: 12px; color: #000;">
            <img src="<?= BASE_URL ?>../assets/images/people.png" alt="Icon"
                style="width: 64px; height: 64px; object-fit: contain;"> Data Tes
        </h2>
        <div style="width: 100%; height: 4px; background-color: rgba(13, 193, 199, 0.97); border-radius: 2px;"></div>
    </div>

    <!-- Data Card (Margin atas dinetralkan agar sejajar manis dengan title) -->
    <div class="guru-card" style="margin-top: 0;">
        <!-- TOOLBAR -->
        <div class="guru-toolbar">
            <?php if ($user['role'] === 'admin'): ?>
                <button type="button" class="btn-tambah-guru" onclick="openModal()">
                    <span>+</span> Tambah Data
                </button>
            <?php endif; ?>
            <div class="guru-search-wrapper">
                <form method="GET" action="<?= BASE_URL ?>hasil_tes.php"
                    style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
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

        <!-- ENTRIES SELECTOR -->
        <div class="guru-entries">
            <form method="GET" action="<?= BASE_URL ?>hasil_tes.php"
                style="display: flex; align-items: center; gap: 8px;">
                <span>Show</span>
                <select name="show_entries" onchange="this.form.submit()">
                    <option value="10" <?= $show_entries == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $show_entries == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $show_entries == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $show_entries == 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span>entries</span>
                <input type="hidden" name="q" value="<?= escape_html($search) ?>">
                <input type="hidden" name="page" value="1">
            </form>
        </div>

        <!-- TABLE -->
        <div class="guru-table-wrapper">
            <table class="guru-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Siswa</th>
                        <th>Guru Pengetes</th>
                        <th>Kelas</th>
                        <th>Jilid</th>
                        <th>Nilai</th>
                        <th>Naik Ke Jilid</th>
                        <th>Keterangan</th>
                        <?php if ($user['role'] === 'admin'): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php foreach ($results as $index => $result): ?>
                            <tr>
                                <td data-label="No"><?= $offset + $index + 1 ?></td>
                                <td data-label="Tanggal"><?= escape_html($result['tanggal']) ?></td>
                                <td data-label="Nama Siswa"><?= escape_html($result['nama_siswa']) ?></td>
                                <td data-label="Guru Pengetes"><?= escape_html($result['nama_guru']) ?></td>
                                <td data-label="Kelas"><?= escape_html($result['kelas']) ?></td>
                                <td data-label="Jilid"><?= escape_html($result['jilid']) ?></td>
                                <td data-label="Nilai">
                                    <?php if ($result['nilai'] === 'Lulus'): ?>
                                        <span
                                            style="background-color: #dcfce7; color: #16a34a; padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 13px;">Lulus</span>
                                    <?php else: ?>
                                        <span
                                            style="background-color: #fee2e2; color: #dc2626; padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 13px;"><?= escape_html($result['nilai']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Naik Ke Jilid"><?= escape_html($result['naik_ke_jilid'] ?? '-') ?></td>
                                <td data-label="Keterangan"><?= escape_html($result['keterangan'] ?? '-') ?></td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <td data-label="Aksi">
                                        <div class="action-buttons-claude">
                                            <a href="#" class="btn-edit-claude" title="Edit Data"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($result), ENT_QUOTES, 'UTF-8') ?>); return false;">
                                                <img src="<?= BASE_URL ?>../assets/images/pencil%20edit.png" alt="Edit"
                                                    onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\'></path><path d=\'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\'></path></svg>'">
                                            </a>
                                            <form method="post" action="<?= BASE_URL ?>hasil_tes.php"
                                                style="display:flex; margin:0;" onsubmit="return confirm('Hapus data ini?')">
                                                <input type="hidden" name="delete_id" value="<?= $result['id'] ?>">
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
                            <td colspan="<?= $user['role'] === 'admin' ? '10' : '9' ?>"
                                style="text-align: center; padding: 32px; color: #999;">Tidak ada data yang
                                ditemukan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER INFO -->
        <div class="guru-footer-info">
            Menampilkan <?= count($results) > 0 ? ($offset + 1) : 0 ?> sampai
            <?= $offset + count($results) ?> dari
            <?= $total_records ?> entries
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="guru-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>">«
                        Pertama</a>
                    <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>">‹
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
                        <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor;

                if ($end_page < $total_pages): ?>
                    <span>...</span>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>">Selanjutnya
                        ›</a>
                    <a href="?page=<?= $total_pages ?>&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>">Terakhir
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
            <h2>Tambah Hasil Tes</h2>
            <button type="button" class="modal-close" onclick="closeModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>hasil_tes.php" id="formTambahData">
            <input type="hidden" name="add_hasil" value="1">

            <div class="modal-form-group">
                <label for="tanggal">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" required>
            </div>

            <div class="modal-form-group">
                <label for="siswa_id">Siswa</label>
                <select id="siswa_id" name="siswa_id" required>
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>"><?= escape_html($student['nama_siswa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="guru_id">Guru Pengetes</label>
                <select id="guru_id" name="guru_id" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>"><?= escape_html($teacher['nama']) ?></option>
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
                    <option value="91">91</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="jilid">Jilid yang Dites</label>
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
                <label for="nilai">Nilai</label>
                <select id="nilai" name="nilai" required>
                    <option value="">-- Pilih Nilai --</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Tidak Lulus">Tidak Lulus</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="naik_ke_jilid">Naik ke Jilid</label>
                <select id="naik_ke_jilid" name="naik_ke_jilid">
                    <option value="">- Tidak Naik -</option>
                    <option value="Buku 1">Buku 1</option>
                    <option value="Buku 2">Buku 2</option>
                    <option value="Buku 3">Buku 3</option>
                    <option value="Al-Qur'an">Al-Qur'an</option>
                    <option value="Gharib">Gharib</option>
                    <option value="Tajwid">Tajwid</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="keterangan">Keterangan</label>
                <input type="text" id="keterangan" name="keterangan" placeholder="Masukan Keterangan">
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
            <h2>Edit Hasil Tes</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>hasil_tes.php" id="formEditData">
            <input type="hidden" name="edit_hasil" value="1">
            <input type="hidden" name="id" id="edit_id" value="">

            <div class="modal-form-group">
                <label for="edit_tanggal">Tanggal</label>
                <input type="date" id="edit_tanggal" name="tanggal" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_siswa_id">Siswa</label>
                <select id="edit_siswa_id" name="siswa_id" required>
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>"><?= escape_html($student['nama_siswa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_guru_id">Guru Pengetes</label>
                <select id="edit_guru_id" name="guru_id" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>"><?= escape_html($teacher['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
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
                    <option value="91">91</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_jilid">Jilid yang Dites</label>
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
                <label for="edit_nilai">Nilai</label>
                <select id="edit_nilai" name="nilai" required>
                    <option value="">-- Pilih Nilai --</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Tidak Lulus">Tidak Lulus</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_naik_ke_jilid">Naik ke Jilid</label>
                <select id="edit_naik_ke_jilid" name="naik_ke_jilid">
                    <option value="">- Tidak Naik -</option>
                    <option value="Buku 1">Buku 1</option>
                    <option value="Buku 2">Buku 2</option>
                    <option value="Buku 3">Buku 3</option>
                    <option value="Al-Qur'an">Al-Qur'an</option>
                    <option value="Gharib">Gharib</option>
                    <option value="Tajwid">Tajwid</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_keterangan">Keterangan</label>
                <input type="text" id="edit_keterangan" name="keterangan" placeholder="Masukan Keterangan">
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

    function openEditModal(hasilData) {
        document.getElementById('edit_id').value = hasilData.id || '';
        document.getElementById('edit_tanggal').value = hasilData.tanggal || '';
        document.getElementById('edit_siswa_id').value = hasilData.siswa_id || '';
        document.getElementById('edit_guru_id').value = hasilData.guru_id || '';
        document.getElementById('edit_kelas').value = hasilData.kelas || '';
        document.getElementById('edit_jilid').value = hasilData.jilid || '';
        document.getElementById('edit_nilai').value = hasilData.nilai || '';
        document.getElementById('edit_naik_ke_jilid').value = hasilData.naik_ke_jilid || '';
        document.getElementById('edit_keterangan').value = hasilData.keterangan || '';

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
<?php require_once __DIR__ . '/../app/views/layout/footer.php'; ?>