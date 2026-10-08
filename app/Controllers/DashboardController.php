<?php

namespace App\Controllers;

use App\Models\AbsensiModel;
use App\Models\PesertaRatModel;
use App\Models\RatModel;
use App\Models\SesiModel;

class DashboardController extends BaseController
{
    protected AbsensiModel $absensiModel;
    protected SesiModel $sesiModel;
    protected RatModel $ratModel;
    protected PesertaRatModel $pesertaModel;

    public function __construct()
    {
        $this->absensiModel = new AbsensiModel();
        $this->sesiModel    = new SesiModel();
        $this->ratModel     = new RatModel();
        $this->pesertaModel = new PesertaRatModel();
    }

    public function index()
    {
        $idRat = $this->idRatDipilih();

        // Auto-pilih RAT aktif kalau belum ada yang dipilih
        if (! $idRat) {
            $ratAktif = $this->ratModel->getActive();
            if ($ratAktif) {
                $idRat = (int) $ratAktif['id'];
                session()->set('rat_dipilih', $idRat);
                session()->set('rat_dipilih_label', label_rat($ratAktif));
            }
        }

        $rat = $idRat ? $this->ratModel->find($idRat) : null;
        if (! $rat) {
            return view('dashboard/index', ['rat' => null]);
        }

        return view('dashboard/index', ['rat' => $rat] + $this->ringkasan($idRat));
    }

    /**
     * Endpoint polling (setiap 5 detik) — data yang sama dengan index().
     */
    public function polling()
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return $this->response->setJSON(['error' => 'Tidak ada RAT yang dipilih.']);
        }

        $data = $this->ringkasan($idRat);

        return $this->response->setJSON([
            'sesi' => array_map(static fn ($s) => [
                'id_sesi'      => $s['sesi']['id'],
                'status'       => $s['sesi']['status'],
                'jumlah'       => $s['jumlah'],
                'total'        => $s['total'],
                'persen'       => $s['persen'],
                'kuorum'       => $s['kuorum'],
                'kuorum_capai' => $s['kuorum_capai'],
            ], $data['sesi_data']),
            'max_hadir'    => $data['max_hadir'],
            'sesi_capai'   => $data['sesi_capai'],
            'sesi_dinilai' => $data['sesi_dinilai'],
            'waktu'        => date('H:i:s'),
        ]);
    }

    /**
     * Hitung data dashboard. Cukup 3 query (sesi, jumlah hadir, jumlah peserta)
     * berapa pun banyaknya sesi — penting karena dipanggil polling tiap 5 detik.
     */
    private function ringkasan(int $idRat): array
    {
        $daftarSesi   = $this->sesiModel->getByRat($idRat);
        $hadirPerSesi = $this->absensiModel->hitungPerSesi(array_column($daftarSesi, 'id'));
        $pesertaSesi  = $this->pesertaModel->hitungPerSesi($idRat);

        $sesiData    = [];
        $maxHadir    = 0;
        $sesiDinilai = 0; // sesi yang sudah punya daftar peserta
        $sesiCapai   = 0;

        foreach ($daftarSesi as $sesi) {
            $id     = (int) $sesi['id'];
            $jumlah = (int) ($hadirPerSesi[$id] ?? 0);
            $total  = (int) ($pesertaSesi[$id] ?? 0);
            $kuorum = hitung_kuorum($total);
            // Sesi tanpa peserta TIDAK boleh dianggap kuorum (dulu: 0 >= 0 = tercapai)
            $capai  = $total > 0 && $jumlah >= $kuorum;

            if ($total > 0) $sesiDinilai++;
            if ($capai) $sesiCapai++;
            $maxHadir = max($maxHadir, $jumlah);

            $sesiData[] = [
                'sesi'         => $sesi,
                'jumlah'       => $jumlah,
                'total'        => $total,
                'persen'       => $total > 0 ? (int) round($jumlah / $total * 100) : 0,
                'kuorum'       => $kuorum,
                'kuorum_capai' => $capai,
            ];
        }

        return [
            'sesi_data'     => $sesiData,
            'total_peserta' => $this->pesertaModel->totalPeserta($idRat),
            'max_hadir'     => $maxHadir,
            'sesi_dinilai'  => $sesiDinilai,
            'sesi_capai'    => $sesiCapai,
        ];
    }
}
