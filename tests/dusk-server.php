<?php

/**
 * Router server uji Dusk (port 8010) yang memakai konfigurasi .env.uat.
 *
 * Dengan APP_ENV=uat, Laravel memuat .env.uat (database sipintar_dusk) tanpa menukar file .env,
 * sehingga aplikasi pengembangan di port 8000 tetap memakai database aslinya walau test sedang berjalan.
 *
 * Jalankan dari folder root project:
 *     php -S 127.0.0.1:8010 -t public tests/dusk-server.php
 */
putenv('APP_ENV=uat');
$_ENV['APP_ENV'] = 'uat';
$_SERVER['APP_ENV'] = 'uat';

$folderPublic = realpath(__DIR__ . '/../public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

// File statis (CSS, JS, gambar) disajikan langsung oleh server bawaan PHP dari -t public
if ($uri !== '/' && is_file($folderPublic . $uri)) {
    return false;
}

require $folderPublic . '/index.php';
