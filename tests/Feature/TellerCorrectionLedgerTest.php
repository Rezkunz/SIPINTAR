<?php

namespace Tests\Feature;

use App\Models\JenisTransaksi;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Role;
use App\Models\User;
use App\Services\TellerCorrectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TellerCorrectionLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['role_id' => 1, 'nama_role' => 'Teller']);
        JenisTransaksi::create(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        JenisTransaksi::create(['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan']);
        JenisTransaksi::create(['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan']);

        DB::table('tblKAS')->insert([
            'kas_id' => 1,
            'nama_kas' => 'Kas Tunai',
            'jenis_kas' => 'kas',
            'saldo' => 5000000,
        ]);

        DB::table('tblPERIODE')->insert([
            'periode_id' => 1,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'status_periode' => 'buka',
            'user_id' => null,
        ]);
    }

    public function test_skenario_acuan_setoran_500rb_jadi_penarikan_300rb_saldo_berurutan(): void
    {
        $teller = $this->createTeller('0811112001');
        // Saldo awal 500.000 + Setoran 500.000 => 1.000.000
        $rekening = $this->createRekening(1000000, '1001-2026-00011');
        $trxAsliId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'periode_id' => 1,
            'user_id' => $teller->user_id,
            'debit' => 0,
            'kredit' => 500000,
            'saldo_akhir' => 1000000,
            'tgl_transaksi' => now()->subMinute(),
        ], 'transaksi_id');

        $this->actingAs($teller);
        $penggantiId = app(TellerCorrectionService::class)->correct(
            $trxAsliId,
            $teller->user_id,
            'Salah input jenis, seharusnya penarikan 300.000',
            2,
            300000,
            '1001-2026-00011'
        );

        // 1. Transaksi asli tetap utuh: Setoran 500.000, saldo 1.000.000
        $asli = DB::table('tblTRANSAKSI')->where('transaksi_id', $trxAsliId)->first();
        $this->assertSame(500000.0, (float) $asli->kredit);
        $this->assertSame(1000000.0, (float) $asli->saldo_akhir);

        $audit = DB::table('tblAUDIT_LOG')
            ->where('aksi', 'KOREKSI_TRANSAKSI')
            ->where('entitas_id', $trxAsliId)
            ->first();
        $this->assertNotNull($audit);
        $dataBaru = json_decode($audit->data_baru, true);

        // 2. Baris pembatalan: 1.000.000 - 500.000 = 500.000
        $pembatalan = DB::table('tblTRANSAKSI')->where('transaksi_id', $dataBaru['reversal_id'])->first();
        $this->assertNotNull($pembatalan);
        $this->assertSame('Koreksi - Pembatalan Setoran', $dataBaru['reversal_label']);
        $this->assertSame(500000.0, (float) $pembatalan->debit);
        $this->assertSame(500000.0, (float) $pembatalan->saldo_akhir);

        // 3. Baris pengganti: 500.000 - 300.000 = 200.000
        $pengganti = DB::table('tblTRANSAKSI')->where('transaksi_id', $penggantiId)->first();
        $this->assertSame($penggantiId, (int) $dataBaru['corrected_id']);
        $this->assertSame(300000.0, (float) $pengganti->debit);
        $this->assertSame(200000.0, (float) $pengganti->saldo_akhir);

        // Timestamp pembatalan, pengganti, dan audit log sama
        $this->assertSame((string) $pembatalan->tgl_transaksi, (string) $pengganti->tgl_transaksi);
        $this->assertSame((string) $pengganti->tgl_transaksi, (string) $audit->created_at);

        // Saldo akhir rekening = 200.000
        $saldoRek = (float) DB::table('tblREKENING')->where('rekening_id', $rekening->rekening_id)->value('saldo');
        $this->assertSame(200000.0, $saldoRek);

        // Koreksi ganda pada transaksi asli yang sama harus ditolak
        $this->expectException(ValidationException::class);
        app(TellerCorrectionService::class)->correct($trxAsliId, $teller->user_id, 'Koreksi ganda', 2, 100000);
    }

    public function test_koreksi_penarikan_ke_setoran_append_only_dan_saldo_berurutan(): void
    {
        $teller = $this->createTeller('0811112002');
        $rekening = $this->createRekening(400000, '1001-2026-00102');
        $trxAsliId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekening->rekening_id,
            'jenis_transaksi_id' => 2,
            'periode_id' => 1,
            'user_id' => $teller->user_id,
            'debit' => 100000,
            'kredit' => 0,
            'saldo_akhir' => 400000,
            'tgl_transaksi' => now()->subMinute(),
        ], 'transaksi_id');

        $this->actingAs($teller);
        $penggantiId = app(TellerCorrectionService::class)->correct(
            $trxAsliId,
            $teller->user_id,
            'Seharusnya setoran 250.000',
            1,
            250000
        );

        $audit = DB::table('tblAUDIT_LOG')->where('aksi', 'KOREKSI_TRANSAKSI')->where('entitas_id', $trxAsliId)->first();
        $dataBaru = json_decode($audit->data_baru, true);

        // Pembatalan penarikan: 400.000 + 100.000 = 500.000
        $pembatalan = DB::table('tblTRANSAKSI')->where('transaksi_id', $dataBaru['reversal_id'])->first();
        $this->assertSame('Koreksi - Pembatalan Penarikan', $dataBaru['reversal_label']);
        $this->assertSame(500000.0, (float) $pembatalan->saldo_akhir);

        // Pengganti setoran: 500.000 + 250.000 = 750.000
        $pengganti = DB::table('tblTRANSAKSI')->where('transaksi_id', $penggantiId)->first();
        $this->assertSame(750000.0, (float) $pengganti->saldo_akhir);
        $this->assertSame(750000.0, (float) DB::table('tblREKENING')->where('rekening_id', $rekening->rekening_id)->value('saldo'));
    }

    public function test_koreksi_ditolak_jika_saldo_tidak_cukup_dan_rollback(): void
    {
        $teller = $this->createTeller('0811112003');
        // Saldo awal 500.000 + Setoran 500.000 = 1.000.000; koreksi jadi Penarikan 600.000 -> 500.000 - 600.000 = -100.000 (ditolak)
        $rekening = $this->createRekening(1000000, '1001-2026-00103');
        $trxAsliId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'periode_id' => 1,
            'user_id' => $teller->user_id,
            'debit' => 0,
            'kredit' => 500000,
            'saldo_akhir' => 1000000,
            'tgl_transaksi' => now()->subMinute(),
        ], 'transaksi_id');

        $this->actingAs($teller);
        $this->expectException(ValidationException::class);

        try {
            app(TellerCorrectionService::class)->correct(
                $trxAsliId,
                $teller->user_id,
                'Tes saldo tidak cukup',
                2,
                600000
            );
        } finally {
            $this->assertSame(0, DB::table('tblAUDIT_LOG')->where('aksi', 'KOREKSI_TRANSAKSI')->count());
            $this->assertSame(1000000.0, (float) DB::table('tblREKENING')->where('rekening_id', $rekening->rekening_id)->value('saldo'));
            $this->assertSame(1, DB::table('tblTRANSAKSI')->count());
        }
    }

    public function test_upaya_mengoreksi_baris_hasil_koreksi_ditolak(): void
    {
        $teller = $this->createTeller('0811112004');
        $rekening = $this->createRekening(500000, '1001-2026-00104');
        $trxAsliId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'periode_id' => 1,
            'user_id' => $teller->user_id,
            'debit' => 0,
            'kredit' => 100000,
            'saldo_akhir' => 500000,
            'tgl_transaksi' => now()->subMinute(),
        ], 'transaksi_id');

        $this->actingAs($teller);
        $service = app(TellerCorrectionService::class);
        $penggantiId = $service->correct($trxAsliId, $teller->user_id, 'Koreksi pertama', 1, 150000);
        $audit = DB::table('tblAUDIT_LOG')->where('aksi', 'KOREKSI_TRANSAKSI')->where('entitas_id', $trxAsliId)->first();
        $pembatalanId = json_decode($audit->data_baru, true)['reversal_id'];

        foreach ([$pembatalanId, $penggantiId] as $childId) {
            try {
                $service->correct($childId, $teller->user_id, 'Coba koreksi hasil koreksi', 1, 50000);
                $this->fail('Seharusnya menolak koreksi atas baris hasil koreksi.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('transaksi', $e->errors());
            }
        }
    }

    private function createTeller(string $noHp): User
    {
        return User::create([
            'role_id' => 1,
            'no_hp' => $noHp,
            'password' => bcrypt('password'),
            'nama_user' => 'Teller ' . $noHp,
            'status_akun' => 'aktif',
        ]);
    }

    private function createRekening(float $saldo, string $noRek): Rekening
    {
        $nasabah = Nasabah::create([
            'no_hp' => '088' . random_int(10000000, 99999999),
            'nama_nasabah' => 'Nasabah ' . $noRek,
            'jenis_nasabah' => 'non_pegawai',
            'status_nasabah' => 'aktif',
        ]);

        return Rekening::create([
            'no_rek' => $noRek,
            'nasabah_id' => $nasabah->nasabah_id,
            'tgl_buka' => now()->toDateString(),
            'saldo' => $saldo,
            'status_rekening' => 'aktif',
        ]);
    }
}

