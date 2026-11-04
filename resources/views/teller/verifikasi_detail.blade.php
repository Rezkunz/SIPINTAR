@extends('layouts.app')

@section('title', 'Detail Draft')
@section('breadcrumb', 'Detail Draft')

@section('content')
<div class="page-header">
    <h1 class="page-title">Detail Draft Transaksi</h1>
    <p class="page-subtitle">Validasi data sebelum transaksi dicatat.</p>
</div>

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2;">{{ $errors->first() }}</div>
@endif

<div class="content-card">
    <div class="card-body-content">
        <p><strong>Nasabah:</strong> {{ $draft->rekening->nasabah->nama_nasabah ?? '-' }}</p>
        <p><strong>Nomor Rekening:</strong> {{ $draft->rekening->no_rek ?? '-' }}</p>
        <p><strong>Jenis:</strong> {{ ucfirst($draft->jenisTransaksi->nama_jenis ?? '-') }}</p>
        <p><strong>Nominal:</strong> Rp {{ number_format($draft->nominal, 0, ',', '.') }}</p>
        <p><strong>Operator:</strong> {{ $draft->siswa->nama_user ?? '-' }}</p>
        <p><strong>Waktu Input:</strong> {{ $draft->tgl_input?->format('d/m/Y H:i:s') }}</p>

        @if ($draft->status_verifikasi === 'pending')
            <div style="display: flex; gap: 10px; margin-top: 24px;">
                <form method="POST" action="{{ route('tabungan.verifikasi.approve', $draft) }}">
                    @csrf
                    <button class="btn-ui btn-ui-primary" type="submit">Setujui dan Catat</button>
                </form>
                <form method="POST" action="{{ route('tabungan.verifikasi.reject', $draft) }}">
                    @csrf
                    <button class="btn-ui btn-ui-outline" type="submit">Tolak Draft</button>
                </form>
            </div>
        @else
            <p class="badge-pill amber">Draft sudah diproses: {{ ucfirst($draft->status_verifikasi) }}</p>
        @endif
    </div>
</div>
@endsection