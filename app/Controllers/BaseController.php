<?php

namespace App\Controllers;

use App\Models\LogAktivitasModel;
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
     * ID RAT yang sedang dipilih di session (0 kalau belum ada).
     */
    protected function idRatDipilih(): int
    {
        return (int) session()->get('rat_dipilih');
    }
}
