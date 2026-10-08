<?php

namespace App\Controllers;

use App\Models\AbsensiModel;
use App\Models\AnggotaModel;
use App\Models\RatModel;
use App\Models\SesiModel;
use App\Models\PesertaRatModel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $idRat = session()->get('rat_dipilih');
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
        $idRat = session()->get('rat_dipilih');

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

        return $this->outputExcel($spreadsheet, $namaFile);
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
        $idRat = session()->get('rat_dipilih');

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

        $idSesiList = array_column($daftarSesi, 'id');

        // Buat sheet per sesi
        $judulDipakai = [];
        foreach ($daftarSesi as $i => $sesi) {
            $sheet = $spreadsheet->createSheet($i);
            $judul = $this->judulSheet($sesi['nama_sesi']);
            if (isset($judulDipakai[strtolower($judul)])) {
                $judul = mb_substr($judul, 0, 26) . ' (' . ($i + 1) . ')';
            }
            $judulDipakai[strtolower($judul)] = true;
            $sheet->setTitle($judul);

            $dataAbsen = $this->absensiModel->getDetailBySesi((int) $sesi['id']);
            $this->tulisHeaderSesi($sheet, $rat, $sesi, count($dataAbsen));
            $this->tulisDataSesi($sheet, $dataAbsen, 8);
            $this->formatSheet($sheet, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], count($dataAbsen) + 7);
        }

        // Sheet Summary (F-15)
        $sheetSummary = $spreadsheet->createSheet(count($daftarSesi));
        $sheetSummary->setTitle('Summary Doorprize');
        $this->tulisSheetSummary($sheetSummary, $rat, $daftarSesi, $idSesiList);

        // Aktifkan sheet pertama saat file dibuka
        $spreadsheet->setActiveSheetIndex(0);

        $namaFile = 'Rekap_Gabungan_RAT_' . $rat['tahun_buku'] . '_' . date('Ymd_His') . '.xlsx';

        $this->catatLog("Download rekap gabungan RAT {$rat['tahun_buku']} (" . count($daftarSesi) . ' sesi)');

        return $this->outputExcel($spreadsheet, $namaFile);
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

        // Header kolom tabel (baris 7) — ditambah Departemen, Section, Shift
        $sheet->setCellValue('A7', 'No.');
        $sheet->setCellValue('B7', 'NIP');
        $sheet->setCellValue('C7', 'Nama Anggota');
        $sheet->setCellValue('D7', 'Departemen');
        $sheet->setCellValue('E7', 'Section');
        $sheet->setCellValue('F7', 'Shift');
        $sheet->setCellValue('G7', 'Waktu Absen');
        $sheet->setCellValue('H7', 'Metode');

        // Style header info
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A2', 'A3', 'A4', 'A5', 'A6'] as $cell) {
            $sheet->getStyle($cell)->getFont()->setSize(10);
        }

        // Style header kolom
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '022760']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A7:H7')->applyFromArray($headerStyle);
    }

    private function tulisDataSesi($sheet, array $dataAbsen, int $startRow): void
    {
        foreach ($dataAbsen as $i => $baris) {
            $row = $startRow + $i;
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $baris['nip']);
            $sheet->setCellValue('C' . $row, $baris['nama']);
            $sheet->setCellValue('D' . $row, $baris['departemen'] ?? '-');
            $sheet->setCellValue('E' . $row, $baris['section']    ?? '-');
            $sheet->setCellValue('F' . $row, $baris['shift']      ?? '-');
            $sheet->setCellValue('G' . $row, date('d/m/Y H:i:s', strtotime($baris['waktu_absen'])));
            $sheet->setCellValue('H' . $row, strtoupper($baris['metode']));

            // Warna alternating baris
            if ($i % 2 === 1) {
                $sheet->getStyle('A' . $row . ':H' . $row)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0F7FF');
            }

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function tulisSheetSummary($sheet, array $rat, array $daftarSesi, array $idSesiList): void
    {
        // Header info
        $sheet->setCellValue('A1', 'SUMMARY DOORPRIZE — ' . strtoupper($rat['nama_rat']));
        $namaSesi = array_map(static fn ($s) => label_sesi($s['nama_sesi']), $daftarSesi);
        $sheet->setCellValue('A2', 'Kriteria: hadir di SELURUH sesi (' . implode(', ', $namaSesi) . ')');
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
        $sheet->setCellValue('A5', 'No.');
        $sheet->setCellValue('B5', 'NIP');
        $sheet->setCellValue('C5', 'Nama Anggota');
        foreach ($daftarSesi as $i => $sesi) {
            $sheet->setCellValue($kolom[3 + $i] . '5', label_sesi($sesi['nama_sesi']));
        }
        $sheet->setCellValue($lastKolom . '5', 'Eligible Doorprize');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '024ad8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A5:' . $lastKolom . '5')->applyFromArray($headerStyle);

        // Data summary per anggota
        $dataAnggota = $this->absensiModel->getSummaryPerAnggota($idSesiList, (int) $rat['id']);
        foreach ($dataAnggota as $i => $baris) {
            $row = 6 + $i;
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $baris['nip']);
            $sheet->setCellValue('C' . $row, $baris['nama']);

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

    private function outputExcel(Spreadsheet $spreadsheet, string $namaFile): \CodeIgniter\HTTP\ResponseInterface
    {
        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    // ── Backup SQL ────────────────────────────────────────────────────────
    public function backupSql()
    {
        $db     = \Config\Database::connect();
        $tables = ['tb_rat', 'tb_sesi', 'tb_anggota', 'tb_peserta_rat', 'tb_absensi', 'tb_doorprize_items', 'tb_doorprize_winners', 'tb_users', 'tb_log_aktivitas'];
        $tanggal = date('Y-m-d_H-i-s');
        $sql    = "-- Backup Database KPNI\n-- Tanggal: {$tanggal}\n-- Sistem Absensi RAT KPNI\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // DROP + CREATE
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->getResultArray();
            if (empty($create)) continue;
            $createSql = $create[0]['Create Table'];
            $sql .= "-- Tabel: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSql . ";\n\n";

            // INSERT data
            $rows = $db->table($table)->get()->getResultArray();
            if (!empty($rows)) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
                $vals = [];
                foreach ($rows as $row) {
                    $escaped = array_map(static fn ($v) => $v === null ? 'NULL' : $db->escape((string) $v), $row);
                    $vals[] = '(' . implode(', ', $escaped) . ')';
                }
                $sql .= implode(",\n", $vals) . ";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $this->catatLog('Download backup database (SQL)');

        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', "attachment; filename=\"backup_kpni_{$tanggal}.sql\"")
            ->setHeader('Cache-Control', 'max-age=0')
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
            $rows = $db->table($table)->get()->getResultArray();
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            if (empty($rows)) {
                $sheet->setCellValue('A1', 'Tidak ada data');
                continue;
            }

            // Header
            $headers = array_keys($rows[0]);
            foreach ($headers as $i => $h) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($col . '1', $h);
            }
            $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')
                ->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '022760']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

            // Data
            foreach ($rows as $r => $row) {
                foreach (array_values($row) as $c => $val) {
                    $col = Coordinate::stringFromColumnIndex($c + 1);
                    $sheet->setCellValue($col . ($r + 2), $val);
                }
                if ($r % 2 === 1) {
                    $sheet->getStyle('A' . ($r+2) . ':' . Coordinate::stringFromColumnIndex(count($headers)) . ($r+2))
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F7FF');
                }
            }

            foreach (range(1, count($headers)) as $i) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }
        }

        $this->catatLog('Download backup database (Excel)');

        return $this->outputExcel($spreadsheet, "backup_kpni_{$tanggal}.xlsx");
    }

    /**
     * Export foto absensi per sesi dalam ZIP.
     */
    public function exportFoto($idSesi)
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');

        $sesi = $this->sesiModel->find($idSesi);
        if (! $sesi || (int) $sesi['id_rat'] !== $this->idRatDipilih()) return redirect()->to('/rekap')->with('error', 'Sesi tidak ditemukan.');

        $rat = $this->ratModel->find($sesi['id_rat']);

        $rows = db_connect()->query(
            "SELECT nip_snapshot, nama_snapshot, foto_path FROM tb_absensi WHERE id_sesi = ? AND foto_path IS NOT NULL AND foto_path != ''",
            [$idSesi]
        )->getResultArray();

        if (empty($rows)) {
            return redirect()->to('/rekap')->with('error', 'Tidak ada foto absensi di sesi ini.');
        }

        $zip = new \ZipArchive();
        $label   = label_sesi($sesi['nama_sesi']);
        $zipName = 'Foto_Absensi_' . $this->namaAman($label) . '_' . ($rat['tahun_buku'] ?? '') . '.zip';
        $zipPath = WRITEPATH . 'tmp/' . $zipName;
        if (!is_dir(WRITEPATH . 'tmp/')) mkdir(WRITEPATH . 'tmp/', 0755, true);

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->to('/rekap')->with('error', 'Gagal membuat file ZIP.');
        }

        $folderName = $this->namaAman($label);
        foreach ($rows as $row) {
            $filePath = FCPATH . $row['foto_path'];
            if (file_exists($filePath)) {
                $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                $fileName = $row['nip_snapshot'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $row['nama_snapshot']) . '.' . $ext;
                $zip->addFile($filePath, $folderName . '/' . $fileName);
            }
        }
        $zip->close();

        // File ZIP sementara langsung dihapus setelah dibaca (dulu menumpuk di writable/tmp)
        $isi = is_file($zipPath) ? file_get_contents($zipPath) : '';
        @unlink($zipPath);
        if ($isi === '') {
            return redirect()->to('/rekap')->with('error', 'File foto tidak ditemukan di server.');
        }
        $this->catatLog("Download foto absensi {$label}");

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
