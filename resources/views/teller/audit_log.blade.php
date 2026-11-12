@extends('layouts.app')

@section('title', 'Audit Log Koreksi')
@section('breadcrumb', 'Audit Log')

@section('content')
@php
    $rp = fn ($nilai) => 'Rp ' . number_format((float) $nilai, 0, ',', '.');
    $labelJenis = fn ($nama) => ucfirst(str_replace('_', ' ', (string) $nama));
@endphp

<div class="page-header">
    <h1 class="page-title">Audit Log Koreksi Transaksi</h1>
    <p class="page-subtitle">Jejak setiap koreksi transaksi tabungan: nilai lama, nilai baru, alasan, pengguna, dan waktu.</p>
</div>

<div class="content-card">
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.audit-log') }}" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px;">
                <label for="no_rek">Nomor rekening (opsional)</label>
                <input id="no_rek" class="form-input" name="no_rek" value="{{ $noRek }}" placeholder="Semua rekening">
            </div>
            <button class="btn-ui btn-ui-primary" type="submit">Filter</button>
            @if ($noRek !== '')
                <a class="btn-ui btn-ui-outline" href="{{ route('tabungan.audit-log') }}">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="content-card" style="margin-top: 20px;">
    <div class="table-responsive">
        <table class="financial-table" dusk="tabel-audit-log">
            <thead>
                <tr>
                    <th style="width: 150px;">Waktu</th>
                    <th>Pengguna</th>
                    <th>Rekening</th>
                    <th>Transaksi</th>
                    <th>Nilai Lama</th>
                    <th>Nilai Baru</th>
                    <th>Alasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $lama = json_decode((string) $log->data_lama, true) ?: [];
                        $baru = json_decode((string) $log->data_baru, true) ?: [];
                        $nominalLama = (float) ($lama['debit'] ?? 0) > 0 ? $lama['debit'] : ($lama['kredit'] ?? 0);
                    @endphp
                    <tr dusk="audit-log-{{ $log->entitas_id }}">
                        <td>{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->nama_user ?? '-' }}</td>
                        <td>
                            <div style="font-family: ui-monospace, SFMono-Regular, monospace;">{{ $log->no_rek ?? '-' }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $log->nama_nasabah ?? '' }}</div>
                        </td>
                        <td>#{{ $log->entitas_id }}</td>
                        <td>
                            <div>{{ $labelJenis($log->jenis_lama) }} {{ $rp($nominalLama) }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Saldo {{ $rp($lama['saldo_akhir'] ?? 0) }}</div>
                        </td>
                        <td>
                            <div>{{ $labelJenis($namaJenis[$baru['jenis_transaksi_id'] ?? 0] ?? '-') }} {{ $rp($baru['nominal'] ?? 0) }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Saldo {{ $rp($baru['saldo_akhir'] ?? 0) }}</div>
                        </td>
                        <td>{{ $baru['alasan'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-placeholder">Belum ada koreksi transaksi yang tercatat.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding: 16px 20px;">{{ $logs->links() }}</div>
</div>
@endsection
