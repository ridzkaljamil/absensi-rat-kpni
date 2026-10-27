<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Helper bersama untuk semua export Excel (rekap, peserta, anggota, doorprize, backup).
 *
 * Semua teks ditulis sebagai string eksplisit: NIP/UID tetap berawalan 0,
 * dan nama yang diawali "=" tidak berubah menjadi rumus.
 */
final class ExcelExport
{
    public const BIRU      = '0960A8';
    public const NAVY      = '022760';
    public const ZEBRA     = 'F0F7FF';

    /**
     * Tulis baris judul kolom bergaya (teks putih, latar berwarna, rata tengah).
     */
    public static function judul(Worksheet $sheet, array $judul, int $baris = 1, string $warna = self::BIRU): void
    {
        self::baris($sheet, $baris, $judul);
        $sheet->getStyle('A' . $baris . ':' . self::kolom(count($judul)) . $baris)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $warna]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    /**
     * Tulis satu baris data mulai kolom A. Angka (int/float) tetap angka, sisanya string.
     */
    public static function baris(Worksheet $sheet, int $baris, array $nilai, bool $zebra = false): void
    {
        $i = 1;
        foreach ($nilai as $v) {
            $sel = self::kolom($i++) . $baris;
            if (is_int($v) || is_float($v)) {
                $sheet->setCellValue($sel, $v);
            } else {
                $sheet->setCellValueExplicit($sel, (string) ($v ?? ''), DataType::TYPE_STRING);
            }
        }
        if ($zebra) {
            $sheet->getStyle('A' . $baris . ':' . self::kolom(count($nilai)) . $baris)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::ZEBRA);
        }
    }

    /**
     * Tabel sederhana: judul di baris 1, data mulai baris 2 (zebra), lebar kolom otomatis.
     *
     * @param list<array> $data setiap elemen = daftar nilai per kolom (urut sesuai $judul)
     */
    public static function tabel(Worksheet $sheet, array $judul, array $data, string $warna = self::BIRU): void
    {
        self::judul($sheet, $judul, 1, $warna);
        foreach (array_values($data) as $i => $nilai) {
            self::baris($sheet, $i + 2, $nilai, $i % 2 === 1);
        }
        self::lebarOtomatis($sheet, count($judul));
    }

    public static function lebarOtomatis(Worksheet $sheet, int $jumlahKolom): void
    {
        for ($i = 1; $i <= $jumlahKolom; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

    public static function kolom(int $indeks): string
    {
        return Coordinate::stringFromColumnIndex($indeks);
    }

    /**
     * Response unduhan .xlsx.
     */
    public static function unduh(Spreadsheet $spreadsheet, string $namaFile): ResponseInterface
    {
        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        $isi = ob_get_clean();

        return service('response')
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($isi);
    }
}
