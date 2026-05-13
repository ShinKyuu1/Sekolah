<?php

class Siswa
{
    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM siswa ORDER BY nama_siswa ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM siswa WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
    }
}
