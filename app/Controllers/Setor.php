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
    /** Kewajiban pajak per jenis & masa (hitungan bersama di BaseController). */
    private function kewajiban(): array
    {
        return $this->kewajibanPajak();
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
        ] + $this->batasBuku(), 'Bukti Setor & Pajak — Aplikasi PBJ Sekolah');
    }

    /** Balasan form modal (pola Bahan::balasForm): AJAX -> JSON {ok, pesan}; biasa -> redirect flash. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('/bukti-setor')->with($ok ? 'sukses' : 'galat', $pesan);
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
            return $this->balasForm(false, 'Jenis setoran, tanggal setor, dan jumlah wajib diisi.');
        }
        if ($g = $this->galatBulanTertutup((string) $r->getPost('tanggal_setor'))) {
            return $this->balasForm(false, $g);
        }
        // Masa wajib & berformat sah untuk jenis pajak — setoran tanpa masa
        // tidak pernah mengurangi kewajiban mana pun. Sisa dana boleh tanpa
        // masa, tetapi format yang diisi harus tetap sah.
        $masa = trim((string) $r->getPost('masa'));
        if ($masa !== '' && ! preg_match('/^\d{4}-\d{2}$/', $masa)) {
            return $this->balasForm(false, 'Masa pajak harus berformat YYYY-MM (cth. 2026-05).');
        }
        if ($jenis !== 'sisa_dana' && $masa === '') {
            return $this->balasForm(false, 'Masa pajak wajib diisi untuk setoran pajak — tanpa masa, setoran tidak mengurangi kewajiban masa mana pun.');
        }
        // Peringatan kelebihan setor (bukan blokir: setor gabungan beberapa
        // masa adalah praktik sah) — kelebihan tidak menutup masa lain.
        $peringatan = '';
        if ($jenis === 'sisa_dana') {
            $per = $this->penerimaanPerTahap();
            $setorSisa = (float) $this->db()->query(
                "SELECT COALESCE(SUM(jumlah),0) v FROM bukti_setor WHERE sekolah_id = ? AND jenis = 'sisa_dana' AND is_deleted = 0",
                [$this->sid()]
            )->getRow()->v;
            $tersedia = ($per[1] + $per[2] + $per['lain'] - $this->totalBelanja()) - $setorSisa;
            if ($jumlah > $tersedia) {
                $peringatan = ' Perhatian: jumlah melebihi sisa dana terhitung yang belum dikembalikan (' . $this->rp(max(0, $tersedia)) . ').';
            }
        } else {
            $wajib = $this->kewajiban()[$jenis . '|' . $masa] ?? 0;
            $sudah = (float) $this->db()->query(
                'SELECT COALESCE(SUM(jumlah),0) v FROM bukti_setor WHERE sekolah_id = ? AND jenis = ? AND masa = ? AND is_deleted = 0',
                [$this->sid(), $jenis, $masa]
            )->getRow()->v;
            $kurang = $wajib - $sudah;
            if ($jumlah > $kurang) {
                $peringatan = ' Perhatian: jumlah melebihi kekurangan kewajiban ' . strtoupper($jenis) . ' masa ' . $masa . ' (' . $this->rp(max(0, $kurang)) . ') — kelebihan tidak mengurangi kewajiban masa lain.';
            }
        }
        // Saldo bank tidak boleh minus pada tanggal setor maupun sesudahnya.
        if ($g = $this->galatSaldoMinus('bank', (string) $r->getPost('tanggal_setor'), $jumlah)) {
            return $this->balasForm(false, $g);
        }
        $this->db()->table('bukti_setor')->insert([
            'sekolah_id' => $this->sid(),
            'jenis' => $jenis,
            'masa' => $masa !== '' ? $masa : null,
            'tanggal_setor' => (string) $r->getPost('tanggal_setor'),
            'jumlah' => $jumlah,
            'kode_billing' => trim((string) $r->getPost('kode_billing')) ?: null,
            'ntpn' => trim((string) $r->getPost('ntpn')) ?: null,
            'keterangan' => trim((string) $r->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAudit('bukti_setor', (int) $this->db()->insertID(), 'buat', 'Bukti setor pajak dicatat.');
        return $this->balasForm(true, 'Bukti setor dicatat dan masuk Buku Kas sebagai uang keluar.' . $peringatan);
    }

    public function hapus($id)
    {
        $row = $this->db()->table('bukti_setor')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($row && ($g = $this->galatBulanTertutup($row['tanggal_setor']))) {
            return redirect()->to('/bukti-setor')->with('galat', $g);
        }
        $this->hapusLunak('bukti_setor', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        $this->catatAudit('bukti_setor', (int) $id, 'hapus', 'Bukti setor dihapus.');
        return redirect()->to('/bukti-setor')->with('sukses', 'Bukti setor dihapus.');
    }

    public function hapusMassal()
    {
        $n = 0;
        $terkunci = 0;
        foreach ($this->idsTerpilih() as $id) {
            $row = $this->db()->table('bukti_setor')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($row) {
                if ($this->galatBulanTertutup($row['tanggal_setor'])) {
                    $terkunci++;
                    continue;
                }
                $this->hapusLunak('bukti_setor', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        $pesan = $n > 0 ? $n . ' bukti setor terpilih dihapus.' : 'Tidak ada bukti setor yang diproses.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' bukti setor dilewati karena bukunya sudah ditutup.';
        }
        return redirect()->to('/bukti-setor')->with($n > 0 ? 'sukses' : 'galat', $pesan);
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('bukti_setor', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $rows = $this->db()->table('bukti_setor')->select('id, tanggal_setor')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray();
        $n = 0;
        $terkunci = 0;
        foreach ($rows as $row) {
            if ($this->galatBulanTertutup($row['tanggal_setor'])) {
                $terkunci++;
                continue;
            }
            $this->hapusLunak('bukti_setor', ['id' => (int) $row['id'], 'sekolah_id' => $this->sid()]);
            $n++;
        }
        $pesan = 'Bukti setor dikosongkan: ' . $n . ' catatan dihapus.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' catatan bulan tertutup tidak ikut terhapus.';
        }
        return redirect()->to('/bukti-setor')->with('sukses', $pesan);
    }
}
