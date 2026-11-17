<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-09 | TC-09-05 (Positive) - Validasi jadwal dengan kartu piutang
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Angka angsuran pokok, jasa, dan sisa piutang sama dengan perhitungan manual.
 */
class TC_09_05Test extends SipintarDuskTestCase
{
    public function test_validasi_jadwal_dengan_kartu_piutang(): void
    {
        // Pinjaman Rp5.000.000 / 10 bulan, sudah dibayar 2 kali
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        // Perhitungan manual (Kartu Piutang Individu): flat 2%/bulan dari plafon
        $pokok = 5000000 / 10;         // 500.000
        $jasa  = 5000000 * 0.02;       // 100.000
        $total = $pokok + $jasa;       // 600.000
        $sisa  = 5000000 - 2 * $pokok; // 4.000.000
        $rp = fn ($n) => number_format($n, 0, ',', '.');

        $this->browse(function (Browser $browser) use ($pinjamanId, $pokok, $jasa, $total, $sisa, $rp) {
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/kartu"]')
                ->waitForLocation('/bbm/kartu')
                ->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('Rp ' . $rp($pokok))
                ->assertSee('Rp ' . $rp($jasa))
                ->assertSee('Rp ' . $rp($total))
                ->assertSee($rp(2 * $pokok))  // pokok terbayar
                ->assertSee($rp($sisa))       // sisa pokok piutang
                ->screenshot('TC-09-05');
        });
    }
}
