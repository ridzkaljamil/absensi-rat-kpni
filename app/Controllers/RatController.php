<?php

namespace App\Controllers;

use App\Models\RatModel;
use App\Models\SesiModel;
use App\Models\AnggotaModel;
use App\Models\PesertaRatModel;

class RatController extends BaseController
{
    protected $ratModel;
    protected $sesiModel;
    protected $anggotaModel;
    protected $pesertaModel;

    public function __construct()
    {
        $this->ratModel     = new RatModel();
        $this->sesiModel    = new SesiModel();
        $this->anggotaModel = new AnggotaModel();
        $this->pesertaModel = new PesertaRatModel();
    }

    public function index()
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');

        $daftarRat = $this->ratModel->getAllOrdered();

        // Total peserta unik per RAT (satu query)
        $jumlah = array_column(db_connect()->query(
            'SELECT id_rat, COUNT(DISTINCT nip) AS n FROM tb_peserta_rat GROUP BY id_rat'
        )->getResultArray(), 'n', 'id_rat');
        foreach ($daftarRat as &$r) {
            $r['total_peserta'] = (int) ($jumlah[$r['id']] ?? 0);
        }
        unset($r);

        return view('rat/index', ['daftarRat' => $daftarRat]);
    }

    public function store()
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');

        $rules = [
            'nama_rat'   => 'required|min_length[5]|max_length[150]',
            'tahun_buku' => 'required|is_natural_no_zero|exact_length[4]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $tahunBuku = (int) $this->request->getPost('tahun_buku');

        // Cek duplikasi tahun buku
        if ($this->ratModel->tahunBukuSudahAda($tahunBuku)) {
            return redirect()->back()->withInput()
                ->with('error', "RAT untuk Tahun Buku {$tahunBuku} sudah pernah dibuat.");
        }

        // Set RAT aktif lama menjadi selesai
        $ratAktifLama = $this->ratModel->where('status', 'aktif')->first();
        if ($ratAktifLama) {
            $this->ratModel->update($ratAktifLama['id'], ['status' => 'selesai']);
        }

        // Insert RAT baru
        $namaRat   = trim($this->request->getPost('nama_rat'));
        $idRatBaru = $this->ratModel->insert([
            'nama_rat'   => $namaRat,
            'tahun_buku' => $tahunBuku,
            'tanggal'    => $this->request->getPost('tanggal') ?: null,
            'lokasi'     => trim($this->request->getPost('lokasi') ?? ''),
            'status'     => 'aktif',
        ]);

        // Buat 5 sesi default (Sesi 1 - Sesi 5) — bisa diubah di Kelola Sesi
        $this->sesiModel->buatSesiDefault($idRatBaru);

        // ── AUTO-SET SESSION ─────────────────────────────────────
        // RAT baru otomatis jadi RAT aktif yang dipilih
        session()->set('rat_dipilih', $idRatBaru);
        session()->set('rat_dipilih_label', label_rat(['nama_rat' => $namaRat, 'tahun_buku' => $tahunBuku]));

        $this->catatLog("Buat RAT baru: {$namaRat} ({$tahunBuku})");

        return redirect()->to('/rat')
            ->with('success', "RAT Tahun Buku {$tahunBuku} berhasil dibuat. Silakan import data peserta RAT terlebih dahulu.");
    }

    public function pilih(int $idRat)
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');

        $rat = $this->ratModel->find($idRat);
        if (!$rat) {
            return redirect()->back()->with('error', 'Data RAT tidak ditemukan.');
        }

        session()->set('rat_dipilih', $idRat);
        session()->set('rat_dipilih_label', label_rat($rat));

        $this->catatLog("Pilih RAT: {$rat['nama_rat']} ({$rat['tahun_buku']})");

        return redirect()->to('/dashboard')
            ->with('success', 'Menampilkan data: ' . $rat['nama_rat']);
    }

    public function selesaikan(int $idRat)
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');

        $rat = $this->ratModel->find($idRat);
        if (!$rat) return redirect()->to('/rat')->with('error', 'RAT tidak ditemukan.');

        $this->ratModel->update($idRat, ['status' => 'selesai']);
        $this->catatLog("Selesaikan RAT: {$rat['nama_rat']} ({$rat['tahun_buku']})");

        return redirect()->to('/rat')->with('success', "{$rat['nama_rat']} telah ditandai selesai.");
    }

    public function hapus(int $idRat)
    {
        if (session('role') !== 'admin') return redirect()->to('/dashboard');

        $rat = $this->ratModel->find($idRat);
        if (!$rat) return redirect()->to('/rat')->with('error', 'RAT tidak ditemukan.');

        // Hapus session jika RAT yang dihapus adalah yang sedang dipilih
        if (session()->get('rat_dipilih') == $idRat) {
            session()->remove('rat_dipilih');
            session()->remove('rat_dipilih_label');
        }

        $this->ratModel->delete($idRat);
        $this->catatLog("Hapus RAT: {$rat['nama_rat']} ({$rat['tahun_buku']})");

        return redirect()->to('/rat')
            ->with('success', "RAT {$rat['nama_rat']} dan semua data terkait telah dihapus.");
    }

}
