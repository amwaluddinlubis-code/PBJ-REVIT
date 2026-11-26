<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('pengguna_id')) {
            return redirect()->to('/');
        }
        return view($this->loginVersion() === 'v2' ? 'auth/login_v2' : 'auth/login', ['judul' => 'Masuk — Analisa Perencana']);
    }

    private function loginVersion(): string
    {
        try {
            if ($this->db()->tableExists('aplikasi_pengaturan')) {
                $nilai = $this->db()->table('aplikasi_pengaturan')->where('kunci', 'login_version')->get()->getRowArray()['nilai'] ?? 'v1';
                return in_array($nilai, ['v1', 'v2'], true) ? $nilai : 'v1';
            }
        } catch (\Throwable $e) {
            // Instalasi lama tetap memakai login v1 sampai migrasi dijalankan.
        }
        return 'v1';
    }

    public function proses()
    {
        $username = (string) $this->request->getPost('username');
        $sandi    = (string) $this->request->getPost('sandi');
        // Pembatasan percobaan masuk: maks 5 kegagalan per pengguna+IP per 10 menit.
        $cache = \Config\Services::cache();
        $kunci = 'login_gagal_' . md5(mb_strtolower($username) . '|' . $this->request->getIPAddress());
        $gagal = (int) ($cache->get($kunci) ?? 0);
        if ($gagal >= 5) {
            return redirect()->back()->with('galat', 'Terlalu banyak percobaan masuk yang gagal. Coba lagi dalam 10 menit.');
        }
        $user = $this->db()->table('users')->where(['username' => $username, 'is_deleted' => 0])->get()->getRowArray();
        if (! $user || ! password_verify($sandi, $user['password_hash'])) {
            $cache->save($kunci, $gagal + 1, 600);
            return redirect()->back()->with('galat', 'Username atau kata sandi salah.');
        }
        if (isset($user['aktif']) && (int) $user['aktif'] === 0) {
            return redirect()->back()->with('galat', 'Akun ini nonaktif. Hubungi administrator.');
        }
        $cache->delete($kunci);
        session()->regenerate(true);
        session()->set([
            'pengguna_id'       => (int) $user['id'],
            'pengguna_nama'     => $user['nama'],
            'pengguna_username' => $user['username'],
            'pengguna_peran'    => $user['peran'],
            'harus_ganti_sandi' => ! empty($user['harus_ganti_sandi']),
        ]);
        try {
            $db = $this->db();
            if ($db->tableExists('audit_events')) {
                $db->table('audit_events')->insert([
                    'sekolah_id' => null, 'user_id' => (int) $user['id'], 'username' => $user['username'],
                    'entitas' => 'akun', 'entitas_id' => (int) $user['id'], 'aksi' => 'masuk',
                    'keterangan' => 'Masuk sebagai ' . $user['peran'], 'ip' => $this->request->getIPAddress(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            // abaikan
        }
        return redirect()->to('/')->with('sukses', 'Selamat datang, ' . $user['nama'] . '.');
    }

    public function keluar()
    {
        session()->destroy();
        return redirect()->to('login')->with('sukses', 'Anda sudah keluar.');
    }

    /** Sentuh sesi (dipanggil peringatan sesi di layout): menulis penanda
     *  waktu agar sesi diperbarui tanpa mengubah data apa pun. */
    public function sentuhSesi()
    {
        session()->set('sentuh_sesi', time());
        return $this->response->setJSON(['ok' => true]);
    }
}
