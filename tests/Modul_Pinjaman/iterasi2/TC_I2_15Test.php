<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-15 (Positive) - Notifikasi tugas tertunda modul pinjaman
 * Aktor    : Bendahara Bank Mini, Bendahara Sekolah, Nasabah (Pegawai)
 * Expected : Angka lonceng sama dengan jumlah tugas tertunda sesuai data, tiap item mengarah ke menu
 *            terkait, dan saat tidak ada tugas tampil "Tidak ada tugas tertunda" tanpa angka.
 */
class TC_I2_15Test extends Iterasi2DuskTestCase
{
    private function itemNotif(Browser $browser): array
    {
        return $browser->script('return [...document.querySelectorAll("#topbar-notif-dropdown [data-notif]")].map(e => e.dataset.notif);')[0];
    }

    public function test_notifikasi_sesuai_data_tiap_peran(): void
    {
        $this->browse(function (Browser $browser) {
            // Tidak ada tugas: lonceng tanpa angka
            $this->loginSebagai($browser, self::BBM)
                ->assertMissing('#topbar-notif-badge')
                ->click('#topbar-notif-btn')
                ->waitFor('#notif-kosong')
                ->assertSeeIn('#notif-kosong', 'Tidak ada tugas tertunda');

            // Pengajuan baru masuk (nasabah pegawai mengajukan)
            $pinjamanId = $this->buatPinjaman(5000000, 10, 'diajukan');

            $browser->refresh()->assertSeeIn('#topbar-notif-badge', '1');
            $this->assertSame(['pengajuan-baru'], $this->itemNotif($browser));
            $browser->click('#topbar-notif-btn')
                ->waitFor('[data-notif="pengajuan-baru"]')
                ->screenshot('TC-I2-15-bbm')
                ->click('[data-notif="pengajuan-baru"]')
                ->waitForLocation('/bbm/pinjaman/kelayakan');

            // Nasabah pegawai melihat status pengajuannya
            $this->loginSebagai($browser, self::PEGAWAI)->assertSeeIn('#topbar-notif-badge', '1');
            $this->assertSame(['status-diajukan'], $this->itemNotif($browser));

            // Setelah dinilai layak: BBM mendapat tugas pencairan, nasabah melihat "disetujui"
            $this->nilaiLayak($pinjamanId);
            $browser->refresh();
            $this->assertSame(['status-layak'], $this->itemNotif($browser));

            $this->loginSebagai($browser, self::BBM);
            $this->assertSame(['menunggu-cair'], $this->itemNotif($browser));

            // Bendahara Sekolah: paket baru + data tunjangan bulan ini belum diisi
            $cairId = $this->buatPinjamanCair(3000000, 6, 'potong_gaji');
            $this->buatRencanaPotongan($cairId);
            $this->loginSebagai($browser, self::BND_SKL)->assertSeeIn('#topbar-notif-badge', '2');
            $this->assertSame(['paket-baru', 'tunjangan-belum'], $this->itemNotif($browser));
            $browser->click('#topbar-notif-btn')->waitFor('[data-notif="paket-baru"]')->screenshot('TC-I2-15');
        });

        $this->assertSame(1, DB::table('tblRENCANA_POTONGAN')->where('status_potongan', 'dikirim')->count());
    }
}
