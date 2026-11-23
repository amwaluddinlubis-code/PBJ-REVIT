<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Mesin Laporan & Template Dokumen — port konsep mesin template SPJ BOSP
 * (repo raw-rkas: SpjTemplateService, SpjRepeatingRowRenderer,
 * DocumentTemplatePlaceholderInspectorService, SpjUnresolvedPlaceholderGuard)
 * ke aplikasi PBJ:
 *
 * - Template berupa berkas .xlsx yang sel-selnya berisi penanda
 *   {{NAMA_PLACEHOLDER}} (huruf besar, sama seperti SPJ BOSP).
 * - Katalog placeholder terdaftar per jenis dokumen, berkelompok, dan
 *   dibangun dari konteks entitas nyata (satu SPK / satu gedung / sekolah).
 * - Baris berulang: satu baris contoh pada template yang selnya berisi
 *   penanda berawalan tertentu (RINCIAN_, TENAGA_, KERJA_, REKAP_) digandakan
 *   mengikuti jumlah baris data; baris contoh dihapus bila datanya kosong.
 * - Penggantian hanya pada sel bertipe string (sel rumus/angka tidak disentuh).
 * - Penjaga: sesudah render, penanda yang masih tersisa = render GAGAL dan
 *   daftarnya dilaporkan — dokumen setengah jadi tidak pernah terunduh.
 * - Nilai uang ditulis sebagai string angka bulat polos (agar bisa dijumlah
 *   Excel) dan tersedia juga varian _RP yang sudah terformat; qty 2 desimal.
 */
class MesinLaporan
{
    /** Registry jenis dokumen: label, konteks, awalan baris berulang. */
    public static function jenis(): array
    {
        return [
            'spk'     => ['label' => 'Dokumen SPK', 'konteks' => 'Satu SPK', 'berulang' => ['RINCIAN' => 'Baris rincian SPK', 'TENAGA' => 'Baris kebutuhan tenaga kerja', 'RPT_BAP' => 'Baris daftar BAP SPK ini', 'RPT_BAST' => 'Baris daftar BAST SPK ini', 'RPT_INV' => 'Baris daftar Invoice SPK ini']],
            'gedung'  => ['label' => 'Laporan Gedung', 'konteks' => 'Satu gedung', 'berulang' => ['KERJA' => 'Baris pekerjaan BOQ']],
            'sekolah' => ['label' => 'Laporan Sekolah', 'konteks' => 'Rekap sekolah aktif', 'berulang' => ['REKAP' => 'Baris rekap per gedung']],
        ];
    }

    /** Grup placeholder "Sekolah & Pejabat" — sama untuk semua jenis. */
    private static function grupSekolah(): array
    {
        return ['Sekolah & Pejabat' => [
            'NAMA_SEKOLAH' => 'Nama sekolah', 'NPSN' => 'NPSN', 'ALAMAT_SEKOLAH' => 'Alamat sekolah',
            'DESA' => 'Desa/kelurahan', 'KECAMATAN' => 'Kecamatan', 'KABUPATEN' => 'Kabupaten',
            'PROVINSI' => 'Provinsi', 'EMAIL_SEKOLAH' => 'Email sekolah',
            'PAKET_PEKERJAAN' => 'Nama paket pekerjaan', 'TAHUN_ANGGARAN' => 'Tahun anggaran',
            'NAMA_KEPSEK' => 'Nama kepala sekolah', 'NIP_KEPSEK' => 'NIP kepala sekolah',
            'NAMA_KETUA' => 'Nama Ketua Panitia P2SP', 'NAMA_SEKRETARIS' => 'Nama sekretaris panitia',
            'NAMA_PELAKSANA' => 'Nama Kepala Pelaksana',
        ]];
    }

    /** Katalog placeholder skalar per jenis: grup => [KUNCI => label]. */
    public static function katalog(string $jenis): array
    {
        $dasar = self::grupSekolah();
        switch ($jenis) {
            case 'spk':
                return $dasar + [
                    'Dokumen SPK' => [
                        'NOMOR_SPK' => 'Nomor SPK (angka)', 'NOMOR_SURAT_SPK' => 'Nomor surat SPK lengkap',
                        'TANGGAL_SPK' => 'Tanggal SPK (dd/mm/yyyy)', 'TANGGAL_SPK_PANJANG' => 'Tanggal SPK panjang (hari, tanggal bulan tahun)',
                        'PAKET_SPK' => 'Nama paket SPK', 'TGL_MULAI' => 'Tanggal mulai', 'TGL_SELESAI' => 'Tanggal selesai',
                        'STATUS_SPK' => 'Status SPK', 'GEDUNG_SPK' => 'Gedung tujuan SPK (kosong bila gabungan)',
                    ],
                    'Penyedia' => [
                        'PENYEDIA_NAMA' => 'Nama penyedia', 'PENYEDIA_ALAMAT' => 'Alamat penyedia',
                        'PENYEDIA_TTD' => 'Nama penanda tangan penyedia', 'PENERIMA_MANUAL' => 'Nama penerima manual (penyedia Masyarakat)',
                    ],
                    'Nilai & Pajak' => [
                        'TOTAL_SPK' => 'Total nilai SPK (angka polos)', 'TOTAL_SPK_RP' => 'Total nilai SPK (Rp terformat)',
                        'TOTAL_TERBILANG' => 'Total terbilang (tanpa kata Rupiah)',
                        'DPP' => 'DPP (angka polos)', 'PPN' => 'PPN dipungut (angka polos)', 'PPH22' => 'PPh 22 (angka polos)',
                        'DIBAYAR' => 'Bersih dibayar ke penyedia (angka polos)', 'DIBAYAR_RP' => 'Bersih dibayar (Rp terformat)',
                    ],
                    'Dokumen Lanjutan (yang pertama)' => [
                        'BAP_NOMOR_SURAT' => 'Nomor surat BAP pertama', 'BAP_TANGGAL' => 'Tanggal BAP pertama',
                        'BAST_NOMOR_SURAT' => 'Nomor surat BAST pertama', 'BAST_TANGGAL' => 'Tanggal BAST pertama',
                        'INVOICE_NOMOR_SURAT' => 'Nomor surat invoice pertama', 'INVOICE_NILAI_RP' => 'Nilai invoice pertama (Rp terformat)',
                    ],
                ];
            case 'gedung':
                return $dasar + [
                    'Gedung' => [
                        'NAMA_GEDUNG' => 'Nama gedung', 'NILAI_BOQ' => 'Nilai BOQ asli dokumen (angka polos)',
                        'NILAI_BOQ_RP' => 'Nilai BOQ asli (Rp terformat)', 'NILAI_ANALISA' => 'Nilai analisa AHSP berjalan (angka polos)',
                        'NILAI_ANALISA_RP' => 'Nilai analisa AHSP (Rp terformat)', 'NILAI_REALISASI_RP' => 'Nilai realisasi BOQ gedung ini (Rp terformat)',
                        'PERSEN_FISIK' => 'Kemajuan fisik gedung (%)', 'JUMLAH_PEKERJAAN' => 'Jumlah baris pekerjaan BOQ',
                    ],
                ];
            case 'sekolah':
                return $dasar + [
                    'Rekap Sekolah' => [
                        'TOTAL_ANGGARAN_RP' => 'Total anggaran/pagu sekolah (Rp terformat)',
                        'TOTAL_BOQ_RP' => 'Total nilai analisa semua gedung didanai (Rp terformat)',
                        'TOTAL_SPK_RP' => 'Total nilai SPK terdaftar (Rp terformat)',
                        'TOTAL_TERBAYAR_RP' => 'Total realisasi dibayar bersih (Rp terformat)',
                        'SISA_PAGU_RP' => 'Sisa pagu (Rp terformat)',
                        'JUMLAH_GEDUNG' => 'Jumlah gedung didanai', 'JUMLAH_SPK' => 'Jumlah SPK aktif',
                    ],
                ];
        }
        return $dasar;
    }

    /** Katalog placeholder baris berulang per jenis: awalan => [KUNCI => label]. */
    public static function katalogBerulang(string $jenis): array
    {
        switch ($jenis) {
            case 'spk':
                return [
                    'RINCIAN' => [
                        'RINCIAN_NO' => 'Nomor urut baris', 'RINCIAN_KODE' => 'Kode bahan', 'RINCIAN_NAMA' => 'Nama bahan',
                        'RINCIAN_GEDUNG' => 'Gedung tujuan rincian', 'RINCIAN_QTY' => 'Qty (2 desimal)',
                        'RINCIAN_SATUAN' => 'Satuan', 'RINCIAN_HARGA' => 'Harga satuan (angka polos)', 'RINCIAN_TOTAL' => 'Total baris (angka polos)',
                    ],
                    'TENAGA' => [
                        'TENAGA_NO' => 'Nomor urut baris', 'TENAGA_JENIS' => 'Jenis tenaga (Pekerja/Tukang)',
                        'TENAGA_KEAHLIAN' => 'Keahlian', 'TENAGA_OH' => 'Kebutuhan OH (2 desimal)', 'TENAGA_ORANG' => 'Perkiraan jumlah orang',
                    ],
                    'RPT_BAP' => [
                        'RPT_BAP_NO' => 'Nomor urut baris', 'RPT_BAP_NOMOR_SURAT' => 'Nomor surat BAP',
                        'RPT_BAP_TANGGAL' => 'Tanggal BAP', 'RPT_BAP_JUMLAH_RINCIAN' => 'Jumlah rincian yang diperiksa pada BAP ini',
                    ],
                    'RPT_BAST' => [
                        'RPT_BAST_NO' => 'Nomor urut baris', 'RPT_BAST_NOMOR_SURAT' => 'Nomor surat BAST',
                        'RPT_BAST_TANGGAL' => 'Tanggal BAST', 'RPT_BAST_BAP_NOMOR' => 'Nomor surat BAP yang diserahterimakan',
                    ],
                    'RPT_INV' => [
                        'RPT_INV_NO' => 'Nomor urut baris', 'RPT_INV_NOMOR_SURAT' => 'Nomor surat invoice',
                        'RPT_INV_TANGGAL' => 'Tanggal invoice', 'RPT_INV_NILAI' => 'Nilai invoice (angka polos)',
                        'RPT_INV_NILAI_RP' => 'Nilai invoice (Rp terformat)', 'RPT_INV_STATUS' => 'Status bayar invoice',
                    ],
                ];
            case 'gedung':
                return [
                    'KERJA' => [
                        'KERJA_NO' => 'Nomor urut baris', 'KERJA_KODE' => 'Kode analisa', 'KERJA_NAMA' => 'Nama pekerjaan',
                        'KERJA_VOLUME' => 'Volume berjalan (2 desimal)', 'KERJA_SATUAN' => 'Satuan',
                        'KERJA_HARGA' => 'Harga satuan berjalan: revisi disahkan/AHSP/dokumen (angka polos) — basis yang sama dengan Nilai Analisa, jadi total kolom KERJA_NILAI menyamai NILAI_ANALISA', 'KERJA_NILAI' => 'Nilai baris berjalan (angka polos)',
                        'KERJA_HARGA_RP' => 'Harga satuan berjalan (Rp terformat)', 'KERJA_NILAI_RP' => 'Nilai baris berjalan (Rp terformat)',
                        'KERJA_HARGA_DOKUMEN' => 'Harga satuan dokumen asli (angka polos)', 'KERJA_NILAI_DOKUMEN' => 'Nilai baris pada harga dokumen (angka polos)',
                        'KERJA_REALISASI' => 'Realisasi fisik baris (%)',
                    ],
                ];
            case 'sekolah':
                return [
                    'REKAP' => [
                        'REKAP_NO' => 'Nomor urut baris', 'REKAP_GEDUNG' => 'Nama gedung',
                        'REKAP_BOQ' => 'Nilai BOQ asli (angka polos)', 'REKAP_ANALISA' => 'Nilai analisa AHSP (angka polos)',
                        'REKAP_FISIK' => 'Kemajuan fisik (%)',
                    ],
                ];
        }
        return [];
    }

    /** Format nilai uang ala SPJ: string angka bulat polos. */
    public static function angka($nilai): string
    {
        return (string) (int) round((float) $nilai);
    }

    /** Format qty ala SPJ: 2 desimal, titik sebagai pemisah. */
    public static function qty($nilai): string
    {
        return number_format((float) $nilai, 2, '.', '');
    }

    /** Pindai semua penanda {{...}} yang dipakai sebuah berkas template. */
    public static function pindai(string $path): array
    {
        $ss = IOFactory::load($path);
        $ketemu = [];
        foreach ($ss->getAllSheets() as $sheet) {
            foreach (self::selString($sheet) as $nilai) {
                if (preg_match_all('/\{\{([A-Z0-9_]+)\}\}/', $nilai, $m)) {
                    foreach ($m[1] as $kunci) {
                        $ketemu[$kunci] = true;
                    }
                }
            }
        }
        $ss->disconnectWorksheets();
        $daftar = array_keys($ketemu);
        sort($daftar);
        return $daftar;
    }

    /** @return array<int,string> teks semua sel string/rich text pada satu sheet */
    private static function selString(Worksheet $sheet): array
    {
        $nilai = [];
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $t = self::teksSel($cell);
                if ($t !== null && $t !== '') {
                    $nilai[] = $t;
                }
            }
        }
        return $nilai;
    }

    /** Teks polos satu sel: string biasa, atau rich text (sel berformat sebagian). */
    private static function teksSel($cell): ?string
    {
        $v = $cell->getValue();
        if (is_string($v)) {
            return $v;
        }
        if ($v instanceof RichText) {
            return $v->getPlainText();
        }
        return null;
    }

    /**
     * Render template: ganti placeholder skalar + gandakan baris berulang.
     *
     * @param array<string,string> $skalar  peta KUNCI => nilai
     * @param array<string,array<int,array<string,string>>> $berulang awalan => baris data
     * @return array{0: ?Spreadsheet, 1: array<int,string>} [hasil, penanda tak terisi]
     */
    public static function render(string $path, array $skalar, array $berulang): array
    {
        $ss = IOFactory::load($path);
        foreach ($ss->getAllSheets() as $sheet) {
            self::renderBerulang($sheet, $berulang);
        }
        foreach ($ss->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $v = self::teksSel($cell);
                    if ($v === null || ! str_contains($v, '{{')) {
                        continue;
                    }
                    $baru = $v;
                    foreach ($skalar as $kunci => $nilai) {
                        $baru = str_replace('{{' . $kunci . '}}', (string) $nilai, $baru);
                    }
                    if ($baru !== $v) {
                        $cell->setValueExplicit($baru, DataType::TYPE_STRING);
                    }
                }
            }
        }
        // Penjaga placeholder tak terisi (ala SpjUnresolvedPlaceholderGuard).
        $sisa = [];
        foreach ($ss->getAllSheets() as $sheet) {
            foreach (self::selString($sheet) as $nilai) {
                if (preg_match_all('/\{\{([A-Z0-9_]+)\}\}/', $nilai, $m)) {
                    foreach ($m[1] as $kunci) {
                        $sisa[$kunci] = true;
                    }
                }
            }
        }
        if ($sisa !== []) {
            $daftar = array_keys($sisa);
            sort($daftar);
            $ss->disconnectWorksheets();
            return [null, $daftar];
        }
        return [$ss, []];
    }

    /**
     * Bangun template contoh siap unduh untuk satu jenis: kop sekolah,
     * semua placeholder skalar katalog, dan satu baris contoh per blok
     * berulang. Pengguna tinggal menata ulang isinya di Excel.
     */
    public static function contoh(string $jenis): Spreadsheet
    {
        $meta = self::jenis()[$jenis] ?? self::jenis()['spk'];
        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Laporan');
        $ws->getColumnDimension('A')->setWidth(38);
        $ws->getColumnDimension('B')->setWidth(46);
        $ws->getColumnDimension('C')->setWidth(18);
        $ws->getColumnDimension('D')->setWidth(18);
        $ws->getColumnDimension('E')->setWidth(20);
        $ws->getColumnDimension('F')->setWidth(22);

        $r = 1;
        $ws->setCellValue('A' . $r, '{{NAMA_SEKOLAH}}');
        $ws->getStyle('A' . $r)->getFont()->setBold(true)->setSize(14);
        $r++;
        $ws->setCellValue('A' . $r, '{{ALAMAT_SEKOLAH}} — {{KECAMATAN}}, {{KABUPATEN}}, {{PROVINSI}}');
        $r += 2;
        $ws->setCellValue('A' . $r, 'TEMPLATE CONTOH — ' . strtoupper($meta['label']) . ' (konteks: ' . $meta['konteks'] . ')');
        $ws->getStyle('A' . $r)->getFont()->setBold(true);
        $r++;
        $ws->setCellValue('A' . $r, 'Tata ulang bebas di Excel; yang penting ejaan penanda di dalam kurung kurawal ganda jangan diubah. Baris contoh blok berulang digandakan otomatis saat render.');
        $r += 2;

        foreach (self::katalog($jenis) as $grup => $peta) {
            $ws->setCellValue('A' . $r, strtoupper($grup));
            $ws->getStyle('A' . $r)->getFont()->setBold(true);
            $r++;
            foreach ($peta as $kunci => $label) {
                $ws->setCellValue('A' . $r, $label);
                $ws->setCellValue('B' . $r, '{{' . $kunci . '}}');
                $r++;
            }
            $r++;
        }
        foreach (self::katalogBerulang($jenis) as $awalan => $peta) {
            $ws->setCellValue('A' . $r, strtoupper(self::jenis()[$jenis]['berulang'][$awalan] ?? $awalan) . ' (baris contoh — digandakan otomatis)');
            $ws->getStyle('A' . $r)->getFont()->setBold(true);
            $r++;
            $kolom = 'A';
            foreach ($peta as $kunci => $label) {
                $ws->setCellValue($kolom . $r, $label);
                $ws->getStyle($kolom . $r)->getFont()->setBold(true);
                $kolom++;
            }
            $r++;
            $kolom = 'A';
            foreach ($peta as $kunci => $label) {
                $ws->setCellValue($kolom . $r, '{{' . $kunci . '}}');
                $kolom++;
            }
            $r += 2;
        }
        return $ss;
    }

    /**
     * Gandakan blok contoh tiap awalan mengikuti jumlah baris data.
     * Baris-baris berurutan yang memuat penanda awalan yang sama membentuk
     * SATU blok contoh (boleh multi-baris); satu sheet boleh memuat banyak
     * blok, termasuk awalan yang sama di tempat terpisah. Blok diproses
     * dari bawah ke atas agar nomor baris blok lain tidak bergeser oleh
     * sisipan/penghapusan. Seluruh sel baris contoh (nilai + gaya) tersalin
     * ke baris sisipan — bukan hanya sel berpenanda. Sel rich text ikut
     * terbaca (hasil gantinya teks polos). Batasan: baris yang memuat dua
     * awalan berbeda mengikuti awalan pertama yang ditemukan; sel gabung
     * (merge) yang melintasi blok tidak ikut digandakan.
     */
    private static function renderBerulang(Worksheet $sheet, array $berulang): void
    {
        $barisAwalan = [];
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $t = self::teksSel($cell);
                if ($t === null || ! str_contains($t, '{{')) {
                    continue;
                }
                foreach (array_keys($berulang) as $awalan) {
                    if (str_contains($t, '{{' . $awalan . '_')) {
                        $barisAwalan[$row->getRowIndex()] = $awalan;
                        break;
                    }
                }
                if (isset($barisAwalan[$row->getRowIndex()])) {
                    break;
                }
            }
        }
        if ($barisAwalan === []) {
            return;
        }
        ksort($barisAwalan);
        $nomorBaris = array_keys($barisAwalan);
        $blok = [];
        $i = 0;
        while ($i < count($nomorBaris)) {
            $awalan = $barisAwalan[$nomorBaris[$i]];
            $mulai = $nomorBaris[$i];
            $tinggi = 1;
            while ($i + $tinggi < count($nomorBaris) && $nomorBaris[$i + $tinggi] === $mulai + $tinggi && $barisAwalan[$nomorBaris[$i + $tinggi]] === $awalan) {
                $tinggi++;
            }
            $blok[] = [$awalan, $mulai, $tinggi];
            $i += $tinggi;
        }
        usort($blok, static fn ($a, $b) => $b[1] <=> $a[1]);
        $kolomAkhir = $sheet->getHighestColumn();
        foreach ($blok as [$awalan, $mulai, $tinggi]) {
            $barisData = $berulang[$awalan] ?? [];
            $jumlah = count($barisData);
            if ($jumlah === 0) {
                $sheet->removeRow($mulai, $tinggi);
                continue;
            }
            if ($jumlah > 1) {
                $sheet->insertNewRowBefore($mulai + $tinggi, ($jumlah - 1) * $tinggi);
                for ($k = 1; $k < $jumlah; $k++) {
                    for ($r = 0; $r < $tinggi; $r++) {
                        $src = $mulai + $r;
                        $dst = $mulai + $k * $tinggi + $r;
                        $h = $sheet->getRowDimension($src)->getRowHeight();
                        if ($h !== null) {
                            $sheet->getRowDimension($dst)->setRowHeight($h);
                        }
                        foreach ($sheet->getRowIterator($src, $src) as $rowSrc) {
                            foreach ($rowSrc->getCellIterator('A', $kolomAkhir) as $cell) {
                                $v = $cell->getValue();
                                if ($v !== null) {
                                    $sheet->getCell($cell->getColumn() . $dst)->setValue($v);
                                }
                                try {
                                    $sheet->duplicateStyle($sheet->getStyle($cell->getCoordinate()), $cell->getColumn() . $dst);
                                } catch (\Throwable $e) {
                                    // gaya gagal disalin tidak menggagalkan render
                                }
                            }
                        }
                    }
                }
            }
            foreach ($barisData as $k => $data) {
                for ($r = 0; $r < $tinggi; $r++) {
                    $baris = $mulai + $k * $tinggi + $r;
                    foreach ($sheet->getRowIterator($baris, $baris) as $rowIsi) {
                        foreach ($rowIsi->getCellIterator('A', $kolomAkhir) as $cell) {
                            $t = self::teksSel($cell);
                            if ($t === null || ! str_contains($t, '{{' . $awalan . '_')) {
                                continue;
                            }
                            $baru = $t;
                            foreach ($data as $kunci => $nilai) {
                                $baru = str_replace('{{' . $kunci . '}}', (string) $nilai, $baru);
                            }
                            $cell->setValueExplicit($baru, DataType::TYPE_STRING);
                        }
                    }
                }
            }
        }
    }
}
