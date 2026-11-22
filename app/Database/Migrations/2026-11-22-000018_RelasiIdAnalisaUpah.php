<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Desain ulang relasi berbasis teks (audit relasi 22 Nov 2026):
 * - pekerjaan.analisa_id: relasi pekerjaan -> analisa lewat id (selama ini
 *   lewat teks kode_analisa). Di-backfill dari kode + sekolah gedung;
 *   kode_analisa tetap disimpan untuk tampilan/impor dan jadi fallback.
 * - analisa_komponen.upah_id: relasi komponen upah -> upah_master lewat id
 *   (selama ini dicocokkan lewat nama teks). Di-backfill dari kecocokan
 *   nama persis (huruf besar/kecil & spasi tepi diabaikan) dalam sekolah
 *   yang sama; komponen yang namanya tidak cocok tetap NULL dan dibaca
 *   lewat fallback nama seperti sebelumnya.
 */
class RelasiIdAnalisaUpah extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pekerjaan', [
            'analisa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'kode_analisa'],
        ]);
        $this->forge->addColumn('analisa_komponen', [
            'upah_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'bahan_id'],
        ]);
        $db = $this->db;
        $db->query('CREATE INDEX idx_pekerjaan_analisa ON pekerjaan (analisa_id)');
        $db->query('CREATE INDEX idx_komponen_upah ON analisa_komponen (upah_id)');
        // Backfill pekerjaan: kode unik per (sekolah, kode) menjamin 1:1.
        $db->query(
            'UPDATE pekerjaan p
             JOIN gedung g ON g.id = p.gedung_id
             JOIN analisa a ON a.kode = p.kode_analisa AND a.sekolah_id = g.sekolah_id
             SET p.analisa_id = a.id
             WHERE p.analisa_id IS NULL AND p.kode_analisa IS NOT NULL AND p.kode_analisa <> \'\''
        );
        // Backfill komponen upah: kecocokan nama persis dalam sekolah sama.
        $db->query(
            'UPDATE analisa_komponen ak
             JOIN analisa a ON a.id = ak.analisa_id
             JOIN upah_master um ON um.sekolah_id = a.sekolah_id
               AND LOWER(TRIM(um.jenis)) = LOWER(TRIM(ak.nama)) AND um.is_deleted = 0
             SET ak.upah_id = um.id
             WHERE ak.jenis = \'upah\' AND ak.upah_id IS NULL AND ak.is_deleted = 0'
        );
    }

    public function down()
    {
        $this->forge->dropColumn('pekerjaan', 'analisa_id');
        $this->forge->dropColumn('analisa_komponen', 'upah_id');
    }
}
