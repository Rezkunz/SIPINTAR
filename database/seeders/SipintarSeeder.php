<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SipintarSeeder extends Seeder
{
    /**
     * Seed the SIPINTAR ERD tables with initial master data and demo accounts.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('sipintar123');

        // 1. tblROLE
        $roles = [
            ['role_id' => 1, 'nama_role' => 'Teller'],
            ['role_id' => 2, 'nama_role' => 'Bendahara'],
            ['role_id' => 3, 'nama_role' => 'Bendahara Bank Mini'],
            ['role_id' => 4, 'nama_role' => 'Manager'],
            ['role_id' => 5, 'nama_role' => 'Siswa Praktik'],
            ['role_id' => 6, 'nama_role' => 'Nasabah'],
        ];
        foreach ($roles as $r) {
            DB::table('tblROLE')->updateOrInsert(['role_id' => $r['role_id']], $r);
        }

        // 2. tblJENIS_TRANSAKSI
        $jenisTransaksi = [
            ['jenis_transaksi_id' => 1, 'nama_jenis' => 'setoran'],
            ['jenis_transaksi_id' => 2, 'nama_jenis' => 'penarikan'],
            ['jenis_transaksi_id' => 3, 'nama_jenis' => 'jasa_tabungan'],
        ];
        foreach ($jenisTransaksi as $jt) {
            DB::table('tblJENIS_TRANSAKSI')->updateOrInsert(['jenis_transaksi_id' => $jt['jenis_transaksi_id']], $jt);
        }

        // 3. tblKAS (2 Tempat Penyimpanan: Cash & Bank Milik Bendahara Bank Mini)
        $kasList = [
            ['kas_id' => 1, 'nama_kas' => 'Kas Tunai (Cash)', 'jenis_kas' => 'kas', 'saldo' => 50000000.00],
            ['kas_id' => 2, 'nama_kas' => 'Rekening Bank Bendahara Bank Mini', 'jenis_kas' => 'bank', 'saldo' => 100000000.00],
        ];
        foreach ($kasList as $k) {
            DB::table('tblKAS')->updateOrInsert(['kas_id' => $k['kas_id']], $k);
        }

        // 4. tblPERIODE
        DB::table('tblPERIODE')->updateOrInsert(
            ['tahun' => 2026, 'bulan' => 10],
            ['periode_id' => 1, 'status_periode' => 'buka', 'user_id' => null]
        );

        // 5. tblNASABAH
        $nasabahList = [
            [
                'nasabah_id'     => 1,
                'no_hp'          => '081234567890',
                'nama_nasabah'   => 'Muhammad Reza Pratama',
                'jenis_nasabah'  => 'non_pegawai',
                'kelas_unit'     => 'XII AKL 1',
                'status_nasabah' => 'aktif',
            ],
            [
                'nasabah_id'     => 2,
                'no_hp'          => '081234567899',
                'nama_nasabah'   => 'Drs. Supriyadi (Guru)',
                'jenis_nasabah'  => 'pegawai',
                'kelas_unit'     => 'Unit Kejuruan Akuntansi',
                'status_nasabah' => 'aktif',
            ],
        ];
        foreach ($nasabahList as $n) {
            DB::table('tblNASABAH')->updateOrInsert(['nasabah_id' => $n['nasabah_id']], $n);
        }

        // 6. tblREKENING
        $rekeningList = [
            [
                'rekening_id'     => 1,
                'no_rek'          => '1001-2026-00001',
                'nasabah_id'      => 1,
                'tgl_buka'        => now()->toDateString(),
                'saldo'           => 500000.00,
                'status_rekening' => 'aktif',
            ],
            [
                'rekening_id'     => 2,
                'no_rek'          => '1001-2026-00002',
                'nasabah_id'      => 2,
                'tgl_buka'        => now()->toDateString(),
                'saldo'           => 1500000.00,
                'status_rekening' => 'aktif',
            ],
        ];
        foreach ($rekeningList as $rek) {
            DB::table('tblREKENING')->updateOrInsert(['rekening_id' => $rek['rekening_id']], $rek);
        }

        // 7. tblUSER (Akun Login untuk 6 Role)
        $users = [
            [
                'user_id'     => 1,
                'role_id'     => 4, // Manager
                'nasabah_id'  => null,
                'no_hp'       => '081234567895',
                'no_induk'    => 'MGR-001',
                'email'       => 'manager@sipintar.sch.id',
                'password'    => $defaultPassword,
                'nama_user'   => 'Dra. Hj. Nurul Aini',
                'status_akun' => 'aktif',
            ],
            [
                'user_id'     => 2,
                'role_id'     => 3, // Bendahara Bank Mini
                'nasabah_id'  => null,
                'no_hp'       => '081234567894',
                'no_induk'    => 'BBM-001',
                'email'       => 'bbm@sipintar.sch.id',
                'password'    => $defaultPassword,
                'nama_user'   => 'Hendra Wijaya',
                'status_akun' => 'aktif',
            ],
            [
                'user_id'     => 3,
                'role_id'     => 2, // Bendahara
                'nasabah_id'  => null,
                'no_hp'       => '081234567893',
                'no_induk'    => 'BND-001',
                'email'       => 'bendahara@sipintar.sch.id',
                'password'    => $defaultPassword,
                'nama_user'   => 'Budi Santoso',
                'status_akun' => 'aktif',
            ],
            [
                'user_id'     => 4,
                'role_id'     => 1, // Teller
                'nasabah_id'  => null,
                'no_hp'       => '081234567892',
                'no_induk'    => 'TLR-001',
                'email'       => 'teller@sipintar.sch.id',
                'password'    => $defaultPassword,
                'nama_user'   => 'Siti Rahmawati',
                'status_akun' => 'aktif',
            ],
            [
                'user_id'     => 5,
                'role_id'     => 5, // Siswa Praktik
                'nasabah_id'  => null,
                'no_hp'       => '081234567891',
                'no_induk'    => '0061234567',
                'email'       => 'siswa@sipintar.sch.id',
                'password'    => $defaultPassword,
                'nama_user'   => 'Andi Siswa Praktik',
                'status_akun' => 'aktif',
            ],
            [
                'user_id'     => 6,
                'role_id'     => 6, // Nasabah
                'nasabah_id'  => 1,
                'no_hp'       => '081234567890',
                'no_induk'    => '0059876543',
                'email'       => 'nasabah@sipintar.sch.id',
                'password'    => $defaultPassword,
                'nama_user'   => 'Muhammad Reza Pratama',
                'status_akun' => 'aktif',
            ],
        ];

        foreach ($users as $u) {
            DB::table('tblUSER')->updateOrInsert(['user_id' => $u['user_id']], $u);
        }

        if (DB::getDriverName() === 'pgsql') {
            $sequences = [
                'tblROLE'            => 'role_id',
                'tblJENIS_TRANSAKSI' => 'jenis_transaksi_id',
                'tblKAS'             => 'kas_id',
                'tblPERIODE'         => 'periode_id',
                'tblNASABAH'         => 'nasabah_id',
                'tblREKENING'        => 'rekening_id',
                'tblUSER'            => 'user_id',
            ];
            foreach ($sequences as $table => $pk) {
                DB::statement("SELECT setval(pg_get_serial_sequence('\"{$table}\"', '{$pk}'), COALESCE(MAX(\"{$pk}\"), 1), MAX(\"{$pk}\") IS NOT NULL) FROM \"{$table}\"");
            }
        }
    }
}
