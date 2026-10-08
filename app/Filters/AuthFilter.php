<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Cek status login. Kalau belum login, tendang ke halaman login.
     *
     * Kalau filter dipanggil dengan argumen (misal 'admin'), berarti
     * halaman ini khusus Admin - User yang mencoba akses akan ditolak.
     * Dipakai di Routes.php seperti: ->filter('auth:admin')
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if ($arguments && in_array('admin', $arguments, true)) {
            if ($session->get('role') !== 'admin') {
                return redirect()->to('/dashboard')
                    ->with('error', 'Halaman ini hanya bisa diakses oleh Admin.');
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada yang perlu dilakukan setelah response.
    }
}
