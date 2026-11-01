@extends('layouts.app')

@section('title', 'Dashboard Bendahara Bank Mini')
@section('breadcrumb', 'Dashboard')

@section('content')

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Selamat Bertugas, {{ auth()->user()->nama_user }}</h1>
        <p>Pusat kendali operasional kas tunai (cash), kas rekening bank Bendahara Bank Mini, portofolio pembiayaan guru & staf, serta tabungan siswa.</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('kas.harian') }}" class="btn-banner-white">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
            <span>Buku Kas Harian</span>
        </a>
        <a href="{{ route('kas.bank') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
            <span>Kas Bank Bendahara</span>
        </a>
        <a href="{{ route('bbm.kartu.rekap') }}" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Rekap Piutang</span>
        </a>
    </div>
</div>

{{-- ── SECTION 1: LIKUIDITAS KAS (2 TEMPAT PENYIMPANAN: CASH & BANK BENDAHARA) ── --}}
<div style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Posisi Likuiditas Kas (Cash & Bank Milik Bendahara Bank Mini)</h3>
        <span style="font-size: 12px; color: var(--text-muted);">2 Tempat Penyimpanan Dana Bank Mini</span>
    </div>
    <div class="metric-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="metric-card card-emerald">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Kas Tunai (Cash)</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($saldoKasFisik, 0, ',', '.') }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Uang tunai fisik di kas Bank Mini</div>
        </div>

        <div class="metric-card card-blue">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Kas Bank Bendahara</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($saldoBankMitra, 0, ',', '.') }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Rekening bank milik Bendahara Bank Mini</div>
        </div>

        <div class="metric-card card-amber">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Total Saldo Kas Likuid</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalKas, 0, ',', '.') }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Akumulasi Cash + Bank Bendahara</div>
        </div>

        <div class="metric-card card-indigo">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Total Simpanan DPK</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalDanaTabungan, 0, ',', '.') }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">{{ $rekeningAktifCount }} rekening penabung aktif</div>
        </div>
    </div>
</div>

{{-- ── SECTION 2: PORTOFOLIO PINJAMAN & PIUTANG PEGAWAI ── --}}
<div style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Portofolio Kredit & Piutang Pegawai</h3>
        <span style="font-size: 12px; color: var(--text-muted);">Pembiayaan Guru & Tenaga Kependidikan</span>
    </div>
    <div class="metric-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="metric-card card-blue">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Sisa Pokok Piutang (Outstanding)</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalOutstandingPiutang, 0, ',', '.') }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Kredit beredar pada {{ $debiturAktif }} debitur aktif</div>
        </div>

        <div class="metric-card card-emerald">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Akumulasi Pokok Kembali</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalPokokTerbayar, 0, ',', '.') }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Total pengembalian pokok kredit</div>
        </div>

        <div class="metric-card card-amber">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m16 12-4-4-4 4M12 16V8"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Pendapatan Jasa 2% Terkumpul</span>
            </div>
            <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($totalJasaTerbayar, 0, ',', '.') }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Laba operasional bunga pembiayaan</div>
        </div>

        <div class="metric-card card-indigo">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
            </div>
            <div class="metric-header">
                <span class="metric-title">Status Penyaluran Kredit</span>
            </div>
            <div class="metric-value" style="font-size: 1.35rem;">{{ $debiturAktif }} Aktif <span style="font-size: 0.95rem; font-weight: 500; color: var(--text-muted);">/ {{ $pinjamanLunas }} Lunas</span></div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Disalurkan: Rp {{ number_format($totalPinjamanDisalurkan, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- ── MAIN CONTENT (2 KOLOM: 2:1) ── --}}
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    {{-- ── KOLOM KIRI (2/3): TABEL MUTASI KAS & PAKET POTONGAN ── --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- 1. TABEL CATATAN BUKU KAS HARIAN TERBARU --}}
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-bar">
                <div>
                    <h2 class="card-heading">Catatan Mutasi Buku Kas Harian Terbaru</h2>
                    <div style="font-size: 12px; color: var(--text-muted);">Pencatatan kas tunai (cash) dan rekening bank Bendahara Bank Mini</div>
                </div>
                <a href="{{ route('kas.harian') }}" class="btn-ui btn-ui-outline btn-ui-sm">Buka Buku Kas</a>
            </div>
            <div class="table-responsive">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pos Kas</th>
                            <th>Uraian Transaksi</th>
                            <th>Aliran</th>
                            <th style="text-align: right;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutasiList as $m)
                            <tr>
                                <td style="white-space: nowrap; font-size: 12.5px;">{{ \Carbon\Carbon::parse($m->tgl_mutasi)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge-pill {{ str_contains(strtolower($m->nama_kas), 'bank') ? 'blue' : 'green' }}" style="font-size: 11px;">
                                        {{ $m->nama_kas }}
                                    </span>
                                </td>
                                <td style="font-size: 13px; font-weight: 500; color: var(--text-heading);">
                                    {{ $m->uraian ?? '-' }}
                                    @if ($m->nama_user)
                                        <div style="font-size: 11px; color: var(--text-muted);">Oleh: {{ $m->nama_user }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-pill {{ $m->arah === 'masuk' ? 'green' : 'red' }}">
                                        {{ ucfirst($m->arah) }}
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 700; white-space: nowrap; color: {{ $m->arah === 'masuk' ? 'var(--color-success)' : 'var(--color-danger)' }};">
                                    {{ $m->arah === 'masuk' ? '+ Rp ' . number_format($m->nominal, 0, ',', '.') : '- Rp ' . number_format($m->nominal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-placeholder">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--text-faint); display: block;"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                                        Belum ada catatan mutasi kas fisik pada periode ini.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 2. TABEL MONITORING PAKET POTONGAN PAYROLL BERKALA --}}
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-bar">
                <div>
                    <h2 class="card-heading">Monitoring Paket Rencana Potongan Tunjangan</h2>
                    <div style="font-size: 12px; color: var(--text-muted);">Status koordinasi serah terima dana potongan gaji dari Bendahara Sekolah</div>
                </div>
                <a href="{{ route('bbm.angsuran.potongan') }}" class="btn-ui btn-ui-outline btn-ui-sm">Kelola Potongan</a>
            </div>
            <div class="table-responsive">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>ID Paket</th>
                            <th>Periode</th>
                            <th style="text-align: right;">Rencana Tagihan</th>
                            <th style="text-align: right;">Realisasi Sukses</th>
                            <th>Status Penyerahan</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paketPotonganList as $pkt)
                            <tr>
                                <td style="font-weight: 700; color: var(--brand-primary); font-family: ui-monospace, SFMono-Regular, monospace;">
                                    #RP-{{ str_pad($pkt->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::createFromDate($pkt->tahun, $pkt->bulan, 1)->translatedFormat('F Y') }}
                                </td>
                                <td style="text-align: right; font-weight: 600;">
                                    Rp {{ number_format($pkt->total_potongan, 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 700; color: {{ $pkt->total_realisasi > 0 ? 'var(--color-success)' : 'var(--text-muted)' }};">
                                    {{ $pkt->total_realisasi > 0 ? 'Rp ' . number_format($pkt->total_realisasi, 0, ',', '.') : '-' }}
                                </td>
                                <td>
                                    @if ($pkt->status_potongan === 'selesai')
                                        <span class="badge-pill green">Selesai & Dibukukan</span>
                                    @elseif ($pkt->status_potongan === 'menunggu_verifikasi')
                                        <span class="badge-pill amber">Menunggu Penerimaan</span>
                                    @else
                                        <span class="badge-pill blue">Siap Dipotong Payroll</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="{{ route('bbm.angsuran.potongan.detail', $pkt->rencana_potongan_id) }}" class="btn-ui btn-ui-outline btn-ui-sm" style="padding: 4px 10px; font-size: 11.5px;" title="Lihat Rincian Paket">
                                            Rincian
                                        </a>
                                        @if (in_array($pkt->status_potongan, ['menunggu_verifikasi', 'selesai']))
                                            <a href="{{ route('bbm.angsuran.potongan.bast', $pkt->rencana_potongan_id) }}" target="_blank" class="btn-ui btn-ui-outline btn-ui-sm" style="padding: 4px 10px; font-size: 11.5px;" title="Cetak Berita Acara Serah Terima">
                                                BAST
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-placeholder">
                                        Belum ada riwayat paket rencana potongan tunjangan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ── KOLOM KANAN (1/3): ANALITIK KAS, KOMPOSISI DPK, & KONTROL PIUTANG ── --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- 1. ARUS KAS BULAN BERJALAN --}}
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-bar">
                <h2 class="card-heading">Arus Kas Bulan Berjalan</h2>
                <span class="badge-pill green">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</span>
            </div>
            <div class="card-body-content" style="padding: 18px 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border-subtle);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        </div>
                        <div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">Total Pemasukan Kas</div>
                            <div style="font-size: 14.5px; font-weight: 700; color: var(--color-success);">+ Rp {{ number_format($kasMasukBulanIni, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-subtle);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                        </div>
                        <div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">Total Pengeluaran Kas</div>
                            <div style="font-size: 14.5px; font-weight: 700; color: var(--color-danger);">- Rp {{ number_format($kasKeluarBulanIni, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <div style="padding-top: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 12.5px; font-weight: 600; color: var(--text-heading);">Arus Kas Bersih (Net Cashflow)</span>
                        <span style="font-size: 14.5px; font-weight: 800; color: {{ $netCashflow >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                            {{ $netCashflow >= 0 ? '+' : '-' }} Rp {{ number_format(abs($netCashflow), 0, ',', '.') }}
                        </span>
                    </div>
                    <div style="font-size: 11.5px; color: var(--text-muted);">
                        @if ($netCashflow >= 0)
                            Posisi likuiditas mengalami <strong>surplus penerimaan</strong> operasional kas.
                        @else
                            Pengeluaran kas melebihi penerimaan pada periode bulan ini.
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. KOMPOSISI DANA PIHAK KETIGA (DPK SIMPANAN) --}}
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-bar">
                <h2 class="card-heading">Komposisi Himpunan Simpanan (DPK)</h2>
            </div>
            <div class="card-body-content" style="padding: 18px 20px;">
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-heading);">TAPEL (Tabungan Pelajar)</span>
                        <span style="font-size: 13px; font-weight: 700; color: var(--brand-primary);">Rp {{ number_format($tabunganSiswa, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">
                        <span>{{ $rekeningSiswaCount }} Rekening Aktif</span>
                        <span>Bunga Jasa 0,1% / bln</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 999px; overflow: hidden;">
                        <div style="height: 100%; background: var(--brand-primary); width: {{ $totalDanaTabungan > 0 ? round(($tabunganSiswa / $totalDanaTabungan) * 100) : 0 }}%;"></div>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--text-heading);">TAKAR (Tabungan Karyawan/Guru)</span>
                        <span style="font-size: 13px; font-weight: 700; color: #2563eb;">Rp {{ number_format($tabunganGuru, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">
                        <span>{{ $rekeningGuruCount }} Rekening Aktif</span>
                        <span>Bunga Jasa 0,2% / bln</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 999px; overflow: hidden;">
                        <div style="height: 100%; background: #2563eb; width: {{ $totalDanaTabungan > 0 ? round(($tabunganGuru / $totalDanaTabungan) * 100) : 0 }}%;"></div>
                    </div>
                </div>

                <div style="background: var(--surface-bg); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 12px; margin-top: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 11.5px; font-weight: 600; color: var(--text-body);">Rasio Cadangan Kas terhadap DPK</span>
                        <span style="font-size: 12px; font-weight: 800; color: var(--color-success);">
                            {{ $totalDanaTabungan > 0 ? round(($totalKas / $totalDanaTabungan) * 100) : 100 }}%
                        </span>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted);">
                        Likuiditas kas bank mini sangat aman untuk menjamin penarikan simpanan nasabah sewaktu-waktu.
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. STATUS PORTOFOLIO KREDIT & PENGAWASAN PINJAMAN --}}
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-bar">
                <h2 class="card-heading">Pengawasan Portofolio Kredit</h2>
            </div>
            <div class="card-body-content" style="padding: 18px 20px;">
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 12.5px; color: var(--text-body);">Debitur Aktif Berjalan</div>
                        <span class="badge-pill blue" style="font-weight: 700;">{{ $debiturAktif }} Orang</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 12.5px; color: var(--text-body);">Pinjaman Telah Lunas</div>
                        <span class="badge-pill green" style="font-weight: 700;">{{ $pinjamanLunas }} Orang</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 8px;">
                        <div style="font-size: 12.5px; color: var(--text-body);">Pengajuan Baru / Verifikasi Komite</div>
                        <span class="badge-pill amber" style="font-weight: 700;">{{ $pengajuanBaru }} Orang</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 16px;">
                    <a href="{{ route('bbm.kartu.rekap') }}" class="btn-ui btn-ui-outline btn-ui-sm" style="text-align: center; justify-content: center; font-size: 11.5px; padding: 8px 10px;">
                        Rekap Piutang
                    </a>
                    <a href="{{ route('bbm.pinjaman.kelayakan') }}" class="btn-ui btn-ui-outline btn-ui-sm" style="text-align: center; justify-content: center; font-size: 11.5px; padding: 8px 10px;">
                        Kelayakan Komite
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
