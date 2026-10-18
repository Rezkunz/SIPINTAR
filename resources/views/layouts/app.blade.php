<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIPINTAR') — Sistem Perbankan Bank Mini Sekolah</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Direct Core Stylesheets with Cache Busting --}}
    <link rel="stylesheet" href="{{ asset('css/core/variables.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/core/layout.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/core/components.css') }}?v={{ time() }}">

    {{-- Role Specific Stylesheet Auto-Loader --}}
    @if (auth()->check() && file_exists(public_path('css/roles/' . auth()->user()->role . '.css')))
        <link rel="stylesheet" href="{{ asset('css/roles/' . auth()->user()->role . '.css') }}?v={{ time() }}">
    @endif

    @stack('styles')
    @yield('head')

    <script>
        try {
            if (localStorage.getItem('sipintar_sidebar_collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch(e) {}
    </script>
</head>
<body>

    <div class="mobile-backdrop" id="mobile-backdrop" onclick="toggleSidebarMenu()"></div>

    {{-- ── SIDEBAR ── --}}
    <aside class="app-sidebar" id="app-sidebar" aria-label="Navigasi">
        <div class="sidebar-brand-wrapper">
            <button type="button" class="sidebar-brand-btn" id="sidebar-brand-toggle" onclick="toggleSidebarCollapse()" title="Klik untuk perkecil / perbesar navigasi" aria-label="Toggle navigasi">
                <img src="{{ asset('images/sipintar_mark.svg') }}" alt="SIPINTAR" class="sidebar-logo-img">
                <div class="sidebar-brand-info">
                    <div class="sidebar-brand-title">SIP<span>INTAR</span></div>
                    <div class="sidebar-brand-caption">Bank Mini Sekolah</div>
                </div>
                <div class="sidebar-collapse-toggle">
                    <svg class="collapse-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </div>
            </button>
        </div>

        <nav class="sidebar-nav" aria-label="Menu Utama">
            @php $role = auth()->user()->role; @endphp

            {{-- ── MANAGER ── --}}
            @if ($role === 'manager')
                <div class="nav-section-title">Ringkasan</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>

                <div class="nav-section-title">Laporan Keuangan</div>
                <a href="{{ route('laporan.tabungan') }}" class="nav-link {{ request()->routeIs('laporan.tabungan') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>Laporan Tabungan</span>
                </a>
                <a href="{{ route('laporan.pinjaman') }}" class="nav-link {{ request()->routeIs('laporan.pinjaman') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>Laporan Pinjaman</span>
                </a>
                <a href="{{ route('laporan.tahunan') }}" class="nav-link {{ request()->routeIs('laporan.tahunan') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>Laporan Tahunan</span>
                </a>
                <a href="{{ route('laporan.neraca') }}" class="nav-link {{ request()->routeIs('laporan.neraca') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
                    <span>Neraca & Gabungan</span>
                </a>
                <a href="{{ route('laporan.ekspor') }}" class="nav-link {{ request()->routeIs('laporan.ekspor') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span>Ekspor Dokumen</span>
                </a>

                <div class="nav-section-title">Pengaturan</div>
                <a href="{{ route('periode.tutup') }}" class="nav-link {{ request()->routeIs('periode.tutup') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Tutup Periode</span>
                </a>
                <a href="{{ route('akun.kelola') }}" class="nav-link {{ request()->routeIs('akun.kelola') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Kelola Pengguna</span>
                </a>
            @endif

            {{-- ── TELLER ── --}}
            @if ($role === 'teller')
                <div class="nav-section-title">Loket</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                    <span>Dashboard Loket</span>
                </a>
                <a href="{{ route('tabungan.verifikasi') }}" class="nav-link {{ request()->routeIs('tabungan.verifikasi') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Verifikasi Draft</span>
                </a>
                <a href="{{ route('tabungan.setoran') }}" class="nav-link {{ request()->routeIs('tabungan.setoran') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Setoran</span>
                </a>
                <a href="{{ route('tabungan.penarikan') }}" class="nav-link {{ request()->routeIs('tabungan.penarikan') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Penarikan</span>
                </a>
                <a href="{{ route('tabungan.bukti') }}" class="nav-link {{ request()->routeIs('tabungan.bukti') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Cetak Bukti</span>
                </a>

                <div class="nav-section-title">Layanan Rekening</div>
                <a href="{{ route('tabungan.jasa') }}" class="nav-link {{ request()->routeIs('tabungan.jasa') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    <span>Hitung Jasa</span>
                </a>
                <a href="{{ route('tabungan.saldo') }}" class="nav-link {{ request()->routeIs('tabungan.saldo') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                    <span>Saldo & Mutasi</span>
                </a>
                <a href="{{ route('tabungan.koreksi') }}" class="nav-link {{ request()->routeIs('tabungan.koreksi') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span>Koreksi Transaksi</span>
                </a>
            @endif

            {{-- ── SISWA PRAKTIK ── --}}
            @if ($role === 'siswa_praktik')
                <div class="nav-section-title">Praktik Siswa</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('tabungan.draft') }}" class="nav-link {{ request()->routeIs('tabungan.draft') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>Input Draft</span>
                </a>
                <a href="{{ route('tabungan.buka-rekening') }}" class="nav-link {{ request()->routeIs('tabungan.buka-rekening') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                    <span>Buka Rekening</span>
                </a>
                <a href="{{ route('tabungan.setoran') }}" class="nav-link {{ request()->routeIs('tabungan.setoran') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Entri Setoran</span>
                </a>
                <a href="{{ route('tabungan.penarikan') }}" class="nav-link {{ request()->routeIs('tabungan.penarikan') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Entri Penarikan</span>
                </a>
            @endif

            {{-- ── BENDAHARA ── --}}
            @if ($role === 'bendahara')
                <div class="nav-section-title">Pinjaman</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('pinjaman.index') }}" class="nav-link {{ request()->routeIs('pinjaman.index') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span>Daftar Pinjaman</span>
                </a>
                <a href="{{ route('pinjaman.ajukan') }}" class="nav-link {{ request()->routeIs('pinjaman.ajukan') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    <span>Pengajuan Pinjaman</span>
                </a>
                <a href="{{ route('pinjaman.kelayakan') }}" class="nav-link {{ request()->routeIs('pinjaman.kelayakan') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
                    <span>Penilaian Kelayakan</span>
                </a>
                <a href="{{ route('pinjaman.cair') }}" class="nav-link {{ request()->routeIs('pinjaman.cair') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                    <span>Pencairan Pinjaman</span>
                </a>

                <div class="nav-section-title">Angsuran</div>
                <a href="{{ route('angsuran.catat') }}" class="nav-link {{ request()->routeIs('angsuran.catat') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <span>Catat Angsuran</span>
                </a>
                <a href="{{ route('angsuran.potongan') }}" class="nav-link {{ request()->routeIs('angsuran.potongan') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                    <span>Rencana Potongan</span>
                </a>
                <a href="{{ route('angsuran.pantau') }}" class="nav-link {{ request()->routeIs('angsuran.pantau') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Pantau Potongan</span>
                </a>
            @endif

            {{-- ── BENDAHARA BANK MINI ── --}}
            @if ($role === 'bendahara_mini')
                <div class="nav-section-title">Buku Kas</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                    <span>Dashboard Kas</span>
                </a>
                <a href="{{ route('kas.harian') }}" class="nav-link {{ request()->routeIs('kas.harian') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                    <span>Buku Kas Harian</span>
                </a>
                <a href="{{ route('kas.bank') }}" class="nav-link {{ request()->routeIs('kas.bank') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                    <span>Kas & Bank</span>
                </a>

                <div class="nav-section-title">Piutang</div>
                <a href="{{ route('bbm.kartu') }}" class="nav-link {{ request()->routeIs('bbm.kartu') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><line x1="15" y1="8" x2="17" y2="8"/><line x1="15" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="17" y2="16"/></svg>
                    <span>Kartu Piutang</span>
                </a>
                <a href="{{ route('bbm.koreksi') }}" class="nav-link {{ request()->routeIs('bbm.koreksi') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span>Koreksi Angsuran</span>
                </a>
            @endif

            {{-- ── NASABAH ── --}}
            @if ($role === 'nasabah')
                @php $jenisNasabah = auth()->user()->nasabah?->jenis_nasabah ?? 'non_pegawai'; @endphp
                <div class="nav-section-title">Rekening Mandiri</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <span>Informasi Rekening</span>
                </a>
                <a href="{{ route('nasabah.saldo') }}" class="nav-link {{ request()->routeIs('nasabah.saldo') ? 'active' : '' }}">
                    <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                    <span>Riwayat Mutasi</span>
                </a>
                {{-- Angsuran & Pinjaman: hanya untuk Pegawai --}}
                @if ($jenisNasabah === 'pegawai')
                    <div class="nav-section-title">Pinjaman</div>
                    <a href="{{ route('nasabah.angsuran') }}" class="nav-link {{ request()->routeIs('nasabah.angsuran') ? 'active' : '' }}">
                        <svg class="nav-svg-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Jadwal Angsuran</span>
                    </a>
                    <a href="{{ route('nasabah.pinjaman') }}" class="nav-link {{ request()->routeIs('nasabah.pinjaman') ? 'active' : '' }}">
                        <svg class="nav-svg-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>Info Pinjaman</span>
                    </a>
                @endif
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile-widget">
                <div class="user-avatar-initial">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div class="user-meta-name">{{ auth()->user()->name }}</div>
                    <div class="user-meta-role">{{ auth()->user()->roleName() }}</div>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── TOPBAR (Swiss Modernism Precision) ── --}}
    <header class="app-topbar">
        <div class="topbar-left">
            <button class="btn-toggle-menu" onclick="toggleSidebarMenu()" aria-label="Buka menu navigasi">
                Menu
            </button>
            <div class="topbar-breadcrumbs">
                <span>SIPINTAR</span>
                <span class="separator">/</span>
                <span class="current">@yield('breadcrumb', 'Dashboard')</span>
            </div>
        </div>

        <div class="topbar-right">
            {{-- ── PRETTIFIED DATE & LIVE CLOCK BADGE (WITH SLIDE-DOWN TICKER) ── --}}
            <div class="topbar-date-card" title="Waktu & Tanggal Operasional Sistem">
                <div class="date-icon-box">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="date-text-wrap">
                    <div class="topbar-clock-row">
                        <div id="topbar-live-clock" class="topbar-live-clock">
                            <span class="clock-unit" id="clock-h">{{ \Carbon\Carbon::now()->format('H') }}</span>
                            <span class="clock-sep">:</span>
                            <span class="clock-unit" id="clock-m">{{ \Carbon\Carbon::now()->format('i') }}</span>
                            <span class="clock-sep">:</span>
                            <span class="clock-unit" id="clock-s">{{ \Carbon\Carbon::now()->format('s') }}</span>
                        </div>
                        <span class="topbar-clock-tz">WIB</span>
                    </div>
                    <span class="date-full">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                </div>
            </div>

            {{-- ── GLOBAL NOTIFICATION CENTER DROPDOWN (ALL ROLES) ── --}}
            <div class="topbar-notif-container" id="topbar-notif-container">
                <button type="button" class="topbar-notif-btn" id="topbar-notif-btn" onclick="toggleAppNotification(event)" aria-label="Notifikasi Sistem" title="Pusat Pemberitahuan Sistem">
                    <svg class="notif-bell-svg animate-bounce-subtle" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="topbar-notif-badge" id="topbar-notif-badge">3</span>
                    <span class="topbar-notif-pulse" id="topbar-notif-pulse"></span>
                </button>

                <div class="topbar-notif-dropdown" id="topbar-notif-dropdown">
                    <div class="notif-dropdown-header">
                        <div class="notif-dropdown-title">
                            <div class="notif-header-dot"></div>
                            <span>Pusat Notifikasi</span>
                            <span class="notif-count-badge" id="notif-count-badge">3 Baru</span>
                        </div>
                        <button type="button" class="notif-mark-read-btn" onclick="markAllNotifsRead(event)">Tandai Dibaca</button>
                    </div>

                    <div class="notif-dropdown-body">
                        <div class="notif-card-item unread">
                            <div class="notif-card-icon emerald">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </div>
                            <div class="notif-card-content">
                                <div class="notif-card-head">
                                    <span class="notif-source">Sistem Transaksi</span>
                                    <span class="notif-time">Aktif</span>
                                </div>
                                <p class="notif-msg">Seluruh modul transaksi Bank Mini Sekolah (tabungan, kasir, pinjaman) berjalan normal dan sinkron.</p>
                            </div>
                        </div>

                        <div class="notif-card-item unread">
                            <div class="notif-card-icon blue">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <div class="notif-card-content">
                                <div class="notif-card-head">
                                    <span class="notif-source">Keamanan Akun</span>
                                    <span class="notif-time">Aman</span>
                                </div>
                                <p class="notif-msg">Sesi login Anda terenkripsi TLS 256-Bit. Terakhir login dari browser aktif.</p>
                            </div>
                        </div>

                        <div class="notif-card-item">
                            <div class="notif-card-icon amber">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                            <div class="notif-card-content">
                                <div class="notif-card-head">
                                    <span class="notif-source">Jadwal Operasional</span>
                                    <span class="notif-time">Hari Ini</span>
                                </div>
                                <p class="notif-msg">Operasional loket Bank Mini Sekolah hari Senin-Jumat pukul 08:00 s.d 15:00 WIB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="notif-dropdown-footer">
                        <span>SIPINTAR &bull; Layanan Perbankan Sekolah Terpadu</span>
                    </div>
                </div>
            </div>

            {{-- ── PROFILE DROPDOWN MENU ── --}}
            <div class="topbar-profile-container" id="topbar-profile-container">
                <button type="button" class="topbar-profile-btn" id="topbar-profile-btn" onclick="toggleProfileDropdown(event)" aria-expanded="false" aria-haspopup="true" title="Menu Pengguna">
                    <div class="topbar-avatar" id="topbar-avatar-badge">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        <span class="avatar-status-dot"></span>
                    </div>
                    <div class="topbar-user-info">
                        <span class="topbar-user-name" id="topbar-user-name-label">{{ auth()->user()->name }}</span>
                        <span class="topbar-user-role">{{ auth()->user()->roleName() }}</span>
                    </div>
                    <svg class="dropdown-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                    <div class="dropdown-header">
                        <div class="dropdown-avatar-lg" id="dropdown-avatar-badge">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="dropdown-user-meta">
                            <div class="dropdown-user-name" id="dropdown-user-name-label">{{ auth()->user()->name }}</div>
                            <div class="dropdown-user-badge">{{ auth()->user()->roleName() }}</div>
                            <div class="dropdown-user-contact" id="dropdown-user-phone-label">{{ auth()->user()->no_hp ?? 'SIPINTAR Akun Terverifikasi' }}</div>
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <button type="button" class="dropdown-item" onclick="openProfileBanner()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>Edit Profil Saya</span>
                    </button>

                    <div class="dropdown-divider"></div>

                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="dropdown-item item-logout">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>Sign Out / Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- ── FLOATING PROFILE EDIT MODAL ("Banner Melayang") ── --}}
    <div class="profile-banner-backdrop" id="profile-banner-backdrop" onclick="closeProfileBanner()"></div>
    <div class="profile-slide-banner" id="profile-slide-banner" role="dialog" aria-modal="true" aria-labelledby="profile-banner-title" onclick="if(event.target === this) closeProfileBanner()">
        <div class="profile-banner-container">
            <div class="profile-banner-header">
                <div class="profile-banner-title-wrap">
                    <div class="profile-banner-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <h3 class="profile-banner-title" id="profile-banner-title">Pengaturan Profil & Keamanan</h3>
                        <p class="profile-banner-sub">Perbarui informasi nama, kelas/unit, no. WhatsApp, email, dan kata sandi</p>
                    </div>
                </div>
                <button type="button" class="profile-banner-close" onclick="closeProfileBanner()" aria-label="Tutup Form Edit Profil" title="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Segmented Tabs Nav --}}
            <div class="profile-tabs-nav">
                <button type="button" class="profile-tab-btn active" id="tab-btn-info" onclick="switchProfileTab('info')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Informasi Profil</span>
                </button>
                <button type="button" class="profile-tab-btn" id="tab-btn-security" onclick="switchProfileTab('security')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Ganti Kata Sandi</span>
                </button>
            </div>

            <div id="profile-banner-alert" class="profile-banner-alert" style="display: none;"></div>

            <form id="profile-banner-form" onsubmit="submitProfileBanner(event)" autocomplete="off">
                @csrf
                <input type="hidden" name="active_tab" id="profile_active_tab" value="info">

                {{-- TAB 1: INFORMASI PROFIL (Default aktif, semua data profil sudah terisi otomatis) --}}
                <div class="profile-banner-grid" id="pane-profile-info">
                    {{-- 1. Nama Lengkap / Nasabah (Full Width) --}}
                    <div class="profile-field-group col-span-2">
                        <label for="prof_nama" class="profile-field-label">
                            <span>Nama Lengkap / Nasabah <span class="required-mark">*</span></span>
                        </label>
                        <div class="profile-input-wrap">
                            <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" id="prof_nama" name="nama" class="profile-input" value="{{ auth()->user()->name }}" required minlength="3" maxlength="100" placeholder="Nama lengkap Anda / nasabah">
                        </div>
                        <div class="profile-error-msg" id="err-nama"></div>
                    </div>

                    {{-- 2. Kelas / Unit Kerja --}}
                    <div class="profile-field-group">
                        <label for="prof_kelas" class="profile-field-label">
                            <span>Kelas / Unit Kerja</span>
                            @if(auth()->user()->nasabah_id)
                                <span class="badge-role-pill">Nasabah</span>
                            @else
                                <span class="badge-role-pill neutral">Staf</span>
                            @endif
                        </label>
                        <div class="profile-input-wrap">
                            <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                            <input type="text" id="prof_kelas" name="kelas_unit" class="profile-input" value="{{ auth()->user()->nasabah?->kelas_unit ?? '' }}" placeholder="Contoh: XII AKL 1 / Staf">
                        </div>
                        <div class="profile-error-msg" id="err-kelas_unit"></div>
                    </div>

                    {{-- 3. Nomor Handphone / WhatsApp --}}
                    <div class="profile-field-group">
                        <label for="prof_no_hp" class="profile-field-label">
                            <span>No. WhatsApp / HP <span class="required-mark">*</span></span>
                        </label>
                        <div class="profile-input-wrap">
                            <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <input type="tel" id="prof_no_hp" name="no_hp" class="profile-input font-mono" value="{{ auth()->user()->no_hp }}" required placeholder="081234567890">
                        </div>
                        <div class="profile-error-msg" id="err-no_hp"></div>
                    </div>

                    {{-- 4. Alamat Email (Full Width) --}}
                    <div class="profile-field-group col-span-2">
                        <label for="prof_email" class="profile-field-label">
                            <span>Alamat Email <span class="required-mark">*</span></span>
                        </label>
                        <div class="profile-input-wrap">
                            <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <input type="email" id="prof_email" name="email" class="profile-input" value="{{ auth()->user()->email }}" required maxlength="100" placeholder="nama@sekolah.sch.id">
                        </div>
                        <div class="profile-error-msg" id="err-email"></div>
                    </div>
                </div>

                {{-- TAB 2: KEAMANAN & KATA SANDI (Hanya aktif saat diklik tab Ganti Kata Sandi) --}}
                <div id="pane-profile-security" style="display: none;">
                    <div class="security-intro-box">
                        <div class="security-intro-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div class="security-intro-text">
                            <strong>Verifikasi Keamanan & Ganti Sandi</strong>
                            <p>Masukkan kata sandi lama Anda untuk verifikasi identitas, lalu buat kata sandi baru (minimal 8 karakter).</p>
                        </div>
                    </div>

                    <div class="profile-banner-grid">
                        {{-- Kata Sandi Saat Ini (Lama) untuk Verifikasi --}}
                        <div class="profile-field-group col-span-2">
                            <label for="prof_current_password" class="profile-field-label">
                                <span>Kata Sandi Saat Ini (Lama) <span class="required-mark">*</span></span>
                                <span class="badge-role-pill neutral">Verifikasi Keamanan</span>
                            </label>
                            <div class="profile-input-wrap">
                                <svg class="profile-input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input type="password" id="prof_current_password" name="current_password" class="profile-input has-toggle" placeholder="Ketik kata sandi lama Anda saat ini" autocomplete="current-password">
                                <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('prof_current_password', this)" title="Lihat kata sandi">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="profile-error-msg" id="err-current_password"></div>
                        </div>

                        {{-- Kata Sandi Baru --}}
                        <div class="profile-field-group">
                            <label for="prof_password" class="profile-field-label">
                                <span>Kata Sandi Baru <span class="required-mark">*</span></span>
                            </label>
                            <div class="profile-input-wrap">
                                <svg class="profile-input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input type="password" id="prof_password" name="password" class="profile-input has-toggle" minlength="8" placeholder="Min. 8 karakter baru" autocomplete="new-password">
                                <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('prof_password', this)" title="Lihat kata sandi">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="profile-error-msg" id="err-password"></div>
                        </div>

                        {{-- Konfirmasi Kata Sandi Baru --}}
                        <div class="profile-field-group">
                            <label for="prof_password_confirmation" class="profile-field-label">
                                <span>Ulangi Kata Sandi Baru <span class="required-mark">*</span></span>
                            </label>
                            <div class="profile-input-wrap">
                                <svg class="profile-input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input type="password" id="prof_password_confirmation" name="password_confirmation" class="profile-input has-toggle" minlength="8" placeholder="Ulangi kata sandi baru" autocomplete="new-password">
                                <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('prof_password_confirmation', this)" title="Lihat kata sandi">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="profile-error-msg" id="err-password_confirmation"></div>
                        </div>
                    </div>
                </div>

                <div class="profile-banner-footer">
                    <div class="profile-info-tip">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span id="profile-footer-tip-text">Sistem otomatis menyelaraskan data pengguna dan identitas nasabah terkait.</span>
                    </div>
                    <div class="profile-footer-buttons">
                        <button type="button" class="btn-ui btn-ui-outline" onclick="closeProfileBanner()">Batal</button>
                        <button type="submit" class="btn-ui btn-ui-primary" id="btn-submit-profile">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            <span id="btn-submit-profile-label">Simpan Data Profil</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── MAIN CONTENT ── --}}
    <main class="app-main">
        @yield('content')
    </main>

    {{-- ── 3D POPUP NOTIFICATION MODAL (Image 2) ── --}}
    @include('partials.status_popup')

    <script>
        // ── LIVE CLOCK DENGAN ANIMASI SLIDE-DOWN REALTIME ──
        function updateLiveClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');

            updateClockDigit('clock-h', h);
            updateClockDigit('clock-m', m);
            updateClockDigit('clock-s', s);
        }

        function updateClockDigit(id, val) {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.textContent !== val) {
                el.textContent = val;
                el.classList.remove('is-sliding');
                void el.offsetWidth; // Trigger DOM reflow to replay CSS keyframe
                el.classList.add('is-sliding');
            }
        }
        setInterval(updateLiveClock, 1000);

        // ── GLOBAL NOTIFICATION CENTER TOGGLE & ACTIONS ──
        function toggleAppNotification(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('topbar-notif-dropdown');
            const btn = document.getElementById('topbar-notif-btn');
            if (!dropdown) return;
            const isOpen = dropdown.classList.toggle('is-open');
            btn?.classList.toggle('is-active', isOpen);

            // Close profile dropdown if open
            document.getElementById('profile-dropdown-menu')?.classList.remove('is-open');
            document.getElementById('topbar-profile-btn')?.classList.remove('is-active');
        }

        function markAllNotifsRead(e) {
            e.stopPropagation();
            document.querySelectorAll('#topbar-notif-dropdown .notif-card-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            document.getElementById('topbar-notif-badge')?.classList.add('is-hidden');
            document.getElementById('topbar-notif-pulse')?.classList.add('is-hidden');
            const countBadge = document.getElementById('notif-count-badge');
            if (countBadge) {
                countBadge.textContent = 'Semua Dibaca';
                countBadge.style.background = '#F1F5F9';
                countBadge.style.color = '#64748B';
            }
        }

        document.addEventListener('click', (e) => {
            const container = document.getElementById('topbar-notif-container');
            if (container && !container.contains(e.target)) {
                document.getElementById('topbar-notif-dropdown')?.classList.remove('is-open');
                document.getElementById('topbar-notif-btn')?.classList.remove('is-active');
            }
        });

        // ── SIDEBAR MINIMIZE / COLLAPSE TOGGLE ──
        function toggleSidebarCollapse() {
            const isCurrentlyCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
            if (isCurrentlyCollapsed) {
                document.documentElement.classList.remove('sidebar-collapsed');
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sipintar_sidebar_collapsed', '0');
            } else {
                document.documentElement.classList.add('sidebar-collapsed');
                document.body.classList.add('sidebar-collapsed');
                localStorage.setItem('sipintar_sidebar_collapsed', '1');
            }
        }

        function toggleSidebarMenu() {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('mobile-backdrop');
            sidebar.classList.toggle('is-open');
            backdrop.classList.toggle('is-open');
        }

        // ── TOPBAR PROFILE DROPDOWN ──
        function toggleProfileDropdown(e) {
            e.stopPropagation();
            const menu = document.getElementById('profile-dropdown-menu');
            const btn = document.getElementById('topbar-profile-btn');
            const isOpen = menu.classList.toggle('is-open');
            btn.classList.toggle('is-active', isOpen);
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        // ── TAB SWITCHER FORM PROFIL ──
        function switchProfileTab(tab) {
            const infoPane = document.getElementById('pane-profile-info');
            const securityPane = document.getElementById('pane-profile-security');
            const btnInfo = document.getElementById('tab-btn-info');
            const btnSecurity = document.getElementById('tab-btn-security');
            const footerTip = document.getElementById('profile-footer-tip-text');
            const btnSubmitLabel = document.getElementById('btn-submit-profile-label');
            const activeTabInput = document.getElementById('profile_active_tab');

            if (activeTabInput) activeTabInput.value = tab;

            if (tab === 'info') {
                infoPane.style.display = 'grid';
                securityPane.style.display = 'none';
                btnInfo?.classList.add('active');
                btnSecurity?.classList.remove('active');
                if (footerTip) footerTip.textContent = 'Sistem otomatis menyelaraskan data pengguna dan identitas nasabah terkait.';
                if (btnSubmitLabel) btnSubmitLabel.textContent = 'Simpan Data Profil';
            } else {
                infoPane.style.display = 'none';
                securityPane.style.display = 'block';
                btnInfo?.classList.remove('active');
                btnSecurity?.classList.add('active');
                if (footerTip) footerTip.textContent = 'Gunakan minimal 8 karakter untuk keamanan kata sandi.';
                if (btnSubmitLabel) btnSubmitLabel.textContent = 'Perbarui Kata Sandi';
            }
        }

        // Toggle Lihat/Sembunyikan Kata Sandi
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword
                ? `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                : `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        }

        // ── SLIDE-DOWN PROFILE BANNER CONTROLLER ──
        function openProfileBanner() {
            // Tutup dropdown menu profil terlebih dahulu
            document.getElementById('profile-dropdown-menu')?.classList.remove('is-open');
            document.getElementById('topbar-profile-btn')?.classList.remove('is-active');

            // Selalu buka tab Informasi Profil terlebih dahulu
            switchProfileTab('info');

            // Buka banner drawer dan backdrop
            const banner = document.getElementById('profile-slide-banner');
            const backdrop = document.getElementById('profile-banner-backdrop');
            if (banner && backdrop) {
                banner.classList.add('is-open');
                backdrop.classList.add('is-open');
                document.body.classList.add('profile-banner-active');
                setTimeout(() => {
                    document.getElementById('prof_nama')?.focus();
                }, 200);
            }
        }

        function closeProfileBanner() {
            const banner = document.getElementById('profile-slide-banner');
            const backdrop = document.getElementById('profile-banner-backdrop');
            if (banner && backdrop) {
                banner.classList.remove('is-open');
                backdrop.classList.remove('is-open');
                document.body.classList.remove('profile-banner-active');
            }
            // Sembunyikan alert error
            const alertEl = document.getElementById('profile-banner-alert');
            if (alertEl) alertEl.style.display = 'none';
            // Bersihkan error messages
            document.querySelectorAll('.profile-error-msg').forEach(el => el.textContent = '');
        }

        // Tangani submit banner profil via AJAX
        async function submitProfileBanner(e) {
            e.preventDefault();

            // Clear previous errors
            document.querySelectorAll('.profile-error-msg').forEach(el => el.textContent = '');
            const alertEl = document.getElementById('profile-banner-alert');
            if (alertEl) {
                alertEl.style.display = 'none';
                alertEl.className = 'profile-banner-alert';
            }

            const btn = document.getElementById('btn-submit-profile');
            const btnLabel = document.getElementById('btn-submit-profile-label');
            const originalText = btnLabel ? btnLabel.textContent : 'Simpan Data';

            const activeTab = document.getElementById('profile_active_tab')?.value || 'info';

            // ── VALIDASI KHUSUS SAAT DI TAB GANTI KATA SANDI ──
            if (activeTab === 'security') {
                const curPwd = document.getElementById('prof_current_password')?.value || '';
                const newPwd = document.getElementById('prof_password')?.value || '';
                const confPwd = document.getElementById('prof_password_confirmation')?.value || '';

                let hasClientError = false;
                let firstErrorMsg = '';

                if (!curPwd.trim()) {
                    const errEl = document.getElementById('err-current_password');
                    if (errEl) errEl.textContent = 'Kata sandi saat ini (lama) wajib diisi untuk verifikasi identitas.';
                    if (!firstErrorMsg) firstErrorMsg = 'Kata sandi saat ini (lama) wajib diisi untuk verifikasi identitas.';
                    hasClientError = true;
                }

                if (!newPwd) {
                    const errEl = document.getElementById('err-password');
                    if (errEl) errEl.textContent = 'Kata sandi baru wajib diisi.';
                    if (!firstErrorMsg) firstErrorMsg = 'Kata sandi baru wajib diisi.';
                    hasClientError = true;
                } else if (newPwd.length < 8) {
                    const errEl = document.getElementById('err-password');
                    if (errEl) errEl.textContent = 'Kata sandi baru minimal 8 karakter demi keamanan.';
                    if (!firstErrorMsg) firstErrorMsg = 'Kata sandi baru minimal 8 karakter demi keamanan.';
                    hasClientError = true;
                }

                if (!confPwd) {
                    const errEl = document.getElementById('err-password_confirmation');
                    if (errEl) errEl.textContent = 'Konfirmasi kata sandi baru wajib diisi.';
                    if (!firstErrorMsg) firstErrorMsg = 'Konfirmasi kata sandi baru wajib diisi.';
                    hasClientError = true;
                } else if (newPwd && newPwd !== confPwd) {
                    const errEl = document.getElementById('err-password_confirmation');
                    if (errEl) errEl.textContent = 'Konfirmasi kata sandi baru tidak cocok.';
                    if (!firstErrorMsg) firstErrorMsg = 'Konfirmasi kata sandi baru tidak cocok.';
                    hasClientError = true;
                }

                if (hasClientError) {
                    showErrorPopup(firstErrorMsg, 'ERROR', 'RETRY');
                    return;
                }
            }

            if (btn) btn.disabled = true;
            if (btnLabel) btnLabel.textContent = 'Menyimpan...';

            const form = document.getElementById('profile-banner-form');
            const formData = new FormData(form);

            try {
                const response = await fetch("{{ route('profile.update') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const res = await response.json();

                if (response.ok && res.success) {
                    alertEl.className = 'profile-banner-alert success';
                    alertEl.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <span>${res.message}</span>`;
                    alertEl.style.display = 'flex';

                    // Update DOM elements secara dinamis tanpa jeda (berlaku untuk semua role)
                    if (res.user) {
                        const newName = res.user.nama;
                        const initial = newName.charAt(0).toUpperCase();

                        // 1. Topbar elements
                        const topName = document.getElementById('topbar-user-name-label');
                        if (topName) topName.textContent = newName;

                        const topAvatar = document.getElementById('topbar-avatar-badge');
                        if (topAvatar && topAvatar.firstChild) topAvatar.firstChild.textContent = initial;

                        // 2. Dropdown elements
                        const dropName = document.getElementById('dropdown-user-name-label');
                        if (dropName) dropName.textContent = newName;

                        const dropPhone = document.getElementById('dropdown-user-phone-label');
                        if (dropPhone) dropPhone.textContent = res.user.no_hp;

                        const dropAvatar = document.getElementById('dropdown-avatar-badge');
                        if (dropAvatar) dropAvatar.textContent = initial;

                        // 3. Sidebar elements
                        const sideName = document.querySelector('.user-meta-name');
                        if (sideName) sideName.textContent = newName;

                        const sideAvatar = document.querySelector('.user-avatar-initial');
                        if (sideAvatar) sideAvatar.textContent = initial;

                        // 4. Hero Welcome Greeting Banner (Semua Role: Nasabah, Teller, Bendahara, Siswa, Manager)
                        document.querySelectorAll('.dashboard-welcome-banner h1').forEach(heading => {
                            const raw = heading.textContent;
                            if (raw.includes(',')) {
                                const prefix = raw.split(',')[0];
                                heading.textContent = `${prefix}, ${newName}`;
                            } else {
                                heading.textContent = newName;
                            }
                        });

                        // 5. WhatsApp Contact di Banner Nasabah
                        const welcomeWa = document.querySelector('.welcome-content p strong');
                        if (welcomeWa && res.user.no_hp) {
                            welcomeWa.textContent = res.user.no_hp;
                        }

                        // 6. Kartu Rekening / Digital Passbook Debit Card (Nasabah)
                        const passbookHolder = document.querySelector('.passbook-holder-name');
                        if (passbookHolder) {
                            passbookHolder.textContent = newName;
                        }
                        const passbookSub = document.querySelector('.passbook-holder-sub');
                        if (passbookSub && res.user.kelas_unit) {
                            passbookSub.textContent = res.user.kelas_unit;
                        }
                    }

                    // Reset field password
                    const curPwd = document.getElementById('prof_current_password');
                    if (curPwd) curPwd.value = '';
                    const newPwd = document.getElementById('prof_password');
                    if (newPwd) newPwd.value = '';
                    const confPwd = document.getElementById('prof_password_confirmation');
                    if (confPwd) confPwd.value = '';

                    // Tutup modal profil dan tampilkan Popup 3D Sukses dengan auto-reload data saat user klik DONE
                    closeProfileBanner();
                    showSuccessPopup(res.message || 'Profil berhasil diperbarui dengan baik!', 'SUCCESS !', 'DONE', () => {
                        window.location.reload();
                    });

                } else {
                    // Tampilkan pesan error spesifik di bawah field masing-masing
                    if (res.errors) {
                        if (res.errors.current_password || res.errors.password || res.errors.password_confirmation) {
                            switchProfileTab('security');
                        } else {
                            switchProfileTab('info');
                        }
                        for (const [key, msgs] of Object.entries(res.errors)) {
                            const errBox = document.getElementById(`err-${key}`);
                            if (errBox) {
                                errBox.textContent = msgs[0];
                            }
                        }
                    }

                    // Tampilkan Popup 3D Error (Sesuai Gambar 2)
                    showErrorPopup(res.message || 'Periksa kembali data yang Anda masukkan.', 'ERROR', 'RETRY');
                }
            } catch (err) {
                showErrorPopup('Terjadi kendala jaringan saat menghubungi server.', 'ERROR', 'RETRY');
            } finally {
                btn.disabled = false;
                btnLabel.textContent = originalText;
            }
        }

        // Tutup dropdown atau banner dengan tombol Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileBanner();
                document.getElementById('profile-dropdown-menu')?.classList.remove('is-open');
                document.getElementById('topbar-profile-btn')?.classList.remove('is-active');
            }
        });

        // Tutup dropdown bila klik di luar
        document.addEventListener('click', function(e) {
            const container = document.getElementById('topbar-profile-container');
            if (container && !container.contains(e.target)) {
                document.getElementById('profile-dropdown-menu')?.classList.remove('is-open');
                const btn = document.getElementById('topbar-profile-btn');
                if (btn) {
                    btn.classList.remove('is-active');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });

        // Sinkronisasi kelas body & otomatisasikan tooltip native untuk nav-link saat collapsed
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('sipintar_sidebar_collapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
                document.documentElement.classList.add('sidebar-collapsed');
            }

            document.querySelectorAll('.nav-link').forEach(function(link) {
                const label = link.querySelector('span')?.textContent?.trim();
                if (label && !link.getAttribute('title')) {
                    link.setAttribute('title', label);
                }
            });
        });
    </script>
    @yield('scripts')

    {{-- ── SIPINTAR AI CHATBOT WIDGET ── --}}
    <div id="chatbot-container" class="chatbot-container">

        {{-- Floating Trigger Button --}}
        <button type="button" id="chatbot-trigger" class="chatbot-trigger-btn" onclick="toggleChatbot()" aria-label="Buka Asisten SIPINTAR AI" title="Asisten SIPINTAR AI">
            <span class="chatbot-trigger-icon" id="chatbot-icon-open">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    <circle cx="9" cy="10" r="1" fill="currentColor"/>
                    <circle cx="12" cy="10" r="1" fill="currentColor"/>
                    <circle cx="15" cy="10" r="1" fill="currentColor"/>
                </svg>
            </span>
            <span class="chatbot-trigger-icon chatbot-icon-hidden" id="chatbot-icon-close">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </span>
            <span class="chatbot-pulse-ring"></span>
        </button>

        {{-- Chat Window --}}
        <div class="chatbot-window" id="chatbot-window">
            {{-- Header --}}
            <div class="chatbot-header">
                <div class="chatbot-header-left">
                    <div class="chatbot-avatar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                        </svg>
                    </div>
                    <div>
                        <div class="chatbot-name">SIPINTAR AI</div>
                        <div class="chatbot-status"><span class="chatbot-status-dot"></span>Online</div>
                    </div>
                </div>
                <button type="button" onclick="toggleChatbot()" class="chatbot-close-btn" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Messages --}}
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="chatbot-msg bot">
                    <div class="chatbot-bubble">
                        Halo, <strong>{{ auth()->user()->name }}</strong>! 👋<br>
                        Saya asisten SIPINTAR. Saya bisa bantu Anda soal:
                        <div class="chatbot-quick-chips" id="chatbot-quick-chips">
                            <button type="button" class="chatbot-chip" onclick="sendQuick('Berapa saldo tabungan saya?')">💰 Cek Saldo</button>
                            <button type="button" class="chatbot-chip" onclick="sendQuick('Cara setor tabungan?')">🏦 Cara Setor</button>
                            <button type="button" class="chatbot-chip" onclick="sendQuick('Cara tarik tunai?')">💸 Cara Tarik</button>
                            @if(auth()->user()->nasabah?->jenis_nasabah === 'pegawai')
                            <button type="button" class="chatbot-chip" onclick="sendQuick('Cara mengajukan pinjaman?')">📋 Pinjaman</button>
                            <button type="button" class="chatbot-chip" onclick="sendQuick('Kapan jatuh tempo angsuran saya?')">📅 Angsuran</button>
                            @endif
                            <button type="button" class="chatbot-chip" onclick="sendQuick('Jam operasional Bank Mini?')">🕐 Jam Buka</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Typing Indicator (hidden by default) --}}
            <div class="chatbot-typing" id="chatbot-typing" style="display:none;">
                <div class="chatbot-bubble typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>

            {{-- Input Area --}}
            <div class="chatbot-input-area">
                <div class="chatbot-input-wrap">
                    <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Ketik pertanyaan Anda..." maxlength="300" onkeydown="if(event.key==='Enter')sendChatMessage()">
                    <button type="button" class="chatbot-send-btn" onclick="sendChatMessage()" aria-label="Kirim">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </div>
                <div class="chatbot-footer-note">Didukung oleh SIPINTAR AI • <a href="#" style="color:inherit;">Privasi</a></div>
            </div>
        </div>
    </div>

    <style>
    /* ── CHATBOT WIDGET STYLES ── */
    .chatbot-container {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 9999;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .chatbot-trigger-btn {
        position: relative;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0f7c52 0%, #16a34a 100%);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 4px 20px rgba(15,124,82,0.45), 0 2px 8px rgba(0,0,0,0.15);
        transition: transform 0.2s cubic-bezier(.34,1.56,.64,1), box-shadow 0.2s;
    }
    .chatbot-trigger-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 28px rgba(15,124,82,0.55);
    }
    .chatbot-trigger-icon { display:flex; align-items:center; justify-content:center; transition: opacity 0.2s, transform 0.3s; }
    .chatbot-icon-hidden { display: none; }
    .chatbot-pulse-ring {
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid rgba(15,124,82,0.4);
        animation: chatbotPulse 2s ease-out infinite;
        pointer-events: none;
    }
    @keyframes chatbotPulse {
        0% { transform: scale(1); opacity: 0.8; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    .chatbot-window {
        position: absolute;
        bottom: 70px;
        right: 0;
        width: 360px;
        max-height: 520px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 12px 48px rgba(0,0,0,0.18), 0 2px 12px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform-origin: bottom right;
        transform: scale(0.85) translateY(12px);
        opacity: 0;
        pointer-events: none;
        transition: transform 0.3s cubic-bezier(.34,1.56,.64,1), opacity 0.25s ease;
    }
    .chatbot-window.is-open {
        transform: scale(1) translateY(0);
        opacity: 1;
        pointer-events: all;
    }
    .chatbot-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        background: linear-gradient(135deg, #0f7c52 0%, #16a34a 100%);
        color: #fff;
    }
    .chatbot-header-left { display: flex; align-items: center; gap: 10px; }
    .chatbot-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center;
    }
    .chatbot-name { font-weight: 700; font-size: 13.5px; }
    .chatbot-status { font-size: 11px; opacity: 0.85; display: flex; align-items: center; gap: 5px; margin-top: 1px; }
    .chatbot-status-dot { width: 7px; height: 7px; border-radius: 50%; background: #86efac; animation: statusBlink 1.8s ease-in-out infinite; }
    @keyframes statusBlink { 0%,100%{opacity:1} 50%{opacity:0.4} }
    .chatbot-close-btn {
        background: rgba(255,255,255,0.15);
        border: none; cursor: pointer;
        color: #fff;
        padding: 6px; border-radius: 8px;
        display: flex; align-items: center;
        transition: background 0.15s;
    }
    .chatbot-close-btn:hover { background: rgba(255,255,255,0.25); }
    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 14px 14px 4px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        scroll-behavior: smooth;
        max-height: 300px;
    }
    .chatbot-messages::-webkit-scrollbar { width: 4px; }
    .chatbot-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .chatbot-msg { display: flex; }
    .chatbot-msg.bot { justify-content: flex-start; }
    .chatbot-msg.user { justify-content: flex-end; }
    .chatbot-bubble {
        max-width: 82%;
        padding: 10px 13px;
        border-radius: 14px;
        font-size: 13px;
        line-height: 1.55;
        word-break: break-word;
        animation: bubbleIn 0.25s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes bubbleIn { from{transform:scale(0.85) translateY(6px);opacity:0} to{transform:scale(1) translateY(0);opacity:1} }
    .chatbot-msg.bot .chatbot-bubble {
        background: #f0fdf4;
        color: #1a2e1e;
        border-bottom-left-radius: 4px;
        border: 1px solid #d1fae5;
    }
    .chatbot-msg.user .chatbot-bubble {
        background: linear-gradient(135deg, #0f7c52 0%, #16a34a 100%);
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .chatbot-quick-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }
    .chatbot-chip {
        background: #fff;
        border: 1.5px solid #d1fae5;
        color: #0f7c52;
        font-size: 11.5px;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
    }
    .chatbot-chip:hover {
        background: #0f7c52;
        color: #fff;
        border-color: #0f7c52;
        transform: translateY(-1px);
    }
    .chatbot-typing { padding: 0 14px 8px; }
    .typing-dots { display: flex !important; gap: 5px; align-items: center; background: #f0fdf4 !important; width: fit-content; }
    .typing-dots span {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #0f7c52;
        display: inline-block;
        animation: typingBounce 1.2s ease-in-out infinite;
    }
    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingBounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }
    .chatbot-input-area {
        padding: 12px 14px 10px;
        border-top: 1px solid #e5e7eb;
        background: #fafafa;
    }
    .chatbot-input-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1.5px solid #d1d5db;
        border-radius: 12px;
        padding: 6px 8px 6px 12px;
        transition: border-color 0.15s;
    }
    .chatbot-input-wrap:focus-within { border-color: #0f7c52; }
    .chatbot-input {
        flex: 1;
        border: none;
        outline: none;
        background: none;
        font-size: 13px;
        font-family: inherit;
        color: #1f2937;
    }
    .chatbot-send-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0f7c52 0%, #16a34a 100%);
        border: none; cursor: pointer;
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .chatbot-send-btn:hover { transform: scale(1.08); box-shadow: 0 2px 8px rgba(15,124,82,0.4); }
    .chatbot-footer-note {
        font-size: 10.5px;
        color: #9ca3af;
        text-align: center;
        margin-top: 7px;
    }
    @media (max-width: 480px) {
        .chatbot-container { bottom: 16px; right: 16px; }
        .chatbot-window { width: calc(100vw - 32px); right: -16px; }
    }
    </style>

    <script>
    // ── SIPINTAR AI CHATBOT ENGINE ──
    const CHATBOT_FAQ = [
        { keys: ['saldo', 'tabungan saya', 'berapa saldo', 'cek saldo'], answer: 'Untuk melihat saldo terkini, klik menu <strong>Informasi Rekening</strong> di sidebar, atau klik <strong>Riwayat Mutasi</strong> untuk melihat riwayat transaksi lengkap Anda.' },
        { keys: ['setor', 'setor tunai', 'cara setor', 'menabung', 'tabung'], answer: '🏦 <strong>Cara Setor Tunai:</strong><br>1. Datang ke loket kasir Bank Mini Sekolah<br>2. Sebutkan nomor rekening Anda kepada teller<br>3. Serahkan uang yang akan disetorkan<br>4. Terima slip setoran resmi ber-stempel' },
        { keys: ['tarik', 'tarik tunai', 'ambil uang', 'penarikan', 'cara tarik'], answer: '💸 <strong>Cara Tarik Tunai:</strong><br>1. Datang ke loket kasir pada jam operasional<br>2. Sebutkan nomor rekening & jumlah penarikan<br>3. Teller akan memverifikasi identitas Anda<br>4. Uang diserahkan beserta slip penarikan' },
        { keys: ['jam', 'operasional', 'buka', 'jam buka', 'jam operasional'], answer: '🕐 <strong>Jam Operasional Bank Mini:</strong><br>Senin – Jumat: 07.30 – 14.00 WIB<br>Sabtu: 07.30 – 11.00 WIB<br>Minggu & Hari Libur: <em>Tutup</em>' },
        { keys: ['pinjam', 'pinjaman', 'kredit', 'ajukan pinjaman'], answer: '📋 <strong>Pengajuan Pinjaman:</strong><br>1. Hubungi Bendahara Sekolah<br>2. Siapkan: KTP/KK, slip gaji 3 bulan terakhir, dan formulir pengajuan<br>3. Penilaian kelayakan dilakukan dalam 3 hari kerja<br>4. Dana cair setelah disetujui Manager' },
        { keys: ['angsuran', 'cicilan', 'jatuh tempo', 'bayar angsuran'], answer: '📅 <strong>Jadwal Angsuran:</strong><br>Lihat jadwal lengkap di menu <strong>Jadwal Angsuran</strong> di sidebar. Angsuran dipotong otomatis dari gaji setiap bulan tanggal 25.' },
        { keys: ['no rekening', 'nomor rekening', 'rekening saya'], answer: 'Nomor rekening Anda dapat dilihat di halaman <strong>Informasi Rekening</strong> (halaman dashboard). Nomor rekening berformat: 1001-YYYY-XXXXX' },
        { keys: ['lupa sandi', 'ganti sandi', 'kata sandi', 'password'], answer: '🔒 Untuk mengganti kata sandi, klik avatar/nama Anda di pojok kanan atas topbar, lalu pilih tab <strong>Ganti Kata Sandi</strong> di panel profil.' },
        { keys: ['hubungi', 'kontak', 'admin', 'bantuan lebih'], answer: '📞 Hubungi admin Bank Mini Sekolah:<br>• Loket Teller – Gedung Keuangan<br>• WhatsApp: <strong>0812-3456-7890</strong><br>• Email: <strong>bankmini@sipintar.sch.id</strong>' },
        { keys: ['halo', 'hai', 'hi', 'hello', 'selamat pagi', 'selamat siang', 'selamat sore'], answer: 'Halo! 😊 Ada yang bisa saya bantu? Ketik pertanyaan Anda atau pilih topik di bawah.' },
        { keys: ['terima kasih', 'makasih', 'thanks', 'ok', 'oke'], answer: 'Sama-sama! 😊 Semoga bermanfaat. Ada lagi yang ingin ditanyakan?' },
    ];

    let chatbotOpen = false;

    function toggleChatbot() {
        chatbotOpen = !chatbotOpen;
        const win = document.getElementById('chatbot-window');
        const iconOpen = document.getElementById('chatbot-icon-open');
        const iconClose = document.getElementById('chatbot-icon-close');
        if (chatbotOpen) {
            win.classList.add('is-open');
            iconOpen.style.display = 'none';
            iconClose.style.display = 'flex';
            document.getElementById('chatbot-input').focus();
        } else {
            win.classList.remove('is-open');
            iconOpen.style.display = 'flex';
            iconClose.style.display = 'none';
        }
    }

    function sendQuick(text) {
        document.getElementById('chatbot-input').value = text;
        sendChatMessage();
    }

    function appendMessage(text, role) {
        const container = document.getElementById('chatbot-messages');
        const msg = document.createElement('div');
        msg.className = `chatbot-msg ${role}`;
        const bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble';
        bubble.innerHTML = text;
        msg.appendChild(bubble);
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }

    function showTyping(show) {
        document.getElementById('chatbot-typing').style.display = show ? 'block' : 'none';
        if (show) document.getElementById('chatbot-messages').scrollTop = 99999;
    }

    function getBotAnswer(text) {
        const lower = text.toLowerCase();
        for (const faq of CHATBOT_FAQ) {
            if (faq.keys.some(k => lower.includes(k))) return faq.answer;
        }
        // Gemini AI fallback — kirim ke endpoint backend
        return null;
    }

    async function sendChatMessage() {
        const input = document.getElementById('chatbot-input');
        const text = input.value.trim();
        if (!text) return;
        input.value = '';

        // Hide quick chips after first message
        const chips = document.getElementById('chatbot-quick-chips');
        if (chips) chips.style.display = 'none';

        appendMessage(text, 'user');
        showTyping(true);

        // Simulate brief delay for UX
        await new Promise(r => setTimeout(r, 600 + Math.random() * 400));

        const faqAnswer = getBotAnswer(text);
        showTyping(false);

        if (faqAnswer) {
            appendMessage(faqAnswer, 'bot');
        } else {
            // Fallback: try Gemini API via backend
            try {
                const resp = await fetch('/chatbot/ask', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message: text })
                });
                if (resp.ok) {
                    const data = await resp.json();
                    appendMessage(data.reply || 'Maaf, saya tidak mengerti pertanyaan itu.', 'bot');
                } else {
                    throw new Error('API error');
                }
            } catch {
                appendMessage('Maaf, saya belum bisa menjawab itu. Silakan hubungi staf Bank Mini Sekolah secara langsung untuk bantuan lebih lanjut. 🙏', 'bot');
            }
        }
    }

    // Close chatbot when clicking outside
    document.addEventListener('click', function(e) {
        if (chatbotOpen && !document.getElementById('chatbot-container').contains(e.target)) {
            toggleChatbot();
        }
    });
    </script>
</body>
</html>
