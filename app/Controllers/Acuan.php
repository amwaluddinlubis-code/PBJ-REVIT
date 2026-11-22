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

    /** Samakan koefisien satu grup komponen ke nilai acuan nasional. */
    public function samakan()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $acuanId = (int) $this->request->getPost('acuan_id');
        $grup = (string) $this->request->getPost('grup');
        [$ac, $analisa] = $this->pasangan($acuanId);
        if (! $ac || ! $analisa) {
            return redirect()->to('acuan')->with('galat', 'Pasangan acuan/analisa tidak ditemukan.');
        }
        $db = $this->db();
        $db->transStart();
        $ket = $this->terapkanSamakan($ac, $analisa, $grup);
        $db->transComplete();
        if ($ket === null || ! $db->transStatus()) {
            return redirect()->to('acuan/' . $acuanId)->with('galat', 'Komponen tidak dapat disamakan dari sini (grup berisi lebih dari satu komponen atau satuan tidak terkonversi) — ubah lewat modul Analisa.');
        }
        return redirect()->to('acuan/' . $acuanId)->with('sukses', 'Koefisien disamakan ke acuan nasional: ' . $ket);
    }

    /** Samakan semua grup komponen yang menyimpang dan dapat disamakan. */
    public function samakanSemua()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $acuanId = (int) $this->request->getPost('acuan_id');
        [$ac, $analisa] = $this->pasangan($acuanId);
        if (! $ac || ! $analisa) {
            return redirect()->to('acuan')->with('galat', 'Pasangan acuan/analisa tidak ditemukan.');
        }
        $db = $this->db();
        $banding = $this->susunBanding($ac, $analisa);
        $db->transStart();
        $n = 0;
        foreach ($banding['baris'] as $b) {
            if ($b['bisa'] && $this->terapkanSamakan($ac, $analisa, (string) $b['grup']) !== null) {
                $n++;
            }
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->to('acuan/' . $acuanId)->with('galat', 'Penyamaan dibatalkan karena kegagalan di tengah proses.');
        }
        return redirect()->to('acuan/' . $acuanId)->with('sukses', $n . ' koefisien komponen disamakan ke acuan nasional; harga satuan analisa dihitung ulang.');
    }

    /** @return array{0:?array, 1:?array} [baris acuan, baris analisa sekolah aktif] */
    private function pasangan(int $acuanId): array
    {
        if (! $this->acuanAda()) {
            return [null, null];
        }
        $db = $this->db();
        $ac = $db->table('ahsp_acuan')->where('id', $acuanId)->where('is_deleted', 0)->get()->getRowArray();
        if (! $ac || $ac['tanpa_padanan']) {
            return [null, null];
        }
        $analisa = $db->table('analisa')->where('sekolah_id', $this->sid())->where('kode', $ac['kode_kita'])
            ->where('is_deleted', 0)->get()->getRowArray();
        return [$ac, $analisa ?: null];
    }

    /**
     * Terapkan penyamaan satu grup: koefisien komponen tunggal pada grup itu
     * diganti nilai acuan (sudah terkonversi ke satuan komponen), harga satuan
     * analisa dihitung ulang, dan aksi dicatat di jejak audit.
     * Mengembalikan keterangan perubahan, atau null bila tidak dapat diterapkan.
     */
    private function terapkanSamakan(array $ac, array $analisa, string $grup): ?string
    {
        $banding = $this->susunBanding($ac, $analisa);
        foreach ($banding['baris'] as $b) {
            if ($b['grup'] !== $grup || ! $b['bisa']) {
                continue;
            }
            $db = $this->db();
            $komp = $db->table('analisa_komponen')->where('id', (int) $b['komp_id'])
                ->where('analisa_id', (int) $analisa['id'])->where('is_deleted', 0)->get()->getRowArray();
            if (! $komp) {
                return null;
            }
            $lama = (float) $komp['koefisien'];
            $db->table('analisa_komponen')->where('id', (int) $komp['id'])
                ->update(['koefisien' => (float) $b['target']]);
            $this->hitungUlangHargaSatuan((int) $analisa['id']);
            $ket = $komp['nama'] . ' (' . $analisa['kode'] . '): '
                . rtrim(rtrim(number_format($lama, 6, ',', '.'), '0'), ',') . ' → '
                . rtrim(rtrim(number_format((float) $b['target'], 6, ',', '.'), '0'), ',')
                . ' ' . $komp['satuan'] . ' mengikuti acuan ' . $ac['kode_nasional'];
            $this->catatAudit('analisa_komponen', (int) $komp['id'], 'samakan_ke_acuan', $ket);
            return $ket;
        }
        return null;
    }

    /** Sama seperti Analisa::hitungUlangHargaSatuan — jumlah koefisien × tarif komponen aktif. */
    private function hitungUlangHargaSatuan(int $analisaId): void
    {
        $db = $this->db();
        $total = (float) ($db->query(
            'SELECT COALESCE(SUM(koefisien * COALESCE(tarif, 0)), 0) AS t FROM analisa_komponen WHERE analisa_id = ? AND is_deleted = 0',
            [$analisaId]
        )->getRowArray()['t'] ?? 0);
        $db->table('analisa')->where('id', $analisaId)
            ->update(['harga_satuan' => $total]);
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
                $gk[$g]['ids'][] = (int) $k['id'];
            } else {
                $gk[$g] = ['nama' => (string) $k['nama'], 'satuan' => (string) $k['satuan'],
                           'koef' => (float) $k['koefisien'], 'tarif' => (float) $k['tarif'],
                           'ids' => [(int) $k['id']]];
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
                $bisa = ! $gagal && count($kk['ids']) === 1 && $selisih !== null && abs($selisih) > 0.05;
                $baris[] = ['nama' => $namaA, 'satuan' => $kk['satuan'], 'kita' => $kk['koef'],
                            'acuan' => $gagal ? null : $kv, 'selisih' => $selisih,
                            'grup' => $g, 'komp_id' => count($kk['ids']) === 1 ? $kk['ids'][0] : null,
                            'jumlah_kita' => count($kk['ids']), 'bisa' => $bisa,
                            'target' => $gagal ? null : $kv];
            } else {
                $baris[] = ['nama' => $namaA, 'satuan' => (string) $daftar[0]['satuan'], 'kita' => null,
                            'acuan' => count($daftar) === 1 ? (float) $daftar[0]['koefisien'] : null, 'selisih' => null,
                            'grup' => $g, 'komp_id' => null, 'jumlah_kita' => 0, 'bisa' => false, 'target' => null];
            }
        }
        foreach ($gk as $g => $kk) {
            if (! isset($terpakai[$g])) {
                $baris[] = ['nama' => $kk['nama'] . ' (hanya di analisa kita)', 'satuan' => $kk['satuan'],
                            'kita' => $kk['koef'], 'acuan' => null, 'selisih' => null,
                            'grup' => $g, 'komp_id' => null, 'jumlah_kita' => count($kk['ids']), 'bisa' => false, 'target' => null];
            }
        }
        $hargaKita = (float) ($analisa['harga_satuan'] ?? 0);
        return ['baris' => $baris, 'harga_kita' => $hargaKita, 'harga_acuan' => $hargaAcuan,
                'selisih_harga' => $hargaKita > 0 ? ($hargaAcuan - $hargaKita) / $hargaKita * 100 : null,
                'menyimpang' => $menyimpang,
                'bisa_count' => count(array_filter($baris, static fn ($b) => $b['bisa']))];
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
