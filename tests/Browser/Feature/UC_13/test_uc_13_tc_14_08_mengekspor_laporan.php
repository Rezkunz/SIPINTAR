<?php

namespace Tests\Browser\Feature\UC_13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\TestDox;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Browser\Support\SkenarioTestCase;

/**
 * UC-13 TC-14-08 Mengekspor laporan
 * Test Case: Ekspor laporan ke PDF/Excel
 */
class test_uc_13_tc_14_08_mengekspor_laporan extends SkenarioTestCase
{
    #[TestDox('UC-13 TC-14-08 Mengekspor laporan')]
    public function test_uc_13_tc_14_08_mengekspor_laporan(): void
    {
        $this->transaksiTeller('1001-2026-00001', 100000, 'setoran');
        $folder = static::folderUnduhan();

        $this->browse(function (Browser $browser) use ($folder) {
            $this->loginForm($browser, self::AKUN['manajer'])
                ->visit('/laporan/tabungan/bulanan?bulan=' . now()->month . '&tahun=' . now()->year)
                ->assertSee('1001-2026-00001');

            $layar = $browser->script("return document.querySelector('table').innerText;")[0];

            $browser->click('a.lap-unduh-btn.pdf');
            $pdf = $this->tungguUnduhan($folder, 'pdf');
            $browser->click('a.lap-unduh-btn.excel');
            $xlsx = $this->tungguUnduhan($folder, 'xlsx');

            // File PDF valid
            $this->assertStringStartsWith('%PDF', file_get_contents($pdf, false, null, 0, 4));

            // Isi Excel sama dengan tampilan: setiap nomor rekening & nominal di Excel tampil di layar
            $isiExcel = collect(IOFactory::load($xlsx)->getActiveSheet()->toArray(null, true, false))->flatten()->filter()->implode(' ');
            $this->assertStringContainsString('1001-2026-00001', $isiExcel);
            $saldo = (float) DB::table('tblREKENING')->where('no_rek', '1001-2026-00001')->value('saldo');
            $this->assertStringContainsString(self::rp($saldo), $layar);
            $this->assertMatchesRegularExpression('/\b' . (int) $saldo . '(\.0+)?\b/', $isiExcel);
        });
    }
}
