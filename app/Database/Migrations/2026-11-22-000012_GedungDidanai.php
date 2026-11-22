<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penanda gedung yang didanai paket: hanya gedung didanai yang dihitung
 * pada verifikasi anggaran dan rekap kebutuhan tingkat sekolah. Gedung
 * lain tetap tersimpan dan bisa dibuka, tetapi tidak menggelembungkan
 * total BOQ terhadap pagu. Bawaan 1 (didanai) agar perilaku lama tidak
 * berubah sebelum pengguna menandai.
 */
class GedungDidanai extends Migration
{
    public function up()
    {
        $this->forge->addColumn('gedung', [
            'didanai' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('gedung', 'didanai');
    }
}
