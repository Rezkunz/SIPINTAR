@extends('layouts.app')

@section('title', 'Rekening Aktif')
@section('breadcrumb', 'Rekening Aktif')

@section('content')
<div class="page-header">
    <h1 class="page-title">Daftar Rekening Aktif </h1>
    <p class="page-subtitle">Rekening pada daftar ini sudah disetujui dan menerima setoran awal oleh Siswa Praktik.</p>
</div>

<div class="metric-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header"><span class="metric-title">Total Rekening Aktif</span></div>
        <div class="metric-value">{{ $totalAktif }}</div>
    </div>
    <div class="metric-card card-blue">
        <div class="metric-header"><span class="metric-title">Peran Teller</span></div>
        <div class="metric-value" style="font-size: 18px;">Layanan Transaksi</div>
    </div>
</div>

<div class="content-card">
    <div class="card-header-bar" style="flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="card-heading">Rekening yang Sudah Disetujui</h2>
            <div style="font-size: 12px; color: var(--text-muted);">Teller melayani transaksi hanya untuk rekening aktif.</div>
        </div>
        <form action="{{ route('tabungan.buka-rekening') }}" method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS/NIP, no. rekening" class="form-input" style="padding: 8px 14px; font-size: 13px; width: 260px;">
            <button type="submit" class="btn-ui btn-ui-primary btn-ui-sm">Cari</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Nomor Rekening</th>
                    <th>Nama Nasabah</th>
                    <th>Kategori / Kelas</th>
                    <th>Nomor HP</th>
                    <th style="text-align: right;">Saldo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activeRekening as $rekening)
                    <tr>
                        <td>{{ $rekening->no_rek }}</td>
                        <td>{{ $rekening->nama_nasabah }}</td>
                        <td>{{ ucfirst($rekening->jenis_nasabah) }} / {{ $rekening->kelas_unit ?? '-' }}</td>
                        <td>{{ $rekening->nasabah_no_hp }}</td>
                        <td style="text-align: right;">Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</td>
                        <td><span class="badge-pill green">Aktif</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-placeholder">Belum ada rekening aktif.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($activeRekening->hasPages())
        <div style="padding: 16px 20px;">{{ $activeRekening->links() }}</div>
    @endif
</div>
@endsection