<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-14 (Positive) - Peringatan tunjangan tidak cukup di Rencana Potongan
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : Nominal potongan bergantung potongan lain di luar Bank Mini (mis. pajak); angsuran yang
 *            tidak dapat dipotong sebaiknya dibayar langsung.
 * Expected : Bila tunjangan bersih < angsuran, baris diberi peringatan "Tidak cukup → sarankan bayar
 *            langsung" dan tidak dicentang otomatis; bila cukup, ditandai "Cukup" dan tercentang.
 */
class TC_I2_14Test extends Iterasi2DuskTestCase
{
    public function test_peringatan_tunjangan_tidak_cukup_pada_rencana_potongan(): void
    {
        // Angsuran ke-1 bulan ini Rp600.000 (potong gaji)
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $jadwal1 = $this->jadwal($pinjamanId, 1);

        // Tunjangan Rp500.000 - pajak Rp100.000 = Rp400.000 < Rp600.000
        $this->isiTunjangan(self::PEGAWAI, 4500000, 500000, 100000);

        $this->browse(function (Browser $browser) use ($jadwal1) {
            $checkbox = "input[name=\"jadwal_ids[]\"][value=\"{$jadwal1->jadwal_angsuran_id}\"]";

            $this->loginSebagai($browser, self::BBM)
                ->visit('/bbm/angsuran/potongan')
                ->assertSeeIn("#tunj-status-{$jadwal1->jadwal_angsuran_id}", 'Rp 400.000')
                ->assertSeeIn("#tunj-status-{$jadwal1->jadwal_angsuran_id}", 'Tidak cukup')
                ->assertNotChecked($checkbox)
                ->screenshot('TC-I2-14-kurang');

            // Bendahara Sekolah memperbarui tunjangan -> cukup
            DB::table('tblDATA_TUNJANGAN')->update(['tunjangan' => 2000000]);

            $browser->refresh()
                ->assertSeeIn("#tunj-status-{$jadwal1->jadwal_angsuran_id}", 'Cukup')
                ->assertChecked($checkbox)
                ->screenshot('TC-I2-14');
        });
    }
}
