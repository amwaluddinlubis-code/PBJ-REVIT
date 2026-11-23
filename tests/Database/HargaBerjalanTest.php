<?php

namespace Tests\Database;

use Tests\Support\PbjDatabaseTestCase;
use Tests\Support\TestableBaseController;

/**
 * Mesin harga berjalan — dasar penilaian BOQ seluruh aplikasi.
 *
 * Urutan dasar yang dijaga (AGENTS.md): harga revisi disahkan >
 * harga AHSP (jumlah koefisien x tarif komponen terkini; tarif upah
 * memakai yang terbesar antara tarif komponen dan Master Upah) >
 * harga satuan dokumen. Volume berjalan memakai volume_revisi bila ada.
 */
class HargaBerjalanTest extends PbjDatabaseTestCase
{
    /**
     * Analisa contoh: bahan koef 2 x tarif 1.000 = 2.000;
     * upah koef 0,5 x tarif master 8.000 (mengalahkan tarif komponen
     * 5.000) = 4.000. Harga AHSP total = 6.000.
     */
    private function analisaContoh(int $sid, string $kode): int
    {
        $bahanId = $this->buatBahan($sid, 'HB-' . $kode, 'Bahan ' . $kode, 'bh', 1000);
        $upahId  = $this->buatUpahMaster($sid, 'Pekerja', 8000);
        return $this->buatAnalisa($sid, $kode, [
            ['bahan', 'Bahan ' . $kode, 'bh', 2.0, 1000, $bahanId, null],
            ['upah', 'Pekerja', 'OH', 0.5, 5000, null, $upahId],
        ]);
    }

    public function testHargaRevisiMengalahkanAhspDanDokumen(): void
    {
        $sid = $this->buatSekolah();
        $aid = $this->analisaContoh($sid, 'HR1');
        $c   = new TestableBaseController();
        $p   = ['harga_revisi' => 7500, 'analisa_id' => $aid, 'kode_analisa' => 'HR1', 'harga_satuan' => 9999];
        $this->assertSame(7500.0, $c->pubHargaBerjalan($p, $sid));
    }

    public function testHargaAhspDariAnalisaIdDenganTarifUpahTerbesar(): void
    {
        $sid = $this->buatSekolah();
        $aid = $this->analisaContoh($sid, 'HR2');
        $c   = new TestableBaseController();
        $this->assertSame(6000.0, $c->pubHargaAhspKode('HR2', $sid));
        $p = ['harga_revisi' => null, 'analisa_id' => $aid, 'kode_analisa' => 'HR2', 'harga_satuan' => 9999];
        $this->assertSame(6000.0, $c->pubHargaBerjalan($p, $sid));
    }

    public function testHargaAhspFallbackKodeBilaAnalisaIdKosong(): void
    {
        $sid = $this->buatSekolah();
        $this->analisaContoh($sid, 'HR3');
        $c = new TestableBaseController();
        $p = ['harga_revisi' => null, 'analisa_id' => null, 'kode_analisa' => 'HR3', 'harga_satuan' => 9999];
        $this->assertSame(6000.0, $c->pubHargaBerjalan($p, $sid));
    }

    public function testHargaDokumenBilaTanpaAnalisa(): void
    {
        $sid = $this->buatSekolah();
        $c   = new TestableBaseController();
        $p   = ['harga_revisi' => null, 'analisa_id' => null, 'kode_analisa' => 'TIDAK-ADA', 'harga_satuan' => 4321];
        $this->assertSame(4321.0, $c->pubHargaBerjalan($p, $sid));
        $this->assertNull($c->pubHargaAhspKode('TIDAK-ADA', $sid));
    }

    public function testTarifUpahFallbackNamaMaster(): void
    {
        $sid = $this->buatSekolah();
        $this->buatUpahMaster($sid, 'Tukang', 9000);
        // Komponen upah TANPA upah_id: master dicari lewat nama jenis.
        $this->buatAnalisa($sid, 'HR5', [
            ['upah', 'Tukang', 'OH', 1.0, 1000, null, null],
        ]);
        $c = new TestableBaseController();
        $this->assertSame(9000.0, $c->pubHargaAhspKode('HR5', $sid));
    }

    public function testPetaHargaTerisolasiPerSekolah(): void
    {
        $sidA = $this->buatSekolah();
        $sidB = $this->buatSekolah();
        $this->analisaContoh($sidA, 'SAMA');
        $this->buatAnalisa($sidB, 'SAMA', [
            ['bahan', 'Bahan B', 'bh', 1.0, 111, null, null],
        ]);
        $c = new TestableBaseController();
        $this->assertSame(6000.0, $c->pubHargaAhspKode('SAMA', $sidA));
        $this->assertSame(111.0, $c->pubHargaAhspKode('SAMA', $sidB));
    }

    public function testKomponenTerhapusTidakIkutHargaAhsp(): void
    {
        $sid = $this->buatSekolah();
        $aid = $this->buatAnalisa($sid, 'HR7', [
            ['bahan', 'Bahan Aktif', 'bh', 1.0, 2000, null, null],
            ['bahan', 'Bahan Buang', 'bh', 1.0, 5000, null, null],
        ]);
        $komponenBuang = (int) $this->db->table('analisa_komponen')
            ->where(['analisa_id' => $aid, 'nama' => 'Bahan Buang'])->get()->getRow()->id;
        $this->hapusLunakBaris('analisa_komponen', $komponenBuang);
        $c = new TestableBaseController();
        $this->assertSame(2000.0, $c->pubHargaAhspKode('HR7', $sid));
    }

    public function testTotalBoqGedungMemakaiVolumeDanHargaBerjalan(): void
    {
        $sid = $this->buatSekolah();
        $aid = $this->analisaContoh($sid, 'HR8');
        $g   = $this->buatGedung($sid);
        // Vol revisi 4 (mengalahkan vol awal 10) x AHSP 6.000 = 24.000
        $this->buatPekerjaan($g, ['analisa_id' => $aid, 'kode_analisa' => 'HR8', 'volume' => 10, 'volume_revisi' => 4, 'harga_satuan' => 9999]);
        // Tanpa analisa: 3 x harga dokumen 2.000 = 6.000
        $this->buatPekerjaan($g, ['volume' => 3, 'harga_satuan' => 2000]);
        // Terhapus lunak: tidak ikut total.
        $p3 = $this->buatPekerjaan($g, ['volume' => 100, 'harga_satuan' => 1000]);
        $this->hapusLunakBaris('pekerjaan', $p3);
        // totalBoqGedung() bekerja dalam konteks sekolah aktif (sesi).
        session()->set('sekolah_id', $sid);
        $c = new TestableBaseController();
        $this->assertSame(30000.0, $c->pubTotalBoqGedung($g));
    }

    public function testVerifikasiAnggaranHanyaGedungDidanai(): void
    {
        $sid = $this->buatSekolah(100000);
        $g1  = $this->buatGedung($sid, 'Gedung Didanai', 1);
        $g2  = $this->buatGedung($sid, 'Gedung Tidak Didanai', 0);
        $this->buatPekerjaan($g1, ['volume' => 3, 'harga_satuan' => 10000]);
        $this->buatPekerjaan($g2, ['volume' => 5, 'harga_satuan' => 10000]);
        $this->buatBiayaLain($sid, 5000);
        $c = new TestableBaseController();
        $v = $c->pubVerifikasiAnggaran($sid);
        $this->assertSame(30000.0, $v['total_boq']);
        $this->assertSame(5000.0, $v['biaya_lain']);
        $this->assertSame(35000.0, $v['total_semua']);
        $this->assertSame(65000.0, $v['selisih']);
        $this->assertSame(1, $v['gedung']);
        $this->assertSame(2, $v['gedung_semua']);
    }
}
