<?php

namespace App\Controllers;

class Negosiasi extends BaseController
{
    /** Harga terpilih sebuah bahan = harga survei termurah. */
    private function terpilih(int $bahanId): ?int
    {
        $row = $this->db()->query(
            'SELECT MIN(harga) m FROM survei_harga WHERE bahan_id = ? AND harga IS NOT NULL AND is_deleted = 0',
            [$bahanId]
        )->getRow();
        return $row && $row->m !== null ? (int) $row->m : null;
    }

    public function index()
    {
        $db = $this->db();
        $identitas = $this->sekolahAktif();
        $bahan = $db->query(
            'SELECT *, (qty_rkb+qty_adm+qty_toilet) AS qty_total FROM bahan WHERE sekolah_id = ? AND is_deleted = 0 ORDER BY kode',
            [$this->sid()]
        )->getResultArray();
        $nego = [];
        foreach ($db->table('negosiasi')->where('is_deleted', 0)->get()->getResultArray() as $r) {
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
            $ada = $db->table('negosiasi')->where(['bahan_id' => $bid, 'is_deleted' => 0])->get()->getRowArray();
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

    /** Hapus hasil negosiasi bahan tercentang. */
    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $bahanId) {
            $milik = $this->db()->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($milik === 0) {
                continue;
            }
            $ada = $this->db()->table('negosiasi')->where(['bahan_id' => $bahanId, 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('negosiasi', ['bahan_id' => $bahanId]);
                $n++;
            }
        }
        return redirect()->to('negosiasi')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? 'Hasil negosiasi ' . $n . ' bahan terpilih dihapus.' : 'Tidak ada hasil negosiasi yang diproses.');
    }

    /** Kosongkan seluruh hasil negosiasi sekolah aktif. */
    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('negosiasi', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $n = (int) $this->db()->query(
            'SELECT COUNT(*) j FROM negosiasi n JOIN bahan b ON b.id = n.bahan_id WHERE b.sekolah_id = ? AND n.is_deleted = 0',
            [$this->sid()]
        )->getRow()->j;
        $this->hapusLunak('negosiasi', fn ($b) => $b->whereIn('bahan_id',
            $this->db()->table('bahan')->select('id')->where('sekolah_id', $this->sid())));
        return redirect()->to('negosiasi')->with('sukses', 'Hasil negosiasi dikosongkan: ' . $n . ' baris dihapus.');
    }
}
