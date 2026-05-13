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
}

function logoutUser(): void
{
    $_SESSION = [];

    if (session_id() !== '') {
        session_destroy();
    }
}

function authenticate(string $username, string $password): ?array
{
    $user = User::findByUsername($username);

    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }

    return null;
}
