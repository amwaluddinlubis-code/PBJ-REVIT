<?php

namespace App\Controllers;

/**
 * Bukti Setor & Pajak: register penyetoran pajak yang dipungut/dipotong
 * bendahara (PPh 22, PPN, PPh 21, PPh 23, PPh 4 ayat 2) ke kas negara
 * dengan kode e-billing dan NTPN, dibandingkan terhadap kewajiban yang
 * timbul dari transaksi — serta pengembalian sisa dana bantuan lewat
 * SIMPONI. Sesuai tata cara penyetoran pada Juknis Revitalisasi SMP 2026.
 */
class Setor extends BaseController
{
    /** Kewajiban pajak per jenis & masa dari transaksi yang sudah terjadi. */
    private function kewajiban(): array
    {
        $db = $this->db();
        $sid = $this->sid();
        $wajib = []; // "jenis|masa" => jumlah

        $tambah = static function (string $jenis, ?string $tanggal, float $jumlah) use (&$wajib) {
            if ($jumlah <= 0 || ! $tanggal) {
                return;
            }
            $kunci = $jenis . '|' . substr($tanggal, 0, 7);
            $wajib[$kunci] = ($wajib[$kunci] ?? 0) + $jumlah;
        };

        $peta = $this->ppnSpkMap();
        foreach ($db->query(
            'SELECT p.tanggal AS tgl_bayar, i.tanggal AS tgl_inv, i.nilai, i.spk_id
             FROM pembayaran p JOIN invoice i ON i.id = p.invoice_id AND i.is_deleted = 0
             JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
             WHERE s.sekolah_id = ? AND p.is_deleted = 0',
            [$sid]
        )->getResultArray() as $r) {
            $tgl = $r['tgl_bayar'] ?: $r['tgl_inv'];
            $pjk = $this->pajakPembelian((float) $r['nilai'], $this->ppnInvoice($peta, (int) $r['spk_id'], (float) $r['nilai']));
            $tambah('pph22', $tgl, $pjk['pph22']);
            $tambah('ppn', $tgl, $pjk['ppn']);
        }

        foreach ($db->table('upah_bayar')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $u) {
            $tambah('pph21', $u['tanggal'], $this->pph21UpahHarian((float) $u['tarif'], (float) $u['orang_hari']));
        }

        foreach ($db->table('biaya_lain')->where(['sekolah_id' => $sid, 'is_deleted' => 0])->get()->getResultArray() as $b) {
            if (empty($b['pajak'])) {
                continue;
            }
            $jenis = str_starts_with($b['pajak'], 'pph42') ? 'pph42' : (str_starts_with($b['pajak'], 'pph21') ? 'pph21' : $b['pajak']);
            if (! in_array($jenis, ['pph23', 'pph42', 'pph21'], true)) {
                continue;
            }
            $tambah($jenis, $b['created_at'], $this->potonganPajak($b['pajak'], (float) $b['jumlah']));
        }
        ksort($wajib);
        return $wajib;
    }

    public function index()
    {
        $db = $this->db();
        $setor = $db->table('bukti_setor')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('tanggal_setor', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        $sudah = [];
        foreach ($setor as $s) {
            $kunci = $s['jenis'] . '|' . ($s['masa'] ?? '');
            $sudah[$kunci] = ($sudah[$kunci] ?? 0) + (float) $s['jumlah'];
        }
        $baris = [];
        foreach ($this->kewajiban() as $kunci => $jumlah) {
            [$jenis, $masa] = explode('|', $kunci);
            $disetor = $sudah[$kunci] ?? 0;
            $baris[] = [
                'jenis' => $jenis, 'masa' => $masa, 'wajib' => $jumlah, 'disetor' => $disetor,
                'kurang' => $jumlah - $disetor,
                'status' => $disetor <= 0 ? 'Belum disetor' : ($disetor < $jumlah ? 'Sebagian' : 'Lunas'),
            ];
            unset($sudah[$kunci]);
        }
        $per = $this->penerimaanPerTahap();
        $diterima = $per[1] + $per[2] + $per['lain'];
        $sisa = $diterima - $this->totalBelanja();
        $setorSisa = 0.0;
        foreach ($setor as $s) {
            if ($s['jenis'] === 'sisa_dana') {
                $setorSisa += (float) $s['jumlah'];
            }
        }
        return $this->tampil('setor/index', [
            'baris' => $baris, 'setor' => $setor,
            'sisa' => $sisa, 'setor_sisa' => $setorSisa,
            'tgl_bayar_default' => $this->tanggalBulanBekerja(),
        ], 'Bukti Setor & Pajak — Aplikasi PBJ Sekolah');
    }

    public function simpan()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $r = $this->request;
        $jenis = (string) $r->getPost('jenis');
        $jumlah = (float) preg_replace('/[^0-9]/', '', (string) $r->getPost('jumlah'));
        if (! in_array($jenis, ['pph22', 'ppn', 'pph21', 'pph23', 'pph42', 'sisa_dana'], true)
            || $jumlah <= 0 || ! $r->getPost('tanggal_setor')) {
            return redirect()->to('/bukti-setor')->with('galat', 'Jenis setoran, tanggal setor, dan jumlah wajib diisi.');
        }
        if ($g = $this->galatBulanBekerja((string) $r->getPost('tanggal_setor'))) {
            return redirect()->to('/bukti-setor')->with('galat', $g);
        }
        $masa = trim((string) $r->getPost('masa'));
        $this->db()->table('bukti_setor')->insert([
            'sekolah_id' => $this->sid(),
            'jenis' => $jenis,
            'masa' => preg_match('/^\d{4}-\d{2}$/', $masa) ? $masa : null,
            'tanggal_setor' => (string) $r->getPost('tanggal_setor'),
            'jumlah' => $jumlah,
            'kode_billing' => trim((string) $r->getPost('kode_billing')) ?: null,
            'ntpn' => trim((string) $r->getPost('ntpn')) ?: null,
            'keterangan' => trim((string) $r->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAudit('bukti_setor', (int) $this->db()->insertID(), 'buat', 'Bukti setor pajak dicatat.');
        return redirect()->to('/bukti-setor')->with('sukses', 'Bukti setor dicatat dan masuk Buku Kas sebagai uang keluar.');
    }

    public function hapus($id)
    {
        $this->hapusLunak('bukti_setor', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        $this->catatAudit('bukti_setor', (int) $id, 'hapus', 'Bukti setor dihapus.');
        return redirect()->to('/bukti-setor')->with('sukses', 'Bukti setor dihapus.');
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            $ada = $this->db()->table('bukti_setor')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('bukti_setor', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        return redirect()->to('/bukti-setor')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' bukti setor terpilih dihapus.' : 'Tidak ada bukti setor yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('bukti_setor', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('bukti_setor')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        foreach ($ids as $id) {
            $this->hapusLunak('bukti_setor', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        }
        return redirect()->to('/bukti-setor')->with('sukses', 'Bukti setor dikosongkan: ' . count($ids) . ' catatan dihapus.');
    }
}
