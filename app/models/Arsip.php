<?php

class Arsip
{
    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM arsip ORDER BY tanggal DESC');
        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM arsip')->fetchColumn();
    }
}
