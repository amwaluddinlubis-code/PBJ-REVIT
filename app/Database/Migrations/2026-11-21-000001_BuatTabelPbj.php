<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BuatTabelPbj extends Migration
{
    public function up()
    {
        // ---------- users ----------
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'nama'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'peran'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'admin'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users');

        // ---------- identitas (satu baris) ----------
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama_sekolah'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'npsn'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alamat'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'desa'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kecamatan'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kabupaten'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'provinsi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kode_pos'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'kop_pemerintah'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'kop_dinas'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'alamat_kop'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'tahun_anggaran'  => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'paket_pekerjaan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'tanggal_survei'  => ['type' => 'DATE', 'null' => true],
            'kepsek_nama'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kepsek_nip'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'ketua_nama'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sekretaris_nama' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pelaksana_nama'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pemeriksa1'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pemeriksa2'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pemeriksa3'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'toko1'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'toko2'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'toko3'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'toko4'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'penyedia_default_nama'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'penyedia_default_alamat' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('identitas');

        // ---------- bahan (master RAB) ----------
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode'      => ['type' => 'VARCHAR', 'constraint' => 10],
            'nama'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'satuan'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'harga_rab' => ['type' => 'BIGINT', 'default' => 0],
            'qty_rkb'   => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
            'qty_adm'   => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
            'qty_toilet' => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('bahan');

        // ---------- penyedia ----------
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'alamat'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ttd_nama'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('penyedia');

        // ---------- spk ----------
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor'       => ['type' => 'INT', 'unsigned' => true],
            'tanggal'     => ['type' => 'DATE', 'null' => true],
            'tgl_mulai'   => ['type' => 'DATE', 'null' => true],
            'tgl_selesai' => ['type' => 'DATE', 'null' => true],
            'penyedia_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ttd_penyedia' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'paket'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Aktif'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nomor');
        $this->forge->addForeignKey('penyedia_id', 'penyedia', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('spk');

        // ---------- spk_detail ----------
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'spk_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'bahan_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nama_manual'   => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'satuan_manual' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'qty'           => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
            'harga'         => ['type' => 'BIGINT', 'default' => 0],
            'pajak'         => ['type' => 'TINYINT', 'default' => 0],
            'keterangan'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('spk_id');
        $this->forge->addKey('bahan_id');
        $this->forge->addForeignKey('spk_id', 'spk', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('bahan_id', 'bahan', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('spk_detail');

        // ---------- survei_harga ----------
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'bahan_id' => ['type' => 'INT', 'unsigned' => true],
            'toko'     => ['type' => 'TINYINT'],
            'harga'    => ['type' => 'BIGINT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['bahan_id', 'toko']);
        $this->forge->addForeignKey('bahan_id', 'bahan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('survei_harga');

        // ---------- negosiasi ----------
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'bahan_id'        => ['type' => 'INT', 'unsigned' => true],
            'harga_penawaran' => ['type' => 'BIGINT', 'null' => true],
            'harga_nego'      => ['type' => 'BIGINT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('bahan_id');
        $this->forge->addForeignKey('bahan_id', 'bahan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('negosiasi');

        // ---------- bap + detail ----------
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'spk_id'     => ['type' => 'INT', 'unsigned' => true],
            'nomor_urut' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('spk_id', 'spk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bap');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'bap_id'     => ['type' => 'INT', 'unsigned' => true],
            'detail_id'  => ['type' => 'INT', 'unsigned' => true],
            'kondisi'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Cukup'],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('bap_id', 'bap', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('detail_id', 'spk_detail', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bap_detail');

        // ---------- bast ----------
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'spk_id'     => ['type' => 'INT', 'unsigned' => true],
            'nomor_urut' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('spk_id', 'spk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bast');

        // ---------- invoice ----------
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nomor_urut' => ['type' => 'INT', 'unsigned' => true],
            'no_toko'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tanggal'    => ['type' => 'DATE', 'null' => true],
            'spk_id'     => ['type' => 'INT', 'unsigned' => true],
            'bap_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'bast_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nilai'      => ['type' => 'BIGINT', 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Belum Bayar'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nomor_urut');
        $this->forge->addForeignKey('spk_id', 'spk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('bap_id', 'bap', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('bast_id', 'bast', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('invoice');

        // ---------- pembayaran (jurnal) ----------
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal'    => ['type' => 'DATE', 'null' => true],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('invoice_id', 'invoice', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pembayaran');

        // ---------- buku_material ----------
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tanggal'    => ['type' => 'DATE', 'null' => true],
            'no_bpb'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'bahan_id'   => ['type' => 'INT', 'unsigned' => true],
            'masuk'      => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
            'keluar'     => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('bahan_id', 'bahan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('buku_material');
    }

    public function down()
    {
        foreach (['buku_material', 'pembayaran', 'invoice', 'bast', 'bap_detail', 'bap', 'negosiasi', 'survei_harga', 'spk_detail', 'spk', 'penyedia', 'bahan', 'identitas', 'users'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
