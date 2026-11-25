<?php

namespace App\Controllers;

/**
 * Rencana Mingguan: target volume pekerjaan per gedung untuk satu minggu.
 * Dari target dihitung daftar belanja bersih minggu itu:
 * kebutuhan kotor (target × koefisien analisa) − stok tersedia
 * (sudah diterima/BAST + saldo Buku Material) − pesanan berjalan
 * (SPK aktif yang belum di-BAST), plus kebutuhan upah dalam Oh.
 */
class RencanaMingguan extends BaseController
{
    protected function ambilRencana(int $id): ?array
    {
        $r = $this->db()->table('rencana_mingguan')
            ->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        return $r ?: null;
    }

    public function index()
    {
        $db = $this->db();
        $daftar = $db->query(
            'SELECT r.*, g.nama AS gedung_nama,
                    (SELECT COUNT(*) FROM rencana_mingguan_detail d WHERE d.rencana_id = r.id AND d.is_deleted = 0 AND d.volume_target > 0) AS jml_target
             FROM rencana_mingguan r JOIN gedung g ON g.id = r.gedung_id
             WHERE r.sekolah_id = ? AND r.is_deleted = 0 AND g.is_deleted = 0
             ORDER BY r.minggu_mulai DESC, r.id DESC',
            [$this->sid()]
        )->getResultArray();
        return $this->tampil('rencana_mingguan/index', [
            'daftar' => $daftar,
            'gedung' => $this->semuaGedung(),
        ], 'Rencana Mingguan — Aplikasi PBJ Sekolah');
    }

    public function buat()
    {
        $gedungId = (int) $this->request->getPost('gedung_id');
        $mulai = trim((string) $this->request->getPost('minggu_mulai'));
        if ($this->ambilGedung($gedungId) === null || $mulai === '') {
            return redirect()->to('rencana-mingguan')->with('galat', 'Pilih gedung dan tanggal mulai minggu terlebih dahulu.');
        }
        $this->db()->table('rencana_mingguan')->insert([
            'sekolah_id' => $this->sid(), 'gedung_id' => $gedungId, 'minggu_mulai' => $mulai,
            'keterangan' => trim((string) $this->request->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db()->insertID();
        $this->catatAudit('rencana_mingguan', $id, 'buat', 'Rencana mingguan dibuat untuk gedung ' . ($this->ambilGedung($gedungId)['nama'] ?? '') . ', minggu mulai ' . $mulai . '.');
        return redirect()->to('rencana-mingguan/' . $id)->with('sukses', 'Rencana mingguan dibuat. Isi target volume pekerjaan minggu ini di bawah.');
    }

    public function hapus($id)
    {
        $r = $this->ambilRencana((int) $id);
        if ($r === null) {
            return redirect()->to('rencana-mingguan')->with('galat', 'Rencana tidak ditemukan.');
        }
        $this->hapusLunak('rencana_mingguan_detail', ['rencana_id' => (int) $id]);
        $this->hapusLunak('rencana_mingguan', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('rencana-mingguan')->with('sukses', 'Rencana mingguan dihapus.');
    }

    public function simpanTarget($id)
    {
        $r = $this->ambilRencana((int) $id);
        if ($r === null) {
            return redirect()->to('rencana-mingguan')->with('galat', 'Rencana tidak ditemukan.');
        }
        $target = (array) $this->request->getPost('target');
        $map = [];
        foreach ($target as $pid => $nilai) {
            $map[(int) $pid] = max(0, (float) str_replace(',', '.', (string) $nilai));
        }
        $this->db()->transStart();
        $this->terapkanTarget((int) $id, (int) $r['gedung_id'], $map);
        $this->db()->transComplete();
        return redirect()->to('rencana-mingguan/' . $id)->with('sukses', 'Target minggu ini disimpan. Daftar belanja bersih di bawah sudah dihitung ulang.');
    }

    /** Terapkan peta target volume per pekerjaan (tambah/ubah/hidupkan/hapus lunak). */
    protected function terapkanTarget(int $rencanaId, int $gedungId, array $map): void
    {
        $db = $this->db();
        $sah = [];
        foreach ($db->table('pekerjaan')->select('id')->where(['gedung_id' => $gedungId, 'is_deleted' => 0])->get()->getResultArray() as $p) {
            $sah[(int) $p['id']] = true;
        }
        foreach ($map as $pid => $vol) {
            $pid = (int) $pid;
            if (! isset($sah[$pid])) {
                continue;
            }
            $vol = max(0, (float) $vol);
            $ada = $db->table('rencana_mingguan_detail')->where(['rencana_id' => $rencanaId, 'pekerjaan_id' => $pid])->get()->getRowArray();
            if ($vol <= 0) {
                if ($ada !== null && (int) $ada['is_deleted'] === 0) {
                    $this->hapusLunak('rencana_mingguan_detail', ['id' => (int) $ada['id']]);
                }
                continue;
            }
            if ($ada !== null) {
                $db->table('rencana_mingguan_detail')->where('id', (int) $ada['id'])->update([
                    'volume_target' => $vol, 'is_deleted' => 0, 'deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $db->table('rencana_mingguan_detail')->insert([
                    'rencana_id' => $rencanaId, 'pekerjaan_id' => $pid, 'volume_target' => $vol,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /** Normalisasi nama bab untuk pemetaan dokumen Kurva S ↔ BOQ aplikasi. */
    protected function normalBab(?string $bab): string
    {
        $s = strtoupper((string) $bab);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s) ?? '';
        $s = trim(preg_replace('/\s+/', ' ', $s) ?? '');
        // Buang angka romawi bab di awal (I, II, III, IV, V, VI, ... XV).
        $s = preg_replace('/^(XV|IV|V?I{0,3}|IX|X) /', '', $s) ?? $s;
        return trim($s);
    }

    /**
     * Isi target otomatis dari rencana Kurva S resmi: untuk minggu kalender
     * yang memuat tanggal rencana ini, target per bab = rencana kumulatif
     * dokumen s.d. minggu itu dikurangi realisasi tercatat (kejar
     * ketertinggalan), lalu dibagi ke pekerjaan dalam bab menurut porsi
     * sisa nilainya. Target lama pada pekerjaan di luar bab terpetakan
     * tidak diubah.
     */
    public function targetKurva($id)
    {
        $r = $this->ambilRencana((int) $id);
        if ($r === null) {
            return redirect()->to('rencana-mingguan')->with('galat', 'Rencana tidak ditemukan.');
        }
        $h = $this->intiTargetKurva($r);
        if (! $h['ok']) {
            return redirect()->to('rencana-mingguan/' . $id)->with('galat', $h['pesan']);
        }
        return redirect()->to('rencana-mingguan/' . $id)->with('sukses', 'Target terisi dari Kurva S (minggu kalender ke-' . $h['ke'] . ', pola kejar ketertinggalan): ' . $h['jumlah'] . ' pekerjaan senilai Rp ' . number_format($h['total'], 0, ',', '.') . '. Silakan sesuaikan bila perlu, lalu gunakan daftar belanja bersih di bawah.');
    }

    /**
     * Inti perhitungan "Ambil Target dari Kurva S" — dipakai targetKurva()
     * (tombol di Rencana Mingguan) dan Kurva::buatRencana() (tombol per
     * baris di halaman Kurva S) agar hanya ada satu jalur hitung target.
     * Mengisi target rencana dari rencana kumulatif Kurva S s.d. minggu
     * kalender yang memuat minggu_mulai rencana, dikurangi realisasi
     * tercatat (pola kejar ketertinggalan), dibagi ke pekerjaan menurut
     * porsi sisa nilai babnya. Target tersimpan di rencana_mingguan_detail
     * — dari sanalah daftar belanja rencana itu dihitung, jadi setiap
     * rencana belanja selalu tercatat kebutuhan pekerjaannya.
     *
     * @return array{ok: bool, pesan: string, ke: int, jumlah: int, total: float}
     */
    protected function intiTargetKurva(array $r): array
    {
        $gagal = static fn (string $pesan, int $ke = 0) => ['ok' => false, 'pesan' => $pesan, 'ke' => $ke, 'jumlah' => 0, 'total' => 0.0];
        $db = $this->db();
        if (! $db->tableExists('kurva_rencana')) {
            return $gagal('Rencana Kurva S belum tersedia (migrasi belum dijalankan).');
        }
        $barisKurva = $db->table('kurva_rencana')
            ->where(['sekolah_id' => $this->sid(), 'gedung_id' => (int) $r['gedung_id'], 'is_deleted' => 0])
            ->get()->getResultArray();
        if ($barisKurva === []) {
            return $gagal('Gedung ini belum punya rencana Kurva S. Impor dulu rencana resmi di halaman Kurva S.');
        }
        // Minggu kalender yang memuat tanggal mulai rencana; bila di luar
        // rentang, pakai minggu terdekat.
        $kalender = $db->table('kurva_minggu')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('minggu_ke')->get()->getResultArray();
        $ke = null;
        foreach ($kalender as $km) {
            if ($r['minggu_mulai'] >= $km['tanggal_mulai'] && $r['minggu_mulai'] <= $km['tanggal_selesai']) {
                $ke = (int) $km['minggu_ke'];
                break;
            }
        }
        if ($ke === null && $kalender !== []) {
            $ke = $r['minggu_mulai'] < $kalender[0]['tanggal_mulai']
                ? (int) $kalender[0]['minggu_ke']
                : (int) $kalender[count($kalender) - 1]['minggu_ke'];
        }
        if ($ke === null) {
            return $gagal('Kalender minggu Kurva S belum terimpor.');
        }
        // Total proyek dokumen = jumlah seluruh baris (grup, bab) sekolah.
        $totalProyek = 0.0;
        foreach ($db->query(
            'SELECT MAX(jumlah) AS j FROM kurva_rencana WHERE sekolah_id = ? AND is_deleted = 0 GROUP BY grup, bab',
            [$this->sid()]
        )->getResultArray() as $row) {
            $totalProyek += (float) $row['j'];
        }
        // Rencana kumulatif per bab ternormalisasi s.d. minggu ke-$ke.
        $rencanaBab = [];
        foreach ($barisKurva as $b) {
            if ((int) $b['minggu_ke'] <= $ke) {
                $k = $this->normalBab($b['bab']);
                $rencanaBab[$k] = ($rencanaBab[$k] ?? 0.0) + (float) $b['bobot_minggu'];
            }
        }
        // Pekerjaan gedung + realisasi + harga berjalan.
        $pekerjaan = $db->query(
            'SELECT p.*, COALESCE(p.volume_revisi, p.volume) AS vol_berjalan,
                    (SELECT COALESCE(SUM(x.volume), 0) FROM realisasi x WHERE x.pekerjaan_id = p.id AND x.is_deleted = 0) AS terealisasi
             FROM pekerjaan p WHERE p.gedung_id = ? AND p.is_deleted = 0 ORDER BY p.urutan, p.id',
            [(int) $r['gedung_id']]
        )->getResultArray();
        $perBab = [];
        foreach ($pekerjaan as $p) {
            $p['harga_berjalan'] = $this->hargaBerjalan($p, $this->sid());
            $perBab[$this->normalBab($p['bab'] ?? '')][] = $p;
        }
        $map = [];
        $totalTarget = 0.0;
        foreach ($rencanaBab as $babNorm => $bobotKum) {
            if (empty($perBab[$babNorm])) {
                continue;
            }
            $targetNilai = $bobotKum / 100 * $totalProyek;
            $sudahNilai = 0.0;
            $sisaNilai = 0.0;
            foreach ($perBab[$babNorm] as $p) {
                $sudahNilai += (float) $p['terealisasi'] * (float) $p['harga_berjalan'];
                $sisaNilai += max(0.0, (float) $p['vol_berjalan'] - (float) $p['terealisasi']) * (float) $p['harga_berjalan'];
            }
            $perlu = max(0.0, $targetNilai - $sudahNilai);
            if ($perlu <= 0 || $sisaNilai <= 0) {
                continue;
            }
            $fraksi = min(1.0, $perlu / $sisaNilai);
            foreach ($perBab[$babNorm] as $p) {
                $sisaVol = max(0.0, (float) $p['vol_berjalan'] - (float) $p['terealisasi']);
                if ($sisaVol <= 0) {
                    continue;
                }
                $t = round($sisaVol * $fraksi, 4);
                if ($t > 0) {
                    $map[(int) $p['id']] = $t;
                    $totalTarget += $t * (float) $p['harga_berjalan'];
                }
            }
        }
        if ($map === []) {
            return $gagal('Tidak ada target yang perlu diisi dari Kurva S minggu ke-' . $ke . ' — realisasi bab terpetakan sudah mencapai rencana kumulatifnya, atau bab dokumen tidak cocok dengan bab BOQ gedung ini.', $ke);
        }
        $db->transStart();
        $this->terapkanTarget((int) $r['id'], (int) $r['gedung_id'], $map);
        $db->transComplete();
        $this->catatAudit('rencana_mingguan', (int) $r['id'], 'target_dari_kurva_s', 'Minggu kalender ke-' . $ke . '; ' . count($map) . ' pekerjaan; nilai target Rp ' . number_format($totalTarget, 0, ',', '.'));
        return ['ok' => true, 'pesan' => '', 'ke' => $ke, 'jumlah' => count($map), 'total' => $totalTarget];
    }

    /** Hitungan belanja & upah dari target tersimpan pada satu rencana. */
    private function hitung(array $r): array
    {
        $db = $this->db();
        $target = [];
        foreach ($db->table('rencana_mingguan_detail')->where(['rencana_id' => $r['id'], 'is_deleted' => 0])->get()->getResultArray() as $d) {
            if ((float) $d['volume_target'] > 0) {
                $target[(int) $d['pekerjaan_id']] = (float) $d['volume_target'];
            }
        }
        if ($target === []) {
            return ['bahan' => [], 'upah' => [], 'total_belanja_bersih' => 0.0, 'total_upah' => 0.0];
        }
        // Kebutuhan kotor bahan: target × koefisien, dikonversi ke satuan master.
        $mentah = $db->query(
            "SELECT b.id AS bahan_id, b.kode, b.nama, b.satuan, ak.satuan AS satuan_komponen,
                    ak.koefisien, d.volume_target, p.id AS pekerjaan_id, a.id AS analisa_id
             FROM rencana_mingguan_detail d
             JOIN pekerjaan p ON p.id = d.pekerjaan_id AND p.is_deleted = 0
             JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = ?))
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'bahan' AND ak.bahan_id IS NOT NULL
             JOIN bahan b ON b.id = ak.bahan_id
             WHERE d.rencana_id = ? AND d.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0 AND b.is_deleted = 0",
            [$this->sid(), $r['id']]
        )->getResultArray();
        $bahan = [];
        foreach ($mentah as $m) {
            $bid = (int) $m['bahan_id'];
            if (! isset($bahan[$bid])) {
                $bahan[$bid] = $m + ['kotor' => 0.0, 'analisa_pertama' => (int) ($m['analisa_id'] ?? 0)];
            }
            $bahan[$bid]['kotor'] += (float) $m['volume_target'] * (float) $m['koefisien'] * $this->faktorSatuan($m['satuan_komponen'] ?? null, $m);
        }

        // Stok & pesanan berjalan per bahan (seluruh sekolah). Diterima =
        // rincian SPK yang tercakup BAST (lewat BAP-nya; BAST lama tanpa
        // bap_id mencakup seluruh SPK) — definisi yang sama dengan
        // Material::stokPerBahan / BaseController::masukBastPerBahan.
        // Stok akhir = diterima − catatan KELUAR Buku Material (catatan
        // masuk manual diabaikan, keputusan AMWAL 25 Nov 2026).
        $diterima = $dalamPesanan = $bukuKeluar = [];
        $syaratBast = '(EXISTS (SELECT 1 FROM bap_detail bd JOIN bap bp ON bp.id = bd.bap_id AND bp.is_deleted = 0 JOIN bast x ON x.bap_id = bd.bap_id AND x.is_deleted = 0 WHERE bd.detail_id = d.id AND bd.is_deleted = 0)'
            . ' OR EXISTS (SELECT 1 FROM bast y WHERE y.spk_id = s.id AND y.bap_id IS NULL AND y.is_deleted = 0))';
        foreach ($db->query(
            'SELECT d.bahan_id,
                    SUM(CASE WHEN ' . $syaratBast . ' THEN d.qty ELSE 0 END) AS diterima,
                    SUM(CASE WHEN ' . $syaratBast . ' THEN 0 ELSE d.qty END) AS berjalan
             FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
             WHERE s.sekolah_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0 GROUP BY d.bahan_id',
            [$this->sid()]
        )->getResultArray() as $s) {
            $diterima[(int) $s['bahan_id']] = (float) $s['diterima'];
            $dalamPesanan[(int) $s['bahan_id']] = (float) $s['berjalan'];
        }
        foreach ($db->query(
            'SELECT m.bahan_id, SUM(m.keluar) AS keluar FROM buku_material m
             JOIN bahan b ON b.id = m.bahan_id WHERE b.sekolah_id = ? AND m.is_deleted = 0 GROUP BY m.bahan_id',
            [$this->sid()]
        )->getResultArray() as $s) {
            $bukuKeluar[(int) $s['bahan_id']] = (float) $s['keluar'];
        }
        $totalBersih = 0.0;
        foreach ($bahan as $bid => &$b) {
            $b['stok'] = ($diterima[$bid] ?? 0) - ($bukuKeluar[$bid] ?? 0);
            $b['berjalan'] = $dalamPesanan[$bid] ?? 0;
            $b['bersih'] = max(0.0, $b['kotor'] - $b['stok'] - $b['berjalan']);
            $b['harga'] = $this->hargaAcuan($bid);
            $b['nilai'] = $b['bersih'] * $b['harga'];
            $totalBersih += $b['nilai'];
        }
        unset($b);
        $bahan = array_values($bahan);
        usort($bahan, static fn ($a, $c) => $c['nilai'] <=> $a['nilai']);

        // Kebutuhan upah: Oh per jenis + nilai dari tarif komponen.
        $upah = [];
        $totalUpah = 0.0;
        foreach ($db->query(
            "SELECT ak.nama, SUM(d.volume_target * ak.koefisien) AS oh, SUM(d.volume_target * ak.koefisien * COALESCE(ak.tarif, 0)) AS nilai
             FROM rencana_mingguan_detail d
             JOIN pekerjaan p ON p.id = d.pekerjaan_id AND p.is_deleted = 0
             JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = ?))
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'upah'
             WHERE d.rencana_id = ? AND d.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0
               AND NOT EXISTS (SELECT 1 FROM upah_master um WHERE um.sekolah_id = a.sekolah_id AND um.jenis = ak.nama AND um.is_deleted = 1)
             GROUP BY ak.nama ORDER BY oh DESC",
            [$this->sid(), $r['id']]
        )->getResultArray() as $u) {
            $upah[] = $u;
            $totalUpah += (float) $u['nilai'];
        }
        return ['bahan' => $bahan, 'upah' => $upah, 'total_belanja_bersih' => $totalBersih, 'total_upah' => $totalUpah];
    }

    public function detail($id)
    {
        $r = $this->ambilRencana((int) $id);
        if ($r === null) {
            return redirect()->to('rencana-mingguan')->with('galat', 'Rencana tidak ditemukan.');
        }
        $db = $this->db();
        $gedung = $this->ambilGedung((int) $r['gedung_id']);
        $pekerjaan = $db->query(
            'SELECT p.*, COALESCE(p.volume_revisi, p.volume) AS vol_berjalan,
                    (SELECT COALESCE(SUM(x.volume), 0) FROM realisasi x WHERE x.pekerjaan_id = p.id AND x.is_deleted = 0) AS terealisasi,
                    (SELECT d.volume_target FROM rencana_mingguan_detail d WHERE d.rencana_id = ? AND d.pekerjaan_id = p.id AND d.is_deleted = 0) AS target
             FROM pekerjaan p WHERE p.gedung_id = ? AND p.is_deleted = 0 ORDER BY p.urutan, p.id',
            [(int) $id, $r['gedung_id']]
        )->getResultArray();
        $hitung = $this->hitung($r);
        $verif = $this->verifikasiAnggaran($this->sid());
        $adaKurva = $db->tableExists('kurva_rencana') && $db->table('kurva_rencana')
            ->where(['sekolah_id' => $this->sid(), 'gedung_id' => (int) $r['gedung_id'], 'is_deleted' => 0])
            ->countAllResults() > 0;
        return $this->tampil('rencana_mingguan/detail', [
            'r' => $r, 'gedung' => $gedung, 'pekerjaan' => $pekerjaan,
            'bahan' => $hitung['bahan'], 'upah' => $hitung['upah'],
            'total_bersih' => $hitung['total_belanja_bersih'], 'total_upah' => $hitung['total_upah'],
            'sisa_pagu' => max(0.0, (float) ($verif['pagu'] ?? 0) - (float) ($verif['total_semua'] ?? 0)),
            'adaKurva' => $adaKurva,
        ], 'Rencana Mingguan — Aplikasi PBJ Sekolah');
    }

    public function buatSpk($id)
    {
        $r = $this->ambilRencana((int) $id);
        if ($r === null) {
            return redirect()->to('rencana-mingguan')->with('galat', 'Rencana tidak ditemukan.');
        }
        $db = $this->db();
        $pilihan = (array) $this->request->getPost('pilih');
        $qtyPost = (array) $this->request->getPost('qty');
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
            return redirect()->to('rencana-mingguan/' . $id)->with('galat', 'Centang minimal satu bahan dengan jumlah beli lebih dari 0.');
        }
        $nomor = $this->nomorSpkBerikutnya();
        $gedung = $this->ambilGedung((int) $r['gedung_id']);
        $db->transStart();
        $db->table('spk')->insert([
            'sekolah_id' => $this->sid(), 'nomor' => $nomor, 'tanggal' => date('Y-m-d'),
            'gedung_id' => (int) $r['gedung_id'],
            'paket' => 'Rencana mingguan ' . date('d/m/Y', strtotime($r['minggu_mulai'])) . ' — ' . ($gedung['nama'] ?? ''),
            'status' => 'Aktif', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $spkId = (int) $db->insertID();
        foreach ($baris as $b) {
            $db->table('spk_detail')->insert([
                'spk_id' => $spkId, 'bahan_id' => (int) $b['bahan']['id'], 'sekolah_id' => $this->sid(),
                'gedung_id' => (int) $r['gedung_id'],
                'qty' => $b['qty'], 'harga' => $b['harga'],
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        $this->catatAudit('spk', $spkId, 'buat', 'SPK ' . $nomor . ' dibuat dari rencana mingguan #' . $id . ' (' . count($baris) . ' bahan).');
        return redirect()->to('/spk/detail/' . $spkId)->with('sukses', 'SPK ' . $nomor . ' dibuat dari rencana mingguan (' . count($baris) . ' bahan). Lengkapi penyedia & tanggalnya bila perlu.');
    }

    /** Daftar rencana bertarget untuk dipilih pada alur SPK gabungan. */
    private function daftarBertarget(): array
    {
        return $this->db()->query(
            'SELECT r.*, g.nama AS gedung_nama,
                    (SELECT COUNT(*) FROM rencana_mingguan_detail d WHERE d.rencana_id = r.id AND d.is_deleted = 0 AND d.volume_target > 0) AS jml_target
             FROM rencana_mingguan r JOIN gedung g ON g.id = r.gedung_id
             WHERE r.sekolah_id = ? AND r.is_deleted = 0 AND g.is_deleted = 0
             ORDER BY r.minggu_mulai DESC, r.id DESC',
            [$this->sid()]
        )->getResultArray();
    }

    public function gabung()
    {
        $daftar = array_values(array_filter($this->daftarBertarget(), static fn ($r) => (int) $r['jml_target'] > 0));
        return $this->tampil('rencana_mingguan/gabung', ['daftar' => $daftar], 'Gabungkan Rencana Mingguan — Aplikasi PBJ Sekolah');
    }

    /**
     * Hitungan gabungan beberapa rencana: kebutuhan kotor per bahan per
     * gedung dijumlahkan, lalu stok tersedia & pesanan berjalan (yang
     * sifatnya se-sekolah) dikurangkan SATU KALI dari gabungan dan
     * sisanya dialokasikan ke gedung menurut porsi kebutuhan kotornya.
     */
    private function hitungGabungan(array $rencanaList): array
    {
        $perBahan = [];
        foreach ($rencanaList as $r) {
            $h = $this->hitung($r);
            foreach ($h['bahan'] as $b) {
                $bid = (int) $b['bahan_id'];
                if (! isset($perBahan[$bid])) {
                    $perBahan[$bid] = [
                        'id' => $bid, 'kode' => $b['kode'], 'nama' => $b['nama'], 'satuan' => $b['satuan'],
                        'harga' => (float) $b['harga'], 'stok' => (float) $b['stok'], 'berjalan' => (float) $b['berjalan'],
                        'per_gedung' => [],
                    ];
                }
                $perBahan[$bid]['per_gedung'][(int) $r['gedung_id']] = ($perBahan[$bid]['per_gedung'][(int) $r['gedung_id']] ?? 0.0) + (float) $b['kotor'];
            }
        }
        $baris = [];
        $total = 0.0;
        foreach ($perBahan as $pb) {
            $totalKotor = array_sum($pb['per_gedung']);
            $totalBersih = max(0.0, $totalKotor - $pb['stok'] - $pb['berjalan']);
            $alokasi = [];
            $teralokasi = 0.0;
            $gedungTerbesar = null;
            foreach ($pb['per_gedung'] as $gid => $kotor) {
                $a = $totalKotor > 0 ? round($totalBersih * $kotor / $totalKotor, 2) : 0.0;
                $alokasi[$gid] = $a;
                $teralokasi += $a;
                if ($gedungTerbesar === null || $kotor > $pb['per_gedung'][$gedungTerbesar]) {
                    $gedungTerbesar = $gid;
                }
            }
            if ($gedungTerbesar !== null && abs($totalBersih - $teralokasi) >= 0.005) {
                $alokasi[$gedungTerbesar] = round($alokasi[$gedungTerbesar] + ($totalBersih - $teralokasi), 2);
            }
            foreach ($alokasi as $gid => $a) {
                if ($a > 0) {
                    $baris[] = [
                        'bahan_id' => $pb['id'], 'kode' => $pb['kode'], 'nama' => $pb['nama'], 'satuan' => $pb['satuan'],
                        'gedung_id' => $gid, 'kotor' => $pb['per_gedung'][$gid], 'qty' => $a,
                        'harga' => $pb['harga'], 'nilai' => $a * $pb['harga'],
                        'stok' => $pb['stok'], 'berjalan' => $pb['berjalan'],
                    ];
                    $total += $a * $pb['harga'];
                }
            }
        }
        usort($baris, static fn ($a, $b) => $b['nilai'] <=> $a['nilai']);
        return ['baris' => $baris, 'total' => $total];
    }

    /** Ambil baris rencana dari daftar id POST, terverifikasi milik sekolah. */
    private function rencanaDariIds(array $ids): array
    {
        $keluar = [];
        foreach ($this->daftarBertarget() as $r) {
            if (in_array((int) $r['id'], $ids, true) && (int) $r['jml_target'] > 0) {
                $keluar[] = $r;
            }
        }
        return $keluar;
    }

    public function gabungPratinjau()
    {
        $ids = array_map('intval', (array) $this->request->getPost('ids'));
        $rencanaList = $this->rencanaDariIds($ids);
        if (count($rencanaList) < 2) {
            return redirect()->to('rencana-mingguan/gabung')->with('galat', 'Pilih minimal dua rencana mingguan yang sudah punya target untuk digabungkan.');
        }
        $gab = $this->hitungGabungan($rencanaList);
        if ($gab['baris'] === []) {
            return redirect()->to('rencana-mingguan/gabung')->with('galat', 'Tidak ada kebutuhan bersih dari rencana terpilih — semua kebutuhan tertutup stok tersedia atau pesanan berjalan.');
        }
        $gedungNama = [];
        foreach ($rencanaList as $r) {
            $gedungNama[(int) $r['gedung_id']] = (string) $r['gedung_nama'];
        }
        return $this->tampil('rencana_mingguan/gabung_pratinjau', [
            'rencana' => $rencanaList, 'baris' => $gab['baris'], 'total' => $gab['total'], 'gedung_nama' => $gedungNama,
        ], 'Pratinjau SPK Gabungan — Aplikasi PBJ Sekolah');
    }

    public function gabungBuat()
    {
        $db = $this->db();
        $ids = array_map('intval', (array) $this->request->getPost('ids'));
        $rencanaList = $this->rencanaDariIds($ids);
        if (count($rencanaList) < 2) {
            return redirect()->to('rencana-mingguan/gabung')->with('galat', 'Pilih minimal dua rencana mingguan yang sudah punya target untuk digabungkan.');
        }
        $qtyPost = (array) $this->request->getPost('qty');
        $baris = [];
        foreach ($qtyPost as $bahanId => $perGedung) {
            foreach ((array) $perGedung as $gedungId => $q) {
                $qty = (float) str_replace(',', '.', (string) $q);
                if ($qty <= 0) {
                    continue;
                }
                $b = $db->table('bahan')->where(['id' => (int) $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
                $g = $this->ambilGedung((int) $gedungId);
                if ($b !== null && $g !== null) {
                    $baris[] = ['bahan' => $b, 'gedung_id' => (int) $gedungId, 'qty' => $qty, 'harga' => $this->hargaAcuan((int) $bahanId)];
                }
            }
        }
        if ($baris === []) {
            return redirect()->to('rencana-mingguan/gabung')->with('galat', 'Tidak ada baris dengan jumlah beli lebih dari 0.');
        }
        $gedungIds = array_values(array_unique(array_column($baris, 'gedung_id')));
        $namaGedung = [];
        $minggu = [];
        foreach ($rencanaList as $r) {
            $namaGedung[(int) $r['gedung_id']] = (string) $r['gedung_nama'];
            $minggu[] = date('d/m/Y', strtotime($r['minggu_mulai']));
        }
        $nomor = $this->nomorSpkBerikutnya();
        $db->transStart();
        $db->table('spk')->insert([
            'sekolah_id' => $this->sid(), 'nomor' => $nomor, 'tanggal' => date('Y-m-d'),
            'gedung_id' => count($gedungIds) === 1 ? $gedungIds[0] : null,
            'paket' => 'Gabungan rencana mingguan ' . implode(', ', array_unique($minggu)) . ' — ' . implode(', ', array_map(static fn ($gid) => $namaGedung[$gid] ?? '', $gedungIds)),
            'status' => 'Aktif', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $spkId = (int) $db->insertID();
        foreach ($baris as $b) {
            $db->table('spk_detail')->insert([
                'spk_id' => $spkId, 'bahan_id' => (int) $b['bahan']['id'], 'sekolah_id' => $this->sid(),
                'gedung_id' => $b['gedung_id'],
                'qty' => $b['qty'], 'harga' => $b['harga'],
                'keterangan' => 'Untuk ' . ($namaGedung[$b['gedung_id']] ?? ''),
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        $this->catatAudit('spk', $spkId, 'buat_gabungan', 'SPK ' . $nomor . ' gabungan dari ' . count($rencanaList) . ' rencana mingguan (' . count($baris) . ' baris rincian, ' . count($gedungIds) . ' gedung).');
        return redirect()->to('/spk/detail/' . $spkId)->with('sukses', 'SPK ' . $nomor . ' gabungan dibuat dari ' . count($rencanaList) . ' rencana mingguan (' . count($baris) . ' baris rincian untuk ' . count($gedungIds) . ' gedung). Lengkapi penyedia & tanggalnya bila perlu.');
    }
}
