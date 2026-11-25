<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Acuan AHSP menjadi PER SEKOLAH (keputusan AMWAL 25 Nov 2026): setiap
 * sekolah memiliki salinan acuannya sendiri di ahsp_acuan, agar penyesuaian
 * acuan untuk satu sekolah tidak mengubah sekolah lain. Baris lama menjadi
 * TEMPLAT nasional (sekolah_id NULL) — sumber kloning untuk sekolah baru
 * dan tombol "Siapkan Acuan dari Nasional"; tidak lagi dibaca langsung
 * oleh halaman pembanding kecuali sebagai cadangan bila set sekolah belum
 * ada. Migrasi ini menambahkan kolom sekolah_id, mengganti kunci unik
 * global kode_kita menjadi unik per (sekolah_id, kode_kita), lalu
 * mengkloning templat ke semua sekolah aktif beserta komponennya.
 */
class AhspAcuanPerSekolah extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ahsp_acuan', [
            'sekolah_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'id'],
        ]);
        $indeks = $this->db->query("SHOW INDEX FROM `ahsp_acuan` WHERE Key_name = 'kode_kita'")->getResultArray();
        if ($indeks !== []) {
            $this->db->query('ALTER TABLE `ahsp_acuan` DROP INDEX `kode_kita`');
        }
        $this->db->query('ALTER TABLE `ahsp_acuan` ADD UNIQUE KEY `uq_ahsp_acuan_sekolah_kode` (`sekolah_id`, `kode_kita`), ADD KEY `idx_ahsp_acuan_sekolah` (`sekolah_id`)');

        $now = date('Y-m-d H:i:s');
        $templat = $this->db->table('ahsp_acuan')->where('sekolah_id', null)->where('is_deleted', 0)->get()->getResultArray();
        if ($templat === []) {
            return;
        }
        foreach ($this->db->table('sekolah')->where('is_deleted', 0)->get()->getResultArray() as $s) {
            foreach ($templat as $t) {
                $komponen = $this->db->table('ahsp_acuan_komponen')
                    ->where('acuan_id', (int) $t['id'])->where('is_deleted', 0)->get()->getResultArray();
                unset($t['id']);
                $t['sekolah_id'] = (int) $s['id'];
                $t['created_at'] = $now;
                $t['updated_at'] = $now;
                $this->db->table('ahsp_acuan')->insert($t);
                $idBaru = (int) $this->db->insertID();
                foreach ($komponen as $k) {
                    unset($k['id']);
                    $k['acuan_id'] = $idBaru;
                    $k['created_at'] = $now;
                    $k['updated_at'] = $now;
                    $this->db->table('ahsp_acuan_komponen')->insert($k);
                }
            }
        }
    }

    public function down()
    {
        // Hapus salinan per sekolah (komponen ikut terhapus oleh FK CASCADE),
        // kembalikan kunci unik global, lalu buang kolomnya.
        $this->db->query('DELETE FROM `ahsp_acuan` WHERE `sekolah_id` IS NOT NULL');
        $this->db->query('ALTER TABLE `ahsp_acuan` DROP INDEX `uq_ahsp_acuan_sekolah_kode`, DROP INDEX `idx_ahsp_acuan_sekolah`');
        $this->db->query('ALTER TABLE `ahsp_acuan` ADD UNIQUE KEY `kode_kita` (`kode_kita`)');
        $this->forge->dropColumn('ahsp_acuan', 'sekolah_id');
    }
}
