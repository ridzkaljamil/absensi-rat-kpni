<?php

namespace App\Models;

use CodeIgniter\Model;

class RatModel extends Model
{
    protected $table            = 'tb_rat';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['nama_rat', 'tahun_buku', 'tanggal', 'lokasi', 'total_anggota', 'status'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nama_rat'   => 'required|min_length[5]|max_length[150]',
        'tahun_buku' => 'required|is_natural_no_zero|exact_length[4]',
    ];

    protected $validationMessages = [
        'tahun_buku' => [
            'required' => 'Tahun buku wajib diisi.',
            'exact_length' => 'Tahun buku harus 4 digit, contoh: 2026.',
        ],
    ];

    /**
     * Ambil seluruh histori RAT, terbaru duluan.
     * Dipakai untuk dropdown selector (F-18).
     */
    public function getAllOrdered(): array
    {
        return $this->orderBy('tahun_buku', 'DESC')->findAll();
    }

    /**
     * RAT yang sedang aktif (status='aktif'). Asumsi: hanya ada 1 RAT
     * aktif di satu waktu - RAT lama otomatis diset 'selesai' saat
     * RAT baru dibuat (lihat RatController::store()).
     */
    public function getActive(): ?array
    {
        return $this->where('status', 'aktif')->orderBy('tahun_buku', 'DESC')->first();
    }

    /**
     * Cek apakah tahun buku tertentu sudah pernah dibuat sebelumnya.
     * Mencegah Admin membuat RAT dengan tahun buku yang sama dua kali.
     */
    public function tahunBukuSudahAda(int $tahunBuku): bool
    {
        return $this->where('tahun_buku', $tahunBuku)->countAllResults() > 0;
    }
}
