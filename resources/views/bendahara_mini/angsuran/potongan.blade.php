@extends('layouts.app')

@section('title', 'Rencana Potongan Angsuran Bulanan')
@section('breadcrumb', 'Rencana Potongan')

@section('content')

<style>
/* ── Themed Period Filter Controls ── */
.period-filter-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: #F8FAFC;
    padding: 4px 10px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
}

.period-filter-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding-right: 4px;
}

.period-filter-label svg {
    stroke: var(--brand-primary);
}

.sipintar-select {
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-heading);
    background-color: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    padding: 6px 30px 6px 12px;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%230D5C46' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px 12px;
    appearance: none;
    -webkit-appearance: none;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.sipintar-select:hover {
    border-color: var(--brand-primary);
    background-color: #F0FDF4;
}

.sipintar-select:focus {
    outline: none;
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 3px rgba(13, 92, 70, 0.15);
}

/* ── Distinct Colored Icon Action Buttons ── */
.action-icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.action-icon-btn:hover {
    transform: translateY(-2px);
}

/* Tombol Mata / Lihat Rincian (Hijau Emerald Khas SIPINTAR) */
.action-btn-view {
    background-color: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #047857;
}

.action-btn-view:hover {
    background-color: var(--brand-primary);
    border-color: var(--brand-primary);
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(13, 92, 70, 0.25);
}

/* Tombol Mata jika Siap Terima Dana (Amber/Gold Alert) */
.action-btn-view.btn-waiting {
    background-color: #FFFBEB;
    border: 1px solid #FCD34D;
    color: #B45309;
}

.action-btn-view.btn-waiting:hover {
    background-color: #D97706;
    border-color: #D97706;
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
}

/* Tombol Cetak Dokumen BAST (Biru Dokumen Trust Blue) */
.action-btn-bast {
    background-color: #F0F9FF;
    border: 1px solid #BAE6FD;
    color: #0284C7;
}

.action-btn-bast:hover {
    background-color: #0284C7;
    border-color: #0284C7;
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
}

.pulse-badge-dot {
    position: absolute;
    top: -3px;
    right: -3px;
    width: 9px;
    height: 9px;
    background-color: #EF4444;
    border: 2px solid #FFFFFF;
    border-radius: 50%;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.35);
}

/* ── Floating Modal Banner with Blurred Backdrop (Popup Melayang & Background Blur) ── */
.sipintar-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.sipintar-modal-card {
    background: #FFFFFF;
    border-radius: 18px;
    width: 100%;
    max-width: 1060px;
    max-height: calc(90vh);
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.15);
    overflow: hidden;
    position: relative;
    animation: modalPopIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalPopIn {
    from {
        opacity: 0;
        transform: scale(0.93) translateY(16px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.banner-top-bar {
    background: linear-gradient(135deg, var(--brand-primary) 0%, #064030 100%);
    padding: 18px 24px;
    color: #FFFFFF;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: nowrap;
    gap: 16px;
    flex-shrink: 0;
}

.banner-tag-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--radius-pill);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #FEF08A;
    margin-bottom: 4px;
}

/* Close Icon Button (Bulat Saja Bersih) */
.modal-close-icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #FFFFFF;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.modal-close-icon-btn:hover {
    background: rgba(255, 255, 255, 0.35);
    transform: rotate(90deg);
}

.modal-scrollable-body {
    overflow-y: auto;
    padding: 22px 24px;
    flex: 1;
}

.modal-footer-bar {
    padding: 14px 24px;
    background-color: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.banner-metric-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

@media (max-width: 820px) {
    .banner-metric-strip {
        grid-template-columns: repeat(2, 1fr);
    }
}

.banner-metric-box {
    background-color: #F8FAFC;
    padding: 14px 16px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.banner-metric-box .label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.05em;
    margin-bottom: 5px;
}

.banner-metric-box .value {
    font-size: 17px;
    font-weight: 800;
    color: var(--text-heading);
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

.banner-confirmation-card {
    background-color: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 20px;
}
</style>

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Penyusunan Rencana Potongan Tunjangan</h1>
        <p>Himpun daftar tagihan angsuran pinjaman bulanan pegawai untuk diserahkan ke Bendahara Sekolah sebagai dasar pemotongan tunjangan.</p>
    </div>
</div>

@if (session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#166534; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#991b1b; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── FLOATING POPUP MODAL BANNER (MELAYANG DENGAN BACKGROUND BLUR & TOMBOL ✕) ── --}}
<div id="paketDetailBanner" class="sipintar-modal-backdrop" onclick="if(event.target === this) tutupDetailBanner()" role="dialog" aria-modal="true" aria-labelledby="bannerTitle">
    <div class="sipintar-modal-card">
        {{-- Header Modal Melayang --}}
        <div class="banner-top-bar">
            <div>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <h2 style="font-size:18px; font-weight:800; margin:0;" id="bannerTitle">Paket #RP-0000</h2>
                    <span id="bannerStatusBadge" class="badge-pill blue">Diproses Sekolah</span>
                </div>
                <div style="font-size:12px; color:rgba(255,255,255,0.85); margin-top:4px;" id="bannerSubinfo">Periode: -</div>
            </div>
            {{-- Tombol Tutup Tunggal --}}
            <button type="button" onclick="tutupDetailBanner()" class="modal-close-icon-btn" title="Tutup (Esc)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Area Scrollable Isi Modal --}}
        <div class="modal-scrollable-body">
            {{-- Metric Ringkasan Paket --}}
            <div class="banner-metric-strip">
                <div class="banner-metric-box">
                    <div class="label">Total Rencana Potongan</div>
                    <div class="value" id="bannerTotalRencana" style="color:var(--brand-primary);">Rp 0</div>
                </div>
                <div class="banner-metric-box">
                    <div class="label">Dana Realisasi Dipotong</div>
                    <div class="value" id="bannerTotalRealisasi" style="color:#059669;">Rp 0</div>
                </div>
                <div class="banner-metric-box">
                    <div class="label">Jumlah Pegawai Terdaftar</div>
                    <div class="value" id="bannerJumlahPegawai">0 Orang</div>
                </div>
                <div class="banner-metric-box">
                    <div class="label">Metode Penyerahan Dana</div>
                    <div class="value" id="bannerMetode" style="font-size:15px; text-transform:uppercase;">-</div>
                </div>
            </div>

            {{-- Catatan Sekolah jika ada --}}
            <div id="bannerCatatanSekolahBox" style="display:none; padding:12px 18px; background:#F8FAFC; border-radius:10px; margin-bottom:18px; font-size:13px; color:#334155; border:1px solid #E2E8F0; border-left:4px solid var(--brand-primary);">
                <strong>Catatan Bendahara Sekolah:</strong> <span id="bannerCatatanSekolahText">-</span>
            </div>

            {{-- Bukti Transfer Bank jika diunggah oleh Bendahara Sekolah --}}
            <div id="bannerBuktiTransferBox" style="display:none; padding:12px 18px; background:#EFF6FF; border-radius:10px; margin-bottom:18px; font-size:13px; color:#1E40AF; border:1px solid #BFDBFE; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span><strong>Bukti Transfer Terlampir:</strong> Bendahara Sekolah telah melampirkan berkas bukti transfer.</span>
                </div>
                <a id="bannerBuktiTransferLink" href="#" target="_blank" class="btn-ui btn-ui-sm" style="background:#2563EB; color:#FFFFFF; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-weight:700; border-radius:6px; padding:6px 14px;">
                    <span>Lihat Bukti Transfer</span>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
            </div>

            {{-- Form Penerimaan Dana Kas (Bersih, Manusiawi, Bebas AI Slop) --}}
            <div id="bannerKonfirmasiSection" style="display:none; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:16px 20px; margin-bottom:18px;">
                <form id="bannerKonfirmasiForm" method="POST" action="">
                    @csrf
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid #E2E8F0;">
                        <div>
                            <h3 style="font-size:14px; font-weight:800; color:var(--text-heading); margin:0 0 2px 0;">
                                Penerimaan Dana Kas
                            </h3>
                            <div style="font-size:12px; color:var(--text-muted);">
                                Total dana diserahkan: <strong id="bannerKonfirmasiNominal" style="color:#166534; font-size:13px;">Rp 0</strong>
                            </div>
                        </div>

                        {{-- Kas Tujuan Display (Rapi, Ringkas, Alami) --}}
                        <div style="display:inline-flex; align-items:center; gap:8px; background:#FFFFFF; border:1px solid #CBD5E1; padding:6px 12px; border-radius:8px;">
                            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em;">Kas Masuk:</span>
                            <span id="bannerAutoKasName" style="font-size:13px; font-weight:700; color:var(--text-heading);">Kas Tunai Brankas</span>
                            <span id="bannerAutoKasBadge" class="badge-pill green" style="font-size:10px; padding:2px 8px;">Tunai</span>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 200px 1fr; gap:14px;" class="banner-konfirmasi-grid">
                        <div>
                            <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.04em; margin-bottom:5px;">Tanggal Terima *</label>
                            <input type="date" name="tgl_terima" value="{{ date('Y-m-d') }}" class="form-input" style="width:100%; font-size:13px; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1;" required>
                        </div>
                        <div>
                            <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.04em; margin-bottom:5px;">Catatan Serah Terima (Opsional)</label>
                            <input type="text" name="catatan" id="bannerKonfirmasiCatatan" placeholder="Misal: Diterima tunai di loket kasir Bank Mini" class="form-input" style="width:100%; font-size:13px; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1;">
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabel Rincian Pegawai dalam Paket (Lebar & Tidak Kepotong) --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="font-size:13px; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.04em; margin:0;">Daftar Pegawai dalam Paket</h4>
                </div>
                <div class="table-responsive" style="border:1px solid #E2E8F0; border-radius:10px; overflow-x:auto;">
                    <table class="financial-table" style="min-width: 820px;">
                        <thead>
                            <tr>
                                <th style="width:36px; text-align:center;">No</th>
                                <th>Nama Pegawai / Unit</th>
                                <th style="text-align:center; white-space:nowrap;">Angsuran</th>
                                <th style="text-align:right; white-space:nowrap;">Pokok</th>
                                <th style="text-align:right; white-space:nowrap;">Jasa 2%</th>
                                <th style="text-align:right; white-space:nowrap;">Nominal Rencana</th>
                                <th style="text-align:right; white-space:nowrap;">Realisasi Gaji</th>
                                <th style="text-align:center; white-space:nowrap;">Hasil Potongan</th>
                                <th style="white-space:nowrap;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="bannerPegawaiTableBody">
                            {{-- Diisi secara dinamis lewat JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Footer Modal --}}
        <div class="modal-footer-bar">
            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="btn-ui btn-ui-outline" onclick="tutupDetailBannerDirect()" style="gap:6px;">
                    <span>Tutup</span>
                </button>
                <div id="bannerBastLinkWrapper">
                    {{-- Tombol BAST jika sudah selesai / verifikasi --}}
                </div>
            </div>

            <div id="bannerFooterActionWrapper">
                {{-- Tombol Konfirmasi Penerimaan Dana di Pojok Kanan Bawah --}}
                <button type="submit" 
                        form="bannerKonfirmasiForm" 
                        id="bannerBtnSubmitFooter" 
                        class="btn-ui btn-ui-primary" 
                        style="display:none; gap:6px; font-weight:700; padding:10px 20px; font-size:13px;"
                        onclick="return confirm('Konfirmasi penerimaan dana dan bukukan mutasi kas sekarang?')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Terima Uang & Bukukan Kas</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── FORM PENYUSUNAN PAKET POTONGAN BULANAN ── --}}
<div class="content-card" style="margin-bottom: 24px;">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 class="card-heading">Daftar Tagihan Jatuh Tempo (Metode Potong Gaji)</h2>
            <div style="font-size:12px; color:var(--text-muted);">Pilih pegawai yang akan diajukan ke Bendahara Sekolah untuk pemotongan periode {{ \Carbon\Carbon::createFromDate($tahunSekarang, $bulanSekarang, 1)->translatedFormat('F Y') }}</div>
        </div>

        {{-- Dropdown Filter Periode yang Selaras dengan Tema SIPINTAR --}}
        <form method="GET" action="{{ route('bbm.angsuran.potongan') }}">
            <div class="period-filter-toolbar">
                <div class="period-filter-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>Periode Tagihan:</span>
                </div>
                <select name="bulan" class="sipintar-select" onchange="this.form.submit()" title="Pilih Bulan">
                    @for ($b = 1; $b <= 12; $b++)
                        <option value="{{ $b }}" {{ $b == $bulanSekarang ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(2026, $b, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <select name="tahun" class="sipintar-select" onchange="this.form.submit()" title="Pilih Tahun">
                    @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $y == $tahunSekarang ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </form>
    </div>

    <form method="POST" action="{{ route('bbm.angsuran.potongan.simpan') }}">
        @csrf

        <input type="hidden" name="bulan" value="{{ $bulanSekarang }}">
        <input type="hidden" name="tahun" value="{{ $tahunSekarang }}">

        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th style="width:40px; text-align:center;">
                            <input type="checkbox" id="checkAll" onclick="toggleSelectAll(this)" checked>
                        </th>
                        <th>Jatuh Tempo</th>
                        <th>Nama Pegawai / Unit</th>
                        <th>Tenor Ke</th>
                        <th style="text-align: right;">Pokok</th>
                        <th style="text-align: right;">Jasa 2%</th>
                        <th style="text-align: right;">Total Potongan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($calonPotongan as $cp)
                        <tr>
                            <td style="text-align:center;">
                                <input type="checkbox" name="jadwal_ids[]" value="{{ $cp->jadwal_angsuran_id }}" 
                                       class="item-checkbox" data-nominal="{{ $cp->total_angsuran }}" 
                                       onchange="recalculateTotal()" checked>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($cp->jatuh_tempo)->format('d/m/Y') }}</td>
                            <td>
                                <div style="font-weight:700; color:var(--text-heading);">{{ $cp->nama_nasabah }}</div>
                                <div style="font-size:11px; color:var(--text-muted);">{{ $cp->kelas_unit ?? 'Pegawai' }}</div>
                            </td>
                            <td>
                                <span class="badge-pill blue">Ke-{{ $cp->angsuran_ke }}/{{ $cp->tenor }}</span>
                            </td>
                            <td style="text-align: right;">Rp {{ number_format($cp->pokok, 0, ',', '.') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($cp->bunga, 0, ',', '.') }}</td>
                            <td style="text-align: right; font-weight:700; color:var(--text-heading);">
                                Rp {{ number_format($cp->total_angsuran, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-placeholder" style="padding:32px 16px; text-align:center;">
                                    Tidak ada jadwal tagihan potong gaji yang jatuh tempo pada bulan berjalan.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($calonPotongan->isNotEmpty())
            <div style="padding:18px 24px; border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                <div>
                    <span style="font-size:13px; color:var(--text-muted);">Total Rencana Potongan Terpilih:</span>
                    <span style="font-size:18px; font-weight:800; color:#166534; margin-left:8px;" id="dispTotalRencana">Rp 0</span>
                </div>
                <button type="submit" class="btn-ui btn-ui-primary" onclick="return confirm('Terbitkan paket rencana potongan ini dan teruskan ke Bendahara Sekolah?')">
                    Terbitkan Rencana ke Bendahara Sekolah
                </button>
            </div>
        @endif
    </form>
</div>

{{-- ── RIWAYAT PAKET RENCANA POTONGAN (DENGAN FILTER BULAN, TAHUN & STATUS) ── --}}
<div class="content-card" id="tabelRiwayatCard">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Daftar Paket Rencana Potongan Tunjangan</h2>
            <div style="font-size:12px; color:var(--text-muted);">Riwayat serah terima pemotongan payroll ke Bendahara Sekolah (maksimal 10 baris per halaman)</div>
        </div>

        {{-- Form Filter Riwayat --}}
        <form method="GET" action="{{ route('bbm.angsuran.potongan') }}#tabelRiwayatCard" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            {{-- Filter Bulan --}}
            <select name="filter_bulan" class="form-select" onchange="this.form.submit()" style="padding:6px 10px; font-size:12.5px; border-radius:6px; min-width:120px;">
                <option value="">-- Semua Bulan --</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" {{ request('filter_bulan') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>

            {{-- Filter Tahun --}}
            <select name="filter_tahun" class="form-select" onchange="this.form.submit()" style="padding:6px 10px; font-size:12.5px; border-radius:6px; min-width:90px;">
                <option value="">-- Tahun --</option>
                @foreach (range(2025, 2028) as $y)
                    <option value="{{ $y }}" {{ request('filter_tahun') == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endforeach
            </select>

            {{-- Filter Status Verifikasi --}}
            <select name="filter_status" class="form-select" onchange="this.form.submit()" style="padding:6px 10px; font-size:12.5px; border-radius:6px; min-width:150px;">
                <option value="">-- Semua Status --</option>
                <option value="dikirim" {{ request('filter_status') === 'dikirim' ? 'selected' : '' }}>Diproses Sekolah</option>
                <option value="menunggu_verifikasi" {{ request('filter_status') === 'menunggu_verifikasi' ? 'selected' : '' }}>Siap Terima Kas</option>
                <option value="selesai" {{ request('filter_status') === 'selesai' ? 'selected' : '' }}>Selesai Dibukukan</option>
            </select>

            @if (request()->hasAny(['filter_bulan', 'filter_tahun', 'filter_status']))
                <a href="{{ route('bbm.angsuran.potongan') }}#tabelRiwayatCard" class="btn-ui btn-ui-sm btn-ui-outline" style="padding:6px 10px; font-size:12px; text-decoration:none;" title="Reset Filter">
                    Reset
                </a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>ID Paket</th>
                    <th>Periode</th>
                    <th style="text-align: right;">Total Rencana</th>
                    <th style="text-align: right;">Dana Diterima</th>
                    <th style="text-align: center;">Status Verifikasi</th>
                    <th style="text-align: center; width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatRencana as $rr)
                    @php
                        $namaBulan = \Carbon\Carbon::createFromDate($rr->tahun, $rr->bulan, 1)->translatedFormat('F Y');
                    @endphp
                    <tr id="row-paket-{{ $rr->rencana_potongan_id }}">
                        <td>
                            <span style="font-weight:700; font-family:monospace;">
                                #RP-{{ str_pad($rr->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-heading);">{{ $namaBulan }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">Bulan {{ $rr->bulan }}/{{ $rr->tahun }}</div>
                        </td>
                        <td style="text-align: right; font-weight:700; color:var(--text-heading);">
                            Rp {{ number_format($rr->total_potongan, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight:800; color:{{ $rr->total_realisasi > 0 ? '#166534' : 'var(--text-muted)' }};">
                            @if ($rr->total_realisasi > 0)
                                Rp {{ number_format($rr->total_realisasi, 0, ',', '.') }}
                            @else
                                <span style="font-size:12px; font-weight:normal; color:var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($rr->status_potongan === 'selesai')
                                <span class="badge-pill green">Selesai Dibukukan</span>
                            @elseif ($rr->status_potongan === 'menunggu_verifikasi')
                                <span class="badge-pill amber" style="font-weight:700;">Siap Terima Kas</span>
                            @elseif ($rr->status_potongan === 'dikirim')
                                <span class="badge-pill blue">Diproses Sekolah</span>
                            @else
                                <span class="badge-pill gray">Draft</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display:flex; gap:8px; justify-content:center; align-items:center;">
                                {{-- Tombol Mata (Buka Modal Melayang dengan Background Blur - Hijau Emerald) --}}
                                <button type="button" 
                                        onclick="bukaDetailBanner({{ $rr->rencana_potongan_id }})" 
                                        class="action-icon-btn action-btn-view {{ $rr->status_potongan === 'menunggu_verifikasi' ? 'btn-waiting' : '' }}" 
                                        title="Lihat Rincian Paket #RP-{{ str_pad($rr->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}"
                                        id="btn-eye-{{ $rr->rencana_potongan_id }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    @if ($rr->status_potongan === 'menunggu_verifikasi')
                                        <span class="pulse-badge-dot" title="Dana siap diterima & dibukukan"></span>
                                    @endif
                                </button>

                                {{-- Tombol BAST (Biru Dokumen - Hanya muncul jika sudah verifikasi/selesai) --}}
                                @if (in_array($rr->status_potongan, ['menunggu_verifikasi', 'selesai']))
                                    <a href="{{ route('bbm.angsuran.potongan.bast', $rr->rencana_potongan_id) }}" 
                                       target="_blank" 
                                       class="action-icon-btn action-btn-bast" 
                                       title="Cetak Berita Acara Serah Terima (BAST)">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:24px 16px; text-align:center;">
                                Belum ada riwayat rencana potongan yang pernah disusun.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($riwayatRencana->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
            {{ $riwayatRencana->links() }}
        </div>
    @endif
</div>

{{-- Data JSON Rincian Paket untuk Floating Popup Modal --}}
<script>
const dataPaket = {
@foreach ($riwayatRencana as $rr)
    "{{ $rr->rencana_potongan_id }}": {
        "id": "{{ $rr->rencana_potongan_id }}",
        "idFormatted": "#RP-{{ str_pad($rr->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}",
        "bulan": "{{ $rr->bulan }}",
        "tahun": "{{ $rr->tahun }}",
        "periodeFormatted": "{{ \Carbon\Carbon::createFromDate($rr->tahun, $rr->bulan, 1)->translatedFormat('F Y') }}",
        "totalRencana": {{ (float) $rr->total_potongan }},
        "totalRealisasi": {{ (float) $rr->total_realisasi }},
        "status": "{{ $rr->status_potongan }}",
        "metode": "{{ $rr->metode_penyerahan ?? '-' }}",
        "catatan": {!! json_encode($rr->catatan_sekolah) !!},
        "buktiTransfer": "{{ !empty($rr->bukti_transfer) ? asset('storage/' . $rr->bukti_transfer) : '' }}",
        "konfirmasiUrl": "{{ route('bbm.angsuran.potongan.konfirmasi', $rr->rencana_potongan_id) }}",
        "bastUrl": "{{ in_array($rr->status_potongan, ['menunggu_verifikasi', 'selesai']) ? route('bbm.angsuran.potongan.bast', $rr->rencana_potongan_id) : '' }}",
        "items": [
            @if (isset($rincianPerPaket[$rr->rencana_potongan_id]))
                @foreach ($rincianPerPaket[$rr->rencana_potongan_id] as $item)
                {
                    "nama": {!! json_encode($item->nama_nasabah) !!},
                    "unit": {!! json_encode($item->kelas_unit ?? 'Pegawai') !!},
                    "angsuranKe": {{ $item->angsuran_ke }},
                    "tenor": {{ $item->tenor }},
                    "pokok": {{ (float) $item->pokok }},
                    "bunga": {{ (float) $item->bunga }},
                    "nominalRencana": {{ (float) $item->nominal_rencana }},
                    "nominalRealisasi": {{ (float) ($item->nominal_realisasi ?? 0) }},
                    "hasilPotongan": "{{ $item->hasil_potongan ?? 'pending' }}",
                    "statusAngsuran": "{{ $item->status_angsuran }}",
                    "keterangan": {!! json_encode($item->keterangan ?? '-') !!}
                },
                @endforeach
            @endif
        ]
    },
@endforeach
};

let activePaketId = null;

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function bukaDetailBanner(rencanaId) {
    const banner = document.getElementById('paketDetailBanner');
    const p = dataPaket[rencanaId];
    if (!p) return;

    activePaketId = rencanaId;

    // Reset visual aktif pada semua tombol mata
    document.querySelectorAll('.action-icon-btn').forEach(btn => btn.classList.remove('btn-active'));
    const currentBtn = document.getElementById('btn-eye-' + rencanaId);
    if (currentBtn) currentBtn.classList.add('btn-active');

    // Isi data header banner
    document.getElementById('bannerTitle').innerText = 'Paket ' + p.idFormatted;
    document.getElementById('bannerSubinfo').innerText = 'Periode Pemotongan: ' + p.periodeFormatted + ' (Bulan ' + p.bulan + '/' + p.tahun + ')';

    // Status Badge
    const badgeEl = document.getElementById('bannerStatusBadge');
    if (p.status === 'selesai') {
        badgeEl.className = 'badge-pill green';
        badgeEl.innerText = 'Selesai Dibukukan';
    } else if (p.status === 'menunggu_verifikasi') {
        badgeEl.className = 'badge-pill amber';
        badgeEl.innerText = 'Siap Terima Kas';
    } else if (p.status === 'dikirim') {
        badgeEl.className = 'badge-pill blue';
        badgeEl.innerText = 'Diproses Sekolah';
    } else {
        badgeEl.className = 'badge-pill gray';
        badgeEl.innerText = 'Draft';
    }

    // Metric Values (dengan spasi yang benar pada format Orang)
    document.getElementById('bannerTotalRencana').innerText = formatRupiah(p.totalRencana);
    document.getElementById('bannerTotalRealisasi').innerText = (p.totalRealisasi > 0) ? formatRupiah(p.totalRealisasi) : '-';
    document.getElementById('bannerJumlahPegawai').innerText = p.items.length + ' Orang';
    document.getElementById('bannerMetode').innerText = (p.metode && p.metode !== '-') ? p.metode : 'Menunggu Serah Terima';

    // Catatan Sekolah
    const catatanBox = document.getElementById('bannerCatatanSekolahBox');
    if (p.catatan && p.catatan.trim() !== '') {
        catatanBox.style.display = 'block';
        document.getElementById('bannerCatatanSekolahText').innerText = p.catatan;
    } else {
        catatanBox.style.display = 'none';
    }

    // Bukti Transfer Bank
    const buktiBox = document.getElementById('bannerBuktiTransferBox');
    const buktiLink = document.getElementById('bannerBuktiTransferLink');
    if (buktiBox && buktiLink) {
        if (p.buktiTransfer && p.buktiTransfer.trim() !== '') {
            buktiLink.href = p.buktiTransfer;
            buktiBox.style.display = 'flex';
        } else {
            buktiBox.style.display = 'none';
        }
    }

    // Section Konfirmasi Dana
    const konfirmasiSec = document.getElementById('bannerKonfirmasiSection');
    const submitBtnFooter = document.getElementById('bannerBtnSubmitFooter');
    if (p.status === 'menunggu_verifikasi') {
        konfirmasiSec.style.display = 'block';
        if (submitBtnFooter) submitBtnFooter.style.display = 'inline-flex';
        document.getElementById('bannerKonfirmasiNominal').innerText = formatRupiah(p.totalRealisasi);
        document.getElementById('bannerKonfirmasiForm').action = p.konfirmasiUrl;

        // Deteksi kas penampung berdasarkan metode penyerahan dari sekolah
        const isTransfer = (p.metode && p.metode.toLowerCase().includes('transfer'));
        const autoKasNameEl = document.getElementById('bannerAutoKasName');
        const autoKasBadgeEl = document.getElementById('bannerAutoKasBadge');
        const catatanInput = document.getElementById('bannerKonfirmasiCatatan');
        if (autoKasNameEl && autoKasBadgeEl) {
            if (isTransfer) {
                autoKasNameEl.innerText = 'Rekening Bank Bendahara Bank Mini';
                autoKasBadgeEl.className = 'badge-pill blue';
                autoKasBadgeEl.innerText = 'Transfer Bank Bendahara';
                if (catatanInput) catatanInput.placeholder = 'Misal: Transfer ke rekening bank Bendahara Bank Mini terverifikasi';
            } else {
                autoKasNameEl.innerText = 'Kas Tunai (Cash)';
                autoKasBadgeEl.className = 'badge-pill green';
                autoKasBadgeEl.innerText = 'Cash / Tunai';
                if (catatanInput) catatanInput.placeholder = 'Misal: Diterima tunai (cash) di kasir Bank Mini';
            }
        }
    } else {
        konfirmasiSec.style.display = 'none';
        if (submitBtnFooter) submitBtnFooter.style.display = 'none';
    }

    // Link BAST di Footer
    const bastWrap = document.getElementById('bannerBastLinkWrapper');
    if (p.bastUrl) {
        bastWrap.innerHTML = `<a href="${p.bastUrl}" target="_blank" class="btn-ui btn-ui-outline" style="gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Cetak Dokumen BAST</span>
        </a>`;
    } else {
        bastWrap.innerHTML = '';
    }

    // Render Baris Pegawai (Format Tabel Rapih, Angka Tidak Terpotong)
    const tbody = document.getElementById('bannerPegawaiTableBody');
    tbody.innerHTML = '';
    if (p.items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:20px; color:var(--text-muted);">Tidak ada rincian data pegawai dalam paket ini.</td></tr>`;
    } else {
        p.items.forEach((item, idx) => {
            let statusPill = '<span class="badge-pill gray">Pending</span>';
            if (item.hasilPotongan === 'berhasil') {
                statusPill = '<span class="badge-pill green">Berhasil</span>';
            } else if (item.hasilPotongan === 'sebagian') {
                statusPill = '<span class="badge-pill amber">Sebagian</span>';
            } else if (item.hasilPotongan === 'gagal') {
                statusPill = '<span class="badge-pill red">Gagal</span>';
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align:center; color:var(--text-muted);">${idx + 1}</td>
                <td>
                    <div style="font-weight:700; color:var(--text-heading);">${item.nama}</div>
                    <div style="font-size:11px; color:var(--text-muted);">${item.unit}</div>
                </td>
                <td style="text-align:center; white-space:nowrap;">
                    <span class="badge-pill blue">Ke-${item.angsuranKe}/${item.tenor}</span>
                </td>
                <td style="text-align:right; white-space:nowrap;">${formatRupiah(item.pokok)}</td>
                <td style="text-align:right; white-space:nowrap;">${formatRupiah(item.bunga)}</td>
                <td style="text-align:right; font-weight:700; white-space:nowrap; color:var(--text-heading);">${formatRupiah(item.nominalRencana)}</td>
                <td style="text-align:right; font-weight:700; white-space:nowrap; color:${item.nominalRealisasi > 0 ? '#166534' : 'var(--text-muted)'};">
                    ${item.nominalRealisasi > 0 ? formatRupiah(item.nominalRealisasi) : '-'}
                </td>
                <td style="text-align:center; white-space:nowrap;">${statusPill}</td>
                <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;">${item.keterangan || '-'}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Tampilkan Floating Popup Modal dengan Backdrop Blur
    banner.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function tutupDetailBanner() {
    const banner = document.getElementById('paketDetailBanner');
    banner.style.display = 'none';
    document.body.style.overflow = '';
    activePaketId = null;

    document.querySelectorAll('.action-icon-btn').forEach(btn => btn.classList.remove('btn-active'));
    const submitBtnFooter = document.getElementById('bannerBtnSubmitFooter');
    if (submitBtnFooter) submitBtnFooter.style.display = 'none';
}

// Tutup modal jika user menekan tombol Escape di keyboard
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' || event.key === 'Esc') {
        const banner = document.getElementById('paketDetailBanner');
        if (banner && banner.style.display === 'flex') {
            tutupDetailBanner();
        }
    }
});

function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    recalculateTotal();
}

function recalculateTotal() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    let total = 0;
    checkboxes.forEach(cb => {
        total += parseFloat(cb.dataset.nominal) || 0;
    });
    const dispEl = document.getElementById('dispTotalRencana');
    if (dispEl) {
        dispEl.innerText = formatRupiah(total);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    recalculateTotal();
});
</script>

@endsection
