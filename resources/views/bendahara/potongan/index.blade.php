@extends('layouts.app')

@section('title', 'Daftar Rencana Potongan Tunjangan')
@section('breadcrumb', 'Rencana Potongan')

@section('content')

<style>
/* ── Distinct Colored Icon Action Buttons (Selaras 100% dengan Bendahara Bank Mini) ── */
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
    border: none;
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

/* Tombol Catat Hasil (Warm Amber Icon Button 36x36px) */
.action-btn-edit {
    background-color: #FFFBEB;
    border: 1px solid #FCD34D;
    color: #B45309;
}

.action-btn-edit:hover {
    background-color: #D97706;
    border-color: #D97706;
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
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

/* Tombol Unduh CSV Format Bank Payroll (Netral Slate) */
.action-btn-download {
    background-color: #F8FAFC;
    border: 1px solid #CBD5E1;
    color: #334155;
}

.action-btn-download:hover {
    background-color: #0F172A;
    border-color: #0F172A;
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.25);
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

/* ── Segmented Method Buttons in Modal ── */
.modal-method-btn {
    border: 1px solid #CBD5E1;
    background-color: #FFFFFF;
    border-radius: 8px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.modal-method-btn:hover {
    background-color: #F8FAFC;
    border-color: #94A3B8;
}

.modal-method-btn.active-tunai {
    background-color: #ECFDF5;
    border-color: #059669;
    color: #047857;
    box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.15);
}

.modal-method-btn.active-transfer {
    background-color: #EFF6FF;
    border-color: #0284C7;
    color: #0284C7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
}

@media (max-width: 640px) {
    .banner-form-grid {
        grid-template-columns: 1fr !important;
    }
}

.row-failed {
    background-color: #FFF5F5 !important;
}

/* ── Modal Toggle Pills for Berhasil / Gagal ── */
.modal-toggle-pill-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #F1F5F9;
    padding: 3px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
}

.modal-toggle-label {
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    padding: 5px 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #64748B;
    transition: all 0.15s ease;
    user-select: none;
    margin: 0;
}

.modal-toggle-label input[type="radio"] {
    display: none;
}

.modal-toggle-label.active-berhasil {
    background-color: #ECFDF5;
    color: #047857;
    border: 1px solid #A7F3D0;
    box-shadow: 0 1px 2px rgba(4, 120, 87, 0.12);
}

.modal-toggle-label.active-gagal {
    background-color: #FEF2F2;
    color: #B91C1C;
    border: 1px solid #FECACA;
    box-shadow: 0 1px 2px rgba(185, 28, 28, 0.12);
}
    user-select: none;
    margin: 0;
}

.modal-toggle-label input[type="radio"] {
    display: none;
}

.modal-toggle-label.active-berhasil {
    background-color: #ECFDF5;
    color: #047857;
    border: 1px solid #A7F3D0;
    box-shadow: 0 1px 2px rgba(4, 120, 87, 0.12);
}

.modal-toggle-label.active-gagal {
    background-color: #FEF2F2;
    color: #B91C1C;
    border: 1px solid #FECACA;
    box-shadow: 0 1px 2px rgba(185, 28, 28, 0.12);
}
</style>

{{-- ── HERO BANNER (Selaras dengan Desain Bendahara Bank Mini) ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Monitoring Rencana Potongan Pegawai</h1>
        <p>Pantau paket rencana potongan angsuran pinjaman guru dan staf dari Bank Mini, unduh format untuk bank mitra payroll, dan catat hasil eksekusi potong gaji.</p>
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

<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Daftar Paket Rencana Potongan Bulanan</h2>
            <div style="font-size:12px; color:var(--text-muted);">Paket potongan berkala yang telah diterbitkan oleh Bendahara Bank Mini</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>ID Paket</th>
                    <th>Periode Pemotongan</th>
                    <th style="text-align: right;">Rencana Tagihan</th>
                    <th style="text-align: right;">Realisasi Sukses</th>
                    <th style="text-align: center;">Status Paket</th>
                    <th style="text-align: center; width: 200px;">Aksi & Dokumen</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rencanaList as $r)
                    @php
                        $namaBulan = \Carbon\Carbon::createFromDate($r->tahun, $r->bulan, 1)->translatedFormat('F Y');
                    @endphp
                    <tr>
                        <td>
                            <span style="font-weight:700; font-family:monospace; color:var(--text-heading);">
                                #RP-{{ str_pad($r->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-heading);">{{ $namaBulan }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">Penyusun: {{ $r->nama_pembuat ?? 'BBM' }}</div>
                        </td>
                        <td style="text-align: right; font-weight:700; color:var(--text-heading);">
                            Rp {{ number_format($r->total_potongan, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight:800; color: {{ $r->total_realisasi > 0 ? '#166534' : 'var(--text-muted)' }};">
                            @if ($r->total_realisasi > 0)
                                Rp {{ number_format($r->total_realisasi, 0, ',', '.') }}
                            @else
                                <span style="font-size:12px; font-weight:normal; color:var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($r->status_potongan === 'selesai')
                                <span class="badge-pill green">Selesai Dibukukan</span>
                            @elseif ($r->status_potongan === 'menunggu_verifikasi')
                                <span class="badge-pill amber">Menunggu Konfirmasi Bank Mini</span>
                            @elseif ($r->status_potongan === 'dikirim')
                                <span class="badge-pill blue">Siap Dipotong Payroll</span>
                            @else
                                <span class="badge-pill gray">Draft</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display:inline-flex; align-items:center; gap:8px; justify-content:center;">
                                {{-- Tombol Mata (Buka Floating Popup Banner & Catat Hasil) --}}
                                <button type="button" 
                                        id="btn-eye-{{ $r->rencana_potongan_id }}"
                                        onclick="bukaDetailBanner('{{ $r->rencana_potongan_id }}')" 
                                        class="action-icon-btn action-btn-view {{ $r->status_potongan === 'dikirim' ? 'btn-waiting' : '' }}" 
                                        title="{{ $r->status_potongan === 'dikirim' ? 'Buka & Catat Hasil Potongan' : 'Lihat Rincian Paket' }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    @if ($r->status_potongan === 'dikirim')
                                        <span class="pulse-badge-dot" title="Perlu dicatat hasil potongan"></span>
                                    @endif
                                </button>

                                {{-- Tombol Unduh Format Bank Payroll (CSV) --}}
                                <a href="{{ route('bendahara.potongan.download', $r->rencana_potongan_id) }}" 
                                   class="action-icon-btn action-btn-download" 
                                   title="Unduh Format CSV untuk Bank Mitra Payroll">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </a>

                                {{-- Tombol BAST jika sudah ada realisasi / serah terima --}}
                                @if (in_array($r->status_potongan, ['menunggu_verifikasi', 'selesai']))
                                    <a href="{{ route('bendahara.potongan.bast', $r->rencana_potongan_id) }}" 
                                       target="_blank" 
                                       class="action-icon-btn action-btn-bast" 
                                       title="Cetak Berita Acara Serah Terima (BAST)">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 6 2 18 2 18 9"/>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                            <rect x="6" y="14" width="12" height="8"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:32px 16px; text-align:center;">
                                Belum ada rencana potongan angsuran yang diajukan oleh Bank Mini.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rencanaList->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
            {{ $rencanaList->links() }}
        </div>
    @endif
</div>

{{-- ── FLOATING POPUP BANNER MODAL (Melayang & Background Blur - Sama Persis Bank Mini) ── --}}
<div id="paketDetailBanner" class="sipintar-modal-backdrop" onclick="tutupDetailBanner(event)">
    <div class="sipintar-modal-card" onclick="event.stopPropagation()">
        {{-- Top Floating Header --}}
        <div class="banner-top-bar">
            <div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <h2 id="bannerTitle" style="font-size:20px; font-weight:800; margin:0; letter-spacing:-0.02em;">Paket #RP-0000</h2>
                    <span id="bannerStatusBadge" class="badge-pill gray" style="font-size:11px;">Draft</span>
                </div>
                <div id="bannerSubinfo" style="font-size:12px; color:rgba(255,255,255,0.85); margin-top:4px;">
                    Periode: -
                </div>
            </div>
            
            {{-- Tombol Tutup Tunggal --}}
            <button type="button" class="modal-close-icon-btn" onclick="tutupDetailBannerDirect()" title="Tutup">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Scrollable Content Body --}}
        <div class="modal-scrollable-body">
            {{-- 4 Metric Strip --}}
            <div class="banner-metric-strip">
                <div class="banner-metric-box">
                    <div class="label">Total Rencana Potongan</div>
                    <div class="value" id="bannerTotalRencana">Rp 0</div>
                </div>
                <div class="banner-metric-box">
                    <div class="label">Dana Realisasi Dipotong</div>
                    <div class="value" id="bannerTotalRealisasi" style="color:#166534;">-</div>
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

            {{-- FORM CATAT HASIL TERINTEGRASI DI DALAM MODAL BANNER --}}
            <form id="formCatatHasilModal" method="POST" action="" enctype="multipart/form-data">
                @csrf

                {{-- Section Konfigurasi Penyerahan (Khusus saat dikirim/draft) --}}
                <div id="modalFormEksekusiSection" style="display:none; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:16px 20px; margin-bottom:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid #E2E8F0;">
                        <div>
                            <h3 style="font-size:14px; font-weight:800; color:var(--text-heading); margin:0 0 2px 0;">
                                Serah Terima Dana ke Bank Mini
                            </h3>
                            <div style="font-size:12px; color:var(--text-muted);">
                                Total dana diserahkan: <strong id="modalDispTotalRealisasi" style="color:#166534; font-size:13px;">Rp 0</strong>
                            </div>
                        </div>

                        {{-- Segmented Pills Metode Serah Terima --}}
                        <div style="display:inline-flex; align-items:center; gap:8px;">
                            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em;">Metode Penyerahan:</span>
                            <div style="display:flex; gap:6px;">
                                <button type="button" id="modalBtnMetodeTunai" class="modal-method-btn active-tunai" onclick="pilihMetodeModal('tunai')">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                                    <span>Setor Tunai (Cash)</span>
                                </button>
                                <button type="button" id="modalBtnMetodeTransfer" class="modal-method-btn" onclick="pilihMetodeModal('transfer')">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
                                    <span>Transfer ke Bank Bendahara</span>
                                </button>
                            </div>
                            <input type="hidden" name="metode_penyerahan" id="modalInputMetode" value="tunai">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 200px 1fr; gap:14px;" class="banner-form-grid">
                        <div>
                            <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.04em; margin-bottom:5px;">Tanggal Eksekusi *</label>
                            <input type="date" name="tgl_potongan" id="modalTglPotongan" value="{{ date('Y-m-d') }}" class="form-input" style="width:100%; font-size:13px; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1;" required>
                        </div>

                        <div>
                            <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.04em; margin-bottom:5px;">Catatan Serah Terima (Opsional)</label>
                            <input type="text" name="catatan_sekolah" id="modalInputCatatan" placeholder="Misal: Seluruh potongan payroll berhasil diserahkan ke Bank Mini" class="form-input" style="width:100%; font-size:13px; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1;">
                        </div>
                    </div>

                    {{-- Unggah Bukti Transfer Bank (Dinamis jika Transfer dipilih) --}}
                    <div id="modalBoxBuktiTransfer" style="display:none; margin-top:14px; background:#EFF6FF; border:1px dashed #60A5FA; padding:12px 16px; border-radius:8px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <div>
                                <label style="display:block; font-size:12px; font-weight:700; color:#0369A1; margin-bottom:2px;">
                                    Unggah Bukti Transfer Bank (Opsional)
                                </label>
                                <div style="font-size:11px; color:#0369A1;">Foto resi ATM, tangkapan layar m-banking, atau mutasi debet (maks 5 MB).</div>
                            </div>
                            <div>
                                <input type="file" name="bukti_transfer" accept="image/jpeg,image/png,image/jpg,application/pdf" class="form-input" style="background:#FFFFFF; padding:6px 10px; font-size:12px; border-radius:6px;">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Area Read-Only saat paket menunggu_verifikasi atau selesai --}}
                <div id="modalReadOnlySection" style="display:none;">
                    {{-- Catatan Sekolah jika ada --}}
                    <div id="bannerCatatanSekolahBox" style="display:none; padding:12px 18px; background:#F8FAFC; border-radius:10px; margin-bottom:14px; font-size:13px; color:#334155; border:1px solid #E2E8F0; border-left:4px solid var(--brand-primary);">
                        <strong>Catatan Serah Terima:</strong> <span id="bannerCatatanSekolahText">-</span>
                    </div>

                    {{-- Bukti Transfer Bank jika diunggah --}}
                    <div id="bannerBuktiTransferBox" style="display:none; padding:12px 18px; background:#EFF6FF; border-radius:10px; margin-bottom:14px; font-size:13px; color:#1E40AF; border:1px solid #BFDBFE; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span><strong>Bukti Transfer Terlampir:</strong> Berkas struk / mutasi transfer telah diunggah ke sistem.</span>
                        </div>
                        <a id="bannerBuktiTransferLink" href="#" target="_blank" class="btn-ui btn-ui-sm" style="background:#2563EB; color:#FFFFFF; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-weight:700; border-radius:6px; padding:6px 14px;">
                            <span>Lihat Bukti Transfer</span>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Tabel Rincian Pegawai dalam Paket --}}
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                        <h4 style="font-size:13px; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.04em; margin:0;">Daftar Pegawai dalam Paket</h4>
                        <div style="width:220px;">
                            <input type="text" class="form-input" placeholder="Cari nama guru / unit..." oninput="filterTableRows(this, 'tablePegawaiPotongan')" style="padding:4px 10px; font-size:12px; width:100%; border-radius:6px;">
                        </div>
                    </div>
                    <div class="table-responsive" style="border:1px solid #E2E8F0; border-radius:10px; overflow-x:auto;">
                        <table class="financial-table" style="min-width: 820px;">
                        <table class="financial-table" id="tablePegawaiPotongan" style="min-width: 820px;">
                            <thead id="bannerPegawaiTableHead">
                                {{-- Diisi secara dinamis sesuai mode (interactive vs read-only) --}}
                            </thead>
                            <tbody id="bannerPegawaiTableBody">
                                {{-- Diisi secara dinamis lewat JavaScript --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        {{-- Footer Modal Bar (Tombol Utama di Pojok Kanan Bawah) --}}
        <div class="modal-footer-bar">
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" class="btn-ui btn-ui-outline" onclick="tutupDetailBannerDirect()">
                    <span>Tutup</span>
                </button>
                <a id="bannerDownloadCsvLink" href="#" class="btn-ui btn-ui-outline" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Unduh CSV Bank</span>
                </a>
                <a id="bannerBastBtn" href="#" target="_blank" class="btn-ui btn-ui-outline" style="display:none; text-decoration:none; align-items:center; gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Cetak BAST</span>
                </a>
            </div>

            <div>
                {{-- Tombol Submit Catat Hasil di Pojok Kanan Bawah --}}
                <button type="submit" 
                        form="formCatatHasilModal" 
                        id="bannerBtnSubmitHasil" 
                        class="btn-ui btn-ui-primary" 
                        style="display:none; gap:6px; font-weight:700; padding:10px 20px; font-size:13px;"
                        onclick="return confirm('Simpan hasil pemotongan tunjangan pegawai dan teruskan ke Bendahara Bank Mini?')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Simpan & Serahkan ke Bank Mini</span>
                </button>

                {{-- Status Pills --}}
                <span id="bannerStatusWaitingPill" class="badge-pill amber" style="display:none; font-size:12px; padding:6px 14px; font-weight:700;">
                    Menunggu Konfirmasi Bank Mini
                </span>
                <span id="bannerStatusSelesaiPill" class="badge-pill green" style="display:none; font-size:12px; padding:6px 14px; font-weight:700;">
                    Selesai Dibukukan
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Data JSON Rincian Paket untuk Floating Popup Modal --}}
<script>
const dataPaket = {
@foreach ($rencanaList as $r)
    "{{ $r->rencana_potongan_id }}": {
        "id": "{{ $r->rencana_potongan_id }}",
        "idFormatted": "#RP-{{ str_pad($r->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}",
        "bulan": "{{ $r->bulan }}",
        "tahun": "{{ $r->tahun }}",
        "periodeFormatted": "{{ \Carbon\Carbon::createFromDate($r->tahun, $r->bulan, 1)->translatedFormat('F Y') }}",
        "penyusun": "{{ $r->nama_pembuat ?? 'Bendahara Bank Mini' }}",
        "totalRencana": {{ (float) $r->total_potongan }},
        "totalRealisasi": {{ (float) $r->total_realisasi }},
        "status": "{{ $r->status_potongan }}",
        "metode": "{{ $r->metode_penyerahan ?? '-' }}",
        "catatan": {!! json_encode($r->catatan_sekolah) !!},
        "buktiTransfer": "{{ !empty($r->bukti_transfer) ? asset('storage/' . $r->bukti_transfer) : '' }}",
        "simpanUrl": "{{ route('bendahara.potongan.simpan', $r->rencana_potongan_id) }}",
        "downloadUrl": "{{ route('bendahara.potongan.download', $r->rencana_potongan_id) }}",
        "bastUrl": "{{ in_array($r->status_potongan, ['menunggu_verifikasi', 'selesai']) ? route('bendahara.potongan.bast', $r->rencana_potongan_id) : '' }}",
        "items": [
            @if (isset($rincianPerPaket[$r->rencana_potongan_id]))
                @foreach ($rincianPerPaket[$r->rencana_potongan_id] as $item)
                {
                    "detailId": {{ $item->detail_potongan_id }},
                    "nama": {!! json_encode($item->nama_nasabah) !!},
                    "unit": {!! json_encode($item->kelas_unit ?? 'Pegawai') !!},
                    "angsuranKe": {{ $item->angsuran_ke }},
                    "tenor": {{ $item->tenor ?? 6 }},
                    "pokok": {{ (float) $item->pokok }},
                    "bunga": {{ (float) $item->bunga }},
                    "nominalRencana": {{ (float) $item->nominal_rencana }},
                    "nominalRealisasi": {{ (float) ($item->nominal_realisasi ?? 0) }},
                    "hasilPotongan": "{{ $item->hasil_potongan ?? 'pending' }}",
                    "statusAngsuran": "{{ $item->status_angsuran }}",
                    "keterangan": {!! json_encode($item->keterangan ?? '') !!}
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

function pilihMetodeModal(metode) {
    const btnTunai = document.getElementById('modalBtnMetodeTunai');
    const btnTransfer = document.getElementById('modalBtnMetodeTransfer');
    const inputMetode = document.getElementById('modalInputMetode');
    const boxUpload = document.getElementById('modalBoxBuktiTransfer');
    const metricMetode = document.getElementById('bannerMetode');

    inputMetode.value = metode;

    if (metode === 'transfer') {
        btnTransfer.className = 'modal-method-btn active-transfer';
        btnTunai.className = 'modal-method-btn';
        boxUpload.style.display = 'block';
        if (metricMetode) metricMetode.innerText = 'TRANSFER BANK';
    } else {
        btnTunai.className = 'modal-method-btn active-tunai';
        btnTransfer.className = 'modal-method-btn';
        boxUpload.style.display = 'none';
        if (metricMetode) metricMetode.innerText = 'SETOR TUNAI FISIK';
    }
}

function toggleHasilModal(detailId, status) {
    const lblBerhasil = document.getElementById('lbl-berhasil-' + detailId);
    const lblGagal = document.getElementById('lbl-gagal-' + detailId);
    const inputKet = document.getElementById('ket-' + detailId);
    const rowEl = document.getElementById('row-detail-' + detailId);
    const p = dataPaket[activePaketId];

    if (status === 'berhasil') {
        lblBerhasil.classList.add('active-berhasil');
        lblGagal.classList.remove('active-gagal');
        if (rowEl) rowEl.classList.remove('row-failed');
    } else {
        lblGagal.classList.add('active-gagal');
        lblBerhasil.classList.remove('active-berhasil');
        if (rowEl) rowEl.classList.add('row-failed');
        if (inputKet && inputKet.value.trim() === '') {
            inputKet.focus();
        }
    }

    // Hitung ulang live realisasi
    if (!p) return;
    let totalSukses = 0;
    p.items.forEach(item => {
        const radioBerhasil = document.querySelector(`input[name="hasil[${item.detailId}]"][value="berhasil"]`);
        if (radioBerhasil && radioBerhasil.checked) {
            totalSukses += item.nominalRencana;
        }
    });

    const realisasiEl = document.getElementById('bannerTotalRealisasi');
    if (realisasiEl) {
        realisasiEl.innerText = formatRupiah(totalSukses);
    }
    const modalDispEl = document.getElementById('modalDispTotalRealisasi');
    if (modalDispEl) {
        modalDispEl.innerText = formatRupiah(totalSukses);
    }
}

function bukaDetailBanner(rencanaId) {
    const banner = document.getElementById('paketDetailBanner');
    const p = dataPaket[rencanaId];
    if (!p) return;

    activePaketId = rencanaId;

    // Reset visual tombol aktif
    document.querySelectorAll('.action-icon-btn').forEach(btn => btn.classList.remove('btn-active'));
    const currentBtn = document.getElementById('btn-eye-' + rencanaId);
    if (currentBtn) currentBtn.classList.add('btn-active');

    // Header info
    document.getElementById('bannerTitle').innerText = 'Paket ' + p.idFormatted;
    document.getElementById('bannerSubinfo').innerText = 'Periode: ' + p.periodeFormatted + ' • Diterbitkan oleh ' + (p.penyusun || 'Bendahara Bank Mini');

    // Status Badge
    const badgeEl = document.getElementById('bannerStatusBadge');
    if (p.status === 'selesai') {
        badgeEl.className = 'badge-pill green';
        badgeEl.innerText = 'Selesai Dibukukan';
    } else if (p.status === 'menunggu_verifikasi') {
        badgeEl.className = 'badge-pill amber';
        badgeEl.innerText = 'Menunggu Konfirmasi Bank Mini';
    } else if (p.status === 'dikirim') {
        badgeEl.className = 'badge-pill blue';
        badgeEl.innerText = 'Siap Dipotong Payroll';
    } else {
        badgeEl.className = 'badge-pill gray';
        badgeEl.innerText = 'Draft';
    }

    // Metric Values
    document.getElementById('bannerTotalRencana').innerText = formatRupiah(p.totalRencana);
    const nominalTampil = (p.totalRealisasi > 0) ? p.totalRealisasi : p.totalRencana;
    document.getElementById('bannerTotalRealisasi').innerText = formatRupiah(nominalTampil);
    const modalDispEl = document.getElementById('modalDispTotalRealisasi');
    if (modalDispEl) {
        modalDispEl.innerText = formatRupiah(nominalTampil);
    }
    document.getElementById('bannerJumlahPegawai').innerText = p.items.length + ' Orang';
    document.getElementById('bannerMetode').innerText = (p.metode && p.metode !== '-') ? p.metode.toUpperCase() : 'SETOR TUNAI FISIK';

    // Form Action URL
    const formEl = document.getElementById('formCatatHasilModal');
    formEl.action = p.simpanUrl;

    // Tampilkan / Sembunyikan Section Eksekusi vs Read-Only
    const formSection = document.getElementById('modalFormEksekusiSection');
    const readOnlySection = document.getElementById('modalReadOnlySection');
    const btnSubmitHasil = document.getElementById('bannerBtnSubmitHasil');
    const pillWaiting = document.getElementById('bannerStatusWaitingPill');
    const pillSelesai = document.getElementById('bannerStatusSelesaiPill');

    const isInteractive = (p.status === 'dikirim' || p.status === 'draft');

    if (isInteractive) {
        formSection.style.display = 'block';
        readOnlySection.style.display = 'none';
        btnSubmitHasil.style.display = 'inline-flex';
        pillWaiting.style.display = 'none';
        pillSelesai.style.display = 'none';
        pilihMetodeModal('tunai');
        document.getElementById('modalInputCatatan').value = '';
    } else {
        formSection.style.display = 'none';
        readOnlySection.style.display = 'block';
        btnSubmitHasil.style.display = 'none';

        if (p.status === 'menunggu_verifikasi') {
            pillWaiting.style.display = 'inline-flex';
            pillSelesai.style.display = 'none';
        } else {
            pillWaiting.style.display = 'none';
            pillSelesai.style.display = 'inline-flex';
        }

        // Catatan Sekolah jika ada
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
        if (p.buktiTransfer && p.buktiTransfer.trim() !== '') {
            buktiLink.href = p.buktiTransfer;
            buktiBox.style.display = 'flex';
        } else {
            buktiBox.style.display = 'none';
        }
    }

    // Download CSV Link
    const downloadCsvLink = document.getElementById('bannerDownloadCsvLink');
    if (downloadCsvLink) {
        downloadCsvLink.href = p.downloadUrl;
    }

    // BAST Button
    const bastBtn = document.getElementById('bannerBastBtn');
    if (p.bastUrl && p.bastUrl.trim() !== '') {
        bastBtn.href = p.bastUrl;
        bastBtn.style.display = 'inline-flex';
    } else {
        bastBtn.style.display = 'none';
    }

    // Render Tabel Pegawai dalam Paket
    const thead = document.getElementById('bannerPegawaiTableHead');
    const tbody = document.getElementById('bannerPegawaiTableBody');
    tbody.innerHTML = '';

    if (isInteractive) {
        thead.innerHTML = `
            <tr>
                <th style="width:36px; text-align:center;">No</th>
                <th>Nama Pegawai / Unit</th>
                <th style="text-align:center; white-space:nowrap;">Angsuran</th>
                <th style="text-align:right; white-space:nowrap;">Pokok</th>
                <th style="text-align:right; white-space:nowrap;">Jasa 2%</th>
                <th style="text-align:right; white-space:nowrap;">Total Tagihan</th>
                <th style="text-align:center; white-space:nowrap; width:170px;">Hasil Potongan Gaji</th>
                <th style="white-space:nowrap; width:200px;">Keterangan</th>
            </tr>
        `;
    } else {
        thead.innerHTML = `
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
        `;
    }

    if (!p.items || p.items.length === 0) {
        const colCount = isInteractive ? 8 : 9;
        tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center; padding:18px; color:var(--text-muted);">Tidak ada pegawai terdaftar dalam paket ini.</td></tr>`;
    } else {
        p.items.forEach((item, index) => {
            const tr = document.createElement('tr');

            if (isInteractive) {
                // Interactive Mode (Input Hasil Potongan)
                tr.id = 'row-detail-' + item.detailId;
                tr.innerHTML = `
                    <td style="text-align:center; font-weight:600; color:var(--text-muted);">${index + 1}</td>
                    <td>
                        <div style="font-weight:700; color:var(--text-heading); font-size:13px;">${item.nama}</div>
                        <div style="font-size:11px; color:var(--text-muted);">${item.unit}</div>
                    </td>
                    <td style="text-align:center; white-space:nowrap;">
                        <span class="badge-pill blue" style="font-size:11px;">Ke-${item.angsuranKe}/${item.tenor}</span>
                    </td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap;">${formatRupiah(item.pokok)}</td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap;">${formatRupiah(item.bunga)}</td>
                    <td style="text-align:right; font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap;">${formatRupiah(item.nominalRencana)}</td>
                    <td style="text-align:center; white-space:nowrap;">
                        <div class="modal-toggle-pill-group">
                            <label class="modal-toggle-label active-berhasil" id="lbl-berhasil-${item.detailId}">
                                <input type="radio" name="hasil[${item.detailId}]" value="berhasil" checked onchange="toggleHasilModal(${item.detailId}, 'berhasil')">
                                <span>Berhasil</span>
                            </label>
                            <label class="modal-toggle-label" id="lbl-gagal-${item.detailId}">
                                <input type="radio" name="hasil[${item.detailId}]" value="gagal" onchange="toggleHasilModal(${item.detailId}, 'gagal')">
                                <span>Gagal</span>
                            </label>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="keterangan[${item.detailId}]" id="ket-${item.detailId}" placeholder="Opsional (misal: Cuti)" class="form-input" style="padding:6px 10px; font-size:12px; border-radius:6px; border:1px solid #CBD5E1; width:100%;">
                    </td>
                `;
            } else {
                // Read-Only Mode
                let statusBadge = '<span class="badge-pill gray">Pending</span>';
                if (item.hasilPotongan === 'berhasil') {
                    statusBadge = '<span class="badge-pill green" style="font-weight:700;">Berhasil</span>';
                } else if (item.hasilPotongan === 'gagal') {
                    statusBadge = '<span class="badge-pill red" style="font-weight:700;">Gagal</span>';
                }

                const realisasiText = (item.nominalRealisasi > 0) 
                    ? ('<strong style="color:#166534; font-weight:800; white-space:nowrap;">' + formatRupiah(item.nominalRealisasi) + '</strong>') 
                    : '<span style="color:var(--text-muted); font-size:12px;">-</span>';

                tr.innerHTML = `
                    <td style="text-align:center; font-weight:600; color:var(--text-muted);">${index + 1}</td>
                    <td>
                        <div style="font-weight:700; color:var(--text-heading); font-size:13px;">${item.nama}</div>
                        <div style="font-size:11px; color:var(--text-muted);">${item.unit}</div>
                    </td>
                    <td style="text-align:center; white-space:nowrap;">
                        <span class="badge-pill blue" style="font-size:11px;">Ke-${item.angsuranKe}/${item.tenor}</span>
                    </td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap;">${formatRupiah(item.pokok)}</td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap;">${formatRupiah(item.bunga)}</td>
                    <td style="text-align:right; font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap;">${formatRupiah(item.nominalRencana)}</td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap;">${realisasiText}</td>
                    <td style="text-align:center; white-space:nowrap;">${statusBadge}</td>
                    <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;">${item.keterangan || '-'}</td>
                `;
            }

            tbody.appendChild(tr);
        });
    }

    // Tampilkan modal
    banner.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function tutupDetailBanner(event) {
    if (event.target.id === 'paketDetailBanner') {
        tutupDetailBannerDirect();
    }
}

function tutupDetailBannerDirect() {
    const banner = document.getElementById('paketDetailBanner');
    if (banner) banner.style.display = 'none';
    document.body.style.overflow = 'auto';

    document.querySelectorAll('.action-icon-btn').forEach(btn => btn.classList.remove('btn-active'));
    activePaketId = null;
}

// Support tombol ESC di keyboard untuk menutup modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        tutupDetailBannerDirect();
    }
});

// Auto-open jika ada parameter ?open=xx di URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const openId = urlParams.get('open');
    if (openId && dataPaket[openId]) {
        bukaDetailBanner(openId);
    }
});
</script>

@endsection
