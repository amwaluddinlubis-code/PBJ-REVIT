<?php

namespace App\Libraries;

use Config\Database;

/**
 * Importir TEMPLATE MANUAL (5 sheet: Petunjuk, Sekolah, Harga, Analisa, BOQ).
 *
 * Format datar satu baris per komponen/pekerjaan, dibaca mulai baris 5
 * (baris 4 = judul kolom). Baris yang kolom pertamanya diawali "CONTOH"
 * dilewati. Semantik sama dengan ParserPerencana: analisa diganti per kode,
 * pekerjaan diganti per gedung, harga master bahan tidak ditimpa.
 */
class ParserTemplate
{
    private $db;
    private int $sid;

    public function __construct(int $sekolahId)
    {
        $this->db = Database::connect();
        $this->sid = $sekolahId;
    }

    /** Apakah berkas ini template manual? (punya 4 sheet data khas) */
    public static function berkasTemplate(PembacaXlsxMentah $p): bool
    {
        $punya = 0;
        foreach (['Sekolah', 'Harga', 'Analisa', 'BOQ'] as $s) {
            if ($p->adaSheet($s)) {
                $punya++;
            }
        }
        return $punya >= 3 && !$p->adaSheet('Rekap RAB per ruang ');
    }

    public function impor(string $path): array
    {
        $p = new PembacaXlsxMentah($path);
        $stat = ['bahan_baru' => 0, 'bahan_diperbarui' => 0, 'upah' => 0, 'analisa' => 0,
            'komponen' => 0, 'tak_tertaut' => 0, 'gedung' => 0, 'pekerjaan' => 0, 'nilai_boq' => 0.0];
        $this->imporSekolah($p);
        $this->imporHarga($p, $stat);
        $petaBahan = $this->petaBahan();
        $petaUpah = $this->petaUpah();
        $petaHarsat = $this->bacaHarsat($p);
        $this->imporAnalisa($p, $stat, $petaBahan, $petaUpah, $petaHarsat);
        $this->imporBoq($p, $stat, $petaHarsat);
        return $stat;
    }

    // ---------- Sekolah ----------
    private function imporSekolah(PembacaXlsxMentah $p): void
    {
        if (!$p->adaSheet('Sekolah')) {
            return;
        }
        $peta = [
            'nama sekolah' => 'nama_sekolah', 'npsn' => 'npsn', 'tahun anggaran' => 'tahun_anggaran',
            'total anggaran' => 'total_anggaran', 'pagu' => 'total_anggaran', 'alamat' => 'alamat',
            'desa' => 'desa', 'kelurahan' => 'desa', 'kecamatan' => 'kecamatan', 'kabupaten' => 'kabupaten',
            'kepala sekolah' => 'kepsek_nama', 'nip kepala sekolah' => 'kepsek_nip',
        ];
        $data = [];
        foreach ($p->baris('Sekolah', 3) as $sel) {
            $label = ParserPerencana::normal((string) ($sel[0] ?? ''));
            $nilai = trim((string) ($sel[2] ?? ''));
            if ($label === '' || $nilai === '') {
                continue;
            }
            foreach ($peta as $pola => $kolom) {
                if (str_contains($label, $pola)) {
                    $data[$kolom] = $kolom === 'total_anggaran' ? (float) $nilai : $nilai;
                    break;
                }
            }
        }
        if ($data) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('sekolah')->where('id', $this->sid)->update($data);
        }
    }

    // ---------- Harga ----------
    private function imporHarga(PembacaXlsxMentah $p, array &$stat): void
    {
        if (!$p->adaSheet('Harga')) {
            return;
        }
        $kodeBerikut = $this->kodeBahanBerikut();
        foreach ($this->barisData($p, 'Harga', 5, 'kode') as $sel) {
            $kode = strtoupper(trim((string) ($sel[0] ?? '')));
            $jenis = strtolower(trim((string) ($sel[1] ?? '')));
            $nama = trim((string) ($sel[2] ?? ''));
            $satuan = trim((string) ($sel[3] ?? '')) ?: '-';
            $harga = (float) ($sel[4] ?? 0);
            if ($nama === '' || str_starts_with($kode, 'CONTOH') || str_starts_with(strtoupper($nama), 'CONTOH')) {
                continue;
            }
            if (str_contains($jenis, 'upah')) {
                $ada = $this->db->table('upah_master')->where(['sekolah_id' => $this->sid, 'jenis' => $nama, 'is_deleted' => 0])->get()->getRowArray();
                if ($ada) {
                    if ($harga > 0) {
                        $this->db->table('upah_master')->where('id', $ada['id'])->update(['tarif' => $harga, 'satuan' => $satuan]);
                    }
                } else {
                    $this->db->table('upah_master')->insert(['sekolah_id' => $this->sid, 'jenis' => $nama, 'satuan' => $satuan, 'tarif' => $harga]);
                }
                $stat['upah']++;
                continue;
            }
            $ada = null;
            if ($kode !== '') {
                $ada = $this->db->table('bahan')->where(['sekolah_id' => $this->sid, 'kode' => $kode, 'is_deleted' => 0])->get()->getRowArray();
            }
            if (!$ada) {
                $ada = $this->db->table('bahan')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])
                    ->where('LOWER(nama) =', strtolower($nama))->get()->getRowArray();
            }
            if ($ada) {
                $this->db->table('bahan')->where('id', $ada['id'])->update([
                    'nama' => $nama, 'satuan' => $satuan,
                    'harga_rab' => (int) round($harga), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $stat['bahan_diperbarui']++;
            } else {
                if ($kode === '') {
                    $kode = 'B' . str_pad((string) $kodeBerikut++, 3, '0', STR_PAD_LEFT);
                }
                $this->db->table('bahan')->insert([
                    'sekolah_id' => $this->sid, 'kode' => $kode, 'nama' => $nama, 'satuan' => $satuan,
                    'harga_rab' => (int) round($harga), 'qty_rkb' => 0, 'qty_adm' => 0, 'qty_toilet' => 0,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $stat['bahan_baru']++;
            }
        }
    }

    // ---------- Analisa ----------
    /** Peta kode analisa -> harga satuan resmi dari sheet Harsat template. */
    private function bacaHarsat(PembacaXlsxMentah $p): array
    {
        $peta = [];
        if (! $p->adaSheet('Harsat')) {
            return $peta;
        }
        foreach ($this->barisData($p, 'Harsat', 3, 'kode analisa') as $sel) {
            $kode = ParserPerencana::kanonKode(trim((string) ($sel[0] ?? '')));
            $harga = (float) ($sel[2] ?? 0);
            if ($kode !== '' && $harga > 0) {
                $peta[$kode] = $harga;
            }
        }
        return $peta;
    }

    private function imporAnalisa(PembacaXlsxMentah $p, array &$stat, array $petaBahan, array $petaUpah, array $petaHarsat = []): void
    {
        if (!$p->adaSheet('Analisa')) {
            return;
        }
        $hargaBahan = [];
        foreach ($this->db->table('bahan')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])->select('id, harga_rab')->get()->getResultArray() as $b) {
            $hargaBahan[(int) $b['id']] = (float) $b['harga_rab'];
        }
        $grup = [];
        foreach ($this->barisData($p, 'Analisa', 8, 'kode analisa') as $sel) {
            $kode = ParserPerencana::kanonKode(trim((string) ($sel[0] ?? '')));
            if ($kode === '' || str_starts_with(strtoupper((string) ($sel[0] ?? '')), 'CONTOH')) {
                continue;
            }
            $grup[$kode]['nama'] = trim((string) ($sel[1] ?? '')) ?: ($grup[$kode]['nama'] ?? $kode);
            $grup[$kode]['satuan'] = trim((string) ($sel[2] ?? '')) ?: ($grup[$kode]['satuan'] ?? null);
            $grup[$kode]['komponen'] = $grup[$kode]['komponen'] ?? [];
            $namaKomp = trim((string) ($sel[4] ?? ''));
            if ($namaKomp === '') {
                continue;
            }
            $jenisMentah = strtolower(trim((string) ($sel[3] ?? '')));
            $jenis = str_contains($jenisMentah, 'upah') ? 'upah' : (str_contains($jenisMentah, 'alat') ? 'alat' : 'bahan');
            $grup[$kode]['komponen'][] = [
                'jenis' => $jenis, 'nama' => $namaKomp,
                'koefisien' => (float) ($sel[5] ?? 0),
                'satuan' => trim((string) ($sel[6] ?? '')) ?: null,
                // Harga per satuan komponen dari dokumen analisa (opsional):
                // acuan harga komponen yang sebenarnya, per satuan komponen.
                'harga' => (float) ($sel[7] ?? 0),
            ];
        }
        foreach ($grup as $kode => $g) {
            $ada = $this->db->table('analisa')->where(['sekolah_id' => $this->sid, 'kode' => $kode, 'is_deleted' => 0])->get()->getRowArray();
            $hargaResmi = $petaHarsat[$kode] ?? null;
            if ($ada) {
                $aid = (int) $ada['id'];
                $ubah = ['nama' => $g['nama'], 'satuan' => $g['satuan']];
                if ($hargaResmi !== null) {
                    $ubah['harga_satuan'] = $hargaResmi;
                }
                $this->db->table('analisa')->where('id', $aid)->update($ubah);
                $this->db->table('analisa_komponen')->where('analisa_id', $aid)->delete();
            } else {
                $this->db->table('analisa')->insert(['sekolah_id' => $this->sid, 'kode' => $kode, 'nama' => $g['nama'], 'satuan' => $g['satuan'], 'harga_satuan' => $hargaResmi]);
                $aid = (int) $this->db->insertID();
            }
            foreach ($g['komponen'] as $k) {
                $bahanId = null;
                // Harga Komponen dari template menang; cadangan: tarif master.
                $tarif = ($k['harga'] ?? 0) > 0 ? (float) $k['harga'] : 0.0;
                if ($k['jenis'] === 'upah') {
                    if ($tarif <= 0) {
                        $tarif = $petaUpah[ParserPerencana::normal($k['nama'])] ?? 0.0;
                    }
                } else {
                    $bahanId = $petaBahan[ParserPerencana::normal($k['nama'])] ?? $petaBahan[ParserPerencana::normalDasar($k['nama'])] ?? null;
                    if ($bahanId) {
                        if ($tarif <= 0) {
                            $tarif = $hargaBahan[$bahanId] ?? 0.0;
                        }
                    } elseif ($k['jenis'] === 'bahan') {
                        $stat['tak_tertaut']++;
                    }
                }
                $this->db->table('analisa_komponen')->insert([
                    'analisa_id' => $aid, 'jenis' => $k['jenis'], 'nama' => $k['nama'],
                    'satuan' => $k['satuan'], 'koefisien' => $k['koefisien'],
                    'bahan_id' => $bahanId, 'tarif' => $tarif,
                ]);
                $stat['komponen']++;
            }
            $stat['analisa']++;
        }
    }

    // ---------- BOQ ----------
    private function imporBoq(PembacaXlsxMentah $p, array &$stat, array $petaHarsat = []): void
    {
        if (!$p->adaSheet('BOQ')) {
            return;
        }
        $hargaAnalisa = $this->hargaPerAnalisa();
        $perGedung = [];
        foreach ($this->barisData($p, 'BOQ', 7, 'gedung') as $sel) {
            $gedung = trim((string) ($sel[0] ?? ''));
            $nama = trim((string) ($sel[2] ?? ''));
            if ($gedung === '' || $nama === '' || str_starts_with(strtoupper($gedung), 'CONTOH')) {
                continue;
            }
            $kode = ParserPerencana::kanonKode(trim((string) ($sel[3] ?? '')));
            $volume = (float) ($sel[4] ?? 0);
            // Kolom Harga Satuan (opsional): bila diisi, dipakai apa adanya
            // (mis. ekspor dari dokumen resmi) — mengalahkan hitungan analisa.
            $hargaIsi = (float) ($sel[6] ?? 0);
            // Urutan acuan harga: kolom BOQ terisi -> Harsat resmi -> hitungan komponen.
            $harga = $hargaIsi > 0 ? $hargaIsi
                : ($kode !== '' ? ($petaHarsat[$kode] ?? $hargaAnalisa[$kode] ?? 0.0) : 0.0);
            $perGedung[$gedung][] = [
                'bab' => trim((string) ($sel[1] ?? '')) ?: null,
                'nama' => $nama, 'kode_analisa' => $kode !== '' ? $kode : null,
                'volume' => $volume, 'satuan' => trim((string) ($sel[5] ?? '')) ?: null,
                'harga_satuan' => $harga, 'jumlah' => $harga * $volume,
            ];
        }
        $urut = (int) ($this->db->table('gedung')->where('sekolah_id', $this->sid)->selectMax('urutan')->get()->getRow()->urutan ?? 0);
        foreach ($perGedung as $namaGedung => $items) {
            $ada = $this->db->table('gedung')->where('sekolah_id', $this->sid)
                ->where('LOWER(nama) =', strtolower($namaGedung))->get()->getRowArray();
            $total = array_sum(array_column($items, 'jumlah'));
            if ($ada) {
                $gid = (int) $ada['id'];
                $this->db->table('pekerjaan')->where('gedung_id', $gid)->delete();
                $this->db->table('gedung')->where('id', $gid)->update(['nilai_boq' => $total, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                $this->db->table('gedung')->insert([
                    'sekolah_id' => $this->sid, 'nama' => $namaGedung, 'nilai_boq' => $total,
                    'sumber_sheet' => null, 'urutan' => ++$urut,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $gid = (int) $this->db->insertID();
            }
            foreach ($items as $i => $it) {
                $this->db->table('pekerjaan')->insert([
                    'gedung_id' => $gid, 'bab' => $it['bab'], 'nama' => $it['nama'],
                    'kode_analisa' => $it['kode_analisa'], 'volume' => $it['volume'],
                    'satuan' => $it['satuan'], 'harga_satuan' => $it['harga_satuan'],
                    'jumlah' => $it['jumlah'], 'urutan' => $i + 1,
                ]);
                $stat['pekerjaan']++;
                $stat['nilai_boq'] += $it['jumlah'];
            }
            $stat['gedung']++;
        }
    }

    // ---------- Pembantu ----------
    /**
     * Baris data sesudah baris judul kolom. Judul dikenali dari isi sel
     * pertama (bukan nomor baris — pembaca mentah melewati baris kosong,
     * jadi menghitung baris akan menggeser data).
     */
    private function barisData(PembacaXlsxMentah $p, string $sheet, int $kolom, string $penanda = ''): \Generator
    {
        $lewatJudul = $penanda === '';
        foreach ($p->baris($sheet, $kolom) as $sel) {
            if (!$lewatJudul) {
                if (str_starts_with(ParserPerencana::normal((string) ($sel[0] ?? '')), $penanda)) {
                    $lewatJudul = true;
                }
                continue;
            }
            yield $sel;
        }
    }

    private function petaBahan(): array
    {
        $peta = [];
        foreach ($this->db->table('bahan')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])->get()->getResultArray() as $b) {
            $peta[ParserPerencana::normal($b['nama'])] = (int) $b['id'];
            $peta[ParserPerencana::normalDasar($b['nama'])] = (int) $b['id'];
        }
        return $peta;
    }

    private function petaUpah(): array
    {
        $peta = [];
        foreach ($this->db->table('upah_master')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])->get()->getResultArray() as $u) {
            $peta[ParserPerencana::normal($u['jenis'])] = (float) $u['tarif'];
        }
        return $peta;
    }

    private function hargaPerAnalisa(): array
    {
        // Harga satuan analisa dihitung dari komponen dengan KONVERSI SATUAN
        // yang sama seperti kebutuhan bahan (semen kg->zak, pasir kg->m3,
        // keramik bh->ktk) — tanpa konversi, nilai BOQ bisa melesat jauh.
        $harga = [];
        $q = $this->db->query(
            'SELECT a.kode, ak.jenis, ak.koefisien, ak.satuan AS satuan_komponen, ak.tarif,
                    b.nama AS nama_bahan, b.satuan AS satuan_bahan, b.harga_rab
             FROM analisa a JOIN analisa_komponen ak ON ak.analisa_id = a.id
             LEFT JOIN bahan b ON b.id = ak.bahan_id
             WHERE a.sekolah_id = ?',
            [$this->sid]
        );
        foreach ($q->getResultArray() as $r) {
            $nilai = 0.0;
            if ($r['jenis'] !== 'upah' && $r['harga_rab'] !== null) {
                $faktor = self::faktorSatuan($r['satuan_komponen'], ['nama' => $r['nama_bahan'], 'satuan' => $r['satuan_bahan']]);
                $nilai = (float) $r['koefisien'] * $faktor * (float) $r['harga_rab'];
            } else {
                $nilai = (float) $r['koefisien'] * (float) ($r['tarif'] ?? 0);
            }
            $harga[$r['kode']] = ($harga[$r['kode']] ?? 0.0) + $nilai;
        }
        return $harga;
    }

    /** Salinan aturan konversi satuan dari BaseController::faktorSatuan(). */
    private static function faktorSatuan(?string $satuanKomponen, array $bahan): float
    {
        $sk = mb_strtolower(trim((string) $satuanKomponen));
        $sb = mb_strtolower(trim((string) ($bahan['satuan'] ?? '')));
        // Dokumen perencana tidak mencantumkan satuan beli (bahan hasil impor
        // bersatuan '-'): tebak satuan beli dari nama bahan agar konversi jalan.
        if ($sb === '' || $sb === '-') {
            $nb = mb_strtolower((string) ($bahan['nama'] ?? ''));
            if (str_contains($nb, 'semen')) {
                $sb = 'zak';
            } elseif (str_contains($nb, 'pasir') || str_contains($nb, 'kerikil') || str_contains($nb, 'batu')) {
                $sb = 'm3';
            } elseif (str_contains($nb, 'keramik')) {
                $sb = 'ktk';
            }
        }
        if ($sk === 'kg') {
            if (str_contains($sb, 'zak')) {
                return 1 / 50;
            }
            if (str_starts_with($sb, 'm')) {
                return str_contains(mb_strtolower((string) $bahan['nama']), 'pasir') ? 1 / 1400 : 1 / 1600;
            }
        }
        if (in_array($sk, ['bh', 'pcs', 'buah'], true) && $sb === 'ktk'
            && str_contains(mb_strtolower((string) $bahan['nama']), 'keramik')) {
            $nama = (string) $bahan['nama'];
            if (str_contains($nama, '40')) {
                return 1 / 6;
            }
            if (str_contains($nama, '30')) {
                return 1 / 11;
            }
            if (str_contains($nama, '25')) {
                return 1 / 16;
            }
            if (str_contains($nama, '20')) {
                return 1 / 25;
            }
        }
        return 1.0;
    }

    private function kodeBahanBerikut(): int
    {
        $maks = 0;
        foreach ($this->db->table('bahan')->where(['sekolah_id' => $this->sid, 'is_deleted' => 0])->select('kode')->get()->getResultArray() as $b) {
            if (preg_match('/^B(\d+)$/', (string) $b['kode'], $m)) {
                $maks = max($maks, (int) $m[1]);
            }
        }
        return $maks + 1;
    }
}
