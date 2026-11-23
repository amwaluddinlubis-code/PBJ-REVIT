<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Nama penerima manual pada SPK: untuk pembelian dari masyarakat (pasir,
 * batu, kayu, dsb.) penyedianya cukup satu record khusus "Masyarakat",
 * sedangkan nama orang yang menerima pembayaran diisi manual per SPK dan
 * diwarisi semua dokumen turunannya (detail, cetak SPK, BAP, BAST, invoice).
 */
class SpkPenerimaManual extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('spk')) {
            return;
        }
        if (! $this->db->fieldExists('penerima_manual', 'spk')) {
            $this->forge->addColumn('spk', [
                'penerima_manual' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'penyedia_id'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('spk') && $this->db->fieldExists('penerima_manual', 'spk')) {
            $this->forge->dropColumn('spk', 'penerima_manual');
        }
    }
}
