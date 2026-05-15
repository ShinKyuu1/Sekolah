<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/User.php';

function isLoggedIn(): bool
{
    return !empty($_SESSION['user']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // LOGIKA SESSION TIMEOUT (30 Menit = 1800 Detik)
    $timeout_duration = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        logoutUser();
        flash('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 30 menit. Silakan login kembali.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // Perbarui waktu aktivitas terakhir pengguna setiap kali mereka memuat halaman
    $_SESSION['last_activity'] = time();
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'nama' => $user['nama'],
        'role' => $user['role'],
    ];

    // Catat waktu pertama kali login
    $_SESSION['last_activity'] = time();
}

function logoutUser(): void
{
    $_SESSION = [];

    if (session_id() !== '') {
        session_destroy();
    }
}

// --- FUNGSI KEAMANAN CSRF TOKEN ---
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function authenticate(string $username, string $password): ?array
{
    $user = User::findByUsername($username);

    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }

    return null;
}
