@extends('layouts.app')

@section('title', 'Ruang Praktik Siswa')
@section('breadcrumb', 'Praktik Siswa')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <div class="welcome-badge-tag">Simulasi Kasir & Perbankan Siswa</div>
        <h1>Selamat Berlatih, {{ auth()->user()->name }}</h1>
        <p>Laboratorium perbankan mini sekolah: lakukan simulasi pembukaan rekening, entri draft setoran/tarikan, dan pelajari alur verifikasi loket.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('tabungan.draft') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            <span>Input Draft Transaksi</span>
        </a>
        <a href="{{ route('tabungan.buka-rekening') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
            <span>Simulasi Buka Rekening</span>
        </a>
    </div>
</div>

{{-- ── 3 FINANCIAL METRICS ── --}}
<div class="metric-grid">
    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Draft Menunggu Verifikasi</span>
        </div>
        <div class="metric-value">{{ $draftPending }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Draft Sukses Disetujui</span>
        </div>
        <div class="metric-value">{{ $draftDisetujui }}</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Latihan Kasir</span>
        </div>
        <div class="metric-value">{{ $totalDraft }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── TABEL RIWAYAT DRAFT SISWA ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Riwayat Draft Transaksi yang Anda Input</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Status verifikasi draft transaksi oleh teller loket</div>
            </div>
            <a href="{{ route('tabungan.draft') }}" class="btn-ui btn-ui-primary btn-ui-sm">Entri Baru</a>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Waktu Entri</th>
                        <th>Nasabah & Rekening</th>
                        <th>Jenis</th>
                        <th style="text-align: right;">Nominal</th>
                        <th style="text-align: center;">Status Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drafts as $d)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($d->tgl_input)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $d->nama_nasabah }}</div>
                                <div style="font-size: 11px; color: var(--text-muted); font-family: ui-monospace, SFMono-Regular, monospace;">{{ $d->no_rek }}</div>
                            </td>
                            <td>
                                <span class="badge-pill {{ str_contains(strtolower($d->nama_jenis_transaksi), 'setor') ? 'green' : 'amber' }}">
                                    {{ $d->nama_jenis_transaksi }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-heading);">
                                Rp {{ number_format($d->nominal, 0, ',', '.') }}
                            </td>
                            <td style="text-align: center;">
                                @if ($d->status_verifikasi === 'disetujui')
                                    <span class="badge-pill green">Disetujui</span>
                                @elseif ($d->status_verifikasi === 'ditolak')
                                    <span class="badge-pill red">Ditolak</span>
                                @else
                                    <span class="badge-pill amber">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--text-faint); display: block;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    Belum ada draft simulasi transaksi yang Anda input hari ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── SHORTCUT MENU SIMULASI ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Menu Praktik Kasir</h2>
        </div>
        <div class="card-body-content">
            <div class="shortcut-grid">
                <a href="{{ route('tabungan.draft') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>Input Draft</span>
                </a>
                <a href="{{ route('tabungan.buka-rekening') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                    <span>Buka Rekening</span>
                </a>
                <a href="{{ route('tabungan.setoran') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Form Setor</span>
                </a>
                <a href="{{ route('tabungan.penarikan') }}" class="shortcut-tile">
                    <svg class="tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Form Tarik</span>
                </a>
            </div>
        </div>
    </div>

</div>

@endsection
