@extends('layouts.app')

@section('title', 'Buku Kas Bendahara & Angsuran')
@section('breadcrumb', 'Laporan Pinjaman')

@section('content')

@php
    $rp = fn ($n) => number_format($n, 0, ',', '.');
    $nilaiAtauKosong = fn ($n) => $n != 0 ? number_format($n, 0, ',', '.') : '';
    $labelJenis = [
        'angsuran'  => ['label' => 'Angsuran Loket', 'warna' => 'green'],
        'potongan'  => ['label' => 'Potong Gaji',    'warna' => 'green'],
        'pencairan' => ['label' => 'Pencairan',      'warna' => 'blue'],
        'koreksi'   => ['label' => 'Koreksi',        'warna' => 'red'],
        'manual'    => ['label' => 'Kas Manual',     'warna' => 'gray'],
    ];
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Buku Kas Bendahara &amp; Penerimaan Angsuran</h1>
        <p>Kas harian Bendahara Bank Mini untuk pinjaman pegawai: pemasukan angsuran (pokok dan jasa), pencairan pinjaman, dan kas operasional, beserta rekap penerimaan angsuran per saluran pembayaran.</p>
    </div>
</div>

@include('manager.laporan.partials.toolbar_pinjaman', ['routeLaporan' => 'laporan.pinjaman.kas', 'filterHalaman' => ['sumber' => $sumber === 'semua' ? null : $sumber]])

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Saldo Awal</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($saldoAwal) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">{{ $labelSumber }} per 1 {{ $awalBulan->translatedFormat('F Y') }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Total Pemasukan</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($total['pemasukan']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Pokok {{ $rp($total['pokok']) }} &bull; Jasa {{ $rp($total['jasa']) }} &bull; Lain {{ $rp($total['lain_lain']) }}</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Pengeluaran</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($total['pengeluaran']) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Termasuk {{ $total['jumlah_pencairan'] }} pencairan pinjaman (Rp {{ $rp($total['pencairan']) }})</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Saldo Akhir</span>
        </div>
        <div class="metric-value" style="color: {{ $saldoAkhir < 0 ? '#DC2626' : 'inherit' }};"><span class="curr-prefix">Rp</span>{{ $rp($saldoAkhir) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">{{ $labelSumber }} per akhir {{ $awalBulan->translatedFormat('F Y') }}</div>
    </div>
</div>

{{-- ── REKAP PENERIMAAN ANGSURAN ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Rekap Penerimaan Angsuran</h2>
            <div style="font-size:12px; color:var(--text-muted);">Angsuran pinjaman yang diterima pada {{ $awalBulan->translatedFormat('F Y') }} per saluran pembayaran &bull; koreksi mengurangi penerimaan</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="financial-table" style="font-size:13px;">
            <thead>
                <tr>
                    <th style="min-width:220px;">Saluran Pembayaran</th>
                    <th style="text-align:center; min-width:110px;">Jumlah Transaksi</th>
                    <th style="text-align:right; min-width:140px;">Pokok</th>
                    <th style="text-align:right; min-width:140px;">Jasa</th>
                    <th style="text-align:right; min-width:140px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rekapAngsuran['saluran'] as $kode => $s)
                    @php $isKoreksi = $kode === 'koreksi'; @endphp
                    <tr>
                        <td style="font-weight:700; color:{{ $isKoreksi ? '#DC2626' : 'var(--text-heading)' }};">{{ $s['label'] }}</td>
                        <td style="text-align:center;">{{ $s['jumlah'] }}</td>
                        <td style="text-align:right; white-space:nowrap; color:{{ $isKoreksi ? '#DC2626' : 'inherit' }};">{{ $isKoreksi && $s['pokok'] ? '- ' : '' }}{{ $rp($s['pokok']) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:{{ $isKoreksi ? '#DC2626' : 'inherit' }};">{{ $isKoreksi && $s['jasa'] ? '- ' : '' }}{{ $rp($s['jasa']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:700; color:{{ $isKoreksi ? '#DC2626' : 'inherit' }};">{{ $isKoreksi && ($s['pokok'] + $s['jasa']) ? '- ' : '' }}{{ $rp($s['pokok'] + $s['jasa']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#F8FAFC; border-top:2px solid #CBD5E1;">
                    <td colspan="2" style="font-weight:800; color:var(--text-heading);">PENERIMAAN BERSIH</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-primary);">{{ $rp($rekapAngsuran['bersih']['pokok']) }}</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-accent-hover);">{{ $rp($rekapAngsuran['bersih']['jasa']) }}</td>
                    <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--text-heading);">{{ $rp($rekapAngsuran['bersih']['pokok'] + $rekapAngsuran['bersih']['jasa']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ── TABEL BUKU KAS BENDAHARA ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Kas Harian Bendahara Bank Mini</h2>
            <div style="font-size:12px; color:var(--text-muted);">Bulan {{ $awalBulan->translatedFormat('F Y') }} &bull; {{ $labelSumber }} &bull; urut tanggal transaksi</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableKasBendahara" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th rowspan="2" style="min-width:100px; vertical-align:bottom;">Tanggal</th>
                    <th rowspan="2" style="min-width:300px; vertical-align:bottom;">Uraian</th>
                    <th colspan="3" style="text-align:center; background:var(--brand-primary-light); color:var(--brand-primary);">Pemasukan</th>
                    <th rowspan="2" style="text-align:right; min-width:120px; vertical-align:bottom; background:var(--color-danger-bg); color:var(--color-danger);">Pengeluaran</th>
                    <th rowspan="2" style="text-align:right; min-width:130px; vertical-align:bottom;">Saldo</th>
                </tr>
                <tr>
                    <th style="text-align:right; min-width:110px; background:var(--brand-primary-light);">Lain-lain</th>
                    <th style="text-align:right; min-width:110px; background:var(--brand-primary-light);">Pokok</th>
                    <th style="text-align:right; min-width:100px; background:var(--brand-primary-light);">Jasa</th>
                </tr>
            </thead>
            <tbody>
                @if ($baris->onFirstPage())
                    <tr style="background:var(--surface-hover);">
                        <td style="color:var(--text-muted);">01/{{ $awalBulan->format('m/Y') }}</td>
                        <td style="font-weight:700; color:var(--text-heading);">Saldo awal ({{ $labelSumber }})</td>
                        <td colspan="4"></td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--text-heading);">{{ $rp($saldoAwal) }}</td>
                    </tr>
                @endif
                @forelse ($baris as $b)
                    <tr>
                        <td style="white-space:nowrap; font-weight:600; color:var(--text-heading);">{{ $b->tanggal->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight:600; color:var(--text-heading); line-height:1.4;">{{ $b->uraian }}</div>
                            <div style="margin-top:3px; display:flex; gap:4px;">
                                <span class="badge-pill {{ $labelJenis[$b->jenis]['warna'] }}" style="font-size:10px; padding:1px 7px;">{{ $labelJenis[$b->jenis]['label'] }}</span>
                                <span class="badge-pill gray" style="font-size:10px; padding:1px 7px;">{{ $b->label_kas }}</span>
                            </div>
                        </td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46;">{{ $nilaiAtauKosong($b->lain_lain) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#0D5C46; font-weight:700;">{{ $nilaiAtauKosong($b->pokok) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:var(--brand-accent-hover); font-weight:700;">{{ $nilaiAtauKosong($b->jasa) }}</td>
                        <td style="text-align:right; white-space:nowrap; color:#DC2626; font-weight:700;">{{ $nilaiAtauKosong($b->pengeluaran) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:{{ $b->saldo < 0 ? '#DC2626' : 'var(--brand-primary)' }};">{{ $rp($b->saldo) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                Belum ada mutasi kas bendahara pada {{ $awalBulan->translatedFormat('F Y') }}.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($baris->isNotEmpty())
                <tfoot>
                    <tr style="background:#F8FAFC; border-top:2px solid #CBD5E1;">
                        <td colspan="2" style="font-weight:800; color:var(--text-heading);">
                            JUMLAH
                            <div style="font-size:10.5px; font-weight:500; color:var(--text-muted);">Seluruh {{ $baris->total() }} mutasi bulan ini</div>
                        </td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#0D5C46;">{{ $rp($total['lain_lain']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#0D5C46;">{{ $rp($total['pokok']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-accent-hover);">{{ $rp($total['jasa']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:#DC2626;">{{ $rp($total['pengeluaran']) }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-primary);">{{ $rp($saldoAkhir) }}</td>
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
