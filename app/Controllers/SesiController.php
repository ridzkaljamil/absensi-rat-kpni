<?php

namespace App\Controllers;

use App\Models\RatModel;
use App\Models\SesiModel;

class SesiController extends BaseController
{
    protected SesiModel $sesiModel;
    protected RatModel $ratModel;

    public function __construct()
    {
        $this->sesiModel = new SesiModel();
        $this->ratModel  = new RatModel();
    }

    public function index()
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return redirect()->to('/rat')->with('error', 'Pilih RAT terlebih dahulu.');
        }

        $daftarSesi = $this->sesiModel->getByRat($idRat);
        $absensi    = (new \App\Models\AbsensiModel())->hitungPerSesi(array_column($daftarSesi, 'id'));
        $peserta    = (new \App\Models\PesertaRatModel())->hitungPerSesi($idRat);

        return view('sesi/index', [
            'rat'        => $this->ratModel->find($idRat),
            'daftarSesi' => $daftarSesi,
            'jmlAbsensi' => $absensi,
            'jmlPeserta' => $peserta,
        ]);
    }

    public function store()
    {
        $idRat = $this->idRatDipilih();
        $nama  = trim((string) $this->request->getPost('nama_sesi'));
        if (! $idRat || $nama === '') {
            return redirect()->back()->with('error', 'Nama sesi wajib diisi.');
        }

        $waktu = $this->validasiWaktu();
        if (is_string($waktu)) {
            return redirect()->back()->with('error', $waktu);
        }

        // Urutan = urutan terbesar + 1 (aman walau ada sesi yang pernah dihapus)
        $maks = $this->sesiModel->selectMax('urutan')->where('id_rat', $idRat)->first();

        $this->sesiModel->insert([
            'id_rat'        => $idRat,
            'nama_sesi'     => $nama,
            'urutan'        => (int) ($maks['urutan'] ?? 0) + 1,
            'waktu_mulai'   => $waktu[0],
            'waktu_selesai' => $waktu[1],
            'status'        => 'aktif',
        ]);

        $this->catatLog('Menambah sesi baru: ' . label_sesi($nama));

        return redirect()->to('/sesi')->with('success', label_sesi($nama) . ' berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $sesi = $this->sesiMilikRat($id);
        if (! $sesi) {
            return redirect()->to('/sesi')->with('error', 'Sesi tidak ditemukan.');
        }
        if ($sesi['status'] === 'terkunci') {
            return redirect()->to('/sesi')->with('error', 'Sesi terkunci tidak bisa diubah. Buka kunci terlebih dahulu.');
        }

        $nama = trim((string) $this->request->getPost('nama_sesi'));
        if ($nama === '') {
            return redirect()->to('/sesi')->with('error', 'Nama sesi wajib diisi.');
        }
        $waktu = $this->validasiWaktu();
        if (is_string($waktu)) {
            return redirect()->to('/sesi')->with('error', $waktu);
        }

        $this->sesiModel->update($id, [
            'nama_sesi'     => $nama,
            'waktu_mulai'   => $waktu[0],
            'waktu_selesai' => $waktu[1],
        ]);
        $this->catatLog('Mengubah sesi: ' . label_sesi($nama) . ' (' . substr($waktu[0], 0, 5) . '–' . substr($waktu[1], 0, 5) . ')');

        return redirect()->to('/sesi')->with('success', 'Data ' . label_sesi($nama) . ' berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        $sesi = $this->sesiMilikRat($id);
        if (! $sesi) {
            return redirect()->to('/sesi')->with('error', 'Sesi tidak ditemukan.');
        }

        $label  = label_sesi($sesi['nama_sesi']);
        $jumlah = db_connect()->table('tb_absensi')->where('id_sesi', $id)->countAllResults();
        if ($jumlah > 0) {
            return redirect()->to('/sesi')->with('error', "{$label} sudah memiliki {$jumlah} data absensi dan tidak bisa dihapus.");
        }

        $this->sesiModel->delete($id); // peserta sesi ikut terhapus (FK cascade)
        $this->catatLog("Menghapus sesi: {$label}");

        return redirect()->to('/sesi')->with('success', "{$label} berhasil dihapus.");
    }

    public function lock(int $id)
    {
        $sesi = $this->sesiMilikRat($id);
        if (! $sesi) {
            return redirect()->to('/sesi')->with('error', 'Sesi tidak ditemukan.');
        }

        $this->sesiModel->lock($id, (int) session()->get('user_id'));
        $label = label_sesi($sesi['nama_sesi']);
        $this->catatLog("Mengunci sesi: {$label}");

        return redirect()->to('/sesi')->with('success', "{$label} berhasil dikunci.");
    }

    public function unlock(int $id)
    {
        $sesi = $this->sesiMilikRat($id);
        if (! $sesi) {
            return redirect()->to('/sesi')->with('error', 'Sesi tidak ditemukan.');
        }

        $this->sesiModel->unlock($id);
        $label = label_sesi($sesi['nama_sesi']);
        $this->catatLog("Membuka kunci sesi: {$label}");

        return redirect()->to('/sesi')->with('success', "{$label} berhasil dibuka.");
    }

    // ─────────────────────────────────────────────

    private function sesiMilikRat(int $id): ?array
    {
        $sesi = $this->sesiModel->find($id);

        return ($sesi && (int) $sesi['id_rat'] === $this->idRatDipilih()) ? $sesi : null;
    }

    /**
     * Ambil & validasi jam mulai/selesai format 24 jam (HH:MM).
     * Return [mulai, selesai] atau pesan error.
     */
    private function validasiWaktu(): array|string
    {
        $mulai   = jam_valid($this->request->getPost('waktu_mulai'));
        $selesai = jam_valid($this->request->getPost('waktu_selesai'));

        if (! $mulai || ! $selesai) {
            return 'Format jam harus 24 jam, contoh 08:00 atau 13:30.';
        }
        if ($selesai <= $mulai) {
            return 'Jam selesai harus lebih besar dari jam mulai.';
        }

        return [$mulai, $selesai];
    }
}
