@extends('layouts.app')

@section('title', 'Setoran Teller')
@section('breadcrumb', 'Setoran')

@section('content')
<div class="page-header"><h1 class="page-title">Setoran Tunai</h1><p class="page-subtitle">Catat setoran langsung pada rekening aktif.</p></div>
@if ($errors->any())<div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2;">{{ $errors->first() }}</div>@endif
<div class="content-card"><div class="card-body-content">
    <form method="POST" action="{{ route('tabungan.setoran.store') }}">
        @csrf
        <label for="no_rek">Rekening</label>
        <select id="no_rek" name="no_rek" class="form-input" required>
            <option value="">Pilih rekening aktif</option>
            @foreach ($rekeningList as $rekening)
                <option value="{{ $rekening->no_rek }}" @selected(old('no_rek') === $rekening->no_rek)>{{ $rekening->no_rek }} - {{ $rekening->nasabah->nama_nasabah ?? '-' }}</option>
            @endforeach
        </select>
        <label for="nominal" style="display:block; margin-top: 16px;">Nominal Setoran</label>
        <input id="nominal" name="nominal" class="form-input" type="number" min="1000" step="1000" value="{{ old('nominal') }}" required>
        <button class="btn-ui btn-ui-primary" type="submit" style="margin-top: 20px;">Simpan Setoran</button>
    </form>
</div></div>
@endsection