@extends('layouts.app')

@section('title', 'Detail Draft')
@section('breadcrumb', 'Detail Draft')

@section('content')
<div class="page-header">
    <h1 class="page-title">Detail Draft Transaksi</h1>
    <p class="page-subtitle">Validasi data sebelum transaksi dicatat.</p>
</div>

@if ($errors->any() && !$errors->has('alasan_penolakan'))
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
            @php $bukaFormTolak = $errors->has('alasan_penolakan'); @endphp
            <div style="display: flex; gap: 10px; margin-top: 24px;">
                <form method="POST" action="{{ route('tabungan.verifikasi.approve', $draft) }}">
                    @csrf
                    <button class="btn-ui btn-ui-primary" type="submit" id="btn-setujui">Setujui dan Catat</button>
                </form>
                <button class="btn-ui btn-ui-outline" type="button" id="btn-tolak"
                    onclick="document.getElementById('form-tolak').style.display = 'block'; document.getElementById('alasan_penolakan').focus();">
                    Tolak Draft
                </button>
            </div>

            <form method="POST" action="{{ route('tabungan.verifikasi.reject', $draft) }}" id="form-tolak"
                style="display: {{ $bukaFormTolak ? 'block' : 'none' }}; margin-top: 20px; padding: 16px; border: 1px solid #FECACA; border-radius: 10px; background: #FEF2F2;">
                @csrf
                <label for="alasan_penolakan" style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Alasan Penolakan</label>
                <textarea id="alasan_penolakan" name="alasan_penolakan" rows="3" maxlength="500" class="profile-input"
                    placeholder="Contoh: Nominal tidak sesuai slip" style="width: 100%;">{{ old('alasan_penolakan') }}</textarea>
                @error('alasan_penolakan')
                    <div class="text-error" style="color: #991B1B; font-size: 12.5px; margin-top: 6px;">{{ $message }}</div>
                @enderror
                <div style="display: flex; gap: 10px; margin-top: 12px;">
                    <button class="btn-ui btn-ui-primary" type="submit" id="btn-konfirmasi-tolak" style="background: #DC2626; border-color: #DC2626;">Konfirmasi</button>
                    <button class="btn-ui btn-ui-outline" type="button"
                        onclick="document.getElementById('form-tolak').style.display = 'none';">Batal</button>
                </div>
            </form>
        @else
            <p class="badge-pill amber">Draft sudah diproses: {{ ucfirst($draft->status_verifikasi) }}</p>
            @if ($draft->status_verifikasi === 'ditolak' && $draft->alasan_penolakan)
                <p><strong>Alasan Penolakan:</strong> {{ $draft->alasan_penolakan }}</p>
            @endif
        @endif
    </div>
</div>
@endsection