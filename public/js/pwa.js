/*
 * SIPINTAR – PWA di sisi browser
 *  1. Mendaftarkan service worker (/sw.js)
 *  2. Tombol "Pasang Aplikasi": setiap elemen [data-pwa-install] muncul bila aplikasi dapat dipasang
 *  3. Banner "Versi baru tersedia" saat ada pembaruan service worker
 */
(function () {
    'use strict';

    if (navigator.webdriver) return;

    const sudahTerpasang = () =>
        window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const iOS = /iphone|ipad|ipod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    let promptPasang = null;

    function tombolPasang() {
        return document.querySelectorAll('[data-pwa-install]');
    }

    function tampilkanTombol(tampil) {
        tombolPasang().forEach((el) => { el.hidden = !tampil; });
    }

    function pesan(teks, judul) {
        if (typeof window.showSuccessPopup === 'function') {
            window.showSuccessPopup(teks, judul || 'INFO', 'OK');
        } else {
            window.alert(teks.replace(/<[^>]+>/g, ''));
        }
    }

    // ── 2. Pemasangan aplikasi ──
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        promptPasang = e;
        tampilkanTombol(true);
    });

    window.addEventListener('appinstalled', () => {
        promptPasang = null;
        tampilkanTombol(false);
        pesan('SIPINTAR berhasil dipasang. Buka dari ikon SIPINTAR di layar utama perangkat Anda.', 'TERPASANG');
    });

    async function pasang(e) {
        e.preventDefault();
        if (promptPasang) {
            try {
                promptPasang.prompt();
                await promptPasang.userChoice;
            } catch (err) {
                pesan('Pemasangan tidak dapat dimulai. Coba lewat menu browser: "Instal aplikasi" / "Tambahkan ke layar utama".', 'GAGAL');
            } finally {
                promptPasang = null;
                tampilkanTombol(false);
            }
            return;
        }
        if (iOS) {
            pesan('Di iPhone/iPad: ketuk tombol <strong>Bagikan</strong> (kotak dengan panah ke atas) di Safari, lalu pilih <strong>Tambah ke Layar Utama</strong>.', 'PASANG DI iOS');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        tombolPasang().forEach((el) => el.addEventListener('click', pasang));
        // Safari iOS tidak punya beforeinstallprompt: tampilkan tombol berisi petunjuk manual
        tampilkanTombol(!sudahTerpasang() && iOS);
    });

    // ── 1 & 3. Service worker + pembaruan ──
    if (!('serviceWorker' in navigator)) return;

    function bannerPembaruan(worker) {
        if (document.getElementById('pwa-update-banner')) return;
        const el = document.createElement('div');
        el.id = 'pwa-update-banner';
        el.setAttribute('role', 'status');
        el.style.cssText = 'position:fixed;left:50%;bottom:20px;transform:translateX(-50%);z-index:99999;'
            + 'display:flex;gap:12px;align-items:center;max-width:calc(100% - 32px);padding:12px 16px;border-radius:12px;'
            + 'background:#0F172A;color:#FFFFFF;font:600 13px/1.4 system-ui,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.25);';
        el.innerHTML = '<span>Versi baru SIPINTAR tersedia.</span>'
            + '<button type="button" style="border:0;border-radius:8px;padding:7px 12px;background:#10B981;color:#FFFFFF;font-weight:700;cursor:pointer;">Muat Ulang</button>';
        el.querySelector('button').addEventListener('click', () => worker.postMessage('SKIP_WAITING'));
        document.body.appendChild(el);
    }

    const sudahAdaController = Boolean(navigator.serviceWorker.controller);
    let sedangMuatUlang = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!sudahAdaController || sedangMuatUlang) return;
        sedangMuatUlang = true;
        window.location.reload();
    });

    window.addEventListener('load', async () => {
        try {
            const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

            if (reg.waiting && navigator.serviceWorker.controller) {
                bannerPembaruan(reg.waiting);
            }
            reg.addEventListener('updatefound', () => {
                const baru = reg.installing;
                if (!baru) return;
                baru.addEventListener('statechange', () => {
                    // Hanya tampilkan banner bila sebelumnya sudah ada SW aktif (bukan pemasangan pertama)
                    if (baru.state === 'installed' && navigator.serviceWorker.controller) {
                        bannerPembaruan(baru);
                    }
                });
            });
        } catch (err) {
            // Aplikasi tetap berjalan normal tanpa PWA (mis. HTTP non-localhost atau browser lama)
            console.warn('[SIPINTAR] Service worker tidak dapat didaftarkan:', err);
        }
    });
})();
