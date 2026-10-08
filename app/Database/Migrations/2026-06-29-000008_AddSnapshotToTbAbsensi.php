<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSnapshotToTbAbsensi extends Migration
{
    /**
     * Latar belakang:
     * Fitur hapus anggota membutuhkan data rekap tetap akurat walau
     * anggota sudah dihapus. Solusi: simpan nama dan PR number anggota
     * sebagai snapshot saat absen dicatat — jadi JOIN ke tb_anggota
     * tidak lagi diperlukan untuk menampilkan nama di rekap.
     *
     * id_anggota dibuat nullable supaya record absensi tetap ada
     * walau anggotanya sudah dihapus dari tb_anggota.
     */
    public function up()
    {
        // Ubah id_anggota jadi nullable
        $this->forge->modifyColumn('tb_absensi', [
            'id_anggota' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id_sesi',
            ],
        ]);

        // Tambah kolom snapshot
        $this->forge->addColumn('tb_absensi', [
            'nama_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'id_anggota',
                'comment'    => 'Nama anggota saat absen - tetap ada walau anggota dihapus',
            ],
            'pr_number_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 6,
                'null'       => true,
                'after'      => 'nama_snapshot',
                'comment'    => 'PR Number anggota saat absen',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_absensi', 'nama_snapshot');
        $this->forge->dropColumn('tb_absensi', 'pr_number_snapshot');

        $this->forge->modifyColumn('tb_absensi', [
            'id_anggota' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
        ]);
    }
}
