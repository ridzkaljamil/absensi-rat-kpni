<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUrutanToTbSesi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_sesi', [
            'urutan' => [
                'type'    => 'TINYINT',
                'null'    => false,
                'default' => 1,
                'after'   => 'nama_sesi',
                'comment' => 'Urutan tampil sesi dalam satu RAT',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_sesi', 'urutan');
    }
}
