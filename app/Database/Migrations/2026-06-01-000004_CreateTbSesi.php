<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbSesi extends Migration
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
            'nama_sesi' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Pagi / Siang / Sore',
            ],
            'waktu_mulai' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'waktu_selesai' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['aktif', 'terkunci'],
                'default'    => 'aktif',
                'comment'    => 'F-03: lock/unlock sesi',
            ],
            'locked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'locked_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'id_user (Admin) yang mengunci sesi terakhir kali - last-write-wins, Bagian 9 poin 4',
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
        $this->forge->addKey('id_rat');
        $this->forge->addForeignKey('id_rat', 'tb_rat', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_sesi');
    }

    public function down()
    {
        $this->forge->dropTable('tb_sesi');
    }
}
