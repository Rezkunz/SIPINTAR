<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Memperbarui data profil pengguna aktif (Semua Role).
     * Field yang diizinkan: nama, kelas_unit (jika nasabah), no_hp, email, password.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi login tidak valid.'], 401);
        }

        $activeTab = $request->input('active_tab', 'info');
        $isSecurityTab = ($activeTab === 'security') || $request->filled('password') || $request->filled('current_password');

        // 1. Validasi Berstandar Industri
        $rules = [
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'kelas_unit' => ['nullable', 'string', 'max:50'],
            'no_hp' => [
                'required',
                'regex:/^(08|628)[0-9]{8,13}$/',
                function ($attribute, $value, $fail) use ($user) {
                    // Cek unique no_hp di tblUSER kecuali milik user saat ini
                    $existsUser = DB::table('tblUSER')
                        ->where('no_hp', $value)
                        ->where('user_id', '!=', $user->user_id)
                        ->exists();

                    if ($existsUser) {
                        $fail('Nomor handphone / WhatsApp sudah digunakan oleh akun lain.');
                    }
                },
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                function ($attribute, $value, $fail) use ($user) {
                    $existsEmail = DB::table('tblUSER')
                        ->where('email', $value)
                        ->where('user_id', '!=', $user->user_id)
                        ->exists();

                    if ($existsEmail) {
                        $fail('Alamat email sudah terdaftar di sistem.');
                    }
                },
            ],
        ];

        if ($isSecurityTab) {
            $rules['current_password'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('Kata sandi saat ini (lama) tidak sesuai.');
                    }
                },
            ];
            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:current_password',
            ];
            $rules['password_confirmation'] = [
                'required',
                'string',
            ];
        } else {
            $rules['current_password'] = ['nullable'];
            $rules['password'] = ['nullable'];
            $rules['password_confirmation'] = ['nullable'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.min' => 'Nama lengkap minimal 3 karakter.',
            'no_hp.required' => 'Nomor WhatsApp wajib diisi.',
            'no_hp.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'current_password.required' => 'Kata sandi saat ini (lama) wajib diisi untuk verifikasi identitas.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.different' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.',
            'password.min' => 'Kata sandi baru minimal 8 karakter demi keamanan.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi kata sandi baru wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 2. Simpan Perubahan ke Database
        DB::beginTransaction();
        try {
            // Update tblUSER
            $userData = [
                'nama_user' => trim($request->nama),
                'no_hp'     => trim($request->no_hp),
                'email'     => strtolower(trim($request->email)),
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            DB::table('tblUSER')
                ->where('user_id', $user->user_id)
                ->update($userData);

            // Jika user berelasi dengan tblNASABAH, sinkronkan juga
            $nasabahId = $user->nasabah_id;
            if (!$nasabahId && $user->role === 'nasabah') {
                $foundNasabah = DB::table('tblNASABAH')->where('no_hp', $user->no_hp)->first();
                if ($foundNasabah) {
                    $nasabahId = $foundNasabah->nasabah_id;
                    DB::table('tblUSER')->where('user_id', $user->user_id)->update(['nasabah_id' => $nasabahId]);
                }
            }

            if ($nasabahId) {
                $nasabahData = [
                    'nama_nasabah' => trim($request->nama),
                    'no_hp'        => trim($request->no_hp),
                ];

                if ($request->has('kelas_unit')) {
                    $nasabahData['kelas_unit'] = trim($request->kelas_unit);
                }

                DB::table('tblNASABAH')
                    ->where('nasabah_id', $nasabahId)
                    ->update($nasabahData);
            }

            DB::commit();

            $successMessage = $isSecurityTab 
                ? 'Kata sandi akun Anda berhasil diperbarui!' 
                : 'Profil akun berhasil diperbarui.';

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'user' => [
                    'nama' => trim($request->nama),
                    'no_hp' => trim($request->no_hp),
                    'email' => strtolower(trim($request->email)),
                    'kelas_unit' => $request->kelas_unit ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memperbarui profil: ' . $e->getMessage(),
            ], 500);
        }
    }
}
