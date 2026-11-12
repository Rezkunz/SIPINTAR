<?php

namespace Tests\Browser\Support;

use Tests\DuskTestCase;

/**
 * Dasar pengujian skenario: setiap test case dimulai dari database testing yang bersih
 * (migrate:fresh + DatabaseSeeder) ditambah akun uji ketujuh peran.
 *
 * Tidak memakai trait DatabaseMigrations karena trait itu menjalankan migrate:rollback
 * sesudah tiap tes, sementara sebagian migrasi lama tidak dapat di-rollback bila tabel berisi data.
 */
abstract class SkenarioTestCase extends DuskTestCase
{
    use DataUji;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->siapkanDataUji();

        foreach (glob(static::folderUnduhan() . DIRECTORY_SEPARATOR . '*.{pdf,xlsx,csv,crdownload}', GLOB_BRACE) ?: [] as $file) {
            @unlink($file);
        }
    }
}
