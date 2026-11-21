<?php

namespace App\Controllers;

class Material extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $baris = $db->query(
            'SELECT m.*, b.kode, b.nama, b.satuan FROM buku_material m
             JOIN bahan b ON b.id = m.bahan_id ORDER BY m.tanggal IS NULL, m.tanggal, m.id'
        )->getResultArray();
        // Saldo berjalan per bahan
        $saldo = [];
        foreach ($baris as &$r) {
            $saldo[$r['bahan_id']] = ($saldo[$r['bahan_id']] ?? 0) + (float) $r['masuk'] - (float) $r['keluar'];
            $r['sisa'] = $saldo[$r['bahan_id']];
        }
        $bahan = $db->table('bahan')->orderBy('kode')->get()->getResultArray();
        return $this->tampil('material/index', ['baris' => $baris, 'bahan' => $bahan], 'Buku Material — Aplikasi PBJ Sekolah');
    }

    public function simpan()
    {
        $this->db()->table('buku_material')->insert([
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'no_bpb' => trim((string) $this->request->getPost('no_bpb')) ?: null,
            'bahan_id' => (int) $this->request->getPost('bahan_id'),
            'masuk' => (float) $this->request->getPost('masuk'),
            'keluar' => (float) $this->request->getPost('keluar'),
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('material')->with('sukses', 'Baris buku material dicatat.');
    }

    public function hapus($id)
    {
        $this->db()->table('buku_material')->where('id', (int) $id)->delete();
        return redirect()->to('material')->with('sukses', 'Baris dihapus.');
    }

    public function sisa()
    {
        $bahan = $this->db()->query(
            'SELECT b.kode, b.nama, b.satuan,
                    (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_rab,
                    (SELECT COALESCE(SUM(d.qty),0) FROM spk_detail d WHERE d.bahan_id = b.id) AS masuk_spk,
                    (SELECT COALESCE(SUM(m.masuk),0) FROM buku_material m WHERE m.bahan_id = b.id) AS gudang_masuk,
                    (SELECT COALESCE(SUM(m.keluar),0) FROM buku_material m WHERE m.bahan_id = b.id) AS gudang_keluar
             FROM bahan b ORDER BY b.kode'
        )->getResultArray();
        return $this->tampil('material/sisa', ['bahan' => $bahan], 'Sisa Bahan — Aplikasi PBJ Sekolah');
    }
}
