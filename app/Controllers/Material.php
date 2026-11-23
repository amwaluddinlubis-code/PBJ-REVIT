<?php

namespace App\Controllers;

class Material extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $baris = $db->query(
            'SELECT m.*, b.kode, b.nama, b.satuan, g.nama AS gedung_nama FROM buku_material m
             JOIN bahan b ON b.id = m.bahan_id LEFT JOIN gedung g ON g.id = m.gedung_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 AND b.is_deleted = 0 ORDER BY m.tanggal IS NULL, m.tanggal, m.id',
            [$this->sid()]
        )->getResultArray();
        // Saldo berjalan per bahan
        $saldo = [];
        foreach ($baris as &$r) {
            $saldo[$r['bahan_id']] = ($saldo[$r['bahan_id']] ?? 0) + (float) $r['masuk'] - (float) $r['keluar'];
            $r['sisa'] = $saldo[$r['bahan_id']];
        }
        unset($r);
        // Rekap keluar/masuk per gedung (hanya baris yang gedungnya terisi)
        $rekapGedung = $db->query(
            'SELECT g.nama AS gedung_nama, b.kode, b.nama, b.satuan,
                    SUM(m.masuk) AS masuk, SUM(m.keluar) AS keluar, SUM(m.masuk - m.keluar) AS saldo
             FROM buku_material m JOIN bahan b ON b.id = m.bahan_id JOIN gedung g ON g.id = m.gedung_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 AND b.is_deleted = 0
             GROUP BY g.nama, b.kode, b.nama, b.satuan ORDER BY g.nama, b.kode',
            [$this->sid()]
        )->getResultArray();
        $bahan = $db->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('kode')->get()->getResultArray();
        $gedung = $db->table('gedung')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray();
        return $this->tampil('material/index', ['baris' => $baris, 'bahan' => $bahan, 'gedung' => $gedung, 'rekap_gedung' => $rekapGedung], 'Buku Material — Aplikasi PBJ Sekolah');
    }

    public function simpan()
    {
        $gedungId = (int) $this->request->getPost('gedung_id') ?: null;
        if ($gedungId !== null && $this->ambilGedung($gedungId) === null) {
            $gedungId = null;
        }
        $this->db()->table('buku_material')->insert([
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'no_bpb' => trim((string) $this->request->getPost('no_bpb')) ?: null,
            'bahan_id' => (int) $this->request->getPost('bahan_id'),
            'gedung_id' => $gedungId,
            'masuk' => (float) str_replace(',', '.', (string) $this->request->getPost('masuk')),
            'keluar' => (float) str_replace(',', '.', (string) $this->request->getPost('keluar')),
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('material')->with('sukses', 'Baris buku material dicatat.');
    }

    public function hapus($id)
    {
        $this->hapusLunak('buku_material', ['id' => (int) $id]);
        return redirect()->to('material')->with('sukses', 'Baris dihapus.');
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            $milik = (int) $this->db()->query(
                'SELECT COUNT(*) j FROM buku_material m JOIN bahan b ON b.id = m.bahan_id WHERE m.id = ? AND b.sekolah_id = ? AND m.is_deleted = 0',
                [$id, $this->sid()]
            )->getRow()->j;
            if ($milik > 0) {
                $this->hapusLunak('buku_material', ['id' => $id]);
                $n++;
            }
        }
        return redirect()->to('material')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' baris buku material terpilih dihapus.' : 'Tidak ada baris yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('buku_material', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $n = (int) $this->db()->query(
            'SELECT COUNT(*) j FROM buku_material m JOIN bahan b ON b.id = m.bahan_id WHERE b.sekolah_id = ? AND m.is_deleted = 0',
            [$this->sid()]
        )->getRow()->j;
        $this->hapusLunak('buku_material', fn ($b) => $b->whereIn('bahan_id',
            $this->db()->table('bahan')->select('id')->where('sekolah_id', $this->sid())));
        return redirect()->to('material')->with('sukses', 'Buku material dikosongkan: ' . $n . ' baris dihapus.');
    }

    public function sisa()
    {
        $bahan = $this->db()->query(
            'SELECT b.id, b.kode, b.nama, b.satuan,
                    (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_warisan,
                    (SELECT COALESCE(SUM(d.qty),0) FROM spk_detail d WHERE d.bahan_id = b.id AND d.is_deleted = 0) AS masuk_spk,
                    (SELECT COALESCE(SUM(m.masuk),0) FROM buku_material m WHERE m.bahan_id = b.id AND m.is_deleted = 0) AS gudang_masuk,
                    (SELECT COALESCE(SUM(m.keluar),0) FROM buku_material m WHERE m.bahan_id = b.id AND m.is_deleted = 0) AS gudang_keluar
             FROM bahan b WHERE b.sekolah_id = ? AND b.is_deleted = 0 ORDER BY b.kode',
            [$this->sid()]
        )->getResultArray();
        // Patokan qty RAB sama persis dengan Master Bahan & Dashboard:
        // kebutuhan BOQ x koefisien analisa untuk bahan tertaut pekerjaan
        // gedung didanai; kolom warisan qty_rkb/adm/toilet (selalu 0 pada
        // sekolah hasil impor perencana) hanya untuk bahan lainnya.
        $petaButuh = [];
        foreach ($this->kebutuhanBahanSekolah()['tertaut'] as $b) {
            $petaButuh[(int) $b['bahan_id']] = (float) $b['qty_butuh'];
        }
        foreach ($bahan as &$b) {
            $b['qty_rab'] = $petaButuh[(int) $b['id']] ?? (float) $b['qty_warisan'];
        }
        unset($b);
        return $this->tampil('material/sisa', ['bahan' => $bahan], 'Sisa Bahan — Aplikasi PBJ Sekolah');
    }
}
