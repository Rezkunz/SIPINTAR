<?php

namespace Tests\Modul_Tabungan_Iterasi_2;

require_once __DIR__ . '/../Modul_Tabungan_Iterasi 1/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Tabungan\TabunganDuskTestCase;

class Uc06CetakDokumenFisikTellerTest extends TabunganDuskTestCase
{
    /**
     * TC-06-01 | Positive | Cetak slip setoran dan slip pengambilan (dengan No. KK) valid
     */
    public function test_tc_06_01_cetak_slip_setoran_dan_pengambilan_valid(): void
    {
        $setoranId = $this->createTransaction($this->rekeningAktif, 'setoran', 125000, 1125000);
        $penarikanId = $this->createTransaction($this->rekeningAktif, 'penarikan', 50000, 1075000);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 1075000]);

        $this->browse(function (Browser $browser) use ($setoranId, $penarikanId) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/cetak/slip-setoran/' . $setoranId)
                ->waitForText('SLIP SETORAN', 15)
                ->assertSee('Budi Siswa Aktif')
                ->assertSee($this->rekeningAktif)
                ->assertSee('125.000,-')
                ->assertSee('Seratus dua puluh lima ribu rupiah')
                ->visit('/tabungan/cetak/slip-pengambilan/' . $penarikanId . '?no_kk=KK-001')
                ->waitForText('SLIP PENGAMBILAN', 15)
                ->assertSee('Budi Siswa Aktif')
                ->assertSee($this->rekeningAktif)
                ->assertSee('50.000,-')
                ->assertSee('Lima puluh ribu rupiah')
                ->assertSee('No. KK.')
                ->assertSee('KK-001');
        });
    }

    /**
     * TC-06-02 | Positive | Cetak buku tabungan menampilkan SANDI (S1/S2/K1), offset baris, dan ringkasan total valid
     */
    public function test_tc_06_02_cetak_buku_tabungan_sandi_offset_dan_total_valid(): void
    {
        $setoranId = $this->createTransaction($this->rekeningAktif, 'setoran', 100000, 1100000);
        $this->createTransaction($this->rekeningAktif, 'penarikan', 25000, 1075000);
        $this->seedCorrectionAudit($setoranId);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 1075000]);

        $this->browse(function (Browser $browser) {
            $this->loginAsTeller($browser)
                ->visit('/tabungan/cetak/buku-tabungan?no_rek=' . urlencode($this->rekeningAktif) . '&line_offset=3')
                ->waitForText('Cetak Buku Tabungan', 15)
                ->assertSee($this->rekeningAktif)
                ->assertSee('Budi Siswa Aktif')
                ->assertSee('Offset baris: 3')
                ->assertSee('DEBIT')
                ->assertSee('KREDIT')
                ->assertSee('SALDO')
                ->assertSee('K1')
                ->assertSee('S2')
                ->assertSee('Total Debit')
                ->assertSee('Rp 25.000')
                ->assertSee('Total Kredit')
                ->assertSee('Rp 100.000')
                ->assertSee('Total Keseluruhan (Saldo Akhir)')
                ->assertSee('Rp 1.075.000');
        });
    }
}
