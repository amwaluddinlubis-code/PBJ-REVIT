<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Akses menu per peran: satu baris per (peran, menu) menyimpan boleh/tidak.
 * Peran bersifat global (akun tidak terikat sekolah), jadi tabel ini tanpa
 * sekolah_id. Ketiadaan baris berarti bawaan: menu boleh diakses. Admin
 * selalu boleh semua dan tidak disimpan di sini.
 */
class AksesMenu extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'peran'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'menu'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'boleh'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['peran', 'menu']);
        $this->forge->createTable('akses_menu');
    }

    public function down()
    {
        $this->forge->dropTable('akses_menu');
    }
}
