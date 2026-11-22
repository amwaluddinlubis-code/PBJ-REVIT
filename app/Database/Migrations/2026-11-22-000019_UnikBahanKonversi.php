<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kunci unik (bahan_id, satuan_komponen) untuk bahan_konversi (audit relasi
 * 22 Nov 2026): satu pasangan hanya boleh punya satu faktor manual agar
 * faktorManual() tidak ambigu. Duplikat dibersihkan lebih dulu dengan pola
 * yang sama seperti kunci unik lain di aplikasi: baris aktif dipertahankan,
 * sisanya dihapus lunak; duplikat yang sama-sama terhapus lunak dibuang
 * permanen dari tabel sampah ini agar indeks bisa dibuat.
 */
class UnikBahanKonversi extends Migration
{
    public function up()
    {
        $db = $this->db;
        $pasangan = $db->query(
            'SELECT bahan_id, satuan_komponen FROM bahan_konversi
             GROUP BY bahan_id, satuan_komponen HAVING COUNT(*) > 1'
        )->getResultArray();
        foreach ($pasangan as $p) {
            $baris = $db->table('bahan_konversi')
                ->where(['bahan_id' => $p['bahan_id'], 'satuan_komponen' => $p['satuan_komponen']])
                ->orderBy('is_deleted')->orderBy('id')
                ->get()->getResultArray();
            $utama = true;
            foreach ($baris as $b) {
                if ($utama) {
                    $utama = false;
                    continue;
                }
                if ((int) $b['is_deleted'] === 1) {
                    $db->table('bahan_konversi')->where('id', $b['id'])->delete();
                } else {
                    $db->table('bahan_konversi')->where('id', $b['id'])
                        ->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
                }
            }
        }
        $db->query('ALTER TABLE bahan_konversi ADD UNIQUE KEY unik_konversi_bahan_satuan (bahan_id, satuan_komponen)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE bahan_konversi DROP INDEX unik_konversi_bahan_satuan');
    }
}
