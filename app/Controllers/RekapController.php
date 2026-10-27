<?php

namespace App\Controllers;

use App\Libraries\ExcelExport;
use App\Models\AbsensiModel;
use App\Models\AnggotaModel;
use App\Models\RatModel;
use App\Models\SesiModel;
use App\Models\PesertaRatModel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RekapController extends BaseController
{
    protected AbsensiModel $absensiModel;
    protected AnggotaModel $anggotaModel;
    protected RatModel     $ratModel;
    protected SesiModel    $sesiModel;
    protected PesertaRatModel $pesertaModel;

    public function __construct()
    {
        $this->absensiModel = new AbsensiModel();
        $this->anggotaModel = new AnggotaModel();
        $this->ratModel     = new RatModel();
        $this->sesiModel    = new SesiModel();
        $this->pesertaModel = new PesertaRatModel();
    }

    /**
     * F-21: Halaman cetak rekap (print-friendly, buka di tab baru).
     */
    public function cetak()
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) return redirect()->to('/rekap')->with('error', 'Pilih RAT terlebih dahulu.');

        $rat         = $this->ratModel->find($idRat);
        $daftarSesi  = $this->sesiModel->getByRat($idRat);
        $pesertaSesi = $this->pesertaModel->hitungPerSesi($idRat);

        // Data absensi + peserta & kuorum per sesi
        foreach ($daftarSesi as &$sesi) {
            $sesi['data']    = $this->absensiModel->getDetailBySesi((int) $sesi['id']);
            $sesi['peserta'] = (int) ($pesertaSesi[$sesi['id']] ?? 0);
            $sesi['kuorum']  = hitung_kuorum($sesi['peserta']);
        }
        unset($sesi);

        return view('rekap/cetak', [
            'rat'        => $rat,
            'daftarSesi' => $daftarSesi,
            'totalAktif' => $this->pesertaModel->totalPeserta($idRat),
        ]);
    }

    /**
     * Halaman pilih rekap - menampilkan daftar sesi dan tombol download.
     */
    public function index()
    {
        $idRat = $this->idRatDipilih();

        if (! $idRat) {
            return redirect()->to('/rat')
                ->with('error', 'Pilih RAT terlebih dahulu sebelum mengunduh rekap.');
        }

        $daftarSesi  = $this->sesiModel->getByRat($idRat);
        $hadirSesi   = $this->absensiModel->hitungPerSesi(array_column($daftarSesi, 'id'));
        $pesertaSesi = $this->pesertaModel->hitungPerSesi($idRat);

        $dataSesi = [];
        foreach ($daftarSesi as $sesi) {
            $dataSesi[] = [
                'id'            => $sesi['id'],
                'nama_sesi'     => $sesi['nama_sesi'],
                'status'        => $sesi['status'],
                'jumlah_hadir'  => (int) ($hadirSesi[$sesi['id']] ?? 0),
                'total_peserta' => (int) ($pesertaSesi[$sesi['id']] ?? 0),
            ];
        }

        return view('rekap/index', [
            'rat'        => $this->ratModel->find($idRat),
            'dataSesi'   => $dataSesi,
            'totalAktif' => $this->pesertaModel->totalPeserta($idRat),
        ]);
    }

    /**
     * F-06: Download rekap satu sesi sebagai file Excel.
     * Acceptance criteria PRD: isinya sesuai data absensi sesi yang
     * dipilih, tanpa data sesi lain.
     */
    public function downloadSesi(int $idSesi)
    {
        $sesi = $this->sesiModel->find($idSesi);
        if (! $sesi || (int) $sesi['id_rat'] !== $this->idRatDipilih()) {
            return redirect()->to('/rekap')->with('error', 'Sesi tidak ditemukan.');
        }

        $rat       = $this->ratModel->find($sesi['id_rat']);
        $dataAbsen = $this->absensiModel->getDetailBySesi($idSesi);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->judulSheet($sesi['nama_sesi']));

        $this->tulisHeaderSesi($sheet, $rat, $sesi, count($dataAbsen));
        $this->tulisDataSesi($sheet, $dataAbsen, 8);
        $this->formatSheet($sheet, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], count($dataAbsen) + 7);

        $label    = label_sesi($sesi['nama_sesi']);
        $namaFile = 'Rekap_' . $this->namaAman($label) . '_' . $rat['tahun_buku'] . '_' . date('Ymd_His') . '.xlsx';
        $this->catatLog("Download rekap {$label} RAT {$rat['tahun_buku']}");

        return ExcelExport::unduh($spreadsheet, $namaFile);
    }

    /**
     * F-07/F-15: Download rekap gabungan semua sesi dalam 1 file Excel
     * dengan 4 sheet: Sesi Pagi, Sesi Siang, Sesi Sore, dan Summary.
     *
     * Sheet Summary (F-15): otomatis menandai kolom "Eligible Doorprize"
     * berdasarkan kehadiran di SEMUA sesi. Tidak perlu input manual.
     */
    public function downloadGabungan()
    {
        $idRat = $this->idRatDipilih();

        if (! $idRat) {
            return redirect()->to('/rekap')->with('error', 'Pilih RAT terlebih dahulu.');
        }

        $rat        = $this->ratModel->find($idRat);
        $daftarSesi = $this->sesiModel->getByRat($idRat);

        if (empty($daftarSesi)) {
            return redirect()->to('/rekap')->with('error', 'Tidak ada sesi untuk RAT ini.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // hapus sheet default kosong

        // Buat sheet per sesi
        $judulDipakai = ['summary doorprize' => true]; // nama sheet ringkasan dicadangkan
        foreach ($daftarSesi as $i => $sesi) {
            $sheet = $spreadsheet->createSheet($i);
            $judul = $this->judulSheet($sesi['nama_sesi']);
            for ($n = 2; isset($judulDipakai[mb_strtolower($judul)]); $n++) {
                $judul = mb_substr($this->judulSheet($sesi['nama_sesi']), 0, 25) . ' (' . $n . ')';
            }
            $judulDipakai[mb_strtolower($judul)] = true;
            $sheet->setTitle($judul);

            $dataAbsen = $this->absensiModel->getDetailBySesi((int) $sesi['id']);
            $this->tulisHeaderSesi($sheet, $rat, $sesi, count($dataAbsen));
            $this->tulisDataSesi($sheet, $dataAbsen, 8);
            $this->formatSheet($sheet, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], count($dataAbsen) + 7);
        }

        // Sheet Summary (F-15) — kriteria sama dengan halaman Doorprize: sesi yang punya daftar peserta
        $jumlahPeserta = $this->pesertaModel->hitungPerSesi($idRat);
        $sesiDipakai   = array_values(array_filter($daftarSesi, static fn ($s) => (int) ($jumlahPeserta[$s['id']] ?? 0) > 0));
        $sheetSummary  = $spreadsheet->createSheet(count($daftarSesi));
        $sheetSummary->setTitle('Summary Doorprize');
        $this->tulisSheetSummary($sheetSummary, $rat, $sesiDipakai, array_column($sesiDipakai, 'id'));

        // Aktifkan sheet pertama saat file dibuka
        $spreadsheet->setActiveSheetIndex(0);

        $namaFile = 'Rekap_Gabungan_RAT_' . $rat['tahun_buku'] . '_' . date('Ymd_His') . '.xlsx';

        $this->catatLog("Download rekap gabungan RAT {$rat['tahun_buku']} (" . count($daftarSesi) . ' sesi)');

        return ExcelExport::unduh($spreadsheet, $namaFile);
    }

    // ─────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────

    private function tulisHeaderSesi($sheet, array $rat, array $sesi, int $jumlahHadir): void
    {
        $sheet->setCellValue('A1', 'REKAP ABSENSI ' . strtoupper($rat['nama_rat']));
        $peserta = $this->pesertaModel->totalPesertaSesi((int) $sesi['id']);
        $kuorum  = hitung_kuorum($peserta);
        $status  = $peserta === 0 ? 'belum ada daftar peserta' : ($jumlahHadir >= $kuorum ? 'TERCAPAI' : 'belum tercapai');

        $sheet->setCellValue('A2', label_sesi($sesi['nama_sesi']) . ' · ' . substr($sesi['waktu_mulai'], 0, 5) . ' - ' . substr($sesi['waktu_selesai'], 0, 5) . ' WIB · ' . ucfirst($sesi['status']));
        $sheet->setCellValue('A3', 'Peserta sesi: ' . $peserta . ' orang');
        $sheet->setCellValue('A4', 'Kuorum (50% + 1): ' . $kuorum . ' orang — ' . $status);
        $sheet->setCellValue('A5', 'Jumlah Hadir: ' . $jumlahHadir . ' orang');
        $sheet->setCellValue('A6', 'Dicetak: ' . date('d/m/Y H:i:s'));

        ExcelExport::judul($sheet, ['No.', 'NIP', 'Nama Anggota', 'Departemen', 'Section', 'Shift', 'Waktu Absen', 'Metode'], 7, ExcelExport::NAVY);

        // Style header info
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A2', 'A3', 'A4', 'A5', 'A6'] as $cell) {
            $sheet->getStyle($cell)->getFont()->setSize(10);
        }

    }

    private function tulisDataSesi($sheet, array $dataAbsen, int $startRow): void
    {
        foreach ($dataAbsen as $i => $b) {
            $row = $startRow + $i;
            ExcelExport::baris($sheet, $row, [
                $i + 1, $b['nip'], $b['nama'], $b['departemen'] ?: '-', $b['section'] ?: '-', $b['shift'] ?: '-',
                date('d/m/Y H:i:s', strtotime($b['waktu_absen'])), strtoupper($b['metode']),
            ], $i % 2 === 1);
            foreach (['A', 'B', 'F', 'H'] as $kol) {
                $sheet->getStyle($kol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
    }

    private function tulisSheetSummary($sheet, array $rat, array $daftarSesi, array $idSesiList): void
    {
        // Header info
        $sheet->setCellValue('A1', 'SUMMARY DOORPRIZE — ' . strtoupper($rat['nama_rat']));
        $namaSesi = array_map(static fn ($s) => label_sesi($s['nama_sesi']), $daftarSesi);
        $sheet->setCellValue('A2', 'Kriteria: hadir di SELURUH sesi yang punya daftar peserta (' . ($namaSesi ? implode(', ', $namaSesi) : 'belum ada') . ')');
        $sheet->setCellValue('A3', 'Dicetak: ' . date('d/m/Y H:i:s'));

        $kolom = ['A', 'B', 'C'];
        // Kolom hadir per sesi (D, E, F, ...) + kolom Eligible Doorprize
        for ($i = 0; $i <= count($daftarSesi); $i++) {
            $kolom[] = Coordinate::stringFromColumnIndex(4 + $i);
        }

        $totalKolom = count($kolom);
        $lastKolom  = $kolom[$totalKolom - 1];

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->mergeCells('A1:' . $lastKolom . '1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header kolom tabel (baris 5)
        ExcelExport::judul($sheet, array_merge(['No.', 'NIP', 'Nama Anggota'], $namaSesi, ['Eligible Doorprize']), 5);

        // Data summary per anggota
        $dataAnggota = $this->absensiModel->getSummaryPerAnggota($idSesiList, (int) $rat['id']);
        foreach ($dataAnggota as $i => $baris) {
            $row = 6 + $i;
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValueExplicit('B' . $row, (string) $baris['nip'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, (string) $baris['nama'], DataType::TYPE_STRING);

            foreach ($daftarSesi as $j => $sesi) {
                $hadir = $baris['hadir_' . $sesi['id']] ?? false;
                $sel   = $kolom[3 + $j] . $row;
                $sheet->setCellValue($sel, $hadir ? '✓' : '—');
                $sheet->getStyle($sel)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                if ($hadir) {
                    $sheet->getStyle($sel)
                        ->getFont()->getColor()->setRGB('16a34a');
                }
            }

            // F-15: kolom Eligible Doorprize
            $eligible = $baris['eligible_doorprize'];
            $sheet->setCellValue($lastKolom . $row, $eligible ? 'YA' : 'Tidak');
            $sheet->getStyle($lastKolom . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($eligible) {
                $sheet->getStyle($lastKolom . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '16a34a']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f0fdf4']],
                ]);
            } else {
                $sheet->getStyle($lastKolom . $row)
                    ->getFont()->getColor()->setRGB('b3262b');
            }

            // Alternating row
            if ($i % 2 === 1) {
                $sheet->getStyle('A' . $row . ':C' . $row)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f7f7f7');
            }

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->formatSheet($sheet, array_slice($kolom, 0, -1), count($dataAnggota) + 5, $lastKolom);

        // Hitung total eligible doorprize
        $totalEligible = count(array_filter($dataAnggota, fn($b) => $b['eligible_doorprize']));
        $barisTotals   = 6 + count($dataAnggota) + 1;
        $sheet->setCellValue('A' . $barisTotals, 'Total Eligible Doorprize:');
        $sheet->setCellValue('C' . $barisTotals, $totalEligible . ' peserta');
        $sheet->getStyle('A' . $barisTotals . ':B' . $barisTotals)
            ->getFont()->setBold(true);
    }

    private function formatSheet($sheet, array $kolom, int $lastRow, ?string $lastKolom = null): void
    {
        $lk = $lastKolom ?? end($kolom);

        // Border mencakup baris header kolom (7) + seluruh baris data.
        // Sheet Summary pakai baris header 5, bukan 7.
        $headerRow = ($sheet->getTitle() === 'Summary Doorprize') ? 5 : 7;

        $sheet->getStyle('A' . $headerRow . ':' . $lk . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'e8e8e8'],
                ],
            ],
        ]);

        // Auto-width kolom
        foreach ($kolom as $k) {
            $sheet->getColumnDimension($k)->setAutoSize(true);
        }
        if ($lastKolom && ! in_array($lastKolom, $kolom)) {
            $sheet->getColumnDimension($lastKolom)->setAutoSize(true);
        }

        // Freeze tepat di bawah baris header kolom (baris data pertama
        // tetap terlihat saat scroll ke bawah).
        $sheet->freezePane('A' . ($headerRow + 1));
    }

    // ── Backup SQL ────────────────────────────────────────────────────────
    /**
     * Backup lengkap yang bisa di-import ulang lewat phpMyAdmin.
     * INSERT dipecah per 500 baris supaya tidak melebihi batas max_allowed_packet saat restore.
     */
    public function backupSql()
    {
        $db      = db_connect();
        $tables  = ['migrations', 'tb_users', 'tb_rat', 'tb_sesi', 'tb_anggota', 'tb_peserta_rat', 'tb_absensi', 'tb_doorprize_items', 'tb_doorprize_winners', 'tb_log_aktivitas'];
        $tanggal = date('Y-m-d_H-i-s');
        $out     = fopen('php://temp', 'w+');

        fwrite($out, "-- Backup Database Sistem Absensi RAT KPNI\n-- Tanggal: {$tanggal}\n-- Berisi data pribadi anggota, simpan dengan aman.\n\n"
            . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT'); // semua tabel diambil dari satu titik waktu
        foreach ($tables as $table) {
            if (! $db->tableExists($table)) continue;
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            fwrite($out, "-- Tabel: {$table}\nDROP TABLE IF EXISTS `{$table}`;\n" . $create['Create Table'] . ";\n\n");

            $kolomUrut = $db->getFieldNames($table)[0];
            for ($offset = 0; ; $offset += 500) {
                $rows = $db->table($table)->orderBy($kolomUrut, 'ASC')->limit(500, $offset)->get()->getResultArray();
                if ($rows === []) break;
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $vals = array_map(static fn ($row) => '(' . implode(', ', array_map(static fn ($v) => $v === null ? 'NULL' : $db->escape((string) $v), $row)) . ')', $rows);
                fwrite($out, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $vals) . ";\n");
                if (count($rows) < 500) break;
            }
            fwrite($out, "\n");
        }
        $db->query('COMMIT');
        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        rewind($out);
        $sql = stream_get_contents($out);
        fclose($out);

        $this->catatLog('Download backup database (SQL)');

        return $this->response
            ->setHeader('Content-Type', 'application/sql; charset=utf-8')
            ->setHeader('Content-Disposition', "attachment; filename=\"backup_kpni_{$tanggal}.sql\"")
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($sql);
    }

    // ── Backup Excel ──────────────────────────────────────────────────────
    public function backupExcel()
    {
        $db      = \Config\Database::connect();
        $tables  = [
            'tb_rat'           => 'RAT',
            'tb_sesi'          => 'Sesi',
            'tb_anggota'       => 'Anggota',
            'tb_peserta_rat'   => 'Peserta RAT',
            'tb_absensi'       => 'Absensi',
            'tb_doorprize_items'   => 'Doorprize Barang',
            'tb_doorprize_winners' => 'Doorprize Pemenang',
            'tb_users'         => 'Users',
            'tb_log_aktivitas' => 'Log Aktivitas',
        ];
        $tanggal    = date('Y-m-d_H-i-s');
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($tables as $table => $sheetName) {
            $builder = $db->table($table);
            if ($table === 'tb_users') {
                $builder->select('id, username, role, nama_lengkap, created_at, updated_at'); // hash password tidak ikut
            }
            $rows  = $builder->get()->getResultArray();
            $sheet = $spreadsheet->createSheet()->setTitle($sheetName);

            if ($rows === []) {
                $sheet->setCellValue('A1', 'Tidak ada data');
                continue;
            }
            // Semua sel backup ditulis sebagai teks apa adanya
            ExcelExport::tabel($sheet, array_keys($rows[0]), array_map(
                static fn ($r) => array_map(static fn ($v) => $v === null ? '' : (string) $v, array_values($r)), $rows
            ), ExcelExport::NAVY);
        }

        $this->catatLog('Download backup database (Excel)');

        return ExcelExport::unduh($spreadsheet, "backup_kpni_{$tanggal}.xlsx");
    }

    /**
     * Export foto absensi per sesi dalam ZIP.
     */
    public function exportFoto($idSesi)
    {
        $sesi = $this->sesiModel->find($idSesi);
        if (! $sesi || (int) $sesi['id_rat'] !== $this->idRatDipilih()) return redirect()->to('/rekap')->with('error', 'Sesi tidak ditemukan.');

        $rat = $this->ratModel->find($sesi['id_rat']);

        $rows = db_connect()->query(
            "SELECT ab.nip_snapshot, ab.nama_snapshot, ab.foto_path FROM tb_absensi ab
             JOIN tb_peserta_rat p ON p.id_sesi = ab.id_sesi AND p.nip = ab.nip_snapshot
             WHERE ab.id_sesi = ? AND ab.foto_path IS NOT NULL AND ab.foto_path != ''",
            [$idSesi]
        )->getResultArray();

        if (empty($rows)) {
            return redirect()->to('/rekap')->with('error', 'Tidak ada foto absensi di sesi ini.');
        }

        $label   = label_sesi($sesi['nama_sesi']);
        $zipName = 'Foto_Absensi_' . $this->namaAman($label) . '_' . ($rat['tahun_buku'] ?? '') . '.zip';
        if (! is_dir(WRITEPATH . 'tmp/')) mkdir(WRITEPATH . 'tmp/', 0755, true);
        $zipPath = WRITEPATH . 'tmp/foto_' . bin2hex(random_bytes(6)) . '.zip'; // nama unik: aman kalau dua admin mengunduh bersamaan

        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return redirect()->to('/rekap')->with('error', 'Gagal membuat file ZIP.');
            }
            $folder = $this->namaAman($label);
            $dipakai = [];
            $jumlah  = 0;
            foreach ($rows as $row) {
                $filePath = FCPATH . $row['foto_path'];
                if (! str_starts_with($row['foto_path'], 'uploads/absensi-foto/') || ! is_file($filePath)) continue;
                $dasar = trim(($row['nip_snapshot'] ?: 'tanpa-nip') . '_' . $this->namaAman((string) $row['nama_snapshot']), '_');
                $nama  = $dasar;
                for ($n = 2; isset($dipakai[$nama]); $n++) $nama = $dasar . '_' . $n;
                $dipakai[$nama] = true;
                $zip->addFile($filePath, $folder . '/' . $nama . '.' . pathinfo($filePath, PATHINFO_EXTENSION));
                $jumlah++;
            }
            $zip->close();

            if ($jumlah === 0 || ! is_file($zipPath)) {
                return redirect()->to('/rekap')->with('error', 'File foto tidak ditemukan di server.');
            }
            $isi = file_get_contents($zipPath);
        } finally {
            if (is_file($zipPath)) @unlink($zipPath);
        }
        $this->catatLog("Download foto absensi {$label} ({$jumlah} foto)");

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipName . '"')
            ->setBody($isi);
    }

    /**
     * Judul sheet Excel: maks. 31 karakter, tanpa karakter terlarang.
     */
    private function judulSheet(string $namaSesi): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', label_sesi($namaSesi)), 0, 31);
    }

    private function namaAman(string $teks): string
    {
        return trim(preg_replace('/[^A-Za-z0-9]+/', '_', $teks), '_');
    }
}
