@extends('layouts.app')

@section('title', 'Kartu Piutang Digital')
@section('breadcrumb', 'Kartu Piutang')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Kartu Piutang Digital Pegawai</h1>
        <p>Kartu kendali riwayat pembiayaan pegawai, jadwal jatuh tempo tagihan cicilan, dan rekam jejak angsuran sah per debitur.</p>
    </div>
</div>

@if (!$pinjamanTerpilih)

    {{-- ========================================================================= --}}
    {{-- TAMPILAN 1: GALERI VISUAL KARTU PIUTANG DEBITUR (KARTU TIAP NASABAH)     --}}
    {{-- ========================================================================= --}}
    <div class="content-card" style="margin-bottom: 24px;">
        <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
            <div>
                <h2 class="card-heading">Daftar Kartu Debitur Pembiayaan</h2>
                <div style="font-size:12px; color:var(--text-muted);">Pilih kartu debitur untuk melihat detail matriks jadwal tagihan dan riwayat transaksi</div>
            </div>
            
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div style="width:280px; position:relative;">
                    <input type="text" id="searchKartu" class="form-input" placeholder="Cari nama, unit, ID pinjaman..." 
                           oninput="filterKartuDebitur(this.value)"
                           style="padding:8px 14px; font-size:13px; border-radius:8px; width:100%;">
                </div>
                <span class="badge-pill green">{{ $daftarPeminjam->count() }} Debitur Terdaftar</span>
            </div>
        </div>

        {{-- Grid Kartu Nasabah --}}
        <div id="gridKartuDebitur" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap:20px; padding:6px 0;">
            @forelse ($daftarPeminjam as $dp)
                @php
                    $isLunas = $dp->status_pinjaman === 'lunas';
                @endphp
                <div class="kartu-debitur-item" 
                     data-search="{{ strtolower($dp->nama_nasabah . ' ' . $dp->kelas_unit . ' ' . $dp->pinjaman_id . ' ' . ($dp->no_rek ?? '')) }}"
                     style="background: #FFFFFF; border: 1.5px solid {{ $isLunas ? '#E2E8F0' : '#A7F3D0' }}; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); display: flex; flex-direction: column; transition: transform 0.15s ease, box-shadow 0.15s ease;">
                    
                    {{-- Header Kartu (Gaya Fintech Passbook) --}}
                    <div style="background: {{ $isLunas ? 'linear-gradient(135deg, #475569 0%, #334155 100%)' : 'linear-gradient(135deg, #0D5C46 0%, #064030 100%)' }}; padding: 16px 18px; color: #FFFFFF; position: relative; overflow: hidden;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; position: relative; z-index: 1;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:24px; height:24px; border-radius:5px; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                </div>
                                <span style="font-size:11px; font-weight:800; letter-spacing:0.04em; color:rgba(255,255,255,0.9);">KARTU PIUTANG</span>
                            </div>
                            <span style="font-size:10.5px; font-weight:800; padding:3px 9px; border-radius:999px; text-transform:uppercase; {{ $isLunas ? 'background:#E2E8F0; color:#334155;' : 'background:#DCFCE7; color:#166534;' }}">
                                {{ $isLunas ? 'Lunas Sempurna' : 'Aktif Berjalan' }}
                            </span>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:flex-end; position: relative; z-index: 1;">
                            <div>
                                <div style="font-size:11px; color:rgba(255,255,255,0.7); text-transform:uppercase; letter-spacing:0.04em;">Nomor Kontrak / Pinjaman</div>
                                <div style="font-size:15px; font-weight:800; font-family:monospace; color:#FEF08A; margin-top:2px;">
                                    #PINJ-{{ str_pad($dp->pinjaman_id, 4, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:10.5px; color:rgba(255,255,255,0.7);">Tenor / Metode</div>
                                <div style="font-size:12px; font-weight:700; color:#FFFFFF;">
                                    {{ $dp->tenor }} Bln &bull; {{ $dp->metode_bayar === 'potong_gaji' ? 'Payroll' : 'Tunai' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Body Kartu --}}
                    <div style="padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            {{-- Info Debitur --}}
                            <div style="margin-bottom: 14px;">
                                <div style="font-weight: 800; font-size: 15px; color: var(--text-heading); line-height: 1.3;">
                                    {{ $dp->nama_nasabah }}
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
                                    {{ $dp->kelas_unit ?? 'Pegawai SMKN 1 Lemahabang' }} &bull; Rek: <span style="font-family:monospace;">{{ $dp->no_rek ?? '-' }}</span>
                                </div>
                            </div>

                            {{-- Financial Metrics 3 Kolom --}}
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px;">
                                <div>
                                    <div style="font-size: 10.5px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Plafon</div>
                                    <div style="font-size: 13px; font-weight: 800; color: var(--text-heading); margin-top: 2px;">
                                        Rp {{ number_format($dp->nominal_pinjaman, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 10.5px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Terbayar</div>
                                    <div style="font-size: 13px; font-weight: 800; color: #0D5C46; margin-top: 2px;">
                                        Rp {{ number_format($dp->pokok_terbayar, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 10.5px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sisa Pokok</div>
                                    <div style="font-size: 13px; font-weight: 800; color: {{ $isLunas ? '#166534' : '#D97706' }}; margin-top: 2px;">
                                        Rp {{ number_format($dp->sisa_pokok, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar Angsuran --}}
                            <div style="margin-bottom: 16px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:11.5px; margin-bottom:5px;">
                                    <span style="color:var(--text-muted);">Kemajuan Pelunasan:</span>
                                    <strong style="color:var(--text-heading);">{{ $dp->lunas_jadwal }} dari {{ $dp->total_jadwal }} Bulan ({{ $dp->persen_lunas }}%)</strong>
                                </div>
                                <div style="width:100%; height:7px; background:#E2E8F0; border-radius:999px; overflow:hidden;">
                                    <div style="width:{{ $dp->persen_lunas }}%; height:100%; background:{{ $isLunas ? '#10B981' : '#0D5C46' }}; border-radius:999px;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Buka Kartu --}}
                        <div style="padding-top: 12px; border-top: 1px dashed #CBD5E1;">
                            <a href="{{ route('bbm.kartu.index', ['pinjaman_id' => $dp->pinjaman_id]) }}" 
                               class="btn-ui btn-ui-sm {{ $isLunas ? 'btn-ui-outline' : 'btn-ui-primary' }}" 
                               style="width: 100%; justify-content: center; font-weight: 700; padding: 9px 14px; text-decoration: none;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                <span>Buka Kartu Piutang &amp; Matriks</span>
                            </a>
                        </div>
                    </div>

                </div>
            @empty
                <div style="grid-column: 1 / -1; padding: 48px; text-align: center; color: var(--text-muted);">
                    Belum ada fasilitas pinjaman yang dicairkan.
                </div>
            @endforelse
        </div>
    </div>

@else

    {{-- ========================================================================= --}}
    {{-- TAMPILAN 2: RINCIAN LENGKAP KARTU PIUTANG DEBITUR TERPILIH                 --}}
    {{-- ========================================================================= --}}
    
    {{-- Bar Navigasi Kembali --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <a href="{{ route('bbm.kartu.index') }}" class="btn-ui btn-ui-outline" style="text-decoration:none; font-weight:700;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali ke Galeri Kartu
        </a>
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:12px; color:var(--text-muted);">Ganti Debitur:</span>
            <select class="form-select" onchange="if(this.value) window.location.href='{{ route('bbm.kartu.index') }}?pinjaman_id=' + this.value;" style="padding:6px 12px; font-size:12.5px; border-radius:6px;">
                @foreach ($daftarPeminjam as $dp)
                    <option value="{{ $dp->pinjaman_id }}" {{ $pinjamanTerpilih->pinjaman_id == $dp->pinjaman_id ? 'selected' : '' }}>
                        {{ $dp->nama_nasabah }} (#PINJ-{{ $dp->pinjaman_id }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ── 4 KARTU METRIK DEBITUR ── --}}
    <div class="metric-grid" style="margin-bottom: 24px;">
        <div class="metric-card card-blue">
            <div class="metric-header">
                <span class="metric-title">Plafon Pinjaman Awal</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($pinjamanTerpilih->nominal_pinjaman, 0, ',', '.') }}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Tenor: {{ $pinjamanTerpilih->tenor }} Bulan &bull; Jasa 2% Flat</div>
        </div>

        <div class="metric-card card-emerald">
            <div class="metric-header">
                <span class="metric-title">Pokok Terbayar</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalPokokTerbayar, 0, ',', '.') }}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Jasa Diterima: Rp {{ number_format($totalJasaTerbayar, 0, ',', '.') }}</div>
        </div>

        <div class="metric-card card-amber">
            <div class="metric-header">
                <span class="metric-title">Sisa Pokok Piutang</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($sisaPokokPiutang, 0, ',', '.') }}</div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Dihitung dinamis: Plafon - Pokok Terbayar</div>
        </div>

        <div class="metric-card card-purple">
            <div class="metric-header">
                <span class="metric-title">Status Pelunasan</span>
            </div>
            <div class="metric-value">
                @if ($pinjamanTerpilih->status_pinjaman === 'lunas')
                    <span class="badge-pill purple" style="font-size:13px; padding:4px 10px;">Lunas Sempurna</span>
                @else
                    <span class="badge-pill green" style="font-size:13px; padding:4px 10px;">Aktif Berjalan</span>
                @endif
            </div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Metode: {{ $pinjamanTerpilih->metode_bayar === 'potong_gaji' ? 'Potong Payroll' : 'Tunai Kasir' }}</div>
        </div>
    </div>

    {{-- ── IDENTITAS DEBITUR BANNER ── --}}
    <div class="content-card" style="margin-bottom: 24px; padding: 20px 24px; background: #FFFFFF; border: 1.5px solid var(--border-subtle); border-radius: 12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="width:48px; height:48px; border-radius:50%; background:#0D5C46; color:#FFFFFF; font-weight:800; font-size:17px; display:flex; align-items:center; justify-content:center;">
                    {{ strtoupper(substr($pinjamanTerpilih->nama_nasabah, 0, 2)) }}
                </div>
                <div>
                    <h2 style="margin:0; font-size:17px; font-weight:800; color:var(--text-heading);">
                        {{ $pinjamanTerpilih->nama_nasabah }}
                    </h2>
                    <div style="font-size:12.5px; color:var(--text-muted); margin-top:2px;">
                        Unit: <strong>{{ $pinjamanTerpilih->kelas_unit ?? 'Pegawai SMKN 1 Lemahabang' }}</strong> &bull; No. HP: {{ $pinjamanTerpilih->no_hp ?? '-' }} &bull; Akad: #PINJ-{{ str_pad($pinjamanTerpilih->pinjaman_id, 4, '0', STR_PAD_LEFT) }}
                    </div>
                </div>
            </div>

            <div style="text-align:right;">
                <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Tanggal Pencairan Kas</div>
                <div style="font-size:13.5px; font-weight:800; color:#0D5C46; margin-top:2px;">
                    {{ $pinjamanTerpilih->tgl_cair ? \Carbon\Carbon::parse($pinjamanTerpilih->tgl_cair)->format('d F Y') : '-' }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── 2 TABEL DUA KOLOM: MATRIKS JADWAL & RIWAYAT TRANSAKSI ── --}}
    <div style="display:grid; grid-template-columns: 3fr 2fr; gap:24px; align-items:start;">

        {{-- ── TABEL JADWAL ANGSURAN RESMI ── --}}
        <div class="content-card">
            <div class="card-header-bar">
                <div>
                    <h2 class="card-heading">Matriks Jadwal Tagihan Angsuran</h2>
                    <div style="font-size:12px; color:var(--text-muted);">Jadwal jatuh tempo dengan jasa 2% flat per bulan</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Ke</th>
                            <th>Jatuh Tempo</th>
                            <th style="text-align: right;">Pokok</th>
                            <th style="text-align: right;">Jasa 2%</th>
                            <th style="text-align: right;">Total Cicilan</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jadwalList as $j)
                            @php $isBayar = $j->status_bayar === 'lunas'; @endphp
                            <tr style="{{ $isBayar ? 'background:#F0FDF4;' : '' }}">
                                <td style="font-weight:700;">Ke-{{ $j->angsuran_ke }}</td>
                                <td style="font-size:13px;">{{ \Carbon\Carbon::parse($j->jatuh_tempo)->format('d/m/Y') }}</td>
                                <td style="text-align: right;">Rp {{ number_format($j->pokok, 0, ',', '.') }}</td>
                                <td style="text-align: right; color:#047857;">Rp {{ number_format($j->bunga, 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight:700;">Rp {{ number_format($j->pokok + $j->bunga, 0, ',', '.') }}</td>
                                <td style="text-align: center;">
                                    <span class="badge-pill {{ $isBayar ? 'green' : 'amber' }}">
                                        {{ $isBayar ? 'Lunas' : 'Belum Bayar' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── TABEL RIWAYAT TRANSAKSI ANGSURAN SAH ── --}}
        <div class="content-card">
            <div class="card-header-bar">
                <div>
                    <h2 class="card-heading">Riwayat Transaksi Angsuran Sah</h2>
                    <div style="font-size:12px; color:var(--text-muted);">Audit trail pembayaran kasir &amp; potongan resmi</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Tgl Bayar</th>
                            <th>Angs.</th>
                            <th style="text-align: right;">Pokok</th>
                            <th style="text-align: right;">Jasa</th>
                            <th style="text-align: right;">Total Kas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatAngsuran as $r)
                            <tr>
                                <td style="font-size:12.5px;">{{ \Carbon\Carbon::parse($r->tgl_bayar)->format('d/m/Y') }}</td>
                                <td><span class="badge-pill blue">Ke-{{ $r->angsuran_ke }}</span></td>
                                <td style="text-align: right;">Rp {{ number_format($r->bayar_pokok, 0, ',', '.') }}</td>
                                <td style="text-align: right; color:#047857;">Rp {{ number_format($r->bayar_bunga, 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight:800; color:#0D5C46;">
                                    Rp {{ number_format($r->bayar_pokok + $r->bayar_bunga, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-placeholder" style="padding:28px 14px; text-align:center;">
                                        Belum ada riwayat angsuran sah yang dibukukan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endif

<script>
function filterKartuDebitur(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('.kartu-debitur-item');
    items.forEach(el => {
        const text = el.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
            el.style.display = 'flex';
        } else {
            el.style.display = 'none';
        }
    });
}
</script>

@endsection
