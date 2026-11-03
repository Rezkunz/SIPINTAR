<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LaporanEksporTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;

    /**
     * route dasar => parameter periode yang diuji
     */
    private const LAPORAN = [
        'laporan.tabungan'         => ['kategori' => 'siswa', 'bulan' => 6, 'tahun' => 2026],
        'laporan.tabungan.bulanan' => ['kategori' => 'guru', 'bulan' => 6, 'tahun' => 2026],
        'laporan.tabungan.kas'     => ['bulan' => 6, 'tahun' => 2026],
        'laporan.pinjaman'         => ['bulan' => 6, 'tahun' => 2026],
        'laporan.pinjaman.kas'     => ['bulan' => 6, 'tahun' => 2026],
        'laporan.tahunan'          => ['tahun' => 2026],
        'laporan.neraca'           => ['bulan' => 6, 'tahun' => 2026],
    ];

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

        DB::table('tblJENIS_TRANSAKSI')->insert([
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan'],
        ]);
        DB::table('tblPERIODE')->insert(['periode_id' => 1, 'tahun' => 2026, 'bulan' => 1, 'status_periode' => 'buka']);
        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 10000000],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank', 'jenis_kas' => 'bank', 'saldo' => 2000000],
        ]);

        DB::table('tblNASABAH')->insert([
            ['nasabah_id' => 1, 'no_hp' => '0810', 'nama_nasabah' => 'RISKA RISMAYANTI', 'jenis_nasabah' => 'non_pegawai', 'status_nasabah' => 'aktif'],
            ['nasabah_id' => 2, 'no_hp' => '0820', 'nama_nasabah' => 'BUDHI SUPRIYADI', 'jenis_nasabah' => 'pegawai', 'status_nasabah' => 'aktif'],
        ]);
        DB::table('tblREKENING')->insert([
            ['rekening_id' => 1, 'no_rek' => '1836/TP/BM/2013', 'nasabah_id' => 1, 'tgl_buka' => '2026-01-10', 'saldo' => 1000000, 'status_rekening' => 'aktif'],
            ['rekening_id' => 2, 'no_rek' => '010/TK/BM/2004', 'nasabah_id' => 2, 'tgl_buka' => '2026-02-15', 'saldo' => 5000000, 'status_rekening' => 'aktif'],
        ]);
        $trx = fn ($rek, $debit, $kredit, $saldo, $tgl) => [
            'rekening_id' => $rek, 'jenis_transaksi_id' => $debit > 0 ? 2 : 1, 'periode_id' => 1, 'user_id' => $this->teller->user_id,
            'debit' => $debit, 'kredit' => $kredit, 'saldo_akhir' => $saldo, 'tgl_transaksi' => $tgl,
        ];
        DB::table('tblTRANSAKSI')->insert([
            $trx(1, 0, 1200000, 1200000, '2026-06-02 09:00:00'),
            $trx(1, 200000, 0, 1000000, '2026-06-03 09:00:00'),
            $trx(2, 0, 5000000, 5000000, '2026-06-02 10:00:00'),
        ]);

        DB::table('tblPINJAMAN')->insert([
            'pinjaman_id' => 1, 'nasabah_id' => 2, 'user_id' => $bbm, 'nominal_pinjaman' => 1200000, 'tenor' => 12,
            'bunga_persen' => 2, 'tgl_pengajuan' => '2026-01-05', 'metode_bayar' => 'tunai', 'status_pinjaman' => 'cair',
        ]);
        DB::table('tblPENCAIRAN')->insert(['pencairan_id' => 1, 'pinjaman_id' => 1, 'user_id' => $bbm, 'tgl_cair' => '2026-01-05', 'nominal_cair' => 1200000]);
        DB::table('tblJADWAL_ANGSURAN')->insert(['jadwal_angsuran_id' => 1, 'pinjaman_id' => 1, 'angsuran_ke' => 1, 'jatuh_tempo' => '2026-02-05', 'pokok' => 100000, 'bunga' => 24000, 'status_bayar' => 'lunas']);
        DB::table('tblANGSURAN')->insert(['angsuran_id' => 1, 'jadwal_angsuran_id' => 1, 'user_id' => $bbm, 'tgl_bayar' => '2026-06-05', 'jenis_bayar' => 'angsuran', 'bayar_pokok' => 100000, 'bayar_bunga' => 24000]);
        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => 1, 'periode_id' => 1, 'user_id' => $bbm, 'tgl_mutasi' => '2026-06-05', 'angsuran_id' => 1,
            'arah' => 'masuk', 'nominal' => 124000, 'uraian' => 'Penerimaan angsuran langsung ke-1 pinjaman #PINJ-1',
        ]);
    }

    public function test_semua_laporan_bisa_diunduh_sebagai_pdf(): void
    {
        foreach (self::LAPORAN as $route => $param) {
            $response = $this->actingAs($this->manager)->get(route($route . '.pdf', $param));

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'), $route);
            $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'), $route);
            $this->assertStringStartsWith('%PDF', $response->getContent(), $route);
        }
    }

    public function test_semua_laporan_bisa_diunduh_sebagai_excel_dengan_kop(): void
    {
        foreach (self::LAPORAN as $route => $param) {
            $sheet = $this->unduhExcel($route, $param);

            $this->assertSame('BANK MINI WIYATA CIPTA NIAGA', $sheet->getCell('A1')->getValue(), $route);
            $this->assertSame('SMK NEGERI 1 LEMAHABANG', $sheet->getCell('A2')->getValue(), $route);
        }
    }

    public function test_excel_menyimpan_angka_sebagai_angka(): void
    {
        $harian = $this->isiSheet($this->unduhExcel('laporan.tabungan', self::LAPORAN['laporan.tabungan']));
        $this->assertContains('RISKA RISMAYANTI', $harian);
        $this->assertContains(1000000.0, $harian);          // jumlah mutasi bersih Juni = 1,2 jt - 200 rb
        $this->assertContains(-200000.0, $harian);

        $neraca = $this->isiSheet($this->unduhExcel('laporan.neraca', self::LAPORAN['laporan.neraca']));
        $this->assertContains('TOTAL AKTIVA', $neraca);
        $this->assertContains(1100000.0, $neraca);           // piutang = 1,2 jt - 100 rb

        $pinjaman = $this->isiSheet($this->unduhExcel('laporan.pinjaman', self::LAPORAN['laporan.pinjaman']));
        $this->assertContains('#PINJ-0001', $pinjaman);
        $this->assertContains('Rasio NPL (Kurang Lancar + Diragukan + Macet)', $pinjaman);

        $kasBendahara = $this->isiSheet($this->unduhExcel('laporan.pinjaman.kas', self::LAPORAN['laporan.pinjaman.kas']));
        $this->assertContains(100000.0, $kasBendahara);       // pokok angsuran
        $this->assertContains(24000.0, $kasBendahara);        // jasa angsuran
    }

    public function test_nama_file_mengikuti_periode(): void
    {
        $response = $this->actingAs($this->manager)->get(route('laporan.neraca.xlsx', ['bulan' => 6, 'tahun' => 2026]));
        $this->assertStringContainsString('NERACA_2026_06_30.xlsx', $response->headers->get('Content-Disposition'));

        $response = $this->actingAs($this->manager)->get(route('laporan.tahunan.pdf', ['tahun' => 2026]));
        $this->assertStringContainsString('LAPORAN_KEUANGAN_TAHUNAN_2026.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_tombol_unduh_tampil_dengan_filter_aktif(): void
    {
        $this->actingAs($this->manager)
            ->get(route('laporan.tabungan', ['kategori' => 'guru', 'bulan' => 6, 'tahun' => 2026]))
            ->assertSee(route('laporan.tabungan.xlsx', ['kategori' => 'guru', 'bulan' => 6, 'tahun' => 2026]))
            ->assertSee(route('laporan.tabungan.pdf', ['kategori' => 'guru', 'bulan' => 6, 'tahun' => 2026]))
            ->assertSee(route('laporan.tabungan.csv', ['kategori' => 'guru', 'bulan' => 6, 'tahun' => 2026]));
    }

    public function test_halaman_ekspor_memuat_seluruh_laporan(): void
    {
        $response = $this->actingAs($this->manager)->get(route('laporan.ekspor'));

        $response->assertOk();
        foreach (array_keys(self::LAPORAN) as $route) {
            $response->assertSee(route($route . '.xlsx'), false);
            $response->assertSee(route($route . '.pdf'), false);
            $response->assertSee(route($route . '.csv'), false);
        }
    }

    public function test_selain_manager_tidak_bisa_mengunduh(): void
    {
        $this->actingAs($this->teller)->get(route('laporan.neraca.pdf'))->assertForbidden();
        $this->actingAs($this->teller)->get(route('laporan.tabungan.xlsx'))->assertForbidden();
        $this->actingAs($this->teller)->get(route('laporan.ekspor'))->assertForbidden();
    }

    private function unduhExcel(string $route, array $param)
    {
        $response = $this->actingAs($this->manager)->get(route($route . '.xlsx', $param));
        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'), $route);

        $file = tempnam(sys_get_temp_dir(), 'lap') . '.xlsx';
        file_put_contents($file, $response->streamedContent());
        $sheet = IOFactory::load($file)->getActiveSheet();
        @unlink($file);

        return $sheet;
    }

    private function isiSheet($sheet): array
    {
        return collect($sheet->toArray(null, false, false, false))->flatten()->filter(fn ($v) => $v !== null && $v !== '')->values()->all();
    }
}
