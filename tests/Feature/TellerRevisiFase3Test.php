<?php

namespace Tests\Feature;

use App\Models\Draft;
use App\Models\JenisTransaksi;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Role;
use App\Models\User;
use App\Services\DraftService;
use App\Services\RekeningService;
use App\Services\TellerCorrectionService;
use App\Services\TellerTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TellerRevisiFase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['role_id' => 1, 'nama_role' => 'Teller']);
        Role::create(['role_id' => 5, 'nama_role' => 'Siswa Praktik']);
        Role::create(['role_id' => 6, 'nama_role' => 'Nasabah']);

        JenisTransaksi::create(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        JenisTransaksi::create(['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan']);
        JenisTransaksi::create(['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan']);

        DB::table('tblKAS')->insert([
            'kas_id' => 1,
            'nama_kas' => 'Kas Tunai',
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

    public function test_siswa_praktik_buka_rekening_dan_aktivasi_menambah_kas_tunai_atomik(): void
    {
        $siswa = $this->createUser(5, '0811111111');
        $service = app(RekeningService::class);

        $rekening = $service->bukaRekening([
            'nama_nasabah' => 'Siti Aminah',
            'jenis_nasabah' => 'siswa',
            'kelas_unit' => 'XI AKL 2',
            'no_hp' => '081234567801',
            'no_induk' => '0051112223',
            'password' => 'rahasia123',
        ]);

        $this->assertDatabaseHas('tblUSER', [
            'nasabah_id' => $rekening->nasabah_id,
            'role_id' => 6,
            'no_hp' => '081234567801',
        ]);

        $service->aktifkanDenganSetoran($rekening->rekening_id, $siswa->user_id, 50000);

        $this->assertDatabaseHas('tblREKENING', [
            'rekening_id' => $rekening->rekening_id,
            'status_rekening' => 'aktif',
            'saldo' => 50000,
        ]);
        $this->assertDatabaseHas('tblKAS', [
            'kas_id' => 1,
            'saldo' => 1050000,
        ]);
        $this->assertDatabaseHas('tblMUTASI_KAS', [
            'arah' => 'masuk',
            'nominal' => 50000,
        ]);
    }

    public function test_rollback_penuh_jika_kas_tidak_tersedia_saat_aktivasi_rekening(): void
    {
        $siswa = $this->createUser(5, '0811111112');
        $service = app(RekeningService::class);
        $rekening = $service->bukaRekening([
            'nama_nasabah' => 'Gagal Rollback',
            'jenis_nasabah' => 'siswa',
            'no_hp' => '081234567802',
            'password' => 'rahasia123',
        ]);

        DB::table('tblKAS')->delete();
        $this->expectException(ValidationException::class);

        try {
            $service->aktifkanDenganSetoran($rekening->rekening_id, $siswa->user_id, 25000);
        } finally {
            $this->assertDatabaseHas('tblREKENING', [
                'rekening_id' => $rekening->rekening_id,
                'status_rekening' => 'pending',
                'saldo' => 0,
            ]);
            $this->assertDatabaseCount('tblTRANSAKSI', 0);
        }
    }

    public function test_toggle_minimal_setoran_dan_audit_bypass(): void
    {
        $teller = $this->createUser(1, '0811111113');
        $rekening = $this->createRekening(100000);

        // Default aktif: setoran < 10.000 ditolak
        $this->actingAs($teller)->post(route('tabungan.setoran.store'), [
            'no_rek' => $rekening->no_rek,
            'nominal' => 5000,
        ])->assertSessionHasErrors('nominal');

        // Toggle dimatikan: setoran 5.000 diterima & tercatat di audit log
        $this->actingAs($teller)->post(route('tabungan.setoran.store'), [
            'no_rek' => $rekening->no_rek,
            'nominal' => 5000,
            'abaikan_minimal' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('tblREKENING', [
            'rekening_id' => $rekening->rekening_id,
            'saldo' => 105000,
        ]);
        $this->assertDatabaseHas('tblAUDIT_LOG', [
            'aksi' => 'BYPASS_MINIMAL_SETORAN',
            'user_id' => $teller->user_id,
        ]);
    }

    public function test_penarikan_ditolak_jika_kas_tunai_tidak_cukup(): void
    {
        $teller = $this->createUser(1, '0811111114');
        $rekening = $this->createRekening(500000);
        DB::table('tblKAS')->where('kas_id', 1)->update(['saldo' => 10000]);

        $this->actingAs($teller)->post(route('tabungan.penarikan.store'), [
            'no_rek' => $rekening->no_rek,
            'nominal' => 50000,
        ])->assertSessionHasErrors('nominal');

        $this->assertDatabaseHas('tblREKENING', [
            'rekening_id' => $rekening->rekening_id,
            'saldo' => 500000,
        ]);
    }

    public function test_tutup_rekening_saldo_nol_dan_saldo_lebih_dari_nol(): void
    {
        $teller = $this->createUser(1, '0811111115');
        $rekNol = $this->createRekening(0, '1001-2026-00010');
        $rekSaldo = $this->createRekening(75000, '1001-2026-00011');

        // Saldo 0
        $this->actingAs($teller)->post(route('tabungan.kelola-akun.tutup', $rekNol->rekening_id), [
            'alasan' => 'Lulus',
        ])->assertRedirect(route('tabungan.kelola-akun.bukti-tutup', $rekNol->rekening_id));

        $this->assertDatabaseHas('tblREKENING', [
            'rekening_id' => $rekNol->rekening_id,
            'status_rekening' => 'tutup',
            'saldo' => 0,
        ]);

        // Saldo > 0
        $this->actingAs($teller)->post(route('tabungan.kelola-akun.tutup', $rekSaldo->rekening_id), [
            'alasan' => 'Pindah sekolah',
        ])->assertRedirect(route('tabungan.kelola-akun.bukti-tutup', $rekSaldo->rekening_id));

        $this->assertDatabaseHas('tblREKENING', [
            'rekening_id' => $rekSaldo->rekening_id,
            'status_rekening' => 'tutup',
            'saldo' => 0,
        ]);
        $this->assertDatabaseHas('tblKAS', [
            'kas_id' => 1,
            'saldo' => 925000,
        ]);
        $this->assertDatabaseHas('tblMUTASI_KAS', [
            'arah' => 'keluar',
            'nominal' => 75000,
        ]);
    }

    public function test_rekening_tutup_ditolak_di_semua_alur_transaksi(): void
    {
        $teller = $this->createUser(1, '0811111116');
        $siswa = $this->createUser(5, '0811111117');
        $rek = $this->createRekening(50000, '1001-2026-00012');

        $trxId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rek->rekening_id,
            'jenis_transaksi_id' => 1,
            'periode_id' => 1,
            'user_id' => $teller->user_id,
            'debit' => 0,
            'kredit' => 50000,
            'saldo_akhir' => 50000,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');

        $draft = Draft::create([
            'rekening_id' => $rek->rekening_id,
            'jenis_transaksi_id' => 1,
            'siswa_id' => $siswa->user_id,
            'nominal' => 20000,
            'tgl_input' => now(),
            'status_verifikasi' => 'pending',
        ]);

        // Tutup paksa via update agar kita bisa uji semua alur
        DB::table('tblREKENING')->where('rekening_id', $rek->rekening_id)->update(['status_rekening' => 'tutup']);

        $txService = app(TellerTransactionService::class);
        $corrService = app(TellerCorrectionService::class);
        $draftService = app(DraftService::class);

        try {
            $txService->storeDirect($teller->user_id, $rek->no_rek, 20000, 'setoran');
            $this->fail('Setoran seharusnya ditolak pada rekening tutup.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('no_rek', $e->errors());
        }

        try {
            $txService->approveDraft($draft->draft_id, $teller->user_id);
            $this->fail('Approve draft seharusnya ditolak pada rekening tutup.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('draft', $e->errors());
        }

        try {
            $corrService->correct($trxId, $teller->user_id, 'Tes', 1, 30000);
            $this->fail('Koreksi seharusnya ditolak pada rekening tutup.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('transaksi', $e->errors());
        }

        try {
            $draftService->createDraft($siswa->user_id, [
                'no_rek' => $rek->no_rek,
                'jenis' => 'setoran',
                'nominal' => 20000,
            ]);
            $this->fail('Draft seharusnya ditolak pada rekening tutup.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('no_rek', $e->errors());
        }
    }

    public function test_rbac_hanya_teller_dapat_mengakses_kelola_akun(): void
    {
        $teller = $this->createUser(1, '0811111118');
        $siswa = $this->createUser(5, '0811111119');
        $nasabah = $this->createUser(6, '0811111120');

        $this->actingAs($teller)->get(route('tabungan.kelola-akun'))->assertOk();
        $this->actingAs($siswa)->get(route('tabungan.kelola-akun'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('tabungan.kelola-akun'))->assertForbidden();
    }

    private function createUser(int $roleId, string $noHp): User
    {
        return User::create([
            'role_id' => $roleId,
            'no_hp' => $noHp,
            'password' => bcrypt('password'),
            'nama_user' => 'User ' . $noHp,
            'status_akun' => 'aktif',
        ]);
    }

    private function createRekening(float $saldo, string $noRek = '1001-2026-00001'): Rekening
    {
        $nasabah = Nasabah::create([
            'no_hp' => '089' . random_int(10000000, 99999999),
            'nama_nasabah' => 'Nasabah ' . $noRek,
            'jenis_nasabah' => 'non_pegawai',
            'kelas_unit' => 'XII AKL 1',
            'status_nasabah' => 'aktif',
        ]);

        return Rekening::create([
            'no_rek' => $noRek,
            'nasabah_id' => $nasabah->nasabah_id,
            'tgl_buka' => now()->toDateString(),
            'saldo' => $saldo,
            'status_rekening' => 'aktif',
        ]);
    }
}
