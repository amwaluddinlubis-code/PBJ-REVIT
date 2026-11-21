<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ceklis kelengkapan dokumen per SPK: penanda manual untuk dokumen
 * fisik yang tidak tercatat otomatis di aplikasi (dokumen perencanaan
 * pengadaan dan kuitansi/faktur fisik dari penyedia), melengkapi
 * status otomatis dari modul survei, negosiasi, BAP, BAST, invoice,
 * dan pembayaran.
 */
class DokumenSpk extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id' => ['type' => 'INT', 'unsigned' => true],
            'spk_id'     => ['type' => 'INT', 'unsigned' => true],
            'item'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'ada'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['spk_id', 'item']);
        $this->forge->createTable('dokumen_spk');
    }

    public function down()
    {
        $this->forge->dropTable('dokumen_spk');
    }
}
