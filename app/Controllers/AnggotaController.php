<?php

namespace App\Controllers;

use App\Models\AnggotaModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
            'totalAktif'    => $this->anggotaModel->where('status','aktif')->countAllResults(),
            'totalNonaktif' => $this->anggotaModel->where('status','nonaktif')->countAllResults(),
        ]);
    }

    public function store()
    {
        $nip        = trim($this->request->getPost('nip'));
        $nama       = strtoupper(trim($this->request->getPost('nama')));
        $departemen = strtoupper(trim($this->request->getPost('departemen') ?? ''));
        $section    = strtoupper(trim($this->request->getPost('section') ?? ''));
        $shift      = strtoupper(trim($this->request->getPost('shift') ?? ''));

        if (! $this->validate([
            'nip'  => ['rules'=>'required|exact_length[6]|numeric','errors'=>['required'=>'NIP wajib diisi.','exact_length'=>'NIP harus tepat 6 digit.','numeric'=>'NIP harus angka.']],
            'nama' => ['rules'=>'required|min_length[3]','errors'=>['required'=>'Nama wajib diisi.','min_length'=>'Nama minimal 3 karakter.']],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->anggotaModel->nipSudahAda($nip)) {
            return redirect()->back()->withInput()->with('error', "NIP {$nip} sudah terdaftar.");
        }

        $this->anggotaModel->insert([
            'nip'        => $nip,
            'nama'       => $nama,
            'departemen' => $departemen ?: null,
            'section'    => $section    ?: null,
            'shift'      => $shift      ?: null,
            'status'     => 'aktif',
        ]);

        $this->catatLog("Menambah anggota baru: {$nama} (NIP: {$nip})");
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

        $uid = trim($this->request->getPost('uid_rfid') ?? '');
        if (! $uid) return redirect()->to('/anggota')->with('error', 'UID kartu tidak boleh kosong.');

        // Cek duplikat, kecuali kalau UID itu milik anggota ini sendiri
        $existing = $this->anggotaModel->findByUidRfid($uid);
        if ($existing && (int) $existing['id'] !== $id) {
            return redirect()->to('/anggota')->with('error', "Kartu ini sudah terdaftar ke anggota lain ({$existing['nama']}).");
        }

        $this->anggotaModel->update($id, ['uid_rfid' => $uid]);
        $this->catatLog("Mendaftarkan kartu RFID untuk anggota {$anggota['nama']} ({$anggota['nip']}): {$uid}");
        return redirect()->to('/anggota')->with('success', "Kartu RFID berhasil dipasangkan ke {$anggota['nama']}.");
    }

    public function hapus(int $id)
    {
        $anggota = $this->anggotaModel->find($id);
        if (! $anggota) return redirect()->to('/anggota')->with('error', 'Data tidak ditemukan.');

        // NULL-kan id_anggota di tb_absensi supaya histori absensi tetap ada
        // (nama sudah tersimpan di nama_snapshot saat absen dicatat)
        $this->db->table('tb_absensi')->where('id_anggota', $id)->update(['id_anggota' => null]);

        $this->anggotaModel->delete($id);
        $this->catatLog("Menghapus anggota: {$anggota['nama']} ({$anggota['nip']}) — histori absensi tetap ada via snapshot");
        return redirect()->to('/anggota')->with('success', "{$anggota['nama']} berhasil dihapus. Histori absensi tetap tersimpan.");
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

    public function import()
    {
        $file = $this->request->getFile('file_excel');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'File Excel tidak valid.');
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        array_shift($rows); // skip header
        $berhasil = 0;
        $gagal = [];
        $noBaris = 1;

        foreach ($rows as $row) {
            $noBaris++;
            // Format: A=No, B=NIP, C=Nama, D=Departemen, E=Section, F=Shift, G=RFID
            $nip        = isset($row[1]) ? trim((string)$row[1]) : '';
            $nama       = isset($row[2]) ? strtoupper(trim((string)$row[2])) : '';
            $departemen = isset($row[3]) ? strtoupper(trim((string)$row[3])) : '';
            $section    = isset($row[4]) ? strtoupper(trim((string)$row[4])) : '';
            $shift      = isset($row[5]) ? strtoupper(trim((string)$row[5])) : '';
            $uid        = isset($row[6]) ? trim((string)$row[6]) : '';
            // Skip baris kosong dan RFID formula
            if (str_starts_with($uid, '=')) $uid = '';

            if ($nip === '' && $nama === '') continue;
            if ($nip === '' || $nama === '') {
                $gagal[] = "Baris {$noBaris}: NIP atau Nama kosong."; continue;
            }
            if (strlen($nip) !== 6 || !is_numeric($nip)) {
                $gagal[] = "Baris {$noBaris}: NIP {$nip} tidak valid (harus 6 digit angka)."; continue;
            }
            if ($this->anggotaModel->nipSudahAda($nip)) {
                $gagal[] = "Baris {$noBaris}: NIP {$nip} sudah terdaftar (duplikat)."; continue;
            }

            $insertData = [
                'nip'        => $nip,
                'nama'       => $nama,
                'departemen' => $departemen ?: null,
                'section'    => $section    ?: null,
                'shift'      => $shift      ?: null,
                'status'     => 'aktif',
            ];
            if ($uid !== '' && !$this->anggotaModel->uidRfidSudahAda($uid)) {
                $insertData['uid_rfid'] = $uid;
            }

            $this->anggotaModel->insert($insertData);
            $berhasil++;
        }

        $this->catatLog("Import Excel anggota: {$berhasil} berhasil, " . count($gagal) . " gagal.");

        if (! empty($gagal)) {
            return redirect()->to('/anggota')
                ->with('success', "{$berhasil} anggota berhasil diimpor.")
                ->with('gagalImport', $gagal);
        }

        return redirect()->to('/anggota')->with('success', "{$berhasil} anggota baru berhasil diimpor.");
    }

    public function export()
    {
        $daftarAnggota = $this->anggotaModel->orderBy('status','ASC')->orderBy('nama','ASC')->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Anggota KPNI');

        // Header
        $headers = ['No.', 'NIP', 'Nama', 'Departemen', 'Section', 'Shift', 'RFID', 'Status'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'0960A8']],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
        ]);

        foreach ($daftarAnggota as $i => $a) {
            $row = $i + 2;
            $sheet->setCellValue('A'.$row, $i + 1);
            $sheet->setCellValue('B'.$row, $a['nip']);
            $sheet->setCellValue('C'.$row, $a['nama']);
            $sheet->setCellValue('D'.$row, $a['departemen'] ?? '');
            $sheet->setCellValue('E'.$row, $a['section']    ?? '');
            $sheet->setCellValue('F'.$row, $a['shift']      ?? '');
            $sheet->setCellValue('G'.$row, $a['uid_rfid']   ?? '');
            $sheet->setCellValue('H'.$row, ucfirst($a['status']));
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F7FF');
            }
        }

        foreach (['A','B','C','D','E','F','G','H'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $this->catatLog("Export/backup data anggota (" . count($daftarAnggota) . " anggota)");

        return $this->response
            ->setHeader('Content-Type','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition','attachment; filename="Data_Anggota_KPNI_' . date('Ymd_His') . '.xlsx"')
            ->setHeader('Cache-Control','max-age=0')
            ->setBody($content);
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
        if (session('role') !== 'admin') return redirect()->to('/dashboard');

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
        $id  = (int) $this->request->getPost('id');
        $uid = trim($this->request->getPost('uid_rfid'));

        if (!$id || !$uid) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $anggota = $this->anggotaModel->find($id);
        if (!$anggota) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anggota tidak ditemukan.']);
        }

        $existing = $this->anggotaModel->where('uid_rfid', $uid)->where('id !=', $id)->first();
        if ($existing) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'UID sudah dipakai oleh ' . $existing['nama']]);
        }

        $this->anggotaModel->update($id, ['uid_rfid' => $uid]);
        $this->catatLog("Bulk RFID: {$anggota['nama']} ({$anggota['nip']}) → {$uid}");

        return $this->response->setJSON(['status' => 'success', 'nama' => $anggota['nama'], 'nip' => $anggota['nip']]);
    }

}
