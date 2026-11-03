<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAkunAktif
{
    /**
     * Putus sesi pengguna yang akunnya dinonaktifkan Manager saat sedang login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->status_akun !== 'aktif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $pesan = 'Akun Anda telah dinonaktifkan. Silakan hubungi Manager Bank Mini.';

            return $request->expectsJson()
                ? response()->json(['message' => $pesan], 403)
                : redirect()->route('login')->withErrors(['login' => $pesan]);
        }

        return $next($request);
    }
}
