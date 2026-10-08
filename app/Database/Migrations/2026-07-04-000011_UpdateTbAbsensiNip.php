<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTbAbsensiNip extends Migration
{
    public function up()
    {
        // Ganti snapshot column pr_number_snapshot → nip_snapshot di tb_absensi
        $this->forge->modifyColumn('tb_absensi', [
            'pr_number_snapshot' => [
                'name'       => 'nip_snapshot',
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('tb_absensi', [
            'nip_snapshot' => [
                'name'       => 'pr_number_snapshot',
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
        ]);
    }
}
