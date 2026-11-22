<?php

namespace App\Controllers;

/**
 * Kurva S pelaksanaan pekerjaan per gedung (format dokumen jadwal
 * Juknis): garis rencana kumulatif berbentuk S dibandingkan realisasi
 * kumulatif mingguan dari data realisasi BOQ. Jendela waktu diambil
 * dari tanggal mulai/selesai SPK yang tertaut ke gedung.
 */
class Kurva extends BaseController
{
    public function index()
    {
        $semua = $this->semuaGedung();
        $gid = (int) ($this->request->getGet('gedung') ?? 0);
        $g = null;
        foreach ($semua as $row) {
            if ((int) $row['id'] === $gid) {
                $g = $row;
                break;
            }
        }
        if (! $g && $semua !== []) {
            $g = $semua[0];
            $gid = (int) $g['id'];
        }
        $minggu = [];
        $svg = '';
        if ($g) {
            [$minggu, $svg] = $this->hitung($g);
        }
        return $this->tampil('kurva/index', [
            'semua' => $semua, 'g' => $g, 'minggu' => $minggu, 'svg' => $svg,
        ], 'Kurva S Pelaksanaan — Aplikasi PBJ Sekolah');
    }

    /** @return array{0: array, 1: string} baris mingguan + SVG kurva */
    private function hitung(array $g): array
    {
        $db = $this->db();
        $spk = $db->query(
            'SELECT MIN(tgl_mulai) AS mulai, MAX(tgl_selesai) AS selesai FROM spk WHERE gedung_id = ? AND is_deleted = 0',
            [(int) $g['id']]
        )->getRowArray();
        $real = $db->query(
            'SELECT rl.tanggal, SUM(rl.volume * COALESCE(p.harga_revisi, p.harga_satuan)) AS nilai
             FROM realisasi rl JOIN pekerjaan p ON p.id = rl.pekerjaan_id
             WHERE p.gedung_id = ? AND rl.is_deleted = 0 AND p.is_deleted = 0
             GROUP BY rl.tanggal ORDER BY rl.tanggal',
            [(int) $g['id']]
        )->getResultArray();

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
        $svg = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%;height:auto" role="img" aria-label="Kurva S rencana vs realisasi">'
            . $grid
            . '<line x1="' . $L . '" y1="' . ($T + $ph) . '" x2="' . ($W - $R) . '" y2="' . ($T + $ph) . '" stroke="#999"/>'
            . '<polyline fill="none" stroke="#9aa0a6" stroke-width="2" stroke-dasharray="6 4" points="' . $garis($titikRencana) . '"/>'
            . '<polyline fill="none" stroke="#1a56db" stroke-width="2.5" points="' . $garis($titikRealisasi) . '"/>'
            . '<text x="' . ($L + 8) . '" y="' . ($T + 14) . '" font-size="12" fill="#9aa0a6">- - Rencana (Kurva S)</text>'
            . '<text x="' . ($L + 8) . '" y="' . ($T + 32) . '" font-size="12" fill="#1a56db">— Realisasi</text>'
            . '</svg>';
        return [$minggu, $svg];
    }
}
