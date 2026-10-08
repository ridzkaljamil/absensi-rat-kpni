<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUidRfidToTbAnggota extends Migration
{
    /**
     * Latar belakang keputusan (didiskusikan dengan pembimbing lapangan
     * dan dikonfirmasi via testing reader fisik):
     *
     * Kartu RFID KPNI ternyata bersifat READ-ONLY - reader selalu
     * membaca UID unik dari pabrik (contoh: 0005543189), BUKAN PR
     * Number yang ditulis ke kartu. Asumsi awal di Bab III ("kartu
     * RFID sudah menyimpan PR Number langsung") tidak berlaku untuk
     * jenis kartu yang dipakai KPNI saat ini.
     *
     * Solusi: kolom uid_rfid ini menjadi "pemetaan" - sistem mencari
     * anggota berdasarkan UID kartu, lalu mengetahui PR Number dan
     * nama anggotanya dari situ. PR Number tetap jadi identitas utama
     * anggota (dipakai di laporan, rekap, dll) - uid_rfid cuma jadi
     * kunci tambahan untuk pencarian saat tap kartu.
     *
     * Data UID belum tersedia dari koperasi saat migration ini dibuat
     * (kolom RFID di file Excel mereka masih kosong, sedang di-follow up
     * ke tim developer internal koperasi) - makanya kolom ini nullable,
     * dan untuk sementara absensi tetap bisa jalan via input manual
     * PR Number sambil menunggu data UID menyusul.
     */
    public function up()
    {
        $this->forge->addColumn('tb_anggota', [
            'uid_rfid' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'unique'     => true,
                'after'      => 'pr_number',
                'comment'    => 'UID fisik kartu RFID (dari pabrik) - dipetakan ke anggota ini. Null = belum ada data RFID.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_anggota', 'uid_rfid');
    }
}
