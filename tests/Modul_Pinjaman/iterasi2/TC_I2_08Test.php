<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-08 (Positive) - Data terbaru tampil tanpa refresh manual
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : Saat UAT pengguna dua kali diminta "di-refresh dulu" agar data terbaru muncul.
 * Expected : Kembali ke halaman sebelumnya (tombol Back) langsung menampilkan data terbaru; halaman dikirim dengan Cache-Control no-store.
 */
class TC_I2_08Test extends Iterasi2DuskTestCase
{
    public function test_data_terbaru_tampil_tanpa_refresh(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'tunai');
        $jadwal1 = $this->jadwal($pinjamanId, 1);

        $this->browse(function (Browser $browser) use ($pinjamanId, $jadwal1) {
            // Buka kartu piutang dulu (sisa 5.000.000)
            $this->loginSebagai($browser, self::BBM)
                ->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('5.000.000');

            $cacheControl = $browser->driver->executeAsyncScript(
                'const cb = arguments[arguments.length - 1];'
                . 'fetch(location.href).then(r => cb(r.headers.get("cache-control") || "")).catch(() => cb(""));'
            );
            $this->assertStringContainsString('no-store', $cacheControl);

            // Bayar angsuran ke-1 lalu kembali dengan tombol Back (tanpa refresh)
            $this->assertStringContainsString('berhasil dicatat', $this->bayarLangsung($browser, $jadwal1->jadwal_angsuran_id));
            $browser->back()->back()
                ->waitForLocation('/bbm/kartu')
                ->waitForText('4.500.000')
                ->screenshot('TC-I2-08');
        });
    }
}
