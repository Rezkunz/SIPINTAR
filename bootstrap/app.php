<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'rekening.aktif' => \App\Http\Middleware\EnsureRekeningAktif::class,
            'akun.aktif'     => \App\Http\Middleware\EnsureAkunAktif::class,
            'no.cache'       => \App\Http\Middleware\NoCacheHalamanLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error tidak ditampilkan mentah; pengguna diarahkan kembali ke dashboard role-nya
        $exceptions->render(fn (\Throwable $e, \Illuminate\Http\Request $request) => \App\Exceptions\HalamanError::render($e, $request));
    })->create();
