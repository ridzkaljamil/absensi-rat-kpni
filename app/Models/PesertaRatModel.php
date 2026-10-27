<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Peserta RAT — dicatat PER SESI (kolom id_sesi).
 * Satu NIP boleh muncul di beberapa sesi dalam RAT yang sama
 * (unique key: id_sesi + nip).
 */
class PesertaRatModel extends Model
{
    protected $table         = 'tb_peserta_rat';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_rat', 'id_sesi', 'nip', 'nama', 'departemen', 'section', 'shift', 'created_at',
    ];
    protected $useTimestamps = false;

    // ─────────────────────────────────────────────
    //  Query
    // ─────────────────────────────────────────────

    /**
     * Peserta satu sesi + kartu RFID-nya (dari data induk).
     */
    public function getBySesi(int $idSesi): array
    {
        return $this->db->query(
            'SELECT p.*, a.uid_rfid FROM tb_peserta_rat p
             LEFT JOIN tb_anggota a ON a.nip = p.nip
             WHERE p.id_sesi = ? ORDER BY p.nama ASC',
            [$idSesi]
        )->getResultArray();
    }

    /**
     * Jumlah peserta satu sesi yang sudah punya kartu RFID.
     */
    public function jumlahBerkartu(int $idSesi): int
    {
        return (int) ($this->db->query(
            'SELECT COUNT(*) n FROM tb_peserta_rat p JOIN tb_anggota a ON a.nip = p.nip
             WHERE p.id_sesi = ? AND a.uid_rfid IS NOT NULL AND a.uid_rfid <> ""',
            [$idSesi]
        )->getRowArray()['n'] ?? 0);
    }

    /**
     * Daftar peserta unik (per NIP) di seluruh sesi satu RAT.
     * Dipakai untuk export "semua sesi" dan doorprize.
     */
    public function getUnikByRat(int $idRat): array
    {
        return $this->db->query(
            'SELECT p.*, a.uid_rfid FROM tb_peserta_rat p
             JOIN (SELECT MIN(id) AS id FROM tb_peserta_rat WHERE id_rat = ? GROUP BY nip) u ON u.id = p.id
             LEFT JOIN tb_anggota a ON a.nip = p.nip
             ORDER BY p.nama ASC',
            [$idRat]
        )->getResultArray();
    }

    public function nipTerdaftarSesi(int $idSesi, string $nip): ?array
    {
        return $this->where('id_sesi', $idSesi)->where('nip', $nip)->first();
    }




    public function totalPesertaSesi(int $idSesi): int
    {
        return $this->where('id_sesi', $idSesi)->countAllResults();
    }

    /**
     * Total peserta UNIK satu RAT (NIP yang sama di beberapa sesi dihitung 1).
     */
    public function totalPeserta(int $idRat): int
    {
        $row = $this->db->query(
            'SELECT COUNT(DISTINCT nip) AS n FROM tb_peserta_rat WHERE id_rat = ?',
            [$idRat]
        )->getRowArray();

        return (int) ($row['n'] ?? 0);
    }

    /**
     * Jumlah peserta per sesi dalam satu query: [id_sesi => jumlah].
     */
    public function hitungPerSesi(int $idRat): array
    {
        $rows = $this->db->query(
            'SELECT id_sesi, COUNT(*) AS n FROM tb_peserta_rat WHERE id_rat = ? GROUP BY id_sesi',
            [$idRat]
        )->getResultArray();

        return array_column($rows, 'n', 'id_sesi');
    }

    // ─────────────────────────────────────────────
    //  Import & salin
    // ─────────────────────────────────────────────

    /**
     * Import dari array hasil baca Excel ke satu sesi.
     * Data lama di-preload sekali; NIP yang muncul dua kali di file cukup diambil baris terakhirnya.
     * Kartu RFID TIDAK ditulis di sini — dipasang lewat App\Libraries\KartuRfid supaya dicek bentrok.
     */
    public function importBulkSesi(int $idRat, int $idSesi, array $rows): array
    {
        $now      = date('Y-m-d H:i:s');
        $existing = array_column($this->select('id, nip')->where('id_sesi', $idSesi)->findAll(), 'id', 'nip');

        $baru = [];
        $ubah = [];
        $skipped = 0;
        foreach ($rows as $row) {
            $nip  = (string) ($row['nip'] ?? '');
            $nama = trim((string) ($row['nama'] ?? ''));
            if ($nip === '' || $nama === '') {
                $skipped++;
                continue;
            }

            $data = [
                'nama'       => mb_substr($nama, 0, 100),
                'departemen' => trim((string) ($row['departemen'] ?? '')) ?: null,
                'section'    => trim((string) ($row['section'] ?? '')) ?: null,
                'shift'      => trim((string) ($row['shift'] ?? '')) ?: null,
            ];

            if (isset($existing[$nip])) {
                $ubah[$existing[$nip]] = $data + ['id' => $existing[$nip]];
            } else {
                $baru[$nip] = $data + ['id_rat' => $idRat, 'id_sesi' => $idSesi, 'nip' => $nip, 'created_at' => $now];
            }
        }

        if ($baru !== []) {
            $this->insertBatch(array_values($baru), null, 200);
        }
        if ($ubah !== []) {
            $this->updateBatch(array_values($ubah), 'id', 200);
        }

        return ['inserted' => count($baru), 'updated' => count($ubah), 'skipped' => $skipped];
    }

    /**
     * Salin peserta dari KEHADIRAN sesi sebelumnya ke sesi tujuan.
     * Yang disalin hanya peserta sesi sumber yang benar-benar hadir (sama dengan angka kuorum).
     */
    public function salinDariKehadiran(int $idRat, int $idSesiTujuan, int $idSesiSumber): array
    {
        $hadir = $this->db->query(
            'SELECT ab.nip_snapshot AS nip, ab.nama_snapshot AS nama,
                    COALESCE(NULLIF(p.departemen, ""), a.departemen) AS departemen,
                    COALESCE(NULLIF(p.section, ""), a.section)       AS section,
                    COALESCE(NULLIF(p.shift, ""), a.shift)           AS shift
             FROM tb_absensi ab
             JOIN tb_peserta_rat p  ON p.nip = ab.nip_snapshot AND p.id_sesi = ab.id_sesi
             LEFT JOIN tb_anggota a ON a.nip = ab.nip_snapshot
             WHERE ab.id_sesi = ?',
            [$idSesiSumber]
        )->getResultArray();

        $sudahAda = array_flip(array_column(
            $this->select('nip')->where('id_sesi', $idSesiTujuan)->findAll(),
            'nip'
        ));

        $now   = date('Y-m-d H:i:s');
        $batch = [];
        $skipped = 0;
        foreach ($hadir as $h) {
            $nip = $h['nip'];
            if (isset($sudahAda[$nip]) || isset($batch[$nip])) {
                $skipped++;
                continue;
            }
            $batch[$nip] = [
                'id_rat'     => $idRat,
                'id_sesi'    => $idSesiTujuan,
                'nip'        => $nip,
                'nama'       => $h['nama'] ?? '',
                'departemen' => $h['departemen'] ?: null,
                'section'    => $h['section'] ?: null,
                'shift'      => $h['shift'] ?: null,
                'created_at' => $now,
            ];
        }

        if ($batch !== []) {
            $this->insertBatch(array_values($batch), null, 200);
        }

        return ['inserted' => count($batch), 'skipped' => $skipped, 'total_hadir' => count($hadir)];
    }
}
