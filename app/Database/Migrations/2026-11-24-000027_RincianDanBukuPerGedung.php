<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Atribusi per gedung untuk SPK gabungan & Buku Material:
 * - spk_detail.gedung_id: gedung tujuan tiap rincian (satu SPK dapat
 *   melayani beberapa gedung — rincian dipisah per gedung agar kebutuhan
 *   & stok tiap gedung tetap terbaca). Baris lama diisi dari gedung SPK.
 * - buku_material.gedung_id: gedung tujuan catatan keluar/masuk material
 *   (NULL = gudang bersama / belum ditentukan).
 */
class RincianDanBukuPerGedung extends Migration
{
    public function up()
    {
        $this->forge->addColumn('spk_detail', [
            'gedung_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'bahan_id'],
        ]);
        $this->forge->addColumn('buku_material', [
            'gedung_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'bahan_id'],
        ]);
        $this->db->query(
            'UPDATE spk_detail d JOIN spk s ON s.id = d.spk_id
             SET d.gedung_id = s.gedung_id WHERE d.gedung_id IS NULL AND s.gedung_id IS NOT NULL'
        );
    }

    public function down()
    {
        $this->forge->dropColumn('spk_detail', 'gedung_id');
        $this->forge->dropColumn('buku_material', 'gedung_id');
    }
}
