<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kartu RFID disimpan di SATU tempat: tb_anggota.uid_rfid.
 *
 * Sebelumnya UID juga disalin ke setiap baris tb_peserta_rat (per sesi),
 * sehingga harus dijaga sinkron di beberapa tabel. Migration ini:
 *  1. Membuatkan data induk untuk peserta yang punya kartu tapi belum ada di tb_anggota.
 *  2. Memindahkan kartu dari peserta ke data induk kalau data induk belum punya kartu
 *     dan kartunya belum dipakai orang lain.
 *  3. Mengosongkan UID yang tidak valid (sampah Excel seperti "#N/A").
 *  4. Menghapus kolom tb_peserta_rat.uid_rfid.
 *
 * Aman dijalankan ulang: kalau kolom sudah tidak ada, tidak ada yang dilakukan.
 */
class KartuRfidDiDataInduk extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (! $db->fieldExists('uid_rfid', 'tb_peserta_rat')) {
            return;
        }

        // 1. Peserta berkartu yang belum ada di data induk → dibuatkan (satu baris per NIP)
        $db->query("
            INSERT INTO tb_anggota (nip, nama, departemen, section, shift, status, created_at, updated_at)
            SELECT p.nip, MAX(p.nama), MAX(NULLIF(p.departemen, '')), MAX(NULLIF(p.section, '')), MAX(NULLIF(p.shift, '')), 'aktif', NOW(), NOW()
            FROM tb_peserta_rat p
            LEFT JOIN tb_anggota a ON a.nip = p.nip
            WHERE a.id IS NULL AND p.uid_rfid IS NOT NULL AND p.uid_rfid <> ''
            GROUP BY p.nip
        ");

        // 2. Kartu peserta → data induk (hanya kalau data induk kosong & kartu belum dipakai NIP lain)
        //    Kalau satu NIP punya kartu berbeda di beberapa sesi, yang dipakai kartu terbaru (baris id terbesar).
        $kandidat = $db->query("
            SELECT p.nip, p.uid_rfid AS uid
            FROM tb_peserta_rat p
            JOIN (SELECT nip, MAX(id) AS id FROM tb_peserta_rat WHERE uid_rfid IS NOT NULL AND uid_rfid <> '' GROUP BY nip) t ON t.id = p.id
            JOIN tb_anggota a ON a.nip = p.nip
            WHERE a.uid_rfid IS NULL OR a.uid_rfid = ''
        ")->getResultArray();
        foreach ($kandidat as $k) {
            $dipakai = $db->table('tb_anggota')->where('uid_rfid', $k['uid'])->where('nip !=', $k['nip'])->countAllResults();
            if ($dipakai === 0) {
                $db->table('tb_anggota')->where('nip', $k['nip'])->update(['uid_rfid' => $k['uid'], 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }

        // 3. UID sampah dari Excel (mis. "#N/A") dikosongkan supaya kartunya bisa didaftarkan ulang
        $db->query("UPDATE tb_anggota SET uid_rfid = NULL WHERE uid_rfid IS NOT NULL AND uid_rfid NOT REGEXP '^[0-9A-Za-z]{4,50}$'");

        // 4. Kolom duplikat dihapus
        $db->query('ALTER TABLE tb_peserta_rat DROP COLUMN uid_rfid');
    }

    public function down()
    {
        if ($this->db->fieldExists('uid_rfid', 'tb_peserta_rat')) {
            return;
        }
        $this->db->query('ALTER TABLE tb_peserta_rat ADD COLUMN uid_rfid VARCHAR(50) NULL DEFAULT NULL AFTER shift');
        $this->db->query('UPDATE tb_peserta_rat p JOIN tb_anggota a ON a.nip = p.nip SET p.uid_rfid = a.uid_rfid');
    }
}
