<?php

namespace App\Controllers;

/**
 * Editor Analisa (AHSP): daftar analisa sekolah + ubah koefisien/tarif
 * komponen, tambah & hapus komponen — tanpa impor ulang. Setiap perubahan
 * komponen menghitung ulang harga satuan analisa dari komponennya.
 */
class Analisa extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $semua = $db->query(
            'SELECT a.*, (SELECT COUNT(*) FROM analisa_komponen k WHERE k.analisa_id = a.id AND k.is_deleted = 0) AS jml_komponen,
                    (SELECT COUNT(*) FROM pekerjaan p WHERE (p.analisa_id = a.id OR (p.analisa_id IS NULL AND p.kode_analisa = a.kode)) AND p.is_deleted = 0 AND p.gedung_id IN (SELECT id FROM gedung WHERE sekolah_id = a.sekolah_id)) AS dipakai,
                    (SELECT COUNT(*) FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id WHERE (p.analisa_id = a.id OR (p.analisa_id IS NULL AND p.kode_analisa = a.kode)) AND p.is_deleted = 0 AND g.sekolah_id = a.sekolah_id AND g.is_deleted = 0 AND g.didanai = 1) AS dipakai_didanai,
                    (SELECT COUNT(*) FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id WHERE (p.analisa_id = a.id OR (p.analisa_id IS NULL AND p.kode_analisa = a.kode)) AND p.is_deleted = 0 AND g.sekolah_id = a.sekolah_id AND g.is_deleted = 0 AND g.didanai = 0) AS dipakai_lain
             FROM analisa a WHERE a.sekolah_id = ? AND a.is_deleted = 0 ORDER BY a.kode',
            [$this->sid()]
        )->getResultArray();
        // Indeks cari backend untuk tabel daftar analisa: nama komponen
        // dan nama bahan tertaut tiap analisa, agar kotak pencarian yang
        // sudah ada di halaman (data-cari) bisa menemukan analisa dari
        // nama bahan/komponennya — bukan hanya dari kode/nama analisa.
        $petaCari = [];
        foreach ($db->query(
            'SELECT ak.analisa_id, ak.nama AS komponen, b.nama AS bahan_nama
             FROM analisa_komponen ak
             LEFT JOIN bahan b ON b.id = ak.bahan_id AND b.is_deleted = 0
             WHERE ak.is_deleted = 0',
        )->getResultArray() as $k) {
            $petaCari[(int) $k['analisa_id']][(string) $k['komponen']] = true;
            if (! empty($k['bahan_nama'])) {
                $petaCari[(int) $k['analisa_id']][(string) $k['bahan_nama']] = true;
            }
        }
        foreach ($semua as &$a) {
            $a['cari_teks'] = isset($petaCari[(int) $a['id']]) ? implode(' ', array_keys($petaCari[(int) $a['id']])) : '';
        }
        unset($a);
        // Panel perbaikan (tarif 0 & beda satuan) mengikuti tab aktif:
        // tab didanai hanya memuat analisa yang dipakai gedung didanai.
        $tabPanel = (string) $this->request->getGet('tab') === 'lainnya' ? 'lainnya' : 'didanai';
        $idsTab = [];
        foreach ($semua as $a) {
            $diDidanai = (int) $a['dipakai_didanai'] > 0;
            if (($tabPanel === 'didanai') === $diDidanai) {
                $idsTab[] = (int) $a['id'];
            }
        }
        $komponenNol = $db->query(
            'SELECT a.kode, a.nama AS analisa_nama, ak.id AS komponen_id, ak.analisa_id, ak.jenis, ak.nama AS komponen, ak.koefisien, ak.satuan
             FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.is_deleted = 0 AND a.is_deleted = 0 AND COALESCE(ak.tarif, 0) <= 0
             ORDER BY a.kode, ak.jenis, ak.nama',
            [$this->sid()]
        )->getResultArray();
        $komponenNol = array_values(array_filter(
            $komponenNol,
            static fn ($k) => in_array((int) $k['analisa_id'], $idsTab, true)
        ));
        // Bahan yang satuan komponennya berbeda dari satuan master:
        // tampilkan faktor yang berlaku + faktor manual (bila sudah diatur).
        $norm = static fn ($x) => str_replace(['³', '²'], ['3', '2'], mb_strtolower(trim((string) $x)));
        $bedaSatuan = [];
        $inTab = $idsTab === [] ? '0' : implode(',', array_map('intval', $idsTab));
        // Satu komponen contoh per pasangan (bahan, satuan komponen) agar kolom
        // Satuan Komponen bisa bertautan langsung ke editor analisa komponen itu.
        $contoh = [];
        foreach ($db->query(
            'SELECT ak.bahan_id, ak.satuan, MIN(ak.id) AS kid
             FROM analisa_komponen ak
             JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.is_deleted = 0 AND a.is_deleted = 0
               AND ak.analisa_id IN (' . $inTab . ')
             GROUP BY ak.bahan_id, ak.satuan',
            [$this->sid(), 'bahan']
        )->getResultArray() as $c) {
            $contoh[$c['bahan_id'] . '|' . $c['satuan']] = (int) $c['kid'];
        }
        $petaAid = [];
        if ($contoh !== []) {
            foreach ($db->table('analisa_komponen')->select('id, analisa_id')
                ->whereIn('id', array_values($contoh))->get()->getResultArray() as $m) {
                $petaAid[(int) $m['id']] = (int) $m['analisa_id'];
            }
        }
        foreach ($db->query(
            'SELECT b.id AS bahan_id, b.kode, b.nama, b.satuan AS satuan_master, ak.satuan AS satuan_komponen, COUNT(*) AS jml
             FROM analisa_komponen ak
             JOIN analisa a ON a.id = ak.analisa_id
             JOIN bahan b ON b.id = ak.bahan_id
             WHERE a.sekolah_id = ? AND ak.jenis = ? AND ak.is_deleted = 0 AND a.is_deleted = 0 AND b.is_deleted = 0
               AND ak.analisa_id IN (' . $inTab . ')
             GROUP BY b.id, b.kode, b.nama, b.satuan, ak.satuan ORDER BY jml DESC',
            [$this->sid(), 'bahan']
        )->getResultArray() as $r) {
            if ($norm($r['satuan_komponen']) === $norm($r['satuan_master'])) {
                continue;
            }
            $r['faktor_manual'] = $this->faktorManual((int) $r['bahan_id'], (string) $r['satuan_komponen']);
            $r['faktor_berlaku'] = $this->faktorSatuan($r['satuan_komponen'], $r);
            $r['contoh_kid'] = $contoh[$r['bahan_id'] . '|' . $r['satuan_komponen']] ?? null;
            $r['contoh_aid'] = $r['contoh_kid'] !== null ? ($petaAid[$r['contoh_kid']] ?? null) : null;
            $bedaSatuan[] = $r;
        }
        // Dua tab: analisa yang dipakai gedung didanai vs sisanya
        // (hanya dipakai gedung tidak didanai, atau belum dipakai sama sekali).
        $tab = (string) $this->request->getGet('tab') === 'lainnya' ? 'lainnya' : 'didanai';
        $diDidanai = array_values(array_filter($semua, static fn ($a) => (int) $a['dipakai_didanai'] > 0));
        $lainnya = array_values(array_filter($semua, static fn ($a) => (int) $a['dipakai_didanai'] === 0));
        $belum = count(array_filter($semua, static fn ($a) => (float) ($a['harga_satuan'] ?? 0) <= 0));
        return $this->tampil('analisa/index', [
            'daftar' => $tab === 'didanai' ? $diDidanai : $lainnya,
            'tab' => $tab,
            'jml_didanai' => count($diDidanai),
            'jml_lainnya' => count($lainnya),
            'belum' => $belum,
            'komponen_nol' => $komponenNol,
            'beda_satuan' => $bedaSatuan,
        ], 'Analisa Pekerjaan — Aplikasi PBJ Sekolah');
    }

    public function detail($id)
    {
        $a = $this->ambilAnalisa((int) $id);
        if (! $a) {
            return redirect()->to('analisa')->with('galat', 'Analisa tidak ditemukan.');
        }
        $db = $this->db();
        $komponen = $db->query(
            "SELECT ak.*, b.nama AS nama_master, b.kode AS kode_master
             FROM analisa_komponen ak
             LEFT JOIN bahan b ON b.id = ak.bahan_id AND b.is_deleted = 0
             WHERE ak.analisa_id = ? AND ak.is_deleted = 0
             ORDER BY FIELD(ak.jenis, 'bahan', 'upah', 'alat'), ak.id",
            [$a['id']]
        )->getResultArray();
        // Pembanding AHSP Nasional (bila analisa ini punya padanan acuan):
        // koefisien acuan per komponen + nilainya dengan tarif komponen ini.
        $acuanInfo = null;
        $acuanPerKomp = [];
        $ac = \App\Libraries\BandingAcuan::acuanUntukKode($db, (string) $a['kode'], $this->sid());
        if ($ac) {
            $acuanInfo = $ac;
            $kompAcuan = \App\Libraries\BandingAcuan::komponenAcuan($db, (int) $ac['id']);
            $hitungGrup = [];
            foreach ($komponen as $k) {
                $g = \App\Libraries\BandingAcuan::grupKanon((string) $k['nama'], (string) $k['jenis']);
                $hitungGrup[$g] = ($hitungGrup[$g] ?? 0) + 1;
            }
            foreach ($komponen as $k) {
                $g = \App\Libraries\BandingAcuan::grupKanon((string) $k['nama'], (string) $k['jenis']);
                $ko = \App\Libraries\BandingAcuan::koefGrup($kompAcuan, $g, (string) ($k['satuan'] ?? ''));
                $acuanPerKomp[(int) $k['id']] = [
                    'koef'   => $ko,
                    'nilai'  => $ko === null ? null : $ko * (float) ($k['tarif'] ?? 0),
                    'banyak' => ($hitungGrup[$g] ?? 0) > 1,
                ];
            }
        }
        $bahan = $db->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('nama')->get()->getResultArray();
        $upah = $db->table('upah_master')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('jenis')->get()->getResultArray();
        $pemakai = $db->query(
            "SELECT p.id AS pekerjaan_id, p.nama, p.volume, p.volume_revisi, p.satuan,
                    g.id AS gedung_id, g.nama AS gedung
             FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE (p.analisa_id = ? OR (p.analisa_id IS NULL AND p.kode_analisa = ?))
               AND p.is_deleted = 0 AND g.is_deleted = 0 AND g.sekolah_id = ?
             ORDER BY g.urutan, g.id, p.urutan, p.id",
            [$a['id'], $a['kode'], $this->sid()]
        )->getResultArray();
        return $this->tampil('analisa/detail', [
            'a' => $a, 'komponen' => $komponen, 'bahan' => $bahan, 'upah' => $upah, 'pemakai' => $pemakai,
            'acuanInfo' => $acuanInfo, 'acuanPerKomp' => $acuanPerKomp,
        ], 'Analisa ' . $a['kode'] . ' — Aplikasi PBJ Sekolah');
    }

    /** Simpan perubahan koefisien & tarif semua komponen sekaligus. */
    public function komponenSimpan($id)
    {
        $a = $this->ambilAnalisa((int) $id);
        if (! $a) {
            return redirect()->to('analisa')->with('galat', 'Analisa tidak ditemukan.');
        }
        $db = $this->db();
        $n = 0;
        foreach ((array) ($this->request->getPost('komp') ?? []) as $kid => $v) {
            $ok = $db->table('analisa_komponen')->where(['id' => (int) $kid, 'analisa_id' => $a['id'], 'is_deleted' => 0])->update([
                'koefisien' => (float) str_replace(',', '.', (string) ($v['koefisien'] ?? 0)),
                'tarif' => (float) str_replace(['.', ','], ['', '.'], (string) ($v['tarif'] ?? 0)),
            ]);
            if ($ok) {
                $n++;
            }
        }
        $harga = $this->hitungUlangHargaSatuan((int) $a['id']);
        return redirect()->to('analisa/' . $a['id'])->with('sukses', $n . ' komponen diperbarui. Harga satuan analisa kini ' . $this->rp($harga) . ' (dihitung dari komponen).');
    }

    /** Balasan aksi komponen: JSON untuk permintaan AJAX (modal), redirect untuk alur biasa. */
    private function balasKomponen(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->back()->with($ok ? 'sukses' : 'galat', $pesan);
    }

    /** Ubah satu komponen dari modal editor: koefisien, tarif, satuan. */
    public function komponenUbah($id)
    {
        $a = $this->ambilAnalisa((int) $id);
        if (! $a) {
            return $this->balasKomponen(false, 'Analisa tidak ditemukan.');
        }
        $kid = (int) $this->request->getPost('komponen_id');
        $k = $this->db()->table('analisa_komponen')
            ->where(['id' => $kid, 'analisa_id' => $a['id'], 'is_deleted' => 0])->get()->getRowArray();
        if (! $k) {
            return $this->balasKomponen(false, 'Komponen tidak ditemukan.');
        }
        $satuan = trim((string) $this->request->getPost('satuan'));
        $this->db()->table('analisa_komponen')->where('id', $kid)->update([
            'koefisien' => (float) str_replace(',', '.', (string) $this->request->getPost('koefisien')),
            'tarif'     => (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif')),
            'satuan'    => $satuan !== '' ? $satuan : $k['satuan'],
        ]);
        $total = $this->hitungUlangHargaSatuan((int) $a['id']);
        return $this->balasKomponen(true, 'Komponen diperbarui. Harga satuan analisa kini ' . $this->rp($total) . '.');
    }

    public function komponenTambah($id)
    {
        $a = $this->ambilAnalisa((int) $id);
        if (! $a) {
            return redirect()->to('analisa')->with('galat', 'Analisa tidak ditemukan.');
        }
        $jenis = (string) $this->request->getPost('jenis');
        if (! in_array($jenis, ['bahan', 'upah', 'alat'], true)) {
            $jenis = 'bahan';
        }
        $db = $this->db();
        $bahanId = null;
        $upahId = null;
        $nama = trim((string) $this->request->getPost('nama'));
        $satuan = trim((string) $this->request->getPost('satuan'));
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif'));
        if ($jenis === 'bahan' && (int) $this->request->getPost('bahan_id') > 0) {
            $b = $db->table('bahan')->where(['id' => (int) $this->request->getPost('bahan_id'), 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($b) {
                $bahanId = (int) $b['id'];
                $nama = $nama !== '' ? $nama : (string) $b['nama'];
                $satuan = $satuan !== '' ? $satuan : (string) $b['satuan'];
                if ($tarif <= 0) {
                    $tarif = (float) $b['harga_rab'];
                }
            }
        }
        if ($jenis === 'upah' && $nama !== '') {
            $m = $db->table('upah_master')->where(['sekolah_id' => $this->sid(), 'jenis' => $nama, 'is_deleted' => 0])->get()->getRowArray();
            if ($m) {
                $upahId = (int) $m['id'];
                if ($tarif <= 0) {
                    $tarif = (float) $m['tarif'];
                }
            }
            if ($satuan === '') {
                $satuan = 'OH';
            }
        }
        if ($nama === '') {
            return redirect()->to('analisa/' . $a['id'])->with('galat', 'Nama komponen wajib diisi (atau pilih bahan dari master).');
        }
        $db->table('analisa_komponen')->insert([
            'analisa_id' => $a['id'], 'jenis' => $jenis, 'nama' => $nama, 'satuan' => $satuan ?: null,
            'koefisien' => (float) str_replace(',', '.', (string) $this->request->getPost('koefisien')),
            'bahan_id' => $bahanId, 'upah_id' => $upahId, 'tarif' => $tarif,
        ]);
        $this->hitungUlangHargaSatuan((int) $a['id']);
        return $this->balasKomponen(true, 'Komponen "' . $nama . '" ditambahkan.');
    }

    /** Simpan faktor konversi manual untuk pasangan bahan + satuan komponen. */
    public function simpanKonversi()
    {
        $db = $this->db();
        $bahanId = (int) $this->request->getPost('bahan_id');
        $satuan = trim((string) $this->request->getPost('satuan_komponen'));
        $mentah = trim((string) $this->request->getPost('faktor'));
        // Angka Indonesia: koma = desimal; titik ribuan dibuang bila ada koma.
        $faktor = str_contains($mentah, ',')
            ? (float) str_replace(['.', ','], ['', '.'], $mentah)
            : (float) $mentah;
        $bahan = $db->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($bahan === null || $satuan === '' || $faktor <= 0) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'pesan' => 'Konversi tidak disimpan: bahan/satuan tidak valid atau faktor harus lebih dari 0.']);
            }
            return redirect()->to(base_url('analisa'))->with('galat', 'Konversi tidak disimpan: bahan/satuan tidak valid atau faktor harus lebih dari 0.');
        }
        $ada = $db->table('bahan_konversi')->where([
            'sekolah_id' => $this->sid(), 'bahan_id' => $bahanId, 'satuan_komponen' => $satuan,
        ])->get()->getRowArray();
        if ($ada !== null) {
            $db->table('bahan_konversi')->where('id', $ada['id'])->update([
                'faktor' => $faktor, 'is_deleted' => 0, 'deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $db->table('bahan_konversi')->insert([
                'sekolah_id' => $this->sid(), 'bahan_id' => $bahanId, 'satuan_komponen' => $satuan,
                'faktor' => $faktor, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $pesanKonversi = 'Konversi satuan ' . $bahan['nama'] . ' (' . $satuan . ' ke ' . ($bahan['satuan'] ?? '') . ') diatur ke faktor ' . rtrim(rtrim(number_format($faktor, 8, ',', '.'), '0'), ',') . '. Kebutuhan bahan dihitung ulang dengan faktor ini.';
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'pesan' => $pesanKonversi]);
        }
        return redirect()->to(base_url('analisa'))->with('sukses', $pesanKonversi);
    }

    /** Hapus faktor konversi manual (kembali ke aturan bawaan). */
    public function hapusKonversi()
    {
        $bahanId = (int) $this->request->getPost('bahan_id');
        $satuan = trim((string) $this->request->getPost('satuan_komponen'));
        $this->hapusLunak('bahan_konversi', [
            'sekolah_id' => $this->sid(), 'bahan_id' => $bahanId, 'satuan_komponen' => $satuan,
        ]);
        return redirect()->to(base_url('analisa'))->with('sukses', 'Konversi manual dihapus; pasangan satuan itu kembali memakai aturan bawaan.');
    }

    public function komponenHapus($id, $kompId)
    {
        $a = $this->ambilAnalisa((int) $id);
        if (! $a) {
            return redirect()->to('analisa')->with('galat', 'Analisa tidak ditemukan.');
        }
        $this->hapusLunak('analisa_komponen', ['id' => (int) $kompId, 'analisa_id' => $a['id']]);
        $this->hitungUlangHargaSatuan((int) $a['id']);
        return redirect()->to('analisa/' . $a['id'])->with('sukses', 'Komponen dihapus; harga satuan dihitung ulang.');
    }

    private function ambilAnalisa(int $id): ?array
    {
        return $this->db()->table('analisa')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
    }

    /** Harga satuan analisa = Σ koefisien × tarif komponen aktif. */
    private function hitungUlangHargaSatuan(int $analisaId): float
    {
        $db = $this->db();
        $total = (float) ($db->query(
            'SELECT COALESCE(SUM(koefisien * COALESCE(tarif, 0)), 0) AS t FROM analisa_komponen WHERE analisa_id = ? AND is_deleted = 0',
            [$analisaId]
        )->getRow()->t ?? 0);
        $db->table('analisa')->where('id', $analisaId)->update(['harga_satuan' => $total]);
        return $total;
    }
}
