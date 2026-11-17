<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-10 | TC-10-04 (Negative) - Potongan gagal / tidak terpotong
 * Aktor    : Bendahara Sekolah - bendsekolah@sipintar.test
 * Expected : Angsuran tidak tercatat; status angsuran tetap 'Belum Bayar'/tunggakan.
 */
class TC_10_04Test extends SipintarDuskTestCase
{
    public function test_potongan_gagal_tidak_terpotong(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $r = $this->buatRencanaPotongan($pinjamanId);

        $this->browse(function (Browser $sekolah) use ($r) {
            // Bendahara Sekolah membuka rencana dan mencatat hasil potongan
            $this->loginSebagai($sekolah, self::BND_SKL)
                ->click('a[href$="/bendahara/potongan"]')
                ->waitForLocation('/bendahara/potongan')
                ->click("#btn-eye-{$r['rencana_id']}")
                ->waitFor('#bannerBtnSubmitHasil');
            // 3. Tandai 'Terpotong' (Berhasil) / 'Tidak Terpotong' (Gagal)
            $this->pilihRadio($sekolah, "input[name='hasil[{$r['detail_id']}]'][value='gagal']");
            $this->isi($sekolah, "#ket-{$r['detail_id']}", 'Pegawai cuti di luar tanggungan, gaji tidak cair');
            $sekolah->press('#bannerBtnSubmitHasil')->waitForDialog()->acceptDialog();
            $this->assertStringContainsString('berhasil disimpan', $this->pesanPopup($sekolah));
            $sekolah->screenshot('TC-10-04');
        });

        $this->assertDatabaseHas('tblDETAIL_POTONGAN', ['detail_potongan_id' => $r['detail_id'], 'hasil_potongan' => 'gagal']);
        $this->assertSame(0, DB::table('tblANGSURAN')->count());
        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $r['jadwal_id'], 'status_bayar' => 'belum']);
    }
}
