<?php

namespace App\Controllers;

/**
 * Rencana Belanja: turunan kebutuhan bahan & upah dari BOQ/AHSP.
 * Dari sini panitia membuat SPK berisi bahan yang dipilih — rencana belanja
 * bukan daftar bebas, melainkan kebutuhan pekerjaan yang belum terpenuhi.
 */
class Rencana extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $gedungFilter = (int) ($this->request->getGet('gedung') ?? 0);
        if ($gedungFilter > 0 && $this->ambilGedung($gedungFilter) === null) {
            $gedungFilter = 0;
        }
        $hari = max(1, (int) ($this->request->getGet('hari') ?? 6));

        // Langkah 1: daftar pekerjaan (per gedung atau gabungan semua gedung).
        $realisasi = [];
        foreach ($db->query(
            'SELECT r.pekerjaan_id, SUM(r.volume) v FROM realisasi r JOIN pekerjaan p ON p.id = r.pekerjaan_id
             JOIN gedung g ON g.id = p.gedung_id WHERE g.sekolah_id = ? AND r.is_deleted = 0 GROUP BY r.pekerjaan_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $realisasi[(int) $r['pekerjaan_id']] = (float) $r['v'];
        }
        $sqlPek = 'SELECT p.id, p.nama, p.bab, p.satuan, COALESCE(p.volume_revisi, p.volume) AS volume_berjalan,
                          g.id AS gedung_id, g.nama AS gedung_nama,
                          COALESCE(a.kode, p.kode_analisa) AS kode_analisa, a.nama AS nama_analisa
                   FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
                   LEFT JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = g.sekolah_id)) AND a.is_deleted = 0
                   WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND g.is_deleted = 0';
        $bindPek = [$this->sid()];
        if ($gedungFilter > 0) {
            $sqlPek .= ' AND g.id = ?';
            $bindPek[] = $gedungFilter;
        }
        $sqlPek .= ' ORDER BY g.urutan, g.id, p.urutan, p.id';
        $daftarPekerjaan = [];
        foreach ($db->query($sqlPek, $bindPek)->getResultArray() as $p) {
            $p['realisasi'] = $realisasi[(int) $p['id']] ?? 0.0;
            $p['sisa_volume'] = max(0, (float) $p['volume_berjalan'] - $p['realisasi']);
            $daftarPekerjaan[(int) $p['id']] = $p;
        }

        // Pilihan pengguna: pekerjaan tercentang + volume rencana per pekerjaan.
        $pilihanIds = array_values(array_filter(array_map('intval', (array) $this->request->getGet('pilih_pekerjaan')), static fn ($i) => $i > 0));
        $volPost = (array) $this->request->getGet('vol');
        $volRencana = [];
        foreach ($pilihanIds as $pid) {
            if (! isset($daftarPekerjaan[$pid])) {
                continue;
            }
            $v = isset($volPost[$pid]) ? (float) str_replace(',', '.', (string) $volPost[$pid]) : $daftarPekerjaan[$pid]['sisa_volume'];
            $volRencana[$pid] = max(0, $v);
        }
        $sudahHitung = $this->request->getGet('hitung') !== null && $volRencana !== [];

        $hasil = null;
        if ($sudahHitung) {
            $hasil = $this->hitungPilihan($volRencana, $daftarPekerjaan, $hari);
        }

        $sekolah = $this->sekolahAktif();
        $anggaran = (float) ($sekolah['total_anggaran'] ?? 0);
        $terbayar = $this->totalBelanja();

        return $this->tampil('rencana/index', [
            'daftar_pekerjaan' => array_values($daftarPekerjaan),
            'vol_rencana' => $volRencana, 'pilihan_ids' => array_keys($volRencana),
            'hasil' => $hasil, 'hari_kerja' => $hari,
            'gedung_filter' => $gedungFilter,
            'anggaran' => $anggaran, 'terbayar' => $terbayar,
            'sisa_pagu' => $anggaran > 0 ? $anggaran - $terbayar : null,
        ], 'Rencana Belanja — Aplikasi PBJ Sekolah');
    }

    /**
     * Langkah 2–4 dari pilihan pekerjaan: analisa terpakai, kebutuhan bahan
     * bersih (kotor dari volume rencana − sudah dipesan di SPK), dan kebutuhan
     * tenaga (OH + perkiraan jumlah pekerja dari target hari kerja).
     */
    private function hitungPilihan(array $volRencana, array $daftarPekerjaan, int $hari): array
    {
        $db = $this->db();
        $ids = array_keys($volRencana);
        $in = implode(',', array_map('intval', $ids));
        $joinA = 'JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = ?))';

        // Langkah 2: analisa yang dipakai pekerjaan terpilih.
        $analisa = [];
        foreach ($db->query(
            "SELECT a.id, a.kode, a.nama, a.satuan, p.id AS pid FROM pekerjaan p {$joinA}
             WHERE p.id IN ({$in}) AND p.is_deleted = 0 AND a.is_deleted = 0",
            [$this->sid()]
        )->getResultArray() as $r) {
            $aid = (int) $r['id'];
            if (! isset($analisa[$aid])) {
                $analisa[$aid] = $r + ['volume' => 0.0, 'pekerjaan' => 0];
            }
            $analisa[$aid]['volume'] += $volRencana[(int) $r['pid']] ?? 0;
            $analisa[$aid]['pekerjaan']++;
        }
        foreach ($analisa as &$a) {
            $a['harga'] = (float) ($this->hargaAhspId((int) $a['id'], $this->sid()) ?? 0);
            $a['nilai'] = $a['volume'] * $a['harga'];
        }
        unset($a);
        $analisa = array_values($analisa);
        usort($analisa, static fn ($x, $y) => $y['nilai'] <=> $x['nilai']);

        // Langkah 3: kebutuhan bahan dari volume rencana x koefisien.
        $gabung = [];
        foreach ($db->query(
            "SELECT p.id AS pid, b.id AS bahan_id, b.kode, b.nama, b.satuan, ak.satuan AS satuan_komponen,
                    SUM(ak.koefisien) AS koef_total
             FROM pekerjaan p {$joinA}
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'bahan' AND ak.bahan_id IS NOT NULL
             JOIN bahan b ON b.id = ak.bahan_id
             WHERE p.id IN ({$in}) AND p.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0 AND b.is_deleted = 0
             GROUP BY p.id, b.id, b.kode, b.nama, b.satuan, ak.satuan",
            [$this->sid()]
        )->getResultArray() as $r) {
            $id = (int) $r['bahan_id'];
            if (! isset($gabung[$id])) {
                $gabung[$id] = $r + ['qty_butuh' => 0.0, 'dikonversi' => false, 'satuan_beda' => false];
            }
            $faktor = $this->faktorSatuan($r['satuan_komponen'] ?? null, $r);
            $gabung[$id]['qty_butuh'] += ($volRencana[(int) $r['pid']] ?? 0) * (float) $r['koef_total'] * $faktor;
            if ($faktor !== 1.0) {
                $gabung[$id]['dikonversi'] = true;
            } elseif (mb_strtolower(trim((string) ($r['satuan_komponen'] ?? ''))) !== ''
                && mb_strtolower(trim((string) ($r['satuan_komponen'] ?? ''))) !== mb_strtolower(trim((string) $r['satuan']))) {
                $gabung[$id]['satuan_beda'] = true;
            }
        }
        $takTertaut = [];
        foreach ($db->query(
            "SELECT ak.nama, p.id AS pid, SUM(ak.koefisien) AS koef_total
             FROM pekerjaan p {$joinA}
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'bahan' AND ak.bahan_id IS NULL
             WHERE p.id IN ({$in}) AND p.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0
             GROUP BY ak.nama, p.id",
            [$this->sid()]
        )->getResultArray() as $r) {
            $takTertaut[$r['nama']] = ($takTertaut[$r['nama']] ?? 0) + ($volRencana[(int) $r['pid']] ?? 0) * (float) $r['koef_total'];
        }
        arsort($takTertaut);

        $dipesan = [];
        foreach ($db->query(
            'SELECT d.bahan_id, SUM(d.qty) q FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
             WHERE s.sekolah_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0 GROUP BY d.bahan_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $dipesan[(int) $r['bahan_id']] = (float) $r['q'];
        }
        $asalBahan = [];
        foreach ($db->query(
            'SELECT ak.bahan_id, COUNT(*) AS komponen, COUNT(DISTINCT ak.analisa_id) AS analisa, MIN(ak.analisa_id) AS analisa_pertama
             FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.bahan_id IS NOT NULL AND ak.is_deleted = 0 AND a.is_deleted = 0
             GROUP BY ak.bahan_id',
            [$this->sid(), 'bahan']
        )->getResultArray() as $r) {
            $asalBahan[(int) $r['bahan_id']] = $r;
        }
        $tertaut = array_values($gabung);
        usort($tertaut, static fn ($x, $y) => $y['qty_butuh'] <=> $x['qty_butuh']);
        foreach ($tertaut as &$b) {
            $b['harga'] = $this->hargaAcuan((int) $b['bahan_id']);
            $b['dipesan'] = $dipesan[(int) $b['bahan_id']] ?? 0;
            $b['sisa'] = max(0, (float) $b['qty_butuh'] - $b['dipesan']);
            $asal = $asalBahan[(int) $b['bahan_id']] ?? null;
            $b['komponen_dari'] = (int) ($asal['komponen'] ?? 0);
            $b['analisa_dari'] = (int) ($asal['analisa'] ?? 0);
            $b['analisa_pertama'] = (int) ($asal['analisa_pertama'] ?? 0);
        }
        unset($b);

        // Langkah 4: kebutuhan tenaga dari pekerjaan terpilih.
        $upah = $this->upahPilihan($volRencana, $hari);

        return [
            'analisa' => $analisa,
            'kebutuhan' => ['tertaut' => $tertaut, 'tak_tertaut' => $takTertaut],
            'upah' => $upah,
            'jumlah_pekerjaan' => count($volRencana),
        ];
    }

    /**
     * Hapus bahan dari rencana belanja: komponen bahan ini di semua analisa
     * sekolah dihapus lunak (sumber kebutuhan memang dari Analisa), sehingga
     * bahan hilang dari kebutuhan semua gedung. SPK yang sudah terlanjur
     * memakai bahan ini tidak berubah. Dapat dipulihkan dari Sampah.
     */
    public function hapusBahan($id)
    {
        $db = $this->db();
        $bahan = $db->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($bahan === null) {
            return redirect()->to(base_url('rencana-belanja'))->with('galat', 'Bahan tidak ditemukan.');
        }
        $baris = $db->query(
            'SELECT ak.id, ak.analisa_id FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.bahan_id = ? AND ak.is_deleted = 0 AND a.is_deleted = 0',
            [$this->sid(), 'bahan', (int) $id]
        )->getResultArray();
        if ($baris === []) {
            return redirect()->to(base_url('rencana-belanja'))->with('galat', 'Bahan ini sudah tidak memiliki komponen aktif di Analisa.');
        }
        $jmlAnalisa = count(array_unique(array_column($baris, 'analisa_id')));
        $db->table('analisa_komponen')->whereIn('id', array_map('intval', array_column($baris, 'id')))
            ->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
        $diSpk = (int) $db->table('spk_detail')->where(['bahan_id' => (int) $id, 'is_deleted' => 0])->countAllResults();
        $pesan = 'Bahan ' . $bahan['nama'] . ' dihapus dari rencana: ' . count($baris) . ' komponen di ' . $jmlAnalisa . ' analisa dihapus dari Analisa (dapat dipulihkan lewat Pemeliharaan Database, menu Sampah). Bahan ini hilang dari kebutuhan semua gedung.';
        if ($diSpk > 0) {
            $pesan .= ' Catatan: bahan ini sudah tercantum di ' . $diSpk . ' baris SPK — SPK tersebut tidak berubah.';
        }
        return redirect()->to(base_url('rencana-belanja'))->with('sukses', $pesan);
    }

    /** Buat SPK baru berisi bahan-bahan terpilih dari rencana belanja. */
    /**
     * Kebutuhan tenaga (OH per jenis kanonik Pekerja/Tukang) dari pekerjaan
     * terpilih: volume rencana x koefisien komponen upah analisanya.
     * Dipakai Langkah 4 halaman Rencana dan pengisian otomatis catatan
     * tenaga kerja SPK saat dibuat dari rencana belanja.
     */
    private function upahPilihan(array $volRencana, int $hari): array
    {
        if ($volRencana === []) {
            return [];
        }
        $db = $this->db();
        $in = implode(',', array_map('intval', array_keys($volRencana)));
        $joinA = 'JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = ?))';
        $upah = [];
        foreach ($db->query(
            "SELECT p.id AS pid, ak.nama AS jenis, SUM(ak.koefisien) AS koef_total,
                    COALESCE(MAX(um.tarif), MAX(umn.tarif), MAX(ak.tarif), 0) AS tarif
             FROM pekerjaan p {$joinA}
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'upah'
             LEFT JOIN upah_master um ON um.id = ak.upah_id AND um.is_deleted = 0
             LEFT JOIN upah_master umn ON ak.upah_id IS NULL AND umn.sekolah_id = a.sekolah_id AND umn.jenis = ak.nama AND umn.is_deleted = 0
             WHERE p.id IN ({$in}) AND p.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0
               AND NOT EXISTS (SELECT 1 FROM upah_master umh WHERE umh.is_deleted = 1
                     AND (umh.id = ak.upah_id OR (ak.upah_id IS NULL AND umh.sekolah_id = a.sekolah_id AND umh.jenis = ak.nama)))
             GROUP BY p.id, ak.nama",
            [$this->sid()]
        )->getResultArray() as $r) {
            $j = $r['jenis'];
            if (! isset($upah[$j])) {
                $upah[$j] = ['jenis' => $j, 'orang_hari' => 0.0, 'tarif' => (float) $r['tarif']];
            }
            $upah[$j]['orang_hari'] += ($volRencana[(int) $r['pid']] ?? 0) * (float) $r['koef_total'];
            $upah[$j]['tarif'] = max($upah[$j]['tarif'], (float) $r['tarif']);
        }
        $upah = array_values($upah);
        usort($upah, static fn ($x, $y) => $y['orang_hari'] <=> $x['orang_hari']);
        foreach ($upah as &$u) {
            $u['jumlah_pekerja'] = (int) ceil($u['orang_hari'] / max(1, $hari));
            $u['nilai'] = $u['orang_hari'] * $u['tarif'];
        }
        unset($u);
        return $upah;
    }

    public function buatSpk()
    {
        $db = $this->db();
        $pilihan = (array) $this->request->getPost('pilih');
        $qtyPost = (array) $this->request->getPost('qty');
        $gedungId = (int) $this->request->getPost('gedung_id') ?: null;
        if ($gedungId !== null && $this->ambilGedung($gedungId) === null) {
            $gedungId = null;
        }
        $baris = [];
        foreach ($pilihan as $bahanId) {
            $bahanId = (int) $bahanId;
            $qty = (float) str_replace(',', '.', (string) ($qtyPost[$bahanId] ?? 0));
            $b = $db->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($b !== null && $qty > 0) {
                $baris[] = ['bahan' => $b, 'qty' => $qty, 'harga' => $this->hargaAcuan($bahanId)];
            }
        }
        if ($baris === []) {
            return redirect()->to('/rencana-belanja')->with('galat', 'Centang minimal satu bahan dengan jumlah rencana lebih dari 0.');
        }
        $row = $db->query('SELECT MAX(nomor) m FROM spk WHERE sekolah_id = ?', [$this->sid()])->getRow();
        $nomor = ((int) ($row->m ?? 0)) + 1;
        $db->transStart();
        $db->table('spk')->insert([
            'sekolah_id' => $this->sid(), 'nomor' => $nomor, 'tanggal' => date('Y-m-d'),
            'gedung_id' => $gedungId,
            'paket' => 'Rencana belanja dari kebutuhan' . ($gedungId !== null ? ' — ' . ($this->ambilGedung($gedungId)['nama'] ?? '') : ''),
            'status' => 'Aktif', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $spkId = (int) $db->insertID();
        foreach ($baris as $b) {
            $db->table('spk_detail')->insert([
                'spk_id' => $spkId, 'bahan_id' => (int) $b['bahan']['id'], 'sekolah_id' => $this->sid(),
                'qty' => $b['qty'], 'harga' => $b['harga'],
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        // Catatan kebutuhan tenaga kerja SPK: dari pekerjaan terpilih
        // Langkah 1 (dikirim form sebagai kerja[pid]=volume + hari).
        $jmlTenaga = 0;
        $volKerja = [];
        foreach ((array) $this->request->getPost('kerja') as $pid => $v) {
            $pid = (int) $pid;
            $v = (float) str_replace(',', '.', (string) $v);
            if ($pid > 0 && $v > 0) {
                $volKerja[$pid] = $v;
            }
        }
        if ($volKerja !== []) {
            $valid = array_map('intval', array_column($db->query(
                'SELECT p.id FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND g.is_deleted = 0',
                [$this->sid()]
            )->getResultArray(), 'id'));
            $volKerja = array_intersect_key($volKerja, array_flip($valid));
            $hari = max(1, (int) ($this->request->getPost('hari') ?: 6));
            foreach ($this->upahPilihan($volKerja, $hari) as $u) {
                if ((float) $u['orang_hari'] <= 0) {
                    continue;
                }
                $db->table('spk_tenaga')->insert([
                    'sekolah_id' => $this->sid(), 'spk_id' => $spkId, 'jenis' => $u['jenis'], 'keahlian' => null,
                    'oh' => round((float) $u['orang_hari'], 2), 'jumlah_orang' => (int) $u['jumlah_pekerja'],
                    'keterangan' => 'Otomatis dari Rencana Belanja — ' . count($volKerja) . ' pekerjaan terpilih, target ' . $hari . ' hari kerja.',
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $jmlTenaga++;
            }
        }
        $db->transComplete();
        $pesan = 'SPK ' . $nomor . ' dibuat dari rencana belanja (' . count($baris) . ' bahan). Lengkapi penyedia & tanggalnya bila perlu.';
        if ($jmlTenaga > 0) {
            $pesan .= ' Catatan kebutuhan tenaga kerja ikut terisi otomatis (' . $jmlTenaga . ' jenis tenaga) — lihat panel Kebutuhan Tenaga Kerja.';
        }
        return redirect()->to('/spk/detail/' . $spkId)->with('sukses', $pesan);
    }
}
