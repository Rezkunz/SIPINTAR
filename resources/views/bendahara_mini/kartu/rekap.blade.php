@extends('layouts.app')

@section('title', 'Rekapitulasi Portofolio Piutang')
@section('breadcrumb', 'Rekap Piutang')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Rekapitulasi Piutang Pegawai Bank Mini</h1>
        <p>Ringkasan menyeluruh portofolio pembiayaan, akumulasi pelunasan pokok/jasa, dan sisa saldo piutang seluruh debitur.</p>
    </div>
</div>

{{-- ── 4 FINANCIAL METRICS ── --}}
<div class="metric-grid">
    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Total Plafon Portofolio</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalPlafonPortofolio, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Akumulasi Pokok Kembali</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalPokokTerbayar, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Akumulasi Jasa 2% Diterima</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalBungaTerbayar, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Sisa Piutang Beredar</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalSisaPiutang, 0, ',', '.') }}</div>
    </div>
</div>

{{-- ── TABEL REKAPITULASI PORTOFOLIO ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Daftar Rekapitulasi Piutang per Peminjam</h2>
            <div style="font-size:12px; color:var(--text-muted);">Data real-time yang dapat ditelusuri langsung ke riwayat transaksi angsuran</div>
        </div>
        <span class="badge-pill blue">{{ $rekapList->count() }} Debitur Terdaftar</span>
    </div>

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Nama Peminjam / Unit</th>
                    <th>Tgl Cair</th>
                    <th style="text-align: right;">Plafon Pinjaman</th>
                    <th>Tenor</th>
                    <th style="text-align: right;">Pokok Terbayar</th>
                    <th style="text-align: right;">Sisa Pokok Piutang</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center;">Kartu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapList as $r)
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $r->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $r->kelas_unit ?? 'Pegawai' }} • #PINJ-{{ $r->pinjaman_id }}</div>
                        </td>
                        <td>{{ $r->tgl_cair ? \Carbon\Carbon::parse($r->tgl_cair)->format('d/m/Y') : '-' }}</td>
                        <td style="text-align: right; font-weight:700;">
                            Rp {{ number_format($r->nominal_pinjaman, 0, ',', '.') }}
                        </td>
                        <td>{{ $r->tenor }} Bulan</td>
                        <td style="text-align: right; color:#166534; font-weight:600;">
                            Rp {{ number_format($r->total_pokok_bayar, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight:800; color:{{ $r->sisa_pokok_piutang > 0 ? '#b45309' : '#166534' }};">
                            Rp {{ number_format($r->sisa_pokok_piutang, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            @if ($r->status_pinjaman === 'lunas')
                                <span class="badge-pill purple">Lunas</span>
                            @else
                                <span class="badge-pill green">Aktif</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('bbm.kartu.index', ['pinjaman_id' => $r->pinjaman_id]) }}" class="btn-ui btn-ui-sm btn-ui-outline" style="text-decoration:none;">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-placeholder" style="padding:32px 16px; text-align:center;">
                                Belum ada data pinjaman yang aktif atau dicairkan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

