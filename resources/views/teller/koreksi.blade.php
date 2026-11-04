@extends('layouts.app')

@section('title', 'Koreksi Transaksi')
@section('breadcrumb', 'Koreksi Transaksi')

@section('content')
<div class="page-header">
    <h1 class="page-title">Koreksi Transaksi</h1>
    <p class="page-subtitle">Ubah jenis dan nominal transaksi. Transaksi awal tetap tersimpan sebagai histori.</p>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; margin-bottom: 20px;">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

<div class="content-card">
    <div class="table-responsive">
        <table class="financial-table">
            <thead><tr><th>Tanggal</th><th>Rekening</th><th>Nasabah</th><th>Jenis Lama</th><th style="text-align:right;">Nominal Lama</th><th>Koreksi Baru</th></tr></thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->tgl_transaksi }}</td>
                        <td>{{ $transaction->no_rek }}</td>
                        <td>{{ $transaction->nama_nasabah }}</td>
                        <td>{{ ucfirst($transaction->nama_jenis) }}</td>
                        <td style="text-align:right;">Rp {{ number_format($transaction->debit > 0 ? $transaction->debit : $transaction->kredit, 0, ',', '.') }}</td>
                        <td>
                            <form method="POST" action="{{ route('tabungan.koreksi.process', $transaction->transaksi_id) }}" style="display:grid; grid-template-columns: 150px 140px 220px auto; gap:8px; min-width: 560px; align-items:center;">
                                @csrf
                                <select class="form-input" name="jenis_transaksi_id" required>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->jenis_transaksi_id }}" @selected((int) $transaction->jenis_transaksi_id === (int) $type->jenis_transaksi_id)>{{ ucfirst(str_replace('_', ' ', $type->nama_jenis)) }}</option>
                                    @endforeach
                                </select>
                                <input class="form-input" name="nominal" type="number" min="0.01" step="0.01" value="{{ $transaction->debit ?: $transaction->kredit }}" required>
                                <input class="form-input" name="alasan" required maxlength="500" placeholder="Alasan koreksi">
                                <button class="btn-ui btn-ui-outline btn-ui-sm" type="submit">Koreksi</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-placeholder">Belum ada transaksi.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($transactions->hasPages())<div style="padding: 16px 20px;">{{ $transactions->links() }}</div>@endif
</div>
@endsection