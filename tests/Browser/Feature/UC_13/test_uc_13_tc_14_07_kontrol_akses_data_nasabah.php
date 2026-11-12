<?php

namespace Tests\Browser\Feature\UC_13;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-07 Kontrol akses data nasabah
 * Test Case: Mengakses rekening milik nasabah lain
 */
class test_uc_13_tc_14_07_kontrol_akses_data_nasabah extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-07 Kontrol akses data nasabah')]
    public function test_uc_13_tc_14_07_kontrol_akses_data_nasabah(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginForm($browser, self::AKUN['nasabah_siswa'])
                ->visit('/nasabah/saldo')
                ->assertSee('1001-2026-00001');

            // Mengganti identitas rekening di URL portal nasabah tetap menampilkan rekening sendiri
            $browser->visit('/nasabah/saldo?no_rek=1001-2026-00002&rekening_id=2')
                ->assertSee('1001-2026-00001')
                ->assertDontSee('1001-2026-00002')
                ->assertDontSee('Supriyadi');

            // Membuka URL mutasi rekening nasabah lain (halaman petugas) ditolak
            $browser->visit('/tabungan/mutasi?no_rek=1001-2026-00002')
                ->assertSee('Halaman Tidak Dapat Diakses')
                ->assertDontSee('Supriyadi');
        });
    }
}
