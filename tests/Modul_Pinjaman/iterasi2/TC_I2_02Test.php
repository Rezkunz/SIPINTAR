<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-02 (Positive) - Potongan gagal dialihkan ke pembayaran langsung
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test
 * Feedback : "Kalau tidak bisa dipotong, dibayar langsung; pencatatan tetap di satu buku, yang beda hanya metodenya."
 * Expected : Angsuran yang potongannya gagal dapat dibayar langsung dan tercatat di kartu piutang yang sama dengan label metode "Langsung".
 */
class TC_I2_02Test extends Iterasi2DuskTestCase
{
    public function test_potongan_gagal_dibayar_langsung_di_buku_yang_sama(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $r = $this->buatRencanaPotongan($pinjamanId);

        // Bendahara Sekolah sudah melaporkan potongan angsuran ke-1 GAGAL
        DB::table('tblDETAIL_POTONGAN')->where('detail_potongan_id', $r['detail_id'])
            ->update(['hasil_potongan' => 'gagal', 'nominal_realisasi' => 0, 'keterangan' => 'Tunjangan belum cair']);
        DB::table('tblRENCANA_POTONGAN')->where('rencana_potongan_id', $r['rencana_id'])
            ->update(['status_potongan' => 'menunggu_verifikasi', 'total_realisasi' => 0]);

        $this->browse(function (Browser $browser) use ($r, $pinjamanId) {
            $this->loginSebagai($browser, self::BBM);
            $browser->visit('/bbm/angsuran/langsung')->assertMissing("#paket-aktif-{$r['jadwal_id']}");

            $pesan = $this->bayarLangsung($browser, $r['jadwal_id']);
            $this->assertStringContainsString('berhasil dicatat', $pesan);

            // Tercatat di kartu piutang yang sama, metode "Langsung"
            $browser->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('4.500.000')
                ->screenshot('TC-I2-02');
            $this->assertContains('Langsung', $this->teksSemua($browser, 'td span.badge-pill'));
        });

        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $r['jadwal_id'], 'status_bayar' => 'lunas']);
        $this->assertDatabaseHas('tblANGSURAN', ['jadwal_angsuran_id' => $r['jadwal_id'], 'detail_potongan_id' => null, 'bayar_pokok' => 500000]);
    }
}
