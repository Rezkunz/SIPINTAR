<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KelolaPenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;
    protected User $nasabah;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'Teller'],
            ['role_id' => 2, 'nama_role' => 'Bendahara'],
            ['role_id' => 3, 'nama_role' => 'Bendahara Bank Mini'],
            ['role_id' => 4, 'nama_role' => 'Manager'],
            ['role_id' => 5, 'nama_role' => 'Siswa Praktik'],
            ['role_id' => 6, 'nama_role' => 'Nasabah'],
        ]);

        $user = fn ($role, $hp, $induk, $nama, $extra = []) => User::create(array_merge([
            'role_id' => $role, 'no_hp' => $hp, 'no_induk' => $induk,
            'nama_user' => $nama, 'password' => bcrypt('password'), 'status_akun' => 'aktif',
        ], $extra));

        $this->manager = $user(4, '081200000004', 'MGR-001', 'Manager Satu');
        $this->teller  = $user(1, '081200000001', 'TLR-001', 'Teller Satu');

        DB::table('tblNASABAH')->insert(['nasabah_id' => 1, 'no_hp' => '081200000009', 'nama_nasabah' => 'Reza Pratama', 'jenis_nasabah' => 'non_pegawai', 'kelas_unit' => 'XII AKL 1']);
        $this->nasabah = $user(6, '081200000009', '0059876543', 'Reza Pratama', ['nasabah_id' => 1]);
    }

    private function dataPetugas(array $ubah = []): array
    {
        return array_merge([
            'role' => 'teller', 'nama_user' => 'Dewi Lestari', 'no_induk' => '',
            'no_hp' => '081299990000', 'email' => 'dewi@sipintar.sch.id',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ], $ubah);
    }

    public function test_daftar_pengguna_dan_filter_peran(): void
    {
        $this->actingAs($this->manager)->get(route('akun.kelola'))
            ->assertOk()
            ->assertSee('Teller Satu')
            ->assertSee('Reza Pratama');

        $this->actingAs($this->manager)->get(route('akun.kelola', ['role' => 'teller']))
            ->assertOk()
            ->assertSee('Teller Satu')
            ->assertDontSee('Reza Pratama');
    }

    public function test_tambah_petugas_dengan_kode_pegawai_otomatis(): void
    {
        $this->actingAs($this->manager)->post(route('akun.kelola.store'), $this->dataPetugas())
            ->assertRedirect()
            ->assertSessionHas('success');

        $baru = User::where('no_hp', '081299990000')->first();
        $this->assertSame(1, $baru->role_id);
        $this->assertSame('TLR-002', $baru->no_induk);
        $this->assertSame('aktif', $baru->status_akun);
        $this->assertTrue(Hash::check('rahasia123', $baru->password));

        $log = DB::table('tblAUDIT_LOG')->where('entitas', 'tblUSER')->where('aksi', 'INSERT')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('rahasia123', $log->data_baru);
        $this->assertStringNotContainsString('password', $log->data_baru);
    }

    public function test_siswa_praktik_wajib_nisn_sepuluh_digit(): void
    {
        $this->actingAs($this->manager)->post(route('akun.kelola.store'), $this->dataPetugas(['role' => 'siswa_praktik']))
            ->assertSessionHasErrors('no_induk');

        $this->actingAs($this->manager)->post(route('akun.kelola.store'), $this->dataPetugas(['role' => 'siswa_praktik', 'no_induk' => '12345']))
            ->assertSessionHasErrors('no_induk');

        $this->actingAs($this->manager)->post(route('akun.kelola.store'), $this->dataPetugas(['role' => 'siswa_praktik', 'no_induk' => '0061234500']))
            ->assertSessionHasNoErrors();
        $this->assertSame(5, User::where('no_induk', '0061234500')->value('role_id'));
    }

    public function test_akun_nasabah_tidak_dibuat_dari_sini_dan_data_unik_dicek(): void
    {
        $this->actingAs($this->manager)->post(route('akun.kelola.store'), $this->dataPetugas(['role' => 'nasabah']))
            ->assertSessionHasErrors('role');

        $this->actingAs($this->manager)->post(route('akun.kelola.store'), $this->dataPetugas(['no_hp' => '081200000001']))
            ->assertSessionHasErrors('no_hp');

        $this->actingAs($this->manager)->post(route('akun.kelola.store'), $this->dataPetugas(['no_induk' => 'MGR-001']))
            ->assertSessionHasErrors('no_induk');
    }

    public function test_ubah_petugas_tanpa_mengganti_sandi(): void
    {
        $sandiLama = $this->teller->password;

        $this->actingAs($this->manager)->put(route('akun.kelola.update', $this->teller->user_id), $this->dataPetugas([
            'role' => 'bendahara_mini', 'nama_user' => 'Teller Dipromosikan', 'no_induk' => 'BBM-009',
            'no_hp' => '081200000001', 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHas('success');

        $akun = $this->teller->fresh();
        $this->assertSame(3, $akun->role_id);
        $this->assertSame('Teller Dipromosikan', $akun->nama_user);
        $this->assertSame('BBM-009', $akun->no_induk);
        $this->assertSame($sandiLama, $akun->password);
        $this->assertTrue(DB::table('tblAUDIT_LOG')->where('aksi', 'UPDATE')->where('entitas_id', $akun->user_id)->exists());
    }

    public function test_akun_nasabah_hanya_bisa_direset_sandinya(): void
    {
        $this->actingAs($this->manager)->put(route('akun.kelola.update', $this->nasabah->user_id), [
            'nama_user' => 'Nama Diganti', 'role' => 'manager',
            'password' => 'sandibaru1', 'password_confirmation' => 'sandibaru1',
        ])->assertSessionHas('success');

        $akun = $this->nasabah->fresh();
        $this->assertSame('Reza Pratama', $akun->nama_user);
        $this->assertSame(6, $akun->role_id);
        $this->assertTrue(Hash::check('sandibaru1', $akun->password));
        $this->assertTrue(DB::table('tblAUDIT_LOG')->where('aksi', 'RESET_PASSWORD')->exists());
    }

    public function test_manager_tidak_bisa_mengubah_peran_atau_menonaktifkan_diri_sendiri(): void
    {
        $this->actingAs($this->manager)->put(route('akun.kelola.update', $this->manager->user_id), $this->dataPetugas([
            'role' => 'teller', 'nama_user' => 'Manager Satu', 'no_induk' => 'MGR-001', 'no_hp' => '081200000004',
            'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasErrors('role');
        $this->assertSame(4, $this->manager->fresh()->role_id);

        $this->actingAs($this->manager)->patch(route('akun.kelola.status', $this->manager->user_id))
            ->assertSessionHas('error');
        $this->assertSame('aktif', $this->manager->fresh()->status_akun);
    }

    public function test_nonaktifkan_akun_memutus_sesi_dan_menolak_login(): void
    {
        $this->actingAs($this->manager)->patch(route('akun.kelola.status', $this->teller->user_id))
            ->assertSessionHas('success');
        $this->assertSame('nonaktif', $this->teller->fresh()->status_akun);

        $this->actingAs($this->teller->fresh())->get(route('tabungan.buka-rekening'))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.post'), ['login' => 'TLR-001', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        // Aktifkan kembali
        $this->actingAs($this->manager)->patch(route('akun.kelola.status', $this->teller->user_id));
        $this->assertSame('aktif', $this->teller->fresh()->status_akun);
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)->get(route('akun.kelola'))->assertForbidden();
        $this->actingAs($this->teller)->post(route('akun.kelola.store'), $this->dataPetugas())->assertForbidden();
    }
}
