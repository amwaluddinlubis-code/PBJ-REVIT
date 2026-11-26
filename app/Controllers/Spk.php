<?php

namespace App\Controllers;

class Spk extends BaseController
{
    public function index()
    {
        $spk = $this->db()->query(
            'SELECT s.*, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, COUNT(d.id) AS jumlah, COALESCE(SUM(d.qty*d.harga),0) AS nilai
             FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id
             LEFT JOIN spk_detail d ON d.spk_id = s.id AND d.is_deleted = 0
             WHERE s.sekolah_id = ? AND s.is_deleted = 0
             GROUP BY s.id ORDER BY s.nomor',
            [$this->sid()]
        )->getResultArray();
        // Data untuk modal form SPK (baru & ubah) di halaman register —
        // daftar penyedia + nomor berikutnya, sama seperti Spk::baru().
        $this->penyediaMasyarakatId();
        return $this->tampil('spk/index', [
            'spk'         => $spk,
            'penyedia'    => $this->db()->table('penyedia')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray(),
            'nomor_baru'  => $this->nomorSpkBerikutnya(),
            'cadangan'    => $this->pencadanganPagu(),
        ], 'Register SPK — Aplikasi PBJ Sekolah');
    }

    public function baru()
    {
        $this->penyediaMasyarakatId();
        return $this->tampil('spk/form', [
            's' => ['nomor' => $this->nomorSpkBerikutnya(), 'status' => 'Aktif'],
            'penyedia' => $this->db()->table('penyedia')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray(),
            'aksi' => 'spk/simpan',
        ], 'SPK Baru');
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if ($g = $this->galatFormSpk($data, 0)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'pesan' => $g]);
            }
            return redirect()->back()->withInput()->with('galat', $g);
        }
        $data['sekolah_id'] = $this->sid();
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('spk')->insert($data);
        $peringatan = $this->peringatanCadangan();
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'pesan' => 'SPK ' . $data['nomor'] . ' dibuat — buka tombol Detail pada barisnya untuk mengisi rincian.' . $peringatan]);
        }
        return redirect()->to('spk/detail/' . $this->db()->insertID())->with('sukses', 'SPK ' . $data['nomor'] . ' dibuat — silakan isi rinciannya.' . $peringatan);
    }

    public function ubah($id)
    {
        $this->penyediaMasyarakatId();
        $s = $this->db()->table('spk')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $s) {
            return redirect()->to('spk')->with('galat', 'SPK tidak ditemukan.');
        }
        return $this->tampil('spk/form', [
            's' => $s,
            'penyedia' => $this->db()->table('penyedia')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray(),
            'aksi' => 'spk/perbarui/' . $id,
        ], 'Ubah SPK ' . $s['nomor']);
    }

    public function perbarui($id)
    {
        $data = $this->ambilForm();
        if ($g = $this->galatFormSpk($data, (int) $id)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'pesan' => $g]);
            }
            return redirect()->back()->withInput()->with('galat', $g);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('spk')->where(['id' => (int) $id, 'sekolah_id' => $this->sid()])->update($data);
        $peringatan = $this->peringatanCadangan();
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'pesan' => 'SPK diperbarui.' . $peringatan]);
        }
        if ($this->request->getPost('balik') === 'detail') {
            return redirect()->to('spk/detail/' . (int) $id)->with('sukses', 'SPK diperbarui.' . $peringatan);
        }
        return redirect()->to('spk')->with('sukses', 'SPK diperbarui.' . $peringatan);
    }

    /** Peringatan pencadangan pagu: komitmen SPK aktif melewati pagu tersedia. */
    private function peringatanCadangan(): string
    {
        $c = $this->pencadanganPagu();
        if ($c['anggaran'] > 0 && $c['tersedia'] < 0) {
            return ' Perhatian: pencadangan pagu SPK aktif (' . $this->rp($c['komitmen']) . ') ditambah yang sudah terbayar (' . $this->rp($c['terbayar']) . ') melewati total anggaran (' . $this->rp($c['anggaran']) . ') — pagu tersedia ' . $this->rp($c['tersedia']) . '. Pembayaran tetap dijaga cekPagu.';
        }
        return '';
    }

    /**
     * Kunci referensial: SPK yang sudah punya turunan (BAP/BAST/invoice
     * aktif) tidak bisa dihapus — pembatalan lewat status Batal.
     * Mengembalikan pesan galat atau null bila boleh dihapus.
     */
    private function galatHapusSpk(int $id): ?string
    {
        $db = $this->db();
        $turunan = [];
        if ($db->table('bap')->where(['spk_id' => $id, 'is_deleted' => 0])->countAllResults() > 0) {
            $turunan[] = 'BAP';
        }
        if ($db->table('bast')->where(['spk_id' => $id, 'is_deleted' => 0])->countAllResults() > 0) {
            $turunan[] = 'BAST';
        }
        if ($db->table('invoice')->where(['spk_id' => $id, 'is_deleted' => 0])->countAllResults() > 0) {
            $turunan[] = 'invoice';
        }
        if ($turunan !== []) {
            return 'SPK ini sudah punya dokumen turunan (' . implode(', ', $turunan) . ') sehingga tidak bisa dihapus. Untuk membatalkannya, ubah status SPK menjadi Batal — pencadangan pagunya ikut bebas.';
        }
        return null;
    }

    public function hapus($id)
    {
        if ($g = $this->galatHapusSpk((int) $id)) {
            return redirect()->to('spk')->with('galat', $g);
        }
        // Hapus lunak: SPK ditandai terhapus; rinciannya menjadi belum bernomor
        // (spk_id dikosongkan) agar tetap bisa dipakai ulang. BAP/BAST/invoice
        // yang menunjuk SPK ini ikut tersembunyi lewat saringannya.
        $db = $this->db();
        $db->table('spk_detail')->where(['spk_id' => (int) $id, 'is_deleted' => 0])
            ->update(['spk_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->hapusLunak('spk', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('spk')->with('sukses', 'SPK dihapus; rincian barangnya kini berstatus belum bernomor.');
    }

    /** Inti penghapusan satu SPK untuk dipakai massal. */
    private function hapusSatuSpk(int $id): bool
    {
        $ada = $this->db()->table('spk')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
        if ($ada === 0 || $this->galatHapusSpk($id) !== null) {
            return false;
        }
        $this->db()->table('spk_detail')->where(['spk_id' => $id, 'is_deleted' => 0])
            ->update(['spk_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->hapusLunak('spk', ['id' => $id, 'sekolah_id' => $this->sid()]);
        return true;
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            if ($this->hapusSatuSpk($id)) {
                $n++;
            }
        }
        return redirect()->to('spk')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' SPK terpilih dihapus; rincian barangnya kini berstatus belum bernomor.' : 'Tidak ada SPK yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('spk', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('spk')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        $n = 0;
        foreach ($ids as $id) {
            if ($this->hapusSatuSpk((int) $id)) {
                $n++;
            }
        }
        return redirect()->to('spk')->with('sukses', 'Register SPK dikosongkan: ' . $n . ' SPK dihapus; semua rincian barangnya kini berstatus belum bernomor.');
    }

    public function detail($id)
    {
        $db = $this->db();
        $s = $db->query(
            'SELECT s.*, s.ttd_penyedia AS ttd_asli, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0',
            [(int) $id]
        )->getRowArray();
        if (! $s || (int) $s['sekolah_id'] !== $this->sid()) {
            return redirect()->to('spk')->with('galat', 'SPK tidak ditemukan di sekolah aktif.');
        }
        $detail = $db->query(
            'SELECT d.*, COALESCE(b.kode, "") AS kode, COALESCE(b.nama, d.nama_manual) AS nama,
                    COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty*d.harga AS total,
                    CASE WHEN d.pajak = 1 THEN ROUND(0.11 * d.qty * d.harga / 1.11) ELSE 0 END AS ppn,
                    gd.nama AS gedung_rincian
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id
             LEFT JOIN gedung gd ON gd.id = COALESCE(d.gedung_id, (SELECT s2.gedung_id FROM spk s2 WHERE s2.id = d.spk_id))
             WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [(int) $id]
        )->getResultArray();
        $gabungan = count(array_unique(array_filter(array_map(static fn ($d) => $d['gedung_rincian'] ?? null, $detail)))) > 1;
        $bahan = $db->query('SELECT id, kode, nama, satuan, harga_rab FROM bahan WHERE sekolah_id = ? AND is_deleted = 0 ORDER BY kode', [$this->sid()])->getResultArray();
        // Sisa kebutuhan per bahan untuk gedung SPK ini (kebutuhan BOQ −
        // sudah dipesan pada SPK gedung itu) — bawaan qty Tambah Baris.
        $sisaButuh = [];
        if (! empty($s['gedung_id'])) {
            foreach ($this->kebutuhanBahan((int) $s['gedung_id'])['tertaut'] as $kb) {
                $sisaButuh[(int) $kb['bahan_id']] = (float) $kb['qty_butuh'];
            }
            foreach ($db->query(
                'SELECT d.bahan_id, COALESCE(SUM(d.qty),0) AS qty FROM spk_detail d JOIN spk sx ON sx.id = d.spk_id AND sx.is_deleted = 0
                 WHERE COALESCE(d.gedung_id, sx.gedung_id) = ? AND d.is_deleted = 0 AND d.bahan_id IS NOT NULL GROUP BY d.bahan_id',
                [(int) $s['gedung_id']]
            )->getResultArray() as $r) {
                $bid = (int) $r['bahan_id'];
                if (isset($sisaButuh[$bid])) {
                    $sisaButuh[$bid] -= (float) $r['qty'];
                }
            }
        }
        foreach ($bahan as &$b) {
            $b['harga_acuan'] = $this->hargaAcuan((int) $b['id']);
            $b['sisa_butuh'] = isset($sisaButuh[(int) $b['id']]) ? max(0, $sisaButuh[(int) $b['id']]) : null;
        }
        unset($b);
        $nilaiPaket = array_sum(array_map(static fn ($d) => (float) $d['total'], $detail));
        $pembanding = (int) $db->query(
            'SELECT COUNT(DISTINCT sh.toko) AS j FROM survei_harga sh
             JOIN spk_detail d ON d.bahan_id = sh.bahan_id AND d.is_deleted = 0
             WHERE d.spk_id = ? AND sh.is_deleted = 0',
            [(int) $id]
        )->getRow()->j;
        // Ceklis kelengkapan dokumen bukti belanja (Juknis): status
        // otomatis dari modul terkait + penanda manual untuk dokumen fisik.
        $hitung = static fn (string $sql) => (int) $db->query($sql, [(int) $id])->getRow()->j;
        $manual = [];
        try {
            foreach ($db->table('dokumen_spk')->where(['spk_id' => (int) $id, 'is_deleted' => 0])->get()->getResultArray() as $m) {
                $manual[$m['item']] = (int) $m['ada'] === 1;
            }
        } catch (\Throwable $e) {
            // tabel dokumen_spk belum dimigrasi
        }
        $ceklis = [
            ['kunci' => 'perencanaan', 'label' => 'Dokumen perencanaan pengadaan (jumlah, spesifikasi, waktu & lokasi serah terima, alokasi anggaran, persyaratan penyedia)', 'ada' => $manual['perencanaan'] ?? false, 'manual' => true],
            ['kunci' => 'pembandingan', 'label' => 'Hasil pembandingan harga (Survei Harga)', 'ada' => $pembanding > 0, 'manual' => false, 'tautan' => 'survei', 'label_tautan' => 'Buka Survei Harga'],
            ['kunci' => 'negosiasi', 'label' => 'Hasil negosiasi harga', 'ada' => $hitung('SELECT COUNT(*) AS j FROM negosiasi n JOIN spk_detail d ON d.bahan_id = n.bahan_id AND d.is_deleted = 0 WHERE d.spk_id = ? AND n.is_deleted = 0 AND n.harga_nego IS NOT NULL') > 0, 'manual' => false, 'tautan' => 'negosiasi', 'label_tautan' => 'Buka Negosiasi'],
            ['kunci' => 'pemesanan', 'label' => 'Surat pemesanan (SPK ini)', 'ada' => true, 'manual' => false],
            ['kunci' => 'pemeriksaan', 'label' => 'Hasil pemeriksaan barang/jasa (BAP)', 'ada' => $hitung('SELECT COUNT(*) AS j FROM bap WHERE spk_id = ? AND is_deleted = 0') > 0, 'manual' => false, 'jangkar' => '#panel-bap-bast', 'label_tautan' => 'Isi di panel BAP & BAST'],
            ['kunci' => 'bast', 'label' => 'Berita Acara Serah Terima (BAST)', 'ada' => $hitung('SELECT COUNT(*) AS j FROM bast WHERE spk_id = ? AND is_deleted = 0') > 0, 'manual' => false, 'jangkar' => '#panel-bap-bast', 'label_tautan' => 'Isi di panel BAP & BAST'],
            ['kunci' => 'invoice', 'label' => 'Invoice / tagihan penyedia', 'ada' => $hitung('SELECT COUNT(*) AS j FROM invoice WHERE spk_id = ? AND is_deleted = 0') > 0, 'manual' => false, 'tautan' => 'invoice', 'label_tautan' => 'Buka Register Invoice'],
            ['kunci' => 'pembayaran', 'label' => 'Bukti pembayaran (jurnal pembayaran)', 'ada' => $hitung('SELECT COUNT(*) AS j FROM pembayaran p JOIN invoice i ON i.id = p.invoice_id AND i.is_deleted = 0 WHERE i.spk_id = ? AND p.is_deleted = 0') > 0, 'manual' => false, 'tautan' => 'invoice/jurnal', 'label_tautan' => 'Buka Jurnal Pembayaran'],
            ['kunci' => 'kuitansi', 'label' => 'Kuitansi/faktur fisik dari penyedia (ditandatangani + stempel; bermaterai bila nilai kuitansi ≥ Rp5 juta)', 'ada' => $manual['kuitansi'] ?? false, 'manual' => true],
        ];
        // Panel Tuntaskan SPK (Ide A): status rantai BAP/BAST/Invoice/Bayar
        // untuk penyelesaian satu panel dari halaman ini.
        $invTuntas = $db->query('SELECT * FROM invoice WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut LIMIT 1', [(int) $id])->getRowArray();
        $pajakTuntas = null;
        if ($invTuntas) {
            $pajakTuntas = $this->pajakPembelian((float) $invTuntas['nilai'], $this->ppnInvoice($this->ppnSpkMap(), (int) $id, (float) $invTuntas['nilai']));
        }
        $tuntas = [
            'jml_bap' => $hitung('SELECT COUNT(*) AS j FROM bap WHERE spk_id = ? AND is_deleted = 0'),
            'jml_bast' => $hitung('SELECT COUNT(*) AS j FROM bast WHERE spk_id = ? AND is_deleted = 0'),
            'invoice' => $invTuntas,
            'pajak' => $pajakTuntas,
            'tgl_terima' => $s['tgl_selesai'] ?: date('Y-m-d'),
            // Rekomendasi tanggal bayar: jurnal terakhir / tanggal invoice, dijepit Bulan Terbuka.
            'tgl_bayar' => $this->tanggalBayarRekomendasi($invTuntas['tanggal'] ?? null),
        ] + $this->batasBuku();
        // Catatan kebutuhan tenaga kerja SPK (panel + cetak).
        $tenaga = [];
        try {
            $tenaga = $db->table('spk_tenaga')->where(['spk_id' => (int) $id, 'is_deleted' => 0])->orderBy('id')->get()->getResultArray();
        } catch (\Throwable $e) {
            // tabel spk_tenaga belum dimigrasi
        }
        // Panel BAP & BAST (pengisian lengkap di halaman ini): daftar BAP
        // SPK ini beserta item pemeriksaannya dan BAST-nya.
        $daftarBap = $db->table('bap')->where(['spk_id' => (int) $id, 'is_deleted' => 0])->orderBy('nomor_urut')->orderBy('id')->get()->getResultArray();
        $itemsPerBap = [];
        foreach ($daftarBap as &$b) {
            $b['surat'] = $this->nomorSurat('BAP', (int) $b['nomor_urut']);
            $itemsPerBap[(int) $b['id']] = $db->query(
                'SELECT bd.id AS bd_id, bd.kondisi, bd.keterangan, d.qty,
                        COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan
                 FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
                 LEFT JOIN bahan b ON b.id = d.bahan_id WHERE bd.bap_id = ? AND bd.is_deleted = 0 ORDER BY bd.id',
                [(int) $b['id']]
            )->getResultArray();
        }
        unset($b);
        $bapById = [];
        foreach ($daftarBap as $b) {
            $bapById[(int) $b['id']] = $b;
        }
        $bastPerBap = [];
        $bastUmum = [];
        foreach ($db->table('bast')->where(['spk_id' => (int) $id, 'is_deleted' => 0])->orderBy('nomor_urut')->orderBy('id')->get()->getResultArray() as $bs) {
            $bs['surat'] = $this->nomorSurat('BAST', (int) $bs['nomor_urut']);
            // Tanggal BAP tertaut ditampilkan di form BAST (info baca-saja).
            $bs['bap_tanggal'] = ! empty($bs['bap_id']) ? ($bapById[(int) $bs['bap_id']]['tanggal'] ?? null) : null;
            $bs['bap_nomor_urut'] = ! empty($bs['bap_id']) ? ($bapById[(int) $bs['bap_id']]['nomor_urut'] ?? null) : null;
            if (! empty($bs['bap_id'])) {
                $bastPerBap[(int) $bs['bap_id']][] = $bs;
            } else {
                $bastUmum[] = $bs;
            }
        }
        $jmlTerbap = $hitung('SELECT COUNT(*) AS j FROM bap_detail bd JOIN bap bp ON bp.id = bd.bap_id AND bp.is_deleted = 0 WHERE bp.spk_id = ? AND bd.is_deleted = 0');
        $penyediaList = $db->table('penyedia')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray();
        $gedungList = $db->table('gedung')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('urutan')->orderBy('id')->get()->getResultArray();
        // Template Laporan aktif untuk konteks SPK (tombol render di panel).
        try {
            $templateSpk = $db->table('laporan_template')->where(['sekolah_id' => $this->sid(), 'jenis' => 'spk', 'aktif' => 1, 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray();
        } catch (\Throwable $e) {
            $templateSpk = [];
        }
        return $this->tampil('spk/detail', [
            's' => $s, 'detail' => $detail, 'gabungan' => $gabungan, 'template_spk' => $templateSpk, 'bahan' => $bahan,
            'rincian_terkunci' => $this->rincianTerkunci((int) $id),
            'nilai_paket' => $nilaiPaket,
            'ketentuan' => $this->ketentuanPbj($nilaiPaket),
            'pembanding' => $pembanding,
            'ceklis' => $ceklis,
            'tuntas' => $tuntas,
            'tenaga' => $tenaga,
            'daftar_bap' => $daftarBap, 'items_per_bap' => $itemsPerBap,
            'bast_per_bap' => $bastPerBap, 'bast_umum' => $bastUmum,
            'jml_terbap' => $jmlTerbap,
            'penyedia_list' => $penyediaList, 'gedung_list' => $gedungList,
            'gedung_spk' => $s['gedung_id'] ? (array_column($gedungList, 'nama', 'id')[(int) $s['gedung_id']] ?? null) : null,
        ], 'SPK ' . $s['nomor']);
    }

    /** Simpan penanda manual ceklis kelengkapan dokumen SPK. */
    public function ceklis($id)
    {
        $s = $this->db()->table('spk')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $s) {
            return redirect()->to('spk')->with('galat', 'SPK tidak ditemukan di sekolah aktif.');
        }
        $item = (string) $this->request->getPost('item');
        if (! in_array($item, ['perencanaan', 'kuitansi'], true)) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', 'Item ceklis tidak dikenal.');
        }
        $ada = $this->request->getPost('ada') ? 1 : 0;
        $db = $this->db();
        $lama = $db->table('dokumen_spk')->where(['spk_id' => (int) $id, 'item' => $item, 'is_deleted' => 0])->get()->getRowArray();
        if ($lama) {
            $db->table('dokumen_spk')->where('id', $lama['id'])->update(['ada' => $ada, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('dokumen_spk')->insert([
                'sekolah_id' => $this->sid(), 'spk_id' => (int) $id, 'item' => $item, 'ada' => $ada,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->to('spk/detail/' . (int) $id)->with('sukses', 'Ceklis kelengkapan dokumen diperbarui.');
    }

    /**
     * Tuntaskan SPK (Ide A): buat BAP + BAST + Invoice sekaligus dalam satu
     * transaksi dari satu tanggal terima. Hanya untuk SPK yang belum punya
     * BAP maupun Invoice — SPK yang sudah berjalan sebagian tetap dikelola
     * dari halaman Dokumen/Invoice seperti biasa.
     */
    public function tuntaskan($id)
    {
        $db = $this->db();
        $s = $db->table('spk')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $s) {
            return redirect()->to('spk')->with('galat', 'SPK tidak ditemukan di sekolah aktif.');
        }
        $jmlBap = (int) $db->query('SELECT COUNT(*) AS j FROM bap WHERE spk_id = ? AND is_deleted = 0', [(int) $id])->getRow()->j;
        $jmlInv = (int) $db->query('SELECT COUNT(*) AS j FROM invoice WHERE spk_id = ? AND is_deleted = 0', [(int) $id])->getRow()->j;
        if ($jmlBap > 0 || $jmlInv > 0) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', 'SPK ini sudah mempunyai BAP/Invoice — penyelesaian satu klik hanya untuk SPK yang belum dimulai; lanjutkan dari halaman Dokumen/Invoice.');
        }
        $detail = $db->table('spk_detail')->where(['spk_id' => (int) $id, 'is_deleted' => 0])->get()->getResultArray();
        if (! $detail) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', 'SPK belum mempunyai rincian barang.');
        }
        $tgl = (string) ($this->request->getPost('tanggal_terima') ?: '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
            $tgl = $s['tgl_selesai'] ?: date('Y-m-d');
        }
        // Tanggal terima menjadi tanggal BAP+BAST+Invoice sekaligus —
        // tidak boleh mendahului tanggal SPK (rantai SPK → BAP → BAST).
        if (! empty($s['tanggal']) && $tgl < (string) $s['tanggal']) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', 'Tanggal terima (' . tgl($tgl) . ') tidak boleh sebelum tanggal SPK (' . tgl($s['tanggal']) . ').');
        }
        $total = (int) round(array_sum(array_map(static fn ($d) => (float) $d['qty'] * (float) $d['harga'], $detail)));
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $urutBap = (int) $db->query('SELECT MAX(nomor_urut) m FROM bap')->getRow()->m + 1;
        $db->table('bap')->insert(['spk_id' => (int) $id, 'nomor_urut' => $urutBap, 'tanggal' => $tgl, 'created_at' => $now, 'updated_at' => $now]);
        $bapId = (int) $db->insertID();
        foreach ($detail as $d) {
            $db->table('bap_detail')->insert(['bap_id' => $bapId, 'detail_id' => $d['id'], 'kondisi' => 'Cukup']);
        }
        $urutBast = (int) $db->query('SELECT MAX(nomor_urut) m FROM bast')->getRow()->m + 1;
        $db->table('bast')->insert(['spk_id' => (int) $id, 'bap_id' => $bapId, 'nomor_urut' => $urutBast, 'tanggal' => $tgl, 'created_at' => $now, 'updated_at' => $now]);
        $bastId = (int) $db->insertID();
        $urutInv = (int) $db->query('SELECT MAX(nomor_urut) m FROM invoice')->getRow()->m + 1;
        $db->table('invoice')->insert([
            'nomor_urut' => $urutInv, 'no_toko' => null, 'tanggal' => $tgl, 'spk_id' => (int) $id,
            'bap_id' => $bapId, 'bast_id' => $bastId, 'nilai' => $total, 'status' => 'Belum Bayar',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', 'Penyelesaian gagal disimpan; tidak ada dokumen yang dibuat.');
        }
        $this->catatAudit('spk', (int) $id, 'tuntaskan', "BAP urut {$urutBap}, BAST urut {$urutBast}, dan Invoice urut {$urutInv} dibuat sekaligus (tanggal terima {$tgl}).");
        return redirect()->to('spk/detail/' . (int) $id)->with('sukses', "SPK dituntaskan: BAP urut {$urutBap}, BAST urut {$urutBast}, dan Invoice urut {$urutInv} dibuat sekaligus. Tinggal catat pembayarannya di panel yang sama.");
    }

    /** Tambah baris catatan kebutuhan tenaga kerja SPK. */
    public function tenagaTambah($spkId)
    {
        $db = $this->db();
        $s = $db->table('spk')->where(['id' => (int) $spkId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $s) {
            return redirect()->to('spk')->with('galat', 'SPK tidak ditemukan di sekolah aktif.');
        }
        $jenis = trim((string) $this->request->getPost('jenis'));
        if (! in_array($jenis, ['Pekerja', 'Tukang'], true)) {
            return redirect()->to('spk/detail/' . (int) $spkId)->with('galat', 'Jenis tenaga harus Pekerja atau Tukang.');
        }
        $oh = (float) str_replace(',', '.', (string) ($this->request->getPost('oh') ?: 0));
        $orang = $this->request->getPost('jumlah_orang');
        // Perkiraan orang kosong = ceil(OH ÷ lama periode SPK) bila periode
        // diketahui (pola hitung yang sama dengan jalur Rencana Belanja).
        if (($orang === '' || $orang === null) && $oh > 0 && ! empty($s['tgl_mulai']) && ! empty($s['tgl_selesai']) && $s['tgl_selesai'] >= $s['tgl_mulai']) {
            $hari = (int) ((strtotime((string) $s['tgl_selesai']) - strtotime((string) $s['tgl_mulai'])) / 86400) + 1;
            $orang = (string) (int) ceil($oh / max(1, $hari));
        }
        $db->table('spk_tenaga')->insert([
            'sekolah_id' => $this->sid(), 'spk_id' => (int) $spkId, 'jenis' => $jenis,
            'keahlian' => trim((string) $this->request->getPost('keahlian')) ?: null,
            'oh' => round($oh, 2),
            'jumlah_orang' => ($orang === '' || $orang === null) ? null : max(0, (int) $orang),
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('spk/detail/' . (int) $spkId)->with('sukses', 'Baris kebutuhan tenaga kerja ditambahkan.');
    }

    /** Hapus baris catatan kebutuhan tenaga kerja SPK (hapus lunak). */
    public function tenagaHapus($tenagaId)
    {
        $t = $this->db()->table('spk_tenaga')->where(['id' => (int) $tenagaId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $t) {
            return redirect()->to('spk')->with('galat', 'Baris tenaga kerja tidak ditemukan.');
        }
        $this->hapusLunak('spk_tenaga', ['id' => (int) $tenagaId]);
        return redirect()->to('spk/detail/' . (int) $t['spk_id'])->with('sukses', 'Baris kebutuhan tenaga kerja dihapus.');
    }

    /** Catat pembayaran invoice SPK ini langsung dari panel Tuntaskan. */
    public function bayarCepat($id)
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $db = $this->db();
        $s = $db->table('spk')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $s) {
            return redirect()->to('spk')->with('galat', 'SPK tidak ditemukan di sekolah aktif.');
        }
        $inv = $db->query("SELECT * FROM invoice WHERE spk_id = ? AND is_deleted = 0 AND status <> 'Sudah Bayar' ORDER BY nomor_urut LIMIT 1", [(int) $id])->getRowArray();
        if (! $inv) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', 'Tidak ada invoice yang menunggu pembayaran untuk SPK ini.');
        }
        // Kontrol pagu sama seperti Jurnal Pembayaran.
        [$boleh, $sisaPagu, $anggaran] = $this->cekPagu((float) $inv['nilai']);
        if (! $boleh) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', sprintf(
                'Pembayaran ditahan: nilai invoice %s melewati sisa pagu anggaran %s dari total anggaran %s.',
                $this->rp($inv['nilai']),
                $this->rp(max(0, $sisaPagu)),
                $this->rp($anggaran)
            ));
        }
        $tgl = (string) ($this->request->getPost('tanggal_bayar') ?: '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
            $tgl = date('Y-m-d');
        }
        if ($g = $this->galatBulanTertutup($tgl)) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', $g);
        }
        // Urutan tanggal sama seperti Jurnal Pembayaran: bayar tidak mendahului invoice.
        if (! empty($inv['tanggal']) && $tgl < (string) $inv['tanggal']) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', 'Tanggal bayar (' . tgl($tgl) . ') tidak boleh sebelum tanggal invoice (' . tgl($inv['tanggal']) . ') — pembayaran dicatat sesudah tagihan diterbitkan.');
        }
        // Saldo bank tidak boleh minus pada tanggal bayar maupun sesudahnya.
        $pjk = $this->pajakPembelian((float) $inv['nilai'], $this->ppnInvoice($this->ppnSpkMap(), (int) $inv['spk_id'], (float) $inv['nilai']));
        if ($g = $this->galatSaldoMinus('bank', $tgl, (float) $pjk['dibayar'])) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', $g);
        }
        if ($g = $this->galatRekeningPenyedia((int) $inv['spk_id'])) {
            return redirect()->to('spk/detail/' . (int) $id)->with('galat', $g);
        }
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('pembayaran')->insert(['invoice_id' => (int) $inv['id'], 'tanggal' => $tgl, 'keterangan' => null, 'created_at' => $now, 'updated_at' => $now]);
        $db->table('invoice')->where('id', (int) $inv['id'])->update(['status' => 'Sudah Bayar', 'updated_at' => $now]);
        $db->transComplete();
        $this->catatAudit('pembayaran', (int) $inv['id'], 'buat', 'Pembayaran invoice sebesar ' . $this->rp($inv['nilai']) . ' dicatat dari panel Tuntaskan SPK.');
        return redirect()->to('spk/detail/' . (int) $id)->with('sukses', 'Pembayaran dicatat; PPh 22 dan nilai bersih terhitung otomatis. Rantai SPK ini selesai — tinggal cetak SPJ lengkapnya.');
    }

    /**
     * Alasan rincian SPK terkunci (null = boleh diubah): SPK sudah punya
     * BAST atau invoice terbayar — dokumen yang sudah ditandatangani tidak
     * boleh berubah isinya lewat ubah/hapus rincian.
     */
    private function rincianTerkunci(int $spkId): ?string
    {
        $db = $this->db();
        if ($db->table('bast')->where(['spk_id' => $spkId, 'is_deleted' => 0])->countAllResults() > 0) {
            return 'Rincian SPK ini terkunci karena sudah mempunyai BAST — koreksi dilakukan lewat dokumen baru, bukan mengubah rincian yang sudah diserahterimakan.';
        }
        $terbayar = (int) $db->query(
            'SELECT COUNT(*) AS j FROM pembayaran p JOIN invoice i ON i.id = p.invoice_id AND i.is_deleted = 0
             WHERE i.spk_id = ? AND p.is_deleted = 0',
            [$spkId]
        )->getRow()->j;
        if ($terbayar > 0) {
            return 'Rincian SPK ini terkunci karena sudah ada invoice yang terbayar — koreksi pembayaran lewat Jurnal Pembayaran terlebih dahulu.';
        }
        return null;
    }

    public function detailTambah($spkId)
    {
        $bahanId = (int) $this->request->getPost('bahan_id');
        $qty = (float) str_replace(',', '.', (string) ($this->request->getPost('qty') ?: 0));
        $harga = $this->request->getPost('harga');
        $harga = ($harga === '' || $harga === null) ? $this->hargaAcuan($bahanId) : (int) $harga;
        if ($qty <= 0 || $harga < 0) {
            return redirect()->to('spk/detail/' . $spkId)->with('galat', 'Qty harus lebih dari 0 dan harga tidak boleh negatif.');
        }
        $this->db()->table('spk_detail')->insert([
            'sekolah_id' => $this->sid(),
            'spk_id' => (int) $spkId, 'bahan_id' => $bahanId,
            'qty' => $qty, 'harga' => $harga,
            'pajak' => (int) $this->request->getPost('pajak'),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('spk/detail/' . $spkId)->with('sukses', 'Baris ditambahkan.');
    }

    public function detailUbah($detailId)
    {
        $d = $this->db()->table('spk_detail')->where(['id' => (int) $detailId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $d) {
            return redirect()->to('spk')->with('galat', 'Baris tidak ditemukan.');
        }
        $kembali = $d['spk_id'] ? 'spk/detail/' . $d['spk_id'] : 'belum-bernomor';
        if (! empty($d['spk_id']) && ($kunci = $this->rincianTerkunci((int) $d['spk_id']))) {
            return redirect()->to($kembali)->with('galat', $kunci);
        }
        $qty = (float) str_replace(',', '.', (string) ($this->request->getPost('qty') ?: 0));
        $harga = (int) $this->request->getPost('harga');
        if ($qty <= 0 || $harga < 0) {
            return redirect()->to($kembali)->with('galat', 'Qty harus lebih dari 0 dan harga tidak boleh negatif.');
        }
        $this->db()->table('spk_detail')->where('id', (int) $detailId)->update([
            'qty' => $qty,
            'harga' => $harga,
            'pajak' => (int) $this->request->getPost('pajak'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to($kembali)->with('sukses', 'Baris diperbarui.');
    }

    public function detailHapus($detailId)
    {
        $d = $this->db()->table('spk_detail')->where(['id' => (int) $detailId, 'is_deleted' => 0])->get()->getRowArray();
        $kembali = ($d && $d['spk_id']) ? 'spk/detail/' . $d['spk_id'] : 'belum-bernomor';
        if ($d && ! empty($d['spk_id']) && ($kunci = $this->rincianTerkunci((int) $d['spk_id']))) {
            return redirect()->to($kembali)->with('galat', $kunci);
        }
        $this->hapusLunak('spk_detail', ['id' => (int) $detailId]);
        return redirect()->to($kembali)->with('sukses', 'Baris dihapus.');
    }

    public function belumBernomor()
    {
        $detail = $this->db()->query(
            'SELECT d.*, COALESCE(b.kode, "") AS kode, COALESCE(b.nama, d.nama_manual) AS nama,
                    COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty*d.harga AS total
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id
             WHERE d.spk_id IS NULL AND d.sekolah_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [$this->sid()]
        )->getResultArray();
        $spk = $this->db()->table('spk')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nomor')->get()->getResultArray();
        return $this->tampil('spk/belum', ['detail' => $detail, 'spk' => $spk], 'Detail Belum Bernomor SPK — Aplikasi PBJ Sekolah');
    }

    public function pasangNomor($detailId)
    {
        $spkId = (int) $this->request->getPost('spk_id');
        if ($spkId <= 0 || $this->db()->table('spk')->where(['id' => $spkId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults() === 0) {
            return redirect()->to('belum-bernomor')->with('galat', 'Pilih dulu nomor SPK dari sekolah aktif.');
        }
        $this->db()->table('spk_detail')->where('id', (int) $detailId)->update(['spk_id' => $spkId, 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('belum-bernomor')->with('sukses', 'Baris dipasangkan ke SPK.');
    }

    private function ambilForm(): array
    {
        $r = $this->request;
        $penyediaBaru = trim((string) $r->getPost('penyedia_baru'));
        $penyediaId = (int) $r->getPost('penyedia_id') ?: null;
        if ($penyediaBaru !== '') {
            $this->db()->table('penyedia')->insert(['sekolah_id' => $this->sid(), 'nama' => $penyediaBaru, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $penyediaId = (int) $this->db()->insertID();
        }
        $tgl = static fn ($v) => ($v === '' || $v === null) ? null : $v;
        $tanggal = $tgl($r->getPost('tanggal'));
        // Warisan: tgl_mulai kosong = tanggal SPK; penandatangan kosong =
        // ttd_nama master penyedia.
        $ttd = trim((string) $r->getPost('ttd_penyedia')) ?: null;
        if ($ttd === null && $penyediaId !== null) {
            $pen = $this->db()->table('penyedia')->select('ttd_nama')->where(['id' => $penyediaId, 'sekolah_id' => $this->sid()])->get()->getRowArray();
            $ttd = ! empty($pen['ttd_nama']) ? (string) $pen['ttd_nama'] : null;
        }
        return [
            'nomor' => (int) $r->getPost('nomor'),
            'tanggal' => $tanggal,
            'tgl_mulai' => $tgl($r->getPost('tgl_mulai')) ?? $tanggal,
            'tgl_selesai' => $tgl($r->getPost('tgl_selesai')),
            'penyedia_id' => $penyediaId,
            'penerima_manual' => trim((string) $r->getPost('penerima_manual')) ?: null,
            'gedung_id' => (int) $r->getPost('gedung_id') ?: null,
            'ttd_penyedia' => $ttd,
            'paket' => trim((string) $r->getPost('paket')) ?: null,
            'status' => (string) $r->getPost('status') ?: 'Aktif',
        ];
    }

    /** Validasi kepala SPK: urutan tanggal, nomor unik, penerima Masyarakat. */
    private function galatFormSpk(array $data, int $kecualiId): ?string
    {
        if (! empty($data['tanggal']) && ! empty($data['tgl_mulai']) && (string) $data['tgl_mulai'] < (string) $data['tanggal']) {
            return 'Tanggal mulai (' . tgl($data['tgl_mulai']) . ') tidak boleh sebelum tanggal SPK (' . tgl($data['tanggal']) . ').';
        }
        if (! empty($data['tgl_mulai']) && ! empty($data['tgl_selesai']) && (string) $data['tgl_selesai'] < (string) $data['tgl_mulai']) {
            return 'Tanggal selesai (' . tgl($data['tgl_selesai']) . ') tidak boleh sebelum tanggal mulai (' . tgl($data['tgl_mulai']) . ').';
        }
        $dipakai = $this->db()->table('spk')
            ->where(['nomor' => $data['nomor'], 'sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->where('id <>', $kecualiId)->countAllResults() > 0;
        if ($dipakai) {
            return 'Nomor SPK ' . $data['nomor'] . ' sudah ada.';
        }
        if (! empty($data['penyedia_id']) && empty($data['penerima_manual'])) {
            $pen = $this->db()->table('penyedia')->select('nama')->where(['id' => (int) $data['penyedia_id'], 'sekolah_id' => $this->sid()])->get()->getRowArray();
            if ($pen !== null && (string) $pen['nama'] === 'Masyarakat') {
                return 'SPK untuk penyedia "Masyarakat" wajib mengisi nama penerima (orang yang dibayar) pada kolom Penerima.';
            }
        }
        return null;
    }
}
