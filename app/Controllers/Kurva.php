<?php

namespace App\Controllers;

use App\Libraries\PengimporKurvaS;

/**
 * Kurva S pelaksanaan pekerjaan (format dokumen jadwal Juknis): garis
 * rencana kumulatif dibandingkan realisasi kumulatif mingguan dari data
 * realisasi BOQ. Bila rencana resmi Time Schedule perencana sudah
 * diimpor (tabel kurva_rencana), garis rencana memakai bobot resmi itu
 * per minggu kalender proyek; bila belum, rencana digambar sebagai
 * kurva S perkiraan (cosinus) mengikuti tanggal SPK gedung.
 */
class Kurva extends BaseController
{
    public function index()
    {
        $semua = $this->semuaGedung();
        $db = $this->db();
        $adaRencana = $db->tableExists('kurva_rencana')
            && $db->table('kurva_rencana')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults() > 0;

        $pilihan = (string) ($this->request->getGet('gedung') ?? '');
        $mode = 'perkiraan';
        $g = null;
        $total = false;
        $minggu = [];
        $svg = '';
        $basis = 0.0;
        if ($adaRencana && ($pilihan === 'total' || $pilihan === '')) {
            $mode = 'resmi';
            $total = true;
            [$minggu, $svg, $basis] = $this->hitungResmi(null);
        } else {
            $gid = (int) $pilihan;
            foreach ($semua as $row) {
                if ((int) $row['id'] === $gid) {
                    $g = $row;
                    break;
                }
            }
            if (! $g && $semua !== []) {
                $g = $semua[0];
            }
            if ($g) {
                if ($adaRencana && $this->gedungPunyaRencana((int) $g['id'])) {
                    $mode = 'resmi';
                    [$minggu, $svg, $basis] = $this->hitungResmi((int) $g['id']);
                } else {
                    [$minggu, $svg] = $this->hitung($g);
                    $basis = (float) $g['nilai_boq'];
                }
            }
        }
        return $this->tampil('kurva/index', [
            'semua' => $semua, 'g' => $g, 'minggu' => $minggu, 'svg' => $svg,
            'mode' => $mode, 'total' => $total, 'adaRencana' => $adaRencana, 'basis' => $basis,
        ], 'Kurva S Pelaksanaan — Aplikasi PBJ Sekolah');
    }

    /** Impor / ganti rencana Kurva S resmi dari berkas JSON hasil ekspor perencana. */
    public function impor()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $berkas = $this->request->getFile('berkas');
        if (! $berkas || ! $berkas->isValid() || $berkas->getError() !== UPLOAD_ERR_OK) {
            return redirect()->to('kurva-s')->with('galat', 'Berkas JSON rencana Kurva S wajib dipilih.');
        }
        if (strtolower($berkas->getClientExtension()) !== 'json' || $berkas->getSize() > 2 * 1024 * 1024) {
            return redirect()->to('kurva-s')->with('galat', 'Berkas harus .json dan maksimal 2 MB.');
        }
        $data = json_decode((string) file_get_contents($berkas->getTempName()), true);
        if (! is_array($data)) {
            return redirect()->to('kurva-s')->with('galat', 'Isi berkas bukan JSON yang sah.');
        }
        try {
            $r = (new PengimporKurvaS($this->db(), $this->sid()))->impor($data);
        } catch (\Throwable $e) {
            return redirect()->to('kurva-s')->with('galat', 'Impor Kurva S gagal: ' . $e->getMessage());
        }
        $this->catatAudit('kurva_rencana', null, 'impor', $r['baris'] . ' baris bab, ' . $r['minggu'] . ' minggu, total bobot ' . number_format($r['total_bobot'], 2, ',', '.') . '%');
        $peta = [];
        foreach ($r['pemetaan'] as $grup => $gedung) {
            $peta[] = $grup . ' → ' . ($gedung ?? 'kurva total saja');
        }
        return redirect()->to('kurva-s')->with('sukses', 'Rencana Kurva S resmi terimpor: ' . $r['baris'] . ' baris bab dalam ' . $r['minggu'] . ' minggu (total bobot ' . number_format($r['total_bobot'], 2, ',', '.') . '%). Pemetaan kelompok: ' . implode('; ', $peta) . '.');
    }

    private function gedungPunyaRencana(int $gedungId): bool
    {
        return $this->db()->table('kurva_rencana')
            ->where(['sekolah_id' => $this->sid(), 'gedung_id' => $gedungId, 'is_deleted' => 0])
            ->countAllResults() > 0;
    }

    /**
     * Kurva dari rencana resmi: rencana kumulatif = bobot mingguan dokumen
     * (dinormalkan ke total bobot cakupannya); realisasi kumulatif dari
     * entri realisasi BOQ yang dikelompokkan ke rentang minggu kalender.
     *
     * @return array{0: array, 1: string, 2: float} baris mingguan, SVG, basis nilai realisasi
     */
    private function hitungResmi(?int $gedungId): array
    {
        $db = $this->db();
        $kalender = $db->table('kurva_minggu')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('minggu_ke')->get()->getResultArray();
        if ($kalender === []) {
            return [[], '', 0.0];
        }
        // Bobot mingguan per minggu_ke dalam cakupan (gedung / semua).
        $q = $db->table('kurva_rencana')->select('minggu_ke, SUM(bobot_minggu) AS bm')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0]);
        if ($gedungId !== null) {
            $q->where('gedung_id', $gedungId);
        }
        $perMinggu = [];
        foreach ($q->groupBy('minggu_ke')->get()->getResultArray() as $r) {
            $perMinggu[(int) $r['minggu_ke']] = (float) $r['bm'];
        }
        // Penyebut: total bobot per (grup, bab) dalam cakupan.
        $q2 = $db->table('kurva_rencana')->select('grup, bab, MAX(bobot) AS bt')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0]);
        if ($gedungId !== null) {
            $q2->where('gedung_id', $gedungId);
        }
        $totalBobot = 0.0;
        foreach ($q2->groupBy('grup, bab')->get()->getResultArray() as $r) {
            $totalBobot += (float) $r['bt'];
        }
        // Realisasi per tanggal dalam cakupan.
        $real = [];
        if ($gedungId !== null) {
            $real = $this->nilaiRealisasiPerTanggal($gedungId);
            $basis = $this->totalBoqGedung($gedungId);
        } else {
            $basis = $this->totalBoqAktif($this->sid());
            $biaya = $db->query(
                'SELECT COALESCE(SUM(jumlah), 0) AS t FROM biaya_lain WHERE sekolah_id = ? AND is_deleted = 0',
                [$this->sid()]
            )->getRowArray();
            $basis += (float) ($biaya['t'] ?? 0);
            foreach ($this->semuaGedungDidanai() as $g) {
                foreach ($this->nilaiRealisasiPerTanggal((int) $g['id']) as $r) {
                    $real[] = $r;
                }
            }
        }
        $minggu = [];
        $titikRencana = [[0, 0]];
        $titikRealisasi = [[0, 0]];
        $n = count($kalender);
        $kumBobot = 0.0;
        foreach ($kalender as $i => $km) {
            $ke = (int) $km['minggu_ke'];
            $kumBobot += $perMinggu[$ke] ?? 0.0;
            $rencana = $totalBobot > 0 ? min(100, $kumBobot / $totalBobot * 100) : 0.0;
            $kum = 0.0;
            foreach ($real as $r) {
                if ($r['tanggal'] <= $km['tanggal_selesai']) {
                    $kum += (float) $r['nilai'];
                }
            }
            $realisasi = $basis > 0 ? min(100, $kum / $basis * 100) : 0.0;
            $minggu[] = [
                'ke' => $ke, 'mulai' => $km['tanggal_mulai'], 'tanggal' => $km['tanggal_selesai'],
                'rencana' => $rencana, 'realisasi' => $realisasi, 'nilai' => $kum,
            ];
            $prop = $n > 1 ? $i / ($n - 1) * 100 : 100;
            $titikRencana[] = [$prop, $rencana];
            $titikRealisasi[] = [$prop, $realisasi];
        }
        return [$minggu, $this->buatSvg($titikRencana, $titikRealisasi, 'Rencana (dokumen perencana)'), $basis];
    }

    /** @return array{0: array, 1: string} baris mingguan + SVG kurva */
    private function hitung(array $g): array
    {
        $db = $this->db();
        $spk = $db->query(
            'SELECT MIN(tgl_mulai) AS mulai, MAX(tgl_selesai) AS selesai FROM spk WHERE gedung_id = ? AND is_deleted = 0',
            [(int) $g['id']]
        )->getRowArray();
        $real = $this->nilaiRealisasiPerTanggal((int) $g['id']);

        $mulai = $spk['mulai'] ?? null;
        $selesai = $spk['selesai'] ?? null;
        if (! $mulai && $real !== []) {
            $mulai = $real[0]['tanggal'];
        }
        if (! $selesai && $real !== []) {
            $selesai = $real[count($real) - 1]['tanggal'];
        }
        if (! $mulai || ! $selesai || $selesai <= $mulai) {
            $mulai = $mulai ?: date('Y-m-d');
            $selesai = date('Y-m-d', strtotime($mulai . ' +16 weeks'));
        }
        $tMulai = strtotime($mulai);
        $tSelesai = strtotime($selesai);
        $totalHari = max(1, (int) (($tSelesai - $tMulai) / 86400));
        $boq = (float) $g['nilai_boq'];

        $minggu = [];
        $titikRencana = [];
        $titikRealisasi = [];
        $jumlahMinggu = (int) ceil($totalHari / 7);
        for ($w = 0; $w <= $jumlahMinggu; $w++) {
            $tAkhir = $tMulai + $w * 7 * 86400;
            $prop = min(1.0, $w / max(1, $jumlahMinggu));
            // Garis rencana berbentuk S (halus di awal & akhir)
            $rencana = (1 - cos(M_PI * $prop)) / 2 * 100;
            $kum = 0.0;
            foreach ($real as $r) {
                if (strtotime($r['tanggal']) <= $tAkhir) {
                    $kum += (float) $r['nilai'];
                }
            }
            $realisasi = $boq > 0 ? min(100, $kum / $boq * 100) : 0.0;
            $minggu[] = [
                'ke' => $w, 'tanggal' => date('Y-m-d', $tAkhir),
                'rencana' => $rencana, 'realisasi' => $realisasi, 'nilai' => $kum,
            ];
            $titikRencana[] = [$prop * 100, $rencana];
            $titikRealisasi[] = [$prop * 100, $realisasi];
        }
        return [$minggu, $this->buatSvg($titikRencana, $titikRealisasi, 'Rencana (Kurva S)')];
    }

    private function buatSvg(array $titikRencana, array $titikRealisasi, string $labelRencana): string
    {
        // SVG 720x260, area plot dengan sumbu % di kedua arah
        $W = 720; $H = 260; $L = 44; $B = 26; $T = 12; $R = 12;
        $pw = $W - $L - $R; $ph = $H - $T - $B;
        $xy = static fn (array $p) => ($L + $p[0] / 100 * $pw) . ',' . ($T + $ph - $p[1] / 100 * $ph);
        $garis = static fn (array $pts) => implode(' ', array_map($xy, $pts));
        $grid = '';
        foreach ([0, 25, 50, 75, 100] as $v) {
            $y = $T + $ph - $v / 100 * $ph;
            $grid .= '<line x1="' . $L . '" y1="' . $y . '" x2="' . ($W - $R) . '" y2="' . $y . '" stroke="#ddd" stroke-width="1"/>'
                . '<text x="' . ($L - 6) . '" y="' . ($y + 4) . '" font-size="11" text-anchor="end" fill="#666">' . $v . '%</text>';
        }
        return '<svg viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%;height:auto" role="img" aria-label="Kurva S rencana vs realisasi">'
            . $grid
            . '<line x1="' . $L . '" y1="' . ($T + $ph) . '" x2="' . ($W - $R) . '" y2="' . ($T + $ph) . '" stroke="#999"/>'
            . '<polyline fill="none" stroke="#9aa0a6" stroke-width="2" stroke-dasharray="6 4" points="' . $garis($titikRencana) . '"/>'
            . '<polyline fill="none" stroke="#1a56db" stroke-width="2.5" points="' . $garis($titikRealisasi) . '"/>'
            . '<text x="' . ($L + 8) . '" y="' . ($T + 14) . '" font-size="12" fill="#9aa0a6">- - ' . esc($labelRencana) . '</text>'
            . '<text x="' . ($L + 8) . '" y="' . ($T + 32) . '" font-size="12" fill="#1a56db">— Realisasi</text>'
            . '</svg>';
    }
}
