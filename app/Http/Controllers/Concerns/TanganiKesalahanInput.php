<?php

namespace App\Http\Controllers\Concerns;

use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Penanganan kesalahan seragam untuk semua aksi yang menerima input (simpan, ubah, hapus).
 *
 * Pemakaian di controller:
 *
 *     try {
 *         ... proses simpan ...
 *     } catch (Throwable $e) {
 *         return $this->gagalProses($e, 'menyimpan setoran tabungan');
 *     }
 *
 * - ValidationException, HttpException, AuthorizationException & ModelNotFoundException diteruskan apa adanya.
 * - RuntimeException / DomainException dianggap aturan bisnis: pesannya ditampilkan ke pengguna.
 * - QueryException diterjemahkan berdasarkan SQLSTATE menjadi pesan yang jelas.
 * - Kesalahan lain dicatat ke log dengan kode referensi agar mudah dilacak.
 */
trait TanganiKesalahanInput
{
    protected function gagalProses(Throwable $e, string $aksi, bool $json = false): RedirectResponse|JsonResponse
    {
        // Biarkan Laravel menangani validasi (pesan per kolom), 403, dan 404 seperti biasa
        if ($e instanceof ValidationException
            || $e instanceof HttpExceptionInterface
            || $e instanceof AuthorizationException
            || $e instanceof ModelNotFoundException) {
            throw $e;
        }

        $pesan = $this->pesanKesalahan($e, $aksi);

        if ($json || request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $pesan], $this->kesalahanBisnis($e) ? 422 : 500);
        }

        return back()->withInput(request()->except(['password', 'password_confirmation', 'current_password', '_token']))
            ->with('error', $pesan);
    }

    protected function pesanKesalahan(Throwable $e, string $aksi): string
    {
        if ($this->kesalahanBisnis($e)) {
            return $e->getMessage();
        }

        $ref = strtoupper(Str::random(6));
        Log::error("[{$ref}] Gagal {$aksi}: " . $e->getMessage(), [
            'user_id' => auth()->id(),
            'url'     => request()->fullUrl(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        ]);

        if ($e instanceof QueryException) {
            $state = (string) ($e->errorInfo[0] ?? $e->getCode());

            $sebab = match (true) {
                $state === '23505'           => 'data yang sama sudah terdaftar (duplikat). Periksa kembali isian Anda.',
                $state === '23000'           => 'data bertentangan dengan data yang sudah ada (duplikat atau masih terhubung).',
                $state === '23503'           => 'data masih terhubung dengan data lain, sehingga tidak dapat disimpan atau dihapus.',
                $state === '23502'           => 'ada kolom wajib yang kosong.',
                $state === '22003'           => 'nilai angka terlalu besar untuk disimpan.',
                $state === '22001'           => 'isian teks terlalu panjang.',
                in_array($state, ['22P02', '22007', '22008'], true) => 'format isian tidak sesuai (angka/tanggal).',
                in_array($state, ['40001', '40P01'], true)        => 'data sedang diproses bersamaan oleh pengguna lain. Silakan coba lagi.',
                str_starts_with($state, '08') || $state === '57P01' => 'koneksi ke database terputus. Silakan coba beberapa saat lagi.',
                default                      => 'terjadi kendala pada database.',
            };

            return "Gagal {$aksi}: {$sebab} (Kode: {$ref})";
        }

        return "Gagal {$aksi} karena kesalahan sistem. Silakan coba lagi; bila berulang, laporkan ke admin dengan kode {$ref}.";
    }

    private function kesalahanBisnis(Throwable $e): bool
    {
        // Hanya exception yang sengaja dilempar aplikasi (pesan sudah ramah pengguna).
        // Turunan RuntimeException dari framework/driver (mis. PDOException) pesannya teknis, jadi tidak ditampilkan.
        $kelas = get_class($e);
        if (str_starts_with($kelas, 'App\\')) {
            return $e->getMessage() !== '';
        }

        $kelasUmum = in_array($kelas, [RuntimeException::class, DomainException::class, InvalidArgumentException::class], true);
        $folderApp = str_replace('\\', '/', app_path());
        $dariApp = str_starts_with(str_replace('\\', '/', $e->getFile()), $folderApp);

        return $kelasUmum && $dariApp && $e->getMessage() !== '';
    }
}
