<?php

namespace App\Controllers;

/**
 * Manajemen User (khusus admin) & Profil Saya (semua pengguna):
 * daftar pengguna, tambah/ubah/hapus, ubah nama profil, ganti kata sandi.
 */
class Pengguna extends BaseController
{
    private const PERAN = ['admin', 'ketua', 'bendahara', 'pelaksana', 'kepsek', 'operator'];

    private function admin(): bool
    {
        return ($this->pengguna['peran'] ?? '') === 'admin';
    }

    public function index()
    {
        if (! $this->admin()) {
            return redirect()->to('/')->with('galat', 'Manajemen user hanya untuk administrator.');
        }
        return $this->tampil('pengguna/index', [
            'daftar' => $this->db()->table('users')->where('is_deleted', 0)->orderBy('id')->get()->getResultArray(),
            'pilihan_peran' => self::PERAN,
            'saya' => (int) ($this->pengguna['id'] ?? 0),
        ], 'Manajemen User');
    }

    public function simpan()
    {
        if (! $this->admin()) {
            return redirect()->to('/');
        }
        $r = $this->request;
        $username = trim((string) $r->getPost('username'));
        $nama = trim((string) $r->getPost('nama'));
        $peran = (string) $r->getPost('peran');
        $sandi = (string) $r->getPost('sandi');
        if ($username === '' || $nama === '' || strlen($sandi) < 6) {
            return redirect()->to('/pengguna')->with('galat', 'Username, nama wajib diisi dan kata sandi minimal 6 karakter.');
        }
        if (! in_array($peran, self::PERAN, true)) {
            $peran = 'operator';
        }
        $ada = $this->db()->table('users')->where(['username' => $username, 'is_deleted' => 0])->countAllResults();
        if ($ada > 0) {
            return redirect()->to('/pengguna')->with('galat', 'Username "' . $username . '" sudah dipakai pengguna lain.');
        }
        $this->db()->table('users')->insert([
            'username' => $username, 'nama' => $nama, 'peran' => $peran,
            'password_hash' => password_hash($sandi, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/pengguna')->with('sukses', 'Pengguna "' . $username . '" ditambahkan.');
    }

    public function ubah($id)
    {
        if (! $this->admin()) {
            return redirect()->to('/');
        }
        $id = (int) $id;
        $r = $this->request;
        $data = [
            'nama' => trim((string) $r->getPost('nama')),
            'peran' => in_array((string) $r->getPost('peran'), self::PERAN, true) ? (string) $r->getPost('peran') : 'operator',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($data['nama'] === '') {
            return redirect()->to('/pengguna')->with('galat', 'Nama pengguna tidak boleh kosong.');
        }
        // Admin terakhir tidak boleh diturunkan perannya.
        if ($data['peran'] !== 'admin' && $this->jumlahAdminSelain($id) === 0
            && $this->db()->table('users')->where('id', $id)->get()->getRowArray()['peran'] === 'admin') {
            return redirect()->to('/pengguna')->with('galat', 'Tidak bisa mengubah peran admin terakhir.');
        }
        $sandi = (string) $r->getPost('sandi');
        if ($sandi !== '') {
            if (strlen($sandi) < 6) {
                return redirect()->to('/pengguna')->with('galat', 'Kata sandi baru minimal 6 karakter.');
            }
            $data['password_hash'] = password_hash($sandi, PASSWORD_DEFAULT);
        }
        $this->db()->table('users')->where('id', $id)->update($data);
        return redirect()->to('/pengguna')->with('sukses', 'Pengguna diperbarui.');
    }

    public function hapus($id)
    {
        if (! $this->admin()) {
            return redirect()->to('/');
        }
        $id = (int) $id;
        if ($id === (int) ($this->pengguna['id'] ?? 0)) {
            return redirect()->to('/pengguna')->with('galat', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }
        $target = $this->db()->table('users')->where('id', $id)->get()->getRowArray();
        if ($target && $target['peran'] === 'admin' && $this->jumlahAdminSelain($id) === 0) {
            return redirect()->to('/pengguna')->with('galat', 'Admin terakhir tidak dapat dihapus.');
        }
        $this->hapusLunak('users', ['id' => $id]);
        return redirect()->to('/pengguna')->with('sukses', 'Pengguna dihapus.');
    }

    /**
     * Hapus massal pengguna tercentang dengan penjaga yang sama seperti
     * hapus satuan: akun sendiri dan admin terakhir tidak dapat dihapus.
     * (Tidak ada "kosongkan" untuk modul ini.)
     */
    public function hapusMassal()
    {
        if (! $this->admin()) {
            return redirect()->to('/');
        }
        $saya = (int) ($this->pengguna['id'] ?? 0);
        $n = 0;
        $dilewati = [];
        foreach ($this->idsTerpilih() as $id) {
            $target = $this->db()->table('users')->where(['id' => $id, 'is_deleted' => 0])->get()->getRowArray();
            if ($target === null) {
                continue;
            }
            if ($id === $saya) {
                $dilewati[] = $target['username'] . ' (akun Anda sendiri)';
                continue;
            }
            if ($target['peran'] === 'admin' && $this->jumlahAdminSelain($id) === 0) {
                $dilewati[] = $target['username'] . ' (admin terakhir)';
                continue;
            }
            $this->hapusLunak('users', ['id' => $id]);
            $n++;
        }
        return redirect()->to('/pengguna')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 || $dilewati !== [] ? $this->ringkasHapus($n, $dilewati, 'pengguna') : 'Tidak ada pengguna yang diproses.');
    }

    private function jumlahAdminSelain(int $id): int
    {
        return $this->db()->table('users')->where(['peran' => 'admin', 'is_deleted' => 0])->where('id !=', $id)->countAllResults();
    }

    // ---------- Profil saya ----------

    public function profil()
    {
        $saya = $this->db()->table('users')->where('id', (int) ($this->pengguna['id'] ?? 0))->get()->getRowArray();
        return $this->tampil('pengguna/profil', ['saya' => $saya], 'Profil Saya');
    }

    public function profilSimpan()
    {
        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') {
            return redirect()->to('/profil')->with('galat', 'Nama tidak boleh kosong.');
        }
        $this->db()->table('users')->where('id', (int) $this->pengguna['id'])->update([
            'nama' => $nama, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        session()->set('pengguna_nama', $nama);
        return redirect()->to('/profil')->with('sukses', 'Profil diperbarui.');
    }

    public function sandiSimpan()
    {
        $r = $this->request;
        $lama = (string) $r->getPost('sandi_lama');
        $baru = (string) $r->getPost('sandi_baru');
        $ulang = (string) $r->getPost('sandi_ulang');
        $saya = $this->db()->table('users')->where('id', (int) $this->pengguna['id'])->get()->getRowArray();
        if (! $saya || ! password_verify($lama, $saya['password_hash'])) {
            return redirect()->to('/profil')->with('galat', 'Kata sandi lama salah.');
        }
        if (strlen($baru) < 6) {
            return redirect()->to('/profil')->with('galat', 'Kata sandi baru minimal 6 karakter.');
        }
        if ($baru !== $ulang) {
            return redirect()->to('/profil')->with('galat', 'Ulangi kata sandi baru tidak sama.');
        }
        $this->db()->table('users')->where('id', (int) $this->pengguna['id'])->update([
            'password_hash' => password_hash($baru, PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/profil')->with('sukses', 'Kata sandi berhasil diganti.');
    }
}
