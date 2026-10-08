<?php

namespace App\Controllers;

use App\Models\LogAktivitasModel;

class LogAktivitasController extends BaseController
{
    protected LogAktivitasModel $logModel;

    public function __construct()
    {
        $this->logModel = new LogAktivitasModel();
    }

    /**
     * F-08: Tampilkan log aktivitas sistem, terbaru duluan.
     * Acceptance criteria PRD: setiap aksi penting (lock sesi, import
     * data, download rekap) tercatat dengan nama Admin + timestamp,
     * dan bisa dilihat dalam daftar terurut dari terbaru.
     */
    public function index()
    {
        $keyword = $this->request->getGet('cari');

        $builder = $this->logModel
            ->select('tb_log_aktivitas.*, tb_users.nama_lengkap, tb_users.username')
            ->join('tb_users', 'tb_users.id = tb_log_aktivitas.id_user', 'left')
            ->orderBy('tb_log_aktivitas.waktu', 'DESC');

        if ($keyword) {
            $builder = $builder->groupStart()
                ->like('tb_log_aktivitas.aksi', $keyword)
                ->orLike('tb_users.nama_lengkap', $keyword)
                ->groupEnd();
        }

        $data = [
            'daftarLog' => $builder->paginate(10, 'default'),
            'pager'     => $this->logModel->pager,
            'keyword'   => $keyword,
        ];

        return view('log/index', $data);
    }

    public function bersihkan()
    {
        $db = \Config\Database::connect();
        $db->table('tb_log_aktivitas')->truncate();
        return redirect()->to('/log')->with('success', 'Semua log aktivitas berhasil dibersihkan.');
    }
}
