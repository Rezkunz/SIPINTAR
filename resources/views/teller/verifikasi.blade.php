@extends('layouts.app')

@section('title', 'Verifikasi Draft')
@section('breadcrumb', 'Verifikasi Draft')

@section('content')
<div class="page-header">
    <h1 class="page-title">Verifikasi Draft Transaksi</h1>
    <p class="page-subtitle">Periksa draft Siswa Praktik sebelum saldo dan kas berubah.</p>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5;">{{ session('success') }}</div>
@endif

<div class="content-card">
    <div class="card-header-bar">
        <h2 class="card-heading">Antrean Draft Pending</h2>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Nasabah</th>
                    <th>Rekening</th>
                    <th>Jenis</th>
                    <th style="text-align: right;">Nominal</th>
                    <th>Siswa</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drafts as $draft)
                    <tr>
                        <td>{{ $draft->tgl_input?->format('d/m/Y H:i') }}</td>
                        <td>{{ $draft->rekening->nasabah->nama_nasabah ?? '-' }}</td>
                        <td>{{ $draft->rekening->no_rek ?? '-' }}</td>
                        <td>{{ ucfirst($draft->jenisTransaksi->nama_jenis ?? '-') }}</td>
                        <td style="text-align: right;">Rp {{ number_format($draft->nominal, 0, ',', '.') }}</td>
                        <td>{{ $draft->siswa->nama_user ?? '-' }}</td>
                        <td><a class="btn-ui btn-ui-outline btn-ui-sm" href="{{ route('tabungan.verifikasi.show', $draft) }}">Periksa</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-placeholder">Tidak ada draft pending.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($drafts->hasPages())
        <div style="padding: 16px 20px;">{{ $drafts->links() }}</div>
    @endif
</div>
@endsection