<?php

namespace Tests\Feature;

use App\Models\Nasabah;
use App\Models\Role;
use App\Models\User;
use App\Services\PeriodeLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PinjamanRbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $bendaharaMini;
    protected User $bendaharaSekolah;
    protected User $userNasabah1;
    protected User $userNasabah2;
    protected Nasabah $nasabah1;
    protected Nasabah $nasabah2;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup master roles
        Role::create(['role_id' => 1, 'nama_role' => 'Teller']);
        Role::create(['role_id' => 2, 'nama_role' => 'Bendahara']);
        Role::create(['role_id' => 3, 'nama_role' => 'Bendahara Bank Mini']);
        Role::create(['role_id' => 4, 'nama_role' => 'Manager']);
        Role::create(['role_id' => 5, 'nama_role' => 'Siswa Praktik']);
        Role::create(['role_id' => 6, 'nama_role' => 'Nasabah']);

        // Akun Bendahara Bank Mini
        $this->bendaharaMini = User::create([
            'user_id'     => 10,
            'role_id'     => 3,
            'nasabah_id'  => null,
            'no_hp'       => '081200000001',
            'no_induk'    => 'BBM001',
            'nama_user'   => 'Bendahara Bank Mini',
            'email'       => 'bbm@sipintar.sch.id',
            'password'    => bcrypt('password'),
            'status_akun' => 'aktif',
        ]);

        // Akun Bendahara Sekolah
        $this->bendaharaSekolah = User::create([
            'user_id'     => 11,
            'role_id'     => 2,
            'nasabah_id'  => null,
            'no_hp'       => '081200000002',
            'no_induk'    => 'BND001',
            'nama_user'   => 'Bendahara Sekolah',
            'email'       => 'bendahara@sipintar.sch.id',
            'password'    => bcrypt('password'),
            'status_akun' => 'aktif',
        ]);

        // Periode aktif
        DB::table('tblPERIODE')->insert([
            'periode_id'     => 1,
            'user_id'        => $this->bendaharaMini->user_id,
            'tahun'          => (int) date('Y'),
            'bulan'          => (int) date('n'),
            'status_periode' => 'buka',
            'tgl_tutup'      => null,
        ]);

        // Nasabah 1 (Guru)
        $this->nasabah1 = Nasabah::create([
            'no_hp'          => '081200000101',
            'nama_nasabah'   => 'Budi Santoso, S.Pd',
            'jenis_nasabah'  => 'pegawai',
            'kelas_unit'     => 'Guru RPL',
            'gaji_pokok'     => 4500000,
            'tunjangan'      => 1500000,
            'status_nasabah' => 'aktif',
        ]);

        $this->userNasabah1 = User::create([
            'user_id'     => 21,
            'role_id'     => 6,
            'nasabah_id'  => $this->nasabah1->nasabah_id,
            'no_hp'       => '081200000101',
            'no_induk'    => 'GUR001',
            'nama_user'   => 'Budi Santoso',
            'email'       => 'budi@sipintar.sch.id',
            'password'    => bcrypt('password'),
            'status_akun' => 'aktif',
        ]);

        // Nasabah 2 (Karyawan)
        $this->nasabah2 = Nasabah::create([
            'no_hp'          => '081200000102',
            'nama_nasabah'   => 'Siti Rahmawati, S.Kom',
            'jenis_nasabah'  => 'pegawai',
            'kelas_unit'     => 'Tata Usaha',
            'gaji_pokok'     => 3800000,
            'tunjangan'      => 1000000,
            'status_nasabah' => 'aktif',
        ]);

        $this->userNasabah2 = User::create([
            'user_id'     => 22,
            'role_id'     => 6,
            'nasabah_id'  => $this->nasabah2->nasabah_id,
            'no_hp'       => '081200000102',
            'no_induk'    => 'KRY001',
            'nama_user'   => 'Siti Rahmawati',
            'email'       => 'siti@sipintar.sch.id',
            'password'    => bcrypt('password'),
            'status_akun' => 'aktif',
        ]);

        // Rekening Aktif untuk Nasabah 1 dan Nasabah 2 (Terverifikasi Fisik di Teller)
        \App\Models\Rekening::create([
            'no_rek'          => '1001-2026-00001',
            'nasabah_id'      => $this->nasabah1->nasabah_id,
            'tgl_buka'        => now()->toDateString(),
            'saldo'           => 100000.00,
            'status_rekening' => 'aktif',
        ]);

        \App\Models\Rekening::create([
            'no_rek'          => '1001-2026-00002',
            'nasabah_id'      => $this->nasabah2->nasabah_id,
            'tgl_buka'        => now()->toDateString(),
            'saldo'           => 100000.00,
            'status_rekening' => 'aktif',
        ]);
    }

    /**
     * Uji Bendahara Sekolah dilarang mengakses pencairan pinjaman, pengajuan, dan kas (403 Forbidden).
     */
    public function test_bendahara_sekolah_dilarang_akses_pencairan_dan_kas(): void
    {
        $this->actingAs($this->bendaharaSekolah);

        // Akses menu pengajuan pinjaman harus ditolak 403
        $respPengajuan = $this->get(route('bbm.pinjaman.ajukan'));
        $respPengajuan->assertStatus(403);

        // Akses menu pencairan pinjaman harus ditolak 403
        $respCair = $this->get(route('bbm.pinjaman.cair'));
        $respCair->assertStatus(403);

        // Akses kas harian harus ditolak 403
        $respKas = $this->get(route('kas.harian'));
        $respKas->assertStatus(403);
    }

    /**
     * Uji Bendahara Sekolah berhak mengakses dashboard dan daftar potongan.
     */
    public function test_bendahara_sekolah_bisa_akses_rencana_dan_hasil_potongan(): void
    {
        $this->actingAs($this->bendaharaSekolah);

        // Dashboard bendahara sekolah harus berhasil 200
        $respDashboard = $this->get(route('dashboard'));
        $respDashboard->assertStatus(200);
        $respDashboard->assertSee('Daftar Rencana Potongan');

        $response = $this->get(route('bendahara.potongan.index'));
        $response->assertStatus(200);
    }

    /**
     * Uji Bendahara Bank Mini memiliki hak penuh pada modul Pinjaman, Angsuran, dan Kas.
     */
    public function test_bendahara_bank_mini_bisa_akses_seluruh_fitur_operasional(): void
    {
        $this->actingAs($this->bendaharaMini);

        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('bbm.pinjaman.index'))->assertStatus(200);
        $this->get(route('bbm.pinjaman.ajukan'))->assertStatus(200);
        $this->get(route('bbm.pinjaman.cair'))->assertStatus(200);
        $this->get(route('bbm.angsuran.langsung'))->assertStatus(200);
        $this->get(route('bbm.angsuran.potongan'))->assertStatus(200);
        $this->get(route('bbm.angsuran.koreksi'))->assertStatus(200);
        $this->get(route('kas.harian'))->assertStatus(200);
        $this->get(route('bbm.kartu.index'))->assertStatus(200);

        // Uji submit pengajuan pinjaman oleh BBM
        $postResp = $this->post(route('bbm.pinjaman.store'), [
            'nasabah_id'       => $this->nasabah1->nasabah_id,
            'nominal_pinjaman' => 5000000,
            'tenor'            => 10,
            'metode_bayar'     => 'potong_gaji',
            'tgl_pengajuan'    => date('Y-m-d'),
        ]);

        $postResp->assertRedirect(route('bbm.pinjaman.kelayakan'));
        $this->assertDatabaseHas('tblPINJAMAN', [
            'nasabah_id'       => $this->nasabah1->nasabah_id,
            'nominal_pinjaman' => 5000000,
            'tenor'            => 10,
            'status_pinjaman'  => 'diajukan',
        ]);

        // Uji evaluasi kelayakan oleh komite (BBM)
        $pinjamanId = DB::table('tblPINJAMAN')->where('nasabah_id', $this->nasabah1->nasabah_id)->value('pinjaman_id');
        $kelayakanResp = $this->post(route('bbm.pinjaman.kelayakan.simpan', $pinjamanId), [
            'hasil_kelayakan' => 'layak',
            'penghasilan'     => 6000000,
            'catatan'         => 'Rasio cicilan aman (< 30%). Disetujui komite.',
        ]);
        $kelayakanResp->assertRedirect(route('bbm.pinjaman.kelayakan'));
        $this->assertDatabaseHas('tblPINJAMAN', [
            'pinjaman_id'     => $pinjamanId,
            'status_pinjaman' => 'layak',
        ]);
        $this->assertDatabaseHas('tblKELAYAKAN', [
            'pinjaman_id'     => $pinjamanId,
            'hasil_kelayakan' => 'layak',
        ]);
    }

    /**
     * Uji Anti-IDOR Nasabah: Nasabah hanya bisa melihat jadwal miliknya sendiri berdasarkan session.
     */
    public function test_nasabah_hanya_melihat_jadwal_angsuran_milik_sendiri_anti_idor(): void
    {
        // Buat pinjaman aktif untuk nasabah 1
        DB::table('tblPINJAMAN')->insert([
            'pinjaman_id'       => 501,
            'nasabah_id'        => $this->nasabah1->nasabah_id,
            'user_id'           => $this->bendaharaMini->user_id,
            'nominal_pinjaman'  => 3000000,
            'tenor'             => 3,
            'bunga_persen'      => 2.0,
            'metode_bayar'      => 'potong_gaji',
            'status_pinjaman'   => 'cair',
            'tgl_pengajuan'     => Carbon::now()->toDateString(),
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pencairan_id'  => 601,
            'pinjaman_id'   => 501,
            'user_id'       => $this->bendaharaMini->user_id,
            'tgl_cair'      => Carbon::now()->toDateString(),
            'nominal_cair'  => 3000000,
        ]);

        DB::table('tblJADWAL_ANGSURAN')->insert([
            [
                'jadwal_angsuran_id' => 701,
                'pinjaman_id'        => 501,
                'angsuran_ke'        => 1,
                'jatuh_tempo'        => Carbon::now()->addMonth()->toDateString(),
                'pokok'              => 1000000,
                'bunga'              => 60000,
                'status_bayar'       => 'belum',
            ],
            [
                'jadwal_angsuran_id' => 702,
                'pinjaman_id'        => 501,
                'angsuran_ke'        => 2,
                'jatuh_tempo'        => Carbon::now()->addMonths(2)->toDateString(),
                'pokok'              => 1000000,
                'bunga'              => 60000,
                'status_bayar'       => 'belum',
            ],
            [
                'jadwal_angsuran_id' => 703,
                'pinjaman_id'        => 501,
                'angsuran_ke'        => 3,
                'jatuh_tempo'        => Carbon::now()->addMonths(3)->toDateString(),
                'pokok'              => 1000000,
                'bunga'              => 60000,
                'status_bayar'       => 'belum',
            ],
        ]);

        // Login sebagai Nasabah 1
        $this->actingAs($this->userNasabah1);
        $response1 = $this->get(route('nasabah.angsuran'));
        $response1->assertStatus(200);
        $response1->assertSee('3.000.000');

        // Login sebagai Nasabah 2 (tidak punya pinjaman)
        $this->actingAs($this->userNasabah2);
        $response2 = $this->get(route('nasabah.angsuran'));
        $response2->assertStatus(200);
        // Pastikan nasabah 2 TIDAK melihat data pinjaman nasabah 1
        $response2->assertDontSee('3.000.000');
        $response2->assertSee('Tidak Ada Pinjaman Aktif');

        // Nasabah 1 mencoba akses menu Bendahara Bank Mini -> 403
        $this->actingAs($this->userNasabah1);
        $this->get(route('bbm.pinjaman.index'))->assertStatus(403);
    }

    /**
     * Uji alur lengkap: Bendahara Bank Mini buat rencana -> Sekolah unduh CSV & catat hasil -> Bank Mini konfirmasi terima uang & bukukan kas.
     */
    public function test_alur_lengkap_potongan_sekolah_hingga_konfirmasi_kas_bank_mini(): void
    {
        // Setup Master Kas Tunai & Bank
        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai', 'jenis_kas' => 'kas', 'saldo' => 50000000],
            ['kas_id' => 2, 'nama_kas' => 'Kas Bank BJB', 'jenis_kas' => 'bank', 'saldo' => 100000000],
        ]);

        // Buat pinjaman aktif untuk nasabah 1
        DB::table('tblPINJAMAN')->insert([
            'pinjaman_id'       => 801,
            'nasabah_id'        => $this->nasabah1->nasabah_id,
            'user_id'           => $this->bendaharaMini->user_id,
            'nominal_pinjaman'  => 6000000,
            'tenor'             => 6,
            'bunga_persen'      => 2.0,
            'metode_bayar'      => 'potong_gaji',
            'status_pinjaman'   => 'cair',
            'tgl_pengajuan'     => Carbon::now()->toDateString(),
        ]);

        $jadwalId = DB::table('tblJADWAL_ANGSURAN')->insertGetId([
            'pinjaman_id'        => 801,
            'angsuran_ke'        => 1,
            'jatuh_tempo'        => Carbon::now()->toDateString(),
            'pokok'              => 1000000,
            'bunga'              => 120000,
            'status_bayar'       => 'belum',
        ]);

        // 1. Bendahara Bank Mini susun rencana potongan bulanan
        $this->actingAs($this->bendaharaMini);
        $respBuat = $this->post(route('bbm.angsuran.potongan.simpan'), [
            'jadwal_ids' => [$jadwalId],
            'bulan'      => (int) date('n'),
            'tahun'      => (int) date('Y'),
        ]);
        $respBuat->assertRedirect(route('bbm.angsuran.potongan'));

        $rencana = DB::table('tblRENCANA_POTONGAN')->first();
        $this->assertNotNull($rencana);
        $this->assertEquals('dikirim', $rencana->status_potongan);

        // 2. Bendahara Sekolah unduh daftar potongan CSV format bank
        $this->actingAs($this->bendaharaSekolah);
        $respDownload = $this->get(route('bendahara.potongan.download', $rencana->rencana_potongan_id));
        $respDownload->assertStatus(200);
        $respDownload->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // 3. Bendahara Sekolah catat hasil potongan (berhasil, metode transfer rekening bank)
        // 3. Bendahara Sekolah mengisi hasil potongan gaji dan mengunggah bukti transfer
        Storage::fake('public');
        $fileBukti = UploadedFile::fake()->create('struk_transfer_bjb.jpg', 200, 'image/jpeg');

        $detail = DB::table('tblDETAIL_POTONGAN')->where('rencana_potongan_id', $rencana->rencana_potongan_id)->first();
        $respSimpan = $this->post(route('bendahara.potongan.simpan', $rencana->rencana_potongan_id), [
            'tgl_potongan'      => Carbon::now()->toDateString(),
            'metode_penyerahan' => 'transfer',
            'catatan_sekolah'   => 'Dipindahbukukan dari rekening sekolah BJB',
            'bukti_transfer'    => $fileBukti,
            'hasil'             => [
                $detail->detail_potongan_id => 'berhasil',
            ],
            'keterangan'        => [
                $detail->detail_potongan_id => 'Sukses terpotong payroll',
            ],
        ]);
        $respSimpan->assertRedirect(route('bendahara.potongan.index'));

        // Cek bahwa status sekarang 'menunggu_verifikasi' dan kas Bank Mini BELUM bertambah
        $rencanaUpdated = DB::table('tblRENCANA_POTONGAN')->where('rencana_potongan_id', $rencana->rencana_potongan_id)->first();
        $this->assertEquals('menunggu_verifikasi', $rencanaUpdated->status_potongan);
        $this->assertEquals(1120000, (float) $rencanaUpdated->total_realisasi);
        $this->assertNotNull($rencanaUpdated->bukti_transfer);
        Storage::disk('public')->assertExists($rencanaUpdated->bukti_transfer);
        $this->assertEquals(0, DB::table('tblANGSURAN')->count(), 'Angsuran belum boleh dibukukan sebelum uang diterima Bank Mini');

        // 4. Bendahara Bank Mini verifikasi penerimaan dana dan masukkan ke Kas Bank
        $this->actingAs($this->bendaharaMini);
        $respKonfirmasi = $this->post(route('bbm.angsuran.potongan.konfirmasi', $rencana->rencana_potongan_id), [
            'kas_tujuan' => 'kas_bank',
            'tgl_terima' => Carbon::now()->toDateString(),
            'catatan'    => 'Sudah dicek rekening koran BJB, dana masuk',
        ]);
        $respKonfirmasi->assertRedirect(route('bbm.angsuran.potongan'));

        // Cek status akhir
        $rencanaFinal = DB::table('tblRENCANA_POTONGAN')->where('rencana_potongan_id', $rencana->rencana_potongan_id)->first();
        $this->assertEquals('selesai', $rencanaFinal->status_potongan);

        // Angsuran resmi telah dibukukan
        $this->assertEquals(1, DB::table('tblANGSURAN')->count());
        $jadwalUpdated = DB::table('tblJADWAL_ANGSURAN')->where('jadwal_angsuran_id', $jadwalId)->first();
        $this->assertEquals('lunas', $jadwalUpdated->status_bayar);

        // Kas Bank bertambah sebesar nominal potongan (1.120.000)
        $kasBank = DB::table('tblKAS')->where('jenis_kas', 'bank')->first();
        $this->assertEquals(101120000, (float) $kasBank->saldo);

        // Berita Acara Serah Terima (BAST) dapat diakses
        $respBast = $this->get(route('bbm.angsuran.potongan.bast', $rencana->rencana_potongan_id));
        $respBast->assertStatus(200);
        $respBast->assertSee('Berita Acara Serah Terima');
    }

    /**
     * Uji Penguncian Periode: jika periode ditutup, transaksi harus ditolak.
     */
    public function test_periode_terkunci_menolak_transaksi(): void
    {
        $today = Carbon::now();

        // Ubah status periode menjadi tutup
        DB::table('tblPERIODE')
            ->where('tahun', $today->year)
            ->where('bulan', $today->month)
            ->update(['status_periode' => 'tutup']);

        // Pastikan PeriodeLockService melempar exception saat dicek
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('telah ditutup oleh Manajer dan terkunci');

        PeriodeLockService::assertPeriodeTerbuka($today->toDateString());
    }

    /**
     * Uji Halaman Rencana Potongan BBM: Dropdown tema SIPINTAR, Tombol Aksi Logo Mata, In-Page Detail Banner.
     */
    public function test_halaman_rencana_potongan_menampilkan_data_dan_inpage_banner(): void
    {
        $this->actingAs($this->bendaharaMini);

        // Akses halaman rencana potongan
        $response = $this->get(route('bbm.angsuran.potongan'));
        $response->assertStatus(200);

        // Verifikasi elemen dropdown bertema
        $response->assertSee('sipintar-select');
        $response->assertSee('period-filter-toolbar');

        // Verifikasi elemen tombol aksi logo mata dan banner in-page
        $response->assertSee('bukaDetailBanner');
        $response->assertSee('paketDetailBanner');
        $response->assertSee('tutupDetailBanner');
    }

    /**
     * Uji Nasabah Pegawai bisa mengajukan pinjaman mandiri sesuai Use Case.
     */
    public function test_nasabah_pegawai_bisa_mengajukan_pinjaman_mandiri(): void
    {
        $this->actingAs($this->userNasabah1);

        // Akses form pengajuan pinjaman
        $response = $this->get(route('nasabah.pinjaman.ajukan'));
        $response->assertStatus(200);
        $response->assertSee('Form Permohonan Pinjaman Pegawai');

        // Akses informasi saldo & riwayat mutasi
        $respSaldo = $this->get(route('nasabah.saldo'));
        $respSaldo->assertStatus(200);
        $respSaldo->assertSee('Buku tabungan digital');

        // Submit pengajuan pinjaman
        $postResp = $this->post(route('nasabah.pinjaman.store'), [
            'nominal_pinjaman' => 5000000,
            'tenor'            => 10,
            'metode_bayar'     => 'potong_gaji',
        ]);

        $postResp->assertRedirect(route('nasabah.pinjaman'));

        // Cek tersimpan di database dengan status diajukan
        $this->assertDatabaseHas('tblPINJAMAN', [
            'nasabah_id'       => $this->nasabah1->nasabah_id,
            'nominal_pinjaman' => 5000000,
            'tenor'            => 10,
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'diajukan',
        ]);
    }

    /**
     * Uji Nasabah dengan status rekening 'pending' (menunggu aktivasi teller):
     * 1. Seluruh layanan transaksi (mutasi, angsuran, pinjaman) terkunci.
     * 2. Nasabah tidak muncul pada dropdown pilihan peminjam BBM.
     * 3. BBM tidak bisa mengajukan pinjaman untuk nasabah pending.
     */
    public function test_nasabah_rekening_pending_terkunci_dan_tidak_muncul_di_pengajuan_bbm(): void
    {
        // 1. Buat nasabah baru dengan rekening 'pending' (seperti registrasi mandiri)
        $nasabahPending = Nasabah::create([
            'no_hp'          => '081299887766',
            'nama_nasabah'   => 'Reza Dwi Saputro',
            'jenis_nasabah'  => 'pegawai',
            'kelas_unit'     => 'Tata Usaha',
            'gaji_pokok'     => 4000000,
            'tunjangan'      => 1000000,
            'status_nasabah' => 'aktif',
        ]);

        $userPending = User::create([
            'user_id'     => 99,
            'role_id'     => 6, // Nasabah
            'nasabah_id'  => $nasabahPending->nasabah_id,
            'no_hp'       => '081299887766',
            'no_induk'    => '1211221121212',
            'nama_user'   => 'Reza Dwi Saputro',
            'email'       => 'reza.pending@sipintar.sch.id',
            'password'    => bcrypt('password'),
            'status_akun' => 'aktif',
        ]);

        \App\Models\Rekening::create([
            'no_rek'          => '1001-2026-00099',
            'nasabah_id'      => $nasabahPending->nasabah_id,
            'tgl_buka'        => now()->toDateString(),
            'saldo'           => 0.00,
            'status_rekening' => 'pending', // Belum diaktivasi teller
        ]);

        // A. Cek dari sisi Nasabah: Boleh buka dashboard (melihat status pending)
        $this->actingAs($userPending);
        $respDash = $this->get(route('dashboard'));
        $respDash->assertStatus(200);
        $respDash->assertSee('Menunggu Aktivasi di Loket Teller');

        // B. Cek dari sisi Nasabah: Rute layanan transaksi harus terkunci dan redirect ke dashboard
        $respAngsuran = $this->get(route('nasabah.angsuran'));
        $respAngsuran->assertRedirect(route('dashboard'));
        $respAngsuran->assertSessionHas('error');

        $respPinjaman = $this->get(route('nasabah.pinjaman'));
        $respPinjaman->assertRedirect(route('dashboard'));

        $respAjukan = $this->get(route('nasabah.pinjaman.ajukan'));
        $respAjukan->assertRedirect(route('dashboard'));

        // C. Cek dari sisi Bendahara Bank Mini: Form pengajuan pinjaman TIDAK boleh menampilkan nasabah pending
        $this->actingAs($this->bendaharaMini);
        $respBbmForm = $this->get(route('bbm.pinjaman.ajukan'));
        $respBbmForm->assertStatus(200);
        $respBbmForm->assertDontSee('Reza Dwi Saputro — Tata Usaha');

        // D. Cek jika BBM mencoba submit pinjaman untuk nasabah pending: Ditolak dengan pesan error
        $respBbmStore = $this->post(route('bbm.pinjaman.store'), [
            'nasabah_id'       => $nasabahPending->nasabah_id,
            'nominal_pinjaman' => 3000000,
            'tenor'            => 10,
            'metode_bayar'     => 'potong_gaji',
            'tgl_pengajuan'    => date('Y-m-d'),
        ]);
        $respBbmStore->assertSessionHas('error');
    }

    /**
     * Uji Validasi Pinjaman Berjalan: Nasabah tidak dapat mengajukan pinjaman baru jika masih memiliki pinjaman aktif.
     */
    public function test_nasabah_tidak_bisa_mengajukan_pinjaman_baru_jika_pinjaman_lama_masih_berjalan(): void
    {
        // 1. Buat pinjaman aktif berjalan (status: cair) untuk nasabah1
        $pinjId = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $this->nasabah1->nasabah_id,
            'user_id'          => $this->bendaharaMini->user_id,
            'nominal_pinjaman' => 2000000,
            'tenor'            => 5,
            'bunga_persen'     => 2.0,
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'cair',
            'tgl_pengajuan'    => Carbon::now()->toDateString(),
        ]);

        // A. Dari sisi Nasabah (Web Nasabah)
        $this->actingAs($this->userNasabah1);
        
        // Akses halaman form ajukan pinjaman tetap terbuka namun menampilkan notifikasi terkunci
        $respForm = $this->get(route('nasabah.pinjaman.ajukan'));
        $respForm->assertStatus(200);
        $respForm->assertSee('Fasilitas Pinjaman Baru Belum Dapat Diajukan');
        $respForm->assertSee('Pinjaman Aktif');

        // Percobaan submit form juga harus ditolak
        $respSubmit = $this->post(route('nasabah.pinjaman.store'), [
            'nominal_pinjaman' => 1000000,
            'tenor'            => 3,
            'metode_bayar'     => 'potong_gaji',
        ]);
        $respSubmit->assertRedirect(route('nasabah.pinjaman'));
        $respSubmit->assertSessionHas('error');

        // B. Dari sisi Bendahara Bank Mini (Loket / Form BBM)
        $this->actingAs($this->bendaharaMini);

        // Form ajukan pinjaman BBM menandai nasabah tersebut memiliki pinjaman aktif
        $respBbmForm = $this->get(route('bbm.pinjaman.ajukan'));
        $respBbmForm->assertStatus(200);
        $respBbmForm->assertSee('Pinjaman Aktif Sedang Berjalan');

        // BBM submit permohonan baru untuk nasabah tersebut harus ditolak
        $respBbmStore = $this->post(route('bbm.pinjaman.store'), [
            'nasabah_id'       => $this->nasabah1->nasabah_id,
            'nominal_pinjaman' => 1500000,
            'tenor'            => 4,
            'metode_bayar'     => 'potong_gaji',
            'tgl_pengajuan'    => Carbon::now()->toDateString(),
        ]);
        $respBbmStore->assertSessionHas('error');

        // C. Jika pinjaman tersebut diubah menjadi lunas, nasabah baru bisa mengajukan lagi
        DB::table('tblPINJAMAN')->where('pinjaman_id', $pinjId)->update(['status_pinjaman' => 'lunas']);

        $this->actingAs($this->userNasabah1);
        $respFormLunas = $this->get(route('nasabah.pinjaman.ajukan'));
        $respFormLunas->assertStatus(200);
    }

    /**
     * Uji Siklus Koreksi dan Pembayaran Ulang:
     * Transaksi angsuran yang dikoreksi (dibatalkan) dapat dibayar kembali tanpa duplicate entry violation.
     */
    public function test_angsuran_yang_dibatalkan_dapat_dibayar_kembali_tanpa_duplicate_key_error(): void
    {
        // 1. Setup master kas jika belum ada
        if (!DB::table('tblKAS')->where('kas_id', 1)->exists()) {
            DB::table('tblKAS')->insert([
                'kas_id'    => 1,
                'nama_kas'  => 'Kas Tunai Kasir',
                'jenis_kas' => 'kas',
                'saldo'     => 10000000,
            ]);
        }

        // 2. Buat pinjaman dan jadwal angsuran
        $pinjId = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $this->nasabah1->nasabah_id,
            'user_id'          => $this->bendaharaMini->user_id,
            'nominal_pinjaman' => 1000000,
            'tenor'            => 2,
            'bunga_persen'     => 2.0,
            'metode_bayar'     => 'tunai',
            'status_pinjaman'  => 'cair',
            'tgl_pengajuan'    => Carbon::now()->toDateString(),
        ]);

        $jadwalId = DB::table('tblJADWAL_ANGSURAN')->insertGetId([
            'pinjaman_id'  => $pinjId,
            'angsuran_ke'  => 1,
            'jatuh_tempo'  => Carbon::now()->toDateString(),
            'pokok'        => 500000,
            'bunga'        => 20000,
            'status_bayar' => 'belum',
        ]);

        $this->actingAs($this->bendaharaMini);

        // 3. Bayar angsuran ke-1 pertama kali
        $pay1 = $this->post(route('bbm.angsuran.langsung.simpan'), [
            'jadwal_angsuran_id' => $jadwalId,
            'tgl_bayar'          => Carbon::now()->toDateString(),
            'bayar_pokok'        => 500000,
            'bayar_bunga'        => 20000,
        ]);
        $pay1->assertSessionHas('success');
        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $jadwalId, 'status_bayar' => 'lunas']);

        $angsuran1 = DB::table('tblANGSURAN')->where('jadwal_angsuran_id', $jadwalId)->first();
        $this->assertNotNull($angsuran1);

        // 4. Koreksi / batalkan angsuran tersebut
        $koreksiResp = $this->post(route('bbm.angsuran.koreksi.proses', $angsuran1->angsuran_id), [
            'alasan_koreksi' => 'Salah input nasabah pembayar di loket',
        ]);
        $koreksiResp->assertSessionHas('success');
        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $jadwalId, 'status_bayar' => 'belum']);

        // 5. Bayar ulang angsuran ke-1 (seharusnya TIDAK crash dengan duplicate key 1062)
        $pay2 = $this->post(route('bbm.angsuran.langsung.simpan'), [
            'jadwal_angsuran_id' => $jadwalId,
            'tgl_bayar'          => Carbon::now()->toDateString(),
            'bayar_pokok'        => 500000,
            'bayar_bunga'        => 20000,
        ]);
        $pay2->assertSessionHas('success');
        $this->assertDatabaseHas('tblJADWAL_ANGSURAN', ['jadwal_angsuran_id' => $jadwalId, 'status_bayar' => 'lunas']);

        // Pastikan sekarang ada 2 baris transaksi angsuran tercatat untuk jadwal ini (1 yang dibatalkan, 1 yang sah)
        $this->assertEquals(2, DB::table('tblANGSURAN')->where('jadwal_angsuran_id', $jadwalId)->count());
    }
}


