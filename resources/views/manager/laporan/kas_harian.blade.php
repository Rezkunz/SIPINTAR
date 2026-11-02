@extends('layouts.app')

@section('title', 'Buku Kas Harian Tabungan')
@section('breadcrumb', 'Laporan Tabungan')

@section('content')

@php
    $rp = fn ($n) => number_format($n, 0, ',', '.');
    $nilaiAtauStrip = fn ($n) => $n != 0 ? number_format($n, 0, ',', '.') : '-';
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Buku Kas Harian Tabungan</h1>
        <p>Arus kas tunai dari setoran dan penarikan tabungan siswa serta guru/karyawan per hari, mulai dari kas awal hingga kas akhir. Klik sebuah tanggal untuk melihat rincian transaksinya.</p>
    </div>
</div>

@include('manager.laporan.partials.toolbar_tabungan', ['routeLaporan' => 'laporan.tabungan.kas', 'routeCsv' => 'laporan.tabungan.kas.csv', 'filterNasabah' => false])

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Kas Awal Bulan</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($kasAwalBulan) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Posisi kas per 1 {{ $awalBulan->translatedFormat('F Y') }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Total Penerimaan</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($total['total_masuk']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Setoran siswa &amp; guru bulan ini</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Pengeluaran</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($total['total_keluar']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Penarikan siswa &amp; guru bulan ini</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Kas Akhir Bulan</span>
        </div>
        <div class="metric-value" style="color: {{ $kasAkhirBulan < 0 ? '#DC2626' : 'inherit' }};"><span class="curr-prefix">Rp</span>{{ $rp($kasAkhirBulan) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">{{ $total['transaksi'] }} transaksi dalam {{ $hari->count() }} hari aktif</div>
    </div>
</div>

{{-- ── TABEL BUKU KAS HARIAN ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Buku Kas Harian</h2>
            <div style="font-size:12px; color:var(--text-muted);">Bulan {{ $awalBulan->translatedFormat('F Y') }} &bull; hanya menampilkan hari yang ada transaksinya</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableBukuKas" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th rowspan="2" style="width:44px; vertical-align:bottom;">No</th>
                    <th rowspan="2" style="min-width:130px; vertical-align:bottom;">Tanggal</th>
                    <th rowspan="2" style="text-align:right; min-width:130px; vertical-align:bottom;">Kas Awal</th>
                    <th colspan="2" style="text-align:center; background:var(--brand-primary-light); color:var(--brand-primary);">Tab. Siswa</th>
                    <th colspan="2" style="text-align:center; background:var(--brand-accent-light); color:var(--brand-accent-hover);">Tab. Guru / Karyawan</th>
                    <th colspan="2" style="text-align:center; background:var(--surface-subtle);">Jumlah</th>
                    <th rowspan="2" style="text-align:right; min-width:130px; vertical-align:bottom;">Kas Akhir</th>
                    <th rowspan="2" style="width:90px; vertical-align:bottom;"></th>
                </tr>
                <tr>
                    <th style="text-align:right; min-width:110px; background:var(--brand-primary-light);">Penerimaan</th>
                    <th style="text-align:right; min-width:110px; background:var(--brand-primary-light);">Pengeluaran</th>
                    <th style="text-align:right; min-width:110px; background:var(--brand-accent-light);">Penerimaan</th>
                    <th style="text-align:right; min-width:110px; background:var(--brand-accent-light);">Pengeluaran</th>
                    <th style="text-align:right; min-width:110px; background:var(--surface-subtle);">Penerimaan</th>
                    <th style="text-align:right; min-width:110px; background:var(--surface-subtle);">Pengeluaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hari as $i => $h)
                    @php $terpilih = $tanggalTerpilih && $tanggalTerpilih->isSameDay($h->tanggal); @endphp
                    <tr style="{{ $terpilih ? 'background:var(--brand-accent-light);' : '' }}">
                        <td style="color:var(--text-muted);">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $h->tanggal->format('d/m/Y') }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $h->tanggal->translatedFormat('l') }} &bull; {{ $h->jumlah_transaksi }} transaksi</div>
                        </td>
                        <td style="text-align:right; white-space:nowrap; color:var(--text-body);">{{ $rp($h->kas_awal) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46; font-weight:{{ $h->siswa_masuk ? '700' : '400' }};">{{ $nilaiAtauStrip($h->siswa_masuk) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#DC2626; font-weight:{{ $h->siswa_keluar ? '700' : '400' }};">{{ $nilaiAtauStrip($h->siswa_keluar) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46; font-weight:{{ $h->guru_masuk ? '700' : '400' }};">{{ $nilaiAtauStrip($h->guru_masuk) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#DC2626; font-weight:{{ $h->guru_keluar ? '700' : '400' }};">{{ $nilaiAtauStrip($h->guru_keluar) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:700; color:var(--text-heading);">{{ $rp($h->total_masuk) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:700; color:var(--text-heading);">{{ $rp($h->total_keluar) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:{{ $h->kas_akhir < 0 ? '#DC2626' : 'var(--brand-primary)' }};">{{ $rp($h->kas_akhir) }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('laporan.tabungan.kas', ['bulan' => $bulan, 'tahun' => $tahun, 'tanggal' => $h->tanggal->toDateString()]) }}#rincian"
                               class="btn-ui btn-ui-sm {{ $terpilih ? 'btn-ui-primary' : 'btn-ui-outline' }}" style="font-size:11.5px; padding:4px 10px; text-decoration:none;">
                                Rincian
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                Belum ada transaksi setoran atau penarikan tabungan pada {{ $awalBulan->translatedFormat('F Y') }}.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($hari->isNotEmpty())
                <tfoot>
                    <tr style="background:#F8FAFC; border-top:2px solid #CBD5E1;">
                        <td></td>
                        <td style="font-weight:800; color:var(--text-heading);">JUMLAH</td>
                        <td style="text-align:right; white-space:nowrap; color:var(--text-muted);">{{ $rp($kasAwalBulan) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#0D5C46;">{{ $rp($total['siswa_masuk']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#DC2626;">{{ $rp($total['siswa_keluar']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#0D5C46;">{{ $rp($total['guru_masuk']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#DC2626;">{{ $rp($total['guru_keluar']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--text-heading);">{{ $rp($total['total_masuk']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--text-heading);">{{ $rp($total['total_keluar']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-primary);">{{ $rp($kasAkhirBulan) }}</td>
                        <td></td>
                    </tr>
                    <tr style="background:#F8FAFC;">
                        <td></td>
                        <td colspan="2" style="font-size:11.5px; color:var(--text-muted);">Selisih (penerimaan &minus; pengeluaran)</td>
                        <td colspan="2" style="text-align:center; white-space:nowrap; font-weight:700; color:{{ $total['siswa_masuk'] - $total['siswa_keluar'] < 0 ? '#DC2626' : '#0D5C46' }};">{{ $rp($total['siswa_masuk'] - $total['siswa_keluar']) }}</td>
                        <td colspan="2" style="text-align:center; white-space:nowrap; font-weight:700; color:{{ $total['guru_masuk'] - $total['guru_keluar'] < 0 ? '#DC2626' : '#0D5C46' }};">{{ $rp($total['guru_masuk'] - $total['guru_keluar']) }}</td>
                        <td colspan="2" style="text-align:center; white-space:nowrap; font-weight:700; color:{{ $total['total_masuk'] - $total['total_keluar'] < 0 ? '#DC2626' : '#0D5C46' }};">{{ $rp($total['total_masuk'] - $total['total_keluar']) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── RINCIAN TRANSAKSI TANGGAL TERPILIH ── --}}
@if ($tanggalTerpilih)
    <div class="content-card" id="rincian">
        <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
            <div>
                <h2 class="card-heading">Rincian Transaksi {{ $tanggalTerpilih->translatedFormat('l, d F Y') }}</h2>
                <div style="font-size:12px; color:var(--text-muted);">Debet = penarikan (kas keluar) &bull; Kredit = setoran (kas masuk)</div>
            </div>
            <a href="{{ route('laporan.tabungan.kas', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn-ui btn-ui-sm btn-ui-outline" style="text-decoration:none;">
                Tutup Rincian
            </a>
        </div>

        <div class="table-responsive">
            <table class="financial-table" style="font-size:12.5px;">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:44px; vertical-align:bottom;">No</th>
                        <th rowspan="2" style="width:70px; vertical-align:bottom;">Jam</th>
                        <th rowspan="2" style="min-width:140px; vertical-align:bottom;">No Rekening</th>
                        <th rowspan="2" style="min-width:200px; vertical-align:bottom;">Nama</th>
                        <th colspan="2" style="text-align:center; background:var(--brand-primary-light); color:var(--brand-primary);">Tabungan Pelajar</th>
                        <th colspan="2" style="text-align:center; background:var(--brand-accent-light); color:var(--brand-accent-hover);">Tab. Guru / Karyawan</th>
                    </tr>
                    <tr>
                        <th style="text-align:right; min-width:110px; background:var(--brand-primary-light);">Debet</th>
                        <th style="text-align:right; min-width:110px; background:var(--brand-primary-light);">Kredit</th>
                        <th style="text-align:right; min-width:110px; background:var(--brand-accent-light);">Debet</th>
                        <th style="text-align:right; min-width:110px; background:var(--brand-accent-light);">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rincian as $i => $r)
                        @php $isGuru = $r->jenis_nasabah === 'pegawai'; @endphp
                        <tr>
                            <td style="color:var(--text-muted);">{{ $rincian->firstItem() + $i }}</td>
                            <td style="color:var(--text-muted);">{{ \Carbon\Carbon::parse($r->tgl_transaksi)->format('H:i') }}</td>
                            <td style="font-family:monospace; font-size:12.5px;">{{ $r->no_rek }}</td>
                            <td style="font-weight:700; color:var(--text-heading);">{{ $r->nama_nasabah }}</td>
                            <td style="text-align:right; white-space:nowrap; color:#DC2626; font-weight:700;">{{ $isGuru ? '' : ($r->debit > 0 ? $rp($r->debit) : '') }}</td>
                            <td style="text-align:right; white-space:nowrap; color:#0D5C46; font-weight:700;">{{ $isGuru ? '' : ($r->kredit > 0 ? $rp($r->kredit) : '') }}</td>
                            <td style="text-align:right; white-space:nowrap; color:#DC2626; font-weight:700;">{{ $isGuru ? ($r->debit > 0 ? $rp($r->debit) : '') : '' }}</td>
                            <td style="text-align:right; white-space:nowrap; color:#0D5C46; font-weight:700;">{{ $isGuru ? ($r->kredit > 0 ? $rp($r->kredit) : '') : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                    Tidak ada transaksi setoran atau penarikan pada tanggal ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rincian->hasPages())
            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
                {{ $rincian->links() }}
            </div>
        @endif
    </div>
@endif

@endsection
