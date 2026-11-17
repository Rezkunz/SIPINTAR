<?php

namespace Tests\Modul_Pinjaman\iterasi2;

use Laravel\Dusk\Browser;

/**
 * Iterasi 2 | TC-I2-10 (Positive) - Chatbot FAQ sesuai peran pengguna
 * Aktor    : Bendahara Bank Mini - bendahara@sipintar.test, Nasabah (Siswa) - nasabah.siswa@sipintar.test
 * Feedback : Pengguna bingung alur & menu; diganti dengan chatbot FAQ yang mencakup banyak kemungkinan pertanyaan.
 * Expected : Chip pembuka & jawaban menyesuaikan peran; jawaban punya tombol pintasan ke menu terkait;
 *            pertanyaan bahasa sehari-hari tetap dikenali; pertanyaan di luar FAQ mendapat saran, bukan error;
 *            teks pengguna tidak dieksekusi sebagai HTML.
 */
class TC_I2_10Test extends Iterasi2DuskTestCase
{
    /** Kirim pertanyaan lewat kotak chat dan kembalikan teks balasan bot terakhir. */
    private function tanya(Browser $browser, string $pertanyaan): string
    {
        $jumlahAwal = count($this->teksSemua($browser, '.chatbot-msg.bot'));
        $this->isi($browser, '#chatbot-input', $pertanyaan);
        $browser->click('.chatbot-send-btn')
            ->waitUntil("document.querySelectorAll('.chatbot-msg.bot').length > {$jumlahAwal}", 10);

        $balasan = $this->teksSemua($browser, '.chatbot-msg.bot .chatbot-bubble');

        return end($balasan);
    }

    public function test_chatbot_faq_sesuai_peran(): void
    {
        $this->browse(function (Browser $browser) {
            // ── Bendahara Bank Mini ──
            $this->loginSebagai($browser, self::BBM)
                ->click('#chatbot-trigger')
                ->waitFor('#chatbot-window.is-open')
                ->assertSeeIn('#chatbot-quick-chips', 'Cara menilai kelayakan pinjaman?')
                ->assertDontSeeIn('#chatbot-quick-chips', 'Berapa saldo tabungan saya?');

            // Bahasa sehari-hari tetap dikenali + tombol pintasan ke menu
            $jawab = $this->tanya($browser, 'gmn caranya nyairin pinjaman yg udah layak?');
            $this->assertStringContainsString('Pencairan Pinjaman', $jawab);
            $browser->assertPresent('.chatbot-msg.bot:last-child a.chatbot-menu-link[href$="/bbm/pinjaman/cair"]');

            $this->assertStringContainsString('paket potongan tunjangan', $this->tanya($browser, 'kenapa tombol bayar hilang?'));

            // Daftar topik sesuai peran
            $this->tanya($browser, 'apa saja yang bisa ditanyakan?');
            $topik = $this->teksSemua($browser, '.chatbot-msg.bot:last-child .chatbot-chip');
            $this->assertGreaterThanOrEqual(20, count($topik));
            $this->assertContains('Cara menilai kelayakan pinjaman?', $topik);
            $browser->screenshot('TC-I2-10-bbm');

            // Pertanyaan di luar FAQ -> saran, bukan error
            $this->assertStringContainsString('belum menemukan jawaban', $this->tanya($browser, 'cuaca hari ini bagaimana'));

            // Teks pengguna tidak dirender sebagai HTML
            $this->tanya($browser, '<b id="xss-uji">tebal</b>');
            $browser->assertMissing('#xss-uji');
            $pesanPengguna = $this->teksSemua($browser, '.chatbot-msg.user .chatbot-bubble');
            $this->assertSame('<b id="xss-uji">tebal</b>', end($pesanPengguna));

            // ── Nasabah siswa: jawaban pinjaman berbeda ──
            $this->loginSebagai($browser, self::SISWA)
                ->click('#chatbot-trigger')
                ->waitFor('#chatbot-window.is-open')
                ->assertSeeIn('#chatbot-quick-chips', 'Berapa saldo tabungan saya?');

            $this->assertStringContainsString('khusus guru dan karyawan', $this->tanya($browser, 'aku mau pinjam uang bisa gak?'));
            $this->assertStringContainsString('0,1% per bulan', $this->tanya($browser, 'dapet bunga gak kalau nabung'));
            $browser->screenshot('TC-I2-10');
        });
    }
}
