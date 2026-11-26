<?php

namespace App\Controllers;

/**
 * Penerimaan Dana Bantuan: pencairan Bantuan Pemerintah Program
 * Revitalisasi dilakukan bertahap — Tahap 1 sebesar 70% dan Tahap 2
 * sebesar 30% setelah kemajuan pekerjaan mencapai minimal 50%
 * (Juknis Revitalisasi SMP 2026). Penerimaan dicatat sebagai transaksi
 * kas masuk sehingga otomatis tampil di Buku Kas; setiap penerimaan
 * dapat dicetak kuitansinya mengikuti format dokumen Juknis.
 */
class Penerimaan extends BaseController
{
    public function index()
    {
        $idn = $this->sekolahAktif();
        $pagu = (float) ($idn['total_anggaran'] ?? 0);
        $per = $this->penerimaanPerTahap();
        $daftar = $this->db()->table('kas_transaksi')
            ->where(['sekolah_id' => $this->sid(), 'kategori' => 'penerimaan', 'jenis' => 'masuk', 'is_deleted' => 0])
            ->orderBy('tanggal')->orderBy('id')->get()->getResultArray();

        $totalBoq = 0.0;
        $tertimbang = 0.0;
        foreach ($this->semuaGedung() as $g) {
            $totalBoq += (float) $g['nilai_boq'];
            $tertimbang += (float) $g['nilai_boq'] * (float) $g['persen_fisik'];
        }
        $fisik = $totalBoq > 0 ? $tertimbang / $totalBoq : 0.0;

        return $this->tampil('penerimaan/index', [
            'pagu' => $pagu,
            'target1' => round($pagu * 0.7), 'target2' => round($pagu * 0.3),
            'terima1' => $per[1], 'terima2' => $per[2], 'terimaLain' => $per['lain'],
            'daftar' => $daftar, 'fisik' => $fisik,
        ], 'Penerimaan Dana Bantuan — Aplikasi PBJ Sekolah');
    }

    /** Balasan form modal (pola Bahan::balasForm): AJAX -> JSON {ok, pesan}; biasa -> redirect flash. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('/penerimaan-dana')->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function simpan()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $r = $this->request;
        $jumlah = (float) preg_replace('/[^0-9]/', '', (string) $r->getPost('jumlah'));
        $tahap = (int) $r->getPost('tahap');
        if ($jumlah <= 0 || ! $r->getPost('tanggal') || ! in_array($tahap, [1, 2], true)) {
            return $this->balasForm(false, 'Tanggal, tahap (1/2), dan jumlah penerimaan wajib diisi.');
        }
        $kanal = (string) $r->getPost('kanal') === 'tunai' ? 'tunai' : 'bank';
        $this->db()->table('kas_transaksi')->insert([
            'sekolah_id' => $this->sid(),
            'tanggal' => (string) $r->getPost('tanggal'),
            'uraian' => 'Penerimaan dana bantuan Revitalisasi tahap ' . $tahap . ($tahap === 1 ? ' (70%)' : ' (30%)'),
            'kategori' => 'penerimaan',
            'tahap' => $tahap,
            'kanal' => $kanal,
            'jenis' => 'masuk',
            'jumlah' => $jumlah,
            'keterangan' => trim((string) $r->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAudit('penerimaan', (int) $this->db()->insertID(), 'buat', 'Penerimaan tahap ' . $tahap . ' sebesar ' . $this->rp($jumlah));
        return $this->balasForm(true, 'Penerimaan tahap ' . $tahap . ' dicatat dan masuk Buku Kas.');
    }

    /** Syarat baris penerimaan pada tabel kas bersama. */
    private function syaratPenerimaan(): array
    {
        return ['sekolah_id' => $this->sid(), 'kategori' => 'penerimaan', 'jenis' => 'masuk', 'is_deleted' => 0];
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            $ada = $this->db()->table('kas_transaksi')->where(['id' => $id] + $this->syaratPenerimaan())->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('kas_transaksi', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        return redirect()->to('/penerimaan-dana')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' catatan penerimaan terpilih dihapus (ikut hilang dari Buku Kas).' : 'Tidak ada catatan yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('penerimaan', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('kas_transaksi')->select('id')
            ->where($this->syaratPenerimaan())->get()->getResultArray(), 'id');
        foreach ($ids as $id) {
            $this->hapusLunak('kas_transaksi', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        }
        return redirect()->to('/penerimaan-dana')->with('sukses', 'Catatan penerimaan dikosongkan: ' . count($ids) . ' catatan dihapus (ikut hilang dari Buku Kas).');
    }

    /** Cetak kuitansi bukti penerimaan uang (format dokumen Juknis). */
    public function cetakKuitansi($id)
    {
        $k = $this->db()->table('kas_transaksi')
            ->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'kategori' => 'penerimaan', 'is_deleted' => 0])
            ->get()->getRowArray();
        if (! $k) {
            return redirect()->to('/penerimaan-dana')->with('galat', 'Catatan penerimaan tidak ditemukan.');
        }
        return view('cetak/kuitansi_penerimaan', [
            'idn' => $this->sekolahAktif(), 'k' => $k,
            'terbilang' => $this->terbilang($k['jumlah']),
        ]);
    }
}
