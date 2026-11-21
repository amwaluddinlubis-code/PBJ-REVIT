<?php

namespace App\Libraries;

/**
 * Pembaca .xlsx mentah: membaca NILAI TERSIMPAN (cached values) langsung
 * dari XML di dalam zip, tanpa menghidupkan rumus.
 *
 * Dibutuhkan karena workbook perencana menyimpan rumus antar-sheet yang
 * sebagian rantainya #REF! — mesin hitung PhpSpreadsheet gagal, sedangkan
 * nilai tersimpan di berkas (hasil simpan terakhir Excel) benar. Pembaca ini
 * juga jauh lebih ringan: streaming XMLReader, tanpa memuat seluruh workbook.
 */
class PembacaXlsxMentah
{
    private \ZipArchive $zip;
    /** @var array<string,string> nama sheet => path di dalam zip */
    private array $petaSheet = [];
    /** @var string[] */
    private array $strings = [];

    public function __construct(string $path)
    {
        $this->zip = new \ZipArchive();
        if ($this->zip->open($path) !== true) {
            throw new \RuntimeException('Berkas .xlsx tidak dapat dibuka.');
        }
        $wb = $this->zip->getFromName('xl/workbook.xml');
        if ($wb === false) {
            throw new \RuntimeException('Berkas bukan workbook .xlsx yang valid.');
        }
        $relMap = [];
        $rels = $this->zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rels !== false) {
            $xr = new \XMLReader();
            $xr->XML($rels);
            while ($xr->read()) {
                if ($xr->nodeType === \XMLReader::ELEMENT && $xr->localName === 'Relationship') {
                    $relMap[(string) $xr->getAttribute('Id')] = (string) $xr->getAttribute('Target');
                }
            }
        }
        $xr = new \XMLReader();
        $xr->XML($wb);
        while ($xr->read()) {
            if ($xr->nodeType === \XMLReader::ELEMENT && $xr->localName === 'sheet') {
                $nama = (string) $xr->getAttribute('name');
                $rid = (string) $xr->getAttributeNs('id', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $target = $relMap[$rid] ?? '';
                if (str_starts_with($target, '/')) {
                    $target = ltrim($target, '/');
                } elseif (! str_starts_with($target, 'xl/')) {
                    $target = 'xl/' . $target;
                }
                if ($nama !== '' && $target !== 'xl/') {
                    $this->petaSheet[$nama] = $target;
                }
            }
        }
        $ss = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false) {
            $xr = new \XMLReader();
            $xr->XML($ss);
            $cur = null;
            while ($xr->read()) {
                if ($xr->nodeType === \XMLReader::ELEMENT && $xr->localName === 'si') {
                    $cur = '';
                } elseif ($xr->nodeType === \XMLReader::ELEMENT && $xr->localName === 't' && $cur !== null) {
                    $cur .= $xr->readString();
                } elseif ($xr->nodeType === \XMLReader::END_ELEMENT && $xr->localName === 'si' && $cur !== null) {
                    $this->strings[] = $cur;
                    $cur = null;
                }
            }
        }
    }

    /** @return string[] nama sheet apa adanya */
    public function daftarSheet(): array
    {
        return array_keys($this->petaSheet);
    }

    /** Path zip untuk nama sheet (pencocokan longgar: trim + abaikan kapital). */
    private function pathSheet(string $nama): ?string
    {
        if (isset($this->petaSheet[$nama])) {
            return $this->petaSheet[$nama];
        }
        $target = ParserPerencana::normal($nama);
        foreach ($this->petaSheet as $n => $p) {
            if (ParserPerencana::normal($n) === $target) {
                return $p;
            }
        }
        return null;
    }

    public function adaSheet(string $nama): bool
    {
        return $this->pathSheet($nama) !== null;
    }

    /**
     * Hasilkan baris-baris sheet sebagai array nilai (indeks 0 = kolom A),
     * dibatasi sampai kolom ke-$kolomMaks. Nilai rumus = nilai tersimpan.
     */
    public function baris(string $namaSheet, int $kolomMaks = 20): \Generator
    {
        $path = $this->pathSheet($namaSheet);
        if ($path === null) {
            return;
        }
        $xml = $this->zip->getFromName($path);
        if ($xml === false) {
            return;
        }
        $xr = new \XMLReader();
        $xr->XML($xml);
        $baris = null;
        $kolom = -1;
        $tipe = '';
        $dalamIs = false;
        while ($xr->read()) {
            $nama = $xr->localName;
            if ($xr->nodeType === \XMLReader::ELEMENT) {
                if ($nama === 'row') {
                    $baris = array_fill(0, $kolomMaks, null);
                } elseif ($nama === 'c' && $baris !== null) {
                    $r = (string) $xr->getAttribute('r');
                    $kolom = self::indeksKolom($r);
                    $tipe = (string) $xr->getAttribute('t');
                    if ($xr->isEmptyElement) {
                        $kolom = -1;
                    }
                } elseif ($nama === 'v' && $kolom >= 0 && $kolom < $kolomMaks && $baris !== null) {
                    $mentah = $xr->readString();
                    if ($tipe === 's') {
                        $baris[$kolom] = $this->strings[(int) $mentah] ?? '';
                    } elseif ($tipe === 'str' || $tipe === 'e') {
                        $baris[$kolom] = $mentah;
                    } else {
                        $baris[$kolom] = is_numeric($mentah) ? (float) $mentah : $mentah;
                    }
                } elseif ($nama === 'is') {
                    $dalamIs = true;
                } elseif ($nama === 't' && $dalamIs && $kolom >= 0 && $kolom < $kolomMaks && $baris !== null) {
                    $baris[$kolom] = (string) ($baris[$kolom] ?? '') . $xr->readString();
                }
            } elseif ($xr->nodeType === \XMLReader::END_ELEMENT) {
                if ($nama === 'row' && $baris !== null) {
                    yield $baris;
                    $baris = null;
                } elseif ($nama === 'c') {
                    $kolom = -1;
                    $tipe = '';
                } elseif ($nama === 'is') {
                    $dalamIs = false;
                }
            }
        }
    }

    /** 'L22' -> 11 (indeks 0-based kolom L). */
    private static function indeksKolom(string $ref): int
    {
        if (! preg_match('/^([A-Z]+)\d+$/', $ref, $m)) {
            return -1;
        }
        $n = 0;
        foreach (str_split($m[1]) as $huruf) {
            $n = $n * 26 + (ord($huruf) - 64);
        }
        return $n - 1;
    }
}
