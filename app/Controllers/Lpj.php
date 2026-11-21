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
        $real = $db->query(
            'SELECT COALESCE(SUM(r.volume * p.harga_satuan), 0) t FROM realisasi r JOIN pekerjaan p ON p.id = r.pekerjaan_id WHERE p.gedung_id = ? AND r.is_deleted = 0 AND p.is_deleted = 0',
            [$gid]
        )->getRowArray();
        $nilaiRealisasi = (float) ($real['t'] ?? 0);

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
             WHERE p.gedung_id = ? AND p.is_deleted = 0 GROUP BY p.id ORDER BY p.urutan',
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
             WHERE p.gedung_id = ? AND p.is_deleted = 0 GROUP BY p.id ORDER BY p.urutan',
            [$g['id']]
        )->getResultArray();
        return view('lpj/cetak_gedung', [
            'g' => $this->rekapGedung($g), 'pekerjaan' => $pekerjaan, 'sekolah' => $this->sekolahAktif(),
        ]);
    }
}
