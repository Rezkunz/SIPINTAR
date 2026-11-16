<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-01 (Negative) - Bayar langsung angsuran yang sedang dalam paket potongan
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : Potongan & pembayaran langsung dicatat di satu buku -> tidak boleh terjadi pembayaran ganda.
 * Expected : Tombol bayar tidak tersedia, tampil penanda paket potongan; pembayaran paksa ditolak dan tidak tercatat.
 */
class TC_I2_01Test extends Iterasi2DuskTestCase
{
    public function test_bayar_langsung_angsuran_dalam_paket_potongan_ditolak(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $r = $this->buatRencanaPotongan($pinjamanId); // angsuran ke-1 sudah dikirim ke Bendahara Sekolah

        $this->browse(function (Browser $browser) use ($r) {
            $this->loginSebagai($browser, self::BBM)
                ->click('a[href$="/bbm/angsuran/langsung"]')
                ->waitForLocation('/bbm/angsuran/langsung')
                // Tidak ada tombol "Bayar Ke-1", yang tampil penanda paket potongan
                ->assertPresent("#paket-aktif-{$r['jadwal_id']}")
                ->assertSeeIn("#paket-aktif-{$r['jadwal_id']}", 'Dalam Paket Potongan #RP-' . str_pad($r['rencana_id'], 4, '0', STR_PAD_LEFT))
                ->assertMissing("button[onclick^=\"bukaModalBayar({$r['jadwal_id']},\"]")
                ->screenshot('TC-I2-01-penanda');

            // Paksa kirim form pembayaran (bypass UI) -> server tetap menolak
            $this->isi($browser, '#form_jadwal_id', $r['jadwal_id']);
            $this->isi($browser, '#form_bayar_pokok', 500000);
            $this->isi($browser, '#form_bayar_bunga', 100000);
            $browser->script('document.getElementById("formBayarTunai").submit();');

            $pesan = $this->pesanPopup($browser);
            $browser->screenshot('TC-I2-01');
            $this->assertStringContainsString('sedang diproses dalam paket potongan', $pesan);
        });

        $this->assertSame(0, DB::table('tblANGSURAN')->count(), 'Angsuran tidak boleh tercatat dua kali');
        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $r['jadwal_id'], 'status_bayar' => 'belum']);
    }
}
