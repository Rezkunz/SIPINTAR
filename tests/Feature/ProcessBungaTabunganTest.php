<?php

namespace Tests\Feature;

use App\Models\JenisTransaksi;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessBungaTabunganTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-30 23:00:00');

        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'teller'],
            ['role_id' => 4, 'nama_role' => 'manager'],
        ]);

        JenisTransaksi::create(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        JenisTransaksi::create(['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan']);
        JenisTransaksi::create(['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan']);

        $this->manager = User::create([
            'role_id' => 4,
            'no_hp' => '081234567895',
            'no_induk' => 'MGR-001',
            'email' => 'manager@sipintar.sch.id',
            'password' => bcrypt('password'),
            'nama_user' => 'Manager Test',
            'status_akun' => 'aktif',
        ]);

        DB::table('tblKAS')->insert([
            'kas_id' => 1,
            'nama_kas' => 'Kas Vault Utama',
            'jenis_kas' => 'kas',
            'saldo' => 5000000,
        ]);

        DB::table('tblPERIODE')->insert([
            'periode_id' => 1,
            'tahun' => 2026,
            'bulan' => 11,
            'status_periode' => 'buka',
            'user_id' => $this->manager->user_id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createAccount(string $noRek, string $jenisNasabah, float $saldo, string $statusRekening = 'aktif', string $statusNasabah = 'aktif'): Rekening
    {
        $unique = substr(md5($noRek), 0, 8);
        $nasabah = Nasabah::create([
            'no_hp' => '08' . str_pad((string) rand(10000000, 99999999), 10, '0', STR_PAD_RIGHT),
            'nama_nasabah' => 'Nasabah ' . $noRek,
            'jenis_nasabah' => $jenisNasabah,
            'kelas_unit' => $jenisNasabah === 'pegawai' ? 'Staff TU' : 'XII AKL',
            'status_nasabah' => $statusNasabah,
        ]);

        return Rekening::create([
            'no_rek' => $noRek,
            'nasabah_id' => $nasabah->nasabah_id,
            'tgl_buka' => '2026-01-10',
            'saldo' => $saldo,
            'status_rekening' => $statusRekening,
        ]);
    }

    public function test_command_credits_savings_interest_and_updates_account_balance(): void
    {
        // Siswa: 0.1% dari Rp 1.000.000 = Rp 1.000
        $siswaRek = $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);
        // Pegawai: 0.2% dari Rp 2.000.000 = Rp 4.000
        $pegawaiRek = $this->createAccount('1001-2026-00002', 'pegawai', 2000000);

        $this->artisan('sipintar:process-bunga-tabungan')
            ->expectsOutputToContain('Berhasil Diproses')
            ->assertExitCode(0);

        $this->assertEquals(1001000.0, (float) $siswaRek->fresh()->saldo);
        $this->assertEquals(2004000.0, (float) $pegawaiRek->fresh()->saldo);

        $this->assertDatabaseHas('tblTRANSAKSI', [
            'rekening_id' => $siswaRek->rekening_id,
            'jenis_transaksi_id' => 3,
            'debit' => 0,
            'kredit' => 1000,
            'saldo_akhir' => 1001000,
            'user_id' => $this->manager->user_id,
        ]);

        $this->assertDatabaseHas('tblTRANSAKSI', [
            'rekening_id' => $pegawaiRek->rekening_id,
            'jenis_transaksi_id' => 3,
            'debit' => 0,
            'kredit' => 4000,
            'saldo_akhir' => 2004000,
            'user_id' => $this->manager->user_id,
        ]);

        $this->assertDatabaseHas('tblAUDIT_LOG', [
            'aksi' => 'HITUNG_BUNGA_TABUNGAN',
            'entitas' => 'tblTRANSAKSI',
        ]);
    }

    public function test_command_is_idempotent_running_twice_does_not_duplicate_interest(): void
    {
        $rek = $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);

        $this->artisan('sipintar:process-bunga-tabungan')->assertExitCode(0);
        $this->assertEquals(1001000.0, (float) $rek->fresh()->saldo);

        // Eksekusi kedua di bulan yang sama
        $this->artisan('sipintar:process-bunga-tabungan')
            ->expectsOutputToContain('sudah tercatat pada periode')
            ->assertExitCode(0);

        // Saldo tetap 1.001.000 (tidak menjadi 1.002.000)
        $this->assertEquals(1001000.0, (float) $rek->fresh()->saldo);
        $count = DB::table('tblTRANSAKSI')->where('rekening_id', $rek->rekening_id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_dry_run_option_does_not_write_to_database(): void
    {
        $rek = $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);

        $this->artisan('sipintar:process-bunga-tabungan', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY-RUN]')
            ->assertExitCode(0);

        $this->assertEquals(1000000.0, (float) $rek->fresh()->saldo);
        $this->assertDatabaseMissing('tblTRANSAKSI', [
            'rekening_id' => $rek->rekening_id,
            'jenis_transaksi_id' => 3,
        ]);
        $this->assertDatabaseMissing('tblAUDIT_LOG', [
            'aksi' => 'HITUNG_BUNGA_TABUNGAN',
        ]);
    }

    public function test_zero_balance_or_inactive_accounts_are_skipped(): void
    {
        // Saldo 0
        $rekNol = $this->createAccount('1001-2026-00010', 'non_pegawai', 0);
        // Rekening nonaktif (blokir / tutup)
        $rekBlokir = $this->createAccount('1001-2026-00011', 'non_pegawai', 500000, statusRekening: 'blokir');
        // Nasabah nonaktif
        $rekNasabahNonaktif = $this->createAccount('1001-2026-00012', 'non_pegawai', 500000, statusNasabah: 'nonaktif');

        $this->artisan('sipintar:process-bunga-tabungan')->assertExitCode(0);

        $this->assertEquals(0.0, (float) $rekNol->fresh()->saldo);
        $this->assertEquals(500000.0, (float) $rekBlokir->fresh()->saldo);
        $this->assertEquals(500000.0, (float) $rekNasabahNonaktif->fresh()->saldo);

        $this->assertEquals(0, DB::table('tblTRANSAKSI')->where('jenis_transaksi_id', 3)->count());
    }

    public function test_interest_rounded_to_zero_is_skipped(): void
    {
        // Saldo Rp 100 -> 0.1% = 0.1 -> round = 0
        $rekKecil = $this->createAccount('1001-2026-00020', 'non_pegawai', 100);

        $this->artisan('sipintar:process-bunga-tabungan')
            ->expectsOutputToContain('dibulatkan ke Rp 0')
            ->assertExitCode(0);

        $this->assertEquals(100.0, (float) $rekKecil->fresh()->saldo);
        $this->assertDatabaseMissing('tblTRANSAKSI', [
            'rekening_id' => $rekKecil->rekening_id,
            'jenis_transaksi_id' => 3,
        ]);
    }

    public function test_command_fails_if_period_does_not_exist_or_is_closed(): void
    {
        $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);

        // Kasus 1: Periode belum ada (misal bulan 12/2026)
        Carbon::setTestNow('2026-12-31 23:00:00');
        $this->artisan('sipintar:process-bunga-tabungan')
            ->expectsOutputToContain('tidak ditemukan di tblPERIODE')
            ->assertExitCode(1);

        // Kasus 2: Periode ada tapi berstatus 'tutup'
        DB::table('tblPERIODE')->insert([
            'periode_id' => 2,
            'tahun' => 2026,
            'bulan' => 12,
            'status_periode' => 'tutup',
            'user_id' => $this->manager->user_id,
        ]);

        $this->artisan('sipintar:process-bunga-tabungan')
            ->expectsOutputToContain("berstatus 'tutup'")
            ->assertExitCode(1);

        // Tidak ada transaksi baru
        $this->assertEquals(0, DB::table('tblTRANSAKSI')->count());
    }

    public function test_command_fails_if_master_jasa_tabungan_missing(): void
    {
        $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);
        DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', 'jasa_tabungan')->delete();

        $this->artisan('sipintar:process-bunga-tabungan')
            ->expectsOutputToContain("Master jenis transaksi 'jasa_tabungan' belum tersedia")
            ->assertExitCode(1);

        $this->assertEquals(0, DB::table('tblTRANSAKSI')->count());
    }

    public function test_command_does_not_modify_cash_or_cash_mutation_tables(): void
    {
        $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);

        $kasSebelum = DB::table('tblKAS')->where('kas_id', 1)->value('saldo');
        $mutasiCountSebelum = DB::table('tblMUTASI_KAS')->count();

        $this->artisan('sipintar:process-bunga-tabungan')->assertExitCode(0);

        $kasSesudah = DB::table('tblKAS')->where('kas_id', 1)->value('saldo');
        $mutasiCountSesudah = DB::table('tblMUTASI_KAS')->count();

        $this->assertEquals($kasSebelum, $kasSesudah);
        $this->assertEquals($mutasiCountSebelum, $mutasiCountSesudah);
    }

    public function test_command_with_custom_periode_option(): void
    {
        DB::table('tblPERIODE')->insert([
            'periode_id' => 5,
            'tahun' => 2026,
            'bulan' => 5,
            'status_periode' => 'buka',
            'user_id' => $this->manager->user_id,
        ]);

        $rek = $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);

        $this->artisan('sipintar:process-bunga-tabungan', ['--periode' => '2026-05'])
            ->expectsOutputToContain('periode 2026-05')
            ->assertExitCode(0);

        $this->assertEquals(1001000.0, (float) $rek->fresh()->saldo);
        $this->assertDatabaseHas('tblTRANSAKSI', [
            'rekening_id' => $rek->rekening_id,
            'periode_id' => 5,
            'kredit' => 1000,
        ]);
    }

    public function test_command_with_invalid_periode_format_fails(): void
    {
        $this->artisan('sipintar:process-bunga-tabungan', ['--periode' => '2026/05'])
            ->expectsOutputToContain('tidak valid')
            ->assertExitCode(1);
    }

    public function test_isolated_failure_does_not_prevent_other_accounts_from_processing(): void
    {
        $rek1 = $this->createAccount('1001-2026-00001', 'non_pegawai', 1000000);
        $rek2 = $this->createAccount('1001-2026-00002', 'pegawai', 2000000);

        // Mock InterestPostingService agar rekening 1 gagal tetapi rekening 2 berhasil
        $mockService = $this->partialMock(\App\Services\InterestPostingService::class);
        $mockService->shouldReceive('processAccountInterest')
            ->withArgs(fn ($rekId) => $rekId === $rek1->rekening_id)
            ->andThrow(new \RuntimeException('Koneksi timeout untuk rekening 1'));
        $mockService->makePartial();

        $this->artisan('sipintar:process-bunga-tabungan')
            ->expectsOutputToContain('Koneksi timeout untuk rekening 1')
            ->assertExitCode(0);

        // Rekening 2 tetap berhasil diproses
        $this->assertEquals(2004000.0, (float) $rek2->fresh()->saldo);
        $this->assertDatabaseHas('tblTRANSAKSI', [
            'rekening_id' => $rek2->rekening_id,
            'kredit' => 4000,
        ]);
    }
}
