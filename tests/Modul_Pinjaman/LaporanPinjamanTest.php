<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanPinjamanTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;
    protected int $bbmId;

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
        $this->bbmId   = $user(3, '081200000003', 'BBM001', 'Bendahara Bank Mini')->user_id;

        // Posisi uji: 30 Juni 2026
        $this->buatPinjaman(1, 'AGAN DARMAWAN', 1200000, 6, '2026-01-05', [
            // ke-1..3 dibayar tepat waktu; ke-4 dibayar lalu dikoreksi; ke-5 dibayar setelah posisi
            1 => ['2026-02-05'], 2 => ['2026-03-05'], 3 => ['2026-04-05'],
            4 => ['2026-05-05', 'dikoreksi'], 5 => ['2026-07-02'],
        ]);
        $this->buatPinjaman(2, 'BUDI SANTOSO', 600000, 3, '2026-01-10', []);                 // menunggak sejak 10 Feb
        $this->buatPinjaman(3, 'CITRA LESTARI', 1000000, 10, '2026-05-20', [1 => ['2026-06-18']]);
        $this->buatPinjaman(4, 'DEDI LUNAS', 300000, 1, '2026-02-01', [1 => ['2026-03-01']], 'lunas');
        $this->buatPinjaman(5, 'EKO MACET', 500000, 2, '2025-10-01', []);                    // menunggak sejak 1 Nov 2025
        $this->buatPinjaman(6, 'FARHAN KURANG', 400000, 1, '2026-02-10', []);                // menunggak sejak 10 Mar
        $this->buatPinjaman(7, 'GINA BARU', 800000, 4, '2026-07-15', []);                   // cair setelah posisi
    }

    /**
     * Pinjaman flat: pokok = plafon/tenor, jasa 2% per bulan; jatuh tempo tiap bulan setelah cair.
     * $bayar: [angsuran_ke => [tgl_bayar, 'dikoreksi'?]]
     */
    private function buatPinjaman(int $id, string $nama, int $plafon, int $tenor, string $tglCair, array $bayar, string $status = 'cair'): void
    {
        DB::table('tblNASABAH')->insert([
            'nasabah_id' => $id, 'no_hp' => "08{$id}00", 'nama_nasabah' => $nama,
            'jenis_nasabah' => 'pegawai', 'kelas_unit' => 'Guru', 'status_nasabah' => 'aktif',
        ]);

        DB::table('tblPINJAMAN')->insert([
            'pinjaman_id' => $id, 'nasabah_id' => $id, 'user_id' => $this->bbmId, 'nominal_pinjaman' => $plafon,
            'tenor' => $tenor, 'bunga_persen' => 2, 'tgl_pengajuan' => $tglCair, 'metode_bayar' => 'potong_gaji',
            'status_pinjaman' => $status,
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id' => $id, 'user_id' => $this->bbmId, 'tgl_cair' => $tglCair, 'nominal_cair' => $plafon,
        ]);

        $pokok = $plafon / $tenor;
        $bunga = $plafon * 0.02;

        for ($ke = 1; $ke <= $tenor; $ke++) {
            $jadwalId = DB::table('tblJADWAL_ANGSURAN')->insertGetId([
                'pinjaman_id' => $id, 'angsuran_ke' => $ke,
                'jatuh_tempo' => Carbon::parse($tglCair)->addMonthsNoOverflow($ke)->toDateString(),
                'pokok' => $pokok, 'bunga' => $bunga,
                'status_bayar' => isset($bayar[$ke]) && !in_array('dikoreksi', $bayar[$ke]) ? 'lunas' : 'belum',
            ]);

            if (!isset($bayar[$ke])) {
                continue;
            }

            $angsuranId = DB::table('tblANGSURAN')->insertGetId([
                'jadwal_angsuran_id' => $jadwalId, 'user_id' => $this->bbmId, 'tgl_bayar' => $bayar[$ke][0],
                'jenis_bayar' => 'angsuran', 'bayar_pokok' => $pokok, 'bayar_bunga' => $bunga,
            ]);

            if (in_array('dikoreksi', $bayar[$ke])) {
                DB::table('tblKOREKSI_ANGSURAN')->insert([
                    'angsuran_id' => $angsuranId, 'user_id' => $this->bbmId, 'tgl_koreksi' => $bayar[$ke][0],
                    'alasan_koreksi' => 'Salah input', 'nominal_pokok' => $pokok, 'nominal_bunga' => $bunga,
                    'nominal_total' => $pokok + $bunga,
                ]);
            }
        }
    }

    private function rekap(array $param = [])
    {
        return $this->actingAs($this->manager)
            ->get(route('laporan.pinjaman', array_merge(['bulan' => 6, 'tahun' => 2026], $param)));
    }

    public function test_posisi_akhir_bulan_hanya_memuat_pinjaman_berjalan(): void
    {
        $response = $this->rekap();

        $response->assertOk();
        $this->assertEquals('2026-06-30', $response->viewData('tanggalPosisi')->toDateString());

        $nama = $response->viewData('baris')->pluck('nama_nasabah')->all();
        $this->assertEqualsCanonicalizing(['AGAN DARMAWAN', 'BUDI SANTOSO', 'CITRA LESTARI', 'EKO MACET', 'FARHAN KURANG'], $nama);
        $response->assertDontSee('DEDI LUNAS');   // sisa pokok 0
        $response->assertDontSee('GINA BARU');    // belum cair pada posisi
    }

    public function test_angsuran_dikoreksi_dan_dibayar_setelah_posisi_dihitung_belum_dibayar(): void
    {
        $agan = $this->rekap()->viewData('baris')->firstWhere('nama_nasabah', 'AGAN DARMAWAN');

        $this->assertEquals(3, $agan->angsuran_dibayar);
        $this->assertEquals(600000.0, $agan->pokok_terbayar);
        $this->assertEquals(600000, $agan->sisa_pokok);
        $this->assertEquals(2, $agan->bulan_tunggakan);              // ke-4 (dikoreksi) & ke-5 (dibayar Juli)
        $this->assertEquals(448000.0, $agan->nominal_tunggakan);     // 2 × (200.000 + 24.000)
        $this->assertEquals(56, $agan->hari_terlambat);              // 5 Mei → 30 Juni
        $this->assertEquals('dpk', $agan->kolektibilitas);
    }

    public function test_kolektibilitas_sesuai_lama_keterlambatan(): void
    {
        $baris = $this->rekap()->viewData('baris')->keyBy('nama_nasabah');

        $this->assertEquals('lancar', $baris['CITRA LESTARI']->kolektibilitas);
        $this->assertEquals(0, $baris['CITRA LESTARI']->hari_terlambat);

        $this->assertEquals(112, $baris['FARHAN KURANG']->hari_terlambat);
        $this->assertEquals('kurang_lancar', $baris['FARHAN KURANG']->kolektibilitas);

        $this->assertEquals(140, $baris['BUDI SANTOSO']->hari_terlambat);
        $this->assertEquals('diragukan', $baris['BUDI SANTOSO']->kolektibilitas);
        $this->assertEquals(3, $baris['BUDI SANTOSO']->bulan_tunggakan);

        $this->assertEquals(241, $baris['EKO MACET']->hari_terlambat);
        $this->assertEquals('macet', $baris['EKO MACET']->kolektibilitas);
    }

    public function test_ringkasan_sebaran_dan_rasio_npl(): void
    {
        $response = $this->rekap();

        $ringkasan = $response->viewData('ringkasan');
        $this->assertEquals(5, $ringkasan['jumlah_pinjaman']);
        $this->assertEquals(3000000.0, $ringkasan['total_sisa_pokok']);   // 600rb + 600rb + 900rb + 500rb + 400rb
        $this->assertEquals(4, $ringkasan['jumlah_menunggak']);
        $this->assertEquals(50.0, $ringkasan['rasio_npl']);               // (400rb + 600rb + 500rb) / 3 jt

        $sebaran = $response->viewData('sebaran');
        $this->assertEquals(1, $sebaran['lancar']['jumlah']);
        $this->assertEquals(1, $sebaran['dpk']['jumlah']);
        $this->assertEquals(1, $sebaran['kurang_lancar']['jumlah']);
        $this->assertEquals(1, $sebaran['diragukan']['jumlah']);
        $this->assertEquals(1, $sebaran['macet']['jumlah']);
        $this->assertEquals(30.0, $sebaran['lancar']['persen']);          // 900rb / 3 jt
    }

    public function test_bulan_berjalan_dihitung_per_hari_ini(): void
    {
        $response = $this->rekap(['bulan' => 11, 'tahun' => 2026]);

        $this->assertEquals('2026-11-02', $response->viewData('tanggalPosisi')->toDateString());
    }

    public function test_filter_kolektibilitas_dan_pencarian(): void
    {
        $response = $this->rekap(['kolektibilitas' => 'macet']);
        $this->assertEquals(['EKO MACET'], $response->viewData('baris')->pluck('nama_nasabah')->all());
        // Ringkasan tetap menyeluruh walau tabel disaring
        $this->assertEquals(5, $response->viewData('ringkasan')['jumlah_pinjaman']);

        $response = $this->rekap(['cari' => 'citra']);
        $this->assertEquals(['CITRA LESTARI'], $response->viewData('baris')->pluck('nama_nasabah')->all());

        $response = $this->rekap(['cari' => 'PINJ-0002']);
        $this->assertEquals(['BUDI SANTOSO'], $response->viewData('baris')->pluck('nama_nasabah')->all());
    }

    public function test_ekspor_csv_rekap_pinjaman(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.pinjaman.csv', ['bulan' => 6, 'tahun' => 2026]));

        $response->assertOk();
        $this->assertStringContainsString('REKAP_PINJAMAN_KOLEKTIBILITAS_2026_06_30', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('#PINJ-0005;"EKO MACET"', $csv);
        $this->assertStringContainsString(';241;Macet', $csv);
        $this->assertStringContainsString('"RASIO NPL";;;50%', $csv);
    }

    public function test_menu_laporan_pinjaman_tidak_memuat_laporan_tabungan(): void
    {
        $this->rekap()
            ->assertSee('Rekap Pinjaman &amp; Kolektibilitas', false)
            ->assertDontSee('Buku Kas Harian')
            ->assertDontSee('Rekap Tabungan');
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)
            ->get(route('laporan.pinjaman'))
            ->assertForbidden();
    }
}
