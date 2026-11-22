<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('pengguna_id')) {
            return redirect()->to('login')->with('galat', 'Silakan masuk dahulu.');
        }
        if (session()->get('harus_ganti_sandi')) {
            $path = $request->getUri()->getPath();
            if (! str_contains($path, 'profil') && ! str_contains($path, 'logout')) {
                return redirect()->to('profil')->with('galat', 'Demi keamanan, ganti kata sandi Anda terlebih dahulu.');
            }
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
