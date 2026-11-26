<?php

namespace App\Controllers;

/**
 * Pembukuan menurut Juknis Revitalisasi SMP 2026: Buku Kas Umum (BKU),
 * Buku Bank, dan Buku Pembantu Kas Tunai. Isi buku adalah gabungan
 * transaksi manual (penerimaan dana, koreksi) dengan pembayaran invoice,
 * pembayaran upah, dan bukti setor yang sudah tercatat di modul lain.
 * Buku ditutup per bulan (saldo awal, mutasi, saldo akhir) dan dapat
 * dicetak mengikuti format dokumen Juknis.
 */
class BukuKas extends BaseController
{
    private function namaBuku(string $buku): string
    {
        return ['bku' => 'Buku Kas Umum', 'bank' => 'Buku Bank', 'tunai' => 'Buku Pembantu Kas Tunai', 'pajak' => 'Buku Pajak'][$buku] ?? 'Buku Kas Umum';
    }

    /** Baris milik Buku Pajak: pungutan (masuk) + setoran pajak (keluar). */
    private function barisPajak(array $e): bool
    {
        return ($e['sumber'] ?? '') === 'pungut'
            || (($e['sumber'] ?? '') === 'setor' && ($e['kategori'] ?? '') === 'pajak');
    }

    private function kanalBuku(string $buku): ?string
    {
        return ['bank' => 'bank', 'tunai' => 'tunai'][$buku] ?? null;
    }

    /**
     * Baris beku (snapshot tutup_buku_baris) untuk bulan tertutup, atau
     * null bila bulan belum ditutup / snapshot belum ada (bulan yang
     * ditutup sebelum snapshot diterapkan dibaca dari data hidup).
     */
    private function snapshotBulan(string $bulan): ?array
    {
        [$tahun, $bln] = array_map('intval', explode('-', $bulan));
        $tb = $this->db()->table('tutup_buku')->where(['sekolah_id' => $this->sid(), 'tahun' => $tahun, 'bulan' => $bln, 'is_deleted' => 0])->get()->getRowArray();
        if (! $tb) {
            return null;
        }
        $rows = $this->db()->table('tutup_buku_baris')->where(['tutup_buku_id' => (int) $tb['id'], 'is_deleted' => 0])->orderBy('nomor', 'ASC')->get()->getResultArray();
        if ($rows === []) {
            return null;
        }
        return ['tutup' => $tb, 'baris' => array_map(static fn ($r) => [
            'nomor' => (int) $r['nomor'], 'tanggal' => $r['tanggal'], 'uraian' => $r['uraian'],
            'masuk' => (float) $r['masuk'], 'keluar' => (float) $r['keluar'],
            'kanal' => $r['kanal'], 'sumber' => $r['sumber'], 'ref_id' => (int) $r['ref_id'],
            'kategori' => $r['kategori'],
        ], $rows)];
    }

    /** Susun baris buku untuk satu bulan: [saldo_awal, baris+saldo berjalan, saldo_akhir]. */
    private function susunBulan(string $buku, string $bulan): array
    {
        $kanal = $this->kanalBuku($buku);
        // Bulan tertutup dibaca dari snapshot beku (baris + nomor bukti
        // tidak berubah walau data sumber kelak dikoreksi).
        $snap = $this->snapshotBulan($bulan);
        if ($snap !== null) {
            $semua = $this->gabungBukuKas($kanal);
            if ($buku === 'pajak') {
                $semua = array_values(array_filter($semua, fn ($e) => $this->barisPajak($e)));
            }
            $awalBulan = $bulan . '-01';
            $saldo = 0.0;
            foreach ($semua as $e) {
                if (($e['tanggal'] ?? $awalBulan) < $awalBulan) {
                    $saldo += $e['masuk'] - $e['keluar'];
                }
            }
            if ($buku === 'bku') {
                $saldo = (float) $snap['tutup']['saldo_awal'];
            }
            $saldoAwal = $saldo;
            $baris = [];
            foreach ($snap['baris'] as $e) {
                if ($kanal !== null && $e['kanal'] !== $kanal) {
                    continue;
                }
                if ($buku === 'pajak' && ! $this->barisPajak($e)) {
                    continue;
                }
                $saldo += $e['masuk'] - $e['keluar'];
                $baris[] = $e + ['saldo' => $saldo];
            }
            return [$saldoAwal, $baris, $saldo];
        }
        $semua = $this->gabungBukuKas($kanal);
        if ($buku === 'pajak') {
            $semua = array_values(array_filter($semua, fn ($e) => $this->barisPajak($e)));
        }
        $awalBulan = $bulan . '-01';
        $akhirBulan = date('Y-m-t', strtotime($awalBulan));
        $saldo = 0.0;
        $baris = [];
        foreach ($semua as $e) {
            $tgl = $e['tanggal'] ?? $awalBulan;
            if ($tgl < $awalBulan) {
                $saldo += $e['masuk'] - $e['keluar'];
            } elseif ($tgl <= $akhirBulan) {
                $saldo += $e['masuk'] - $e['keluar'];
                $baris[] = $e + ['saldo' => $saldo, 'nomor' => count($baris) + 1];
            }
        }
        $saldoAwal = $baris ? $baris[0]['saldo'] - ($baris[0]['masuk'] - $baris[0]['keluar']) : $saldo;
        return [$saldoAwal, $baris, $saldo];
    }

    public function index()
    {
        $buku = (string) ($this->request->getGet('buku') ?? 'bku');
        if (! in_array($buku, ['bku', 'bank', 'tunai', 'pajak'], true)) {
            $buku = 'bku';
        }
        $bulan = (string) ($this->request->getGet('bulan') ?? '');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            // Bawaan: Bulan Terbuka (bulan terkecil yang belum ditutup).
            $bulan = $this->bulanTerbuka()['ym'] ?? date('Y-m');
        }
        [$saldoAwal, $baris, $saldoAkhir] = $this->susunBulan($buku, $bulan);
        $manual = $this->db()->table('kas_transaksi')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])
            ->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        // Data panel Tutup Buku: bulan terbuka, ringkasannya, dan daftar bulan tertutup.
        $bt = $this->bulanTerbuka();
        $ringkasTerbuka = null;
        $belumBayarBulan = 0;
        if ($bt !== null) {
            [$sa, $br, $sk] = $this->susunBulan('bku', $bt['ym']);
            $ringkasTerbuka = [
                'saldo_awal' => $sa, 'saldo_akhir' => $sk, 'jumlah_baris' => count($br),
                'total_masuk' => array_sum(array_column($br, 'masuk')),
                'total_keluar' => array_sum(array_column($br, 'keluar')),
            ];
            $belumBayarBulan = (int) $this->db()->query(
                "SELECT COUNT(*) j FROM invoice i JOIN spk s ON s.id = i.spk_id AND s.is_deleted = 0
                 WHERE s.sekolah_id = ? AND i.is_deleted = 0 AND i.status = 'Belum Bayar' AND i.tanggal LIKE ?",
                [$this->sid(), $bt['ym'] . '%']
            )->getRow()->j;
        }
        // Pilihan transaksi asal untuk jurnal balik koreksi: baris buku
        // pada bulan-bulan yang sudah ditutup.
        $pilihanKoreksi = [];
        $tertutupSemua = $this->petaTutupBuku();
        if ($tertutupSemua !== []) {
            foreach ($this->gabungBukuKas() as $e) {
                $ym = substr((string) ($e['tanggal'] ?? ''), 0, 7);
                $b = (int) substr($ym, 5, 2);
                if (! isset($tertutupSemua[$b]) || sprintf('%04d-%02d', (int) $tertutupSemua[$b]['tahun'], $b) !== $ym) {
                    continue;
                }
                $nilai = ($e['tanggal'] ?? '') . '|' . $e['sumber'] . '|' . (int) $e['ref_id'];
                $pilihanKoreksi[$nilai] = [
                    'label' => tgl($e['tanggal']) . ' — ' . $e['uraian'] . ' (' . ($e['masuk'] > 0 ? 'masuk ' . $this->rp($e['masuk']) : 'keluar ' . $this->rp($e['keluar'])) . ')',
                    'data' => ['jumlah' => (string) (int) ($e['masuk'] > 0 ? $e['masuk'] : $e['keluar']), 'jenis-asal' => $e['masuk'] > 0 ? 'masuk' : 'keluar'],
                ];
            }
        }
        return $this->tampil('bukukas/index', [
            'buku' => $buku, 'nama_buku' => $this->namaBuku($buku), 'bulan' => $bulan,
            'saldo_awal' => $saldoAwal, 'baris' => $baris, 'saldo_akhir' => $saldoAkhir,
            'manual' => $manual, 'pilihan_koreksi' => $pilihanKoreksi,
            'tgl_bayar_default' => $this->tanggalBulanBekerja(),
            'buku_terbuka' => $bt, 'ringkas_terbuka' => $ringkasTerbuka,
            'daftar_tutup' => $this->petaTutupBuku(), 'belum_bayar_bulan' => $belumBayarBulan,
            'spj_belum_lengkap' => $this->bulanTanpaSnapshot(),
            'saya_admin' => ($this->pengguna['peran'] ?? '') === 'admin',
        ] + $this->batasBuku(), $this->namaBuku($buku) . ' — Aplikasi PBJ Sekolah');
    }

    /** Balasan form modal (pola Bahan::balasForm): AJAX -> JSON {ok, pesan}; biasa -> redirect flash. */
    private function balasForm(bool $ok, string $pesan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to('/buku-kas')->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function simpan()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $r = $this->request;
        $uraian = trim((string) $r->getPost('uraian'));
        $jumlah = (float) preg_replace('/[^0-9]/', '', (string) $r->getPost('jumlah'));
        $jenis = (string) $r->getPost('jenis') === 'keluar' ? 'keluar' : 'masuk';
        $kanal = (string) $r->getPost('kanal') === 'tunai' ? 'tunai' : 'bank';
        $kategori = (string) $r->getPost('kategori');
        // Satu pintu per peristiwa uang: penerimaan dicatat di modul
        // Penerimaan, pajak & sisa dana di Bukti Setor — mencatatnya juga
        // sebagai transaksi manual menggandakan BKU.
        if ($kategori === 'penerimaan') {
            return $this->balasForm(false, 'Penerimaan dana bantuan dicatat di modul Penerimaan Dana, bukan sebagai transaksi kas manual — agar tidak tercatat dua kali di Buku Kas.');
        }
        if (in_array($kategori, ['pajak', 'sisa_dana'], true)) {
            return $this->balasForm(false, 'Setoran pajak dan pengembalian sisa dana dicatat di modul Bukti Setor & Pajak, bukan sebagai transaksi kas manual — agar tidak tercatat dua kali di Buku Kas.');
        }
        if (! in_array($kategori, ['lainnya', 'koreksi'], true)) {
            $kategori = 'lainnya';
        }
        $tahap = null;
        // Koreksi bulan tertutup lewat jurnal balik: wajib merujuk
        // transaksi asal di bulan tertutup dan tercatat sebagai lawannya
        // (masuk <-> keluar) di bulan terbuka.
        $koreksiRef = null;
        if ($kategori === 'koreksi') {
            $koreksiRef = trim((string) $r->getPost('koreksi_ref'));
            if ($koreksiRef === '') {
                return $this->balasForm(false, 'Koreksi wajib memilih transaksi asal di bulan tertutup yang dikoreksi.');
            }
            $asal = null;
            foreach ($this->gabungBukuKas() as $e) {
                if (($e['tanggal'] ?? '') . '|' . $e['sumber'] . '|' . (int) $e['ref_id'] === $koreksiRef) {
                    $asal = $e;
                    break;
                }
            }
            if ($asal === null) {
                return $this->balasForm(false, 'Transaksi asal koreksi tidak ditemukan di buku.');
            }
            $tertutup = $this->petaTutupBuku();
            $ymAsal = substr((string) $asal['tanggal'], 0, 7);
            $bulanAsal = (int) substr($ymAsal, 5, 2);
            if (! isset($tertutup[$bulanAsal]) || sprintf('%04d-%02d', (int) $tertutup[$bulanAsal]['tahun'], $bulanAsal) !== $ymAsal) {
                return $this->balasForm(false, 'Transaksi asal koreksi harus berada di bulan yang sudah ditutup; transaksi bulan terbuka dikoreksi langsung di modulnya.');
            }
            // Arah jurnal balik ditentukan server: lawan dari transaksi asal.
            $jenis = ((float) $asal['masuk'] > 0) ? 'keluar' : 'masuk';
            $uraian .= ' (koreksi atas transaksi ' . tgl($asal['tanggal']) . ' — ' . $asal['uraian'] . ')';
        }
        if ($uraian === '' || $jumlah <= 0 || ! $r->getPost('tanggal')) {
            return $this->balasForm(false, 'Tanggal, uraian, dan jumlah wajib diisi.');
        }
        if ($g = $this->galatBulanTertutup((string) $r->getPost('tanggal'))) {
            return $this->balasForm(false, $g);
        }
        // Keluar manual tidak masuk totalBelanja() (bisa berupa koreksi),
        // jadi pagu diterapikan sebagai peringatan, bukan blokir.
        $peringatan = '';
        if ($jenis === 'keluar') {
            [$boleh, $sisaPagu, $anggaran] = $this->cekPagu($jumlah);
            if (! $boleh) {
                $peringatan = ' Perhatian: pengeluaran ini melewati sisa pagu anggaran (' . $this->rp(max(0, $sisaPagu)) . ' dari total ' . $this->rp($anggaran) . ').';
            }
            // Saldo kanal tidak boleh minus pada tanggal mana pun.
            if ($g = $this->galatSaldoMinus($kanal, (string) $r->getPost('tanggal'), $jumlah)) {
                return $this->balasForm(false, $g);
            }
        }
        $this->db()->table('kas_transaksi')->insert([
            'sekolah_id' => $this->sid(),
            'tanggal' => (string) $r->getPost('tanggal'),
            'uraian' => $uraian,
            'kategori' => $kategori,
            'tahap' => $tahap,
            'kanal' => $kanal,
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'koreksi_ref' => $koreksiRef,
            'keterangan' => trim((string) $r->getPost('keterangan')) ?: null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAudit('kas', (int) $this->db()->insertID(), 'buat', 'Transaksi kas manual: ' . $uraian . ' ' . $this->rp($jumlah));
        return $this->balasForm(true, 'Transaksi kas dicatat dan masuk ke buku kas.' . $peringatan);
    }

    public function hapus($id)
    {
        $row = $this->db()->table('kas_transaksi')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if ($row && ($g = $this->galatBulanTertutup($row['tanggal']))) {
            return redirect()->to('/buku-kas')->with('galat', $g);
        }
        $this->hapusLunak('kas_transaksi', ['id' => (int) $id, 'sekolah_id' => $this->sid()]);
        $this->catatAudit('kas', (int) $id, 'hapus', 'Transaksi kas manual dihapus.');
        return redirect()->to('/buku-kas')->with('sukses', 'Transaksi kas dihapus dari buku.');
    }

    public function hapusMassal()
    {
        $n = 0;
        $terkunci = 0;
        foreach ($this->idsTerpilih() as $id) {
            $row = $this->db()->table('kas_transaksi')->where(['id' => $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
            if ($row) {
                if ($this->galatBulanTertutup($row['tanggal'])) {
                    $terkunci++;
                    continue;
                }
                $this->hapusLunak('kas_transaksi', ['id' => $id, 'sekolah_id' => $this->sid()]);
                $n++;
            }
        }
        $pesan = $n > 0 ? $n . ' transaksi kas terpilih dihapus dari buku.' : 'Tidak ada transaksi yang diproses.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' transaksi dilewati karena bukunya sudah ditutup.';
        }
        return redirect()->to('/buku-kas')->with($n > 0 ? 'sukses' : 'galat', $pesan);
    }

    public function kosongkan()
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $this->catatAudit('kas', null, 'kosongkan', 'Modul dikosongkan oleh admin.');
        $rows = $this->db()->table('kas_transaksi')->select('id, tanggal')
            ->where(['sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getResultArray();
        $n = 0;
        $terkunci = 0;
        foreach ($rows as $row) {
            if ($this->galatBulanTertutup($row['tanggal'])) {
                $terkunci++;
                continue;
            }
            $this->hapusLunak('kas_transaksi', ['id' => (int) $row['id'], 'sekolah_id' => $this->sid()]);
            $n++;
        }
        $pesan = 'Transaksi kas manual dikosongkan: ' . $n . ' transaksi dihapus. Baris buku yang berasal dari jurnal pembayaran, upah, dan bukti setor tidak ikut terhapus karena sumbernya di modul masing-masing.';
        if ($terkunci > 0) {
            $pesan .= ' ' . $terkunci . ' transaksi bulan tertutup tidak ikut terhapus.';
        }
        return redirect()->to('/buku-kas')->with('sukses', $pesan);
    }

    /**
     * Tutup Buku bulan terbuka (berurutan Januari–Desember): saldo akhir
     * dibekukan di tabel tutup_buku dan menjadi saldo awal bulan
     * berikutnya; pencatatan berikutnya langsung masuk bulan n+1.
     */
    public function tutupBuku()
    {
        if ($d = $this->penjagaPeran(['bendahara'])) {
            return $d;
        }
        $bt = $this->bulanTerbuka();
        if ($bt === null) {
            return redirect()->to('/buku-kas')->with('galat', 'Semua bulan tahun anggaran ini sudah ditutup.');
        }
        // Daftar periksa rekonsiliasi (pola ARKAS): bendahara menyatakan
        // saldo rekening koran bank, kas fisik hasil opname, serta bunga
        // & biaya bank bulan ini (0 bila tidak ada) sebelum buku ditutup.
        $r = $this->request;
        if ($r->getPost('saldo_koran') === null || $r->getPost('kas_fisik') === null
            || $r->getPost('bunga_bank') === null || $r->getPost('biaya_bank') === null) {
            return redirect()->to('/buku-kas')->with('galat', 'Daftar periksa tutup buku wajib diisi: saldo rekening koran bank, kas fisik hasil opname, bunga bank, dan biaya bank (isi 0 bila tidak ada).');
        }
        $angka = static fn ($v) => (float) preg_replace('/[^0-9]/', '', (string) $v);
        $saldoKoran = $angka($r->getPost('saldo_koran'));
        $kasFisik = $angka($r->getPost('kas_fisik'));
        $bunga = $angka($r->getPost('bunga_bank'));
        $biaya = $angka($r->getPost('biaya_bank'));
        $penjelasan = trim((string) $r->getPost('penjelasan_rekon')) ?: null;
        $now0 = date('Y-m-d H:i:s');
        $tglAkhirBulan = date('Y-m-t', strtotime($bt['ym'] . '-01'));
        // Bunga/biaya yang dinyatakan dicatat sebagai transaksi kas
        // otomatis bila belum tercatat bulan ini (pola ARKAS).
        $sudahAda = fn (string $kat) => (int) $this->db()->query(
            'SELECT COUNT(*) j FROM kas_transaksi WHERE sekolah_id = ? AND kategori = ? AND is_deleted = 0 AND tanggal LIKE ?',
            [$this->sid(), $kat, $bt['ym'] . '%']
        )->getRow()->j > 0;
        if ($bunga > 0 && ! $sudahAda('bunga_bank')) {
            $this->db()->table('kas_transaksi')->insert([
                'sekolah_id' => $this->sid(), 'tanggal' => $tglAkhirBulan,
                'uraian' => 'Bunga bank ' . $bt['label'], 'kategori' => 'bunga_bank', 'tahap' => null,
                'kanal' => 'bank', 'jenis' => 'masuk', 'jumlah' => $bunga,
                'keterangan' => 'Dicatat otomatis dari daftar periksa tutup buku.',
                'created_at' => $now0, 'updated_at' => $now0,
            ]);
        }
        if ($biaya > 0 && ! $sudahAda('biaya_bank')) {
            $this->db()->table('kas_transaksi')->insert([
                'sekolah_id' => $this->sid(), 'tanggal' => $tglAkhirBulan,
                'uraian' => 'Biaya bank ' . $bt['label'], 'kategori' => 'biaya_bank', 'tahap' => null,
                'kanal' => 'bank', 'jenis' => 'keluar', 'jumlah' => $biaya,
                'keterangan' => 'Dicatat otomatis dari daftar periksa tutup buku.',
                'created_at' => $now0, 'updated_at' => $now0,
            ]);
        }
        [$saldoAwal, $baris, $saldoAkhir] = $this->susunBulan('bku', $bt['ym']);
        [, , $saldoBankAkhir] = $this->susunBulan('bank', $bt['ym']);
        [, , $saldoTunaiAkhir] = $this->susunBulan('tunai', $bt['ym']);
        $selisihBank = $saldoKoran - $saldoBankAkhir;
        $selisihTunai = $kasFisik - $saldoTunaiAkhir;
        // Saldo awal mengikuti saldo akhir beku bulan sebelumnya bila ada.
        $tertutup = $this->petaTutupBuku();
        if (isset($tertutup[$bt['bulan'] - 1])) {
            $saldoAwal = (float) $tertutup[$bt['bulan'] - 1]['saldo_akhir'];
            $saldoAkhir = $saldoAwal
                + array_sum(array_column($baris, 'masuk'))
                - array_sum(array_column($baris, 'keluar'));
        }
        $now = date('Y-m-d H:i:s');
        $this->db()->table('tutup_buku')->insert([
            'sekolah_id' => $this->sid(),
            'tahun' => $bt['tahun'], 'bulan' => $bt['bulan'],
            'saldo_awal' => $saldoAwal,
            'total_masuk' => array_sum(array_column($baris, 'masuk')),
            'total_keluar' => array_sum(array_column($baris, 'keluar')),
            'saldo_akhir' => $saldoAkhir,
            'jumlah_baris' => count($baris),
            'saldo_koran' => $saldoKoran, 'kas_fisik' => $kasFisik,
            'bunga_bank' => $bunga, 'biaya_bank' => $biaya,
            'selisih_bank' => $selisihBank, 'selisih_tunai' => $selisihTunai,
            'penjelasan_rekon' => $penjelasan,
            'ditutup_oleh' => $this->pengguna['username'] ?? null,
            'ditutup_pada' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $idTutup = (int) $this->db()->insertID();
        // Bekukan baris bulan ini sebagai snapshot bernomor bukti urut
        // per bulan (tahap 2 rancang ulang keuangan): cetakan bulan
        // tertutup sesudah ini membaca snapshot, bukan data hidup.
        $nomor = 0;
        foreach ($baris as $e) {
            $nomor++;
            $this->db()->table('tutup_buku_baris')->insert([
                'tutup_buku_id' => $idTutup, 'sekolah_id' => $this->sid(),
                'nomor' => $nomor, 'tanggal' => $e['tanggal'] ?? null,
                'uraian' => $e['uraian'], 'masuk' => (float) $e['masuk'], 'keluar' => (float) $e['keluar'],
                'saldo' => (float) $e['saldo'], 'kanal' => $e['kanal'] ?? 'bank',
                'sumber' => $e['sumber'] ?? 'manual', 'ref_id' => (int) ($e['ref_id'] ?? 0),
                'kategori' => $e['kategori'] ?? null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $this->selaraskanBulanBekerja();
        $this->catatAudit('tutup_buku', $idTutup, 'tutup', 'Buku ' . $bt['label'] . ' ditutup; saldo akhir ' . $this->rp($saldoAkhir) . '.');
        $berikut = $this->bulanTerbuka();
        $catatanRekon = '';
        if (abs($selisihBank) > 0.005 || abs($selisihTunai) > 0.005) {
            $catatanRekon = ' Rekonsiliasi: selisih bank ' . $this->rp($selisihBank) . ', selisih tunai ' . $this->rp($selisihTunai) . ' (tersimpan di catatan tutup buku).';
        }
        return redirect()->to('/buku-kas')->with('sukses', 'Buku ' . $bt['label'] . ' ditutup dengan saldo akhir ' . $this->rp($saldoAkhir) . '.' . $catatanRekon
            . ($berikut ? ' Pencatatan berikutnya masuk ' . $berikut['label'] . '.' : ' Seluruh bulan tahun ini sudah tertutup.'));
    }

    /**
     * Buka kembali buku yang sudah ditutup (admin saja, wajib alasan):
     * bulan itu dan bulan-bulan sesudahnya dalam tahun yang sama kembali
     * terbuka dan harus ditutup ulang berurutan.
     */
    public function bukaKembali($id)
    {
        if ($d = $this->penjagaPeran([])) {
            return $d;
        }
        $alasan = trim((string) $this->request->getPost('alasan'));
        if ($alasan === '') {
            return redirect()->to('/buku-kas')->with('galat', 'Alasan membuka kembali buku wajib diisi.');
        }
        $row = $this->db()->table('tutup_buku')->where(['id' => (int) $id, 'sekolah_id' => $this->sid(), 'is_deleted' => 0])->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/buku-kas')->with('galat', 'Catatan tutup buku tidak ditemukan.');
        }
        $now = date('Y-m-d H:i:s');
        // Snapshot bulan yang dibuka kembali ikut dibatalkan agar buku
        // kembali dibaca dari data hidup.
        $idBatal = array_column($this->db()->table('tutup_buku')
            ->select('id')
            ->where(['sekolah_id' => $this->sid(), 'tahun' => (int) $row['tahun'], 'is_deleted' => 0])
            ->where('bulan >=', (int) $row['bulan'])->get()->getResultArray(), 'id');
        if ($idBatal !== []) {
            $this->db()->table('tutup_buku_baris')->whereIn('tutup_buku_id', $idBatal)
                ->update(['is_deleted' => 1, 'deleted_at' => $now, 'updated_at' => $now]);
        }
        $this->db()->table('tutup_buku')
            ->where(['sekolah_id' => $this->sid(), 'tahun' => (int) $row['tahun'], 'is_deleted' => 0])
            ->where('bulan >=', (int) $row['bulan'])
            ->update([
                'dibuka_kembali_oleh' => $this->pengguna['username'] ?? null,
                'dibuka_kembali_pada' => $now,
                'alasan_buka_kembali' => $alasan,
                'is_deleted' => 1, 'deleted_at' => $now, 'updated_at' => $now,
            ]);
        $this->selaraskanBulanBekerja();
        $label = $this->namaBulan((int) $row['bulan']) . ' ' . (int) $row['tahun'];
        $this->catatAudit('tutup_buku', (int) $id, 'buka-kembali', 'Buku ' . $label . ' dibuka kembali. Alasan: ' . $alasan);
        return redirect()->to('/buku-kas')->with('sukses', 'Buku ' . $label . ' dibuka kembali; tutup ulang berurutan sesudah koreksi selesai.');
    }

    /**
     * SPJ Bulanan (tahap 3 rancang ulang keuangan): daftar bukti per
     * bulan tertutup dari snapshot beku, dengan rujukan ke dokumen asal
     * tiap transaksi. Bulan tertutup tanpa snapshot (ditutup sebelum
     * snapshot diterapkan) ditandai belum lengkap.
     */
    public function spj()
    {
        $bulan = (string) ($this->request->getGet('bulan') ?? '');
        $tertutup = $this->petaTutupBuku();
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = $tertutup !== [] ? sprintf('%04d-%02d', (int) end($tertutup)['tahun'], (int) array_key_last($tertutup)) : date('Y-m');
        }
        $snap = $this->snapshotBulan($bulan);
        [$thn, $bln] = array_map('intval', explode('-', $bulan));
        $tutup = $tertutup[$bln] ?? null;
        if ($tutup !== null && (int) $tutup['tahun'] !== $thn) {
            $tutup = null;
        }
        return $this->tampil('bukukas/spj', [
            'bulan' => $bulan, 'daftar_tutup' => $tertutup,
            'tutup' => $tutup, 'baris' => $snap['baris'] ?? [],
            'snapshot_ada' => $snap !== null,
        ], 'SPJ Bulanan — Aplikasi PBJ Sekolah');
    }

    public function cetak()
    {
        $buku = (string) ($this->request->getGet('buku') ?? 'bku');
        if (! in_array($buku, ['bku', 'bank', 'tunai', 'pajak'], true)) {
            $buku = 'bku';
        }
        $bulan = (string) ($this->request->getGet('bulan') ?? '');
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            // Bawaan: Bulan Terbuka (bulan terkecil yang belum ditutup).
            $bulan = $this->bulanTerbuka()['ym'] ?? date('Y-m');
        }
        [$saldoAwal, $baris, $saldoAkhir] = $this->susunBulan($buku, $bulan);
        // Catatan tutup buku (termasuk rekonsiliasi) untuk bulan tertutup.
        [$thn, $bln] = array_map('intval', explode('-', $bulan));
        $tutup = $this->db()->tableExists('tutup_buku')
            ? $this->db()->table('tutup_buku')->where(['sekolah_id' => $this->sid(), 'tahun' => $thn, 'bulan' => $bln, 'is_deleted' => 0])->get()->getRowArray()
            : null;
        return view('cetak/buku_kas', [
            'idn' => $this->sekolahAktif(),
            'nama_buku' => $this->namaBuku($buku), 'bulan' => $bulan,
            'saldo_awal' => $saldoAwal, 'baris' => $baris, 'saldo_akhir' => $saldoAkhir,
            'tutup' => $tutup,
        ]);
    }
}
