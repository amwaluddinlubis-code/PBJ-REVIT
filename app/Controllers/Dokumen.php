<?php

namespace App\Controllers;

class Dokumen extends BaseController
{
    private function hariTanggal(?string $tgl): string
    {
        if (! $tgl) {
            return '……………, tanggal ……………';
        }
        $ts = strtotime($tgl);
        $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $hari[date('l', $ts)] . ', tanggal ' . $this->terbilang((int) date('j', $ts)) . ' bulan '
            . $bulan[(int) date('n', $ts)] . ' tahun ' . $this->terbilang((int) date('Y', $ts))
            . ' (' . date('d/m/Y', $ts) . ')';
    }

    private function identitas(): array
    {
        return $this->sekolahAktif();
    }

    public function index()
    {
        $db = $this->db();
        $spk = $db->query(
            'SELECT s.id, s.nomor, s.paket, s.tanggal, s.tgl_mulai, s.tgl_selesai, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia,
                    (SELECT COUNT(*) FROM spk_detail d WHERE d.spk_id = s.id AND d.is_deleted = 0) AS jumlah_detail,
                    (SELECT COUNT(*) FROM bap_detail bd JOIN bap bp ON bp.id = bd.bap_id AND bp.is_deleted = 0
                      WHERE bp.spk_id = s.id AND bd.is_deleted = 0) AS jumlah_terbap
             FROM spk s
             LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.sekolah_id = ? AND s.is_deleted = 0 ORDER BY s.nomor',
            [$this->sid()]
        )->getResultArray();
        $bap = [];
        foreach ($db->table('bap')->where('is_deleted', 0)->get()->getResultArray() as $r) {
            $bap[$r['spk_id']][] = $r;
        }
        $bapItems = [];
        foreach ($db->query(
            'SELECT bd.bap_id, bd.id AS bd_id, bd.kondisi, bd.keterangan, d.qty,
                    COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan
             FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
             LEFT JOIN bahan b ON b.id = d.bahan_id WHERE bd.is_deleted = 0 ORDER BY bd.id'
        )->getResultArray() as $r) {
            $bapItems[$r['bap_id']][] = $r;
        }
        $bastPerBap = [];
        $bastUmum = [];
        foreach ($db->table('bast')->where('is_deleted', 0)->get()->getResultArray() as $r) {
            if (! empty($r['bap_id'])) {
                $bastPerBap[$r['bap_id']][] = $r;
            } else {
                $bastUmum[$r['spk_id']][] = $r;
            }
        }
        return $this->tampil('dokumen/index', [
            'spk' => $spk, 'bap' => $bap, 'bap_items' => $bapItems,
            'bast_per_bap' => $bastPerBap, 'bast_umum' => $bastUmum,
        ], 'BAP & BAST — Aplikasi PBJ Sekolah');
    }

    public function bapBuat($spkId)
    {
        $db = $this->db();
        // Aturan ketat: BAP baru hanya bila masih ada rincian SPK yang belum
        // ter-BAP — jumlah item BAP tidak boleh melebihi jumlah rincian SPK.
        $total = (int) $db->query('SELECT COUNT(*) AS j FROM spk_detail WHERE spk_id = ? AND is_deleted = 0', [(int) $spkId])->getRow()->j;
        $terbap = (int) $db->query(
            'SELECT COUNT(*) AS j FROM bap_detail bd JOIN bap bp ON bp.id = bd.bap_id AND bp.is_deleted = 0 WHERE bp.spk_id = ? AND bd.is_deleted = 0',
            [(int) $spkId]
        )->getRow()->j;
        if ($terbap >= $total) {
            return redirect()->to('dokumen')->with('galat', 'Semua rincian SPK ini sudah ter-BAP (' . $terbap . ' dari ' . $total . ' barang) — BAP baru tidak diperlukan.');
        }
        $row = $db->query('SELECT MAX(nomor_urut) m FROM bap')->getRow();
        $db->table('bap')->insert([
            'spk_id' => (int) $spkId, 'nomor_urut' => ((int) $row->m) + 1,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $bapId = (int) $db->insertID();
        foreach ($db->table('spk_detail')->where(['spk_id' => (int) $spkId, 'is_deleted' => 0])->get()->getResultArray() as $d) {
            $db->table('bap_detail')->insert(['bap_id' => $bapId, 'detail_id' => $d['id'], 'kondisi' => 'Cukup']);
        }
        return redirect()->to('dokumen')->with('sukses', 'BAP dibuat dari rincian SPK — lengkapi tanggal dan kondisi pemeriksaan lewat tombol Ubah pada BAP-nya.');
    }

    public function bapUbah($bapId)
    {
        $db = $this->db();
        $bap = $db->table('bap')->where(['id' => (int) $bapId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $bap) {
            return redirect()->to('dokumen')->with('galat', 'BAP tidak ditemukan.');
        }
        $items = $db->query(
            'SELECT bd.id AS bd_id, bd.kondisi, bd.keterangan, d.qty,
                    COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan
             FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
             LEFT JOIN bahan b ON b.id = d.bahan_id WHERE bd.bap_id = ? AND bd.is_deleted = 0 ORDER BY bd.id',
            [(int) $bapId]
        )->getResultArray();
        $spk = $db->query('SELECT nomor, tanggal, tgl_mulai, tgl_selesai FROM spk WHERE id = ?', [(int) $bap['spk_id']])->getRowArray();
        return $this->tampil('dokumen/bap_form', ['bap' => $bap, 'items' => $items, 'spk' => $spk], 'BAP ' . $this->nomorSurat('BAP', (int) $bap['nomor_urut']));
    }

    public function bapSimpan($bapId)
    {
        $db = $this->db();
        $db->table('bap')->where('id', (int) $bapId)->update([
            'nomor_urut' => (int) $this->request->getPost('nomor_urut'),
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($this->request->getPost('kondisi') ?? [] as $bdId => $kondisi) {
            $db->table('bap_detail')->where('id', (int) $bdId)->update([
                'kondisi' => $kondisi,
                'keterangan' => ($this->request->getPost('keterangan') ?? [])[$bdId] ?? null,
            ]);
        }
        return redirect()->to('dokumen')->with('sukses', 'BAP disimpan.');
    }

    /** BAST dibuat per BAP (aturan 1 BAP = 1 BAST); parameternya id BAP. */
    public function bastBuat($bapId)
    {
        $db = $this->db();
        $bap = $db->table('bap')->where(['id' => (int) $bapId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $bap) {
            return redirect()->to('dokumen')->with('galat', 'BAP tidak ditemukan.');
        }
        if ($db->table('bast')->where(['bap_id' => (int) $bapId, 'is_deleted' => 0])->countAllResults() > 0) {
            return redirect()->to('dokumen')->with('galat', 'BAP ini sudah mempunyai BAST — satu BAP hanya boleh satu BAST.');
        }
        $row = $db->query('SELECT MAX(nomor_urut) m FROM bast')->getRow();
        $db->table('bast')->insert([
            'spk_id' => (int) $bap['spk_id'], 'bap_id' => (int) $bapId, 'nomor_urut' => ((int) $row->m) + 1,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('dokumen')->with('sukses', 'BAST dibuat untuk BAP urut ' . (int) $bap['nomor_urut'] . ' — lengkapi tanggalnya lewat tombol Ubah.');
    }

    public function bastUbah($bastId)
    {
        $bast = $this->db()->table('bast')->where(['id' => (int) $bastId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $bast) {
            return redirect()->to('dokumen')->with('galat', 'BAST tidak ditemukan.');
        }
        return $this->tampil('dokumen/bast_form', ['bast' => $bast], 'BAST ' . $this->nomorSurat('BAST', (int) $bast['nomor_urut']));
    }

    public function bastSimpan($bastId)
    {
        $this->db()->table('bast')->where('id', (int) $bastId)->update([
            'nomor_urut' => (int) $this->request->getPost('nomor_urut'),
            'tanggal' => $this->request->getPost('tanggal') ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('dokumen')->with('sukses', 'BAST disimpan.');
    }

    // ---------------- CETAK ----------------

    public function cetakSpk($spkId)
    {
        $db = $this->db();
        $s = $db->query('SELECT s.*, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, p.alamat AS penyedia_alamat, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0', [(int) $spkId])->getRowArray();
        if (! $s) {
            return redirect()->to('dokumen')->with('galat', 'SPK tidak ditemukan.');
        }
        $detail = $db->query(
            'SELECT COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty, d.harga, d.qty*d.harga AS total,
                    gd.nama AS gedung_rincian
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id
             LEFT JOIN gedung gd ON gd.id = COALESCE(d.gedung_id, (SELECT s2.gedung_id FROM spk s2 WHERE s2.id = d.spk_id))
             WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY COALESCE(d.gedung_id, (SELECT s2.gedung_id FROM spk s2 WHERE s2.id = d.spk_id)), d.id',
            [(int) $spkId]
        )->getResultArray();
        $total = array_sum(array_column($detail, 'total'));
        $tenaga = [];
        try {
            $tenaga = $db->table('spk_tenaga')->where(['spk_id' => (int) $spkId, 'is_deleted' => 0])->orderBy('id')->get()->getResultArray();
        } catch (\Throwable $e) {
            // tabel spk_tenaga belum dimigrasi
        }
        return view('cetak/spk', [
            'idn' => $this->identitas(), 's' => $s, 'detail' => $detail,
            'total' => $total, 'terbilang' => $this->terbilang($total),
            'nomor' => $this->nomorSurat('SPK', (int) $s['nomor']),
            'hariTanggal' => $this->hariTanggal($s['tanggal']),
            'tenaga' => $tenaga,
        ]);
    }

    public function cetakBap($bapId)
    {
        $db = $this->db();
        $bap = $db->table('bap')->where(['id' => (int) $bapId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $bap) {
            return redirect()->to('dokumen')->with('galat', 'BAP tidak ditemukan.');
        }
        $s = $db->query('SELECT s.*, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0', [(int) $bap['spk_id']])->getRowArray();
        $items = $db->query(
            'SELECT bd.kondisi, bd.keterangan, d.qty, COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan
             FROM bap_detail bd JOIN spk_detail d ON d.id = bd.detail_id
             LEFT JOIN bahan b ON b.id = d.bahan_id WHERE bd.bap_id = ? AND bd.is_deleted = 0 ORDER BY bd.id',
            [(int) $bapId]
        )->getResultArray();
        return view('cetak/bap', [
            'idn' => $this->identitas(), 'bap' => $bap, 's' => $s, 'items' => $items,
            'nomor' => $this->nomorSurat('BAP', (int) $bap['nomor_urut']),
            'nomorSpk' => $this->nomorSurat('SPK', (int) $s['nomor']),
            'hariTanggal' => $this->hariTanggal($bap['tanggal']),
        ]);
    }

    public function cetakBast($bastId)
    {
        $db = $this->db();
        $bast = $db->table('bast')->where('id', (int) $bastId)->get()->getRowArray();
        if (! $bast) {
            return redirect()->to('dokumen')->with('galat', 'BAST tidak ditemukan.');
        }
        $s = $db->query('SELECT s.*, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0', [(int) $bast['spk_id']])->getRowArray();
        $items = $db->query(
            'SELECT COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [(int) $bast['spk_id']]
        )->getResultArray();
        return view('cetak/bast', [
            'idn' => $this->identitas(), 'bast' => $bast, 's' => $s, 'items' => $items,
            'nomor' => $this->nomorSurat('BAST', (int) $bast['nomor_urut']),
            'nomorSpk' => $this->nomorSurat('SPK', (int) $s['nomor']),
            'hariTanggal' => $this->hariTanggal($bast['tanggal']),
        ]);
    }

    /**
     * Cetak SPJ Lengkap (Ide A): SPK + BAP + BAST + Invoice SPK ini dalam
     * satu berkas siap cetak, tiap dokumen mulai di halaman baru. Dokumen
     * yang belum ada dilewati.
     */
    public function cetakSpj($spkId)
    {
        $db = $this->db();
        $s = $db->table('spk')->where(['id' => (int) $spkId, 'is_deleted' => 0])->get()->getRowArray();
        if (! $s || (int) $s['sekolah_id'] !== $this->sid()) {
            return redirect()->to('dokumen')->with('galat', 'SPK tidak ditemukan di sekolah aktif.');
        }
        $ambilIsi = static function ($html): string {
            if (! is_string($html)) {
                return '';
            }
            if (preg_match('/<body>(.*)<\/body>/s', $html, $m)) {
                $html = $m[1];
            }
            return (string) preg_replace('/<div class="takcetak">.*?<\/div>/s', '', $html);
        };
        $lembar = [];
        $lembar[] = $ambilIsi($this->cetakSpk($spkId));
        $bap = $db->query('SELECT * FROM bap WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut LIMIT 1', [(int) $spkId])->getRowArray();
        if ($bap) {
            $lembar[] = $ambilIsi($this->cetakBap((int) $bap['id']));
            $bast = $db->query('SELECT * FROM bast WHERE bap_id = ? AND is_deleted = 0 ORDER BY nomor_urut LIMIT 1', [(int) $bap['id']])->getRowArray();
            if ($bast) {
                $lembar[] = $ambilIsi($this->cetakBast((int) $bast['id']));
            }
        }
        $inv = $db->query('SELECT * FROM invoice WHERE spk_id = ? AND is_deleted = 0 ORDER BY nomor_urut LIMIT 1', [(int) $spkId])->getRowArray();
        if ($inv) {
            // Data sama seperti Invoice::cetak.
            $i = $db->query(
                'SELECT i.*, s.nomor AS spk_nomor, s.tanggal AS spk_tanggal, s.paket, COALESCE(NULLIF(TRIM(s.ttd_penyedia), ""), NULLIF(TRIM(s.penerima_manual), "")) AS ttd_penyedia,
                        COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia, p.alamat AS penyedia_alamat
                 FROM invoice i JOIN spk s ON s.id = i.spk_id
                 LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE i.id = ? AND s.sekolah_id = ? AND i.is_deleted = 0 AND s.is_deleted = 0',
                [(int) $inv['id'], $this->sid()]
            )->getRowArray();
            if ($i) {
                $detailInv = $db->query(
                    'SELECT COALESCE(b.nama, d.nama_manual) AS nama, COALESCE(b.satuan, d.satuan_manual) AS satuan,
                            d.qty, d.harga, d.qty*d.harga AS total
                     FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
                    [(int) $spkId]
                )->getResultArray();
                $lembar[] = $ambilIsi(view('cetak/invoice', [
                    'idn' => $this->sekolahAktif(), 'i' => $i, 'detail' => $detailInv,
                    'pajak' => $this->pajakPembelian((float) $i['nilai'], $this->ppnInvoice($this->ppnSpkMap(), (int) $spkId, (float) $i['nilai'])),
                    'totalSpk' => array_sum(array_column($detailInv, 'total')),
                    'terbilang' => $this->terbilang($i['nilai']),
                    'nomor' => $this->nomorSurat('INV', (int) $i['nomor_urut']),
                    'nomorSpk' => $this->nomorSurat('SPK', (int) $i['spk_nomor']),
                ]));
            }
        }
        $lembar = array_values(array_filter($lembar, static fn ($x) => trim((string) $x) !== ''));
        return view('cetak/spj', ['lembar' => $lembar, 'nomorSpk' => $this->nomorSurat('SPK', (int) $s['nomor'])]);
    }

    /**
     * BAST Aset per gedung (format dokumen Juknis): serah terima aset
     * hasil pembangunan — untuk satuan pendidikan negeri disampaikan
     * kepada Dinas Pendidikan, untuk swasta kepada penyelenggara
     * (yayasan). Berbeda dari BAST Pekerjaan yang disampaikan ke PPK.
     */
    public function cetakBastAset($gedungId)
    {
        $g = null;
        foreach ($this->semuaGedung() as $row) {
            if ((int) $row['id'] === (int) $gedungId) {
                $g = $row;
                break;
            }
        }
        if (! $g) {
            return redirect()->to('lpj')->with('galat', 'Gedung tidak ditemukan di sekolah aktif.');
        }
        return view('cetak/bast_aset', [
            'idn' => $this->identitas(), 'g' => $g,
            'hariTanggal' => $this->hariTanggal(date('Y-m-d')),
        ]);
    }

    /** Papan Nama Kegiatan (format dokumen Juknis) dari identitas sekolah. */
    public function cetakPapanNama()
    {
        return view('cetak/papan_nama', ['idn' => $this->identitas()]);
    }
}
