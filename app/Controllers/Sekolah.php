<?php

namespace App\Controllers;

class Sekolah extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $daftar = $db->query(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM bahan b WHERE b.sekolah_id = s.id AND b.is_deleted = 0) AS jumlah_bahan,
                    (SELECT COUNT(*) FROM spk k WHERE k.sekolah_id = s.id AND k.is_deleted = 0) AS jumlah_spk
             FROM sekolah s WHERE s.is_deleted = 0 ORDER BY s.nama_sekolah'
        )->getResultArray();
        return $this->tampil('sekolah/index', ['daftar' => $daftar], 'Kelola Sekolah — Aplikasi PBJ Sekolah');
    }

    public function baru()
    {
        return $this->tampil('sekolah/form', ['s' => [], 'aksi' => 'sekolah/simpan'], 'Tambah Sekolah');
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if (trim((string) $data['nama_sekolah']) === '') {
            return redirect()->back()->withInput()->with('galat', 'Nama sekolah wajib diisi.');
        }
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('sekolah')->insert($data);
        $id = (int) $this->db()->insertID();
        session()->set('sekolah_id', $id);
        return redirect()->to('pengaturan')->with('sukses', 'Sekolah "' . $data['nama_sekolah'] . '" dibuat dan langsung aktif — lengkapi identitasnya di halaman ini, lalu isi Master Bahan.');
    }

    /** Jadikan sekolah ini yang aktif. */
    public function pilih($id = null)
    {
        $target = (int) ($id ?? $this->request->getPost('sekolah_id'));
        if ($target > 0 && $this->db()->table('sekolah')->where(['id' => $target, 'is_deleted' => 0])->countAllResults() > 0) {
            session()->set('sekolah_id', $target);
            $s = $this->db()->table('sekolah')->where(['id' => $target, 'is_deleted' => 0])->get()->getRowArray();
            return redirect()->to('/')->with('sukses', 'Sekolah aktif: ' . ($s['nama_sekolah'] ?? ''));
        }
        return redirect()->to('/')->with('galat', 'Sekolah tidak ditemukan.');
    }

    public function hapus($id)
    {
        $db = $this->db();
        $id = (int) $id;
        $ada = (int) $db->query('SELECT (SELECT COUNT(*) FROM bahan WHERE sekolah_id = ? AND is_deleted = 0) + (SELECT COUNT(*) FROM spk WHERE sekolah_id = ? AND is_deleted = 0) AS j', [$id, $id])->getRow()->j;
        if ($ada > 0) {
            return redirect()->to('sekolah')->with('galat', 'Sekolah ini sudah mempunyai data bahan/SPK — tidak bisa dihapus.');
        }
        if ($db->table('sekolah')->where('is_deleted', 0)->countAllResults() <= 1) {
            return redirect()->to('sekolah')->with('galat', 'Minimal harus ada satu sekolah.');
        }
        $this->hapusLunak('sekolah', ['id' => $id]);
        if ($this->sid() === $id) {
            session()->remove('sekolah_id');
        }
        return redirect()->to('sekolah')->with('sukses', 'Sekolah kosong dihapus.');
    }

    private function ambilForm(): array
    {
        $kolom = ['nama_sekolah', 'npsn', 'alamat', 'desa', 'kecamatan', 'kabupaten', 'provinsi', 'email', 'kode_pos',
            'tahun_anggaran', 'paket_pekerjaan', 'kepsek_nama', 'kepsek_nip', 'ketua_nama', 'sekretaris_nama', 'pelaksana_nama'];
        $data = [];
        foreach ($kolom as $k) {
            $v = $this->request->getPost($k);
            $data[$k] = ($v === '' || $v === null) ? null : trim((string) $v);
        }
        return $data;
    }
}
