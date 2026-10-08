<?php

namespace App\Models;

use CodeIgniter\Model;

class DoorprizeWinnerModel extends Model
{
    protected $table         = 'tb_doorprize_winners';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_rat', 'id_item', 'nip', 'nama', 'departemen', 'section', 'waktu_undi'];
    protected $useTimestamps = false;

    /**
     * Riwayat pemenang + dept/section "live" (fallback ke peserta/anggota).
     * Join peserta memakai subquery 1 baris per NIP, karena peserta
     * sekarang tercatat per sesi (NIP yang sama bisa ada di beberapa sesi).
     */
    public function getByRat(int $idRat): array
    {
        return $this->db->query('
            SELECT w.*, i.nama_barang, i.kategori,
                   COALESCE(NULLIF(w.departemen, ""), p.departemen, a.departemen, "-") AS dept_live,
                   COALESCE(NULLIF(w.section, ""),    p.section,    a.section,    "-") AS section_live
            FROM tb_doorprize_winners w
            JOIN tb_doorprize_items i ON i.id = w.id_item
            LEFT JOIN (
                SELECT nip, MAX(NULLIF(departemen, "")) AS departemen, MAX(NULLIF(section, "")) AS section
                FROM tb_peserta_rat WHERE id_rat = ? GROUP BY nip
            ) p ON p.nip = w.nip
            LEFT JOIN tb_anggota a ON a.nip = w.nip
            WHERE w.id_rat = ?
            ORDER BY w.waktu_undi DESC
        ', [$idRat, $idRat])->getResultArray();
    }

    /**
     * Daftar NIP yang sudah menang di RAT ini, sebagai set [nip => true].
     */
    public function nipPemenang(int $idRat): array
    {
        $rows = $this->select('nip')->where('id_rat', $idRat)->findAll();

        return array_fill_keys(array_column($rows, 'nip'), true);
    }
}
