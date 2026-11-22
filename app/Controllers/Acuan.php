<?php

namespace App\Controllers;

/**
 * Master Acuan AHSP Nasional — perbandingan koefisien analisa sekolah aktif
 * (dokumen perencana) terhadap acuan nasional (SE Dirjen Bina Konstruksi
 * 68/SE/Dk/2024, Lampiran VI; dasar Permen PUPR 8/2023).
 *
 * Pemetaan lewat kode analisa: baris ahsp_acuan.kode_kita dicocokkan dengan
 * analisa.kode pada sekolah aktif. Yang dibandingkan adalah KOEFISIEN;
 * harga acuan dihitung ulang dengan tarif komponen sekolah sendiri.
 * Kepala Tukang & Mandor pada acuan bertarif Rp 0 (sudah dihilangkan dari
 * aplikasi, keputusan 22 Nov 2026).
 */
class Acuan extends BaseController
{
    private function acuanAda(): bool
    {
        return $this->db()->tableExists('ahsp_acuan');
    }

    public function index()
    {
        $db = $this->db();
        $daftar = [];
        if ($this->acuanAda()) {
            $sid = $this->sid();
            foreach ($db->table('ahsp_acuan')->where('is_deleted', 0)->orderBy('kode_kita')->get()->getResultArray() as $ac) {
                $analisa = $db->table('analisa')->where('sekolah_id', $sid)->where('kode', $ac['kode_kita'])
                    ->where('is_deleted', 0)->get()->getRowArray();
                $ringkas = null;
                if ($analisa && ! $ac['tanpa_padanan']) {
                    $ringkas = $this->susunBanding($ac, $analisa);
                }
                $daftar[] = ['acuan' => $ac, 'analisa' => $analisa, 'ringkas' => $ringkas];
            }
        }
        return $this->tampil('acuan/index', ['daftar' => $daftar, 'ada' => $this->acuanAda()], 'Master Acuan AHSP Nasional');
    }

    public function detail($id)
    {
        if (! $this->acuanAda()) {
            return redirect()->to('acuan')->with('galat', 'Master acuan belum dimuat.');
        }
        $db = $this->db();
        $ac = $db->table('ahsp_acuan')->where('id', (int) $id)->where('is_deleted', 0)->get()->getRowArray();
        if (! $ac) {
            return redirect()->to('acuan')->with('galat', 'Item acuan tidak ditemukan.');
        }
        $analisa = $db->table('analisa')->where('sekolah_id', $this->sid())->where('kode', $ac['kode_kita'])
            ->where('is_deleted', 0)->get()->getRowArray();
        $banding = $analisa && ! $ac['tanpa_padanan'] ? $this->susunBanding($ac, $analisa) : null;
        return $this->tampil('acuan/detail', ['acuan' => $ac, 'analisa' => $analisa, 'banding' => $banding],
            'Acuan ' . $ac['kode_kita'] . ' vs AHSP Nasional');
    }

    /**
     * Susun perbandingan per grup komponen untuk satu pasangan analisa-acuan.
     * Mengembalikan baris-baris grup + harga acuan + jumlah menyimpang (>25%).
     */
    private function susunBanding(array $ac, array $analisa): array
    {
        $db = $this->db();
        $gk = [];
        foreach ($db->table('analisa_komponen')->where('analisa_id', (int) $analisa['id'])->where('is_deleted', 0)->get()->getResultArray() as $k) {
            $g = $this->grupKanon((string) $k['nama'], (string) $k['jenis']);
            if (isset($gk[$g])) {
                $gk[$g]['koef'] += (float) $k['koefisien'];
            } else {
                $gk[$g] = ['nama' => (string) $k['nama'], 'satuan' => (string) $k['satuan'],
                           'koef' => (float) $k['koefisien'], 'tarif' => (float) $k['tarif']];
            }
        }
        $ga = [];
        foreach ($db->table('ahsp_acuan_komponen')->where('acuan_id', (int) $ac['id'])->where('is_deleted', 0)->get()->getResultArray() as $k) {
            $g = $this->grupKanon((string) $k['nama'], (string) $k['jenis']);
            $ga[$g][] = $k;
        }
        $baris = [];
        $hargaAcuan = 0.0;
        $menyimpang = 0;
        $terpakai = [];
        foreach ($ga as $g => $daftar) {
            $namaA = implode(' + ', array_map(static fn ($k) => (string) $k['nama'], $daftar));
            $kk = $gk[$g] ?? null;
            if ($kk !== null) {
                $terpakai[$g] = true;
                $kv = 0.0;
                $gagal = false;
                foreach ($daftar as $k) {
                    $k1 = $this->konversiKoef($g, (float) $k['koefisien'], (string) $k['satuan'], $kk['satuan']);
                    if ($k1 === null) {
                        $gagal = true;
                        break;
                    }
                    $kv += $k1;
                }
                $selisih = (! $gagal && $kv != 0.0) ? ($kk['koef'] - $kv) / $kv * 100 : null;
                if ($selisih !== null && abs($selisih) > 25) {
                    $menyimpang++;
                }
                if (! $gagal && ! in_array($g, ['kepala', 'mandor'], true)) {
                    $hargaAcuan += $kv * $kk['tarif'];
                }
                $baris[] = ['nama' => $namaA, 'satuan' => $kk['satuan'], 'kita' => $kk['koef'],
                            'acuan' => $gagal ? null : $kv, 'selisih' => $selisih];
            } else {
                $baris[] = ['nama' => $namaA, 'satuan' => (string) $daftar[0]['satuan'], 'kita' => null,
                            'acuan' => count($daftar) === 1 ? (float) $daftar[0]['koefisien'] : null, 'selisih' => null];
            }
        }
        foreach ($gk as $g => $kk) {
            if (! isset($terpakai[$g])) {
                $baris[] = ['nama' => $kk['nama'] . ' (hanya di analisa kita)', 'satuan' => $kk['satuan'],
                            'kita' => $kk['koef'], 'acuan' => null, 'selisih' => null];
            }
        }
        $hargaKita = (float) ($analisa['harga_satuan'] ?? 0);
        return ['baris' => $baris, 'harga_kita' => $hargaKita, 'harga_acuan' => $hargaAcuan,
                'selisih_harga' => $hargaKita > 0 ? ($hargaAcuan - $hargaKita) / $hargaKita * 100 : null,
                'menyimpang' => $menyimpang];
    }

    /** Grup kanonik komponen agar nama perencana dan nama nasional bisa dipasangkan. */
    private function grupKanon(string $nama, string $jenis): string
    {
        $n = strtolower($nama);
        if ($jenis === 'upah') {
            if (str_contains($n, 'pekerja')) {
                return 'pekerja';
            }
            if (str_contains($n, 'kepala')) {
                return 'kepala';
            }
            if (str_contains($n, 'mandor')) {
                return 'mandor';
            }
            if (str_contains($n, 'tukang')) {
                return 'tukang';
            }
            return 'upah_lain';
        }
        if (str_contains($n, 'semen warna')) {
            return 'semen_warna';
        }
        if (str_contains($n, 'semen')) {
            return 'semen';
        }
        if (str_contains($n, 'pasir')) {
            return 'pasir';
        }
        if (str_contains($n, 'kerikil') || str_contains($n, 'split')) {
            return 'kerikil';
        }
        if (str_contains($n, 'batu')) {
            return 'batu';
        }
        if (str_contains($n, 'bata')) {
            return 'bata';
        }
        if (str_contains($n, 'besi beton') || str_contains($n, 'besi polos') || str_contains($n, 'besi ulir') || str_contains($n, 'baja tulangan')) {
            return 'besi';
        }
        if (str_contains($n, 'kawat beton') || str_contains($n, 'kawat tali') || str_contains($n, 'kawat bendrat')) {
            return 'kawat';
        }
        if (str_contains($n, 'kawat las')) {
            return 'kawat_las';
        }
        if (str_starts_with($n, 'lem')) {
            return 'lem';
        }
        if (str_contains($n, 'dolken')) {
            return 'dolken';
        }
        if (str_contains($n, 'plywood') || str_contains($n, 'triplek') || str_contains($n, 'multiplek')) {
            return 'plywood';
        }
        if (str_contains($n, 'minyak')) {
            return 'minyak';
        }
        if (str_contains($n, 'kayu') || str_contains($n, 'papan') || str_contains($n, 'balok') || str_contains($n, 'kaso')) {
            return 'kayu';
        }
        if (str_contains($n, 'fischer') || str_contains($n, 'fisher')) {
            return 'fischer';
        }
        if (str_contains($n, 'klem')) {
            return 'klem';
        }
        if (str_contains($n, 'tedoos') || str_contains($n, 'inbouwdoos') || str_contains($n, 'doos')) {
            return 'tdus';
        }
        if (str_contains($n, 'sochet')) {
            return 'socket';
        }
        if (str_contains($n, 'paku sekrup') || str_contains($n, 'sekrup')) {
            return 'paku_sekrup';
        }
        if (str_contains($n, 'paku')) {
            return 'paku';
        }
        if (trim($n) === 'air' || str_starts_with($n, 'air ')) {
            return 'air';
        }
        if (str_contains($n, 'meni')) {
            return 'meni';
        }
        if (str_contains($n, 'plamur') || str_contains($n, 'plamir')) {
            return 'plamur';
        }
        if (str_contains($n, 'cat dasar')) {
            return 'cat_dasar';
        }
        if (str_contains($n, 'cat')) {
            return 'cat_penutup';
        }
        if (str_contains($n, 'kuas') || str_contains($n, 'kwas')) {
            return 'kuas';
        }
        if (str_contains($n, 'pengencer') || str_contains($n, 'thinner') || str_contains($n, 'tiner')) {
            return 'pengencer';
        }
        if (str_contains($n, 'ampelas') || str_contains($n, 'amplas')) {
            return 'ampelas';
        }
        if (str_contains($n, 'keramik')) {
            return 'keramik';
        }
        if (str_contains($n, 'hollow')) {
            return 'hollow';
        }
        if (str_contains($n, 'baja ringan') && ! str_contains($n, 'reng')) {
            return 'baja_ringan';
        }
        if (str_contains($n, 'reng')) {
            return 'reng';
        }
        if (str_contains($n, 'rivet') || str_contains($n, 'keling')) {
            return 'rivet';
        }
        if (str_contains($n, 'nok')) {
            return 'nok';
        }
        if (str_contains($n, 'kaca')) {
            return 'kaca';
        }
        if (str_contains($n, 'sealant')) {
            return 'sealant';
        }
        if (str_contains($n, 'engsel')) {
            return 'engsel';
        }
        if (str_contains($n, 'grendel') || str_contains($n, 'sloot') || str_contains($n, 'slot')) {
            return 'grendel';
        }
        if (str_contains($n, 'kunci')) {
            return 'kunci';
        }
        if (str_contains($n, 'kait')) {
            return 'kait';
        }
        if (str_contains($n, 'handle') || str_contains($n, 'spring')) {
            return 'handle';
        }
        if (str_contains($n, 'saklar')) {
            return 'saklar';
        }
        if (str_contains($n, 'stop kontak')) {
            return 'stopkontak';
        }
        if (str_contains($n, 'socket') || str_contains($n, 'soket')) {
            return 'socket';
        }
        if (str_contains($n, 'kabel')) {
            return 'kabel';
        }
        if (str_contains($n, 'conduit') || str_contains($n, 'konduit') || str_contains($n, 'pipa')) {
            return 'conduit';
        }
        if (str_contains($n, 't dus') || str_contains($n, 'tdus') || str_contains($n, 'dus')) {
            return 'tdus';
        }
        if (str_contains($n, 'elbow') || str_contains($n, 'knie')) {
            return 'elbow';
        }
        if (str_contains($n, 'isolasi')) {
            return 'isolasi';
        }
        if (str_contains($n, 'lasdop')) {
            return 'lasdop';
        }
        if (str_contains($n, 'lisplang') || str_contains($n, 'lisplank')) {
            return 'lisplank';
        }
        if (str_contains($n, 'spandek') || str_contains($n, 'galval') || str_contains($n, 'atap metal') || str_contains($n, 'zincalum')) {
            return 'atap';
        }
        if (str_contains($n, 'waterpass')) {
            return 'waterpass';
        }
        if (str_contains($n, 'tanah')) {
            return 'tanah';
        }
        return 'lain_' . substr((string) preg_replace('/[^a-z0-9]+/', '_', $n), 0, 24);
    }

    private function normSat(string $s): string
    {
        $s = str_replace(['³', '²', '¹'], ['3', '2', ''], strtolower(trim($s)));
        return ['bh' => 'buah', 'lbr' => 'lembar', 'btg' => 'batang', "m'" => 'm', 'org' => 'oh'][$s] ?? $s;
    }

    /** Konversi koefisien acuan ke satuan komponen kita (agregat lepas: pasir 1.400 kg/m3, kerikil 1.600 kg/m3 — selaras faktorSatuan bawaan). */
    private function konversiKoef(string $grup, float $nilai, string $dari, string $ke): ?float
    {
        $dari = $this->normSat($dari);
        $ke = $this->normSat($ke);
        if ($dari === $ke) {
            return $nilai;
        }
        $faktor = [
            'pasir'   => ['m3>kg' => 1400.0, 'kg>m3' => 1 / 1400],
            'kerikil' => ['m3>kg' => 1600.0, 'kg>m3' => 1 / 1600],
        ];
        $f = $faktor[$grup][$dari . '>' . $ke] ?? null;
        return $f === null ? null : $nilai * $f;
    }
}
