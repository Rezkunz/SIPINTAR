<?php

namespace Database\Seeders;

use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data minimum untuk pengujian browser (Laravel Dusk) modul Pinjaman & Kas.
 * Akun uji mengikuti tabel UAT (UC-08 s.d. UC-12), password: "Sipintar#Uji2026".
 */
class DuskSeeder extends Seeder
{
    public const PASSWORD = 'Sipintar#Uji2026';

    public function run(): void
    {
        $password = Hash::make(self::PASSWORD);

        foreach ([
            1 => 'Teller', 2 => 'Bendahara', 3 => 'Bendahara Bank Mini',
            4 => 'Manager', 5 => 'Siswa Praktik', 6 => 'Nasabah',
        ] as $id => $nama) {
            DB::table('tblROLE')->insert(['role_id' => $id, 'nama_role' => $nama]);
        }

        DB::table('tblJENIS_TRANSAKSI')->insert([
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan'],
        ]);

        DB::table('tblKAS')->insert([
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 50000000],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank Bendahara Bank Mini', 'jenis_kas' => 'bank', 'saldo' => 100000000],
        ]);

        $bbm = User::create([
            'role_id' => 3, 'nasabah_id' => null, 'no_hp' => '089900000003', 'no_induk' => 'DUSK-BBM',
            'nama_user' => 'Bendahara Bank Mini Uji', 'email' => 'bendahara@sipintar.test',
            'password' => $password, 'status_akun' => 'aktif',
        ]);

        User::create([
            'role_id' => 2, 'nasabah_id' => null, 'no_hp' => '089900000002', 'no_induk' => 'DUSK-BND',
            'nama_user' => 'Bendahara Sekolah Uji', 'email' => 'bendsekolah@sipintar.test',
            'password' => $password, 'status_akun' => 'aktif',
        ]);

        // Akun tambahan untuk Modul Dashboard (login, laporan, periode, kelola akun)
        User::create([
            'role_id' => 1, 'nasabah_id' => null, 'no_hp' => '089900000001', 'no_induk' => 'DUSK-TLR',
            'nama_user' => 'Teller Uji', 'email' => 'teller@sipintar.test',
            'password' => $password, 'status_akun' => 'aktif',
        ]);

        User::create([
            'role_id' => 4, 'nasabah_id' => null, 'no_hp' => '089900000004', 'no_induk' => 'DUSK-MGR',
            'nama_user' => 'Manajer Uji', 'email' => 'manajer@sipintar.test',
            'password' => $password, 'status_akun' => 'aktif',
        ]);

        User::create([
            'role_id' => 5, 'nasabah_id' => null, 'no_hp' => '089900000005', 'no_induk' => '0099000005',
            'nama_user' => 'Siswa Praktik Uji', 'email' => 'praktik@sipintar.test',
            'password' => $password, 'status_akun' => 'aktif',
        ]);

        DB::table('tblPERIODE')->insert([
            'user_id' => $bbm->user_id, 'tahun' => (int) date('Y'), 'bulan' => (int) date('n'),
            'status_periode' => 'buka', 'tgl_tutup' => null,
        ]);

        $this->buatNasabah('pegawai@sipintar.test', 'Budi Pegawai Uji', 'pegawai', 'Guru RPL', 4500000, 1500000, '089900000101', 'DUSK-PGW', '1001-2026-90001');
        $this->buatNasabah('nasabah.siswa@sipintar.test', 'Sari Siswa Uji', 'non_pegawai', 'XII RPL 1', 0, 0, '089900000102', 'DUSK-SIS', '1001-2026-90002');
    }

    private function buatNasabah(string $email, string $nama, string $jenis, string $unit, int $gaji, int $tunjangan, string $hp, string $noInduk, string $noRek): void
    {
        $nasabah = Nasabah::create([
            'no_hp' => $hp, 'nama_nasabah' => $nama, 'jenis_nasabah' => $jenis, 'kelas_unit' => $unit,
            'gaji_pokok' => $gaji, 'tunjangan' => $tunjangan, 'status_nasabah' => 'aktif',
        ]);

        User::create([
            'role_id' => 6, 'nasabah_id' => $nasabah->nasabah_id, 'no_hp' => $hp, 'no_induk' => $noInduk,
            'nama_user' => $nama, 'email' => $email,
            'password' => Hash::make(self::PASSWORD), 'status_akun' => 'aktif',
        ]);

        Rekening::create([
            'no_rek' => $noRek, 'nasabah_id' => $nasabah->nasabah_id, 'tgl_buka' => now()->toDateString(),
            // Saldo awal 0: saldo hanya terbentuk dari transaksi tercatat (konsisten dengan laporan)
            'saldo' => 0, 'status_rekening' => 'aktif',
        ]);
    }
}
