<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    public function test_konversi_terbilang_indonesia(): void
    {
        $this->assertSame('nol', Terbilang::convert(0));
        $this->assertSame('seribu', Terbilang::convert(1000));
        $this->assertSame('satu juta lima ratus ribu', Terbilang::convert(1500000));
        $this->assertSame('dua juta lima belas', Terbilang::convert(2000015));
        $this->assertSame('Lima puluh ribu rupiah', Terbilang::rupiah(50000));
    }
}
