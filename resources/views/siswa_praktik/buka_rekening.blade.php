@extends('layouts.app')

@section('title', 'Buka Rekening Baru')
@section('breadcrumb', 'Buka Rekening')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Pembukaan Rekening Baru</h1>
        <p>Pendaftaran nasabah baru dan penerbitan nomor rekening tabungan sekolah secara instan untuk siswa atau pegawai.</p>
    </div>
</div>

<div class="layout-split">

    {{-- ── FORM PEMBUKAAN REKENING ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Formulir Pendaftaran Nasabah</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Lengkapi identitas nasabah untuk menerbitkan nomor rekening tabungan</div>
            </div>
            <span class="badge-pill amber">Menunggu Setoran Awal</span>
        </div>

        @if(session('success_rekening'))
            <div style="margin: 20px 24px 0; padding: 14px 16px; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: var(--radius-md); display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: #059669; color: #FFFFFF; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                    <div style="font-size: 13px; font-weight: 700; color: #065F46;">Pendaftaran Berhasil!</div>
                    <div style="font-size: 12px; color: #047857; margin-top: 2px;">
                        Nasabah <strong>{{ session('success_nama') }}</strong> telah terdaftar dengan Nomor Rekening: 
                        <strong style="font-family: ui-monospace, SFMono-Regular, monospace; letter-spacing: 0.5px;">{{ session('success_rekening') }}</strong>
                    </div>
                </div>
            </div>
        @endif

        @if(session('status'))
            <div style="margin: 20px 24px 0; padding: 12px 16px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: var(--radius-md); color: #1E40AF; font-size: 13px;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="margin: 20px 24px 0; padding: 12px 16px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: var(--radius-md);" role="alert">
                <div style="font-size: 13px; font-weight: 700; color: #991B1B; margin-bottom: 4px;">Terdapat kesalahan pengisian formulir:</div>
                <ul style="font-size: 12px; color: #B91C1C; padding-left: 18px; margin: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tabungan.buka-rekening.store') }}" method="POST" id="form-buka-rekening" style="padding: 24px;">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                {{-- Nama Nasabah --}}
                <div class="profile-field-group" style="grid-column: span 2;">
                    <label for="nama_nasabah" class="profile-field-label">
                        <span>Nama Lengkap Nasabah <span class="required-mark">*</span></span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="nama_nasabah" name="nama_nasabah" class="profile-input" value="{{ old('nama_nasabah') }}" placeholder="Contoh: Muhammad Reza Pratama" required maxlength="100" autocomplete="name">
                    </div>
                    @error('nama_nasabah')
                        <span class="profile-error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Jenis Nasabah --}}
                <div class="profile-field-group">
                    <label for="jenis_nasabah" class="profile-field-label">
                        <span>Kategori Nasabah <span class="required-mark">*</span></span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <select id="jenis_nasabah" name="jenis_nasabah" class="profile-input" required onchange="toggleLabelNomorInduk(this.value)">
                            <option value="siswa" {{ old('jenis_nasabah') === 'siswa' ? 'selected' : '' }}>Siswa / Non-Pegawai</option>
                            <option value="pegawai" {{ old('jenis_nasabah') === 'pegawai' ? 'selected' : '' }}>Guru / Pegawai</option>
                        </select>
                    </div>
                    @error('jenis_nasabah')
                        <span class="profile-error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Nomor Induk (NISN / NIP) --}}
                <div class="profile-field-group">
                    <label for="no_induk" class="profile-field-label">
                        <span id="label-nomor-induk">Nomor Induk Siswa (NISN) <span class="required-mark">*</span></span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="11" y2="16"/></svg>
                        <input type="text" id="no_induk" name="no_induk" class="profile-input" value="{{ old('no_induk') }}" placeholder="Contoh: 0059876543" required maxlength="30" inputmode="numeric">
                    </div>
                    @error('no_induk')
                        <span class="profile-error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Kelas atau Unit Kerja --}}
                <div class="profile-field-group">
                    <label for="kelas_unit" class="profile-field-label">
                        <span id="label-kelas-unit">Kelas / Ruang Belajar</span>
                        <span style="font-size: 10px; color: var(--text-muted); font-weight: normal;">(Opsional)</span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                        <input type="text" id="kelas_unit" name="kelas_unit" class="profile-input" value="{{ old('kelas_unit') }}" placeholder="Contoh: X AKL 1 atau TU Keuangan" maxlength="50">
                    </div>
                    @error('kelas_unit')
                        <span class="profile-error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Nomor HP / WhatsApp --}}
                <div class="profile-field-group">
                    <label for="no_hp" class="profile-field-label">
                        <span>No. Handphone / WhatsApp <span class="required-mark">*</span></span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input type="tel" id="no_hp" name="no_hp" class="profile-input" value="{{ old('no_hp') }}" placeholder="Contoh: 081234567890" required maxlength="15" inputmode="tel">
                    </div>
                    @error('no_hp')
                        <span class="profile-error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Email Akun Nasabah (Opsional) --}}
                <div class="profile-field-group full-width">
                    <label for="email" class="profile-field-label">
                        <span>Email Akun Nasabah</span>
                        <span style="font-size: 10px; color: var(--text-muted); font-weight: normal;">(Opsional)</span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input type="email" id="email" name="email" class="profile-input" value="{{ old('email') }}" placeholder="Contoh: nasabah@sipintar.sch.id" maxlength="100">
                    </div>
                    @error('email')
                        <span class="profile-error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Password Akun Nasabah (Wajib) --}}
                <div class="profile-field-group">
                    <label for="password" class="profile-field-label">
                        <span>Password Akun Nasabah <span class="required-mark">*</span></span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" class="profile-input" placeholder="Minimal 6 karakter" required minlength="6" maxlength="100">
                    </div>
                    @error('password')
                        <span class="profile-error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Konfirmasi Password Akun Nasabah (Wajib) --}}
                <div class="profile-field-group">
                    <label for="password_confirmation" class="profile-field-label">
                        <span>Konfirmasi Password <span class="required-mark">*</span></span>
                    </label>
                    <div class="profile-input-wrap">
                        <svg class="profile-input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="profile-input" placeholder="Ulangi password" required minlength="6" maxlength="100">
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-subtle);">
                <button type="reset" class="btn-ui btn-ui-outline" style="min-height: 44px; padding: 0 18px;">
                    Atur Ulang
                </button>
                <button type="submit" id="btn-submit-rekening" class="btn-ui btn-ui-primary" style="min-height: 44px; padding: 0 24px; font-weight: 700;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span id="btn-submit-text">Buka Rekening Baru</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── INFORMASI ATURAN PEMBUKAAN REKENING ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Ketentuan Layanan</h2>
        </div>
        <div style="padding: 20px 24px; font-size: 13px; color: var(--text-body); line-height: 1.6;">
            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">1</div>
                <div>
                    <strong style="color: var(--text-heading);">Persetujuan Siswa Praktik</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Rekening menunggu persetujuan dan setoran awal dari Siswa Praktik.</div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">2</div>
                <div>
                    <strong style="color: var(--text-heading);">Format Nomor Rekening</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Format standar <code style="font-family: monospace; background: #F1F5F9; padding: 2px 4px; border-radius: 4px;">1001-TAHUN-NNNNN</code> diterbitkan secara berurutan dan unik.</div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #FFFBEB; color: #D97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">3</div>
                <div>
                    <strong style="color: var(--text-heading);">Pencegahan Duplikat</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Satu nasabah hanya dapat memiliki satu rekening aktif berdasarkan Nomor HP atau Nomor Induk yang terdaftar.</div>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #F1F5F9; color: var(--text-muted); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">4</div>
                <div>
                    <strong style="color: var(--text-heading);">Langkah Berikutnya</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Siswa Praktik menerima setoran awal minimal Rp20.000 lalu menyetujui rekening agar statusnya menjadi aktif.</div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="content-card" style="margin-top: 24px;">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Rekening Menunggu Setoran Awal</h2>
            <div style="font-size: 12px; color: var(--text-muted);">Setoran minimal Rp20.000 mengaktifkan rekening dan dicatat sebagai transaksi awal.</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Nomor Rekening</th>
                    <th>Nama Nasabah</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th style="text-align: right;">Setoran Awal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingRekening as $pending)
                    <tr>
                        <td>{{ $pending->no_rek }}</td>
                        <td>{{ $pending->nasabah->nama_nasabah ?? '-' }}</td>
                        <td>{{ ucfirst($pending->nasabah->jenis_nasabah ?? '-') }}</td>
                        <td><span class="badge-pill amber">Pending</span></td>
                        <td style="text-align: right;">
                            <form method="POST" action="{{ route('tabungan.buka-rekening.approve', $pending) }}" style="display: inline-flex; gap: 8px; align-items: center;">
                                @csrf
                                <input type="text" inputmode="numeric" value="{{ number_format((float) config('sipintar.setoran.min_awal', 20000), 0, ',', '.') }}" required style="width: 130px; text-align:right;" class="form-input" oninput="syncSetoranAwal(this)">
                                <input type="hidden" name="setoran_awal" value="{{ config('sipintar.setoran.min_awal', 20000) }}">
                                <button type="submit" class="btn-ui btn-ui-primary btn-ui-sm">Setujui & Aktifkan</button>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-placeholder">Tidak ada rekening yang menunggu setoran awal.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($pendingRekening->hasPages())
        <div style="padding: 16px 20px;">{{ $pendingRekening->links() }}</div>
    @endif
</div>

<script>
    function syncSetoranAwal(el) {
        const digits = el.value.replace(/\D/g, '');
        const hidden = el.parentElement.querySelector('input[name="setoran_awal"]');
        if (hidden) hidden.value = digits;
        el.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    }

    // ── INTERAKTIF LABEL SESUAI KATEGORI NASABAH ──
    function toggleLabelNomorInduk(jenis) {
        const lblInduk = document.getElementById('label-nomor-induk');
        const inputInduk = document.getElementById('no_induk');
        const lblUnit = document.getElementById('label-kelas-unit');
        const inputUnit = document.getElementById('kelas_unit');

        if (jenis === 'pegawai') {
            if (lblInduk) lblInduk.innerHTML = 'Nomor Induk Pegawai (NIP) <span class="required-mark">*</span>';
            if (inputInduk) inputInduk.placeholder = 'Contoh: 198001012005011002';
            if (lblUnit) lblUnit.innerHTML = 'Unit Kerja / Bagian';
            if (inputUnit) inputUnit.placeholder = 'Contoh: Unit Akuntansi atau Guru Produktif';
        } else {
            if (lblInduk) lblInduk.innerHTML = 'Nomor Induk Siswa (NISN) <span class="required-mark">*</span>';
            if (inputInduk) inputInduk.placeholder = 'Contoh: 0059876543';
            if (lblUnit) lblUnit.innerHTML = 'Kelas / Ruang Belajar';
            if (inputUnit) inputUnit.placeholder = 'Contoh: X AKL 1';
        }
    }

    // ── PROTEKSI DOUBLE SUBMIT & INDIKATOR PROSES ──
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-buka-rekening');
        const btn = document.getElementById('btn-submit-rekening');
        const btnText = document.getElementById('btn-submit-text');

        if (form && btn) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.style.opacity = '0.75';
                btn.style.cursor = 'not-allowed';
                if (btnText) btnText.textContent = 'Memproses Pendaftaran...';
            });
        }

        // ── TRIGGER MODAL STATUS POPUP JIKA REKENING BERHASIL DITERBITKAN ──
        @if(session('success_rekening'))
            if (typeof window.showSuccessPopup === 'function') {
                window.showSuccessPopup(
                    "Pengajuan rekening {{ session('success_rekening') }} & akun login nasabah ({{ session('success_nama') }}) berhasil dibuat. Menunggu setoran awal minimal Rp20.000.",
                    "PENGAJUAN TERCATAT",
                    "SELESAI"
                );
            }
        @endif
    });
</script>

@endsection
