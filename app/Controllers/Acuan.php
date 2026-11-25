<?php

namespace App\Controllers;

use App\Libraries\BandingAcuan;

/**
 * Acuan AHSP per sekolah — perbandingan koefisien analisa sekolah aktif
 * (dokumen perencana) terhadap set acuan MILIK sekolah itu (migrasi
 * 000029; disalin dari acuan nasional SE Dirjen Bina Konstruksi
 * 68/SE/Dk/2024, Lampiran VI; dasar Permen PUPR 8/2023). Setiap sekolah
 * punya salinannya sendiri; baris sekolah_id NULL adalah templat nasional
 * sumber kloning dan tidak ditampilkan di sini.
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

    /** Kolom sekolah_id tersedia sesudah migrasi 000029 dijalankan. */
    private function perSekolah(): bool
    {
        return $this->acuanAda()
            && in_array('sekolah_id', $this->db()->getFieldNames('ahsp_acuan'), true);
    }

    /** Siapkan set acuan sekolah aktif dari templat nasional (kloning). */
    public function siapkan()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        if (! $this->perSekolah()) {
            return redirect()->to('acuan')->with('galat', 'Acuan per sekolah belum tersedia — jalankan php spark migrate lebih dulu.');
        }
        $n = \App\Libraries\PengimporAcuan::kloningKeSekolah($this->db(), $this->sid());
        $this->catatAudit('ahsp_acuan', null, 'siapkan_dari_nasional', $n . ' item acuan dikloning dari templat nasional ke sekolah aktif.');
        return redirect()->to('acuan')->with('sukses', $n > 0
            ? 'Set acuan sekolah ini disiapkan dari acuan nasional: ' . $n . ' item terkloning.'
            : 'Set acuan sekolah ini sudah lengkap — tidak ada item baru dari templat nasional.');
    }

    public function index()
    {
        $db = $this->db();
        $daftar = [];
        $adaTemplat = false;
        if ($this->acuanAda()) {
            $sid = $this->sid();
            $q = $db->table('ahsp_acuan')->where('is_deleted', 0);
            if ($this->perSekolah()) {
                $q->where('sekolah_id', $sid);
                $adaTemplat = $db->table('ahsp_acuan')->where('sekolah_id', null)->where('is_deleted', 0)->countAllResults() > 0;
            }
            foreach ($q->orderBy('kode_kita')->get()->getResultArray() as $ac) {
                $analisa = $db->table('analisa')->where('sekolah_id', $sid)->where('kode', $ac['kode_kita'])
                    ->where('is_deleted', 0)->get()->getRowArray();
                $ringkas = null;
                if ($analisa && ! $ac['tanpa_padanan']) {
                    $ringkas = $this->susunBanding($ac, $analisa);
                }
                $daftar[] = ['acuan' => $ac, 'analisa' => $analisa, 'ringkas' => $ringkas];
            }
        }
        return $this->tampil('acuan/index', [
            'daftar' => $daftar, 'ada' => $this->acuanAda(), 'perSekolah' => $this->perSekolah(),
            'adaTemplat' => $adaTemplat, 'sekolah' => $this->sekolahAktif(),
        ], 'Acuan AHSP');
    }

    public function detail($id)
    {
        if (! $this->acuanAda()) {
            return redirect()->to('acuan')->with('galat', 'Master acuan belum dimuat.');
        }
        $db = $this->db();
        $q = $db->table('ahsp_acuan')->where('id', (int) $id)->where('is_deleted', 0);
        if ($this->perSekolah()) {
            $q->where('sekolah_id', $this->sid());
        }
        $ac = $q->get()->getRowArray();
        if (! $ac) {
            return redirect()->to('acuan')->with('galat', 'Item acuan tidak ditemukan di set acuan sekolah aktif.');
        }
        $analisa = $db->table('analisa')->where('sekolah_id', $this->sid())->where('kode', $ac['kode_kita'])
            ->where('is_deleted', 0)->get()->getRowArray();
        $banding = $analisa && ! $ac['tanpa_padanan'] ? $this->susunBanding($ac, $analisa) : null;
        return $this->tampil('acuan/detail', ['acuan' => $ac, 'analisa' => $analisa, 'banding' => $banding],
            'Acuan ' . $ac['kode_kita'] . ' — Acuan AHSP');
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
            $g = BandingAcuan::grupKanon((string) $k['nama'], (string) $k['jenis']);
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
            $g = BandingAcuan::grupKanon((string) $k['nama'], (string) $k['jenis']);
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
                    $k1 = BandingAcuan::konversiKoef($g, (float) $k['koefisien'], (string) $k['satuan'], $kk['satuan']);
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

}
