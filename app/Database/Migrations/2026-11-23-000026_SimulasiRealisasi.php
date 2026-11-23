<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Realisasi mingguan untuk Simulasi Mingguan: bobot terealisasi (persen
 * terhadap total proyek Kurva S) per minggu kalender per gedung, diisi
 * panitia. Mesin simulasi menyebar nilai ini ke bab mengikuti pola Kurva S
 * lalu menghitung ulang sisa kebutuhan bahan & tenaga minggu-minggu
 * berikutnya. Terpisah dari catatan realisasi per pekerjaan.
 */
class SimulasiRealisasi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id' => ['type' => 'INT', 'unsigned' => true],
            'minggu_ke'  => ['type' => 'SMALLINT', 'unsigned' => true],
            'gedung_id'  => ['type' => 'INT', 'unsigned' => true],
            'bobot'      => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0],
            'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['sekolah_id', 'minggu_ke', 'gedung_id']);
        $this->forge->createTable('simulasi_realisasi');
    }

    public function down()
    {
        $this->forge->dropTable('simulasi_realisasi', true);
    }
}
