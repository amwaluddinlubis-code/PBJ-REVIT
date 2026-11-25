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
/**
 * Kurva mewarisi RencanaMingguan agar tombol "Buat Rencana Belanja" per
 * baris minggu memakai intiTargetKurva() yang sama dengan tombol "Ambil
 * Target dari Kurva S" di Rencana Mingguan — satu jalur hitung target.
 */
class Kurva extends RencanaMingguan
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
        // Aksi per baris (mode resmi): rencana belanja minggu itu & realisasi simulasi.
        $petaRencana = [];
        $petaRealisasiSim = [];
        $rencanaAda = [];
        $jumlahGedungBerencana = 0;
        if ($mode === 'resmi' && $db->tableExists('rencana_mingguan')) {
            if (! $total && $g) {
                foreach ($minggu as $m) {
                    $r = $db->table('rencana_mingguan')
                        ->where(['sekolah_id' => $this->sid(), 'gedung_id' => (int) $g['id'], 'minggu_mulai' => $m['mulai'], 'is_deleted' => 0])
                        ->get()->getRowArray();
                    $petaRencana[(int) $m['ke']] = $r ? (int) $r['id'] : null;
                }
                if ($db->tableExists('simulasi_realisasi')) {
                    foreach ($db->table('simulasi_realisasi')->where(['sekolah_id' => $this->sid(), 'gedung_id' => (int) $g['id'], 'is_deleted' => 0])->get()->getResultArray() as $sr) {
                        $petaRealisasiSim[(int) $sr['minggu_ke']] = (float) $sr['bobot'];
                    }
                }
            } elseif ($total) {
                $ids = [];
                foreach ($db->table('kurva_rencana')->select('gedung_id')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->distinct()->get()->getResultArray() as $kr) {
                    if ($kr['gedung_id'] !== null) {
                        $ids[] = (int) $kr['gedung_id'];
                    }
                }
                $jumlahGedungBerencana = count($ids);
                if ($ids !== []) {
                    foreach ($minggu as $m) {
                        $rencanaAda[(int) $m['ke']] = (int) $db->table('rencana_mingguan')
                            ->whereIn('gedung_id', $ids)
                            ->where(['sekolah_id' => $this->sid(), 'minggu_mulai' => $m['mulai'], 'is_deleted' => 0])
                            ->countAllResults();
                    }
                }
            }
        }
        return $this->tampil('kurva/index', [
            'semua' => $semua, 'g' => $g, 'minggu' => $minggu, 'svg' => $svg,
            'mode' => $mode, 'total' => $total, 'adaRencana' => $adaRencana, 'basis' => $basis,
            'petaRencana' => $petaRencana, 'petaRealisasiSim' => $petaRealisasiSim,
            'rencanaAda' => $rencanaAda, 'jumlahGedungBerencana' => $jumlahGedungBerencana,
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

    /**
     * Buat Rencana Belanja (Rencana Mingguan) dari satu baris minggu Kurva S:
     * rencana untuk minggu kalender itu dibuat (bila belum ada) dan target
     * pekerjaannya langsung terisi dari Kurva S lewat intiTargetKurva() —
     * angka rencana kerja & persen tidak diketik ulang, dan rencana belanja
     * yang terbentuk selalu tercatat kebutuhan pekerjaannya. Parameter
     * gedung = id gedung, atau 'semua' (tampilan Total Sekolah: semua
     * gedung yang punya rencana kurva). Rencana yang sudah bertarget tidak
     * ditimpa; pengguna diarahkan membukanya.
     */
    public function buatRencana()
    {
        $db = $this->db();
        $ke = (int) $this->request->getPost('minggu_ke');
        $param = (string) $this->request->getPost('gedung');
        $kembali = 'kurva-s?gedung=' . ($param === 'semua' ? 'total' : (int) $param);
        if (! $db->tableExists('kurva_rencana') || ! $db->tableExists('kurva_minggu') || ! $db->tableExists('rencana_mingguan')) {
            return redirect()->to($kembali)->with('galat', 'Data Kurva S / Rencana Mingguan belum tersedia — jalankan migrasi lebih dulu.');
        }
        $km = $db->table('kurva_minggu')->where(['sekolah_id' => $this->sid(), 'minggu_ke' => $ke, 'is_deleted' => 0])->get()->getRowArray();
        if ($km === null) {
            return redirect()->to($kembali)->with('galat', 'Minggu kalender ke-' . $ke . ' tidak ditemukan pada rencana Kurva S.');
        }
        $sasaran = [];
        if ($param === 'semua') {
            foreach ($db->table('kurva_rencana')->select('gedung_id')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->distinct()->get()->getResultArray() as $kr) {
                if ($kr['gedung_id'] !== null && $this->ambilGedung((int) $kr['gedung_id']) !== null) {
                    $sasaran[] = (int) $kr['gedung_id'];
                }
            }
        } else {
            $gid = (int) $param;
            if ($this->ambilGedung($gid) === null || ! $this->gedungPunyaRencana($gid)) {
                return redirect()->to($kembali)->with('galat', 'Gedung tidak ditemukan atau belum punya rencana Kurva S.');
            }
            $sasaran[] = $gid;
        }
        if ($sasaran === []) {
            return redirect()->to($kembali)->with('galat', 'Belum ada gedung yang punya rencana Kurva S.');
        }
        $ringkasan = [];
        $gagal = [];
        $satuId = null;
        foreach ($sasaran as $gid) {
            $g = $this->ambilGedung($gid);
            $r = $db->table('rencana_mingguan')
                ->where(['sekolah_id' => $this->sid(), 'gedung_id' => $gid, 'minggu_mulai' => $km['tanggal_mulai'], 'is_deleted' => 0])
                ->get()->getRowArray();
            $baru = false;
            if ($r === null) {
                $db->table('rencana_mingguan')->insert([
                    'sekolah_id' => $this->sid(), 'gedung_id' => $gid, 'minggu_mulai' => $km['tanggal_mulai'],
                    'keterangan' => 'Dari Kurva S — minggu ke-' . $ke,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $r = $this->ambilRencana((int) $db->insertID());
                $baru = true;
                $this->catatAudit('rencana_mingguan', (int) $r['id'], 'buat_dari_kurva_s', 'Dibuat dari halaman Kurva S, minggu kalender ke-' . $ke . ' (' . $km['tanggal_mulai'] . '), gedung ' . ($g['nama'] ?? $gid) . '.');
            }
            $satuId = (int) $r['id'];
            $jmlTarget = (int) $db->table('rencana_mingguan_detail')
                ->where(['rencana_id' => (int) $r['id'], 'is_deleted' => 0])->where('volume_target >', 0)->countAllResults();
            if ($jmlTarget > 0) {
                $ringkasan[] = ($g['nama'] ?? 'Gedung') . ': sudah ada (' . $jmlTarget . ' pekerjaan target)';
                continue;
            }
            $h = $this->intiTargetKurva($r);
            if ($h['ok']) {
                $ringkasan[] = ($g['nama'] ?? 'Gedung') . ': ' . $h['jumlah'] . ' pekerjaan target senilai Rp ' . number_format($h['total'], 0, ',', '.');
            } else {
                if ($baru) {
                    $this->hapusLunak('rencana_mingguan', ['id' => (int) $r['id']]);
                    $satuId = null;
                }
                $gagal[] = ($g['nama'] ?? 'Gedung') . ': ' . $h['pesan'];
            }
        }
        if (count($sasaran) === 1 && $gagal === [] && $satuId !== null) {
            return redirect()->to('rencana-mingguan/' . $satuId)
                ->with('sukses', 'Rencana belanja minggu ke-' . $ke . ' siap dari Kurva S — ' . implode('; ', $ringkasan) . '. Daftar belanja bersih & kebutuhan upahnya terhitung di halaman ini; silakan sesuaikan target bila perlu.');
        }
        $pesan = 'Rencana belanja minggu ke-' . $ke . ' dari Kurva S: ' . implode('; ', $ringkasan ?: ['tidak ada yang dibuat']) . '.';
        if ($gagal !== []) {
            $pesan .= ' Belum berhasil: ' . implode('; ', $gagal) . '.';
        }
        return redirect()->to($kembali)->with($gagal !== [] && $ringkasan === [] ? 'galat' : 'sukses', $pesan);
    }

    /**
     * Isi realisasi mingguan (%) untuk Simulasi dari satu baris Kurva S —
     * menulis tabel simulasi_realisasi yang sama dengan formulir Simulasi
     * Mingguan (lewat simpanRealisasiSimulasi), jadi nilai yang diisi di
     * sini langsung menggerakkan Simulasi tanpa diketik ulang di sana.
     */
    public function simpanRealisasi()
    {
        $db = $this->db();
        $gid = (int) $this->request->getPost('gedung_id');
        $ke = (int) $this->request->getPost('minggu_ke');
        $kembali = 'kurva-s?gedung=' . $gid;
        if ($this->ambilGedung($gid) === null) {
            return redirect()->to('kurva-s')->with('galat', 'Gedung tidak ditemukan.');
        }
        if (! $db->tableExists('simulasi_realisasi') || ! $db->tableExists('kurva_minggu')) {
            return redirect()->to($kembali)->with('galat', 'Tabel simulasi/kurva belum tersedia — jalankan migrasi lebih dulu.');
        }
        $km = $db->table('kurva_minggu')->where(['sekolah_id' => $this->sid(), 'minggu_ke' => $ke, 'is_deleted' => 0])->get()->getRowArray();
        if ($km === null) {
            return redirect()->to($kembali)->with('galat', 'Minggu kalender ke-' . $ke . ' tidak ditemukan.');
        }
        $nilai = max(0.0, min(100.0, (float) str_replace(',', '.', trim((string) $this->request->getPost('nilai')))));
        $this->simpanRealisasiSimulasi($ke, $gid, $nilai);
        $this->catatAudit('simulasi_realisasi', null, 'simpan_dari_kurva_s', 'Realisasi simulasi minggu ke-' . $ke . ' gedung ' . $gid . ' = ' . number_format($nilai, 2, ',', '.') . '% (diisi dari halaman Kurva S).');
        return redirect()->to($kembali)->with('sukses', 'Realisasi minggu ke-' . $ke . ' tersimpan: ' . number_format($nilai, 2, ',', '.') . '% — Simulasi Mingguan kini memakai nilai ini.');
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
