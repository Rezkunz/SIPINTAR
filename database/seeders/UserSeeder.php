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

        $users = [
            [
                'name'     => 'Muhammad Reza Pratama',
                'email'    => 'nasabah@sipintar.sch.id',
                'no_induk' => '0059876543',
                'phone'    => '081234567890',
                'role'     => 'nasabah',
            ],
            [
                'name'     => 'Andi Siswa Praktik',
                'email'    => 'siswa@sipintar.sch.id',
                'no_induk' => '0061234567',
                'phone'    => '081234567891',
                'role'     => 'siswa_praktik',
            ],
            [
                'name'     => 'Siti Rahmawati',
                'email'    => 'teller@sipintar.sch.id',
                'no_induk' => 'TLR-001',
                'phone'    => '081234567892',
                'role'     => 'teller',
            ],
            [
                'name'     => 'Budi Santoso',
                'email'    => 'bendahara@sipintar.sch.id',
                'no_induk' => 'BND-001',
                'phone'    => '081234567893',
                'role'     => 'bendahara',
            ],
            [
                'name'     => 'Hendra Wijaya',
                'email'    => 'bbm@sipintar.sch.id',
                'no_induk' => 'BBM-001',
                'phone'    => '081234567894',
                'role'     => 'bendahara_mini',
            ],
            [
                'name'     => 'Dra. Hj. Nurul Aini',
                'email'    => 'manager@sipintar.sch.id',
                'no_induk' => 'MGR-001',
                'phone'    => '081234567895',
                'role'     => 'manager',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['no_induk' => $userData['no_induk']],
                [
                    'name'     => $userData['name'],
                    'email'    => $userData['email'],
                    'password' => $defaultPassword,
                    'phone'    => $userData['phone'],
                    'role'     => $userData['role'],
                ]
            );
        }
    }
}
