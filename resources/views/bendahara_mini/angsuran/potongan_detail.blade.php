@extends('layouts.app')

@section('title', 'Detail Paket Potongan & Konfirmasi Dana')
@section('breadcrumb', 'Detail Paket Potongan')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Detail Paket Potongan Gaji & Serah Terima</h1>
        <p>Paket #RP-{{ str_pad($rencana->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }} • Periode {{ \Carbon\Carbon::createFromDate($rencana->tahun, $rencana->bulan, 1)->translatedFormat('F Y') }}</p>
    </div>
    @if (in_array($rencana->status_potongan, ['menunggu_verifikasi', 'selesai']))
    <div class="welcome-actions">
        <a href="{{ route('bbm.angsuran.potongan.bast', $rencana->rencana_potongan_id) }}" target="_blank" class="btn-banner-trans">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            <span>Cetak BAST</span>
        </a>
    </div>
    @endif
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

{{-- ── 3 FINANCIAL METRICS RINGKASAN ── --}}
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:18px; margin-bottom:24px;">
    <div class="content-card" style="padding:20px;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Total Rencana Tagihan</div>
        <div style="font-size:22px; font-weight:800; color:var(--text-heading); margin-top:6px;">
            Rp {{ number_format($rencana->total_potongan, 0, ',', '.') }}
        </div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">{{ $detailList->count() }} Pegawai Terdaftar</div>
    </div>

    <div class="content-card" style="padding:20px;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Realisasi Pemotongan</div>
        <div style="font-size:22px; font-weight:800; color:#166534; margin-top:6px;">
            Rp {{ number_format($rencana->total_realisasi, 0, ',', '.') }}
        </div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
            Metode: <strong>{{ strtoupper($rencana->metode_penyerahan ?? '-') }}</strong>
        </div>
    </div>

    <div class="content-card" style="padding:20px;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Status Paket</div>
        <div style="margin-top:8px;">
            @if ($rencana->status_potongan === 'selesai')
                <span class="badge-pill green" style="font-size:13px; padding:6px 12px;">✓ Selesai & Kas Dibukukan</span>
            @elseif ($rencana->status_potongan === 'menunggu_verifikasi')
                <span class="badge-pill amber" style="font-size:13px; padding:6px 12px;">⏳ Menunggu Penerimaan Dana</span>
            @elseif ($rencana->status_potongan === 'dikirim')
                <span class="badge-pill blue" style="font-size:13px; padding:6px 12px;">Sedang Diproses Bendahara Sekolah</span>
            @else
                <span class="badge-pill gray" style="font-size:13px; padding:6px 12px;">Draft</span>
            @endif
        </div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:6px;">
            Eksekusi: {{ $rencana->tgl_potongan ? \Carbon\Carbon::parse($rencana->tgl_potongan)->translatedFormat('d M Y') : 'Belum dieksekusi' }}
        </div>
    </div>
</div>

{{-- ── FORM KONFIRMASI PENERIMAAN DANA (JIKA MENUNGGU VERIFIKASI) ── --}}
@if ($rencana->status_potongan === 'menunggu_verifikasi')
    <div class="content-card" style="margin-bottom:24px; border: 2px solid #fde68a; background: #fffdf5;">
        <div class="card-header-bar" style="background:#fef3c7; border-bottom:1px solid #fde68a;">
            <div>
                <h2 class="card-heading" style="color:#92400e;">⚠️ Form Konfirmasi Penerimaan Uang dari Bendahara Sekolah</h2>
                <div style="font-size:12px; color:#78350f;">
                    Bendahara Sekolah telah menyerahkan hasil potongan sebesar <strong>Rp {{ number_format($rencana->total_realisasi, 0, ',', '.') }}</strong> via <strong>{{ strtoupper($rencana->metode_penyerahan ?? 'tunai') }}</strong>. Silakan verifikasi dan masukkan ke kas Bank Mini.
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('bbm.angsuran.potongan.konfirmasi', $rencana->rencana_potongan_id) }}" style="padding:20px;">
            @csrf

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Masukkan Uang ke Kas <span class="required-mark">*</span></label>
                    <select name="kas_tujuan" class="form-input" required>
                        <option value="kas_harian" {{ $rencana->metode_penyerahan === 'tunai' ? 'selected' : '' }}>
                            Kas Harian (Uang Tunai Fisik di Loket)
                        </option>
                        <option value="kas_bank" {{ $rencana->metode_penyerahan === 'transfer' ? 'selected' : '' }}>
                            Kas Bank (Rekening Giro Bank Mini)
                        </option>
                    </select>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Saldo kas terpilih akan bertambah otomatis</div>
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tanggal Uang Diterima <span class="required-mark">*</span></label>
                    <input type="date" name="tgl_terima" class="form-input" value="{{ date('Y-m-d') }}" required>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Tanggal pembukuan kas masuk dan pelunasan angsuran</div>
                </div>

                <div class="form-group" style="margin:0; grid-column: 1 / -1;">
                    <label class="form-label">Catatan Penerimaan (Opsional)</label>
                    <input type="text" name="catatan" class="form-input" placeholder="Contoh: Diterima tunai dari Bu Bendahara Sekolah di loket Bank Mini">
                </div>
            </div>

            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #fde68a; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:12px; color:#92400e;">
                    🔒 Mengonfirmasi penerimaan akan <strong>otomatis menambah kas</strong> dan <strong>melunasi angsuran seluruh pegawai yang berhasil dipotong</strong> secara atomik.
                </div>
                <button type="submit" class="btn-ui btn-ui-primary" style="background:#d97706; border-color:#b45309;" onclick="return confirm('Konfirmasi penerimaan dana Rp {{ number_format($rencana->total_realisasi, 0, ',', '.') }} dan bukukan seluruh angsuran pegawai?')">
                    Terima Dana & Bukukan ke Kas
                </button>
            </div>
        </form>
    </div>
@endif

{{-- ── TABEL RINCIAN PEGAWAI DALAM PAKET ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Daftar Pegawai & Hasil Pemotongan</h2>
            <div style="font-size:12px; color:var(--text-muted);">Rincian tagihan per pegawai dan status realisasi dari bank payroll</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Nama Pegawai / Unit</th>
                    <th style="text-align: center;">Tenor Ke</th>
                    <th style="text-align: right;">Pokok</th>
                    <th style="text-align: right;">Jasa 2%</th>
                    <th style="text-align: right;">Rencana Tagihan</th>
                    <th style="text-align: center;">Status Potongan Sekolah</th>
                    <th style="text-align: center;">Status Angsuran di Sistem</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detailList as $d)
                    @php $subtotal = $d->pokok + $d->bunga; @endphp
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $d->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $d->kelas_unit ?? 'Pegawai' }} • Pinjaman #PINJ-{{ $d->pinjaman_id }}</div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-pill blue">Ke-{{ $d->angsuran_ke }}</span>
                        </td>
                        <td style="text-align: right;">Rp {{ number_format($d->pokok, 0, ',', '.') }}</td>
                        <td style="text-align: right;">Rp {{ number_format($d->bunga, 0, ',', '.') }}</td>
                        <td style="text-align: right; font-weight:700; color:var(--text-heading);">
                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            @if ($d->hasil_potongan === 'berhasil')
                                <span class="badge-pill green">✓ Berhasil</span>
                            @elseif ($d->hasil_potongan === 'gagal')
                                <span class="badge-pill red">✕ Gagal</span>
                            @else
                                <span class="badge-pill gray">Belum Dieksekusi</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($d->status_bayar === 'lunas')
                                <span class="badge-pill green">Lunas Terbayar</span>
                            @else
                                <span class="badge-pill amber">Belum Terbayar</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--text-muted);">{{ $d->keterangan ?? '-' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

