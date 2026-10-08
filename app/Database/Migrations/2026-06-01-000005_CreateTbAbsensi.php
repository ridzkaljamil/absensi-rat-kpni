<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbAbsensi extends Migration
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
            'id_sesi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_anggota' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'waktu_absen' => [
                'type' => 'DATETIME',
            ],
            'metode' => [
                'type'       => 'ENUM',
                'constraint' => ['rfid', 'manual'],
                'comment'    => 'F-05: cara input absensi',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_sesi');
        $this->forge->addKey('id_anggota');
        $this->forge->addForeignKey('id_sesi', 'tb_sesi', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_anggota', 'tb_anggota', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('tb_absensi');

        // PENTING — Bagian 9 poin 6 (PRD): unique constraint untuk mencegah
        // race condition saat 2 laptop tap RFID nyaris bersamaan untuk anggota
        // yang sama. Database jadi penjaga terakhir, bukan hanya validasi PHP.
        $this->db->query(
            'ALTER TABLE tb_absensi
             ADD CONSTRAINT unique_absensi_per_sesi
             UNIQUE (id_sesi, id_anggota)'
        );
    }

    public function down()
    {
        $this->forge->dropTable('tb_absensi');
    }
}
