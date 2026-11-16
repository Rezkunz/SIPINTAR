<?php

namespace Tests\Modul_Pinjaman;

use App\Services\PinjamanCalculatorService;
use Database\Seeders\DuskSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Basis pengujian UAT modul Pinjaman & Kas SIPINTAR.
 * Setiap test berjalan di database bersih (migrate:fresh + DuskSeeder).
 */
abstract class SipintarDuskTestCase extends DuskTestCase
{
    use DatabaseMigrations;

    protected bool $seed = true;
    protected string $seeder = DuskSeeder::class;

    public const PEGAWAI   = 'pegawai@sipintar.test';
    public const SISWA     = 'nasabah.siswa@sipintar.test';
    public const BBM       = 'bendahara@sipintar.test';
    public const BND_SKL   = 'bendsekolah@sipintar.test';

    /** Login melalui form /login seperti pengguna sebenarnya. */
    protected function loginSebagai(Browser $browser, string $email): Browser
    {
        // Hapus sesi test sebelumnya agar /login tidak langsung redirect ke dashboard
        $browser->visit('/login');
        $browser->driver->manage()->deleteAllCookies();

        $browser->visit('/login')->waitFor('#login');
        $this->isi($browser, '#login', $email);
        $this->isi($browser, '#password', DuskSeeder::PASSWORD);

        return $browser->press('#btn-submit')->waitForLocation('/dashboard', 15);
    }

    /**
     * Isi input lalu picu event input & change supaya handler oninput halaman
     * (simulasi cicilan, dsb.) ikut berjalan. Dipakai sebagai pengganti type()
     * karena keystroke sendKeys kadang hilang pada headless Chrome di Windows.
     */
    protected function isi(Browser $browser, string $selector, string|int $nilai): Browser
    {
        // Tunggu elemen ada di DOM (bukan harus terlihat: input hidden juga didukung)
        $browser->waitUntil(sprintf('document.querySelector(%s) !== null', json_encode($selector)));

        $browser->script(sprintf(
            'const el = document.querySelector(%s); el.value = %s;'
            . 'el.dispatchEvent(new Event("input", {bubbles: true}));'
            . 'el.dispatchEvent(new Event("change", {bubbles: true}));',
            json_encode($selector),
            json_encode((string) $nilai)
        ));

        return $browser;
    }

    /** Pilih radio yang disembunyikan oleh label/pill bergaya custom (event change tetap terpicu). */
    protected function pilihRadio(Browser $browser, string $selector): Browser
    {
        $browser->waitUntil(sprintf('document.querySelector(%s) !== null', json_encode($selector)));
        $browser->script(sprintf('document.querySelector(%s).click();', json_encode($selector)));

        return $browser;
    }

    /** Tutup popup notifikasi sukses/error agar tidak menghalangi klik berikutnya. */
    protected function tutupPopup(Browser $browser): Browser
    {
        return $browser->click('#sipintar-popup-btn')->waitUntilMissing('#sipintar-popup-modal.is-open');
    }

    /** Klik tombol "OK" pada modal konfirmasi global (sipintarConfirm). */
    protected function setujuiKonfirmasi(Browser $browser): Browser
    {
        return $browser->waitFor('#sipintarConfirmBtnOk')->click('#sipintarConfirmBtnOk');
    }

    /** Tunggu popup notifikasi (success/error) lalu kembalikan teksnya. */
    protected function pesanPopup(Browser $browser): string
    {
        $browser->waitFor('#sipintar-popup-modal.is-open', 15);

        return (string) $browser->text('#sipintar-popup-desc');
    }

    protected function userId(string $email): int
    {
        return (int) DB::table('tblUSER')->where('email', $email)->value('user_id');
    }

    protected function nasabahId(string $email): int
    {
        return (int) DB::table('tblUSER')->where('email', $email)->value('nasabah_id');
    }

    protected function namaNasabah(string $email): string
    {
        return (string) DB::table('tblNASABAH')->where('nasabah_id', $this->nasabahId($email))->value('nama_nasabah');
    }

    protected function buatPinjaman(int $nominal = 5000000, int $tenor = 10, string $status = 'diajukan', string $metode = 'potong_gaji'): int
    {
        return (int) DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $this->nasabahId(self::PEGAWAI),
            'user_id'          => $this->userId(self::BBM),
            'nominal_pinjaman' => $nominal,
            'tenor'            => $tenor,
            'bunga_persen'     => 2.00,
            'tgl_pengajuan'    => now()->toDateString(),
            'metode_bayar'     => $metode,
            'status_pinjaman'  => $status,
        ], 'pinjaman_id');
    }

    protected function nilaiLayak(int $pinjamanId): void
    {
        DB::table('tblKELAYAKAN')->insert([
            'pinjaman_id'     => $pinjamanId,
            'user_id'         => $this->userId(self::BBM),
            'penghasilan'     => 6000000,
            'hasil_kelayakan' => 'layak',
            'catatan'         => 'Layak (data uji)',
            'tgl_nilai'       => now()->toDateString(),
        ]);
        DB::table('tblPINJAMAN')->where('pinjaman_id', $pinjamanId)->update(['status_pinjaman' => 'layak']);
    }

    /**
     * Pinjaman berstatus cair beserta jadwal angsurannya.
     * $tglCair default sebulan lalu sehingga angsuran ke-1 jatuh tempo bulan ini.
     * $sudahDibayar = jumlah angsuran awal yang ditandai lunas.
     */
    protected function buatPinjamanCair(int $nominal = 5000000, int $tenor = 10, string $metode = 'potong_gaji', int $sudahDibayar = 0, ?string $tglCair = null): int
    {
        $tglCair ??= now()->subMonthNoOverflow()->toDateString();
        $pinjamanId = $this->buatPinjaman($nominal, $tenor, 'cair', $metode);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id' => $pinjamanId, 'user_id' => $this->userId(self::BBM),
            'tgl_cair' => $tglCair, 'nominal_cair' => $nominal, 'metode_cair' => 'cash',
        ]);

        foreach (PinjamanCalculatorService::generateJadwalAngsuran($pinjamanId, $nominal, $tenor, $tglCair) as $baris) {
            $jadwalId = DB::table('tblJADWAL_ANGSURAN')->insertGetId($baris, 'jadwal_angsuran_id');

            if ($baris['angsuran_ke'] <= $sudahDibayar) {
                DB::table('tblANGSURAN')->insert([
                    'jadwal_angsuran_id' => $jadwalId, 'detail_potongan_id' => null,
                    'user_id' => $this->userId(self::BBM), 'tgl_bayar' => now()->toDateString(),
                    'jenis_bayar' => 'angsuran', 'bayar_pokok' => $baris['pokok'], 'bayar_bunga' => $baris['bunga'],
                ]);
                DB::table('tblJADWAL_ANGSURAN')->where('jadwal_angsuran_id', $jadwalId)->update(['status_bayar' => 'lunas']);
            }
        }

        return $pinjamanId;
    }

    /** Rencana potongan berstatus 'dikirim' untuk angsuran ke-1 pinjaman tertentu. */
    protected function buatRencanaPotongan(int $pinjamanId): array
    {
        $jadwal = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjamanId)->where('angsuran_ke', 1)->first();
        $periodeId = DB::table('tblPERIODE')->where('tahun', (int) date('Y'))->where('bulan', (int) date('n'))->value('periode_id');

        $rencanaId = (int) DB::table('tblRENCANA_POTONGAN')->insertGetId([
            'periode_id' => $periodeId, 'user_id' => $this->userId(self::BBM),
            'total_potongan' => $jadwal->pokok + $jadwal->bunga, 'status_potongan' => 'dikirim',
        ], 'rencana_potongan_id');

        $detailId = (int) DB::table('tblDETAIL_POTONGAN')->insertGetId([
            'rencana_potongan_id' => $rencanaId, 'jadwal_angsuran_id' => $jadwal->jadwal_angsuran_id,
            'nominal_rencana' => $jadwal->pokok + $jadwal->bunga, 'hasil_potongan' => null,
        ], 'detail_potongan_id');

        return ['rencana_id' => $rencanaId, 'detail_id' => $detailId, 'jadwal_id' => $jadwal->jadwal_angsuran_id];
    }

    protected function saldoKas(string $jenis): float
    {
        return (float) DB::table('tblKAS')->where('jenis_kas', $jenis)->value('saldo');
    }
}
