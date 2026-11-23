<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
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
            'spk'     => ['label' => 'Dokumen SPK', 'konteks' => 'Satu SPK', 'berulang' => ['RINCIAN' => 'Baris rincian SPK', 'TENAGA' => 'Baris kebutuhan tenaga kerja']],
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
                ];
            case 'gedung':
                return [
                    'KERJA' => [
                        'KERJA_NO' => 'Nomor urut baris', 'KERJA_KODE' => 'Kode analisa', 'KERJA_NAMA' => 'Nama pekerjaan',
                        'KERJA_VOLUME' => 'Volume berjalan (2 desimal)', 'KERJA_SATUAN' => 'Satuan',
                        'KERJA_HARGA' => 'Harga satuan dokumen (angka polos)', 'KERJA_NILAI' => 'Nilai baris (angka polos)',
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

    /** @return array<int,string> nilai semua sel bertipe string pada satu sheet */
    private static function selString(Worksheet $sheet): array
    {
        $nilai = [];
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $v = $cell->getValue();
                if (is_string($v) && $v !== '') {
                    $nilai[] = $v;
                }
            }
        }
        return $nilai;
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
                    $v = $cell->getValue();
                    if (! is_string($v) || ! str_contains($v, '{{')) {
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

    /** Gandakan baris contoh tiap awalan mengikuti jumlah baris data. */
    private static function renderBerulang(Worksheet $sheet, array $berulang): void
    {
        foreach ($berulang as $awalan => $barisData) {
            // Cari baris contoh: baris pertama yang selnya memuat {{AWALAN_...}}.
            $barisContoh = null;
            $selContoh = [];
            foreach ($sheet->getRowIterator() as $row) {
                $ketemu = false;
                foreach ($row->getCellIterator() as $cell) {
                    $v = $cell->getValue();
                    if (is_string($v) && str_contains($v, '{{' . $awalan . '_')) {
                        $selContoh[$cell->getColumn()] = $v;
                        $ketemu = true;
                    }
                }
                if ($ketemu) {
                    $barisContoh = $row->getRowIndex();
                    break;
                }
            }
            if ($barisContoh === null) {
                continue; // template memang tidak memakai blok ini
            }
            $jumlah = count($barisData);
            if ($jumlah === 0) {
                $sheet->removeRow($barisContoh, 1);
                continue;
            }
            if ($jumlah > 1) {
                $sheet->insertNewRowBefore($barisContoh + 1, $jumlah - 1);
                // Salin nilai contoh + gaya ke baris sisipan.
                $tinggi = $sheet->getRowDimension($barisContoh)->getRowHeight();
                for ($i = 1; $i < $jumlah; $i++) {
                    $target = $barisContoh + $i;
                    if ($tinggi !== null) {
                        $sheet->getRowDimension($target)->setRowHeight($tinggi);
                    }
                    foreach ($selContoh as $kolom => $v) {
                        $sheet->setCellValue($kolom . $target, $v);
                        try {
                            $sheet->duplicateStyle($sheet->getStyle($kolom . $barisContoh), $kolom . $target);
                        } catch (\Throwable $e) {
                            // gaya gagal disalin tidak menggagalkan render
                        }
                    }
                }
            }
            // Isi tiap baris dengan data barisnya.
            foreach ($barisData as $i => $data) {
                $baris = $barisContoh + $i;
                foreach ($selContoh as $kolom => $v) {
                    $baru = $v;
                    foreach ($data as $kunci => $nilai) {
                        $baru = str_replace('{{' . $kunci . '}}', (string) $nilai, $baru);
                    }
                    $sheet->setCellValueExplicit($kolom . $baris, $baru, DataType::TYPE_STRING);
                }
            }
        }
    }
}
