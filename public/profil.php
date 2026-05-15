<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/validation.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $user = currentUser();
    $id = $user['id'];
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $username === '') {
        flash('error', 'Nama dan Username tidak boleh kosong.');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Cek apakah username sudah dipakai orang lain
    $stmt = db()->prepare('SELECT id FROM users WHERE username = :username AND id != :id');
    $stmt->execute(['username' => $username, 'id' => $id]);
    if ($stmt->fetch()) {
        flash('error', 'Username sudah digunakan oleh pengguna lain.');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $updateQuery = 'UPDATE users SET nama = :nama, username = :username';
    $params = ['nama' => $nama, 'username' => $username, 'id' => $id];

    if (!empty($password)) {
        $updateQuery .= ', password_hash = :password_hash';
        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $fileType = mime_content_type($file['tmp_name']);
        if (in_array($fileType, ['image/jpeg', 'image/png'], true)) {
            $filename = time() . '_avatar_' . $id . '.jpg';
            $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $updateQuery .= ', avatar = :avatar';
                $params['avatar'] = 'uploads/' . $filename;
            }
        } else {
            flash('error', 'Format poto profil harus JPG atau PNG.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    $updateQuery .= ' WHERE id = :id';
    $stmt = db()->prepare($updateQuery);
    $stmt->execute($params);

    // Update session profil terbaru
    $_SESSION['user'] = array_merge($_SESSION['user'], ['nama' => $nama, 'username' => $username]);
    if (isset($params['avatar'])) $_SESSION['user']['avatar'] = $params['avatar'];

    flash('success', 'Profil berhasil diperbarui.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

header('Location: ' . BASE_URL . 'dashboard.php');
exit;
