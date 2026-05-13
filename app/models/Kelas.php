<?php

class Kelas
{
    public static function all(): array
    {
        $stmt = db()->query('SELECT k.*, s.nama_siswa, u.nama AS nama_guru FROM kelas k JOIN siswa s ON k.siswa_id = s.id JOIN users u ON k.guru_id = u.id ORDER BY s.nama_siswa ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM kelas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM kelas')->fetchColumn();
    }
}
