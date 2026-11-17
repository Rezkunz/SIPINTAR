<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Database\Seeders\PinjamanKasDemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-16 (Positive) - Konsistensi data demo pinjaman & kas
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Data demo modul pinjaman lolos seluruh pemeriksaan `sipintar:audit-pinjaman`
 *            (setiap pencairan/angsuran punya mutasi kas), dan saldo kas di layar sama dengan
 *            saldo awal + penjumlahan mutasi kas.
 */
class TC_I2_16Test extends Iterasi2DuskTestCase
{
    public function test_data_demo_pinjaman_konsisten(): void
    {
        $this->seed(PinjamanKasDemoSeeder::class);

        $kode = Artisan::call('sipintar:audit-pinjaman');
        $this->assertSame(0, $kode, "Audit menemukan ketidaksesuaian:\n" . Artisan::output());

        // Saldo kas tunai = saldo awal DuskSeeder (Rp50.000.000) + neto mutasi
        $neto = (float) DB::table('tblMUTASI_KAS')->where('kas_id', 1)
            ->sum(DB::raw("CASE WHEN arah = 'masuk' THEN nominal ELSE -nominal END"));
        $saldo = 50000000 + $neto;
        $this->assertEquals($saldo, $this->saldoKas('kas'));

        $this->browse(function (Browser $browser) use ($saldo) {
            $this->loginSebagai($browser, self::BBM)
                ->visit('/kas/harian?filter_type=semua')
                ->screenshot('TC-I2-16');
            $this->assertContains(
                'Rp' . number_format($saldo, 0, ',', '.'),
                $browser->script('return [...document.querySelectorAll(".metric-value")].map(e => e.innerText.replace(/\s/g, ""));')[0]
            );
        });
    }
}
