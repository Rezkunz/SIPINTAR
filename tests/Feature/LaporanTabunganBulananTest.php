<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanTabunganBulananTest extends TestCase
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
            $nasabah(3, 'SISWA BARU APRIL', 'non_pegawai'),
            $nasabah(4, 'SISWA REKENING TUTUP', 'non_pegawai'),
            $nasabah(5, 'BUDHI SUPRIYADI', 'pegawai'),
        ]);

        $rekening = fn ($id, $noRek, $tglBuka, $status = 'aktif') => ['rekening_id' => $id, 'no_rek' => $noRek, 'nasabah_id' => $id, 'tgl_buka' => $tglBuka, 'saldo' => 0, 'status_rekening' => $status];
        DB::table('tblREKENING')->insert([
            $rekening(1, '1836/TP/BM/2013', '2013-07-01'),
            $rekening(2, '2751/TP/BM/2018', '2018-07-01'),
            $rekening(3, '3300/TP/BM/2026', '2026-04-02'),
            $rekening(4, '3100/TP/BM/2020', '2020-07-01', 'tutup'),
            $rekening(5, '010/TK/BM/2004', '2004-07-01'),
        ]);

        $tellerId = $this->teller->user_id;
        $trx = fn ($rek, $jenis, $debit, $kredit, $saldo, $tgl) => [
            'rekening_id' => $rek, 'jenis_transaksi_id' => $jenis, 'periode_id' => 1, 'user_id' => $tellerId,
            'debit' => $debit, 'kredit' => $kredit, 'saldo_akhir' => $saldo, 'tgl_transaksi' => $tgl,
        ];

        DB::table('tblTRANSAKSI')->insert([
            $trx(1, 1, 0, 100000, 100000, '2026-02-10 09:00:00'),
            $trx(1, 1, 0, 70000, 170000, '2026-03-05 09:00:00'),
            $trx(1, 2, 20000, 0, 150000, '2026-03-05 09:00:00'),      // jam sama: transaksi_id terbesar yang dipakai
            $trx(1, 3, 0, 150, 150150, '2026-03-31 16:00:00'),        // jasa Maret yang sudah dibukukan
            $trx(1, 1, 0, 99999, 250149, '2026-04-01 09:00:00'),      // bulan berikutnya
            $trx(3, 1, 0, 50000, 50000, '2026-04-02 09:00:00'),
            $trx(4, 2, 30000, 0, 0, '2026-01-15 09:00:00'),
            $trx(5, 1, 0, 5600000, 5600000, '2026-03-03 09:00:00'),
        ]);
    }

    private function rekap(array $param)
    {
        return $this->actingAs($this->manager)
            ->get(route('laporan.tabungan.bulanan', array_merge(['bulan' => 3, 'tahun' => 2026], $param)));
    }

    public function test_pokok_diambil_dari_saldo_akhir_bulan_tanpa_menghitung_ulang_jasa(): void
    {
        $response = $this->rekap(['kategori' => 'siswa']);

        $response->assertOk();
        $baris = $response->viewData('baris')->keyBy('rekening_id');

        $this->assertEquals(150000.0, $baris[1]->pokok);
        $this->assertEquals(150.0, $baris[1]->jasa);          // 0,1% × 150.000
        $this->assertEquals(150150.0, $baris[1]->total);

        $this->assertEquals(0.0, $baris[2]->pokok);
        $this->assertEquals(0.0, $baris[2]->jasa);
    }

    public function test_rekening_belum_dibuka_dan_rekening_tutup_bersaldo_nol_tidak_ikut(): void
    {
        $response = $this->rekap(['kategori' => 'siswa']);

        $response->assertSee('RISKA RISMAYANTI');
        $response->assertDontSee('SISWA BARU APRIL');
        $response->assertDontSee('SISWA REKENING TUTUP');
        $this->assertEquals(2, $response->viewData('ringkasan')['jumlah_rekening']);
        $this->assertEquals(1, $response->viewData('ringkasan')['rekening_bersaldo']);
    }

    public function test_bulan_sebelumnya_memakai_saldo_bulan_itu(): void
    {
        $baris = $this->rekap(['kategori' => 'siswa', 'bulan' => 2])->viewData('baris')->keyBy('rekening_id');

        $this->assertEquals(100000.0, $baris[1]->pokok);
        $this->assertEquals(100.0, $baris[1]->jasa);
    }

    public function test_tarif_jasa_guru_dua_permil(): void
    {
        $response = $this->rekap(['kategori' => 'guru']);

        $response->assertSee('BUDHI SUPRIYADI');
        $response->assertDontSee('RISKA RISMAYANTI');

        $ringkasan = $response->viewData('ringkasan');
        $this->assertEquals(5600000.0, $ringkasan['total_pokok']);
        $this->assertEquals(11200.0, $ringkasan['total_jasa']);    // 0,2% × 5.600.000
        $this->assertEquals(5611200.0, $ringkasan['total_akhir']);
    }

    public function test_pencarian_menyaring_rekap_bulanan(): void
    {
        $response = $this->rekap(['kategori' => 'siswa', 'cari' => 'umar']);

        $response->assertSee('UMAR ALFARUQ');
        $response->assertDontSee('RISKA RISMAYANTI');
        $this->assertEquals(0.0, $response->viewData('ringkasan')['total_pokok']);
    }

    public function test_ekspor_csv_rekap_bulanan(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan.bulanan.csv', ['kategori' => 'siswa', 'bulan' => 3, 'tahun' => 2026]));

        $response->assertOk();
        $this->assertStringContainsString('REKAP_TABUNGAN_JASA_SISWA', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('"JASA 0,1%"', $csv);
        $this->assertStringContainsString('1836/TP/BM/2013;"RISKA RISMAYANTI";150000;150;150150', $csv);
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)
            ->get(route('laporan.tabungan.bulanan'))
            ->assertForbidden();
    }
}
