<?php

namespace Tests\Database;

use Tests\Support\PbjDatabaseTestCase;
use Tests\Support\TestableInvoice;

/**
 * Kunci invoice (aturan AGENTS.md sejak migrasi 000015): invoice yang
 * sudah memiliki jurnal pembayaran AKTIF tidak dapat diubah/dihapus/
 * dikosongkan — koreksi lewat penghapusan jurnal pembayaran. Predikat
 * kuncinya adalah Invoice::sudahTerbayar().
 */
class KunciInvoiceTest extends PbjDatabaseTestCase
{
    public function testInvoiceTanpaJurnalTidakTerkunci(): void
    {
        $sid = $this->buatSekolah();
        $spk = $this->buatSpk($sid, 1);
        $inv = $this->buatInvoice($spk, 101, 100000);
        $c   = new TestableInvoice();
        $this->assertFalse($c->pubSudahTerbayar($inv));
    }

    public function testInvoiceBerjurnalAktifTerkunci(): void
    {
        $sid = $this->buatSekolah();
        $spk = $this->buatSpk($sid, 1);
        $inv = $this->buatInvoice($spk, 102, 100000);
        $this->buatPembayaran($inv);
        $c = new TestableInvoice();
        $this->assertTrue($c->pubSudahTerbayar($inv));
    }

    public function testJurnalTerhapusLunakMembukaKunci(): void
    {
        $sid = $this->buatSekolah();
        $spk = $this->buatSpk($sid, 1);
        $inv = $this->buatInvoice($spk, 103, 100000);
        $jur = $this->buatPembayaran($inv);
        $this->hapusLunakBaris('pembayaran', $jur);
        $c = new TestableInvoice();
        $this->assertFalse($c->pubSudahTerbayar($inv));
    }

    public function testJurnalInvoiceLainTidakMengunci(): void
    {
        $sid  = $this->buatSekolah();
        $spk  = $this->buatSpk($sid, 1);
        $inv1 = $this->buatInvoice($spk, 104, 100000);
        $inv2 = $this->buatInvoice($spk, 105, 200000);
        $this->buatPembayaran($inv1);
        $c = new TestableInvoice();
        $this->assertTrue($c->pubSudahTerbayar($inv1));
        $this->assertFalse($c->pubSudahTerbayar($inv2));
    }
}
