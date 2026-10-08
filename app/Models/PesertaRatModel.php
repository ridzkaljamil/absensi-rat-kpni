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
        'id_rat', 'id_sesi', 'nip', 'nama', 'departemen', 'section', 'shift', 'uid_rfid', 'created_at',
    ];
    protected $useTimestamps = false;

    // ─────────────────────────────────────────────
    //  Query
    // ─────────────────────────────────────────────

    public function getBySesi(int $idSesi): array
    {
        return $this->where('id_sesi', $idSesi)->orderBy('nama', 'ASC')->findAll();
    }

    /**
     * Daftar peserta unik (per NIP) di seluruh sesi satu RAT.
     * Dipakai untuk export "semua sesi" dan doorprize.
     */
    public function getUnikByRat(int $idRat): array
    {
        return $this->db->query(
            'SELECT p.* FROM tb_peserta_rat p
             JOIN (SELECT MIN(id) AS id FROM tb_peserta_rat WHERE id_rat = ? GROUP BY nip) u ON u.id = p.id
             ORDER BY p.nama ASC',
            [$idRat]
        )->getResultArray();
    }

    public function nipTerdaftarSesi(int $idSesi, string $nip): ?array
    {
        return $this->where('id_sesi', $idSesi)->where('nip', $nip)->first();
    }

    /**
     * Cari peserta (sesi mana pun di RAT ini) pemilik kartu RFID.
     * Kartu cukup didaftarkan sekali, berlaku untuk semua sesi.
     */
    public function cariByRfidDiRat(int $idRat, string $uid): ?array
    {
        return $this->where('id_rat', $idRat)->where('uid_rfid', $uid)->first();
    }

    /**
     * Cek apakah UID sudah dipakai orang LAIN (NIP berbeda) di RAT ini.
     */
    public function rfidDipakaiOrangLain(int $idRat, string $uid, string $nip): ?array
    {
        return $this->where('id_rat', $idRat)
            ->where('uid_rfid', $uid)
            ->where('nip !=', $nip)
            ->first();
    }

    /**
     * Pasang UID ke seluruh baris NIP ini di semua sesi RAT.
     */
    public function setRfidNip(int $idRat, string $nip, string $uid): void
    {
        $this->builder()->where('id_rat', $idRat)->where('nip', $nip)->update(['uid_rfid' => $uid]);
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
     * Data lama di-preload sekali supaya tidak query per baris.
     */
    public function importBulkSesi(int $idRat, int $idSesi, array $rows): array
    {
        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $now      = date('Y-m-d H:i:s');

        $existing = [];
        foreach ($this->select('id, nip, uid_rfid')->where('id_sesi', $idSesi)->findAll() as $e) {
            $existing[$e['nip']] = $e;
        }

        $batchInsert = [];
        foreach ($rows as $row) {
            $nip = trim((string) ($row['nip'] ?? ''));
            if ($nip === '') {
                $skipped++;
                continue;
            }

            $data = [
                'id_rat'     => $idRat,
                'id_sesi'    => $idSesi,
                'nip'        => $nip,
                'nama'       => trim((string) ($row['nama'] ?? '')),
                'departemen' => trim((string) ($row['departemen'] ?? '')) ?: null,
                'section'    => trim((string) ($row['section'] ?? '')) ?: null,
                'shift'      => trim((string) ($row['shift'] ?? '')) ?: null,
                'uid_rfid'   => trim((string) ($row['uid_rfid'] ?? '')) ?: null,
                'created_at' => $now,
            ];

            if (isset($existing[$nip])) {
                if (empty($data['uid_rfid']) && ! empty($existing[$nip]['uid_rfid'])) {
                    $data['uid_rfid'] = $existing[$nip]['uid_rfid'];
                }
                $this->update($existing[$nip]['id'], $data);
                $updated++;
            } else {
                $batchInsert[$nip] = $data; // key NIP: baris duplikat di file cukup sekali
                $existing[$nip]    = ['id' => 0, 'nip' => $nip, 'uid_rfid' => $data['uid_rfid']];
                $inserted++;
            }
        }

        if ($batchInsert !== []) {
            $this->insertBatch(array_values($batchInsert), null, 200);
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Salin peserta dari KEHADIRAN sesi sebelumnya ke sesi tujuan.
     * Yang disalin hanya NIP yang benar-benar absen di sesi sumber.
     */
    public function salinDariKehadiran(int $idRat, int $idSesiTujuan, int $idSesiSumber): array
    {
        $hadir = $this->db->query(
            'SELECT ab.nip_snapshot AS nip, ab.nama_snapshot AS nama,
                    COALESCE(NULLIF(p.departemen, ""), a.departemen) AS departemen,
                    COALESCE(NULLIF(p.section, ""), a.section)       AS section,
                    COALESCE(NULLIF(p.shift, ""), a.shift)           AS shift,
                    COALESCE(NULLIF(p.uid_rfid, ""), a.uid_rfid)     AS uid_rfid
             FROM tb_absensi ab
             LEFT JOIN tb_peserta_rat p ON p.nip = ab.nip_snapshot AND p.id_sesi = ?
             LEFT JOIN tb_anggota a     ON a.nip = ab.nip_snapshot
             WHERE ab.id_sesi = ? AND ab.nip_snapshot IS NOT NULL AND ab.nip_snapshot <> ""',
            [$idSesiSumber, $idSesiSumber]
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
                'uid_rfid'   => $h['uid_rfid'] ?: null,
                'created_at' => $now,
            ];
        }

        if ($batch !== []) {
            $this->insertBatch(array_values($batch), null, 200);
        }

        return ['inserted' => count($batch), 'skipped' => $skipped, 'total_hadir' => count($hadir)];
    }
}
