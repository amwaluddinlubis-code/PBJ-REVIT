<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Berita Acara Revisi Pekerjaan menurut Juknis: setiap perubahan
 * pekerjaan (penambahan/pengurangan volume atau biaya) dibuatkan
 * berita acara revisi yang ditandatangani Kepala Sekolah, Ketua P2SP,
 * dan Tim Teknis, dan dikonsultasikan kepada PPK melalui fasilitator.
 */
class RevisiPekerjaan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id'    => ['type' => 'INT', 'unsigned' => true],
            'gedung_id'     => ['type' => 'INT', 'unsigned' => true],
            'pekerjaan_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tanggal'       => ['type' => 'DATE'],
            'uraian'        => ['type' => 'TEXT'],
            'perubahan'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_deleted'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sekolah_id');
        $this->forge->createTable('revisi_pekerjaan');
    }

    public function down()
    {
        $this->forge->dropTable('revisi_pekerjaan');
    }
}
