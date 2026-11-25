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
        // Stok tersedia per bahan (sudah di-BAST + saldo neto buku) —
        // pilihan bahan pada form catat HANYA menampilkan bahan berstok,
        // beserta sisa stoknya, agar pencatatan tidak salah bahan.
        $stok = $this->stokPerBahan();
        $bahanPilih = [];
        foreach ($bahan as $b) {
            $sisaStok = $stok[(int) $b['id']] ?? 0.0;
            if ($sisaStok > 0.0005) {
                $bahanPilih[] = $b + ['stok' => $sisaStok];
            }
        }
        // Kebutuhan bahan per gedung (dari BOQ gedung itu) dibandingkan
        // yang sudah keluar ke gedung itu — panel Kebutuhan per Gedung
        // dan petunjuk langsung pada form catat.
        $keluarGedung = [];
        foreach ($db->query(
            'SELECT m.gedung_id, m.bahan_id, SUM(m.keluar) AS qty FROM buku_material m
             JOIN bahan b ON b.id = m.bahan_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 AND m.gedung_id IS NOT NULL
             GROUP BY m.gedung_id, m.bahan_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $keluarGedung[(int) $r['gedung_id']][(int) $r['bahan_id']] = (float) $r['qty'];
        }
        $petaBahan = [];
        foreach ($bahan as $b) {
            $petaBahan[(int) $b['id']] = $b;
        }
        $kebutuhanGedung = [];
        $petaInfo = [];
        foreach ($gedung as $g) {
            $gid = (int) $g['id'];
            $barisButuh = [];
            $tercatat = [];
            foreach ($this->kebutuhanBahan($gid)['tertaut'] ?? [] as $kb) {
                $bid = (int) $kb['bahan_id'];
                $keluar = $keluarGedung[$gid][$bid] ?? 0.0;
                $barisButuh[] = ['bahan_id' => $bid, 'kode' => $kb['kode'], 'nama' => $kb['nama'], 'satuan' => $kb['satuan'],
                    'butuh' => (float) $kb['qty_butuh'], 'keluar' => $keluar, 'sisa' => (float) $kb['qty_butuh'] - $keluar];
                $tercatat[$bid] = true;
            }
            // Keluar yang tercatat ke gedung ini untuk bahan di luar
            // kebutuhan gedung — ditampilkan agar salah catat terlihat.
            foreach ($keluarGedung[$gid] ?? [] as $bid => $qty) {
                if (! isset($tercatat[$bid]) && $qty > 0 && isset($petaBahan[$bid])) {
                    $b = $petaBahan[$bid];
                    $barisButuh[] = ['bahan_id' => $bid, 'kode' => $b['kode'], 'nama' => $b['nama'], 'satuan' => $b['satuan'],
                        'butuh' => 0.0, 'keluar' => $qty, 'sisa' => -$qty];
                }
            }
            if ($barisButuh) {
                $kebutuhanGedung[] = ['gedung' => $g, 'baris' => $barisButuh];
                foreach ($barisButuh as $r) {
                    $petaInfo[$gid][$r['bahan_id']] = ['butuh' => $r['butuh'], 'keluar' => $r['keluar'], 'sisa' => $r['sisa'], 'satuan' => (string) ($r['satuan'] ?? '')];
                }
            }
        }
        return $this->tampil('material/index', ['baris' => $baris, 'bahan' => $bahan, 'gedung' => $gedung, 'rekap_gedung' => $rekapGedung,
            'bahan_pilih' => $bahanPilih, 'kebutuhan_gedung' => $kebutuhanGedung, 'peta_info' => $petaInfo], 'Buku Material — Aplikasi PBJ Sekolah');
    }

    /**
     * Stok tersedia per bahan — definisi sama dengan Rencana Mingguan:
     * qty SPK yang SPK-nya sudah di-BAST + saldo neto Buku Material
     * (masuk − keluar). Kunci: bahan_id.
     */
    private function stokPerBahan(): array
    {
        $db = $this->db();
        $stok = [];
        foreach ($db->query(
            'SELECT d.bahan_id, SUM(d.qty) AS qty FROM spk_detail d
             JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
             JOIN bahan b ON b.id = d.bahan_id
             WHERE b.sekolah_id = ? AND d.is_deleted = 0
               AND EXISTS (SELECT 1 FROM bast x WHERE x.spk_id = s.id AND x.is_deleted = 0)
             GROUP BY d.bahan_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $stok[(int) $r['bahan_id']] = (float) $r['qty'];
        }
        foreach ($db->query(
            'SELECT m.bahan_id, SUM(m.masuk - m.keluar) AS neto FROM buku_material m
             JOIN bahan b ON b.id = m.bahan_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 GROUP BY m.bahan_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $stok[(int) $r['bahan_id']] = ($stok[(int) $r['bahan_id']] ?? 0.0) + (float) $r['neto'];
        }
        return $stok;
    }

    public function simpan()
    {
        $db = $this->db();
        $bahanId = (int) $this->request->getPost('bahan_id');
        $bahan = $db->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $bahan) {
            return redirect()->to('material')->with('galat', 'Bahan tidak ditemukan pada sekolah aktif.');
        }
        $masuk = (float) str_replace(',', '.', (string) $this->request->getPost('masuk'));
        $keluar = (float) str_replace(',', '.', (string) $this->request->getPost('keluar'));
        if ($masuk <= 0 && $keluar <= 0) {
            return redirect()->to('material')->with('galat', 'Isi jumlah Masuk atau Keluar terlebih dahulu.');
        }
        // Validasi stok: yang keluar tidak boleh melebihi stok tersedia
        // (sudah di-BAST + saldo buku) bahan itu.
        if ($keluar > 0) {
            $stok = $this->stokPerBahan()[$bahanId] ?? 0.0;
            if ($keluar > $stok + 0.0005) {
                return redirect()->to('material')->with('galat', 'Keluar ' . number_format($keluar, 2, ',', '.') . ' ' . ($bahan['satuan'] ?? '')
                    . ' melebihi stok tersedia ' . number_format(max($stok, 0), 2, ',', '.') . ' ' . ($bahan['satuan'] ?? '')
                    . ' untuk ' . $bahan['kode'] . ' — ' . $bahan['nama'] . '. Stok tersedia = bahan yang sudah di-BAST dikurangi yang sudah keluar.');
            }
        }
        $gedungId = (int) $this->request->getPost('gedung_id') ?: null;
        if ($gedungId !== null && $this->ambilGedung($gedungId) === null) {
            $gedungId = null;
        }
        $this->db()->table('buku_material')->insert([
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'no_bpb' => trim((string) $this->request->getPost('no_bpb')) ?: null,
            'bahan_id' => $bahanId,
            'gedung_id' => $gedungId,
            'masuk' => $masuk,
            'keluar' => $keluar,
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
