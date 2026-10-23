<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Nominal Transaksi Draft Siswa Praktik
    |--------------------------------------------------------------------------
    |
    | Batasan minimum dan maksimum nominal per draft setoran/penarikan
    | yang diinput oleh siswa praktik pada simulasi bank mini.
    |
    */
    'draft' => [
        'min_nominal' => 1000,
        'max_nominal' => 5000000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Rekening Nasabah
    |--------------------------------------------------------------------------
    |
    | Format nomor rekening standar: [Prefix]-[Tahun]-[Urutan 5 Digit]
    | Contoh: 1001-2026-00001
    |
    */
    'rekening' => [
        'prefix' => '1001',
    ],

];
