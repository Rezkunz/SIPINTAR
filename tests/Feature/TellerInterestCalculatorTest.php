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

class TellerInterestCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;
    protected User $siswaPraktik;
    protected User $nasabahUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-08 10:00:00');

        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'teller'],
            ['role_id' => 2, 'nama_role' => 'bendahara'],
            ['role_id' => 3, 'nama_role' => 'bendahara_mini'],
            ['role_id' => 4, 'nama_role' => 'manager'],
            ['role_id' => 5, 'nama_role' => 'siswa_praktik'],
            ['role_id' => 6, 'nama_role' => 'nasabah'],
        ]);

        JenisTransaksi::create(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        JenisTransaksi::create(['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan']);
        JenisTransaksi::create(['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan']);

        DB::table('tblPERIODE')->insert([
            'periode_id' => 1,
            'tahun' => 2026,
            'bulan' => 11,
            'status_periode' => 'buka',
            'user_id' => null,
        ]);

        $this->teller = User::create([
            'role_id' => 1,
            'no_hp' => '081234567801',
            'no_induk' => 'TLR-001',
            'email' => 'teller@sipintar.sch.id',
            'password' => bcrypt('password'),
            'nama_user' => 'Siti Teller',
            'status_akun' => 'aktif',
        ]);

        $this->siswaPraktik = User::create([
            'role_id' => 5,
            'no_hp' => '081234567805',
            'no_induk' => '0061234567',
            'email' => 'siswa@sipintar.sch.id',
            'password' => bcrypt('password'),
            'nama_user' => 'Andi Siswa',
            'status_akun' => 'aktif',
        ]);

        $this->nasabahUser = User::create([
            'role_id' => 6,
            'no_hp' => '081234567806',
            'no_induk' => '0059876543',
            'email' => 'nasabah@sipintar.sch.id',
            'password' => bcrypt('password'),
            'nama_user' => 'Budi Nasabah',
            'status_akun' => 'aktif',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createAccount(string $noRek, string $nama, string $jenisNasabah, float $saldo): Rekening
    {
        $nasabah = Nasabah::create([
            'no_hp' => '08' . str_pad((string) rand(10000000, 99999999), 10, '0', STR_PAD_RIGHT),
            'nama_nasabah' => $nama,
            'jenis_nasabah' => $jenisNasabah,
            'kelas_unit' => $jenisNasabah === 'pegawai' ? 'Guru Matematika' : 'X AKL 1',
            'status_nasabah' => 'aktif',
        ]);

        return Rekening::create([
            'no_rek' => $noRek,
            'nasabah_id' => $nasabah->nasabah_id,
            'tgl_buka' => '2026-01-15',
            'saldo' => $saldo,
            'status_rekening' => 'aktif',
        ]);
    }

    public function test_teller_can_view_calculator_page_with_empty_state(): void
    {
        $this->createAccount('1001-2026-00001', 'Ahmad Siswa', 'non_pegawai', 500000);

        $response = $this->actingAs($this->teller)->get(route('tabungan.jasa'));

        $response->assertOk()
            ->assertSee('Kalkulator Bunga Tabungan')
            ->assertSee('1001-2026-00001')
            ->assertSee('Ahmad Siswa')
            ->assertSee('Belum ada rekening yang dipilih')
            ->assertDontSee('Proses Bunga Rekening Ini');
    }

    public function test_teller_can_calculate_interest_for_student_account(): void
    {
        // Siswa: 0,1% dari Rp 1.000.000 = Rp 1.000
        $rek = $this->createAccount('1001-2026-00001', 'Ahmad Siswa', 'non_pegawai', 1000000);

        $response = $this->actingAs($this->teller)
            ->get(route('tabungan.jasa', ['no_rek' => $rek->no_rek]));

        $response->assertOk()
            ->assertSee('Ahmad Siswa')
            ->assertSee('Siswa / Non-Pegawai')
            ->assertSee('0,1% / bulan')
            ->assertSee('Rp 1.000.000')
            ->assertSee('+ Rp 1.000')
            ->assertSee('Rp 1.001.000')
            ->assertSee('Belum Dibukukan')
            ->assertDontSee('Proses Bunga Rekening Ini');
    }

    public function test_teller_can_calculate_interest_for_employee_account(): void
    {
        // Pegawai: 0,2% dari Rp 2.500.000 = Rp 5.000
        $rek = $this->createAccount('1001-2026-00002', 'Budi Guru', 'pegawai', 2500000);

        $response = $this->actingAs($this->teller)
            ->get(route('tabungan.jasa', ['no_rek' => $rek->no_rek]));

        $response->assertOk()
            ->assertSee('Budi Guru')
            ->assertSee('Pegawai')
            ->assertSee('0,2% / bulan')
            ->assertSee('Rp 2.500.000')
            ->assertSee('+ Rp 5.000')
            ->assertSee('Rp 2.505.000')
            ->assertSee('Belum Dibukukan');
    }

    public function test_calculator_shows_already_posted_status_when_interest_transacted(): void
    {
        $rek = $this->createAccount('1001-2026-00001', 'Ahmad Siswa', 'non_pegawai', 1001000);

        // Catat transaksi bunga di bulan berjalan
        DB::table('tblTRANSAKSI')->insert([
            'rekening_id' => $rek->rekening_id,
            'jenis_transaksi_id' => 3,
            'periode_id' => 1,
            'draft_id' => null,
            'user_id' => $this->teller->user_id,
            'debit' => 0,
            'kredit' => 1000,
            'saldo_akhir' => 1001000,
            'tgl_transaksi' => '2026-11-05 14:00:00',
        ]);

        $response = $this->actingAs($this->teller)
            ->get(route('tabungan.jasa', ['no_rek' => $rek->no_rek]));

        $response->assertOk()
            ->assertSee('Sudah Dibukukan')
            ->assertSee('Bunga telah dibukukan');
    }

    public function test_post_process_interest_route_no_longer_exists(): void
    {
        $rek = $this->createAccount('1001-2026-00001', 'Ahmad Siswa', 'non_pegawai', 1000000);

        // POST ke /tabungan/jasa/{rekening} harus ditolak (404 Not Found atau 405 Method Not Allowed)
        $response = $this->actingAs($this->teller)->post("/tabungan/jasa/{$rek->rekening_id}");

        $this->assertTrue(in_array($response->status(), [404, 405], true));
    }

    public function test_non_teller_roles_are_forbidden(): void
    {
        // Guest (belum login)
        $this->get(route('tabungan.jasa'))->assertRedirect('/login');

        // Siswa Praktik
        $this->actingAs($this->siswaPraktik)->get(route('tabungan.jasa'))->assertForbidden();

        // Nasabah
        $this->actingAs($this->nasabahUser)->get(route('tabungan.jasa'))->assertForbidden();
    }
}
