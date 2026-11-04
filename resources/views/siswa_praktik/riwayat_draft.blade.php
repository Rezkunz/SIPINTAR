@extends('layouts.app')

@section('title', 'Riwayat Draft Transaksi')
@section('breadcrumb', 'Riwayat Draft')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Riwayat Draft Transaksi Saya</h1>
        <p>Pantau seluruh draft setoran dan penarikan yang telah Anda ajukan, serta status verifikasi oleh teller loket.</p>
    </div>
</div>

{{-- ── 4 RINGKASAN METRIK STATUS DRAFT ── --}}
<div class="metric-grid" style="grid-template-columns: repeat(4, 1fr);">
    <a href="{{ route('tabungan.draft.riwayat', ['status' => 'semua']) }}" class="metric-card card-blue" style="text-decoration: none; cursor: pointer; border: {{ $statusFilter === 'semua' ? '2px solid #2563EB' : '1px solid var(--border-subtle)' }};">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Draft Saya</span>
        </div>
        <div class="metric-value">{{ $totalSemua }}</div>
    </a>

    <a href="{{ route('tabungan.draft.riwayat', ['status' => 'pending']) }}" class="metric-card card-amber" style="text-decoration: none; cursor: pointer; border: {{ $statusFilter === 'pending' ? '2px solid #D97706' : '1px solid var(--border-subtle)' }};">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Menunggu Verifikasi</span>
        </div>
        <div class="metric-value">{{ $totalPending }}</div>
    </a>

    <a href="{{ route('tabungan.draft.riwayat', ['status' => 'disetujui']) }}" class="metric-card card-emerald" style="text-decoration: none; cursor: pointer; border: {{ $statusFilter === 'disetujui' ? '2px solid #059669' : '1px solid var(--border-subtle)' }};">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Telah Disetujui</span>
        </div>
        <div class="metric-value">{{ $totalDisetujui }}</div>
    </a>

    <a href="{{ route('tabungan.draft.riwayat', ['status' => 'ditolak']) }}" class="metric-card card-indigo" style="text-decoration: none; cursor: pointer; border: {{ $statusFilter === 'ditolak' ? '2px solid #DC2626' : '1px solid var(--border-subtle)' }};">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Ditolak Teller</span>
        </div>
        <div class="metric-value">{{ $totalDitolak }}</div>
    </a>
</div>

{{-- ── TABEL RIWAYAT DRAFT TRANSAKSI ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Daftar Draft Transaksi Diajukan</h2>
            <div style="font-size: 12px; color: var(--text-muted);">Menampilkan draft yang Anda catat sendiri di ruang praktik</div>
        </div>

        {{-- Filter Status Tabs --}}
        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('tabungan.draft.riwayat', ['status' => 'semua']) }}" class="btn-ui btn-ui-sm {{ $statusFilter === 'semua' ? 'btn-ui-primary' : 'btn-ui-outline' }}">
                Semua ({{ $totalSemua }})
            </a>
            <a href="{{ route('tabungan.draft.riwayat', ['status' => 'pending']) }}" class="btn-ui btn-ui-sm {{ $statusFilter === 'pending' ? 'btn-ui-primary' : 'btn-ui-outline' }}">
                Pending ({{ $totalPending }})
            </a>
            <a href="{{ route('tabungan.draft.riwayat', ['status' => 'disetujui']) }}" class="btn-ui btn-ui-sm {{ $statusFilter === 'disetujui' ? 'btn-ui-primary' : 'btn-ui-outline' }}">
                Disetujui ({{ $totalDisetujui }})
            </a>
            <a href="{{ route('tabungan.draft.riwayat', ['status' => 'ditolak']) }}" class="btn-ui btn-ui-sm {{ $statusFilter === 'ditolak' ? 'btn-ui-primary' : 'btn-ui-outline' }}">
                Ditolak ({{ $totalDitolak }})
            </a>
        </div>
    </div>

    @if(session('status'))
        <div style="margin: 16px 24px 0; padding: 12px 16px; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: var(--radius-md); font-size: 13px; color: #065F46; display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th style="width: 150px;">Waktu Input</th>
                    <th>Nasabah & Rekening</th>
                    <th style="width: 120px;">Jenis</th>
                    <th style="text-align: right; width: 140px;">Nominal</th>
                    <th style="text-align: center; width: 160px;">Status Otorisasi</th>
                    <th style="width: 130px;">Teller Loket</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drafts as $d)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-heading);">{{ $d->tgl_input ? $d->tgl_input->format('d/m/Y') : '-' }}</div>
                            <div style="font-size: 11px; color: var(--text-muted); font-family: ui-monospace, SFMono-Regular, monospace;">
                                {{ $d->tgl_input ? $d->tgl_input->format('H:i:s') : '-' }} WIB
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--text-heading);">{{ $d->rekening->nasabah->nama_nasabah ?? '-' }}</div>
                            <div style="font-size: 11px; color: var(--text-muted); font-family: ui-monospace, SFMono-Regular, monospace; letter-spacing: 0.3px;">
                                {{ $d->rekening->no_rek ?? '-' }}
                            </div>
                        </td>
                        <td>
                            @if(str_contains(strtolower($d->jenisTransaksi->nama_jenis ?? ''), 'setor'))
                                <span class="badge-pill green" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Setoran
                                </span>
                            @else
                                <span class="badge-pill amber" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Penarikan
                                </span>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--text-heading); font-family: ui-monospace, SFMono-Regular, monospace; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($d->nominal, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            @if ($d->status_verifikasi === 'disetujui')
                                <span class="badge-pill green" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    Disetujui
                                </span>
                            @elseif ($d->status_verifikasi === 'ditolak')
                                <span class="badge-pill red" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Ditolak
                                </span>
                            @else
                                <span class="badge-pill amber" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($d->teller)
                                <div style="font-size: 12px; font-weight: 600; color: var(--text-heading);">{{ $d->teller->nama_user }}</div>
                            @else
                                <span style="font-size: 11px; color: var(--text-faint);">Menunggu loket</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding: 40px 20px;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 10px; color: var(--text-faint); display: block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                <div style="font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Tidak Ada Data Draft Transaksi</div>
                                <div style="font-size: 12px; color: var(--text-muted); max-width: 360px; margin: 0 auto 14px;">
                                    @if($statusFilter !== 'semua')
                                        Tidak ditemukan draft transaksi Anda dengan status "{{ $statusFilter }}".
                                    @else
                                        Anda belum pernah membuat entri draft transaksi simulasi perbankan.
                                    @endif
                                </div>
                                <a href="{{ route('tabungan.draft') }}" class="btn-ui btn-ui-primary btn-ui-sm">
                                    Input Draft Sekarang
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginasi --}}
    @if ($drafts->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
            <div style="font-size: 12px; color: var(--text-muted);">
                Menampilkan {{ $drafts->firstItem() ?? 0 }} sampai {{ $drafts->lastItem() ?? 0 }} dari {{ $drafts->total() }} entri draft
            </div>
            <div>
                {{ $drafts->links() }}
            </div>
        </div>
    @endif
</div>

@endsection
