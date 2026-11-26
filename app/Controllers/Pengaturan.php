<?php

namespace App\Controllers;

class Pengaturan extends BaseController
{
    public function index()
    {
        return $this->tampil('pengaturan/index', [
            'idn' => $this->sekolahAktif(),
            'buku_terbuka' => $this->bulanTerbuka(),
            'terbayar' => $this->totalBelanja(),
            'loginVersion' => $this->ambilLoginVersion(),
            'penyedia' => $this->db()->table('penyedia')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray(),
        ], 'Identitas & Pengaturan');
    }

    public function loginTampilan()
    {
        if (($this->pengguna['peran'] ?? '') !== 'admin') {
            return redirect()->to('pengaturan')->with('galat', 'Hanya administrator yang dapat mengubah halaman login.');
        }
        if (! $this->db()->tableExists('aplikasi_pengaturan')) {
            return redirect()->to('pengaturan')->with('galat', 'Jalankan migrasi aplikasi terlebih dahulu.');
        }
        $versi = (string) $this->request->getPost('login_version');
        if (! in_array($versi, ['v1', 'v2'], true)) {
            return redirect()->to('pengaturan')->with('galat', 'Versi halaman login tidak dikenal.');
        }
        $db = $this->db();
        $ada = $db->table('aplikasi_pengaturan')->where('kunci', 'login_version')->get()->getRowArray();
        $data = ['nilai' => $versi, 'updated_at' => date('Y-m-d H:i:s')];
        if ($ada) {
            $db->table('aplikasi_pengaturan')->where('id', (int) $ada['id'])->update($data);
        } else {
            $db->table('aplikasi_pengaturan')->insert($data + ['kunci' => 'login_version', 'created_at' => date('Y-m-d H:i:s')]);
        }
        return redirect()->to('pengaturan')->with('sukses', 'Halaman login aktif diubah ke ' . strtoupper($versi) . '.');
    }

    private function ambilLoginVersion(): string
    {
        try {
            if ($this->db()->tableExists('aplikasi_pengaturan')) {
                $nilai = $this->db()->table('aplikasi_pengaturan')->where('kunci', 'login_version')->get()->getRowArray()['nilai'] ?? 'v1';
                return in_array($nilai, ['v1', 'v2'], true) ? $nilai : 'v1';
            }
        } catch (\Throwable $e) {
            // Fallback untuk instalasi yang belum menjalankan migrasi terbaru.
        }
        return 'v1';
    }

    /** Matriks akses menu per peran (khusus admin). */
    public function aksesMenu()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $peranList = array_values(array_filter(\App\Libraries\AksesMenu::PERAN, static fn ($p) => $p !== 'admin'));
        $peta = [];
        foreach ($peranList as $peran) {
            $peta[$peran] = \App\Libraries\AksesMenu::petaUntukPeran($this->db(), $peran);
        }
        return $this->tampil('pengaturan/akses_menu', [
            'katalog'   => \App\Libraries\AksesMenu::katalog(),
            'peranList' => $peranList,
            'peta'      => $peta,
        ], 'Akses Menu — Aplikasi PBJ Sekolah');
    }

    /** Simpan matriks akses menu, atau kembalikan ke bawaan (semua boleh). */
    public function aksesMenuSimpan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $db = $this->db();
        $peranList = array_values(array_filter(\App\Libraries\AksesMenu::PERAN, static fn ($p) => $p !== 'admin'));
        $db->transStart();
        if ($this->request->getPost('atur_ulang') !== null) {
            $db->table('akses_menu')->whereIn('peran', $peranList)->delete();
            $db->transComplete();
            $this->catatAudit('akses_menu', null, 'atur-ulang', 'Akses menu dikembalikan ke bawaan (semua peran boleh semua menu katalog).');
            return redirect()->to('pengaturan/akses-menu')->with('sukses', 'Akses menu dikembalikan ke bawaan.');
        }
        $isian = (array) $this->request->getPost('akses');
        $sekarang = date('Y-m-d H:i:s');
        foreach ($peranList as $peran) {
            $db->table('akses_menu')->where('peran', $peran)->delete();
            foreach (\App\Libraries\AksesMenu::semuaKunci() as $menu) {
                $db->table('akses_menu')->insert([
                    'peran' => $peran, 'menu' => $menu,
                    'boleh' => isset($isian[$peran][$menu]) ? 1 : 0,
                    'created_at' => $sekarang, 'updated_at' => $sekarang,
                ]);
            }
        }
        $db->transComplete();
        $this->catatAudit('akses_menu', null, 'ubah', 'Matriks akses menu per peran diperbarui.');
        return redirect()->to('pengaturan/akses-menu')->with('sukses', 'Pengaturan akses menu disimpan.');
    }

    public function simpan()
    {
        $kolom = ['nama_sekolah', 'npsn', 'alamat', 'desa', 'kecamatan', 'kabupaten', 'provinsi', 'email', 'kode_pos',
            'kop_pemerintah', 'kop_dinas', 'alamat_kop', 'tahun_anggaran', 'paket_pekerjaan', 'tanggal_survei',
            'kepsek_nama', 'kepsek_nip', 'ketua_nama', 'sekretaris_nama', 'pelaksana_nama',
            'pemeriksa1', 'pemeriksa2', 'pemeriksa3', 'toko1', 'toko2', 'toko3', 'toko4',
            'penyedia_default_nama', 'penyedia_default_alamat'];
        $data = [];
        foreach ($kolom as $k) {
            $v = $this->request->getPost($k);
            $data[$k] = ($v === '' || $v === null) ? null : $v;
        }
        // Pagu anggaran: terima ketikan berformat ribuan ("2.540.292.000").
        $pagu = (string) $this->request->getPost('total_anggaran');
        $data['total_anggaran'] = (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9.,]/', '', $pagu) ?? '0');
        // Bulan pencatatan tidak lagi diatur manual: Bulan Terbuka adalah
        // turunan Tutup Buku bulanan (BukuKas::tutupBuku/bukaKembali yang
        // menyelaraskan kolom cache bulan_bekerja).
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('sekolah')->where('id', $this->sid())->update($data);
        $pesan = 'Identitas disimpan — kop surat dan dokumen mengikuti otomatis.';
        // Peringatan: pagu baru di bawah belanja yang sudah tercatat.
        $belanja = $this->totalBelanja();
        if ($data['total_anggaran'] > 0 && $data['total_anggaran'] < $belanja - 0.5) {
            $pesan .= ' Perhatian: pagu baru (' . rp($data['total_anggaran']) . ') lebih kecil dari belanja yang sudah tercatat (' . rp($belanja) . ') — pembayaran berikutnya akan tertahan kontrol pagu.';
        }
        return redirect()->to('pengaturan')->with('sukses', $pesan);
    }

    /** Balasan form modal (pola balasForm): AJAX -> JSON {ok, pesan}. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('pengaturan')->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function penyediaTambah()
    {
        // Modal form_modal mengirim id tersembunyi: 0 = baru, >0 = ubah.
        $id = (int) $this->request->getPost('id');
        return $id > 0 ? $this->prosesPenyediaUbah($id) : $this->prosesPenyediaTambah();
    }

    private function prosesPenyediaTambah()
    {
        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') {
            return $this->balasForm(false, 'Nama penyedia tidak boleh kosong.');
        }
        $ganda = $this->db()->query(
            'SELECT COUNT(*) j FROM penyedia WHERE sekolah_id = ? AND is_deleted = 0 AND LOWER(TRIM(nama)) = LOWER(?)',
            [$this->sid(), $nama]
        )->getRow()->j;
        $this->db()->table('penyedia')->insert([
            'sekolah_id' => $this->sid(),
            'nama' => $nama,
            'alamat' => trim((string) $this->request->getPost('alamat')) ?: null,
            'ttd_nama' => trim((string) $this->request->getPost('ttd_nama')) ?: null,
            'no_rekening' => trim((string) $this->request->getPost('no_rekening')) ?: null,
            'nama_bank' => trim((string) $this->request->getPost('nama_bank')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->balasForm(true, 'Penyedia ditambahkan.' . ($ganda > 0 ? ' Perhatian: sudah ada penyedia bernama sama — periksa apakah ini memang toko yang berbeda.' : ''));
    }

    public function penyediaUbah($id)
    {
        return $this->prosesPenyediaUbah((int) $id);
    }

    private function prosesPenyediaUbah(int $id)
    {
        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') {
            return $this->balasForm(false, 'Nama penyedia tidak boleh kosong.');
        }
        $ganda = $this->db()->query(
            'SELECT COUNT(*) j FROM penyedia WHERE sekolah_id = ? AND is_deleted = 0 AND id <> ? AND LOWER(TRIM(nama)) = LOWER(?)',
            [$this->sid(), $id, $nama]
        )->getRow()->j;
        $this->db()->table('penyedia')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->update([
            'nama' => $nama,
            'alamat' => trim((string) $this->request->getPost('alamat')) ?: null,
            'ttd_nama' => trim((string) $this->request->getPost('ttd_nama')) ?: null,
            'no_rekening' => trim((string) $this->request->getPost('no_rekening')) ?: null,
            'nama_bank' => trim((string) $this->request->getPost('nama_bank')) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->balasForm(true, 'Penyedia "' . $nama . '" diperbarui.' . ($ganda > 0 ? ' Perhatian: ada penyedia lain bernama sama.' : ''));
    }

    public function penyediaHapus($id)
    {
        $dipakai = $this->db()->table('spk')->where(['penyedia_id' => (int) $id, 'is_deleted' => 0])->countAllResults();
        if ($dipakai > 0) {
            return redirect()->to('pengaturan')->with('galat', 'Penyedia ini dipakai SPK — tidak bisa dihapus.');
        }
        $this->hapusLunak('penyedia', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('pengaturan')->with('sukses', 'Penyedia dihapus.');
    }

    /** Hapus massal penyedia tercentang; yang dipakai SPK dilewati. */
    public function penyediaHapusMassal()
    {
        [$n, $dilewati] = $this->hapusPenyediaBanyak($this->idsTerpilih());
        return redirect()->to('pengaturan')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 || $dilewati !== [] ? $this->ringkasHapus($n, $dilewati, 'penyedia') . ($dilewati !== [] ? ' Penyedia yang dilewati dipakai SPK.' : '') : 'Tidak ada penyedia yang diproses.');
    }

    /** Kosongkan master penyedia sekolah aktif (yang dipakai SPK dipertahankan). */
    public function penyediaKosongkan()
    {
        $ids = array_map('intval', array_column($this->db()->table('penyedia')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id'));
        [$n, $dilewati] = $this->hapusPenyediaBanyak($ids);
        return redirect()->to('pengaturan')->with('sukses',
            'Master penyedia dikosongkan: ' . $this->ringkasHapus($n, $dilewati, 'penyedia') . ($dilewati !== [] ? ' Penyedia yang dilewati dipakai SPK.' : ''));
    }

    /** @return array{0:int,1:array<int,string>} */
    private function hapusPenyediaBanyak(array $ids): array
    {
        $n = 0;
        $dilewati = [];
        foreach ($ids as $id) {
            $p = $this->db()->table('penyedia')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($p === null) {
                continue;
            }
            $dipakai = $this->db()->table('spk')->where(['penyedia_id' => $id, 'is_deleted' => 0])->countAllResults();
            if ($dipakai > 0) {
                $dilewati[] = $p['nama'];
                continue;
            }
            $this->hapusLunak('penyedia', ['id' => $id, 'sekolah_id' => $this->sid()]);
            $n++;
        }
        return [$n, $dilewati];
    }
}
