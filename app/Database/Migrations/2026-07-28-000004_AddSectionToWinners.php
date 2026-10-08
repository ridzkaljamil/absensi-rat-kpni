<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSectionToWinners extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_doorprize_winners', [
            'section' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'departemen',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_doorprize_winners', 'section');
    }
}
