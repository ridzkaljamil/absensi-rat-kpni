<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Wajib login. Dengan argumen 'admin' (->filter('auth:admin')) halaman khusus Admin.
 *
 * Akun dicek ulang ke database setiap request, supaya user yang dihapus
 * atau diturunkan role-nya langsung kehilangan akses walau session masih hidup.
 * Request AJAX/fetch mendapat JSON 401/403, bukan halaman login HTML.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user    = $session->get('isLoggedIn')
            ? (new UserModel())->select('id, username, role, nama_lengkap, password')->find((int) $session->get('user_id'))
            : null;

        // Akun dihapus, atau password diganti di perangkat lain/oleh admin → wajib login ulang
        $sandiBerubah = $user && $session->get('tanda_sandi') !== substr(hash('sha256', $user['password']), 0, 16);
        if (! $user || $sandiBerubah) {
            $pesan = ! $session->get('isLoggedIn') ? 'Silakan login terlebih dahulu.'
                : ($user ? 'Password akun ini sudah diganti. Silakan login ulang.' : 'Akun ini sudah tidak aktif. Hubungi Admin.');
            if ($session->get('isLoggedIn')) {
                // Data login dihapus tanpa menghancurkan session, supaya pesan di halaman login tetap tampil
                $session->remove(['isLoggedIn', 'user_id', 'username', 'role', 'nama_lengkap', 'tanda_sandi', 'rat_dipilih', 'rat_dipilih_label', 'absen_saya']);
                $session->regenerate(true);
            }

            return $this->tolak($request, 401, $pesan, '/login');
        }

        // Role & nama selalu mengikuti database
        if ($session->get('role') !== $user['role'] || $session->get('nama_lengkap') !== $user['nama_lengkap']) {
            $session->set(['role' => $user['role'], 'nama_lengkap' => $user['nama_lengkap']]);
        }

        if ($arguments && in_array('admin', $arguments, true) && $user['role'] !== 'admin') {
            return $this->tolak($request, 403, 'Halaman ini hanya bisa diakses oleh Admin.', '/dashboard');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    private function tolak(RequestInterface $request, int $status, string $pesan, string $ke)
    {
        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return service('response')->setStatusCode($status)->setJSON([
                'status'  => 'error',
                'kode'    => $status === 401 ? 'sesi_habis' : 'akses_ditolak',
                'message' => $pesan,
            ]);
        }

        return redirect()->to($ke)->with('error', $pesan);
    }
}
