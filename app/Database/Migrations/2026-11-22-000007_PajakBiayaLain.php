<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom jenis pajak pada biaya non-gedung: potongan saat pembayaran baris
 * biaya (PPh 23 jasa/sewa, PPh 4 ayat 2 perencana/pengawas, PPh 21 honor)
 * menurut Juknis Revitalisasi SMP 2026. Nominal potongan dihitung dari
 * jumlah baris pada saat ditampilkan, tidak disimpan.
 */
class PajakBiayaLain extends Migration
{
    public function up()
    {
        $this->forge->addColumn('biaya_lain', [
            'pajak' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'jumlah'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('biaya_lain', 'pajak');
    }
}
