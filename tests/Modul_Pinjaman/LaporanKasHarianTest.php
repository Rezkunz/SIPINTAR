<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanKasHarianTest extends TestCase
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
            'role_id' => 4, 'no_hp' => '081200000004', 'no_induk' => 'MGR001',
            'nama_user' => 'Manager', 'password' => bcrypt('password'), 'status_akun' => 'aktif',
        ]);

        $this->teller = User::create([
            'role_id' => 1, 'no_hp' => '081200000001', 'no_induk' => 'TLR001',
            'nama_user' => 'Teller', 'password' => bcrypt('password'), 'status_akun' => 'aktif',
        ]);

        DB::table('tblJENIS_TRANSAKSI')->insert([
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan'],
        ]);

        DB::table('tblPERIODE')->insert(['periode_id' => 1, 'tahun' => 2026, 'bulan' => 3, 'status_periode' => 'buka']);

        $nasabah = fn ($id, $nama, $jenis) => ['nasabah_id' => $id, 'no_hp' => "08{$id}00", 'nama_nasabah' => $nama, 'jenis_nasabah' => $jenis, 'status_nasabah' => 'aktif'];
        DB::table('tblNASABAH')->insert([
            $nasabah(1, 'RISKA RISMAYANTI', 'non_pegawai'),
            $nasabah(2, 'UMAR ALFARUQ', 'non_pegawai'),
            $nasabah(3, 'BUDHI SUPRIYADI', 'pegawai'),
        ]);

        $rekening = fn ($id, $noRek) => ['rekening_id' => $id, 'no_rek' => $noRek, 'nasabah_id' => $id, 'tgl_buka' => '2020-07-01', 'saldo' => 0, 'status_rekening' => 'aktif'];
        DB::table('tblREKENING')->insert([
            $rekening(1, '1836/TP/BM/2013'),
            $rekening(2, '2751/TP/BM/2018'),
            $rekening(3, '010/TK/BM/2004'),
        ]);

        $tellerId = $this->teller->user_id;
        $trx = fn ($rek, $jenis, $debit, $kredit, $tgl) => [
            'rekening_id' => $rek, 'jenis_transaksi_id' => $jenis, 'periode_id' => 1, 'user_id' => $tellerId,
            'debit' => $debit, 'kredit' => $kredit, 'saldo_akhir' => 0, 'tgl_transaksi' => $tgl,
        ];

        DB::table('tblTRANSAKSI')->insert([
            // Februari → membentuk kas awal Maret 600.000
            $trx(1, 1, 0, 100000, '2026-02-10 09:00:00'),
            $trx(3, 1, 0, 500000, '2026-02-11 09:00:00'),
            $trx(1, 3, 0, 50, '2026-02-28 16:00:00'),          // jasa: bukan uang kas
            // Maret
            $trx(2, 1, 0, 25000, '2026-03-02 10:30:00'),
            $trx(1, 1, 0, 50000, '2026-03-02 08:15:00'),
            $trx(3, 2, 200000, 0, '2026-03-02 13:00:00'),
            $trx(2, 2, 10000, 0, '2026-03-05 09:00:00'),
            $trx(1, 3, 0, 150, '2026-03-31 16:00:00'),         // jasa: bukan uang kas
        ]);
    }

    private function buku(array $param = [])
    {
        return $this->actingAs($this->manager)
            ->get(route('laporan.tabungan.kas', array_merge(['bulan' => 3, 'tahun' => 2026], $param)));
    }

    public function test_kas_awal_bulan_dari_akumulasi_setoran_dikurangi_penarikan_sebelumnya(): void
    {
        $response = $this->buku();

        $response->assertOk();
        $this->assertEquals(600000.0, $response->viewData('kasAwalBulan'));
    }

    public function test_ringkasan_per_hari_berantai_dari_kas_awal_ke_kas_akhir(): void
    {
        $hari = $this->buku()->viewData('hari');

        $this->assertCount(2, $hari);

        [$h1, $h2] = [$hari[0], $hari[1]];
        $this->assertEquals('2026-03-02', $h1->tanggal->toDateString());
        $this->assertEquals(600000.0, $h1->kas_awal);
        $this->assertEquals(75000.0, $h1->siswa_masuk);
        $this->assertEquals(0.0, $h1->siswa_keluar);
        $this->assertEquals(0.0, $h1->guru_masuk);
        $this->assertEquals(200000.0, $h1->guru_keluar);
        $this->assertEquals(475000.0, $h1->kas_akhir);
        $this->assertEquals(3, $h1->jumlah_transaksi);

        $this->assertEquals(475000.0, $h2->kas_awal);
        $this->assertEquals(10000.0, $h2->siswa_keluar);
        $this->assertEquals(465000.0, $h2->kas_akhir);
    }

    public function test_total_bulan_dan_jasa_tidak_ikut_dihitung(): void
    {
        $response = $this->buku();

        $total = $response->viewData('total');
        $this->assertEquals(75000.0, $total['total_masuk']);
        $this->assertEquals(210000.0, $total['total_keluar']);
        $this->assertEquals(4, $total['transaksi']);
        $this->assertEquals(465000.0, $response->viewData('kasAkhirBulan'));
    }

    public function test_rincian_tanggal_terpilih_urut_kronologis(): void
    {
        $response = $this->buku(['tanggal' => '2026-03-02']);

        $response->assertOk();
        $response->assertSee('Rincian Transaksi');

        $rincian = $response->viewData('rincian');
        $this->assertEquals(3, $rincian->total());
        $this->assertEquals(['RISKA RISMAYANTI', 'UMAR ALFARUQ', 'BUDHI SUPRIYADI'], collect($rincian->items())->pluck('nama_nasabah')->all());
    }

    public function test_tanggal_di_luar_bulan_terpilih_diabaikan(): void
    {
        $response = $this->buku(['tanggal' => '2026-02-10']);

        $response->assertOk();
        $this->assertNull($response->viewData('tanggalTerpilih'));
        $this->assertNull($response->viewData('rincian'));
    }

    public function test_tanpa_tanggal_rincian_tidak_ditampilkan(): void
    {
        $response = $this->buku();

        $this->assertNull($response->viewData('rincian'));
        $response->assertDontSee('Rincian Transaksi');
    }

    public function test_ekspor_csv_buku_kas(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan.kas.csv', ['bulan' => 3, 'tahun' => 2026]));

        $response->assertOk();
        $this->assertMatchesRegularExpression('/BUKU_KAS_HARIAN_[A-Z]+_2026\.csv/', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('1;02/03/2026;600000;75000;0;0;200000;75000;200000;475000', $csv);
        $this->assertStringContainsString('"RINCIAN TRANSAKSI"', $csv);
        $this->assertStringContainsString('010/TK/BM/2004;"BUDHI SUPRIYADI";;;200000;', $csv);
    }

    public function test_buku_kas_berada_di_menu_laporan_tabungan(): void
    {
        $this->actingAs($this->manager)
            ->get(route('laporan.tabungan'))
            ->assertOk()
            ->assertSee('laporan/tabungan/kas-harian?', false);
    }

    public function test_menu_laporan_pinjaman_tidak_memuat_laporan_tabungan(): void
    {
        $this->actingAs($this->manager)
            ->get(route('laporan.pinjaman'))
            ->assertOk()
            ->assertDontSee('Buku Kas Harian')
            ->assertDontSee(route('laporan.tabungan.kas'));
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)
            ->get(route('laporan.tabungan.kas'))
            ->assertForbidden();
    }
}
