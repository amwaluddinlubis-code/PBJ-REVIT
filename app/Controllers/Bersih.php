<?php

namespace App\Controllers;

/**
 * Wizard Pembersihan Hasil Impor (3 langkah, pola sesi seperti Impor):
 * 1) Pekerjaan bervolume < 1 per gedung ditandai tidak terpakai
 *    (volume 0 tercentang bawaan; volume pecahan menunggu keputusan).
 * 2) Analisa yang hanya dirujuk pekerjaan terpilih menjadi tak
 *    terpakai dan dapat ikut dibuang.
 * 3) Bahan "yatim" — yang sesudah langkah 1–2 tidak dirujuk analisa
 *    aktif, SPK, survei, negosiasi, atau buku material mana pun —
 *    ditawarkan untuk dihapus dari Master Bahan.
 * Semua penghapusan bersifat lunak (dapat dipulihkan) dan nilai BOQ
 * gedung dihitung ulang dari pekerjaan yang tersisa.
 */
class Bersih extends BaseController
{
    // ---------- Langkah 1: pekerjaan volume < 1 ----------

    public function index()
    {
        $db = $this->db();
        // Langkah 1 hanya memuat ringkasan per gedung; baris pekerjaan satu
        // grup dimuat saat panelnya dibuka (endpoint grup) agar halaman awal
        // tetap ringan walau baris volume < 1 berjumlah ribuan.
        $grupGedung = $db->query(
            'SELECT g.id AS gedung_id, g.nama AS gedung_nama, COUNT(*) AS jumlah, COALESCE(SUM(p.jumlah), 0) AS total
             FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND COALESCE(p.volume, 0) < 1
             GROUP BY g.id ORDER BY g.urutan, g.id',
            [$this->sid()]
        )->getResultArray();
        $totalKecil = 0;
        foreach ($grupGedung as $g) {
            $totalKecil += (int) $g['jumlah'];
        }
        $totalAktif = (int) $db->query(
            'SELECT COUNT(*) c FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id WHERE g.sekolah_id = ? AND p.is_deleted = 0',
            [$this->sid()]
        )->getRow()->c;
        $buka = (int) ($this->request->getGet('buka') ?? 0);
        return $this->tampil('bersih/langkah1', [
            'grupGedung' => $grupGedung, 'totalKecil' => $totalKecil, 'totalAktif' => $totalAktif,
            'buka' => $buka, 'barisBuka' => $buka > 0 ? $this->barisGrup($buka) : [],
        ], 'Pembersihan Hasil Impor — Aplikasi PBJ Sekolah');
    }

    /** Baris pekerjaan volume < 1 pada satu gedung (terisolasi sekolah aktif). */
    private function barisGrup(int $gedungId): array
    {
        return $this->db()->query(
            'SELECT p.id, p.nama, COALESCE(p.volume, 0) AS volume, p.satuan, p.jumlah, p.kode_analisa,
                    (SELECT COUNT(*) FROM realisasi r WHERE r.pekerjaan_id = p.id AND r.is_deleted = 0) AS punya_realisasi
             FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.gedung_id = ? AND p.is_deleted = 0 AND COALESCE(p.volume, 0) < 1
             ORDER BY p.urutan, p.id',
            [$this->sid(), $gedungId]
        )->getResultArray();
    }

    /** Fragmen tabel satu grup gedung untuk Langkah 1 (dimuat saat panel dibuka). */
    public function grup($gedungId = 0)
    {
        return view('bersih/_grup_tabel', ['rows' => $this->barisGrup((int) $gedungId)]);
    }

    public function langkah2()
    {
        $mode = (string) $this->request->getPost('mode');
        if (in_array($mode, ['volume_nol', 'volume_kecil'], true)) {
            // Mode massal: himpunan id dihitung di server karena formulir
            // ribuan centang melampaui batas variabel POST PHP
            // (max_input_vars, bawaan 1000). Pekerjaan yang sudah punya
            // realisasi selalu dikecualikan pada mode ini.
            $syarat = $mode === 'volume_nol' ? 'COALESCE(p.volume, 0) <= 0' : 'COALESCE(p.volume, 0) < 1';
            $rows = $this->db()->query(
                "SELECT p.id FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
                 WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND $syarat
                   AND NOT EXISTS (SELECT 1 FROM realisasi r WHERE r.pekerjaan_id = p.id AND r.is_deleted = 0)",
                [$this->sid()]
            )->getResultArray();
            $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        } else {
            $ids = array_map('intval', (array) $this->request->getPost('pekerjaan'));
            $ids = $this->pekerjaanValid($ids);
        }
        if ($ids === []) {
            return redirect()->to('/bersih')->with('galat', 'Centang dulu pekerjaan yang tidak terpakai.');
        }
        session()->set('bersih_stage', ['pekerjaan' => $ids]);
        return redirect()->to('/bersih/analisa');
    }

    // ---------- Langkah 2: analisa yang menjadi tak terpakai ----------

    public function analisa()
    {
        $stage = session()->get('bersih_stage');
        if (! is_array($stage) || empty($stage['pekerjaan'])) {
            return redirect()->to('/bersih')->with('galat', 'Mulai dari langkah 1 dulu.');
        }
        $kandidat = $this->analisaKandidat($stage['pekerjaan']);
        return $this->tampil('bersih/langkah2', [
            'kandidat' => $kandidat, 'jumlahPekerjaan' => count($stage['pekerjaan']),
        ], 'Pembersihan Hasil Impor — Langkah 2: Analisa Tak Terpakai');
    }

    public function langkah3()
    {
        $stage = session()->get('bersih_stage');
        if (! is_array($stage) || empty($stage['pekerjaan'])) {
            return redirect()->to('/bersih')->with('galat', 'Mulai dari langkah 1 dulu.');
        }
        $ids = array_map('intval', (array) $this->request->getPost('analisa'));
        // Hanya analisa yang benar-benar kandidat yang boleh disimpan
        $sah = array_map(static fn ($a) => (int) $a['id'], $this->analisaKandidat($stage['pekerjaan']));
        $stage['analisa'] = array_values(array_intersect($ids, $sah));
        session()->set('bersih_stage', $stage);
        return redirect()->to('/bersih/bahan');
    }

    // ---------- Langkah 3: bahan yatim + ringkasan + eksekusi ----------

    public function bahan()
    {
        $stage = session()->get('bersih_stage');
        if (! is_array($stage) || empty($stage['pekerjaan'])) {
            return redirect()->to('/bersih')->with('galat', 'Mulai dari langkah 1 dulu.');
        }
        $analisaIds = $stage['analisa'] ?? [];
        [$grupA, $grupB, $terlindungi] = $this->bahanKandidat($analisaIds);
        $ringkasan = $this->ringkasanDampak($stage['pekerjaan'], $analisaIds);
        return $this->tampil('bersih/langkah3', [
            'grupA' => $grupA, 'grupB' => $grupB, 'terlindungi' => $terlindungi,
            'ringkasan' => $ringkasan,
            'jumlahPekerjaan' => count($stage['pekerjaan']), 'jumlahAnalisa' => count($analisaIds),
        ], 'Pembersihan Hasil Impor — Langkah 3: Bahan Yatim');
    }

    public function eksekusi()
    {
        $stage = session()->get('bersih_stage');
        if (! is_array($stage) || empty($stage['pekerjaan'])) {
            return redirect()->to('/bersih')->with('galat', 'Tahap pembersihan sudah berakhir. Ulangi dari langkah 1.');
        }
        $pekerjaanIds = $this->pekerjaanValid($stage['pekerjaan']);
        $analisaIds = $stage['analisa'] ?? [];
        $bahanIds = array_map('intval', (array) $this->request->getPost('bahan'));
        // Bahan yang boleh dihapus diverifikasi ulang saat eksekusi:
        // hanya yang saat ini juga benar-benar yatim.
        [$grupA, $grupB] = $this->bahanKandidat($analisaIds);
        $yatimSah = array_map(static fn ($b) => (int) $b['id'], array_merge($grupA, $grupB));
        $bahanIds = array_values(array_intersect($bahanIds, $yatimSah));

        $db = $this->db();
        $gedungTerpengaruh = [];
        foreach ($db->query(
            'SELECT DISTINCT gedung_id FROM pekerjaan WHERE id IN (' . implode(',', $pekerjaanIds ?: [0]) . ')'
        )->getResultArray() as $r) {
            $gedungTerpengaruh[] = (int) $r['gedung_id'];
        }
        $boqSebelum = [];
        foreach ($gedungTerpengaruh as $gid) {
            $g = $this->ambilGedung($gid);
            $boqSebelum[$gid] = (float) ($g['nilai_boq'] ?? 0);
        }

        $db->transStart();
        foreach ($pekerjaanIds as $pid) {
            $this->hapusLunak('pekerjaan', ['id' => $pid]);
        }
        foreach ($gedungTerpengaruh as $gid) {
            $db->query(
                'UPDATE gedung SET nilai_boq = (SELECT COALESCE(SUM(jumlah), 0) FROM pekerjaan WHERE gedung_id = ? AND is_deleted = 0), updated_at = NOW() WHERE id = ?',
                [$gid, $gid]
            );
        }
        foreach ($analisaIds as $aid) {
            $a = $db->table('analisa')->where(['id' => $aid, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($a) {
                $db->table('analisa_komponen')->where('analisa_id', $aid)->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
                $this->hapusLunak('analisa', ['id' => $aid, 'sekolah_id' => $this->sid()]);
            }
        }
        foreach ($bahanIds as $bid) {
            $this->hapusLunak('bahan', ['id' => $bid, 'sekolah_id' => $this->sid()]);
        }
        $db->transComplete();
        session()->remove('bersih_stage');

        $pesan = 'Pembersihan selesai: ' . count($pekerjaanIds) . ' pekerjaan tak terpakai dibuang, '
            . count($analisaIds) . ' analisa tak terpakai dibuang, ' . count($bahanIds) . ' bahan yatim dihapus dari master (semua hapus lunak, dapat dipulihkan).';
        foreach ($gedungTerpengaruh as $gid) {
            $g = $this->ambilGedung($gid);
            $pesan .= ' BOQ ' . ($g['nama'] ?? 'gedung') . ': ' . $this->rp($boqSebelum[$gid]) . ' → ' . $this->rp($g['nilai_boq'] ?? 0) . '.';
        }
        return redirect()->to('/gedung')->with('sukses', $pesan);
    }

    public function batal()
    {
        session()->remove('bersih_stage');
        return redirect()->to('/impor')->with('sukses', 'Pembersihan dibatalkan — tidak ada data yang diubah.');
    }

    // ---------- Perhitungan kandidat ----------

    /** Id pekerjaan volume < 1 milik sekolah aktif dari daftar id mentah. */
    private function pekerjaanValid(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $rows = $this->db()->query(
            'SELECT p.id FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND COALESCE(p.volume, 0) < 1 AND p.id IN (' . implode(',', $ids) . ')',
            [$this->sid()]
        )->getResultArray();
        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /**
     * Analisa yang kodenya hanya dirujuk pekerjaan terpilih — tidak
     * ada pekerjaan aktif lain yang memakainya.
     */
    private function analisaKandidat(array $pekerjaanIds): array
    {
        $db = $this->db();
        if ($pekerjaanIds === []) {
            return [];
        }
        $in = implode(',', $pekerjaanIds);
        $kode = $db->query(
            "SELECT DISTINCT kode_analisa FROM pekerjaan WHERE id IN ($in) AND kode_analisa IS NOT NULL AND kode_analisa <> ''"
        )->getResultArray();
        $hasil = [];
        foreach ($kode as $k) {
            $masihDipakai = (int) $db->query(
                'SELECT COUNT(*) c FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
                 WHERE g.sekolah_id = ? AND p.kode_analisa = ? AND p.is_deleted = 0 AND p.id NOT IN (' . $in . ')',
                [$this->sid(), $k['kode_analisa']]
            )->getRow()->c;
            if ($masihDipakai > 0) {
                continue;
            }
            foreach ($db->table('analisa')->where(['sekolah_id' => $this->sid(), 'kode' => $k['kode_analisa'], 'is_deleted' => 0])->get()->getResultArray() as $a) {
                $a['jumlah_komponen'] = (int) $db->table('analisa_komponen')->where(['analisa_id' => $a['id'], 'is_deleted' => 0])->countAllResults();
                $hasil[] = $a;
            }
        }
        return $hasil;
    }

    /**
     * Bahan kandidat hapus, dua kelompok:
     * A) hanya dirujuk analisa terpilih (dan tidak dirujuk apa pun lain)
     * B) tidak dirujuk apa pun sama sekali
     * Bahan lain dihitung sebagai terlindungi (tidak ditawarkan).
     *
     * @return array{0: array, 1: array, 2: int}
     */
    private function bahanKandidat(array $analisaIds): array
    {
        $db = $this->db();
        $sid = $this->sid();
        $semua = $db->table('bahan')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray();
        $rujukanAnalisaLain = [];
        $rujukanAnalisaPilih = [];
        foreach ($db->query(
            'SELECT ak.bahan_id, ak.analisa_id FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.bahan_id IS NOT NULL AND ak.is_deleted = 0 AND a.is_deleted = 0',
            [$sid]
        )->getResultArray() as $r) {
            if (in_array((int) $r['analisa_id'], $analisaIds, true)) {
                $rujukanAnalisaPilih[(int) $r['bahan_id']] = true;
            } else {
                $rujukanAnalisaLain[(int) $r['bahan_id']] = true;
            }
        }
        $terpakaiLain = [];
        foreach (['spk_detail', 'survei_harga', 'negosiasi', 'buku_material'] as $tabel) {
            foreach ($db->query("SELECT DISTINCT bahan_id FROM $tabel WHERE bahan_id IS NOT NULL AND is_deleted = 0")->getResultArray() as $r) {
                $terpakaiLain[(int) $r['bahan_id']] = true;
            }
        }
        $grupA = [];
        $grupB = [];
        $terlindungi = 0;
        foreach ($semua as $b) {
            $id = (int) $b['id'];
            if (isset($rujukanAnalisaLain[$id]) || isset($terpakaiLain[$id])) {
                $terlindungi++;
            } elseif (isset($rujukanAnalisaPilih[$id])) {
                $grupA[] = $b;
            } else {
                $grupB[] = $b;
            }
        }
        return [$grupA, $grupB, $terlindungi];
    }

    /** Ringkasan dampak sebelum eksekusi: nilai pekerjaan terpilih & BOQ gedung terkait. */
    private function ringkasanDampak(array $pekerjaanIds, array $analisaIds): array
    {
        $db = $this->db();
        $in = implode(',', array_map('intval', $pekerjaanIds ?: [0]));
        $nilai = (float) $db->query("SELECT COALESCE(SUM(jumlah), 0) t FROM pekerjaan WHERE id IN ($in)")->getRow()->t;
        $gedung = $db->query(
            "SELECT g.nama, g.nilai_boq, COUNT(*) c FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id WHERE p.id IN ($in) GROUP BY g.id, g.nama, g.nilai_boq ORDER BY g.urutan"
        )->getResultArray();
        return ['nilai_pekerjaan' => $nilai, 'gedung' => $gedung];
    }
}
