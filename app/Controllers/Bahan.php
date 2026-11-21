<?php

namespace App\Controllers;

class Bahan extends BaseController
{
    public function index()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $sql = 'SELECT b.*, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_total,
                    (b.qty_rkb+b.qty_adm+b.qty_toilet)*b.harga_rab AS anggaran,
                    COALESCE(SUM(d.qty),0) AS terpakai
             FROM bahan b LEFT JOIN spk_detail d ON d.bahan_id = b.id AND d.is_deleted = 0
             WHERE b.sekolah_id = ? AND b.is_deleted = 0';
        $bind = [$this->sid()];
        if ($q !== '') {
            $sql .= ' AND (b.nama LIKE ? OR b.kode LIKE ?)';
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
        }
        $sql .= ' GROUP BY b.id ORDER BY b.kode';
        $bahan = $this->db()->query($sql, $bind)->getResultArray();
        return $this->tampil('bahan/index', ['bahan' => $bahan, 'q' => $q], 'Master Bahan — Analisa Perencana');
    }

    public function baru()
    {
        $row = $this->db()->query('SELECT MAX(CAST(SUBSTRING(kode,2) AS UNSIGNED)) m FROM bahan WHERE sekolah_id = ? AND is_deleted = 0', [$this->sid()])->getRow();
        $kode = 'B' . str_pad((string) (((int) $row->m) + 1), 3, '0', STR_PAD_LEFT);
        return $this->tampil('bahan/form', ['b' => ['kode' => $kode], 'aksi' => 'bahan/simpan'], 'Tambah Bahan');
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if ($this->db()->table('bahan')->where(['kode' => $data['kode'], 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('galat', 'Kode ' . $data['kode'] . ' sudah dipakai.');
        }
        $data['sekolah_id'] = $this->sid();
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->insert($data);
        return redirect()->to('bahan')->with('sukses', 'Bahan ' . $data['kode'] . ' ditambahkan.');
    }

    public function ubah($id)
    {
        $b = $this->db()->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $b) {
            return redirect()->to('bahan')->with('galat', 'Bahan tidak ditemukan.');
        }
        return $this->tampil('bahan/form', ['b' => $b, 'aksi' => 'bahan/perbarui/' . $id], 'Ubah Bahan ' . $b['kode']);
    }

    public function perbarui($id)
    {
        $data = $this->ambilForm();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid()])->update($data);
        return redirect()->to('bahan')->with('sukses', 'Bahan ' . $data['kode'] . ' diperbarui.');
    }

    public function hapus($id)
    {
        $dipakai = $this->db()->table('spk_detail')->where(['bahan_id' => (int) $id, 'is_deleted' => 0])->countAllResults();
        if ($dipakai > 0) {
            return redirect()->to('bahan')->with('galat', 'Bahan ini sudah dipakai di SPK Detail — tidak bisa dihapus.');
        }
        $this->hapusLunak('bahan', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
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
