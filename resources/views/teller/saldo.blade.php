@extends('layouts.app')

@section('title', 'Cek Saldo')
@section('breadcrumb', 'Saldo')

@section('content')
<div class="page-header"><h1 class="page-title">Cek Saldo Rekening</h1><p class="page-subtitle">Cari rekening aktif untuk melihat saldo terkini.</p></div>
<div class="content-card"><div class="card-body-content">
    <form method="GET" action="{{ route('tabungan.saldo') }}" style="display:flex; gap:10px;">
        <input class="form-input" name="no_rek" value="{{ request('no_rek') }}" placeholder="Nomor rekening" required>
        <button class="btn-ui btn-ui-primary" type="submit">Cari</button>
    </form>
    @if ($rekening)
        <div style="margin-top: 24px;"><strong>{{ $rekening->nasabah->nama_nasabah ?? '-' }}</strong><div>{{ $rekening->no_rek }}</div><h2>Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</h2></div>
    @elseif(request('no_rek'))
        <p style="margin-top: 20px;">Rekening aktif tidak ditemukan.</p>
    @endif
</div></div>
@endsection