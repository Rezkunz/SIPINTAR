@extends('layouts.app')

@section('title', 'Buka Rekening Baru')
@section('breadcrumb', 'Buka Rekening')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <div class="welcome-badge-tag">Simulasi Kasir & Rekening Siswa</div>
        <h1>Pembukaan Rekening Baru</h1>
        <p>Pendaftaran nasabah baru dan penerbitan nomor rekening tabungan sekolah secara instan untuk siswa atau pegawai.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('tabungan.draft') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            <span>Input Draft Transaksi</span>
        </a>
        <a href="{{ route('tabungan.draft.riwayat') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Riwayat Draft Saya</span>
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── FORM PEMBUKAAN REKENING ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Formulir Pendaftaran Nasabah</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Lengkapi identitas nasabah untuk menerbitkan nomor rekening tabungan</div>
            </div>
            <span class="badge-pill green">Rekening Langsung Aktif</span>
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
                    <strong style="color: var(--text-heading);">Aktivasi Langsung</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Rekening langsung berstatus aktif dengan saldo awal Rp 0 tanpa verifikasi teller.</div>
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
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Setelah rekening terbit, nasabah dapat disimulasikan melakukan setoran tabungan pertama melalui menu Input Draft.</div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
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
                    "Rekening tabungan berhasil dibuka dengan Nomor Rekening: {{ session('success_rekening') }} untuk nasabah {{ session('success_nama') }}.",
                    "REKENING TERBIT !",
                    "SELESAI"
                );
            }
        @endif
    });
</script>

@endsection
