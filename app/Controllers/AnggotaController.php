<?php

namespace App\Controllers;

use App\Libraries\ExcelExport;
use App\Libraries\KartuRfid;
use App\Models\AnggotaModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AnggotaController extends BaseController
{
    protected AnggotaModel $anggotaModel;
    protected $db;

    public function __construct()
    {
        $this->anggotaModel = new AnggotaModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $keyword    = $this->request->getGet('cari');
        $filterDept = $this->request->getGet('departemen');
        $filterSect = $this->request->getGet('section');
        $filterShift= $this->request->getGet('shift');
        $filterStat = $this->request->getGet('status');
        $filterRfid = $this->request->getGet('rfid');

        $builder = $this->anggotaModel->orderBy('status','ASC')->orderBy('nama','ASC');

        if ($keyword) {
            $builder = $builder->groupStart()
                ->like('nama', $keyword)->orLike('nip', $keyword)
                ->groupEnd();
        }
        if ($filterDept) $builder = $builder->where('departemen', $filterDept);
        if ($filterSect) $builder = $builder->where('section', $filterSect);
        if ($filterShift) $builder = $builder->where('shift', $filterShift);
        if ($filterStat) $builder = $builder->where('status', $filterStat);
        if ($filterRfid === 'ada')   $builder = $builder->where('uid_rfid IS NOT NULL')->where('uid_rfid !=', '');
        if ($filterRfid === 'belum') $builder = $builder->groupStart()->where('uid_rfid IS NULL')->orWhere('uid_rfid', '')->groupEnd();

        $daftar = $builder->paginate(20, 'default');

        // Anggota yang sudah punya histori absensi tidak boleh dihapus (cek sekaligus, bukan per baris)
        $punyaAbsensi = [];
        $ids = array_column($daftar, 'id');
        if ($ids !== []) {
            $rows = $this->db->table('tb_absensi')->select('id_anggota')->distinct()->whereIn('id_anggota', $ids)->get()->getResultArray();
            $punyaAbsensi = array_fill_keys(array_column($rows, 'id_anggota'), true);
        }
        foreach ($daftar as &$a) {
            $a['boleh_hapus'] = ! isset($punyaAbsensi[$a['id']]);
        }
        unset($a);

        // Ambil daftar unik untuk dropdown filter
        $daftarDept  = $this->db->table('tb_anggota')->select('departemen')->distinct()->where('departemen IS NOT NULL')->where('departemen !=', '')->orderBy('departemen')->get()->getResultArray();
        $daftarSect  = $this->db->table('tb_anggota')->select('section')->distinct()->where('section IS NOT NULL')->where('section !=', '')->orderBy('section')->get()->getResultArray();

        return view('anggota/index', [
            'daftarAnggota' => $daftar,
            'pager'         => $this->anggotaModel->pager,
            'keyword'       => $keyword,
            'filterDept'    => $filterDept,
            'filterSect'    => $filterSect,
            'filterShift'   => $filterShift,
            'filterStat'    => $filterStat,
            'filterRfid'    => $filterRfid,
            'daftarDept'    => array_column($daftarDept, 'departemen'),
            'daftarSect'    => array_column($daftarSect, 'section'),
            'daftarShift'   => array_column($this->db->table('tb_anggota')->select('shift')->distinct()->where('shift IS NOT NULL')->where('shift !=', '')->orderBy('shift')->get()->getResultArray(), 'shift'),
            'totalAktif'    => $this->anggotaModel->where('status','aktif')->countAllResults(),
            'totalNonaktif' => $this->anggotaModel->where('status','nonaktif')->countAllResults(),
        ]);
    }

    public function store()
    {
        $nip        = normalisasi_nip((string) $this->request->getPost('nip'));
        $nama       = strtoupper(trim((string) $this->request->getPost('nama')));
        $departemen = strtoupper(trim($this->request->getPost('departemen') ?? ''));
        $section    = strtoupper(trim($this->request->getPost('section') ?? ''));
        $shift      = strtoupper(trim($this->request->getPost('shift') ?? ''));

        if (! $this->validateData(['nip' => $nip, 'nama' => $nama], [
            'nip'  => ['rules'=>'required|exact_length[6]|is_natural','errors'=>['required'=>'NIP wajib diisi.','exact_length'=>'NIP harus tepat 6 digit.','is_natural'=>'NIP harus berupa angka.']],
            'nama' => ['rules'=>'required|min_length[3]','errors'=>['required'=>'Nama wajib diisi.','min_length'=>'Nama minimal 3 karakter.']],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->anggotaModel->nipSudahAda($nip)) {
            return redirect()->back()->withInput()->with('error', "NIP {$nip} sudah terdaftar.");
        }
        $kartu = new KartuRfid();
        $uid   = normalisasi_uid((string) $this->request->getPost('uid_rfid'));
        if ($uid !== '' && ($pesan = $kartu->cekBentrok($uid, $nip)) !== null) {
            return redirect()->back()->withInput()->with('error', $pesan);
        }

        $this->anggotaModel->insert([
            'nip'        => $nip,
            'nama'       => $nama,
            'departemen' => $departemen ?: null,
            'section'    => $section    ?: null,
            'shift'      => $shift      ?: null,
            'status'     => 'aktif',
        ]);

        if ($uid !== '') {
            $kartu->pasang($nip, $uid); // sudah dicek di atas
        }

        $this->catatLog("Menambah anggota baru: {$nama} (NIP: {$nip})" . ($uid !== '' ? " dengan RFID {$uid}" : ''));
        return redirect()->to('/anggota')->with('success', "Anggota {$nama} berhasil ditambahkan.");
    }

    public function update(int $id)
    {
        $anggota = $this->anggotaModel->find($id);
        if (! $anggota) return redirect()->to('/anggota')->with('error', 'Data tidak ditemukan.');

        $nama       = strtoupper(trim($this->request->getPost('nama')));
        $departemen = strtoupper(trim($this->request->getPost('departemen') ?? ''));
        $section    = strtoupper(trim($this->request->getPost('section')    ?? ''));
        $shift      = strtoupper(trim($this->request->getPost('shift')      ?? ''));

        if (! $this->validate(['nama'=>['rules'=>'required|min_length[3]|max_length[100]','errors'=>['required'=>'Nama wajib diisi.','min_length'=>'Nama minimal 3 karakter.']]])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $this->anggotaModel->update($id, [
            'nama'       => $nama,
            'departemen' => $departemen ?: null,
            'section'    => $section    ?: null,
            'shift'      => $shift      ?: null,
        ]);
        $this->catatLog("Mengubah data anggota NIP {$anggota['nip']}: nama={$nama}, dept={$departemen}, section={$section}, shift={$shift}");
        return redirect()->to('/anggota')->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function updateRfid(int $id)
    {
        $anggota = $this->anggotaModel->find($id);
        if (! $anggota) return redirect()->to('/anggota')->with('error', 'Data tidak ditemukan.');

        $uid   = normalisasi_uid((string) $this->request->getPost('uid_rfid'));
        $pesan = (new KartuRfid())->pasang($anggota['nip'], $uid);
        if ($pesan !== null) {
            return redirect()->to('/anggota')->with('error', $pesan);
        }

        $this->catatLog("Mendaftarkan kartu RFID untuk anggota {$anggota['nama']} ({$anggota['nip']}): {$uid}");

        return redirect()->to('/anggota')->with('success', "Kartu RFID berhasil dipasangkan ke {$anggota['nama']} (berlaku di semua sesi dan RAT).");
    }

    /**
     * Lepas kartu RFID dari anggota (hilang/rusak/keluar) supaya kartunya bisa dipakai orang lain.
     */
    public function lepasRfid(int $id)
    {
        $anggota = $this->anggotaModel->find($id);
        if (! $anggota) return redirect()->to('/anggota')->with('error', 'Data tidak ditemukan.');

        (new KartuRfid())->lepas($anggota['nip']);
        $this->catatLog("Melepas kartu RFID {$anggota['uid_rfid']} dari {$anggota['nama']} ({$anggota['nip']})");

        return redirect()->to('/anggota')->with('success', "Kartu RFID {$anggota['nama']} sudah dilepas.");
    }

    public function hapus(int $id)
    {
        $anggota = $this->anggotaModel->find($id);
        if (! $anggota) return redirect()->to('/anggota')->with('error', 'Data tidak ditemukan.');

        // Anggota yang sudah pernah absen cukup dinonaktifkan, supaya riwayat & rekap tetap utuh
        $pernahAbsen = $this->db->table('tb_absensi')->where('id_anggota', $id)->countAllResults();
        if ($pernahAbsen > 0) {
            return redirect()->to('/anggota')->with('error', "{$anggota['nama']} sudah punya {$pernahAbsen} riwayat absensi sehingga tidak bisa dihapus. Gunakan Nonaktifkan.");
        }

        $this->anggotaModel->delete($id);
        $this->catatLog("Menghapus anggota: {$anggota['nama']} ({$anggota['nip']})");

        return redirect()->to('/anggota')->with('success', "{$anggota['nama']} berhasil dihapus.");
    }

    public function nonaktifkan(int $id)
    {
        $anggota = $this->anggotaModel->find($id);
        if (! $anggota) return redirect()->to('/anggota')->with('error', 'Data tidak ditemukan.');
        $this->anggotaModel->nonaktifkan($id);
        $this->catatLog("Menonaktifkan anggota: {$anggota['nama']} ({$anggota['nip']})");
        return redirect()->to('/anggota')->with('success', "{$anggota['nama']} telah dinonaktifkan.");
    }

    public function aktifkanKembali(int $id)
    {
        $anggota = $this->anggotaModel->find($id);
        if (! $anggota) return redirect()->to('/anggota')->with('error', 'Data tidak ditemukan.');
        $this->anggotaModel->aktifkanKembali($id);
        $this->catatLog("Mengaktifkan kembali anggota: {$anggota['nama']} ({$anggota['nip']})");
        return redirect()->to('/anggota')->with('success', "{$anggota['nama']} telah diaktifkan kembali.");
    }

    /**
     * Import data induk dari Excel. Kolom dikenali dari judul di baris 1
     * (NIP, Nama, Departemen, Section, Shift, RFID); kalau tidak ada judul,
     * dipakai urutan template: A=No, B=NIP, C=Nama, D=Departemen, E=Section, F=Shift, G=RFID.
     * Semua baris disimpan dalam satu transaksi.
     */
    public function import()
    {
        $file = $this->request->getFile('file_excel');
        if (! $file || ! $file->isValid()) {
            return redirect()->to('/anggota')->with('error', 'File Excel tidak valid' . ($file ? ': ' . $file->getErrorString() : '.'));
        }
        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls', 'csv'], true)) {
            return redirect()->to('/anggota')->with('error', 'Format file harus .xlsx / .xls / .csv');
        }

        try {
            $reader = IOFactory::createReaderForFile($file->getTempName());
            $reader->setReadDataOnly(true);
            $rows = $reader->load($file->getTempName())->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return redirect()->to('/anggota')->with('error', 'Gagal membaca file. Pastikan file Excel tidak rusak.');
        }

        // Kenali kolom dari judul baris pertama
        $kolom = ['nip' => 1, 'nama' => 2, 'departemen' => 3, 'section' => 4, 'shift' => 5, 'uid' => 6];
        $judul = array_map(static fn ($v) => strtolower(trim((string) $v)), $rows[0] ?? []);
        $adaJudul = false;
        foreach (['nip' => 'nip', 'nama' => 'nama', 'departemen' => 'dep', 'section' => 'section', 'shift' => 'shift', 'uid' => 'rfid'] as $field => $kunci) {
            foreach ($judul as $i => $j) {
                if ($j !== '' && str_contains($j, $kunci)) { $kolom[$field] = $i; $adaJudul = true; break; }
            }
        }
        if ($adaJudul) {
            array_shift($rows); // baris judul (file tanpa judul: baris pertama sudah data)
            // Kolom yang judulnya tidak ditemukan dikosongkan, jangan ambil posisi template
            foreach ($kolom as $field => $posisi) {
                $ketemu = false;
                foreach ($judul as $i => $j) {
                    if ($i === $posisi && $j !== '' && str_contains($j, ['nip' => 'nip', 'nama' => 'nama', 'departemen' => 'dep', 'section' => 'section', 'shift' => 'shift', 'uid' => 'rfid'][$field])) { $ketemu = true; break; }
                }
                if (! $ketemu) $kolom[$field] = null;
            }
        }

        $nipAda = array_flip(array_column($this->db->table('tb_anggota')->select('nip')->get()->getResultArray(), 'nip'));
        $kartuBaru = []; // [nip => uid] dipasang setelah insert lewat aturan KartuRfid

        $batch = [];
        $gagal = [];
        $now   = date('Y-m-d H:i:s');
        foreach ($rows as $n => $row) {
            $baris = $n + 2;
            $ambil = static fn ($f) => $kolom[$f] === null ? '' : trim((string) ($row[$kolom[$f]] ?? ''));
            $nip   = normalisasi_nip($ambil('nip'));
            $nama  = strtoupper($ambil('nama'));
            $uid   = $ambil('uid');
            $uid   = str_starts_with($uid, '=') ? '' : normalisasi_uid(preg_replace('/\.0+$/', '', $uid));

            if ($nip === '' && $nama === '') continue;
            if ($nip === '' || $nama === '') { $gagal[] = "Baris {$baris}: NIP atau Nama kosong."; continue; }
            if (! ctype_digit($nip) || strlen($nip) !== 6) { $gagal[] = "Baris {$baris}: NIP {$nip} tidak valid (harus 6 digit angka)."; continue; }
            if (isset($nipAda[$nip])) { $gagal[] = "Baris {$baris}: NIP {$nip} sudah terdaftar."; continue; }

            $batch[] = [
                'nip' => $nip, 'nama' => mb_substr($nama, 0, 100),
                'departemen' => strtoupper($ambil('departemen')) ?: null,
                'section'    => strtoupper($ambil('section')) ?: null,
                'shift'      => strtoupper($ambil('shift')) ?: null,
                'status'     => 'aktif', 'created_at' => $now, 'updated_at' => $now,
            ];
            $nipAda[$nip] = true;
            if ($uid !== '') $kartuBaru[$nip] = [$uid, $baris];
        }

        if ($batch !== []) {
            $this->db->transStart();
            $this->db->table('tb_anggota')->insertBatch($batch, null, 200);
            $this->db->transComplete();
            if (! $this->db->transStatus()) {
                return redirect()->to('/anggota')->with('error', 'Import gagal, tidak ada data yang disimpan.');
            }

            // Kartu dipasang satu per satu dengan aturan yang sama seperti pendaftaran manual
            $kartu = new KartuRfid();
            foreach ($kartuBaru as $nip => [$uid, $baris]) {
                $pesan = $kartu->pasang($nip, $uid);
                if ($pesan !== null) {
                    $gagal[] = "Baris {$baris}: NIP {$nip} disimpan tanpa kartu — {$pesan}";
                }
            }
        }

        $berhasil = count($batch);
        $this->catatLog("Import Excel anggota: {$berhasil} berhasil, " . count($gagal) . ' dilewati.');

        $redirect = redirect()->to('/anggota')->with('success', "{$berhasil} anggota baru berhasil diimpor.");

        return $gagal ? $redirect->with('gagalImport', $gagal) : $redirect;
    }

    public function export()
    {
        $daftar = $this->anggotaModel->orderBy('status', 'ASC')->orderBy('nama', 'ASC')->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Data Anggota KPNI');
        ExcelExport::tabel($sheet, ['No.', 'NIP', 'Nama', 'Departemen', 'Section', 'Shift', 'RFID', 'Status'], array_map(
            static fn ($a, $i) => [$i + 1, $a['nip'], $a['nama'], $a['departemen'], $a['section'], $a['shift'], $a['uid_rfid'], ucfirst($a['status'])],
            $daftar, array_keys($daftar)
        ));

        $this->catatLog('Export/backup data anggota (' . count($daftar) . ' anggota)');

        return ExcelExport::unduh($spreadsheet, 'Data_Anggota_KPNI_' . date('Ymd_His') . '.xlsx');
    }

    public function hapusSemua()
    {
        $konfirmasi = $this->request->getPost('konfirmasi');
        if ($konfirmasi !== 'HAPUS SEMUA DATA ANGGOTA') {
            return redirect()->to('/anggota')->with('error', 'Konfirmasi teks tidak sesuai. Penghapusan dibatalkan.');
        }

        $total = $this->anggotaModel->countAllResults();

        // NULL-kan id_anggota di tb_absensi supaya histori tetap ada via snapshot
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('tb_absensi')->update(['id_anggota' => null]);
        $this->db->table('tb_anggota')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        $this->catatLog("HAPUS SEMUA DATA ANGGOTA — {$total} anggota dihapus permanen");
        return redirect()->to('/anggota')->with('success', "Seluruh {$total} data anggota berhasil dihapus. Histori absensi tetap tersimpan via snapshot.");
    }


    public function bulkRfid()
    {
        $anggota = $this->anggotaModel
            ->where('status', 'aktif')
            ->groupStart()
                ->where('uid_rfid IS NULL')
                ->orWhere('uid_rfid', '')
            ->groupEnd()
            ->orderBy('nama', 'ASC')
            ->findAll();

        $totalDone = $this->anggotaModel
            ->where('status', 'aktif')
            ->where('uid_rfid IS NOT NULL')
            ->where('uid_rfid !=', '')
            ->countAllResults();

        $totalAll = $this->anggotaModel->where('status', 'aktif')->countAllResults();

        return view('peserta_rat/bulk_rfid', [
            'rat'       => ['nama_rat' => 'Data Anggota'],
            'peserta'   => $anggota,
            'totalDone' => $totalDone,
            'totalAll'  => $totalAll,
            'mode'      => 'anggota',
            'backUrl'   => '/anggota',
        ]);
    }

    public function bulkRfidSave()
    {
        $anggota = $this->anggotaModel->find((int) $this->request->getPost('id'));
        $uid     = normalisasi_uid((string) $this->request->getPost('uid_rfid'));
        if (! $anggota || $uid === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $pesan = (new KartuRfid())->pasang($anggota['nip'], $uid);
        if ($pesan !== null) {
            return $this->response->setJSON(['status' => 'error', 'message' => $pesan]);
        }
        $this->catatLog("Bulk RFID: {$anggota['nama']} ({$anggota['nip']}) → {$uid}");

        return $this->response->setJSON(['status' => 'success', 'nama' => $anggota['nama'], 'nip' => $anggota['nip']]);
    }

}
