<?php

namespace App\Controllers;

use App\Models\UserModel;

class UserController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        return view('users/index', [
            'daftarUser' => $this->userModel->orderBy('role','ASC')->orderBy('nama_lengkap','ASC')->findAll(),
        ]);
    }

    public function store()
    {
        $rules = [
            'username'     => ['rules'=>'required|min_length[3]|max_length[50]|is_unique[tb_users.username]','errors'=>['required'=>'Username wajib diisi.','is_unique'=>'Username sudah dipakai.','min_length'=>'Username minimal 3 karakter.']],
            'nama_lengkap' => ['rules'=>'required|min_length[3]','errors'=>['required'=>'Nama wajib diisi.']],
            'role'         => ['rules'=>'required|in_list[admin,user]','errors'=>['required'=>'Role wajib dipilih.']],
            'password'     => ['rules'=>'required|min_length[6]','errors'=>['required'=>'Password wajib diisi.','min_length'=>'Password minimal 6 karakter.']],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $this->userModel->insert([
            'username'     => $this->request->getPost('username'),
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'role'         => $this->request->getPost('role'),
            'password'     => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
        ]);

        $this->catatLog("Membuat akun baru: {$this->request->getPost('username')} ({$this->request->getPost('role')})");
        return redirect()->to('/users')->with('success', 'Akun berhasil dibuat.');
    }

    public function update(int $id)
    {
        $user = $this->userModel->find($id);
        if (! $user) return redirect()->to('/users')->with('error', 'User tidak ditemukan.');

        // Jangan bisa edit akun sendiri (biar tidak ada yang kunci diri sendiri)
        if ($id == session()->get('user_id')) {
            return redirect()->to('/users')->with('error', 'Tidak bisa mengedit akun Anda sendiri dari sini. Gunakan menu Ganti Password.');
        }

        $nama     = trim($this->request->getPost('nama_lengkap'));
        $username = trim($this->request->getPost('username'));
        $role     = $this->request->getPost('role');
        $password = trim($this->request->getPost('password') ?? '');

        if (! $this->validate([
            'username'     => ['rules'=>'required|min_length[3]|max_length[50]','errors'=>['required'=>'Username wajib diisi.','min_length'=>'Username minimal 3 karakter.']],
            'nama_lengkap' => ['rules'=>'required|min_length[3]','errors'=>['required'=>'Nama wajib diisi.']],
            'role'         => ['rules'=>'required|in_list[admin,user]'],
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        // Cek username unik (kecuali milik user ini sendiri)
        $existing = $this->userModel->where('username', $username)->where('id !=', $id)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Username sudah dipakai oleh akun lain.');
        }

        $data = ['nama_lengkap'=>$nama, 'username'=>$username, 'role'=>$role];
        if ($password !== '') {
            if (strlen($password) < 6) {
                return redirect()->back()->with('error', 'Password baru minimal 6 karakter.');
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $data);
        $this->catatLog("Mengubah akun: {$username} (role: {$role})");
        return redirect()->to('/users')->with('success', 'Akun berhasil diperbarui.');
    }

    public function hapus(int $id)
    {
        if ($id == session()->get('user_id')) {
            return redirect()->to('/users')->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        $user = $this->userModel->find($id);
        if (! $user) return redirect()->to('/users')->with('error', 'User tidak ditemukan.');

        $this->userModel->delete($id);
        $this->catatLog("Menghapus akun: {$user['username']}");
        return redirect()->to('/users')->with('success', "Akun {$user['username']} berhasil dihapus.");
    }

}
