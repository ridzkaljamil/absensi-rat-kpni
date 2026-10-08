<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Peserta RAT dicatat per sesi (kuorum dihitung per sesi).
 *
 * Migration ini setara dengan SQL manual yang sudah dijalankan di
 * phpMyAdmin pada 8 Oktober 2026. Dibuat aman dijalankan ulang:
 * kalau kolom/index sudah ada, langkah tersebut dilewati — sehingga
 * `php spark migrate` di database lama maupun instalasi baru sama-sama jalan.
 */
class AddIdSesiToTbPesertaRat extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->fieldExists('id_sesi', 'tb_peserta_rat')) {
            $db->query('ALTER TABLE tb_peserta_rat ADD COLUMN id_sesi INT(11) UNSIGNED NULL DEFAULT NULL AFTER id_rat');
            $db->query('ALTER TABLE tb_peserta_rat ADD INDEX idx_peserta_sesi (id_sesi)');

            // Peserta lama dimasukkan ke sesi pertama RAT-nya
            $db->query('UPDATE tb_peserta_rat p
                        JOIN (SELECT id_rat, MIN(id) AS first_sesi_id FROM tb_sesi GROUP BY id_rat) s ON p.id_rat = s.id_rat
                        SET p.id_sesi = s.first_sesi_id
                        WHERE p.id_sesi IS NULL');
            $db->query('DELETE FROM tb_peserta_rat WHERE id_sesi IS NULL');

            $db->query('ALTER TABLE tb_peserta_rat MODIFY COLUMN id_sesi INT(11) UNSIGNED NOT NULL');
            $db->query('ALTER TABLE tb_peserta_rat ADD CONSTRAINT fk_peserta_sesi FOREIGN KEY (id_sesi) REFERENCES tb_sesi(id) ON DELETE CASCADE ON UPDATE CASCADE');
        }

        // Unique key lama (id_rat, nip) diganti (id_sesi, nip):
        // satu NIP boleh terdaftar di beberapa sesi dalam RAT yang sama.
        $indexes = array_column($db->query('SHOW INDEX FROM tb_peserta_rat')->getResultArray(), 'Key_name');
        if (in_array('id_rat_nip', $indexes, true)) {
            $db->query('ALTER TABLE tb_peserta_rat DROP INDEX id_rat_nip');
        }
        if (! in_array('id_sesi_nip', $indexes, true)) {
            $db->query('ALTER TABLE tb_peserta_rat ADD UNIQUE KEY id_sesi_nip (id_sesi, nip)');
        }
    }

    public function down()
    {
        // Sengaja tidak menghapus kolom id_sesi: data peserta per sesi akan hilang.
    }
}
