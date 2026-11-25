<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url', 'pilih'];
    protected $pengguna;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $s = session();
        $this->pengguna = $s->get('pengguna_id') ? [
            'id'       => $s->get('pengguna_id'),
            'nama'     => $s->get('pengguna_nama'),
            'username' => $s->get('pengguna_username'),
            'peran'    => $s->get('pengguna_peran'),
        ] : null;
    }

    protected function db()
    {
        return \Config\Database::connect();
    }

    /** Render view di dalam layout utama. */
    /**
     * Hapus lunak: tandai baris is_deleted = 1 + deleted_at (data tetap di
     * database dan bisa dipulihkan). $syarat bisa array where atau callable
     * yang menerima query builder.
     */
    protected function hapusLunak(string $tabel, $syarat): void
    {
        $b = $this->db()->table($tabel);
        if (is_callable($syarat)) {
            $syarat($b);
        } else {
            $b->where($syarat);
        }
        $b->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
    }

    /** Id tercentang dari formulir hapus massal (POST ids[]). */
    /**
     * Catat jejak audit untuk aksi penting (uang & persetujuan).
     * Best-effort: kegagalan mencatat tidak boleh menggagalkan aksi utama.
     */
    protected function catatAudit(string $entitas, ?int $entitasId, string $aksi, ?string $keterangan = null): void
    {
        try {
            $db = $this->db();
            if (! $db->tableExists('audit_events')) {
                return;
            }
            $db->table('audit_events')->insert([
                'sekolah_id' => $this->pengguna ? $this->sid() : null,
                'user_id'    => $this->pengguna['id'] ?? null,
                'username'   => $this->pengguna['username'] ?? null,
                'entitas'    => $entitas,
                'entitas_id' => $entitasId,
                'aksi'       => $aksi,
                'keterangan' => $keterangan,
                'ip'         => $this->request->getIPAddress(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // abaikan — audit tidak boleh menghalangi pekerjaan
        }
    }

    /**
     * Gerbang peran: kembalikan respons penolakan bila peran pengguna tidak
     * termasuk daftar yang diizinkan; admin selalu lolos. Pemakaian:
     * if ($d = $this->penjagaPeran(['bendahara'])) { return $d; }
     * Daftar kosong berarti khusus admin.
     */
    protected function penjagaPeran(array $peran)
    {
        $saya = (string) ($this->pengguna['peran'] ?? '');
        if ($saya === 'admin' || in_array($saya, $peran, true)) {
            return null;
        }
        return redirect()->back()->with('galat', 'Peran Anda (' . ($saya ?: '-') . ') tidak berwenang melakukan tindakan ini.');
    }

    protected function idsTerpilih(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->request->getPost('ids'))));
    }

    /** Ringkasan hasil hapus banyak untuk pesan kilat. */
    protected function ringkasHapus(int $dihapus, array $dilewati, string $satuan): string
    {
        $pesan = $dihapus . ' ' . $satuan . ' dihapus.';
        if ($dilewati !== []) {
            $pesan .= ' ' . count($dilewati) . ' dilewati: ' . implode(', ', array_slice($dilewati, 0, 8)) . (count($dilewati) > 8 ? ', …' : '') . '.';
        }
        return $pesan;
    }

    protected function tampil(string $view, array $data = [], string $judul = 'Aplikasi PBJ Sekolah')
    {
        $data['q']            = $data['q'] ?? (string) ($this->request->getGet('q') ?? '');
        $data['judul']        = $judul;
        $data['pengguna']     = $this->pengguna;
        $data['aksesMenu']    = $this->pengguna ? \App\Libraries\AksesMenu::petaUntukPeran($this->db(), (string) ($this->pengguna['peran'] ?? '')) : [];
        $data['sekolahAktif'] = $this->sekolahAktif();
        $data['semuaSekolah'] = $this->semuaSekolah();
        $data['semuaGedung']  = $this->semuaGedung();
        $data['konten']       = view($view, $data);
        return view('layout/main', $data);
    }

    /** Id bahan yang dipakai gedung aktif yang didanai: tertaut komponen
        analisa yang analisanya dipakai pekerjaan gedung tersebut.
        Dipakai bersama oleh Master Bahan (panel Kelengkapan) dan Dashboard
        (peringatan bahan tanpa harga) agar basis hitungnya selalu sama. */
    protected function idBahanDipakaiGedung(): array
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

    /** ID sekolah yang sedang aktif (dari sesi; bawaan: sekolah pertama). */
    protected function sid(): int
    {
        $db = $this->db();
        $id = (int) (session()->get('sekolah_id') ?? 0);
        if ($id > 0 && $db->table('sekolah')->where(['id' => $id, 'is_deleted' => 0])->countAllResults() > 0) {
            return $id;
        }
        $row = $db->table('sekolah')->where('is_deleted', 0)->orderBy('id')->get()->getRowArray();
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0) {
            session()->set('sekolah_id', $id);
        }
        return $id;
    }

    /** Baris sekolah aktif. */
    protected function sekolahAktif(): array
    {
        $id = $this->sid();
        return $id > 0 ? ($this->db()->table('sekolah')->where(['id' => $id, 'is_deleted' => 0])->get()->getRowArray() ?? []) : [];
    }

    /** Semua sekolah terdaftar (untuk pengalih di layout). */
    protected function semuaSekolah(): array
    {
        return $this->db()->table('sekolah')->where('is_deleted', 0)->orderBy('nama_sekolah')->get()->getResultArray();
    }

    /** Semua gedung sekolah aktif + persen kemajuan fisiknya (untuk menu). */
    protected function semuaGedung(): array
    {
        try {
            $rows = $this->db()->query(
                'SELECT g.*, COALESCE(r.nilai, 0) AS nilai_realisasi
                 FROM gedung g
                 LEFT JOIN (
                     SELECT p.gedung_id AS gid, SUM(rl.volume * COALESCE(p.harga_revisi, p.harga_satuan)) AS nilai
                     FROM realisasi rl JOIN pekerjaan p ON p.id = rl.pekerjaan_id
                     WHERE rl.is_deleted = 0 AND p.is_deleted = 0
                     GROUP BY p.gedung_id
                 ) r ON r.gid = g.id
                 WHERE g.sekolah_id = ? AND g.is_deleted = 0 ORDER BY g.urutan, g.id',
                [$this->sid()]
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as &$g) {
            $g['persen_fisik'] = (float) $g['nilai_boq'] > 0 ? min(100, (float) $g['nilai_realisasi'] / (float) $g['nilai_boq'] * 100) : 0;
        }
        return $rows;
    }

    /** Satu baris gedung milik sekolah aktif (null bila tidak ada). */
    protected function ambilGedung(int $id): ?array
    {
        $g = $this->db()->table('gedung')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        return $g ?: null;
    }

    /**
     * Kebutuhan bahan satu gedung, dihitung dari BOQ x koefisien AHSP.
     * Mengembalikan baris per bahan master yang tertaut + daftar komponen tak tertaut.
     */
    /**
     * Faktor konversi satuan komponen AHSP ke satuan beli bahan master.
     * Koefisien AHSP semen/agregat dinyatakan dalam kg, sedangkan bahan dibeli
     * per zak (semen 50 kg) atau per m3 (pasir ~1.400 kg/m3, kerikil/batu ~1.600 kg/m3).
     */
    /** Peta konversi manual bahan (bahan_konversi), dimuat sekali per request. */
    private ?array $petaKonversi = null;

    /**
     * Nama kanonik jenis upah (keputusan AMWAL 22 Nov 2026: master upah
     * hanya 2 baris): semua varian "Tukang ..." → 'Tukang', semua varian
     * "Pekerja ..." → 'Pekerja'. Mandor & Kepala Tukang mengembalikan
     * null khusus: keduanya dihilangkan, bukan digabung. Nama lain
     * (mis. Peralatan) juga null dan tidak disentuh.
     */
    protected function upahKanon(?string $nama): ?string
    {
        $n = strtolower(trim((string) $nama));
        if ($n === '') {
            return null;
        }
        // Mandor & Kepala Tukang DIHILANGKAN (keputusan AMWAL 22 Nov 2026):
        // tidak dipetakan ke jenis mana pun; komponennya dihapus lunak
        // oleh perapian/impor dan tidak pernah terhitung.
        if (str_contains($n, 'mandor') || str_contains($n, 'kepala')) {
            return null;
        }
        if (str_contains($n, 'pekerja')) {
            return 'Pekerja';
        }
        if (str_contains($n, 'tukang')) {
            return 'Tukang';
        }
        return null;
    }

    protected function faktorManual(int $bahanId, string $satuanKomponen): ?float
    {
        if ($this->petaKonversi === null) {
            $this->petaKonversi = [];
            if ($this->db()->tableExists('bahan_konversi')) {
                foreach ($this->db()->table('bahan_konversi')
                    ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray() as $k) {
                    $this->petaKonversi[(int) $k['bahan_id'] . '|' . mb_strtolower(trim((string) $k['satuan_komponen']))] = (float) $k['faktor'];
                }
            }
        }
        return $this->petaKonversi[$bahanId . '|' . mb_strtolower(trim($satuanKomponen))] ?? null;
    }

    protected function faktorSatuan(?string $satuanKomponen, array $bahan): float
    {
        $bahanId = (int) ($bahan['id'] ?? $bahan['bahan_id'] ?? 0);
        if ($bahanId > 0 && $satuanKomponen !== null && trim((string) $satuanKomponen) !== '') {
            $manual = $this->faktorManual($bahanId, (string) $satuanKomponen);
            if ($manual !== null) {
                return $manual;
            }
        }
        $sk = mb_strtolower(trim((string) $satuanKomponen));
        $sb = mb_strtolower(trim((string) ($bahan['satuan'] ?? '')));
        // Dokumen perencana tidak mencantumkan satuan beli (bahan hasil impor
        // bersatuan '-'): tebak satuan beli dari nama bahan agar konversi jalan.
        if ($sb === '' || $sb === '-') {
            $nb = mb_strtolower((string) ($bahan['nama'] ?? ''));
            if (str_contains($nb, 'semen')) {
                $sb = 'zak';
            } elseif (str_contains($nb, 'pasir') || str_contains($nb, 'kerikil') || str_contains($nb, 'batu')) {
                $sb = 'm3';
            } elseif (str_contains($nb, 'keramik')) {
                $sb = 'ktk';
            }
        }
        if ($sk === 'kg') {
            if (str_contains($sb, 'zak')) {
                return 1 / 50;
            }
            if (str_starts_with($sb, 'm')) {
                return str_contains(mb_strtolower((string) $bahan['nama']), 'pasir') ? 1 / 1400 : 1 / 1600;
            }
        }
        // Keramik dihitung AHSP per bh/pcs, dibeli per ktk (isi baku per ukuran)
        if (in_array($sk, ['bh', 'pcs', 'buah'], true) && $sb === 'ktk'
            && str_contains(mb_strtolower((string) $bahan['nama']), 'keramik')) {
            $nama = (string) $bahan['nama'];
            if (str_contains($nama, '40')) {
                return 1 / 6;
            }
            if (str_contains($nama, '30')) {
                return 1 / 11;
            }
            if (str_contains($nama, '25')) {
                return 1 / 16;
            }
            if (str_contains($nama, '20')) {
                return 1 / 25;
            }
        }
        return 1.0;
    }

    /**
     * Penerimaan bahan OTOMATIS dari BAST (keputusan AMWAL 25 Nov 2026):
     * barang masuk stok pada tanggal BAST, sebesar qty baris rincian SPK
     * yang tercakup BAP dari BAST itu (bast.bap_id). BAST lama tanpa
     * bap_id mencakup seluruh rincian SPK-nya. Catatan masuk manual di
     * Buku Material TIDAK dihitung lagi — stok murni dari BAST dikurangi
     * catatan keluar. Kunci hasil: bahan_id => qty diterima.
     */
    protected function masukBastPerBahan(): array
    {
        $peta = [];
        foreach ($this->db()->query(
            'SELECT d.bahan_id, SUM(d.qty) AS qty FROM spk_detail d
             JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
             JOIN bahan b ON b.id = d.bahan_id
             WHERE b.sekolah_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0
               AND (
                 EXISTS (SELECT 1 FROM bap_detail bd
                         JOIN bap bp ON bp.id = bd.bap_id AND bp.is_deleted = 0
                         JOIN bast x ON x.bap_id = bd.bap_id AND x.is_deleted = 0
                         WHERE bd.detail_id = d.id AND bd.is_deleted = 0)
                 OR EXISTS (SELECT 1 FROM bast y WHERE y.spk_id = s.id AND y.bap_id IS NULL AND y.is_deleted = 0)
               )
             GROUP BY d.bahan_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $peta[(int) $r['bahan_id']] = (float) $r['qty'];
        }
        return $peta;
    }

    /**
     * Baris catatan MASUK otomatis dari BAST untuk Buku Material dan
     * ekspornya: satu baris per rincian SPK yang diterima (bila tercakup
     * beberapa BAST, dipakai yang tanggalnya paling awal). Tanggal =
     * tanggal BAST; kosong -> tanggal BAP -> tanggal pembuatan BAST.
     * Kolom: detail_id, bahan_id, kode, nama, satuan, masuk, tanggal,
     * spk_nomor, bast_urut.
     */
    protected function barisMasukBast(): array
    {
        $db = $this->db();
        $perDetail = [];
        $catat = static function (array $r) use (&$perDetail) {
            $did = (int) $r['detail_id'];
            $tgl = $r['tanggal'] ?? null;
            if (! isset($perDetail[$did]) || ($tgl !== null && ($perDetail[$did]['tanggal'] === null || $tgl < $perDetail[$did]['tanggal']))) {
                $perDetail[$did] = $r;
            }
        };
        foreach ($db->query(
            'SELECT d.id AS detail_id, d.bahan_id, b.kode, b.nama, b.satuan, d.qty AS masuk,
                    COALESCE(x.tanggal, bp.tanggal, DATE(x.created_at)) AS tanggal,
                    s.nomor AS spk_nomor, x.nomor_urut AS bast_urut
             FROM spk_detail d
             JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
             JOIN bahan b ON b.id = d.bahan_id
             JOIN bap_detail bd ON bd.detail_id = d.id AND bd.is_deleted = 0
             JOIN bap bp ON bp.id = bd.bap_id AND bp.is_deleted = 0
             JOIN bast x ON x.bap_id = bd.bap_id AND x.is_deleted = 0
             WHERE b.sekolah_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0',
            [$this->sid()]
        )->getResultArray() as $r) {
            $catat($r);
        }
        // BAST lama tanpa bap_id: mencakup seluruh rincian SPK-nya.
        foreach ($db->query(
            'SELECT d.id AS detail_id, d.bahan_id, b.kode, b.nama, b.satuan, d.qty AS masuk,
                    COALESCE(y.tanggal, DATE(y.created_at)) AS tanggal,
                    s.nomor AS spk_nomor, y.nomor_urut AS bast_urut
             FROM spk_detail d
             JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
             JOIN bahan b ON b.id = d.bahan_id
             JOIN bast y ON y.spk_id = s.id AND y.bap_id IS NULL AND y.is_deleted = 0
             WHERE b.sekolah_id = ? AND d.bahan_id IS NOT NULL AND d.is_deleted = 0',
            [$this->sid()]
        )->getResultArray() as $r) {
            $catat($r);
        }
        return array_values($perDetail);
    }

    protected function kebutuhanBahan(int $gedungId): array
    {
        $db = $this->db();
        $mentah = $db->query(
            "SELECT b.id AS bahan_id, b.kode, b.nama, b.satuan, ak.satuan AS satuan_komponen,
                    SUM(COALESCE(p.volume_revisi, p.volume) * ak.koefisien) AS qty_mentah
             FROM pekerjaan p
             JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = ?))
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'bahan' AND ak.bahan_id IS NOT NULL
             JOIN bahan b ON b.id = ak.bahan_id
             WHERE p.gedung_id = ? AND p.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0 AND b.is_deleted = 0
             GROUP BY b.id, b.kode, b.nama, b.satuan, ak.satuan",
            [$this->sid(), $gedungId]
        )->getResultArray();
        $gabung = [];
        foreach ($mentah as $r) {
            $id = (int) $r['bahan_id'];
            if (! isset($gabung[$id])) {
                $gabung[$id] = $r + ['qty_butuh' => 0.0, 'dikonversi' => false, 'satuan_beda' => false];
            }
            $faktor = $this->faktorSatuan($r['satuan_komponen'] ?? null, $r);
            $gabung[$id]['qty_butuh'] += (float) $r['qty_mentah'] * $faktor;
            if ($faktor !== 1.0) {
                $gabung[$id]['dikonversi'] = true;
            } elseif (mb_strtolower(trim((string) ($r['satuan_komponen'] ?? ''))) !== ''
                && mb_strtolower(trim((string) ($r['satuan_komponen'] ?? ''))) !== mb_strtolower(trim((string) $r['satuan']))) {
                $gabung[$id]['satuan_beda'] = true;
            }
        }
        $tertaut = array_values($gabung);
        usort($tertaut, static fn ($a, $b) => $b['qty_butuh'] <=> $a['qty_butuh']);
        $takTertaut = $db->query(
            "SELECT ak.nama, SUM(COALESCE(p.volume_revisi, p.volume) * ak.koefisien) AS qty_butuh, COUNT(DISTINCT p.id) AS dipakai_pekerjaan
             FROM pekerjaan p
             JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = ?))
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'bahan' AND ak.bahan_id IS NULL
             WHERE p.gedung_id = ? AND p.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0
             GROUP BY ak.nama ORDER BY qty_butuh DESC",
            [$this->sid(), $gedungId]
        )->getResultArray();
        return ['tertaut' => $tertaut, 'tak_tertaut' => $takTertaut];
    }

    /** Kebutuhan upah satu gedung (orang-hari per jenis tenaga), dari BOQ x koefisien AHSP. */
    protected function kebutuhanUpah(int $gedungId): array
    {
        return $this->db()->query(
            "SELECT ak.nama AS jenis, SUM(COALESCE(p.volume_revisi, p.volume) * ak.koefisien) AS orang_hari,
                    COALESCE(MAX(um.tarif), MAX(umn.tarif), MAX(ak.tarif), 0) AS tarif
             FROM pekerjaan p
             JOIN analisa a ON (a.id = p.analisa_id OR (p.analisa_id IS NULL AND a.kode = p.kode_analisa AND a.sekolah_id = ?))
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'upah'
             LEFT JOIN upah_master um ON um.id = ak.upah_id AND um.is_deleted = 0
             LEFT JOIN upah_master umn ON ak.upah_id IS NULL AND umn.sekolah_id = a.sekolah_id AND umn.jenis = ak.nama AND umn.is_deleted = 0
             WHERE p.gedung_id = ? AND p.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0
               AND NOT EXISTS (SELECT 1 FROM upah_master umh WHERE umh.is_deleted = 1
                     AND (umh.id = ak.upah_id OR (ak.upah_id IS NULL AND umh.sekolah_id = a.sekolah_id AND umh.jenis = ak.nama)))
             GROUP BY ak.nama ORDER BY orang_hari DESC",
            [$this->sid(), $gedungId]
        )->getResultArray();
    }

    /** Gedung aktif yang didanai paket (untuk total & rekap tingkat sekolah). */
    protected function semuaGedungDidanai(): array
    {
        return array_values(array_filter($this->semuaGedung(), static fn ($g) => (int) ($g['didanai'] ?? 1) === 1));
    }

    /** Kebutuhan bahan gabungan seluruh gedung sekolah (per bahan master). */
    protected function kebutuhanBahanSekolah(): array
    {
        $gabung = [];
        $takTertaut = [];
        foreach ($this->semuaGedungDidanai() as $g) {
            $kb = $this->kebutuhanBahan((int) $g['id']);
            foreach ($kb['tertaut'] as $b) {
                $id = (int) $b['bahan_id'];
                if (! isset($gabung[$id])) {
                    $gabung[$id] = $b;
                } else {
                    $gabung[$id]['qty_butuh'] += (float) $b['qty_butuh'];
                    $gabung[$id]['dikonversi'] = $gabung[$id]['dikonversi'] || ! empty($b['dikonversi']);
                    $gabung[$id]['satuan_beda'] = ($gabung[$id]['satuan_beda'] ?? false) || ! empty($b['satuan_beda']);
                }
            }
            foreach ($kb['tak_tertaut'] as $t) {
                $takTertaut[$t['nama']] = ($takTertaut[$t['nama']] ?? 0) + (float) $t['qty_butuh'];
            }
        }
        $tertaut = array_values($gabung);
        usort($tertaut, static fn ($a, $b) => $b['qty_butuh'] <=> $a['qty_butuh']);
        return ['tertaut' => $tertaut, 'tak_tertaut' => $takTertaut];
    }

    /** Kebutuhan upah gabungan seluruh gedung sekolah (per jenis tenaga). */
    protected function kebutuhanUpahSekolah(): array
    {
        $gabung = [];
        foreach ($this->semuaGedungDidanai() as $g) {
            foreach ($this->kebutuhanUpah((int) $g['id']) as $u) {
                $j = $u['jenis'];
                if (! isset($gabung[$j])) {
                    $gabung[$j] = $u;
                } else {
                    $gabung[$j]['orang_hari'] += (float) $u['orang_hari'];
                }
            }
        }
        $rows = array_values($gabung);
        usort($rows, static fn ($a, $b) => $b['orang_hari'] <=> $a['orang_hari']);
        return $rows;
    }

    /** Total belanja terbayar sekolah: invoice 'Sudah Bayar' + pembayaran upah. */
    protected function totalBelanja(): float
    {
        $db = $this->db();
        $inv = $db->query(
            "SELECT COALESCE(SUM(i.nilai), 0) AS t FROM invoice i JOIN spk s ON s.id = i.spk_id
             WHERE i.status = 'Sudah Bayar' AND s.sekolah_id = ? AND i.is_deleted = 0 AND s.is_deleted = 0",
            [$this->sid()]
        )->getRowArray();
        $upah = $db->table('upah_bayar')->selectSum('jumlah')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        return (float) ($inv['t'] ?? 0) + (float) ($upah['jumlah'] ?? 0);
    }

    /** Total nilai BOQ semua gedung AKTIF sebuah sekolah (volume x harga satuan pekerjaan aktif). */
    /** @var array<int, array<string, float>> cache peta harga AHSP per sekolah */
    private array $petaAhsp = [];

    private array $petaAhspId = [];

    private array $cacheAnalisaId = [];

    /**
     * Harga satuan hasil AHSP per kode analisa: jumlah koefisien x tarif
     * komponen aktif saat ini. Tarif komponen upah memakai yang terbesar
     * antara tarif komponen dan tarif Master Upah (sama seperti
     * kebutuhanUpah()). Inilah dasar penilaian BOQ berjalan.
     */
    protected function petaHargaAhsp(?int $sid = null): array
    {
        $sid = $sid ?? $this->sid();
        if (isset($this->petaAhsp[$sid])) {
            return $this->petaAhsp[$sid];
        }
        $this->petaAhsp[$sid] = [];
        $this->petaAhspId[$sid] = [];
        foreach ($this->db()->query(
            "SELECT a.id, a.kode, SUM(ak.koefisien * CASE WHEN ak.jenis = 'upah'
                        THEN GREATEST(COALESCE(ak.tarif, 0), COALESCE(
                            (SELECT MAX(um.tarif) FROM upah_master um WHERE um.id = ak.upah_id AND um.is_deleted = 0),
                            (SELECT MAX(um2.tarif) FROM upah_master um2 WHERE ak.upah_id IS NULL AND um2.sekolah_id = a.sekolah_id AND um2.jenis = ak.nama AND um2.is_deleted = 0), 0))
                        ELSE COALESCE(ak.tarif, 0) END) AS harga
             FROM analisa a JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.is_deleted = 0
             WHERE a.sekolah_id = ? AND a.is_deleted = 0 GROUP BY a.id, a.kode",
            [$sid]
        )->getResultArray() as $r) {
            $this->petaAhsp[$sid][(string) $r['kode']] = (float) $r['harga'];
            $this->petaAhspId[$sid][(int) $r['id']] = (float) $r['harga'];
        }
        return $this->petaAhsp[$sid];
    }

    /** Harga AHSP satu kode analisa; null bila analisa/komponennya tidak ada. */
    protected function hargaAhspKode(?string $kode, ?int $sid = null): ?float
    {
        if ($kode === null || $kode === '') {
            return null;
        }
        $peta = $this->petaHargaAhsp($sid);
        return $peta[$kode] ?? null;
    }

    /** Harga AHSP satu analisa menurut id; null bila analisa/komponennya tidak ada. */
    protected function hargaAhspId(?int $analisaId, ?int $sid = null): ?float
    {
        if ($analisaId === null || $analisaId <= 0) {
            return null;
        }
        $sid = $sid ?? $this->sid();
        $this->petaHargaAhsp($sid);
        return $this->petaAhspId[$sid][$analisaId] ?? null;
    }

    /**
     * Id analisa dari kode pada satu sekolah (relasi pekerjaan -> analisa
     * sejak migrasi 000018). Dipakai jalur tulis agar analisa_id selalu
     * terisi mengikuti kode_analisa; null bila kode kosong/tidak dikenal.
     */
    protected function analisaIdDariKode(?string $kode, ?int $sid = null): ?int
    {
        $kode = trim((string) $kode);
        if ($kode === '') {
            return null;
        }
        $sid = $sid ?? $this->sid();
        $kunci = $sid . '|' . $kode;
        if (! array_key_exists($kunci, $this->cacheAnalisaId)) {
            $a = $this->db()->table('analisa')->select('id')
                ->where(['sekolah_id' => $sid, 'kode' => $kode, 'is_deleted' => 0])
                ->get()->getRowArray();
            $this->cacheAnalisaId[$kunci] = $a ? (int) $a['id'] : null;
        }
        return $this->cacheAnalisaId[$kunci];
    }

    /** Total BOQ berjalan satu gedung dengan dasar harga berjalan (AHSP). */
    protected function totalBoqGedung(int $gedungId): float
    {
        $total = 0.0;
        foreach ($this->db()->table('pekerjaan')
            ->select('volume, volume_revisi, harga_satuan, harga_revisi, kode_analisa, analisa_id')
            ->where(['gedung_id' => $gedungId, 'is_deleted' => 0])->get()->getResultArray() as $p) {
            $total += $this->volBerjalan($p) * $this->hargaBerjalan($p);
        }
        return $total;
    }

    /** Nilai realisasi satu gedung: volume realisasi x harga berjalan pekerjaannya. */
    protected function nilaiRealisasiGedung(int $gedungId): float
    {
        $total = 0.0;
        foreach ($this->db()->query(
            'SELECT r.volume, p.harga_satuan, p.harga_revisi, p.kode_analisa, p.analisa_id
             FROM realisasi r JOIN pekerjaan p ON p.id = r.pekerjaan_id
             WHERE p.gedung_id = ? AND r.is_deleted = 0 AND p.is_deleted = 0',
            [$gedungId]
        )->getResultArray() as $r) {
            $total += (float) $r['volume'] * $this->hargaBerjalan($r);
        }
        return $total;
    }

    /** Nilai realisasi per tanggal satu gedung (untuk Kurva S). */
    protected function nilaiRealisasiPerTanggal(int $gedungId): array
    {
        $per = [];
        foreach ($this->db()->query(
            'SELECT r.tanggal, r.volume, p.harga_satuan, p.harga_revisi, p.kode_analisa, p.analisa_id
             FROM realisasi r JOIN pekerjaan p ON p.id = r.pekerjaan_id
             WHERE p.gedung_id = ? AND r.is_deleted = 0 AND p.is_deleted = 0
             ORDER BY r.tanggal',
            [$gedungId]
        )->getResultArray() as $r) {
            $per[$r['tanggal']] = ($per[$r['tanggal']] ?? 0.0) + (float) $r['volume'] * $this->hargaBerjalan($r);
        }
        $hasil = [];
        foreach ($per as $tgl => $nilai) {
            $hasil[] = ['tanggal' => $tgl, 'nilai' => $nilai];
        }
        return $hasil;
    }

    protected function totalBoqAktif(?int $sid = null): float
    {
        $sid = $sid ?? $this->sid();
        $total = 0.0;
        foreach ($this->db()->query(
            'SELECT p.volume, p.volume_revisi, p.harga_satuan, p.harga_revisi, p.kode_analisa, p.analisa_id
             FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND g.is_deleted = 0 AND g.didanai = 1',
            [$sid]
        )->getResultArray() as $p) {
            $total += $this->volBerjalan($p) * $this->hargaBerjalan($p, $sid);
        }
        return $total;
    }

    /**
     * Verifikasi anggaran: total BOQ gedung aktif + biaya non-gedung
     * dibandingkan pagu sekolah. Mengembalikan [total_boq, biaya_lain,
     * total_semua, pagu, selisih (pagu - total_semua), gedung (jumlah aktif)].
     */
    protected function verifikasiAnggaran(?int $sid = null): array
    {
        $sid = $sid ?? $this->sid();
        $sekolah = $this->db()->table('sekolah')->where('id', $sid)->get()->getRowArray() ?: [];
        $n = $this->db()->query(
            'SELECT COUNT(*) AS n FROM gedung WHERE sekolah_id = ? AND is_deleted = 0 AND didanai = 1',
            [$sid]
        )->getRowArray();
        $nSemua = $this->db()->query(
            'SELECT COUNT(*) AS n FROM gedung WHERE sekolah_id = ? AND is_deleted = 0',
            [$sid]
        )->getRowArray();
        $total = $this->totalBoqAktif($sid);
        $pagu = (float) ($sekolah['total_anggaran'] ?? 0);
        $bl = $this->db()->query(
            'SELECT COALESCE(SUM(jumlah), 0) AS t FROM biaya_lain WHERE sekolah_id = ? AND is_deleted = 0',
            [$sid]
        )->getRowArray();
        $biayaLain = (float) ($bl['t'] ?? 0);
        $totalSemua = $total + $biayaLain;
        return [
            'total_boq' => $total,
            'biaya_lain' => $biayaLain,
            'total_semua' => $totalSemua,
            'pagu' => $pagu,
            'selisih' => $pagu - $totalSemua,
            'gedung' => (int) ($n['n'] ?? 0),
            'gedung_semua' => (int) ($nSemua['n'] ?? 0),
        ];
    }

    /** Total RAB awal (volume & harga dokumen perencana, sebelum revisi). */
    protected function totalRabAwal(?int $sid = null): float
    {
        $sid = $sid ?? $this->sid();
        $total = 0.0;
        foreach ($this->db()->query(
            'SELECT p.volume, p.harga_satuan, p.kode_analisa, p.analisa_id
             FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND g.is_deleted = 0 AND g.didanai = 1',
            [$sid]
        )->getResultArray() as $p) {
            $harga = $this->hargaAhspId(isset($p['analisa_id']) ? (int) $p['analisa_id'] : null, $sid)
                ?? $this->hargaAhspKode($p['kode_analisa'] ?? null, $sid)
                ?? (float) $p['harga_satuan'];
            $total += (float) $p['volume'] * $harga;
        }
        return $total;
    }

    /** Volume berjalan sebuah pekerjaan: hasil revisi disahkan, atau awal. */
    protected function volBerjalan(array $p): float
    {
        return (float) ($p['volume_revisi'] ?? $p['volume'] ?? 0);
    }

    /**
     * Harga satuan berjalan sebuah pekerjaan. Urutan dasarnya:
     * harga revisi yang disahkan (BA Revisi) > harga hasil AHSP
     * (jumlah komponen analisa terkini) > harga satuan dokumen BOQ
     * (untuk pekerjaan tanpa analisa/komponen).
     */
    /**
     * Simpan satu sel realisasi mingguan Simulasi (tabel simulasi_realisasi):
     * nilai > 0 di-upsert (baris terhapus lunak dihidupkan lagi), nilai 0
     * menghapus lunak barisnya. Satu-satunya jalur tulis tabel ini — dipakai
     * Simulasi::simpan (formulir massal) dan Kurva::simpanRealisasi (isi per
     * baris di halaman Kurva S). Mengembalikan true bila nilai tersimpan.
     */
    protected function simpanRealisasiSimulasi(int $mingguKe, int $gedungId, float $nilai): bool
    {
        $db = $this->db();
        $ada = $db->table('simulasi_realisasi')
            ->where(['sekolah_id' => $this->sid(), 'minggu_ke' => $mingguKe, 'gedung_id' => $gedungId])->get()->getRowArray();
        if ($nilai > 0) {
            if ($ada) {
                $db->table('simulasi_realisasi')->where('id', (int) $ada['id'])
                    ->update(['bobot' => $nilai, 'is_deleted' => 0, 'deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                $db->table('simulasi_realisasi')
                    ->insert(['sekolah_id' => $this->sid(), 'minggu_ke' => $mingguKe, 'gedung_id' => $gedungId, 'bobot' => $nilai, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
            return true;
        }
        if ($ada && (int) $ada['is_deleted'] === 0) {
            $this->hapusLunak('simulasi_realisasi', ['id' => (int) $ada['id']]);
        }
        return false;
    }

    protected function hargaBerjalan(array $p, ?int $sid = null): float
    {
        if (($p['harga_revisi'] ?? null) !== null) {
            return (float) $p['harga_revisi'];
        }
        return $this->hargaAhspId(isset($p['analisa_id']) ? (int) $p['analisa_id'] : null, $sid)
            ?? $this->hargaAhspKode($p['kode_analisa'] ?? null, $sid)
            ?? (float) ($p['harga_satuan'] ?? 0);
    }

    /**
     * Nomor SPK berikutnya untuk satu sekolah: MAX(nomor) dari SELURUH
     * baris spk sekolah itu, termasuk yang terhapus lunak, ditambah 1.
     * Kunci unik spk_sekolah_nomor tetap dipegang baris terhapus, jadi
     * menghitung hanya dari baris aktif menyebabkan tabrakan
     * "duplicate entry" sesudah ada SPK yang dihapus. Dipakai semua
     * jalur pembuatan SPK (Register SPK, Rencana Belanja, Rencana
     * Mingguan) — jangan menghitung nomor dengan cara lain.
     */
    protected function nomorSpkBerikutnya(?int $sid = null): int
    {
        $row = $this->db()->query(
            'SELECT MAX(nomor) m FROM spk WHERE sekolah_id = ?',
            [$sid ?? $this->sid()]
        )->getRow();
        return ((int) ($row->m ?? 0)) + 1;
    }

    /**
     * Hitung ulang volume/harga berjalan satu pekerjaan dari revisi
     * disahkan TERAKHIR yang menyentuhnya (jenis volume/hapus/ganti).
     * Tanpa revisi disahkan, berjalan kembali ke RAB awal (NULL).
     */
    protected function segarkanBerjalan(int $pekerjaanId): void
    {
        $r = $this->db()->table('revisi_pekerjaan')
            ->where(['pekerjaan_id' => $pekerjaanId, 'status' => 'disahkan', 'is_deleted' => 0])
            ->whereIn('jenis', ['volume', 'hapus', 'ganti'])
            ->orderBy('disahkan_at', 'DESC')->orderBy('id', 'DESC')
            ->get()->getRowArray();
        $ubah = ['volume_revisi' => null, 'harga_revisi' => null];
        if ($r) {
            $ubah['volume_revisi'] = $r['jenis'] === 'volume' ? $r['volume_baru'] : 0;
            $ubah['harga_revisi'] = $r['harga_baru'] !== null ? (float) $r['harga_baru'] : null;
        }
        $this->db()->table('pekerjaan')->where('id', $pekerjaanId)->update($ubah);
    }

    /**
     * Kontrol pagu (Aturan 4): total belanja tidak boleh melebihi total anggaran.
     * Mengembalikan [boleh, sisa_sebelum, total_anggaran].
     */
    protected function cekPagu(float $tambahan): array
    {
        $anggaran = (float) ($this->sekolahAktif()['total_anggaran'] ?? 0);
        $terpakai = $this->totalBelanja();
        if ($anggaran <= 0) {
            return [true, PHP_FLOAT_MAX, 0.0]; // pagu belum diatur -> tidak memblokir
        }
        return [$terpakai + $tambahan <= $anggaran + 0.5, $anggaran - $terpakai, $anggaran];
    }

    /**
     * Peta PPN per SPK sekolah aktif: total nilai SPK dan PPN yang terkandung
     * di dalamnya (baris bertanda kena PPN; PPN = 11/111 dari harga, karena
     * harga toko dianggap sudah termasuk PPN).
     */
    protected function ppnSpkMap(): array
    {
        $map = [];
        foreach ($this->db()->query(
            'SELECT d.spk_id, COALESCE(SUM(d.qty*d.harga),0) AS total,
                    COALESCE(SUM(CASE WHEN d.pajak = 1 THEN ROUND(0.11 * d.qty * d.harga / 1.11) ELSE 0 END),0) AS ppn
             FROM spk_detail d JOIN spk s ON s.id = d.spk_id AND s.is_deleted = 0
             WHERE s.sekolah_id = ? AND d.spk_id IS NOT NULL AND d.is_deleted = 0 GROUP BY d.spk_id',
            [$this->sid()]
        )->getResultArray() as $r) {
            $map[(int) $r['spk_id']] = ['total' => (float) $r['total'], 'ppn' => (float) $r['ppn']];
        }
        return $map;
    }

    /** PPN terkandung pada satu invoice: proporsional terhadap total SPK-nya. */
    protected function ppnInvoice(array $peta, int $spkId, float $nilai): float
    {
        $info = $peta[$spkId] ?? null;
        if ($info === null || $info['total'] <= 0 || $info['ppn'] <= 0) {
            return 0.0;
        }
        return round($info['ppn'] * $nilai / $info['total']);
    }

    /**
     * Pajak pembelian menurut Juknis Revitalisasi SMP 2026:
     * - DPP (dasar pengenaan pajak) = nilai tagihan di luar PPN.
     * - PPN 11% dipungut bendahara dari bagian harga yang kena PPN.
     * - PPh 22 = 1,5% dari DPP; pembelian dengan DPP sampai dengan
     *   Rp2.000.000 TIDAK dipungut PPh 22.
     * Mengembalikan [dpp, ppn, pph22, dibayar] dengan dibayar = nilai bersih
     * yang diterima penyedia (nilai - PPN dipungut - PPh 22).
     */
    protected function pajakPembelian(float $nilai, float $ppnTermasuk): array
    {
        $dpp = max(0.0, $nilai - $ppnTermasuk);
        $pph22 = $dpp > 2000000 ? round($dpp * 0.015) : 0.0;
        return [
            'dpp' => $dpp,
            'ppn' => $ppnTermasuk,
            'pph22' => $pph22,
            'dibayar' => $nilai - $ppnTermasuk - $pph22,
        ];
    }

    /**
     * Ketentuan PBJ menurut nilai paket (Juknis Revitalisasi SMP 2026,
     * mengacu Permendikbudristek 18/2022): bentuk surat pemesanan, metode
     * penetapan penyedia + jumlah minimum calon penyedia, dan kewajiban
     * materai pada kuitansi.
     */
    protected function ketentuanPbj(float $nilai): array
    {
        if ($nilai <= 10000000) {
            $dokumen = 'Nota pembelian/pembayaran';
        } elseif ($nilai <= 50000000) {
            $dokumen = 'Kuitansi';
        } else {
            $dokumen = 'Surat Perintah Kerja (SPK)';
        }
        if ($nilai <= 50000000) {
            $metode = 'Pemilihan langsung atau negosiasi harga';
            $min = 1;
        } elseif ($nilai <= 200000000) {
            $metode = 'Perbandingan harga, kualitas, ketersediaan, dan kemudahan';
            $min = 2;
        } else {
            $metode = 'Perbandingan harga, kualitas, ketersediaan, dan kemudahan';
            $min = 3;
        }
        return [
            'dokumen' => $dokumen,
            'metode' => $metode,
            'min_penyedia' => $min,
            'materai' => $nilai >= 5000000,
        ];
    }

    /** Pilihan pajak yang dipotong saat membayar baris Biaya Non-Gedung. */
    protected function opsiPajakBiayaLain(): array
    {
        return [
            '' => 'Tanpa potongan pajak',
            'pph23' => 'PPh 23 — jasa/sewa (2%)',
            'pph42-ska' => 'PPh 4 ayat 2 — perencana/pengawas ber-SKA (3,5%)',
            'pph42' => 'PPh 4 ayat 2 — perencana/pengawas tanpa SKA (6%)',
            'pph21-gol4' => 'PPh 21 — honor PNS Gol. IV (15%)',
            'pph21-gol3' => 'PPh 21 — honor PNS Gol. III (5%)',
            'pph21-gol2' => 'PPh 21 — honor PNS Gol. II (0%)',
            'pph21-nonpns' => 'PPh 21 — honor non-PNS (5% dari 50%)',
        ];
    }

    /** Nominal potongan pajak satu baris Biaya Non-Gedung menurut jenisnya. */
    protected function potonganPajak(?string $jenis, float $jumlah): float
    {
        $tarif = [
            'pph23' => 0.02,
            'pph42-ska' => 0.035,
            'pph42' => 0.06,
            'pph21-gol4' => 0.15,
            'pph21-gol3' => 0.05,
            'pph21-gol2' => 0.0,
            'pph21-nonpns' => 0.5 * 0.05,
        ];
        $kunci = $jenis ?? '';
        return isset($tarif[$kunci]) ? round($jumlah * $tarif[$kunci]) : 0.0;
    }

    /**
     * PPh 21 upah harian menurut Juknis: upah sampai dengan Rp450.000 per
     * hari tidak dipotong; di atasnya dipotong 5% dari kelebihan per hari.
     * Catatan: batas kumulatif Rp4.500.000 dalam 22 hari kerja per bulan
     * untuk penerima yang sama perlu dicek manual dari riwayat pembayaran.
     */
    protected function pph21UpahHarian(float $tarifPerHari, float $orangHari): float
    {
        if ($tarifPerHari <= 450000) {
            return 0.0;
        }
        return round(($tarifPerHari - 450000) * 0.05 * $orangHari);
    }

    /** Label jenis bukti setor ke kas negara. */
    protected function labelSetor(string $jenis): string
    {
        return [
            'pph22' => 'PPh Pasal 22',
            'ppn' => 'PPN dipungut',
            'pph21' => 'PPh Pasal 21',
            'pph23' => 'PPh Pasal 23',
            'pph42' => 'PPh Pasal 4 ayat 2',
            'sisa_dana' => 'Pengembalian sisa dana bantuan (SIMPONI)',
        ][$jenis] ?? $jenis;
    }

    /**
     * Gabungan seluruh transaksi kas sekolah aktif untuk pembukuan
     * (BKU / Buku Bank / Buku Pembantu Kas Tunai menurut Juknis):
     * transaksi manual (kas_transaksi) + pembayaran invoice (keluar via
     * bank, sebesar nilai bersih yang diterima penyedia) + pembayaran
     * upah (keluar tunai, sebesar nilai bersih diterima pekerja) +
     * penyetoran ke kas negara (bukti_setor). Diurutkan tanggal menaik.
     * $kanal: null = semua (BKU), 'bank' = Buku Bank, 'tunai' = BPKT.
     */
    protected function gabungBukuKas(?string $kanal = null): array
    {
        $db = $this->db();
        $sid = $this->sid();
        $entri = [];

        foreach ($db->table('kas_transaksi')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $k) {
            $entri[] = [
                'tanggal' => $k['tanggal'], 'uraian' => $k['uraian'],
                'masuk' => $k['jenis'] === 'masuk' ? (float) $k['jumlah'] : 0.0,
                'keluar' => $k['jenis'] === 'keluar' ? (float) $k['jumlah'] : 0.0,
                'kanal' => $k['kanal'], 'sumber' => 'manual', 'ref_id' => (int) $k['id'],
                'kategori' => $k['kategori'],
            ];
        }

        $peta = $this->ppnSpkMap();
        foreach ($db->query(
            'SELECT p.tanggal AS tgl_bayar, i.tanggal AS tgl_inv, i.nomor_urut, i.nilai, i.spk_id,
                    s.nomor AS spk_nomor, py.nama AS penyedia
             FROM pembayaran p JOIN invoice i ON i.id = p.invoice_id AND i.is_deleted = 0
             JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
             LEFT JOIN penyedia py ON py.id = s.penyedia_id
             WHERE s.sekolah_id = ? AND p.is_deleted = 0',
            [$sid]
        )->getResultArray() as $r) {
            $pjk = $this->pajakPembelian((float) $r['nilai'], $this->ppnInvoice($peta, (int) $r['spk_id'], (float) $r['nilai']));
            $entri[] = [
                'tanggal' => $r['tgl_bayar'] ?: $r['tgl_inv'],
                'uraian' => 'Pembayaran Invoice ' . (int) $r['nomor_urut'] . ' (SPK ' . (int) $r['spk_nomor'] . ')'
                    . ($r['penyedia'] ? ' — ' . $r['penyedia'] : '')
                    . '; tagihan ' . $this->rp($r['nilai'])
                    . ($pjk['ppn'] > 0 ? '; PPN dipungut ' . $this->rp($pjk['ppn']) : '')
                    . ($pjk['pph22'] > 0 ? '; PPh 22 ' . $this->rp($pjk['pph22']) : ''),
                'masuk' => 0.0, 'keluar' => $pjk['dibayar'],
                'kanal' => 'bank', 'sumber' => 'invoice', 'ref_id' => (int) $r['nomor_urut'],
                'kategori' => 'belanja',
            ];
        }

        foreach ($db->table('upah_bayar')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $u) {
            $pph21 = $this->pph21UpahHarian((float) $u['tarif'], (float) $u['orang_hari']);
            $entri[] = [
                'tanggal' => $u['tanggal'],
                'uraian' => 'Upah ' . $u['jenis_tenaga'] . ($u['penerima'] ? ' — ' . $u['penerima'] : '')
                    . ' (' . rtrim(rtrim(number_format((float) $u['orang_hari'], 2, ',', '.'), '0'), ',') . ' OH × ' . $this->rp($u['tarif']) . ')'
                    . ($pph21 > 0 ? '; PPh 21 ' . $this->rp($pph21) : ''),
                'masuk' => 0.0, 'keluar' => (float) $u['jumlah'] - $pph21,
                'kanal' => 'tunai', 'sumber' => 'upah', 'ref_id' => (int) $u['id'],
                'kategori' => 'belanja',
            ];
        }

        foreach ($db->table('bukti_setor')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $b) {
            $entri[] = [
                'tanggal' => $b['tanggal_setor'],
                'uraian' => 'Setor ' . $this->labelSetor($b['jenis']) . ($b['masa'] ? ' masa ' . $b['masa'] : '')
                    . ($b['ntpn'] ? ' — NTPN ' . $b['ntpn'] : ''),
                'masuk' => 0.0, 'keluar' => (float) $b['jumlah'],
                'kanal' => 'bank', 'sumber' => 'setor', 'ref_id' => (int) $b['id'],
                'kategori' => $b['jenis'] === 'sisa_dana' ? 'sisa_dana' : 'pajak',
            ];
        }

        if ($kanal !== null) {
            $entri = array_values(array_filter($entri, static fn ($e) => $e['kanal'] === $kanal));
        }
        usort($entri, static fn ($a, $b) => [$a['tanggal'] ?? '9999', $a['sumber'], $a['ref_id']] <=> [$b['tanggal'] ?? '9999', $b['sumber'], $b['ref_id']]);
        return $entri;
    }

    /** Penerimaan dana bantuan per tahap dari kas_transaksi (kategori penerimaan). */
    protected function penerimaanPerTahap(): array
    {
        $hasil = [1 => 0.0, 2 => 0.0, 'lain' => 0.0];
        foreach ($this->db()->table('kas_transaksi')->where(['sekolah_id' => $this->sid(), 'kategori' => 'penerimaan', 'jenis' => 'masuk', 'is_deleted' => 0])->get()->getResultArray() as $k) {
            $t = (int) ($k['tahap'] ?? 0);
            if ($t === 1 || $t === 2) {
                $hasil[$t] += (float) $k['jumlah'];
            } else {
                $hasil['lain'] += (float) $k['jumlah'];
            }
        }
        return $hasil;
    }

    /** Harga acuan sebuah bahan: negosiasi -> penawaran -> pembanding terpilih -> RAB. */
    /**
     * Penyedia khusus "Masyarakat" untuk sekolah aktif: pembelian dari warga
     * (pasir, batu, kayu, dsb.) tidak didaftarkan satu per satu — cukup satu
     * record ini, nama penerimanya diisi manual per SPK (spk.penerima_manual).
     * Dibuat otomatis bila belum ada; record terhapus lunak dihidupkan lagi.
     */
    protected function penyediaMasyarakatId(): int
    {
        $db = $this->db();
        $ada = $db->table('penyedia')->where(['sekolah_id' => $this->sid(), 'nama' => 'Masyarakat', 'is_deleted' => 0])->get()->getRowArray();
        if ($ada) {
            return (int) $ada['id'];
        }
        $lama = $db->table('penyedia')->where(['sekolah_id' => $this->sid(), 'nama' => 'Masyarakat', 'is_deleted' => 1])->get()->getRowArray();
        if ($lama) {
            $db->table('penyedia')->where('id', (int) $lama['id'])->update(['is_deleted' => 0, 'deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')]);
            return (int) $lama['id'];
        }
        $db->table('penyedia')->insert([
            'sekolah_id' => $this->sid(), 'nama' => 'Masyarakat',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    protected function hargaAcuan(int $bahanId): int
    {
        $db = $this->db();
        $nego = $db->table('negosiasi')->where(['bahan_id' => $bahanId, 'is_deleted' => 0])->get()->getRowArray();
        if ($nego) {
            if (! empty($nego['harga_nego'])) {
                return (int) $nego['harga_nego'];
            }
            if (! empty($nego['harga_penawaran'])) {
                return (int) $nego['harga_penawaran'];
            }
        }
        $bahan = $db->table('bahan')->where(['id' => $bahanId, 'is_deleted' => 0])->get()->getRowArray();
        return (int) ($bahan['harga_rab'] ?? 0);
    }

    protected function rp($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }

    /** Ubah angka menjadi kata Bahasa Indonesia (untuk terbilang di dokumen). */
    protected function terbilang($n): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $this->terbilangMentah($n)));
    }

    private function terbilangMentah($n): string
    {
        $n = abs((int) $n);
        $kata = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        if ($n < 12) {
            return $kata[$n];
        }
        if ($n < 20) {
            return $this->terbilangMentah($n - 10) . ' Belas';
        }
        if ($n < 100) {
            return $this->terbilangMentah(intdiv($n, 10)) . ' Puluh ' . $this->terbilangMentah($n % 10);
        }
        if ($n < 200) {
            return 'Seratus ' . $this->terbilangMentah($n - 100);
        }
        if ($n < 1000) {
            return $this->terbilangMentah(intdiv($n, 100)) . ' Ratus ' . $this->terbilangMentah($n % 100);
        }
        if ($n < 2000) {
            return 'Seribu ' . $this->terbilangMentah($n - 1000);
        }
        if ($n < 1000000) {
            return $this->terbilangMentah(intdiv($n, 1000)) . ' Ribu ' . $this->terbilangMentah($n % 1000);
        }
        if ($n < 1000000000) {
            return $this->terbilangMentah(intdiv($n, 1000000)) . ' Juta ' . $this->terbilangMentah($n % 1000000);
        }
        if ($n < 1000000000000) {
            return $this->terbilangMentah(intdiv($n, 1000000000)) . ' Miliar ' . $this->terbilangMentah($n % 1000000000);
        }
        return $this->terbilangMentah(intdiv($n, 1000000000000)) . ' Triliun ' . $this->terbilangMentah($n % 1000000000000);
    }

    /** Nomor surat resmi: 422/<JENIS>/<3 digit>/<tahun anggaran>. */
    protected function nomorSurat(string $jenis, int $urut): string
    {
        $idn = $this->sekolahAktif();
        $tahun = $idn['tahun_anggaran'] ?? date('Y');
        return '422/' . $jenis . '/' . str_pad((string) $urut, 3, '0', STR_PAD_LEFT) . '/' . $tahun;
    }
}
