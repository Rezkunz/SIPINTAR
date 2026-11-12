<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiswaPraktik\BukaRekeningController;
use App\Http\Controllers\SiswaPraktik\DraftTransaksiController;
use App\Http\Controllers\Teller\TellerTransactionController;
use App\Http\Controllers\Teller\TellerVerificationController;
use App\Http\Controllers\Teller\TellerInterestController;
use App\Http\Controllers\Teller\TellerCorrectionController;
use App\Http\Controllers\Teller\TellerNasabahController;
use App\Http\Controllers\BendaharaMini\PinjamanBbmController;
use App\Http\Controllers\BendaharaMini\AngsuranBbmController;
use App\Http\Controllers\BendaharaMini\KartuPiutangBbmController;
use App\Http\Controllers\BendaharaMini\KasController;
use App\Http\Controllers\Bendahara\PotonganSekolahController;
use App\Http\Controllers\Nasabah\NasabahDashboardController;
use App\Http\Controllers\Manager\LaporanTabunganController;
use App\Http\Controllers\Manager\LaporanKasHarianController;
use App\Http\Controllers\Manager\LaporanPinjamanController;
use App\Http\Controllers\Manager\LaporanKasBendaharaController;
use App\Http\Controllers\Manager\LaporanTahunanController;
use App\Http\Controllers\Manager\LaporanNeracaController;
use App\Http\Controllers\Manager\PosNeracaController;
use App\Http\Controllers\Manager\TutupPeriodeController;
use App\Http\Controllers\Manager\KelolaPenggunaController;
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
Route::middleware(['auth', 'akun.aktif'])->group(function () {

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
            Route::get('/draft', [DraftTransaksiController::class, 'create'])->name('draft');
            Route::post('/draft', [DraftTransaksiController::class, 'store'])->name('draft.store');
            Route::get('/draft/riwayat', [DraftTransaksiController::class, 'index'])->name('draft.riwayat');
            Route::get('/draft/cari-rekening', [DraftTransaksiController::class, 'searchRekening'])->name('draft.cari-rekening');
            Route::get('/draft/{draft}', [DraftTransaksiController::class, 'show'])->name('draft.show');

            Route::post('/buka-rekening', [BukaRekeningController::class, 'store'])->name('buka-rekening.store');
            Route::post('/buka-rekening/{rekening}/approve', [BukaRekeningController::class, 'approve'])->name('buka-rekening.approve');
        });

        // Akses bersama: Siswa Praktik & Teller (Otoritas Teller Sebagai Pusat Approval)
        Route::middleware('role:siswa_praktik,teller')->group(function () {
            Route::get('/setoran', [TellerTransactionController::class, 'setoran'])->name('setoran');
            Route::post('/setoran', [TellerTransactionController::class, 'storeSetoran'])->name('setoran.store');
            Route::get('/penarikan', [TellerTransactionController::class, 'penarikan'])->name('penarikan');
            Route::post('/penarikan', [TellerTransactionController::class, 'storePenarikan'])->name('penarikan.store');
            Route::get('/buka-rekening', [TellerTransactionController::class, 'bukaRekening'])->name('buka-rekening');
            Route::get('/rekening/cari', [TellerTransactionController::class, 'searchRekening'])->name('rekening.cari');

          
        });

        // Akses khusus Teller
        Route::middleware('role:teller')->group(function () {
            Route::get('/verifikasi', [TellerVerificationController::class, 'index'])->name('verifikasi');
            Route::get('/verifikasi/{draft}', [TellerVerificationController::class, 'show'])->name('verifikasi.show');
            Route::post('/verifikasi/{draft}/setujui', [TellerVerificationController::class, 'approve'])->name('verifikasi.approve');
            Route::post('/verifikasi/{draft}/tolak', [TellerVerificationController::class, 'reject'])->name('verifikasi.reject');

            Route::get('/bukti', [TellerTransactionController::class, 'bukti'])->name('bukti');
            Route::get('/bukti/{transaksi}', [TellerTransactionController::class, 'showBukti'])->name('bukti.show');

            Route::get('/jasa', [TellerInterestController::class, 'index'])->name('jasa');

            Route::get('/saldo', [TellerTransactionController::class, 'saldo'])->name('saldo');
            Route::get('/mutasi', [TellerTransactionController::class, 'mutasi'])->name('mutasi');
            Route::get('/mutasi/cetak', [TellerTransactionController::class, 'printMutasi'])->name('mutasi.cetak');
            Route::get('/log-transaksi', [TellerTransactionController::class, 'logHarian'])->name('log-transaksi');
            Route::get('/log-transaksi/cetak', [TellerTransactionController::class, 'printLogHarian'])->name('log-transaksi.cetak');

            Route::get('/koreksi', [TellerCorrectionController::class, 'index'])->name('koreksi');
            Route::post('/koreksi/{transaksi}', [TellerCorrectionController::class, 'correct'])->name('koreksi.process');
            Route::get('/audit-log', [TellerCorrectionController::class, 'auditLog'])->name('audit-log');

            Route::get('/nasabah/tambah', [TellerNasabahController::class, 'create'])->name('nasabah.tambah');
            Route::post('/nasabah', [TellerNasabahController::class, 'store'])->name('nasabah.store');
            Route::get('/nasabah/{nasabah}/ubah', [TellerNasabahController::class, 'edit'])->whereNumber('nasabah')->name('nasabah.ubah');
            Route::put('/nasabah/{nasabah}', [TellerNasabahController::class, 'update'])->whereNumber('nasabah')->name('nasabah.update');
            Route::post('/rekening/{rekening}/tutup', [TellerNasabahController::class, 'tutup'])->whereNumber('rekening')->name('rekening.tutup');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: BENDAHARA SEKOLAH (Khusus Pemantauan & Pencatatan Hasil Potongan Gaji)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:bendahara')->group(function () {
        Route::prefix('bendahara/potongan')->name('bendahara.potongan.')->group(function () {
            Route::get('/', [PotonganSekolahController::class, 'index'])->name('index');
            Route::get('/download/{rencana}', [PotonganSekolahController::class, 'downloadCsv'])->name('download');
            Route::get('/bast/{rencana}', [PotonganSekolahController::class, 'cetakBast'])->name('bast');
            Route::get('/hasil/{rencana}', [PotonganSekolahController::class, 'formHasil'])->name('hasil');
            Route::post('/hasil/{rencana}', [PotonganSekolahController::class, 'simpanHasil'])->name('simpan');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: BENDAHARA BANK MINI (Pinjaman, Angsuran, Piutang, dan Kas)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:bendahara_mini')->group(function () {
        // 1. Modul Pinjaman
        Route::prefix('bbm/pinjaman')->name('bbm.pinjaman.')->group(function () {
            Route::get('/', [PinjamanBbmController::class, 'index'])->name('index');
            Route::get('/ajukan', [PinjamanBbmController::class, 'create'])->name('ajukan');
            Route::post('/ajukan', [PinjamanBbmController::class, 'store'])->name('store');
            Route::get('/kelayakan', [PinjamanBbmController::class, 'kelayakan'])->name('kelayakan');
            Route::post('/kelayakan/{pinjaman}', [PinjamanBbmController::class, 'simpanKelayakan'])->name('kelayakan.simpan');
            Route::get('/cair', [PinjamanBbmController::class, 'pencairan'])->name('cair');
            Route::post('/cair/{pinjaman}', [PinjamanBbmController::class, 'prosesPencairan'])->name('cair.proses');
        });

        // 2. Modul Angsuran
        Route::prefix('bbm/angsuran')->name('bbm.angsuran.')->group(function () {
            Route::get('/langsung', [AngsuranBbmController::class, 'catatLangsung'])->name('langsung');
            Route::post('/langsung', [AngsuranBbmController::class, 'simpanPembayaranLangsung'])->name('langsung.simpan');
            Route::get('/potongan', [AngsuranBbmController::class, 'rencanaPotongan'])->name('potongan');
            Route::post('/potongan', [AngsuranBbmController::class, 'simpanRencanaPotongan'])->name('potongan.simpan');
            Route::get('/potongan/{rencana}', [AngsuranBbmController::class, 'detailRencanaPotongan'])->name('potongan.detail');
            Route::post('/potongan/{rencana}/konfirmasi', [AngsuranBbmController::class, 'konfirmasiPenerimaanDana'])->name('potongan.konfirmasi');
            Route::get('/potongan/{rencana}/bast', [AngsuranBbmController::class, 'cetakBast'])->name('potongan.bast');
            Route::get('/koreksi', [AngsuranBbmController::class, 'koreksi'])->name('koreksi');
            Route::post('/koreksi/{angsuran}', [AngsuranBbmController::class, 'prosesKoreksi'])->name('koreksi.proses');
        });

        // 3. Modul Kartu Piutang
        Route::prefix('bbm/kartu')->name('bbm.kartu.')->group(function () {
            Route::get('/', [KartuPiutangBbmController::class, 'kartu'])->name('index');
            Route::get('/rekap', function () {
                return redirect()->route('bbm.kartu.index');
            })->name('rekap');
        });

        // 4. Modul Kas Harian & Bank
        Route::prefix('kas')->name('kas.')->group(function () {
            Route::get('/harian', [KasController::class, 'harian'])->name('harian');
            Route::post('/harian', [KasController::class, 'simpanHarian'])->name('harian.simpan');
            Route::get('/bank', [KasController::class, 'bank'])->name('bank');
            Route::post('/bank', [KasController::class, 'simpanBank'])->name('bank.simpan');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: MANAGER (Laporan Keuangan Eksekutif, Periode, Akun)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:manager')->group(function () {
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/tabungan', [LaporanTabunganController::class, 'index'])->name('tabungan');
            Route::get('/tabungan/csv', [LaporanTabunganController::class, 'exportCsv'])->name('tabungan.csv');
            Route::get('/tabungan/pdf', [LaporanTabunganController::class, 'exportPdf'])->name('tabungan.pdf');
            Route::get('/tabungan/xlsx', [LaporanTabunganController::class, 'exportXlsx'])->name('tabungan.xlsx');
            Route::get('/tabungan/bulanan', [LaporanTabunganController::class, 'bulanan'])->name('tabungan.bulanan');
            Route::get('/tabungan/bulanan/csv', [LaporanTabunganController::class, 'exportBulananCsv'])->name('tabungan.bulanan.csv');
            Route::get('/tabungan/bulanan/pdf', [LaporanTabunganController::class, 'exportBulananPdf'])->name('tabungan.bulanan.pdf');
            Route::get('/tabungan/bulanan/xlsx', [LaporanTabunganController::class, 'exportBulananXlsx'])->name('tabungan.bulanan.xlsx');
            Route::get('/tabungan/kas-harian', [LaporanKasHarianController::class, 'index'])->name('tabungan.kas');
            Route::get('/tabungan/kas-harian/csv', [LaporanKasHarianController::class, 'exportCsv'])->name('tabungan.kas.csv');
            Route::get('/tabungan/kas-harian/pdf', [LaporanKasHarianController::class, 'exportPdf'])->name('tabungan.kas.pdf');
            Route::get('/tabungan/kas-harian/xlsx', [LaporanKasHarianController::class, 'exportXlsx'])->name('tabungan.kas.xlsx');

            Route::get('/pinjaman', [LaporanPinjamanController::class, 'index'])->name('pinjaman');
            Route::get('/pinjaman/csv', [LaporanPinjamanController::class, 'exportCsv'])->name('pinjaman.csv');
            Route::get('/pinjaman/pdf', [LaporanPinjamanController::class, 'exportPdf'])->name('pinjaman.pdf');
            Route::get('/pinjaman/xlsx', [LaporanPinjamanController::class, 'exportXlsx'])->name('pinjaman.xlsx');
            Route::get('/pinjaman/kas-bendahara', [LaporanKasBendaharaController::class, 'index'])->name('pinjaman.kas');
            Route::get('/pinjaman/kas-bendahara/csv', [LaporanKasBendaharaController::class, 'exportCsv'])->name('pinjaman.kas.csv');
            Route::get('/pinjaman/kas-bendahara/pdf', [LaporanKasBendaharaController::class, 'exportPdf'])->name('pinjaman.kas.pdf');
            Route::get('/pinjaman/kas-bendahara/xlsx', [LaporanKasBendaharaController::class, 'exportXlsx'])->name('pinjaman.kas.xlsx');

            Route::get('/tahunan', [LaporanTahunanController::class, 'index'])->name('tahunan');
            Route::get('/tahunan/csv', [LaporanTahunanController::class, 'exportCsv'])->name('tahunan.csv');
            Route::get('/tahunan/pdf', [LaporanTahunanController::class, 'exportPdf'])->name('tahunan.pdf');
            Route::get('/tahunan/xlsx', [LaporanTahunanController::class, 'exportXlsx'])->name('tahunan.xlsx');

            Route::get('/neraca', [LaporanNeracaController::class, 'index'])->name('neraca');
            Route::get('/neraca/csv', [LaporanNeracaController::class, 'exportCsv'])->name('neraca.csv');
            Route::get('/neraca/pdf', [LaporanNeracaController::class, 'exportPdf'])->name('neraca.pdf');
            Route::get('/neraca/xlsx', [LaporanNeracaController::class, 'exportXlsx'])->name('neraca.xlsx');
            Route::post('/neraca/pos', [PosNeracaController::class, 'store'])->name('neraca.pos.store');
            Route::put('/neraca/pos/{pos}', [PosNeracaController::class, 'update'])->whereNumber('pos')->name('neraca.pos.update');
            Route::delete('/neraca/pos/{pos}', [PosNeracaController::class, 'destroy'])->whereNumber('pos')->name('neraca.pos.destroy');

            Route::get('/ekspor', function () {
                return view('manager.laporan.ekspor');
            })->name('ekspor');
        });

        Route::get('/periode/tutup', [TutupPeriodeController::class, 'index'])->name('periode.tutup');
        Route::post('/periode/tutup', [TutupPeriodeController::class, 'tutup'])->name('periode.tutup.proses');
        Route::post('/periode/buka-kembali', [TutupPeriodeController::class, 'bukaKembali'])->name('periode.tutup.buka');

        Route::get('/akun/kelola', [KelolaPenggunaController::class, 'index'])->name('akun.kelola');
        Route::post('/akun/kelola', [KelolaPenggunaController::class, 'store'])->name('akun.kelola.store');
        Route::put('/akun/kelola/{user}', [KelolaPenggunaController::class, 'update'])->whereNumber('user')->name('akun.kelola.update');
        Route::patch('/akun/kelola/{user}/status', [KelolaPenggunaController::class, 'ubahStatus'])->whereNumber('user')->name('akun.kelola.status');
    });

    /*
    |--------------------------------------------------------------------------
    | Hak Akses: NASABAH (Portal Mandiri Nasabah — Layanan Aktif)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:nasabah', 'rekening.aktif'])->group(function () {
        Route::prefix('nasabah')->name('nasabah.')->group(function () {
            Route::get('/saldo', [NasabahDashboardController::class, 'mutasi'])->name('saldo');

            Route::get('/angsuran', [NasabahDashboardController::class, 'jadwalAngsuran'])->name('angsuran');

            // Info & Pengajuan Pinjaman (khusus pegawai — validasi di controller)
            Route::get('/pinjaman', [NasabahDashboardController::class, 'pinjaman'])->name('pinjaman');
            Route::get('/pinjaman/ajukan', [NasabahDashboardController::class, 'formAjukanPinjaman'])->name('pinjaman.ajukan');
            Route::post('/pinjaman/ajukan', [NasabahDashboardController::class, 'simpanAjukanPinjaman'])->name('pinjaman.store');
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

/*
|--------------------------------------------------------------------------
| Alamat tidak dikenal: lewat grup web agar sesi terbaca, sehingga halaman 404
| dapat menampilkan tombol kembali ke dashboard role pengguna yang sedang login
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    abort(404);
});
