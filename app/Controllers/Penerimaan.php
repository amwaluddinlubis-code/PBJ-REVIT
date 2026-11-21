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

    public function simpan()
    {
        $r = $this->request;
        $jumlah = (float) preg_replace('/[^0-9]/', '', (string) $r->getPost('jumlah'));
        $tahap = (int) $r->getPost('tahap');
        if ($jumlah <= 0 || ! $r->getPost('tanggal') || ! in_array($tahap, [1, 2], true)) {
            return redirect()->to('/penerimaan-dana')->with('galat', 'Tanggal, tahap (1/2), dan jumlah penerimaan wajib diisi.');
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
        return redirect()->to('/penerimaan-dana')->with('sukses', 'Penerimaan tahap ' . $tahap . ' dicatat dan masuk Buku Kas.');
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
