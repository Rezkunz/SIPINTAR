@extends('layouts.app')

@section('title', 'Informasi Pinjaman')
@section('breadcrumb', 'Info Pinjaman')

@section('content')

<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Informasi Pinjaman & Angsuran</h1>
        <p>Layanan fasilitas pembiayaan guru dan staf, simulasi cicilan, serta riwayat pinjaman pegawai Bank Mini Wiyata Cipta Niaga.</p>
    </div>
</div>

@if (session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#166534; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#991b1b; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── 1. KONDISI: PINJAMAN AKTIF BERJALAN (STATUS: CAIR) ── --}}
@if ($pinjamanAktif && $pinjamanAktif->status_pinjaman === 'cair')
<div class="content-card" style="margin-bottom: 24px; border-left: 4px solid var(--brand-primary);">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Fasilitas Pinjaman Aktif</h2>
            <div style="font-size:11.5px; color:var(--text-muted);">Cicilan kredit pegawai yang sedang berjalan</div>
        </div>
        <span class="badge-pill green" style="font-size:12px;">
            Pinjaman Berjalan
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
                <div style="font-size:11px; color:var(--text-muted);">Metode Pembayaran</div>
                <div style="font-size:1.05rem; font-weight:700; margin-top:4px;">
                    {{ $pinjamanAktif->metode_bayar === 'potong_gaji' ? 'Potong Tunjangan' : 'Tunai Loket' }}
                </div>
            </div>
            <div class="nasabah-stat-box stat-indigo" style="padding:16px;">
                <div style="font-size:11px; color:var(--text-muted);">Jatuh Tempo Berikutnya</div>
                <div style="font-size:1rem; font-weight:700; margin-top:4px;">
                    @php $next = $jadwalAngsuran->first(fn($a) => in_array($a->status_angsuran ?? ($a->status_bayar ?? ''), ['belum', 'belum_bayar'])); @endphp
                    {{ $next ? \Carbon\Carbon::parse($next->tgl_jatuh_tempo ?? ($next->jatuh_tempo ?? now()))->format('d M Y') : 'Lunas' }}
                </div>
            </div>
        </div>

        {{-- Jadwal Angsuran Lengkap --}}
        @if ($jadwalAngsuran->count() > 0)
        <h3 style="font-size:13px; font-weight:700; color:var(--text-heading); margin-bottom:12px;">Jadwal Cicilan Angsuran Lengkap</h3>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Ke-</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:right;">Angsuran Pokok</th>
                        <th style="text-align:right;">Jasa 2%</th>
                        <th style="text-align:right;">Total Tagihan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jadwalAngsuran as $i => $angsuran)
                    <tr>
                        <td style="font-weight:700;">Ke-{{ $angsuran->angsuran_ke ?? ($i + 1) }}</td>
                        <td>{{ \Carbon\Carbon::parse($angsuran->tgl_jatuh_tempo ?? ($angsuran->jatuh_tempo ?? now()))->format('d/m/Y') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($angsuran->nominal_angsuran ?? ($angsuran->pokok ?? 0), 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($angsuran->bunga ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">Rp {{ number_format($angsuran->total_angsuran ?? (($angsuran->nominal_angsuran ?? ($angsuran->pokok ?? 0)) + ($angsuran->bunga ?? 0)), 0, ',', '.') }}</td>
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
        @endif
    </div>
</div>

{{-- ── 2. KONDISI: PERMOHONAN DALAM PROSES (STATUS: DIAJUKAN / LAYAK) ── --}}
@elseif ($pinjamanAktif && in_array($pinjamanAktif->status_pinjaman, ['diajukan', 'layak']))
<div class="content-card" style="margin-bottom: 24px; border-left: 4px solid #f59e0b; background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Permohonan Pinjaman Sedang Diproses</h2>
            <div style="font-size:11.5px; color:var(--text-muted);">Pengajuan Anda telah tercatat dan sedang dalam tahapan komite kredit</div>
        </div>
        <span class="badge-pill amber" style="font-size:12px;">
            {{ $pinjamanAktif->status_pinjaman === 'layak' ? 'Disetujui (Menunggu Pencairan)' : 'Menunggu Penilaian Kelayakan' }}
        </span>
    </div>
    <div class="card-body-content" style="padding:20px;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:16px;">
            <div>
                <div style="font-size:11px; color:var(--text-muted);">Nominal Pengajuan</div>
                <div style="font-size:1.3rem; font-weight:800; color:var(--text-heading); margin-top:2px;">
                    Rp {{ number_format($pinjamanAktif->nominal_pinjaman, 0, ',', '.') }}
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted);">Rencana Tenor</div>
                <div style="font-size:1.1rem; font-weight:700; margin-top:2px;">{{ $pinjamanAktif->tenor }} Bulan</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted);">Tgl. Pengajuan</div>
                <div style="font-size:1rem; font-weight:600; margin-top:2px;">{{ \Carbon\Carbon::parse($pinjamanAktif->tgl_pengajuan)->format('d F Y') }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted);">Metode Pengembalian</div>
                <div style="font-size:1rem; font-weight:600; margin-top:2px;">
                    {{ $pinjamanAktif->metode_bayar === 'potong_gaji' ? 'Potong Tunjangan' : 'Setor Tunai' }}
                </div>
            </div>
        </div>
        @if ($pinjamanAktif->status_pinjaman === 'layak')
            {{-- Panduan Lengkap Pencairan Dana --}}
            <div style="margin-top:18px; padding:18px 20px; background:#FFFFFF; border:1px solid #BBF7D0; border-radius:12px; box-shadow:0 2px 6px rgba(16,185,129,0.08);">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                    <div style="width:34px; height:34px; border-radius:8px; background:#DCFCE7; color:#166534; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <div style="font-size:14px; font-weight:800; color:#166534;">Permohonan Pinjaman Telah Disetujui (Status: LAYAK)</div>
                        <div style="font-size:12px; color:var(--text-muted);">Dana pinjaman Anda siap diproses untuk pencairan oleh Petugas Bank Mini Sekolah.</div>
                    </div>
                </div>

                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:14px 16px; margin-bottom:14px;">
                    <div style="font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
                        📌 Langkah-Langkah Pencairan Dana:
                    </div>
                    <ol style="margin:0; padding-left:20px; font-size:12.5px; color:#334155; line-height:1.65;">
                        <li><strong>Kunjungi Ruang Bank Mini Sekolah</strong> pada jam kerja operasional (Senin – Jumat 07.30 s/d 14.00 WIB).</li>
                        <li><strong>Temui Petugas Loket</strong> (Siswa Praktik / Bendahara Bank Mini) dan sebutkan nama Anda atau tunjukkan kartu identitas pegawai.</li>
                        <li><strong>Pilih Salah Satu Metode Pencairan Dana:</strong>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:10px; margin-top:8px; margin-bottom:8px;">
                                <div style="background:#FFFFFF; border:1px solid #CBD5E1; border-radius:8px; padding:10px 12px;">
                                    <div style="font-weight:700; color:#065F46; font-size:12.5px;">💵 Uang Tunai (Cash di Loket)</div>
                                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Diterima fisik secara langsung di loket kasir Bank Mini.</div>
                                </div>
                                <div style="background:#FFFFFF; border:1px solid #CBD5E1; border-radius:8px; padding:10px 12px;">
                                    <div style="font-weight:700; color:#0369A1; font-size:12.5px;">💳 Transfer Rekening (TF Bank)</div>
                                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Ditransfer ke rekening bank / e-wallet Anda oleh Bendahara.</div>
                                </div>
                            </div>
                        </li>
                        <li><strong>Tanda Tangani Berita Acara</strong> tanda terima penyerahan dana. Setelah itu, jadwal cicilan akan otomatis aktif.</li>
                    </ol>
                </div>

                <div style="font-size:11.5px; color:var(--text-muted); display:flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Jika memiliki pertanyaan seputar pencairan, hubungi Bendahara Bank Mini di ruang administrasi keuangan.</span>
                </div>
            </div>
        @else
            <div style="margin-top:16px; padding:14px 18px; background:#fff; border:1px solid #fde68a; border-radius:8px; font-size:12.5px; color:#92400e;">
                Permohonan Anda sedang dalam antrean verifikasi kapasitas penghasilan dan penilaian kelayakan oleh Bendahara Bank Mini.
            </div>
        @endif
    </div>
</div>

@endif

{{-- ── RIWAYAT PINJAMAN SEBELUMNYA ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Riwayat Fasilitas Pinjaman Saya</h2>
            <div style="font-size:11.5px; color:var(--text-muted);">Seluruh rekam jejak pengajuan dan fasilitas pinjaman Anda</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Tgl. Pengajuan</th>
                    <th style="text-align:right;">Nominal Pinjaman</th>
                    <th>Tenor</th>
                    <th>Metode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pinjaman as $p)
                <tr>
                    <td style="font-size:13px;">{{ \Carbon\Carbon::parse($p->tgl_pengajuan)->format('d/m/Y') }}</td>
                    <td style="text-align:right; font-weight:700; color:var(--text-heading);">
                        Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}
                    </td>
                    <td>{{ $p->tenor }} Bulan</td>
                    <td>{{ $p->metode_bayar === 'potong_gaji' ? 'Potong Tunjangan' : 'Tunai Loket' }}</td>
                    <td>
                        @if ($p->status_pinjaman === 'lunas')
                            <span class="badge-pill green">Lunas</span>
                        @elseif ($p->status_pinjaman === 'cair')
                            <span class="badge-pill blue">Berjalan</span>
                        @elseif ($p->status_pinjaman === 'layak')
                            <span class="badge-pill green">Disetujui</span>
                        @elseif ($p->status_pinjaman === 'ditolak')
                            <span class="badge-pill red">Ditolak</span>
                        @else
                            <span class="badge-pill amber">Diajukan</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-placeholder" style="padding:32px 16px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 6px; display:block; color:var(--text-faint);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Belum ada riwayat permohonan pinjaman.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
