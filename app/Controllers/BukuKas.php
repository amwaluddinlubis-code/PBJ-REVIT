<?php

namespace App\Controllers;

/**
 * Pembukuan menurut Juknis Revitalisasi SMP 2026: Buku Kas Umum (BKU),
 * Buku Bank, dan Buku Pembantu Kas Tunai. Isi buku adalah gabungan
 * transaksi manual (penerimaan dana, koreksi) dengan pembayaran invoice,
 * pembayaran upah, dan bukti setor yang sudah tercatat di modul lain.
 * Buku ditutup per bulan (saldo awal, mutasi, saldo akhir) dan dapat
 * dicetak mengikuti format dokumen Juknis.
 */
class BukuKas extends BaseController
{
    private function namaBuku(string $buku): string
    {
        return ['bku' => 'Buku Kas Umum', 'bank' => 'Buku Bank', 'tunai' => 'Buku Pembantu Kas Tunai'][$buku] ?? 'Buku Kas Umum';
    }

    private function kanalBuku(string $buku): ?string
    {
        return ['bank' => 'bank', 'tunai' => 'tunai'][$buku] ?? null;
    }

    /** Susun baris buku untuk satu bulan: [saldo_awal, baris+saldo berjalan, saldo_akhir]. */
    private function susunBulan(string $buku, string $bulan): array
    {
        $semua = $this->gabungBukuKas($this->kanalBuku($buku));
        $awalBulan = $bulan . '-01';
        $akhirBulan = date('Y-m-t', strtotime($awalBulan));
        $saldo = 0.0;
        $baris = [];
        foreach ($semua as $e) {
            $tgl = $e['tanggal'] ?? $awalBulan;
            if ($tgl < $awalBulan) {
                $saldo += $e['masuk'] - $e['keluar'];
            } elseif ($tgl <= $akhirBulan) {
                $saldo += $e['masuk'] - $e['keluar'];
                $baris[] = $e + ['saldo' => $saldo];
            }
        }
        $saldoAwal = $baris ? $baris[0]['saldo'] - ($baris[0]['masuk'] - $baris[0]['keluar']) : $saldo;
        return [$saldoAwal, $baris, $saldo];
    }

    public function index()
    {
        $buku = (string) ($this->request->getGet('buku') ?? 'bku');
        if (! in_array($buku, ['bku', 'bank', 'tunai'], true)) {
            $buku = 'bku';
        }
        $bulan = (string) ($this->request->getGet('bulan') ?? date('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }
        [$saldoAwal, $baris, $saldoAkhir] = $this->susunBulan($buku, $bulan);
        $manual = $this->db()->table('kas_transaksi')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        // Data panel Tutup Buku: bulan terbuka, ringkasannya, dan daftar bulan tertutup.
        $bt = $this->bulanTerbuka();
        $ringkasTerbuka = null;
        $belumBayarBulan = 0;
        if ($bt !== null) {
            [$sa, $br, $sk] = $this->susunBulan('bku', $bt['ym']);
            $ringkasTerbuka = [
                'saldo_awal' => $sa, 'saldo_akhir' => $sk, 'jumlah_baris' => count($br),
                'total_masuk' => array_sum(array_column($br, 'masuk')),
                'total_keluar' => array_sum(array_column($br, 'keluar')),
            ];
            $belumBayarBulan = (int) $this->db()->query(
                "SELECT COUNT(*) j FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
                 WHERE s.sekolah_id = ? AND i.is_deleted = 0 AND i.status = 'Belum Bayar' AND i.tanggal LIKE ?",
                [$this->sid(), $bt['ym'] . '%']
            )->getRow()->j;
        }
        return $this->tampil('bukukas/index', [
            'buku' => $buku, 'nama_buku' => $this->namaBuku($buku), 'bulan' => $bulan,
            'saldo_awal' => $saldoAwal, 'baris' => $baris, 'saldo_akhir' => $saldoAkhir,
            'manual' => $manual,
            'tgl_bayar_default' => $this->tanggalBulanBekerja(),
            'buku_terbuka' => $bt, 'ringkas_terbuka' => $ringkasTerbuka,
            'daftar_tutup' => $this->petaTutupBuku(), 'belum_bayar_bulan' => $belumBayarBulan,
            'saya_admin' => ($this->pengguna['peran'] ?? '') === 'admin',
        ] + $this->batasBuku(), $this->namaBuku($buku) . ' — Aplikasi PBJ Sekolah');
    }

    /** Balasan form modal (pola Bahan::balasForm): AJAX -> JSON {ok, pesan}; biasa -> redirect flash. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('/buku-kas')->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function simpan()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $r = $this->request;
        $uraian = trim((string) $r->getPost('uraian'));
        $jumlah = (float) preg_replace('/[^0-9]/', '', (string) $r->getPost('jumlah'));
        $jenis = (string) $r->getPost('jenis') === 'keluar' ? 'keluar' : 'masuk';
        $kanal = (string) $r->getPost('kanal') === 'tunai' ? 'tunai' : 'bank';
        $kategori = (string) $r->getPost('kategori');
        if (! in_array($kategori, ['penerimaan', 'pajak', 'sisa_dana', 'lainnya'], true)) {
            $kategori = 'lainnya';
        }
        $tahap = $kategori === 'penerimaan' ? ((int) $r->getPost('tahap') ?: null) : null;
        if ($uraian === '' || $jumlah <= 0 || ! $r->getPost('tanggal')) {
            return $this->balasForm(false, 'Tanggal, uraian, dan jumlah wajib diisi.');
        }
        if ($g = $this->galatBulanTertutup((string) $r->getPost('tanggal'))) {
            return $this->balasForm(false, $g);
        }
        $this->db()->table('kas_transaksi')->insert([
            'sekolah_id' => $this->sid(),
            'tanggal' => (string) $r->getPost('tanggal'),
            'uraian' => $uraian,
            'kategori' => $kategori,
            'tahap' => $tahap,
            'kanal' => $kanal,
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'keterangan' => trim((string) $r->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAudit('kas', (int) $this->db()->insertID(), 'buat', 'Transaksi kas manual: ' . $uraian . ' ' . $this->rp($jumlah));
        return $this->balasForm(true, 'Transaksi kas dicatat dan masuk ke buku kas.');
    }

    public function hapus($id)
    {
        $row = $this->db()->table('kas_transaksi')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($row && ($g = $this->galatBulanTertutup($row['tanggal']))) {
            return redirect()->to('/buku-kas')->with('galat', $g);
        }
        $this->hapusLunak('kas_transaksi', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        $this->catatAudit('kas', (int) $id, 'hapus', 'Transaksi kas manual dihapus.');
        return redirect()->to('/buku-kas')->with('sukses', 'Transaksi kas dihapus dari buku.');
    }

    public function hapusMassal()
    {
        $n = 0;
        $terkunci = 0;
        foreach ($this->idsTerpilih() as $id) {
            $row = $this->db()->table('kas_transaksi')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($row) {
                if ($this->galatBulanTertutup($row['tanggal'])) {
                    $terkunci++;
                    continue;
                }
                $this->hapusLunak('kas_transaksi', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        $pesan = $n > 0 ? $n . ' transaksi kas terpilih dihapus dari buku.' : 'Tidak ada transaksi yang diproses.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' transaksi dilewati karena bukunya sudah ditutup.';
        }
        return redirect()->to('/buku-kas')->with($n > 0 ? 'sukses' : 'galat', $pesan);
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('kas', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $rows = $this->db()->table('kas_transaksi')->select('id, tanggal')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray();
        $n = 0;
        $terkunci = 0;
        foreach ($rows as $row) {
            if ($this->galatBulanTertutup($row['tanggal'])) {
                $terkunci++;
                continue;
            }
            $this->hapusLunak('kas_transaksi', ['id' => (int) $row['id'], 'sekolah_id' => $this->sid()]);
            $n++;
        }
        $pesan = 'Transaksi kas manual dikosongkan: ' . $n . ' transaksi dihapus. Baris buku yang berasal dari jurnal pembayaran, upah, dan bukti setor tidak ikut terhapus karena sumbernya di modul masing-masing.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' transaksi bulan tertutup tidak ikut terhapus.';
        }
        return redirect()->to('/buku-kas')->with('sukses', $pesan);
    }

    /**
     * Tutup Buku bulan terbuka (berurutan Januari–Desember): saldo akhir
     * dibekukan di tabel tutup_buku dan menjadi saldo awal bulan
     * berikutnya; pencatatan berikutnya langsung masuk bulan n+1.
     */
    public function tutupBuku()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $bt = $this->bulanTerbuka();
        if ($bt === null) {
            return redirect()->to('/buku-kas')->with('galat', 'Semua bulan tahun anggaran ini sudah ditutup.');
        }
        [$saldoAwal, $baris, $saldoAkhir] = $this->susunBulan('bku', $bt['ym']);
        // Saldo awal mengikuti saldo akhir beku bulan sebelumnya bila ada.
        $tertutup = $this->petaTutupBuku();
        if (isset($tertutup[$bt['bulan'] - 1])) {
            $saldoAwal = (float) $tertutup[$bt['bulan'] - 1]['saldo_akhir'];
            $saldoAkhir = $saldoAwal
                + array_sum(array_column($baris, 'masuk'))
                - array_sum(array_column($baris, 'keluar'));
        }
        $now = date('Y-m-d H:i:s');
        $this->db()->table('tutup_buku')->insert([
            'sekolah_id' => $this->sid(),
            'tahun' => $bt['tahun'], 'bulan' => $bt['bulan'],
            'saldo_awal' => $saldoAwal,
            'total_masuk' => array_sum(array_column($baris, 'masuk')),
            'total_keluar' => array_sum(array_column($baris, 'keluar')),
            'saldo_akhir' => $saldoAkhir,
            'jumlah_baris' => count($baris),
            'ditutup_oleh' => $this->pengguna['username'] ?? null,
            'ditutup_pada' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $idTutup = (int) $this->db()->insertID();
        $this->selaraskanBulanBekerja();
        $this->catatAudit('tutup_buku', $idTutup, 'tutup', 'Buku ' . $bt['label'] . ' ditutup; saldo akhir ' . $this->rp($saldoAkhir) . '.');
        $berikut = $this->bulanTerbuka();
        return redirect()->to('/buku-kas')->with('sukses', 'Buku ' . $bt['label'] . ' ditutup dengan saldo akhir ' . $this->rp($saldoAkhir) . '.'
            . ($berikut ? ' Pencatatan berikutnya masuk ' . $berikut['label'] . '.' : ' Seluruh bulan tahun ini sudah tertutup.'));
    }

    /**
     * Buka kembali buku yang sudah ditutup (admin saja, wajib alasan):
     * bulan itu dan bulan-bulan sesudahnya dalam tahun yang sama kembali
     * terbuka dan harus ditutup ulang berurutan.
     */
    public function bukaKembali($id)
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $alasan = trim((string) $this->request->getPost('alasan'));
        if ($alasan === '') {
            return redirect()->to('/buku-kas')->with('galat', 'Alasan membuka kembali buku wajib diisi.');
        }
        $row = $this->db()->table('tutup_buku')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/buku-kas')->with('galat', 'Catatan tutup buku tidak ditemukan.');
        }
        $now = date('Y-m-d H:i:s');
        $this->db()->table('tutup_buku')
            ->where(['sekolah_id' => $this->sid(), 'tahun' => (int) $row['tahun'], 'is_deleted' => 0])
            ->where('bulan >=', (int) $row['bulan'])
            ->update([
                'dibuka_kembali_oleh' => $this->pengguna['username'] ?? null,
                'dibuka_kembali_pada' => $now,
                'alasan_buka_kembali' => $alasan,
                'is_deleted' => 1, 'deleted_at' => $now, 'updated_at' => $now,
            ]);
        $this->selaraskanBulanBekerja();
        $label = $this->namaBulan((int) $row['bulan']) . ' ' . (int) $row['tahun'];
        $this->catatAudit('tutup_buku', (int) $id, 'buka-kembali', 'Buku ' . $label . ' dibuka kembali. Alasan: ' . $alasan);
        return redirect()->to('/buku-kas')->with('sukses', 'Buku ' . $label . ' dibuka kembali; tutup ulang berurutan sesudah koreksi selesai.');
    }

    public function cetak()
    {
        $buku = (string) ($this->request->getGet('buku') ?? 'bku');
        if (! in_array($buku, ['bku', 'bank', 'tunai'], true)) {
            $buku = 'bku';
        }
        $bulan = (string) ($this->request->getGet('bulan') ?? date('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }
        [$saldoAwal, $baris, $saldoAkhir] = $this->susunBulan($buku, $bulan);
        return view('cetak/buku_kas', [
            'idn' => $this->sekolahAktif(),
            'nama_buku' => $this->namaBuku($buku), 'bulan' => $bulan,
            'saldo_awal' => $saldoAwal, 'baris' => $baris, 'saldo_akhir' => $saldoAkhir,
        ]);
    }
}
