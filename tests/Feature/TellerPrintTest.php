<?php

namespace Tests\Feature;

use App\Models\JenisTransaksi;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TellerPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['role_id' => 1, 'nama_role' => 'Teller']);
        Role::create(['role_id' => 6, 'nama_role' => 'Nasabah']);
        JenisTransaksi::create(['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran']);
        JenisTransaksi::create(['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan']);

        DB::table('tblPERIODE')->insert([
            'periode_id' => 1,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'status_periode' => 'buka',
            'user_id' => null,
        ]);
    }

    public function test_teller_dapat_mencetak_slip_dan_buku_tabungan_dengan_escaping_aman(): void
    {
        $teller = User::create([
            'role_id' => 1,
            'no_hp' => '0811111001',
            'no_induk' => 'TLR-001',
            'password' => bcrypt('password'),
            'nama_user' => 'Teller Uji',
            'status_akun' => 'aktif',
        ]);

        $nasabah = Nasabah::create([
            'no_hp' => '081234560001',
            'nama_nasabah' => '<script>alert(1)</script> Budi',
            'jenis_nasabah' => 'non_pegawai',
            'kelas_unit' => 'XII AKL 1',
            'status_nasabah' => 'aktif',
        ]);

        $rekening = Rekening::create([
            'no_rek' => '1001-2026-00099',
            'nasabah_id' => $nasabah->nasabah_id,
            'tgl_buka' => now()->toDateString(),
            'saldo' => 50000,
            'status_rekening' => 'aktif',
        ]);

        $trxId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekening->rekening_id,
            'jenis_transaksi_id' => 1,
            'periode_id' => 1,
            'user_id' => $teller->user_id,
            'debit' => 0,
            'kredit' => 50000,
            'saldo_akhir' => 50000,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');

        $this->actingAs($teller)
            ->get(route('tabungan.cetak.slip-setoran', $trxId))
            ->assertOk()
            ->assertSee('SLIP SETORAN')
            ->assertSee('Lima puluh ribu rupiah')
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($teller)
            ->get(route('tabungan.cetak.slip-pengambilan', ['transaksi' => $trxId, 'no_kk' => '3209012345670001']))
            ->assertOk()
            ->assertSee('SLIP PENGAMBILAN')
            ->assertSee('3209012345670001');

        $this->actingAs($teller)
            ->get(route('tabungan.cetak.buku-tabungan', ['no_rek' => $rekening->no_rek, 'line_offset' => 3]))
            ->assertOk()
            ->assertSee('S1')
            ->assertSee('TLR-001');
    }

    public function test_role_non_teller_ditolak_mengakses_endpoint_cetak(): void
    {
        $nasabahUser = User::create([
            'role_id' => 6,
            'no_hp' => '0811111002',
            'password' => bcrypt('password'),
            'nama_user' => 'Nasabah Uji',
            'status_akun' => 'aktif',
        ]);

        $this->actingAs($nasabahUser)
            ->get(route('tabungan.cetak.buku-tabungan', ['no_rek' => '1001-2026-00099']))
            ->assertForbidden();
    }
}
