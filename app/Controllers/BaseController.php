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
    protected function tampil(string $view, array $data = [], string $judul = 'Aplikasi PBJ Sekolah')
    {
        $data['judul']        = $judul;
        $data['pengguna']     = $this->pengguna;
        $data['sekolahAktif'] = $this->sekolahAktif();
        $data['semuaSekolah'] = $this->semuaSekolah();
        $data['konten']       = view($view, $data);
        return view('layout/main', $data);
    }

    /** ID sekolah yang sedang aktif (dari sesi; bawaan: sekolah pertama). */
    protected function sid(): int
    {
        $db = $this->db();
        $id = (int) (session()->get('sekolah_id') ?? 0);
        if ($id > 0 && $db->table('sekolah')->where('id', $id)->countAllResults() > 0) {
            return $id;
        }
        $row = $db->table('sekolah')->orderBy('id')->get()->getRowArray();
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
        return $id > 0 ? ($this->db()->table('sekolah')->where('id', $id)->get()->getRowArray() ?? []) : [];
    }

    /** Semua sekolah terdaftar (untuk pengalih di layout). */
    protected function semuaSekolah(): array
    {
        return $this->db()->table('sekolah')->orderBy('nama_sekolah')->get()->getResultArray();
    }

    /** Harga acuan sebuah bahan: negosiasi -> penawaran -> pembanding terpilih -> RAB. */
    protected function hargaAcuan(int $bahanId): int
    {
        $db = $this->db();
        $nego = $db->table('negosiasi')->where('bahan_id', $bahanId)->get()->getRowArray();
        if ($nego) {
            if (! empty($nego['harga_nego'])) {
                return (int) $nego['harga_nego'];
            }
            if (! empty($nego['harga_penawaran'])) {
                return (int) $nego['harga_penawaran'];
            }
        }
        $bahan = $db->table('bahan')->where('id', $bahanId)->get()->getRowArray();
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
