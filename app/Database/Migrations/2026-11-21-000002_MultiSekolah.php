<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Multi-sekolah: tabel identitas menjadi tabel sekolah (banyak baris),
 * dan tabel akar data (bahan, penyedia, spk) mendapat kolom sekolah_id.
 * Tabel anak mengikuti induknya (spk_detail/bap/bast/invoice lewat spk;
 * survei_harga/negosiasi/buku_material lewat bahan).
 */
class MultiSekolah extends Migration
{
    public function up()
    {
        $db = $this->db;

        $db->query('RENAME TABLE identitas TO sekolah');
        $db->query('ALTER TABLE sekolah ADD COLUMN created_at DATETIME NULL, ADD COLUMN updated_at DATETIME NULL');

        $db->query('ALTER TABLE bahan ADD COLUMN sekolah_id INT UNSIGNED NULL AFTER id');
        $db->query('UPDATE bahan SET sekolah_id = 1 WHERE sekolah_id IS NULL');
        $db->query('ALTER TABLE bahan DROP INDEX kode, ADD UNIQUE KEY bahan_sekolah_kode (sekolah_id, kode)');
        $db->query('ALTER TABLE bahan ADD CONSTRAINT bahan_sekolah_fk FOREIGN KEY (sekolah_id) REFERENCES sekolah (id) ON DELETE CASCADE ON UPDATE CASCADE');

        $db->query('ALTER TABLE penyedia ADD COLUMN sekolah_id INT UNSIGNED NULL AFTER id');
        $db->query('UPDATE penyedia SET sekolah_id = 1 WHERE sekolah_id IS NULL');
        $db->query('ALTER TABLE penyedia ADD CONSTRAINT penyedia_sekolah_fk FOREIGN KEY (sekolah_id) REFERENCES sekolah (id) ON DELETE CASCADE ON UPDATE CASCADE');

        $db->query('ALTER TABLE spk ADD COLUMN sekolah_id INT UNSIGNED NULL AFTER id');
        $db->query('UPDATE spk SET sekolah_id = 1 WHERE sekolah_id IS NULL');
        $db->query('ALTER TABLE spk DROP INDEX nomor, ADD UNIQUE KEY spk_sekolah_nomor (sekolah_id, nomor)');
        $db->query('ALTER TABLE spk ADD CONSTRAINT spk_sekolah_fk FOREIGN KEY (sekolah_id) REFERENCES sekolah (id) ON DELETE CASCADE ON UPDATE CASCADE');

        // spk_detail ikut membawa sekolah_id agar baris manual (tanpa bahan)
        // dan baris belum bernomor tetap terikat ke sekolahnya
        $db->query('ALTER TABLE spk_detail ADD COLUMN sekolah_id INT UNSIGNED NULL AFTER id');
        $db->query('UPDATE spk_detail d JOIN bahan b ON b.id = d.bahan_id SET d.sekolah_id = b.sekolah_id WHERE d.bahan_id IS NOT NULL');
        $db->query('UPDATE spk_detail d JOIN spk s ON s.id = d.spk_id SET d.sekolah_id = s.sekolah_id WHERE d.sekolah_id IS NULL AND d.spk_id IS NOT NULL');
        $db->query('UPDATE spk_detail SET sekolah_id = 1 WHERE sekolah_id IS NULL');
        $db->query('ALTER TABLE spk_detail ADD CONSTRAINT detail_sekolah_fk FOREIGN KEY (sekolah_id) REFERENCES sekolah (id) ON DELETE CASCADE ON UPDATE CASCADE');

        // nomor urut invoice tidak lagi unik global (diperiksa per sekolah di aplikasi)
        $db->query('ALTER TABLE invoice DROP INDEX nomor_urut');
    }

    public function down()
    {
        $db = $this->db;
        $db->query('ALTER TABLE invoice ADD UNIQUE KEY nomor_urut (nomor_urut)');
        $db->query('ALTER TABLE spk_detail DROP FOREIGN KEY detail_sekolah_fk, DROP COLUMN sekolah_id');
        $db->query('ALTER TABLE spk DROP FOREIGN KEY spk_sekolah_fk, DROP INDEX spk_sekolah_nomor, ADD UNIQUE KEY nomor (nomor), DROP COLUMN sekolah_id');
        $db->query('ALTER TABLE penyedia DROP FOREIGN KEY penyedia_sekolah_fk, DROP COLUMN sekolah_id');
        $db->query('ALTER TABLE bahan DROP FOREIGN KEY bahan_sekolah_fk, DROP INDEX bahan_sekolah_kode, ADD UNIQUE KEY kode (kode), DROP COLUMN sekolah_id');
        $db->query('ALTER TABLE sekolah DROP COLUMN created_at, DROP COLUMN updated_at');
        $db->query('RENAME TABLE sekolah TO identitas');
    }
}
