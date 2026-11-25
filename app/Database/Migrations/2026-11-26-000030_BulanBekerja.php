<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bulan Bekerja (keputusan AMWAL 26 Nov 2026): kolom bulan_bekerja pada
 * sekolah — bulan (1–12) dalam tahun_anggaran yang sedang TERBUKA untuk
 * pencatatan uang. Semua pencatatan arus kas (pembayaran invoice,
 * pembayaran upah, transaksi kas manual, bukti setor) hanya diterima bila
 * tanggalnya jatuh di bulan bekerja; diatur dari halaman Pengaturan,
 * bersebelahan dengan Tahun Anggaran. NULL = belum diatur (belum
 * mengunci). Penegakan di BaseController::galatBulanBekerja().
 */
class BulanBekerja extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('bulan_bekerja', 'sekolah')) {
            $this->forge->addColumn('sekolah', [
                'bulan_bekerja' => ['type' => 'TINYINT', 'null' => true, 'after' => 'tahun_anggaran'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('bulan_bekerja', 'sekolah')) {
            $this->forge->dropColumn('sekolah', 'bulan_bekerja');
        }
    }
}
