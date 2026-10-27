<?php

namespace App\Models;

use CodeIgniter\Model;

class DoorprizeWinnerModel extends Model
{
    protected $table         = 'tb_doorprize_winners';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_rat', 'id_item', 'nip', 'nama', 'departemen', 'section', 'waktu_undi', 'hangus'];
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
            LEFT JOIN tb_doorprize_items i ON i.id = w.id_item
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
     * Status undian per NIP di RAT ini: [nip => 'menang'|'hangus'].
     * Keduanya sama-sama tidak ikut undian lagi.
     */
    public function statusPerNip(int $idRat): array
    {
        $hasil = [];
        foreach ($this->select('nip, hangus')->where('id_rat', $idRat)->findAll() as $r) {
            $hasil[$r['nip']] = $r['hangus'] ? 'hangus' : 'menang';
        }

        return $hasil;
    }

    /**
     * Unit barang yang sudah terpakai (pemenang yang hangus tidak dihitung, unitnya diundi ulang).
     */
    public function jumlahUnitTerpakai(int $idItem): int
    {
        return $this->where('id_item', $idItem)->where('hangus', 0)->countAllResults();
    }
}
