@extends('layouts.app')

@section('title', 'Log Transaksi Rekening')
@section('breadcrumb', 'Log Transaksi')

@section('content')
<div class="page-header">
    <h1 class="page-title">Log Transaksi Rekening</h1>
    <p class="page-subtitle">Lihat histori transaksi atau gunakan mode koreksi untuk transaksi yang salah.</p>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; margin-bottom: 20px;">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

<div class="content-card">
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.mutasi') }}" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px;">
                <label for="no_rek">Nomor rekening</label>
                <input id="no_rek" class="form-input" name="no_rek" value="{{ request('no_rek') }}" placeholder="Masukkan nomor rekening" required>
            </div>
            <input type="hidden" name="mode" value="{{ $mode }}">
            <button class="btn-ui btn-ui-primary" type="submit">Cari Log</button>
        </form>

        @if ($rekening)
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:20px;">
                <div style="flex:1;"><strong>{{ $rekening->nasabah->nama_nasabah ?? '-' }}</strong><div style="font-size:12px;color:var(--text-muted);">{{ $rekening->no_rek }} | Saldo Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</div></div>
                <a class="btn-ui btn-ui-outline btn-ui-sm" href="{{ route('tabungan.mutasi', ['no_rek' => $rekening->no_rek, 'mode' => 'lihat']) }}">Lihat Mutasi</a>
                <a class="btn-ui btn-ui-outline btn-ui-sm" href="{{ route('tabungan.mutasi', ['no_rek' => $rekening->no_rek, 'mode' => 'koreksi']) }}">Mode Koreksi</a>
                <a class="btn-ui btn-ui-primary btn-ui-sm" target="_blank" href="{{ route('tabungan.mutasi.cetak', ['no_rek' => $rekening->no_rek]) }}">Cetak Log</a>
            </div>
        @endif
    </div>
</div>

@if ($rekening)
<div class="content-card" style="margin-top:20px;">
    <div class="card-header-bar"><h2 class="card-heading">{{ $mode === 'koreksi' ? 'Koreksi Transaksi' : 'Riwayat Mutasi' }}</h2></div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead><tr><th>Tanggal</th><th>Jenis</th><th style="text-align:right;">Debit</th><th style="text-align:right;">Kredit</th><th style="text-align:right;">Saldo</th>@if ($mode === 'koreksi')<th>Koreksi</th>@endif</tr></thead>
            <tbody>
                @forelse ($mutasi as $transaksi)
                    <tr>
                        <td>{{ $transaksi->tgl_transaksi }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $transaksi->nama_jenis)) }}</td>
                        <td style="text-align:right;">Rp {{ number_format($transaksi->debit, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($transaksi->kredit, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($transaksi->saldo_akhir, 0, ',', '.') }}</td>
                        @if ($mode === 'koreksi')
                            <td>
                                <form method="POST" action="{{ route('tabungan.koreksi.process', $transaksi->transaksi_id) }}" style="display:grid; grid-template-columns: 140px 120px 190px auto; gap:6px; min-width: 500px;">
                                    @csrf
                                    <input type="hidden" name="no_rek" value="{{ $rekening->no_rek }}">
                                    <select class="form-input" name="jenis_transaksi_id" required>
                                        @foreach ($types as $type)
                                            <option value="{{ $type->jenis_transaksi_id }}" @selected((int) $transaksi->jenis_transaksi_id === (int) $type->jenis_transaksi_id)>{{ ucfirst(str_replace('_', ' ', $type->nama_jenis)) }}</option>
                                        @endforeach
                                    </select>
                                    <input class="form-input" name="nominal" type="number" min="0.01" step="0.01" value="{{ $transaksi->debit > 0 ? $transaksi->debit : $transaksi->kredit }}" required>
                                    <input class="form-input" name="alasan" required maxlength="500" placeholder="Alasan koreksi">
                                    <button class="btn-ui btn-ui-outline btn-ui-sm" type="submit">Simpan</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $mode === 'koreksi' ? 6 : 5 }}"><div class="empty-placeholder">Belum ada transaksi.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($mutasi instanceof \Illuminate\Pagination\LengthAwarePaginator)<div style="padding: 16px 20px;">{{ $mutasi->links() }}</div>@endif
</div>
@endif
@endsection
