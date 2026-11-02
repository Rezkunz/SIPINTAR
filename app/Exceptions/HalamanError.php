<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Mengganti halaman error bawaan Laravel dengan halaman ramah yang mengarahkan pengguna
 * kembali ke dashboard role-nya. Rincian teknis tetap tercatat di storage/logs/laravel.log.
 */
class HalamanError
{
    public static function render(Throwable $e, Request $request): ?Response
    {
        // Biarkan Laravel menangani: validasi form (kembali + pesan), belum login (ke halaman login), API/JSON
        if ($e instanceof ValidationException || $e instanceof AuthenticationException || $request->expectsJson()) {
            return null;
        }

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        [$judul, $pesan] = match (true) {
            $status === 403 => ['Halaman Tidak Dapat Diakses', 'Halaman ini bukan bagian dari hak akses peran Anda. Silakan kembali ke dashboard Anda.'],
            $status === 404 => ['Halaman Tidak Ditemukan', 'Halaman atau data yang Anda cari tidak ada, mungkin sudah dihapus atau alamatnya salah. Silakan kembali ke dashboard Anda.'],
            $status === 405 => ['Alamat Tidak Dapat Dibuka Langsung', 'Alamat ini hanya untuk memproses formulir. Silakan kembali ke dashboard dan ulangi dari menu.'],
            $status === 419 => ['Sesi Formulir Kedaluwarsa', 'Halaman terlalu lama dibiarkan terbuka sehingga formulir tidak dapat diproses. Silakan kembali ke dashboard lalu ulangi.'],
            $status === 429 => ['Terlalu Banyak Permintaan', 'Tunggu sebentar sebelum mencoba lagi, lalu kembali ke dashboard Anda.'],
            $status === 503 => ['Sistem Sedang Dalam Perawatan', 'SIPINTAR sedang diperbarui. Silakan coba beberapa saat lagi.'],
            default         => ['Terjadi Kendala pada Sistem', 'Permintaan Anda belum dapat diproses. Silakan kembali ke dashboard dan coba lagi. Bila terus terjadi, hubungi Manager Bank Mini.'],
        };

        return response()->view('errors.halaman', [
            'status' => $status,
            'judul'  => $judul,
            'pesan'  => $pesan,
            'detail' => $status < 500 ? self::pesanUntukPengguna($e) : null,
        ], $status, $e instanceof HttpExceptionInterface ? $e->getHeaders() : []);
    }

    /**
     * Pesan abort() yang ditulis untuk pengguna (mis. "Halaman ini hanya untuk nasabah pegawai.")
     * ditampilkan; pesan teknis bawaan framework disembunyikan.
     */
    private static function pesanUntukPengguna(Throwable $e): ?string
    {
        $pesan = trim($e->getMessage());

        $teknis = '/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS) |could not be found|No query results|not supported for route|unauthorized|CSRF|Too Many|Unauthenticated|Service Unavailable/i';

        return ($pesan === '' || preg_match($teknis, $pesan)) ? null : $pesan;
    }
}
