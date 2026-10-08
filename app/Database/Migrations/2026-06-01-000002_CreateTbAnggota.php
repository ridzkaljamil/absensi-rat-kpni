<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbAnggota extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'pr_number' => [
                'type' => 'VARCHAR',
                'constraint' => 6,
                'unique' => true,
                'comment' => 'Format 6 digit: [1=L/2=P][2 digit tahun masuk][3 digit nomor urut]',
            ],
            'nama' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['aktif', 'nonaktif'],
                'default' => 'aktif',
                'comment' => 'F-19: nonaktif = anggota keluar/habis kontrak, data tetap tersimpan untuk histori',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('tb_anggota');
    }

    public function down()
    {
        $this->forge->dropTable('tb_anggota');
    }
}
