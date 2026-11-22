<?php

namespace App\Controllers;

class Bahan extends BaseController
{
    /** Id bahan yang benar-benar digunakan: tertaut komponen analisa aktif atau tercantum di SPK Detail aktif. */
    private function idBahanTerpakai(): array
    {
        $set = [];
        foreach ($this->db()->query(
            'SELECT DISTINCT ak.bahan_id FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.bahan_id IS NOT NULL AND ak.is_deleted = 0 AND a.is_deleted = 0',
            [$this->sid(), 'bahan']
        )->getResultArray() as $r) {
            $set[(int) $r['bahan_id']] = true;
        }
        foreach ($this->db()->query(
            'SELECT DISTINCT d.bahan_id FROM spk_detail d JOIN spk s ON s.id = d.spk_id
             WHERE s.sekolah_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0 AND s.is_deleted = 0',
            [$this->sid()]
        )->getResultArray() as $r) {
            $set[(int) $r['bahan_id']] = true;
        }
        return $set;
    }

    /** Id bahan yang dipakai gedung aktif yang didanai: tertaut komponen
        analisa yang analisanya dipakai pekerjaan gedung tersebut. */
    private function idBahanDipakaiGedung(): array
    {
        $set = [];
        foreach ($this->db()->query(
            'SELECT DISTINCT ak.bahan_id FROM analisa_komponen ak
             JOIN analisa a ON a.id = ak.analisa_id AND a.is_deleted = 0
             JOIN pekerjaan pk ON (pk.analisa_id = a.id OR (pk.analisa_id IS NULL AND pk.kode_analisa = a.kode)) AND pk.is_deleted = 0
             JOIN gedung g ON g.id = pk.gedung_id AND g.is_deleted = 0 AND g.didanai = 1
             WHERE a.sekolah_id = ? AND g.sekolah_id = ? AND ak.jenis = ? AND ak.bahan_id IS NOT NULL AND ak.is_deleted = 0',
            [$this->sid(), $this->sid(), 'bahan']
        )->getResultArray() as $r) {
            $set[(int) $r['bahan_id']] = true;
        }
        return $set;
    }

    /**
     * Daftar perubahan sinkron harga bahan -> tarif komponen analisa.
     * Hanya komponen yang tarifnya BERBEDA dari harga acuan bahannya dan
     * bahannya punya harga acuan > 0 yang masuk daftar.
     */
    private function daftarSinkron(): array
    {
        $berubah = [];
        $tetap = 0;
        $tanpaHarga = 0;
        foreach ($this->db()->query(
            'SELECT ak.id, ak.tarif, ak.nama AS komponen, ak.satuan, ak.bahan_id, a.id AS analisa_id, a.kode AS kode_analisa,
                    b.kode AS kode_bahan, b.nama AS nama_bahan, b.satuan AS satuan_bahan
             FROM analisa_komponen ak
             JOIN analisa a ON a.id = ak.analisa_id AND a.is_deleted = 0
             JOIN bahan b ON b.id = ak.bahan_id AND b.is_deleted = 0
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.bahan_id IS NOT NULL AND ak.is_deleted = 0
               AND EXISTS (
                 SELECT 1 FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
                 WHERE (p.analisa_id = a.id OR (p.analisa_id IS NULL AND p.kode_analisa = a.kode))
                   AND p.is_deleted = 0 AND g.is_deleted = 0 AND g.didanai = 1 AND g.sekolah_id = a.sekolah_id
               )
             ORDER BY a.kode, ak.id',
            [$this->sid(), 'bahan']
        )->getResultArray() as $k) {
            $acuan = $this->hargaAcuan((int) $k['bahan_id']);
            if ($acuan <= 0) {
                $tanpaHarga++;
                continue;
            }
            // Sadar satuan: harga acuan adalah per satuan MASTER, sedangkan
            // tarif komponen per satuan KOMPONEN. Tarif baru per satuan
            // komponen = acuan x faktor; perbandingan memakai tarif yang
            // dinormalkan ke satuan master (tarif / faktor) — selaras
            // dengan kolom Tarif Analisa per Gedung. Tanpa ini, menyalin
            // acuan mentah ke komponen bersatuan kg merusak harga AHSP.
            $faktor = $this->faktorSatuan($k['satuan'] ?? null, [
                'id' => (int) $k['bahan_id'],
                'satuan' => $k['satuan_bahan'] ?? '',
                'nama' => $k['nama_bahan'] ?? '',
            ]);
            if ($faktor <= 0) {
                $faktor = 1.0;
            }
            $normal = (float) $k['tarif'] / $faktor;
            if (abs($normal - $acuan) < 1) {
                $tetap++;
                continue;
            }
            $berubah[] = [
                'komponen_id' => (int) $k['id'], 'analisa_id' => (int) $k['analisa_id'],
                'kode_analisa' => $k['kode_analisa'], 'komponen' => $k['komponen'], 'satuan' => $k['satuan'],
                'kode_bahan' => $k['kode_bahan'], 'nama_bahan' => $k['nama_bahan'],
                'tarif_lama' => (float) $k['tarif'], 'tarif_baru' => round($acuan * $faktor, 2),
            ];
        }
        return ['berubah' => $berubah, 'tetap' => $tetap, 'tanpa_harga' => $tanpaHarga];
    }

    /** Pratinjau sinkron (AJAX): hanya tarif yang berubah yang dikirim. */
    public function sinkronPratinjau()
    {
        $d = $this->daftarSinkron();
        return $this->response->setJSON([
            'berubah' => $d['berubah'], 'tetap' => $d['tetap'], 'tanpa_harga' => $d['tanpa_harga'],
            'analisa_terdampak' => count(array_unique(array_column($d['berubah'], 'analisa_id'))),
        ]);
    }

    /** Terapkan sinkron: tarif komponen analisa := harga acuan bahannya. */
    public function sinkronAnalisa()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $d = $this->daftarSinkron();
        if ($d['berubah'] === []) {
            return redirect()->to('/bahan')->with('sukses', 'Tidak ada tarif yang berubah — semua tarif komponen analisa sudah sama dengan harga bahan.');
        }
        $db = $this->db();
        $db->transStart();
        $analisaIds = [];
        foreach ($d['berubah'] as $b) {
            $db->table('analisa_komponen')->where('id', $b['komponen_id'])->update(['tarif' => $b['tarif_baru']]);
            $analisaIds[$b['analisa_id']] = true;
        }
        foreach (array_keys($analisaIds) as $aid) {
            $total = (float) ($db->query(
                'SELECT COALESCE(SUM(koefisien * COALESCE(tarif, 0)), 0) AS t FROM analisa_komponen WHERE analisa_id = ? AND is_deleted = 0',
                [$aid]
            )->getRow()->t ?? 0);
            $db->table('analisa')->where('id', $aid)->update(['harga_satuan' => $total]);
        }
        $db->transComplete();
        $this->catatAudit('analisa_komponen', null, 'sinkron_harga_bahan', count($d['berubah']) . ' komponen di ' . count($analisaIds) . ' analisa disinkronkan dari harga bahan');
        return redirect()->to('/bahan')->with('sukses', 'Sinkron selesai: ' . count($d['berubah']) . ' tarif komponen di ' . count($analisaIds) . ' analisa kini mengikuti harga bahan. Nilai BOQ dashboard sudah berubah mengikuti hasil AHSP baru.');
    }

    public function index()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $tab = (string) ($this->request->getGet('tab') ?? 'gedung') === 'luar' ? 'luar' : 'gedung';
        $bahan = $this->ambilBahan($q, $tab);
        $setTerpakai = $this->idBahanDipakaiGedung();
        $upah = $this->db()->table('upah_master')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('jenis')->get()->getResultArray();
        return $this->tampil('bahan/index', [
            'bahan' => $bahan, 'q' => $q, 'upah' => $upah,
            'tab' => $tab, 'jmlTerpakai' => count($setTerpakai),
            'totalBahan' => $this->db()->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults(),
            'totalUpah' => count($upah),
            'bahanTanpaHarga' => array_values(array_filter($this->db()->query(
                'SELECT b.id, b.kode, b.nama, b.satuan FROM bahan b
                 LEFT JOIN negosiasi n ON n.bahan_id = b.id AND n.is_deleted = 0
                 WHERE b.sekolah_id = ? AND b.is_deleted = 0
                   AND (b.harga_rab IS NULL OR b.harga_rab = 0)
                   AND (n.harga_nego IS NULL OR n.harga_nego = 0)
                   AND (n.harga_penawaran IS NULL OR n.harga_penawaran = 0)
                 ORDER BY b.kode',
                [$this->sid()]
            )->getResultArray(), fn ($b) => isset($setTerpakai[(int) $b['id']]))),
            'upahTanpaTarif' => array_values(array_filter($upah, static fn ($u) => (float) $u['tarif'] <= 0)),
            'duplikatBahan' => $this->grupDuplikat($bahan),
            'duplikatUpah' => $this->grupDuplikat($upah, 'jenis'),
        ], 'Master Bahan & Upah — Aplikasi PBJ Sekolah');
    }

    /** Daftar bahan aktif sekolah ini (dengan total RAB & pemakaian SPK). */
    private function ambilBahan(string $q = '', string $tab = 'gedung'): array
    {
        $sql = 'SELECT b.*, (b.qty_rkb+b.qty_adm+b.qty_toilet) AS qty_total,
                    (b.qty_rkb+b.qty_adm+b.qty_toilet)*b.harga_rab AS anggaran,
                    COALESCE(SUM(d.qty),0) AS terpakai
             FROM bahan b LEFT JOIN spk_detail d ON d.bahan_id = b.id AND d.is_deleted = 0
             WHERE b.sekolah_id = ? AND b.is_deleted = 0';
        $bind = [$this->sid()];
        if ($q !== '') {
            $sql .= ' AND (b.nama LIKE ? OR b.kode LIKE ?)';
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
        }
        $sql .= ' GROUP BY b.id ORDER BY b.kode';
        $baris = $this->db()->query($sql, $bind)->getResultArray();
        // Qty RAB utama = kebutuhan langsung dari BOQ × analisa (sama seperti
        // Kebutuhan Bahan & Rencana Belanja). Kolom lama qty_rkb/adm/toilet
        // hanya terisi oleh impor Excel v4 — untuk sekolah hasil impor
        // perencana kolom itu 0 semua, jadi tampilkan hitungan langsungnya
        // bila bahan ini memang terpakai di pekerjaan gedung aktif.
        $petaButuh = [];
        foreach ($this->kebutuhanBahanSekolah()['tertaut'] as $b) {
            $petaButuh[(int) $b['bahan_id']] = (float) $b['qty_butuh'];
        }
        if ($tab === 'gedung' || $tab === 'luar') {
            $set = $this->idBahanDipakaiGedung();
            $baris = array_values(array_filter($baris, static fn ($r) => $tab === 'gedung' ? isset($set[(int) $r['id']]) : ! isset($set[(int) $r['id']])));
        }
        foreach ($baris as &$r) {
            $langsung = $petaButuh[(int) $r['id']] ?? null;
            if ($langsung !== null) {
                $r['qty_total'] = $langsung;
                $r['anggaran']  = $langsung * (float) $r['harga_rab'];
            }
        }
        unset($r);
        $this->lampirkanTarifGedung($baris);
        return $baris;
    }


    /**
     * Tarif komponen analisa per gedung didanai untuk tiap bahan, sebagai
     * pembanding harga modul Bahan vs modul Analisa. Status 'sama' bila
     * semua tarif komponen — sesudah dinormalkan ke satuan master lewat
     * faktorSatuan() (mis. tarif per kg ÷ 1/50 = per zak) — sama dengan
     * harga acuan bahan; 'beda' bila ada yang menyimpang; 'nol' bila
     * harga acuan bahan sendiri masih 0 sehingga tak bisa dibandingkan.
     */
    private function lampirkanTarifGedung(array &$baris): void
    {
        if ($baris === []) {
            return;
        }
        $sid = $this->sid();
        $perBahan = [];
        foreach ($this->db()->query(
            'SELECT ak.bahan_id, g.nama AS gedung, ak.tarif, ak.satuan AS satuan_komp
             FROM analisa_komponen ak
             JOIN analisa a ON a.id = ak.analisa_id AND a.is_deleted = 0 AND a.sekolah_id = ?
             JOIN pekerjaan p ON (p.analisa_id = a.id OR (p.analisa_id IS NULL AND p.kode_analisa = a.kode)) AND p.is_deleted = 0
             JOIN gedung g ON g.id = p.gedung_id AND g.is_deleted = 0 AND g.didanai = 1 AND g.sekolah_id = ?
             WHERE ak.jenis = ? AND ak.bahan_id IS NOT NULL AND ak.is_deleted = 0
             ORDER BY g.nama, ak.tarif',
            [$sid, $sid, 'bahan']
        )->getResultArray() as $k) {
            $perBahan[(int) $k['bahan_id']][(string) $k['gedung']][] = $k;
        }
        $nego = [];
        foreach ($this->db()->table('negosiasi')->where(['is_deleted' => 0])->get()->getResultArray() as $n) {
            $nego[(int) $n['bahan_id']] = $n;
        }
        foreach ($baris as &$r) {
            $id = (int) $r['id'];
            $r['tarif_gedung'] = [];
            $r['status_harga'] = null;
            if (! isset($perBahan[$id])) {
                continue;
            }
            $acuan = (float) ($r['harga_rab'] ?? 0);
            if (isset($nego[$id])) {
                if (! empty($nego[$id]['harga_nego'])) {
                    $acuan = (float) $nego[$id]['harga_nego'];
                } elseif (! empty($nego[$id]['harga_penawaran'])) {
                    $acuan = (float) $nego[$id]['harga_penawaran'];
                }
            }
            $semuaSama = $acuan > 0;
            foreach ($perBahan[$id] as $gedung => $komps) {
                $unik = [];
                foreach ($komps as $k) {
                    $tarif = (float) $k['tarif'];
                    $faktor = $this->faktorSatuan($k['satuan_komp'] ?? null, $r);
                    $normal = $faktor > 0 ? $tarif / $faktor : $tarif;
                    if ($acuan <= 0 || abs($normal - $acuan) >= 1) {
                        $semuaSama = false;
                    }
                    $kunci = number_format($tarif, 2, '.', '') . '|' . (string) ($k['satuan_komp'] ?? '');
                    $unik[$kunci] = ['tarif' => $tarif, 'satuan' => (string) ($k['satuan_komp'] ?? ''), 'sama' => $acuan > 0 && abs($normal - $acuan) < 1];
                }
                $r['tarif_gedung'][] = ['gedung' => $gedung, 'nilai' => array_values($unik)];
            }
            $r['status_harga'] = $acuan <= 0 ? 'nol' : ($semuaSama ? 'sama' : 'beda');
            // Bila tarifnya sama persis di semua gedung, ringkas jadi satu
            // baris "Semua Gedung" agar baris tabel tidak menjulang.
            $pasangan = [];
            foreach ($r['tarif_gedung'] as $tg) {
                foreach ($tg['nilai'] as $n) {
                    $pasangan[number_format($n['tarif'], 2, '.', '') . '|' . $n['satuan']] = $n;
                }
            }
            $r['tarif_seragam'] = count($pasangan) === 1
                ? ['nilai' => array_values($pasangan)[0], 'jumlah' => count($r['tarif_gedung'])]
                : null;
        }
        unset($r);
    }

    /** Potongan tabel bahan untuk pencarian langsung (AJAX gaya Livewire). */
    public function tabel()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $tab = (string) ($this->request->getGet('tab') ?? 'gedung') === 'luar' ? 'luar' : 'gedung';
        return view('bahan/_tabel', ['bahan' => $this->ambilBahan($q, $tab)]);
    }

    /** Hapus massal bahan tercentang; yang sudah dipakai SPK dilewati. */
    public function hapusMassal()
    {
        $ids = array_values(array_unique(array_map('intval', (array) $this->request->getPost('ids'))));
        [$dihapus, $dilewati] = $this->hapusBahanBanyak($ids);
        return $this->laporHapusBahan($dihapus, $dilewati, 'terpilih');
    }

    /** Kosongkan master bahan sekolah aktif (yang dipakai SPK dipertahankan). */
    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('master', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('bahan')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        [$dihapus, $dilewati] = $this->hapusBahanBanyak(array_map('intval', $ids));
        return $this->laporHapusBahan($dihapus, $dilewati, 'master');
    }

    /** @return array{0:int,1:array<int,string>} [jumlah terhapus, kode dilewati] */
    private function hapusBahanBanyak(array $ids): array
    {
        $dihapus = 0;
        $dilewati = [];
        foreach ($ids as $id) {
            $b = $this->db()->table('bahan')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if (! $b) {
                continue;
            }
            $dipakai = $this->db()->table('spk_detail')->where(['bahan_id' => $id, 'is_deleted' => 0])->countAllResults();
            if ($dipakai > 0) {
                $dilewati[] = $b['kode'];
                continue;
            }
            $this->putusTautanKomponen($id);
            $this->hapusLunak('bahan', ['id' => $id, 'sekolah_id' => $this->sid()]);
            $dihapus++;
        }
        return [$dihapus, $dilewati];
    }

    /**
     * Penjaga relasi saat bahan dihapus: komponen analisa yang tertaut
     * diputus tautannya (bahan_id -> NULL) agar muncul kembali di panel
     * Komponen Belum Tertaut dan bisa ditautkan ulang, bukan menunjuk
     * bahan yang sudah tidak aktif (audit relasi 22 Nov 2026).
     */
    private function putusTautanKomponen(int $bahanId): void
    {
        $this->db()->table('analisa_komponen')
            ->where(['bahan_id' => $bahanId, 'is_deleted' => 0])
            ->update(['bahan_id' => null]);
    }

    private function laporHapusBahan(int $dihapus, array $dilewati, string $konteks)
    {
        if ($dihapus === 0 && $dilewati === []) {
            return redirect()->to('bahan')->with('galat', 'Tidak ada bahan yang diproses.');
        }
        $pesan = $dihapus . ' bahan ' . ($konteks === 'master' ? 'dihapus dari master.' : 'terpilih dihapus.');
        if ($dilewati !== []) {
            $pesan .= ' ' . count($dilewati) . ' bahan dipertahankan karena sudah dipakai di SPK Detail: ' . implode(', ', $dilewati) . '.';
        }
        return redirect()->to('bahan')->with($dihapus > 0 ? 'sukses' : 'galat', $pesan);
    }

    /** Hapus massal jenis tenaga tercentang dari Master Upah. */
    public function upahHapusMassal()
    {
        $ids = array_values(array_unique(array_map('intval', (array) $this->request->getPost('ids'))));
        $n = 0;
        foreach ($ids as $id) {
            $ada = $this->db()->table('upah_master')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('upah_master', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        return redirect()->to('bahan')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' jenis tenaga terpilih dihapus dari Master Upah.' : 'Tidak ada jenis tenaga yang diproses.');
    }

    /** Kosongkan Master Upah sekolah aktif. */
    public function upahKosongkan()
    {
        $ids = array_column($this->db()->table('upah_master')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        foreach ($ids as $id) {
            $this->hapusLunak('upah_master', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        }
        return redirect()->to('bahan')->with('sukses', 'Master Upah dikosongkan: ' . count($ids) . ' jenis tenaga dihapus. Pembayaran upah yang sudah tercatat tidak berubah.');
    }

    /**
     * Rapikan Master Upah menjadi benar-benar 2 baris (Pekerja & Tukang):
     * semua varian nama upah pada komponen analisa diseragamkan ke nama
     * kanoniknya dan ditautkan ke master kanonik; master lain dihapus
     * lunak. Komponen bernama lain (mis. Peralatan) tidak disentuh —
     * tautannya dilepas agar tarifnya tetap terbaca dari komponen sendiri.
     */
    public function upahRapikan()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $db = $this->db();
        $sid = $this->sid();
        $db->transStart();

        // 1) Pastikan master kanonik ada; tarif dari master sejenis terbesar.
        $masterKanon = [];
        foreach (['Pekerja', 'Tukang'] as $kanon) {
            $ada = $db->table('upah_master')->where(['sekolah_id' => $sid, 'jenis' => $kanon, 'is_deleted' => 0])->get()->getRowArray();
            if (! $ada) {
                $tarif = 0.0;
                foreach ($db->table('upah_master')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $m) {
                    if ($this->upahKanon($m['jenis']) === $kanon) {
                        $tarif = max($tarif, (float) $m['tarif']);
                    }
                }
                if ($tarif <= 0) {
                    $tarif = (float) ($db->query(
                        "SELECT COALESCE(MAX(ak.tarif), 0) AS t FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
                         WHERE a.sekolah_id = ? AND ak.jenis = 'upah' AND ak.is_deleted = 0",
                        [$sid]
                    )->getRow()->t ?? 0);
                }
                $db->table('upah_master')->insert([
                    'sekolah_id' => $sid, 'jenis' => $kanon, 'satuan' => 'Oh', 'tarif' => $tarif,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $ada = ['id' => (int) $db->insertID()];
            }
            $masterKanon[$kanon] = (int) $ada['id'];
        }

        // 2) Seragamkan nama + tautan komponen upah (upah_id dibaca dari
        //    nama LAMA: penugasan SET MySQL berjalan kiri ke kanan).
        $db->query(
            "UPDATE analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             SET ak.upah_id = CASE
                   WHEN LOWER(ak.nama) LIKE '%pekerja%' THEN ?
                   WHEN LOWER(ak.nama) LIKE '%tukang%' OR LOWER(ak.nama) LIKE '%mandor%' THEN ?
                   ELSE ak.upah_id END,
                 ak.nama = CASE
                   WHEN LOWER(ak.nama) LIKE '%pekerja%' THEN 'Pekerja'
                   WHEN LOWER(ak.nama) LIKE '%tukang%' OR LOWER(ak.nama) LIKE '%mandor%' THEN 'Tukang'
                   ELSE ak.nama END
             WHERE a.sekolah_id = ? AND ak.jenis = 'upah' AND ak.is_deleted = 0",
            [$masterKanon['Pekerja'], $masterKanon['Tukang'], $sid]
        );
        $diseragamkan = $db->affectedRows();

        // 3) Lepas tautan komponen tak dikenali dari master yang akan digabung,
        //    lalu hapus lunak semua master selain dua kanonik.
        $lainnya = $db->table('upah_master')->where(['sekolah_id' => $sid, 'is_deleted' => 0])
            ->whereNotIn('id', array_values($masterKanon))->get()->getResultArray();
        foreach ($lainnya as $m) {
            $db->query(
                'UPDATE analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id SET ak.upah_id = NULL WHERE ak.upah_id = ? AND a.sekolah_id = ?',
                [(int) $m['id'], $sid]
            );
            $this->hapusLunak('upah_master', ['id' => (int) $m['id'], 'sekolah_id' => $sid]);
        }
        $db->transComplete();
        $this->catatAudit('upah_master', null, 'rapikan_upah_2_jenis', $diseragamkan . ' komponen diseragamkan; ' . count($lainnya) . ' master digabung ke Pekerja & Tukang');
        return redirect()->to('bahan')->with('sukses', 'Master Upah dirapikan menjadi 2 jenis (Pekerja & Tukang): ' . $diseragamkan . ' komponen analisa diseragamkan dan ' . count($lainnya) . ' jenis master digabungkan. Komponen bernama lain (mis. Peralatan) tidak diubah.');
    }

    /** Bentuk normal nama untuk pencocokan duplikat (huruf kecil, spasi tunggal). */
    public static function normalNama(string $s): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($s)));
    }

    /**
     * Kelompok baris dengan nama normal yang sama tetapi satuan/harga
     * (atau tarif) berbeda — inilah duplikat yang harus digabung agar
     * satu bahan/upah hanya punya satu satuan dan satu harga acuan.
     */
    private function grupDuplikat(array $rows, string $kolomNama = 'nama'): array
    {
        $kelompok = [];
        foreach ($rows as $r) {
            $kelompok[self::normalNama((string) $r[$kolomNama])][] = $r;
        }
        $hasil = [];
        foreach ($kelompok as $nama => $anggota) {
            if (count($anggota) < 2) {
                continue;
            }
            $hasil[] = ['nama' => $anggota[0][$kolomNama], 'anggota' => $anggota];
        }
        return $hasil;
    }

    /** Id bahan lain dengan nama normal yang sama (untuk cegah duplikat). */
    private function namaBahanDipakai(string $nama, int $kecualiId = 0): ?array
    {
        foreach ($this->db()->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray() as $b) {
            if ((int) $b['id'] !== $kecualiId && self::normalNama($b['nama']) === self::normalNama($nama)) {
                return $b;
            }
        }
        return null;
    }

    /** Simpan isian massal panel Kelengkapan Harga & Tarif. */
    public function kelengkapanSimpan()
    {
        $db = $this->db();
        $nB = 0;
        $nU = 0;
        foreach ((array) ($this->request->getPost('harga') ?? []) as $id => $nilai) {
            $nilai = (int) $nilai;
            if ($nilai <= 0) {
                continue;
            }
            if ($db->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->update(['harga_rab' => $nilai])) {
                $nB++;
            }
        }
        foreach ((array) ($this->request->getPost('tarif') ?? []) as $id => $nilai) {
            $nilai = (int) $nilai;
            if ($nilai <= 0) {
                continue;
            }
            if ($db->table('upah_master')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->update(['tarif' => $nilai])) {
                $nU++;
            }
        }
        return redirect()->to('bahan')->with('sukses', "Kelengkapan disimpan: {$nB} harga bahan dan {$nU} tarif upah terisi.");
    }

    public function baru()
    {
        return $this->tampil('bahan/form', ['b' => ['kode' => $this->kodeBerikutnya()], 'aksi' => 'bahan/simpan'], 'Tambah Bahan');
    }

    /** Kode bahan berikutnya (B + nomor terbesar + 1) untuk sekolah aktif. */
    private function kodeBerikutnya(): string
    {
        $row = $this->db()->query('SELECT MAX(CAST(SUBSTRING(kode,2) AS UNSIGNED)) m FROM bahan WHERE sekolah_id = ? AND is_deleted = 0', [$this->sid()])->getRow();
        return 'B' . str_pad((string) (((int) $row->m) + 1), 3, '0', STR_PAD_LEFT);
    }

    /** JSON kode berikutnya — dipakai modal Tambah Bahan. */
    public function kodeBaru()
    {
        return $this->response->setJSON(['kode' => $this->kodeBerikutnya()]);
    }

    /** Balasan simpan bahan: JSON untuk modal (AJAX), redirect untuk form halaman. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return $ok
            ? redirect()->to('bahan')->with('sukses', $pesan)
            : redirect()->back()->withInput()->with('galat', $pesan);
    }

    public function simpan()
    {
        $data = $this->ambilForm();
        if ($this->db()->table('bahan')->where(['kode' => $data['kode'], 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults() > 0) {
            return $this->balasForm(false, 'Kode ' . $data['kode'] . ' sudah dipakai.');
        }
        if ($sama = $this->namaBahanDipakai($data['nama'])) {
            return $this->balasForm(false, 'Bahan "' . $data['nama'] . '" sudah ada di master sebagai ' . $sama['kode'] . ' (' . $sama['satuan'] . ', ' . $this->rp($sama['harga_rab']) . '). Satu bahan hanya boleh punya satu satuan dan satu harga — ubah bahan yang sudah ada, atau gabungkan bila terlanjur ganda.');
        }
        // Baris terhapus-lunak berkode sama dihidupkan kembali (kunci unik
        // (sekolah, kode) dipegang baris lunak itu, jadi insert akan gagal).
        $lama = $this->db()->table('bahan')->where(['kode' => $data['kode'], 'sekolah_id' => $this->sid(), 'is_deleted' => 1])->get()->getRowArray();
        if ($lama) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['is_deleted'] = 0;
            $data['deleted_at'] = null;
            $this->db()->table('bahan')->where('id', (int) $lama['id'])->update($data);
            return $this->balasForm(true, 'Bahan ' . $data['kode'] . ' dihidupkan kembali dari data yang pernah dihapus.');
        }
        $data['sekolah_id'] = $this->sid();
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->insert($data);
        return $this->balasForm(true, 'Bahan ' . $data['kode'] . ' ditambahkan.');
    }

    public function ubah($id)
    {
        $b = $this->db()->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $b) {
            return redirect()->to('bahan')->with('galat', 'Bahan tidak ditemukan.');
        }
        return $this->tampil('bahan/form', ['b' => $b, 'aksi' => 'bahan/perbarui/' . $id], 'Ubah Bahan ' . $b['kode']);
    }

    public function perbarui($id)
    {
        $ada = $this->db()->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
        if ($ada === 0) {
            return $this->balasForm(false, 'Bahan tidak ditemukan.');
        }
        $data = $this->ambilForm();
        // Kolom qty warisan (qty_rkb/adm/toilet) tidak lagi tampil di form
        // (audit relasi 22 Nov 2026): bila tidak terkirim, nilai lama
        // dipertahankan agar data impor Excel v4 tidak terhapus.
        if ($this->request->getPost('qty_rkb') === null) {
            unset($data['qty_rkb'], $data['qty_adm'], $data['qty_toilet']);
        }
        if ($sama = $this->namaBahanDipakai($data['nama'], (int) $id)) {
            return $this->balasForm(false, 'Nama itu sudah dipakai bahan ' . $sama['kode'] . '. Satu bahan hanya boleh punya satu satuan dan satu harga — gunakan fitur Gabungkan Duplikat di halaman Master Bahan.');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('bahan')->where(['id' => (int) $id, 'sekolah_id' => $this->sid()])->update($data);
        return $this->balasForm(true, 'Bahan ' . $data['kode'] . ' diperbarui.');
    }

    public function hapus($id)
    {
        $dipakai = $this->db()->table('spk_detail')->where(['bahan_id' => (int) $id, 'is_deleted' => 0])->countAllResults();
        if ($dipakai > 0) {
            return redirect()->to('bahan')->with('galat', 'Bahan ini sudah dipakai di SPK Detail — tidak bisa dihapus.');
        }
        $this->putusTautanKomponen((int) $id);
        $this->hapusLunak('bahan', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('bahan')->with('sukses', 'Bahan dihapus.');
    }

    /**
     * Gabungkan duplikat bahan menjadi satu master: semua referensi
     * (SPK detail, komponen analisa, survei harga, negosiasi, buku
     * material) dialihkan ke bahan utama, kuantitas RAB dijumlahkan,
     * lalu baris duplikat dihapus lunak. Sesudah ini bahan tersebut
     * hanya punya satu satuan dan satu harga acuan.
     */
    public function gabung()
    {
        $utamaId = (int) $this->request->getPost('utama');
        $anggota = array_map('intval', (array) $this->request->getPost('anggota'));
        $utama = $this->db()->table('bahan')->where(['id' => $utamaId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $utama || ! in_array($utamaId, $anggota, true) || count($anggota) < 2) {
            return redirect()->to('bahan')->with('galat', 'Pilih bahan utama dari kelompok duplikatnya.');
        }
        $db = $this->db();
        $db->transStart();
        // Perbaikan dulu, baru gabung: satuan & harga final yang diisi
        // pengguna pada formulir alert menjadi satuan/harga master.
        $satuanFinal = trim((string) $this->request->getPost('satuan_final'));
        $hargaFinal = $this->request->getPost('harga_final');
        $perbaikan = ['updated_at' => date('Y-m-d H:i:s')];
        if ($satuanFinal !== '') {
            $perbaikan['satuan'] = $satuanFinal;
        }
        if ($hargaFinal !== null && $hargaFinal !== '') {
            $perbaikan['harga_rab'] = (int) $hargaFinal;
        }
        if (count($perbaikan) > 1) {
            $db->table('bahan')->where('id', $utamaId)->update($perbaikan);
        }
        foreach ($anggota as $aid) {
            if ($aid === $utamaId) {
                continue;
            }
            $lain = $db->table('bahan')->where(['id' => $aid, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if (! $lain) {
                continue;
            }
            foreach (['spk_detail', 'analisa_komponen', 'survei_harga', 'negosiasi', 'buku_material'] as $tabel) {
                $db->table($tabel)->where('bahan_id', $aid)->update(['bahan_id' => $utamaId]);
            }
            $db->table('bahan')->where('id', $utamaId)->update([
                'qty_rkb' => (float) $utama['qty_rkb'] + (float) $lain['qty_rkb'],
                'qty_adm' => (float) $utama['qty_adm'] + (float) $lain['qty_adm'],
                'qty_toilet' => (float) $utama['qty_toilet'] + (float) $lain['qty_toilet'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $utama['qty_rkb'] = (float) $utama['qty_rkb'] + (float) $lain['qty_rkb'];
            $utama['qty_adm'] = (float) $utama['qty_adm'] + (float) $lain['qty_adm'];
            $utama['qty_toilet'] = (float) $utama['qty_toilet'] + (float) $lain['qty_toilet'];
            $this->hapusLunak('bahan', ['id' => $aid, 'sekolah_id' => $this->sid()]);
        }
        $db->transComplete();
        return redirect()->to('bahan')->with('sukses', 'Duplikat "' . $utama['nama'] . '" digabung ke ' . $utama['kode'] . ' — semua referensi dialihkan dan kuantitas RAB dijumlahkan.');
    }

    // ---------- Master Upah (referensi bersama modul Upah & Analisa) ----------

    public function upahSimpan()
    {
        $jenis = trim((string) $this->request->getPost('jenis'));
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif'));
        if ($jenis === '') {
            return redirect()->to('bahan')->with('galat', 'Jenis tenaga wajib diisi.');
        }
        foreach ($this->db()->table('upah_master')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray() as $u) {
            if (self::normalNama($u['jenis']) === self::normalNama($jenis)) {
                return redirect()->to('bahan')->with('galat', 'Jenis tenaga "' . $jenis . '" sudah ada di Master Upah dengan tarif ' . $this->rp($u['tarif']) . '. Satu jenis tenaga hanya boleh punya satu tarif acuan.');
            }
        }
        // Baris terhapus-lunak berjenis sama dihidupkan kembali.
        foreach ($this->db()->table('upah_master')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 1])->get()->getResultArray() as $u) {
            if (self::normalNama($u['jenis']) === self::normalNama($jenis)) {
                $this->db()->table('upah_master')->where('id', (int) $u['id'])->update([
                    'jenis' => $jenis, 'satuan' => trim((string) $this->request->getPost('satuan')) ?: 'OH',
                    'tarif' => $tarif, 'is_deleted' => 0, 'deleted_at' => null,
                ]);
                return redirect()->to('bahan')->with('sukses', 'Jenis tenaga "' . $jenis . '" dihidupkan kembali di Master Upah.');
            }
        }
        $this->db()->table('upah_master')->insert([
            'sekolah_id' => $this->sid(), 'jenis' => $jenis,
            'satuan' => trim((string) $this->request->getPost('satuan')) ?: 'OH', 'tarif' => $tarif,
        ]);
        return redirect()->to('bahan')->with('sukses', 'Jenis tenaga "' . $jenis . '" ditambahkan ke Master Upah.');
    }

    public function upahPerbarui($id)
    {
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif'));
        $this->db()->table('upah_master')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->update(['tarif' => $tarif]);
        return redirect()->to('bahan')->with('sukses', 'Tarif Master Upah diperbarui.');
    }

    public function upahHapus($id)
    {
        $this->hapusLunak('upah_master', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        return redirect()->to('bahan')->with('sukses', 'Jenis tenaga dihapus dari Master Upah. Pembayaran upah yang sudah tercatat tidak berubah (tarif tersimpan sebagai potret saat pembayaran).');
    }

    /**
     * Gabungkan duplikat jenis tenaga: tarif & satuan mengikuti baris
     * utama, baris lain dihapus. Pembayaran upah historis menyimpan
     * jenis sebagai teks + tarif potret, jadi tidak perlu dialihkan.
     */
    /** Alihkan semua pemakaian nama jenis upah (komponen analisa + pembayaran) ke nama tujuan. */
    private function alihkanJenisUpah(string $dari, string $ke): void
    {
        if ($dari === $ke) {
            return;
        }
        $db = $this->db();
        $tujuan = $db->table('upah_master')->where(['sekolah_id' => $this->sid(), 'jenis' => $ke, 'is_deleted' => 0])->get()->getRowArray();
        $sumber = $db->table('upah_master')->where(['sekolah_id' => $this->sid(), 'jenis' => $dari])->get()->getRowArray();
        $db->query(
            'UPDATE analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id SET ak.nama = ?, ak.upah_id = ?
             WHERE a.sekolah_id = ? AND ak.jenis = \'upah\' AND (ak.nama = ? OR ak.upah_id = ?)',
            [$ke, $tujuan ? (int) $tujuan['id'] : null, $this->sid(), $dari, $sumber ? (int) $sumber['id'] : 0]
        );
        $db->table('upah_bayar')->where(['sekolah_id' => $this->sid(), 'jenis_tenaga' => $dari])->update(['jenis_tenaga' => $ke]);
    }

    /** Gabung manual: jenis-jenis sumber dilebur ke satu jenis tujuan. */
    public function upahGabungManual()
    {
        $db = $this->db();
        $tujuanId = (int) $this->request->getPost('tujuan');
        $sumber = array_values(array_diff(array_map('intval', (array) ($this->request->getPost('sumber') ?? [])), [$tujuanId]));
        $tujuan = $db->table('upah_master')->where(['id' => $tujuanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $tujuan || $sumber === []) {
            return redirect()->to('bahan')->with('galat', 'Pilih jenis tujuan dan minimal satu jenis yang digabungkan ke dalamnya.');
        }
        $tarifFinal = $this->request->getPost('tarif_final');
        $db->transStart();
        if ($tarifFinal !== null && $tarifFinal !== '') {
            $db->table('upah_master')->where('id', $tujuanId)->update(['tarif' => (float) str_replace(['.', ','], ['', '.'], (string) $tarifFinal)]);
        }
        $namaSumber = [];
        foreach ($sumber as $sidRow) {
            $s = $db->table('upah_master')->where(['id' => $sidRow, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if (! $s) {
                continue;
            }
            $this->alihkanJenisUpah((string) $s['jenis'], (string) $tujuan['jenis']);
            $this->hapusLunak('upah_master', ['id' => $sidRow, 'sekolah_id' => $this->sid()]);
            $namaSumber[] = $s['jenis'];
        }
        $db->transComplete();
        return redirect()->to('bahan')->with('sukses', 'Jenis ' . implode(', ', $namaSumber) . ' digabungkan ke "' . $tujuan['jenis'] . '" — komponen analisa dan riwayat pembayaran ikut dialihkan.');
    }

    public function upahGabung()
    {
        $utamaId = (int) $this->request->getPost('utama');
        $anggota = array_map('intval', (array) $this->request->getPost('anggota'));
        if (! in_array($utamaId, $anggota, true) || count($anggota) < 2) {
            return redirect()->to('bahan')->with('galat', 'Pilih jenis tenaga utama dari kelompok duplikatnya.');
        }
        // Perbaikan dulu, baru gabung: tarif final dari formulir alert.
        $tarifFinal = $this->request->getPost('tarif_final');
        if ($tarifFinal !== null && $tarifFinal !== '') {
            $this->db()->table('upah_master')->where(['id' => $utamaId, 'sekolah_id' => $this->sid()])->update([
                'tarif' => (float) str_replace(['.', ','], ['', '.'], (string) $tarifFinal),
            ]);
        }
        $utama = $this->db()->table('upah_master')->where(['id' => $utamaId, 'sekolah_id' => $this->sid()])->get()->getRowArray();
        foreach ($anggota as $aid) {
            if ($aid !== $utamaId) {
                $lain = $this->db()->table('upah_master')->where(['id' => $aid, 'sekolah_id' => $this->sid()])->get()->getRowArray();
                if ($lain && $utama) {
                    $this->alihkanJenisUpah((string) $lain['jenis'], (string) $utama['jenis']);
                }
                $this->hapusLunak('upah_master', ['id' => $aid, 'sekolah_id' => $this->sid()]);
            }
        }
        return redirect()->to('bahan')->with('sukses', 'Duplikat jenis tenaga digabung — kini hanya ada satu tarif acuan untuk jenis tersebut.');
    }

    private function ambilForm(): array
    {
        $r = $this->request;
        return [
            'kode'       => strtoupper(trim((string) $r->getPost('kode'))),
            'nama'       => trim((string) $r->getPost('nama')),
            'satuan'     => trim((string) $r->getPost('satuan')),
            'harga_rab'  => (int) $r->getPost('harga_rab'),
            'qty_rkb'    => (float) $r->getPost('qty_rkb'),
            'qty_adm'    => (float) $r->getPost('qty_adm'),
            'qty_toilet' => (float) $r->getPost('qty_toilet'),
        ];
    }
}
