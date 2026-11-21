<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Biaya non-gedung tingkat sekolah: perencanaan & pengawasan (sheet
 * "Prcn dan pgws") dan biaya pengelolaan P2SP (sheet "ADMINISTRASI")
 * dari dokumen perencana, ditambah entri manual pengguna. Ikut dihitung
 * dalam verifikasi anggaran (total BOQ gedung aktif + biaya non-gedung
 * dibandingkan pagu), BUKAN belanja terbayar pada kontrol pagu.
 */
class BiayaLain extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id'     => ['type' => 'INT', 'unsigned' => true],
            'kelompok'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'uraian'         => ['type' => 'VARCHAR', 'constraint' => 190],
            'volume'         => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
            'satuan'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'periode'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'satuan_periode' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'harga_satuan'   => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'jumlah'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'keterangan'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sumber'         => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'manual'],
            'is_deleted'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sekolah_id');
        $this->forge->createTable('biaya_lain');
    }

    public function down()
    {
        $this->forge->dropTable('biaya_lain');
    }
}
