<?php

namespace Tests\Modul_Tabungan;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use PDOException;
use Tests\DuskTestCase;

abstract class TabunganDuskTestCase extends DuskTestCase
{
    protected int $managerId;
    protected int $tellerId;
    protected int $siswaId;
    protected int $siswaLainId;
    protected int $nasabahId;
    protected int $nasabahPendingId;
    protected int $nasabahPendingUserId;
    protected int $periodeId;
    protected string $rekeningAktif;
    protected string $rekeningPegawai;
    protected string $rekeningPending;
    protected string $rekeningBlokir;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->truncateDatabase();
        $this->seedBaseline();
    }

    protected function tearDown(): void
    {
        DB::disconnect();
        DB::purge();
        parent::tearDown();
    }

    protected function truncateDatabase(): void
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                DB::statement('TRUNCATE TABLE
                    "tblAUDIT_LOG",
                    "tblKOREKSI_ANGSURAN",
                    "tblDETAIL_POTONGAN",
                    "tblANGSURAN",
                    "tblJADWAL_ANGSURAN",
                    "tblPENCAIRAN",
                    "tblKELAYAKAN",
                    "tblPINJAMAN",
                    "tblBUKTI",
                    "tblMUTASI_KAS",
                    "tblSALDO_KAS",
                    "tblRENCANA_POTONGAN",
                    "tblTRANSAKSI",
                    "tblDRAFT",
                    "tblREKENING",
                    "tblUSER",
                    "tblNASABAH",
                    "tblPERIODE",
                    "tblKAS",
                    "tblJENIS_TRANSAKSI",
                    "tblROLE",
                    "tblPOS_NERACA"
                    RESTART IDENTITY CASCADE');

                return;
            } catch (QueryException|PDOException $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                DB::disconnect();
                usleep(500000);
                DB::reconnect();
            }
        }
    }

    protected function seedBaseline(): void
    {
        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'teller'],
            ['role_id' => 2, 'nama_role' => 'bendahara'],
            ['role_id' => 3, 'nama_role' => 'bendahara_mini'],
            ['role_id' => 4, 'nama_role' => 'manager'],
            ['role_id' => 5, 'nama_role' => 'siswa_praktik'],
            ['role_id' => 6, 'nama_role' => 'nasabah'],
        ]);

        DB::table('tblJENIS_TRANSAKSI')->insert([
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan'],
        ]);

        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Teller', 'jenis_kas' => 'kas', 'saldo' => 10000000],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank', 'jenis_kas' => 'bank', 'saldo' => 100000000],
        ]);

        $this->nasabahId = DB::table('tblNASABAH')->insertGetId([
            'no_hp' => '081200000001',
            'nama_nasabah' => 'Budi Siswa Aktif',
            'jenis_nasabah' => 'non_pegawai',
            'kelas_unit' => 'XI AKL 1',
            'status_nasabah' => 'aktif',
        ], 'nasabah_id');

        $pegawaiId = DB::table('tblNASABAH')->insertGetId([
            'no_hp' => '081200000002',
            'nama_nasabah' => 'Ibu Guru Aktif',
            'jenis_nasabah' => 'pegawai',
            'kelas_unit' => 'Unit Keuangan',
            'status_nasabah' => 'aktif',
        ], 'nasabah_id');

        $this->nasabahPendingId = DB::table('tblNASABAH')->insertGetId([
            'no_hp' => '081200000003',
            'nama_nasabah' => 'Citra Pending',
            'jenis_nasabah' => 'non_pegawai',
            'kelas_unit' => 'X AKL 2',
            'status_nasabah' => 'aktif',
        ], 'nasabah_id');

        $blokirId = DB::table('tblNASABAH')->insertGetId([
            'no_hp' => '081200000004',
            'nama_nasabah' => 'Dedi Blokir',
            'jenis_nasabah' => 'non_pegawai',
            'kelas_unit' => 'XII AKL 1',
            'status_nasabah' => 'aktif',
        ], 'nasabah_id');

        $year = now()->format('Y');

        $this->rekeningAktif = "1001-{$year}-00001";
        $this->rekeningPegawai = "1001-{$year}-00002";
        $this->rekeningPending = "1001-{$year}-00003";
        $this->rekeningBlokir = "1001-{$year}-00004";

        DB::table('tblREKENING')->insert([
            [
                'no_rek' => $this->rekeningAktif,
                'nasabah_id' => $this->nasabahId,
                'tgl_buka' => now()->toDateString(),
                'saldo' => 1000000,
                'status_rekening' => 'aktif',
            ],
            [
                'no_rek' => $this->rekeningPegawai,
                'nasabah_id' => $pegawaiId,
                'tgl_buka' => now()->toDateString(),
                'saldo' => 2000000,
                'status_rekening' => 'aktif',
            ],
            [
                'no_rek' => $this->rekeningPending,
                'nasabah_id' => $this->nasabahPendingId,
                'tgl_buka' => now()->toDateString(),
                'saldo' => 0,
                'status_rekening' => 'pending',
            ],
            [
                'no_rek' => $this->rekeningBlokir,
                'nasabah_id' => $blokirId,
                'tgl_buka' => now()->toDateString(),
                'saldo' => 50000,
                'status_rekening' => 'blokir',
            ],
        ]);

        $password = Hash::make('sipintar123');
        $now = now();

        $this->managerId = DB::table('tblUSER')->insertGetId([
            'role_id' => 4,
            'nasabah_id' => null,
            'no_hp' => '081200000010',
            'no_induk' => 'MGR-001',
            'email' => 'manager.uji@sipintar.test',
            'password' => $password,
            'nama_user' => 'Manager Uji',
            'status_akun' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'user_id');

        $this->tellerId = DB::table('tblUSER')->insertGetId([
            'role_id' => 1,
            'nasabah_id' => null,
            'no_hp' => '081200000011',
            'no_induk' => 'TLR-001',
            'email' => 'teller.uji@sipintar.test',
            'password' => $password,
            'nama_user' => 'Teller Uji',
            'status_akun' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'user_id');

        $this->siswaId = DB::table('tblUSER')->insertGetId([
            'role_id' => 5,
            'nasabah_id' => null,
            'no_hp' => '081200000012',
            'no_induk' => 'SIS-001',
            'email' => 'siswa.uji@sipintar.test',
            'password' => $password,
            'nama_user' => 'Siswa Uji',
            'status_akun' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'user_id');

        $this->siswaLainId = DB::table('tblUSER')->insertGetId([
            'role_id' => 5,
            'nasabah_id' => null,
            'no_hp' => '081200000013',
            'no_induk' => 'SIS-002',
            'email' => 'siswa.lain@sipintar.test',
            'password' => $password,
            'nama_user' => 'Siswa Lain',
            'status_akun' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'user_id');

        DB::table('tblUSER')->insert([
            'role_id' => 6,
            'nasabah_id' => $this->nasabahId,
            'no_hp' => '081200000001',
            'no_induk' => 'NSB-001',
            'email' => 'nasabah.uji@sipintar.test',
            'password' => $password,
            'nama_user' => 'Budi Siswa Aktif',
            'status_akun' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->nasabahPendingUserId = DB::table('tblUSER')->insertGetId([
            'role_id' => 6,
            'nasabah_id' => $this->nasabahPendingId,
            'no_hp' => '081200000003',
            'no_induk' => 'NSB-002',
            'email' => 'nasabah.pending@sipintar.test',
            'password' => $password,
            'nama_user' => 'Citra Pending',
            'status_akun' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'user_id');

        $this->periodeId = DB::table('tblPERIODE')->insertGetId([
            'user_id' => $this->managerId,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'status_periode' => 'buka',
            'tgl_tutup' => null,
        ], 'periode_id');
    }

    protected function userById(int $id): User
    {
        return User::findOrFail($id);
    }

    protected function disableAutoReload(Browser $browser): Browser
    {
        $js = "window.addEventListener('blur', function(e){e.stopImmediatePropagation();}, true); window.addEventListener('focus', function(e){e.stopImmediatePropagation();}, true); if ('serviceWorker' in navigator) { navigator.serviceWorker.getRegistrations().then(function(regs){ regs.forEach(function(r){ r.unregister(); }); }); }";
        foreach (['/session/:sessionId/chromium/send_command', '/session/:sessionId/goog/cdp/execute'] as $endpoint) {
            try {
                $browser->driver->executeCustomCommand($endpoint, 'POST', [
                    'cmd' => 'Page.addScriptToEvaluateOnNewDocument',
                    'params' => ['source' => $js],
                ]);
                break;
            } catch (\Throwable $e) {
            }
        }
        try {
            $browser->script($js);
        } catch (\Throwable $e) {
        }

        return $browser;
    }

    protected function isi(Browser $browser, string $selector, string|int $nilai): Browser
    {
        $browser->waitUntil(sprintf('document.querySelector(%s) !== null', json_encode($selector)), 15);

        $browser->script(sprintf(
            'const el = document.querySelector(%s); el.focus(); el.value = %s;'
            . 'el.dispatchEvent(new Event("input", {bubbles: true}));'
            . 'el.dispatchEvent(new Event("change", {bubbles: true}));',
            json_encode($selector),
            json_encode((string) $nilai)
        ));

        return $browser;
    }

    protected function loginAsTeller(Browser $browser): Browser
    {
        $this->disableAutoReload($browser);
        $browser->loginAs($this->userById($this->tellerId));

        return $this->disableAutoReload($browser);
    }

    protected function loginAsSiswa(Browser $browser): Browser
    {
        $this->disableAutoReload($browser);
        $browser->loginAs($this->userById($this->siswaId));

        return $this->disableAutoReload($browser);
    }

    protected function loginAsSiswaLain(Browser $browser): Browser
    {
        $this->disableAutoReload($browser);
        $browser->loginAs($this->userById($this->siswaLainId));

        return $this->disableAutoReload($browser);
    }

    protected function dismissPopup(Browser $browser): void
    {
        $browser->pause(150);
        $browser->script('if (window.closeStatusPopup) { window.closeStatusPopup(); }');
        $browser->pause(100);
    }

    protected function createDraft(
        string $noRek,
        string $jenis,
        float $nominal,
        ?int $siswaId = null,
        string $status = 'pending'
    ): int {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $noRek)->value('rekening_id');
        $jenisId = (int) DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', $jenis)->value('jenis_transaksi_id');

        return DB::table('tblDRAFT')->insertGetId([
            'rekening_id' => $rekeningId,
            'jenis_transaksi_id' => $jenisId,
            'siswa_id' => $siswaId ?? $this->siswaId,
            'teller_id' => null,
            'nominal' => $nominal,
            'tgl_input' => now(),
            'status_verifikasi' => $status,
        ], 'draft_id');
    }

    protected function createTransaction(
        string $noRek,
        string $jenis,
        float $nominal,
        float $saldoAkhir,
        ?int $userId = null
    ): int {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $noRek)->value('rekening_id');
        $jenisId = (int) DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', $jenis)->value('jenis_transaksi_id');

        return DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekeningId,
            'jenis_transaksi_id' => $jenisId,
            'periode_id' => $this->periodeId,
            'draft_id' => null,
            'user_id' => $userId ?? $this->tellerId,
            'debit' => $jenis === 'penarikan' ? $nominal : 0,
            'kredit' => in_array($jenis, ['setoran', 'jasa_tabungan'], true) ? $nominal : 0,
            'saldo_akhir' => $saldoAkhir,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');
    }

    protected function markPeriodClosed(): void
    {
        DB::table('tblPERIODE')->where('periode_id', $this->periodeId)->update([
            'status_periode' => 'tutup',
            'tgl_tutup' => now(),
        ]);
    }

    protected function seedCorrectionAudit(int $transaksiId): void
    {
        DB::table('tblAUDIT_LOG')->insert([
            'user_id' => $this->tellerId,
            'aksi' => 'KOREKSI_TRANSAKSI',
            'entitas' => 'tblTRANSAKSI',
            'entitas_id' => $transaksiId,
            'data_lama' => json_encode(['debit' => 0, 'kredit' => 50000, 'saldo_akhir' => 1050000]),
            'data_baru' => json_encode(['reversal_id' => 999999, 'corrected_id' => 999998]),
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
    }
}

