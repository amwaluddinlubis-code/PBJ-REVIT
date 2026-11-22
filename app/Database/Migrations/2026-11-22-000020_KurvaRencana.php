<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kurva S rencana resmi dari dokumen Time Schedule perencana teknis:
 * kalender minggu proyek (kurva_minggu) + bobot rencana per kelompok/bab
 * per minggu (kurva_rencana). Bobot dalam persen terhadap total proyek.
 * Baris dengan gedung_id NULL hanya masuk kurva total sekolah
 * (kelompok Persiapan & Biaya Manajemen pada dokumen sumber).
 */
class KurvaRencana extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id'    => ['type' => 'INT', 'unsigned' => true],
            'minggu_ke'     => ['type' => 'SMALLINT', 'unsigned' => true],
            'tanggal_mulai' => ['type' => 'DATE'],
            'tanggal_selesai' => ['type' => 'DATE'],
            'is_deleted'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['sekolah_id', 'minggu_ke']);
        $this->forge->createTable('kurva_minggu');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id'   => ['type' => 'INT', 'unsigned' => true],
            'gedung_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'grup'         => ['type' => 'VARCHAR', 'constraint' => 60],
            'bab'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jumlah'       => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'bobot'        => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0],
            'minggu_ke'    => ['type' => 'SMALLINT', 'unsigned' => true],
            'bobot_minggu' => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0],
            'is_deleted'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sekolah_id', 'gedung_id', 'minggu_ke']);
        $this->forge->createTable('kurva_rencana');
    }

    public function down()
    {
        $this->forge->dropTable('kurva_rencana', true);
        $this->forge->dropTable('kurva_minggu', true);
    }
}
