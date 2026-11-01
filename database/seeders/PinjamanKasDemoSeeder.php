<?php

namespace Database\Seeders;

use App\Models\Nasabah;
use App\Models\User;
use App\Services\PinjamanCalculatorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PinjamanKasDemoSeeder extends Seeder
{
    /**
     * Seed data contoh realistis guru & staf peminjam untuk simulasi UAT.
     */
    public function run(): void
    {
        // 1. Akun Bendahara Bank Mini (BBM)
        $bbmUser = User::where('role_id', 3)->first();
        if (!$bbmUser) {
            $bbmUser = User::create([
                'role_id'     => 3,
                'no_hp'       => '081299900001',
                'no_induk'    => 'BBM-001',
                'nama_user'   => 'Hendra Wijaya, S.E (Bendahara Bank Mini)',
                'email'       => 'bbm@sipintar.sch.id',
                'password'    => Hash::make('sipintar123'),
                'status_akun' => 'aktif',
            ]);
        }

        // 2. Akun Bendahara Sekolah (Pengelola Payroll & Tunjangan)
        $bendaharaSekolah = User::where('role_id', 2)->first();
        if (!$bendaharaSekolah) {
            $bendaharaSekolah = User::create([
                'role_id'     => 2,
                'no_hp'       => '081299900002',
                'no_induk'    => 'BND-001',
                'nama_user'   => 'Budi Santoso, S.Pd (Bendahara Sekolah)',
                'email'       => 'bendahara@sipintar.sch.id',
                'password'    => Hash::make('sipintar123'),
                'status_akun' => 'aktif',
            ]);
        }

        // 3. Pastikan Master Kas Tunai (Cash) dan Kas Bank Bendahara Bank Mini Terisi
        $kasTunai = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();
        if (!$kasTunai) {
            DB::table('tblKAS')->insert([
                'nama_kas'  => 'Kas Tunai (Cash)',
                'jenis_kas' => 'kas',
                'saldo'     => 50000000,
            ]);
        } else {
            DB::table('tblKAS')->where('kas_id', $kasTunai->kas_id)->update(['nama_kas' => 'Kas Tunai (Cash)']);
        }

        $kasBank = DB::table('tblKAS')->where('jenis_kas', 'bank')->first();
        if (!$kasBank) {
            DB::table('tblKAS')->insert([
                'nama_kas'  => 'Rekening Bank Bendahara Bank Mini',
                'jenis_kas' => 'bank',
                'saldo'     => 100000000,
            ]);
        } else {
            DB::table('tblKAS')->where('kas_id', $kasBank->kas_id)->update(['nama_kas' => 'Rekening Bank Bendahara Bank Mini']);
        }

        // 4. Pastikan periode 3 bulan terakhir terbuka
        $now = Carbon::now();
        $prevMonth = Carbon::now()->subMonth();
        $twoMonthsAgo = Carbon::now()->subMonths(2);

        $this->ensurePeriode($bbmUser->user_id, $twoMonthsAgo->year, $twoMonthsAgo->month);
        $this->ensurePeriode($bbmUser->user_id, $prevMonth->year, $prevMonth->month);
        $this->ensurePeriode($bbmUser->user_id, $now->year, $now->month);

        // 5. Data 8 Profil Guru & Karyawan Sekolah
        $dataNasabah = [
            [
                'key'    => 1,
                'nama'   => 'Drs. H. Ahmad Sudrajat, M.Pd',
                'unit'   => 'Guru Senior Matematika',
                'hp'     => '081310010001',
                'induk'  => 'PEG-001',
                'email'  => 'guru.matematika@sipintar.sch.id',
                'gaji'   => 5500000,
                'tunj'   => 2000000,
            ],
            [
                'key'    => 2,
                'nama'   => 'Ibu Endang Purwanti, S.Pd',
                'unit'   => 'Guru Bahasa Indonesia',
                'hp'     => '081310010002',
                'induk'  => 'PEG-002',
                'email'  => 'guru.bahasa@sipintar.sch.id',
                'gaji'   => 4800000,
                'tunj'   => 1500000,
            ],
            [
                'key'    => 3,
                'nama'   => 'Bpk. Bambang Irawan, S.T',
                'unit'   => 'Guru Produktif TKJ',
                'hp'     => '081310010003',
                'induk'  => 'PEG-003',
                'email'  => 'guru.tkj@sipintar.sch.id',
                'gaji'   => 4500000,
                'tunj'   => 1300000,
            ],
            [
                'key'    => 4,
                'nama'   => 'Ibu Dewi Sartika, S.E',
                'unit'   => 'Staf Administrasi Tata Usaha',
                'hp'     => '081310010004',
                'induk'  => 'PEG-004',
                'email'  => 'staf.tu@sipintar.sch.id',
                'gaji'   => 3800000,
                'tunj'   => 1000000,
            ],
            [
                'key'    => 5,
                'nama'   => 'Bpk. Agus Supriadi, A.Md',
                'unit'   => 'Teknisi Laboratorium Komputer',
                'hp'     => '081310010005',
                'induk'  => 'PEG-005',
                'email'  => 'teknisi.lab@sipintar.sch.id',
                'gaji'   => 3200000,
                'tunj'   => 800000,
            ],
            [
                'key'    => 6,
                'nama'   => 'Ibu Hj. Ratna Sari, M.Pd',
                'unit'   => 'Guru Biologi & Pembina OSIS',
                'hp'     => '081310010006',
                'induk'  => 'PEG-006',
                'email'  => 'guru.biologi@sipintar.sch.id',
                'gaji'   => 5200000,
                'tunj'   => 1800000,
            ],
            [
                'key'    => 7,
                'nama'   => 'Bpk. Drs. H. Suryono, M.M',
                'unit'   => 'Guru Seni Budaya',
                'hp'     => '081310010007',
                'induk'  => 'PEG-007',
                'email'  => 'guru.seni@sipintar.sch.id',
                'gaji'   => 4900000,
                'tunj'   => 1400000,
            ],
            [
                'key'    => 8,
                'nama'   => 'Ibu Sri Wahyuni, S.Pd',
                'unit'   => 'Guru Bimbingan Konseling',
                'hp'     => '081310010008',
                'induk'  => 'PEG-008',
                'email'  => 'guru.bk@sipintar.sch.id',
                'gaji'   => 4600000,
                'tunj'   => 1200000,
            ],
        ];

        $peminjam = [];
        foreach ($dataNasabah as $d) {
            $nasabah = Nasabah::where('no_hp', $d['hp'])->first();
            if (!$nasabah) {
                $nasabah = Nasabah::create([
                    'no_hp'          => $d['hp'],
                    'nama_nasabah'   => $d['nama'],
                    'jenis_nasabah'  => 'pegawai',
                    'kelas_unit'     => $d['unit'],
                    'gaji_pokok'     => $d['gaji'],
                    'tunjangan'      => $d['tunj'],
                    'status_nasabah' => 'aktif',
                ]);
            } else {
                $nasabah->update([
                    'nama_nasabah' => $d['nama'],
                    'kelas_unit'   => $d['unit'],
                    'gaji_pokok'   => $d['gaji'],
                    'tunjangan'    => $d['tunj'],
                ]);
            }

            $user = User::where('nasabah_id', $nasabah->nasabah_id)->first();
            if (!$user) {
                $user = User::create([
                    'role_id'     => 6, // Nasabah
                    'nasabah_id'  => $nasabah->nasabah_id,
                    'no_hp'       => $d['hp'],
                    'no_induk'    => $d['induk'],
                    'nama_user'   => $d['nama'],
                    'email'       => $d['email'],
                    'password'    => Hash::make('sipintar123'),
                    'status_akun' => 'aktif',
                ]);
            } else {
                $user->update([
                    'nama_user' => $d['nama'],
                    'email'     => $d['email'],
                    'password'  => Hash::make('sipintar123'),
                ]);
            }

            // Pastikan guru/karyawan memiliki rekening tabungan berstatus aktif
            $rekNo = sprintf('1001-2026-%05d', $nasabah->nasabah_id);
            \App\Models\Rekening::firstOrCreate(
                ['nasabah_id' => $nasabah->nasabah_id],
                [
                    'no_rek'          => $rekNo,
                    'tgl_buka'        => '2026-01-15',
                    'saldo'           => 500000.00,
                    'status_rekening' => 'aktif',
                ]
            );

            $peminjam[$d['key']] = ['nasabah' => $nasabah, 'user' => $user];
        }

        // =========================================================================
        // SKENARIO 1: Pengajuan Baru (Menunggu Penilaian Kelayakan oleh BBM)
        // Peminjam: Drs. H. Ahmad Sudrajat, M.Pd (Guru Senior Matematika)
        // Login: guru.matematika@sipintar.sch.id / password
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[1]['nasabah']->nasabah_id)->delete();
        $pinj1Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[1]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 5000000,
            'tenor'            => 10,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $now->copy()->subDays(2)->toDateString(),
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'diajukan',
        ]);

        // =========================================================================
        // SKENARIO 2: Disetujui Layak (Menunggu Pencairan Dana oleh BBM)
        // Peminjam: Ibu Endang Purwanti, S.Pd (Guru Bahasa Indonesia)
        // Login: guru.bahasa@sipintar.sch.id / password
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[2]['nasabah']->nasabah_id)->delete();
        $pinj2Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[2]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 3000000,
            'tenor'            => 6,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $now->copy()->subDays(5)->toDateString(),
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'layak',
        ]);

        DB::table('tblKELAYAKAN')->insert([
            'pinjaman_id'     => $pinj2Id,
            'user_id'         => $bbmUser->user_id,
            'penghasilan'     => 6300000,
            'hasil_kelayakan' => 'layak',
            'catatan'         => 'Rasio cicilan terhadap total penghasilan aman (< 25%). Rekam jejak mengajar sangat baik.',
            'tgl_nilai'       => $now->copy()->subDays(3)->toDateString(),
        ]);

        // =========================================================================
        // SKENARIO 3: Aktif Berjalan — Jalur Potongan Gaji Sekolah
        // Peminjam: Bpk. Bambang Irawan, S.T (Guru Produktif TKJ)
        // Login: guru.tkj@sipintar.sch.id / password
        // Plafon: Rp 6.000.000, Tenor: 6 Bulan.
        // Bulan 1 (Okt) Lunas masuk paket RP selesai. Bulan 2 (Nov) Masuk Rencana Potongan Siap Terima Dana!
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[3]['nasabah']->nasabah_id)->delete();
        $tglCair3 = $twoMonthsAgo->copy()->startOfMonth()->addDays(2)->toDateString();
        $pinj3Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[3]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 6000000,
            'tenor'            => 6,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $twoMonthsAgo->copy()->startOfMonth()->toDateString(),
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'cair',
        ]);

        DB::table('tblKELAYAKAN')->insert([
            'pinjaman_id'     => $pinj3Id,
            'user_id'         => $bbmUser->user_id,
            'penghasilan'     => 5800000,
            'hasil_kelayakan' => 'layak',
            'catatan'         => 'Disetujui untuk pembiayaan laptop & sertifikasi kompetensi.',
            'tgl_nilai'       => $twoMonthsAgo->copy()->startOfMonth()->addDays(1)->toDateString(),
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id'   => $pinj3Id,
            'user_id'       => $bbmUser->user_id,
            'tgl_cair'      => $tglCair3,
            'nominal_cair'  => 6000000,
        ]);

        $jadwal3 = PinjamanCalculatorService::generateJadwalAngsuran($pinj3Id, 6000000, 6, $tglCair3, 2.0);
        foreach ($jadwal3 as $row) {
            DB::table('tblJADWAL_ANGSURAN')->insert($row);
        }

        // Bulan ke-1 (Oktober 2026) sudah lunas melalui paket potongan Oktober
        $jadwalBulan1 = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinj3Id)->where('angsuran_ke', 1)->first();
        if ($jadwalBulan1) {
            DB::table('tblANGSURAN')->insert([
                'jadwal_angsuran_id' => $jadwalBulan1->jadwal_angsuran_id,
                'detail_potongan_id' => null,
                'user_id'            => $bbmUser->user_id,
                'tgl_bayar'          => $jadwalBulan1->jatuh_tempo,
                'jenis_bayar'        => 'angsuran',
                'bayar_pokok'        => $jadwalBulan1->pokok,
                'bayar_bunga'        => $jadwalBulan1->bunga,
            ]);
            DB::table('tblJADWAL_ANGSURAN')
                ->where('jadwal_angsuran_id', $jadwalBulan1->jadwal_angsuran_id)
                ->update(['status_bayar' => 'lunas']);
        }

        // =========================================================================
        // SKENARIO 4: Aktif Berjalan — Jalur Bayar Langsung / Tunai Loket BBM
        // Peminjam: Ibu Dewi Sartika, S.E (Staf Administrasi TU)
        // Login: staf.tu@sipintar.sch.id / password
        // Plafon: Rp 4.000.000, Tenor: 4 Bulan. Bulan 1 Lunas, Sisa 3 Bulan.
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[4]['nasabah']->nasabah_id)->delete();
        $tglCair4 = $prevMonth->copy()->startOfMonth()->addDays(5)->toDateString();
        $pinj4Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[4]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 4000000,
            'tenor'            => 4,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $prevMonth->copy()->startOfMonth()->toDateString(),
            'metode_bayar'     => 'tunai',
            'status_pinjaman'  => 'cair',
        ]);

        DB::table('tblKELAYAKAN')->insert([
            'pinjaman_id'     => $pinj4Id,
            'user_id'         => $bbmUser->user_id,
            'penghasilan'     => 4800000,
            'hasil_kelayakan' => 'layak',
            'catatan'         => 'Plafon disetujui 4 juta untuk keperluan renovasi rumah keluarga.',
            'tgl_nilai'       => $prevMonth->copy()->startOfMonth()->addDays(3)->toDateString(),
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id'   => $pinj4Id,
            'user_id'       => $bbmUser->user_id,
            'tgl_cair'      => $tglCair4,
            'nominal_cair'  => 4000000,
        ]);

        $jadwal4 = PinjamanCalculatorService::generateJadwalAngsuran($pinj4Id, 4000000, 4, $tglCair4, 2.0);
        foreach ($jadwal4 as $row) {
            DB::table('tblJADWAL_ANGSURAN')->insert($row);
        }

        // Bayar 1x angsuran langsung ke kas tunai BBM
        $jadwal4_1 = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinj4Id)->where('angsuran_ke', 1)->first();
        if ($jadwal4_1) {
            DB::table('tblANGSURAN')->insert([
                'jadwal_angsuran_id' => $jadwal4_1->jadwal_angsuran_id,
                'detail_potongan_id' => null,
                'user_id'            => $bbmUser->user_id,
                'tgl_bayar'          => $jadwal4_1->jatuh_tempo,
                'jenis_bayar'        => 'angsuran',
                'bayar_pokok'        => $jadwal4_1->pokok,
                'bayar_bunga'        => $jadwal4_1->bunga,
            ]);
            DB::table('tblJADWAL_ANGSURAN')
                ->where('jadwal_angsuran_id', $jadwal4_1->jadwal_angsuran_id)
                ->update(['status_bayar' => 'lunas']);
        }

        // =========================================================================
        // SKENARIO 5: Sudah Lunas Sempurna (100% Lunas)
        // Peminjam: Bpk. Agus Supriadi, A.Md (Teknisi Lab Komputer)
        // Login: teknisi.lab@sipintar.sch.id / password
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[5]['nasabah']->nasabah_id)->delete();
        $tglCair5 = $now->copy()->subMonths(3)->toDateString();
        $pinj5Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[5]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 2000000,
            'tenor'            => 2,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $now->copy()->subMonths(3)->subDays(3)->toDateString(),
            'metode_bayar'     => 'tunai',
            'status_pinjaman'  => 'lunas',
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id'   => $pinj5Id,
            'user_id'       => $bbmUser->user_id,
            'tgl_cair'      => $tglCair5,
            'nominal_cair'  => 2000000,
        ]);

        $jadwal5 = PinjamanCalculatorService::generateJadwalAngsuran($pinj5Id, 2000000, 2, $tglCair5, 2.0);
        foreach ($jadwal5 as $row) {
            $row['status_bayar'] = 'lunas';
            $jId = DB::table('tblJADWAL_ANGSURAN')->insertGetId($row);

            DB::table('tblANGSURAN')->insert([
                'jadwal_angsuran_id' => $jId,
                'detail_potongan_id' => null,
                'user_id'            => $bbmUser->user_id,
                'tgl_bayar'          => $row['jatuh_tempo'],
                'jenis_bayar'        => 'angsuran',
                'bayar_pokok'        => $row['pokok'],
                'bayar_bunga'        => $row['bunga'],
            ]);
        }

        // =========================================================================
        // SKENARIO 6: Guru Aktif Potong Gaji 1 — Siap Diajukan di Okt & Nov
        // Peminjam: Ibu Hj. Ratna Sari, M.Pd (Guru Biologi & Pembina OSIS)
        // Login: guru.biologi@sipintar.sch.id / password
        // Plafon: Rp 6.000.000, Tenor: 6 Bulan. Angsuran Rp 1.120.000 / bln.
        // Jadwal 1 (Okt) dan Jadwal 2 (Nov) berstatus BELUM BAYAR & BELUM MASUK PAKET.
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[6]['nasabah']->nasabah_id)->delete();
        $tglCair6 = $twoMonthsAgo->copy()->startOfMonth()->addDays(9)->toDateString();
        $pinj6Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[6]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 6000000,
            'tenor'            => 6,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $twoMonthsAgo->copy()->startOfMonth()->toDateString(),
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'cair',
        ]);

        DB::table('tblKELAYAKAN')->insert([
            'pinjaman_id'     => $pinj6Id,
            'user_id'         => $bbmUser->user_id,
            'penghasilan'     => 7000000,
            'hasil_kelayakan' => 'layak',
            'catatan'         => 'Guru Senior & Pembina OSIS, kemampuan bayar sangat memadai.',
            'tgl_nilai'       => $twoMonthsAgo->copy()->startOfMonth()->addDays(5)->toDateString(),
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id'   => $pinj6Id,
            'user_id'       => $bbmUser->user_id,
            'tgl_cair'      => $tglCair6,
            'nominal_cair'  => 6000000,
        ]);

        $jadwal6 = PinjamanCalculatorService::generateJadwalAngsuran($pinj6Id, 6000000, 6, $tglCair6, 2.0);
        foreach ($jadwal6 as $row) {
            DB::table('tblJADWAL_ANGSURAN')->insert($row);
        }

        // =========================================================================
        // SKENARIO 7: Guru Aktif Potong Gaji 2 — Siap Diajukan di Okt & Nov
        // Peminjam: Bpk. Drs. H. Suryono, M.M (Guru Seni Budaya)
        // Login: guru.seni@sipintar.sch.id / password
        // Plafon: Rp 4.000.000, Tenor: 4 Bulan. Angsuran Rp 1.080.000 / bln.
        // Jadwal 1 (Okt) dan Jadwal 2 (Nov) berstatus BELUM BAYAR & BELUM MASUK PAKET.
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[7]['nasabah']->nasabah_id)->delete();
        $tglCair7 = $twoMonthsAgo->copy()->startOfMonth()->addDays(14)->toDateString();
        $pinj7Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[7]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 4000000,
            'tenor'            => 4,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $twoMonthsAgo->copy()->startOfMonth()->toDateString(),
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'cair',
        ]);

        DB::table('tblKELAYAKAN')->insert([
            'pinjaman_id'     => $pinj7Id,
            'user_id'         => $bbmUser->user_id,
            'penghasilan'     => 6300000,
            'hasil_kelayakan' => 'layak',
            'catatan'         => 'Plafon 4 juta untuk perlengkapan pameran seni siswa.',
            'tgl_nilai'       => $twoMonthsAgo->copy()->startOfMonth()->addDays(10)->toDateString(),
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id'   => $pinj7Id,
            'user_id'       => $bbmUser->user_id,
            'tgl_cair'      => $tglCair7,
            'nominal_cair'  => 4000000,
        ]);

        $jadwal7 = PinjamanCalculatorService::generateJadwalAngsuran($pinj7Id, 4000000, 4, $tglCair7, 2.0);
        foreach ($jadwal7 as $row) {
            DB::table('tblJADWAL_ANGSURAN')->insert($row);
        }

        // =========================================================================
        // SKENARIO 8: Guru Aktif Potong Gaji 3 — Siap Diajukan di Bulan Berjalan (Nov)
        // Peminjam: Ibu Sri Wahyuni, S.Pd (Guru Bimbingan Konseling)
        // Login: guru.bk@sipintar.sch.id / password
        // Plafon: Rp 3.000.000, Tenor: 6 Bulan. Angsuran Rp 560.000 / bln.
        // Jadwal 1 (Nov) berstatus BELUM BAYAR & BELUM MASUK PAKET.
        // =========================================================================
        DB::table('tblPINJAMAN')->where('nasabah_id', $peminjam[8]['nasabah']->nasabah_id)->delete();
        $tglCair8 = $prevMonth->copy()->startOfMonth()->addDays(4)->toDateString();
        $pinj8Id = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $peminjam[8]['nasabah']->nasabah_id,
            'user_id'          => $bbmUser->user_id,
            'nominal_pinjaman' => 3000000,
            'tenor'            => 6,
            'bunga_persen'     => 2.0,
            'tgl_pengajuan'    => $prevMonth->copy()->startOfMonth()->toDateString(),
            'metode_bayar'     => 'potong_gaji',
            'status_pinjaman'  => 'cair',
        ]);

        DB::table('tblKELAYAKAN')->insert([
            'pinjaman_id'     => $pinj8Id,
            'user_id'         => $bbmUser->user_id,
            'penghasilan'     => 5800000,
            'hasil_kelayakan' => 'layak',
            'catatan'         => 'Kelayakan disetujui untuk pembiayaan kursus konseling anak.',
            'tgl_nilai'       => $prevMonth->copy()->startOfMonth()->addDays(2)->toDateString(),
        ]);

        DB::table('tblPENCAIRAN')->insert([
            'pinjaman_id'   => $pinj8Id,
            'user_id'       => $bbmUser->user_id,
            'tgl_cair'      => $tglCair8,
            'nominal_cair'  => 3000000,
        ]);

        $jadwal8 = PinjamanCalculatorService::generateJadwalAngsuran($pinj8Id, 3000000, 6, $tglCair8, 2.0);
        foreach ($jadwal8 as $row) {
            DB::table('tblJADWAL_ANGSURAN')->insert($row);
        }

        // =========================================================================
        // SEEDING PAKET RENCANA POTONGAN GAJI
        // 1. Paket Oktober 2026: Selesai & Dibukukan (Dilengkapi BAST)
        // 2. Paket November 2026: Menunggu Verifikasi / Siap Terima Dana (Bambang Irawan)
        // =========================================================================
        DB::table('tblDETAIL_POTONGAN')->delete();
        DB::table('tblRENCANA_POTONGAN')->delete();

        $periodeOkt = DB::table('tblPERIODE')
            ->where('tahun', $prevMonth->year)
            ->where('bulan', $prevMonth->month)
            ->first();

        if ($periodeOkt && $jadwalBulan1) {
            $nomOkt = $jadwalBulan1->pokok + $jadwalBulan1->bunga;
            $rpOktId = DB::table('tblRENCANA_POTONGAN')->insertGetId([
                'periode_id'        => $periodeOkt->periode_id,
                'user_id'           => $bbmUser->user_id,
                'total_potongan'    => $nomOkt,
                'tgl_potongan'      => $prevMonth->copy()->endOfMonth()->toDateString(),
                'total_realisasi'   => $nomOkt,
                'metode_penyerahan' => 'transfer',
                'catatan_sekolah'   => 'Pemotongan gaji periode Oktober 2026 berhasil disalurkan via Payroll Bank BJB.',
                'status_potongan'   => 'selesai',
            ]);

            DB::table('tblDETAIL_POTONGAN')->insert([
                'rencana_potongan_id' => $rpOktId,
                'jadwal_angsuran_id'  => $jadwalBulan1->jadwal_angsuran_id,
                'nominal_rencana'     => $nomOkt,
                'hasil_potongan'      => 'berhasil',
                'nominal_realisasi'   => $nomOkt,
                'keterangan'          => 'Lunas dipotong utuh',
            ]);
        }

        $jadwalBulan2 = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinj3Id)->where('angsuran_ke', 2)->first();
        $periodeNov = DB::table('tblPERIODE')
            ->where('tahun', $now->year)
            ->where('bulan', $now->month)
            ->first();

        if ($periodeNov && $jadwalBulan2) {
            $nomNov = $jadwalBulan2->pokok + $jadwalBulan2->bunga;
            $rpNovId = DB::table('tblRENCANA_POTONGAN')->insertGetId([
                'periode_id'        => $periodeNov->periode_id,
                'user_id'           => $bbmUser->user_id,
                'total_potongan'    => $nomNov,
                'tgl_potongan'      => $now->toDateString(),
                'total_realisasi'   => $nomNov,
                'metode_penyerahan' => 'transfer',
                'catatan_sekolah'   => 'Pemotongan gaji November 2026 telah diproses, dana telah ditransfer ke Bank Mini Wiyata.',
                'status_potongan'   => 'menunggu_verifikasi',
            ]);

            DB::table('tblDETAIL_POTONGAN')->insert([
                'rencana_potongan_id' => $rpNovId,
                'jadwal_angsuran_id'  => $jadwalBulan2->jadwal_angsuran_id,
                'nominal_rencana'     => $nomNov,
                'hasil_potongan'      => 'berhasil',
                'nominal_realisasi'   => $nomNov,
                'keterangan'          => 'Dipotong penuh dari tunjangan',
            ]);
        }
    }

    private function ensurePeriode(int $userId, int $tahun, int $bulan): void
    {
        $ada = DB::table('tblPERIODE')
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->exists();

        if (!$ada) {
            DB::table('tblPERIODE')->insert([
                'user_id'        => $userId,
                'tahun'          => $tahun,
                'bulan'          => $bulan,
                'status_periode' => 'buka',
                'tgl_tutup'      => null,
            ]);
        }
    }
}
