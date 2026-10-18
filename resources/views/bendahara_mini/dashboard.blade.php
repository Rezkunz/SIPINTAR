@extends('layouts.app')

@section('title', 'Buku Kas & Bank Mini')
@section('breadcrumb', 'Buku Kas & Bank Mini')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <div class="welcome-badge-tag">Pengelolaan Kas Fisik & Rekonsiliasi Bank</div>
        <h1>Selamat Bertugas, {{ auth()->user()->name }}</h1>
        <p>Buku kas umum bank mini sekolah: pencatatan kas fisik brankas, rekonsiliasi bank penampung mitra, dan kartu piutang.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('kas.harian') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
            <span>Buku Kas Harian</span>
        </a>
        <a href="{{ route('kas.bank') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
            <span>Rekening Bank Mitra</span>
        </a>
    </div>
</div>

{{-- ── 3 FINANCIAL METRICS ── --}}
<div class="metric-grid">
    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Posisi Saldo Kas Fisik (Brankas)</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($saldoKasFisik, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Tabungan DPK Siswa</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalDanaTabungan, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Saldo Bank Mitra Penampung</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($saldoBankMitra, 0, ',', '.') }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── TABEL CATATAN BUKU KAS HARIAN ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Catatan Aliran Kas Terbaru</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Pencatatan kas fisik dan mutasi buku kas harian</div>
            </div>
            <a href="{{ route('kas.harian') }}" class="btn-ui btn-ui-outline btn-ui-sm">Buka Buku Kas</a>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pos Kas</th>
                        <th>Uraian Transaksi</th>
                        <th>Aliran</th>
                        <th style="text-align: right;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mutasiList as $m)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($m->tgl_mutasi)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge-pill blue">{{ $m->nama_kas }}</span>
                            </td>
                            <td>{{ $m->uraian ?? '-' }}</td>
                            <td>
                                <span class="badge-pill {{ $m->arah === 'masuk' ? 'green' : 'red' }}">
                                    {{ ucfirst($m->arah) }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: {{ $m->arah === 'masuk' ? 'var(--color-success)' : 'var(--color-danger)' }};">
                                {{ $m->arah === 'masuk' ? '+ Rp ' . number_format($m->nominal, 0, ',', '.') : '- Rp ' . number_format($m->nominal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--text-faint); display: block;"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                                    Belum ada catatan mutasi kas fisik pada periode ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── SHORTCUT MENU BENDAHARA MINI ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Menu Kas & Bank</h2>
        </div>
        <div class="card-body-content">
            <div class="shortcut-grid">
                <a href="{{ route('kas.harian') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                    <span>Kas Harian</span>
                </a>
                <a href="{{ route('kas.bank') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                    <span>Kas & Bank</span>
                </a>
                <a href="{{ route('bbm.kartu') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><line x1="15" y1="8" x2="17" y2="8"/><line x1="15" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="17" y2="16"/></svg>
                    <span>Piutang</span>
                </a>
                <a href="{{ route('bbm.koreksi') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span>Koreksi</span>
                </a>
            </div>
        </div>
    </div>

</div>

@endsection
