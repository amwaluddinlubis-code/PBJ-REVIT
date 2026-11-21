<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Harga satuan pekerjaan resmi per kode analisa, dari sheet Harsat
 * Pekerjaan dokumen perencana (atau sheet Harsat template). Menjadi acuan
 * harga pekerjaan setelah harga BOQ dan sebelum hitungan dari komponen.
 */
class HargaSatuanAnalisa extends Migration
{
    public function up()
    {
        $this->forge->addColumn('analisa', [
            'harga_satuan' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true, 'after' => 'satuan'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('analisa', 'harga_satuan');
    }
}
