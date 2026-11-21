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

    /** Tujuan impor sebuah sheet menurut pilihan pengguna (peninjauan). */
    private function tujuan(array $pilihan, string $sheet, string $bawaan): string
    {
        $t = $pilihan[$sheet]['tujuan'] ?? $bawaan;
        return in_array($t, ['gedung', 'non_gedung', 'referensi', 'lewati'], true) ? $t : $bawaan;
    }

    /** Nama sheet aktual di berkas dari bentuk normalnya (atau null). */
    private function namaAktual(PembacaXlsxMentah $p, string $normal): ?string
    {
        foreach ($p->daftarSheet() as $n) {
            if (self::normal($n) === $normal) {
                return $n;
            }
        }
        return null;
    }

    /**
     * Jalankan impor; mengembalikan ringkasan hasil. $pilihan (dari tahap
     * peninjauan) memetakan nama sheet => [tujuan, nama_gedung]; tanpa
     * pilihan, perilaku bawaan lama berlaku persis.
     */
    public function impor(string $path, array $pilihan = []): array
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
                if ($this->tujuan($pilihan, $nama, 'referensi') !== 'lewati') {
                    $hasil = $this->imporDaftarHarga($p, $nama, $petaBahan);
                    $ringkas['bahan_baru'] += $hasil['bahan_baru'];
                    $ringkas['upah']      += $hasil['upah'];
                    $petaBahan = $hasil['peta'];
                }
                break;
            }
        }

        // 2) Total bantuan dari Rekap RAB per ruang + NPSN dari Input Data
        $sheetRekap = $this->namaAktual($p, 'rekap rab per ruang');
        if ($sheetRekap !== null && $this->tujuan($pilihan, $sheetRekap, 'referensi') !== 'lewati') {
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
        $sheetInput = $this->namaAktual($p, 'input data');
        if ($sheetInput !== null && $this->tujuan($pilihan, $sheetInput, 'referensi') !== 'lewati') {
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
        $adaAnalisa = false;
        foreach (['Analisa', 'Analisa 2', 'Analisa (2)'] as $nama) {
            if (! $p->adaSheet($nama)) {
                continue;
            }
            if ($this->tujuan($pilihan, $nama, 'referensi') === 'lewati') {
                continue;
            }
            $adaAnalisa = true;
            $hasil = $this->imporAnalisa($p, $nama, $petaBahan, $sudahKode);
            $ringkas['analisa']              += $hasil['analisa'];
            $ringkas['komponen']             += $hasil['komponen'];
            $ringkas['komponen_tak_tertaut'] += $hasil['tak_tertaut'];
            $sudahKode = $hasil['kode'];
        }

        // 3b) Referensi bahan & upah DARI ANALISA: sheet Analisa memuat satuan
        // dan harga setiap komponen — jadikan itu acuan master (daftar harga
        // dokumen tidak punya kolom satuan). Lalu validasi ke Harsat Pekerjaan.
        if ($adaAnalisa) {
            $ringkas['referensi'] = $this->referensiDariAnalisa();
        }
        $sheetHarsat = $this->namaAktual($p, 'harsat pekerjaan');
        if ($sheetHarsat !== null && $this->tujuan($pilihan, $sheetHarsat, 'referensi') !== 'lewati') {
            $petaHarsat = $this->bacaHarsat($p);
            foreach ($petaHarsat as $kode => $harga) {
                $this->db->table('analisa')->where(['sekolah_id' => $this->sid, 'kode' => $kode])
                    ->update(['harga_satuan' => $harga]);
            }
            $ringkas['harsat'] = $this->validasiHarsat($petaHarsat);
        }

        // 3c) Biaya non-gedung: sheet "Prcn dan pgws" (perencanaan &
        // pengawasan) dan "ADMINISTRASI" (biaya pengelolaan P2SP).
        $ringkas['biaya_lain'] = $this->imporBiayaLain($p, $pilihan);

        // 4) Sheet per gedung / non-gedung menurut pemetaan peninjauan.
        // Sheet referensi (harga, analisa, rekap, input, harsat) sudah
        // ditangani di langkah 1-3 dan tidak masuk putaran ini.
        $referensi = ['harga bahan & upah', 'harga bahan & upah 2', 'analisa', 'analisa 2',
                      'analisa (2)', 'rekap rab per ruang', 'input data', 'harsat pekerjaan'];
        foreach ($semuaSheet as $nama) {
            $norm = self::normal($nama);
            if (in_array($norm, $referensi, true)) {
                continue;
            }
            $dipetakan = isset($pilihan[$nama]);
            if ($norm === 'prcn dan pgws' || $norm === 'administrasi') {
                // Bawaan non-gedung (langkah 3c); ke gedung hanya bila
                // pengguna memetakannya secara eksplisit di peninjauan.
                if ($this->tujuan($pilihan, $nama, 'non_gedung') !== 'gedung') {
                    continue;
                }
                $umum = $this->bacaItemUmum($p, $nama);
                if ($umum !== null) {
                    $gid = $this->simpanGedung(trim($nama), $umum, $pilihan[$nama]['nama_gedung'] ?? null);
                    $ringkas['gedung'][] = [
                        'nama' => trim($nama), 'gedung_id' => $gid,
                        'pekerjaan' => count($umum['items']),
                        'total_hitung' => array_sum(array_column($umum['items'], 'jumlah')),
                        'total_sheet' => $umum['grand'],
                    ];
                }
                continue;
            }
            $bawaan = $this->sheetDilewati($nama) ? 'lewati' : 'gedung';
            $tujuan = $this->tujuan($pilihan, $nama, $bawaan);
            if ($tujuan === 'lewati' || $tujuan === 'referensi') {
                continue;
            }
            $boq = $this->bacaBoq($p, $nama);
            if ($tujuan === 'non_gedung') {
                $items = $boq['items'] ?? null;
                if ($items === null && $dipetakan) {
                    $items = ($this->bacaItemUmum($p, $nama) ?? ['items' => []])['items'];
                }
                if ($items) {
                    [$nB, $tB] = $this->boqKeBiayaLain(trim($nama), $items);
                    $ringkas['biaya_lain']['baris'] += $nB;
                    $ringkas['biaya_lain']['total'] += $tB;
                }
                continue;
            }
            // tujuan gedung
            if ($boq === null && $dipetakan) {
                $boq = $this->bacaItemUmum($p, $nama);
            }
            if ($boq === null) {
                continue;
            }
            $gid = $this->simpanGedung(trim($nama), $boq, $pilihan[$nama]['nama_gedung'] ?? null);
            $ringkas['gedung'][] = [
                'nama' => trim($nama), 'gedung_id' => $gid,
                'pekerjaan' => count($boq['items']),
                'total_hitung' => array_sum(array_column($boq['items'], 'jumlah')),
                'total_sheet' => $boq['grand'],
            ];
        }

        return $ringkas;
    }

    /**
     * Turunkan satuan & harga BELI dari satuan & harga KOMPONEN analisa:
     * semen kg -> zak (x50), pasir kg -> m3 (x1400), kerikil/batu kg -> m3
     * (x1600), keramik bh -> ktk (x isi per ukuran). Lainnya apa adanya.
     * Mengembalikan [satuanBeli, hargaBeli, langsung(bool: satuan komponen
     * memang sudah satuan beli)].
     */
    private static function turunkanBeli(string $nama, ?string $satuanKomp, float $hargaKomp): array
    {
        $sk = mb_strtolower(trim((string) $satuanKomp));
        $n  = mb_strtolower($nama);
        if ($sk === 'kg') {
            if (str_contains($n, 'semen')) {
                return ['zak', $hargaKomp * 50, false];
            }
            if (str_contains($n, 'pasir')) {
                return ['m3', $hargaKomp * 1400, false];
            }
            if (str_contains($n, 'kerikil') || str_contains($n, 'batu')) {
                return ['m3', $hargaKomp * 1600, false];
            }
            return ['kg', $hargaKomp, true];
        }
        if (in_array($sk, ['bh', 'pcs', 'buah'], true) && str_contains($n, 'keramik')) {
            $isi = 1;
            foreach (['40' => 6, '30' => 11, '25' => 16, '20' => 25] as $uk => $per) {
                if (str_contains($nama, $uk)) {
                    $isi = $per;
                    break;
                }
            }
            return $isi > 1 ? ['ktk', $hargaKomp * $isi, false] : ['bh', $hargaKomp, true];
        }
        return [trim((string) $satuanKomp) ?: '-', $hargaKomp, true];
    }

    /** Nilai yang paling sering muncul (dibulatkan ke rupiah). */
    private static function modusHarga(array $nilai): float
    {
        $hitung = [];
        foreach ($nilai as $v) {
            $k = (string) (int) round($v);
            $hitung[$k] = ($hitung[$k] ?? 0) + 1;
        }
        arsort($hitung);
        return (float) array_key_first($hitung);
    }

    /**
     * Bangun/perbaiki referensi bahan & upah dari komponen Analisa sekolah ini:
     * - bahan master bersatuan '-' (hasil daftar harga) diisi satuan+harga beli
     *   turunan analisa;
     * - komponen bahan yang belum tertaut dibuatkan master dari analisa lalu
     *   ditautkan;
     * - tarif upah_master diisi dari tarif komponen analisa yang terbanyak.
     */
    private function referensiDariAnalisa(): array
    {
        $db = $this->db;
        $stat = ['bahan_diperbaiki' => 0, 'bahan_baru' => 0, 'upah_diperbarui' => 0];
        $komp = $db->query(
            "SELECT ak.id, ak.jenis, ak.nama, ak.satuan, ak.tarif, ak.bahan_id
             FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.is_deleted = 0",
            [$this->sid]
        )->getResultArray();

        // --- Bahan ---
        $grup = [];
        foreach ($komp as $k) {
            if ($k['jenis'] === 'bahan') {
                $grup[self::normal($k['nama'])][] = $k;
            }
        }
        $maksKode = (int) ($db->query('SELECT COALESCE(MAX(CAST(SUBSTRING(kode,2) AS UNSIGNED)),0) m FROM bahan WHERE sekolah_id = ?', [$this->sid])->getRow()->m ?? 0);
        foreach ($grup as $rows) {
            $langsung = []; $konversi = [];
            foreach ($rows as $k) {
                if ((float) ($k['tarif'] ?? 0) > 0) {
                    [$sat, $hrg, $lgs] = self::turunkanBeli($k['nama'], $k['satuan'], (float) $k['tarif']);
                    $lgs ? $langsung[] = [$sat, $hrg] : $konversi[] = [$sat, $hrg];
                }
            }
            $sumber = $langsung !== [] ? $langsung : $konversi;
            $satuanBeli = $sumber[0][0] ?? '-';
            $hargaBeli  = $sumber !== [] ? self::modusHarga(array_column($sumber, 1)) : 0.0;
            if ($sumber !== []) {
                // satuan mengikuti kelompok harga modus
                foreach ($sumber as [$sat, $hrg]) {
                    if ((int) round($hrg) === (int) $hargaBeli) {
                        $satuanBeli = $sat;
                        break;
                    }
                }
            }
            $bahanId = null;
            foreach ($rows as $k) {
                if (! empty($k['bahan_id'])) {
                    $bahanId = (int) $k['bahan_id'];
                    break;
                }
            }
            if ($bahanId !== null) {
                $b = $db->table('bahan')->where('id', $bahanId)->get()->getRowArray();
                if ($b && (trim((string) $b['satuan']) === '-' || trim((string) $b['satuan']) === '')) {
                    $db->table('bahan')->where('id', $bahanId)->update([
                        'satuan' => $satuanBeli, 'harga_rab' => (int) round($hargaBeli),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $stat['bahan_diperbaiki']++;
                }
            } else {
                $maksKode++;
                $db->table('bahan')->insert([
                    'sekolah_id' => $this->sid, 'kode' => 'B' . str_pad((string) $maksKode, 3, '0', STR_PAD_LEFT),
                    'nama' => $rows[0]['nama'], 'satuan' => $satuanBeli, 'harga_rab' => (int) round($hargaBeli),
                    'qty_rkb' => 0, 'qty_adm' => 0, 'qty_toilet' => 0,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $bahanId = (int) $db->insertID();
                $stat['bahan_baru']++;
            }
            if ($bahanId !== null) {
                $ids = array_column($rows, 'id');
                $db->table('analisa_komponen')->whereIn('id', $ids)->where('bahan_id', null)
                    ->update(['bahan_id' => $bahanId]);
            }
        }

        // --- Upah ---
        $grupUpah = [];
        foreach ($komp as $k) {
            if ($k['jenis'] === 'upah') {
                $grupUpah[self::normal($k['nama'])][] = $k;
            }
        }
        foreach ($grupUpah as $kunci => $rows) {
            if (str_contains($kunci, 'peralatan')) {
                continue; // baris alat yang tercantum di seksi upah dokumen
            }
            $tarifRef = 0.0;
            $positif = array_filter(array_map(static fn ($k) => (float) ($k['tarif'] ?? 0), $rows), static fn ($v) => $v > 0);
            if ($positif !== []) {
                $tarifRef = self::modusHarga(array_values($positif));
            }
            $ada = $db->table('upah_master')->where(['sekolah_id' => $this->sid, 'jenis' => $rows[0]['nama'], 'is_deleted' => 0])->get()->getRowArray();
            if ($ada) {
                if ((float) $ada['tarif'] <= 0 && $tarifRef > 0) {
                    $db->table('upah_master')->where('id', $ada['id'])->update(['tarif' => (int) round($tarifRef)]);
                    $stat['upah_diperbarui']++;
                }
            } else {
                $db->table('upah_master')->insert([
                    'sekolah_id' => $this->sid, 'jenis' => $rows[0]['nama'], 'satuan' => 'OH',
                    'tarif' => (int) round($tarifRef),
                ]);
                $stat['upah_diperbarui']++;
            }
        }

        // Master bahan dari daftar harga yang TIDAK dipakai satu pun komponen
        // analisa (satuan '-' tanpa referensi) disembunyikan secara lunak —
        // referensi resmi sekolah ini adalah yang dipakai Analisa.
        $db->query(
            "UPDATE bahan b SET b.is_deleted = 1, b.deleted_at = NOW()
             WHERE b.sekolah_id = ? AND b.is_deleted = 0 AND (b.satuan = '-' OR b.satuan = '')
               AND NOT EXISTS (SELECT 1 FROM analisa_komponen ak WHERE ak.bahan_id = b.id)
               AND NOT EXISTS (SELECT 1 FROM spk_detail sd WHERE sd.bahan_id = b.id)",
            [$this->sid]
        );
        $stat['bahan_dibersihkan'] = $db->affectedRows();
        return $stat;
    }

    /**
     * Validasi harga analisa terhadap sheet Harsat Pekerjaan (harga satuan
     * resmi per kode). Baris #REF!/kosong di dokumen dilewati. Mengembalikan
     * ringkasan jumlah kode yang cocok (selisih <= 1%) dan yang menyimpang.
     */
    /** Peta kode analisa -> harga satuan resmi dari sheet Harsat Pekerjaan. */
    private function bacaHarsat(PembacaXlsxMentah $p): array
    {
        $harsat = [];
        if (! $p->adaSheet('Harsat Pekerjaan')) {
            return $harsat;
        }
        foreach ($this->baris($p, 'Harsat Pekerjaan', 9) as $sel) {
            foreach ($sel as $j => $v) {
                if (self::polaKode(self::teks($v))) {
                    $harga = self::num($sel[$j + 3] ?? null);
                    if ($harga > 0) {
                        $harsat[self::kanonKode(self::teks($v))] = $harga;
                    }
                    break;
                }
            }
        }
        return $harsat;
    }

    private function validasiHarsat(array $harsat): array
    {
        $hasil = ['kode' => 0, 'cocok' => 0, 'selisih' => 0];
        if ($harsat === []) {
            return $hasil;
        }
        $hitung = [];
        $rows = $this->db->query(
            "SELECT a.kode, SUM(ak.koefisien * COALESCE(ak.tarif, 0)) AS total
             FROM analisa a JOIN analisa_komponen ak ON ak.analisa_id = a.id
             WHERE a.sekolah_id = ? AND ak.is_deleted = 0 GROUP BY a.kode",
            [$this->sid]
        )->getResultArray();
        foreach ($rows as $r) {
            $hitung[$r['kode']] = (float) $r['total'];
        }
        foreach ($harsat as $kode => $harga) {
            if (! isset($hitung[$kode])) {
                continue;
            }
            $hasil['kode']++;
            $selisih = abs($hitung[$kode] - $harga);
            if ($selisih <= max(150, $harga * 0.01)) {
                $hasil['cocok']++;
            } else {
                $hasil['selisih']++;
            }
        }
        return $hasil;
    }

    /**
     * Biaya non-gedung dari sheet "Prcn dan pgws" dan "ADMINISTRASI".
     * Baris sumber impor lama diganti segar; baris manual pengguna utuh.
     * Mengembalikan [baris, total].
     */
    private function imporBiayaLain(PembacaXlsxMentah $p, array $pilihan = []): array
    {
        $this->db->table('biaya_lain')->where(['sekolah_id' => $this->sid, 'sumber' => 'impor'])->delete();
        $hasil = ['baris' => 0, 'total' => 0.0];
        $sisip = function (array $r) use (&$hasil) {
            $r['sekolah_id'] = $this->sid;
            $r['sumber'] = 'impor';
            $r['created_at'] = date('Y-m-d H:i:s');
            $r['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('biaya_lain')->insert($r);
            $hasil['baris']++;
            $hasil['total'] += (float) $r['jumlah'];
        };
        $cariSheet = function (string $target) use ($p) {
            foreach ($p->daftarSheet() as $n) {
                if (self::normal($n) === $target) {
                    return $n;
                }
            }
            return null;
        };

        // --- Perencanaan & Pengawasan ---
        $sheetPrcn = $cariSheet('prcn dan pgws');
        if ($sheetPrcn !== null && $this->tujuan($pilihan, $sheetPrcn, 'non_gedung') !== 'non_gedung') {
            $sheetPrcn = null;
        }
        if ($sheetPrcn !== null) {
            $nilaiKonstruksi = 0.0;
            foreach ($this->baris($p, $sheetPrcn, 12) as $sel) {
                foreach ($sel as $v) {
                    if (self::teks($v) === 'Nilai Konstruksi') {
                        foreach ($sel as $v2) {
                            $nilaiKonstruksi = max($nilaiKonstruksi, self::num($v2));
                        }
                    }
                }
                $uraian = self::teks($sel[3] ?? null);
                if (! in_array(strtolower($uraian), ['perencana', 'pengawas'], true)) {
                    continue;
                }
                $volume = self::num($sel[5] ?? null) ?: 1;
                $periode = self::num($sel[7] ?? null) ?: 1;
                $harga = self::num($sel[9] ?? null);
                $jumlah = self::num($sel[10] ?? null);
                if ($jumlah <= 0) {
                    $jumlah = $volume * $periode * $harga;
                }
                $persen = self::num($sel[11] ?? null);
                $ket = null;
                if ($persen > 0 && $nilaiKonstruksi > 0) {
                    $ket = rtrim(rtrim(number_format($persen * 100, 2, ',', '.'), '0'), ',')
                        . '% dari Nilai Konstruksi Rp ' . number_format($nilaiKonstruksi, 0, ',', '.');
                }
                $sisip([
                    'kelompok' => 'Perencanaan & Pengawasan',
                    'uraian' => $uraian,
                    'volume' => $volume,
                    'satuan' => self::teks($sel[6] ?? null) ?: null,
                    'periode' => $periode,
                    'satuan_periode' => self::teks($sel[8] ?? null) ?: null,
                    'harga_satuan' => $harga,
                    'jumlah' => $jumlah,
                    'keterangan' => $ket,
                ]);
            }
        }

        // --- Biaya Pengelolaan (ADMINISTRASI) ---
        $sheetAdm = $cariSheet('administrasi');
        if ($sheetAdm !== null && $this->tujuan($pilihan, $sheetAdm, 'non_gedung') !== 'non_gedung') {
            $sheetAdm = null;
        }
        if ($sheetAdm !== null) {
            $sub = '';
            foreach ($this->baris($p, $sheetAdm, 12) as $sel) {
                $label3 = self::teks($sel[3] ?? null);
                $uraian4 = self::teks($sel[4] ?? null);
                $adaAngka = isset($sel[5]) || isset($sel[9]) || isset($sel[10]);
                // Baris judul kelompok: label di kolom 3 (bukan '-') atau
                // label di kolom 4 tanpa angka sama sekali (cth. Honor P2SP).
                if (($label3 !== '' && $label3 !== '-' && $uraian4 === '') || ($uraian4 !== '' && ! $adaAngka)) {
                    $sub = $uraian4 !== '' && ! $adaAngka ? $uraian4 : $label3;
                    continue;
                }
                if ($uraian4 === '' || ! $adaAngka) {
                    continue;
                }
                $volume = self::num($sel[5] ?? null);
                $periode = isset($sel[7]) ? self::num($sel[7]) : null;
                $harga = self::num($sel[9] ?? null);
                $jumlah = self::num($sel[10] ?? null);
                if (! isset($sel[10])) {
                    $jumlah = $volume * ($periode !== null && $periode > 0 ? $periode : 1) * $harga;
                }
                $sisip([
                    'kelompok' => 'Biaya Pengelolaan',
                    'uraian' => ($sub !== '' && strcasecmp($sub, $uraian4) !== 0) ? $sub . ' — ' . $uraian4 : $uraian4,
                    'volume' => $volume,
                    'satuan' => self::teks($sel[6] ?? null) ?: null,
                    'periode' => $periode,
                    'satuan_periode' => self::teks($sel[8] ?? null) ?: null,
                    'harga_satuan' => $harga,
                    'jumlah' => $jumlah,
                    'keterangan' => null,
                ]);
            }
        }
        return $hasil;
    }

    /**
     * Pratinjau berkas untuk tahap peninjauan impor: daftar sheet dengan
     * jenis terdeteksi, saran tujuan, jumlah baris data, dan total nilai.
     * Murni membaca berkas — tidak menyentuh database.
     */
    public function pratinjau(string $path): array
    {
        $p = new PembacaXlsxMentah($path);
        $hasil = [];
        foreach ($p->daftarSheet() as $nama) {
            $norm = self::normal($nama);
            $e = ['nama' => $nama, 'jenis' => 'Tidak dikenal', 'kategori' => 'lainnya',
                  'saran' => 'lewati', 'baris' => 0, 'total' => 0.0];
            if (in_array($norm, ['harga bahan & upah', 'harga bahan & upah 2'], true)) {
                $e['jenis'] = 'Daftar Harga Bahan & Upah'; $e['kategori'] = 'referensi'; $e['saran'] = 'referensi';
            } elseif (in_array($norm, ['analisa', 'analisa 2', 'analisa (2)'], true)) {
                $e['jenis'] = 'Analisa AHSP'; $e['kategori'] = 'referensi'; $e['saran'] = 'referensi';
            } elseif ($norm === 'harsat pekerjaan') {
                $e['jenis'] = 'Harsat Pekerjaan'; $e['kategori'] = 'referensi'; $e['saran'] = 'referensi';
            } elseif ($norm === 'rekap rab per ruang') {
                $e['jenis'] = 'Rekap RAB (pagu anggaran)'; $e['kategori'] = 'referensi'; $e['saran'] = 'referensi';
            } elseif ($norm === 'input data') {
                $e['jenis'] = 'Data Sekolah'; $e['kategori'] = 'referensi'; $e['saran'] = 'referensi';
            } elseif ($norm === 'prcn dan pgws' || $norm === 'administrasi') {
                $e['jenis'] = $norm === 'prcn dan pgws' ? 'Biaya Perencanaan & Pengawasan' : 'Biaya Pengelolaan';
                $e['kategori'] = 'non_gedung'; $e['saran'] = 'non_gedung';
                foreach ($this->baris($p, $nama, 12) as $sel) {
                    $adaTotal = false;
                    foreach ($sel as $v) {
                        if (strcasecmp(self::teks($v), 'Total') === 0) { $adaTotal = true; }
                    }
                    if ($adaTotal || ! isset($sel[10])) {
                        continue;
                    }
                    if ($norm === 'prcn dan pgws' && ! in_array(strtolower(self::teks($sel[3] ?? null)), ['perencana', 'pengawas'], true)) {
                        continue;
                    }
                    if ($norm === 'administrasi' && self::teks($sel[4] ?? null) === '') {
                        continue;
                    }
                    $e['baris']++;
                    $e['total'] += self::num($sel[10]);
                }
            } elseif ($this->sheetDilewati($nama)) {
                $e['jenis'] = 'Lembar kerja / gambar / rekap lain';
            } else {
                $boq = $this->bacaBoq($p, $nama);
                if ($boq !== null) {
                    $e['jenis'] = 'BOQ Gedung'; $e['kategori'] = 'gedung'; $e['saran'] = 'gedung';
                    $e['baris'] = count($boq['items']);
                    $e['total'] = array_sum(array_column($boq['items'], 'jumlah'));
                }
            }
            $hasil[] = $e;
        }
        return $hasil;
    }

    /**
     * Pembaca baris biaya UMUM untuk sheet yang dipetakan pengguna secara
     * eksplisit (bukan BOQ baku): judul "Uraian Pekerjaan" + "Jumlah Harga"
     * tanpa syarat kolom Analisa. Uraian dicari di kolom judul s.d. +2
     * (sheet ADMINISTRASI menaruh uraian rinci dua kolom lebih kanan).
     */
    private function bacaItemUmum(PembacaXlsxMentah $p, string $sheet): ?array
    {
        $kolom = null;
        $items = [];
        $grand = 0.0;
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
                if (isset($peta['uraian pekerjaan'], $peta['jumlah harga'])) {
                    $kolom = [
                        'uraian' => $peta['uraian pekerjaan'],
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
                break;
            }
            $nama = '';
            foreach ([$kolom['uraian'], $kolom['uraian'] + 1, $kolom['uraian'] + 2] as $k) {
                $t = self::teks($sel[$k] ?? '');
                if ($t !== '' && $t !== '-' && ! is_numeric($t)) {
                    $nama = $t;
                    break;
                }
            }
            $volume = $kolom['volume'] !== null ? self::num($sel[$kolom['volume']] ?? null) : 0.0;
            $harga = $kolom['harga'] !== null ? self::num($sel[$kolom['harga']] ?? null) : 0.0;
            $jumlah = self::num($sel[$kolom['jumlah']] ?? null);
            if ($nama === '' || strcasecmp($nama, 'Total') === 0) {
                continue;
            }
            if ($jumlah <= 0 && $harga <= 0) {
                continue; // baris judul kelompok / sub-bab
            }
            if ($jumlah <= 0 && $volume > 0 && $harga > 0) {
                $jumlah = $volume * $harga;
            }
            $urut++;
            $items[] = [
                'bab' => '', 'nama' => $nama, 'kode_analisa' => null,
                'volume' => $volume > 0 ? $volume : 1,
                'satuan' => $kolom['sat'] !== null ? self::teks($sel[$kolom['sat']] ?? '') : '',
                'harga_satuan' => $harga, 'jumlah' => $jumlah, 'urutan' => $urut,
            ];
        }
        if ($kolom === null || $items === []) {
            return null;
        }
        return ['items' => $items, 'grand' => $grand];
    }

    /** Tulis baris-baris sebuah sheet sebagai biaya non-gedung. [baris, total] */
    private function boqKeBiayaLain(string $kelompok, array $items): array
    {
        $n = 0;
        $total = 0.0;
        foreach ($items as $it) {
            $this->db->table('biaya_lain')->insert([
                'sekolah_id' => $this->sid,
                'kelompok' => $kelompok,
                'uraian' => $it['nama'],
                'volume' => $it['volume'],
                'satuan' => $it['satuan'] ?: null,
                'periode' => null,
                'satuan_periode' => null,
                'harga_satuan' => $it['harga_satuan'],
                'jumlah' => $it['jumlah'],
                'keterangan' => ($it['bab'] ?? '') !== '' ? $it['bab'] : null,
                'sumber' => 'impor',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $n++;
            $total += (float) $it['jumlah'];
        }
        return [$n, $total];
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
                    $bahanId = null;
                    // Harga satuan komponen dari sheet Analisa adalah acuan utama
                    // (per satuan komponen); untuk upah, master menjadi cadangan
                    // bila harga di sheet kosong/0 (mis. Mandor).
                    $tarif = ($k['harga'] ?? 0) > 0 ? (float) $k['harga'] : null;
                    if ($k['jenis'] === 'bahan') {
                        $bahanId = $petaBahan[self::normal($k['nama'])] ?? $petaBahan[self::normalDasar($k['nama'])] ?? null;
                        if ($bahanId === null) {
                            $nTak++;
                        }
                    } elseif ($k['jenis'] === 'upah' && $tarif === null) {
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
                    'harga' => self::num($sel[$kolKoef + 2] ?? null),
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

    private function simpanGedung(string $namaSheet, array $boq, ?string $namaTampil = null): int
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
            $ubah = ['nilai_boq' => $total, 'updated_at' => date('Y-m-d H:i:s')];
            if ($namaTampil !== null && trim($namaTampil) !== '') {
                $ubah['nama'] = trim($namaTampil);
            }
            $db->table('gedung')->where('id', $gid)->update($ubah);
            $db->table('pekerjaan')->where('gedung_id', $gid)->delete();
        } else {
            $urut = (int) ($db->query('SELECT COALESCE(MAX(urutan),0) m FROM gedung WHERE sekolah_id = ?', [$this->sid])->getRow()->m ?? 0) + 1;
            $db->table('gedung')->insert([
                'sekolah_id' => $this->sid, 'nama' => ($namaTampil !== null && trim($namaTampil) !== '') ? trim($namaTampil) : $namaSheet, 'nilai_boq' => $total,
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
