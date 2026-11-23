<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TemplateLaporan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'berkas'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'placeholder_terpakai' => ['type' => 'TEXT', 'null' => true],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('laporan_template');
    }

    public function down()
    {
        $this->forge->dropTable('laporan_template', true);
    }
}
