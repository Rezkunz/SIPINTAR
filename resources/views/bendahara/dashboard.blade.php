@extends('layouts.app')

@section('title', 'Dashboard Bendahara Sekolah')
@section('breadcrumb', 'Dashboard')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Selamat Bertugas, {{ auth()->user()->nama_user }}</h1>
        <p>Pantau rencana potongan tunjangan pegawai dari Bank Mini dan catat realisasi hasil pemotongan gaji secara akurat.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('bendahara.potongan.index') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Daftar Rencana Potongan</span>
        </a>
    </div>
</div>

{{-- ── 3 FINANCIAL METRICS ── --}}
<div class="metric-grid">
    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Paket Rencana Potongan</span>
        </div>
        <div class="metric-value">{{ $totalRencanaCount }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Akumulasi Total Potongan</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalNominalRencana, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Pegawai Dalam Pemotongan</span>
        </div>
        <div class="metric-value">{{ $totalPegawaiDipotong }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── TABEL DAFTAR RENCANA POTONGAN GAJI ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Daftar Paket Rencana Potongan Tunjangan</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Paket potongan berkala yang disusun oleh Bendahara Bank Mini</div>
            </div>
            <a href="{{ route('bendahara.potongan.index') }}" class="btn-ui btn-ui-outline btn-ui-sm">Semua Paket</a>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>ID Paket</th>
                        <th>Periode</th>
                        <th style="text-align: right;">Total Rencana</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rencanaList as $r)
                        @php
                            $namaBulan = \Carbon\Carbon::createFromDate($r->tahun, $r->bulan, 1)->translatedFormat('F Y');
                        @endphp
                        <tr>
                            <td>
                                <span style="font-weight:700; color:var(--text-heading); font-family:monospace;">
                                    #RP-{{ str_pad($r->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-heading);">{{ $namaBulan }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Bulan {{ $r->bulan }} / {{ $r->tahun }}</div>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-heading);">
                                Rp {{ number_format($r->total_potongan, 0, ',', '.') }}
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('bendahara.potongan.index', ['open' => $r->rencana_potongan_id]) }}"
                                   class="btn-ui btn-ui-sm btn-ui-primary" style="text-decoration:none;">
                                    Catat Hasil
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-placeholder" style="padding: 32px 16px; text-align: center;">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--text-faint); display: block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    Belum ada daftar rencana potongan gaji yang diajukan oleh Bank Mini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── INFORMASI PERAN & PANDUAN KERJA ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Petunjuk Operasional</h2>
        </div>
        <div class="card-body-content" style="font-size: 13px; line-height: 1.6; color: var(--text-muted);">
            <div style="background: var(--bg-surface-elevated, #f8fafc); border-radius: 8px; padding: 14px; margin-bottom: 14px; border: 1px solid var(--border-subtle, #e2e8f0);">
                <div style="font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">1. Pemantauan Rencana</div>
                <p style="margin:0;">Bendahara Bank Mini menyusun daftar rencana potongan angsuran pinjaman bagi pegawai setiap awal periode.</p>
            </div>
            <div style="background: var(--bg-surface-elevated, #f8fafc); border-radius: 8px; padding: 14px; margin-bottom: 14px; border: 1px solid var(--border-subtle, #e2e8f0);">
                <div style="font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">2. Pemotongan Nyata</div>
                <p style="margin:0;">Pemotongan tunjangan/gaji dilakukan di luar sistem oleh Bendahara Sekolah saat penyaluran honor/gaji.</p>
            </div>
            <div style="background: var(--bg-surface-elevated, #f8fafc); border-radius: 8px; padding: 14px; border: 1px solid var(--border-subtle, #e2e8f0);">
                <div style="font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">3. Catat Realisasi</div>
                <p style="margin:0;">Input realisasi hasil pemotongan per pegawai pada menu <strong>Catat Hasil</strong> agar saldo piutang pegawai otomatis terpotong.</p>
            </div>
        </div>
    </div>

</div>

@endsection
