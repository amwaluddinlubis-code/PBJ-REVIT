<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jejak audit untuk aksi penting (uang & persetujuan) + penguatan akun:
 * users.aktif (akun dapat dinonaktifkan) dan users.harus_ganti_sandi
 * (akun baru/bawaan wajib mengganti sandi pada masuk pertama).
 */
class AuditDanAkun extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'entitas'    => ['type' => 'VARCHAR', 'constraint' => 60],
            'entitas_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'aksi'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'ip'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sekolah_id', 'entitas']);
        $this->forge->createTable('audit_events');

        $this->forge->addColumn('users', [
            'aktif'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'peran'],
            'harus_ganti_sandi' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'aktif'],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('audit_events', true);
        $this->forge->dropColumn('users', ['aktif', 'harus_ganti_sandi']);
    }
}
