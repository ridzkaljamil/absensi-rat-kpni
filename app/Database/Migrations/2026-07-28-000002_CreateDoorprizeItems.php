<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDoorprizeItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_rat'      => ['type' => 'INT', 'unsigned' => true],
            'nama_barang' => ['type' => 'VARCHAR', 'constraint' => 200],
            'jumlah'      => ['type' => 'INT', 'default' => 1],
            'kategori'    => ['type' => 'ENUM', 'constraint' => ['utama', 'hiburan'], 'default' => 'utama'],
            'gambar_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_rat');
        $this->forge->createTable('tb_doorprize_items');
    }

    public function down()
    {
        $this->forge->dropTable('tb_doorprize_items', true);
    }
}
