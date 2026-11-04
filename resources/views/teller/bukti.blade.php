@extends('layouts.app')

@section('title', 'Bukti Transaksi')
@section('breadcrumb', 'Bukti Transaksi')

@section('content')
<div class="page-header"><h1 class="page-title">Bukti Transaksi</h1><p class="page-subtitle">Buka dan cetak ulang bukti transaksi.</p></div>
<div class="content-card"><div class="table-responsive"><table class="financial-table"><thead><tr><th>No. Bukti</th><th>Nasabah</th><th>Rekening</th><th>Tanggal</th><th></th></tr></thead><tbody>
    @forelse ($buktiList as $bukti)
        <tr><td>{{ $bukti->no_bukti }}</td><td>{{ $bukti->nama_nasabah }}</td><td>{{ $bukti->no_rek }}</td><td>{{ $bukti->tgl_cetak }}</td><td><a class="btn-ui btn-ui-outline btn-ui-sm" href="{{ route('tabungan.bukti.show', $bukti->transaksi_id) }}">Lihat / Cetak</a></td></tr>
    @empty
        <tr><td colspan="5"><div class="empty-placeholder">Belum ada bukti transaksi.</div></td></tr>
    @endforelse
</tbody></table></div><div style="padding: 16px 20px;">{{ $buktiList->links() }}</div></div>
@endsection