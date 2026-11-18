@extends('layouts.app')

@section('title', 'Kelola Akun Nasabah')
@section('breadcrumb', 'Kelola Akun Nasabah')

@section('content')
<div class="page-header">
    <h1 class="page-title">Kelola Akun Nasabah</h1>
    <p class="page-subtitle">Cek status rekening, perbarui profil nasabah, reset password/PIN, atau tutup rekening.</p>
</div>

<div class="content-card" style="margin-bottom: 20px;">
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.kelola-akun') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
            <div style="flex:1; min-width:240px;">
                <label for="search">Cari Nasabah / Rekening</label>
                <input id="search" type="text" name="search" class="form-input" value="{{ $search }}" placeholder="No. rekening, nama, atau no. HP">
            </div>
            <div style="min-width:160px;">
                <label for="status">Status Rekening</label>
                <select id="status" name="status" class="form-input">
                    <option value="">Semua Status</option>
                    <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="blokir" @selected($status === 'blokir')>Blokir</option>
                    <option value="tutup" @selected($status === 'tutup')>Tutup</option>
                </select>
            </div>
            <button type="submit" class="btn-ui btn-ui-primary">Cari</button>
        </form>
    </div>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>No. Rekening</th>
                    <th>Nama Nasabah</th>
                    <th>Peran / Kelas</th>
                    <th>No. HP</th>
                    <th style="text-align:right;">Saldo</th>
                    <th>Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekeningList as $item)
                    @php
                        $badge = match ($item->status_rekening) {
                            'aktif' => 'green',
                            'pending' => 'amber',
                            'blokir', 'tutup' => 'red',
                            default => 'blue',
                        };
                    @endphp
                    <tr>
                        <td><strong>{{ $item->no_rek }}</strong></td>
                        <td>{{ $item->nasabah->nama_nasabah ?? '-' }}</td>
                        <td>{{ $item->nasabah?->jenis_nasabah === 'pegawai' ? 'Pegawai' : 'Siswa' }} / {{ $item->nasabah?->kelas_unit ?? '-' }}</td>
                        <td>{{ $item->nasabah->no_hp ?? '-' }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                        <td><span class="badge-pill {{ $badge }}">{{ ucfirst($item->status_rekening) }}</span></td>
                        <td style="text-align:center;">
                            <a href="{{ route('tabungan.kelola-akun.show', $item->rekening_id) }}" class="btn-ui btn-ui-outline btn-ui-sm">Kelola</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-placeholder">Data rekening tidak ditemukan.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($rekeningList->hasPages())
        <div style="padding: 16px 20px;">{{ $rekeningList->links() }}</div>
    @endif
</div>
@endsection
