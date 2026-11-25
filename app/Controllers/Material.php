<?php

namespace App\Controllers;

class Material extends BaseController
{
    public function index()
    {
        $db = $this->db();
        // Buku besar gabungan: catatan KELUAR manual (buku_material dengan
        // keluar > 0; kolom masuk manual lama diabaikan) + catatan MASUK
        // otomatis dari BAST (barisMasukBast). Digabung kronologis, lalu
        // saldo berjalan per bahan dihitung atas gabungan itu.
        $baris = [];
        foreach ($db->query(
            'SELECT m.*, b.kode, b.nama, b.satuan, g.nama AS gedung_nama FROM buku_material m
             JOIN bahan b ON b.id = m.bahan_id LEFT JOIN gedung g ON g.id = m.gedung_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 AND b.is_deleted = 0 AND m.keluar > 0',
            [$this->sid()]
        )->getResultArray() as $r) {
            $r['masuk'] = 0.0;
            $r['otomatis'] = false;
            $baris[] = $r;
        }
        foreach ($this->barisMasukBast() as $r) {
            $baris[] = ['id' => null, 'tanggal' => $r['tanggal'], 'no_bpb' => 'BAST ' . $r['bast_urut'],
                'bahan_id' => (int) $r['bahan_id'], 'kode' => $r['kode'], 'nama' => $r['nama'], 'satuan' => $r['satuan'],
                'gedung_id' => null, 'gedung_nama' => null, 'masuk' => (float) $r['masuk'], 'keluar' => 0.0,
                'keterangan' => 'Otomatis dari BAST ' . $r['bast_urut'] . ' — SPK No. ' . $r['spk_nomor'], 'otomatis' => true];
        }
        usort($baris, static function ($a, $b) {
            $ta = $a['tanggal'] ?? '9999-12-31';
            $tb = $b['tanggal'] ?? '9999-12-31';
            if ($ta !== $tb) {
                return strcmp((string) $ta, (string) $tb);
            }
            return ((int) ($a['otomatis'] ?? false) <=> (int) ($b['otomatis'] ?? false))
                ?: ((int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0));
        });
        // Saldo berjalan per bahan
        $saldo = [];
        foreach ($baris as &$r) {
            $saldo[$r['bahan_id']] = ($saldo[$r['bahan_id']] ?? 0) + (float) $r['masuk'] - (float) $r['keluar'];
            $r['sisa'] = $saldo[$r['bahan_id']];
        }
        unset($r);
        // Rekap KELUAR per gedung (hanya catatan keluar yang gedungnya terisi)
        $rekapGedung = $db->query(
            'SELECT g.nama AS gedung_nama, b.kode, b.nama, b.satuan, SUM(m.keluar) AS keluar
             FROM buku_material m JOIN bahan b ON b.id = m.bahan_id JOIN gedung g ON g.id = m.gedung_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 AND b.is_deleted = 0 AND m.keluar > 0
             GROUP BY g.nama, b.kode, b.nama, b.satuan ORDER BY g.nama, b.kode',
            [$this->sid()]
        )->getResultArray();
        $bahan = $db->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('kode')->get()->getResultArray();
        $gedung = $db->table('gedung')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray();
        // Stok tersedia per bahan (penerimaan BAST − catatan keluar) —
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
                $totButuh = 0.0;
                $totSisa = 0.0;
                foreach ($barisButuh as $r) {
                    $totButuh += $r['butuh'];
                    $totSisa += $r['sisa'];
                }
                // Persentase sisa kebutuhan gedung ini — dasar urutan
                // panel (sisa terbesar lebih dulu) dan label pada grup.
                $persen = $totButuh > 0 ? $totSisa / $totButuh * 100 : -1.0;
                $kebutuhanGedung[] = ['gedung' => $g, 'baris' => $barisButuh, 'persen_sisa' => $persen];
                foreach ($barisButuh as $r) {
                    $petaInfo[$gid][$r['bahan_id']] = ['butuh' => $r['butuh'], 'keluar' => $r['keluar'], 'sisa' => $r['sisa'], 'satuan' => (string) ($r['satuan'] ?? '')];
                }
            }
        }
        // Urutan panel: gedung dengan persentase sisa kebutuhan TERBESAR
        // lebih dulu (yang paling belum terpenuhi pekerjaannya).
        usort($kebutuhanGedung, static fn ($a, $b) => $b['persen_sisa'] <=> $a['persen_sisa']);
        // Saringan panel kebutuhan per gedung (GET f_gedung); peta petunjuk
        // form catat tetap memakai daftar lengkap.
        $fGedung = (int) ($this->request->getGet('f_gedung') ?? 0);
        $kebutuhanTampil = $fGedung > 0
            ? array_values(array_filter($kebutuhanGedung, static fn ($kg) => (int) $kg['gedung']['id'] === $fGedung))
            : $kebutuhanGedung;
        return $this->tampil('material/index', ['baris' => $baris, 'bahan' => $bahan, 'gedung' => $gedung, 'rekap_gedung' => $rekapGedung,
            'bahan_pilih' => $bahanPilih, 'kebutuhan_gedung' => $kebutuhanTampil, 'semua_kebutuhan_gedung' => $kebutuhanGedung,
            'f_gedung' => $fGedung, 'peta_info' => $petaInfo], 'Buku Material — Aplikasi PBJ Sekolah');
    }

    /**
     * Stok tersedia per bahan — definisi sama dengan Rencana Mingguan
     * (keputusan AMWAL 25 Nov 2026): penerimaan OTOMATIS dari BAST
     * (masukBastPerBahan) dikurangi catatan KELUAR Buku Material.
     * Catatan masuk manual TIDAK dihitung. Kunci: bahan_id.
     */
    private function stokPerBahan(): array
    {
        $db = $this->db();
        $stok = $this->masukBastPerBahan();
        foreach ($db->query(
            'SELECT m.bahan_id, SUM(m.keluar) AS keluar FROM buku_material m
             JOIN bahan b ON b.id = m.bahan_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 GROUP BY m.bahan_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $stok[(int) $r['bahan_id']] = ($stok[(int) $r['bahan_id']] ?? 0.0) - (float) $r['keluar'];
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
        // Sejak 25 Nov 2026 form ini HANYA mencatat barang KELUAR; barang
        // masuk tercatat otomatis dari BAST (tanggal BAST).
        $keluar = (float) str_replace(',', '.', (string) $this->request->getPost('keluar'));
        if ($keluar <= 0) {
            return redirect()->to('material')->with('galat', 'Isi jumlah Keluar terlebih dahulu — barang masuk kini tercatat otomatis dari BAST.');
        }
        // Validasi stok: yang keluar tidak boleh melebihi stok tersedia
        // (penerimaan BAST − yang sudah keluar) bahan itu.
        $stok = $this->stokPerBahan()[$bahanId] ?? 0.0;
        if ($keluar > $stok + 0.0005) {
            return redirect()->to('material')->with('galat', 'Keluar ' . number_format($keluar, 2, ',', '.') . ' ' . ($bahan['satuan'] ?? '')
                . ' melebihi stok tersedia ' . number_format(max($stok, 0), 2, ',', '.') . ' ' . ($bahan['satuan'] ?? '')
                . ' untuk ' . $bahan['kode'] . ' — ' . $bahan['nama'] . '. Stok tersedia = penerimaan BAST dikurangi yang sudah keluar.');
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
            'masuk' => 0,
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
        // Stok gudang = penerimaan BAST − catatan keluar (masuk manual
        // Buku Material tidak dihitung lagi — keputusan AMWAL 25 Nov 2026).
        $petaMasukBast = $this->masukBastPerBahan();
        foreach ($bahan as &$b) {
            $b['qty_rab'] = $petaButuh[(int) $b['id']] ?? (float) $b['qty_warisan'];
            $b['gudang_masuk'] = $petaMasukBast[(int) $b['id']] ?? 0.0;
        }
        unset($b);
        // Bahan tanpa kebutuhan (Qty RAB 0 dan belum pernah masuk SPK)
        // disembunyikan secara bawaan supaya daftar fokus ke bahan yang
        // benar-benar dipakai gedung didanai; ?semua=1 membukanya kembali.
        // Bahan ber-Qty RAB 0 yang sudah terlanjur masuk SPK (status
        // Melebihi RAB) TIDAK ikut disembunyikan.
        $tampilSemua = $this->request->getGet('semua') === '1';
        $jmlSembunyi = 0;
        foreach ($bahan as $b) {
            if ((float) $b['qty_rab'] == 0.0 && (float) $b['masuk_spk'] == 0.0) {
                $jmlSembunyi++;
            }
        }
        if (! $tampilSemua && $jmlSembunyi > 0) {
            $bahan = array_values(array_filter($bahan, static fn ($b) => ! ((float) $b['qty_rab'] == 0.0 && (float) $b['masuk_spk'] == 0.0)));
        }
        return $this->tampil('material/sisa', ['bahan' => $bahan, 'jmlSembunyi' => $jmlSembunyi, 'tampilSemua' => $tampilSemua], 'Sisa Bahan — Aplikasi PBJ Sekolah');
    }
}
