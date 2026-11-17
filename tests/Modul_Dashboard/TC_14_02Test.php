<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-13 | TC-14-02 (Positive) - Laporan gabungan (neraca)
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : Total tiap pos sama dengan laporan manual.
 */
class TC_14_02Test extends DashboardDuskTestCase
{
    public function test_laporan_gabungan_neraca(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->transaksiTabungan(self::PEGAWAI, 150000);
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        // Laporan manual dari data mentah
        $manual = [
            'Kas Tunai'                => $this->saldoKas('kas'),   // 50.425.000
            'Kas Bank'                 => $this->saldoKas('bank'),  // 100.000.000
            'Piutang Pinjaman Pegawai' => 5000000 - (float) DB::table('tblANGSURAN')->sum('bayar_pokok'), // 4.000.000
            'Tabungan Siswa'           => 275000.0,
            'Tabungan Guru / Karyawan' => 150000.0,
        ];

        $this->browse(function (Browser $browser) use ($manual) {
            // 2. Menu Laporan > Neraca & Gabungan
            $this->loginSebagai($browser, self::MANAJER)
                ->click('a[href$="/laporan/neraca"]')
                ->waitForLocation('/laporan/neraca');

            // 3. Pilih periode (bulan berjalan)
            $browser->visit('/laporan/neraca?bulan=' . date('n') . '&tahun=' . date('Y'))
                ->screenshot('TC-14-02');

            // 4. Bandingkan pos-pos dengan laporan manual
            $pos = $this->posNeraca($browser);
            foreach ($manual as $label => $nilai) {
                $this->assertArrayHasKey($label, $pos, "Pos {$label} tidak ditemukan");
                $this->assertSame($this->rp($nilai), $pos[$label], "Nilai pos {$label}");
            }
        });
    }
}
