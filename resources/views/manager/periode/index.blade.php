@extends('layouts.app')

@section('title', 'Tutup Periode')
@section('breadcrumb', 'Tutup Periode')

@section('content')

@php
    $rp = fn ($n) => number_format($n, 0, ',', '.');
    $namaBulan = $awalBulan->translatedFormat('F Y');
    $tertutup = $terpilih->status === 'tutup';
    $periodeTutupTerakhir = $daftar->firstWhere('status', 'tutup');
    $totalKasAkhir = $ringkasan['kas']->sum('saldo_akhir');
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Tutup Periode</h1>
        <p>Penutupan buku bulanan. Setelah periode ditutup, saldo awal dan akhir setiap kas dibekukan dan semua transaksi tabungan, pinjaman, angsuran, serta kas yang bertanggal di bulan itu akan ditolak sistem.</p>
    </div>
</div>

@include('manager.partials.notifikasi', ['judulGalat' => 'Periode belum diproses:'])

{{-- ── METRIK ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header"><span class="metric-title">Periode Tertutup</span></div>
        <div class="metric-value">{{ $jumlahTutup }}</div>
        <div class="mgr-metric-ket">Bulan yang sudah dikunci</div>
    </div>
    <div class="metric-card card-amber">
        <div class="metric-header"><span class="metric-title">Periode Terbuka</span></div>
        <div class="metric-value">{{ $jumlahBuka }}</div>
        <div class="mgr-metric-ket">Termasuk bulan berjalan</div>
    </div>
    <div class="metric-card card-blue">
        <div class="metric-header"><span class="metric-title">Tutup Buku Terakhir</span></div>
        <div class="metric-value" style="font-size:22px;">{{ $periodeTutupTerakhir ? $periodeTutupTerakhir->awal_bulan->translatedFormat('F Y') : '-' }}</div>
        <div class="mgr-metric-ket">
            {{ $periodeTutupTerakhir?->tgl_tutup ? 'Ditutup ' . $periodeTutupTerakhir->tgl_tutup->translatedFormat('d M Y, H:i') : 'Belum pernah tutup buku' }}
        </div>
    </div>
    <div class="metric-card card-purple">
        <div class="metric-header"><span class="metric-title">Saldo Kas Akhir {{ $awalBulan->translatedFormat('M Y') }}</span></div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($totalKasAkhir) }}</div>
        <div class="mgr-metric-ket">{{ $tertutup ? 'Angka yang dibekukan saat tutup buku' : 'Perhitungan sementara' }}</div>
    </div>
</div>

<div class="periode-layout">
    {{-- ── DAFTAR PERIODE ── --}}
    <div class="content-card periode-daftar">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Daftar Periode</h2>
                <div style="font-size:12px; color:var(--text-muted);">Pilih bulan untuk melihat detail</div>
            </div>
        </div>
        <ul class="periode-list">
            @foreach ($daftar as $p)
                <li>
                    <a href="{{ route('periode.tutup', ['tahun' => $p->tahun, 'bulan' => $p->bulan]) }}"
                       class="periode-item {{ $p->kunci === $terpilih->kunci ? 'active' : '' }}">
                        <span class="periode-item-ikon {{ $p->status }}">
                            @if ($p->status === 'tutup')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            @else
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                            @endif
                        </span>
                        <span class="periode-item-teks">
                            <span class="periode-item-nama">{{ $p->awal_bulan->translatedFormat('F Y') }}</span>
                            <span class="periode-item-ket">
                                @if ($p->status === 'tutup')
                                    Ditutup {{ $p->tgl_tutup?->format('d/m/Y') }}{{ $p->ditutup_oleh ? ' · ' . $p->ditutup_oleh : '' }}
                                @elseif ($p->berjalan)
                                    Bulan berjalan
                                @else
                                    Belum ditutup
                                @endif
                            </span>
                        </span>
                        <span class="badge-pill {{ $p->status === 'tutup' ? 'green' : ($p->berjalan ? 'blue' : 'amber') }}" style="font-size:10.5px; padding:2px 8px;">
                            {{ $p->status === 'tutup' ? 'Tutup' : ($p->berjalan ? 'Berjalan' : 'Buka') }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- ── DETAIL PERIODE TERPILIH ── --}}
    <div class="periode-detail">
        <div class="content-card">
            <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <h2 class="card-heading">Periode {{ $namaBulan }}</h2>
                    <div style="font-size:12px; color:var(--text-muted);">
                        {{ $awalBulan->translatedFormat('d F Y') }} &ndash; {{ $awalBulan->copy()->endOfMonth()->translatedFormat('d F Y') }}
                    </div>
                </div>
                <span class="badge-pill {{ $tertutup ? 'green' : ($terpilih->berjalan ? 'blue' : 'amber') }}">
                    {{ $tertutup ? 'Sudah Ditutup' : ($terpilih->berjalan ? 'Bulan Berjalan' : 'Belum Ditutup') }}
                </span>
            </div>

            {{-- Daftar periksa --}}
            @if (!$tertutup)
                <div class="periode-cek">
                    <div class="periode-cek-judul">Daftar Periksa Sebelum Tutup Buku</div>
                    @if (empty($periksa['penghalang']) && empty($periksa['peringatan']))
                        <div class="periode-cek-item ok">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Semua pemeriksaan lolos. Periode siap ditutup.</span>
                        </div>
                    @endif
                    @foreach ($periksa['penghalang'] as $pesan)
                        <div class="periode-cek-item blokir">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <span>{{ $pesan }}</span>
                        </div>
                    @endforeach
                    @foreach ($periksa['peringatan'] as $pesan)
                        <div class="periode-cek-item awas">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>{{ $pesan }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="periode-cek">
                    <div class="periode-cek-item ok">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>
                            Ditutup pada {{ $terpilih->tgl_tutup?->translatedFormat('d F Y, H:i') }}{{ $terpilih->ditutup_oleh ? ' oleh ' . $terpilih->ditutup_oleh : '' }}.
                            Transaksi bertanggal {{ $namaBulan }} terkunci.
                        </span>
                    </div>
                </div>
            @endif

            {{-- Posisi kas --}}
            <div class="table-responsive">
                <table class="financial-table" style="font-size:12.5px;">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Kas</th>
                            <th style="text-align:right;">Saldo Awal</th>
                            <th style="text-align:right;">Masuk</th>
                            <th style="text-align:right;">Keluar</th>
                            <th style="text-align:right;">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ringkasan['kas'] as $kas)
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--text-heading);">{{ $kas->nama_kas }}</div>
                                    <div style="font-size:11px; color:var(--text-muted);">{{ $kas->jenis_kas === 'bank' ? 'Rekening bank' : 'Kas tunai' }}</div>
                                </td>
                                <td style="text-align:right; white-space:nowrap;">{{ $rp($kas->saldo_awal) }}</td>
                                <td style="text-align:right; white-space:nowrap; color:#047857;">{{ $rp($kas->masuk) }}</td>
                                <td style="text-align:right; white-space:nowrap; color:#B91C1C;">{{ $rp($kas->keluar) }}</td>
                                <td style="text-align:right; white-space:nowrap; font-weight:700; color:var(--text-heading);">{{ $rp($kas->saldo_akhir) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-placeholder" style="padding:20px; text-align:center;">Belum ada data kas.</div></td></tr>
                        @endforelse
                    </tbody>
                    @if ($ringkasan['kas']->count() > 1)
                        <tfoot>
                            <tr style="font-weight:800;">
                                <td>Total</td>
                                <td style="text-align:right;">{{ $rp($ringkasan['kas']->sum('saldo_awal')) }}</td>
                                <td style="text-align:right;">{{ $rp($ringkasan['kas']->sum('masuk')) }}</td>
                                <td style="text-align:right;">{{ $rp($ringkasan['kas']->sum('keluar')) }}</td>
                                <td style="text-align:right;">{{ $rp($totalKasAkhir) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Ringkasan aktivitas --}}
            <div class="periode-ringkas">
                <div class="periode-ringkas-item">
                    <span class="periode-ringkas-label">Transaksi Tabungan</span>
                    <span class="periode-ringkas-nilai">{{ (int) $ringkasan['tabungan']->jumlah }} transaksi</span>
                    <span class="periode-ringkas-sub">Setor Rp {{ $rp($ringkasan['tabungan']->setoran) }} &middot; Tarik Rp {{ $rp($ringkasan['tabungan']->penarikan) }}</span>
                </div>
                <div class="periode-ringkas-item">
                    <span class="periode-ringkas-label">Pencairan Pinjaman</span>
                    <span class="periode-ringkas-nilai">{{ (int) $ringkasan['pencairan']->jumlah }} pinjaman</span>
                    <span class="periode-ringkas-sub">Rp {{ $rp($ringkasan['pencairan']->total) }}</span>
                </div>
                <div class="periode-ringkas-item">
                    <span class="periode-ringkas-label">Angsuran Diterima</span>
                    <span class="periode-ringkas-nilai">{{ (int) $ringkasan['angsuran']->jumlah }} pembayaran</span>
                    <span class="periode-ringkas-sub">Pokok Rp {{ $rp($ringkasan['angsuran']->pokok) }} &middot; Jasa Rp {{ $rp($ringkasan['angsuran']->bunga) }}</span>
                </div>
            </div>

            {{-- Aksi --}}
            @if (!$tertutup)
                <form method="POST" action="{{ route('periode.tutup.proses') }}" class="pos-form"
                      onsubmit="return confirm(this.dataset.konfirmasi);"
                      data-konfirmasi="Tutup buku periode {{ $namaBulan }}? Setelah ditutup, transaksi bertanggal di bulan ini tidak dapat dicatat lagi.">
                    @csrf
                    <input type="hidden" name="tahun" value="{{ $terpilih->tahun }}">
                    <input type="hidden" name="bulan" value="{{ $terpilih->bulan }}">
                    <div class="pos-form-title">Tutup Buku {{ $namaBulan }}</div>
                    <div class="lap-field">
                        <label class="lap-field-label" for="catatan_tutup">Catatan (opsional)</label>
                        <input type="text" name="catatan" id="catatan_tutup" class="pos-input" maxlength="255"
                               value="{{ old('catatan') }}" placeholder="mis. Kas fisik sudah dihitung bersama Bendahara Bank Mini" {{ $periksa['bisa_tutup'] ? '' : 'disabled' }}>
                    </div>
                    <label class="periode-konfirmasi">
                        <input type="checkbox" name="konfirmasi" value="1" {{ $periksa['bisa_tutup'] ? '' : 'disabled' }}>
                        <span>Saya sudah memeriksa seluruh transaksi dan laporan bulan {{ $namaBulan }}.</span>
                    </label>
                    <div class="pos-form-actions">
                        <button type="submit" class="btn-ui btn-ui-primary" style="font-weight:700;" {{ $periksa['bisa_tutup'] ? '' : 'disabled' }}>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>Tutup Periode</span>
                        </button>
                    </div>
                </form>
            @elseif ($bisaDibuka)
                <form method="POST" action="{{ route('periode.tutup.buka') }}" class="pos-form periode-form-buka"
                      onsubmit="return confirm(this.dataset.konfirmasi);"
                      data-konfirmasi="Buka kembali periode {{ $namaBulan }}? Transaksi di bulan ini akan dapat diubah lagi.">
                    @csrf
                    <input type="hidden" name="tahun" value="{{ $terpilih->tahun }}">
                    <input type="hidden" name="bulan" value="{{ $terpilih->bulan }}">
                    <div class="pos-form-title">Buka Kembali Periode</div>
                    <div class="lap-field">
                        <label class="lap-field-label" for="alasan_buka">Alasan</label>
                        <input type="text" name="alasan" id="alasan_buka" class="pos-input" minlength="10" maxlength="255" required
                               value="{{ old('alasan') }}" placeholder="mis. Koreksi angsuran yang salah dicatat tanggal 28">
                    </div>
                    <div class="pos-form-hint">Hanya periode tertutup paling akhir yang dapat dibuka. Alasan tersimpan di audit log.</div>
                    <div class="pos-form-actions">
                        <button type="submit" class="btn-ui btn-ui-outline" style="font-weight:700; color:#B45309; border-color:#FDE68A;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                            <span>Buka Kembali</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- ── RIWAYAT ── --}}
        <div class="content-card">
            <div class="card-header-bar">
                <div>
                    <h2 class="card-heading">Riwayat Tutup &amp; Buka Periode</h2>
                    <div style="font-size:12px; color:var(--text-muted);">10 aktivitas terakhir dari audit log</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="financial-table" style="font-size:12.5px;">
                    <thead>
                        <tr>
                            <th style="min-width:130px;">Waktu</th>
                            <th style="width:90px;">Aksi</th>
                            <th style="min-width:110px;">Periode</th>
                            <th style="min-width:200px;">Catatan / Alasan</th>
                            <th style="min-width:120px;">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $log)
                            <tr>
                                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge-pill {{ $log->aksi === 'TUTUP_PERIODE' ? 'green' : 'amber' }}" style="font-size:10.5px; padding:2px 8px;">
                                        {{ $log->aksi === 'TUTUP_PERIODE' ? 'Tutup' : 'Buka' }}
                                    </span>
                                </td>
                                <td>{{ isset($log->data['periode']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $log->data['periode'] . '-01')->translatedFormat('F Y') : '-' }}</td>
                                <td style="color:var(--text-muted);">{{ $log->data['alasan'] ?? $log->data['catatan'] ?? '-' }}</td>
                                <td>{{ $log->nama_user ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-placeholder" style="padding:24px; text-align:center;">Belum ada aktivitas tutup buku.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
