@extends('layouts.app')

@section('title', 'Dashboard Pembiayaan & Pinjaman')
@section('breadcrumb', 'Pembiayaan & Pinjaman')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <div class="welcome-badge-tag">Tata Kelola Pinjaman & Pembiayaan Pegawai</div>
        <h1>Selamat Bertugas, {{ auth()->user()->name }}</h1>
        <p>Kelola penilaian kelayakan pinjaman guru/staf, monitoring jadwal angsuran, dan rencana pemotongan gaji berkala.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('pinjaman.ajukan') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Pengajuan Pinjaman</span>
        </a>
        <a href="{{ route('angsuran.catat') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>Catat Angsuran</span>
        </a>
    </div>
</div>

{{-- ── 3 FINANCIAL METRICS ── --}}
<div class="metric-grid">
    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Pengajuan Baru Perlu Review</span>
        </div>
        <div class="metric-value">{{ $pengajuanPendingCount }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Portofolio Pinjaman Aktif (Cair)</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalPinjamanCair, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Pinjaman Terselesaikan (Lunas)</span>
        </div>
        <div class="metric-value">{{ $pinjamanLunasCount }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── TABEL DAFTAR PENGAJUAN PINJAMAN ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Daftar Pengajuan Pinjaman Terbaru</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Pengajuan kredit dan status verifikasi komite koperasi</div>
            </div>
            <a href="{{ route('pinjaman.index') }}" class="btn-ui btn-ui-outline btn-ui-sm">Semua Pinjaman</a>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Tgl Pengajuan</th>
                        <th>Peminjam / Unit Kerja</th>
                        <th style="text-align: right;">Nominal</th>
                        <th>Tenor</th>
                        <th>Metode Bayar</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pinjamanList as $p)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($p->tgl_pengajuan)->format('d/m/Y') }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $p->nama_nasabah }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $p->kelas_unit }}</div>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-heading);">
                                Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}
                            </td>
                            <td>{{ $p->tenor }} Bulan</td>
                            <td>
                                <span class="badge-pill blue">
                                    {{ $p->metode_bayar === 'potong_gaji' ? 'Potong Gaji' : 'Tunai' }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                @if ($p->status_pinjaman === 'cair')
                                    <span class="badge-pill green">Cair</span>
                                @elseif ($p->status_pinjaman === 'layak')
                                    <span class="badge-pill blue">Layak</span>
                                @elseif ($p->status_pinjaman === 'ditolak')
                                    <span class="badge-pill red">Ditolak</span>
                                @else
                                    <span class="badge-pill amber">Diajukan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--text-faint); display: block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    Belum ada data pengajuan pinjaman pada sistem.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── SHORTCUT MENU BENDAHARA ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Menu Pembiayaan</h2>
        </div>
        <div class="card-body-content">
            <div class="shortcut-grid">
                <a href="{{ route('pinjaman.ajukan') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    <span>Ajukan</span>
                </a>
                <a href="{{ route('pinjaman.kelayakan') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/></svg>
                    <span>Kelayakan</span>
                </a>
                <a href="{{ route('pinjaman.cair') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                    <span>Pencairan</span>
                </a>
                <a href="{{ route('angsuran.catat') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <span>Angsuran</span>
                </a>
                <a href="{{ route('angsuran.potongan') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/></svg>
                    <span>Potong Gaji</span>
                </a>
                <a href="{{ route('angsuran.pantau') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Pantau</span>
                </a>
            </div>
        </div>
    </div>

</div>

@endsection
