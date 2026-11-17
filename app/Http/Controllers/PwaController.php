<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Progressive Web App: manifest & service worker.
 *
 * Disajikan lewat route (bukan file statis di public/) karena production memakai `php artisan serve`,
 * yang tidak bisa diatur header Cache-Control maupun MIME type .webmanifest-nya.
 */
class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $ikon = fn (string $file, int $ukuran, string $tujuan) => [
            'src'     => asset("icons/{$file}"),
            'sizes'   => "{$ukuran}x{$ukuran}",
            'type'    => 'image/png',
            'purpose' => $tujuan,
        ];

        $manifest = [
            'id'               => '/',
            'name'             => 'SIPINTAR – Bank Mini Sekolah',
            'short_name'       => 'SIPINTAR',
            'description'      => 'Sistem Perbankan Bank Mini Sekolah: tabungan, pinjaman pegawai, kas, dan laporan.',
            'lang'             => 'id',
            'dir'              => 'ltr',
            'start_url'        => '/dashboard?sumber=pwa',
            'scope'            => '/',
            'display'          => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation'      => 'any',
            'background_color' => '#F8FAFC',
            'theme_color'      => '#0D5C46',
            'categories'       => ['finance', 'education', 'business'],
            'icons'            => [
                $ikon('icon-192.png', 192, 'any'),
                $ikon('icon-512.png', 512, 'any'),
                $ikon('icon-maskable-192.png', 192, 'maskable'),
                $ikon('icon-maskable-512.png', 512, 'maskable'),
            ],
            'shortcuts'        => [[
                'name'       => 'Dashboard',
                'short_name' => 'Dashboard',
                'url'        => '/dashboard?sumber=pwa',
                'icons'      => [['src' => asset('icons/shortcut-96.png'), 'sizes' => '96x96', 'type' => 'image/png']],
            ]],
        ];

        return response()
            ->json($manifest, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'no-cache');
    }

    public function serviceWorker(): Response
    {
        $sumber = resource_path('pwa/sw.js');
        $offline = public_path('offline.html');

        // Versi cache berubah setiap kali sw.js / halaman offline / ikon diperbarui (mis. saat deploy)
        $versi = substr(md5(implode('|', [
            filemtime($sumber),
            filemtime($offline),
            filemtime(public_path('icons/icon-192.png')),
            config('app.version', '1'),
        ])), 0, 10);

        $isi = str_replace('__VERSI_CACHE__', $versi, file_get_contents($sumber));

        return response($isi, 200, [
            'Content-Type'           => 'application/javascript; charset=utf-8',
            'Cache-Control'          => 'no-cache, no-store, must-revalidate',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
