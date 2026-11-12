<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TutupPeriodeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Setoran awal tabungan saat aktivasi rekening harus tercatat di tblMUTASI_KAS
 * (agar saldo kas per tanggal tepat), tetapi tidak muncul di Buku Kas Bendahara (laporan pinjaman).
 */
class MutasiKasSetoranTabunganTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;
    protected int $bbm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));

        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'Teller'],
            ['role_id' => 3, 'nama_role' => 'Bendahara Bank Mini'],
            ['role_id' => 4, 'nama_role' => 'Manager'],
        ]);
        $user = fn ($role, $hp, $induk) => User::create([
            'role_id' => $role, 'no_hp' => $hp, 'no_induk' => $induk,
            'nama_user' => $induk, 'password' => bcrypt('password'), 'status_akun' => 'aktif',
        ]);
        $this->manager = $user(4, '081200000004', 'MGR-001');
        $this->teller  = $user(1, '081200000001', 'TLR-001');
        $this->bbm     = $user(3, '081200000003', 'BBM-001')->user_id;

        DB::table('tblJENIS_TRANSAKSI')->insert(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 1000000],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank', 'jenis_kas' => 'bank', 'saldo' => 0],
        ]);
        DB::table('tblNASABAH')->insert(['nasabah_id' => 1, 'no_hp' => '0899', 'nama_nasabah' => 'Siswa Baru', 'jenis_nasabah' => 'non_pegawai']);
        DB::table('tblREKENING')->insert(['rekening_id' => 1, 'no_rek' => '1001-2026-00001', 'nasabah_id' => 1, 'tgl_buka' => '2026-11-10', 'status_rekening' => 'pending']);
    }

    private function aktivasi(float $nominal = 50000)
    {
        return $this->actingAs($this->teller)
            ->post(route('tabungan.buka-rekening.aktivasi', 1), ['setoran_awal' => $nominal]);
    }

    public function test_aktivasi_mencatat_mutasi_kas_yang_bertaut_ke_transaksi(): void
    {
        $this->aktivasi()->assertSessionHas('success');

        $transaksi = DB::table('tblTRANSAKSI')->first();
        $mutasi = DB::table('tblMUTASI_KAS')->first();

        $this->assertNotNull($mutasi);
        $this->assertEquals($transaksi->transaksi_id, $mutasi->transaksi_id);
        $this->assertSame('masuk', $mutasi->arah);
        $this->assertEquals(50000, (float) $mutasi->nominal);
        $this->assertEquals(1, $mutasi->kas_id);
        $this->assertStringContainsString('1001-2026-00001', $mutasi->uraian);
        $this->assertEquals(1050000, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));
        $this->assertTrue(DB::table('tblAUDIT_LOG')->where('entitas', 'tblTRANSAKSI')->exists());
    }

    public function test_saldo_kas_bulan_lalu_tidak_lagi_ikut_bertambah(): void
    {
        $this->aktivasi();

        $service = app(TutupPeriodeService::class);
        // Setoran November tidak boleh mengubah saldo kas akhir Oktober
        $this->assertEquals(1000000, $service->saldoKasPada(1, Carbon::parse('2026-10-31')));
        $this->assertEquals(1050000, $service->saldoKasPada(1, Carbon::parse('2026-11-30')));
    }

    public function test_buku_kas_bendahara_tidak_memuat_dana_tabungan(): void
    {
        DB::table('tblPERIODE')->insert(['periode_id' => 1, 'tahun' => 2026, 'bulan' => 11, 'status_periode' => 'buka']);
        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => 1, 'periode_id' => 1, 'user_id' => $this->bbm, 'tgl_mutasi' => '2026-11-05',
            'arah' => 'keluar', 'nominal' => 200000, 'uraian' => 'Honor Pengurus',
        ]);
        DB::table('tblKAS')->where('kas_id', 1)->decrement('saldo', 200000);

        $this->aktivasi();

        $this->actingAs($this->manager)->get(route('laporan.pinjaman.kas', ['bulan' => 11, 'tahun' => 2026, 'sumber' => 'tunai']))
            ->assertOk()
            ->assertSee('Honor Pengurus')
            ->assertDontSee('Setoran awal tabungan')
            ->assertSee('1.000.000')  // saldo awal tanpa dana tabungan
            ->assertSee('800.000');   // saldo akhir = 1.000.000 - 200.000
    }

    public function test_migrasi_sinkron_melengkapi_mutasi_data_lama_tanpa_mengubah_saldo(): void
    {
        $this->aktivasi();
        // Simulasikan data lama: transaksi ada, mutasi kas belum
        DB::table('tblMUTASI_KAS')->delete();

        $migrasi = require database_path('migrations/2026_11_02_000002_sinkron_mutasi_kas_setoran_awal_tabungan.php');
        $migrasi->up();
        $migrasi->up(); // aman dijalankan ulang

        $this->assertSame(1, DB::table('tblMUTASI_KAS')->count());
        $this->assertNotNull(DB::table('tblMUTASI_KAS')->value('transaksi_id'));
        $this->assertEquals(1050000, (float) DB::table('tblKAS')->where('kas_id', 1)->value('saldo'));

        $migrasi->down();
        $this->assertSame(0, DB::table('tblMUTASI_KAS')->count());
    }
}
