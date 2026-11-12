<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanTahunanTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-02 10:00:00'));

        DB::table('tblROLE')->insert([
            ['role_id' => 1, 'nama_role' => 'Teller'],
            ['role_id' => 3, 'nama_role' => 'Bendahara Bank Mini'],
            ['role_id' => 4, 'nama_role' => 'Manager'],
        ]);

        $user = fn ($role, $hp, $induk, $nama) => User::create([
            'role_id' => $role, 'no_hp' => $hp, 'no_induk' => $induk,
            'nama_user' => $nama, 'password' => bcrypt('password'), 'status_akun' => 'aktif',
        ]);
        $this->manager = $user(4, '081200000004', 'MGR001', 'Manager');
        $this->teller  = $user(1, '081200000001', 'TLR001', 'Teller');
        $bbm           = $user(3, '081200000003', 'BBM001', 'Bendahara Bank Mini')->user_id;
        $tellerId      = $this->teller->user_id;

        DB::table('tblJENIS_TRANSAKSI')->insert([
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan'],
        ]);
        DB::table('tblPERIODE')->insert(['periode_id' => 1, 'tahun' => 2026, 'bulan' => 1, 'status_periode' => 'buka']);
        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 0],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank', 'jenis_kas' => 'bank', 'saldo' => 0],
        ]);

        // Tabungan: siswa 1 jt sejak Januari (jasa 1.000/bln), guru 5 jt sejak Februari (jasa 10.000/bln)
        DB::table('tblNASABAH')->insert([
            ['nasabah_id' => 1, 'no_hp' => '0810', 'nama_nasabah' => 'SISWA A', 'jenis_nasabah' => 'non_pegawai', 'status_nasabah' => 'aktif'],
            ['nasabah_id' => 2, 'no_hp' => '0820', 'nama_nasabah' => 'GURU B', 'jenis_nasabah' => 'pegawai', 'status_nasabah' => 'aktif'],
            ['nasabah_id' => 3, 'no_hp' => '0830', 'nama_nasabah' => 'PEMINJAM C', 'jenis_nasabah' => 'pegawai', 'status_nasabah' => 'aktif'],
        ]);
        DB::table('tblREKENING')->insert([
            ['rekening_id' => 1, 'no_rek' => '1001-2026-00001', 'nasabah_id' => 1, 'tgl_buka' => '2026-01-10', 'saldo' => 1000000, 'status_rekening' => 'aktif'],
            ['rekening_id' => 2, 'no_rek' => '1001-2026-00002', 'nasabah_id' => 2, 'tgl_buka' => '2026-02-15', 'saldo' => 5000000, 'status_rekening' => 'aktif'],
        ]);
        DB::table('tblTRANSAKSI')->insert([
            ['rekening_id' => 1, 'jenis_transaksi_id' => 1, 'periode_id' => 1, 'user_id' => $tellerId, 'debit' => 0, 'kredit' => 1000000, 'saldo_akhir' => 1000000, 'tgl_transaksi' => '2026-01-10 09:00:00'],
            ['rekening_id' => 2, 'jenis_transaksi_id' => 1, 'periode_id' => 1, 'user_id' => $tellerId, 'debit' => 0, 'kredit' => 5000000, 'saldo_akhir' => 5000000, 'tgl_transaksi' => '2026-02-15 09:00:00'],
        ]);

        // Pinjaman dengan 3 angsuran: Des 2025 (tahun lain), Mar 2026 (sah), Apr 2026 (dikoreksi)
        DB::table('tblPINJAMAN')->insert([
            'pinjaman_id' => 1, 'nasabah_id' => 3, 'user_id' => $bbm, 'nominal_pinjaman' => 1200000, 'tenor' => 12,
            'bunga_persen' => 2, 'tgl_pengajuan' => '2025-11-01', 'metode_bayar' => 'tunai', 'status_pinjaman' => 'cair',
        ]);
        DB::table('tblPENCAIRAN')->insert(['pencairan_id' => 1, 'pinjaman_id' => 1, 'user_id' => $bbm, 'tgl_cair' => '2025-11-01', 'nominal_cair' => 1200000]);
        foreach ([1 => '2025-12-01', 2 => '2026-03-05', 3 => '2026-04-05'] as $ke => $tgl) {
            DB::table('tblJADWAL_ANGSURAN')->insert(['jadwal_angsuran_id' => $ke, 'pinjaman_id' => 1, 'angsuran_ke' => $ke, 'jatuh_tempo' => $tgl, 'pokok' => 100000, 'bunga' => 24000, 'status_bayar' => 'lunas']);
        }
        DB::table('tblANGSURAN')->insert([
            ['angsuran_id' => 1, 'jadwal_angsuran_id' => 1, 'user_id' => $bbm, 'tgl_bayar' => '2025-12-01', 'jenis_bayar' => 'angsuran', 'bayar_pokok' => 100000, 'bayar_bunga' => 50000],
            ['angsuran_id' => 2, 'jadwal_angsuran_id' => 2, 'user_id' => $bbm, 'tgl_bayar' => '2026-03-05', 'jenis_bayar' => 'angsuran', 'bayar_pokok' => 100000, 'bayar_bunga' => 12000],
            ['angsuran_id' => 3, 'jadwal_angsuran_id' => 3, 'user_id' => $bbm, 'tgl_bayar' => '2026-04-05', 'jenis_bayar' => 'angsuran', 'bayar_pokok' => 100000, 'bayar_bunga' => 24000],
        ]);
        DB::table('tblKOREKSI_ANGSURAN')->insert([
            'angsuran_id' => 3, 'user_id' => $bbm, 'tgl_koreksi' => '2026-04-06', 'alasan_koreksi' => 'Salah input',
            'nominal_pokok' => 100000, 'nominal_bunga' => 24000, 'nominal_total' => 124000,
        ]);

        $mutasi = fn ($kas, $tgl, $arah, $nominal, $uraian, $extra = []) => DB::table('tblMUTASI_KAS')->insert(array_merge([
            'kas_id' => $kas, 'periode_id' => 1, 'user_id' => $bbm, 'tgl_mutasi' => $tgl,
            'arah' => $arah, 'nominal' => $nominal, 'uraian' => $uraian,
        ], $extra));
        // Kas manual (dihitung)
        $mutasi(2, '2026-05-10', 'masuk', 300000, 'Bunga deposito BPR');
        $mutasi(1, '2026-06-01', 'keluar', 1350000, 'Honor pengurus');
        // Bukan kas manual (tidak dihitung sebagai pendapatan/beban lain)
        $mutasi(1, '2026-03-05', 'masuk', 112000, 'Penerimaan angsuran langsung', ['angsuran_id' => 2]);
        $mutasi(2, '2026-03-10', 'masuk', 100000, 'Penerimaan hasil potongan gaji paket #RP-1 dari Bendahara Sekolah (TRANSFER)');
        $mutasi(1, '2026-01-02', 'keluar', 1200000, 'Pencairan pinjaman #PINJ-1', ['pencairan_id' => 1]);
        $mutasi(1, '2025-07-01', 'keluar', 500000, 'Honor pengurus 2025');
    }

    private function laporan(int $tahun = 2026)
    {
        return $this->actingAs($this->manager)->get(route('laporan.tahunan', ['tahun' => $tahun]));
    }

    public function test_laba_rugi_tahun_berjalan(): void
    {
        $response = $this->laporan();

        $response->assertOk();
        $total = $response->viewData('total');

        $this->assertEquals(12000.0, $total['jasa_pinjaman']);       // angsuran dikoreksi & tahun lain tidak dihitung
        $this->assertEquals(300000.0, $total['pendapatan_lain']);
        $this->assertEquals(312000.0, $total['pendapatan']);
        $this->assertEquals(11000.0, $total['jasa_siswa']);          // 1.000 × Jan–Nov
        $this->assertEquals(100000.0, $total['jasa_guru']);          // 10.000 × Feb–Nov
        $this->assertEquals(1350000.0, $total['beban_operasional']);
        $this->assertEquals(1461000.0, $total['beban']);
        $this->assertEquals(-1149000.0, $total['laba']);
    }

    public function test_dasar_perhitungan_per_bulan_dan_bulan_belum_berjalan(): void
    {
        $response = $this->laporan();

        $this->assertEquals(11, $response->viewData('bulanTerakhir'));
        $bulanan = $response->viewData('bulanan')->keyBy('bulan');

        $this->assertEquals(12000.0, $bulanan[3]->jasa_pinjaman);
        $this->assertEquals(0.0, $bulanan[4]->jasa_pinjaman);
        $this->assertEquals(0.0, $bulanan[1]->jasa_guru);
        $this->assertEquals(10000.0, $bulanan[2]->jasa_guru);
        $this->assertEquals(-1361000.0, $bulanan[6]->laba);          // 0 - (1.000 + 10.000 + 1.350.000)

        $this->assertFalse($bulanan[12]->dihitung);
        $this->assertEquals(0.0, $bulanan[12]->jasa_siswa);
    }

    public function test_beban_jasa_sama_dengan_rekap_tabungan_bulanan(): void
    {
        $bulanan = $this->laporan()->viewData('bulanan')->keyBy('bulan');

        $rekapGuruMaret = $this->actingAs($this->manager)
            ->get(route('laporan.tabungan.bulanan', ['kategori' => 'guru', 'bulan' => 3, 'tahun' => 2026]))
            ->viewData('ringkasan');

        $this->assertEquals($rekapGuruMaret['total_jasa'], $bulanan[3]->jasa_guru);
    }

    public function test_kas_manual_hanya_memuat_mutasi_non_sistem(): void
    {
        $kasManual = $this->laporan()->viewData('kasManual');

        $this->assertEquals(2, $kasManual->total());
        $this->assertEqualsCanonicalizing(['Bunga deposito BPR', 'Honor pengurus'], collect($kasManual->items())->pluck('uraian')->all());
    }

    public function test_tahun_lalu_dihitung_penuh_dan_tahun_depan_belum_berjalan(): void
    {
        $response = $this->laporan(2025);
        $this->assertEquals(12, $response->viewData('bulanTerakhir'));
        $this->assertEquals(50000.0, $response->viewData('total')['jasa_pinjaman']);
        $this->assertEquals(500000.0, $response->viewData('total')['beban_operasional']);
        $this->assertEquals(-450000.0, $response->viewData('total')['laba']);

        $response = $this->laporan(2027);
        $response->assertOk();
        $this->assertEquals(0, $response->viewData('bulanTerakhir'));
        $this->assertEquals(0.0, $response->viewData('total')['laba']);
    }

    public function test_ekspor_csv_laporan_tahunan(): void
    {
        $response = $this->actingAs($this->manager)->get(route('laporan.tahunan.csv', ['tahun' => 2026]));

        $response->assertOk();
        $this->assertStringContainsString('LAPORAN_KEUANGAN_TAHUNAN_2026.csv', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Pendapatan Jasa Pinjaman";12000', $csv);
        $this->assertStringContainsString('"RUGI BERSIH";-1149000', $csv);
        $this->assertStringContainsString('"Bunga deposito BPR";300000;', $csv);
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)
            ->get(route('laporan.tahunan'))
            ->assertForbidden();
    }
}
