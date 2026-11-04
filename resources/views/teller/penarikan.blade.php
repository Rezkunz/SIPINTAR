@extends('layouts.app')

@section('title', 'Penarikan Teller')
@section('breadcrumb', 'Penarikan')

@section('content')
<div class="page-header"><h1 class="page-title">Penarikan Tunai</h1><p class="page-subtitle">Saldo rekening harus menyisakan minimal Rp 10.000.</p></div>
@if ($errors->any())<div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2;">{{ $errors->first() }}</div>@endif
<div class="content-card"><div class="card-body-content">
    <form method="POST" action="{{ route('tabungan.penarikan.store') }}">
        @csrf
        <label for="no_rek">Rekening</label>
        <select id="no_rek" name="no_rek" class="form-input" required>
            <option value="">Pilih rekening aktif</option>
            @foreach ($rekeningList as $rekening)
                <option value="{{ $rekening->no_rek }}" @selected(old('no_rek') === $rekening->no_rek)>{{ $rekening->no_rek }} - {{ $rekening->nasabah->nama_nasabah ?? '-' }} (Rp {{ number_format($rekening->saldo, 0, ',', '.') }})</option>
            @endforeach
        </select>
        <label for="nominal" style="display:block; margin-top: 16px;">Nominal Penarikan</label>
        <input id="nominal" name="nominal" class="form-input" type="number" min="1000" step="1000" value="{{ old('nominal') }}" required>
        <button class="btn-ui btn-ui-primary" type="submit" style="margin-top: 20px;">Simpan Penarikan</button>
    </form>
</div></div>
@endsection