<?php

namespace App\Controllers;

use App\Libraries\ParserPerencana;

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

        return $this->tampil('impor/index', [
            'log' => $log,
            'berkas_folder' => $berkasFolder,
            'folder_impor' => $this->folderImpor(),
            'berkas_terakhir' => $berkasTerakhir,
            'tak_tertaut' => $takTertaut,
            'semua_bahan' => $semuaBahan,
            'jumlah_massal' => $jumlahMassal,
        ], 'Impor Dokumen Perencana');
    }

    /** Daftar nama komponen bahan yang belum tertaut + saran padanan terbaiknya. */
    private function dataTakTertaut(): array
    {
        $db = $this->db();
        $namaList = $db->query(
            "SELECT ak.nama, ak.satuan, COUNT(*) AS dipakai
             FROM analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             WHERE a.sekolah_id = ? AND ak.jenis = 'bahan' AND ak.bahan_id IS NULL
             GROUP BY ak.nama, ak.satuan ORDER BY dipakai DESC",
            [$this->sid()]
        )->getResultArray();
        $semuaBahan = $db->table('bahan')->where('sekolah_id', $this->sid())->orderBy('kode')->get()->getResultArray();
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
        $ada = $this->db()->table('sekolah')->where('id', $id)->countAllResults();
        return $ada > 0 ? $id : null;
    }

    private function jalankan(string $path, string $namaTampil, int $sekolahId)
    {
        session()->set('sekolah_id', $sekolahId);
        try {
            $parser = new ParserPerencana($this->db(), $sekolahId);
            $r = $parser->impor($path);
        } catch (\Throwable $e) {
            return redirect()->to('/impor')->with('galat', 'Impor gagal: ' . $e->getMessage());
        }
        $this->db()->table('impor_log')->insert([
            'sekolah_id' => $sekolahId, 'berkas' => $namaTampil,
            'ringkasan' => json_encode($r), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $pesan = sprintf(
            'Impor selesai: %d gedung, %d analisa (%d komponen), %d bahan baru ditambahkan ke master.',
            count($r['gedung']),
            $r['analisa'],
            $r['komponen'],
            $r['bahan_baru']
        );
        return redirect()->to('/impor')->with('sukses', $pesan);
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
        return $this->jalankan($folder . '/' . $namaBaru, (string) $berkas->getClientName(), $sekolahId);
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
        return $this->jalankan($path, $nama, $sekolahId);
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
        return $this->jalankan($unggahan[0], basename($unggahan[0]) . ' (impor ulang)', $sekolahId);
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
            $b = $this->db()->table('bahan')->where(['sekolah_id' => $this->sid(), 'kode' => $kode])->get()->getRowArray();
            $bahanId = (int) ($b['id'] ?? 0);
        }
        $b = $bahanId > 0 ? $this->db()->table('bahan')->where(['id' => $bahanId, 'sekolah_id' => $this->sid()])->get()->getRowArray() : null;
        if ($b === null || $nama === '') {
            return redirect()->to('/impor')->with('galat', 'Pilih bahan master yang sesuai untuk komponen tersebut.');
        }
        $this->db()->query(
            "UPDATE analisa_komponen ak JOIN analisa a ON a.id = ak.analisa_id
             SET ak.bahan_id = ? WHERE a.sekolah_id = ? AND ak.jenis = 'bahan' AND ak.nama = ?",
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
                     SET ak.bahan_id = ? WHERE a.sekolah_id = ? AND ak.jenis = 'bahan' AND ak.nama = ?",
                    [(int) $d['saran']['id'], $this->sid(), $d['nama']]
                );
                $n++;
            }
        }
        return redirect()->to('/impor')->with('sukses', $n . ' nama komponen tertaut otomatis dari saran padanan (skor ≥ 80). Sisanya dapat ditautkan manual di bawah atau dari tab Kebutuhan Bahan tiap gedung.');
    }
}
