<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbPesertaRat extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_rat' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nip' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'departemen' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'section' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'shift' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
            ],
            'uid_rfid' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('id_rat');
        $this->forge->addUniqueKey(['id_rat', 'nip']);
        $this->forge->addForeignKey('id_rat', 'tb_rat', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_peserta_rat');
    }

    public function down()
    {
        $this->forge->dropTable('tb_peserta_rat');
    }
}
