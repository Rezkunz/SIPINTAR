/*
 * SIPINTAR – Service Worker
 *
 * Prinsip keamanan aplikasi keuangan:
 *  - Halaman & data yang butuh login TIDAK PERNAH disimpan di cache. Setiap pindah halaman selalu
 *    mengambil data terbaru dari server; bila offline, tampil halaman offline.
 *  - Yang disimpan hanya file statis (CSS, JS, gambar, ikon, font) supaya aplikasi cepat dibuka.
 *  - POST/PUT/DELETE, /logout, /chatbot, unduhan & cetak tidak disentuh sama sekali.
 *
 * __VERSI_CACHE__ diganti otomatis oleh PwaController setiap kali file PWA berubah.
 */
const VERSI = '__VERSI_CACHE__';
const CACHE_STATIS = `sipintar-statis-${VERSI}`;
const CACHE_FONT = 'sipintar-font-v1';
const HALAMAN_OFFLINE = '/offline.html';

const PRECACHE = [
    HALAMAN_OFFLINE,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
    '/images/sipintar_mark.png',
];

// Folder file statis milik aplikasi (bukan /storage: berisi berkas unggahan nasabah)
const FOLDER_STATIS = ['/css/', '/js/', '/images/', '/icons/', '/build/'];
const HOST_FONT = ['fonts.googleapis.com', 'fonts.gstatic.com', 'fonts.bunny.net'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_STATIS)
            .then((cache) => cache.addAll(PRECACHE.map((url) => new Request(url, { cache: 'reload' }))))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const nama = await caches.keys();
        await Promise.all(nama
            .filter((n) => n.startsWith('sipintar-') && n !== CACHE_STATIS && n !== CACHE_FONT)
            .map((n) => caches.delete(n)));
        if (self.registration.navigationPreload) {
            await self.registration.navigationPreload.enable();
        }
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // 1. Pindah halaman: selalu dari jaringan; offline -> halaman offline
    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const preload = await event.preloadResponse;
                if (preload) return preload;
                return await fetch(req);
            } catch (err) {
                const cache = await caches.open(CACHE_STATIS);
                return (await cache.match(HALAMAN_OFFLINE)) || Response.error();
            }
        })());
        return;
    }

    // 2. Font Google/Bunny: stale-while-revalidate
    if (HOST_FONT.includes(url.hostname)) {
        event.respondWith(staleWhileRevalidate(req, CACHE_FONT));
        return;
    }

    // 3. File statis aplikasi: cache-first
    if (url.origin === self.location.origin && FOLDER_STATIS.some((f) => url.pathname.startsWith(f))) {
        event.respondWith(cacheFirst(req));
    }
    // Selain itu (JSON, /storage, unduhan, dsb.) dibiarkan langsung ke jaringan tanpa cache.
});

async function cacheFirst(req) {
    const cache = await caches.open(CACHE_STATIS);
    const tersimpan = await cache.match(req);
    if (tersimpan) return tersimpan;

    try {
        const res = await fetch(req);
        if (res.ok && res.type === 'basic') {
            // Hapus versi lama file yang sama (?v=... berbeda) agar cache tidak terus membengkak
            const path = new URL(req.url).pathname;
            const lama = await cache.keys();
            await Promise.all(lama
                .filter((k) => new URL(k.url).pathname === path && k.url !== req.url)
                .map((k) => cache.delete(k)));
            await cache.put(req, res.clone());
        }
        return res;
    } catch (err) {
        // Offline & belum pernah tersimpan: coba versi lain dari file yang sama
        const cadangan = await cache.match(req, { ignoreSearch: true });
        return cadangan || Response.error();
    }
}

async function staleWhileRevalidate(req, namaCache) {
    const cache = await caches.open(namaCache);
    const tersimpan = await cache.match(req);
    const ambil = fetch(req)
        .then((res) => {
            if (res.ok || res.type === 'opaque') cache.put(req, res.clone());
            return res;
        })
        .catch(() => tersimpan || Response.error());
    return tersimpan || ambil;
}
