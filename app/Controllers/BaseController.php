<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url'];
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
