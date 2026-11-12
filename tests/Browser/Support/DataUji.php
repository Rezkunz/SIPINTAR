<?php

namespace Tests\Browser\Support;

use App\Models\User;
use App\Services\TellerTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;

/**
 * Data uji skenario pengujian SIPINTAR (hanya untuk database testing `sipintar_dusk`).
 *
 * Akun uji ketujuh peran (kata sandi semua: sipintar123):
 *   manajer@sipintar.test            Manajer Bank Mini
 *   teller@sipintar.test             Teller
 *   bendahara@sipintar.test          Bendahara Bank Mini
 *   bendahara.sekolah@sipintar.test  Bendahara Sekolah
 *   siswa@sipintar.test              Siswa Praktik
 *   nasabah.siswa@sipintar.test      Nasabah (Siswa)   -> rekening 1001-2026-00001
 *   pegawai@sipintar.test            Nasabah (Pegawai) -> rekening 1001-2026-00005, pinjaman #3 (cair)
 */
trait DataUji
{
    protected const SANDI = 'sipintar123';

    protected const AKUN = [
        'manajer'           => 'manajer@sipintar.test',
        'teller'            => 'teller@sipintar.test',
        'bendahara'         => 'bendahara@sipintar.test',
        'bendahara_sekolah' => 'bendahara.sekolah@sipintar.test',
        'siswa'             => 'siswa@sipintar.test',
        'nasabah_siswa'     => 'nasabah.siswa@sipintar.test',
        'pegawai'           => 'pegawai@sipintar.test',
    ];

    /**
     * Dipanggil setelah migrate:fresh + DatabaseSeeder.
     */
    protected function siapkanDataUji(): void
    {
        $sandi = Hash::make(self::SANDI);

        $petugas = [
            ['role_id' => 4, 'email' => self::AKUN['manajer'],           'nama_user' => 'Manajer Uji',           'no_induk' => 'MGR-901', 'no_hp' => '089000000901'],
            ['role_id' => 1, 'email' => self::AKUN['teller'],            'nama_user' => 'Teller Uji',            'no_induk' => 'TLR-901', 'no_hp' => '089000000902'],
            ['role_id' => 3, 'email' => self::AKUN['bendahara'],         'nama_user' => 'Bendahara Bank Mini Uji', 'no_induk' => 'BBM-901', 'no_hp' => '089000000903'],
            ['role_id' => 2, 'email' => self::AKUN['bendahara_sekolah'], 'nama_user' => 'Bendahara Sekolah Uji', 'no_induk' => 'BND-901', 'no_hp' => '089000000904'],
            ['role_id' => 5, 'email' => self::AKUN['siswa'],             'nama_user' => 'Siswa Praktik Uji',     'no_induk' => '0090000901', 'no_hp' => '089000000905'],
        ];

        foreach ($petugas as $p) {
            DB::table('tblUSER')->insert($p + ['nasabah_id' => null, 'password' => $sandi, 'status_akun' => 'aktif']);
        }

        // Akun nasabah memakai akun nasabah yang sudah terhubung ke rekening
        DB::table('tblUSER')->where('nasabah_id', 1)->update(['email' => self::AKUN['nasabah_siswa'], 'password' => $sandi]);
        DB::table('tblUSER')->where('nasabah_id', 5)->update(['email' => self::AKUN['pegawai'], 'password' => $sandi]);

        $this->catatSaldoAwalRekening();
    }

    /**
     * Rekening contoh dari seeder memiliki saldo tanpa riwayat transaksi. Agar laporan yang
     * dihitung dari riwayat transaksi (mis. Neraca) sama dengan saldo rekening, setiap rekening
     * diberi transaksi "saldo awal" pada 1 September 2026.
     */
    protected function catatSaldoAwalRekening(): void
    {
        $periodeSeptember = DB::table('tblPERIODE')->where(['tahun' => 2026, 'bulan' => 9])->value('periode_id');
        $teller = $this->akun('teller');

        foreach (DB::table('tblREKENING')->where('saldo', '>', 0)->get() as $rek) {
            DB::table('tblTRANSAKSI')->insert([
                'rekening_id'        => $rek->rekening_id,
                'jenis_transaksi_id' => 1,
                'periode_id'         => $periodeSeptember,
                'draft_id'           => null,
                'user_id'            => $teller->user_id,
                'debit'              => 0,
                'kredit'             => $rek->saldo,
                'saldo_akhir'        => $rek->saldo,
                'tgl_transaksi'      => '2026-09-01 08:00:00',
            ]);
        }
    }

    protected function akun(string $peran): User
    {
        return User::where('email', self::AKUN[$peran])->firstOrFail();
    }

    /**
     * Login melalui form login (bukan jalan pintas), lalu tunggu sampai dashboard terbuka.
     */
    protected function loginForm(Browser $browser, string $email, string $sandi = self::SANDI): Browser
    {
        return $browser->visit('/login')
            ->waitFor('#btn-submit')
            ->type('login', $email)
            ->type('password', $sandi)
            ->click('#btn-submit')
            ->waitForLocation('/dashboard');
    }

    /**
     * Logout melalui menu profil di pojok kanan atas.
     */
    protected function logoutMenu(Browser $browser): Browser
    {
        $this->tutupPopup($browser);

        return $browser->click('#topbar-profile-btn')
            ->waitFor('.item-logout')
            ->click('.item-logout')
            ->waitForLocation('/login');
    }

    /**
     * Tutup popup status (SUCCESS/ERROR) bila sedang tampil, dengan menekan tombolnya.
     */
    protected function tutupPopup(Browser $browser): Browser
    {
        $browser->script(
            "const tombol = [...document.querySelectorAll('button')].find(b => ['DONE', 'RETRY'].includes(b.innerText.trim()) && b.offsetParent !== null);"
            . "if (tombol) { tombol.click(); }"
        );

        return $browser->pause(400);
    }

    protected static function rp(float $nilai): string
    {
        return number_format($nilai, 0, ',', '.');
    }

    /**
     * Sisa pokok piutang dihitung langsung dari tabel (perhitungan "manual"):
     * nominal pinjaman cair/lunas dikurangi pokok angsuran sah (tidak dikoreksi).
     */
    protected function hitungSisaPiutangManual(?int $nasabahId = null): float
    {
        $pinjaman = DB::table('tblPINJAMAN as pin')
            ->join('tblPENCAIRAN as cair', 'pin.pinjaman_id', '=', 'cair.pinjaman_id')
            ->whereIn('pin.status_pinjaman', ['cair', 'lunas'])
            ->whereDate('cair.tgl_cair', '<=', today())
            ->when($nasabahId, fn ($q) => $q->where('pin.nasabah_id', $nasabahId))
            ->pluck('pin.nominal_pinjaman', 'pin.pinjaman_id');

        $total = 0.0;
        foreach ($pinjaman as $id => $nominal) {
            $pokok = (float) DB::table('tblANGSURAN as ang')
                ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
                ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
                ->where('ja.pinjaman_id', $id)
                ->whereNull('kor.koreksi_id')
                ->sum('ang.bayar_pokok');
            $total += max(0, (float) $nominal - $pokok);
        }

        return $total;
    }

    /**
     * Transaksi tabungan hari ini melalui layanan Teller (setoran/penarikan tercatat lengkap).
     */
    protected function transaksiTeller(string $noRek, float $nominal, string $jenis): void
    {
        app(TellerTransactionService::class)->storeDirect($this->akun('teller')->user_id, $noRek, $nominal, $jenis);
    }

    /**
     * Nilai pada sel sesudah sel berlabel tertentu dalam tabel laporan.
     */
    protected function nilaiBaris(Browser $browser, string $label): string
    {
        return (string) $browser->script(
            "const td = [...document.querySelectorAll('td')].find(el => el.innerText.trim().startsWith(" . json_encode($label) . "));"
            . "return td && td.nextElementSibling ? td.nextElementSibling.innerText.trim() : '';"
        )[0];
    }

    /**
     * Pilih bulan & tahun pada toolbar laporan (laporan langsung dimuat ulang saat pilihan berubah).
     */
    protected function pilihPeriode(Browser $browser, int $bulan, int $tahun): void
    {
        if ($browser->value('select[name=bulan]') !== (string) $bulan) {
            $browser->waitForReload(fn (Browser $b) => $b->select('bulan', (string) $bulan));
        }
        if ($browser->value('select[name=tahun]') !== (string) $tahun) {
            $browser->waitForReload(fn (Browser $b) => $b->select('tahun', (string) $tahun));
        }
    }

    protected function tungguUnduhan(string $folder, string $ekstensi): string
    {
        for ($i = 0; $i < 40; $i++) {
            $file = glob($folder . DIRECTORY_SEPARATOR . '*.' . $ekstensi);
            if ($file && !glob($folder . DIRECTORY_SEPARATOR . '*.crdownload')) {
                return $file[0];
            }
            usleep(250000);
        }

        $this->fail("File .{$ekstensi} tidak terunduh");
    }
}
