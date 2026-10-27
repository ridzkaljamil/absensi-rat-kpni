<?php

namespace App\Controllers;

use App\Libraries\ExcelExport;
use App\Libraries\KartuRfid;
use App\Models\AbsensiModel;
use App\Models\AnggotaModel;
use App\Models\PesertaRatModel;
use App\Models\RatModel;
use App\Models\SesiModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

        // Kartu RFID dibaca dari data induk (satu-satunya tempat kartu disimpan)
        $builder = $this->pesertaModel
            ->select('tb_peserta_rat.*, a.uid_rfid')
            ->join('tb_anggota a', 'a.nip = tb_peserta_rat.nip', 'left')
            ->where('tb_peserta_rat.id_sesi', $idSesi);
        if ($filter['search'] !== '') {
            $builder->groupStart()
                ->like('tb_peserta_rat.nip', $filter['search'])->orLike('tb_peserta_rat.nama', $filter['search'])->orLike('tb_peserta_rat.departemen', $filter['search'])
                ->groupEnd();
        }
        foreach (['departemen', 'section', 'shift'] as $kolom) {
            if ($filter[$kolom] !== '') $builder->where('tb_peserta_rat.' . $kolom, $filter[$kolom]);
        }
        if ($filter['rfid'] === 'ada') {
            $builder->where('a.uid_rfid IS NOT NULL')->where('a.uid_rfid !=', '');
        } elseif ($filter['rfid'] === 'belum') {
            $builder->groupStart()->where('a.uid_rfid IS NULL')->orWhere('a.uid_rfid', '')->groupEnd();
        }

        $perPage = 20;
        $peserta = $builder->orderBy('tb_peserta_rat.nama', 'ASC')->paginate($perPage);
        $pager   = $this->pesertaModel->pager;

        $totalRfid = $this->pesertaModel->jumlahBerkartu($idSesi);

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
        $sesi  = $this->sesiBisaDiubah((int) $this->request->getPost('id_sesi'));
        if (is_string($sesi)) {
            return redirect()->to('/peserta-rat?sesi=' . (int) $this->request->getPost('id_sesi'))->with('error', $sesi);
        }
        $idSesi  = (int) $sesi['id'];
        $kembali = '/peserta-rat?sesi=' . $idSesi;

        $file = $this->request->getFile('file_excel');
        if (! $file || ! $file->isValid()) {
            return redirect()->to($kembali)->with('error', 'File tidak valid' . ($file ? ': ' . $file->getErrorString() : '.'));
        }
        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls', 'csv'], true)) {
            return redirect()->to($kembali)->with('error', 'Format file harus .xlsx / .xls / .csv');
        }

        try {
            $reader = IOFactory::createReaderForFile($file->getTempName());
            $reader->setReadDataOnly(true);
            $rows = $reader->load($file->getTempName())->getActiveSheet()->toArray(null, true, false, true);
        } catch (\Throwable $e) {
            return redirect()->to($kembali)->with('error', 'Gagal membaca file. Pastikan file Excel tidak rusak.');
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
            return redirect()->to($kembali)->with('error', 'Kolom NIP dan Nama wajib ada di baris judul (baris 1) file Excel.');
        }

        $data = [];
        $nipTidakValid = [];
        foreach ($rows as $i => $row) {
            if ($i === 1) continue;
            $nip = normalisasi_nip((string) ($row[$colMap['nip']] ?? ''));
            if ($nip === '') continue;
            if (! ctype_digit($nip) || strlen($nip) !== 6) {
                $nipTidakValid[] = "baris {$i} ({$nip})";
                continue;
            }
            $baris = ['nip' => $nip];
            foreach (['nama', 'departemen', 'section', 'shift', 'uid_rfid'] as $field) {
                $baris[$field] = isset($colMap[$field]) ? trim((string) ($row[$colMap[$field]] ?? '')) : '';
            }
            if ($baris['nama'] === '') continue; // baris tanpa nama dilewati (juga tidak dapat kartu)
            if ($baris['uid_rfid'] !== '') {
                $baris['uid_rfid'] = normalisasi_uid(preg_replace('/\.0+$/', '', $baris['uid_rfid']));
            }
            $data[] = $baris;
        }
        if ($data === []) {
            return redirect()->to($kembali)->with('error', 'Tidak ada data valid di file.');
        }

        $db = db_connect();
        $db->transStart();
        $hasil = $this->pesertaModel->importBulkSesi($idRat, $idSesi, $data);

        // Lengkapi departemen/section/shift yang kosong dari data induk
        $db->query(
            'UPDATE tb_peserta_rat p JOIN tb_anggota a ON p.nip = a.nip
             SET p.departemen = COALESCE(NULLIF(p.departemen, ""), a.departemen),
                 p.section    = COALESCE(NULLIF(p.section, ""), a.section),
                 p.shift      = COALESCE(NULLIF(p.shift, ""), a.shift)
             WHERE p.id_sesi = ?',
            [$idSesi]
        );

        // Kartu dari file dipasang lewat aturan yang sama dengan pendaftaran manual (cek bentrok, simpan di data induk)
        $kartu = new KartuRfid();
        $kartuGagal = [];
        foreach ($data as $baris) {
            if ($baris['uid_rfid'] === '') continue; // tanpa kartu di file: kartu lama di data induk tetap berlaku
            $pesan = $kartu->pasang($baris['nip'], $baris['uid_rfid'], $baris);
            if ($pesan !== null) {
                $kartuGagal[] = "NIP {$baris['nip']}: {$pesan}";
            }
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to($kembali)->with('error', 'Import gagal, tidak ada data yang diubah. Periksa isi file lalu coba lagi.');
        }

        $label = label_sesi($sesi['nama_sesi']);
        $this->catatLog("Import peserta {$label}: {$hasil['inserted']} baru, {$hasil['updated']} diperbarui, {$hasil['skipped']} dilewati");

        $pesan = "Import {$label} berhasil: {$hasil['inserted']} peserta baru, {$hasil['updated']} diperbarui.";
        if ($nipTidakValid !== []) {
            $pesan .= ' ' . count($nipTidakValid) . ' baris dilewati karena NIP bukan 6 digit: ' . implode(', ', array_slice($nipTidakValid, 0, 5)) . (count($nipTidakValid) > 5 ? ', …' : '') . '.';
        }
        if ($kartuGagal !== [] || $nipTidakValid !== []) {
            $pesan .= ' ' . count($kartuGagal) . ' kartu RFID tidak dipasang karena bentrok: ' . implode('; ', array_slice($kartuGagal, 0, 3)) . (count($kartuGagal) > 3 ? '; …' : '');

            return redirect()->to($kembali)->with('error', $pesan);
        }

        return redirect()->to($kembali)->with('success', $pesan);
    }

    /**
     * Salin peserta dari KEHADIRAN sesi sebelumnya.
     */
    public function salinKehadiran()
    {
        $sesi = $this->sesiBisaDiubah((int) $this->request->getPost('id_sesi'));
        if (is_string($sesi)) {
            return redirect()->to('/peserta-rat?sesi=' . (int) $this->request->getPost('id_sesi'))->with('error', $sesi);
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
        $sesi = $this->sesiBisaDiubah((int) $this->request->getPost('id_sesi'));
        if (is_string($sesi)) {
            return redirect()->to('/peserta-rat?sesi=' . (int) $this->request->getPost('id_sesi'))->with('error', $sesi);
        }
        $idSesi  = (int) $sesi['id'];
        $kembali = '/peserta-rat?sesi=' . $idSesi;
        $nip     = normalisasi_nip((string) $this->request->getPost('nip'));
        $nama    = trim((string) $this->request->getPost('nama'));

        if ($nip === '' || $nama === '') {
            return redirect()->to($kembali)->withInput()->with('error', 'NIP dan Nama wajib diisi.');
        }
        if (! ctype_digit($nip) || strlen($nip) !== 6) {
            return redirect()->to($kembali)->withInput()->with('error', 'NIP harus 6 digit angka.');
        }
        if ($this->pesertaModel->nipTerdaftarSesi($idSesi, $nip)) {
            return redirect()->to($kembali)->withInput()->with('error', "NIP {$nip} sudah terdaftar di sesi ini.");
        }

        $idRat = $this->idRatDipilih();
        $kartu = new KartuRfid();
        $uid   = normalisasi_uid((string) $this->request->getPost('uid_rfid'));

        if ($uid !== '') {
            // Kartu diisi langsung di form: cek bentrok dulu sebelum peserta disimpan
            $bentrok = $kartu->cekBentrok($uid, $nip);
            if ($bentrok !== null) {
                return redirect()->to($kembali)->withInput()->with('error', $bentrok);
            }
        }

        $this->pesertaModel->insert([
            'id_rat'     => $idRat,
            'id_sesi'    => $idSesi,
            'nip'        => $nip,
            'nama'       => $nama,
            'departemen' => trim((string) $this->request->getPost('departemen')) ?: null,
            'section'    => trim((string) $this->request->getPost('section')) ?: null,
            'shift'      => trim((string) $this->request->getPost('shift')) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($uid !== '') {
            $kartu->pasang($nip, $uid, ['nama' => $nama] + $this->request->getPost(['departemen', 'section', 'shift']));
        }
        $berkartu = $uid !== '' || $kartu->milikNip($nip) !== null;

        $this->catatLog('Tambah peserta ' . label_sesi($sesi['nama_sesi']) . ": NIP {$nip}" . ($uid !== '' ? " (RFID {$uid})" : ''));

        $pesan = "Peserta {$nama} (NIP {$nip}) berhasil ditambahkan" . ($berkartu ? ' dan kartu RFID-nya sudah terdaftar.' : '. Kartu RFID belum didaftarkan.');

        return redirect()->to($kembali)->with('success', $pesan);
    }

    public function daftarRfid($id)
    {
        $peserta = $this->pesertaModel->find($id);
        if (! $peserta || (int) $peserta['id_rat'] !== $this->idRatDipilih()) {
            return redirect()->to('/peserta-rat')->with('error', 'Peserta tidak ditemukan.');
        }
        $kembali = '/peserta-rat?sesi=' . $peserta['id_sesi'];

        $uid   = normalisasi_uid((string) $this->request->getPost('uid_rfid'));
        $pesan = (new KartuRfid())->pasang($peserta['nip'], $uid, $peserta);
        if ($pesan !== null) {
            return redirect()->to($kembali)->with('error', $pesan);
        }
        $this->catatLog("Daftar RFID peserta: {$peserta['nama']} (NIP {$peserta['nip']}) → {$uid}");

        return redirect()->to($kembali)->with('success', "RFID berhasil didaftarkan untuk {$peserta['nama']} (berlaku di semua sesi).");
    }

    public function hapus($id)
    {
        $peserta = $this->pesertaModel->find($id);
        if (! $peserta || (int) $peserta['id_rat'] !== $this->idRatDipilih()) {
            return redirect()->to('/peserta-rat')->with('error', 'Peserta tidak ditemukan.');
        }
        $sesi = $this->sesiBisaDiubah((int) $peserta['id_sesi']);
        if (is_string($sesi)) {
            return redirect()->to('/peserta-rat?sesi=' . $peserta['id_sesi'])->with('error', $sesi);
        }

        $this->pesertaModel->delete($id);
        $this->catatLog("Hapus peserta RAT: NIP {$peserta['nip']} - {$peserta['nama']} (sesi {$peserta['id_sesi']})");

        return redirect()->to('/peserta-rat?sesi=' . $peserta['id_sesi'])->with('success', "{$peserta['nama']} dihapus dari daftar peserta sesi ini.");
    }

    public function hapusSemua()
    {
        $sesi = $this->sesiBisaDiubah((int) $this->request->getPost('id_sesi'));
        if (is_string($sesi)) {
            return redirect()->to('/peserta-rat?sesi=' . (int) $this->request->getPost('id_sesi'))->with('error', $sesi);
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
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Peserta RAT');
        ExcelExport::tabel($sheet, ['No.', 'NIP', 'Nama', 'Departemen', 'Section', 'Shift', 'RFID'], array_map(
            static fn ($p, $i) => [$i + 1, $p['nip'], $p['nama'], $p['departemen'], $p['section'], $p['shift'], $p['uid_rfid'] ?? ''],
            $peserta, array_keys($peserta)
        ));

        $this->catatLog("Export peserta RAT {$labelSesi} (" . count($peserta) . ' peserta)');

        return ExcelExport::unduh($spreadsheet, 'Peserta_RAT_' . ($rat['tahun_buku'] ?? '') . '_' . preg_replace('/[^A-Za-z0-9]+/', '_', $labelSesi) . '_' . date('Ymd_His') . '.xlsx');
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
        $belum = array_values(array_filter($semua, static fn ($p) => ($p['uid_rfid'] ?? '') === ''));

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
        $uid     = normalisasi_uid((string) $this->request->getPost('uid_rfid'));

        if (! $peserta || $uid === '' || (int) $peserta['id_rat'] !== $this->idRatDipilih()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $pesan = (new KartuRfid())->pasang($peserta['nip'], $uid, $peserta);
        if ($pesan !== null) {
            return $this->response->setJSON(['status' => 'error', 'message' => $pesan]);
        }
        $this->catatLog("Daftar RFID peserta: {$peserta['nama']} (NIP {$peserta['nip']}) → {$uid}");

        return $this->response->setJSON(['status' => 'success', 'nama' => $peserta['nama'], 'nip' => $peserta['nip']]);
    }

    public function lookupNip()
    {
        $nip     = normalisasi_nip((string) $this->request->getGet('nip'));
        $anggota = $nip !== '' ? $this->anggotaModel->findByPrNumber($nip) : null;
        $rfid    = $nip !== '' ? ((new KartuRfid())->milikNip($nip) ?? '') : '';
        if (! $anggota) {
            return $this->response->setJSON(['found' => false, 'uid_rfid' => $rfid]);
        }

        return $this->response->setJSON([
            'found'      => true,
            'nama'       => $anggota['nama'],
            'departemen' => $anggota['departemen'] ?? '',
            'section'    => $anggota['section'] ?? '',
            'shift'      => $anggota['shift'] ?? '',
            'uid_rfid'   => $rfid,
        ]);
    }

    // ─────────────────────────────────────────────
    //  Helper
    // ─────────────────────────────────────────────


    /**
     * Sesi milik RAT ini yang daftar pesertanya masih boleh diubah.
     * Sesi terkunci dibekukan supaya angka kuorum tidak berubah setelah sesi selesai.
     */
    private function sesiBisaDiubah(int $idSesi): array|string
    {
        $sesi = $this->sesiMilikRat($idSesi);
        if (! $sesi) {
            return 'Sesi tidak valid.';
        }
        if ($sesi['status'] === 'terkunci') {
            return label_sesi($sesi['nama_sesi']) . ' sudah dikunci. Buka kunci dulu di Kelola Sesi untuk mengubah daftar peserta.';
        }

        return $sesi;
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
