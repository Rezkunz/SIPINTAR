<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-17 (Positive) - Pencarian & halaman pada Data Tunjangan (FR-16)
 * Aktor    : Bendahara Sekolah - bendsekolah@sipintar.test
 * Expected : Tabel menampilkan maksimal 5 pegawai per halaman, sisanya di halaman berikutnya;
 *            pencarian nama/unit menyaring baris; isian di halaman lain tetap ikut tersimpan.
 */
class TC_I2_17Test extends Iterasi2DuskTestCase
{
    private function barisTampil(Browser $browser): array
    {
        return $browser->script('return [...document.querySelectorAll("#tabelTunjangan tbody tr[data-nasabah]")]'
            . '.filter(tr => tr.offsetParent !== null).map(tr => tr.dataset.nasabah);')[0];
    }

    public function test_pencarian_dan_halaman_data_tunjangan(): void
    {
        // 1 pegawai dari DuskSeeder + 7 pegawai tambahan = 8 pegawai -> 2 halaman (5 + 3)
        $ids = [];
        foreach (range(1, 7) as $i) {
            $ids[$i] = DB::table('tblNASABAH')->insertGetId([
                'nama_nasabah'   => "Guru Uji {$i}",
                'no_hp'          => '08120000000' . $i,
                'jenis_nasabah'  => 'pegawai',
                'kelas_unit'     => $i === 6 ? 'Guru Matematika' : 'Guru Produktif',
                'status_nasabah' => 'aktif',
            ], 'nasabah_id');
        }
        $budiId = $this->nasabahId(self::PEGAWAI);

        $this->browse(function (Browser $browser) use ($ids, $budiId) {
            $this->loginSebagai($browser, self::BND_SKL)->visit('/bendahara/tunjangan');

            // Halaman 1: maksimal 5 baris
            $this->assertCount(5, $this->barisTampil($browser));
            $browser->assertSeeIn('#paginasiTunjanganInfo', '1–5')
                ->assertSeeIn('#paginasiTunjanganInfo', '8 pegawai')
                ->screenshot('TC-I2-17-hal1');

            // Halaman 2: 3 sisa pegawai
            $browser->click('#paginasiTunjanganLinks a[data-halaman="2"]');
            $this->assertSame([(string) $ids[5], (string) $ids[6], (string) $ids[7]], $this->barisTampil($browser));
            $browser->assertSeeIn('#paginasiTunjanganInfo', '6–8');
            $this->isi($browser, "#tunjangan-{$ids[7]}", 1200000);

            // Kembali ke halaman 1 dan isi pegawai lain, lalu simpan sekaligus
            $browser->click('#paginasiTunjanganLinks a[data-halaman="1"]');
            $this->isi($browser, "#tunjangan-{$budiId}", 1500000);

            // Pencarian unit kerja
            $browser->type('#cariTunjangan', 'matematika');
            $this->assertSame([(string) $ids[6]], $this->barisTampil($browser));
            $browser->assertSeeIn('#infoCariTunjangan', '1 dari 8 pegawai cocok')
                ->screenshot('TC-I2-17-cari');

            // Pencarian tanpa hasil
            $browser->clear('#cariTunjangan')->type('#cariTunjangan', 'tidak ada orangnya');
            $this->assertCount(0, $this->barisTampil($browser));
            $browser->assertVisible('#tunjTidakDitemukan');

            $browser->press('Simpan Data Tunjangan');
            $this->assertStringContainsString('berhasil disimpan', $this->pesanPopup($browser));
            $browser->screenshot('TC-I2-17');
        });

        $periode = ['tahun' => (int) date('Y'), 'bulan' => (int) date('n')];
        $this->assertDatabaseHas('tblDATA_TUNJANGAN', ['nasabah_id' => $ids[7], 'tunjangan' => 1200000] + $periode);
        $this->assertDatabaseHas('tblDATA_TUNJANGAN', ['nasabah_id' => $budiId, 'tunjangan' => 1500000] + $periode);
    }
}
