<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tutup Buku bulanan (keputusan AMWAL 26 Nov 2026): buku kas ditutup per
 * bulan Januari–Desember dalam tahun anggaran, berurutan. Bulan terbuka =
 * bulan terkecil yang belum ditutup; semua pencatatan arus kas hanya
 * diterima bertanggal dalam bulan terbuka (penjaga
 * BaseController::galatBulanTertutup), dan sesudah buku bulan n ditutup
 * pencatatan berikutnya langsung masuk bulan n+1.
 *
 * - tutup_buku: satu baris per bulan yang ditutup; saldo akhir dibekukan
 *   di sini dan menjadi saldo awal bulan berikutnya.
 * - tutup_buku_baris: snapshot baris BKU bulan tertutup bernomor urut
 *   (diisi saat tutup buku; cetakan bulan tertutup membaca snapshot ini
 *   agar selalu identik dengan yang ditandatangani).
 */
class TutupBuku extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tutup_buku')) {
            $this->forge->addField([
                'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'sekolah_id'            => ['type' => 'INT', 'unsigned' => true],
                'tahun'                 => ['type' => 'INT'],
                'bulan'                 => ['type' => 'TINYINT', 'constraint' => 2],
                'saldo_awal'            => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'total_masuk'           => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'total_keluar'          => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'saldo_akhir'           => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'jumlah_baris'          => ['type' => 'INT', 'default' => 0],
                'ditutup_oleh'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'ditutup_pada'          => ['type' => 'DATETIME', 'null' => true],
                'dibuka_kembali_oleh'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'dibuka_kembali_pada'   => ['type' => 'DATETIME', 'null' => true],
                'alasan_buka_kembali'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'is_deleted'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'deleted_at'            => ['type' => 'DATETIME', 'null' => true],
                'created_at'            => ['type' => 'DATETIME', 'null' => true],
                'updated_at'            => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['sekolah_id', 'tahun', 'bulan']);
            $this->forge->createTable('tutup_buku');
        }

        if (! $this->db->tableExists('tutup_buku_baris')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tutup_buku_id' => ['type' => 'INT', 'unsigned' => true],
                'sekolah_id'    => ['type' => 'INT', 'unsigned' => true],
                'nomor'         => ['type' => 'INT', 'default' => 0],
                'tanggal'       => ['type' => 'DATE', 'null' => true],
                'uraian'        => ['type' => 'TEXT', 'null' => true],
                'masuk'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'keluar'        => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'saldo'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'kanal'         => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'bank'],
                'sumber'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'ref_id'        => ['type' => 'INT', 'null' => true],
                'kategori'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'is_deleted'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('tutup_buku_id');
            $this->forge->addKey('sekolah_id');
            $this->forge->createTable('tutup_buku_baris');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tutup_buku_baris')) {
            $this->forge->dropTable('tutup_buku_baris');
        }
        if ($this->db->tableExists('tutup_buku')) {
            $this->forge->dropTable('tutup_buku');
        }
    }
}
