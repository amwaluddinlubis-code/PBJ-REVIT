<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 2 adopsi pola aplikasi keuangan lain (keputusan AMWAL 26 Nov 2026,
 * "ambil saja yang relevan" dari riset ARKAS/SAKTI/SIMDA/SIPD-RI):
 *
 * - tutup_buku: kolom rekonsiliasi akhir bulan (saldo rekening koran,
 *   kas fisik hasil opname, bunga & biaya bank, selisih bank/tunai,
 *   penjelasan) yang dinyatakan bendahara saat tutup buku.
 * - kas_transaksi: koreksi_ref — rujukan transaksi asal untuk jurnal
 *   balik koreksi bulan tertutup yang dicatat di bulan terbuka.
 * - penyedia: no_rekening & nama_bank untuk validasi pembayaran
 *   non-tunai (transfer bank butuh rekening tujuan).
 */
class FaseDuaKeuangan extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tutup_buku')) {
            $kolom = [
                'saldo_koran'     => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true],
                'kas_fisik'       => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true],
                'bunga_bank'      => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'biaya_bank'      => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
                'selisih_bank'    => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true],
                'selisih_tunai'   => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true],
                'penjelasan_rekon' => ['type' => 'TEXT', 'null' => true],
            ];
            foreach ($kolom as $nama => $def) {
                if (! $this->db->getFieldNames('tutup_buku') || ! in_array($nama, $this->db->getFieldNames('tutup_buku'), true)) {
                    $this->forge->addColumn('tutup_buku', [$nama => $def]);
                }
            }
        }
        if ($this->db->tableExists('kas_transaksi') && ! in_array('koreksi_ref', $this->db->getFieldNames('kas_transaksi'), true)) {
            $this->forge->addColumn('kas_transaksi', [
                'koreksi_ref' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            ]);
        }
        if ($this->db->tableExists('penyedia')) {
            $ada = $this->db->getFieldNames('penyedia');
            if (! in_array('no_rekening', $ada, true)) {
                $this->forge->addColumn('penyedia', [
                    'no_rekening' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                ]);
            }
            if (! in_array('nama_bank', $ada, true)) {
                $this->forge->addColumn('penyedia', [
                    'nama_bank' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('penyedia')) {
            $ada = $this->db->getFieldNames('penyedia');
            if (in_array('nama_bank', $ada, true)) {
                $this->forge->dropColumn('penyedia', 'nama_bank');
            }
            if (in_array('no_rekening', $ada, true)) {
                $this->forge->dropColumn('penyedia', 'no_rekening');
            }
        }
        if ($this->db->tableExists('kas_transaksi') && in_array('koreksi_ref', $this->db->getFieldNames('kas_transaksi'), true)) {
            $this->forge->dropColumn('kas_transaksi', 'koreksi_ref');
        }
        if ($this->db->tableExists('tutup_buku')) {
            $ada = $this->db->getFieldNames('tutup_buku');
            foreach (['penjelasan_rekon', 'selisih_tunai', 'selisih_bank', 'biaya_bank', 'bunga_bank', 'kas_fisik', 'saldo_koran'] as $nama) {
                if (in_array($nama, $ada, true)) {
                    $this->forge->dropColumn('tutup_buku', $nama);
                }
            }
        }
    }
}
