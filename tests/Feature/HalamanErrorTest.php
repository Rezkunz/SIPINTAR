<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Error tidak ditampilkan mentah: pengguna melihat halaman ramah dengan tombol
 * kembali ke dashboard role-nya.
 */
class HalamanErrorTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tblROLE')->insert([['role_id' => 1, 'nama_role' => 'Teller'], ['role_id' => 4, 'nama_role' => 'Manager']]);
        $this->teller = User::create([
            'role_id' => 1, 'no_hp' => '081200000001', 'no_induk' => 'TLR-001',
            'nama_user' => 'Teller Satu', 'password' => bcrypt('password'), 'status_akun' => 'aktif',
        ]);
    }

    public function test_membuka_halaman_role_lain_diarahkan_kembali_ke_dashboard(): void
    {
        $this->actingAs($this->teller)->get(route('laporan.tabungan'))
            ->assertForbidden()
            ->assertSee('Halaman Tidak Dapat Diakses')
            ->assertSee('Kembali ke Dashboard Teller')
            ->assertSee('Peran (Teller) Anda tidak memiliki izin')
            ->assertSee(route('dashboard'), false);
    }

    public function test_alamat_tidak_dikenal_tetap_mengenali_pengguna_login(): void
    {
        $this->actingAs($this->teller)->get('/alamat-yang-salah-ketik')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Kembali ke Dashboard Teller')
            ->assertDontSee('could not be found');
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/alamat-yang-salah-ketik')
            ->assertNotFound()
            ->assertSee('Masuk ke SIPINTAR');
    }

    public function test_membuka_alamat_khusus_formulir_secara_langsung(): void
    {
        // Alamat khusus POST yang dibuka lewat GET jatuh ke halaman ramah (bukan error mentah)
        $this->actingAs($this->teller)->get('/periode/buka-kembali')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Kembali ke Dashboard Teller');
    }

    public function test_kesalahan_sistem_tidak_menampilkan_rincian_teknis(): void
    {
        Route::middleware('web')->get('/uji-kendala', fn () => throw new \RuntimeException('SQLSTATE rahasia internal'));

        $this->actingAs($this->teller)->get('/uji-kendala')
            ->assertStatus(500)
            ->assertSee('Terjadi Kendala pada Sistem')
            ->assertSee('Kembali ke Dashboard Teller')
            ->assertDontSee('SQLSTATE rahasia internal');
    }

    public function test_permintaan_json_tetap_berupa_json(): void
    {
        $this->actingAs($this->teller)->getJson(route('laporan.tabungan'))
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_menu_yang_belum_tersedia_meminta_kembali_ke_dashboard(): void
    {
        $this->actingAs($this->teller)->get(route('tabungan.verifikasi'))
            ->assertOk()
            ->assertSee('Fitur ini belum tersedia')
            ->assertSee('Kembali ke Dashboard Teller');
    }
}
