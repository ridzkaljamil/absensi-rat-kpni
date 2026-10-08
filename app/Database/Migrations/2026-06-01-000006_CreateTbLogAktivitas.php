<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbLogAktivitas extends Migration
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
            'id_user' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'aksi' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'comment'    => 'Contoh: "Mengunci Sesi Pagi RAT 2026", "Import 5 anggota baru"',
            ],
            'waktu' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_user');
        $this->forge->addForeignKey('id_user', 'tb_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_log_aktivitas');
    }

    public function down()
    {
        $this->forge->dropTable('tb_log_aktivitas');
    }
}
