<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PinjamanCalculatorService;

class PinjamanCalculatorServiceTest extends TestCase
{
    public function test_hitung_jasa_per_bulan_flat_dua_persen(): void
    {
        // 2% dari Rp 10.000.000 = Rp 200.000
        $jasa = PinjamanCalculatorService::hitungJasaPerBulan(10000000, 2.0);
        $this->assertEquals(200000, $jasa);

        // 2% dari Rp 5.500.000 = Rp 110.000
        $jasa2 = PinjamanCalculatorService::hitungJasaPerBulan(5500000, 2.0);
        $this->assertEquals(110000, $jasa2);
    }

    public function test_hitung_simulasi_pinjaman(): void
    {
        // Plafon 6.000.000, tenor 6 bulan, rate 2%
        // Pokok bulanan = 1.000.000
        // Jasa bulanan = 120.000
        // Total angsuran/bulan = 1.120.000
        // Total bunga = 720.000
        // Total kewajiban = 6.720.000
        $simulasi = PinjamanCalculatorService::hitungSimulasi(6000000, 6, 2.0);

        $this->assertEquals(1000000, $simulasi['pokok_per_bulan']);
        $this->assertEquals(120000, $simulasi['bunga_per_bulan']);
        $this->assertEquals(1120000, $simulasi['total_per_bulan']);
        $this->assertEquals(720000, $simulasi['total_bunga']);
        $this->assertEquals(6720000, $simulasi['total_kewajiban']);
    }

    public function test_generate_jadwal_angsuran_dengan_pembulatan_presisi(): void
    {
        // Kasus ganjil: Plafon 1.000.000, tenor 3 bulan (1.000.000 / 3 = 333.333,33)
        // Bulan 1: 333.333
        // Bulan 2: 333.333
        // Bulan 3 (penyesuaian sisa pembulatan): 1.000.000 - 666.666 = 333.334
        $pinjamanId = 99;
        $jadwal = PinjamanCalculatorService::generateJadwalAngsuran($pinjamanId, 1000000, 3, '2026-01-10', 2.0);

        $this->assertCount(3, $jadwal);

        $this->assertEquals(1, $jadwal[0]['angsuran_ke']);
        $this->assertEquals(333333, $jadwal[0]['pokok']);
        $this->assertEquals(20000, $jadwal[0]['bunga']);
        $this->assertEquals('2026-02-10', $jadwal[0]['jatuh_tempo']);
        $this->assertEquals('belum', $jadwal[0]['status_bayar']);

        $this->assertEquals(2, $jadwal[1]['angsuran_ke']);
        $this->assertEquals(333333, $jadwal[1]['pokok']);

        // Bulan ke-3 menampung selisih pembulatan
        $this->assertEquals(3, $jadwal[2]['angsuran_ke']);
        $this->assertEquals(333334, $jadwal[2]['pokok']);

        // Total akumulasi pokok tepat 1.000.000
        $totalPokok = array_sum(array_column($jadwal, 'pokok'));
        $this->assertEquals(1000000, $totalPokok);
    }

    public function test_hitung_sisa_pokok_dan_status_lunas(): void
    {
        $nominalPinjaman = 5000000;
        
        // Baru bayar 2.000.000
        $sisa1 = PinjamanCalculatorService::hitungSisaPokok($nominalPinjaman, 2000000);
        $this->assertEquals(3000000, $sisa1);
        $this->assertFalse(PinjamanCalculatorService::isLunas($nominalPinjaman, 2000000));

        // Sudah bayar penuh 5.000.000
        $sisa2 = PinjamanCalculatorService::hitungSisaPokok($nominalPinjaman, 5000000);
        $this->assertEquals(0, $sisa2);
        $this->assertTrue(PinjamanCalculatorService::isLunas($nominalPinjaman, 5000000));

        // Bayar lebih dari pokok (toleransi sisa tidak boleh negatif)
        $sisa3 = PinjamanCalculatorService::hitungSisaPokok($nominalPinjaman, 5500000);
        $this->assertEquals(0, $sisa3);
        $this->assertTrue(PinjamanCalculatorService::isLunas($nominalPinjaman, 5500000));
    }
}

