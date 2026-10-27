<?php

namespace App\Controllers;

use App\Models\LogAktivitasModel;
use App\Models\RatModel;
use App\Models\SesiModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController — semua controller aplikasi extend class ini.
 *
 * Fungsi bersama yang dipakai lintas controller diletakkan di sini
 * (sebelumnya catatLog() diduplikasi di setiap controller).
 */
abstract class BaseController extends Controller
{
    /**
     * Helper yang otomatis di-load untuk semua controller (dan view-nya).
     *
     * @var list<string>
     */
    protected $helpers = ['kpni'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);
    }

    /**
     * F-08: catat aksi penting ke tb_log_aktivitas.
     */
    protected function catatLog(string $aksi, ?int $idUser = null): void
    {
        $idUser ??= session()->get('user_id');
        if (! $idUser) {
            return;
        }

        (new LogAktivitasModel())->insert([
            'id_user' => $idUser,
            'aksi'    => mb_substr($aksi, 0, 200),
            'waktu'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * ID RAT yang sedang dipakai (0 kalau belum ada RAT sama sekali).
     *
     * - Admin: RAT yang dipilih di menu RAT; kalau belum memilih, RAT aktif.
     * - Operator (role user): selalu RAT aktif, supaya perangkat absensi
     *   otomatis pindah ke RAT baru tanpa perlu logout.
     */
    protected function idRatDipilih(): int
    {
        $session = session();
        $id      = (int) $session->get('rat_dipilih');
        if ($id && ! (new RatModel())->find($id)) {
            $id = 0; // RAT yang dipilih sudah dihapus (mis. oleh admin lain)
        }

        if ($session->get('role') !== 'admin' || ! $id) {
            $aktif   = (new RatModel())->getActive();
            $idAktif = $aktif ? (int) $aktif['id'] : 0;
            if ($idAktif !== $id) {
                $session->set(['rat_dipilih' => $idAktif ?: null, 'rat_dipilih_label' => $aktif ? label_rat($aktif) : null]);
            }
            $id = $idAktif;
        }

        return $id;
    }

    /**
     * Sesi berdasarkan id, hanya kalau milik RAT yang sedang dipakai.
     */
    protected function sesiMilikRat(int $idSesi): ?array
    {
        if ($idSesi <= 0) {
            return null;
        }
        $sesi = (new SesiModel())->find($idSesi);

        return ($sesi && (int) $sesi['id_rat'] === $this->idRatDipilih()) ? $sesi : null;
    }
}
