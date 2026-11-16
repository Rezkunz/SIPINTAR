<?php

namespace Tests\Modul_Dashboard;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Pinjaman\SipintarDuskTestCase;

/**
 * Basis pengujian UAT Modul Dashboard (login, laporan, periode, kelola akun).
 * Mewarisi helper login & pembuat data pinjaman dari Modul Pinjaman.
 */
abstract class DashboardDuskTestCase extends SipintarDuskTestCase
{
    public const TELLER  = 'teller@sipintar.test';
    public const MANAJER = 'manajer@sipintar.test';
    public const PRAKTIK = 'praktik@sipintar.test';

    /** Path menu yang wajib tampil / tidak boleh tampil untuk tiap peran. */
    public const MENU_PERAN = [
        self::MANAJER => ['ada' => ['/akun/kelola', '/periode/tutup', '/laporan/neraca'], 'tidak' => ['/tabungan/setoran', '/bbm/pinjaman/cair']],
        self::TELLER  => ['ada' => ['/tabungan/setoran', '/tabungan/verifikasi'], 'tidak' => ['/akun/kelola', '/bbm/pinjaman/cair']],
        self::PRAKTIK => ['ada' => ['/tabungan/draft'], 'tidak' => ['/tabungan/verifikasi', '/akun/kelola']],
        self::BND_SKL => ['ada' => ['/bendahara/potongan'], 'tidak' => ['/bbm/pinjaman/cair', '/akun/kelola']],
        self::BBM     => ['ada' => ['/bbm/pinjaman/cair', '/kas/harian'], 'tidak' => ['/akun/kelola', '/tabungan/setoran']],
        self::PEGAWAI => ['ada' => ['/nasabah/saldo', '/nasabah/pinjaman/ajukan'], 'tidak' => ['/akun/kelola', '/bbm/pinjaman/cair']],
        self::SISWA   => ['ada' => ['/nasabah/saldo'], 'tidak' => ['/nasabah/pinjaman/ajukan', '/akun/kelola']],
    ];

    protected function rekening(string $email): object
    {
        return DB::table('tblREKENING')->where('nasabah_id', $this->nasabahId($email))->first();
    }

    /**
     * Catat transaksi tabungan seperti yang dilakukan Teller:
     * tblTRANSAKSI + saldo rekening + mutasi & saldo kas tunai.
     */
    protected function transaksiTabungan(string $email, float $setor, float $tarik = 0, ?string $tanggal = null): int
    {
        $tanggal ??= now()->toDateTimeString();
        $rek = $this->rekening($email);
        $saldoAkhir = (float) $rek->saldo + $setor - $tarik;
        $periodeId = $this->periodeId(substr($tanggal, 0, 10));

        $id = (int) DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id'        => $rek->rekening_id,
            'jenis_transaksi_id' => $setor > 0 ? 1 : 2,
            'periode_id'         => $periodeId,
            'draft_id'           => null,
            'user_id'            => $this->userId(self::TELLER),
            'debit'              => $tarik,
            'kredit'             => $setor,
            'saldo_akhir'        => $saldoAkhir,
            'tgl_transaksi'      => $tanggal,
        ], 'transaksi_id');

        DB::table('tblREKENING')->where('rekening_id', $rek->rekening_id)->update(['saldo' => $saldoAkhir]);

        $neto = $setor - $tarik;
        DB::table('tblMUTASI_KAS')->insert([
            'kas_id' => 1, 'periode_id' => $periodeId, 'user_id' => $this->userId(self::TELLER),
            'transaksi_id' => $id, 'angsuran_id' => null, 'pencairan_id' => null,
            'tgl_mutasi' => substr($tanggal, 0, 10), 'arah' => $neto >= 0 ? 'masuk' : 'keluar',
            'nominal' => abs($neto), 'uraian' => 'Transaksi tabungan (data uji)',
        ]);
        DB::table('tblKAS')->where('kas_id', 1)->increment('saldo', $neto);

        return $id;
    }

    /** periode_id untuk tanggal tertentu (dibuat 'buka' bila belum ada). */
    protected function periodeId(string $tanggal): int
    {
        [$tahun, $bulan] = [(int) substr($tanggal, 0, 4), (int) substr($tanggal, 5, 2)];
        $id = DB::table('tblPERIODE')->where('tahun', $tahun)->where('bulan', $bulan)->value('periode_id');

        return (int) ($id ?? DB::table('tblPERIODE')->insertGetId([
            'user_id' => null, 'tahun' => $tahun, 'bulan' => $bulan, 'status_periode' => 'buka', 'tgl_tutup' => null,
        ], 'periode_id'));
    }

    /** Isi & kirim form login dengan kredensial bebas (tanpa menunggu dashboard). */
    protected function kirimLogin(Browser $browser, string $login, string $password): Browser
    {
        $browser->visit('/login');
        $browser->driver->manage()->deleteAllCookies();
        $browser->visit('/login')->waitFor('#login');
        $this->isi($browser, '#login', $login);
        $this->isi($browser, '#password', $password);

        return $browser->press('#btn-submit');
    }

    /** Pasangan [label pos => angka] dari tabel laporan Neraca. */
    protected function posNeraca(Browser $browser): array
    {
        $baris = $browser->script(
            'return [...document.querySelectorAll("tr")].filter(tr => tr.querySelector("td.lap-income-num"))'
            . '.map(tr => [tr.cells[0].childNodes[0].textContent.trim(), tr.querySelector("td.lap-income-num").innerText.trim()]);'
        )[0];

        $hasil = [];
        foreach ($baris as [$label, $nilai]) {
            $hasil[$label] = preg_replace('/[^0-9.]/', '', $nilai);
        }

        return $hasil;
    }

    /** Teks seluruh kartu metrik (.metric-value) tanpa spasi, mis. "Rp425.000". */
    protected function nilaiMetrik(Browser $browser): array
    {
        return $browser->script(
            'return [...document.querySelectorAll(".metric-value")].map(e => e.innerText.replace(/\s/g, ""));'
        )[0];
    }

    protected function rp(float $n): string
    {
        return number_format($n, 0, ',', '.');
    }
}
