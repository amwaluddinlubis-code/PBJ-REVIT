<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Basis pengujian database mesin uang PBJ.
 *
 * Memakai grup koneksi 'tests' (MySQL/MariaDB, database pbj_test) karena
 * migrasi dan query aplikasi memakai sintaks MySQL. Semua data yang dibuat
 * di sini adalah data SINTETIS khusus pengujian — jangan pernah menunjuk
 * grup 'tests' ke database produksi.
 *
 * Setiap helper membuat baris baru dan mengembalikan id-nya; pengujian
 * selalu menjangkau data lewat id/sekolah yang dibuatnya sendiri agar
 * tidak saling bergantung antar pengujian.
 */
abstract class PbjDatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $refresh   = true;

    /**
     * Atur ulang database tes dengan MENGHAPUS semua tabel lalu migrasi
     * naik dari kosong — BUKAN regress (down()) bawaan DatabaseTestTrait.
     *
     * Alasan: regress + migrasi naik dalam satu proses PHP menabrak
     * cache nama kolom koneksi CodeIgniter (BaseConnection::getFieldNames
     * menyimpan daftar kolom per tabel). Sesudah down() migrasi 000024
     * membuang kolom bast.bap_id, pemeriksaan fieldExists() pada up()
     * di proses yang sama masih membaca cache lama, kolom dianggap ada,
     * addColumn dilewati, dan query backfill-nya gagal dengan
     * "Unknown column 'bs.bap_id' in 'SET'". Menghapus tabel secara
     * fisik menghindari seluruh rantai down() dan membuat setiap
     * rangkaian pengujian mulai dari keadaan yang sama persis.
     */
    private static bool $sudahDiaturUlang = false;

    protected function setUpMigrate()
    {
        if ($this->refresh === true && ! self::$sudahDiaturUlang) {
            self::$sudahDiaturUlang = true;
            $namaDb = $this->db->getDatabase();
            $tabel  = $this->db->query(
                'SELECT TABLE_NAME AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?',
                [$namaDb]
            )->getResultArray();
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tabel as $t) {
                if ($t['n'] === 'migrations') {
                    continue; // tabel pencatat dipertahankan, cukup dikosongkan
                }
                $this->db->query('DROP TABLE IF EXISTS `' . $t['n'] . '`');
            }
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            if ($this->db->tableExists('migrations')) {
                $this->db->query('DELETE FROM migrations');
            } else {
                $this->migrations->ensureTable();
            }
            $this->migrateDatabase();
        }
    }

    protected function buatSekolah(float $totalAnggaran = 0): int
    {
        $this->db->table('sekolah')->insert([
            'nama_sekolah'   => 'Sekolah Uji ' . bin2hex(random_bytes(4)),
            'total_anggaran' => $totalAnggaran,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function buatGedung(int $sekolahId, string $nama = 'Gedung Uji', int $didanai = 1): int
    {
        $this->db->table('gedung')->insert([
            'sekolah_id' => $sekolahId,
            'nama'       => $nama,
            'didanai'    => $didanai,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function buatBahan(int $sekolahId, string $kode, string $nama, ?string $satuan = 'bh', int $hargaRab = 0): int
    {
        $this->db->table('bahan')->insert([
            'sekolah_id' => $sekolahId,
            'kode'       => $kode,
            'nama'       => $nama,
            'satuan'     => $satuan,
            'harga_rab'  => $hargaRab,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function buatUpahMaster(int $sekolahId, string $jenis, float $tarif): int
    {
        $this->db->table('upah_master')->insert([
            'sekolah_id' => $sekolahId,
            'jenis'      => $jenis,
            'satuan'     => 'OH',
            'tarif'      => $tarif,
        ]);
        return (int) $this->db->insertID();
    }

    /**
     * Buat analisa beserta komponennya.
     * $komponen: daftar [jenis, nama, satuan, koefisien, tarif, bahan_id, upah_id].
     */
    protected function buatAnalisa(int $sekolahId, string $kode, array $komponen = []): int
    {
        $this->db->table('analisa')->insert([
            'sekolah_id' => $sekolahId,
            'kode'       => $kode,
            'nama'       => 'Analisa Uji ' . $kode,
            'satuan'     => 'm2',
        ]);
        $analisaId = (int) $this->db->insertID();
        foreach ($komponen as $k) {
            $this->db->table('analisa_komponen')->insert([
                'analisa_id' => $analisaId,
                'jenis'      => $k[0],
                'nama'       => $k[1],
                'satuan'     => $k[2] ?? null,
                'koefisien'  => $k[3],
                'tarif'      => $k[4] ?? null,
                'bahan_id'   => $k[5] ?? null,
                'upah_id'    => $k[6] ?? null,
            ]);
        }
        return $analisaId;
    }

    protected function buatPekerjaan(int $gedungId, array $isi = []): int
    {
        $this->db->table('pekerjaan')->insert(array_merge([
            'gedung_id'    => $gedungId,
            'nama'         => 'Pekerjaan Uji',
            'kode_analisa' => null,
            'analisa_id'   => null,
            'volume'       => 0,
            'satuan'       => 'm2',
            'harga_satuan' => 0,
            'jumlah'       => 0,
        ], $isi));
        return (int) $this->db->insertID();
    }

    protected function buatSpk(int $sekolahId, int $nomor, ?int $gedungId = null): int
    {
        $this->db->table('spk')->insert([
            'sekolah_id' => $sekolahId,
            'nomor'      => $nomor,
            'tanggal'    => date('Y-m-d'),
            'gedung_id'  => $gedungId,
            'paket'      => 'Paket Uji',
            'status'     => 'Aktif',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function buatSpkDetail(int $spkId, ?int $bahanId, float $qty, int $harga = 0): int
    {
        $this->db->table('spk_detail')->insert([
            'spk_id'     => $spkId,
            'bahan_id'   => $bahanId,
            'qty'        => $qty,
            'harga'      => $harga,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function buatInvoice(int $spkId, int $nomorUrut, int $nilai = 0): int
    {
        $this->db->table('invoice')->insert([
            'nomor_urut' => $nomorUrut,
            'tanggal'    => date('Y-m-d'),
            'spk_id'     => $spkId,
            'nilai'      => $nilai,
            'status'     => 'Belum Bayar',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function buatPembayaran(int $invoiceId): int
    {
        $this->db->table('pembayaran')->insert([
            'invoice_id' => $invoiceId,
            'tanggal'    => date('Y-m-d'),
            'keterangan' => 'Pembayaran uji',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function buatBiayaLain(int $sekolahId, float $jumlah): int
    {
        $this->db->table('biaya_lain')->insert([
            'sekolah_id' => $sekolahId,
            'kelompok'   => 'Uji',
            'uraian'     => 'Biaya lain uji',
            'jumlah'     => $jumlah,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    protected function hapusLunakBaris(string $tabel, int $id): void
    {
        $this->db->table($tabel)->where('id', $id)
            ->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
    }
}
