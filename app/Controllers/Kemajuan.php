<?php

namespace App\Controllers;

/**
 * Laporan Kemajuan Penyelesaian Pekerjaan (syarat pencairan tahap 2,
 * kemajuan minimal 50%) dan Laporan Akhir Pekerjaan 100% mengikuti
 * format dokumen Juknis Revitalisasi SMP 2026. Angka diambil langsung
 * dari data aplikasi: kemajuan fisik per gedung dari realisasi BOQ,
 * keuangan dari penerimaan dana dan realisasi pembayaran.
 */
class Kemajuan extends BaseController
{
    private function ringkasan(): array
    {
        $gedung = $this->semuaGedung();
        $totalBoq = 0.0;
        $totalRealisasi = 0.0;
        foreach ($gedung as $g) {
            $totalBoq += (float) $g['nilai_boq'];
            $totalRealisasi += (float) $g['nilai_realisasi'];
        }
        $fisik = $totalBoq > 0 ? min(100, $totalRealisasi / $totalBoq * 100) : 0.0;

        $per = $this->penerimaanPerTahap();
        $diterima = $per[1] + $per[2] + $per['lain'];
        $digunakan = $this->totalBelanja();
        $setorSisa = 0.0;
        foreach ($this->db()->table('bukti_setor')->where(['sekolah_id' => $this->sid(), 'jenis' => 'sisa_dana', 'is_deleted' => 0])->get()->getResultArray() as $b) {
            $setorSisa += (float) $b['jumlah'];
        }
        return [
            'gedung' => $gedung, 'total_boq' => $totalBoq, 'total_realisasi' => $totalRealisasi,
            'fisik' => $fisik, 'diterima' => $diterima, 'digunakan' => $digunakan,
            'sisa' => $diterima - $digunakan, 'setor_sisa' => $setorSisa,
            'pagu' => (float) ($this->sekolahAktif()['total_anggaran'] ?? 0),
        ];
    }

    public function index()
    {
        return $this->tampil('kemajuan/index', $this->ringkasan(), 'Laporan Kemajuan & Laporan Akhir — Aplikasi PBJ Sekolah');
    }

    /** Laporan Kemajuan Penyelesaian Pekerjaan (format dokumen Juknis). */
    public function cetakKemajuan()
    {
        return view('cetak/laporan_kemajuan', ['idn' => $this->sekolahAktif()] + $this->ringkasan());
    }

    /** Laporan Akhir Pekerjaan 100% / berita acara penutup (format Juknis). */
    public function cetakAkhir()
    {
        return view('cetak/laporan_akhir', ['idn' => $this->sekolahAktif()] + $this->ringkasan());
    }
}
