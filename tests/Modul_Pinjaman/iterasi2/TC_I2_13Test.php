<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-13 (Positive) - Impor CSV & salin data tunjangan bulan lalu (FR-16)
 * Aktor    : Bendahara Sekolah - bendsekolah@sipintar.test
 * Expected : Data dari file CSV tersimpan sesuai isinya; tombol salin bulan lalu mengisi pegawai
 *            yang belum memiliki data bulan berjalan.
 */
class TC_I2_13Test extends Iterasi2DuskTestCase
{
    public function test_impor_csv_data_tunjangan(): void
    {
        $nasabahId = $this->nasabahId(self::PEGAWAI);
        $file = tempnam(sys_get_temp_dir(), 'tunj') . '.csv';
        file_put_contents($file, "nasabah_id;nama_pegawai;unit_kerja;gaji_pokok;tunjangan;potongan_lain;keterangan\n"
            . "{$nasabahId};Budi Pegawai Uji;Guru RPL;4800000;1600000;150000;Impor CSV\n"
            . "999999;Tidak Dikenal;-;1000000;100000;0;\n");

        $this->browse(function (Browser $browser) use ($file) {
            $this->loginSebagai($browser, self::BND_SKL)
                ->visit('/bendahara/tunjangan')
                ->attach('#fileImporTunjangan', $file)
                ->press('#btnImporCsv');
            $pesan = $this->pesanPopup($browser);
            $browser->screenshot('TC-I2-13-impor');
            $this->assertStringContainsString('1 data pegawai tersimpan', $pesan);
            $this->assertStringContainsString('1 baris dilewati', $pesan);
        });
        @unlink($file);

        $this->assertDatabaseHas('tblDATA_TUNJANGAN', [
            'nasabah_id' => $nasabahId, 'tahun' => (int) date('Y'), 'bulan' => (int) date('n'),
            'gaji_pokok' => 4800000, 'tunjangan' => 1600000, 'potongan_lain' => 150000, 'keterangan' => 'Impor CSV',
        ]);
    }

    public function test_salin_data_tunjangan_bulan_lalu(): void
    {
        $nasabahId = $this->nasabahId(self::PEGAWAI);
        $this->isiTunjangan(self::PEGAWAI, 4500000, 1500000, 100000, now()->subMonthNoOverflow());

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::BND_SKL)
                ->visit('/bendahara/tunjangan')
                ->press('#btnSalinBulanLalu')
                ->waitForDialog()
                ->acceptDialog();
            $this->assertStringContainsString('1 data disalin', $this->pesanPopup($browser));
            $browser->screenshot('TC-I2-13');
        });

        $this->assertDatabaseHas('tblDATA_TUNJANGAN', [
            'nasabah_id' => $nasabahId, 'tahun' => (int) date('Y'), 'bulan' => (int) date('n'),
            'gaji_pokok' => 4500000, 'tunjangan' => 1500000, 'potongan_lain' => 100000,
        ]);
        $this->assertSame(2, DB::table('tblDATA_TUNJANGAN')->where('nasabah_id', $nasabahId)->count());
    }
}
