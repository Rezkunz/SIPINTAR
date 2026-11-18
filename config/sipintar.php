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

    'bunga_tabungan' => [
        'siswa' => 0.001,
        'non_pegawai' => 0.001,
        'pegawai' => 0.002,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas Minimal Setoran Teller
    |--------------------------------------------------------------------------
    */
    'setoran' => [
        'min_awal' => 20000,
        'min_berikutnya' => 10000,
        'min_tanpa_batas' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemetaan Kode SANDI Buku Tabungan
    |--------------------------------------------------------------------------
    */
    'sandi' => [
        'setoran' => 'S1',
        'penarikan' => 'S2',
        'jasa_tabungan' => 'S3',
        'koreksi' => 'K1',
    ],

    'nasabah' => [
        'default_password' => env('NASABAH_DEFAULT_PASSWORD', 'sipintar123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambang Minimum Kas Tunai (Notifikasi Teller)
    |--------------------------------------------------------------------------
    */
    'kas' => [
        'min_saldo' => env('KAS_MIN_SALDO', 500000),
    ],

];
