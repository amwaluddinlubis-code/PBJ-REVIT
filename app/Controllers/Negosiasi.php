<?php

namespace App\Controllers;

class Negosiasi extends BaseController
{
    /** Harga terpilih sebuah bahan = harga survei termurah. */
    private function terpilih(int $bahanId): ?int
    {
        $row = $this->db()->query(
            'SELECT MIN(harga) m FROM survei_harga WHERE bahan_id = ? AND harga IS NOT NULL',
            [$bahanId]
        )->getRow();
        return $row && $row->m !== null ? (int) $row->m : null;
    }

    public function index()
    {
        $db = $this->db();
        $identitas = $db->table('identitas')->get()->getRowArray();
        $bahan = $db->query(
            'SELECT *, (qty_rkb+qty_adm+qty_toilet) AS qty_total FROM bahan ORDER BY kode'
        )->getResultArray();
        $nego = [];
        foreach ($db->table('negosiasi')->get()->getResultArray() as $r) {
            $nego[$r['bahan_id']] = $r;
        }
        foreach ($bahan as &$b) {
            $b['terpilih'] = $this->terpilih((int) $b['id']);
            $b['penawaran'] = $nego[$b['id']]['harga_penawaran'] ?? $b['terpilih'];
            $b['nego'] = $nego[$b['id']]['harga_nego'] ?? null;
        }
        return $this->tampil('negosiasi/index', [
            'identitas' => $identitas, 'bahan' => $bahan,
        ], 'Negosiasi — Aplikasi PBJ Sekolah');
    }

    public function simpan()
    {
        $db = $this->db();
        $penawaran = $this->request->getPost('penawaran') ?? [];
        $negoIn = $this->request->getPost('nego') ?? [];
        $db->transStart();
        foreach ($penawaran as $bahanId => $nilai) {
            $bid = (int) $bahanId;
            $data = [
                'harga_penawaran' => ($nilai === '' || $nilai === null) ? null : (int) $nilai,
                'harga_nego' => (isset($negoIn[$bid]) && $negoIn[$bid] !== '') ? (int) $negoIn[$bid] : null,
            ];
            $ada = $db->table('negosiasi')->where('bahan_id', $bid)->get()->getRowArray();
            if ($ada) {
                $db->table('negosiasi')->where('id', $ada['id'])->update($data);
            } else {
                $data['bahan_id'] = $bid;
                $db->table('negosiasi')->insert($data);
            }
        }
        $db->transComplete();
        return redirect()->to('negosiasi')->with('sukses', 'Hasil negosiasi disimpan. Harga ini menjadi acuan pertama saat mengisi SPK Detail.');
    }
}
