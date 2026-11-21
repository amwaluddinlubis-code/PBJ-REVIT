<?php

namespace App\Libraries;

/**
 * Parser dokumen perencana (workbook RAB revitalisasi dari tenaga teknis).
 * Membaca: daftar harga bahan & upah, analisa AHSP, sheet BOQ per gedung,
 * total bantuan dari Rekap RAB, lalu menulis ke tabel modul perencana.
 *
 * Nilai dibaca sebagai NILAI TERSIMPAN berkas (PembacaXlsxMentah) karena
 * rumus antar-sheet dokumen ini sebagian rantainya #REF! bagi mesin hitung,
 * sedangkan nilai simpanan terakhir Excel-nya benar.
 */
class ParserPerencana
{
    private $db;
    private int $sid;

    public function __construct($db, int $sekolahId)
    {
        $this->db  = $db;
        $this->sid = $sekolahId;
    }

    /** Normalisasi nama untuk pencocokan: huruf kecil, tanpa tanda baca, spasi tunggal. */
    public static function normal(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['.', ',', '(', ')', '"', "'", '*'], ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    /** Nama dasar sebelum tanda '/' (mis. "Batu Kali / Belah" -> "batu kali"). */
    public static function normalDasar(string $s): string
    {
        return self::normal(explode('/', $s)[0]);
    }

    private static function num($v): float
    {
        if (is_numeric($v)) {
            return (float) $v;
        }
        if (! is_string($v)) {
            return 0.0;
        }
        $s = trim(str_replace(['Rp', ' '], '', $v));
        if ($s === '' || str_contains($s, '#') || str_starts_with($s, '=')) {
            return 0.0;
        }
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }
        return is_numeric($s) ? (float) $s : 0.0;
    }

    private static function teks($v): string
    {
        return trim((string) ($v ?? ''));
    }

    /**
     * Bentuk kanonik kode analisa agar BOQ dan sheet Analisa selalu bertemu:
     * "F1" -> "F.01", "A.1" -> "A.01", "A.01.P.4" -> "A.01.P.04".
     */
    public static function kanonKode(string $kode): string
    {
        $kode = strtoupper(trim($kode));
        if (preg_match('/^([A-Z])(\d+)$/', $kode, $m)) {
            return $m[1] . '.' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
        }
        if (str_contains($kode, '.')) {
            $bagian = explode('.', $kode);
            foreach ($bagian as &$b) {
                if (ctype_digit($b)) {
                    $b = str_pad($b, 2, '0', STR_PAD_LEFT);
                }
            }
            return implode('.', $bagian);
        }
        return $kode;
    }

    /** Pola kode analisa pada baris judul blok (A.01, A.02.01, A.01.P.04, ...). */
    private static function polaKode(string $s): bool
    {
        return (bool) preg_match('/^[A-Z](\.\d+)+(\.P\.\d+)?$/', strtoupper(trim($s)));
    }

    /** Baris sheet dengan batas jumlah baris (0 = semua). */
    private function baris(PembacaXlsxMentah $p, string $sheet, int $kolomMaks, int $batas = 0): \Generator
    {
        $n = 0;
        foreach ($p->baris($sheet, $kolomMaks) as $sel) {
            yield $sel;
            $n++;
            if ($batas > 0 && $n >= $batas) {
                break;
            }
        }
    }

    /** Jalankan impor penuh; mengembalikan ringkasan hasil. */
    public function impor(string $path): array
    {
        $p = new PembacaXlsxMentah($path);
        $semuaSheet = $p->daftarSheet();
        $ringkas = [
            'gedung' => [], 'analisa' => 0, 'komponen' => 0, 'komponen_tak_tertaut' => 0,
            'bahan_baru' => 0, 'upah' => 0, 'total_anggaran' => 0, 'npsn' => '',
        ];

        // 1) Daftar harga bahan & upah -> upah_master + master bahan tertaut
        $petaBahan = $this->petaBahanSekolah();
        foreach (['Harga bahan & upah', 'Harga bahan & upah 2'] as $nama) {
            if ($p->adaSheet($nama)) {
                $hasil = $this->imporDaftarHarga($p, $nama, $petaBahan);
                $ringkas['bahan_baru'] += $hasil['bahan_baru'];
                $ringkas['upah']      += $hasil['upah'];
                $petaBahan = $hasil['peta'];
                break;
            }
        }

        // 2) Total bantuan dari Rekap RAB per ruang + NPSN dari Input Data
        if ($p->adaSheet('Rekap RAB per ruang')) {
            $maks = 0.0;
            foreach ($this->baris($p, 'Rekap RAB per ruang', 30, 12) as $sel) {
                foreach ($sel as $v) {
                    $maks = max($maks, self::num($v));
                }
            }
            if ($maks > 100000000) {
                $ringkas['total_anggaran'] = $maks;
                $this->db->table('sekolah')->where('id', $this->sid)->update(['total_anggaran' => $maks]);
            }
        }
        if ($p->adaSheet('Input Data')) {
            foreach ($this->baris($p, 'Input Data', 8, 45) as $sel) {
                foreach ($sel as $i => $v) {
                    if (self::teks($v) === 'NPSN' && isset($sel[$i + 2])) {
                        $ringkas['npsn'] = self::teks($sel[$i + 2]);
                    }
                }
            }
        }

        // 3) Analisa AHSP (+ komponen tertaut ke bahan/upah)
        $sudahKode = [];
        foreach (['Analisa', 'Analisa 2'] as $nama) {
            if (! $p->adaSheet($nama)) {
                continue;
            }
            $hasil = $this->imporAnalisa($p, $nama, $petaBahan, $sudahKode);
            $ringkas['analisa']              += $hasil['analisa'];
            $ringkas['komponen']             += $hasil['komponen'];
            $ringkas['komponen_tak_tertaut'] += $hasil['tak_tertaut'];
            $sudahKode = $hasil['kode'];
        }

        // 4) Sheet BOQ per gedung
        $bukanBoq = ['harga bahan upah', 'harga bahan upah 2', 'analisa', 'analisa 2',
                     'rekap rab per ruang', 'input data', 'harsat pekerjaan'];
        foreach ($semuaSheet as $nama) {
            if ($this->sheetDilewati($nama) || in_array(self::normal($nama), $bukanBoq, true)) {
                continue;
            }
            $boq = $this->bacaBoq($p, $nama);
            if ($boq === null) {
                continue;
            }
            $gid = $this->simpanGedung(trim($nama), $boq);
            $ringkas['gedung'][] = [
                'nama' => trim($nama), 'gedung_id' => $gid,
                'pekerjaan' => count($boq['items']),
                'total_hitung' => array_sum(array_column($boq['items'], 'jumlah')),
                'total_sheet' => $boq['grand'],
            ];
        }

        return $ringkas;
    }

    private function sheetDilewati(string $nama): bool
    {
        $n = trim($nama);
        if (preg_match('/^C\s*\d+$/i', $n)) {
            return true; // template contoh
        }
        if (str_contains($n, '(REALISASI)') || str_starts_with($n, 'BOQ') || $n === 'RKB 1 (2)') {
            return true;
        }
        foreach (['Rekap', 'Input', 'Harsat', 'Vol', 'Daftar', 'Cover', 'RPD', 'REALISASI', 'Lembar', 'Siteplan', 'Gambar'] as $awal) {
            if (str_starts_with($n, $awal)) {
                return true;
            }
        }
        return false;
    }

    /** Peta nama ternormalisasi -> bahan_id untuk sekolah aktif. */
    private function petaBahanSekolah(): array
    {
        $peta = [];
        foreach ($this->db->table('bahan')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])->get()->getResultArray() as $b) {
            $peta[self::normal($b['nama'])] = (int) $b['id'];
            $peta[self::normalDasar($b['nama'])] = (int) $b['id'];
        }
        return $peta;
    }

    private function imporDaftarHarga(PembacaXlsxMentah $p, string $sheet, array $peta): array
    {
        $mode = null;
        $bahanBaru = 0;
        $upah = 0;
        $maksKode = (int) ($this->db->query('SELECT COALESCE(MAX(CAST(SUBSTRING(kode,2) AS UNSIGNED)),0) m FROM bahan WHERE sekolah_id = ?', [$this->sid])->getRow()->m ?? 0);

        foreach ($this->baris($p, $sheet, 8) as $sel) {
            $gabung = strtoupper((string) preg_replace('/\s+/', ' ', implode(' ', array_map(static fn ($v) => self::teks($v), $sel))));
            if (str_contains($gabung, 'DAFTAR HARGA UPAH')) {
                $mode = 'upah';
                continue;
            }
            if (str_contains($gabung, 'DAFTAR HARGA BAHAN')) {
                $mode = 'bahan';
                continue;
            }
            if ($mode === null) {
                continue;
            }
            $no = self::teks($sel[1] ?? '');
            $nama = self::teks($sel[2] ?? '');
            $harga = self::num($sel[5] ?? null);
            // Baris kategori tidak mempunyai nomor urut; baris data selalu bernomor.
            if ($nama === '' || $no === '' || strtoupper($no) === 'NO'
                || in_array(strtoupper($nama), ['JENIS BAHAN', 'JENIS UPAH'], true)) {
                continue;
            }
            if ($mode === 'upah') {
                $ada = $this->db->table('upah_master')->where(['sekolah_id' => $this->sid, 'jenis' => $nama, 'is_deleted' => 0])->get()->getRowArray();
                if ($ada) {
                    if ($harga > 0) {
                        $this->db->table('upah_master')->where('id', $ada['id'])->update(['tarif' => $harga]);
                    }
                } else {
                    $this->db->table('upah_master')->insert([
                        'sekolah_id' => $this->sid, 'jenis' => $nama, 'satuan' => 'OH', 'tarif' => $harga,
                    ]);
                }
                $upah++;
            } else {
                $kunci = self::normal($nama);
                $dasar = self::normalDasar($nama);
                if (isset($peta[$kunci]) || isset($peta[$dasar])) {
                    $peta[$kunci] = $peta[$kunci] ?? $peta[$dasar];
                    continue; // sudah ada di master; harga master tidak ditimpa
                }
                $maksKode++;
                $kode = 'B' . str_pad((string) $maksKode, 3, '0', STR_PAD_LEFT);
                $this->db->table('bahan')->insert([
                    'sekolah_id' => $this->sid, 'kode' => $kode, 'nama' => $nama,
                    'satuan' => self::teks($sel[3] ?? '') ?: '-', 'harga_rab' => (int) round($harga),
                    'qty_rkb' => 0, 'qty_adm' => 0, 'qty_toilet' => 0,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $peta[$kunci] = (int) $this->db->insertID();
                $peta[$dasar] = $peta[$kunci];
                $bahanBaru++;
            }
        }
        return ['peta' => $peta, 'bahan_baru' => $bahanBaru, 'upah' => $upah];
    }

    private function imporAnalisa(PembacaXlsxMentah $p, string $sheet, array $petaBahan, array $sudahKode): array
    {
        $tarifUpah = [];
        foreach ($this->db->table('upah_master')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])->get()->getResultArray() as $u) {
            $tarifUpah[self::normal($u['jenis'])] = (float) $u['tarif'];
        }
        $cariTarif = static function (string $nama) use ($tarifUpah): ?float {
            $k = self::normal($nama);
            if (isset($tarifUpah[$k])) {
                return $tarifUpah[$k];
            }
            foreach ($tarifUpah as $jenis => $tarif) {
                if (str_starts_with($k, $jenis) || str_starts_with($jenis, $k)) {
                    return $tarif;
                }
            }
            return null;
        };

        // Deteksi tata letak kolom sheet ini: cari baris judul blok pertama
        // (sel berkode analisa diikuti uraian "1 <satuan> ..."). Sheet "Analisa"
        // dan "Analisa 2" berbeda geser kolomnya.
        $kolKode = null; $kolUraian = null; $kolNama = null; $kolKoef = null;
        foreach ($this->baris($p, $sheet, 12, 60) as $sel) {
            foreach ($sel as $j => $v) {
                if (self::polaKode(self::teks($v)) && str_starts_with(self::teks($sel[$j + 1] ?? ''), '1 ')) {
                    $kolKode = $j; $kolUraian = $j + 1; $kolNama = $j + 2; $kolKoef = $j + 3;
                    break 2;
                }
            }
        }
        if ($kolKode === null) {
            return ['analisa' => 0, 'komponen' => 0, 'tak_tertaut' => 0, 'kode' => $sudahKode];
        }

        $nAnalisa = 0; $nKomponen = 0; $nTak = 0;
        $blok = null; $mode = null;
        $simpan = function () use (&$blok, &$nAnalisa, &$nKomponen, &$nTak, &$sudahKode, $petaBahan, $cariTarif) {
            if ($blok === null) {
                return;
            }
            $kode = $blok['kode'];
            if (! isset($sudahKode[$kode])) {
                $this->db->table('analisa')->ignore(true)->insert([
                    'sekolah_id' => $this->sid, 'kode' => $kode, 'nama' => $blok['nama'], 'satuan' => $blok['satuan'],
                ]);
                $row = $this->db->table('analisa')->where(['sekolah_id' => $this->sid, 'kode' => $kode, 'is_deleted' => 0])->get()->getRowArray();
                $aid = (int) $row['id'];
                $this->db->table('analisa')->where('id', $aid)->update(['nama' => $blok['nama'], 'satuan' => $blok['satuan']]);
                $this->db->table('analisa_komponen')->where('analisa_id', $aid)->delete();
                foreach ($blok['komponen'] as $k) {
                    $bahanId = null; $tarif = null;
                    if ($k['jenis'] === 'bahan') {
                        $bahanId = $petaBahan[self::normal($k['nama'])] ?? $petaBahan[self::normalDasar($k['nama'])] ?? null;
                        if ($bahanId === null) {
                            $nTak++;
                        }
                    } elseif ($k['jenis'] === 'upah') {
                        $tarif = $cariTarif($k['nama']);
                    }
                    $this->db->table('analisa_komponen')->insert([
                        'analisa_id' => $aid, 'jenis' => $k['jenis'], 'nama' => $k['nama'],
                        'satuan' => $k['satuan'] ?? null,
                        'koefisien' => $k['koefisien'], 'bahan_id' => $bahanId, 'tarif' => $tarif,
                    ]);
                    $nKomponen++;
                }
                $sudahKode[$kode] = true;
                $nAnalisa++;
            }
            $blok = null;
        };

        foreach ($this->baris($p, $sheet, 12) as $sel) {
            $kode = self::teks($sel[$kolKode] ?? '');
            if (self::polaKode($kode) && str_starts_with(self::teks($sel[$kolUraian] ?? ''), '1 ')) {
                $simpan();
                $uraian = self::teks($sel[$kolUraian] ?? '');
                $satuan = '';
                if (preg_match('/^1\s+(\S+)\s/', $uraian, $m)) {
                    $satuan = $m[1];
                }
                $blok = ['kode' => self::kanonKode($kode), 'nama' => $uraian, 'satuan' => $satuan, 'komponen' => []];
                $mode = null;
                continue;
            }
            if ($blok === null) {
                continue;
            }
            $nama = self::teks($sel[$kolNama] ?? '');
            if ($nama === '') {
                continue;
            }
            $atas = strtoupper($nama);
            if (str_starts_with($atas, 'BAHAN')) {
                $mode = 'bahan';
                continue;
            }
            if (str_starts_with($atas, 'UPAH') || str_starts_with($atas, 'TENAGA')) {
                $mode = 'upah';
                continue;
            }
            if (str_starts_with($atas, 'ALAT')) {
                $mode = 'alat';
                continue;
            }
            if (str_starts_with($atas, 'JUMLAH') || str_starts_with($atas, 'DIBULATKAN')) {
                if (str_starts_with($atas, 'DIBULATKAN')) {
                    $simpan();
                }
                $mode = null;
                continue;
            }
            if ($mode !== null) {
                $blok['komponen'][] = [
                    'jenis' => $mode, 'nama' => $nama, 'koefisien' => self::num($sel[$kolKoef] ?? null),
                    'satuan' => self::teks($sel[$kolKoef + 1] ?? '') ?: null,
                ];
            }
        }
        $simpan();
        return ['analisa' => $nAnalisa, 'komponen' => $nKomponen, 'tak_tertaut' => $nTak, 'kode' => $sudahKode];
    }

    /** Baca satu sheet BOQ; null bila bukan format BOQ perencana. */
    private function bacaBoq(PembacaXlsxMentah $p, string $sheet): ?array
    {
        $kolom = null;
        $items = [];
        $grand = 0.0;
        $bab = '';
        $sub = '';
        $urut = 0;
        foreach ($this->baris($p, $sheet, 20) as $sel) {
            if ($kolom === null) {
                $peta = [];
                foreach ($sel as $j => $v) {
                    $t = self::teks($v);
                    if ($t !== '') {
                        $peta[mb_strtolower($t)] = $j;
                    }
                }
                if (isset($peta['uraian pekerjaan']) && isset($peta['analisa']) && isset($peta['jumlah harga'])) {
                    $kolom = [
                        'no' => $peta['no'] ?? (($peta['uraian pekerjaan']) - 1),
                        'uraian' => $peta['uraian pekerjaan'],
                        'analisa' => $peta['analisa'],
                        'vol' => $peta['vol'] ?? null,
                        'volume' => $peta['volume'] ?? null,
                        'sat' => $peta['sat'] ?? ($peta['satuan'] ?? null),
                        'harga' => $peta['harga satuan'] ?? null,
                        'jumlah' => $peta['jumlah harga'],
                    ];
                }
                continue;
            }
            $semua = implode(' ', array_map(static fn ($v) => self::teks($v), $sel));
            if (stripos($semua, 'GRAND TOTAL') !== false) {
                $grand = self::num($sel[$kolom['jumlah']] ?? null);
                if ($grand <= 0) {
                    foreach ($sel as $v) {
                        $grand = max($grand, self::num($v));
                    }
                }
                break;
            }
            $no = self::teks($sel[$kolom['no']] ?? '');
            if (preg_match('/^[IVXL]+\.$/', $no)) {
                $bab = self::teks($sel[$kolom['uraian']] ?? '');
                $sub = '';
                continue;
            }
            if ($kolom['harga'] !== null && str_starts_with(self::teks($sel[$kolom['harga']] ?? ''), 'Total')) {
                continue; // baris Total per bab
            }
            $nama = self::teks($sel[$kolom['uraian'] + 1] ?? '');
            $kode = self::teks($sel[$kolom['analisa']] ?? '');
            // Volume operatif HANYA kolom "Volume" (Pemb. Baru). Kolom "VOL" adalah
            // volume antara per tipe — baris contoh/alternatif mengisinya tetapi
            // jumlahnya 0 di dokumen, jadi tidak boleh dijadikan volume.
            $volume = $kolom['volume'] !== null ? self::num($sel[$kolom['volume']] ?? null) : 0.0;
            $harga = $kolom['harga'] !== null ? self::num($sel[$kolom['harga']] ?? null) : 0.0;
            $jumlah = self::num($sel[$kolom['jumlah']] ?? null);
            if ($nama === '') {
                $teksUraian = self::teks($sel[$kolom['uraian']] ?? '');
                if ($teksUraian !== '' && $kode === '' && $jumlah <= 0 && ! is_numeric($teksUraian)) {
                    $sub = $teksUraian;
                }
                continue;
            }
            if ($kode === '' && $volume <= 0 && $jumlah <= 0) {
                $sub = $nama;
                continue;
            }
            if ($jumlah <= 0 && $volume > 0 && $harga > 0) {
                $jumlah = $volume * $harga;
            }
            $urut++;
            $items[] = [
                'bab' => trim($bab . ($sub !== '' && $sub !== $bab ? ' / ' . $sub : '')),
                'nama' => $nama, 'kode_analisa' => $kode !== '' ? self::kanonKode($kode) : null,
                'volume' => $volume, 'satuan' => $kolom['sat'] !== null ? self::teks($sel[$kolom['sat']] ?? '') : '',
                'harga_satuan' => $harga, 'jumlah' => $jumlah, 'urutan' => $urut,
            ];
        }
        if ($kolom === null || count($items) < 2) {
            return null;
        }
        return ['items' => $items, 'grand' => $grand];
    }

    private function simpanGedung(string $namaSheet, array $boq): int
    {
        $db = $this->db;
        $ada = $db->table('gedung')->where(['sekolah_id' => $this->sid, 'sumber_sheet' => $namaSheet])->get()->getRowArray();
        if (! $ada) {
            // Klaim gedung bawaan migrasi yang namanya setara dengan nama sheet
            $alias = ['ADM' => 'Ruang ADM', 'TOILET' => 'Toilet', 'RKB 3 kopel' => 'RKB 3 Kopel'];
            $namaCari = $alias[$namaSheet] ?? $namaSheet;
            $ada = $db->query(
                'SELECT * FROM gedung WHERE sekolah_id = ? AND sumber_sheet IS NULL AND LOWER(nama) = LOWER(?) LIMIT 1',
                [$this->sid, $namaCari]
            )->getRowArray() ?: null;
            if ($ada) {
                $db->table('gedung')->where('id', $ada['id'])->update(['sumber_sheet' => $namaSheet]);
            }
        }
        $total = array_sum(array_column($boq['items'], 'jumlah'));
        if ($ada) {
            $gid = (int) $ada['id'];
            $db->table('gedung')->where('id', $gid)->update([
                'nilai_boq' => $total, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('pekerjaan')->where('gedung_id', $gid)->delete();
        } else {
            $urut = (int) ($db->query('SELECT COALESCE(MAX(urutan),0) m FROM gedung WHERE sekolah_id = ?', [$this->sid])->getRow()->m ?? 0) + 1;
            $db->table('gedung')->insert([
                'sekolah_id' => $this->sid, 'nama' => $namaSheet, 'nilai_boq' => $total,
                'sumber_sheet' => $namaSheet, 'urutan' => $urut,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $gid = (int) $db->insertID();
        }
        foreach ($boq['items'] as $it) {
            $db->table('pekerjaan')->insert([
                'gedung_id' => $gid, 'bab' => $it['bab'] ?: null, 'nama' => $it['nama'],
                'kode_analisa' => $it['kode_analisa'], 'volume' => $it['volume'],
                'satuan' => $it['satuan'] ?: null, 'harga_satuan' => $it['harga_satuan'],
                'jumlah' => $it['jumlah'], 'urutan' => $it['urutan'],
            ]);
        }
        return $gid;
    }
}
