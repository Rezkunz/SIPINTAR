<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-08 | TC-08-03 (Negative) - Akun siswa mencoba meminjam
 * Aktor    : Nasabah (Siswa) - nasabah.siswa@sipintar.test
 * Expected : Menu tidak tersedia dan akses URL ditolak (pinjaman khusus guru/karyawan).
 */
class TC_08_03Test extends SipintarDuskTestCase
{
    public function test_siswa_tidak_bisa_meminjam(): void
    {
        $this->browse(function (Browser $browser) {
            // 2. Cari menu Pinjaman -> tidak ada
            $this->loginSebagai($browser, self::SISWA)
                ->assertMissing('a[href$="/nasabah/pinjaman/ajukan"]')
                ->assertMissing('a[href$="/nasabah/pinjaman"]')
                ->assertDontSeeLink('Ajukan Pinjaman');

            // 3. Akses langsung URL form pengajuan -> 403
            $browser->visit('/nasabah/pinjaman/ajukan')
                ->assertSee('403')
                ->assertMissing('#formPinjamanNasabah')
                ->screenshot('TC-08-03');
        });

        $this->assertSame(0, DB::table('tblPINJAMAN')->count());
    }
}
