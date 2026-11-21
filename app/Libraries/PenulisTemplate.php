<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Menulis TEMPLATE MANUAL (format ParserTemplate) dari data aplikasi —
 * untuk fitur "Ekspor Template Terisi": pengguna memeriksa/mengoreksi
 * hasil baca parser di Excel, lalu mengimpornya kembali.
 */
class PenulisTemplate
{
    public static function unduh(Spreadsheet $ss, string $namaBerkas): void
    {
        $writer = new Xlsx($ss);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $namaBerkas . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    public static function dariData(array $sekolah, array $bahan, array $upah, array $analisa, array $boq, array $biayaLain = []): Spreadsheet
    {
        $ss = new Spreadsheet();
        self::sheetPetunjuk($ss->getActiveSheet());

        // Sekolah (kunci-nilai, nilai di kolom C mulai baris 4)
        $ws = $ss->createSheet();
        $ws->setTitle('Sekolah');
        $ws->setCellValue('A1', 'IDENTITAS SEKOLAH & PAGU ANGGARAN');
        $ws->setCellValue('A2', 'Isi kolom C. Baris bertanda * wajib diisi.');
        $isi = [
            'Nama Sekolah *' => $sekolah['nama_sekolah'] ?? '',
            'NPSN' => $sekolah['npsn'] ?? '',
            'Tahun Anggaran *' => $sekolah['tahun_anggaran'] ?? '',
            'Total Anggaran / Pagu (Rp) *' => (float) ($sekolah['total_anggaran'] ?? 0) ?: '',
            'Alamat' => $sekolah['alamat'] ?? '',
            'Desa/Kelurahan' => $sekolah['desa'] ?? '',
            'Kecamatan' => $sekolah['kecamatan'] ?? '',
            'Kabupaten' => $sekolah['kabupaten'] ?? '',
            'Kepala Sekolah' => $sekolah['kepsek_nama'] ?? '',
            'NIP Kepala Sekolah' => $sekolah['kepsek_nip'] ?? '',
        ];
        $r = 4;
        foreach ($isi as $k => $v) {
            $ws->setCellValue('A' . $r, $k);
            $ws->setCellValue('C' . $r, $v);
            $r++;
        }
        $ws->getColumnDimension('A')->setWidth(28);
        $ws->getColumnDimension('C')->setWidth(55);

        // Harga
        $ws = $ss->createSheet();
        $ws->setTitle('Harga');
        self::kepala($ws, 'DAFTAR HARGA BAHAN & UPAH', ['Kode', 'Jenis *', 'Nama *', 'Satuan *', 'Harga Satuan (Rp) *'], [10, 10, 42, 10, 18]);
        $r = 5;
        foreach ($bahan as $b) {
            $ws->fromArray([$b['kode'], 'Bahan', $b['nama'], $b['satuan'], (float) $b['harga_rab']], null, 'A' . $r++);
        }
        foreach ($upah as $u) {
            $ws->fromArray(['', 'Upah', $u['jenis'], $u['satuan'], (float) $u['tarif']], null, 'A' . $r++);
        }

        // Analisa (datar per komponen)
        $ws = $ss->createSheet();
        $ws->setTitle('Analisa');
        self::kepala($ws, 'ANALISA HARGA SATUAN (AHSP)', ['Kode Analisa *', 'Uraian Pekerjaan *', 'Satuan Pekerjaan', 'Jenis Komponen *', 'Nama Komponen *', 'Koefisien *', 'Satuan Komponen', 'Harga Komponen'], [13, 44, 12, 13, 36, 12, 12, 14]);
        $r = 5;
        foreach ($analisa as $a) {
            if ($a['komponen'] === []) {
                // Analisa tanpa komponen tetap ditulis sebagai baris kepala
                // agar tidak hilang saat template diimpor kembali.
                $ws->fromArray([$a['kode'], $a['nama'], $a['satuan'], '', '', '', '', ''], null, 'A' . $r++);
                continue;
            }
            foreach ($a['komponen'] as $k) {
                $hargaKomp = isset($k['tarif']) && (float) $k['tarif'] > 0 ? (float) $k['tarif'] : null;
                $ws->fromArray([$a['kode'], $a['nama'], $a['satuan'], ucfirst($k['jenis']), $k['nama'], (float) $k['koefisien'], $k['satuan'], $hargaKomp], null, 'A' . $r++);
            }
        }

        // Harsat — harga satuan pekerjaan resmi per kode analisa
        $ws = $ss->createSheet();
        $ws->setTitle('Harsat');
        self::kepala($ws, 'HARSAT — HARGA SATUAN PEKERJAAN PER KODE ANALISA', ['Kode Analisa *', 'Uraian Pekerjaan', 'Harga Satuan *'], [13, 46, 16]);
        $r = 5;
        foreach ($analisa as $a) {
            if (isset($a['harga_satuan']) && (float) $a['harga_satuan'] > 0) {
                $ws->fromArray([$a['kode'], $a['nama'], (float) $a['harga_satuan']], null, 'A' . $r++);
            }
        }

        // BOQ (datar per pekerjaan)
        $ws = $ss->createSheet();
        $ws->setTitle('BOQ');
        self::kepala($ws, 'BOQ — DAFTAR PEKERJAAN PER GEDUNG', ['Gedung *', 'Bab / Kelompok', 'Uraian Pekerjaan *', 'Kode Analisa', 'Volume *', 'Satuan', 'Harga Satuan (opsional)'], [24, 22, 46, 13, 12, 10, 17]);
        $r = 5;
        foreach ($boq as $row) {
            $hargaIsi = isset($row['harga_satuan']) && (float) $row['harga_satuan'] > 0 ? (float) $row['harga_satuan'] : null;
            $ws->fromArray([$row['gedung'], $row['bab'], $row['nama'], $row['kode_analisa'], (float) $row['volume'], $row['satuan'], $hargaIsi], null, 'A' . $r++);
        }
        // BiayaLain — biaya non-gedung tingkat sekolah
        $ws = $ss->createSheet();
        $ws->setTitle('BiayaLain');
        self::kepala($ws, 'BIAYA NON-GEDUNG — PERENCANAAN, PENGAWASAN & PENGELOLAAN', ['Kelompok *', 'Uraian *', 'Volume *', 'Satuan', 'Periode', 'Satuan Periode', 'Harga Satuan (Rp) *', 'Keterangan'], [26, 44, 10, 10, 10, 12, 18, 34]);
        $r = 5;
        foreach ($biayaLain as $row) {
            $ws->fromArray([$row['kelompok'], $row['uraian'], (float) $row['volume'], $row['satuan'] ?? '', $row['periode'] !== null ? (float) $row['periode'] : '', $row['satuan_periode'] ?? '', (float) $row['harga_satuan'], $row['keterangan'] ?? ''], null, 'A' . $r++);
        }

        $ss->setActiveSheetIndex(0);
        return $ss;
    }

    private static function kepala($ws, string $judul, array $kolom, array $lebar): void
    {
        $ws->setCellValue('A1', $judul);
        foreach ($kolom as $i => $nama) {
            $huruf = Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue($huruf . '4', $nama);
            $gaya = $ws->getStyle($huruf . '4');
            $gaya->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0049B8');
            $gaya->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $ws->getColumnDimension($huruf)->setWidth($lebar[$i] ?? 14);
        }
        $ws->freezePane('A5');
    }

    private static function sheetPetunjuk($ws): void
    {
        $ws->setTitle('Petunjuk');
        $ws->getColumnDimension('A')->setWidth(105);
        $teks = [
            'TEMPLATE IMPOR DATA PERENCANA — Analisa Perencana (PBJ-REVIT)',
            'Berkas ini adalah EKSPOR dari data aplikasi. Periksa dan koreksi seperlunya, lalu impor kembali lewat menu Impor.',
            'Aturan: jangan mengubah nama sheet dan baris judul (baris 4); data dibaca mulai baris 5.',
            'Kode analisa di BOQ harus sama dengan di Analisa. Nama bahan/upah di Analisa harus sama dengan di Harga.',
            'Harga Komponen di Analisa adalah harga per SATUAN KOMPONEN dari dokumen analisa (mis. Semen Rp 1.860 per kg) — acuan harga yang sebenarnya.',
            'Sheet Harsat memuat harga satuan pekerjaan resmi per kode analisa: dipakai bila kolom Harga Satuan di BOQ kosong, sebelum dihitung dari komponen Analisa.',
            'Sheet BiayaLain memuat biaya non-gedung tingkat sekolah (perencanaan, pengawasan, pengelolaan): jumlah = volume x periode (bila ada) x harga satuan. Baris manual di aplikasi tidak tertimpa impor template.',
            'Baris yang kolom pertamanya diawali CONTOH dilewati saat impor.',
            'Kolom Harga Satuan (opsional) di BOQ: bila diisi, nilai itu yang dipakai apa adanya; bila dikosongkan, harga dihitung dari Analisa.',
        ];
        foreach ($teks as $i => $t) {
            $ws->setCellValue('A' . ($i + 1), $t);
        }
    }
}
