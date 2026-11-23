<?php

namespace Tests\Database;

use Tests\Support\PbjDatabaseTestCase;
use Tests\Support\TestableBaseController;

/**
 * Penomoran SPK: nomor berikutnya = MAX(nomor) seluruh baris sekolah
 * itu TERMASUK yang terhapus lunak, ditambah 1 (aturan AGENTS.md —
 * kunci unik spk_sekolah_nomor tetap dipegang baris terhapus, jadi
 * menghitung dari baris aktif saja menyebabkan tabrakan nomor sesudah
 * ada SPK yang dihapus). Nomor bersifat per sekolah.
 */
class NomorSpkTest extends PbjDatabaseTestCase
{
    public function testSekolahBaruMulaiDariSatu(): void
    {
        $sid = $this->buatSekolah();
        $c   = new TestableBaseController();
        $this->assertSame(1, $c->pubNomorSpkBerikutnya($sid));
    }

    public function testNaikMengikutiNomorTerbesar(): void
    {
        $sid = $this->buatSekolah();
        $this->buatSpk($sid, 1);
        $this->buatSpk($sid, 2);
        $this->buatSpk($sid, 7);
        $c = new TestableBaseController();
        $this->assertSame(8, $c->pubNomorSpkBerikutnya($sid));
    }

    public function testSpkTerhapusLunakTetapMengunciNomornya(): void
    {
        $sid  = $this->buatSekolah();
        $this->buatSpk($sid, 1);
        $spk2 = $this->buatSpk($sid, 2);
        $this->hapusLunakBaris('spk', $spk2);
        $c = new TestableBaseController();
        // Bukan 2: nomor 2 tetap terpegang baris terhapus (kunci unik).
        $this->assertSame(3, $c->pubNomorSpkBerikutnya($sid));
        // Dan nomor hasil hitungan itu benar-benar bisa disisipkan.
        $this->buatSpk($sid, $c->pubNomorSpkBerikutnya($sid));
        $this->assertSame(4, $c->pubNomorSpkBerikutnya($sid));
    }

    public function testNomorTerisolasiPerSekolah(): void
    {
        $sidA = $this->buatSekolah();
        $sidB = $this->buatSekolah();
        $this->buatSpk($sidA, 1);
        $this->buatSpk($sidA, 2);
        $this->buatSpk($sidA, 3);
        $c = new TestableBaseController();
        $this->assertSame(4, $c->pubNomorSpkBerikutnya($sidA));
        $this->assertSame(1, $c->pubNomorSpkBerikutnya($sidB));
    }
}
