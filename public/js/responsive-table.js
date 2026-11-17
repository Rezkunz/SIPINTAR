/*
 * SIPINTAR – Tabel responsif (berlandaskan prinsip UI/UX mobile)
 *
 * Tabel .financial-table (≥ 4 kolom) menyesuaikan lebar layar:
 *   • Laptop/desktop          : tabel biasa.
 *   • Tablet (≤ 960px, ≥ 7 kolom) : KARTU; kolom sekunder di balik "Lihat rincian" (progressive disclosure).
 *   • HP (≤ 720px)            : DAFTAR RINGKAS — satu data = satu baris (nama, nominal, info singkat, status).
 *                               Sentuh baris → panel rincian dari bawah berisi semua kolom + tombol aksi.
 *                               (cognitive load, visual hierarchy, Fitts's law: area sentuh = seluruh baris)
 *
 * Tabel berisi isian (input angka/teks) tetap tampil sebagai kartu lengkap agar tidak ada isian tersembunyi.
 * Atribut opsional pada <table>:
 *   data-no-card       : tetap tabel yang digulir menyamping
 *   data-kartu-lengkap : kartu tanpa diringkas & tanpa mode daftar
 */
(function () {
    'use strict';

    const PENGECUALIAN = '.lap-income, .neraca-total-table, [data-no-card]';
    const layarTablet = window.matchMedia('(max-width: 960px)');
    const layarHp = window.matchMedia('(max-width: 720px)');
    const terdaftar = new Set();

    const KOLOM_PENTING = /total|status|sisa|tagihan|nominal|saldo|tunjangan bersih|hasil/i;
    const KOLOM_NAMA = /nama|peminjam|pegawai|nasabah|debitur|pemohon/i;
    const KOLOM_NILAI = /total|tagihan|nominal|sisa|saldo|jumlah|plafon|pinjaman \(rp\)|angsuran \/ bln/i;
    const KOLOM_STATUS = /status|kemajuan|hasil|tunjangan bersih|tenor ke|angsuran ke/i;
    const ISIAN = 'tbody input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]), tbody select, tbody textarea';

    // ── Label kolom ──────────────────────────────────────────────────────────
    function judulKolom(table) {
        const baris = table.tHead ? table.tHead.rows : [];
        if (!baris.length) return null;
        const judul = [];
        for (const th of baris[baris.length - 1].cells) {
            const teks = (th.innerText || th.textContent || '').replace(/\s+/g, ' ').trim();
            for (let i = 0; i < (th.colSpan || 1); i++) judul.push(teks);
        }
        return judul;
    }

    function labeliBaris(tr, judul) {
        let kolom = 0;
        for (const td of tr.cells) {
            const label = td.classList.contains('kartu-toggle-sel') ? null : judul[kolom];
            if (td.colSpan > 1 || !label) td.removeAttribute('data-label');
            else if (td.getAttribute('data-label') !== label) td.setAttribute('data-label', label);
            kolom += td.colSpan || 1;
        }
    }

    const selData = (tr) => [...tr.cells].filter((td) => !td.classList.contains('kartu-toggle-sel'));
    const barisKosong = (tr) => { const s = selData(tr); return s.length === 1 && s[0].colSpan > 1; };
    const berisiAksi = (td) => !!td.querySelector('button, a.btn-ui, .btn-ui, form');
    const berisiCentang = (td) => !!td.querySelector('input[type="checkbox"]');
    const bolehRingkas = (table) => !table.hasAttribute('data-kartu-lengkap') && !table.querySelector(ISIAN);

    // ── Mode kartu (tablet): kolom sekunder di balik "Lihat rincian" ─────────
    function selPenting(td, urutanBerlabel) {
        if (!td.hasAttribute('data-label')) return true;
        if (urutanBerlabel < 2) return true;
        if (td.querySelector('button, .btn-ui, a.btn, input, select, textarea')) return true;
        if (td.querySelector('.badge-pill.red, .badge-pill.amber')) return true;
        return KOLOM_PENTING.test(td.getAttribute('data-label'));
    }

    function ringkasKartu(table, tr) {
        if (!bolehRingkas(table) || barisKosong(tr)) return;
        const sel = selData(tr);
        let urutan = 0;
        let tersembunyi = 0;
        sel.forEach((td) => {
            const penting = selPenting(td, urutan);
            if (td.hasAttribute('data-label')) urutan++;
            td.classList.toggle('kartu-rinci', !penting);
            if (!penting) tersembunyi++;
        });

        let toggle = tr.querySelector(':scope > td.kartu-toggle-sel');
        if (tersembunyi < 2) {
            sel.forEach((td) => td.classList.remove('kartu-rinci'));
            if (toggle) toggle.remove();
            return;
        }
        if (!toggle) {
            toggle = document.createElement('td');
            toggle.className = 'kartu-toggle-sel';
            toggle.innerHTML = '<button type="button" class="kartu-toggle-btn" aria-expanded="false"></button>';
            toggle.firstChild.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const buka = !tr.classList.contains('kartu-terbuka');
                tr.classList.toggle('kartu-terbuka', buka);
                tulisToggle(toggle.firstChild, buka, Number(toggle.dataset.jumlah));
            });
            tr.appendChild(toggle);
        }
        toggle.dataset.jumlah = tersembunyi;
        tulisToggle(toggle.firstChild, tr.classList.contains('kartu-terbuka'), tersembunyi);
    }

    function tulisToggle(btn, buka, jumlah) {
        btn.setAttribute('aria-expanded', buka ? 'true' : 'false');
        btn.innerHTML = buka
            ? 'Sembunyikan rincian <span aria-hidden="true">▴</span>'
            : `Lihat rincian (${jumlah} data lagi) <span aria-hidden="true">▾</span>`;
    }

    // ── Mode daftar ringkas (HP): peran tiap sel dalam satu baris ────────────
    function tandaiPeran(tr) {
        if (barisKosong(tr)) return;
        const sel = selData(tr);
        sel.forEach((td) => td.removeAttribute('data-peran'));
        const berlabel = sel.filter((td) => td.hasAttribute('data-label') && !berisiAksi(td) && !berisiCentang(td));
        const label = (td) => td.getAttribute('data-label') || '';
        const ambil = (cari) => berlabel.find((td) => !td.hasAttribute('data-peran') && cari(td));

        sel.filter((td) => !td.hasAttribute('data-label') && berisiCentang(td))
            .slice(0, 1).forEach((td) => td.setAttribute('data-peran', 'cek'));

        const judul = ambil((td) => KOLOM_NAMA.test(label(td))) || berlabel[0];
        if (judul) judul.setAttribute('data-peran', 'judul');

        const nilai = ambil((td) => KOLOM_NILAI.test(label(td)) && /\d/.test(td.textContent));
        if (nilai) nilai.setAttribute('data-peran', 'nilai');

        const status = ambil((td) => td.querySelector('.badge-pill') && KOLOM_STATUS.test(label(td)))
            || ambil((td) => td.querySelector('.badge-pill'));
        if (status) {
            status.setAttribute('data-peran', 'status');
            // Pilih lencana paling penting (peringatan dulu) untuk ditampilkan di baris
            const lencana = status.querySelector('.badge-pill.red, .badge-pill.amber') || status.querySelector('.badge-pill');
            status.querySelectorAll('.lencana-status').forEach((b) => b.classList.remove('lencana-status'));
            lencana.classList.add('lencana-status');
        }

        const sub = ambil(() => true);
        if (sub) sub.setAttribute('data-peran', 'sub');

        tr.classList.toggle('punya-cek', !!tr.querySelector(':scope > td[data-peran="cek"]'));
    }

    function terapkanMode(table) {
        const kolom = Number(table.dataset.jumlahKolom || 0);
        const daftar = layarHp.matches && bolehRingkas(table);
        const kartu = !daftar && (kolom >= 7 ? layarTablet.matches : layarHp.matches);
        table.classList.toggle('tabel-daftar-aktif', daftar);
        table.classList.toggle('tabel-kartu-aktif', kartu);
    }

    function siapkan(table) {
        if (table.matches(PENGECUALIAN)) return;
        const judul = judulKolom(table);
        if (!judul || judul.length < 4) return;

        table.dataset.jumlahKolom = judul.length;
        const bagian = [...table.tBodies, table.tFoot].filter(Boolean);
        bagian.forEach((b) => [...b.rows].forEach((tr) => labeliBaris(tr, judul)));
        [...table.tBodies].forEach((b) => [...b.rows].forEach((tr) => {
            ringkasKartu(table, tr);
            tandaiPeran(tr);
        }));
        terapkanMode(table);
        terdaftar.add(table);

        if (!table.__pengamatKartu) {
            table.__pengamatKartu = new MutationObserver(() => siapkan(table));
            bagian.forEach((b) => table.__pengamatKartu.observe(b, { childList: true }));
            table.addEventListener('click', (e) => klikBaris(table, e));
            table.addEventListener('keydown', (e) => {
                if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('tr')) {
                    e.preventDefault();
                    klikBaris(table, e);
                }
            });
        }
        // Baris daftar dapat difokus & dibuka dengan keyboard
        [...table.tBodies].forEach((b) => [...b.rows].forEach((tr) => {
            if (table.classList.contains('tabel-daftar-aktif') && !barisKosong(tr)) {
                tr.tabIndex = 0;
                tr.setAttribute('role', 'button');
            } else {
                tr.removeAttribute('tabindex');
                tr.removeAttribute('role');
            }
        }));
    }

    function siapkanSemua(root) {
        (root || document).querySelectorAll('table.financial-table').forEach(siapkan);
    }

    function perbaruiSemua() {
        terdaftar.forEach((t) => (t.isConnected ? siapkan(t) : terdaftar.delete(t)));
        if (!layarHp.matches) tutupLembar();
    }

    // ── Panel rincian dari bawah (bottom sheet) ─────────────────────────────
    let lembar = null;
    let pemicu = null;

    function buatLembar() {
        if (lembar) return lembar;
        lembar = document.createElement('div');
        lembar.id = 'lembar-rincian';
        lembar.className = 'lembar';
        lembar.hidden = true;
        lembar.innerHTML = `
            <div class="lembar-latar" data-tutup></div>
            <section class="lembar-panel" role="dialog" aria-modal="true" aria-labelledby="lembar-judul">
                <div class="lembar-pegangan" aria-hidden="true"></div>
                <header class="lembar-kepala">
                    <div>
                        <h3 id="lembar-judul" class="lembar-judul"></h3>
                        <div class="lembar-subjudul"></div>
                    </div>
                    <button type="button" class="lembar-tutup" data-tutup aria-label="Tutup rincian">&times;</button>
                </header>
                <dl class="lembar-isi"></dl>
                <div class="lembar-aksi"></div>
            </section>`;
        document.body.appendChild(lembar);
        lembar.addEventListener('click', (e) => {
            if (e.target.closest('[data-tutup]')) { tutupLembar(); return; }
            // Tombol aksi (bayar, lihat jadwal, nilai, cairkan…) dijalankan lalu panel ditutup
            if (e.target.closest('.lembar-aksi button, .lembar-aksi a')) setTimeout(tutupLembar, 0);
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !lembar.hidden) tutupLembar(); });
        window.addEventListener('popstate', () => { if (!lembar.hidden) tutupLembar(true); });
        return lembar;
    }

    function salinTanpaId(el) {
        const salinan = el.cloneNode(true);
        salinan.removeAttribute('id');
        salinan.querySelectorAll('[id]').forEach((n) => n.removeAttribute('id'));
        return salinan;
    }

    function teksRingkas(td) {
        const pertama = td && (td.firstElementChild || td);
        return pertama ? (pertama.innerText || pertama.textContent || '').replace(/\s+/g, ' ').trim() : '';
    }

    function bukaLembar(tr) {
        const el = buatLembar();
        const sel = selData(tr);
        const judul = sel.find((td) => td.dataset.peran === 'judul');
        const sub = sel.find((td) => td.dataset.peran === 'sub');
        el.querySelector('.lembar-judul').textContent = teksRingkas(judul) || 'Rincian';
        el.querySelector('.lembar-subjudul').textContent = sub ? `${sub.dataset.label}: ${teksRingkas(sub)}` : '';

        const isi = el.querySelector('.lembar-isi');
        const aksi = el.querySelector('.lembar-aksi');
        isi.innerHTML = '';
        aksi.innerHTML = '';
        sel.forEach((td) => {
            if (td.dataset.peran === 'cek') return;
            if (berisiAksi(td)) {
                [...td.childNodes].forEach((n) => aksi.appendChild(n.nodeType === 1 ? salinTanpaId(n) : n.cloneNode(true)));
                return;
            }
            if (!td.hasAttribute('data-label')) return;
            const baris = document.createElement('div');
            baris.className = 'lembar-baris';
            const dt = document.createElement('dt');
            dt.textContent = td.dataset.label;
            const dd = document.createElement('dd');
            [...td.childNodes].forEach((n) => dd.appendChild(n.nodeType === 1 ? salinTanpaId(n) : n.cloneNode(true)));
            baris.append(dt, dd);
            isi.appendChild(baris);
        });
        aksi.hidden = !aksi.childNodes.length;

        pemicu = tr;
        el.hidden = false;
        document.documentElement.classList.add('lembar-terbuka');
        requestAnimationFrame(() => el.classList.add('tampil'));
        history.pushState({ lembarRincian: true }, '');
        el.querySelector('.lembar-tutup').focus({ preventScroll: true });
    }

    function tutupLembar(dariRiwayat) {
        if (!lembar || lembar.hidden) return;
        lembar.classList.remove('tampil');
        document.documentElement.classList.remove('lembar-terbuka');
        setTimeout(() => { lembar.hidden = true; }, 200);
        if (!dariRiwayat && history.state && history.state.lembarRincian) history.back();
        if (pemicu && pemicu.isConnected) pemicu.focus({ preventScroll: true });
        pemicu = null;
    }

    function klikBaris(table, e) {
        if (!table.classList.contains('tabel-daftar-aktif')) return;
        if (e.target.closest('input, label, button, a, select, textarea')) return; // centang & tautan tetap berfungsi
        const tr = e.target.closest('tbody > tr');
        if (!tr || tr.parentElement.parentElement !== table || barisKosong(tr)) return;
        bukaLembar(tr);
    }

    // Rotasi layar / ubah ukuran jendela
    [layarTablet, layarHp].forEach((mq) => {
        if (mq.addEventListener) mq.addEventListener('change', perbaruiSemua);
        else mq.addListener(perbaruiSemua);
    });

    document.addEventListener('DOMContentLoaded', () => {
        siapkanSemua();
        new MutationObserver((daftar) => {
            for (const m of daftar) {
                m.addedNodes.forEach((n) => {
                    if (n.nodeType !== 1 || (lembar && lembar.contains(n))) return;
                    if (n.matches('table.financial-table')) siapkan(n);
                    else if (n.querySelector) siapkanSemua(n);
                });
            }
        }).observe(document.body, { childList: true, subtree: true });
    });

    // ── Tombol "kembali ke atas" (tablet & HP) ──────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'btn-ke-atas';
        btn.setAttribute('aria-label', 'Kembali ke atas');
        btn.title = 'Kembali ke atas';
        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>';
        btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        document.body.appendChild(btn);

        let jadwal = null;
        const periksa = () => {
            jadwal = null;
            btn.classList.toggle('tampil', layarTablet.matches && window.scrollY > window.innerHeight);
        };
        window.addEventListener('scroll', () => { if (!jadwal) jadwal = requestAnimationFrame(periksa); }, { passive: true });
        periksa();
    });

    window.SipintarTabelKartu = { siapkan, siapkanSemua, tutupLembar };
})();
