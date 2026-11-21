<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Modul Analisa Perencana: gedung sebagai entitas, BOQ pekerjaan,
 * analisa AHSP + komponen, master upah, realisasi, pembayaran upah,
 * pagu anggaran per sekolah, dan tautan SPK -> gedung.
 */
class ModulPerencana extends Migration
{
    public function up()
    {
        $db = $this->db;

        $db->query('ALTER TABLE sekolah ADD COLUMN total_anggaran DECIMAL(18,2) NOT NULL DEFAULT 0');
        $db->query('ALTER TABLE spk ADD COLUMN gedung_id INT UNSIGNED NULL, ADD KEY idx_spk_gedung (gedung_id)');

        $db->query("CREATE TABLE gedung (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sekolah_id INT UNSIGNED NOT NULL,
            nama VARCHAR(150) NOT NULL,
            keterangan VARCHAR(255) NULL,
            nilai_boq DECIMAL(18,2) NOT NULL DEFAULT 0,
            sumber_sheet VARCHAR(120) NULL,
            urutan INT NOT NULL DEFAULT 0,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            KEY idx_gedung_sekolah (sekolah_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE pekerjaan (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            gedung_id INT UNSIGNED NOT NULL,
            bab VARCHAR(160) NULL,
            nama VARCHAR(255) NOT NULL,
            kode_analisa VARCHAR(20) NULL,
            volume DECIMAL(16,4) NOT NULL DEFAULT 0,
            satuan VARCHAR(20) NULL,
            harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
            jumlah DECIMAL(18,2) NOT NULL DEFAULT 0,
            urutan INT NOT NULL DEFAULT 0,
            KEY idx_pekerjaan_gedung (gedung_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE analisa (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sekolah_id INT UNSIGNED NOT NULL,
            kode VARCHAR(20) NOT NULL,
            nama VARCHAR(255) NULL,
            satuan VARCHAR(20) NULL,
            UNIQUE KEY uq_analisa_sekolah_kode (sekolah_id, kode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE analisa_komponen (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            analisa_id INT UNSIGNED NOT NULL,
            jenis ENUM('bahan','upah','alat') NOT NULL DEFAULT 'bahan',
            nama VARCHAR(200) NOT NULL,
            satuan VARCHAR(20) NULL,
            koefisien DECIMAL(14,6) NOT NULL DEFAULT 0,
            bahan_id INT UNSIGNED NULL,
            tarif DECIMAL(18,2) NULL,
            KEY idx_komponen_analisa (analisa_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE upah_master (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sekolah_id INT UNSIGNED NOT NULL,
            jenis VARCHAR(120) NOT NULL,
            satuan VARCHAR(20) NOT NULL DEFAULT 'OH',
            tarif DECIMAL(18,2) NOT NULL DEFAULT 0,
            UNIQUE KEY uq_upah_sekolah_jenis (sekolah_id, jenis)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE realisasi (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            pekerjaan_id INT UNSIGNED NOT NULL,
            tanggal DATE NOT NULL,
            volume DECIMAL(16,4) NOT NULL DEFAULT 0,
            keterangan VARCHAR(255) NULL,
            created_at DATETIME NULL,
            KEY idx_realisasi_pekerjaan (pekerjaan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE upah_bayar (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sekolah_id INT UNSIGNED NOT NULL,
            gedung_id INT UNSIGNED NULL,
            pekerjaan_id INT UNSIGNED NULL,
            tanggal DATE NOT NULL,
            jenis_tenaga VARCHAR(120) NOT NULL,
            penerima VARCHAR(150) NULL,
            orang_hari DECIMAL(10,2) NOT NULL DEFAULT 0,
            tarif DECIMAL(18,2) NOT NULL DEFAULT 0,
            jumlah DECIMAL(18,2) NOT NULL DEFAULT 0,
            keterangan VARCHAR(255) NULL,
            created_at DATETIME NULL,
            KEY idx_upahbayar_sekolah (sekolah_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->query("CREATE TABLE impor_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sekolah_id INT UNSIGNED NOT NULL,
            berkas VARCHAR(255) NOT NULL,
            ringkasan TEXT NULL,
            created_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Gedung bawaan dari kolom qty lama, untuk sekolah yang sudah punya data bahan
        $db->query("INSERT INTO gedung (sekolah_id, nama, nilai_boq, urutan, created_at)
            SELECT DISTINCT b.sekolah_id, 'RKB 3 Kopel', 0, 1, NOW() FROM bahan b");
        $db->query("INSERT INTO gedung (sekolah_id, nama, nilai_boq, urutan, created_at)
            SELECT DISTINCT b.sekolah_id, 'Ruang ADM', 0, 2, NOW() FROM bahan b");
        $db->query("INSERT INTO gedung (sekolah_id, nama, nilai_boq, urutan, created_at)
            SELECT DISTINCT b.sekolah_id, 'Toilet', 0, 3, NOW() FROM bahan b");
    }

    public function down()
    {
        $db = $this->db;
        $db->query('DROP TABLE IF EXISTS impor_log');
        $db->query('DROP TABLE IF EXISTS upah_bayar');
        $db->query('DROP TABLE IF EXISTS realisasi');
        $db->query('DROP TABLE IF EXISTS upah_master');
        $db->query('DROP TABLE IF EXISTS analisa_komponen');
        $db->query('DROP TABLE IF EXISTS analisa');
        $db->query('DROP TABLE IF EXISTS pekerjaan');
        $db->query('DROP TABLE IF EXISTS gedung');
        $db->query('ALTER TABLE spk DROP COLUMN gedung_id');
        $db->query('ALTER TABLE sekolah DROP COLUMN total_anggaran');
    }
}
