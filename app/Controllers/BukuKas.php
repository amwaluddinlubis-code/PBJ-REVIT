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
        return $this->tampil('bukukas/index', [
            'buku' => $buku, 'nama_buku' => $this->namaBuku($buku), 'bulan' => $bulan,
            'saldo_awal' => $saldoAwal, 'baris' => $baris, 'saldo_akhir' => $saldoAkhir,
            'manual' => $manual,
        ], $this->namaBuku($buku) . ' — Aplikasi PBJ Sekolah');
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
            return redirect()->to('/buku-kas')->with('galat', 'Tanggal, uraian, dan jumlah wajib diisi.');
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
        return redirect()->to('/buku-kas')->with('sukses', 'Transaksi kas dicatat dan masuk ke buku kas.');
    }

    public function hapus($id)
    {
        $this->hapusLunak('kas_transaksi', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        $this->catatAudit('kas', (int) $id, 'hapus', 'Transaksi kas manual dihapus.');
        return redirect()->to('/buku-kas')->with('sukses', 'Transaksi kas dihapus dari buku.');
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            $ada = $this->db()->table('kas_transaksi')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('kas_transaksi', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        return redirect()->to('/buku-kas')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' transaksi kas terpilih dihapus dari buku.' : 'Tidak ada transaksi yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('kas', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('kas_transaksi')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        foreach ($ids as $id) {
            $this->hapusLunak('kas_transaksi', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        }
        return redirect()->to('/buku-kas')->with('sukses', 'Transaksi kas manual dikosongkan: ' . count($ids) . ' transaksi dihapus. Baris buku yang berasal dari jurnal pembayaran, upah, dan bukti setor tidak ikut terhapus karena sumbernya di modul masing-masing.');
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
