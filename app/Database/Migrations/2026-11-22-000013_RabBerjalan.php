<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * RAB Berjalan: revisi pekerjaan yang disahkan mengubah volume/harga
 * efektif (berjalan) tanpa mengubah RAB awal. Kolom volume_revisi /
 * harga_revisi pada pekerjaan adalah nilai berjalan (NULL = ikut awal).
 * Revisi mendapat jenis terstruktur (volume/baru/ganti/hapus/catatan),
 * status (draft/disahkan), dan tautan ke pekerjaan pengganti yang
 * dibuat saat pengesahan.
 */
class RabBerjalan extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pekerjaan', [
            'volume_revisi' => ['type' => 'DECIMAL', 'constraint' => '16,4', 'null' => true, 'after' => 'volume'],
            'harga_revisi'  => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true, 'after' => 'harga_satuan'],
            'dari_revisi_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'urutan'],
        ]);
        $this->forge->addColumn('revisi_pekerjaan', [
            'status' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'draft', 'after' => 'perubahan'],
            'jenis' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'catatan', 'after' => 'status'],
            'volume_baru' => ['type' => 'DECIMAL', 'constraint' => '16,4', 'null' => true, 'after' => 'jenis'],
            'harga_baru' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true, 'after' => 'volume_baru'],
            'nama_baru' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'harga_baru'],
            'satuan_baru' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'nama_baru'],
            'kode_analisa_baru' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'satuan_baru'],
            'pekerjaan_baru_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'kode_analisa_baru'],
            'disahkan_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'pekerjaan_baru_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pekerjaan', ['volume_revisi', 'harga_revisi', 'dari_revisi_id']);
        $this->forge->dropColumn('revisi_pekerjaan', ['status', 'jenis', 'volume_baru', 'harga_baru', 'nama_baru', 'satuan_baru', 'kode_analisa_baru', 'pekerjaan_baru_id', 'disahkan_at']);
    }
}
