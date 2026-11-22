<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AplikasiPengaturan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kunci' => ['type' => 'VARCHAR', 'constraint' => 80],
            'nilai' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kunci');
        $this->forge->createTable('aplikasi_pengaturan', true);
        $this->db->table('aplikasi_pengaturan')->insert([
            'kunci' => 'login_version', 'nilai' => 'v1',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('aplikasi_pengaturan', true);
    }
}
