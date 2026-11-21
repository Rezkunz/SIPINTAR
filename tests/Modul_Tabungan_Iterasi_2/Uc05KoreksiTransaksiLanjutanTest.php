<?php

namespace Tests\Modul_Tabungan_Iterasi_2;

require_once __DIR__ . '/../Modul_Tabungan_Iterasi 1/TabunganDuskTestCase.php';

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Modul_Tabungan\TabunganDuskTestCase;

class Uc05KoreksiTransaksiLanjutanTest extends TabunganDuskTestCase
{
    /**
     * TC-05-01 | Negative | Percobaan mengoreksi transaksi yang sudah berstatus dikoreksi / pembalik / hasil koreksi ditolak
     */
    public function test_tc_05_01_koreksi_ulang_transaksi_yang_sudah_dikoreksi_ditolak(): void
    {
        $rekeningId = (int) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('rekening_id');
        $origId = $this->createTransaction($this->rekeningAktif, 'setoran', 50000, 1050000);

        $revId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekeningId,
            'jenis_transaksi_id' => 1,
            'periode_id' => $this->periodeId,
            'draft_id' => null,
            'user_id' => $this->tellerId,
            'debit' => 50000,
            'kredit' => 0,
            'saldo_akhir' => 1000000,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');

        $corrId = DB::table('tblTRANSAKSI')->insertGetId([
            'rekening_id' => $rekeningId,
            'jenis_transaksi_id' => 1,
            'periode_id' => $this->periodeId,
            'draft_id' => null,
            'user_id' => $this->tellerId,
            'debit' => 0,
            'kredit' => 75000,
            'saldo_akhir' => 1075000,
            'tgl_transaksi' => now(),
        ], 'transaksi_id');

        DB::table('tblREKENING')->where('rekening_id', $rekeningId)->update(['saldo' => 1075000]);

        DB::table('tblAUDIT_LOG')->insert([
            'user_id' => $this->tellerId,
            'aksi' => 'KOREKSI_TRANSAKSI',
            'entitas' => 'tblTRANSAKSI',
            'entitas_id' => $origId,
            'data_lama' => json_encode(['debit' => 0, 'kredit' => 50000, 'saldo_akhir' => 1050000]),
            'data_baru' => json_encode([
                'reversal_id' => $revId,
                'corrected_id' => $corrId,
                'nominal' => 75000,
                'alasan' => 'Koreksi awal',
            ]),
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($origId) {
            $this->disableAutoReload($browser);
            $this->loginAsTeller($browser)
                ->visit('/tabungan/koreksi?no_rek=' . urlencode($this->rekeningAktif))
                ->waitForText('Daftar Transaksi Rekening ' . $this->rekeningAktif, 15)
                ->assertSee('Sudah Dikoreksi');

            $this->disableAutoReload($browser);

            // Uji penolakan backend saat POST koreksi dikirim pada transaksi yang sudah pernah dikoreksi
            $noRek = $this->rekeningAktif;
            $browser->script("
                const token = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/tabungan/koreksi/{$origId}';
                const fields = {
                    _token: token,
                    no_rek: '{$noRek}',
                    jenis_transaksi_id: '1',
                    nominal: '80000',
                    alasan: 'Coba koreksi ulang transaksi yang sudah dikoreksi'
                };
                Object.entries(fields).forEach(([k, v]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = k;
                    input.value = v;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            ");

            $browser->waitForText('Transaksi ini sudah pernah dikoreksi.', 15)
                ->assertSee('Transaksi ini sudah pernah dikoreksi.');
        });

        $this->assertSame(3, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(1, DB::table('tblAUDIT_LOG')->where('aksi', 'KOREKSI_TRANSAKSI')->count());
        $this->assertSame(1075000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }

    /**
     * TC-05-02 | Negative | Mengoreksi transaksi yang membuat sisa saldo rekening < Rp 10.000 ditolak
     */
    public function test_tc_05_02_koreksi_menyebabkan_saldo_kurang_dari_minimum_ditolak(): void
    {
        // Saldo awal sebelum transaksi: Rp 20.000 -> setoran Rp 30.000 -> saldo akhir Rp 50.000
        $this->createTransaction($this->rekeningAktif, 'setoran', 30000, 50000);
        DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->update(['saldo' => 50000]);

        $this->browse(function (Browser $browser) {
            $this->disableAutoReload($browser);
            $this->loginAsTeller($browser)
                ->visit('/tabungan/koreksi?no_rek=' . urlencode($this->rekeningAktif))
                ->waitForText('Daftar Transaksi Rekening ' . $this->rekeningAktif, 15);

            $this->disableAutoReload($browser);

            $browser->pause(250)
                ->waitFor('.btn-open-koreksi', 15)
                ->click('.btn-open-koreksi')
                ->waitFor('#koreksiModal.open', 15)
                ->waitFor('#koreksiJenis', 10)
                ->select('#koreksiJenis', '2')
                ->waitFor('#koreksiNominalDisplay', 10)
                ->clear('#koreksiNominalDisplay')
                ->type('#koreksiNominalDisplay', '15000')
                ->waitFor('#koreksiAlasan', 10)
                ->type('#koreksiAlasan', 'Koreksi menjadi penarikan yang melanggar saldo minimum')
                ->press('Simpan Koreksi')
                ->waitForText('Saldo setelah koreksi harus menyisakan minimal Rp 10.000.', 15)
                ->assertSee('Saldo setelah koreksi harus menyisakan minimal Rp 10.000.');
        });

        $this->assertSame(1, DB::table('tblTRANSAKSI')->count());
        $this->assertSame(0, DB::table('tblAUDIT_LOG')->where('aksi', 'KOREKSI_TRANSAKSI')->count());
        $this->assertSame(50000.0, (float) DB::table('tblREKENING')->where('no_rek', $this->rekeningAktif)->value('saldo'));
    }
}
