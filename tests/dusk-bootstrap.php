<?php

/**
 * Bootstrap PHPUnit untuk test Dusk.
 *
 * `php artisan dusk` sudah memuat .env (database pengembangan) lalu mewariskan variabelnya ke proses
 * PHPUnit. Laravel tidak menimpa variabel yang sudah ada, sehingga .env.uat tidak akan terpakai.
 * Di sini variabel warisan yang juga didefinisikan di .env.uat dibuang lebih dulu, agar test
 * selalu memakai .env.uat (database sipintar_dusk) tanpa menukar file .env.
 */
$fileUat = __DIR__ . '/../.env.uat';

if (is_file($fileUat)) {
    foreach (file($fileUat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $baris) {
        if (preg_match('/^\s*([A-Z0-9_]+)\s*=/', $baris, $cocok) && $cocok[1] !== 'APP_ENV') {
            putenv($cocok[1]);
            unset($_ENV[$cocok[1]], $_SERVER[$cocok[1]]);
        }
    }
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Modul_Tabungan_Iterasi 1/TabunganDuskTestCase.php';
