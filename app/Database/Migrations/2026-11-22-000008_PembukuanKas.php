<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pembukuan menurut Juknis Revitalisasi SMP 2026:
 * - kas_transaksi: transaksi kas manual (penerimaan dana bantuan per tahap,
 *   koreksi, dan transaksi lain di luar pembayaran invoice/upah yang sudah
 *   tercatat otomatis) sebagai bahan Buku Kas Umum, Buku Bank, dan Buku
 *   Pembantu Kas Tunai.
 * - bukti_setor: register bukti penyetoran ke kas negara (pajak yang
 *   dipungut/dipotong bendahara dengan e-billing/NTPN dan pengembalian
 *   sisa dana bantuan lewat SIMPONI).
 */
class PembukuanKas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE'],
            'uraian'     => ['type' => 'VARCHAR', 'constraint' => 190],
            'kategori'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'lainnya'],
            'tahap'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'kanal'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'bank'],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'masuk'],
            'jumlah'     => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sekolah_id');
        $this->forge->createTable('kas_transaksi');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id'   => ['type' => 'INT', 'unsigned' => true],
            'jenis'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'masa'         => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],
            'tanggal_setor' => ['type' => 'DATE'],
            'jumlah'       => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'kode_billing' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'ntpn'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'keterangan'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_deleted'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sekolah_id');
        $this->forge->createTable('bukti_setor');
    }

    public function down()
    {
        $this->forge->dropTable('bukti_setor');
        $this->forge->dropTable('kas_transaksi');
    }
}
