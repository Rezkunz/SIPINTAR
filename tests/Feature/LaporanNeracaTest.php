<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LabaRugiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanNeracaTest extends TestCase
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

        // Saldo kas saat ini; posisi 30 Juni dihitung mundur dari mutasi sesudahnya
        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 10000000],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank', 'jenis_kas' => 'bank', 'saldo' => 2000000],
        ]);

        // Tabungan siswa 1 jt (Jan) lalu +500rb (Jul); guru 5 jt (Feb)
        DB::table('tblNASABAH')->insert([
            ['nasabah_id' => 1, 'no_hp' => '0810', 'nama_nasabah' => 'SISWA A', 'jenis_nasabah' => 'non_pegawai', 'status_nasabah' => 'aktif'],
            ['nasabah_id' => 2, 'no_hp' => '0820', 'nama_nasabah' => 'GURU B', 'jenis_nasabah' => 'pegawai', 'status_nasabah' => 'aktif'],
            ['nasabah_id' => 3, 'no_hp' => '0830', 'nama_nasabah' => 'PEMINJAM C', 'jenis_nasabah' => 'pegawai', 'status_nasabah' => 'aktif'],
        ]);
        DB::table('tblREKENING')->insert([
            ['rekening_id' => 1, 'no_rek' => '1001-2026-00001', 'nasabah_id' => 1, 'tgl_buka' => '2026-01-10', 'saldo' => 1500000, 'status_rekening' => 'aktif'],
            ['rekening_id' => 2, 'no_rek' => '1001-2026-00002', 'nasabah_id' => 2, 'tgl_buka' => '2026-02-15', 'saldo' => 5000000, 'status_rekening' => 'aktif'],
        ]);
        $trx = fn ($rek, $kredit, $saldo, $tgl) => [
            'rekening_id' => $rek, 'jenis_transaksi_id' => 1, 'periode_id' => 1, 'user_id' => $tellerId,
            'debit' => 0, 'kredit' => $kredit, 'saldo_akhir' => $saldo, 'tgl_transaksi' => $tgl,
        ];
        DB::table('tblTRANSAKSI')->insert([
            $trx(1, 1000000, 1000000, '2026-01-10 09:00:00'),
            $trx(2, 5000000, 5000000, '2026-02-15 09:00:00'),
            $trx(1, 500000, 1500000, '2026-07-05 09:00:00'),
        ]);

        // Pinjaman 1,2 jt cair Jan: pokok sah Feb & Mar, Apr dikoreksi, Jul sesudah posisi
        DB::table('tblPINJAMAN')->insert([
            'pinjaman_id' => 1, 'nasabah_id' => 3, 'user_id' => $bbm, 'nominal_pinjaman' => 1200000, 'tenor' => 12,
            'bunga_persen' => 2, 'tgl_pengajuan' => '2026-01-05', 'metode_bayar' => 'tunai', 'status_pinjaman' => 'cair',
        ]);
        DB::table('tblPENCAIRAN')->insert(['pencairan_id' => 1, 'pinjaman_id' => 1, 'user_id' => $bbm, 'tgl_cair' => '2026-01-05', 'nominal_cair' => 1200000]);
        foreach ([1 => '2026-02-05', 2 => '2026-03-05', 3 => '2026-04-05', 4 => '2026-07-05'] as $ke => $tgl) {
            DB::table('tblJADWAL_ANGSURAN')->insert(['jadwal_angsuran_id' => $ke, 'pinjaman_id' => 1, 'angsuran_ke' => $ke, 'jatuh_tempo' => $tgl, 'pokok' => 100000, 'bunga' => 24000, 'status_bayar' => 'lunas']);
            DB::table('tblANGSURAN')->insert(['angsuran_id' => $ke, 'jadwal_angsuran_id' => $ke, 'user_id' => $bbm, 'tgl_bayar' => $tgl, 'jenis_bayar' => 'angsuran', 'bayar_pokok' => 100000, 'bayar_bunga' => 24000]);
        }
        DB::table('tblKOREKSI_ANGSURAN')->insert([
            'angsuran_id' => 3, 'user_id' => $bbm, 'tgl_koreksi' => '2026-04-06', 'alasan_koreksi' => 'Salah input',
            'nominal_pokok' => 100000, 'nominal_bunga' => 24000, 'nominal_total' => 124000,
        ]);

        $mutasi = fn ($kas, $tgl, $arah, $nominal, $uraian) => DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => $kas, 'periode_id' => 1, 'user_id' => $bbm, 'tgl_mutasi' => $tgl,
            'arah' => $arah, 'nominal' => $nominal, 'uraian' => $uraian,
        ]);
        $mutasi(1, '2026-05-01', 'keluar', 1000000, 'Honor pengurus');   // sebelum posisi: memengaruhi laba
        $mutasi(1, '2026-07-10', 'keluar', 500000, 'Pembelian ATK');     // sesudah posisi
        $mutasi(2, '2026-08-01', 'masuk', 300000, 'Bunga deposito');     // sesudah posisi
    }

    private function neraca(array $param = [])
    {
        return $this->actingAs($this->manager)
            ->get(route('laporan.neraca', array_merge(['bulan' => 6, 'tahun' => 2026], $param)));
    }

    public function test_aktiva_pada_posisi_akhir_bulan(): void
    {
        $response = $this->neraca();

        $response->assertOk();
        $this->assertEquals('2026-06-30', $response->viewData('tanggalPosisi')->toDateString());

        $aktiva = $response->viewData('aktiva');
        $this->assertEquals(10500000.0, $aktiva['kas_tunai']);   // 10 jt + 500rb keluar sesudah posisi
        $this->assertEquals(1700000.0, $aktiva['kas_bank']);     // 2 jt - 300rb masuk sesudah posisi
        $this->assertEquals(1000000.0, $aktiva['piutang']);      // 1,2 jt - 2 × 100rb sah
        $this->assertEquals(13200000.0, $response->viewData('totalAktiva'));
    }

    public function test_kewajiban_tabungan_dan_jasa_terutang(): void
    {
        $kewajiban = $this->neraca()->viewData('kewajiban');

        $this->assertEquals(1000000.0, $kewajiban['tabungan_siswa']);  // setoran Juli belum dihitung
        $this->assertEquals(5000000.0, $kewajiban['tabungan_guru']);
        $this->assertEquals(56000.0, $kewajiban['jasa_terutang']);     // siswa 1.000 × 6 + guru 10.000 × 5
    }

    public function test_ekuitas_dan_neraca_seimbang(): void
    {
        $response = $this->neraca();

        $ekuitas = $response->viewData('ekuitas');
        $this->assertEquals(-1008000.0, $ekuitas['laba_berjalan']);    // jasa pinjaman 48rb - jasa tabungan 56rb - honor 1 jt
        $this->assertEquals(8152000.0, $ekuitas['modal_selisih']);
        $this->assertEquals(7144000.0, $response->viewData('totalEkuitas'));

        $this->assertEquals(
            $response->viewData('totalAktiva'),
            $response->viewData('totalKewajiban') + $response->viewData('totalEkuitas')
        );
    }

    public function test_angka_konsisten_dengan_laporan_lain(): void
    {
        $response = $this->neraca();

        $piutangRekap = $this->actingAs($this->manager)
            ->get(route('laporan.pinjaman', ['bulan' => 6, 'tahun' => 2026]))
            ->viewData('ringkasan')['total_sisa_pokok'];
        $this->assertEquals($piutangRekap, $response->viewData('aktiva')['piutang']);

        $this->assertEquals(LabaRugiService::hitung(2026, 6)['total']['laba'], $response->viewData('ekuitas')['laba_berjalan']);
    }

    public function test_bulan_berjalan_dihitung_per_hari_ini(): void
    {
        $response = $this->neraca(['bulan' => 11, 'tahun' => 2026]);

        $this->assertEquals('2026-11-02', $response->viewData('tanggalPosisi')->toDateString());
        $this->assertEquals(10000000.0, $response->viewData('aktiva')['kas_tunai']);
        $this->assertEquals(1500000.0, $response->viewData('kewajiban')['tabungan_siswa']);
    }

    public function test_ekspor_csv_neraca(): void
    {
        $response = $this->actingAs($this->manager)->get(route('laporan.neraca.csv', ['bulan' => 6, 'tahun' => 2026]));

        $response->assertOk();
        $this->assertStringContainsString('NERACA_2026_06_30.csv', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('"TOTAL AKTIVA";13200000', $csv);
        $this->assertStringContainsString('"TOTAL KEWAJIBAN & EKUITAS";13200000', $csv);
    }

    private function tambahPos(array $data)
    {
        return $this->actingAs($this->manager)->post(route('laporan.neraca.pos.store'), array_merge([
            'kembali_bulan' => 6, 'kembali_tahun' => 2026,
        ], $data));
    }

    public function test_manager_menambah_pos_manual_dan_selisih_mengecil(): void
    {
        $this->tambahPos(['kelompok' => 'aktiva', 'nama_pos' => 'Deposito BPR', 'nilai' => 100000000, 'tgl_berlaku' => '2026-01-01', 'keterangan' => 'Depo BPR Sahabat Sejati'])
            ->assertRedirect(route('laporan.neraca', ['bulan' => 6, 'tahun' => 2026]) . '#pos-manual')
            ->assertSessionHas('success');
        $this->tambahPos(['kelompok' => 'aktiva', 'nama_pos' => 'Akumulasi Penyusutan Peralatan', 'nilai' => -7000000, 'tgl_berlaku' => '2026-01-01']);
        $this->tambahPos(['kelompok' => 'kewajiban', 'nama_pos' => 'Cadangan Hadiah', 'nilai' => 10000000, 'tgl_berlaku' => '2026-01-01']);

        $this->assertDatabaseHas('tblPOS_NERACA', ['nama_pos' => 'Deposito BPR', 'kelompok' => 'aktiva', 'user_id' => $this->manager->user_id]);
        $this->assertDatabaseHas('tblAUDIT_LOG', ['aksi' => 'INSERT', 'entitas' => 'tblPOS_NERACA']);

        $response = $this->neraca();
        $response->assertSee('Deposito BPR')->assertSee('input manual');

        // Aktiva 13,2 jt + 100 jt - 7 jt; kewajiban 6,056 jt + 10 jt
        $this->assertEquals(106200000.0, $response->viewData('totalAktiva'));
        $this->assertEquals(16056000.0, $response->viewData('totalKewajiban'));
        $this->assertEquals(91152000.0, $response->viewData('ekuitas')['modal_selisih']);

        // Modal diinput sebesar sisa selisih → selisih menjadi nol, neraca tetap seimbang
        $this->tambahPos(['kelompok' => 'ekuitas', 'nama_pos' => 'Modal Bank Mini', 'nilai' => 91152000, 'tgl_berlaku' => '2025-12-31']);
        $response = $this->neraca();
        $this->assertEquals(0.0, $response->viewData('ekuitas')['modal_selisih']);
        $this->assertEquals($response->viewData('totalAktiva'), $response->viewData('totalKewajiban') + $response->viewData('totalEkuitas'));
    }

    public function test_nilai_pos_mengikuti_tanggal_berlaku(): void
    {
        $this->tambahPos(['kelompok' => 'aktiva', 'nama_pos' => 'Deposito BPR', 'nilai' => 100000000, 'tgl_berlaku' => '2026-01-01']);
        $this->tambahPos(['kelompok' => 'aktiva', 'nama_pos' => 'Deposito BPR', 'nilai' => 150000000, 'tgl_berlaku' => '2026-05-01']);
        $this->tambahPos(['kelompok' => 'aktiva', 'nama_pos' => 'deposito bpr', 'nilai' => 0, 'tgl_berlaku' => '2026-09-01']);   // cair

        $nilai = fn ($bulan) => $this->neraca(['bulan' => $bulan])->viewData('posManual')['aktiva']->sum('nilai');

        $this->assertEquals(100000000.0, $nilai(3));
        $this->assertEquals(150000000.0, $nilai(6));
        $this->assertEquals(0.0, $nilai(10));                                         // nilai 0 tidak ditampilkan
        $this->assertCount(0, $this->neraca(['bulan' => 10])->viewData('posManual')['aktiva']);
        $this->assertCount(0, $this->neraca(['bulan' => 12, 'tahun' => 2025])->viewData('posManual')['aktiva']);
    }

    public function test_ubah_dan_hapus_pos_manual_tercatat_di_audit_log(): void
    {
        $this->tambahPos(['kelompok' => 'aktiva', 'nama_pos' => 'Deposito BTN', 'nilai' => 5000000, 'tgl_berlaku' => '2026-01-01']);
        $posId = DB::table('tblPOS_NERACA')->value('pos_neraca_id');

        $this->actingAs($this->manager)
            ->get(route('laporan.neraca', ['bulan' => 6, 'tahun' => 2026, 'edit' => $posId]))
            ->assertSee('Ubah Pos: Deposito BTN')
            ->assertSee('Simpan Perubahan');

        $this->actingAs($this->manager)->put(route('laporan.neraca.pos.update', $posId), [
            'kelompok' => 'aktiva', 'nama_pos' => 'Deposito BTN', 'nilai' => 7500000, 'tgl_berlaku' => '2026-01-01',
        ])->assertSessionHas('success');
        $this->assertEquals(7500000.0, (float) DB::table('tblPOS_NERACA')->where('pos_neraca_id', $posId)->value('nilai'));
        $this->assertDatabaseHas('tblAUDIT_LOG', ['aksi' => 'UPDATE', 'entitas' => 'tblPOS_NERACA', 'entitas_id' => $posId]);

        $this->actingAs($this->manager)->delete(route('laporan.neraca.pos.destroy', $posId))->assertSessionHas('success');
        $this->assertDatabaseMissing('tblPOS_NERACA', ['pos_neraca_id' => $posId]);
        $this->assertDatabaseHas('tblAUDIT_LOG', ['aksi' => 'DELETE', 'entitas' => 'tblPOS_NERACA', 'entitas_id' => $posId]);
    }

    public function test_validasi_pos_manual(): void
    {
        $this->tambahPos(['kelompok' => 'lainnya', 'nama_pos' => '', 'nilai' => 'abc', 'tgl_berlaku' => ''])
            ->assertSessionHasErrors(['kelompok', 'nama_pos', 'nilai', 'tgl_berlaku']);

        $this->assertDatabaseCount('tblPOS_NERACA', 0);
    }

    public function test_pos_manual_masuk_ke_csv(): void
    {
        $this->tambahPos(['kelompok' => 'aktiva', 'nama_pos' => 'Deposito BPR', 'nilai' => 100000000, 'tgl_berlaku' => '2026-01-01']);

        $csv = $this->actingAs($this->manager)
            ->get(route('laporan.neraca.csv', ['bulan' => 6, 'tahun' => 2026]))
            ->streamedContent();

        $this->assertStringContainsString('"Deposito BPR (input manual)";100000000', $csv);
        $this->assertStringContainsString('"TOTAL AKTIVA";113200000', $csv);
    }

    public function test_selain_manager_tidak_bisa_mengelola_pos_manual(): void
    {
        $this->actingAs($this->teller)
            ->post(route('laporan.neraca.pos.store'), ['kelompok' => 'aktiva', 'nama_pos' => 'X', 'nilai' => 1, 'tgl_berlaku' => '2026-01-01'])
            ->assertForbidden();

        $this->assertDatabaseCount('tblPOS_NERACA', 0);
    }

    public function test_selain_manager_ditolak(): void
    {
        $this->actingAs($this->teller)
            ->get(route('laporan.neraca'))
            ->assertForbidden();
    }
}
