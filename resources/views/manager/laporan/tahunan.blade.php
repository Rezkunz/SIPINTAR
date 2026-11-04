@extends('layouts.app')

@section('title', 'Laporan Keuangan Tahunan')
@section('breadcrumb', 'Laporan Tahunan')

@section('content')

@php
    $rp = fn ($n) => number_format($n, 0, ',', '.');
    // Angka negatif ditulis dalam kurung sesuai kebiasaan laporan keuangan
    $rpAkuntansi = fn ($n) => $n < 0 ? '(' . number_format(abs($n), 0, ',', '.') . ')' : number_format($n, 0, ',', '.');
    $isLaba = $total['laba'] >= 0;
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Laporan Keuangan Tahunan</h1>
        <p>Laporan laba rugi (income statement) Bank Mini selama satu tahun: pendapatan jasa pinjaman dan pendapatan lain dikurangi beban jasa tabungan dan beban operasional, lengkap dengan dasar perhitungan per bulan.</p>
    </div>
</div>

{{-- ── TOOLBAR TAHUN & EKSPOR ── --}}
<form method="GET" action="{{ route('laporan.tahunan') }}" class="lap-toolbar">
    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Tahun Buku
        </span>
        <div class="lap-periode">
            <a href="{{ route('laporan.tahunan', ['tahun' => $tahun - 1]) }}" class="lap-periode-nav" title="Tahun sebelumnya ({{ $tahun - 1 }})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <select name="tahun" class="lap-select" onchange="this.form.submit()" title="Pilih Tahun">
                @for ($y = min(date('Y') - 5, $tahun); $y <= max((int) date('Y'), $tahun); $y++)
                    <option value="{{ $y }}" {{ $y == $tahun ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <a href="{{ route('laporan.tahunan', ['tahun' => $tahun + 1]) }}" class="lap-periode-nav" title="Tahun berikutnya ({{ $tahun + 1 }})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
    </div>

    @include('manager.laporan.partials.tombol_unduh', ['routeEkspor' => 'laporan.tahunan', 'paramEkspor' => ['tahun' => $tahun]])
</form>

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Total Pendapatan</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($total['pendapatan']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Jasa pinjaman + pendapatan lain-lain</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Beban</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($total['beban']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Jasa tabungan + beban operasional</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">{{ $isLaba ? 'Laba Bersih' : 'Rugi Bersih' }}</span>
        </div>
        <div class="metric-value" style="color: {{ $isLaba ? '#0D5C46' : '#DC2626' }};"><span class="curr-prefix">Rp</span>{{ $rpAkuntansi($total['laba']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Pendapatan dikurangi beban</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Periode Dihitung</span>
        </div>
        <div class="metric-value">{{ $bulanTerakhir }} <span style="font-size:14px; font-weight:600; color:var(--text-muted);">/ 12 bulan</span></div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">
            @if ($bulanTerakhir === 0)
                Tahun {{ $tahun }} belum berjalan
            @elseif ($bulanTerakhir < 12)
                Tahun berjalan, s.d. {{ $tanggalPosisi->translatedFormat('d F Y') }}
            @else
                Tahun buku penuh
            @endif
        </div>
    </div>
</div>

{{-- ── LAPORAN LABA RUGI ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Laporan Laba Rugi (Income Statement)</h2>
            <div style="font-size:12px; color:var(--text-muted);">Bank Mini Wiyata Cipta Niaga &bull; per {{ $tanggalPosisi->translatedFormat('d F Y') }}</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table lap-income" style="font-size:13.5px;">
            <tbody>
                <tr class="lap-income-group"><td colspan="3">Pendapatan</td></tr>
                <tr>
                    <td class="lap-income-item">Pendapatan Jasa Pinjaman <span class="lap-income-note">Interest Revenue &bull; jasa angsuran pinjaman pegawai</span></td>
                    <td class="lap-income-num">{{ $rp($total['jasa_pinjaman']) }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="lap-income-item">Pendapatan Lain-lain <span class="lap-income-note">Other Revenue &bull; kas manual masuk</span></td>
                    <td class="lap-income-num">{{ $rp($total['pendapatan_lain']) }}</td>
                    <td></td>
                </tr>
                <tr class="lap-income-subtotal">
                    <td>Total Pendapatan</td>
                    <td></td>
                    <td class="lap-income-num">{{ $rp($total['pendapatan']) }}</td>
                </tr>

                <tr class="lap-income-group"><td colspan="3">Beban</td></tr>
                <tr>
                    <td class="lap-income-item">Beban Jasa Tabungan Guru / Karyawan <span class="lap-income-note">Interest Takar Expense &bull; 0,2% per bulan</span></td>
                    <td class="lap-income-num">{{ $rp($total['jasa_guru']) }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="lap-income-item">Beban Jasa Tabungan Siswa <span class="lap-income-note">Interest Tapel Expense &bull; 0,1% per bulan</span></td>
                    <td class="lap-income-num">{{ $rp($total['jasa_siswa']) }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="lap-income-item">Beban Operasional <span class="lap-income-note">Operating Expenses &bull; kas manual keluar (honor, ATK, adm. bank, dll)</span></td>
                    <td class="lap-income-num">{{ $rp($total['beban_operasional']) }}</td>
                    <td></td>
                </tr>
                <tr class="lap-income-subtotal">
                    <td>Total Beban</td>
                    <td></td>
                    <td class="lap-income-num">({{ $rp($total['beban']) }})</td>
                </tr>

                <tr class="lap-income-total {{ $isLaba ? 'laba' : 'rugi' }}">
                    <td>{{ $isLaba ? 'Laba Bersih' : 'Rugi Bersih' }} <span class="lap-income-note">Net Profit</span></td>
                    <td></td>
                    <td class="lap-income-num">{{ $rpAkuntansi($total['laba']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── DASAR PERHITUNGAN PER BULAN ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Dasar Perhitungan</h2>
            <div style="font-size:12px; color:var(--text-muted);">Rincian pendapatan dan beban per bulan tahun {{ $tahun }}</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th rowspan="2" style="width:44px; vertical-align:bottom;">No</th>
                    <th rowspan="2" style="min-width:110px; vertical-align:bottom;">Bulan</th>
                    <th colspan="2" style="text-align:center; background:var(--brand-primary-light); color:var(--brand-primary);">Pendapatan</th>
                    <th colspan="3" style="text-align:center; background:var(--brand-accent-light); color:var(--brand-accent-hover);">Beban</th>
                    <th rowspan="2" style="text-align:right; min-width:130px; vertical-align:bottom;">Laba / Rugi</th>
                </tr>
                <tr>
                    <th style="text-align:right; min-width:120px; background:var(--brand-primary-light);">Jasa Pinjaman</th>
                    <th style="text-align:right; min-width:120px; background:var(--brand-primary-light);">Lain-lain</th>
                    <th style="text-align:right; min-width:120px; background:var(--brand-accent-light);">Jasa Tab. Siswa</th>
                    <th style="text-align:right; min-width:120px; background:var(--brand-accent-light);">Jasa Tab. Guru</th>
                    <th style="text-align:right; min-width:120px; background:var(--brand-accent-light);">Operasional</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bulanan as $i => $b)
                    <tr style="{{ $b->dihitung ? '' : 'opacity:0.45;' }}">
                        <td style="color:var(--text-muted);">{{ $i + 1 }}</td>
                        <td style="font-weight:700; color:var(--text-heading);">
                            {{ $b->nama_bulan }}
                            @if (!$b->dihitung)
                                <div style="font-size:10.5px; font-weight:500; color:var(--text-muted);">belum berjalan</div>
                            @endif
                        </td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46;">{{ $rp($b->jasa_pinjaman) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46;">{{ $rp($b->pendapatan_lain) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:var(--brand-accent-hover);">{{ $rp($b->jasa_siswa) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:var(--brand-accent-hover);">{{ $rp($b->jasa_guru) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:var(--brand-accent-hover);">{{ $rp($b->beban_operasional) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:{{ $b->laba < 0 ? '#DC2626' : 'var(--brand-primary)' }};">{{ $rpAkuntansi($b->laba) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#F8FAFC; border-top:2px solid #CBD5E1;">
                    <td></td>
                    <td style="font-weight:800; color:var(--text-heading);">JUMLAH</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:#0D5C46;">{{ $rp($total['jasa_pinjaman']) }}</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:#0D5C46;">{{ $rp($total['pendapatan_lain']) }}</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-accent-hover);">{{ $rp($total['jasa_siswa']) }}</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-accent-hover);">{{ $rp($total['jasa_guru']) }}</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-accent-hover);">{{ $rp($total['beban_operasional']) }}</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:{{ $isLaba ? 'var(--brand-primary)' : '#DC2626' }};">{{ $rpAkuntansi($total['laba']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ── RINCIAN KAS MANUAL ── --}}
<div class="content-card" id="kas-manual">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Rincian Kas Manual Bendahara</h2>
            <div style="font-size:12px; color:var(--text-muted);">Sumber Pendapatan Lain-lain dan Beban Operasional &bull; periksa apakah ada setoran modal atau pindah kas yang ikut tercatat</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th style="min-width:100px;">Tanggal</th>
                    <th style="width:70px;">Kas</th>
                    <th style="min-width:280px;">Uraian</th>
                    <th style="text-align:right; min-width:130px;">Pendapatan Lain</th>
                    <th style="text-align:right; min-width:130px;">Beban Operasional</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kasManual as $k)
                    <tr>
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($k->tgl_mutasi)->format('d/m/Y') }}</td>
                        <td><span class="badge-pill gray" style="font-size:10px; padding:1px 7px;">{{ $k->jenis_kas === 'bank' ? 'Bank' : 'Tunai' }}</span></td>
                        <td style="font-weight:600; color:var(--text-heading);">{{ $k->uraian }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46; font-weight:700;">{{ $k->arah === 'masuk' ? $rp($k->nominal) : '' }}</td>
                        <td style="text-align:right; white-space:nowrap; color:var(--brand-accent-hover); font-weight:700;">{{ $k->arah === 'keluar' ? $rp($k->nominal) : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                Tidak ada kas manual yang tercatat pada tahun {{ $tahun }}.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($kasManual->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
            {{ $kasManual->links() }}
        </div>
    @endif
</div>

@endsection
