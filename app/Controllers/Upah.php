<?php

namespace App\Controllers;

/**
 * Pembayaran Upah: pencatatan bayar tenaga kerja per gedung/pekerjaan,
 * tarif master upah, dan penegakan kontrol pagu untuk belanja upah.
 */
class Upah extends BaseController
{
    public function index()
    {
        $db = $this->db();
        $daftar = $db->query(
            'SELECT u.*, g.nama AS nama_gedung, p.nama AS nama_pekerjaan
             FROM upah_bayar u
             LEFT JOIN gedung g ON g.id = u.gedung_id
             LEFT JOIN pekerjaan p ON p.id = u.pekerjaan_id
             WHERE u.sekolah_id = ? AND u.is_deleted = 0 ORDER BY u.tanggal DESC, u.id DESC',
            [$this->sid()]
        )->getResultArray();
        $total = array_sum(array_map(static fn ($r) => (float) $r['jumlah'], $daftar));
        $totalPph21 = 0.0;
        foreach ($daftar as &$u) {
            $u['pph21'] = $this->pph21UpahHarian((float) $u['tarif'], (float) $u['orang_hari']);
            $u['bersih'] = (float) $u['jumlah'] - $u['pph21'];
            $totalPph21 += $u['pph21'];
        }
        unset($u);
        return $this->tampil('upah/index', [
            'daftar' => $daftar, 'total' => $total, 'total_pph21' => $totalPph21,
            'master' => $db->table('upah_master')->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->orderBy('jenis')->get()->getResultArray(),
            'pekerjaan_per_gedung' => $this->pekerjaanPerGedung(),
            'tgl_bayar_default' => $this->tanggalBulanBekerja(),
        ], 'Pembayaran Upah');
    }

    private function pekerjaanPerGedung(): array
    {
        $hasil = [];
        foreach ($this->db()->query(
            'SELECT p.id, p.nama, p.gedung_id FROM pekerjaan p JOIN gedung g ON g.id = p.gedung_id AND g.is_deleted = 0 WHERE g.sekolah_id = ? AND p.is_deleted = 0 ORDER BY p.gedung_id, p.urutan, p.id',
            [$this->sid()]
        )->getResultArray() as $p) {
            $hasil[(int) $p['gedung_id']][] = $p;
        }
        return $hasil;
    }

    /** Balasan form modal (pola Bahan::balasForm): AJAX -> JSON {ok, pesan}; biasa -> redirect flash. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('/upah')->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function simpan()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $r = $this->request;
        $gedungId = (int) $r->getPost('gedung_id') ?: null;
        if ($gedungId !== null && $this->ambilGedung($gedungId) === null) {
            $gedungId = null;
        }
        $jenis = trim((string) $r->getPost('jenis_tenaga'));
        $oh = (float) str_replace(',', '.', (string) $r->getPost('orang_hari'));
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $r->getPost('tarif'));
        if ($tarif <= 0) {
            $m = $this->db()->table('upah_master')->where(['sekolah_id' => $this->sid(), 'jenis' => $jenis, 'is_deleted' => 0])->get()->getRowArray();
            $tarif = (float) ($m['tarif'] ?? 0);
        }
        $jumlah = $oh * $tarif;
        if ($jenis === '' || $oh <= 0 || $jumlah <= 0) {
            return $this->balasForm(false, 'Jenis tenaga, orang-hari, dan tarif wajib terisi benar.');
        }
        $tanggalUpah = (string) ($r->getPost('tanggal') ?: date('Y-m-d'));
        if ($g = $this->galatBulanBekerja($tanggalUpah)) {
            return $this->balasForm(false, $g);
        }
        [$boleh, $sisaPagu, $anggaran] = $this->cekPagu($jumlah);
        if (! $boleh) {
            return $this->balasForm(false, sprintf(
                'Pembayaran upah ditahan: jumlah %s melewati sisa pagu anggaran %s dari total anggaran %s.',
                $this->rp($jumlah),
                $this->rp(max(0, $sisaPagu)),
                $this->rp($anggaran)
            ));
        }
        $this->db()->table('upah_bayar')->insert([
            'sekolah_id' => $this->sid(), 'gedung_id' => $gedungId,
            'pekerjaan_id' => (int) $r->getPost('pekerjaan_id') ?: null,
            'tanggal' => $tanggalUpah,
            'jenis_tenaga' => $jenis,
            'penerima' => trim((string) $r->getPost('penerima')) ?: null,
            'orang_hari' => $oh, 'tarif' => $tarif, 'jumlah' => $jumlah,
            'keterangan' => trim((string) $r->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAudit('upah_bayar', (int) $this->db()->insertID(), 'buat', 'Pembayaran upah ' . $jenis . ' sebesar ' . $this->rp($jumlah));
        return $this->balasForm(true, 'Pembayaran upah ' . $jenis . ' sebesar ' . $this->rp($jumlah) . ' dicatat.');
    }

    public function hapus($id)
    {
        $this->hapusLunak('upah_bayar', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        $this->catatAudit('upah_bayar', (int) $id, 'hapus', 'Catatan pembayaran upah dihapus.');
        return redirect()->to('/upah')->with('sukses', 'Catatan pembayaran upah dihapus.');
    }

    public function hapusMassal()
    {
        $n = 0;
        foreach ($this->idsTerpilih() as $id) {
            $ada = $this->db()->table('upah_bayar')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->countAllResults();
            if ($ada > 0) {
                $this->hapusLunak('upah_bayar', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        return redirect()->to('/upah')->with($n > 0 ? 'sukses' : 'galat',
            $n > 0 ? $n . ' catatan pembayaran upah terpilih dihapus.' : 'Tidak ada catatan yang diproses.');
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('upah_bayar', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $ids = array_column($this->db()->table('upah_bayar')->select('id')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray(), 'id');
        foreach ($ids as $id) {
            $this->hapusLunak('upah_bayar', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        }
        return redirect()->to('/upah')->with('sukses', 'Catatan pembayaran upah dikosongkan: ' . count($ids) . ' catatan dihapus.');
    }

    /**
     * Cetak daftar pembayaran upah satu bulan mengikuti format dokumen
     * pembayaran upah Juknis: daftar penerima dengan jumlah bruto, PPh 21,
     * jumlah bersih, dan kolom tanda tangan penerima.
     */
    public function cetakDaftar()
    {
        $bulan = (string) ($this->request->getGet('bulan') ?? date('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }
        $rows = $this->db()->table('upah_bayar')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->like('tanggal', $bulan, 'after')
            ->orderBy('tanggal')->orderBy('id')->get()->getResultArray();
        $total = ['bruto' => 0.0, 'pph21' => 0.0, 'bersih' => 0.0];
        foreach ($rows as &$u) {
            $u['pph21'] = $this->pph21UpahHarian((float) $u['tarif'], (float) $u['orang_hari']);
            $u['bersih'] = (float) $u['jumlah'] - $u['pph21'];
            $total['bruto'] += (float) $u['jumlah'];
            $total['pph21'] += $u['pph21'];
            $total['bersih'] += $u['bersih'];
        }
        return view('cetak/daftar_upah', [
            'idn' => $this->sekolahAktif(), 'bulan' => $bulan, 'rows' => $rows, 'total' => $total,
        ]);
    }

    /** Ubah tarif satu jenis tenaga pada master upah. */
    public function tarif($id)
    {
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif'));
        $this->db()->table('upah_master')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->update(['tarif' => $tarif]);
        return redirect()->to('/upah')->with('sukses', 'Tarif upah diperbarui.');
    }

    /** Tambah jenis tenaga kanonik (Pekerja/Tukang) ke master upah. */
    public function masterSimpan()
    {
        $jenis = $this->upahKanon((string) $this->request->getPost('jenis'));
        $tarif = (float) str_replace(['.', ','], ['', '.'], (string) $this->request->getPost('tarif'));
        if ($jenis === null) {
            return $this->balasForm(false,
                'Master upah hanya mengenal dua jenis: Pekerja dan Tukang. Varian lain dirapikan lewat tombol "Rapikan ke 2 Jenis" di Master Bahan & Upah.');
        }
        $ada = $this->db()->table('upah_master')
            ->where(['sekolah_id' => $this->sid(), 'jenis' => $jenis, 'is_deleted' => 0])
            ->countAllResults();
        if ($ada > 0) {
            return $this->balasForm(false, 'Jenis tenaga "' . $jenis . '" sudah ada di master upah; ubah tarifnya pada baris tabel.');
        }
        $this->db()->table('upah_master')->ignore(true)->insert([
            'sekolah_id' => $this->sid(), 'jenis' => $jenis, 'satuan' => 'OH', 'tarif' => $tarif,
        ]);
        return $this->balasForm(true, 'Jenis tenaga "' . $jenis . '" ditambahkan ke master upah.');
    }
}
