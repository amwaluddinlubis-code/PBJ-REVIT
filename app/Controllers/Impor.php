<?php

namespace App\Controllers;

use App\Libraries\PembacaXlsxMentah;
use App\Libraries\ParserPerencana;
use App\Libraries\ParserTemplate;
use App\Libraries\PenulisTemplate;

/**
 * Impor Dokumen Perencana — versi satu klik:
 * impor dari folder server (tanpa batas unggah), impor ulang berkas terakhir,
 * unggah biasa, pemilih sekolah di halaman, dan penautan massal komponen.
 */
class Impor extends BaseController
{
    private function folderImpor(): string
    {
        $folder = (new \Config\Paths())->writableDirectory . '/impor';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
            file_put_contents($folder . '/Taruh berkas xlsx di folder ini.txt', "Salin berkas .xlsx dokumen perencana ke folder ini, lalu buka menu Impor Dokumen Perencana di aplikasi dan klik Impor.\nCara ini tidak kena batas ukuran unggah PHP.\n");
        }
        return $folder;
    }

    private function folderUnggahan(): string
    {
        return (new \Config\Paths())->writableDirectory . '/uploads';
    }

    public function index()
    {
        $db = $this->db();
        $log = $db->table('impor_log')->where('sekolah_id', $this->sid())
            ->orderBy('id', 'DESC')->get()->getResultArray();
        foreach ($log as &$l) {
            $l['ringkasan'] = json_decode((string) ($l['ringkasan'] ?? ''), true) ?: null;
        }

        $berkasFolder = [];
        foreach (glob($this->folderImpor() . '/*.xlsx') ?: [] as $f) {
            $berkasFolder[] = ['nama' => basename($f), 'ukuran' => filesize($f), 'tanggal' => date('Y-m-d H:i', filemtime($f))];
        }
        usort($berkasFolder, static fn ($a, $b) => strcmp($b['tanggal'], $a['tanggal']));

        $berkasTerakhir = null;
        $unggahan = glob($this->folderUnggahan() . '/perencana-*.xlsx') ?: [];
        if ($unggahan !== []) {
            usort($unggahan, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
            $berkasTerakhir = ['nama' => basename($unggahan[0]), 'tanggal' => date('Y-m-d H:i', filemtime($unggahan[0]))];
        }

        [$takTertaut, $semuaBahan, $jumlahMassal] = $this->dataTakTertaut();
        $verifikasi = $this->verifikasiAnggaran();

        return $this->tampil('impor/index', [
            'log' => $log,
            'berkas_folder' => $berkasFolder,
            'folder_impor' => $this->folderImpor(),
            'berkas_terakhir' => $berkasTerakhir,
            'tak_tertaut' => $takTertaut,
            'semua_bahan' => $semuaBahan,
            'jumlah_massal' => $jumlahMassal,
            'verifikasi' => $verifikasi,
        ], 'Impor Dokumen Perencana — Aplikasi PBJ Sekolah');
    }

    /** Daftar nama komponen bahan yang belum tertaut + saran padanan terbaiknya. */
    private function dataTakTertaut(): array
    {
        $db = $this->db();
        $namaList = $db->query(
            "SELECT ak.nama, ak.satuan, COUNT(*) AS dipakai
             FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = 'bahan' AND ak.bahan_id IS NULL AND ak.is_deleted = 0 AND a.is_deleted = 0
             GROUP BY ak.nama, ak.satuan ORDER BY dipakai DESC",
            [$this->sid()]
        )->getResultArray();
        $semuaBahan = $db->table('bahan')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('kode')->get()->getResultArray();
        $hasil = [];
        $massal = 0;
        foreach ($namaList as $n) {
            [$bahan, $skor] = $this->saranPadanan((string) $n['nama'], $semuaBahan);
            $n['saran'] = $bahan;
            $n['skor'] = $skor;
            if ($skor >= 80 && $bahan !== null) {
                $massal++;
            }
            $hasil[] = $n;
        }
        return [$hasil, $semuaBahan, $massal];
    }

    /** Skor kemiripan nama komponen vs master bahan: [bahan|null, skor 0-100]. */
    private function saranPadanan(string $nama, array $semuaBahan): array
    {
        $target = ParserPerencana::normal($nama);
        $terbaik = null;
        $skorMax = 0;
        foreach ($semuaBahan as $b) {
            $kandidat = ParserPerencana::normal((string) $b['nama']);
            if ($kandidat === $target) {
                return [$b, 100];
            }
            similar_text($target, $kandidat, $persen);
            $skor = (int) round($persen);
            if (str_contains($target, $kandidat) || str_contains($kandidat, $target)) {
                $skor = max($skor, 85);
            }
            if ($skor > $skorMax) {
                $skorMax = $skor;
                $terbaik = $b;
            }
        }
        return [$skorMax >= 50 ? $terbaik : null, $skorMax];
    }

    private function sekolahTujuan(): ?int
    {
        $id = (int) $this->request->getPost('sekolah_id');
        if ($id <= 0) {
            $id = $this->sid();
        }
        $ada = $this->db()->table('sekolah')->where(['id' => $id, 'is_deleted' => 0])->countAllResults();
        return $ada > 0 ? $id : null;
    }

    private function jalankan(string $path, string $namaTampil, int $sekolahId, array $pilihan = [])
    {
        session()->set('sekolah_id', $sekolahId);
        try {
            if (ParserTemplate::berkasTemplate(new PembacaXlsxMentah($path))) {
                $r = (new ParserTemplate($sekolahId))->impor($path, $pilihan);
                $r['format'] = 'template';
                $pesan = sprintf(
                    'Impor TEMPLATE selesai: %d gedung (%d pekerjaan), %d analisa (%d komponen), %d bahan baru + %d diperbarui, %d jenis upah. Nilai BOQ terhitung Rp %s.',
                    $r['gedung'], $r['pekerjaan'], $r['analisa'], $r['komponen'],
                    $r['bahan_baru'], $r['bahan_diperbarui'], $r['upah'],
                    number_format($r['nilai_boq'], 0, ',', '.')
                );
            } else {
                $r = (new ParserPerencana($this->db(), $sekolahId))->impor($path, $pilihan);
                $r['format'] = 'perencana';
                $pesan = sprintf(
                    'Impor selesai: %d gedung, %d analisa (%d komponen), %d bahan baru ditambahkan ke master.',
                    count($r['gedung']), $r['analisa'], $r['komponen'], $r['bahan_baru']
                );
            }
            if (($r['format'] ?? '') === 'template' && ($r['biaya_lain'] ?? 0) > 0) {
                $pesan .= sprintf(' Biaya non-gedung: %d baris.', $r['biaya_lain']);
            }
            if (($r['format'] ?? '') === 'perencana' && ($r['biaya_lain']['baris'] ?? 0) > 0) {
                $pesan .= sprintf(
                    ' Biaya non-gedung terbaca: %d baris (Rp %s).',
                    $r['biaya_lain']['baris'],
                    number_format($r['biaya_lain']['total'], 0, ',', '.')
                );
            }
            $v = $this->verifikasiAnggaran($sekolahId);
            $r['verifikasi'] = $v;
            $statusV = $v['pagu'] <= 0
                ? 'pagu belum diatur di Pengaturan'
                : ($v['selisih'] == 0 ? 'persis sama dengan pagu' : ($v['selisih'] > 0 ? 'di bawah pagu' : 'MELEBIHI pagu'));
            $pesan .= sprintf(
                ' Verifikasi anggaran: total BOQ %d gedung aktif Rp %s + biaya non-gedung Rp %s = Rp %s vs pagu Rp %s — selisih Rp %s (%s).',
                $v['gedung'],
                number_format($v['total_boq'], 0, ',', '.'),
                number_format($v['biaya_lain'], 0, ',', '.'),
                number_format($v['total_semua'], 0, ',', '.'),
                number_format($v['pagu'], 0, ',', '.'),
                number_format($v['selisih'], 0, ',', '.'),
                $statusV
            );
        } catch (\Throwable $e) {
            return redirect()->to('/impor')->with('galat', 'Impor gagal: ' . $e->getMessage());
        }
        $this->db()->table('impor_log')->insert([
            'sekolah_id' => $sekolahId, 'berkas' => $namaTampil,
            'ringkasan' => json_encode($r), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAudit('impor', null, 'buat', $pesan);
        return redirect()->to('/impor')->with('sukses', $pesan);
    }

    /** Unduh template manual kosong (dengan baris CONTOH). */
    /**
     * Tahap 1 impor: baca daftar sheet berkas TANPA menulis apa pun ke
     * tabel, simpan sebagai stage di sesi, lalu ke halaman peninjauan —
     * pengguna memilih sheet yang diimpor dan memetakannya ke tujuan.
     */
    private function tahapTinjau(string $path, string $namaTampil, int $sekolahId)
    {
        try {
            $pembaca = new PembacaXlsxMentah($path);
            if (ParserTemplate::berkasTemplate($pembaca)) {
                $format = 'template';
                $lembar = $this->pratinjauTemplate($pembaca);
            } else {
                $format = 'perencana';
                $lembar = (new ParserPerencana($this->db(), $sekolahId))->pratinjau($path);
            }
        } catch (\Throwable $e) {
            return redirect()->to('/impor')->with('galat', 'Berkas tidak dapat dibaca: ' . $e->getMessage());
        }
        session()->set('impor_stage', [
            'path' => $path, 'nama' => $namaTampil, 'sekolah_id' => $sekolahId,
            'format' => $format, 'lembar' => $lembar,
        ]);
        return redirect()->to('/impor/petakan');
    }

    /** Pratinjau ringkas untuk berkas template (sheet berperan tetap). */
    private function pratinjauTemplate(PembacaXlsxMentah $p): array
    {
        $peran = [
            'Sekolah' => ['Identitas Sekolah & Pagu', 'referensi'],
            'Harga' => ['Daftar Harga Bahan & Upah', 'referensi'],
            'Analisa' => ['Analisa AHSP', 'referensi'],
            'Harsat' => ['Harsat Pekerjaan', 'referensi'],
            'BOQ' => ['BOQ Gedung', 'gedung'],
            'BiayaLain' => ['Biaya Non-Gedung', 'non_gedung'],
        ];
        $hasil = [];
        foreach ($peran as $sheet => [$jenis, $kategori]) {
            if (! $p->adaSheet($sheet)) {
                continue;
            }
            $baris = 0;
            foreach ($p->baris($sheet, 8) as $sel) {
                $baris++;
            }
            $hasil[] = [
                'nama' => $sheet, 'jenis' => $jenis, 'kategori' => $kategori,
                'saran' => $kategori, 'baris' => max(0, $baris - 4), 'total' => 0.0,
            ];
        }
        return $hasil;
    }

    /** Tahap 2 impor: halaman peninjauan & pemetaan sheet. */
    public function petakan()
    {
        $stage = session()->get('impor_stage');
        if (! is_array($stage) || ! is_file((string) ($stage['path'] ?? ''))) {
            return redirect()->to('/impor')->with('galat', 'Tidak ada impor yang menunggu peninjauan. Pilih berkas dulu lewat salah satu cara impor.');
        }
        $sekolah = $this->db()->table('sekolah')->where('id', (int) $stage['sekolah_id'])->get()->getRowArray();
        return $this->tampil('impor/petakan', [
            'stage' => $stage,
            'nama_sekolah' => $sekolah['nama_sekolah'] ?? '(sekolah tidak ditemukan)',
        ], 'Tinjau & Petakan Impor');
    }

    /** Tahap 3 impor: jalankan impor sesuai pilihan peninjauan. */
    public function eksekusi()
    {
        if ($d = $this->penjagaPeran(['operator'])) {
            return $d;
        }
        $stage = session()->get('impor_stage');
        if (! is_array($stage) || ! is_file((string) ($stage['path'] ?? ''))) {
            return redirect()->to('/impor')->with('galat', 'Tahap peninjauan sudah berakhir. Ulangi pemilihan berkas.');
        }
        $sheets = (array) $this->request->getPost('sheet');
        $tujuan = (array) $this->request->getPost('tujuan');
        $namaGedung = (array) $this->request->getPost('nama_gedung');
        $pilihan = [];
        foreach ($sheets as $i => $sheet) {
            $t = (string) ($tujuan[$i] ?? 'lewati');
            if (! in_array($t, ['gedung', 'non_gedung', 'referensi', 'lewati'], true)) {
                $t = 'lewati';
            }
            $pilihan[(string) $sheet] = [
                'tujuan' => $t,
                'nama_gedung' => trim((string) ($namaGedung[$i] ?? '')) ?: null,
            ];
        }
        session()->remove('impor_stage');
        return $this->jalankan((string) $stage['path'], (string) $stage['nama'], (int) $stage['sekolah_id'], $pilihan);
    }

    /** Batalkan tahap peninjauan tanpa mengimpor apa pun. */
    public function batal()
    {
        session()->remove('impor_stage');
        return redirect()->to('/impor')->with('sukses', 'Peninjauan impor dibatalkan — tidak ada data yang diimpor.');
    }

    public function unduhTemplate()
    {
        $analisa = [[
            'kode' => 'CONTOH-F.01', 'nama' => 'Pondasi Batu Belah Campuran 1SP : 4PP', 'satuan' => 'm3', 'harga_satuan' => 1141370,
            'komponen' => [
                ['jenis' => 'bahan', 'nama' => 'Semen PC', 'koefisien' => 163, 'satuan' => 'kg', 'tarif' => 1860],
                ['jenis' => 'bahan', 'nama' => 'Pasir Pasang', 'koefisien' => 0.52, 'satuan' => 'm3', 'tarif' => 320000],
                ['jenis' => 'bahan', 'nama' => 'Batu Belah', 'koefisien' => 1.2, 'satuan' => 'm3', 'tarif' => 420000],
                ['jenis' => 'upah', 'nama' => 'Pekerja', 'koefisien' => 1.5, 'satuan' => 'OH', 'tarif' => 80671],
                ['jenis' => 'upah', 'nama' => 'Tukang Batu', 'koefisien' => 0.75, 'satuan' => 'OH', 'tarif' => 93578],
                ['jenis' => 'upah', 'nama' => 'Kepala Tukang', 'koefisien' => 0.075, 'satuan' => 'OH', 'tarif' => 0],
                ['jenis' => 'upah', 'nama' => 'Mandor', 'koefisien' => 0.075, 'satuan' => 'OH', 'tarif' => 0],
            ],
        ]];
        $ss = PenulisTemplate::dariData(
            [],
            [['kode' => 'CONTOH-B001', 'nama' => 'Semen PC', 'satuan' => 'zak', 'harga_rab' => 65000]],
            [['jenis' => 'CONTOH-Pekerja', 'satuan' => 'OH', 'tarif' => 80671]],
            $analisa,
            [['gedung' => 'CONTOH-RKB 3 Kopel', 'bab' => 'III. PEKERJAAN PONDASI', 'nama' => 'Pondasi Batu Belah Campuran 1SP : 4PP', 'kode_analisa' => 'F.01', 'volume' => 62.4, 'satuan' => 'm3']],
            [
                ['kelompok' => 'Perencanaan & Pengawasan', 'uraian' => 'CONTOH-Perencana', 'volume' => 1, 'satuan' => 'orang', 'periode' => 1, 'satuan_periode' => 'Ls', 'harga_satuan' => 21017634, 'keterangan' => '1,65% dari nilai konstruksi'],
                ['kelompok' => 'Biaya Pengelolaan', 'uraian' => 'CONTOH-Honor P2SP — Ketua', 'volume' => 1, 'satuan' => 'set', 'periode' => 4, 'satuan_periode' => 'bln', 'harga_satuan' => 750000, 'keterangan' => ''],
            ]
        );
        PenulisTemplate::unduh($ss, 'Template_Impor_Perencana.xlsx');
        exit;
    }

    /** Ekspor data sekolah aktif sebagai template terisi (untuk diperiksa/dikoreksi lalu diimpor kembali). */
    public function eksporTemplate()
    {
        $db = $this->db();
        $sid = $this->sid();
        $bahan = $db->table('bahan')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->orderBy('kode')->get()->getResultArray();
        $upah = $db->table('upah_master')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->orderBy('jenis')->get()->getResultArray();
        $analisa = [];
        foreach ($db->table('analisa')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->orderBy('kode')->get()->getResultArray() as $a) {
            $a['komponen'] = $db->table('analisa_komponen')->where(['analisa_id' => $a['id'], 'is_deleted' => 0])->orderBy('id')->get()->getResultArray();
            $analisa[] = $a;
        }
        $boq = $db->query(
            'SELECT g.nama AS gedung, p.bab, p.nama, p.kode_analisa, p.volume, p.satuan, p.harga_satuan
             FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND g.is_deleted = 0 ORDER BY g.urutan, p.urutan',
            [$sid]
        )->getResultArray();
        $biayaLain = $db->table('biaya_lain')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->orderBy('kelompok')->orderBy('id')->get()->getResultArray();
        $sekolah = $this->sekolahAktif() ?? [];
        $slug = trim(preg_replace('/[^a-z0-9]+/i', '_', (string) ($sekolah['nama_sekolah'] ?? 'sekolah')), '_');
        PenulisTemplate::unduh(PenulisTemplate::dariData($sekolah, $bahan, $upah, $analisa, $boq, $biayaLain), 'Template_Terisi_' . $slug . '.xlsx');
        exit;
    }

    public function proses()
    {
        $sekolahId = $this->sekolahTujuan();
        if ($sekolahId === null) {
            return redirect()->to('/impor')->with('galat', 'Sekolah tujuan tidak ditemukan.');
        }
        $berkas = $this->request->getFile('berkas');
        if ($berkas === null || ! $berkas->isValid()) {
            $kode = $berkas !== null ? $berkas->getError() : -1;
            if (in_array($kode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return redirect()->to('/impor')->with('galat', 'Berkas terlalu besar untuk diunggah (batas PHP server). Gunakan cara "Dari Folder" di halaman ini — salin berkas ke folder impor, tanpa batas ukuran.');
            }
            return redirect()->to('/impor')->with('galat', 'Pilih berkas .xlsx dokumen perencana terlebih dahulu.');
        }
        if (strtolower($berkas->getClientExtension()) !== 'xlsx') {
            return redirect()->to('/impor')->with('galat', 'Berkas harus berformat .xlsx.');
        }
        $folder = $this->folderUnggahan();
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $namaBaru = 'perencana-' . date('Ymd-His') . '.xlsx';
        $berkas->move($folder, $namaBaru);
        return $this->tahapTinjau($folder . '/' . $namaBaru, (string) $berkas->getClientName(), $sekolahId);
    }

    /** Impor dari berkas yang sudah disalin ke folder impor server. */
    public function folder()
    {
        $sekolahId = $this->sekolahTujuan();
        if ($sekolahId === null) {
            return redirect()->to('/impor')->with('galat', 'Sekolah tujuan tidak ditemukan.');
        }
        $nama = basename((string) $this->request->getPost('berkas'));
        $path = $this->folderImpor() . '/' . $nama;
        if ($nama === '' || strtolower(pathinfo($nama, PATHINFO_EXTENSION)) !== 'xlsx' || ! is_file($path)) {
            return redirect()->to('/impor')->with('galat', 'Berkas tidak ditemukan di folder impor. Salin dulu berkas .xlsx ke folder yang ditampilkan di halaman impor.');
        }
        return $this->tahapTinjau($path, $nama, $sekolahId);
    }

    /** Impor ulang berkas terakhir yang pernah diunggah. */
    public function ulang()
    {
        $sekolahId = $this->sekolahTujuan();
        if ($sekolahId === null) {
            return redirect()->to('/impor')->with('galat', 'Sekolah tujuan tidak ditemukan.');
        }
        $unggahan = glob($this->folderUnggahan() . '/perencana-*.xlsx') ?: [];
        if ($unggahan === []) {
            return redirect()->to('/impor')->with('galat', 'Belum ada berkas unggahan tersimpan. Gunakan cara unggah atau folder.');
        }
        usort($unggahan, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
        return $this->tahapTinjau($unggahan[0], basename($unggahan[0]) . ' (impor ulang)', $sekolahId);
    }

    /** Tautkan satu nama komponen ke bahan master pilihan pengguna. */
    public function tautkan()
    {
        $nama = (string) $this->request->getPost('nama_komponen');
        $bahanId = (int) $this->request->getPost('bahan_id');
        if ($bahanId <= 0) {
            // Cadangan tanpa JS: teks "KODE — Nama" dari datalist.
            $teks = (string) $this->request->getPost('bahan_teks');
            $kode = trim(explode('—', $teks)[0]);
            $b = $this->db()->table('bahan')->where(['sekolah_id' => $this->sid(), 'kode' => $kode, 'is_deleted' => 0])->get()->getRowArray();
            $bahanId = (int) ($b['id'] ?? 0);
        }
        $b = $bahanId > 0 ? $this->db()->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray() : null;
        if ($b === null || $nama === '') {
            return redirect()->to('/impor')->with('galat', 'Pilih bahan master yang sesuai untuk komponen tersebut.');
        }
        $this->db()->query(
            "UPDATE analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             SET ak.bahan_id = ? WHERE a.sekolah_id = ? AND ak.jenis = 'bahan' AND ak.nama = ? AND ak.is_deleted = 0 AND ak.is_deleted = 0",
            [$bahanId, $this->sid(), $nama]
        );
        return redirect()->to('/impor')->with('sukses', 'Komponen "' . $nama . '" tertaut ke ' . $b['kode'] . ' — ' . $b['nama'] . '.');
    }

    /** Tautkan massal semua komponen yang saran padanannya berskor >= 80. */
    public function tautkanMassal()
    {
        [$daftar] = $this->dataTakTertaut();
        $n = 0;
        foreach ($daftar as $d) {
            if ($d['saran'] !== null && $d['skor'] >= 80) {
                $this->db()->query(
                    "UPDATE analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
                     SET ak.bahan_id = ? WHERE a.sekolah_id = ? AND ak.jenis = 'bahan' AND ak.nama = ? AND ak.is_deleted = 0 AND ak.is_deleted = 0",
                    [(int) $d['saran']['id'], $this->sid(), $d['nama']]
                );
                $n++;
            }
        }
        return redirect()->to('/impor')->with('sukses', $n . ' nama komponen tertaut otomatis dari saran padanan (skor ≥ 80). Sisanya dapat ditautkan manual di bawah atau dari tab Kebutuhan Bahan tiap gedung.');
    }
}
