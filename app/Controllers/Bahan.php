<?php

namespace App\Controllers;

class Bahan extends BaseController
{
    public function index()
    {
        $bahan = $this->db()->query(
            'SELECT b.*, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_total,
                    (b.qty_rkb+b.qty_adm+b.qty_toilet)*b.harga_rab AS anggaran,
                    COALESCE(SUM(d.qty),0) AS terpakai
             FROM bahan b LEFT JOIN spk_detail d ON d.bahan_id = b.id
             GROUP BY b.id ORDER BY b.kode'
        )->getResultArray();
        return $this->tampil('bahan/index', ['bahan' => $bahan], 'Master Bahan — Aplikasi PBJ Sekolah');
    }

    public function baru()
    {
        $row = $this->db()->query('SELECT MAX(CAST(SUBSTRING(kode,2) AS UNSIGNED)) m FROM bahan')->getRow();
        $kode = 'B' . str_pad((string) (((int) $row->m) + 1), 3, '0', STR_PAD_LEFT);
        return $this->tampil('bahan/form', ['b' => ['kode' => $kode], 'aksi' => 'bahan/simpan'], 'Tambah Bahan');
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if ($this->db()->table('bahan')->where('kode', $data['kode'])->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('galat', 'Kode ' . $data['kode'] . ' sudah dipakai.');
        }
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->insert($data);
        return redirect()->to('bahan')->with('sukses', 'Bahan ' . $data['kode'] . ' ditambahkan.');
    }

    public function ubah($id)
    {
        $b = $this->db()->table('bahan')->where('id', (int) $id)->get()->getRowArray();
        if (! $b) {
            return redirect()->to('bahan')->with('galat', 'Bahan tidak ditemukan.');
        }
        return $this->tampil('bahan/form', ['b' => $b, 'aksi' => 'bahan/perbarui/' . $id], 'Ubah Bahan ' . $b['kode']);
    }

    public function perbarui($id)
    {
        $data = $this->ambilForm();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->where('id', (int) $id)->update($data);
        return redirect()->to('bahan')->with('sukses', 'Bahan ' . $data['kode'] . ' diperbarui.');
    }

    public function hapus($id)
    {
        $dipakai = $this->db()->table('spk_detail')->where('bahan_id', (int) $id)->countAllResults();
        if ($dipakai > 0) {
            return redirect()->to('bahan')->with('galat', 'Bahan ini sudah dipakai di SPK Detail — tidak bisa dihapus.');
        }
        $this->db()->table('bahan')->where('id', (int) $id)->delete();
        return redirect()->to('bahan')->with('sukses', 'Bahan dihapus.');
    }

    private function ambilForm(): array
    {
        $r = $this->request;
        return [
            'kode'       => strtoupper(trim((string) $r->getPost('kode'))),
            'nama'       => trim((string) $r->getPost('nama')),
            'satuan'     => trim((string) $r->getPost('satuan')),
            'harga_rab'  => (int) $r->getPost('harga_rab'),
            'qty_rkb'    => (float) $r->getPost('qty_rkb'),
            'qty_adm'    => (float) $r->getPost('qty_adm'),
            'qty_toilet' => (float) $r->getPost('qty_toilet'),
        ];
    }
}
