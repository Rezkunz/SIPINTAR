<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * Smoke test: halaman utama mengarah ke halaman login.
     */
    public function test_halaman_utama_mengarah_ke_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPathIs('/login')
                ->assertSee('Selamat Datang');
        });
    }
}
