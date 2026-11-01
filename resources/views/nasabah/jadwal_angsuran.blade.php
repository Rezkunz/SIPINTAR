@extends('layouts.app')

@section('title', 'Jadwal Angsuran Pinjaman Saya')
@section('breadcrumb', 'Jadwal Angsuran')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Jadwal Angsuran & Sisa Piutang Saya</h1>
        <p>Pantau rincian cicilan berkala, jadwal jatuh tempo, dan riwayat pembayaran pinjaman Anda secara mandiri.</p>
    </div>
</div>

@if ($pinjamanAktif)

{{-- ── 4 FINANCIAL METRICS NASABAH ── --}}
<div class="metric-grid">
    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Plafon Pinjaman Aktif</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($pinjamanAktif->nominal_pinjaman, 0, ',', '.') }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Tenor: {{ $pinjamanAktif->tenor }} Bulan • Jasa 2% Flat</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Pokok Terbayar</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($riwayatAngsuran->sum('bayar_pokok'), 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Sisa Pokok Piutang</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($sisaPokokPiutang, 0, ',', '.') }}</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Metode Pembayaran</span>
        </div>
        <div class="metric-value" style="font-size:16px;">
            {{ $pinjamanAktif->metode_bayar === 'potong_gaji' ? 'Potong Gaji Sekolah' : 'Tunai di Loket' }}
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 3fr 2fr; gap:24px; align-items:start;">

    {{-- ── TABEL JADWAL ANGSURAN ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Rincian Jadwal Angsuran Bulanan</h2>
                <div style="font-size:12px; color:var(--text-muted);">Jadwal jatuh tempo cicilan pinjaman Anda</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Ke</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align: right;">Pokok</th>
                        <th style="text-align: right;">Jasa 2%</th>
                        <th style="text-align: right;">Total Cicilan</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jadwalAngsuran as $j)
                        <tr>
                            <td><strong>#{{ $j->angsuran_ke }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($j->jatuh_tempo)->format('d/m/Y') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($j->pokok, 0, ',', '.') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($j->bunga, 0, ',', '.') }}</td>
                            <td style="text-align: right; font-weight:700;">Rp {{ number_format($j->total_angsuran, 0, ',', '.') }}</td>
                            <td style="text-align: center;">
                                @if ($j->status_bayar === 'lunas')
                                    <span class="badge-pill green">Lunas</span>
                                @else
                                    <span class="badge-pill amber">Belum</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── TABEL HISTORI ANGSURAN SAYA ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Riwayat Pembayaran Saya</h2>
                <div style="font-size:12px; color:var(--text-muted);">Bukti angsuran yang telah diterima Bank Mini</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Tgl Bayar</th>
                        <th>Ke</th>
                        <th style="text-align: right;">Pokok</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatAngsuran as $ra)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($ra->tgl_bayar)->format('d/m/Y') }}</td>
                            <td>#{{ $ra->angsuran_ke }}</td>
                            <td style="text-align: right;">Rp {{ number_format($ra->bayar_pokok, 0, ',', '.') }}</td>
                            <td style="text-align: right; font-weight:700; color:#166534;">
                                Rp {{ number_format($ra->bayar_pokok + $ra->bayar_bunga, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-placeholder" style="padding:24px 16px; text-align:center;">
                                    Belum ada catatan pembayaran angsuran yang terdata.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@else

<div class="content-card" style="padding:60px 24px; text-align:center;">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="margin:0 auto 16px; color:var(--text-faint); display:block;">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/>
    </svg>
    <h3 style="font-size:16px; font-weight:700; color:var(--text-heading); margin-bottom:6px;">Tidak Ada Pinjaman Aktif</h3>
    <p style="font-size:13px; color:var(--text-muted); max-width:420px; margin:0 auto 20px;">
        Saat ini Anda tidak memiliki pinjaman berjalan di Bank Mini Wiyata Cipta Niaga. Silakan ajukan pinjaman melalui menu permohonan.
    </p>
    <a href="{{ route('nasabah.pinjaman.ajukan') }}" class="btn-ui btn-ui-primary">Ajukan Pinjaman Baru</a>
</div>

@endif

@endsection
