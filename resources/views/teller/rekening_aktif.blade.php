@extends('layouts.app')

@section('title', 'Nasabah & Rekening')
@section('breadcrumb', 'Nasabah & Rekening')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap;">
    <div>
        <h1 class="page-title">Nasabah & Rekening</h1>
        <p class="page-subtitle">Buka rekening langsung di loket, ubah data nasabah, dan tutup rekening yang saldonya sudah nol.</p>
    </div>
    <a href="{{ route('tabungan.nasabah.tambah') }}" class="btn-ui btn-ui-primary" dusk="tambah-nasabah">+ Tambah Nasabah</a>
</div>

@if ($errors->any())
    <div class="content-card" dusk="pesan-error" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

<div class="metric-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header"><span class="metric-title">Total Rekening Aktif</span></div>
        <div class="metric-value">{{ $totalAktif }}</div>
    </div>
    <div class="metric-card card-blue">
        <div class="metric-header"><span class="metric-title">Rekening Tertutup</span></div>
        <div class="metric-value">{{ $totalTutup }}</div>
    </div>
</div>

<div class="content-card">
    <div class="card-header-bar" style="flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            @foreach (['aktif' => 'Aktif', 'tutup' => 'Tertutup', 'semua' => 'Semua'] as $kode => $label)
                <a href="{{ route('tabungan.buka-rekening', ['status' => $kode, 'search' => $search ?: null]) }}"
                   class="btn-ui btn-ui-sm {{ $status === $kode ? 'btn-ui-primary' : 'btn-ui-outline' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form action="{{ route('tabungan.buka-rekening') }}" method="GET" style="display: flex; gap: 8px;">
            <input type="hidden" name="status" value="{{ $status }}">
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
                    <th style="min-width: 260px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activeRekening as $rekening)
                    @php
                        $tertutup = $rekening->status_rekening === 'tutup';
                        $saldoNol = (float) $rekening->saldo == 0.0;
                        $adaDraftPending = isset($pendingDraftRekening[$rekening->rekening_id]);
                    @endphp
                    <tr dusk="baris-rekening-{{ $rekening->rekening_id }}">
                        <td style="font-family: ui-monospace, SFMono-Regular, monospace;">{{ $rekening->no_rek }}</td>
                        <td>
                            {{ $rekening->nama_nasabah }}
                            <div style="font-size: 11px; color: var(--text-muted);">ID Nasabah #{{ $rekening->nasabah_id }}</div>
                        </td>
                        <td>{{ $rekening->jenis_nasabah === 'pegawai' ? 'Pegawai' : 'Siswa' }} / {{ $rekening->kelas_unit ?? '-' }}</td>
                        <td>{{ $rekening->nasabah_no_hp }}</td>
                        <td style="text-align: right;">Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</td>
                        <td>
                            @if ($tertutup)
                                <span class="badge-pill red">Tertutup</span>
                            @else
                                <span class="badge-pill green">Aktif</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <a class="btn-ui btn-ui-outline btn-ui-sm" href="{{ route('tabungan.mutasi', ['no_rek' => $rekening->no_rek]) }}">Mutasi</a>
                                @unless ($tertutup)
                                    <a class="btn-ui btn-ui-outline btn-ui-sm" dusk="ubah-nasabah-{{ $rekening->nasabah_id }}" href="{{ route('tabungan.nasabah.ubah', $rekening->nasabah_id) }}">Ubah</a>
                                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" dusk="tutup-rekening-{{ $rekening->rekening_id }}" style="color: #B91C1C;"
                                        onclick="document.getElementById('tutup-{{ $rekening->rekening_id }}').style.display = 'block'; this.style.display = 'none';">
                                        Tutup Rekening
                                    </button>
                                @endunless
                            </div>

                            @unless ($tertutup)
                                <div id="tutup-{{ $rekening->rekening_id }}" dusk="panel-tutup-{{ $rekening->rekening_id }}"
                                     style="display: none; margin-top: 8px; padding: 10px 12px; border-radius: 8px; background: #FEF2F2; color: #991B1B; font-size: 12.5px; max-width: 340px;">
                                    @if (!$saldoNol)
                                        {{ \App\Services\RekeningService::pesanSaldoHarusNol((float) $rekening->saldo) }}
                                    @elseif ($adaDraftPending)
                                        Rekening tidak dapat ditutup: masih ada draft transaksi yang menunggu verifikasi.
                                    @else
                                        <form method="POST" action="{{ route('tabungan.rekening.tutup', $rekening->rekening_id) }}">
                                            @csrf
                                            <div style="margin-bottom: 8px;">Tutup rekening {{ $rekening->no_rek }}? Rekening tidak dapat menerima transaksi lagi.</div>
                                            <button type="submit" class="btn-ui btn-ui-sm" dusk="konfirmasi-tutup-{{ $rekening->rekening_id }}" style="background: #DC2626; color: #fff; border-color: #DC2626;">Konfirmasi Tutup</button>
                                        </form>
                                    @endif
                                </div>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-placeholder">Tidak ada rekening pada daftar ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($activeRekening->hasPages())
        <div style="padding: 16px 20px;">{{ $activeRekening->links() }}</div>
    @endif
</div>
@endsection
