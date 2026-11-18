@extends('layouts.app')

@section('title', 'Detail Akun Nasabah')
@section('breadcrumb', 'Kelola Akun Nasabah')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
        <h1 class="page-title">{{ $rekening->nasabah->nama_nasabah ?? '-' }}</h1>
        <p class="page-subtitle">No. Rekening: {{ $rekening->no_rek }} &bull; Status: <strong>{{ strtoupper($rekening->status_rekening) }}</strong> &bull; Saldo: <strong>Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</strong></p>
    </div>
    <a href="{{ route('tabungan.kelola-akun') }}" class="btn-ui btn-ui-outline btn-ui-sm">Kembali ke Daftar</a>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; margin-bottom: 20px;">{{ session('success') }}</div>
@endif

@if (session('reset_password_once'))
    <div class="content-card" style="padding: 16px; color: #1E3A8A; background: #EFF6FF; border: 1px solid #93C5FD; margin-bottom: 20px;">
        <strong>Password / PIN Sementara (Hanya Ditampilkan Sekali):</strong>
        <div style="font-size: 20px; font-weight: 800; letter-spacing: 2px; margin-top: 6px; font-family: monospace;">{{ session('reset_password_once') }}</div>
        <div style="font-size: 12px; margin-top: 4px;">Berikan nilai ini kepada nasabah. Nilai ini tidak disimpan dalam bentuk teks biasa dan tidak akan ditampilkan lagi.</div>
    </div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    {{-- Edit Profil Nasabah --}}
    <div class="content-card">
        <div class="card-header-bar"><h2 class="card-heading">Edit Profil Nasabah</h2></div>
        <div class="card-body-content">
            <form method="POST" action="{{ route('tabungan.kelola-akun.profil', $rekening->rekening_id) }}" style="display:flex; flex-direction:column; gap:12px;">
                @csrf
                @method('PUT')
                <div>
                    <label for="nama_nasabah">Nama Nasabah</label>
                    <input id="nama_nasabah" name="nama_nasabah" class="form-input" value="{{ old('nama_nasabah', $rekening->nasabah->nama_nasabah) }}" required>
                </div>
                <div>
                    <label for="no_hp">No. HP / WhatsApp</label>
                    <input id="no_hp" name="no_hp" class="form-input" value="{{ old('no_hp', $rekening->nasabah->no_hp) }}" required>
                </div>
                <div>
                    <label for="jenis_nasabah">Jenis Nasabah</label>
                    <select id="jenis_nasabah" name="jenis_nasabah" class="form-input" required>
                        <option value="non_pegawai" @selected(old('jenis_nasabah', $rekening->nasabah->jenis_nasabah) === 'non_pegawai')>Siswa / Non-Pegawai</option>
                        <option value="pegawai" @selected(old('jenis_nasabah', $rekening->nasabah->jenis_nasabah) === 'pegawai')>Pegawai / Guru</option>
                    </select>
                </div>
                <div>
                    <label for="kelas_unit">Kelas / Unit</label>
                    <input id="kelas_unit" name="kelas_unit" class="form-input" value="{{ old('kelas_unit', $rekening->nasabah->kelas_unit) }}">
                </div>
                <button type="submit" class="btn-ui btn-ui-primary" style="margin-top:6px;">Simpan Perubahan Profil</button>
            </form>
        </div>
    </div>

    {{-- Keamanan & Penutupan Rekening --}}
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="content-card">
            <div class="card-header-bar"><h2 class="card-heading">Reset Password / PIN Nasabah</h2></div>
            <div class="card-body-content">
                <p style="font-size:13px; color:var(--text-muted); margin-top:0;">
                    Akun login: <strong>{{ $rekening->nasabah->user ? 'Terdaftar (' . $rekening->nasabah->user->no_hp . ')' : 'Belum ada (akan dibuat otomatis saat reset)' }}</strong>
                </p>
                <form method="POST" action="{{ route('tabungan.kelola-akun.reset-password', $rekening->rekening_id) }}" onsubmit="return confirm('Reset password/PIN nasabah ini sekarang?');">
                    @csrf
                    <button type="submit" class="btn-ui btn-ui-outline">Generate Password/PIN Baru</button>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header-bar"><h2 class="card-heading">Penutupan Rekening</h2></div>
            <div class="card-body-content">
                @if ($rekening->status_rekening === 'tutup')
                    <p style="font-size:13px; color:#991B1B; margin:0 0 12px;">Rekening ini sudah berstatus <strong>Tutup</strong>.</p>
                    <a href="{{ route('tabungan.kelola-akun.bukti-tutup', $rekening->rekening_id) }}" target="_blank" class="btn-ui btn-ui-outline btn-ui-sm">Cetak Bukti Penutupan</a>
                @else
                    <p style="font-size:13px; color:var(--text-muted); margin-top:0;">
                        Jika saldo masih lebih dari Rp 0 (saat ini <strong>Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</strong>), sistem akan otomatis mencatat penarikan penutupan dan mengurangi Kas Tunai.
                    </p>
                    <form method="POST" action="{{ route('tabungan.kelola-akun.tutup', $rekening->rekening_id) }}" onsubmit="return confirm('PERHATIAN: Rekening {{ $rekening->no_rek }} akan ditutup permanen dan sisa saldo Rp {{ number_format($rekening->saldo, 0, ',', '.') }} akan dicairkan dari Kas Tunai. Lanjutkan?');">
                        @csrf
                        <div style="margin-bottom:12px;">
                            <label for="alasan">Alasan Penutupan (Opsional)</label>
                            <input id="alasan" name="alasan" class="form-input" maxlength="255" placeholder="Contoh: Siswa lulus / pindah sekolah">
                        </div>
                        <button type="submit" class="btn-ui btn-ui-primary" style="background:#DC2626; border-color:#DC2626;">Tutup Rekening Permanen</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
