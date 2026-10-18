<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Tampilkan form login.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login (menggunakan nomor unik NISN / Kode Pegawai / No HP / Email).
     */
    public function login(Request $request)
    {
        $loginRaw = (string) $request->input('login', '');
        $throttleKey = Str::transliterate(Str::lower(trim($loginRaw)) . '|' . $request->ip());

        // 1. Rate Limiting Throttling (Maksimal 5 percobaan gagal per 60 detik)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan masuk yang gagal. Demi keamanan akun Anda, silakan coba kembali dalam {$seconds} detik.",
            ]);
        }

        // 2. Validasi Input Kredensial Login
        $request->validate([
            'login'    => ['required', 'string', 'min:3', 'max:100'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
        ], [
            'login.required'    => 'Nomor telepon, NISN / NIP, atau alamat email wajib diisi.',
            'login.min'         => 'Nomor identitas login minimal 3 karakter.',
            'login.max'         => 'Nomor identitas login maksimal 100 karakter.',
            'password.required' => 'Kata sandi keamanan akun wajib diisi.',
            'password.min'      => 'Kata sandi akun minimal 4 karakter.',
            'password.max'      => 'Kata sandi akun maksimal 100 karakter.',
        ]);

        $loginInput = strip_tags(trim($loginRaw));
        $password   = (string) $request->input('password');
        $remember   = $request->boolean('remember');

        try {
            // 3. Pencarian Akun Pengguna di tblUSER
            $user = User::query()
                ->where('no_hp', $loginInput)
                ->orWhere('no_induk', $loginInput)
                ->orWhere('email', strtolower($loginInput))
                ->first();

            // Cek Keberadaan Akun
            if (!$user) {
                RateLimiter::hit($throttleKey, 60);
                throw ValidationException::withMessages([
                    'login' => 'Nomor HP, NISN/NIP, atau email tersebut tidak terdaftar di sistem SIPINTAR.',
                ]);
            }

            // Cek Kesesuaian Kata Sandi
            if (!Hash::check($password, $user->password)) {
                RateLimiter::hit($throttleKey, 60);
                throw ValidationException::withMessages([
                    'password' => 'Kata sandi yang Anda masukkan salah. Pastikan huruf besar/kecil sesuai.',
                ]);
            }

            // Cek Status Keaktifan Akun
            if ($user->status_akun !== 'aktif') {
                throw ValidationException::withMessages([
                    'login' => 'Akun Anda saat ini berstatus ' . strtoupper($user->status_akun) . '. Silakan hubungi admin sekolah atau teller untuk aktivasi.',
                ]);
            }

            // Autentikasi Berhasil
            RateLimiter::clear($throttleKey);
            Auth::login($user, $remember);
            $request->session()->regenerate();

            Log::info("Login Berhasil: User ID {$user->user_id} ({$user->nama_user}) role {$user->role} dari IP {$request->ip()}");

            return redirect()->intended(route('dashboard'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('QueryException on login: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'login' => 'Terjadi gangguan koneksi pangkalan data perbankan saat memvalidasi akun. Silakan coba sesaat lagi.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Throwable on login: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'login' => 'Gagal memproses otentikasi login karena kesalahan sistem internal. Silakan muat ulang halaman.',
            ]);
        }
    }

    /**
     * Tampilkan form registrasi nasabah baru (Siswa / Guru).
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Proses pendaftaran nasabah baru dan pembuatan rekening otomatis.
     */
    public function register(Request $request)
    {
        // 1. Rate Limiting Pendaftaran (Mencegah Bot Spam - Maksimal 4 registrasi per 5 menit per IP)
        $registerThrottleKey = 'register-attempt|' . $request->ip();
        if (RateLimiter::tooManyAttempts($registerThrottleKey, 4)) {
            $seconds = RateLimiter::availableIn($registerThrottleKey);
            throw ValidationException::withMessages([
                'nama_lengkap' => "Batas pengajuan pendaftaran rekening tercapai untuk saat ini. Mohon tunggu {$seconds} detik sebelum mencoba mendaftar kembali.",
            ]);
        }

        // 2. Sanitasi Awal Input
        $cleanedNoHp = preg_replace('/[^0-9+]/', '', trim((string) $request->input('no_hp', '')));
        $cleanedEmail = strtolower(trim((string) $request->input('email', '')));
        $cleanedNoInduk = strip_tags(trim((string) $request->input('no_induk', '')));
        $cleanedNama = strip_tags(trim((string) $request->input('nama_lengkap', '')));
        $cleanedKelas = strip_tags(trim((string) $request->input('kelas_unit', '')));

        // Merge input yang sudah dibersihkan untuk validasi
        $request->merge([
            'no_hp'        => $cleanedNoHp,
            'email'        => $cleanedEmail,
            'no_induk'     => $cleanedNoInduk,
            'nama_lengkap' => $cleanedNama,
            'kelas_unit'   => $cleanedKelas,
        ]);

        // 3. Validasi Lengkap untuk Semua Field
        $request->validate([
            'nama_lengkap'          => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z\s\.,\'-]+$/'],
            'jenis_nasabah'         => ['required', 'in:non_pegawai,pegawai'],
            'no_induk'              => ['required', 'string', 'min:4', 'max:30', 'regex:/^[a-zA-Z0-9\-\.\/]+$/'],
            'kelas_unit'            => ['required', 'string', 'min:2', 'max:50'],
            'no_hp'                 => ['required', 'string', 'min:10', 'max:16', 'regex:/^[0-9+]+$/'],
            'email'                 => ['required', 'email:rfc,dns', 'max:100'],
            'password'              => ['required', 'string', 'min:6', 'max:64', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:6'],
        ], [
            'nama_lengkap.required'          => 'Nama lengkap wajib diisi sesuai kartu identitas / kartu pelajar.',
            'nama_lengkap.min'               => 'Nama lengkap minimal 3 karakter.',
            'nama_lengkap.max'               => 'Nama lengkap maksimal 100 karakter.',
            'nama_lengkap.regex'             => 'Nama lengkap hanya boleh mengandung huruf, spasi, titik, koma, atau tanda petik.',
            'jenis_nasabah.required'         => 'Kategori nasabah wajib dipilih (Siswa atau Pegawai/Guru).',
            'jenis_nasabah.in'               => 'Pilihan kategori nasabah tidak valid.',
            'no_induk.required'              => 'Nomor Induk (NISN untuk siswa / NIP untuk guru) wajib diisi.',
            'no_induk.min'                   => 'Nomor Induk minimal 4 digit karakter.',
            'no_induk.max'                   => 'Nomor Induk maksimal 30 karakter.',
            'no_induk.regex'                 => 'Nomor Induk hanya boleh berupa angka, huruf, titik, atau strip.',
            'kelas_unit.required'            => 'Kelas ruang belajar atau unit kerja wajib diisi.',
            'kelas_unit.min'                 => 'Kelas atau unit kerja minimal 2 karakter.',
            'kelas_unit.max'                 => 'Kelas atau unit kerja maksimal 50 karakter.',
            'no_hp.required'                 => 'Nomor WhatsApp / HP aktif wajib diisi untuk notifikasi rekening.',
            'no_hp.min'                      => 'Nomor WhatsApp / HP minimal 10 digit angka.',
            'no_hp.max'                      => 'Nomor WhatsApp / HP maksimal 16 digit angka.',
            'no_hp.regex'                    => 'Format nomor WhatsApp / HP tidak valid (gunakan format nomor angka).',
            'email.required'                 => 'Alamat email aktif wajib diisi.',
            'email.email'                    => 'Format alamat email tidak valid (contoh: siswa@sekolah.sch.id).',
            'email.max'                      => 'Alamat email maksimal 100 karakter.',
            'password.required'              => 'Kata sandi keamanan akun wajib diisi.',
            'password.min'                   => 'Kata sandi minimal 6 karakter demi perlindungan akun rekening.',
            'password.max'                   => 'Kata sandi maksimal 64 karakter.',
            'password.confirmed'             => 'Konfirmasi kata sandi tidak cocok. Mohon ketik ulang kata sandi dengan benar.',
            'password_confirmation.required' => 'Kolom ulangi kata sandi wajib diisi untuk verifikasi sandi.',
            'password_confirmation.min'      => 'Kolom ulangi kata sandi minimal 6 karakter.',
        ]);

        // 4. Pengecekan Duplikasi dengan Pesan Spesifik per Field
        if (User::where('no_induk', $cleanedNoInduk)->exists()) {
            throw ValidationException::withMessages([
                'no_induk' => 'Nomor Induk / NISN ini sudah terdaftar. Silakan masuk atau gunakan nomor induk lain.',
            ]);
        }

        if (Nasabah::where('no_hp', $cleanedNoHp)->exists() || User::where('no_hp', $cleanedNoHp)->exists()) {
            throw ValidationException::withMessages([
                'no_hp' => 'Nomor WhatsApp / HP ini sudah digunakan pada akun nasabah lain.',
            ]);
        }

        if (User::where('email', $cleanedEmail)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Alamat email ini sudah terdaftar pada akun lain. Silakan gunakan email lain.',
            ]);
        }

        // 5. Eksekusi Transaksi Database dengan Try-Catch Lengkap
        try {
            $user = DB::transaction(function () use ($cleanedNoHp, $cleanedNama, $request, $cleanedKelas, $cleanedNoInduk, $cleanedEmail) {
                // Simpan Data Nasabah
                $nasabah = Nasabah::create([
                    'no_hp'          => $cleanedNoHp,
                    'nama_nasabah'   => $cleanedNama,
                    'jenis_nasabah'  => $request->input('jenis_nasabah'),
                    'kelas_unit'     => $cleanedKelas,
                    'status_nasabah' => 'aktif',
                ]);

                // Generate Nomor Rekening Baru: format 1001-YYYY-XXXXX
                $tahun = date('Y');
                $urutan = str_pad((string) $nasabah->nasabah_id, 5, '0', STR_PAD_LEFT);
                $noRek = "1001-{$tahun}-{$urutan}";

                Rekening::create([
                    'no_rek'          => $noRek,
                    'nasabah_id'      => $nasabah->nasabah_id,
                    'tgl_buka'        => now()->toDateString(),
                    'saldo'           => 0.00,
                    'status_rekening' => 'aktif',
                ]);

                // Buat Akun Pengguna (Role: Nasabah = role_id 6)
                $user = User::create([
                    'role_id'     => 6, // Nasabah
                    'nasabah_id'  => $nasabah->nasabah_id,
                    'no_hp'       => $cleanedNoHp,
                    'no_induk'    => $cleanedNoInduk,
                    'email'       => $cleanedEmail,
                    'password'    => Hash::make($request->input('password')),
                    'nama_user'   => $cleanedNama,
                    'status_akun' => 'aktif',
                ]);

                return $user;
            });

            RateLimiter::hit($registerThrottleKey, 300);

            Auth::login($user);
            $request->session()->regenerate();

            Log::info("Registrasi Berhasil: Nasabah ID {$user->nasabah_id}, User ID {$user->user_id} ({$user->nama_user}) dari IP {$request->ip()}");

            return redirect()->route('dashboard')->with('success', 'Pendaftaran rekening nasabah berhasil! Selamat datang di portal SIPINTAR.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('QueryException on register: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Pendaftaran rekening gagal disimpan karena terjadi kendala pangkalan data. Silakan coba lagi beberapa saat.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Throwable on register: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Terjadi kesalahan sistem yang tidak terduga saat memproses pembukaan rekening nasabah.',
            ]);
        }
    }

    /**
     * Keluar dari sesi.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
