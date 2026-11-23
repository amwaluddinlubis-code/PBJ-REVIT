<?php

namespace Tests\Support;

use App\Controllers\BaseController;

/**
 * Proxy BaseController protected-helper test (tidak menyentuh DB):
 * akses logika yang dilindungi `protected` melalui method public,
 * agar pengujian unit tetap deterministik dan bebas konfigurasi DB.
 */
class TestableBaseController extends BaseController
{
    public function pubTerbilang($n): string
    {
        return $this->terbilang($n);
    }

    public function pubRp($n): string
    {
        return $this->rp($n);
    }

    public function pubKetentuanPbj(float $nilai): array
    {
        return $this->ketentuanPbj($nilai);
    }

    public function pubPajakPembelian(float $nilai, float $ppnTermasuk): array
    {
        return $this->pajakPembelian($nilai, $ppnTermasuk);
    }

    public function pubPotonganPajak(?string $jenis, float $jumlah): float
    {
        return $this->potonganPajak($jenis, $jumlah);
    }

    public function pubPph21UpahHarian(float $tarifPerHari, float $orangHari): float
    {
        return $this->pph21UpahHarian($tarifPerHari, $orangHari);
    }

    public function pubLabelSetor(string $jenis): string
    {
        return $this->labelSetor($jenis);
    }

    public function pubOpsiPajakBiayaLain(): array
    {
        return $this->opsiPajakBiayaLain();
    }

    public function pubFaktorSatuan(?string $satuanKomponen, array $bahan): float
    {
        return $this->faktorSatuan($satuanKomponen, $bahan);
    }

    // --- Proxy mesin uang (butuh database tes grup 'tests') ---

    public function pubHargaBerjalan(array $p, ?int $sid = null): float
    {
        return $this->hargaBerjalan($p, $sid);
    }

    public function pubPetaHargaAhsp(?int $sid = null): array
    {
        return $this->petaHargaAhsp($sid);
    }

    public function pubHargaAhspKode(?string $kode, ?int $sid = null): ?float
    {
        return $this->hargaAhspKode($kode, $sid);
    }

    public function pubTotalBoqGedung(int $gedungId): float
    {
        return $this->totalBoqGedung($gedungId);
    }

    public function pubTotalBoqAktif(?int $sid = null): float
    {
        return $this->totalBoqAktif($sid);
    }

    public function pubVerifikasiAnggaran(?int $sid = null): array
    {
        return $this->verifikasiAnggaran($sid);
    }

    public function pubNomorSpkBerikutnya(?int $sid = null): int
    {
        return $this->nomorSpkBerikutnya($sid);
    }

    public function pubSid(): int
    {
        return $this->sid();
    }
}
