<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Laporan extends BaseController
{
    /** Kumpulkan angka laporan untuk sekolah aktif. */
    private function data(): array
    {
        $db = $this->db();
        $sid = $this->sid();

        $totalRab = (float) $db->query('SELECT COALESCE(SUM((qty_rkb+qty_adm+qty_toilet)*harga_rab),0) t FROM bahan WHERE sekolah_id = ? AND is_deleted = 0', [$sid])->getRow()->t;
        $totalSpk = (float) $db->query('SELECT COALESCE(SUM(qty*harga),0) t FROM spk_detail WHERE spk_id IS NOT NULL AND sekolah_id = ? AND is_deleted = 0', [$sid])->getRow()->t;

        $realisasi = 0.0;
        $totalInvoice = 0.0;
        $petaPpn = $this->ppnSpkMap();
        foreach ($db->query(
            'SELECT i.nilai, i.spk_id, (SELECT COUNT(*) FROM pembayaran p WHERE p.invoice_id = i.id AND p.is_deleted = 0) AS lunas
             FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0 WHERE s.sekolah_id = ? AND i.is_deleted = 0',
            [$sid]
        )->getResultArray() as $r) {
            $totalInvoice += (float) $r['nilai'];
            if ((int) $r['lunas'] > 0) {
                $pjk = $this->pajakPembelian((float) $r['nilai'], $this->ppnInvoice($petaPpn, (int) $r['spk_id'], (float) $r['nilai']));
                $realisasi += $pjk['dibayar'];
            }
        }

        $gedung = [];
        foreach (['qty_rkb' => 'RKB 3 Kopel', 'qty_adm' => 'Ruang Administrasi', 'qty_toilet' => 'Toilet'] as $kolom => $label) {
            $nilai = (float) $db->query("SELECT COALESCE(SUM({$kolom}*harga_rab),0) t FROM bahan WHERE sekolah_id = ? AND is_deleted = 0", [$sid])->getRow()->t;
            $gedung[] = ['label' => $label, 'anggaran' => $nilai, 'persen' => $totalRab > 0 ? $nilai / $totalRab * 100 : 0];
        }

        $perSpk = $db->query(
            'SELECT s.nomor, s.tanggal, s.paket, s.status, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia,
                    (SELECT COALESCE(SUM(d.qty*d.harga),0) FROM spk_detail d WHERE d.spk_id = s.id AND d.is_deleted = 0) AS nilai_spk,
                    (SELECT COALESCE(SUM(i.nilai),0) FROM invoice i WHERE i.spk_id = s.id AND i.is_deleted = 0) AS nilai_invoice,
                    (SELECT COUNT(*) FROM invoice i JOIN pembayaran pb ON pb.invoice_id = i.id AND pb.is_deleted = 0 WHERE i.spk_id = s.id AND i.is_deleted = 0) AS jumlah_bayar
             FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id
             WHERE s.sekolah_id = ? AND s.is_deleted = 0 GROUP BY s.id ORDER BY s.nomor',
            [$sid]
        )->getResultArray();

        return [
            'totalRab' => $totalRab, 'totalSpk' => $totalSpk,
            'totalInvoice' => $totalInvoice, 'realisasi' => $realisasi,
            'sisa' => $totalRab - $realisasi,
            'gedung' => $gedung, 'perSpk' => $perSpk,
        ];
    }

    public function index()
    {
        return $this->tampil('laporan/index', $this->data(), 'Laporan & Ekspor — Aplikasi PBJ Sekolah');
    }

    public function cetak()
    {
        return view('cetak/laporan', $this->data() + [
            'idn' => $this->sekolahAktif(),
        ]);
    }

    // ---------------- EKSPOR EXCEL ----------------

    private function unduhXlsx(Spreadsheet $ss, string $namaBerkas)
    {
        $path = WRITEPATH . 'cache/' . $namaBerkas;
        (new Xlsx($ss))->save($path);
        return $this->response->download($path, null);
    }

    private function gayaJudul($sheet, array $judul, array $lebar)
    {
        $sheet->fromArray($judul, null, 'A1');
        $kolomAkhir = chr(64 + count($judul));
        $sheet->getStyle("A1:{$kolomAkhir}1")->getFont()->setBold(true);
        foreach ($lebar as $i => $w) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
        }
    }

    public function eksporSpk()
    {
        $sid = $this->sid();
        $baris = $this->db()->query(
            'SELECT s.nomor, s.tanggal, s.paket, s.status, COALESCE(NULLIF(TRIM(s.penerima_manual), ""), p.nama) AS penyedia,
                    (SELECT COUNT(*) FROM spk_detail d WHERE d.spk_id = s.id AND d.is_deleted = 0) AS jumlah,
                    (SELECT COALESCE(SUM(d.qty*d.harga),0) FROM spk_detail d WHERE d.spk_id = s.id AND d.is_deleted = 0) AS nilai
             FROM spk s LEFT JOIN penyedia p ON p.id = s.penyedia_id WHERE s.sekolah_id = ? AND s.is_deleted = 0 ORDER BY s.nomor',
            [$sid]
        )->getResultArray();
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Rekap SPK');
        $this->gayaJudul($sh, ['Nomor SPK', 'Tanggal', 'Paket', 'Penyedia', 'Status', 'Jumlah Baris', 'Nilai (Rp)'], [12, 14, 42, 26, 12, 14, 18]);
        $r = 2;
        foreach ($baris as $b) {
            $sh->fromArray([(int) $b['nomor'], $b['tanggal'], $b['paket'], $b['penyedia'], $b['status'], (int) $b['jumlah'], (float) $b['nilai']], null, 'A' . $r++);
        }
        $sh->setCellValue('F' . $r, 'TOTAL');
        $sh->setCellValue('G' . $r, array_sum(array_column($baris, 'nilai')));
        $sh->getStyle('F' . $r . ':G' . $r)->getFont()->setBold(true);
        return $this->unduhXlsx($ss, 'rekap-spk.xlsx');
    }

    public function eksporMaterial()
    {
        $sid = $this->sid();
        $baris = $this->db()->query(
            'SELECT m.tanggal, m.no_bpb, b.kode, b.nama, b.satuan, m.masuk, m.keluar, m.keterangan
             FROM buku_material m JOIN bahan b ON b.id = m.bahan_id
             WHERE b.sekolah_id = ? AND m.is_deleted = 0 AND b.is_deleted = 0 ORDER BY m.tanggal IS NULL, m.tanggal, m.id',
            [$sid]
        )->getResultArray();
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Buku Material');
        $this->gayaJudul($sh, ['Tanggal', 'No. BPB', 'Kode', 'Nama Bahan', 'Satuan', 'Masuk', 'Keluar', 'Sisa Stok', 'Keterangan'], [14, 14, 10, 34, 10, 12, 12, 12, 30]);
        $saldo = [];
        $r = 2;
        foreach ($baris as $b) {
            $kunci = $b['kode'];
            $saldo[$kunci] = ($saldo[$kunci] ?? 0) + (float) $b['masuk'] - (float) $b['keluar'];
            $sh->fromArray([$b['tanggal'], $b['no_bpb'], $b['kode'], $b['nama'], $b['satuan'], (float) $b['masuk'], (float) $b['keluar'], $saldo[$kunci], $b['keterangan']], null, 'A' . $r++);
        }
        return $this->unduhXlsx($ss, 'buku-material.xlsx');
    }

    public function eksporSisa()
    {
        $sid = $this->sid();
        $baris = $this->db()->query(
            'SELECT b.kode, b.nama, b.satuan, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_rab,
                    (SELECT COALESCE(SUM(d.qty),0) FROM spk_detail d WHERE d.bahan_id = b.id AND d.is_deleted = 0) AS masuk_spk,
                    (SELECT COALESCE(SUM(m.masuk),0) FROM buku_material m WHERE m.bahan_id = b.id AND m.is_deleted = 0) AS gudang_masuk,
                    (SELECT COALESCE(SUM(m.keluar),0) FROM buku_material m WHERE m.bahan_id = b.id AND m.is_deleted = 0) AS gudang_keluar
             FROM bahan b WHERE b.sekolah_id = ? AND b.is_deleted = 0 ORDER BY b.kode',
            [$sid]
        )->getResultArray();
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Sisa Bahan');
        $this->gayaJudul($sh, ['Kode', 'Nama Bahan', 'Satuan', 'Qty RAB', 'Masuk SPK', 'Sisa vs RAB', 'Status', 'Stok Gudang'], [10, 34, 10, 12, 12, 12, 16, 12]);
        $r = 2;
        foreach ($baris as $b) {
            $sisa = (float) $b['qty_rab'] - (float) $b['masuk_spk'];
            $status = $sisa < 0 ? 'Melebihi RAB' : ($sisa == 0 ? 'Sudah penuh' : ($b['masuk_spk'] > 0 ? 'Sebagian' : 'Belum masuk SPK'));
            $sh->fromArray([$b['kode'], $b['nama'], $b['satuan'], (float) $b['qty_rab'], (float) $b['masuk_spk'], $sisa, $status, (float) $b['gudang_masuk'] - (float) $b['gudang_keluar']], null, 'A' . $r++);
        }
        return $this->unduhXlsx($ss, 'sisa-bahan.xlsx');
    }
}
