<?php

namespace Tests\Modul_Dashboard\iterasi2;

use Laravel\Dusk\Browser;
use Tests\Modul_Dashboard\DashboardDuskTestCase;

/**
 * Basis pengujian Iterasi 2 role Manajer Bank Mini (UC-13, UC-14):
 * validasi angka dashboard & laporan terhadap data Teller dan Bendahara Bank Mini
 * sesuai temuan UAT iterasi 1 (Tabel IV-35).
 */
abstract class Iterasi2DashboardTestCase extends DashboardDuskTestCase
{
    /** Hanya digit dari teks elemen, mis. "Rp 1.250.000" -> "1250000". */
    protected function angka(Browser $browser, string $selector): string
    {
        return preg_replace('/\D/', '', $browser->text($selector));
    }

    /** Teks seluruh elemen yang cocok dengan selector. */
    protected function teksSemua(Browser $browser, string $selector): array
    {
        return $browser->script(sprintf(
            'return [...document.querySelectorAll(%s)].map(e => e.innerText.trim());',
            json_encode($selector)
        ))[0];
    }

    /** Nilai kartu metrik berdasarkan judulnya (.metric-title), tanpa spasi, mis. "Rp150.000". */
    protected function metrik(Browser $browser, string $judul): ?string
    {
        return $browser->script(sprintf(
            // textContent: judul kartu ditampilkan kapital lewat CSS sehingga innerText tidak sama dengan teks aslinya
            'const k = [...document.querySelectorAll(".metric-card")].find(c => (c.querySelector(".metric-title")?.textContent || "").trim().toLowerCase() === %s);'
            . 'return k ? k.querySelector(".metric-value").textContent.replace(/\s/g, "") : null;',
            json_encode(mb_strtolower($judul))
        ))[0];
    }

    /** Bayar angsuran secara langsung di menu Angsuran Langsung (Bendahara Bank Mini). */
    protected function bayarLangsung(Browser $browser, int $jadwalId): string
    {
        $browser->visit('/bbm/angsuran/langsung')
            ->click("button[onclick^=\"bukaModalBayar({$jadwalId},\"]")
            ->waitFor('#modalBayarKasir #formBayarTunai')
            ->press('Konfirmasi Terima Kas');

        return $this->pesanPopup($browser);
    }

    /** Unduh berkas dari tautan di halaman (memakai sesi browser) -> [status, content-type, isi biner]. */
    protected function unduh(Browser $browser, string $selector): array
    {
        $href = $browser->attribute($selector, 'href');

        [$status, $tipe, $isi] = $browser->driver->executeAsyncScript(
            'const cb = arguments[arguments.length - 1];'
            . 'fetch(arguments[0]).then(async r => {'
            . '  const buf = new Uint8Array(await r.arrayBuffer()); let s = "";'
            . '  for (let i = 0; i < buf.length; i += 8192) s += String.fromCharCode(...buf.subarray(i, i + 8192));'
            . '  cb([r.status, r.headers.get("content-type"), btoa(s)]);'
            . '}).catch(e => cb([0, "ERROR " + e, ""]));',
            [$href]
        );

        return [$status, (string) $tipe, base64_decode($isi)];
    }
}
