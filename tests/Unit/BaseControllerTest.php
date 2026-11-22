<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\TestableBaseController;

/**
 * Satuan teks & pajak di BaseController (tidak menyentuh DB: faktorSatuan
 * diuji dengan id bahan 0 → hanya aturan bawaan/manual, bukan kebutuhan DB).
 */
class BaseControllerTest extends CIUnitTestCase
{
    private TestableBaseController $ctrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ctrl = new TestableBaseController();
    }

    public function testTerbilang(): void
    {
        $this->assertSame('', $this->ctrl->pubTerbilang(0));
        $this->assertSame('Dua Belas', $this->ctrl->pubTerbilang(12));
        $this->assertSame('Dua Puluh Satu', $this->ctrl->pubTerbilang(21));
        $this->assertSame('Seratus', $this->ctrl->pubTerbilang(100));
        $this->assertSame('Seratus Dua Puluh Lima', $this->ctrl->pubTerbilang(125));
        $this->assertSame('Seribu Seratus', $this->ctrl->pubTerbilang(1100));
    }

    public function testRp(): void
    {
        $this->assertSame('Rp 0', $this->ctrl->pubRp(0));
        $this->assertSame('Rp 12.346', $this->ctrl->pubRp(12345.67));
        $this->assertSame('Rp 1.234.567', $this->ctrl->pubRp(1234567));
    }

    public function testKetentuanPbj(): void
    {
        // Nota ≤ Rp10 juta; Kuitansi ≤ Rp50 juta; SPK > Rp50 juta (Juknis 18/2022).
        $this->assertSame(
            ['dokumen' => 'Nota pembelian/pembayaran', 'metode' => 'Pemilihan langsung atau negosiasi harga', 'min_penyedia' => 1, 'materai' => false],
            $this->ctrl->pubKetentuanPbj(1000)
        );
        $this->assertSame(
            ['dokumen' => 'Nota pembelian/pembayaran', 'metode' => 'Pemilihan langsung atau negosiasi harga', 'min_penyedia' => 1, 'materai' => true],
            $this->ctrl->pubKetentuanPbj(5000000)
        );
        $this->assertSame(
            ['dokumen' => 'Kuitansi', 'metode' => 'Pemilihan langsung atau negosiasi harga', 'min_penyedia' => 1, 'materai' => true],
            $this->ctrl->pubKetentuanPbj(10000001)
        );
        $this->assertSame(
            ['dokumen' => 'Kuitansi', 'metode' => 'Pemilihan langsung atau negosiasi harga', 'min_penyedia' => 1, 'materai' => true],
            $this->ctrl->pubKetentuanPbj(50000000)
        );
        $this->assertSame(
            ['dokumen' => 'Surat Perintah Kerja (SPK)', 'metode' => 'Perbandingan harga, kualitas, ketersediaan, dan kemudahan', 'min_penyedia' => 2, 'materai' => true],
            $this->ctrl->pubKetentuanPbj(50000001)
        );
        $this->assertSame(
            ['dokumen' => 'Surat Perintah Kerja (SPK)', 'metode' => 'Perbandingan harga, kualitas, ketersediaan, dan kemudahan', 'min_penyedia' => 3, 'materai' => true],
            $this->ctrl->pubKetentuanPbj(200000002)
        );
    }

    public function testPajakPembelian(): void
    {
        $this->assertSame(['dpp' => 90000.0, 'ppn' => 10000.0, 'pph22' => 0.0, 'dibayar' => 90000.0], $this->ctrl->pubPajakPembelian(100000, 10000));
        $this->assertSame(['dpp' => 2000000.0, 'ppn' => 0.0, 'pph22' => 0.0, 'dibayar' => 2000000.0], $this->ctrl->pubPajakPembelian(2000000, 0));
        $this->assertSame(['dpp' => 5000000.0, 'ppn' => 0.0, 'pph22' => 75000.0, 'dibayar' => 4925000.0], $this->ctrl->pubPajakPembelian(5000000, 0));
        $this->assertSame(['dpp' => 29000000.0, 'ppn' => 1000000.0, 'pph22' => 435000.0, 'dibayar' => 28565000.0], $this->ctrl->pubPajakPembelian(30000000, 1000000));
    }

    public function testPotonganPajak(): void
    {
        $this->assertSame(2000.0, $this->ctrl->pubPotonganPajak('pph23', 100000));
        $this->assertSame(3500.0, $this->ctrl->pubPotonganPajak('pph42-ska', 100000));
        $this->assertSame(6000.0, $this->ctrl->pubPotonganPajak('pph42', 100000));
        $this->assertSame(15000.0, $this->ctrl->pubPotonganPajak('pph21-gol4', 100000));
        $this->assertSame(5000.0, $this->ctrl->pubPotonganPajak('pph21-gol3', 100000));
        $this->assertSame(0.0, $this->ctrl->pubPotonganPajak('pph21-gol2', 100000));
        $this->assertSame(2500.0, $this->ctrl->pubPotonganPajak('pph21-nonpns', 100000));
        $this->assertSame(0.0, $this->ctrl->pubPotonganPajak(null, 100000));
    }

    public function testPph21UpahHarian(): void
    {
        $this->assertSame(0.0, $this->ctrl->pubPph21UpahHarian(450000, 10));
        $this->assertSame(0.0, $this->ctrl->pubPph21UpahHarian(450000, 0));
        $this->assertSame(75000.0, $this->ctrl->pubPph21UpahHarian(600000, 10));
        $this->assertSame(10000.0, $this->ctrl->pubPph21UpahHarian(500000, 4));
    }

    public function testLabelSetor(): void
    {
        $this->assertSame('PPh Pasal 22', $this->ctrl->pubLabelSetor('pph22'));
        $this->assertSame('PPN dipungut', $this->ctrl->pubLabelSetor('ppn'));
        $this->assertSame('PPh Pasal 21', $this->ctrl->pubLabelSetor('pph21'));
        $this->assertSame('PPh Pasal 23', $this->ctrl->pubLabelSetor('pph23'));
        $this->assertSame('PPh Pasal 4 ayat 2', $this->ctrl->pubLabelSetor('pph42'));
        $this->assertSame('Pengembalian sisa dana bantuan (SIMPONI)', $this->ctrl->pubLabelSetor('sisa_dana'));
    }

    public function testOpsiPajakBiayaLain(): void
    {
        $this->assertSame([
            '' => 'Tanpa potongan pajak',
            'pph23' => 'PPh 23 — jasa/sewa (2%)',
            'pph42-ska' => 'PPh 4 ayat 2 — perencana/pengawas ber-SKA (3,5%)',
            'pph42' => 'PPh 4 ayat 2 — perencana/pengawas tanpa SKA (6%)',
            'pph21-gol4' => 'PPh 21 — honor PNS Gol. IV (15%)',
            'pph21-gol3' => 'PPh 21 — honor PNS Gol. III (5%)',
            'pph21-gol2' => 'PPh 21 — honor PNS Gol. II (0%)',
            'pph21-nonpns' => 'PPh 21 — honor non-PNS (5% dari 50%)',
        ], $this->ctrl->pubOpsiPajakBiayaLain());
    }

    public function testFaktorSatuan(): void
    {
        // sk ale komponen → satuan master: 0.02 karena 1 kg = 1/50 zak;
        // faktor sama seperti anggaran AHSP negosiasi→penawaran→RAB.
        $this->assertEqualsWithDelta(0.02, $this->ctrl->pubFaktorSatuan('kg', ['id' => 0, 'nama' => 'Semen', 'satuan' => 'zak']), 1e-9);
        $this->assertEqualsWithDelta(0.02, $this->ctrl->pubFaktorSatuan('kg', ['id' => 0, 'nama' => 'Semen', 'satuan' => '']), 1e-9); // fallback nama 'semen'
        $this->assertEqualsWithDelta(1 / 1400, $this->ctrl->pubFaktorSatuan('kg', ['id' => 0, 'nama' => 'Pasir', 'satuan' => 'm3']), 1e-9);
        $this->assertEqualsWithDelta(1 / 1600, $this->ctrl->pubFaktorSatuan('kg', ['id' => 0, 'nama' => 'Kerikil', 'satuan' => 'm3']), 1e-9);
        $this->assertEqualsWithDelta(1 / 1600, $this->ctrl->pubFaktorSatuan('kg', ['id' => 0, 'nama' => 'Batu', 'satuan' => 'm3']), 1e-9);
        $this->assertEqualsWithDelta(1 / 6, $this->ctrl->pubFaktorSatuan('bh', ['id' => 0, 'nama' => 'Keramik Ubin 40', 'satuan' => 'ktk']), 1e-9);
        $this->assertEqualsWithDelta(1 / 11, $this->ctrl->pubFaktorSatuan('bh', ['id' => 0, 'nama' => 'Keramik Ubin 30', 'satuan' => 'ktk']), 1e-9);
        $this->assertEqualsWithDelta(1 / 16, $this->ctrl->pubFaktorSatuan('bh', ['id' => 0, 'nama' => 'Keramik Ubin 25', 'satuan' => 'ktk']), 1e-9);
        $this->assertEqualsWithDelta(1 / 25, $this->ctrl->pubFaktorSatuan('bh', ['id' => 0, 'nama' => 'Keramik Ubin 20', 'satuan' => 'ktk']), 1e-9);
        $this->assertSame(1.0, $this->ctrl->pubFaktorSatuan('bh', ['id' => 0, 'nama' => 'Cat', 'satuan' => 'bh']));
        $this->assertSame(1.0, $this->ctrl->pubFaktorSatuan(null, ['id' => 0, 'nama' => 'X']));
    }
}
