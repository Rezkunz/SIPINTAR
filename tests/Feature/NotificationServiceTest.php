<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seedRolesAndJenis();
    }

    private function seedRolesAndJenis(): void
    {
        $roles = [
            ['role_id' => 1, 'nama_role' => 'teller'],
            ['role_id' => 2, 'nama_role' => 'manager'],
            ['role_id' => 3, 'nama_role' => 'bendahara_mini'],
            ['role_id' => 4, 'nama_role' => 'bendahara'],
            ['role_id' => 5, 'nama_role' => 'siswa_praktik'],
            ['role_id' => 6, 'nama_role' => 'nasabah'],
        ];

        foreach ($roles as $r) {
            DB::table('tblROLE')->updateOrInsert(['role_id' => $r['role_id']], $r);
        }

        $jenis = [
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran', 'kode_sandi' => 'S1'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan', 'kode_sandi' => 'S2'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan', 'kode_sandi' => 'S3'],
        ];

        foreach ($jenis as $j) {
            DB::table('tblJENIS_TRANSAKSI')->updateOrInsert(['jenis_transaksi_id' => $j['jenis_transaksi_id']], $j);
        }
    }

    private function makeUser(int $roleId, string $nama, ?int $nasabahId = null): User
    {
        $id = DB::table('tblUSER')->insertGetId([
            'role_id' => $roleId,
            'nasabah_id' => $nasabahId,
            'no_hp' => '0812' . rand(10000000, 99999999),
            'email' => strtolower(str_replace(' ', '', $nama)) . rand(1, 999) . '@test.local',
            'password' => Hash::make('password'),
            'nama_user' => $nama,
            'status_akun' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'user_id');

        return User::findOrFail($id);
    }

    private function makeNasabah(string $nama, string $jenis = 'siswa'): int
    {
        return DB::table('tblNASABAH')->insertGetId([
            'nama_nasabah' => $nama,
            'jenis_nasabah' => $jenis,
            'no_hp' => '0813' . rand(10000000, 99999999),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'nasabah_id');
    }

    public function test_teller_notifications_appear_and_disappear_with_conditions(): void
    {
        $teller = $this->makeUser(1, 'Teller Test');
        $siswa = $this->makeUser(5, 'Siswa Test');
        $nasabahId = $this->makeNasabah('Nasabah A');

        $rekId = DB::table('tblREKENING')->insertGetId([
            'no_rek' => '1001-2026-00001',
            'nasabah_id' => $nasabahId,
            'tgl_buka' => now()->toDateString(),
            'saldo' => 100000,
            'status_rekening' => 'aktif',
        ], 'rekening_id');

        DB::table('tblKAS')->insert([
            'jenis_kas' => 'kas',
            'saldo' => 250000, // di bawah default 500000
        ]);

        $draftId = DB::table('tblDRAFT')->insertGetId([
            'rekening_id' => $rekId,
            'jenis_transaksi_id' => 1,
            'siswa_id' => $siswa->user_id,
            'nominal' => 50000,
            'tgl_input' => now(),
            'status_verifikasi' => 'pending',
        ], 'draft_id');

        $items = NotificationService::untuk($teller);
        $this->assertCount(2, $items);
        $this->assertSame(2, NotificationService::countActionUnread($items));
        $this->assertStringContainsString('1 draft transaksi', $items[0]['teks']);

        // Selesaikan kondisi -> notifikasi hilang
        DB::table('tblDRAFT')->where('draft_id', $draftId)->update(['status_verifikasi' => 'disetujui']);
        DB::table('tblKAS')->where('jenis_kas', 'kas')->update(['saldo' => 1000000]);
        NotificationService::forgetCache($teller->user_id);

        $after = NotificationService::untuk($teller);
        $this->assertCount(0, $after);
        $this->assertSame(0, NotificationService::countActionUnread($after));
    }

    public function test_siswa_praktik_badge_only_counts_action_items(): void
    {
        $siswa1 = $this->makeUser(5, 'Siswa Satu');
        $siswa2 = $this->makeUser(5, 'Siswa Dua');
        $nasabahId = $this->makeNasabah('Nasabah B');

        $rekId = DB::table('tblREKENING')->insertGetId([
            'no_rek' => '1001-2026-00002',
            'nasabah_id' => $nasabahId,
            'tgl_buka' => now()->toDateString(),
            'saldo' => 0,
            'status_rekening' => 'pending',
        ], 'rekening_id');

        DB::table('tblDRAFT')->insert([
            'rekening_id' => $rekId,
            'jenis_transaksi_id' => 1,
            'siswa_id' => $siswa1->user_id,
            'nominal' => 30000,
            'tgl_input' => now(),
            'status_verifikasi' => 'disetujui',
        ]);

        $items1 = NotificationService::untuk($siswa1);
        $this->assertCount(2, $items1);
        $this->assertSame('action', $items1[0]['jenis']);
        $this->assertSame('info', $items1[1]['jenis']);
        $this->assertSame(1, NotificationService::countActionUnread($items1));

        // Siswa Dua tidak boleh melihat hasil draft Siswa Satu
        $items2 = NotificationService::untuk($siswa2);
        $this->assertCount(1, $items2);
        $this->assertSame('action', $items2[0]['jenis']);
    }

    public function test_nasabah_isolation_and_single_card_for_koreksi(): void
    {
        $nasabahAId = $this->makeNasabah('Nasabah Alpha');
        $nasabahBId = $this->makeNasabah('Nasabah Beta');
        $userA = $this->makeUser(6, 'User Alpha', $nasabahAId);
        $userB = $this->makeUser(6, 'User Beta', $nasabahBId);
        $teller = $this->makeUser(1, 'Teller Koreksi');

        $rekA = DB::table('tblREKENING')->insertGetId([
            'no_rek' => '1001-2026-00003',
            'nasabah_id' => $nasabahAId,
            'tgl_buka' => now()->toDateString(),
            'saldo' => 150000,
            'status_rekening' => 'aktif',
        ], 'rekening_id');

        $periodeId = DB::table('tblPERIODE')->insertGetId([
            'user_id' => $teller->user_id,
            'tahun' => now()->year,
            'bulan' => now()->month,
            'status_periode' => 'buka',
        ], 'periode_id');

        $origId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekA,
            'jenis_transaksi_id' => 1,
            'periode_id' => $periodeId,
            'user_id' => $teller->user_id,
            'debit' => 0,
            'kredit' => 100000,
            'saldo_akhir' => 100000,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');

        $revId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekA,
            'jenis_transaksi_id' => 1,
            'periode_id' => $periodeId,
            'user_id' => $teller->user_id,
            'debit' => 100000,
            'kredit' => 0,
            'saldo_akhir' => 0,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');

        $corrId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekA,
            'jenis_transaksi_id' => 1,
            'periode_id' => $periodeId,
            'user_id' => $teller->user_id,
            'debit' => 0,
            'kredit' => 150000,
            'saldo_akhir' => 150000,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');

        DB::table('tblAUDIT_LOG')->insert([
            'user_id' => $teller->user_id,
            'aksi' => 'KOREKSI_TRANSAKSI',
            'entitas' => 'tblTRANSAKSI',
            'entitas_id' => $origId,
            'data_baru' => json_encode([
                'reversal_id' => $revId,
                'corrected_id' => $corrId,
                'reversal_label' => 'Pembatalan Setoran',
                'corrected_label' => 'Hasil Koreksi',
                'nominal' => 150000,
                'alasan' => 'Salah ketik nominal',
            ]),
            'created_at' => now(),
        ]);

        $itemsA = NotificationService::untuk($userA);
        $koreksiCards = array_values(array_filter($itemsA, fn ($i) => str_starts_with($i['id'], 'nasabah_koreksi_')));
        $trxIds = array_map(fn ($i) => $i['id'], $itemsA);

        // Hanya 1 kartu koreksi, dan reversal/corrected child tidak muncul sebagai kartu transaksi baru terpisah
        $this->assertCount(1, $koreksiCards);
        $this->assertStringContainsString('Pembatalan Setoran & Hasil Koreksi', $koreksiCards[0]['teks']);
        $this->assertNotContains("nasabah_trx_{$revId}", $trxIds);
        $this->assertNotContains("nasabah_trx_{$corrId}", $trxIds);

        // Nasabah B tidak melihat notifikasi Nasabah A
        $itemsB = NotificationService::untuk($userB);
        $this->assertCount(0, $itemsB);
    }

    public function test_manager_notifications_and_mark_all_read_persistence(): void
    {
        $manager = $this->makeUser(2, 'Manager Test');
        $nasabahId = $this->makeNasabah('Nasabah Manager');

        // Periode bulan lalu masih buka
        $past = now()->subMonth();
        $periodeId = DB::table('tblPERIODE')->insertGetId([
            'user_id' => $manager->user_id,
            'tahun' => $past->year,
            'bulan' => $past->month,
            'status_periode' => 'buka',
        ], 'periode_id');

        // Rekening saldo tidak sinkron dengan saldo_akhir transaksi terakhir
        $rekId = DB::table('tblREKENING')->insertGetId([
            'no_rek' => '1001-2026-00004',
            'nasabah_id' => $nasabahId,
            'tgl_buka' => now()->toDateString(),
            'saldo' => 999000,
            'status_rekening' => 'aktif',
        ], 'rekening_id');

        DB::table('tblTRANSAKSI')->insert([
            'rekening_id' => $rekId,
            'jenis_transaksi_id' => 1,
            'periode_id' => $periodeId,
            'user_id' => $manager->user_id,
            'debit' => 0,
            'kredit' => 50000,
            'saldo_akhir' => 50000,
            'tgl_transaksi' => now(),
        ]);

        $items = NotificationService::untuk($manager);
        $this->assertSame(2, NotificationService::countActionUnread($items));

        // Tandai dibaca via endpoint -> badge reset ke 0 dan bertahan pada pemanggilan berikutnya
        $this->actingAs($manager)
            ->postJson(route('notifikasi.baca-semua'))
            ->assertOk()
            ->assertJson(['ok' => true, 'unread_action_count' => 0]);

        $afterRead = NotificationService::untuk($manager);
        $this->assertSame(0, NotificationService::countActionUnread($afterRead));
    }
}
