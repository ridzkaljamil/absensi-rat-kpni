<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDoorprizeWinners extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_rat'      => ['type' => 'INT', 'unsigned' => true],
            'id_item'     => ['type' => 'INT', 'unsigned' => true],
            'nip'         => ['type' => 'VARCHAR', 'constraint' => 10],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'departemen'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'waktu_undi'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_rat');
        $this->forge->addKey('id_item');
        $this->forge->createTable('tb_doorprize_winners');
    }

    public function down()
    {
        $this->forge->dropTable('tb_doorprize_winners', true);
    }
}
