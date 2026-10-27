<?php

namespace App\Libraries;

use App\Models\AnggotaModel;

/**
 * Satu-satunya tempat logika kartu RFID.
 *
 * Aturan: satu kartu = satu orang (NIP), disimpan HANYA di data induk
 * (tb_anggota.uid_rfid). Daftar peserta RAT membaca kartu lewat join ke
 * data induk, jadi kartu otomatis berlaku di semua sesi dan semua RAT.
 *
 * Pencocokan UID toleran terhadap nol di depan yang hilang karena Excel
 * ("0005119875" dari reader = "5119875" hasil import).
 */
class KartuRfid
{
    public const MAKS_PANJANG = 50;

    /** UID kartu yang sah: huruf/angka saja, 4–50 karakter (menolak sampah Excel seperti "#N/A"). */
    public static function uidValid(string $uid): bool
    {
        return (bool) preg_match('/^[0-9A-Z]{4,50}$/', normalisasi_uid($uid));
    }

    private AnggotaModel $anggota;

    public function __construct()
    {
        $this->anggota = new AnggotaModel();
    }

    /**
     * Pemilik kartu (baris tb_anggota), atau null kalau kartu belum dikenal.
     */
    public function cariPemilik(string $uid): ?array
    {
        $uid = normalisasi_uid($uid);
        if (! self::uidValid($uid)) {
            return null;
        }

        // Toleransi nol di depan hanya untuk input panjang (bentuk UID kartu),
        // supaya NIP 6 digit yang diketik tidak tertukar dengan kartu orang lain.
        return $this->cocokkan($uid, null, strlen($uid) >= 8);
    }

    /**
     * Pesan error kalau UID tidak valid atau sudah milik NIP lain, null kalau aman.
     */
    public function cekBentrok(string $uid, string $nip): ?string
    {
        $uid = normalisasi_uid($uid);
        if ($uid === '') {
            return 'UID kartu kosong.';
        }
        if (! self::uidValid($uid)) {
            return 'UID kartu tidak valid (hanya huruf/angka, 4–' . self::MAKS_PANJANG . ' karakter).';
        }
        $lain = $this->cocokkan($uid, $nip);

        return $lain ? "Kartu sudah dipakai oleh {$lain['nama']} (NIP {$lain['nip']})." : null;
    }

    /**
     * Pasang kartu ke NIP. Kalau NIP belum ada di data induk, dibuatkan dari
     * $dataBaru (nama, departemen, section, shift — biasanya data peserta).
     * Return null kalau berhasil, atau pesan error.
     */
    public function pasang(string $nip, string $uid, ?array $dataBaru = null): ?string
    {
        $uid   = normalisasi_uid($uid);
        $pesan = $this->cekBentrok($uid, $nip);
        if ($pesan !== null) {
            return $pesan;
        }

        $induk = $this->anggota->findByPrNumber($nip);
        if ($induk) {
            if ($induk['uid_rfid'] !== $uid) {
                $this->anggota->update($induk['id'], ['uid_rfid' => $uid]);
            }

            return null;
        }
        if ($dataBaru === null) {
            return "NIP {$nip} belum ada di data induk.";
        }

        $ok = $this->anggota->insert([
            'nip'        => $nip,
            'nama'       => $dataBaru['nama'] ?? $nip,
            'departemen' => ($dataBaru['departemen'] ?? null) ?: null,
            'section'    => ($dataBaru['section'] ?? null) ?: null,
            'shift'      => ($dataBaru['shift'] ?? null) ?: null,
            'uid_rfid'   => $uid,
            'status'     => 'aktif',
        ]);

        return $ok ? null : (implode(' ', $this->anggota->errors()) ?: "Gagal membuat data induk NIP {$nip}.");
    }

    /**
     * Lepas kartu dari NIP (kartu hilang/rusak atau anggota keluar), supaya kartunya bisa dipakai orang lain.
     */
    public function lepas(string $nip): void
    {
        $this->anggota->builder()->where('nip', $nip)->update(['uid_rfid' => null, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Kartu milik NIP ini (null kalau belum punya).
     */
    public function milikNip(string $nip): ?string
    {
        $uid = $this->anggota->findByPrNumber($nip)['uid_rfid'] ?? null;

        return $uid !== null && $uid !== '' ? $uid : null;
    }

    /**
     * Baris anggota dengan UID sama persis, atau sama setelah nol di depan diabaikan.
     * $kecualiNip dipakai untuk mencari "orang lain" yang memakai kartu ini.
     */
    private function cocokkan(string $uid, ?string $kecualiNip = null, bool $toleranNol = true): ?array
    {
        $builder = $this->anggota->builder()->select('id, nip, nama, uid_rfid, status');
        if ($kecualiNip !== null) {
            $builder->where('nip !=', $kecualiNip);
        }
        $builder->groupStart()->where('uid_rfid', $uid);
        $tanpaNol = ltrim($uid, '0');
        if ($toleranNol && ctype_digit($uid) && $tanpaNol !== '') {
            $builder->orWhere("TRIM(LEADING '0' FROM uid_rfid) =", $tanpaNol);
        }

        return $builder->groupEnd()->get(1)->getRowArray();
    }
}
