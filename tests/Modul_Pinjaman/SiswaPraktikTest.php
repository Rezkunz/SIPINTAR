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

class SiswaPraktikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Inisialisasi master role yang dibutuhkan
        Role::create(['role_id' => 1, 'nama_role' => 'Teller']);
        Role::create(['role_id' => 2, 'nama_role' => 'Bendahara']);
        Role::create(['role_id' => 3, 'nama_role' => 'Bendahara Bank Mini']);
        Role::create(['role_id' => 4, 'nama_role' => 'Manager']);
        Role::create(['role_id' => 5, 'nama_role' => 'Siswa Praktik']);
        Role::create(['role_id' => 6, 'nama_role' => 'Nasabah']);

        // Inisialisasi jenis transaksi
        JenisTransaksi::create(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        JenisTransaksi::create(['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan']);
        JenisTransaksi::create(['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan']);

        DB::table('tblKAS')->insert([
            'kas_id' => 1,
            'nama_kas' => 'Kas Teller Test',
            'jenis_kas' => 'kas',
            'saldo' => 10000000,
        ]);

        DB::table('tblPERIODE')->insert([
            'periode_id' => 1,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'status_periode' => 'buka',
            'user_id' => null,
        ]);
    }

    /**
     * Helper untuk membuat akun siswa praktik.
     */
    protected function createSiswa(string $noInduk = '0061234567', string $hp = '081234567891'): User
    {
        return User::create([
            'role_id'     => 5,
            'nasabah_id'  => null,
            'no_hp'       => $hp,
            'no_induk'    => $noInduk,
            'email'       => "siswa_{$noInduk}@sipintar.sch.id",
            'password'    => bcrypt('password'),
            'nama_user'   => "Siswa {$noInduk}",
            'status_akun' => 'aktif',
        ]);
    }

    /**
     * Helper untuk membuat rekening nasabah aktif.
     */
    protected function createRekening(string $status = 'aktif', float $saldo = 500000.00, string $noRek = '1001-2026-00001'): Rekening
    {
        $nasabah = Nasabah::create([
            'no_hp'          => '08129999' . rand(1000, 9999),
            'nama_nasabah'   => 'Nasabah Uji ' . rand(10, 99),
            'jenis_nasabah'  => 'non_pegawai',
            'kelas_unit'     => 'X AKL 1',
            'status_nasabah' => 'aktif',
        ]);

        return Rekening::create([
            'no_rek'          => $noRek,
            'nasabah_id'      => $nasabah->nasabah_id,
            'tgl_buka'        => now()->toDateString(),
            'saldo'           => $saldo,
            'status_rekening' => $status,
        ]);
    }

    /**
    * Test: Buka rekening baru menunggu setoran awal dan dapat diaktifkan siswa.
     */
    public function test_buka_rekening_berhasil_dan_nomor_rekening_unik_berformat_benar(): void
    {
        $siswa = $this->createSiswa();

        $response = $this->actingAs($siswa)->post('/tabungan/buka-rekening', [
            'nama_nasabah'  => 'Ahmad Rifai',
            'jenis_nasabah' => 'siswa',
            'no_induk'      => '0051122334',
            'kelas_unit'    => 'X AKL 2',
            'no_hp'         => '081234567899',
        ]);

        $year = date('Y');
        $expectedFirstRek = "1001-{$year}-00001";

        $response->assertRedirect('/tabungan/buka-rekening');
        $response->assertSessionHas('success_rekening', $expectedFirstRek);

        $this->assertDatabaseHas('tblNASABAH', [
            'nama_nasabah'   => 'Ahmad Rifai',
            'jenis_nasabah'  => 'non_pegawai',
            'no_hp'          => '081234567899',
            'status_nasabah' => 'aktif',
        ]);

        $this->assertDatabaseHas('tblREKENING', [
            'no_rek'          => $expectedFirstRek,
            'saldo'           => 0.00,
            'status_rekening' => 'pending',
        ]);

        $rekening = Rekening::where('no_rek', $expectedFirstRek)->firstOrFail();
        $approval = $this->actingAs($siswa)->post("/tabungan/buka-rekening/{$rekening->rekening_id}/approve", [
            'setoran_awal' => 10000,
        ]);

        $approval->assertRedirect('/tabungan/buka-rekening');
        $this->assertDatabaseHas('tblREKENING', [
            'no_rek'          => $expectedFirstRek,
            'saldo'           => 10000.00,
            'status_rekening' => 'aktif',
        ]);
        $this->assertDatabaseHas('tblTRANSAKSI', [
            'rekening_id' => $rekening->rekening_id,
            'kredit'      => 10000.00,
        ]);

        // Buat rekening kedua, nomor urut harus bertambah ke 00002
        $response2 = $this->actingAs($siswa)->post('/tabungan/buka-rekening', [
            'nama_nasabah'  => 'Bambang Sutejo',
            'jenis_nasabah' => 'pegawai',
            'no_induk'      => '198501012010011001',
            'kelas_unit'    => 'Tata Usaha',
            'no_hp'         => '081234567888',
        ]);

        $expectedSecondRek = "1001-{$year}-00002";
        $response2->assertSessionHas('success_rekening', $expectedSecondRek);
        $this->assertDatabaseHas('tblREKENING', [
            'no_rek' => $expectedSecondRek,
            'status_rekening' => 'pending',
        ]);
    }

    /**
     * Test: Buka rekening menolak nomor HP yang sudah terdaftar sebagai nasabah.
     */
    public function test_buka_rekening_menolak_nomor_hp_duplikat(): void
    {
        $siswa = $this->createSiswa();

        // Rekening pertama
        $this->actingAs($siswa)->post('/tabungan/buka-rekening', [
            'nama_nasabah'  => 'Dewi Anggraini',
            'jenis_nasabah' => 'siswa',
            'no_induk'      => '0051111222',
            'kelas_unit'    => 'X AKL 1',
            'no_hp'         => '081234567800',
        ]);

        // Rekening kedua dengan HP yang sama
        $response = $this->actingAs($siswa)->post('/tabungan/buka-rekening', [
            'nama_nasabah'  => 'Dewi Kloning',
            'jenis_nasabah' => 'siswa',
            'no_induk'      => '0059999888',
            'kelas_unit'    => 'X AKL 1',
            'no_hp'         => '081234567800',
        ]);

        $response->assertSessionHasErrors('no_hp');
    }

    /**
     * Test: Draft setoran dan penarikan tersimpan dengan status 'pending' dan saldo TIDAK berubah.
     */
    public function test_draft_tersimpan_dengan_status_pending_dan_saldo_tidak_berubah(): void
    {
        $siswa = $this->createSiswa();
        $rekening = $this->createRekening('aktif', 750000.00, '1001-2026-00010');

        // 1. Simpan Draft Setoran Rp 100.000
        $responseSetor = $this->actingAs($siswa)->post('/tabungan/draft', [
            'no_rek'     => $rekening->no_rek,
            'jenis'      => 'setoran',
            'nominal'    => '100000',
        ]);

        $responseSetor->assertRedirect('/tabungan/draft/riwayat');

        $this->assertDatabaseHas('tblDRAFT', [
            'rekening_id'        => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'siswa_id'           => $siswa->user_id,
            'nominal'            => 100000.00,
            'status_verifikasi'  => 'pending',
        ]);

        // Pastikan saldo rekening TIDAK bertambah
        $this->assertEquals(750000.00, (float) $rekening->fresh()->saldo);

        // 2. Simpan Draft Penarikan Rp 50.000
        $responseTarik = $this->actingAs($siswa)->post('/tabungan/draft', [
            'no_rek'     => $rekening->no_rek,
            'jenis'      => 'penarikan',
            'nominal'    => '50000',
        ]);

        $responseTarik->assertRedirect('/tabungan/draft/riwayat');

        $this->assertDatabaseHas('tblDRAFT', [
            'rekening_id'        => $rekening->rekening_id,
            'jenis_transaksi_id' => 2,
            'siswa_id'           => $siswa->user_id,
            'nominal'            => 50000.00,
            'status_verifikasi'  => 'pending',
        ]);

        // Pastikan saldo rekening TIDAK berkurang
        $this->assertEquals(750000.00, (float) $rekening->fresh()->saldo);
    }

    /**
     * Test: Validasi batas nominal draft (min 1.000, maks 5.000.000 sesuai config/sipintar.php).
     */
    public function test_validasi_nominal_draft_minimal_dan_maksimal(): void
    {
        $siswa = $this->createSiswa();
        $rekening = $this->createRekening();

        // Nominal di bawah minimum (misal: 500)
        $responseMin = $this->actingAs($siswa)->post('/tabungan/draft', [
            'no_rek'  => $rekening->no_rek,
            'jenis'   => 'setoran',
            'nominal' => '500',
        ]);
        $responseMin->assertSessionHasErrors('nominal');

        // Nominal di atas batas maksimum (misal: 6.000.000)
        $responseMax = $this->actingAs($siswa)->post('/tabungan/draft', [
            'no_rek'  => $rekening->no_rek,
            'jenis'   => 'setoran',
            'nominal' => '6000000',
        ]);
        $responseMax->assertSessionHasErrors('nominal');

        $this->assertDatabaseCount('tblDRAFT', 0);
    }

    /**
     * Test: Pembuatan draft pada rekening yang diblokir atau ditutup ditolak.
     */
    public function test_draft_pada_rekening_tidak_aktif_ditolak(): void
    {
        $siswa = $this->createSiswa();
        $rekeningBlokir = $this->createRekening('blokir', 500000.00, '1001-2026-00099');

        $response = $this->actingAs($siswa)->post('/tabungan/draft', [
            'no_rek'  => $rekeningBlokir->no_rek,
            'jenis'   => 'setoran',
            'nominal' => '50000',
        ]);

        $response->assertSessionHasErrors('no_rek');
        $this->assertDatabaseCount('tblDRAFT', 0);
    }

    /**
     * Test: Siswa praktik tidak bisa mengakses/melihat draft milik siswa lain (403 IDOR protection).
     */
    public function test_siswa_praktik_tidak_bisa_melihat_draft_orang_lain_403(): void
    {
        $siswa1 = $this->createSiswa('0061111111', '081234567811');
        $siswa2 = $this->createSiswa('0062222222', '081234567822');
        $rekening = $this->createRekening();

        $draftSiswa1 = Draft::create([
            'rekening_id'        => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'siswa_id'           => $siswa1->user_id,
            'nominal'            => 75000.00,
            'tgl_input'          => now(),
            'status_verifikasi'  => 'pending',
        ]);

        // Siswa 1 mengakses miliknya sendiri -> 200 OK
        $this->actingAs($siswa1)->get("/tabungan/draft/{$draftSiswa1->draft_id}")
            ->assertStatus(200);

        // Siswa 2 mencoba mengakses draft milik Siswa 1 -> 403 Forbidden
        $this->actingAs($siswa2)->get("/tabungan/draft/{$draftSiswa1->draft_id}")
            ->assertStatus(403);
    }

    /**
     * Test: Riwayat draft hanya menampilkan draft milik siswa yang sedang login.
     */
    public function test_riwayat_draft_hanya_menampilkan_milik_sendiri(): void
    {
        $siswa1 = $this->createSiswa('0061111111', '081234567811');
        $siswa2 = $this->createSiswa('0062222222', '081234567822');
        $rekening = $this->createRekening();

        Draft::create([
            'rekening_id'        => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'siswa_id'           => $siswa1->user_id,
            'nominal'            => 111000.00,
            'tgl_input'          => now(),
            'status_verifikasi'  => 'pending',
        ]);

        Draft::create([
            'rekening_id'        => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'siswa_id'           => $siswa2->user_id,
            'nominal'            => 222000.00,
            'tgl_input'          => now(),
            'status_verifikasi'  => 'pending',
        ]);

        $response = $this->actingAs($siswa1)->get('/tabungan/draft/riwayat');

        $response->assertStatus(200);
        $response->assertSee('1001-2026-00001');
        $response->assertDontSee('222000');
    }

    /**
     * Test: Role lain (misalnya Nasabah) tidak bisa mengakses rute siswa praktik (403 Forbidden).
     */
    public function test_role_lain_tidak_bisa_mengakses_rute_siswa_praktik_403(): void
    {
        $nasabahUser = User::create([
            'role_id'     => 6, // Nasabah
            'no_hp'       => '081233334444',
            'no_induk'    => '0055556666',
            'email'       => 'nasabah_test@sipintar.sch.id',
            'password'    => bcrypt('password'),
            'nama_user'   => 'Nasabah Akun',
            'status_akun' => 'aktif',
        ]);

        $this->actingAs($nasabahUser)->get('/tabungan/buka-rekening')->assertStatus(403);
        $this->actingAs($nasabahUser)->get('/tabungan/draft')->assertStatus(403);
        $this->actingAs($nasabahUser)->get('/tabungan/draft/riwayat')->assertStatus(403);
        $this->actingAs($nasabahUser)->post('/tabungan/buka-rekening', [])->assertStatus(403);
        $this->actingAs($nasabahUser)->post('/tabungan/draft', [])->assertStatus(403);
    }
}
