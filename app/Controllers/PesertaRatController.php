<?php

namespace App\Controllers;

use App\Models\AbsensiModel;
use App\Models\AnggotaModel;
use App\Models\PesertaRatModel;
use App\Models\RatModel;
use App\Models\SesiModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Peserta RAT per sesi. Setiap sesi punya daftar peserta sendiri
 * (import Excel per sesi, atau salin dari kehadiran sesi sebelumnya).
 */
class PesertaRatController extends BaseController
{
    protected PesertaRatModel $pesertaModel;
    protected AnggotaModel $anggotaModel;
    protected RatModel $ratModel;
    protected SesiModel $sesiModel;

    public function __construct()
    {
        $this->pesertaModel = new PesertaRatModel();
        $this->anggotaModel = new AnggotaModel();
        $this->ratModel     = new RatModel();
        $this->sesiModel    = new SesiModel();
    }

    public function index()
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return redirect()->to('/rat')->with('error', 'Pilih RAT terlebih dahulu.');
        }

        $daftarSesi = $this->sesiModel->getByRat($idRat);
        if (empty($daftarSesi)) {
            return redirect()->to('/sesi')->with('error', 'Belum ada sesi. Buat sesi terlebih dahulu.');
        }

        // Sesi yang dipilih (default: sesi pertama)
        $idSesi    = (int) ($this->request->getGet('sesi') ?? 0);
        $sesiAktif = $daftarSesi[0];
        foreach ($daftarSesi as $s) {
            if ((int) $s['id'] === $idSesi) {
                $sesiAktif = $s;
                break;
            }
        }
        $idSesi = (int) $sesiAktif['id'];

        $filter = [
            'search'     => trim((string) $this->request->getGet('search')),
            'departemen' => (string) $this->request->getGet('departemen'),
            'section'    => (string) $this->request->getGet('section'),
            'shift'      => (string) $this->request->getGet('shift'),
            'rfid'       => (string) $this->request->getGet('rfid'),
        ];

        $builder = $this->pesertaModel->where('id_sesi', $idSesi);
        if ($filter['search'] !== '') {
            $builder->groupStart()
                ->like('nip', $filter['search'])->orLike('nama', $filter['search'])->orLike('departemen', $filter['search'])
                ->groupEnd();
        }
        foreach (['departemen', 'section', 'shift'] as $kolom) {
            if ($filter[$kolom] !== '') $builder->where($kolom, $filter[$kolom]);
        }
        if ($filter['rfid'] === 'ada') {
            $builder->where('uid_rfid IS NOT NULL')->where('uid_rfid !=', '');
        } elseif ($filter['rfid'] === 'belum') {
            $builder->groupStart()->where('uid_rfid IS NULL')->orWhere('uid_rfid', '')->groupEnd();
        }

        $perPage = 20;
        $peserta = $builder->orderBy('nama', 'ASC')->paginate($perPage);
        $pager   = $this->pesertaModel->pager;

        $db        = db_connect();
        $totalRfid = $db->table('tb_peserta_rat')
            ->where('id_sesi', $idSesi)->where('uid_rfid IS NOT NULL')->where('uid_rfid !=', '')
            ->countAllResults();

        $pesertaPerSesi = $this->pesertaModel->hitungPerSesi($idRat);
        $total          = (int) ($pesertaPerSesi[$idSesi] ?? 0);

        // Sesi sebelumnya + jumlah hadirnya (untuk tombol "Salin Kehadiran")
        $sesiSebelumnya = $this->sesiModel->getSesiSebelumnya($idSesi);
        $hadirSebelumnya = $sesiSebelumnya ? (new AbsensiModel())->countBySesi((int) $sesiSebelumnya['id']) : 0;

        return view('peserta_rat/index', [
            'rat'             => $this->ratModel->find($idRat),
            'daftarSesi'      => $daftarSesi,
            'sesiAktif'       => $sesiAktif,
            'idSesi'          => $idSesi,
            'peserta'         => $peserta,
            'pager'           => $pager,
            'nomorAwal'       => ($pager->getCurrentPage() - 1) * $perPage,
            'total'           => $total,
            'kuorum'          => hitung_kuorum($total),
            'totalRfid'       => $totalRfid,
            'pesertaPerSesi'  => $pesertaPerSesi,
            'filter'          => $filter,
            'adaFilter'       => implode('', $filter) !== '',
            'deptList'        => $this->daftarUnik('departemen'),
            'sectionList'     => $this->daftarUnik('section'),
            'sesiSebelumnya'  => $sesiSebelumnya,
            'hadirSebelumnya' => $hadirSebelumnya,
        ]);
    }

    public function import()
    {
        $idRat = $this->idRatDipilih();
        $sesi  = $this->sesiMilikRat((int) $this->request->getPost('id_sesi'));
        if (! $sesi) {
            return redirect()->to('/peserta-rat')->with('error', 'Sesi tidak valid.');
        }
        $idSesi  = (int) $sesi['id'];
        $kembali = '/peserta-rat?sesi=' . $idSesi;

        $file = $this->request->getFile('file_excel');
        if (! $file || ! $file->isValid()) {
            return redirect()->to($kembali)->with('error', 'File tidak valid.');
        }
        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls', 'csv'], true)) {
            return redirect()->to($kembali)->with('error', 'Format file harus .xlsx / .xls / .csv');
        }

        try {
            $rows = IOFactory::load($file->getTempName())->getActiveSheet()->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return redirect()->to($kembali)->with('error', 'Gagal baca file: ' . $e->getMessage());
        }

        // Petakan kolom berdasarkan judul header (baris 1)
        $colMap = [];
        foreach ($rows[1] ?? [] as $col => $val) {
            $val = strtolower(trim((string) $val));
            foreach (['nip' => 'nip', 'nama' => 'nama', 'departemen' => 'departemen', 'section' => 'section', 'shift' => 'shift', 'rfid' => 'uid_rfid'] as $kunci => $field) {
                if (! isset($colMap[$field]) && str_contains($val, $kunci)) {
                    $colMap[$field] = $col;
                }
            }
        }
        if (empty($colMap['nip']) || empty($colMap['nama'])) {
            return redirect()->to($kembali)->with('error', 'Kolom NIP dan Nama wajib ada di header Excel.');
        }

        $data = [];
        foreach ($rows as $i => $row) {
            if ($i === 1) continue;
            $nip = trim((string) ($row[$colMap['nip']] ?? ''));
            if ($nip === '') continue;
            $baris = ['nip' => $nip];
            foreach (['nama', 'departemen', 'section', 'shift', 'uid_rfid'] as $field) {
                $baris[$field] = isset($colMap[$field]) ? trim((string) ($row[$colMap[$field]] ?? '')) : '';
            }
            $data[] = $baris;
        }
        if ($data === []) {
            return redirect()->to($kembali)->with('error', 'Tidak ada data valid di file.');
        }

        $hasil = $this->pesertaModel->importBulkSesi($idRat, $idSesi, $data);

        // Lengkapi departemen/section/shift yang kosong dari data induk
        db_connect()->query(
            'UPDATE tb_peserta_rat p JOIN tb_anggota a ON p.nip = a.nip
             SET p.departemen = COALESCE(NULLIF(p.departemen, ""), a.departemen),
                 p.section    = COALESCE(NULLIF(p.section, ""), a.section),
                 p.shift      = COALESCE(NULLIF(p.shift, ""), a.shift)
             WHERE p.id_sesi = ?',
            [$idSesi]
        );

        $label = label_sesi($sesi['nama_sesi']);
        $this->catatLog("Import peserta {$label}: {$hasil['inserted']} baru, {$hasil['updated']} diperbarui, {$hasil['skipped']} dilewati");

        return redirect()->to($kembali)->with('success', "Import {$label} berhasil: {$hasil['inserted']} peserta baru, {$hasil['updated']} diperbarui.");
    }

    /**
     * Salin peserta dari KEHADIRAN sesi sebelumnya.
     */
    public function salinKehadiran()
    {
        $sesi = $this->sesiMilikRat((int) $this->request->getPost('id_sesi'));
        if (! $sesi) {
            return redirect()->to('/peserta-rat')->with('error', 'Sesi tidak valid.');
        }
        $idSesi  = (int) $sesi['id'];
        $kembali = '/peserta-rat?sesi=' . $idSesi;

        $sumber = $this->sesiModel->getSesiSebelumnya($idSesi);
        if (! $sumber) {
            return redirect()->to($kembali)->with('error', 'Tidak ada sesi sebelumnya.');
        }

        $hasil = $this->pesertaModel->salinDariKehadiran($this->idRatDipilih(), $idSesi, (int) $sumber['id']);
        if ($hasil['total_hadir'] === 0) {
            return redirect()->to($kembali)->with('error', 'Belum ada yang absen di ' . label_sesi($sumber['nama_sesi']) . ', tidak ada yang bisa disalin.');
        }

        $dari = label_sesi($sumber['nama_sesi']);
        $ke   = label_sesi($sesi['nama_sesi']);
        $this->catatLog("Salin kehadiran {$dari} ke {$ke}: {$hasil['inserted']} ditambah, {$hasil['skipped']} sudah ada");

        return redirect()->to($kembali)->with('success', "{$hasil['inserted']} peserta disalin dari kehadiran {$dari} ({$hasil['skipped']} sudah ada sebelumnya).");
    }

    public function store()
    {
        $sesi = $this->sesiMilikRat((int) $this->request->getPost('id_sesi'));
        if (! $sesi) {
            return redirect()->to('/peserta-rat')->with('error', 'Pilih sesi terlebih dahulu.');
        }
        $idSesi  = (int) $sesi['id'];
        $kembali = '/peserta-rat?sesi=' . $idSesi;
        $nip     = trim((string) $this->request->getPost('nip'));
        $nama    = trim((string) $this->request->getPost('nama'));

        if ($nip === '' || $nama === '') {
            return redirect()->to($kembali)->with('error', 'NIP dan Nama wajib diisi.');
        }
        if ($this->pesertaModel->nipTerdaftarSesi($idSesi, $nip)) {
            return redirect()->to($kembali)->with('error', "NIP {$nip} sudah terdaftar di sesi ini.");
        }

        // Kartu yang sudah terdaftar di sesi lain ikut dipakai
        $rfid = $this->pesertaModel->where('id_rat', $this->idRatDipilih())->where('nip', $nip)
            ->where('uid_rfid IS NOT NULL')->first()['uid_rfid'] ?? null;

        $this->pesertaModel->insert([
            'id_rat'     => $this->idRatDipilih(),
            'id_sesi'    => $idSesi,
            'nip'        => $nip,
            'nama'       => $nama,
            'departemen' => trim((string) $this->request->getPost('departemen')) ?: null,
            'section'    => trim((string) $this->request->getPost('section')) ?: null,
            'shift'      => trim((string) $this->request->getPost('shift')) ?: null,
            'uid_rfid'   => $rfid,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->catatLog('Tambah peserta ' . label_sesi($sesi['nama_sesi']) . ": NIP {$nip}");

        return redirect()->to($kembali)->with('success', "Peserta NIP {$nip} berhasil ditambahkan.");
    }

    public function daftarRfid($id)
    {
        $peserta = $this->pesertaModel->find($id);
        if (! $peserta || (int) $peserta['id_rat'] !== $this->idRatDipilih()) {
            return redirect()->to('/peserta-rat')->with('error', 'Peserta tidak ditemukan.');
        }
        $kembali = '/peserta-rat?sesi=' . $peserta['id_sesi'];

        $uid = trim((string) $this->request->getPost('uid_rfid'));
        if ($uid === '') {
            return redirect()->to($kembali)->with('error', 'UID kartu tidak boleh kosong.');
        }

        $pesan = $this->pasangRfid($peserta, $uid);
        if ($pesan !== null) {
            return redirect()->to($kembali)->with('error', $pesan);
        }

        return redirect()->to($kembali)->with('success', "RFID berhasil didaftarkan untuk {$peserta['nama']} (berlaku di semua sesi).");
    }

    public function hapus($id)
    {
        $peserta = $this->pesertaModel->find($id);
        if (! $peserta || (int) $peserta['id_rat'] !== $this->idRatDipilih()) {
            return redirect()->to('/peserta-rat')->with('error', 'Peserta tidak ditemukan.');
        }

        $this->pesertaModel->delete($id);
        $this->catatLog("Hapus peserta RAT: NIP {$peserta['nip']} - {$peserta['nama']} (sesi {$peserta['id_sesi']})");

        return redirect()->to('/peserta-rat?sesi=' . $peserta['id_sesi'])->with('success', "{$peserta['nama']} dihapus dari daftar peserta sesi ini.");
    }

    public function hapusSemua()
    {
        $sesi = $this->sesiMilikRat((int) $this->request->getPost('id_sesi'));
        if (! $sesi) {
            return redirect()->to('/peserta-rat')->with('error', 'Sesi tidak valid.');
        }

        $total = $this->pesertaModel->totalPesertaSesi((int) $sesi['id']);
        $this->pesertaModel->where('id_sesi', $sesi['id'])->delete();

        $label = label_sesi($sesi['nama_sesi']);
        $this->catatLog("Hapus semua peserta {$label} ({$total} peserta)");

        return redirect()->to('/peserta-rat?sesi=' . $sesi['id'])->with('success', "Semua {$total} peserta {$label} berhasil dihapus.");
    }

    public function export()
    {
        $idRat = $this->idRatDipilih();
        $rat   = $this->ratModel->find($idRat);
        $sesi  = $this->sesiMilikRat((int) ($this->request->getGet('sesi') ?? 0));

        if ($sesi) {
            $peserta   = $this->pesertaModel->getBySesi((int) $sesi['id']);
            $labelSesi = label_sesi($sesi['nama_sesi']);
        } else {
            $peserta   = $this->pesertaModel->getUnikByRat($idRat);
            $labelSesi = 'Semua Sesi';
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Peserta RAT');

        $sheet->fromArray(['No.', 'NIP', 'Nama', 'Departemen', 'Section', 'Shift', 'RFID'], null, 'A1');
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0960A8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        foreach ($peserta as $i => $p) {
            $row = $i + 2;
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValueExplicit('B' . $row, $p['nip'], DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $p['nama']);
            $sheet->setCellValue('D' . $row, $p['departemen'] ?? '');
            $sheet->setCellValue('E' . $row, $p['section'] ?? '');
            $sheet->setCellValue('F' . $row, $p['shift'] ?? '');
            $sheet->setCellValueExplicit('G' . $row, (string) ($p['uid_rfid'] ?? ''), DataType::TYPE_STRING);
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F7FF');
            }
        }
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        $content = ob_get_clean();

        $filename = 'Peserta_RAT_' . ($rat['tahun_buku'] ?? '') . '_' . preg_replace('/[^A-Za-z0-9]+/', '_', $labelSesi) . '_' . date('Ymd_His') . '.xlsx';
        $this->catatLog("Export peserta RAT {$labelSesi} (" . count($peserta) . ' peserta)');

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    /**
     * Halaman daftar RFID massal — per NIP unik di RAT ini
     * (satu kartu otomatis berlaku untuk semua sesi).
     */
    public function bulkRfid()
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return redirect()->to('/rat');
        }

        $semua = $this->pesertaModel->getUnikByRat($idRat);
        $punya = [];
        foreach (db_connect()->query(
            'SELECT DISTINCT nip FROM tb_peserta_rat WHERE id_rat = ? AND uid_rfid IS NOT NULL AND uid_rfid <> ""',
            [$idRat]
        )->getResultArray() as $r) {
            $punya[$r['nip']] = true;
        }
        $belum = array_values(array_filter($semua, static fn ($p) => ! isset($punya[$p['nip']])));

        return view('peserta_rat/bulk_rfid', [
            'rat'       => $this->ratModel->find($idRat),
            'peserta'   => $belum,
            'totalDone' => count($semua) - count($belum),
            'totalAll'  => count($semua),
            'mode'      => 'peserta',
            'backUrl'   => '/peserta-rat',
        ]);
    }

    /**
     * API: simpan RFID dari halaman bulk.
     */
    public function bulkRfidSave()
    {
        $peserta = $this->pesertaModel->find((int) $this->request->getPost('id'));
        $uid     = trim((string) $this->request->getPost('uid_rfid'));

        if (! $peserta || $uid === '' || (int) $peserta['id_rat'] !== $this->idRatDipilih()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $pesan = $this->pasangRfid($peserta, $uid);
        if ($pesan !== null) {
            return $this->response->setJSON(['status' => 'error', 'message' => $pesan]);
        }

        return $this->response->setJSON(['status' => 'success', 'nama' => $peserta['nama'], 'nip' => $peserta['nip']]);
    }

    public function lookupNip()
    {
        $nip     = trim((string) $this->request->getGet('nip'));
        $anggota = $nip !== '' ? $this->anggotaModel->findByPrNumber($nip) : null;
        if (! $anggota) {
            return $this->response->setJSON(['found' => false]);
        }

        return $this->response->setJSON([
            'found'      => true,
            'nama'       => $anggota['nama'],
            'departemen' => $anggota['departemen'] ?? '',
            'section'    => $anggota['section'] ?? '',
            'shift'      => $anggota['shift'] ?? '',
        ]);
    }

    // ─────────────────────────────────────────────
    //  Helper
    // ─────────────────────────────────────────────

    /**
     * Sesi berdasarkan id, hanya kalau milik RAT yang sedang dipilih.
     */
    private function sesiMilikRat(int $idSesi): ?array
    {
        if (! $idSesi) return null;
        $sesi = $this->sesiModel->find($idSesi);

        return ($sesi && (int) $sesi['id_rat'] === $this->idRatDipilih()) ? $sesi : null;
    }

    /**
     * Pasang UID ke peserta (semua sesi) + sinkron ke data induk.
     * Return null kalau berhasil, atau pesan error.
     */
    private function pasangRfid(array $peserta, string $uid): ?string
    {
        $idRat = (int) $peserta['id_rat'];

        $bentrok = $this->pesertaModel->rfidDipakaiOrangLain($idRat, $uid, $peserta['nip']);
        if ($bentrok) {
            return "UID sudah dipakai oleh {$bentrok['nama']} (NIP {$bentrok['nip']}).";
        }
        $indukLain = $this->anggotaModel->where('uid_rfid', $uid)->where('nip !=', $peserta['nip'])->first();
        if ($indukLain) {
            return "UID sudah terdaftar di data induk atas nama {$indukLain['nama']}.";
        }

        $this->pesertaModel->setRfidNip($idRat, $peserta['nip'], $uid);

        $induk = $this->anggotaModel->findByPrNumber($peserta['nip']);
        if ($induk) {
            $this->anggotaModel->update($induk['id'], ['uid_rfid' => $uid]);
        }

        $this->catatLog("Daftar RFID peserta: {$peserta['nama']} (NIP {$peserta['nip']}) → {$uid}");

        return null;
    }

    private function daftarUnik(string $kolom): array
    {
        $rows = db_connect()->table('tb_anggota')
            ->select($kolom)->distinct()
            ->where("{$kolom} IS NOT NULL")->where("{$kolom} !=", '')
            ->orderBy($kolom)
            ->get()->getResultArray();

        return array_column($rows, $kolom);
    }
}
