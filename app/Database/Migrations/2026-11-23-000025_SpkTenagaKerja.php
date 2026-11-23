<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Catatan kebutuhan tenaga kerja per SPK: jenis tenaga (Pekerja/Tukang),
 * keahlian (mis. Batu, Kayu, Besi, Cat), kebutuhan OH (orang-hari), dan
 * perkiraan jumlah orang — agar panitia dapat memperkirakan tenaga kerja
 * yang harus disediakan untuk paket SPK itu. Terisi otomatis dari Rencana
 * Belanja (hitungan Langkah 4) dan dapat ditambah/diubah manual di
 * Detail SPK.
 */
class SpkTenagaKerja extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('spk_tenaga')) {
            return;
        }
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id'   => ['type' => 'INT', 'unsigned' => true],
            'spk_id'       => ['type' => 'INT', 'unsigned' => true],
            'jenis'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'keahlian'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'oh'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'jumlah_orang' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'keterangan'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_deleted'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sekolah_id', 'spk_id']);
        $this->forge->createTable('spk_tenaga');
    }

    public function down()
    {
        $this->forge->dropTable('spk_tenaga', true);
    }
}
