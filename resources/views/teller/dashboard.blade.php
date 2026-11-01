@extends('layouts.app')

@section('title', 'Loket Teller')
@section('breadcrumb', 'Loket Teller')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Selamat Bertugas, {{ auth()->user()->name }}</h1>
        <p>Layanan operasional kasir teller: penerimaan setoran, pembayaran penarikan tabungan, dan otorisasi transaksi siswa.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('tabungan.setoran') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Setoran Baru</span>
        </a>
        <a href="{{ route('tabungan.penarikan') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Penarikan Tunai</span>
        </a>
    </div>
</div>

{{-- ── 4 FINANCIAL METRIC CARDS ── --}}
<div class="metric-grid">
    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><circle cx="12" cy="14" r="4"/><polyline points="12 12 12 14 14 14"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Draft Antrean Siswa</span>
        </div>
        <div class="metric-value">{{ $draftPendingCount }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><line x1="6" y1="12" x2="6.01" y2="12"/><line x1="18" y1="12" x2="18.01" y2="12"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Setoran Kas Masuk Hari Ini</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalSetoranHariIni, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Penarikan Kas Keluar Hari Ini</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalPenarikanHariIni, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-indigo">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Transaksi Hari Ini</span>
        </div>
        <div class="metric-value">{{ $transaksiHariIniCount }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── DRAFT TABLE ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Draft Transaksi Menunggu Validasi</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Entri setoran/tarikan dari siswa praktik perbankan</div>
            </div>
            <a href="{{ route('tabungan.verifikasi') }}" class="btn-ui btn-ui-primary btn-ui-sm">Verifikasi Loket</a>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Waktu Entri</th>
                        <th>Nomor Rekening / Nasabah</th>
                        <th>Tipe</th>
                        <th style="text-align: right;">Nominal</th>
                        <th>Operator Siswa</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drafts as $d)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($d->tgl_input)->format('H:i:s') }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $d->nama_nasabah }}</div>
                                <div style="font-size: 11px; color: var(--text-muted); font-family: ui-monospace, SFMono-Regular, monospace;">{{ $d->no_rek }}</div>
                            </td>
                            <td>
                                <span class="badge-pill {{ str_contains(strtolower($d->nama_jenis_transaksi), 'setoran') ? 'green' : 'amber' }}">
                                    {{ $d->nama_jenis_transaksi }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-heading);">
                                Rp {{ number_format($d->nominal, 0, ',', '.') }}
                            </td>
                            <td style="font-size: 12px;">{{ $d->nama_siswa ?? 'Siswa Praktik' }}</td>
                            <td style="text-align: center;">
                                <a href="{{ route('tabungan.verifikasi') }}" class="btn-ui btn-ui-outline btn-ui-sm">Periksa</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--text-faint); display: block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    Semua transaksi draft telah diverifikasi. Tidak ada antrean pending.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── MENU TRANSAKSI LOKET CEPAT ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Menu Kasir Cepat</h2>
        </div>
        <div class="card-body-content">
            <div class="shortcut-grid">
                <a href="{{ route('tabungan.setoran') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Setoran</span>
                </a>
                <a href="{{ route('tabungan.penarikan') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Penarikan</span>
                </a>
                <a href="{{ route('tabungan.saldo') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                    <span>Cek Saldo</span>
                </a>
                <a href="{{ route('tabungan.bukti') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Cetak Slip</span>
                </a>
                <a href="{{ route('tabungan.jasa') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    <span>Hitung Jasa</span>
                </a>
                <a href="{{ route('tabungan.koreksi') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span>Koreksi</span>
                </a>
            </div>
        </div>
    </div>

</div>

@endsection
