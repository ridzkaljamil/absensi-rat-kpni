<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbRat extends Migration
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
            'nama_rat' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'comment'    => 'Contoh: Rapat Anggota Tahunan Tahun Buku 2026',
            ],
            'tahun_buku' => [
                'type'       => 'YEAR',
                'comment'    => 'F-17/F-18: pemisah histori RAT antar tahun, mulai dari 2026',
            ],
            'tanggal' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'lokasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'total_anggota' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => 'Snapshot jumlah anggota aktif saat RAT ini dibuat',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['aktif', 'selesai'],
                'default'    => 'aktif',
                'comment'    => 'RAT yang sedang berjalan vs sudah selesai',
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
        $this->forge->addKey('tahun_buku');
        $this->forge->createTable('tb_rat');
    }

    public function down()
    {
        $this->forge->dropTable('tb_rat');
    }
}
