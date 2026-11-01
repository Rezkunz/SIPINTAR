<?php

namespace App\Http\Middleware;

use App\Models\Nasabah;
use App\Models\Rekening;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRekeningAktif
{
    /**
     * Memastikan nasabah yang login memiliki rekening yang telah diaktivasi fisik oleh Teller/Siswa Praktik.
     * Jika rekening berstatus 'pending', nasabah dilarang mengakses layanan perbankan (mutasi, angsuran, pinjaman).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Pemeriksaan khusus untuk role nasabah
        if ($user->role === 'nasabah') {
            $nasabah = $user->nasabah ?? ($user->nasabah_id ? Nasabah::find($user->nasabah_id) : Nasabah::where('no_hp', $user->no_hp)->first());
            $rekening = $nasabah ? Rekening::where('nasabah_id', $nasabah->nasabah_id)->first() : null;

            if (!$rekening || $rekening->status_rekening !== 'aktif') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Layanan Perbankan Terkunci: Rekening Anda belum diaktivasi oleh Siswa Praktik / Teller Bank Mini. Silakan lakukan aktivasi fisik di loket terlebih dahulu.',
                    ], 403);
                }

                return redirect()->route('dashboard')->with('error', 'Layanan Perbankan Terkunci: Rekening Anda belum diaktivasi oleh Siswa Praktik / Teller Bank Mini. Silakan bawa uang setoran awal tunai (min. Rp 10.000) ke loket Bank Mini Sekolah untuk mengaktifkan akun Anda.');
            }
        }

        return $next($request);
    }
}

