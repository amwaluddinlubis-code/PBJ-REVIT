<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Impor data hasil ekstrak workbook PBJ (writable/impor.json) ke database.
 * Mengisi data SATU sekolah (yang namanya ada di impor.json); sekolah lain tidak disentuh.
 */
class ImporJson extends BaseCommand
{
    protected $group       = 'PBJ';
    protected $name        = 'impor:json';
    protected $description = 'Impor writable/impor.json (ekstrak workbook v4) ke database PBJ.';

    public function run(array $params)
    {
        $path = WRITEPATH . 'impor.json';
        if (! is_file($path)) {
            CLI::error('Berkas tidak ditemukan: ' . $path);
            return;
        }
        $d  = json_decode((string) file_get_contents($path), true);
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->transStart();

        // ---------- sekolah tujuan (dari identitas workbook) ----------
        $m = $d['identitas_mentah'] ?? [];
        $namaSekolah = $m['Nama Satuan Pendidikan'] ?? null;
        $barisSekolah = [

            'nama_sekolah'    => $m['Nama Satuan Pendidikan'] ?? null,
            'npsn'            => isset($m['NPSN']) ? (string) $m['NPSN'] : null,
            'alamat'          => $m['Alamat'] ?? null,
            'kecamatan'       => $m['Kecamatan'] ?? null,
            'kabupaten'       => $m['Kabupaten/Kota'] ?? null,
            'provinsi'        => $m['Provinsi'] ?? null,
            'email'           => $m['Email Sekolah'] ?? null,
            'kode_pos'        => isset($m['Kode Pos']) ? (string) $m['Kode Pos'] : null,
            'kop_pemerintah'  => $m['Baris Kop 1 (Pemerintah)'] ?? null,
            'kop_dinas'       => $m['Baris Kop 2 (Dinas)'] ?? null,
            'alamat_kop'      => $m['Alamat Kop Surat'] ?? null,
            'tahun_anggaran'  => isset($m['Tahun Anggaran']) ? (string) $m['Tahun Anggaran'] : null,
            'paket_pekerjaan' => $m['Paket Pekerjaan'] ?? null,
            'kepsek_nama'     => $m['Nama Kepala Sekolah'] ?? null,
            'kepsek_nip'      => isset($m['NIP Kepala Sekolah']) ? (string) $m['NIP Kepala Sekolah'] : null,
            'ketua_nama'      => $m['Ketua Panitia P2SP'] ?? null,
            'sekretaris_nama' => $m['Sekretaris / Logistik'] ?? null,
            'pelaksana_nama'  => $m['Kepala Pelaksana'] ?? null,
            'pemeriksa1'      => $m['Anggota Pemeriksa 1'] ?? null,
            'pemeriksa2'      => $m['Anggota Pemeriksa 2'] ?? null,
            'pemeriksa3'      => $m['Anggota Pemeriksa 3'] ?? null,
            'toko1'           => $m['Nama Toko 1'] ?? null,
            'toko2'           => $m['Nama Toko 2'] ?? null,
            'toko3'           => $m['Nama Toko 3'] ?? null,
            'toko4'           => $m['Nama Toko 4'] ?? null,
            'penyedia_default_nama'   => $m['Nama Penyedia (default)'] ?? null,
            'penyedia_default_alamat' => $m['Alamat Penyedia'] ?? null,
        ];
        $sudah = $namaSekolah ? $db->table('sekolah')->where('nama_sekolah', $namaSekolah)->get()->getRowArray() : null;
        if ($sudah) {
            $sekolahId = (int) $sudah['id'];
            $db->table('sekolah')->where('id', $sekolahId)->update($barisSekolah);
            // Kosongkan data lama sekolah ini saja (urutan aman terhadap FK).
            $db->table('spk_detail')->where('sekolah_id', $sekolahId)->delete();
            $db->table('spk')->where('sekolah_id', $sekolahId)->delete();
            $db->table('bahan')->where('sekolah_id', $sekolahId)->delete();
            $db->table('penyedia')->where('sekolah_id', $sekolahId)->delete();
        } else {
            $db->table('sekolah')->insert($barisSekolah);
            $sekolahId = (int) $db->insertID();
        }

        // ---------- bahan ----------
        $bahanId = [];
        foreach ($d['bahan'] ?? [] as $b) {
            $db->table('bahan')->insert([
                'sekolah_id' => $sekolahId,
                'kode' => $b['kode'], 'nama' => $b['nama'], 'satuan' => $b['satuan'],
                'harga_rab' => (int) $b['harga_rab'], 'qty_rkb' => $b['qty_rkb'],
                'qty_adm' => $b['qty_adm'], 'qty_toilet' => $b['qty_toilet'],
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $bahanId[$b['kode']] = (int) $db->insertID();
        }

        // ---------- penyedia + spk ----------
        $penyediaId = [];
        $buatPenyedia = static function (?string $nama) use ($db, &$penyediaId, $now, $sekolahId) {
            $nama = trim((string) $nama);
            if ($nama === '') {
                return null;
            }
            if (! isset($penyediaId[$nama])) {
                $db->table('penyedia')->insert(['sekolah_id' => $sekolahId, 'nama' => $nama, 'created_at' => $now, 'updated_at' => $now]);
                $penyediaId[$nama] = (int) $db->insertID();
            }
            return $penyediaId[$nama];
        };

        $refDetail = array_unique(array_filter(array_column($d['detail'] ?? [], 'no_spk')));
        $refInvoice = array_unique(array_filter(array_column($d['invoice'] ?? [], 'no_spk')));
        $spkId = [];
        foreach ($d['spk'] ?? [] as $s) {
            $punyaIsi = $s['tanggal'] || $s['penyedia'] || $s['paket'] || $s['status'];
            if (! $punyaIsi && ! in_array($s['nomor'], $refDetail, true) && ! in_array($s['nomor'], $refInvoice, true)) {
                continue;
            }
            $db->table('spk')->insert([
                'sekolah_id' => $sekolahId,
                'nomor' => $s['nomor'], 'tanggal' => $s['tanggal'],
                'tgl_mulai' => $s['mulai'], 'tgl_selesai' => $s['selesai'],
                'penyedia_id' => $buatPenyedia($s['penyedia']),
                'ttd_penyedia' => $s['ttd'], 'paket' => $s['paket'],
                'status' => $s['status'] ?: 'Aktif',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $spkId[$s['nomor']] = (int) $db->insertID();
        }

        // ---------- spk_detail ----------
        $nDetail = 0;
        foreach ($d['detail'] ?? [] as $r) {
            $bid = $r['kode'] ? ($bahanId[$r['kode']] ?? null) : null;
            $db->table('spk_detail')->insert([
                'sekolah_id' => $sekolahId,
                'spk_id' => $r['no_spk'] ? ($spkId[$r['no_spk']] ?? null) : null,
                'bahan_id' => $bid,
                'nama_manual' => $bid ? null : $r['nama'],
                'satuan_manual' => $bid ? null : $r['satuan'],
                'qty' => $r['qty'], 'harga' => (int) $r['harga'],
                'pajak' => (int) $r['pajak'],
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $nDetail++;
        }

        // ---------- bap/bast + invoice + pembayaran ----------
        foreach ($d['invoice'] ?? [] as $r) {
            $sid = $spkId[$r['no_spk']] ?? null;
            if ($sid === null) {
                continue;
            }
            $bapId = $bastId = null;
            if ($r['no_bap']) {
                $db->table('bap')->insert(['spk_id' => $sid, 'nomor_urut' => $r['no_bap'], 'tanggal' => $r['tgl_bap'], 'created_at' => $now, 'updated_at' => $now]);
                $bapId = (int) $db->insertID();
            }
            if ($r['no_bast']) {
                $db->table('bast')->insert(['spk_id' => $sid, 'nomor_urut' => $r['no_bast'], 'tanggal' => $r['tgl_bast'], 'created_at' => $now, 'updated_at' => $now]);
                $bastId = (int) $db->insertID();
            }
            $status = ($r['status'] === 'Sudah Bayar') ? 'Sudah Bayar' : 'Belum Bayar';
            $db->table('invoice')->insert([
                'nomor_urut' => $r['urut'], 'no_toko' => $r['no_toko'], 'tanggal' => $r['tanggal'],
                'spk_id' => $sid, 'bap_id' => $bapId, 'bast_id' => $bastId,
                'nilai' => (int) $r['nilai'], 'status' => $status,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($status === 'Sudah Bayar') {
                $db->table('pembayaran')->insert([
                    'invoice_id' => (int) $db->insertID(), 'tanggal' => null,
                    'keterangan' => 'Diimpor dari workbook — tanggal bayar belum diisi',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $db->transComplete();
        CLI::write('Impor selesai: bahan=' . count($bahanId) . ' spk=' . count($spkId) . ' detail=' . $nDetail, 'green');
    }
}
