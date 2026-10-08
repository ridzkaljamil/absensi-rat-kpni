<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Data anggota CONTOH (fiktif) untuk mencoba aplikasi.
 *
 * Data anggota asli KPNI tidak disimpan di repository.
 * Jalankan: php spark db:seed AnggotaContohSeeder
 */
class AnggotaContohSeeder extends Seeder
{
    public function run()
    {
        $depan    = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar', 'Gita', 'Hadi', 'Indah', 'Joko', 'Kartika', 'Lutfi', 'Maya', 'Nanda', 'Oki'];
        $belakang = ['Pratama', 'Saputra', 'Lestari', 'Wibowo', 'Kurniawan', 'Permata', 'Santoso', 'Rahayu', 'Hidayat', 'Nugroho'];
        $unit     = [
            ['PRODUCTION', 'ASSEMBLY'],
            ['PARTS PRODUCTION', 'STAMPING'],
            ['PARTS PRODUCTION', 'RUBBER'],
            ['QUALITY ASSURANCE', 'QUALITY INSPECTION'],
            ['MAINTENANCE', 'MACHINE MAINTENANCE'],
            ['HUMAN RESOURCE', 'GENERAL AFFAIRS'],
        ];
        $shift = ['A', 'B', 'N'];
        $now   = date('Y-m-d H:i:s');

        $data = [];
        for ($i = 0; $i < 30; $i++) {
            [$dept, $section] = $unit[$i % count($unit)];
            $data[] = [
                // NIP 6 digit: digit 1 = 1/2, digit 2-3 = tahun masuk, digit 4-6 = nomor urut
                'nip'        => sprintf('%d%02d%03d', 1 + ($i % 2), 15 + ($i % 10), $i + 1),
                'nama'       => strtoupper($depan[$i % count($depan)] . ' ' . $belakang[$i % count($belakang)]),
                'departemen' => $dept,
                'section'    => $section,
                'shift'      => $shift[$i % 3],
                'uid_rfid'   => sprintf('99%08d', $i + 1),
                'status'     => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('tb_anggota')->ignore(true)->insertBatch($data);
        echo count($data) . " anggota contoh ditambahkan.\n";
    }
}
