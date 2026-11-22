<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Konversi satuan manual per bahan: faktor pengali dari satuan komponen
 * AHSP ke satuan master bahan, mengalahkan aturan konversi bawaan
 * (faktorSatuan) untuk pasangan (bahan, satuan komponen) tersebut.
 * Dipakai memperbaiki bahan yang satuannya berbeda antara dokumen
 * analisa dan master bahan.
 */
class BahanKonversi extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'sekolah_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'bahan_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'satuan_komponen' => ['type' => 'VARCHAR', 'constraint' => 40],
            'faktor' => ['type' => 'DECIMAL', 'constraint' => '16,8'],
            'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sekolah_id', 'bahan_id', 'satuan_komponen'], false, false, 'uq_konversi');
        $this->forge->createTable('bahan_konversi', true);
    }

    public function down()
    {
        $this->forge->dropTable('bahan_konversi', true);
    }
}
