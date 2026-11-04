@extends('layouts.app')

@section('title', 'Log Transaksi Harian')
@section('breadcrumb', 'Log Transaksi')

@section('content')
<div class="page-header">
    <h1 class="page-title">Log Transaksi Harian</h1>
    <p class="page-subtitle">Seluruh transaksi Teller pada tanggal yang dipilih.</p>
</div>

<div class="content-card" style="margin-bottom:20px;">
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.log-transaksi') }}" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
            <div><label for="tanggal">Tanggal transaksi</label><input id="tanggal" class="form-input" type="date" name="tanggal" value="{{ $tanggal }}" required></div>
            <button class="btn-ui btn-ui-primary" type="submit">Tampilkan</button>
            <a class="btn-ui btn-ui-outline" target="_blank" href="{{ route('tabungan.log-transaksi.cetak', ['tanggal' => $tanggal]) }}">Cetak Log</a>
        </form>
    </div>
</div>

<div class="metric-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom:20px;">
    <div class="metric-card card-blue"><div class="metric-title">Jumlah Transaksi</div><div class="metric-value">{{ $ringkasan['total_transaksi'] }}</div></div>
    <div class="metric-card card-emerald"><div class="metric-title">Total Kredit</div><div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_kredit'], 0, ',', '.') }}</div></div>
    <div class="metric-card card-amber"><div class="metric-title">Total Debit</div><div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_debit'], 0, ',', '.') }}</div></div>
    <div class="metric-card card-indigo"><div class="metric-title">Total Bunga</div><div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_bunga'], 0, ',', '.') }}</div></div>
</div>

<div class="content-card">
    <div class="card-header-bar"><h2 class="card-heading">Transaksi {{ $tanggal }}</h2><span class="badge-pill blue">Koreksi: {{ $ringkasan['total_koreksi'] }}</span></div>
    <div class="table-responsive"><table class="financial-table"><thead><tr><th>Waktu</th><th>Rekening / Nasabah</th><th>Jenis</th><th style="text-align:right;">Debit</th><th style="text-align:right;">Kredit</th><th style="text-align:right;">Saldo Akhir</th><th>Pencatat</th><th>Status</th></tr></thead><tbody>
        @forelse ($transaksi as $item)
            <tr><td>{{ $item->tgl_transaksi }}</td><td><strong>{{ $item->no_rek }}</strong><div style="font-size:11px;color:var(--text-muted);">{{ $item->nama_nasabah }}</div></td><td>{{ ucfirst(str_replace('_', ' ', $item->nama_jenis)) }}</td><td style="text-align:right;">Rp {{ number_format($item->debit, 0, ',', '.') }}</td><td style="text-align:right;">Rp {{ number_format($item->kredit, 0, ',', '.') }}</td><td style="text-align:right;">Rp {{ number_format($item->saldo_akhir, 0, ',', '.') }}</td><td>{{ $item->nama_pencatat ?? '-' }}</td><td>{{ $item->koreksi_id ? 'Dikoreksi' : 'Normal' }}</td></tr>
        @empty
            <tr><td colspan="8"><div class="empty-placeholder">Tidak ada transaksi pada tanggal tersebut.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @if ($transaksi->hasPages())<div style="padding:16px 20px;">{{ $transaksi->links() }}</div>@endif
</div>
@endsection