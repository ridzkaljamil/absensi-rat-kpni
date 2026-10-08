<?php

namespace App\Controllers;

use App\Models\RatModel;
use App\Models\SesiModel;
use App\Models\AbsensiModel;
use App\Models\PesertaRatModel;
use App\Models\DoorprizeItemModel;
use App\Models\DoorprizeWinnerModel;

class DoorprizeController extends BaseController
{
    protected $ratModel;
    protected $sesiModel;
    protected $absensiModel;
    protected $pesertaModel;
    protected $itemModel;
    protected $winnerModel;

    public function __construct()
    {
        $this->ratModel     = new RatModel();
        $this->sesiModel    = new SesiModel();
        $this->absensiModel = new AbsensiModel();
        $this->pesertaModel = new PesertaRatModel();
        $this->itemModel    = new DoorprizeItemModel();
        $this->winnerModel  = new DoorprizeWinnerModel();
    }

    public function index()
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');
        $idRat = session()->get('rat_dipilih');
        if (!$idRat) return redirect()->to('/rat')->with('error', 'Pilih RAT terlebih dahulu.');

        $rat       = $this->ratModel->find($idRat);
        $items     = $this->itemModel->getByRat($idRat);
        $winners   = $this->winnerModel->getByRat($idRat);
        $sisaItems = $this->itemModel->sisaBelumDiundi($idRat);

        // Eligible: hadir di SELURUH sesi RAT ini
        $daftarSesi  = $this->sesiModel->getByRat($idRat);
        $idSesiList  = array_column($daftarSesi, 'id');
        $summary     = $this->absensiModel->getSummaryPerAnggota($idSesiList, $idRat);
        $pemenang    = $this->winnerModel->nipPemenang($idRat);

        $eligible = [];
        foreach ($summary as $e) {
            if ($e['eligible_doorprize']) {
                $e['sudah_menang'] = isset($pemenang[$e['nip']]);
                $eligible[] = $e;
            }
        }

        return view('doorprize/index', [
            'rat'           => $rat,
            'items'         => $items,
            'winners'       => $winners,
            'sisaItems'     => $sisaItems,
            'eligible'      => $eligible,
            'totalPeserta'  => $this->pesertaModel->totalPeserta($idRat),
            'totalEligible' => count(array_filter($eligible, static fn ($e) => ! $e['sudah_menang'])),
            'daftarSesi'    => $daftarSesi,
            'eligDeptList'  => array_values(array_unique(array_filter(array_column($eligible, 'departemen')))),
            'eligSectList'  => array_values(array_unique(array_filter(array_column($eligible, 'section')))),
        ]);
    }

    // ═══ CRUD Barang ═══
    public function storeItem()
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');
        $idRat = session()->get('rat_dipilih');

        $data = [
            'id_rat'      => $idRat,
            'nama_barang' => trim($this->request->getPost('nama_barang')),
            'jumlah'      => (int) $this->request->getPost('jumlah') ?: 1,
            'kategori'    => $this->request->getPost('kategori') === 'hiburan' ? 'hiburan' : 'utama',
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $gambar = $this->simpanGambar();
        if ($gambar === false) {
            return redirect()->to('/doorprize')->with('error', 'Gambar harus berupa file JPG, PNG, WEBP, atau GIF.');
        }
        if ($gambar) $data['gambar_path'] = $gambar;

        $this->itemModel->insert($data);
        $this->catatLog("Tambah barang doorprize: {$data['nama_barang']}");
        return redirect()->to('/doorprize')->with('success', "Barang \"{$data['nama_barang']}\" ditambahkan.");
    }


    public function updateItem($id)
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');
        $item = $this->itemModel->find($id);
        if (!$item) return redirect()->to('/doorprize')->with('error', 'Barang tidak ditemukan.');

        $data = [
            'nama_barang' => trim($this->request->getPost('nama_barang')),
            'jumlah'      => (int) $this->request->getPost('jumlah') ?: 1,
            'kategori'    => $this->request->getPost('kategori') === 'hiburan' ? 'hiburan' : 'utama',
        ];

        $gambar = $this->simpanGambar();
        if ($gambar === false) {
            return redirect()->to('/doorprize')->with('error', 'Gambar harus berupa file JPG, PNG, WEBP, atau GIF.');
        }
        if ($gambar) {
            if ($item['gambar_path'] && is_file(FCPATH . $item['gambar_path'])) unlink(FCPATH . $item['gambar_path']);
            $data['gambar_path'] = $gambar;
        }

        $this->itemModel->update($id, $data);
        $this->catatLog("Edit barang doorprize: {$data['nama_barang']}");
        return redirect()->to('/doorprize')->with('success', "Barang \"{$data['nama_barang']}\" diperbarui.");
    }

    public function hapusItem($id)
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');
        $item = $this->itemModel->find($id);
        if (!$item) return redirect()->to('/doorprize')->with('error', 'Barang tidak ditemukan.');

        // Hapus file gambar
        if ($item['gambar_path'] && file_exists(FCPATH . $item['gambar_path'])) {
            unlink(FCPATH . $item['gambar_path']);
        }

        $this->itemModel->delete($id);
        $this->catatLog("Hapus barang doorprize: {$item['nama_barang']}");
        return redirect()->to('/doorprize')->with('success', "Barang \"{$item['nama_barang']}\" dihapus.");
    }

    // ═══ Random Pick ═══
    public function pick()
    {
        if (session('role') !== 'admin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $idRat  = session()->get('rat_dipilih');
        $idItem = (int) $this->request->getPost('id_item');

        $item = $this->itemModel->find($idItem);
        if (!$item) return $this->response->setJSON(['status' => 'error', 'message' => 'Barang tidak ditemukan.']);

        // Cek sisa
        $sudahDiundi = $this->winnerModel->where('id_item', $idItem)->countAllResults();
        if ($sudahDiundi >= $item['jumlah']) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Semua unit barang ini sudah diundi.']);
        }

        // Ambil pool eligible yang belum menang
        $idSesiList = array_column($this->sesiModel->getByRat($idRat), 'id');
        $allSummary = $this->absensiModel->getSummaryPerAnggota($idSesiList, $idRat);
        $pemenang   = $this->winnerModel->nipPemenang($idRat);

        $pool = array_values(array_filter(
            $allSummary,
            static fn ($p) => $p['eligible_doorprize'] && ! isset($pemenang[$p['nip']])
        ));

        if ($pool === []) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada peserta eligible yang belum menang.']);
        }

        $winner = $pool[random_int(0, count($pool) - 1)];

        // Dept + section: peserta RAT dulu, fallback ke data induk
        $pr   = db_connect()->query(
            'SELECT MAX(NULLIF(p.departemen, "")) AS p_dept, MAX(NULLIF(p.section, "")) AS p_sect,
                    MAX(a.departemen) AS a_dept, MAX(a.section) AS a_sect
             FROM tb_peserta_rat p LEFT JOIN tb_anggota a ON a.nip = p.nip
             WHERE p.id_rat = ? AND p.nip = ?',
            [$idRat, $winner['nip']]
        )->getRowArray() ?? [];
        $dept = ($pr['p_dept'] ?? null) ?: (($pr['a_dept'] ?? null) ?: '-');
        $sect = ($pr['p_sect'] ?? null) ?: (($pr['a_sect'] ?? null) ?: '-');

        // Simpan
        $this->winnerModel->insert([
            'id_rat'     => $idRat,
            'id_item'    => $idItem,
            'nip'        => $winner['nip'],
            'nama'       => $winner['nama'],
            'departemen' => $dept,
            'section'    => $sect,
            'waktu_undi' => date('Y-m-d H:i:s'),
        ]);

        $this->catatLog("Doorprize: {$winner['nama']} (NIP {$winner['nip']}) menang {$item['nama_barang']}");

        // Return semua nama di pool untuk animasi slot machine
        $namaPool = array_map(fn($p) => $p['nama'], $pool);
        shuffle($namaPool);

        return $this->response->setJSON([
            'status'    => 'success',
            'winner'    => $winner,
            'barang'    => $item['nama_barang'],
            'pool'      => array_slice($namaPool, 0, 30), // 30 nama untuk animasi
            'sisa'      => $item['jumlah'] - $sudahDiundi - 1,
        ]);
    }

    public function hapusWinner($id)
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');
        $w = $this->winnerModel->find($id);
        if ($w) {
            $this->winnerModel->delete($id);
            $this->catatLog("Batalkan pemenang doorprize: {$w['nama']} (NIP {$w['nip']})");
        }
        return redirect()->to('/doorprize')->with('success', 'Pemenang dibatalkan.');
    }

    public function exportWinners()
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');
        $idRat   = session()->get('rat_dipilih');
        $rat     = $this->ratModel->find($idRat);
        $winners = $this->winnerModel->getByRat($idRat);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pemenang Doorprize');

        $headers = ['No.', 'Nama Barang', 'Kategori', 'NIP', 'Nama Pemenang', 'Departemen', 'Section', 'Waktu Undi'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0960A8']],
        ]);

        foreach ($winners as $i => $w) {
            $r = $i + 2;
            $sheet->setCellValue("A$r", $i + 1);
            $sheet->setCellValue("B$r", $w['nama_barang']);
            $sheet->setCellValue("C$r", ucfirst($w['kategori']));
            $sheet->setCellValueExplicit("D$r", $w['nip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("E$r", $w['nama']);
            $sheet->setCellValue("F$r", $w['dept_live'] ?? $w['departemen'] ?? '-');
            $sheet->setCellValue("G$r", $w['section_live'] ?? $w['section'] ?? '-');
            $sheet->setCellValue("H$r", $w['waktu_undi']);
        }
        foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $filename = 'Pemenang_Doorprize_' . ($rat['tahun_buku'] ?? '') . '_' . date('Ymd') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }

    /**
     * Simpan gambar barang. Return path, null (tidak ada file), atau false (format ditolak).
     * Ekstensi ditentukan dari isi file (MIME), bukan nama file kiriman user.
     */
    private function simpanGambar(): string|false|null
    {
        $file = $this->request->getFile('gambar');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) return null;
        if (! $file->isValid() || $file->hasMoved()) return null;

        $ext = $file->guessExtension();
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) return false;

        $dir = FCPATH . 'uploads/doorprize/';
        if (! is_dir($dir)) mkdir($dir, 0755, true);
        $newName = uniqid('prize_') . '.' . $ext;
        $file->move($dir, $newName);

        return 'uploads/doorprize/' . $newName;
    }
}
