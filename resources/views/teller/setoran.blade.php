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
        <label for="nominal_display" style="display:block; margin-top: 16px;">Nominal Setoran (Rp)</label>
        <input id="nominal_display" class="form-input" type="text" inputmode="numeric" placeholder="Contoh: 50.000" value="{{ old('nominal') ? number_format((float) old('nominal'), 0, ',', '.') : '' }}" required oninput="formatNominalSetoran(this)">
        <input type="hidden" id="nominal" name="nominal" value="{{ old('nominal') }}">
        <label style="display:flex; align-items:center; gap:8px; margin-top:12px; font-size:13px; cursor:pointer;">
            <input type="checkbox" id="toggleMinSetoran" checked onchange="toggleMinSetoranInput(this)">
            <span>Terapkan minimal setoran berikutnya (Rp {{ number_format(config('sipintar.setoran.min_berikutnya', 10000), 0, ',', '.') }})</span>
        </label>
        <input type="hidden" name="abaikan_minimal" id="abaikanMinimalInput" value="0">
        <button class="btn-ui btn-ui-primary" type="submit" style="margin-top: 20px;">Simpan Setoran</button>
    </form>
</div></div>
<script>
function formatNominalSetoran(el) {
    const digits = el.value.replace(/\D/g, '');
    document.getElementById('nominal').value = digits;
    el.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
}
function toggleMinSetoranInput(cb) {
    const hidden = document.getElementById('abaikanMinimalInput');
    hidden.value = cb.checked ? '0' : '1';
}
</script>
@endsection