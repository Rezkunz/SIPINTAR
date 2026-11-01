<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds for all 6 roles in SIPINTAR.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('sipintar123');

        $roleMap = [
            'teller'         => 1,
            'bendahara'      => 2,
            'bendahara_mini' => 3,
            'manager'        => 4,
            'siswa_praktik'  => 5,
            'nasabah'        => 6,
        ];

        $users = [
            [
                'nama_user' => 'Muhammad Reza Pratama',
                'email'     => 'nasabah@sipintar.sch.id',
                'no_induk'  => '0059876543',
                'no_hp'     => '081234567890',
                'role_id'   => 6,
            ],
            [
                'nama_user' => 'Andi Siswa Praktik',
                'email'     => 'siswa@sipintar.sch.id',
                'no_induk'  => '0061234567',
                'no_hp'     => '081234567891',
                'role_id'   => 5,
            ],
            [
                'nama_user' => 'Siti Rahmawati',
                'email'     => 'teller@sipintar.sch.id',
                'no_induk'  => 'TLR-001',
                'no_hp'     => '081234567892',
                'role_id'   => 1,
            ],
            [
                'nama_user' => 'Budi Santoso',
                'email'     => 'bendahara@sipintar.sch.id',
                'no_induk'  => 'BND-001',
                'no_hp'     => '081234567893',
                'role_id'   => 2,
            ],
            [
                'nama_user' => 'Hendra Wijaya',
                'email'     => 'bbm@sipintar.sch.id',
                'no_induk'  => 'BBM-001',
                'no_hp'     => '081234567894',
                'role_id'   => 3,
            ],
            [
                'nama_user' => 'Dra. Hj. Nurul Aini',
                'email'     => 'manager@sipintar.sch.id',
                'no_induk'  => 'MGR-001',
                'no_hp'     => '081234567895',
                'role_id'   => 4,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['no_induk' => $userData['no_induk']],
                [
                    'nama_user'   => $userData['nama_user'],
                    'email'       => $userData['email'],
                    'password'    => $defaultPassword,
                    'no_hp'       => $userData['no_hp'],
                    'role_id'     => $userData['role_id'],
                    'status_akun' => 'aktif',
                ]
            );
        }
    }
}
