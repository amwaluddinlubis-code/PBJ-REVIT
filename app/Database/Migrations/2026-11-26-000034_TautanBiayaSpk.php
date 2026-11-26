<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tautan rincian SPK ke baris Biaya Non-Gedung sumbernya
 * (spk_detail.biaya_lain_id): SPK dapat di-generate dari baris
 * biaya_lain terpilih di halaman Biaya Non-Gedung; tautan ini
 * menjadi penjaga anti-dobel (satu baris biaya hanya boleh masuk
 * satu SPK aktif) dan dasar lencana "SPK {nomor}" di halaman itu.
 * NULL untuk rincian biasa (bahan dari rencana/register).
 */
class TautanBiayaSpk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('spk_detail', [
            'biaya_lain_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'gedung_id'],
        ]);
        $this->forge->addKey('biaya_lain_id');
        $this->forge->processIndexes('spk_detail');
    }

    public function down()
    {
        $this->forge->dropKey('spk_detail', 'biaya_lain_id');
        $this->forge->dropColumn('spk_detail', 'biaya_lain_id');
    }
}
