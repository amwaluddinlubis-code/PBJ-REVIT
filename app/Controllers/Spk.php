<?php

namespace App\Controllers;

class Spk extends BaseController
{
    public function index()
    {
        $spk = $this->db()->query(
            'SELECT s.*, p.nama AS penyedia, COUNT(d.id) AS jumlah, COALESCE(SUM(d.qty*d.harga),0) AS nilai
             FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id
             LEFT JOIN spk_detail d ON d.spk_id = s.id AND d.is_deleted = 0
             WHERE s.sekolah_id = ? AND s.is_deleted = 0
             GROUP BY s.id ORDER BY s.nomor',
            [$this->sid()]
        )->getResultArray();
        return $this->tampil('spk/index', ['spk' => $spk], 'Register SPK — Aplikasi PBJ Sekolah');
    }

    public function baru()
    {
        $row = $this->db()->query('SELECT MAX(nomor) m FROM spk WHERE sekolah_id = ? AND is_deleted = 0', [$this->sid()])->getRow();
        return $this->tampil('spk/form', [
            's' => ['nomor' => ((int) $row->m) + 1, 'status' => 'Aktif'],
            'penyedia' => $this->db()->table('penyedia')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray(),
            'aksi' => 'spk/simpan',
        ], 'SPK Baru');
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if ($this->db()->table('spk')->where(['nomor' => $data['nomor'], 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('galat', 'Nomor SPK ' . $data['nomor'] . ' sudah ada.');
        }
        $data['sekolah_id'] = $this->sid();
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('spk')->insert($data);
        return redirect()->to('spk/detail/' . $this->db()->insertID())->with('sukses', 'SPK ' . $data['nomor'] . ' dibuat — silakan isi rinciannya.');
    }

    public function ubah($id)
    {
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
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('spk')->where(['id' => (int) $id, 'sekolah_id' => $this->sid()])->update($data);
        return redirect()->to('spk')->with('sukses', 'SPK diperbarui.');
    }

    public function hapus($id)
    {
        // Hapus lunak: SPK ditandai terhapus; rinciannya menjadi belum bernomor
        // (spk_id dikosongkan) agar tetap bisa dipakai ulang. BAP/BAST/invoice
        // yang menunjuk SPK ini ikut tersembunyi lewat saringannya.
        $db = $this->db();
        $db->table('spk_detail')->where(['spk_id' => (int) $id, 'is_deleted' => 0])
            ->update(['spk_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->hapusLunak('spk', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('spk')->with('sukses', 'SPK dihapus; rincian barangnya kini berstatus belum bernomor.');
    }

    public function detail($id)
    {
        $db = $this->db();
        $s = $db->query(
            'SELECT s.*, p.nama AS penyedia FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.id = ? AND s.is_deleted = 0',
            [(int) $id]
        )->getRowArray();
        if (! $s || (int) $s['sekolah_id'] !== $this->sid()) {
            return redirect()->to('spk')->with('galat', 'SPK tidak ditemukan di sekolah aktif.');
        }
        $detail = $db->query(
            'SELECT d.*, COALESCE(b.kode, "") AS kode, COALESCE(b.nama, d.nama_manual) AS nama,
                    COALESCE(b.satuan, d.satuan_manual) AS satuan, d.qty*d.harga AS total,
                    CASE WHEN d.pajak = 1 THEN ROUND(0.11 * d.qty * d.harga / 1.11) ELSE 0 END AS ppn
             FROM spk_detail d LEFT JOIN bahan b ON b.id = d.bahan_id
             WHERE d.spk_id = ? AND d.is_deleted = 0 ORDER BY d.id',
            [(int) $id]
        )->getResultArray();
        $bahan = $db->query('SELECT id, kode, nama, satuan, harga_rab FROM bahan WHERE sekolah_id = ? AND is_deleted = 0 ORDER BY kode', [$this->sid()])->getResultArray();
        foreach ($bahan as &$b) {
            $b['harga_acuan'] = $this->hargaAcuan((int) $b['id']);
        }
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
            ['kunci' => 'pembandingan', 'label' => 'Hasil pembandingan harga (Survei Harga)', 'ada' => $pembanding > 0, 'manual' => false],
            ['kunci' => 'negosiasi', 'label' => 'Hasil negosiasi harga', 'ada' => $hitung('SELECT COUNT(*) AS j FROM negosiasi n JOIN spk_detail d ON d.bahan_id = n.bahan_id AND d.is_deleted = 0 WHERE d.spk_id = ? AND n.is_deleted = 0 AND n.harga_nego IS NOT NULL') > 0, 'manual' => false],
            ['kunci' => 'pemesanan', 'label' => 'Surat pemesanan (SPK ini)', 'ada' => true, 'manual' => false],
            ['kunci' => 'pemeriksaan', 'label' => 'Hasil pemeriksaan barang/jasa (BAP)', 'ada' => $hitung('SELECT COUNT(*) AS j FROM bap WHERE spk_id = ? AND is_deleted = 0') > 0, 'manual' => false],
            ['kunci' => 'bast', 'label' => 'Berita Acara Serah Terima (BAST)', 'ada' => $hitung('SELECT COUNT(*) AS j FROM bast WHERE spk_id = ? AND is_deleted = 0') > 0, 'manual' => false],
            ['kunci' => 'invoice', 'label' => 'Invoice / tagihan penyedia', 'ada' => $hitung('SELECT COUNT(*) AS j FROM invoice WHERE spk_id = ? AND is_deleted = 0') > 0, 'manual' => false],
            ['kunci' => 'pembayaran', 'label' => 'Bukti pembayaran (jurnal pembayaran)', 'ada' => $hitung('SELECT COUNT(*) AS j FROM pembayaran p JOIN invoice i ON i.id = p.invoice_id AND i.is_deleted = 0 WHERE i.spk_id = ? AND p.is_deleted = 0') > 0, 'manual' => false],
            ['kunci' => 'kuitansi', 'label' => 'Kuitansi/faktur fisik dari penyedia (ditandatangani + stempel; bermaterai bila nilai kuitansi ≥ Rp5 juta)', 'ada' => $manual['kuitansi'] ?? false, 'manual' => true],
        ];
        return $this->tampil('spk/detail', [
            's' => $s, 'detail' => $detail, 'bahan' => $bahan,
            'nilai_paket' => $nilaiPaket,
            'ketentuan' => $this->ketentuanPbj($nilaiPaket),
            'pembanding' => $pembanding,
            'ceklis' => $ceklis,
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

    public function detailTambah($spkId)
    {
        $bahanId = (int) $this->request->getPost('bahan_id');
        $harga = $this->request->getPost('harga');
        $harga = ($harga === '' || $harga === null) ? $this->hargaAcuan($bahanId) : (int) $harga;
        $this->db()->table('spk_detail')->insert([
            'sekolah_id' => $this->sid(),
            'spk_id' => (int) $spkId, 'bahan_id' => $bahanId,
            'qty' => (float) $this->request->getPost('qty'), 'harga' => $harga,
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
        $this->db()->table('spk_detail')->where('id', (int) $detailId)->update([
            'qty' => (float) $this->request->getPost('qty'),
            'harga' => (int) $this->request->getPost('harga'),
            'pajak' => (int) $this->request->getPost('pajak'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $kembali = $d['spk_id'] ? 'spk/detail/' . $d['spk_id'] : 'belum-bernomor';
        return redirect()->to($kembali)->with('sukses', 'Baris diperbarui.');
    }

    public function detailHapus($detailId)
    {
        $d = $this->db()->table('spk_detail')->where(['id' => (int) $detailId, 'is_deleted' => 0])->get()->getRowArray();
        $this->hapusLunak('spk_detail', ['id' => (int) $detailId]);
        $kembali = ($d && $d['spk_id']) ? 'spk/detail/' . $d['spk_id'] : 'belum-bernomor';
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
        return $this->tampil('spk/belum', ['detail' => $detail, 'spk' => $spk], 'Detail Belum Bernomor SPK');
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
        return [
            'nomor' => (int) $r->getPost('nomor'),
            'tanggal' => $tgl($r->getPost('tanggal')),
            'tgl_mulai' => $tgl($r->getPost('tgl_mulai')),
            'tgl_selesai' => $tgl($r->getPost('tgl_selesai')),
            'penyedia_id' => $penyediaId,
            'gedung_id' => (int) $r->getPost('gedung_id') ?: null,
            'ttd_penyedia' => trim((string) $r->getPost('ttd_penyedia')) ?: null,
            'paket' => trim((string) $r->getPost('paket')) ?: null,
            'status' => (string) $r->getPost('status') ?: 'Aktif',
        ];
    }
}
