<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom soft delete (is_deleted + deleted_at) untuk semua tabel bisnis.
 *
 * is_deleted : penanda cepat untuk filter (0 = aktif, 1 = terhapus lunak)
 * deleted_at : kapan baris dihapus lunak (NULL selama aktif)
 *
 * Dikecualikan: impor_log (jejak audit — tidak boleh bisa ditandai hapus)
 * dan tabel sistem migrations.
 *
 * Tahap ini baru skema: aksi Hapus di controller masih menghapus fisik
 * sampai tahap pengabelan (wiring) dikerjakan.
 */
class KolomSoftDelete extends Migration
{
    /** @var string[] */
    private array $tabel = [
        'users', 'sekolah', 'bahan', 'penyedia', 'gedung', 'pekerjaan',
        'analisa', 'analisa_komponen', 'upah_master', 'spk', 'spk_detail',
        'survei_harga', 'negosiasi', 'bap', 'bap_detail', 'bast', 'invoice',
        'pembayaran', 'buku_material', 'realisasi', 'upah_bayar',
    ];

    public function up()
    {
        foreach ($this->tabel as $t) {
            $this->forge->addColumn($t, [
                'is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            ]);
            $this->db->query("ALTER TABLE `{$t}` ADD INDEX `idx_is_deleted` (`is_deleted`)");
        }
    }

    public function down()
    {
        foreach ($this->tabel as $t) {
            $this->db->query("ALTER TABLE `{$t}` DROP INDEX `idx_is_deleted`");
            $this->forge->dropColumn($t, ['is_deleted', 'deleted_at']);
        }
    }
}
