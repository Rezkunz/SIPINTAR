@extends('layouts.app')

@section('title', 'Informasi Saldo & Riwayat Mutasi')
@section('breadcrumb', 'Riwayat Mutasi')

@section('content')

{{-- ── HERO GREETING & INFO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Informasi Saldo & Riwayat Mutasi Rekening</h1>
        <p>Buku tabungan digital: monitoring seluruh aliran setoran tunai, penarikan, dan saldo efektif simpanan Anda di Bank Mini.</p>
    </div>
</div>

{{-- ── 4 FINANCIAL METRIC CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    {{-- Card 1: Saldo Efektif --}}
    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Saldo Efektif</span>
            <span class="badge-pill green" style="font-size:10.5px;">Tersedia</span>
        </div>
        <div class="metric-value">Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:6px;">Dana tabungan nyata yang dapat ditarik sewaktu-waktu</div>
    </div>

    {{-- Card 2: Akumulasi Setoran --}}
    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Akumulasi Setoran</span>
        </div>
        <div class="metric-value" style="color:#0369A1;">+ Rp {{ number_format($totalSetoran, 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:6px;">Total seluruh setoran dana tabungan masuk</div>
    </div>

    {{-- Card 3: Akumulasi Penarikan --}}
    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Akumulasi Penarikan</span>
        </div>
        <div class="metric-value" style="color:{{ $totalPenarikan > 0 ? '#B45309' : 'var(--text-muted)' }};">
            {{ $totalPenarikan > 0 ? '- Rp ' . number_format($totalPenarikan, 0, ',', '.') : 'Rp 0' }}
        </div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:6px;">Total penarikan tunai yang pernah dicairkan</div>
    </div>

    {{-- Card 4: Nomor & Tipe Rekening --}}
    <div class="metric-card">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Nomor Rekening Tabungan</span>
            <span class="badge-pill green" style="font-size:10.5px;">Aktif</span>
        </div>
        <div class="metric-value" style="font-family: monospace; font-size: 1.15rem; letter-spacing: 0.03em;">
            {{ $rekening->no_rek }}
        </div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:6px;">
            {{ $nasabah->jenis_nasabah === 'pegawai' ? 'Tabungan Karyawan (TAKAR)' : 'Tabungan Pelajar (TAPEL)' }}
        </div>
    </div>
</div>

{{-- ── TABEL BUKU TABUNGAN DIGITAL & MUTASI ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 class="card-heading">Riwayat Transaksi Buku Tabungan</h2>
            <div style="font-size:12px; color:var(--text-muted);">Pencatatan mutasi transaksi kas tabungan terverifikasi</div>
        </div>

        {{-- Filter Jenis Transaksi --}}
        <div style="display:flex; gap:6px; align-items:center;">
            <a href="{{ route('nasabah.saldo', ['jenis' => 'semua']) }}" 
               class="btn-ui btn-ui-sm {{ $jenisFilter === 'semua' ? 'btn-ui-primary' : 'btn-ui-outline' }}"
               style="font-size:11.5px; text-decoration:none;">
                Semua ({{ $totalTransaksi }})
            </a>
            <a href="{{ route('nasabah.saldo', ['jenis' => 'setoran']) }}" 
               class="btn-ui btn-ui-sm {{ $jenisFilter === 'setoran' ? 'btn-ui-primary' : 'btn-ui-outline' }}"
               style="font-size:11.5px; text-decoration:none;">
                Setoran Masuk
            </a>
            <a href="{{ route('nasabah.saldo', ['jenis' => 'penarikan']) }}" 
               class="btn-ui btn-ui-sm {{ $jenisFilter === 'penarikan' ? 'btn-ui-primary' : 'btn-ui-outline' }}"
               style="font-size:11.5px; text-decoration:none;">
                Penarikan Keluar
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th style="width: 140px;">Waktu Transaksi</th>
                    <th style="width: 110px;">ID Transaksi</th>
                    <th style="width: 130px;">Jenis</th>
                    <th>Uraian / Keterangan Transaksi</th>
                    <th>Petugas Loket</th>
                    <th style="text-align: right; width: 140px;">Debit (-)</th>
                    <th style="text-align: right; width: 140px;">Kredit (+)</th>
                    <th style="text-align: right; width: 150px;">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaksiList as $t)
                    @php
                        $jenisRaw = strtolower($t->nama_jenis ?? ($t->kredit > 0 ? 'setoran' : 'penarikan'));
                        $isSetoran = $t->kredit > 0;
                    @endphp
                    <tr>
                        <td style="font-size: 12.5px; color: var(--text-heading); font-weight: 500;">
                            {{ \Carbon\Carbon::parse($t->tgl_transaksi)->translatedFormat('d M Y, H:i') }}
                        </td>
                        <td style="font-family: monospace; font-size: 12px; font-weight: 700; color: var(--text-muted);">
                            #TRX-{{ str_pad($t->transaksi_id, 4, '0', STR_PAD_LEFT) }}
                        </td>
                        <td>
                            @if ($jenisRaw === 'setoran' || $isSetoran)
                                <span class="badge-pill green" style="font-size: 11px;">Setoran Tunai</span>
                            @elseif ($jenisRaw === 'penarikan')
                                <span class="badge-pill red" style="font-size: 11px;">Penarikan</span>
                            @else
                                <span class="badge-pill blue" style="font-size: 11px;">Jasa Tabungan</span>
                            @endif
                        </td>
                        <td style="font-size: 13px; color: var(--text-body);">
                            @if ($loop->last && $isSetoran && $transaksiList->total() <= 2)
                                <strong>Setoran Awal Pembukaan Rekening</strong>
                                <div style="font-size: 11px; color: var(--text-muted);">Aktivasi buku tabungan fisik di loket teller</div>
                            @elseif ($isSetoran)
                                Setoran simpanan tunai di loket teller Bank Mini
                            @else
                                Penarikan dana tabungan tunai di kasir Bank Mini
                            @endif
                        </td>
                        <td style="font-size: 12px; color: var(--text-muted);">
                            {{ $t->nama_petugas ?? 'Teller Bank Mini' }}
                        </td>
                        <td style="text-align: right; font-weight: 700; font-size: 13px; color: var(--color-danger, #DC2626);">
                            {{ $t->debit > 0 ? '- Rp ' . number_format($t->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align: right; font-weight: 700; font-size: 13px; color: var(--color-success, #059669);">
                            {{ $t->kredit > 0 ? '+ Rp ' . number_format($t->kredit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align: right; font-weight: 800; font-size: 13.5px; color: var(--brand-primary, #0D5C46);">
                            Rp {{ number_format($t->saldo_akhir, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-placeholder" style="padding: 40px 16px; text-align: center;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px; color:var(--text-faint); display:block;">
                                    <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                                </svg>
                                <div style="font-weight: 700; font-size: 14px; color: var(--text-heading); margin-bottom: 4px;">
                                    Tidak Ada Riwayat Transaksi
                                </div>
                                <div style="font-size: 12.5px; color: var(--text-muted); max-width: 420px; margin: 0 auto;">
                                    Belum ada catatan mutasi tabungan pada filter yang dipilih. Silakan lakukan transaksi setoran atau penarikan di loket Bank Mini.
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transaksiList->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end;">
            {{ $transaksiList->links() }}
        </div>
    @endif
</div>

<style>
@media print {
    .app-sidebar, .app-header, .welcome-actions, .nav-section-title, #mobile-backdrop {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .metric-grid {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}
</style>

@endsection

