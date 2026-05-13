<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';
requireLogin();

// Pastikan hanya admin yang bisa mengakses halaman Data Guru
$user = currentUser();
if (!isset($user['role']) || $user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_guru'])) {
    $id_guru = trim($_POST['id_guru'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $pendidikan = trim($_POST['pendidikan'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $noHp = trim($_POST['noHp'] ?? '');

    if ($nama === '') {
        flash('error', 'Nama Guru harus diisi.');
        header('Location: ' . BASE_URL . 'guru.php');
        exit;
    }

    // Otomatis buat username dan password (karena tabel users mewajibkannya)
    $username = $email !== '' ? explode('@', $email)[0] . rand(10, 99) : strtolower(str_replace(' ', '', $nama)) . rand(10, 99);
    $password = 'qiroati123'; // Password default untuk login guru

    $exists = db()->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
    $exists->execute(['username' => $username]);

    if ($exists->fetchColumn() > 0) {
        $username = $username . rand(100, 999); // Pastikan unik
    }

    $stmt = db()->prepare('INSERT INTO users (id_guru, username, password_hash, nama, jenis_kelamin, jabatan, pendidikan, email, no_hp, role, created_at) VALUES (:id_guru, :username, :password_hash, :nama, :jenis_kelamin, :jabatan, :pendidikan, :email, :no_hp, :role, NOW())');
    $stmt->execute([
        'id_guru' => $id_guru,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'nama' => $nama,
        'jenis_kelamin' => $jenis_kelamin,
        'jabatan' => $jabatan,
        'pendidikan' => $pendidikan,
        'email' => $email,
        'no_hp' => $noHp,
        'role' => 'guru',
    ]);

    flash('success', 'Data guru berhasil ditambahkan.');
    header('Location: ' . BASE_URL . 'guru.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_guru'])) {
    $id = (int)($_POST['id'] ?? 0);
    $id_guru = trim($_POST['id_guru'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $pendidikan = trim($_POST['pendidikan'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $noHp = trim($_POST['noHp'] ?? '');

    if ($id <= 0 || $nama === '') {
        flash('error', 'Nama Guru harus diisi.');
        header('Location: ' . BASE_URL . 'guru.php');
        exit;
    }

    $stmt = db()->prepare('UPDATE users SET id_guru = :id_guru, nama = :nama, jenis_kelamin = :jenis_kelamin, jabatan = :jabatan, pendidikan = :pendidikan, email = :email, no_hp = :no_hp WHERE id = :id AND role = "guru"');
    $stmt->execute([
        'id' => $id,
        'id_guru' => $id_guru,
        'nama' => $nama,
        'jenis_kelamin' => $jenis_kelamin,
        'jabatan' => $jabatan,
        'pendidikan' => $pendidikan,
        'email' => $email,
        'no_hp' => $noHp,
    ]);

    flash('success', 'Data guru berhasil diperbarui.');
    header('Location: ' . BASE_URL . 'guru.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    $stmt = db()->prepare('DELETE FROM users WHERE id = :id AND role = "guru"');
    $stmt->execute(['id' => $deleteId]);
    flash('success', 'Data guru berhasil dihapus.');
    header('Location: ' . BASE_URL . 'guru.php');
    exit;
}

$totalGuru = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "guru"')->fetchColumn();

// Ambil parameter dari GET
$search = trim($_GET['q'] ?? '');
$show_entries = isset($_GET['show_entries']) ? max(1, (int)$_GET['show_entries']) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Hitung total records untuk pagination
$countStmt = db()->prepare('SELECT COUNT(*) FROM users WHERE role = "guru" AND (username LIKE :keyword1 OR nama LIKE :keyword2)');
$countStmt->execute(['keyword1' => "%$search%", 'keyword2' => "%$search%"]);
$total_records = (int) $countStmt->fetchColumn();

// Hitung Pagination
$total_pages = ceil($total_records / $show_entries);
$page = max(1, min($page, $total_pages > 0 ? $total_pages : 1));
$offset = ($page - 1) * $show_entries;

// Ambil data dengan LIMIT dan OFFSET
$stmt = db()->prepare('SELECT id, id_guru, nama, jenis_kelamin, jabatan, pendidikan, email, no_hp, username FROM users WHERE role = "guru" AND (username LIKE :keyword1 OR nama LIKE :keyword2) ORDER BY id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':keyword1', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':keyword2', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $show_entries, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$teachers = $stmt->fetchAll();

$pageTitle = 'Data Guru';
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
                <form method="GET" action="<?= BASE_URL ?>guru.php"
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
            <form method="GET" action="<?= BASE_URL ?>guru.php" style="display: flex; align-items: center; gap: 8px;">
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
                        <th>Nama Guru</th>
                        <th>JK</th>
                        <th>Jabatan</th>
                        <th>Pendidikan</th>
                        <th>E-Mail</th>
                        <th>No HP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($teachers) > 0): ?>
                        <?php foreach ($teachers as $index => $teacher): ?>
                            <tr>
                                <td data-label="No"><?= $offset + $index + 1 ?></td>
                                <td data-label="Nama Guru"><?= escape_html($teacher['nama']) ?></td>
                                <td data-label="JK"><?= escape_html($teacher['jenis_kelamin'] === 'Laki-laki' ? 'L' : ($teacher['jenis_kelamin'] === 'Perempuan' ? 'P' : '-')) ?>
                                </td>
                                <td data-label="Jabatan"><?= escape_html($teacher['jabatan'] ?? '-') ?></td>
                                <td data-label="Pendidikan"><?= escape_html($teacher['pendidikan'] ?? '-') ?></td>
                                <td data-label="E-Mail"><?= escape_html($teacher['email'] ?? '-') ?></td>
                                <td data-label="No HP"><?= escape_html($teacher['no_hp'] ?? '-') ?></td>
                                <td data-label="Aksi">
                                    <div class="action-buttons-claude">
                                        <a href="#" class="btn-edit-claude" title="Edit Data"
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($teacher), ENT_QUOTES, 'UTF-8') ?>); return false;">
                                            <img src="<?= BASE_URL ?>../assets/images/pencil%20edit.png" alt="Edit"
                                                onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\'></path><path d=\'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\'></path></svg>'">
                                        </a>
                                        <form method="post" action="<?= BASE_URL ?>guru.php" style="display:flex; margin:0;"
                                            onsubmit="return confirm('Hapus guru ini?')">
                                            <input type="hidden" name="delete_id" value="<?= $teacher['id'] ?>">
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
                            <td colspan="8" style="text-align: center; padding: 32px; color: #999;">Tidak ada data yang
                                ditemukan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER INFO -->
        <div class="guru-footer-info">
            Menampilkan <?= count($teachers) > 0 ? ($offset + 1) : 0 ?> sampai <?= $offset + count($teachers) ?> dari
            <?= $total_records ?> entries
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="guru-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&q=<?= urlencode($search) ?>&show_entries=<?= $show_entries ?>">« Pertama</a>
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
            <h2>Tambah Data Guru</h2>
            <button type="button" class="modal-close" onclick="closeModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>guru.php" id="formTambahData">
            <input type="hidden" name="add_guru" value="1">

            <div class="modal-form-group">
                <label for="id_guru">Nomor Data Guru</label>
                <input type="text" id="id_guru" name="id_guru" placeholder="Masukan ID">
            </div>

            <div class="modal-form-group">
                <label for="nama">Masukan Nama Guru</label>
                <input type="text" id="nama" name="nama" placeholder="Masukan Nama Guru" required>
            </div>

            <div class="modal-form-group">
                <label for="jenis_kelamin">JK</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="jabatan">Jabatan</label>
                <input type="text" id="jabatan" name="jabatan" placeholder="Masukan Jabatan" required>
            </div>

            <div class="modal-form-group">
                <label for="pendidikan">Pendidikan</label>
                <select id="pendidikan" name="pendidikan" required>
                    <option value="">-- Pilih Jenjang Sekolah --</option>
                    <option value="SD">SD</option>
                    <option value="SMP">SMP</option>
                    <option value="SMA">SMA</option>
                    <option value="D3">D3</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email" placeholder="Masukan Nama E-Mail" required>
            </div>

            <div class="modal-form-group">
                <label for="noHp">No Hp</label>
                <input type="tel" id="noHp" name="noHp" placeholder="Masukan No Hp yang aktif" required>
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
            <h2>Edit Data Guru</h2>
            <button type="button" class="modal-close" onclick="closeEditModal()">✕</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>guru.php" id="formEditData">
            <input type="hidden" name="edit_guru" value="1">
            <input type="hidden" name="id" id="edit_id" value="">

            <div class="modal-form-group">
                <label for="edit_id_guru">Nomor Data Guru</label>
                <input type="text" id="edit_id_guru" name="id_guru" placeholder="Masukan ID">
            </div>

            <div class="modal-form-group">
                <label for="edit_nama">Masukan Nama Guru</label>
                <input type="text" id="edit_nama" name="nama" placeholder="Masukan Nama Guru" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_jenis_kelamin">JK</label>
                <select id="edit_jenis_kelamin" name="jenis_kelamin" required>
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_jabatan">Jabatan</label>
                <input type="text" id="edit_jabatan" name="jabatan" placeholder="Masukan Jabatan" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_pendidikan">Pendidikan</label>
                <select id="edit_pendidikan" name="pendidikan" required>
                    <option value="">-- Pilih Jenjang Sekolah --</option>
                    <option value="SD">SD</option>
                    <option value="SMP">SMP</option>
                    <option value="SMA">SMA</option>
                    <option value="D3">D3</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit_email">E-Mail</label>
                <input type="email" id="edit_email" name="email" placeholder="Masukan Nama E-Mail" required>
            </div>

            <div class="modal-form-group">
                <label for="edit_noHp">No Hp</label>
                <input type="tel" id="edit_noHp" name="noHp" placeholder="Masukan No Hp yang aktif" required>
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

    function openEditModal(guru) {
        document.getElementById('edit_id').value = guru.id || '';
        document.getElementById('edit_id_guru').value = guru.id_guru || '';
        document.getElementById('edit_nama').value = guru.nama || '';
        document.getElementById('edit_jenis_kelamin').value = guru.jenis_kelamin || '';
        document.getElementById('edit_jabatan').value = guru.jabatan || '';
        document.getElementById('edit_pendidikan').value = guru.pendidikan || '';
        document.getElementById('edit_email').value = guru.email || '';
        document.getElementById('edit_noHp').value = guru.no_hp || '';

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
