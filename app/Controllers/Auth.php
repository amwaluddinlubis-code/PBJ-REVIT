<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('pengguna_id')) {
            return redirect()->to('/');
        }
        return view('auth/login', ['judul' => 'Masuk — Analisa Perencana']);
    }

    public function proses()
    {
        $username = (string) $this->request->getPost('username');
        $sandi    = (string) $this->request->getPost('sandi');
        $user = $this->db()->table('users')->where('username', $username)->get()->getRowArray();
        if (! $user || ! password_verify($sandi, $user['password_hash'])) {
            return redirect()->back()->with('galat', 'Username atau kata sandi salah.');
        }
        session()->set([
            'pengguna_id'       => (int) $user['id'],
            'pengguna_nama'     => $user['nama'],
            'pengguna_username' => $user['username'],
            'pengguna_peran'    => $user['peran'],
        ]);
        return redirect()->to('/')->with('sukses', 'Selamat datang, ' . $user['nama'] . '.');
    }

    public function keluar()
    {
        session()->destroy();
        return redirect()->to('login')->with('sukses', 'Anda sudah keluar.');
    }
}
