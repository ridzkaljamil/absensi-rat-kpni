<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'tb_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['username', 'password', 'role', 'nama_lengkap'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Cari user berdasarkan username (untuk proses login).
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Update password user. Password baru di-hash di sini supaya
     * Controller tidak perlu mengulang logic hashing (F-16).
     */
    public function updatePassword(int $id, string $newPlainPassword): bool
    {
        return $this->update($id, [
            'password' => password_hash($newPlainPassword, PASSWORD_DEFAULT),
        ]);
    }
}
