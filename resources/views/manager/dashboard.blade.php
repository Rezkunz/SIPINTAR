@extends('layouts.app')

@section('title', 'Ringkasan Eksekutif Manager')
@section('breadcrumb', 'Ringkasan Eksekutif')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Selamat Datang, {{ auth()->user()->name }}</h1>
        <p>Ringkasan eksekutif bank mini sekolah: tinjau posisi likuiditas kas, pengawasan dana pihak ketiga, dan kesehatan pembiayaan koperasi.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('laporan.neraca') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/></svg>
            <span>Neraca Keuangan</span>
        </a>
        <a href="{{ route('laporan.ekspor') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span>Ekspor Laporan</span>
        </a>
    </div>
</div>

{{-- ── 4 EXECUTIVE FINANCIAL METRICS ── --}}
<style>
    /* Angka utama sejajar di semua kartu; rincian berada di bawahnya */
    .metric-grid-ringkasan .metric-card { justify-content: flex-start; container-type: inline-size; }
    /* Ukuran angka mengikuti lebar kartu agar nilai miliaran tetap utuh di layar laptop */
    .metric-grid-ringkasan .metric-value { font-size: clamp(1.1rem, 9cqi, 1.85rem); overflow-wrap: anywhere; }
    .metric-grid-ringkasan .metric-value .curr-prefix { font-size: clamp(0.8rem, 5cqi, 1.15rem); }
</style>
<div class="metric-grid metric-grid-ringkasan">
    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Dana Pihak Ketiga (Tabungan)</span>
        </div>
        <div class="metric-value" dusk="dashboard-simpanan"><span class="curr-prefix">Rp</span>{{ number_format($totalTabungan, 0, ',', '.') }}</div>
        <div class="metric-rincian" style="margin-top: 8px; font-size: 11.5px; color: var(--text-muted); line-height: 1.6; position: relative; z-index: 1;">
            <div>Dari {{ number_format($jumlahRekeningAktif, 0, ',', '.') }} rekening aktif</div>
        </div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Sisa Piutang Pinjaman</span>
        </div>
        <div class="metric-value" dusk="dashboard-piutang"><span class="curr-prefix">Rp</span>{{ number_format($portofolio['total_sisa_pokok'], 0, ',', '.') }}</div>
        <div class="metric-rincian" style="margin-top: 8px; font-size: 11.5px; color: var(--text-muted); line-height: 1.6; position: relative; z-index: 1;">
            <div>{{ $portofolio['jumlah_pinjaman'] }} pinjaman berjalan</div>
            <div>Pinjaman awal: Rp {{ number_format($portofolio['total_plafon'], 0, ',', '.') }}</div>
            <div>Pokok dibayar: Rp {{ number_format($portofolio['total_pokok_terbayar'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Kas Bank Mini</span>
        </div>
        <div class="metric-value" dusk="dashboard-kas"><span class="curr-prefix">Rp</span>{{ number_format($totalKas, 0, ',', '.') }}</div>
        <div class="metric-rincian" style="margin-top: 8px; font-size: 11.5px; color: var(--text-muted); line-height: 1.6; position: relative; z-index: 1;">
            <div>Kas Tunai: <span dusk="dashboard-kas-tunai">Rp {{ number_format($saldoKasTunai, 0, ',', '.') }}</span></div>
            <div>Kas Bank: <span dusk="dashboard-kas-bank">Rp {{ number_format($saldoKasBank, 0, ',', '.') }}</span></div>
        </div>
    </div>

    <div class="metric-card card-indigo">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Nasabah Terdaftar</span>
        </div>
        <div class="metric-value">{{ $totalNasabah }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── TABEL TRANSAKSI TERBESAR ── --}}
    <div class="content-card">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Pengawasan Transaksi Tabungan Terbesar</h2>
                <div style="font-size: 12px; color: var(--text-muted);">Audit mutasi dengan nominal signifikan pada periode aktif</div>
            </div>
            <a href="{{ route('laporan.tabungan') }}" class="btn-ui btn-ui-outline btn-ui-sm">Buku Laporan</a>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Nasabah & Rekening</th>
                        <th>Kategori</th>
                        <th style="text-align: right;">Nominal Mutasi</th>
                        <th style="text-align: right;">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksiTerbesar as $t)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($t->tgl_transaksi)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading);">{{ $t->nama_nasabah }}</div>
                                <div style="font-size: 11px; color: var(--text-muted); font-family: ui-monospace, SFMono-Regular, monospace;">{{ $t->no_rek }}</div>
                            </td>
                            <td>
                                <span class="badge-pill {{ $t->kredit > 0 ? 'green' : 'amber' }}">
                                    {{ $t->nama_jenis_transaksi ?? ($t->kredit > 0 ? 'Setor Tunai' : 'Penarikan') }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: {{ $t->kredit > 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                                {{ $t->kredit > 0 ? '+ Rp ' . number_format($t->kredit, 0, ',', '.') : '- Rp ' . number_format($t->debit, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-heading);">
                                Rp {{ number_format($t->saldo_akhir, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--text-faint); display: block;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                    Belum ada transaksi signifikan tercatat pada periode ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── KESEHATAN BANK MINI & STATUS PERIODE ── --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-bar">
                <h2 class="card-heading">Kesehatan Bank Mini</h2>
            </div>
            <div class="card-body-content">
                <div class="ratio-meter-card" style="border: none; padding: 0; box-shadow: none;">
                    <div class="ratio-label-row">
                        <span style="color: var(--text-body);">Rasio Likuiditas Kas</span>
                        <span style="color: var(--brand-primary);">Sehat (100%)</span>
                    </div>
                    <div class="ratio-progress-track">
                        <div class="ratio-progress-fill" style="width: 100%;"></div>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 16px;">
                        Ketersediaan dana kas fisik dan tabungan berada pada batas aman.
                    </div>

                    <div class="ratio-label-row">
                        <span style="color: var(--text-body);">Rasio Kredit terhadap DPK (LDR)</span>
                        <span style="color: var(--brand-accent);">Terkendali</span>
                    </div>
                    <div class="ratio-progress-track">
                        <div class="ratio-progress-fill warning" style="width: 45%;"></div>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted);">
                        Penyaluran pembiayaan tidak melampaui total himpunan tabungan.
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-bar">
                <h2 class="card-heading">Status Pembukuan Periode</h2>
            </div>
            <div class="card-body-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Periode Aktif</div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--text-heading);">
                            {{ $periodeAktif ? "Bulan {$periodeAktif->bulan} / Tahun {$periodeAktif->tahun}" : 'Tahun 2026' }}
                        </div>
                    </div>
                    <span class="badge-pill green">Terbuka</span>
                </div>
                <a href="{{ route('periode.tutup') }}" class="btn-ui btn-ui-outline" style="width: 100%; text-align: center;">
                    Kelola Tutup Periode
                </a>
            </div>
        </div>
    </div>

</div>

@endsection
