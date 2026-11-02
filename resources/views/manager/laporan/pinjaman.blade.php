@extends('layouts.app')

@section('title', 'Rekap Pinjaman & Kolektibilitas')
@section('breadcrumb', 'Laporan Pinjaman')

@section('content')

@php
    $rp = fn ($n) => number_format($n, 0, ',', '.');
    $daftarKolek = \App\Http\Controllers\Manager\LaporanPinjamanController::KOLEKTIBILITAS;
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Rekap Pinjaman &amp; Kolektibilitas</h1>
        <p>Posisi portofolio pinjaman pegawai: sisa pokok, tunggakan angsuran, dan kualitas kredit berdasarkan lamanya keterlambatan pembayaran (standar kolektibilitas OJK).</p>
    </div>
</div>

@include('manager.laporan.partials.toolbar_pinjaman', ['routeLaporan' => 'laporan.pinjaman', 'filterHalaman' => ['cari' => $cari, 'kolektibilitas' => $filterKolek]])

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Total Sisa Pokok (Outstanding)</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($ringkasan['total_sisa_pokok']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Dari plafon Rp {{ $rp($ringkasan['total_plafon']) }}</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Pinjaman Berjalan</span>
        </div>
        <div class="metric-value">{{ $ringkasan['jumlah_pinjaman'] }} <span style="font-size:14px; font-weight:600; color:var(--text-muted);">pinjaman</span></div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Masih memiliki sisa pokok per {{ $tanggalPosisi->translatedFormat('d F Y') }}</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Tunggakan</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($ringkasan['total_tunggakan']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">{{ $ringkasan['jumlah_menunggak'] }} peminjam melewati jatuh tempo</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Rasio NPL</span>
        </div>
        <div class="metric-value" style="color: {{ $ringkasan['rasio_npl'] > 5 ? '#DC2626' : '#0D5C46' }};">{{ number_format($ringkasan['rasio_npl'], 2, ',', '.') }}<span style="font-size:16px;">%</span></div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Kurang Lancar + Diragukan + Macet &bull; batas sehat &le; 5%</div>
    </div>
</div>

{{-- ── SEBARAN KOLEKTIBILITAS ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Sebaran Kolektibilitas</h2>
            <div style="font-size:12px; color:var(--text-muted);">Klik kategori untuk menyaring tabel &bull; persentase dari total sisa pokok</div>
        </div>
    </div>
    <div class="kolek-grid">
        @foreach ($sebaran as $kode => $s)
            <a href="{{ route('laporan.pinjaman', array_filter(['bulan' => $bulan, 'tahun' => $tahun, 'cari' => $cari, 'kolektibilitas' => $filterKolek === $kode ? null : $kode])) }}"
               class="kolek-tile kolek-{{ $kode }} {{ $filterKolek === $kode ? 'active' : '' }}">
                <div class="kolek-tile-head">
                    <span class="kolek-badge kolek-{{ $kode }}">{{ $s['label'] }}</span>
                    <span class="kolek-tile-hari">
                        @switch($kode)
                            @case('lancar') Tepat waktu @break
                            @case('dpk') 1&ndash;90 hari @break
                            @case('kurang_lancar') 91&ndash;120 hari @break
                            @case('diragukan') 121&ndash;180 hari @break
                            @default &gt; 180 hari
                        @endswitch
                    </span>
                </div>
                <div class="kolek-tile-jumlah">{{ $s['jumlah'] }} <small>pinjaman</small></div>
                <div class="kolek-tile-nominal">Rp {{ $rp($s['sisa_pokok']) }}</div>
                <div class="kolek-bar"><div style="width: {{ min(100, $s['persen']) }}%;"></div></div>
                <div class="kolek-tile-persen">{{ number_format($s['persen'], 2, ',', '.') }}%</div>
            </a>
        @endforeach
    </div>
</div>

{{-- ── TABEL REKAP PINJAMAN ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">
                Daftar Pinjaman
                @if ($filterKolek)
                    <span class="kolek-badge kolek-{{ $filterKolek }}" style="margin-left:6px; vertical-align:middle;">{{ $daftarKolek[$filterKolek]['label'] }}</span>
                @endif
            </h2>
            <div style="font-size:12px; color:var(--text-muted);">Posisi per {{ $tanggalPosisi->translatedFormat('d F Y') }} &bull; hanya pinjaman yang sudah cair dan masih memiliki sisa pokok</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableRekapPinjaman" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th style="min-width:110px;">No Pinjaman</th>
                    <th style="min-width:200px;">Peminjam</th>
                    <th style="min-width:100px;">Tgl Cair</th>
                    <th style="text-align:right; min-width:120px;">Plafon</th>
                    <th style="text-align:center; min-width:90px;">Angsuran</th>
                    <th style="text-align:right; min-width:120px;">Pokok Terbayar</th>
                    <th style="text-align:right; min-width:120px;">Sisa Pokok</th>
                    <th style="text-align:right; min-width:130px;">Tunggakan</th>
                    <th style="text-align:center; min-width:90px;">Terlambat</th>
                    <th style="min-width:150px;">Kolektibilitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($baris as $i => $b)
                    <tr>
                        <td style="color:var(--text-muted);">{{ $baris->firstItem() + $i }}</td>
                        <td style="font-family:monospace; font-weight:700; color:var(--brand-primary);">{{ $b->no_pinjaman }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $b->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $b->kelas_unit ?? '-' }} &bull; {{ $b->label_metode }}</div>
                        </td>
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($b->tgl_cair)->format('d/m/Y') }}</td>
                        <td style="text-align:right; white-space:nowrap;">{{ $rp($b->nominal_pinjaman) }}</td>
                        <td style="text-align:center; white-space:nowrap;">
                            <span style="font-weight:700; color:var(--text-heading);">{{ $b->angsuran_dibayar }}</span><span style="color:var(--text-muted);">/{{ $b->total_angsuran }}</span>
                        </td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46; font-weight:600;">{{ $rp($b->pokok_terbayar) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--text-heading);">{{ $rp($b->sisa_pokok) }}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            @if ($b->bulan_tunggakan > 0)
                                <div style="font-weight:700; color:#DC2626;">{{ $rp($b->nominal_tunggakan) }}</div>
                                <div style="font-size:11px; color:var(--text-muted);">{{ $b->bulan_tunggakan }} angsuran</div>
                            @else
                                <span style="color:var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            @if ($b->hari_terlambat > 0)
                                <div style="font-weight:700; color:#DC2626;">{{ $b->hari_terlambat }} hari</div>
                                <div style="font-size:11px; color:var(--text-muted);">sejak {{ \Carbon\Carbon::parse($b->jatuh_tempo_tertunggak)->format('d/m/Y') }}</div>
                            @else
                                <span style="color:var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td><span class="kolek-badge kolek-{{ $b->kolektibilitas }}">{{ $daftarKolek[$b->kolektibilitas]['label'] }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                @if ($cari !== '' || $filterKolek)
                                    Tidak ada pinjaman yang cocok dengan filter yang dipilih.
                                @else
                                    Belum ada pinjaman berjalan pada posisi {{ $tanggalPosisi->translatedFormat('d F Y') }}.
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($baris->isNotEmpty() && !$filterKolek && $cari === '')
                <tfoot>
                    <tr style="background:#F8FAFC; border-top:2px solid #CBD5E1;">
                        <td></td>
                        <td colspan="3" style="font-weight:800; color:var(--text-heading);">
                            JUMLAH
                            <div style="font-size:10.5px; font-weight:500; color:var(--text-muted);">Seluruh {{ $ringkasan['jumlah_pinjaman'] }} pinjaman berjalan</div>
                        </td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800;">{{ $rp($ringkasan['total_plafon']) }}</td>
                        <td></td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#0D5C46;">{{ $rp($ringkasan['total_pokok_terbayar']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-primary);">{{ $rp($ringkasan['total_sisa_pokok']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#DC2626;">{{ $rp($ringkasan['total_tunggakan']) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if ($baris->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
            {{ $baris->links() }}
        </div>
    @endif
</div>

@endsection
