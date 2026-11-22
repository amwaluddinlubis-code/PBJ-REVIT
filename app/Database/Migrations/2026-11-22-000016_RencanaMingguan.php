<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rencana Mingguan: target volume pekerjaan per gedung untuk satu minggu,
 * menjadi dasar hitungan daftar belanja bersih minggu itu
 * (kebutuhan dari target − stok tersedia − pesanan yang sedang berjalan).
 */
class RencanaMingguan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id'  => ['type' => 'INT', 'unsigned' => true],
            'gedung_id'   => ['type' => 'INT', 'unsigned' => true],
            'minggu_mulai' => ['type' => 'DATE'],
            'keterangan'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_deleted'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sekolah_id', 'gedung_id']);
        $this->forge->createTable('rencana_mingguan');

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rencana_id'    => ['type' => 'INT', 'unsigned' => true],
            'pekerjaan_id'  => ['type' => 'INT', 'unsigned' => true],
            'volume_target' => ['type' => 'DECIMAL', 'constraint' => '16,4', 'default' => 0],
            'is_deleted'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['rencana_id', 'pekerjaan_id']);
        $this->forge->createTable('rencana_mingguan_detail');
    }

    public function down()
    {
        $this->forge->dropTable('rencana_mingguan_detail', true);
        $this->forge->dropTable('rencana_mingguan', true);
    }
}
