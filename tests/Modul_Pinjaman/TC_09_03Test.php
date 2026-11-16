<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-09 | TC-09-03 (Negative) - Mencairkan tanpa penilaian
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Expected : Sistem menolak; pencairan hanya bisa setelah dinilai layak.
 */
class TC_09_03Test extends SipintarDuskTestCase
{
    public function test_mencairkan_tanpa_penilaian_ditolak(): void
    {
        $pinjamanId = $this->buatPinjaman(5000000, 10, 'diajukan');

        $this->browse(function (Browser $browser) use ($pinjamanId) {
            // 2-3. Pengajuan belum dinilai tidak muncul di daftar pencairan (tidak ada tombol Cairkan)
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/pinjaman/cair"]')
                ->waitForLocation('/bbm/pinjaman/cair')
                ->assertMissing("button[onclick^=\"bukaModalPencairan({$pinjamanId},\"]");

            // Paksa kirim form pencairan untuk ID tersebut (bypass tombol) -> server harus menolak
            $browser->script(sprintf(
                'const f = document.getElementById("formEksekusiPencairan"); f.action = %s; f.submit();',
                json_encode(url("/bbm/pinjaman/cair/{$pinjamanId}"))
            ));

            $this->assertStringContainsString('Hanya pinjaman yang sudah berstatus Layak', $this->pesanPopup($browser));
            $browser->screenshot('TC-09-03');
        });

        $this->assertDatabaseHas('tblPINJAMAN', ['pinjaman_id' => $pinjamanId, 'status_pinjaman' => 'diajukan']);
        $this->assertSame(0, DB::table('tblPENCAIRAN')->count());
        $this->assertSame(0, DB::table('tblJADWAL_ANGSURAN')->count());
    }
}
