<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanKasBendaharaTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Saldo kas saat ini (sesudah seluruh mutasi di bawah)
        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 10000000],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank Bendahara Bank Mini', 'jenis_kas' => 'bank', 'saldo' => 5000000],
        ]);
        DB::table('tblPERIODE')->insert(['periode_id' => 1, 'tahun' => 2026, 'bulan' => 3, 'status_periode' => 'buka']);

        // Dua pinjaman pegawai dengan jadwal angsuran
        foreach ([1 => 'AGAN DARMAWAN', 2 => 'BUDI SANTOSO'] as $id => $nama) {
            DB::table('tblNASABAH')->insert(['nasabah_id' => $id, 'no_hp' => "08{$id}00", 'nama_nasabah' => $nama, 'jenis_nasabah' => 'pegawai', 'status_nasabah' => 'aktif']);
            DB::table('tblPINJAMAN')->insert([
                'pinjaman_id' => $id, 'nasabah_id' => $id, 'user_id' => $bbm, 'nominal_pinjaman' => 600000 * $id, 'tenor' => 6,
                'bunga_persen' => 2, 'tgl_pengajuan' => '2026-02-01', 'metode_bayar' => 'potong_gaji', 'status_pinjaman' => 'cair',
            ]);
            DB::table('tblPENCAIRAN')->insert(['pencairan_id' => $id, 'pinjaman_id' => $id, 'user_id' => $bbm, 'tgl_cair' => '2026-03-03', 'nominal_cair' => 600000 * $id]);
        }
        DB::table('tblJADWAL_ANGSURAN')->insert([
            ['jadwal_angsuran_id' => 1, 'pinjaman_id' => 1, 'angsuran_ke' => 1, 'jatuh_tempo' => '2026-04-03', 'pokok' => 100000, 'bunga' => 12000, 'status_bayar' => 'belum'],
            ['jadwal_angsuran_id' => 2, 'pinjaman_id' => 1, 'angsuran_ke' => 2, 'jatuh_tempo' => '2026-05-03', 'pokok' => 100000, 'bunga' => 12000, 'status_bayar' => 'lunas'],
            ['jadwal_angsuran_id' => 3, 'pinjaman_id' => 2, 'angsuran_ke' => 1, 'jatuh_tempo' => '2026-04-03', 'pokok' => 200000, 'bunga' => 24000, 'status_bayar' => 'lunas'],
        ]);

        // Paket potong gaji #RP-1: 2 pegawai, total 336.000
        DB::table('tblRENCANA_POTONGAN')->insert([
            'rencana_potongan_id' => 1, 'periode_id' => 1, 'user_id' => $bbm, 'total_potongan' => 336000,
            'total_realisasi' => 336000, 'metode_penyerahan' => 'transfer', 'status_potongan' => 'selesai',
        ]);
        DB::table('tblDETAIL_POTONGAN')->insert([
            ['detail_potongan_id' => 1, 'rencana_potongan_id' => 1, 'jadwal_angsuran_id' => 2, 'nominal_rencana' => 112000, 'hasil_potongan' => 'berhasil'],
            ['detail_potongan_id' => 2, 'rencana_potongan_id' => 1, 'jadwal_angsuran_id' => 3, 'nominal_rencana' => 224000, 'hasil_potongan' => 'berhasil'],
        ]);

        $angsuran = fn ($id, $jadwal, $detail, $tgl, $pokok, $bunga) => DB::table('tblANGSURAN')->insert([
            'angsuran_id' => $id, 'jadwal_angsuran_id' => $jadwal, 'detail_potongan_id' => $detail, 'user_id' => $bbm,
            'tgl_bayar' => $tgl, 'jenis_bayar' => 'angsuran', 'bayar_pokok' => $pokok, 'bayar_bunga' => $bunga,
        ]);
        $angsuran(1, 1, null, '2026-03-05', 100000, 12000);   // loket tunai (kemudian dikoreksi)
        $angsuran(2, 2, 1, '2026-03-10', 100000, 12000);      // potong gaji
        $angsuran(3, 3, 2, '2026-03-10', 200000, 24000);      // potong gaji

        $mutasi = fn ($kas, $tgl, $arah, $nominal, $uraian, $extra = []) => DB::table('tblMUTASI_KAS')->insert(array_merge([
            'kas_id' => $kas, 'periode_id' => 1, 'user_id' => $bbm, 'tgl_mutasi' => $tgl,
            'arah' => $arah, 'nominal' => $nominal, 'uraian' => $uraian,
        ], $extra));

        $mutasi(1, '2026-02-10', 'masuk', 1000000, 'Setoran modal kas');
        $mutasi(1, '2026-03-03', 'keluar', 600000, 'Pencairan pinjaman tunai #PINJ-1 a.n. AGAN DARMAWAN (6 bln)', ['pencairan_id' => 1]);
        $mutasi(1, '2026-03-05', 'masuk', 112000, 'Penerimaan angsuran langsung ke-1 pinjaman #PINJ-1', ['angsuran_id' => 1]);
        $mutasi(2, '2026-03-10', 'masuk', 336000, 'Penerimaan hasil potongan gaji paket #RP-1 dari Bendahara Sekolah (TRANSFER)');
        $mutasi(1, '2026-03-12', 'keluar', 112000, 'Koreksi/reversal angsuran #ANG-1: Salah input', ['angsuran_id' => 1]);
        $mutasi(1, '2026-03-20', 'masuk', 50000, 'Penerimaan lain-lain');
        $mutasi(1, '2026-04-02', 'keluar', 30000, 'Pembelian ATK');
    }

    private function buku(array $param = [])
    {
        return $this->actingAs($this->manager)
            ->get(route('laporan.pinjaman.kas', array_merge(['bulan' => 3, 'tahun' => 2026], $param)));
    }

    public function test_saldo_awal_dihitung_mundur_dari_saldo_kas_saat_ini(): void
    {
        $response = $this->buku();

        $response->assertOk();
        // 15.000.000 - (-600rb + 112rb + 336rb - 112rb + 50rb - 30rb)
        $this->assertEquals(15244000.0, $response->viewData('saldoAwal'));
        $this->assertEquals(15030000.0, $response->viewData('saldoAkhir'));
    }

    public function test_pemasukan_dipisah_pokok_jasa_dan_lain_lain(): void
    {
        $baris = $this->buku()->viewData('baris');
        $this->assertEquals(5, $baris->total());

        $perJenis = collect($baris->items())->keyBy('jenis');

        $this->assertEquals(600000.0, $perJenis['pencairan']->pengeluaran);
        $this->assertEquals(14644000.0, $perJenis['pencairan']->saldo);

        $this->assertEquals(100000.0, $perJenis['angsuran']->pokok);
        $this->assertEquals(12000.0, $perJenis['angsuran']->jasa);
        $this->assertEquals(0.0, $perJenis['angsuran']->lain_lain);

        $this->assertEquals(300000.0, $perJenis['potongan']->pokok);   // dijumlah dari angsuran paket #RP-1
        $this->assertEquals(36000.0, $perJenis['potongan']->jasa);
        $this->assertEquals('Bank', $perJenis['potongan']->label_kas);

        $this->assertEquals(112000.0, $perJenis['koreksi']->pengeluaran);
        $this->assertEquals(50000.0, $perJenis['manual']->lain_lain);
    }

    public function test_total_bulan(): void
    {
        $total = $this->buku()->viewData('total');

        $this->assertEquals(400000.0, $total['pokok']);
        $this->assertEquals(48000.0, $total['jasa']);
        $this->assertEquals(50000.0, $total['lain_lain']);
        $this->assertEquals(498000.0, $total['pemasukan']);
        $this->assertEquals(712000.0, $total['pengeluaran']);
        $this->assertEquals(600000.0, $total['pencairan']);
        $this->assertEquals(1, $total['jumlah_pencairan']);
    }

    public function test_rekap_penerimaan_angsuran_per_saluran_dikurangi_koreksi(): void
    {
        $rekap = $this->buku()->viewData('rekapAngsuran');

        $this->assertEquals(['jumlah' => 1, 'pokok' => 100000.0, 'jasa' => 12000.0], array_intersect_key($rekap['saluran']['loket'], array_flip(['jumlah', 'pokok', 'jasa'])));
        $this->assertEquals(300000.0, $rekap['saluran']['potong']['pokok']);
        $this->assertEquals(36000.0, $rekap['saluran']['potong']['jasa']);
        $this->assertEquals(100000.0, $rekap['saluran']['koreksi']['pokok']);
        $this->assertEquals(['pokok' => 300000.0, 'jasa' => 36000.0], $rekap['bersih']);
    }

    public function test_filter_sumber_kas_tunai(): void
    {
        $response = $this->buku(['sumber' => 'tunai']);

        // 10.000.000 - (-600rb + 112rb - 112rb + 50rb - 30rb)
        $this->assertEquals(10580000.0, $response->viewData('saldoAwal'));
        $this->assertEquals(10030000.0, $response->viewData('saldoAkhir'));
        $this->assertEquals(4, $response->viewData('baris')->total());
        $this->assertEquals(0.0, $response->viewData('rekapAngsuran')['saluran']['potong']['pokok']);
    }

    public function test_ekspor_csv_kas_bendahara(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('laporan.pinjaman.kas.csv', ['bulan' => 3, 'tahun' => 2026]));

        $response->assertOk();
        $this->assertMatchesRegularExpression('/KAS_BENDAHARA_SEMUA_[A-Z]+_2026\.csv/', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString(';;"Saldo awal";;;;;15244000', $csv);
        $this->assertStringContainsString('10/03/2026;Bank;"Penerimaan hasil potongan gaji paket #RP-1 dari Bendahara Sekolah (TRANSFER)";;300000;36000;;', $csv);
        $this->assertStringContainsString('"PENERIMAAN BERSIH";;300000;36000;336000', $csv);
    }

    public function test_tab_berada_di_menu_laporan_pinjaman_saja(): void
    {
        $this->buku()
            ->assertSee('Rekap Pinjaman &amp; Kolektibilitas', false)
            ->assertDontSee('Rekap Tabungan')
            ->assertDontSee('Buku Kas Harian');

        $this->actingAs($this->manager)
            ->get(route('laporan.tabungan'))
            ->assertDontSee(route('laporan.pinjaman.kas'));
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)
            ->get(route('laporan.pinjaman.kas'))
            ->assertForbidden();
    }
}
