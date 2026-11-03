<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanTabunganTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'Teller'],
            ['role_id' => 4, 'nama_role' => 'Manager'],
        ]);

        $this->manager = User::create([
            'user_id'     => 1,
            'role_id'     => 4,
            'no_hp'       => '081200000004',
            'no_induk'    => 'MGR001',
            'nama_user'   => 'Manager',
            'password'    => bcrypt('password'),
            'status_akun' => 'aktif',
        ]);

        $this->teller = User::create([
            'user_id'     => 2,
            'role_id'     => 1,
            'no_hp'       => '081200000001',
            'no_induk'    => 'TLR001',
            'nama_user'   => 'Teller',
            'password'    => bcrypt('password'),
            'status_akun' => 'aktif',
        ]);

        DB::table('tblJENIS_TRANSAKSI')->insert([
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan'],
        ]);

        DB::table('tblPERIODE')->insert(['periode_id' => 1, 'tahun' => 2026, 'bulan' => 3, 'status_periode' => 'buka']);

        DB::table('tblNASABAH')->insert([
            ['nasabah_id' => 1, 'no_hp' => '0811', 'nama_nasabah' => 'RISKA RISMAYANTI', 'jenis_nasabah' => 'non_pegawai', 'kelas_unit' => 'XI AKL 1', 'status_nasabah' => 'aktif'],
            ['nasabah_id' => 2, 'no_hp' => '0812', 'nama_nasabah' => 'UMAR ALFARUQ',     'jenis_nasabah' => 'non_pegawai', 'kelas_unit' => 'XII BDP 2', 'status_nasabah' => 'aktif'],
            ['nasabah_id' => 3, 'no_hp' => '0813', 'nama_nasabah' => 'BUDHI SUPRIYADI',  'jenis_nasabah' => 'pegawai',     'kelas_unit' => 'Guru', 'status_nasabah' => 'aktif'],
        ]);

        DB::table('tblREKENING')->insert([
            ['rekening_id' => 1, 'no_rek' => '1836/TP/BM/2013', 'nasabah_id' => 1, 'tgl_buka' => '2013-07-01', 'saldo' => 0, 'status_rekening' => 'aktif'],
            ['rekening_id' => 2, 'no_rek' => '2751/TP/BM/2018', 'nasabah_id' => 2, 'tgl_buka' => '2018-07-01', 'saldo' => 0, 'status_rekening' => 'aktif'],
            ['rekening_id' => 3, 'no_rek' => '010/TK/BM/2004',  'nasabah_id' => 3, 'tgl_buka' => '2004-07-01', 'saldo' => 0, 'status_rekening' => 'aktif'],
        ]);

        $tellerId = $this->teller->user_id;
        $trx = fn ($rek, $jenis, $debit, $kredit, $tgl) => [
            'rekening_id' => $rek, 'jenis_transaksi_id' => $jenis, 'periode_id' => 1, 'user_id' => $tellerId,
            'debit' => $debit, 'kredit' => $kredit, 'saldo_akhir' => 0, 'tgl_transaksi' => $tgl,
        ];

        DB::table('tblTRANSAKSI')->insert([
            $trx(1, 1, 0, 50000, '2026-03-02 08:00:00'),      // setoran
            $trx(1, 1, 0, 25000, '2026-03-02 10:00:00'),      // setoran hari yang sama
            $trx(1, 2, 80000, 0, '2026-03-05 09:00:00'),      // penarikan
            $trx(1, 3, 0, 100, '2026-03-31 15:00:00'),        // jasa: tidak ikut direkap
            $trx(2, 1, 0, 20000, '2026-03-07 09:00:00'),      // setoran hari Sabtu
            $trx(2, 1, 0, 99999, '2026-04-01 09:00:00'),      // bulan berikutnya
            $trx(3, 1, 0, 5600000, '2026-03-03 09:00:00'),    // guru
        ]);
    }

    public function test_manager_melihat_rekap_harian_siswa(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026]));

        $response->assertOk();
        $response->assertSee('RISKA RISMAYANTI');
        $response->assertSee('UMAR ALFARUQ');
        $response->assertDontSee('BUDHI SUPRIYADI');

        $baris = $response->viewData('baris')->keyBy('rekening_id');
        $this->assertEquals(['2026-03-02' => 75000.0, '2026-03-05' => -80000.0], $baris[1]->harian);
        $this->assertEquals(-5000.0, $baris[1]->total);
        $this->assertEquals(20000.0, $baris[2]->total);

        $ringkasan = $response->viewData('ringkasan');
        $this->assertEquals(95000.0, $ringkasan['total_setoran']);
        $this->assertEquals(80000.0, $ringkasan['total_penarikan']);
        $this->assertEquals(15000.0, $ringkasan['mutasi_bersih']);
        $this->assertEquals(2, $ringkasan['rekening_aktif']);
    }

    public function test_kolom_tanggal_hari_kerja_ditambah_akhir_pekan_yang_bertransaksi(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026]));

        $tanggal = $response->viewData('tanggalList')->map->toDateString();

        // Maret 2026 punya 22 hari kerja, ditambah Sabtu 7 Maret yang ada setoran
        $this->assertCount(23, $tanggal);
        $this->assertContains('2026-03-07', $tanggal);
        $this->assertNotContains('2026-03-08', $tanggal);
    }

    public function test_rekap_guru_hanya_memuat_rekening_pegawai(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'guru', 'bulan' => 3, 'tahun' => 2026]));

        $response->assertOk();
        $response->assertSee('BUDHI SUPRIYADI');
        $response->assertDontSee('RISKA RISMAYANTI');
        $this->assertEquals(5600000.0, $response->viewData('ringkasan')['mutasi_bersih']);
    }

    public function test_ekspor_csv_berisi_rekap(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan.csv', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026]));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('1836/TP/BM/2013;"RISKA RISMAYANTI"', $csv);
        $this->assertStringContainsString(';JUMLAH;', $csv);
    }

    public function test_pencarian_berdasarkan_nama_atau_no_rekening(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026, 'cari' => 'umar']));

        $response->assertOk();
        $response->assertSee('UMAR ALFARUQ');
        $response->assertDontSee('RISKA RISMAYANTI');
        $this->assertEquals(20000.0, $response->viewData('ringkasan')['mutasi_bersih']);

        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026, 'cari' => '1836/TP']));

        $response->assertSee('RISKA RISMAYANTI');
        $response->assertDontSee('UMAR ALFARUQ');
    }

    public function test_tabel_dibagi_per_halaman_dengan_total_tetap_menyeluruh(): void
    {
        for ($i = 10; $i < 22; $i++) {
            DB::table('tblNASABAH')->insert(['nasabah_id' => $i, 'no_hp' => "0899{$i}", 'nama_nasabah' => "SISWA {$i}", 'jenis_nasabah' => 'non_pegawai', 'status_nasabah' => 'aktif']);
            DB::table('tblREKENING')->insert(['rekening_id' => $i, 'no_rek' => "9{$i}/TP/BM/2025", 'nasabah_id' => $i, 'tgl_buka' => '2025-07-01', 'saldo' => 0, 'status_rekening' => 'aktif']);
        }

        $halaman1 = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026]));
        $halaman2 = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026, 'page' => 2]));

        $this->assertCount(10, $halaman1->viewData('baris'));
        $this->assertEquals(14, $halaman1->viewData('baris')->total());
        $this->assertCount(4, $halaman2->viewData('baris'));
        $this->assertEquals(15000.0, $halaman2->viewData('ringkasan')['mutasi_bersih']);
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)
            ->get(route('laporan.tabungan'))
            ->assertForbidden();
    }
}
