@extends('layouts.app')

@section('title', 'Input Draft Transaksi')
@section('breadcrumb', 'Input Draft')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Input Draft Transaksi Tabungan</h1>
        <p>Catat draft setoran atau penarikan tabungan nasabah untuk diajukan ke antrean verifikasi teller loket.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── FORM INPUT DRAFT ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Formulir Entri Draft Transaksi</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Pilih rekening nasabah, tentukan jenis transaksi, dan masukkan nominal</div>
            </div>
            <span class="badge-pill amber">Menunggu Verifikasi Loket</span>
        </div>

        @if ($errors->any())
            <div style="margin: 20px 24px 0; padding: 12px 16px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: var(--radius-md);" role="alert">
                <div style="font-size: 13px; font-weight: 700; color: #991B1B; margin-bottom: 4px;">Terdapat kesalahan pada isian formulir:</div>
                <ul style="font-size: 12px; color: #B91C1C; padding-left: 18px; margin: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tabungan.draft.store') }}" method="POST" id="form-input-draft" style="padding: 24px;">
            @csrf

            {{-- 1. Pilihan Jenis Transaksi (Setoran / Penarikan) --}}
            <div style="margin-bottom: 20px;">
                <label class="profile-field-label" style="margin-bottom: 8px;">
                    <span>Jenis Transaksi <span class="required-mark">*</span></span>
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="position: relative; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border: 2px solid {{ (old('jenis', $jenis) === 'setoran') ? 'var(--brand-primary)' : 'var(--border-medium)' }}; background: {{ (old('jenis', $jenis) === 'setoran') ? '#F0FDF4' : '#FFFFFF' }}; border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition);" id="label-jenis-setoran">
                        <input type="radio" name="jenis" value="setoran" {{ old('jenis', $jenis) === 'setoran' ? 'checked' : '' }} onchange="updateJenisUI('setoran')" style="width: 18px; height: 18px; accent-color: var(--brand-primary);">
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #065F46;">Draft Setoran</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Penerimaan uang simpanan nasabah</div>
                        </div>
                    </label>

                    <label style="position: relative; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border: 2px solid {{ (old('jenis', $jenis) === 'penarikan') ? '#D97706' : 'var(--border-medium)' }}; background: {{ (old('jenis', $jenis) === 'penarikan') ? '#FFFBEB' : '#FFFFFF' }}; border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition);" id="label-jenis-penarikan">
                        <input type="radio" name="jenis" value="penarikan" {{ old('jenis', $jenis) === 'penarikan' ? 'checked' : '' }} onchange="updateJenisUI('penarikan')" style="width: 18px; height: 18px; accent-color: #D97706;">
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #92400E;">Draft Penarikan</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Pengambilan saldo simpanan nasabah</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- 2. Rekening Nasabah --}}
            <div class="profile-field-group" style="margin-bottom: 20px;">
                <label for="no_rek" class="profile-field-label">
                    <span>Rekening Nasabah <span class="required-mark">*</span></span>
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">Hanya rekening berstatus aktif</span>
                </label>
                <div class="profile-input-wrap">
                    <svg class="profile-input-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="11" y2="16"/></svg>
                    <select id="no_rek" name="no_rek" class="profile-input" required onchange="onRekeningSelected(this)">
                        <option value="">Pilih Nomor Rekening Nasabah...</option>
                        @foreach ($rekeningList as $r)
                            <option value="{{ $r->no_rek }}"
                                data-nama="{{ $r->nasabah->nama_nasabah ?? '-' }}"
                                data-kategori="{{ ($r->nasabah->jenis_nasabah ?? '') === 'pegawai' ? 'Pegawai' : 'Siswa' }}"
                                data-kelas="{{ $r->nasabah->kelas_unit ?? '-' }}"
                                data-saldo="{{ (float) $r->saldo }}"
                                data-saldo-formatted="Rp {{ number_format($r->saldo, 0, ',', '.') }}"
                                {{ (old('no_rek', $selectedRekening->no_rek ?? '') === $r->no_rek) ? 'selected' : '' }}>
                                {{ $r->no_rek }} &mdash; {{ $r->nasabah->nama_nasabah ?? '-' }} ({{ ($r->nasabah->jenis_nasabah ?? '') === 'pegawai' ? 'Pegawai' : 'Siswa' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('no_rek')
                    <span class="profile-error-msg">{{ $message }}</span>
                @enderror

                {{-- Kotak Info Rekening Terpilih --}}
                <div id="rekening-info-box" style="display: none; margin-top: 12px; padding: 14px 16px; background: #F8FAFC; border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Nama Pemilik</div>
                            <div id="info-nama-nasabah" style="font-size: 13px; font-weight: 700; color: var(--text-heading); margin-top: 2px;">-</div>
                            <div id="info-detail-nasabah" style="font-size: 11.5px; color: var(--text-muted);">-</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Saldo Terdaftar Saat Ini</div>
                            <div id="info-saldo" style="font-size: 15px; font-weight: 800; color: #0D5C46; font-family: ui-monospace, SFMono-Regular, monospace; margin-top: 2px;">Rp 0</div>
                            <div id="penarikan-info-note" style="display: none; font-size: 11px; color: #D97706; margin-top: 3px;">
                                Info: Saldo minimum mengendap diverifikasi oleh Teller loket.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Nominal Transaksi --}}
            <div class="profile-field-group" style="margin-bottom: 20px;">
                <label for="nominal_display" class="profile-field-label">
                    <span>Nominal Transaksi (Rupiah) <span class="required-mark">*</span></span>
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">
                        Min. Rp {{ number_format(config('sipintar.draft.min_nominal', 1000), 0, ',', '.') }} &mdash; 
                        Maks. Rp {{ number_format(config('sipintar.draft.max_nominal', 5000000), 0, ',', '.') }}
                    </span>
                </label>
                <div class="profile-input-wrap">
                    <span style="position: absolute; left: 12px; font-weight: 700; color: var(--text-muted); font-size: 13px; pointer-events: none;">Rp</span>
                    <input type="text" id="nominal_display" class="profile-input" style="padding-left: 38px; font-size: 15px; font-weight: 700; font-family: ui-monospace, SFMono-Regular, monospace;" placeholder="0" value="{{ old('nominal') ? number_format((float) old('nominal'), 0, ',', '.') : '' }}" required oninput="formatRupiahInput(this)">
                    <input type="hidden" id="nominal" name="nominal" value="{{ old('nominal') }}">
                </div>
                @error('nominal')
                    <span class="profile-error-msg">{{ $message }}</span>
                @enderror

                {{-- Chip Cepat Nominal --}}
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" onclick="setNominalCepat(10000)">10.000</button>
                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" onclick="setNominalCepat(20000)">20.000</button>
                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" onclick="setNominalCepat(50000)">50.000</button>
                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" onclick="setNominalCepat(100000)">100.000</button>
                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" onclick="setNominalCepat(500000)">500.000</button>
                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" onclick="setNominalCepat(1000000)">1.000.000</button>
                </div>
            </div>

            {{-- 4. Keterangan / Catatan Transaksi --}}
            <div class="profile-field-group" style="margin-bottom: 24px;">
                <label for="keterangan" class="profile-field-label">
                    <span>Catatan / Keterangan Transaksi</span>
                    <span style="font-size: 10px; color: var(--text-muted); font-weight: normal;">(Opsional)</span>
                </label>
                <div class="profile-input-wrap">
                    <svg class="profile-input-icon" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <input type="text" id="keterangan" name="keterangan" class="profile-input" value="{{ old('keterangan') }}" placeholder="Contoh: Tabungan rutin kelas XII atau Pembayaran kas mingguan" maxlength="255">
                </div>
                @error('keterangan')
                    <span class="profile-error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-subtle);">
                <a href="{{ route('dashboard') }}" class="btn-ui btn-ui-outline" style="min-height: 44px; padding: 0 18px; text-decoration: none; display: inline-flex; align-items: center;">
                    Batal
                </a>
                <button type="submit" id="btn-submit-draft" class="btn-ui btn-ui-primary" style="min-height: 44px; padding: 0 24px; font-weight: 700;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span id="btn-submit-draft-text">Simpan & Ajukan Draft</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── INFORMASI ATURAN DRAFT TRANSAKSI ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Pedoman Simulasi Transaksi</h2>
        </div>
        <div style="padding: 20px 24px; font-size: 13px; color: var(--text-body); line-height: 1.6;">
            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #FFFBEB; color: #D97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">1</div>
                <div>
                    <strong style="color: var(--text-heading);">Status Awal: Menunggu (Pending)</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Draft yang Anda input tidak mengubah saldo rekening secara langsung sebelum disetujui oleh Teller loket.</div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">2</div>
                <div>
                    <strong style="color: var(--text-heading);">Batas Nominal Input</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Minimal nominal adalah Rp {{ number_format(config('sipintar.draft.min_nominal', 1000), 0, ',', '.') }} dan maksimal per draft Rp {{ number_format(config('sipintar.draft.max_nominal', 5000000), 0, ',', '.') }}.</div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">3</div>
                <div>
                    <strong style="color: var(--text-heading);">Draft Penarikan</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Saldo rekening ditampilkan sebagai informasi panduan. Pengecekan saldo minimum mengendap dan ketersediaan uang fisik dieksekusi oleh teller saat verifikasi.</div>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: #F1F5F9; color: var(--text-muted); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">4</div>
                <div>
                    <strong style="color: var(--text-heading);">Riwayat & Log</strong>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Setiap draft yang telah Anda buat dapat dipantau statusnya (pending, disetujui, atau ditolak) pada menu Riwayat Draft.</div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // ── INTERAKTIF PILIHAN REKENING & TAMPILAN SALDO ──
    function onRekeningSelected(selectEl) {
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        const box = document.getElementById('rekening-info-box');
        const namaEl = document.getElementById('info-nama-nasabah');
        const detailEl = document.getElementById('info-detail-nasabah');
        const saldoEl = document.getElementById('info-saldo');

        if (!selectedOption || !selectedOption.value) {
            if (box) box.style.display = 'none';
            return;
        }

        const nama = selectedOption.getAttribute('data-nama') || '-';
        const kategori = selectedOption.getAttribute('data-kategori') || '-';
        const kelas = selectedOption.getAttribute('data-kelas') || '-';
        const saldoFormatted = selectedOption.getAttribute('data-saldo-formatted') || 'Rp 0';

        if (namaEl) namaEl.textContent = nama;
        if (detailEl) detailEl.textContent = `${kategori} • ${kelas}`;
        if (saldoEl) saldoEl.textContent = saldoFormatted;
        if (box) box.style.display = 'block';
    }

    // ── INTERAKTIF GANTI JENIS TRANSAKSI ──
    function updateJenisUI(jenis) {
        const lblSetor = document.getElementById('label-jenis-setoran');
        const lblTarik = document.getElementById('label-jenis-penarikan');
        const notePenarikan = document.getElementById('penarikan-info-note');

        if (jenis === 'setoran') {
            if (lblSetor) {
                lblSetor.style.borderColor = 'var(--brand-primary)';
                lblSetor.style.background = '#F0FDF4';
            }
            if (lblTarik) {
                lblTarik.style.borderColor = 'var(--border-medium)';
                lblTarik.style.background = '#FFFFFF';
            }
            if (notePenarikan) notePenarikan.style.display = 'none';
        } else {
            if (lblTarik) {
                lblTarik.style.borderColor = '#D97706';
                lblTarik.style.background = '#FFFBEB';
            }
            if (lblSetor) {
                lblSetor.style.borderColor = 'var(--border-medium)';
                lblSetor.style.background = '#FFFFFF';
            }
            if (notePenarikan) notePenarikan.style.display = 'block';
        }
    }

    // ── FORMATTER RUPIAH PADA INPUT NOMINAL ──
    function formatRupiahInput(input) {
        let val = input.value.replace(/[^0-9]/g, '');
        const hiddenInput = document.getElementById('nominal');
        if (hiddenInput) hiddenInput.value = val;

        if (!val) {
            input.value = '';
            return;
        }

        input.value = new Intl.NumberFormat('id-ID').format(val);
    }

    function setNominalCepat(nominal) {
        const display = document.getElementById('nominal_display');
        const hidden = document.getElementById('nominal');

        if (hidden) hidden.value = nominal;
        if (display) display.value = new Intl.NumberFormat('id-ID').format(nominal);
    }

    // ── PROTEKSI DOUBLE SUBMIT & TRIGGER INITIAL SELECTION ──
    document.addEventListener('DOMContentLoaded', function() {
        const selectRek = document.getElementById('no_rek');
        if (selectRek && selectRek.value) {
            onRekeningSelected(selectRek);
        }

        const currentJenis = document.querySelector('input[name="jenis"]:checked')?.value || 'setoran';
        updateJenisUI(currentJenis);

        const form = document.getElementById('form-input-draft');
        const btn = document.getElementById('btn-submit-draft');
        const btnText = document.getElementById('btn-submit-draft-text');

        if (form && btn) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.style.opacity = '0.75';
                btn.style.cursor = 'not-allowed';
                if (btnText) btnText.textContent = 'Menyimpan Draft...';
            });
        }
    });
</script>

@endsection
