<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Master Acuan AHSP Nasional (SE Dirjen Bina Konstruksi 68/SE/Dk/2024,
 * Lampiran VI — Cipta Karya dan Perumahan; dasar Permen PUPR 8/2023).
 * Referensi nasional bersifat umum (bukan per sekolah): pemetaan ke analisa
 * aplikasi lewat kode_kita = kode analisa perencana. Data diisi perintah
 * `php spark impor:acuan` dari app/Data/ahsp_acuan.json.
 */
class AhspAcuan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode_kita'     => ['type' => 'VARCHAR', 'constraint' => 10],
            'kode_nasional' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            'nama'          => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            'satuan'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            'catatan'       => ['type' => 'TEXT', 'null' => true],
            'tanpa_padanan' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_deleted'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_kita');
        $this->forge->createTable('ahsp_acuan');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'acuan_id'   => ['type' => 'INT', 'unsigned' => true],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'bahan'],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'satuan'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            'koefisien'  => ['type' => 'DECIMAL', 'constraint' => '14,6', 'default' => 0],
            'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('acuan_id');
        $this->forge->addForeignKey('acuan_id', 'ahsp_acuan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ahsp_acuan_komponen');
    }

    public function down()
    {
        $this->forge->dropTable('ahsp_acuan_komponen', true);
        $this->forge->dropTable('ahsp_acuan', true);
    }
}
