<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PeriodeLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TutupPeriodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;
    protected int $bbm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-02 10:00:00'));

        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'Teller'],
            ['role_id' => 3, 'nama_role' => 'Bendahara Bank Mini'],
            ['role_id' => 4, 'nama_role' => 'Manager'],
            ['role_id' => 5, 'nama_role' => 'Siswa Praktik'],
        ]);

        $user = fn ($role, $hp, $induk, $nama) => User::create([
            'role_id' => $role, 'no_hp' => $hp, 'no_induk' => $induk,
            'nama_user' => $nama, 'password' => bcrypt('password'), 'status_akun' => 'aktif',
        ]);
        $this->manager = $user(4, '081200000004', 'MGR-001', 'Manager Satu');
        $this->teller  = $user(1, '081200000001', 'TLR-001', 'Teller Satu');
        $this->bbm     = $user(3, '081200000003', 'BBM-001', 'Bendahara Bank Mini')->user_id;

        // Saldo kas saat ini sudah mencakup seluruh mutasi
        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 1000000],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank', 'jenis_kas' => 'bank', 'saldo' => 5000000],
        ]);

        DB::table('tblPERIODE')->insert(['periode_id' => 1, 'tahun' => 2026, 'bulan' => 10, 'status_periode' => 'buka']);

        $this->mutasi(1, '2026-10-10', 'masuk', 300000);
        $this->mutasi(1, '2026-10-20', 'keluar', 100000);
        $this->mutasi(1, '2026-11-01', 'masuk', 50000);
    }

    private function mutasi(int $kas, string $tgl, string $arah, float $nominal): void
    {
        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => $kas, 'periode_id' => 1, 'user_id' => $this->bbm, 'tgl_mutasi' => $tgl,
            'arah' => $arah, 'nominal' => $nominal, 'uraian' => 'Uji',
        ]);
    }

    private function tutup(int $tahun, int $bulan, array $tambahan = [])
    {
        return $this->actingAs($this->manager)->post(route('periode.tutup.proses'), array_merge([
            'tahun' => $tahun, 'bulan' => $bulan, 'konfirmasi' => '1',
        ], $tambahan));
    }

    public function test_halaman_default_menampilkan_bulan_lalu_dan_posisi_kas(): void
    {
        $this->actingAs($this->manager)->get(route('periode.tutup'))
            ->assertOk()
            ->assertSee('Periode Oktober 2026')
            ->assertSee('Semua pemeriksaan lolos')
            ->assertSee('750.000')   // saldo awal kas tunai Oktober
            ->assertSee('950.000');  // saldo akhir kas tunai Oktober
    }

    public function test_tutup_periode_membekukan_saldo_dan_mengunci_transaksi(): void
    {
        $this->tutup(2026, 10, ['catatan' => 'Kas fisik cocok'])
            ->assertRedirect(route('periode.tutup', ['tahun' => 2026, 'bulan' => 10]))
            ->assertSessionHas('success');

        $periode = DB::table('tblPERIODE')->where('periode_id', 1)->first();
        $this->assertSame('tutup', $periode->status_periode);
        $this->assertNotNull($periode->tgl_tutup);
        $this->assertEquals($this->manager->user_id, $periode->user_id);

        $saldo = DB::table('tblSALDO_KAS')->where('periode_id', 1)->where('kas_id', 1)->first();
        $this->assertEquals(750000, (float) $saldo->saldo_awal);
        $this->assertEquals(950000, (float) $saldo->saldo_akhir);
        $this->assertEquals(5000000, (float) DB::table('tblSALDO_KAS')->where('periode_id', 1)->where('kas_id', 2)->value('saldo_akhir'));

        // Periode November disiapkan terbuka
        $this->assertSame('buka', DB::table('tblPERIODE')->where('tahun', 2026)->where('bulan', 11)->value('status_periode'));

        $log = DB::table('tblAUDIT_LOG')->where('aksi', 'TUTUP_PERIODE')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Kas fisik cocok', $log->data_baru);

        $this->expectException(RuntimeException::class);
        PeriodeLockService::assertPeriodeTerbuka('2026-10-15');
    }

    public function test_saldo_beku_tidak_berubah_walau_kas_bergerak_setelah_ditutup(): void
    {
        $this->tutup(2026, 10);

        DB::table('tblKAS')->where('kas_id', 1)->increment('saldo', 999999);

        $this->actingAs($this->manager)->get(route('periode.tutup', ['tahun' => 2026, 'bulan' => 10]))
            ->assertOk()
            ->assertSee('950.000')
            ->assertSee('Sudah Ditutup');
    }

    public function test_bulan_berjalan_belum_bisa_ditutup(): void
    {
        $this->tutup(2026, 11)->assertSessionHas('error');

        $this->assertNotSame('tutup', DB::table('tblPERIODE')->where('tahun', 2026)->where('bulan', 11)->value('status_periode'));
    }

    public function test_tutup_buku_harus_berurutan(): void
    {
        DB::table('tblPERIODE')->insert(['periode_id' => 2, 'tahun' => 2026, 'bulan' => 9, 'status_periode' => 'buka']);

        $this->tutup(2026, 10)->assertSessionHas('error');
        $this->assertSame('buka', DB::table('tblPERIODE')->where('periode_id', 1)->value('status_periode'));

        $this->tutup(2026, 9)->assertSessionHas('success');
        $this->tutup(2026, 10)->assertSessionHas('success');
    }

    public function test_draft_siswa_praktik_yang_belum_diverifikasi_menghalangi_tutup_buku(): void
    {
        DB::table('tblJENIS_TRANSAKSI')->insert(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        DB::table('tblNASABAH')->insert(['nasabah_id' => 1, 'no_hp' => '0899', 'nama_nasabah' => 'Siswa', 'jenis_nasabah' => 'non_pegawai']);
        DB::table('tblREKENING')->insert(['rekening_id' => 1, 'no_rek' => '1001-2026-00001', 'nasabah_id' => 1, 'tgl_buka' => '2026-10-01', 'status_rekening' => 'aktif']);
        $siswa = User::create(['role_id' => 5, 'no_hp' => '081200000005', 'no_induk' => '0061234567', 'nama_user' => 'Siswa Praktik', 'password' => bcrypt('x'), 'status_akun' => 'aktif']);
        DB::table('tblDRAFT')->insert([
            'rekening_id' => 1, 'jenis_transaksi_id' => 1, 'siswa_id' => $siswa->user_id,
            'nominal' => 20000, 'tgl_input' => '2026-10-30 09:00:00', 'status_verifikasi' => 'pending',
        ]);

        $this->actingAs($this->manager)->get(route('periode.tutup', ['tahun' => 2026, 'bulan' => 10]))
            ->assertSee('1 draft transaksi tabungan dari Siswa Praktik belum diverifikasi Teller.');

        $this->tutup(2026, 10)->assertSessionHas('error');
        $this->assertSame('buka', DB::table('tblPERIODE')->where('periode_id', 1)->value('status_periode'));
    }

    public function test_konfirmasi_wajib_dicentang(): void
    {
        $this->actingAs($this->manager)
            ->post(route('periode.tutup.proses'), ['tahun' => 2026, 'bulan' => 10])
            ->assertSessionHasErrors('konfirmasi');
    }

    public function test_buka_kembali_periode_tertutup_terakhir_dengan_alasan(): void
    {
        DB::table('tblPERIODE')->insert(['periode_id' => 2, 'tahun' => 2026, 'bulan' => 9, 'status_periode' => 'buka']);
        $this->tutup(2026, 9);
        $this->tutup(2026, 10);

        $buka = fn ($bulan, $alasan) => $this->actingAs($this->manager)->post(route('periode.tutup.buka'), [
            'tahun' => 2026, 'bulan' => $bulan, 'alasan' => $alasan,
        ]);

        $buka(10, 'singkat')->assertSessionHasErrors('alasan');

        // September bukan periode tertutup terakhir
        $buka(9, 'Koreksi angsuran salah tanggal')->assertSessionHas('error');
        $this->assertSame('tutup', DB::table('tblPERIODE')->where('periode_id', 2)->value('status_periode'));

        $buka(10, 'Koreksi angsuran salah tanggal')->assertSessionHas('success');
        $periode = DB::table('tblPERIODE')->where('periode_id', 1)->first();
        $this->assertSame('buka', $periode->status_periode);
        $this->assertNull($periode->tgl_tutup);

        $log = DB::table('tblAUDIT_LOG')->where('aksi', 'BUKA_PERIODE')->first();
        $this->assertStringContainsString('Koreksi angsuran salah tanggal', $log->data_baru);
    }

    public function test_aktivasi_rekening_teller_ditolak_saat_periode_tertutup(): void
    {
        DB::table('tblPERIODE')->insert(['periode_id' => 3, 'tahun' => 2026, 'bulan' => 11, 'status_periode' => 'tutup']);
        DB::table('tblNASABAH')->insert(['nasabah_id' => 1, 'no_hp' => '0899', 'nama_nasabah' => 'Siswa Baru', 'jenis_nasabah' => 'non_pegawai']);
        DB::table('tblREKENING')->insert(['rekening_id' => 1, 'no_rek' => '1001-2026-00001', 'nasabah_id' => 1, 'tgl_buka' => '2026-11-01', 'status_rekening' => 'pending']);

        $this->actingAs($this->teller)
            ->post(route('tabungan.buka-rekening.aktivasi', 1), ['setoran_awal' => 20000])
            ->assertSessionHas('error');

        $this->assertSame('pending', DB::table('tblREKENING')->where('rekening_id', 1)->value('status_rekening'));
        $this->assertSame(0, DB::table('tblTRANSAKSI')->count());
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)->get(route('periode.tutup'))->assertForbidden();
        $this->actingAs($this->teller)->post(route('periode.tutup.proses'), ['tahun' => 2026, 'bulan' => 10, 'konfirmasi' => '1'])->assertForbidden();
    }
}
