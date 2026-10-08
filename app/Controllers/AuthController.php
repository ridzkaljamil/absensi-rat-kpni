<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * F-01 / F-09: Tampilkan halaman login.
     * Kalau sudah login, langsung arahkan ke dashboard sesuai role.
     */
    public function loginPage()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    /**
     * F-01 / F-09: Proses validasi kredensial login.
     */
    public function doLogin()
    {
        $isAjax = $this->request->isAJAX() ||
                  $this->request->getHeaderLine('X-Requested-With') === 'kpni-ajax';

        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[3]',
        ];

        if (! $this->validate($rules)) {
            if ($isAjax) return $this->response->setJSON(['ok'=>false,'msg'=>'Username dan password wajib diisi.']);
            return redirect()->back()->withInput()->with('error', 'Username dan password wajib diisi.');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $user     = $this->userModel->findByUsername($username);

        if (! $user || ! password_verify($password, $user['password'])) {
            if ($isAjax) return $this->response->setJSON(['ok'=>false,'msg'=>'Username atau password salah.']);
            return redirect()->back()->withInput()->with('error', 'Username atau password salah.');
        }

        session()->regenerate(true); // cegah session fixation
        session()->set([
            'isLoggedIn'   => true,
            'user_id'      => $user['id'],
            'username'     => $user['username'],
            'role'         => $user['role'],
            'nama_lengkap' => $user['nama_lengkap'],
        ]);

        $this->catatLog('Login ke sistem', (int) $user['id']);

        if ($isAjax) return $this->response->setJSON(['ok'=>true,'redirect'=>'/dashboard']);
        return redirect()->to('/dashboard');
    }

    /**
     * Logout - hapus seluruh session dan kembali ke halaman login.
     */
    public function logout()
    {
        // Catat log logout sebelum session dihancurkan (F-08)
        $this->catatLog('Logout dari sistem');
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Anda telah logout.');
    }

    /**
     * F-16: Tampilkan form ganti password.
     */
    public function changePasswordPage()
    {
        return view('auth/change_password');
    }

    /**
     * F-16: Proses ganti password.
     * Acceptance criteria (PRD): password lama yang salah harus ditolak
     * dengan pesan error, bukan langsung diizinkan ganti.
     */
    public function doChangePassword()
    {
        $rules = [
            'old_password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Password lama wajib diisi.',
                ],
            ],
            'new_password' => [
                'rules' => 'required|min_length[6]',
                'errors' => [
                    'required'   => 'Password baru wajib diisi.',
                    'min_length' => 'Password baru minimal 6 karakter.',
                ],
            ],
            'confirm_password' => [
                'rules' => 'required|matches[new_password]',
                'errors' => [
                    'required' => 'Konfirmasi password wajib diisi.',
                    'matches'  => 'Konfirmasi password tidak sama dengan password baru.',
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $userId   = session()->get('user_id');
        $user     = $this->userModel->find($userId);
        $oldInput = $this->request->getPost('old_password');

        if (! $user || ! password_verify($oldInput, $user['password'])) {
            return redirect()->back()->with('error', 'Password lama yang Anda masukkan salah.');
        }

        $this->userModel->updatePassword($userId, $this->request->getPost('new_password'));
        $this->catatLog('Mengganti password akun sendiri');

        return redirect()->to('/dashboard')->with('success', 'Password berhasil diubah.');
    }
}
