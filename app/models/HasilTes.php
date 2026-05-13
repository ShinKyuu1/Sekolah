<?php

class HasilTes
{
    public static function all(): array
    {
        $stmt = db()->query('SELECT h.*, s.nama_siswa, u.nama AS nama_guru FROM hasil_tes h JOIN siswa s ON h.siswa_id = s.id JOIN users u ON h.guru_id = u.id ORDER BY h.tanggal DESC');
        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM hasil_tes')->fetchColumn();
    }
}