<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFotoToAbsensi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_absensi', [
            'foto_path' => [
                'type'    => 'VARCHAR',
                'constraint' => 255,
                'null'    => true,
                'default' => null,
                'after'   => 'metode',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_absensi', 'foto_path');
    }
}
