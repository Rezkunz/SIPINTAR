@extends('layouts.app')

@section('title', 'Laporan Transaksi Tabungan')
@section('breadcrumb', 'Laporan Tabungan')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Rekap Harian Transaksi Tabungan</h1>
        <p>Mutasi bersih (setoran dikurangi penarikan) setiap rekening per hari kerja dalam satu bulan, dipisah antara tabungan siswa dan tabungan guru/karyawan.</p>
    </div>
</div>

@include('manager.laporan.partials.toolbar_tabungan', ['routeLaporan' => 'laporan.tabungan', 'routeCsv' => 'laporan.tabungan.csv'])

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Total Setoran</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_setoran'], 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Dana masuk tabungan {{ strtolower($labelKategori) }} bulan ini</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Penarikan</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_penarikan'], 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Dana keluar dari tabungan {{ strtolower($labelKategori) }}</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Mutasi Bersih</span>
        </div>
        <div class="metric-value" style="color: {{ $ringkasan['mutasi_bersih'] < 0 ? '#DC2626' : 'inherit' }};">
            <span class="curr-prefix">Rp</span>{{ number_format($ringkasan['mutasi_bersih'], 0, ',', '.') }}
        </div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Setoran dikurangi penarikan</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Rekening Bertransaksi</span>
        </div>
        <div class="metric-value">{{ $ringkasan['rekening_aktif'] }} <span style="font-size:14px; font-weight:600; color:var(--text-muted);">/ {{ $ringkasan['jumlah_rekening'] }}</span></div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Rekening dengan mutasi pada bulan ini</div>
    </div>
</div>

{{-- ── TABEL REKAP HARIAN ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Rekap Harian {{ $labelKategori }}</h2>
            <div style="font-size:12px; color:var(--text-muted);">Bulan {{ $awalBulan->translatedFormat('F Y') }} &bull; nilai negatif berarti penarikan lebih besar dari setoran</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableRekapHarian" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th style="min-width:140px;">No Rekening</th>
                    <th style="min-width:200px;">Nama Nasabah</th>
                    @foreach ($tanggalList as $tgl)
                        <th style="text-align:right; min-width:92px; white-space:nowrap; {{ $tgl->isWeekend() ? 'background:#FEF3C7;' : '' }}">
                            <div>{{ $tgl->format('d/m') }}</div>
                            <div style="font-size:10.5px; font-weight:500; color:var(--text-muted);">{{ $tgl->translatedFormat('D') }}</div>
                        </th>
                    @endforeach
                    <th style="text-align:right; min-width:120px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($baris as $i => $b)
                    <tr>
                        <td style="color:var(--text-muted);">{{ $baris->firstItem() + $i }}</td>
                        <td style="font-family:monospace; font-size:12.5px;">{{ $b->no_rek }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $b->nama_nasabah }}</div>
                            @if ($b->kelas_unit)
                                <div style="font-size:11px; color:var(--text-muted);">{{ $b->kelas_unit }}</div>
                            @endif
                        </td>
                        @foreach ($tanggalList as $tgl)
                            @php $nilai = $b->harian[$tgl->toDateString()] ?? null; @endphp
                            <td style="text-align:right; white-space:nowrap; font-weight:{{ $nilai ? '700' : '400' }}; color:{{ $nilai === null ? 'var(--text-muted)' : ($nilai < 0 ? '#DC2626' : '#0D5C46') }};">
                                {{ $nilai === null ? '-' : number_format($nilai, 0, ',', '.') }}
                            </td>
                        @endforeach
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:{{ $b->total < 0 ? '#DC2626' : 'var(--text-heading)' }};">
                            {{ number_format($b->total, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $tanggalList->count() + 4 }}">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                @if ($cari !== '')
                                    Tidak ada nasabah yang cocok dengan pencarian "{{ $cari }}".
                                @else
                                    Belum ada rekening tabungan {{ strtolower($labelKategori) }} yang terdaftar.
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($baris->isNotEmpty())
                <tfoot>
                    <tr style="background:#F8FAFC; border-top:2px solid #CBD5E1;">
                        <td></td>
                        <td colspan="2" style="font-weight:800; color:var(--text-heading);">
                            JUMLAH
                            <div style="font-size:10.5px; font-weight:500; color:var(--text-muted);">Seluruh {{ $baris->total() }} rekening{{ $cari !== '' ? ' hasil pencarian' : '' }}</div>
                        </td>
                        @foreach ($tanggalList as $tgl)
                            @php $nilai = $totalHarian[$tgl->toDateString()] ?? 0; @endphp
                            <td style="text-align:right; white-space:nowrap; font-weight:800; color:{{ $nilai < 0 ? '#DC2626' : 'var(--text-heading)' }};">
                                {{ number_format($nilai, 0, ',', '.') }}
                            </td>
                        @endforeach
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:{{ $ringkasan['mutasi_bersih'] < 0 ? '#DC2626' : '#0D5C46' }};">
                            {{ number_format($ringkasan['mutasi_bersih'], 0, ',', '.') }}
                        </td>
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
