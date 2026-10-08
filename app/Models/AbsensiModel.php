<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsensiModel extends Model
{
    protected $table         = 'tb_absensi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_sesi', 'id_anggota', 'nama_snapshot', 'nip_snapshot', 'waktu_absen', 'metode', 'foto_path'];
    protected $useTimestamps = false;

    public function sudahAbsenDiSesi(int $idSesi, int $idAnggota): bool
    {
        return $this->where('id_sesi', $idSesi)
            ->where('id_anggota', $idAnggota)
            ->countAllResults() > 0;
    }

    public function countBySesi(int $idSesi): int
    {
        return $this->where('id_sesi', $idSesi)->countAllResults();
    }

    /**
     * Jumlah hadir per sesi dalam satu query: [id_sesi => jumlah].
     */
    public function hitungPerSesi(array $idSesiList): array
    {
        if ($idSesiList === []) {
            return [];
        }

        $rows = $this->db->table($this->table)
            ->select('id_sesi, COUNT(*) AS n')
            ->whereIn('id_sesi', $idSesiList)
            ->groupBy('id_sesi')
            ->get()->getResultArray();

        return array_map('intval', array_column($rows, 'n', 'id_sesi'));
    }

    /**
     * Detail absensi satu sesi untuk rekap Excel/cetak.
     * Pakai snapshot supaya rekap tetap akurat walau anggota dihapus.
     */
    public function getDetailBySesi(int $idSesi): array
    {
        return $this->db->query(
            'SELECT ab.waktu_absen, ab.metode,
                    COALESCE(a.nip, ab.nip_snapshot, "-")          AS nip,
                    COALESCE(a.nama, ab.nama_snapshot, "(dihapus)") AS nama,
                    COALESCE(NULLIF(p.departemen, ""), a.departemen) AS departemen,
                    COALESCE(NULLIF(p.section, ""), a.section)       AS section,
                    COALESCE(NULLIF(p.shift, ""), a.shift)           AS shift
             FROM tb_absensi ab
             LEFT JOIN tb_anggota a     ON a.id = ab.id_anggota
             LEFT JOIN tb_peserta_rat p ON p.nip = ab.nip_snapshot AND p.id_sesi = ab.id_sesi
             WHERE ab.id_sesi = ?
             ORDER BY ab.waktu_absen ASC',
            [$idSesi]
        )->getResultArray();
    }

    /**
     * Summary kehadiran per peserta RAT (untuk sheet Summary & doorprize).
     *
     * - Daftar peserta diambil UNIK per NIP dari tb_peserta_rat
     *   (satu NIP bisa terdaftar di beberapa sesi).
     * - Eligible doorprize = hadir di SELURUH sesi RAT tersebut.
     *   Tidak hadir di satu sesi saja → tidak eligible.
     */
    public function getSummaryPerAnggota(array $idSesiList, int $idRat): array
    {
        if ($idSesiList === []) {
            return [];
        }

        $daftarPeserta = (new PesertaRatModel())->getUnikByRat($idRat);

        // Index kehadiran: [nip][id_sesi] = true (satu query)
        $hadirIdx = [];
        $rows = $this->db->table('tb_absensi ab')
            ->select('ab.id_sesi, COALESCE(a.nip, ab.nip_snapshot) AS nip')
            ->join('tb_anggota a', 'a.id = ab.id_anggota', 'left')
            ->whereIn('ab.id_sesi', $idSesiList)
            ->get()->getResultArray();
        foreach ($rows as $r) {
            if ($r['nip'] !== null && $r['nip'] !== '') {
                $hadirIdx[$r['nip']][(int) $r['id_sesi']] = true;
            }
        }

        $hasil = [];
        foreach ($daftarPeserta as $p) {
            $baris = [
                'nip'        => $p['nip'],
                'nama'       => $p['nama'],
                'departemen' => $p['departemen'] ?? null,
                'section'    => $p['section'] ?? null,
                'shift'      => $p['shift'] ?? null,
            ];
            foreach ($idSesiList as $idSesi) {
                $baris['hadir_' . $idSesi] = isset($hadirIdx[$p['nip']][(int) $idSesi]);
            }

            $baris['eligible_doorprize'] = ! in_array(false, array_map(
                static fn ($idSesi) => $baris['hadir_' . $idSesi], $idSesiList
            ), true);
            $hasil[] = $baris;
        }

        return $hasil;
    }
}
