<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Pinjaman\SipintarDuskTestCase;

/**
 * Basis pengujian Iterasi 2 Modul Pinjaman: perbaikan dari feedback UAT
 * Bendahara Bank Mini & Bendahara Sekolah.
 */
abstract class Iterasi2DuskTestCase extends SipintarDuskTestCase
{
    protected function jadwal(int $pinjamanId, int $ke): object
    {
        return DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->where('angsuran_ke', $ke)->first();
    }

    /** Catat mutasi kas manual (data uji) dan perbarui saldo kas terkait. */
    protected function mutasiKas(string $jenisKas, string $arah, float $nominal, string $uraian): void
    {
        $kas = DB::table('tblKAS')->where('jenis_kas', $jenisKas)->first();
        $periodeId = DB::table('tblPERIODE')->where('tahun', (int) date('Y'))->where('bulan', (int) date('n'))->value('periode_id');

        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => $kas->kas_id, 'periode_id' => $periodeId, 'user_id' => $this->userId(self::BBM),
            'transaksi_id' => null, 'angsuran_id' => null, 'pencairan_id' => null,
            'tgl_mutasi' => now()->toDateString(), 'arah' => $arah, 'nominal' => $nominal, 'uraian' => $uraian,
        ]);

        DB::table('tblKAS')->where('kas_id', $kas->kas_id)->increment('saldo', $arah === 'masuk' ? $nominal : -$nominal);
    }

    /** Teks seluruh elemen yang cocok dengan selector. */
    protected function teksSemua(Browser $browser, string $selector): array
    {
        return $browser->script(sprintf(
            'return [...document.querySelectorAll(%s)].map(e => e.innerText.trim());',
            json_encode($selector)
        ))[0];
    }

    /** Buka modal bayar angsuran ke-n di menu Angsuran Langsung lalu konfirmasi terima kas. */
    protected function bayarLangsung(Browser $browser, int $jadwalId): string
    {
        $browser->click('a[href$="/bbm/angsuran/langsung"]')
            ->waitForLocation('/bbm/angsuran/langsung')
            ->click("button[onclick^=\"bukaModalBayar({$jadwalId},\"]")
            ->waitFor('#modalBayarKasir #formBayarTunai')
            ->press('Konfirmasi Terima Kas');

        return $this->pesanPopup($browser);
    }
}
