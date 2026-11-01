@extends('layouts.app')

@section('title', 'Portal Nasabah Pegawai')
@section('breadcrumb', 'Rekening & Pinjaman')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Selamat Datang, {{ $nasabah?->nama_nasabah ?? $user->name }}</h1>
        <p>Layanan rekening & fasilitas pinjaman karyawan &bull; Unit: <strong>{{ $nasabah?->kelas_unit ?? '-' }}</strong></p>
    </div>
    @if (($rekening?->status_rekening ?? 'pending') === 'aktif')
        <div class="welcome-actions">
            <a href="{{ route('nasabah.saldo') }}" class="btn-banner-white">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                <span>Riwayat Mutasi</span>
            </a>
            <a href="{{ route('nasabah.angsuran') }}" class="btn-banner-trans">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>Jadwal Angsuran</span>
            </a>
        </div>
    @else
        <div class="welcome-actions">
            <button type="button" onclick="openAktivasiModal()" class="btn-banner-trans" style="cursor:pointer; display:inline-flex; align-items:center; gap:8px; background:rgba(254, 243, 199, 0.2); border:1px solid #FDE68A; color:#FEF3C7; font-weight:700;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Syarat & Aktivasi Rekening</span>
                <span style="background:#F59E0B; color:#FFFFFF; border-radius:10px; font-size:10.5px; padding:2px 7px; font-weight:800;">Pending</span>
            </button>
        </div>
    @endif
</div>

{{-- ── POPUP MODAL PANDUAN AKTIVASI (MENGGANTIKAN BANNER BESAR) ── --}}
@if (($rekening?->status_rekening ?? 'pending') === 'pending')
    @include('nasabah.partials.modal_aktivasi')
@endif

{{-- ── SHOWCASE ROW ── --}}
<div class="nasabah-showcase-grid">

    {{-- LEFT: VIRTUAL PASSBOOK CARD ── --}}
    <div class="passbook-card">
        <div class="passbook-top">
            <div class="passbook-brand-badge">
                <img src="{{ asset('images/sipintar_mark.svg') }}" alt="SIPINTAR" style="width: 24px; height: 24px; border-radius: 6px; background: #FFFFFF; padding: 2px;">
                <span class="passbook-bank-name">SIPINTAR BANK MINI</span>
            </div>
            <div class="passbook-chip-wrap">
                <svg class="contactless-waves" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8.5 16.5a5 5 0 0 1 0-7"/>
                    <path d="M12 19a9 9 0 0 0 0-14"/>
                    <path d="M15.5 21.5a13 13 0 0 0 0-19"/>
                </svg>
                <div class="passbook-chip"></div>
            </div>
        </div>
        <div class="passbook-mid">
            <div class="passbook-rek-label">Nomor Rekening Nasabah</div>
            <div class="passbook-rek-number">{{ $rekening?->no_rek ?? '1001-2026-00000' }}</div>
        </div>
        <div class="passbook-bottom">
            <div>
                <div class="passbook-rek-label">Pemegang Rekening</div>
                <div class="passbook-holder-name">{{ $nasabah?->nama_nasabah ?? $user->name }}</div>
                <div class="passbook-holder-sub">Pegawai / Guru &bull; {{ $nasabah?->kelas_unit ?? 'Nasabah Terdaftar' }}</div>
            </div>
            <div class="passbook-balance-wrap">
                <div class="passbook-rek-label" style="text-align: right;">Total Saldo Efektif</div>
                <div class="passbook-balance-amount">
                    Rp {{ number_format($rekening?->saldo ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: 4-METRIC STATS GRID ── --}}
    <div class="nasabah-stats-grid">
        {{-- Card 1: No Rekening --}}
        <div class="nasabah-stat-box stat-emerald">
            <div class="metric-watermark"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
            <div class="nasabah-stat-header">
                <span class="nasabah-stat-title">Rekening Nasabah</span>
                <button type="button" class="btn-copy-rek" onclick="copyRekening('{{ $rekening?->no_rek ?? '' }}')" title="Salin">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span id="copy-status-text">Salin</span>
                </button>
            </div>
            <div class="nasabah-stat-val" style="font-family: monospace; font-size: 1.25rem;">{{ $rekening?->no_rek ?? 'Belum Terbit' }}</div>
        </div>

        {{-- Card 2: Status Rekening --}}
        <div class="nasabah-stat-box {{ ($rekening?->status_rekening ?? 'pending') === 'pending' ? 'stat-amber' : 'stat-blue' }}">
            <div class="metric-watermark"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <div class="nasabah-stat-header"><span class="nasabah-stat-title">Status Layanan</span></div>
            <div class="nasabah-stat-val" style="font-size:1.25rem; {{ ($rekening?->status_rekening ?? 'pending') === 'pending' ? 'color:#D97706;' : '' }}">
                {{ ucfirst($rekening?->status_rekening ?? 'Aktif') }}
            </div>
            @if (($rekening?->status_rekening ?? 'pending') === 'pending')
                <div style="margin-top:4px;">
                    <button type="button" onclick="openAktivasiModal()" style="background:none; border:none; padding:0; color:#B45309; font-size:11.5px; font-weight:700; text-decoration:underline; cursor:pointer;">
                        Lihat Panduan &bull;
                    </button>
                </div>
            @endif
        </div>

        {{-- Card 3: Kewajiban Pinjaman --}}
        <div class="nasabah-stat-box stat-amber">
            <div class="metric-watermark"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div class="nasabah-stat-header"><span class="nasabah-stat-title">Kewajiban Pinjaman</span></div>
            <div class="nasabah-stat-val" style="font-size:1.1rem;">
                @if ($pinjamanAktif)
                    Rp {{ number_format($pinjamanAktif->nominal_pinjaman, 0, ',', '.') }}
                @else
                    <span style="color: var(--color-success); font-weight: 700;">Nihil ✓</span>
                @endif
            </div>
        </div>

        {{-- Card 4: Angsuran Terdekat --}}
        <div class="nasabah-stat-box stat-indigo">
            <div class="metric-watermark"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <div class="nasabah-stat-header"><span class="nasabah-stat-title">Angsuran Terdekat</span></div>
            <div class="nasabah-stat-val" style="font-size:1rem;">
                @php $nextAngsuran = $jadwalAngsuran->first(fn($a) => in_array($a->status_angsuran ?? ($a->status_bayar ?? ''), ['belum', 'belum_bayar'])); @endphp
                @if ($nextAngsuran)
                    {{ \Carbon\Carbon::parse($nextAngsuran->tgl_jatuh_tempo ?? ($nextAngsuran->jatuh_tempo ?? now()))->format('d M Y') }}
                @elseif ($pinjamanAktif)
                    <span style="color: var(--color-success);">Lunas ✓</span>
                @else
                    Tidak Ada
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── PINJAMAN INFO ALERT (jika ada pinjaman aktif) ── --}}
@if ($pinjamanAktif)
<div class="content-card" style="margin-bottom:24px; border-left: 4px solid #f59e0b;">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Pinjaman Aktif</h2>
            <div style="font-size:11.5px; color:var(--text-muted);">Informasi fasilitas pinjaman yang sedang berjalan</div>
        </div>
        <a href="{{ route('nasabah.pinjaman') }}" class="btn-ui btn-ui-outline btn-ui-sm">Detail Pinjaman</a>
    </div>
    <div class="card-body-content" style="padding:16px 20px;">
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
            <div>
                <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Nominal Pinjaman</div>
                <div style="font-weight:700; font-size:1.1rem;">Rp {{ number_format($pinjamanAktif->nominal_pinjaman, 0, ',', '.') }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Status</div>
                <div>
                    <span class="badge-pill {{ in_array($pinjamanAktif->status_pinjaman, ['cair']) ? 'green' : 'amber' }}">
                        {{ ucfirst(str_replace('_', ' ', $pinjamanAktif->status_pinjaman)) }}
                    </span>
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted); margin-bottom:4px;">Tgl. Pengajuan</div>
                <div style="font-weight:600;">{{ \Carbon\Carbon::parse($pinjamanAktif->tgl_pengajuan)->format('d M Y') }}</div>
            </div>
        </div>

        @if ($jadwalAngsuran->count() > 0)
        <div style="margin-top:16px;">
            <div style="font-size:12px; font-weight:600; color:var(--text-heading); margin-bottom:10px;">Jadwal Angsuran Mendatang</div>
            <div style="display:flex; flex-direction:column; gap:6px;">
                @foreach ($jadwalAngsuran->take(4) as $angsuran)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:#FAFAFA; border-radius:8px; border:1px solid var(--border-subtle);">
                    <div style="font-size:12.5px;">
                        Angsuran ke-<strong>{{ $angsuran->angsuran_ke ?? $loop->iteration }}</strong> &bull;
                        {{ \Carbon\Carbon::parse($angsuran->tgl_jatuh_tempo ?? ($angsuran->jatuh_tempo ?? now()))->format('d M Y') }}
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-weight:700; font-size:12.5px;">Rp {{ number_format($angsuran->total_angsuran ?? ($angsuran->nominal_angsuran ?? (($angsuran->pokok ?? 0) + ($angsuran->bunga ?? 0))), 0, ',', '.') }}</span>
                        <span class="badge-pill {{ in_array($angsuran->status_angsuran ?? ($angsuran->status_bayar ?? ''), ['lunas']) ? 'green' : 'amber' }}" style="font-size:10px;">
                            {{ ucfirst(str_replace('_', ' ', $angsuran->status_angsuran ?? ($angsuran->status_bayar ?? 'Belum Bayar'))) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@elseif (($rekening?->status_rekening ?? 'pending') === 'aktif')
<div class="content-card" style="margin-bottom: 24px; background: linear-gradient(135deg, #F0FDF4 0%, #FFFFFF 100%); border: 1.5px dashed #A7F3D0;">
    <div style="padding: 22px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 46px; height: 46px; border-radius: 12px; background: #ECFDF5; color: #0D5C46; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: #065F46; margin: 0 0 4px;">Fasilitas Pinjaman Pegawai Tersedia</h3>
                <p style="font-size: 12.5px; color: var(--text-muted); margin: 0;">Anda belum memiliki pinjaman aktif. Dapatkan fasilitas kredit permodalan/kebutuhan pegawai dengan jasa flat 2% per bulan dan tenor fleksibel.</p>
            </div>
        </div>
        <a href="{{ route('nasabah.pinjaman.ajukan') }}" class="btn-ui btn-ui-primary" style="padding: 10px 20px; font-weight: 700;">
            Ajukan Pinjaman Sekarang
        </a>
    </div>
</div>
@endif

{{-- ── TWO-COLUMN ACTIVITY ROW ── --}}
<div class="nasabah-activity-grid">

    {{-- LEFT: Riwayat Transaksi --}}
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Mutasi Tabungan Terakhir</h2>
                <div style="font-size:11.5px;color:var(--text-muted);">5 transaksi terbaru pada rekening Anda</div>
            </div>
            @if (($rekening?->status_rekening ?? 'pending') === 'aktif')
                <a href="{{ route('nasabah.saldo') }}" class="btn-ui btn-ui-outline btn-ui-sm">Rincian Mutasi</a>
            @endif
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Waktu Transaksi</th>
                        <th>Jenis</th>
                        <th style="text-align:right;">Mutasi (Rp)</th>
                        <th style="text-align:right;">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksi as $t)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($t->tgl_transaksi)->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge-pill {{ $t->kredit > 0 ? 'green' : 'amber' }}">
                                    {{ $t->nama_jenis_transaksi ?? ($t->kredit > 0 ? 'Setoran' : 'Penarikan') }}
                                </span>
                            </td>
                            <td style="text-align:right;font-weight:700;color:{{ $t->kredit > 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                                {{ $t->kredit > 0 ? '+ Rp '.number_format($t->kredit,0,',','.') : '- Rp '.number_format($t->debit,0,',','.') }}
                            </td>
                            <td style="text-align:right;font-weight:700;">Rp {{ number_format($t->saldo_akhir,0,',','.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-placeholder" style="padding:32px 16px;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 6px;color:var(--text-faint);display:block;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                    Belum ada mutasi tabungan tercatat.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- RIGHT: Layanan Pegawai --}}
    <div class="content-card" style="margin-bottom:0;display:flex;flex-direction:column;justify-content:space-between;">
        <div>
            <div class="card-header-bar">
                <h2 class="card-heading">Layanan Pegawai Bank Mini</h2>
            </div>
            <div class="card-body-content" style="padding:18px 20px;">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div style="width:26px;height:26px;border-radius:6px;background:#ECFDF5;color:#0D5C46;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">1</div>
                        <div style="font-size:12.5px;color:var(--text-body);line-height:1.5;"><strong>Setor & Tarik Tabungan:</strong> Transaksi kas tabungan langsung di loket kasir Bank Mini Sekolah pada jam operasional.</div>
                    </div>
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div style="width:26px;height:26px;border-radius:6px;background:#FEF3C7;color:#92400E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">2</div>
                        <div style="font-size:12.5px;color:var(--text-body);line-height:1.5;"><strong>Pengajuan Pinjaman:</strong> Pengajuan kredit pegawai diajukan langsung ke Bank Mini Sekolah (mandiri via SIPINTAR atau via Bendahara Bank Mini).</div>
                    </div>
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div style="width:26px;height:26px;border-radius:6px;background:#EFF6FF;color:#1e40af;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">3</div>
                        <div style="font-size:12.5px;color:var(--text-body);line-height:1.5;"><strong>Pengembalian Angsuran:</strong> Fleksibel melalui potong gaji bulanan oleh Bendahara Sekolah atau setor tunai mandiri di kasir Bank Mini.</div>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding:16px 20px;border-top:1px solid var(--border-subtle);background:#FAFAFA;border-radius:0 0 var(--radius-lg) var(--radius-lg);">
            @if (($rekening?->status_rekening ?? 'pending') === 'aktif')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <a href="{{ route('nasabah.saldo') }}" class="btn-ui btn-ui-outline btn-ui-sm" style="width:100%; justify-content:center;"><span>Mutasi Rekening</span></a>
                    <a href="{{ route('nasabah.pinjaman') }}" class="btn-ui btn-ui-primary btn-ui-sm" style="width:100%; justify-content:center;"><span>Info Pinjaman</span></a>
                </div>
            @else
                <div style="background:#FEF3C7; border:1px solid #FDE68A; border-radius:8px; padding:10px 14px; text-align:center; color:#92400E; font-size:12px; font-weight:600;">
                    Layanan Transaksi & Fasilitas Pinjaman Terkunci (Menunggu Aktivasi Loket)
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function copyRekening(noRek) {
    if (!noRek) return;
    navigator.clipboard.writeText(noRek).then(() => {
        const btn = document.getElementById('copy-status-text');
        if (btn) {
            btn.textContent = 'Tersalin!';
            btn.style.color = '#059669';
            setTimeout(() => { btn.textContent = 'Salin'; btn.style.color = ''; }, 2000);
        }
    });
}
</script>

@endsection
