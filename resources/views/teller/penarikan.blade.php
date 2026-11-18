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
        <label for="nominal_display" style="display:block; margin-top: 16px;">Nominal Penarikan (Rp)</label>
        <input id="nominal_display" class="form-input" type="text" inputmode="numeric" placeholder="Contoh: 50.000" value="{{ old('nominal') ? number_format((float) old('nominal'), 0, ',', '.') : '' }}" required oninput="formatNominalPenarikan(this)">
        <input type="hidden" id="nominal" name="nominal" value="{{ old('nominal') }}">
        <button class="btn-ui btn-ui-primary" type="submit" style="margin-top: 20px;">Simpan Penarikan</button>
    </form>
</div></div>
<script>
function formatNominalPenarikan(el) {
    const digits = el.value.replace(/\D/g, '');
    document.getElementById('nominal').value = digits;
    el.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
}
</script>
@endsection