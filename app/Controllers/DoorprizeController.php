<?php

namespace App\Controllers;

use App\Libraries\ExcelExport;
use App\Models\AbsensiModel;
use App\Models\DoorprizeItemModel;
use App\Models\DoorprizeWinnerModel;
use App\Models\PesertaRatModel;
use App\Models\RatModel;
use App\Models\SesiModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Doorprize.
 *
 * Eligible = hadir di SELURUH sesi RAT yang dipakai (sesi yang punya daftar peserta).
 * Satu orang hanya bisa menang sekali per RAT (dijaga juga oleh unique key di database).
 * Pemenang yang tidak hadir saat diumumkan bisa ditandai "hangus": unit hadiahnya
 * kembali bisa diundi, tapi orangnya tidak ikut undian lagi.
 */
class DoorprizeController extends BaseController
{
    private const MAKS_UNIT    = 1000;
    private const MAKS_GAMBAR  = 10 * 1024 * 1024; // 10 MB sebelum diperkecil
    private const SISI_GAMBAR  = 800;              // px, sisi terpanjang setelah diperkecil

    protected RatModel $ratModel;
    protected SesiModel $sesiModel;
    protected AbsensiModel $absensiModel;
    protected PesertaRatModel $pesertaModel;
    protected DoorprizeItemModel $itemModel;
    protected DoorprizeWinnerModel $winnerModel;

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
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return redirect()->to('/rat')->with('error', 'Pilih RAT terlebih dahulu.');
        }

        $sesiDipakai = $this->sesiDipakai($idRat);
        $summary     = $this->absensiModel->getSummaryPerAnggota(array_column($sesiDipakai, 'id'), $idRat);
        $statusMenang = $this->winnerModel->statusPerNip($idRat); // [nip => 'menang'|'hangus']

        $eligible = [];
        foreach ($summary as $e) {
            if ($e['eligible_doorprize']) {
                $e['status_menang'] = $statusMenang[$e['nip']] ?? null;
                $eligible[] = $e;
            }
        }
        $winners = $this->winnerModel->getByRat($idRat);

        return view('doorprize/index', [
            'rat'           => $this->ratModel->find($idRat),
            'items'         => $this->itemModel->getByRat($idRat),
            'winners'       => $winners,
            'totalMenang'   => count(array_filter($winners, static fn ($w) => ! $w['hangus'])),
            'sisaItems'     => $this->itemModel->sisaBelumDiundi($idRat),
            'eligible'      => $eligible,
            'totalPeserta'  => $this->pesertaModel->totalPeserta($idRat),
            'totalEligible' => count(array_filter($eligible, static fn ($e) => $e['status_menang'] === null)),
            'daftarSesi'    => $sesiDipakai,
            'sesiKosong'    => count($this->sesiModel->getByRat($idRat)) - count($sesiDipakai),
            'eligDeptList'  => array_values(array_unique(array_filter(array_column($eligible, 'departemen')))),
            'eligSectList'  => array_values(array_unique(array_filter(array_column($eligible, 'section')))),
        ]);
    }

    // ═══ CRUD Barang ═══

    public function storeItem()
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return redirect()->to('/rat')->with('error', 'Pilih RAT terlebih dahulu.');
        }
        $data  = $this->dataBarang();
        if (is_string($data)) {
            return redirect()->to('/doorprize')->with('error', $data);
        }

        $gambar = $this->simpanGambar();
        if ($gambar === false || (is_string($gambar) && str_starts_with($gambar, '!'))) {
            return redirect()->to('/doorprize')->with('error', $gambar ? substr($gambar, 1) : 'Gambar harus berupa file JPG, PNG, atau WEBP.');
        }

        $this->itemModel->insert($data + [
            'id_rat'      => $idRat,
            'gambar_path' => $gambar,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->catatLog("Tambah barang doorprize: {$data['nama_barang']} ({$data['jumlah']} unit)");

        return redirect()->to('/doorprize#barang')->with('success', "Barang \"{$data['nama_barang']}\" ditambahkan.");
    }

    public function updateItem($id)
    {
        $item = $this->itemMilikRat((int) $id);
        if (! $item) return redirect()->to('/doorprize')->with('error', 'Barang tidak ditemukan.');

        $data = $this->dataBarang();
        if (is_string($data)) {
            return redirect()->to('/doorprize')->with('error', $data);
        }
        $gambar = $this->simpanGambar();
        if ($gambar === false || (is_string($gambar) && str_starts_with($gambar, '!'))) {
            return redirect()->to('/doorprize')->with('error', $gambar ? substr($gambar, 1) : 'Gambar harus berupa file JPG, PNG, atau WEBP.');
        }
        if ($gambar) {
            $data['gambar_path'] = $gambar;
        }

        // Kunci baris barang supaya tidak bentrok dengan undian yang sedang berjalan
        $db = db_connect();
        $db->transStart();
        $db->query('SELECT id FROM tb_doorprize_items WHERE id = ? FOR UPDATE', [$id]);
        $sudah = $this->winnerModel->jumlahUnitTerpakai((int) $id);
        if ($data['jumlah'] < $sudah) {
            $db->transComplete();
            $this->hapusFileGambar($gambar ?: null);

            return redirect()->to('/doorprize')->with('error', "Jumlah tidak bisa kurang dari {$sudah}, karena sudah ada {$sudah} pemenang untuk barang ini.");
        }
        $this->itemModel->update($id, $data);
        $db->transComplete();
        if ($gambar) {
            $this->hapusFileGambar($item['gambar_path']);
        }
        $this->catatLog("Edit barang doorprize: {$data['nama_barang']}");

        return redirect()->to('/doorprize#barang')->with('success', "Barang \"{$data['nama_barang']}\" diperbarui.");
    }

    public function hapusItem($id)
    {
        $item = $this->itemMilikRat((int) $id);
        if (! $item) return redirect()->to('/doorprize')->with('error', 'Barang tidak ditemukan.');

        $db = db_connect();
        $db->transStart();
        $db->query('SELECT id FROM tb_doorprize_items WHERE id = ? FOR UPDATE', [$id]);
        $pemenang = $this->winnerModel->where('id_item', $id)->countAllResults();
        if ($pemenang === 0) {
            $this->itemModel->delete($id);
        }
        $db->transComplete();
        if ($pemenang > 0) {
            return redirect()->to('/doorprize#barang')->with('error', "\"{$item['nama_barang']}\" sudah punya {$pemenang} pemenang (termasuk yang hangus) dan tidak bisa dihapus. Hapus pemenangnya dulu di Riwayat Pemenang kalau memang perlu.");
        }

        $this->hapusFileGambar($item['gambar_path']);
        $this->catatLog("Hapus barang doorprize: {$item['nama_barang']}");

        return redirect()->to('/doorprize#barang')->with('success', "Barang \"{$item['nama_barang']}\" dihapus.");
    }

    // ═══ Undian ═══

    /**
     * Undi satu pemenang untuk satu barang. Dikunci per barang (SELECT … FOR UPDATE)
     * supaya dua laptop yang mengundi bersamaan tidak melebihi stok / memenangkan orang yang sama.
     */
    public function pick()
    {
        $idRat = $this->idRatDipilih();
        $item  = $this->itemMilikRat((int) $this->request->getPost('id_item'));
        if (! $idRat || ! $item) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Barang tidak ditemukan di RAT ini. Muat ulang halaman.']);
        }

        $db = db_connect();
        $db->transException(true); // query gagal (lock timeout/deadlock) → exception → diulang
        for ($percobaan = 0; $percobaan < 3; $percobaan++) {
            $db->transBegin();
            try {
                // Kunci baris barang & baca jumlah terbaru (bisa saja baru diubah admin lain)
                $item['jumlah'] = (int) ($db->query('SELECT jumlah FROM tb_doorprize_items WHERE id = ? FOR UPDATE', [$item['id']])->getRowArray()['jumlah'] ?? 0);

                $terpakai = $this->winnerModel->jumlahUnitTerpakai((int) $item['id']);
                if ($terpakai >= $item['jumlah']) {
                    $db->transRollback();

                    return $this->response->setJSON(['status' => 'error', 'message' => 'Semua unit barang ini sudah diundi.']);
                }

                $pool = $this->poolUndian($idRat);
                if ($pool === []) {
                    $db->transRollback();

                    return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada peserta eligible yang belum menang.']);
                }

                $winner = $pool[random_int(0, count($pool) - 1)];
                [$dept, $sect] = $this->deptSection($idRat, $winner);

                $ok = $this->winnerModel->insert([
                    'id_rat'     => $idRat,
                    'id_item'    => $item['id'],
                    'nip'        => $winner['nip'],
                    'nama'       => $winner['nama'],
                    'departemen' => $dept,
                    'section'    => $sect,
                    'waktu_undi' => date('Y-m-d H:i:s'),
                ]);
                if (! $ok) {
                    throw new DatabaseException('Gagal menyimpan pemenang.');
                }
                $idWinner = (int) $this->winnerModel->getInsertID();
                $db->transCommit();
            } catch (DatabaseException|\mysqli_sql_exception) {
                // Biasanya: orang yang sama baru saja dimenangkan dari laptop lain → undi ulang
                $db->transRollback();
                continue;
            }

            $this->catatLog("Doorprize: {$winner['nama']} (NIP {$winner['nip']}) menang {$item['nama_barang']}");

            $namaPool = array_column($pool, 'nama');
            shuffle($namaPool);

            return $this->response->setJSON([
                'status'   => 'success',
                'winner'   => ['id' => $idWinner, 'nip' => $winner['nip'], 'nama' => $winner['nama'], 'departemen' => $dept, 'section' => $sect],
                'id_item'  => (int) $item['id'],
                'barang'   => $item['nama_barang'],
                'kategori' => $item['kategori'],
                'waktu'    => date('d/m H:i'),
                'pool'     => array_slice($namaPool, 0, 30),
                'sisa'     => $item['jumlah'] - $terpakai - 1,
            ] + $this->ringkasanUndian($idRat));
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Undian bentrok dengan laptop lain. Silakan undi lagi.']);
    }

    /**
     * Hapus pemenang (mis. salah undi): data dihapus, orangnya kembali ikut undian, unitnya bisa diundi lagi.
     */
    public function hapusWinner($id)
    {
        return $this->ubahPemenang((int) $id, 'hapus');
    }

    /**
     * Pemenang tidak hadir: unit hadiah kembali bisa diundi, orangnya tidak ikut undian lagi.
     */
    public function hangusWinner($id)
    {
        return $this->ubahPemenang((int) $id, 'hangus');
    }

    private function ubahPemenang(int $id, string $aksi)
    {
        $idRat = $this->idRatDipilih();
        $w     = $this->winnerModel->find($id);
        if (! $w || (int) $w['id_rat'] !== $idRat) {
            $pesan = 'Pemenang tidak ditemukan (mungkin sudah diubah dari laptop lain).';

            return $this->request->isAJAX()
                ? $this->response->setJSON(['status' => 'error', 'message' => $pesan])
                : redirect()->to('/doorprize#picker')->with('error', $pesan);
        }

        if ($aksi === 'hangus') {
            $this->winnerModel->update($w['id'], ['hangus' => 1]);
            $this->catatLog("Doorprize hangus (tidak hadir): {$w['nama']} (NIP {$w['nip']})");
            $pesan = "Hadiah {$w['nama']} hangus. Unitnya bisa diundi ulang.";
        } else {
            $this->winnerModel->delete($w['id']);
            $this->catatLog("Hapus pemenang doorprize: {$w['nama']} (NIP {$w['nip']})");
            $pesan = "{$w['nama']} dihapus dari daftar pemenang dan bisa diundi lagi.";
        }

        if (! $this->request->isAJAX()) {
            return redirect()->to('/doorprize#picker')->with('success', $pesan);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => $pesan, 'aksi' => $aksi, 'id' => $id, 'nip' => $w['nip']]
            + $this->ringkasanUndian($idRat));
    }

    public function exportWinners()
    {
        $idRat   = $this->idRatDipilih();
        $rat     = $this->ratModel->find($idRat);
        $winners = $this->winnerModel->getByRat($idRat);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Pemenang Doorprize');
        ExcelExport::tabel($sheet, ['No.', 'Nama Barang', 'Kategori', 'NIP', 'Nama Pemenang', 'Departemen', 'Section', 'Waktu Undi', 'Status'], array_map(
            static fn ($w, $i) => [
                $i + 1, $w['nama_barang'] ?? '(barang dihapus)', ucfirst($w['kategori'] ?? '-'), $w['nip'], $w['nama'],
                $w['dept_live'] ?? '-', $w['section_live'] ?? '-', $w['waktu_undi'], $w['hangus'] ? 'Hangus (tidak hadir)' : 'Menang',
            ],
            $winners, array_keys($winners)
        ));

        return ExcelExport::unduh($spreadsheet, 'Pemenang_Doorprize_' . ($rat['tahun_buku'] ?? '') . '_' . date('Ymd') . '.xlsx');
    }

    // ─────────────────────────────────────────────
    //  Helper
    // ─────────────────────────────────────────────

    /**
     * Sesi yang benar-benar dipakai (punya daftar peserta). Sesi default yang
     * dibiarkan kosong tidak membuat semua orang gugur dari doorprize.
     */
    private function sesiDipakai(int $idRat): array
    {
        $jumlah = $this->pesertaModel->hitungPerSesi($idRat);
        $dipakai = [];
        foreach ($this->sesiModel->getByRat($idRat) as $s) {
            $s['jumlah_peserta'] = (int) ($jumlah[$s['id']] ?? 0);
            if ($s['jumlah_peserta'] > 0) $dipakai[] = $s;
        }

        return $dipakai;
    }

    /**
     * Angka & daftar hadiah terbaru untuk memperbarui halaman undian tanpa reload.
     */
    private function ringkasanUndian(int $idRat): array
    {
        $sisa = $this->itemModel->sisaBelumDiundi($idRat);

        return [
            'unit_sisa'     => array_sum(array_column($sisa, 'sisa')),
            'eligible_sisa' => count($this->poolUndian($idRat)),
            'total_menang'  => $this->winnerModel->where('id_rat', $idRat)->where('hangus', 0)->countAllResults(),
            'items'         => array_map(static fn ($i) => [
                'id' => (int) $i['id'], 'nama' => $i['nama_barang'], 'img' => $i['gambar_path'] ?? '',
                'kat' => $i['kategori'] ?? 'utama', 'sisa' => (int) $i['sisa'], 'jumlah' => (int) $i['jumlah'],
            ], $sisa),
        ];
    }

    /** Peserta eligible yang belum pernah menang (termasuk yang hangus) di RAT ini. */
    private function poolUndian(int $idRat): array
    {
        $summary = $this->absensiModel->getSummaryPerAnggota(array_column($this->sesiDipakai($idRat), 'id'), $idRat);
        $sudah   = $this->winnerModel->statusPerNip($idRat);

        return array_values(array_filter($summary, static fn ($p) => $p['eligible_doorprize'] && ! isset($sudah[$p['nip']])));
    }

    /** Dept + section: dari peserta RAT dulu, kalau kosong dari data induk. */
    private function deptSection(int $idRat, array $winner): array
    {
        $r = db_connect()->query(
            'SELECT MAX(NULLIF(p.departemen, "")) AS p_dept, MAX(NULLIF(p.section, "")) AS p_sect,
                    MAX(a.departemen) AS a_dept, MAX(a.section) AS a_sect
             FROM tb_peserta_rat p LEFT JOIN tb_anggota a ON a.nip = p.nip
             WHERE p.id_rat = ? AND p.nip = ?',
            [$idRat, $winner['nip']]
        )->getRowArray() ?? [];

        return [($r['p_dept'] ?? null) ?: (($r['a_dept'] ?? null) ?: '-'), ($r['p_sect'] ?? null) ?: (($r['a_sect'] ?? null) ?: '-')];
    }

    private function itemMilikRat(int $id): ?array
    {
        $item = $id ? $this->itemModel->find($id) : null;

        return ($item && (int) $item['id_rat'] === $this->idRatDipilih()) ? $item : null;
    }

    /** Data barang dari form, atau pesan error. */
    private function dataBarang(): array|string
    {
        $nama   = trim((string) $this->request->getPost('nama_barang'));
        $jumlah = (int) $this->request->getPost('jumlah');

        if ($nama === '') return 'Nama barang wajib diisi.';
        if ($jumlah < 1 || $jumlah > self::MAKS_UNIT) return 'Jumlah barang harus 1 sampai ' . self::MAKS_UNIT . '.';

        return [
            'nama_barang' => mb_substr($nama, 0, 200),
            'jumlah'      => $jumlah,
            'kategori'    => $this->request->getPost('kategori') === 'hiburan' ? 'hiburan' : 'utama',
        ];
    }

    /**
     * Simpan gambar barang, diperkecil ke maks. 800 px supaya halaman undian ringan.
     * Return path, null (tidak ada file), false (format ditolak), atau "!pesan" untuk error lain.
     */
    private function simpanGambar(): string|false|null
    {
        $file = $this->request->getFile('gambar');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) return null;
        if (! $file->isValid()) {
            return in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? '!Gambar terlalu besar untuk diunggah. Perkecil dulu (maks. ' . ini_get('upload_max_filesize') . ').'
                : '!Gambar gagal diunggah: ' . $file->getErrorString();
        }
        if ($file->getSize() > self::MAKS_GAMBAR) return '!Gambar maksimal 10 MB.';

        $info = @getimagesize($file->getTempName());
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return false;
        }

        $dir = FCPATH . 'uploads/doorprize/';
        if (! is_dir($dir)) mkdir($dir, 0755, true);

        $png  = $info[2] === IMAGETYPE_PNG; // PNG dipertahankan supaya latar transparan tidak hilang
        $nama = 'prize_' . bin2hex(random_bytes(6)) . ($png ? '.png' : '.jpg');

        try {
            $img = \Config\Services::image('gd')->withFile($file->getTempName());
            if (! $png) {
                $img->convert(IMAGETYPE_JPEG); // WEBP/JPEG disimpan sebagai JPEG sungguhan
                if (method_exists($img, 'reorient')) {
                    $img->reorient(); // foto HP yang miring diputar sesuai EXIF
                }
            }
            if (max($info[0], $info[1]) > self::SISI_GAMBAR) {
                $img->resize(self::SISI_GAMBAR, self::SISI_GAMBAR, true, $info[0] >= $info[1] ? 'width' : 'height');
            }
            $img->save($dir . $nama, 82);
        } catch (\Throwable) {
            return '!Gambar tidak bisa diproses. Coba simpan ulang sebagai JPG.';
        }

        return 'uploads/doorprize/' . $nama;
    }

    private function hapusFileGambar(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/doorprize/') && is_file(FCPATH . $path)) {
            unlink(FCPATH . $path);
        }
    }
}
