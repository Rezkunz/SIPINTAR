@extends('layouts.app')

@section('title', 'Informasi Pinjaman')
@section('breadcrumb', 'Info Pinjaman')

@section('content')

<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <div class="welcome-badge-tag">Fasilitas Pinjaman Pegawai</div>
        <h1>Informasi Pinjaman & Angsuran</h1>
        <p>Riwayat dan status lengkap fasilitas kredit Anda sebagai nasabah pegawai</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('dashboard') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>
</div>

{{-- PINJAMAN AKTIF --}}
@if ($pinjamanAktif)
<div class="content-card" style="margin-bottom: 24px; border-left: 4px solid #16a34a;">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Pinjaman Aktif</h2>
            <div style="font-size:11.5px;color:var(--text-muted);">Detail fasilitas pinjaman yang sedang berjalan</div>
        </div>
        <span class="badge-pill {{ in_array($pinjamanAktif->status_pinjaman, ['cair']) ? 'green' : 'amber' }}" style="font-size:12px;">
            {{ ucfirst(str_replace('_',' ',$pinjamanAktif->status_pinjaman)) }}
        </span>
    </div>
    <div class="card-body-content" style="padding:20px;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:16px; margin-bottom:20px;">
            <div class="nasabah-stat-box stat-emerald" style="padding:16px;">
                <div style="font-size:11px; color:var(--text-muted);">Nominal Pinjaman</div>
                <div style="font-size:1.3rem; font-weight:800; margin-top:4px;">Rp {{ number_format($pinjamanAktif->nominal_pinjaman, 0, ',', '.') }}</div>
            </div>
            <div class="nasabah-stat-box stat-amber" style="padding:16px;">
                <div style="font-size:11px; color:var(--text-muted);">Sisa Angsuran</div>
                <div style="font-size:1.3rem; font-weight:800; margin-top:4px;">
                    {{ $jadwalAngsuran->filter(fn($a) => in_array($a->status_angsuran ?? ($a->status_bayar ?? ''), ['belum', 'belum_bayar']))->count() }} bulan
                </div>
            </div>
            <div class="nasabah-stat-box stat-blue" style="padding:16px;">
                <div style="font-size:11px; color:var(--text-muted);">Tgl. Pengajuan</div>
                <div style="font-size:1rem; font-weight:700; margin-top:4px;">{{ \Carbon\Carbon::parse($pinjamanAktif->tgl_pengajuan)->format('d M Y') }}</div>
            </div>
            <div class="nasabah-stat-box stat-indigo" style="padding:16px;">
                <div style="font-size:11px; color:var(--text-muted);">Jatuh Tempo Berikutnya</div>
                <div style="font-size:1rem; font-weight:700; margin-top:4px;">
                    @php $next = $jadwalAngsuran->first(fn($a) => in_array($a->status_angsuran ?? ($a->status_bayar ?? ''), ['belum', 'belum_bayar'])); @endphp
                    {{ $next ? \Carbon\Carbon::parse($next->tgl_jatuh_tempo ?? ($next->jatuh_tempo ?? now()))->format('d M Y') : 'Lunas ✓' }}
                </div>
            </div>
        </div>

        {{-- Jadwal Angsuran Lengkap --}}
        @if ($jadwalAngsuran->count() > 0)
        <h3 style="font-size:13px; font-weight:700; color:var(--text-heading); margin-bottom:12px;">Jadwal Angsuran Lengkap</h3>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:right;">Angsuran Pokok</th>
                        <th style="text-align:right;">Bunga</th>
                        <th style="text-align:right;">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jadwalAngsuran as $i => $angsuran)
                    <tr>
                        <td>{{ $angsuran->angsuran_ke ?? ($i + 1) }}</td>
                        <td>{{ \Carbon\Carbon::parse($angsuran->tgl_jatuh_tempo ?? ($angsuran->jatuh_tempo ?? now()))->format('d M Y') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($angsuran->nominal_angsuran ?? ($angsuran->pokok ?? 0), 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($angsuran->bunga ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align:right;font-weight:700;">Rp {{ number_format($angsuran->total_angsuran ?? (($angsuran->nominal_angsuran ?? ($angsuran->pokok ?? 0)) + ($angsuran->bunga ?? 0)), 0, ',', '.') }}</td>
                        <td>
                            <span class="badge-pill {{ in_array($angsuran->status_angsuran ?? ($angsuran->status_bayar ?? ''), ['lunas']) ? 'green' : (in_array($angsuran->status_angsuran ?? ($angsuran->status_bayar ?? ''), ['terlambat']) ? 'red' : 'amber') }}">
                                {{ ucfirst(str_replace('_', ' ', $angsuran->status_angsuran ?? ($angsuran->status_bayar ?? 'Belum Bayar'))) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-placeholder">Belum ada data jadwal angsuran.</div>
        @endif
    </div>
</div>
@endif

{{-- RIWAYAT PINJAMAN SEBELUMNYA --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Riwayat Semua Pinjaman</h2>
        </div>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Tgl. Pengajuan</th>
                    <th style="text-align:right;">Nominal</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pinjaman as $p)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($p->tgl_pengajuan)->format('d M Y') }}</td>
                    <td style="text-align:right; font-weight:700;">Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge-pill {{ in_array($p->status_pinjaman, ['cair','lunas']) ? 'green' : ($p->status_pinjaman === 'ditolak' ? 'red' : 'amber') }}">
                            {{ ucfirst(str_replace('_', ' ', $p->status_pinjaman)) }}
                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--text-muted);">{{ $p->keterangan ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-placeholder" style="padding:32px 16px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 6px;display:block;color:var(--text-faint);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Belum ada riwayat pinjaman.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
