<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-D01 (Positive) - Dana Pihak Ketiga (Tabungan) di Dashboard Manajer sama dengan saldo rekening di Teller
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test, Teller - teller@sipintar.test
 * Temuan   : 2 - Angka dashboard perlu dipastikan sama dengan data Teller.
 * Expected : Dana Pihak Ketiga (Tabungan) = jumlah saldo rekening aktif di Teller; "Dari N rekening aktif" sesuai.
 */
class TC_I2_D01Test extends Iterasi2DashboardTestCase
{
    public function test_dana_pihak_ketiga_dashboard_sama_dengan_saldo_rekening_teller(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->transaksiTabungan(self::PEGAWAI, 150000);
        $siswa = $this->rekening(self::SISWA);
        $pegawai = $this->rekening(self::PEGAWAI);

        $this->browse(function (Browser $browser) use ($siswa, $pegawai) {
            // 1-2. Teller: menu Buka Rekening (Daftar Rekening Aktif), saldo tiap rekening aktif
            $this->loginSebagai($browser, self::TELLER)
                ->click('a[href$="/tabungan/buka-rekening"]')
                ->waitForLocation('/tabungan/buka-rekening')
                ->assertSee($siswa->no_rek)->assertSee('275.000')
                ->assertSee($pegawai->no_rek)->assertSee('150.000');

            // 3-4. Manajer: Dashboard
            $this->loginSebagai($browser, self::MANAJER)
                ->waitFor('@dashboard-simpanan')
                ->assertSee('Dari 2 rekening aktif')
                ->screenshot('TC-I2-D01');

            // 5. Bandingkan: 275.000 + 150.000
            $this->assertSame('425000', $this->angka($browser, '@dashboard-simpanan'));
        });
    }
}
