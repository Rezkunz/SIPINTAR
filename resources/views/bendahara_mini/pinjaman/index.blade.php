@extends('layouts.app')

@section('title', 'Daftar Pinjaman Pegawai')
@section('breadcrumb', 'Pinjaman Pegawai')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Pengelolaan Pinjaman Pegawai</h1>
        <p>Kelola seluruh siklus pembiayaan guru dan tenaga kependidikan dari pengajuan hingga pelunasan.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('bbm.pinjaman.ajukan') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Ajukan Pinjaman</span>
        </a>
        <a href="{{ route('bbm.pinjaman.kelayakan') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/></svg>
            <span>Penilaian Kelayakan</span>
        </a>
    </div>
</div>

{{-- ── 4 FINANCIAL METRICS ── --}}
<div class="metric-grid">
    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Permohonan</span>
        </div>
        <div class="metric-value">{{ $totalPengajuan }}</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Disetujui (Layak)</span>
        </div>
        <div class="metric-value">{{ $totalLayak }}</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Pinjaman Berjalan (Cair)</span>
        </div>
        <div class="metric-value">{{ $totalCair }}</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Terselesaikan (Lunas)</span>
        </div>
        <div class="metric-value">{{ $totalLunas }}</div>
    </div>
</div>

{{-- ── TABEL DAFTAR PINJAMAN ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Daftar Pinjaman Pegawai</h2>
            <div style="font-size:12px; color:var(--text-muted);">Data permohonan pinjaman dengan jasa 2% flat per bulan</div>
        </div>

        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            {{-- Search Box --}}
            <div style="position:relative; width:240px;">
                <input type="text" class="form-input" placeholder="Cari nama atau ID pinjaman..." 
                       oninput="filterTableRows(this, 'tableDaftarPinjaman')"
                       style="padding:6px 12px; font-size:12.5px; border-radius:8px; width:100%;">
            </div>

            {{-- Segmented Pill Tabs Filter --}}
            <div style="background:#F1F5F9; border:1px solid #E2E8F0; border-radius:9px; padding:3px; display:inline-flex; gap:3px;">
                <a href="{{ route('bbm.pinjaman.index') }}" style="text-decoration:none; font-size:12px; padding:5px 12px; border-radius:6px; font-weight:700; transition:all 0.15s ease; {{ !$status ? 'background:#0D5C46; color:#FFFFFF; box-shadow:0 1px 2px rgba(0,0,0,0.1);' : 'color:#475569; background:transparent;' }}">Semua</a>
                <a href="{{ route('bbm.pinjaman.index', ['status' => 'diajukan']) }}" style="text-decoration:none; font-size:12px; padding:5px 12px; border-radius:6px; font-weight:700; transition:all 0.15s ease; {{ $status === 'diajukan' ? 'background:#0D5C46; color:#FFFFFF; box-shadow:0 1px 2px rgba(0,0,0,0.1);' : 'color:#475569; background:transparent;' }}">Diajukan</a>
                <a href="{{ route('bbm.pinjaman.index', ['status' => 'layak']) }}" style="text-decoration:none; font-size:12px; padding:5px 12px; border-radius:6px; font-weight:700; transition:all 0.15s ease; {{ $status === 'layak' ? 'background:#0D5C46; color:#FFFFFF; box-shadow:0 1px 2px rgba(0,0,0,0.1);' : 'color:#475569; background:transparent;' }}">Layak</a>
                <a href="{{ route('bbm.pinjaman.index', ['status' => 'cair']) }}" style="text-decoration:none; font-size:12px; padding:5px 12px; border-radius:6px; font-weight:700; transition:all 0.15s ease; {{ $status === 'cair' ? 'background:#0D5C46; color:#FFFFFF; box-shadow:0 1px 2px rgba(0,0,0,0.1);' : 'color:#475569; background:transparent;' }}">Cair</a>
                <a href="{{ route('bbm.pinjaman.index', ['status' => 'lunas']) }}" style="text-decoration:none; font-size:12px; padding:5px 12px; border-radius:6px; font-weight:700; transition:all 0.15s ease; {{ $status === 'lunas' ? 'background:#0D5C46; color:#FFFFFF; box-shadow:0 1px 2px rgba(0,0,0,0.1);' : 'color:#475569; background:transparent;' }}">Lunas</a>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableDaftarPinjaman">
            <thead>
                <tr>
                    <th>ID Pinjaman</th>
                    <th>Tgl Pengajuan</th>
                    <th>Peminjam / Unit Kerja</th>
                    <th style="text-align: right;">Plafon Pinjaman</th>
                    <th>Tenor</th>
                    <th>Metode Bayar</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pinjamanList as $p)
                    <tr>
                        <td>
                            <span style="font-weight:700; color:var(--text-heading); font-family:monospace; font-size:13px;">
                                #PINJ-{{ str_pad($p->pinjaman_id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td style="font-size:13px;">{{ \Carbon\Carbon::parse($p->tgl_pengajuan)->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $p->nama_nasabah }}</div>
                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">
                                {{ $p->kelas_unit ?? 'Pegawai' }} <span style="color:#CBD5E1; margin:0 3px;">/</span> {{ $p->nasabah_hp }}
                            </div>
                        </td>
                        <td style="text-align: right; font-weight:800; color:var(--text-heading);">
                            Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}
                        </td>
                        <td style="font-size:13px;">{{ $p->tenor }} Bulan</td>
                        <td>
                            @if ($p->metode_bayar === 'potong_gaji')
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 9px; border-radius:6px; background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0;">
                                    Potong Gaji
                                </span>
                            @else
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 9px; border-radius:6px; background:#FFFBEB; color:#92400E; border:1px solid #FDE68A;">
                                    Setor Tunai
                                </span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($p->status_pinjaman === 'diajukan')
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:6px; background:#FEF3C7; color:#92400E; border:1px solid #FDE68A;">
                                    Diajukan
                                </span>
                            @elseif ($p->status_pinjaman === 'layak')
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:6px; background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE;">
                                    Layak
                                </span>
                            @elseif ($p->status_pinjaman === 'ditolak')
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:6px; background:#FEE2E2; color:#991B1B; border:1px solid #FECACA;">
                                    Ditolak
                                </span>
                            @elseif ($p->status_pinjaman === 'cair')
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:6px; background:#DCFCE7; color:#166534; border:1px solid #BBF7D0;">
                                    Cair
                                </span>
                            @elseif ($p->status_pinjaman === 'lunas')
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:6px; background:#F1F5F9; color:#475569; border:1px solid #E2E8F0;">
                                    Lunas
                                </span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($p->status_pinjaman === 'diajukan')
                                <a href="{{ route('bbm.pinjaman.kelayakan') }}" class="btn-ui btn-ui-sm btn-ui-outline" style="text-decoration:none; font-weight:600;">
                                    Beri Penilaian
                                </a>
                            @elseif ($p->status_pinjaman === 'layak')
                                <a href="{{ route('bbm.pinjaman.cair') }}" class="btn-ui btn-ui-sm btn-ui-primary" style="text-decoration:none; font-weight:700;">
                                    Cairkan Dana
                                </a>
                            @else
                                <a href="{{ route('bbm.kartu.index', ['pinjaman_id' => $p->pinjaman_id]) }}" class="btn-ui btn-ui-sm btn-ui-outline" style="text-decoration:none; font-weight:600;">
                                    Kartu Piutang
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-placeholder" style="padding:40px 16px; text-align:center;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 8px; color:var(--text-faint); display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                                Tidak ada data pinjaman yang ditemukan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pinjamanList->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-subtle);">
            {{ $pinjamanList->links() }}
        </div>
    @endif
</div>

@endsection

