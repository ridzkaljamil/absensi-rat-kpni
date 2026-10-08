<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Akun admin default untuk login pertama kali.
        // WAJIB ganti password setelah login pertama (lihat F-16 di PRD).
        $data = [
            'username'     => 'admin',
            'password'     => password_hash('admin123', PASSWORD_DEFAULT),
            'role'         => 'admin',
            'nama_lengkap' => 'Administrator KPNI',
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $this->db->table('tb_users')->insert($data);

        echo "Admin default dibuat — username: admin / password: admin123\n";
        echo "PENTING: ganti password ini setelah login pertama kali!\n";
    }
}
