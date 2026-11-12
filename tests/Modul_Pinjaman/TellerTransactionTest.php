<?php

namespace Tests\Feature;

use App\Models\Draft;
use App\Models\JenisTransaksi;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TellerTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['role_id' => 1, 'nama_role' => 'Teller']);
        Role::create(['role_id' => 5, 'nama_role' => 'Siswa Praktik']);
        JenisTransaksi::create(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        JenisTransaksi::create(['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan']);

        DB::table('tblKAS')->insert([
            'kas_id' => 1,
            'nama_kas' => 'Kas Teller',
            'jenis_kas' => 'kas',
            'saldo' => 1000000,
        ]);

        DB::table('tblPERIODE')->insert([
            'periode_id' => 1,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'status_periode' => 'buka',
            'user_id' => null,
        ]);
    }

    public function test_teller_can_record_direct_deposit(): void
    {
        $teller = $this->createTeller();
        $rekening = $this->createRekening(50000);

        $response = $this->actingAs($teller)->post('/tabungan/setoran', [
            'no_rek' => $rekening->no_rek,
            'nominal' => 25000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tblREKENING', [
            'rekening_id' => $rekening->rekening_id,
            'saldo' => 75000,
        ]);
        $this->assertDatabaseHas('tblTRANSAKSI', [
            'rekening_id' => $rekening->rekening_id,
            'kredit' => 25000,
            'debit' => 0,
        ]);
        $this->assertDatabaseHas('tblMUTASI_KAS', [
            'arah' => 'masuk',
            'nominal' => 25000,
        ]);
    }

    public function test_teller_cannot_withdraw_below_minimum_balance(): void
    {
        $teller = $this->createTeller();
        $rekening = $this->createRekening(50000);

        $response = $this->actingAs($teller)->post('/tabungan/penarikan', [
            'no_rek' => $rekening->no_rek,
            'nominal' => 45000,
        ]);

        $response->assertSessionHasErrors('nominal');
        $this->assertDatabaseCount('tblTRANSAKSI', 0);
        $this->assertDatabaseHas('tblREKENING', [
            'rekening_id' => $rekening->rekening_id,
            'saldo' => 50000,
        ]);
    }

    public function test_teller_can_approve_pending_draft(): void
    {
        $teller = $this->createTeller();
        $student = User::create([
            'role_id' => 5,
            'no_hp' => '081234567801',
            'no_induk' => '0060000001',
            'email' => 'siswa-test@sipintar.sch.id',
            'password' => bcrypt('password'),
            'nama_user' => 'Siswa Test',
            'status_akun' => 'aktif',
        ]);
        $rekening = $this->createRekening(50000);
        $draft = Draft::create([
            'rekening_id' => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'siswa_id' => $student->user_id,
            'teller_id' => null,
            'nominal' => 25000,
            'tgl_input' => now(),
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($teller)->post("/tabungan/verifikasi/{$draft->draft_id}/setujui");

        $response->assertRedirect('/tabungan/verifikasi');
        $this->assertDatabaseHas('tblDRAFT', [
            'draft_id' => $draft->draft_id,
            'status_verifikasi' => 'disetujui',
            'teller_id' => $teller->user_id,
        ]);
        $this->assertDatabaseHas('tblTRANSAKSI', [
            'draft_id' => $draft->draft_id,
            'kredit' => 25000,
        ]);
    }

    private function createTeller(): User
    {
        return User::create([
            'role_id' => 1,
            'no_hp' => '081234567800',
            'no_induk' => 'TLR-TEST',
            'email' => 'teller-test@sipintar.sch.id',
            'password' => bcrypt('password'),
            'nama_user' => 'Teller Test',
            'status_akun' => 'aktif',
        ]);
    }

    private function createRekening(float $saldo): Rekening
    {
        $nasabah = Nasabah::create([
            'no_hp' => '081299999999',
            'nama_nasabah' => 'Nasabah Test',
            'jenis_nasabah' => 'non_pegawai',
            'kelas_unit' => 'X AKL 1',
            'status_nasabah' => 'aktif',
        ]);

        return Rekening::create([
            'no_rek' => '1001-2026-00999',
            'nasabah_id' => $nasabah->nasabah_id,
            'tgl_buka' => now()->toDateString(),
            'saldo' => $saldo,
            'status_rekening' => 'aktif',
        ]);
    }
}
