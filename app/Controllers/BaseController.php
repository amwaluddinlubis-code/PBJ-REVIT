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
        $data['judul']        = $judul;
        $data['pengguna']     = $this->pengguna;
        $data['sekolahAktif'] = $this->sekolahAktif();
        $data['semuaSekolah'] = $this->semuaSekolah();
        $data['semuaGedung']  = $this->semuaGedung();
        $data['konten']       = view($view, $data);
        return view('layout/main', $data);
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
                     SELECT p.gedung_id AS gid, SUM(rl.volume * p.harga_satuan) AS nilai
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
    protected function faktorSatuan(?string $satuanKomponen, array $bahan): float
    {
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

    protected function kebutuhanBahan(int $gedungId): array
    {
        $db = $this->db();
        $mentah = $db->query(
            "SELECT b.id AS bahan_id, b.kode, b.nama, b.satuan, ak.satuan AS satuan_komponen,
                    SUM(p.volume * ak.koefisien) AS qty_mentah
             FROM pekerjaan p
             JOIN analisa a ON a.kode = p.kode_analisa AND a.sekolah_id = ?
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
            "SELECT ak.nama, SUM(p.volume * ak.koefisien) AS qty_butuh, COUNT(DISTINCT p.id) AS dipakai_pekerjaan
             FROM pekerjaan p
             JOIN analisa a ON a.kode = p.kode_analisa AND a.sekolah_id = ?
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
            "SELECT ak.nama AS jenis, SUM(p.volume * ak.koefisien) AS orang_hari,
                    COALESCE(MAX(um.tarif), MAX(ak.tarif), 0) AS tarif
             FROM pekerjaan p
             JOIN analisa a ON a.kode = p.kode_analisa AND a.sekolah_id = ?
             JOIN analisa_komponen ak ON ak.analisa_id = a.id AND ak.jenis = 'upah'
             LEFT JOIN upah_master um ON um.sekolah_id = a.sekolah_id AND um.jenis = ak.nama AND um.is_deleted = 0
             WHERE p.gedung_id = ? AND p.is_deleted = 0 AND a.is_deleted = 0 AND ak.is_deleted = 0
             GROUP BY ak.nama ORDER BY orang_hari DESC",
            [$this->sid(), $gedungId]
        )->getResultArray();
    }

    /** Kebutuhan bahan gabungan seluruh gedung sekolah (per bahan master). */
    protected function kebutuhanBahanSekolah(): array
    {
        $gabung = [];
        $takTertaut = [];
        foreach ($this->semuaGedung() as $g) {
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
        foreach ($this->semuaGedung() as $g) {
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
    protected function totalBoqAktif(?int $sid = null): float
    {
        $sid = $sid ?? $this->sid();
        $row = $this->db()->query(
            'SELECT COALESCE(SUM(p.volume * p.harga_satuan), 0) AS t
             FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id
             WHERE g.sekolah_id = ? AND p.is_deleted = 0 AND g.is_deleted = 0',
            [$sid]
        )->getRowArray();
        return (float) ($row['t'] ?? 0);
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
        ];
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
