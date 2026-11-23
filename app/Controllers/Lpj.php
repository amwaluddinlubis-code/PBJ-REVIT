<?php

namespace App\Controllers;

/**
 * LPJ & Realisasi: pertanggungjawaban per gedung dan gabungan sekolah —
 * BOQ vs realisasi fisik, kebutuhan vs belanja, upah, dan kontrol pagu.
 */
class Lpj extends BaseController
{
    /** Rekap satu gedung: BOQ, fisik, kebutuhan, belanja, upah. */
    private function rekapGedung(array $g): array
    {
        $db = $this->db();
        $gid = (int) $g['id'];
        $nilaiRealisasi = $this->nilaiRealisasiGedung($gid);

        $kb = $this->kebutuhanBahan($gid);
        $nilaiKebutuhan = 0.0;
        foreach ($kb['tertaut'] as $b) {
            $nilaiKebutuhan += (float) $b['qty_butuh'] * $this->hargaAcuan((int) $b['bahan_id']);
        }
        $ku = $this->kebutuhanUpah($gid);
        $nilaiUpahButuh = 0.0;
        foreach ($ku as $u) {
            $nilaiUpahButuh += (float) $u['orang_hari'] * (float) $u['tarif'];
        }
        $spk = $db->query(
            'SELECT COALESCE(SUM(d.qty * d.harga), 0) t FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0 WHERE s.gedung_id = ? AND d.is_deleted = 0',
            [$gid]
        )->getRowArray();
        $inv = $db->query(
            "SELECT COALESCE(SUM(i.nilai), 0) t FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0 WHERE s.gedung_id = ? AND i.status = 'Sudah Bayar' AND i.is_deleted = 0",
            [$gid]
        )->getRowArray();
        $upah = $db->table('upah_bayar')->selectSum('jumlah')->where(['sekolah_id' => $this->sid(), 'gedung_id' => $gid, 'is_deleted' => 0])->get()->getRowArray();

        return $g + [
            'nilai_realisasi' => $nilaiRealisasi,
            'persen_fisik' => (float) $g['nilai_boq'] > 0 ? min(100, $nilaiRealisasi / (float) $g['nilai_boq'] * 100) : 0,
            'nilai_kebutuhan_bahan' => $nilaiKebutuhan,
            'nilai_kebutuhan_upah' => $nilaiUpahButuh,
            'nilai_spk' => (float) ($spk['t'] ?? 0),
            'invoice_terbayar' => (float) ($inv['t'] ?? 0),
            'upah_dibayar' => (float) ($upah['jumlah'] ?? 0),
        ];
    }

    public function index()
    {
        $rekap = array_map(fn ($g) => $this->rekapGedung($g), $this->semuaGedung());
        $sekolah = $this->sekolahAktif();
        $anggaran = (float) ($sekolah['total_anggaran'] ?? 0);
        $terbayar = $this->totalBelanja();
        // Belanja tanpa tautan gedung (pengecualian yang harus tampak di LPJ)
        $tanpaGedung = $this->db()->query(
            'SELECT COALESCE(SUM(d.qty * d.harga), 0) t, COUNT(DISTINCT s.id) n FROM spk_detail d JOIN spk s ON s.id = d.spk_id
             WHERE s.sekolah_id = ? AND s.gedung_id IS NULL AND s.is_deleted = 0 AND d.is_deleted = 0',
            [$this->sid()]
        )->getRowArray();
        return $this->tampil('lpj/index', [
            'rekap' => $rekap, 'anggaran' => $anggaran, 'terbayar' => $terbayar,
            'sisa_pagu' => $anggaran > 0 ? $anggaran - $terbayar : null,
            'tanpa_gedung_nilai' => (float) ($tanpaGedung['t'] ?? 0),
            'tanpa_gedung_spk' => (int) ($tanpaGedung['n'] ?? 0),
        ], 'LPJ & Realisasi Sekolah');
    }

    public function gedung($id)
    {
        $g = $this->ambilGedung((int) $id);
        if ($g === null) {
            return redirect()->to('/lpj')->with('galat', 'Gedung tidak ditemukan.');
        }
        $db = $this->db();
        $rekap = $this->rekapGedung($g);
        $pekerjaan = $db->query(
            'SELECT p.*, COALESCE(SUM(r.volume), 0) AS volume_terealisasi
             FROM pekerjaan p LEFT JOIN realisasi r ON r.pekerjaan_id = p.id AND r.is_deleted = 0
             WHERE p.gedung_id = ? AND p.is_deleted = 0 GROUP BY p.id ORDER BY p.urutan, p.id',
            [$g['id']]
        )->getResultArray();
        $kb = $this->kebutuhanBahan((int) $g['id']);
        $dipesan = [];
        foreach ($db->query(
            'SELECT d.bahan_id, SUM(d.qty) q FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0 WHERE s.gedung_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0 GROUP BY d.bahan_id',
            [$g['id']]
        )->getResultArray() as $r) {
            $dipesan[(int) $r['bahan_id']] = (float) $r['q'];
        }
        foreach ($kb['tertaut'] as &$b) {
            $b['harga'] = $this->hargaAcuan((int) $b['bahan_id']);
            $b['dipesan'] = $dipesan[(int) $b['bahan_id']] ?? 0;
        }
        return $this->tampil('lpj/gedung', [
            'g' => $rekap, 'pekerjaan' => $pekerjaan, 'kebutuhan' => $kb,
            'kebutuhan_upah' => $this->kebutuhanUpah((int) $g['id']),
        ], 'LPJ Gedung: ' . $g['nama']);
    }

    /**
     * Data RAB gabungan beberapa gedung: ringkasan per gedung +
     * pekerjaan yang sama (nama & harga satuan sama) digabung volumenya
     * lintas gedung. Melengkapi RAB per gedung yang sudah ada di LPJ
     * Gedung.
     */
    private function dataRabGabungan(array $ids): array
    {
        $semua = $this->semuaGedung();
        $valid = array_map(static fn ($g) => (int) $g['id'], $semua);
        $ids = array_values(array_intersect(array_map('intval', $ids), $valid));
        if ($ids === []) {
            $ids = $valid;
        }
        $perGedung = [];
        foreach ($semua as $g) {
            if (in_array((int) $g['id'], $ids, true)) {
                $perGedung[] = $this->rekapGedung($g);
            }
        }
        $gabungan = [];
        $total = 0.0;
        if ($ids !== []) {
            $in = implode(',', $ids);
            $grup = [];
            foreach ($this->db()->query(
                "SELECT p.nama, p.satuan, p.volume, p.volume_revisi, p.harga_satuan, p.harga_revisi, p.kode_analisa, p.analisa_id, p.gedung_id
                 FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
                 WHERE p.gedung_id IN ($in) AND p.is_deleted = 0 AND g.is_deleted = 0"
            )->getResultArray() as $p) {
                $harga = $this->hargaBerjalan($p);
                $vol = $this->volBerjalan($p);
                $kunci = mb_strtolower(trim((string) $p['nama'])) . '|' . ($p['satuan'] ?? '') . '|' . $harga;
                if (! isset($grup[$kunci])) {
                    $grup[$kunci] = ['nama' => $p['nama'], 'satuan' => $p['satuan'], 'harga_satuan' => $harga, 'volume' => 0.0, 'jumlah' => 0.0, 'gedung' => []];
                }
                $grup[$kunci]['volume'] += $vol;
                $grup[$kunci]['jumlah'] += $vol * $harga;
                $grup[$kunci]['gedung'][(int) $p['gedung_id']] = true;
            }
            $gabungan = [];
            foreach ($grup as $gr) {
                $gr['jumlah_gedung'] = count($gr['gedung']);
                unset($gr['gedung']);
                $gabungan[] = $gr;
            }
            usort($gabungan, static fn ($a, $b) => $b['jumlah'] <=> $a['jumlah']);
            $total = array_sum(array_column($gabungan, 'jumlah'));
        }
        return ['semua' => $semua, 'ids' => $ids, 'perGedung' => $perGedung, 'gabungan' => $gabungan, 'total' => $total];
    }

    /** Halaman RAB Gabungan: pilih beberapa gedung, lihat RAB menyatu. */
    public function rabGabungan()
    {
        $pilih = (array) ($this->request->getGet('gedung') ?? []);
        $d = $this->dataRabGabungan($pilih);
        return $this->tampil('lpj/rab_gabungan', $d, 'RAB Gabungan Beberapa Gedung');
    }

    public function cetakRabGabungan()
    {
        $pilih = (array) ($this->request->getGet('gedung') ?? []);
        $d = $this->dataRabGabungan($pilih);
        return view('lpj/cetak_rab_gabungan', $d + ['sekolah' => $this->sekolahAktif()]);
    }

    public function cetakSekolah()
    {
        $rekap = array_map(fn ($g) => $this->rekapGedung($g), $this->semuaGedung());
        $sekolah = $this->sekolahAktif();
        return view('lpj/cetak_sekolah', [
            'rekap' => $rekap, 'sekolah' => $sekolah,
            'anggaran' => (float) ($sekolah['total_anggaran'] ?? 0),
            'terbayar' => $this->totalBelanja(),
        ]);
    }

    public function cetakGedung($id)
    {
        $g = $this->ambilGedung((int) $id);
        if ($g === null) {
            return redirect()->to('/lpj');
        }
        $db = $this->db();
        $pekerjaan = $db->query(
            'SELECT p.*, COALESCE(SUM(r.volume), 0) AS volume_terealisasi
             FROM pekerjaan p LEFT JOIN realisasi r ON r.pekerjaan_id = p.id AND r.is_deleted = 0
             WHERE p.gedung_id = ? AND p.is_deleted = 0 GROUP BY p.id ORDER BY p.urutan, p.id',
            [$g['id']]
        )->getResultArray();
        return view('lpj/cetak_gedung', [
            'g' => $this->rekapGedung($g), 'pekerjaan' => $pekerjaan, 'sekolah' => $this->sekolahAktif(),
        ]);
    }
}
