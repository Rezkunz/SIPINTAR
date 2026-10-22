<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi (Login & Logout)
|--------------------------------------------------------------------------
*/
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Routes Terotentikasi & Terproteksi Berdasarkan Hak Akses / Role
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard Utama: Masing-masing role otomatis melihat file view dashboard miliknya sendiri
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pengaturan Profil & Akun Pengguna (Semua Role via Slide Banner Drawer)
    Route::get('/profile', function () {
        return view('placeholder', [
            'title' => 'Pengaturan Profil Pengguna',
            'subtitle' => 'Kelola informasi identitas akun, nomor kontak WhatsApp, dan kata sandi login'
        ]);
    })->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: SISWA PRAKTIK & TELLER (Operasional Tabungan)
    |--------------------------------------------------------------------------
    */
    Route::prefix('tabungan')->name('tabungan.')->group(function () {
        // Akses khusus Siswa Praktik
        Route::middleware('role:siswa_praktik')->group(function () {
            Route::get('/draft', function () {
                return view('placeholder', ['title' => 'Input Draft Transaksi', 'subtitle' => 'Form pencatatan draft setoran / penarikan tabungan']);
            })->name('draft');
        });

        // Akses bersama: Siswa Praktik & Teller (Otoritas Teller Sebagai Pusat Approval)
        Route::middleware('role:siswa_praktik,teller')->group(function () {
            Route::get('/buka-rekening', [\App\Http\Controllers\Teller\TellerDashboardController::class, 'bukaRekening'])->name('buka-rekening');
            Route::post('/buka-rekening/aktivasi/{id}', [\App\Http\Controllers\Teller\TellerDashboardController::class, 'aktivasiRekening'])->name('buka-rekening.aktivasi');

            Route::get('/setoran', function () {
                return view('placeholder', ['title' => 'Catat Setoran Tabungan', 'subtitle' => 'Form setoran tabungan nasabah']);
            })->name('setoran');

            Route::get('/penarikan', function () {
                return view('placeholder', ['title' => 'Catat Penarikan Tabungan', 'subtitle' => 'Form penarikan saldo tabungan']);
            })->name('penarikan');
        });

        // Akses khusus Teller
        Route::middleware('role:teller')->group(function () {
            Route::get('/verifikasi', function () {
                return view('placeholder', ['title' => 'Verifikasi Draft Transaksi', 'subtitle' => 'Daftar verifikasi & validasi transaksi teller']);
            })->name('verifikasi');

            Route::get('/bukti', function () {
                return view('placeholder', ['title' => 'Cetak Bukti Transaksi', 'subtitle' => 'Cetak slip setoran / penarikan']);
            })->name('bukti');

            Route::get('/jasa', function () {
                return view('placeholder', ['title' => 'Hitung & Catat Jasa Tabungan', 'subtitle' => 'Perhitungan bagi hasil / jasa tabungan']);
            })->name('jasa');

            Route::get('/saldo', function () {
                return view('placeholder', ['title' => 'Cek Saldo & Mutasi Tabungan', 'subtitle' => 'Riwayat mutasi saldo seluruh nasabah']);
            })->name('saldo');

            Route::get('/koreksi', function () {
                return view('placeholder', ['title' => 'Pengajuan Koreksi Transaksi', 'subtitle' => 'Form koreksi transaksi tabungan']);
            })->name('koreksi');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: BENDAHARA (Pengelolaan Pinjaman & Angsuran)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:bendahara')->group(function () {
        Route::prefix('pinjaman')->name('pinjaman.')->group(function () {
            Route::get('/', function () {
                return view('placeholder', ['title' => 'Kelola Data Pinjaman', 'subtitle' => 'Daftar pinjaman nasabah yang aktif dan selesai']);
            })->name('index');

            Route::get('/ajukan', function () {
                return view('placeholder', ['title' => 'Pengajuan Pinjaman Baru', 'subtitle' => 'Form permohonan pinjaman nasabah']);
            })->name('ajukan');

            Route::get('/kelayakan', function () {
                return view('placeholder', ['title' => 'Penilaian Kelayakan Pinjaman', 'subtitle' => 'Analisis dan penilaian kelayakan kredit/pinjaman']);
            })->name('kelayakan');

            Route::get('/cair', function () {
                return view('placeholder', ['title' => 'Pencairan Pinjaman', 'subtitle' => 'Proses pencairan dana pinjaman ke nasabah']);
            })->name('cair');
        });

        Route::prefix('angsuran')->name('angsuran.')->group(function () {
            Route::get('/catat', function () {
                return view('placeholder', ['title' => 'Catat Pembayaran Angsuran', 'subtitle' => 'Pencatatan pembayaran angsuran pinjaman']);
            })->name('catat');

            Route::get('/potongan', function () {
                return view('placeholder', ['title' => 'Rencana Potongan Gaji', 'subtitle' => 'Daftar rencana pemotongan angsuran via gaji']);
            })->name('potongan');

            Route::get('/hasil', function () {
                return view('placeholder', ['title' => 'Catat Hasil Pemotongan', 'subtitle' => 'Konfirmasi eksekusi pemotongan angsuran']);
            })->name('hasil');

            Route::get('/pantau', function () {
                return view('placeholder', ['title' => 'Pantau Pemotongan Angsuran', 'subtitle' => 'Monitoring status pemotongan angsuran']);
            })->name('pantau');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: BENDAHARA BANK MINI (Kas Harian & Kas Bank)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:bendahara_mini')->group(function () {
        Route::prefix('kas')->name('kas.')->group(function () {
            Route::get('/harian', function () {
                return view('placeholder', ['title' => 'Pencatatan Kas Harian', 'subtitle' => 'Buku kas operasional harian bank mini']);
            })->name('harian');

            Route::get('/bank', function () {
                return view('placeholder', ['title' => 'Pencatatan Kas & Bank', 'subtitle' => 'Rekonsiliasi rekening dan transaksi bank']);
            })->name('bank');
        });

        Route::prefix('bbm')->name('bbm.')->group(function () {
            Route::get('/kartu', function () {
                return view('placeholder', ['title' => 'Kartu Piutang & Status Angsuran', 'subtitle' => 'Kartu kendali piutang per nasabah']);
            })->name('kartu');

            Route::get('/koreksi', function () {
                return view('placeholder', ['title' => 'Persetujuan / Koreksi Angsuran', 'subtitle' => 'Verifikasi dan koreksi data angsuran']);
            })->name('koreksi');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: MANAGER (Laporan Keuangan Eksekutif, Periode, Akun)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:manager')->group(function () {
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/tabungan', function () {
                return view('placeholder', ['title' => 'Laporan Transaksi Tabungan', 'subtitle' => 'Rekapitulasi transaksi tabungan periode tertentu']);
            })->name('tabungan');

            Route::get('/pinjaman', function () {
                return view('placeholder', ['title' => 'Laporan Pinjaman & Kas', 'subtitle' => 'Rekapitulasi pinjaman dan perputaran kas']);
            })->name('pinjaman');

            Route::get('/tahunan', function () {
                return view('placeholder', ['title' => 'Laporan Keuangan Tahunan', 'subtitle' => 'Laporan kinerja keuangan tahun berjalan']);
            })->name('tahunan');

            Route::get('/neraca', function () {
                return view('placeholder', ['title' => 'Laporan Gabungan & Neraca Saldo', 'subtitle' => 'Neraca saldo dan ikhtisar keuangan gabungan']);
            })->name('neraca');

            Route::get('/ekspor', function () {
                return view('placeholder', ['title' => 'Ekspor Laporan (Excel / PDF)', 'subtitle' => 'Unduh laporan dalam format dokumen']);
            })->name('ekspor');
        });

        Route::get('/periode/tutup', function () {
            return view('placeholder', ['title' => 'Tutup Buku / Periode', 'subtitle' => 'Proses penutupan buku periode bulanan / tahunan']);
        })->name('periode.tutup');

        Route::get('/akun/kelola', function () {
            return view('placeholder', ['title' => 'Kelola Akun Pengguna', 'subtitle' => 'Daftar pengguna dan manajemen hak akses']);
        })->name('akun.kelola');
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: NASABAH (Portal Mandiri Nasabah)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:nasabah')->group(function () {
        Route::prefix('nasabah')->name('nasabah.')->group(function () {
            Route::get('/saldo', function () {
                return view('placeholder', ['title' => 'Informasi Saldo & Riwayat Mutasi', 'subtitle' => 'Rincian saldo simpanan dan riwayat transaksi saya']);
            })->name('saldo');

            Route::get('/angsuran', function () {
                return view('placeholder', ['title' => 'Informasi Jadwal Angsuran Pinjaman', 'subtitle' => 'Jadwal jatuh tempo dan histori cicilan saya']);
            })->name('angsuran');

            // Info pinjaman (khusus pegawai — validasi di controller)
            Route::get('/pinjaman', [\App\Http\Controllers\Nasabah\NasabahDashboardController::class, 'pinjaman'])->name('pinjaman');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Chatbot AI Endpoint (Semua role terotentikasi)
    |--------------------------------------------------------------------------
    */
    Route::post('/chatbot/ask', function (\Illuminate\Http\Request $request) {
        $message = trim($request->input('message', ''));
        if (!$message) {
            return response()->json(['reply' => 'Pesan tidak boleh kosong.']);
        }

        // Coba Gemini API jika API key tersedia
        $apiKey = config('services.gemini.api_key');
        if ($apiKey) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [[
                        'parts' => [[
                            'text' => "Kamu adalah asisten Bank Mini SIPINTAR, sebuah bank tabungan sekolah di Indonesia. Jawab pertanyaan berikut dengan singkat, ramah, dan dalam bahasa Indonesia:\n\n{$message}"
                        ]]
                    ]],
                    'generationConfig' => ['maxOutputTokens' => 300]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($reply) {
                        return response()->json(['reply' => $reply]);
                    }
                }
            } catch (\Exception $e) {
                // Fallthrough to default
            }
        }

        return response()->json(['reply' => 'Maaf, asisten AI sedang tidak tersedia. Silakan hubungi staf Bank Mini Sekolah untuk bantuan langsung.']);
    })->name('chatbot.ask');
});
