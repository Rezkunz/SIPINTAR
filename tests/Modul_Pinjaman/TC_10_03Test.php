<?php

namespace Tests\Modul_Pinjaman;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * UC-10 | TC-10-03 (Positive) - Mencatat hasil potongan berhasil
 * Aktor    : Bendahara Sekolah - bendsekolah@sipintar.test
 * Expected : Angsuran otomatis tercatat dan sisa piutang berkurang.
 */
class TC_10_03Test extends SipintarDuskTestCase
{
    public function test_mencatat_hasil_potongan_berhasil(): void
    {
        $pinjamanId = $this->buatPinjamanCair(5000000, 10, 'potong_gaji');
        $r = $this->buatRencanaPotongan($pinjamanId);

        $this->browse(function (Browser $sekolah, Browser $bbm) use ($r, $pinjamanId) {
            // Bendahara Sekolah membuka rencana dan mencatat hasil potongan
            $this->loginSebagai($sekolah, self::BND_SKL)
                ->click('a[href$="/bendahara/potongan"]')
                ->waitForLocation('/bendahara/potongan')
                ->click("#btn-eye-{$r['rencana_id']}")
                ->waitFor('#bannerBtnSubmitHasil');
            // 3. Tandai 'Terpotong' (Berhasil) / 'Tidak Terpotong' (Gagal)
            $this->pilihRadio($sekolah, "input[name='hasil[{$r['detail_id']}]'][value='berhasil']");
            $this->isi($sekolah, '#modalInputCatatan', 'Potongan payroll berhasil');
            $sekolah->press('#bannerBtnSubmitHasil')->waitForDialog()->acceptDialog();
            $this->assertStringContainsString('berhasil disimpan', $this->pesanPopup($sekolah));
            $sekolah->screenshot('TC-10-03-sekolah');

            // 5. Cek kartu piutang (login Bendahara Bank Mini).
            // Sistem membukukan angsuran setelah Bank Mini mengonfirmasi penerimaan dana.
            $this->loginSebagai($bbm, self::BBM)
                ->click('a[href$="/bbm/angsuran/potongan"]')
                ->waitForLocation('/bbm/angsuran/potongan')
                ->click("#btn-eye-{$r['rencana_id']}")
                ->waitFor('#bannerBtnSubmitFooter')
                ->press('#bannerBtnSubmitFooter')
                ->waitForDialog()
                ->acceptDialog();
            $this->pesanPopup($bbm);

            $bbm->visit("/bbm/kartu?pinjaman_id={$pinjamanId}")
                ->assertSee('4.500.000') // sisa pokok 5.000.000 - 500.000
                ->screenshot('TC-10-03');
        });

        $this->assertDatabaseHas('tblDETAIL_POTONGAN', ['detail_potongan_id' => $r['detail_id'], 'hasil_potongan' => 'berhasil']);
        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $r['jadwal_id'], 'status_bayar' => 'lunas']);
        $this->assertSame(1, DB::table('tblANGSURAN')->where('jadwal_angsuran_id', $r['jadwal_id'])->count());
    }
}
