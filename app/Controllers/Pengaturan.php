<?php

namespace App\Controllers;

class Pengaturan extends BaseController
{
    public function index()
    {
        return $this->tampil('pengaturan/index', [
            'idn' => $this->db()->table('identitas')->get()->getRowArray(),
            'penyedia' => $this->db()->table('penyedia')->orderBy('nama')->get()->getResultArray(),
        ], 'Identitas & Pengaturan — Aplikasi PBJ Sekolah');
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
        $this->db()->table('identitas')->where('id', 1)->update($data);
        return redirect()->to('pengaturan')->with('sukses', 'Identitas disimpan — kop surat dan dokumen mengikuti otomatis.');
    }

    public function penyediaTambah()
    {
        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama !== '') {
            $this->db()->table('penyedia')->insert([
                'nama' => $nama,
                'alamat' => trim((string) $this->request->getPost('alamat')) ?: null,
                'ttd_nama' => trim((string) $this->request->getPost('ttd_nama')) ?: null,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->to('pengaturan')->with('sukses', 'Penyedia ditambahkan.');
    }

    public function penyediaHapus($id)
    {
        $dipakai = $this->db()->table('spk')->where('penyedia_id', (int) $id)->countAllResults();
        if ($dipakai > 0) {
            return redirect()->to('pengaturan')->with('galat', 'Penyedia ini dipakai SPK — tidak bisa dihapus.');
        }
        $this->db()->table('penyedia')->where('id', (int) $id)->delete();
        return redirect()->to('pengaturan')->with('sukses', 'Penyedia dihapus.');
    }
}
