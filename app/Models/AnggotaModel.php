<?php

namespace App\Models;

use CodeIgniter\Model;

class AnggotaModel extends Model
{
    protected $table            = 'tb_anggota';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['nip', 'nama', 'departemen', 'section', 'shift', 'uid_rfid', 'status'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nip' => 'required|exact_length[6]|numeric',
        'nama'      => 'required|min_length[3]|max_length[100]',
    ];

    protected $validationMessages = [
        'nip' => [
            'exact_length' => 'PR Number harus 6 digit angka.',
            'numeric'      => 'PR Number harus berupa angka.',
        ],
    ];

    /**
     * Cari anggota berdasarkan PR number - dipakai di Modul Absensi
     * nanti untuk validasi F-12/F-13/F-14/F-20.
     */
    public function findByPrNumber(string $prNumber): ?array
    {
        return $this->where('nip', $prNumber)->first();
    }

    /**
     * Cari anggota berdasarkan UID kartu RFID (Opsi B - lihat catatan
     * lengkap di migration AddUidRfidToTbAnggota). Dipakai sebagai
     * pencarian UTAMA saat tap kartu; kalau tidak ketemu di sini,
     * AbsensiController akan fallback ke pencarian manual PR Number
     * (untuk kartu yang UID-nya belum dipetakan/didaftarkan).
     */
    public function findByUidRfid(string $uidRfid): ?array
    {
        return $this->where('uid_rfid', $uidRfid)->first();
    }

    /**
     * Cek apakah UID kartu ini sudah dipetakan ke anggota lain -
     * mencegah satu kartu fisik terdaftar ganda ke 2 orang berbeda.
     */
    public function uidRfidSudahAda(string $uidRfid): bool
    {
        return $this->where('uid_rfid', $uidRfid)->countAllResults() > 0;
    }

    /**
     * Cek apakah PR number sudah terdaftar - dipakai saat tambah manual
     * maupun import Excel untuk mencegah duplikat (Bagian 9, F-04).
     */
    public function nipSudahAda(string $prNumber): bool
    {
        return $this->where('nip', $prNumber)->countAllResults() > 0;
    }

    /**
     * F-19: nonaktifkan anggota (anggota keluar/habis kontrak).
     * Data TIDAK dihapus - histori absensi RAT lama tetap aman karena
     * foreign key di tb_absensi tidak kehilangan acuannya.
     */
    public function nonaktifkan(int $id): bool
    {
        return $this->update($id, ['status' => 'nonaktif']);
    }

    /**
     * Kebalikan dari nonaktifkan - sesuai acceptance criteria F-19,
     * status bisa diaktifkan kembali jika perlu.
     */
    public function aktifkanKembali(int $id): bool
    {
        return $this->update($id, ['status' => 'aktif']);
    }
}
