<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dokumentasi foto progres pekerjaan menurut Juknis Revitalisasi SMP
 * 2026: foto kondisi sebelum pekerjaan, selama tahap-tahap pekerjaan
 * (mingguan), dan di akhir pekerjaan — menjadi bagian laporan
 * pertanggungjawaban (laporan kemajuan & laporan akhir).
 */
class FotoProgres extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id' => ['type' => 'INT', 'unsigned' => true],
            'gedung_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tahap'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'proses'],
            'tanggal'    => ['type' => 'DATE'],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'berkas'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sekolah_id');
        $this->forge->createTable('foto_progres');
    }

    public function down()
    {
        $this->forge->dropTable('foto_progres');
    }
}
