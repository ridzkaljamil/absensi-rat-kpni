<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * F-08: log aktivitas. Ditulis lewat BaseController::catatLog(),
 * ditampilkan di LogAktivitasController.
 */
class LogAktivitasModel extends Model
{
    protected $table         = 'tb_log_aktivitas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_user', 'aksi', 'waktu'];

    protected $useTimestamps = false; // tabel ini pakai kolom 'waktu' sendiri, bukan created_at/updated_at
}
