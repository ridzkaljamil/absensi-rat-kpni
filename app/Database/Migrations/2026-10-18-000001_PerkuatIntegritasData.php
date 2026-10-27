<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * Pengaman di level database untuk kasus yang sebelumnya hanya dicek di kode:
 *
 * - tb_absensi: satu NIP hanya boleh tercatat sekali per sesi
 *   (sebelumnya unique memakai id_anggota yang bisa NULL setelah anggota dihapus).
 * - tb_anggota.uid_rfid: panjang disamakan dengan tb_peserta_rat (50 karakter).
 * - tb_log_aktivitas: log tidak ikut terhapus saat akun dihapus (id_user jadi NULL).
 * - tb_doorprize_winners: satu NIP hanya boleh menang sekali per RAT,
 *   ditambah kolom `hangus` untuk pemenang yang tidak hadir saat diumumkan.
 *
 * Aman dijalankan ulang (langkah yang sudah ada dilewati).
 */
class PerkuatIntegritasData extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ── tb_absensi ───────────────────────────────────────────
        if (! $this->adaIndex('tb_absensi', 'uniq_absensi_sesi_nip')) {
            $ganda = $db->query(
                'SELECT id_sesi, nip_snapshot, COUNT(*) n FROM tb_absensi
                 WHERE nip_snapshot IS NOT NULL GROUP BY id_sesi, nip_snapshot HAVING n > 1 LIMIT 5'
            )->getResultArray();
            if ($ganda !== []) {
                $contoh = implode(', ', array_map(static fn ($g) => "sesi {$g['id_sesi']} NIP {$g['nip_snapshot']}", $ganda));
                throw new RuntimeException("Ada absensi ganda ({$contoh}). Hapus salah satunya dulu, lalu jalankan migrate lagi.");
            }
            $db->query('ALTER TABLE tb_absensi ADD UNIQUE KEY uniq_absensi_sesi_nip (id_sesi, nip_snapshot)');
        }

        // UID kartu di data induk disamakan panjangnya dengan tb_peserta_rat (50)
        $kolom = $db->query("SHOW COLUMNS FROM tb_anggota LIKE 'uid_rfid'")->getRowArray();
        if ($kolom && stripos($kolom['Type'], 'varchar(50)') === false) {
            $db->query("ALTER TABLE tb_anggota MODIFY uid_rfid VARCHAR(50) NULL DEFAULT NULL COMMENT 'UID kartu RFID, satu kartu satu anggota'");
        }

        // Log aktivitas tetap tersimpan walau akunnya dihapus (sebelumnya ikut terhapus)
        $fk = $db->query("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
                          WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'tb_log_aktivitas_id_user_foreign'")->getRowArray();
        if ($fk && $fk['DELETE_RULE'] !== 'SET NULL') {
            $db->query('ALTER TABLE tb_log_aktivitas DROP FOREIGN KEY tb_log_aktivitas_id_user_foreign');
            $db->query('ALTER TABLE tb_log_aktivitas MODIFY id_user INT(11) UNSIGNED NULL DEFAULT NULL');
            $db->query('ALTER TABLE tb_log_aktivitas ADD CONSTRAINT tb_log_aktivitas_id_user_foreign
                        FOREIGN KEY (id_user) REFERENCES tb_users(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        // ── tb_doorprize_winners ─────────────────────────────────
        if (! $db->fieldExists('hangus', 'tb_doorprize_winners')) {
            $db->query("ALTER TABLE tb_doorprize_winners
                        ADD COLUMN hangus TINYINT(1) NOT NULL DEFAULT 0
                        COMMENT '1 = pemenang tidak hadir, hadiah diundi ulang, orangnya tetap tidak ikut undian'
                        AFTER waktu_undi");
        }
        if (! $this->adaIndex('tb_doorprize_winners', 'uniq_pemenang_rat_nip')) {
            $ganda = $db->query(
                'SELECT nip FROM tb_doorprize_winners GROUP BY id_rat, nip HAVING COUNT(*) > 1 LIMIT 5'
            )->getResultArray();
            if ($ganda !== []) {
                throw new RuntimeException('Ada NIP yang menang lebih dari sekali dalam satu RAT (' . implode(', ', array_column($ganda, 'nip')) . '). Batalkan salah satunya dulu, lalu jalankan migrate lagi.');
            }
            $db->query('ALTER TABLE tb_doorprize_winners ADD UNIQUE KEY uniq_pemenang_rat_nip (id_rat, nip)');
        }
    }

    public function down()
    {
        if ($this->adaIndex('tb_absensi', 'uniq_absensi_sesi_nip')) {
            $this->db->query('ALTER TABLE tb_absensi DROP INDEX uniq_absensi_sesi_nip');
        }
        if ($this->adaIndex('tb_doorprize_winners', 'uniq_pemenang_rat_nip')) {
            $this->db->query('ALTER TABLE tb_doorprize_winners DROP INDEX uniq_pemenang_rat_nip');
        }
        if ($this->db->fieldExists('hangus', 'tb_doorprize_winners')) {
            $this->db->query('ALTER TABLE tb_doorprize_winners DROP COLUMN hangus');
        }
    }

    private function adaIndex(string $tabel, string $nama): bool
    {
        $indexes = array_column($this->db->query("SHOW INDEX FROM {$tabel}")->getResultArray(), 'Key_name');

        return in_array($nama, $indexes, true);
    }
}
