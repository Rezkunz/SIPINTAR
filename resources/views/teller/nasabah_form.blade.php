@extends('layouts.app')

@php $modeUbah = $nasabah !== null; @endphp

@section('title', $modeUbah ? 'Ubah Data Nasabah' : 'Tambah Nasabah')
@section('breadcrumb', $modeUbah ? 'Ubah Data Nasabah' : 'Tambah Nasabah')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $modeUbah ? 'Ubah Data Nasabah' : 'Tambah Nasabah & Buka Rekening' }}</h1>
    <p class="page-subtitle">
        {{ $modeUbah ? 'Perbarui identitas dan nomor kontak nasabah.' : 'Untuk nasabah yang datang langsung ke loket. Rekening langsung aktif setelah setoran awal diterima.' }}
    </p>
</div>

<div class="content-card" style="max-width: 640px;">
    <form method="POST" action="{{ $modeUbah ? route('tabungan.nasabah.update', $nasabah->nasabah_id) : route('tabungan.nasabah.store') }}" style="padding: 24px; display: grid; gap: 16px;">
        @csrf
        @if ($modeUbah)
            @method('PUT')
            <div style="font-size: 13px; color: var(--text-muted);">
                ID Nasabah #{{ $nasabah->nasabah_id }} &middot; Rekening {{ $nasabah->rekening->no_rek ?? '-' }}
            </div>
        @endif

        <div>
            <label for="nama_nasabah">Nama nasabah</label>
            <input id="nama_nasabah" name="nama_nasabah" class="form-input" maxlength="100" value="{{ old('nama_nasabah', $nasabah->nama_nasabah ?? '') }}">
            @error('nama_nasabah')<div class="text-error" style="color: #991B1B; font-size: 12.5px; margin-top: 4px;">{{ $message }}</div>@enderror
        </div>

        @unless ($modeUbah)
            <div>
                <label for="jenis_nasabah">Jenis tabungan / nasabah</label>
                <select id="jenis_nasabah" name="jenis_nasabah" class="form-input">
                    <option value="siswa" @selected(old('jenis_nasabah', 'siswa') === 'siswa')>Siswa</option>
                    <option value="pegawai" @selected(old('jenis_nasabah') === 'pegawai')>Pegawai / Guru</option>
                </select>
                @error('jenis_nasabah')<div class="text-error" style="color: #991B1B; font-size: 12.5px; margin-top: 4px;">{{ $message }}</div>@enderror
            </div>
        @endunless

        <div>
            <label for="kelas_unit">Kelas / unit kerja</label>
            <input id="kelas_unit" name="kelas_unit" class="form-input" maxlength="50" value="{{ old('kelas_unit', $nasabah->kelas_unit ?? '') }}">
            @error('kelas_unit')<div class="text-error" style="color: #991B1B; font-size: 12.5px; margin-top: 4px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="no_hp">Nomor HP / WhatsApp</label>
            <input id="no_hp" name="no_hp" class="form-input" maxlength="15" inputmode="numeric" value="{{ old('no_hp', $nasabah->no_hp ?? '') }}">
            @error('no_hp')<div class="text-error" style="color: #991B1B; font-size: 12.5px; margin-top: 4px;">{{ $message }}</div>@enderror
        </div>

        @unless ($modeUbah)
            <div>
                <label for="setoran_awal">Setoran awal (minimal Rp 10.000)</label>
                <input id="setoran_awal" name="setoran_awal" type="number" min="10000" step="1000" class="form-input" value="{{ old('setoran_awal', 10000) }}">
                @error('setoran_awal')<div class="text-error" style="color: #991B1B; font-size: 12.5px; margin-top: 4px;">{{ $message }}</div>@enderror
            </div>
        @endunless

        <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 8px;">
            <a href="{{ route('tabungan.buka-rekening') }}" class="btn-ui btn-ui-outline">Batal</a>
            <button type="submit" class="btn-ui btn-ui-primary">{{ $modeUbah ? 'Simpan' : 'Buka Rekening' }}</button>
        </div>
    </form>
</div>
@endsection
