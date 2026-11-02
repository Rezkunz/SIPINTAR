@extends('layouts.app')

@section('title', 'Rekap Tabungan & Jasa Bulanan')
@section('breadcrumb', 'Laporan Tabungan')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Rekap Tabungan &amp; Jasa Bulanan</h1>
        <p>Saldo pokok setiap rekening per akhir bulan beserta jasa tabungan (siswa 0,1% dan guru/karyawan 0,2% per bulan) yang menjadi hak nasabah.</p>
    </div>
</div>

@include('manager.laporan.partials.toolbar_tabungan', ['routeLaporan' => 'laporan.tabungan.bulanan'])

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Total Saldo Pokok</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_pokok'], 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Dana tabungan {{ strtolower($labelKategori) }} per {{ $akhirBulan->translatedFormat('d F Y') }}</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Jasa {{ $labelPersenJasa }}</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_jasa'], 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Beban jasa tabungan bulan ini</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Total Pokok + Jasa</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasan['total_akhir'], 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Kewajiban Bank Mini kepada nasabah</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Rekening Bersaldo</span>
        </div>
        <div class="metric-value">{{ $ringkasan['rekening_bersaldo'] }} <span style="font-size:14px; font-weight:600; color:var(--text-muted);">/ {{ $ringkasan['jumlah_rekening'] }}</span></div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Rekening yang memperoleh jasa</div>
    </div>
</div>

{{-- ── TABEL REKAP BULANAN ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Rekap Tabungan &amp; Jasa {{ $labelKategori }}</h2>
            <div style="font-size:12px; color:var(--text-muted);">Per {{ $akhirBulan->translatedFormat('d F Y') }} &bull; jasa {{ $labelPersenJasa }} dari saldo pokok, dibulatkan ke rupiah terdekat</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableRekapBulanan">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th style="min-width:140px;">No Rekening</th>
                    <th style="min-width:220px;">Nama Nasabah</th>
                    <th style="text-align:right; min-width:150px;">Jumlah Pokok</th>
                    <th style="text-align:right; min-width:130px;">Jasa {{ $labelPersenJasa }}</th>
                    <th style="text-align:right; min-width:150px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($baris as $i => $b)
                    <tr>
                        <td style="color:var(--text-muted);">{{ $baris->firstItem() + $i }}</td>
                        <td style="font-family:monospace; font-size:12.5px;">{{ $b->no_rek }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $b->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">
                                {{ $b->kelas_unit ?? '-' }}
                                @if ($b->status_rekening !== 'aktif')
                                    <span class="badge-pill {{ $b->status_rekening === 'blokir' ? 'red' : 'gray' }}" style="font-size:10px; padding:1px 7px; margin-left:4px;">{{ ucfirst($b->status_rekening) }}</span>
                                @endif
                            </div>
                        </td>
                        <td style="text-align:right; white-space:nowrap; font-weight:600; color:{{ $b->pokok < 0 ? '#DC2626' : 'var(--text-heading)' }};">
                            {{ number_format($b->pokok, 0, ',', '.') }}
                        </td>
                        <td style="text-align:right; white-space:nowrap; font-weight:700; color:{{ $b->jasa > 0 ? 'var(--brand-accent-hover)' : 'var(--text-muted)' }};">
                            {{ $b->jasa > 0 ? number_format($b->jasa, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-primary);">
                            {{ number_format($b->total, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                @if ($cari !== '')
                                    Tidak ada nasabah yang cocok dengan pencarian "{{ $cari }}".
                                @else
                                    Belum ada rekening tabungan {{ strtolower($labelKategori) }} pada periode ini.
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
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--text-heading);">{{ number_format($ringkasan['total_pokok'], 0, ',', '.') }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-accent-hover);">{{ number_format($ringkasan['total_jasa'], 0, ',', '.') }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:800; color:var(--brand-primary);">{{ number_format($ringkasan['total_akhir'], 0, ',', '.') }}</td>
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
