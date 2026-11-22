<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Katalog menu sidebar yang dapat diatur aksesnya per peran, beserta peta
 * akses efektifnya. Kunci menu adalah segmen pertama URL halamannya, sama
 * seperti penanda aktif pada sidebar.
 *
 * Menu yang TIDAK masuk katalog dan tidak pernah dibatasi di sini:
 * Dashboard, Profil (termasuk ganti sandi wajib), ganti sekolah, keluar,
 * serta menu khusus admin yang sudah dijaga penjagaPeran (Manajemen User,
 * Pemeliharaan Database, Jejak Audit).
 */
class AksesMenu
{
    /** Peran yang dikenal aplikasi (selaras Pengguna::PERAN). */
    public const PERAN = ['admin', 'ketua', 'bendahara', 'pelaksana', 'kepsek', 'operator'];

    /** Katalog menu yang dapat diatur: nama grup => [kunci segmen => label]. */
    public static function katalog(): array
    {
        return [
            'Perencanaan' => [
                'impor'           => 'Impor Dokumen Perencana',
                'analisa'         => 'Analisa Pekerjaan',
                'acuan'           => 'Acuan AHSP Nasional',
                'bersih'          => 'Pembersihan Hasil Impor',
                'rencana-belanja' => 'Rencana Belanja',
                'rencana-mingguan' => 'Rencana Mingguan',
                'biaya-non-gedung' => 'Biaya Non-Gedung',
            ],
            'Pembangunan Gedung' => [
                'gedung'           => 'Kelola Gedung & BOQ',
                'foto-progres'     => 'Foto Progres',
                'kurva-s'          => 'Kurva S',
                'laporan-kemajuan' => 'Laporan Kemajuan',
                'revisi-pekerjaan' => 'BA Revisi Pekerjaan',
            ],
            'Belanja' => [
                'spk'            => 'Register SPK',
                'belum-bernomor' => 'Belum Bernomor SPK',
                'survei'         => 'Survei Harga',
                'negosiasi'      => 'Negosiasi',
                'dokumen'        => 'BAP & BAST / Cetak',
            ],
            'Material' => [
                'bahan'      => 'Master Bahan & Upah',
                'material'   => 'Buku Material',
                'sisa-bahan' => 'Sisa Bahan',
            ],
            'Keuangan' => [
                'penerimaan-dana' => 'Penerimaan Dana',
                'invoice'         => 'Invoice & Jurnal Pembayaran',
                'buku-kas'        => 'Buku Kas (BKU)',
                'bukti-setor'     => 'Bukti Setor & Pajak',
                'upah'            => 'Pembayaran Upah',
            ],
            'Laporan' => [
                'lpj'     => 'LPJ & Realisasi (termasuk RAB Gabungan)',
                'laporan' => 'Laporan & Ekspor',
            ],
            'Pengaturan' => [
                'pengaturan' => 'Identitas & Pengaturan',
                'sekolah'    => 'Kelola Sekolah',
            ],
        ];
    }

    /** Semua kunci menu pada katalog, urut seperti katalog. */
    public static function semuaKunci(): array
    {
        $kunci = [];
        foreach (self::katalog() as $grup) {
            foreach ($grup as $k => $label) {
                $kunci[] = $k;
            }
        }
        return $kunci;
    }

    /**
     * Peta akses efektif satu peran: bawaan semua menu boleh, ditimpa baris
     * pada tabel akses_menu. Admin selalu boleh semua. Bila tabel belum ada
     * (migrasi belum dijalankan) kembalikan bawaan agar tidak ada yang
     * terkunci hanya karena urutan pembaruan.
     */
    public static function petaUntukPeran(BaseConnection $db, string $peran): array
    {
        $peta = array_fill_keys(self::semuaKunci(), true);
        if ($peran === '' || $peran === 'admin') {
            return $peta;
        }
        try {
            $baris = $db->table('akses_menu')->where('peran', $peran)->get()->getResultArray();
        } catch (\Throwable $e) {
            return $peta;
        }
        foreach ($baris as $r) {
            if (array_key_exists($r['menu'], $peta)) {
                $peta[$r['menu']] = (int) $r['boleh'] === 1;
            }
        }
        return $peta;
    }
}
