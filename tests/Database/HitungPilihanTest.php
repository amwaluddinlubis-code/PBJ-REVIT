<?php

namespace Tests\Database;

use Tests\Support\PbjDatabaseTestCase;
use Tests\Support\TestableRencana;

/**
 * Rencana::hitungPilihan() — mesin kebutuhan Rencana Belanja (dipakai
 * juga Rencana Mingguan dan Simulasi lewat pewarisan).
 *
 * Yang dijaga: kebutuhan kotor = volume rencana x koefisien komponen
 * (dengan konversi satuan), kebutuhan bersih (sisa) = kotor dikurangi
 * yang sudah dipesan pada SPK aktif sekolah itu, dan kebutuhan tenaga
 * (orang-hari serta jumlah pekerja = ceil(OH / target hari)).
 */
class HitungPilihanTest extends PbjDatabaseTestCase
{
    private int $sid;
    private int $bahanId;
    private int $pekerjaanId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sid     = $this->buatSekolah();
        $gedung        = $this->buatGedung($this->sid);
        $this->bahanId = $this->buatBahan($this->sid, 'HP-01', 'Semen Uji', 'zak', 50000);
        $upahId        = $this->buatUpahMaster($this->sid, 'Pekerja', 100000);
        $analisaId     = $this->buatAnalisa($this->sid, 'A-HP', [
            // Bahan: koef 2 zak per satuan pekerjaan, tarif komponen 50.000
            ['bahan', 'Semen Uji', 'zak', 2.0, 50000, $this->bahanId, null],
            // Upah: koef 0,4 OH; tarif master 100.000 mengalahkan komponen 90.000
            ['upah', 'Pekerja', 'OH', 0.4, 90000, null, $upahId],
        ]);
        $this->pekerjaanId = $this->buatPekerjaan($gedung, [
            'analisa_id'   => $analisaId,
            'kode_analisa' => 'A-HP',
            'volume'       => 10,
            'harga_satuan' => 140000,
        ]);
        session()->set('sekolah_id', $this->sid);
    }

    private function cariBahan(array $hasil, int $bahanId): ?array
    {
        foreach ($hasil['kebutuhan']['tertaut'] as $b) {
            if ((int) $b['bahan_id'] === $bahanId) {
                return $b;
            }
        }
        return null;
    }

    public function testKebutuhanKotorDariVolumeKaliKoefisien(): void
    {
        $c      = new TestableRencana();
        $hasil  = $c->pubHitungPilihan([$this->pekerjaanId => 10.0], [], 5);
        $bahan  = $this->cariBahan($hasil, $this->bahanId);
        $this->assertNotNull($bahan);
        $this->assertSame(20.0, (float) $bahan['qty_butuh']); // 10 x koef 2
        $this->assertSame(0.0, (float) $bahan['dipesan']);
        $this->assertSame(20.0, (float) $bahan['sisa']);
    }

    public function testSisaDikurangiPesananSpkAktif(): void
    {
        $spk = $this->buatSpk($this->sid, 1);
        $this->buatSpkDetail($spk, $this->bahanId, 5);
        $c     = new TestableRencana();
        $hasil = $c->pubHitungPilihan([$this->pekerjaanId => 10.0], [], 5);
        $bahan = $this->cariBahan($hasil, $this->bahanId);
        $this->assertSame(5.0, (float) $bahan['dipesan']);
        $this->assertSame(15.0, (float) $bahan['sisa']);
    }

    public function testSpkTerhapusTidakMengurangiSisa(): void
    {
        $spk = $this->buatSpk($this->sid, 1);
        $this->buatSpkDetail($spk, $this->bahanId, 5);
        $this->hapusLunakBaris('spk', $spk);
        $c     = new TestableRencana();
        $hasil = $c->pubHitungPilihan([$this->pekerjaanId => 10.0], [], 5);
        $bahan = $this->cariBahan($hasil, $this->bahanId);
        $this->assertSame(0.0, (float) $bahan['dipesan']);
        $this->assertSame(20.0, (float) $bahan['sisa']);
    }

    public function testSisaTidakPernahNegatif(): void
    {
        $spk = $this->buatSpk($this->sid, 1);
        $this->buatSpkDetail($spk, $this->bahanId, 50); // dipesan > butuh
        $c     = new TestableRencana();
        $hasil = $c->pubHitungPilihan([$this->pekerjaanId => 10.0], [], 5);
        $bahan = $this->cariBahan($hasil, $this->bahanId);
        $this->assertSame(0.0, (float) $bahan['sisa']);
    }

    public function testKebutuhanTenagaDanJumlahPekerja(): void
    {
        $c     = new TestableRencana();
        $hasil = $c->pubHitungPilihan([$this->pekerjaanId => 10.0], [], 2);
        $this->assertCount(1, $hasil['upah']);
        $u = $hasil['upah'][0];
        $this->assertSame('Pekerja', $u['jenis']);
        $this->assertSame(4.0, (float) $u['orang_hari']);       // 10 x koef 0,4
        $this->assertSame(100000.0, (float) $u['tarif']);       // master mengalahkan komponen
        $this->assertSame(2, (int) $u['jumlah_pekerja']);       // ceil(4 / 2 hari)
        $this->assertSame(400000.0, (float) $u['nilai']);       // 4 OH x 100.000
    }

    public function testRingkasanAnalisaMemakaiHargaAhsp(): void
    {
        $c     = new TestableRencana();
        $hasil = $c->pubHitungPilihan([$this->pekerjaanId => 10.0], [], 5);
        $this->assertCount(1, $hasil['analisa']);
        $a = $hasil['analisa'][0];
        // AHSP = 2 x 50.000 (bahan) + 0,4 x 100.000 (upah master) = 140.000
        $this->assertSame(140000.0, (float) $a['harga']);
        $this->assertSame(1400000.0, (float) $a['nilai']); // 10 x 140.000
    }
}
