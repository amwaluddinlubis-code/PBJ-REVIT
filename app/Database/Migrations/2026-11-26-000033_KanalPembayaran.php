<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kanal pembayaran jurnal: 'tunai' (bawaan) atau 'bank'. Hanya
 * pembayaran non-tunai yang masuk Buku Bank; pembayaran tunai masuk
 * Buku Pembantu Kas Tunai (instruksi Wilda 26 Nov 2026).
 */
class KanalPembayaran extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pembayaran', [
            'kanal' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'tunai', 'after' => 'tanggal'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pembayaran', 'kanal');
    }
}
