<?php

namespace App\Models;

use CodeIgniter\Model;

class SesiModel extends Model
{
    protected $table            = 'tb_sesi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id_rat', 'nama_sesi', 'urutan', 'waktu_mulai', 'waktu_selesai',
        'status', 'locked_at', 'locked_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil seluruh sesi milik satu RAT, diurutkan berdasarkan urutan.
     */
    public function getByRat(int $idRat): array
    {
        return $this->where('id_rat', $idRat)
            ->orderBy('urutan', 'ASC')
            ->orderBy('waktu_mulai', 'ASC')
            ->findAll();
    }

    /**
     * Saat RAT baru dibuat, otomatis siapkan 5 sesi default.
     * Waktu dan nama bisa diedit Admin nanti di halaman Kelola Sesi.
     */
    public function buatSesiDefault(int $idRat): void
    {
        $sesiDefault = [
            ['nama_sesi' => 'Sesi 1', 'urutan' => 1, 'waktu_mulai' => '08:00:00', 'waktu_selesai' => '09:30:00'],
            ['nama_sesi' => 'Sesi 2', 'urutan' => 2, 'waktu_mulai' => '09:30:00', 'waktu_selesai' => '11:00:00'],
            ['nama_sesi' => 'Sesi 3', 'urutan' => 3, 'waktu_mulai' => '11:00:00', 'waktu_selesai' => '12:30:00'],
            ['nama_sesi' => 'Sesi 4', 'urutan' => 4, 'waktu_mulai' => '13:00:00', 'waktu_selesai' => '14:30:00'],
            ['nama_sesi' => 'Sesi 5', 'urutan' => 5, 'waktu_mulai' => '14:30:00', 'waktu_selesai' => '16:00:00'],
        ];

        foreach ($sesiDefault as $sesi) {
            $this->insert([
                'id_rat'        => $idRat,
                'nama_sesi'     => $sesi['nama_sesi'],
                'urutan'        => $sesi['urutan'],
                'waktu_mulai'   => $sesi['waktu_mulai'],
                'waktu_selesai' => $sesi['waktu_selesai'],
                'status'        => 'aktif',
            ]);
        }
    }

    /**
     * F-03: Kunci sesi.
     */
    public function lock(int $idSesi, int $idUserAdmin): bool
    {
        return $this->update($idSesi, [
            'status'    => 'terkunci',
            'locked_at' => date('Y-m-d H:i:s'),
            'locked_by' => $idUserAdmin,
        ]);
    }

    /**
     * F-03: Buka kunci sesi.
     */
    public function unlock(int $idSesi): bool
    {
        return $this->update($idSesi, [
            'status'    => 'aktif',
            'locked_at' => null,
            'locked_by' => null,
        ]);
    }

    /**
     * Ambil sesi sebelumnya berdasarkan urutan.
     */
    public function getSesiSebelumnya(int $idSesi): ?array
    {
        $sesi = $this->find($idSesi);
        if (!$sesi) return null;

        return $this->where('id_rat', $sesi['id_rat'])
            ->where('urutan <', $sesi['urutan'])
            ->orderBy('urutan', 'DESC')
            ->first();
    }
}
