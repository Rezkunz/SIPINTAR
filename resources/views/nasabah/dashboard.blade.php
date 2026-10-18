@extends('layouts.app')

@section('title', 'Informasi Rekening')
@section('breadcrumb', 'Informasi Rekening')

@section('content')

{{-- ── HERO GREETING BANNER (Refined & Balanced) ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <div class="welcome-badge-tag">Portal Nasabah Mandiri</div>
        <h1>Selamat Datang, {{ $nasabah->nama_nasabah ?? $user->nama_user ?? $user->name }}</h1>
        <p>Layanan rekening tabungan digital dan fasilitas pembiayaan sekolah terpadu &bull; No. WhatsApp: <strong>{{ $user->no_hp ?? '-' }}</strong></p>
    </div>
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
</div>

{{-- ── SYMMETRICAL 50:50 SHOWCASE ROW ── --}}
<div class="nasabah-showcase-grid">

    {{-- ── LEFT: PREMIUM DIGITAL PASSBOOK DEBIT CARD ── --}}
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
            <div class="passbook-rek-number" id="card-rek-number">
                {{ $rekening->no_rek ?? '1001-2026-00000' }}
            </div>
        </div>

        <div class="passbook-bottom">
            <div>
                <div class="passbook-rek-label">Pemegang Rekening</div>
                <div class="passbook-holder-name">{{ $nasabah->nama_nasabah ?? $user->nama_user }}</div>
                <div class="passbook-holder-sub">{{ $nasabah->kelas_unit ?? 'Nasabah Terdaftar' }}</div>
            </div>
            <div class="passbook-balance-wrap">
                <div class="passbook-rek-label" style="text-align: right;">Total Saldo Efektif</div>
                <div class="passbook-balance-amount">
                    Rp {{ number_format($rekening->saldo ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: 2x2 BALANCED METRIC GRID ── --}}
    <div class="nasabah-stats-grid">
        {{-- Card 1: Nomor Rekening & Salin --}}
        <div class="nasabah-stat-box stat-emerald">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <div class="nasabah-stat-header">
                <span class="nasabah-stat-title">Rekening Nasabah</span>
                <button type="button" class="btn-copy-rek" id="btn-copy-rek" onclick="copyRekening('{{ $rekening->no_rek ?? '' }}')" title="Salin Nomor Rekening">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span id="copy-status-text">Salin</span>
                </button>
            </div>
            <div class="nasabah-stat-val" style="font-family: ui-monospace, SFMono-Regular, monospace; font-size: 1.25rem;">
                {{ $rekening->no_rek ?? 'Belum Terbit' }}
            </div>
        </div>

        {{-- Card 2: Status Operasional Rekening --}}
        <div class="nasabah-stat-box stat-blue">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="nasabah-stat-header">
                <span class="nasabah-stat-title">Status Layanan</span>
            </div>
            <div class="nasabah-stat-val" style="font-size: 1.25rem;">
                {{ ucfirst($rekening->status_rekening ?? 'Aktif') }}
            </div>
        </div>

        {{-- Card 3: Fasilitas Pinjaman / Tagihan --}}
        <div class="nasabah-stat-box stat-amber">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="nasabah-stat-header">
                <span class="nasabah-stat-title">Kewajiban Pinjaman</span>
            </div>
            <div class="nasabah-stat-val" style="font-size: 1.25rem;">
                @if ($pinjamanAktif)
                    Rp {{ number_format($pinjamanAktif->nominal_pinjaman, 0, ',', '.') }}
                @else
                    Nihil
                @endif
            </div>
        </div>

        {{-- Card 4: Kategori & Identitas --}}
        <div class="nasabah-stat-box stat-indigo">
            <div class="metric-watermark">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="nasabah-stat-header">
                <span class="nasabah-stat-title">Kategori Nasabah</span>
            </div>
            <div class="nasabah-stat-val" style="font-size: 1.25rem;">
                {{ ($nasabah->jenis_nasabah ?? '') === 'pegawai' ? 'Guru / Karyawan' : 'Siswa Sekolah' }}
            </div>
        </div>
    </div>

</div>

{{-- ── BALANCED TWO-COLUMN ROW (60% : 40%) ── --}}
<div class="nasabah-activity-grid">

    {{-- ── LEFT: RECENT TRANSACTIONS TABLE ── --}}
    <div class="content-card" style="margin-bottom: 0;">
        <div class="card-header-bar">
            <div>
                <h2 class="card-heading">Mutasi Tabungan Terakhir</h2>
                <div style="font-size: 11.5px; color: var(--text-muted);">Riwayat 5 catatan transaksi tabungan terbaru</div>
            </div>
            <a href="{{ route('nasabah.saldo') }}" class="btn-ui btn-ui-outline btn-ui-sm">Rincian Mutasi</a>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Waktu Transaksi</th>
                        <th>Jenis Transaksi</th>
                        <th style="text-align: right;">Mutasi (Rp)</th>
                        <th style="text-align: right;">Saldo Akhir</th>
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
                            <td style="text-align: right; font-weight: 700; color: {{ $t->kredit > 0 ? 'var(--color-success)' : 'var(--color-danger)' }};">
                                {{ $t->kredit > 0 ? '+ Rp ' . number_format($t->kredit, 0, ',', '.') : '- Rp ' . number_format($t->debit, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--text-heading);">
                                Rp {{ number_format($t->saldo_akhir, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-placeholder" style="padding: 32px 16px;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 6px; color: var(--text-faint); display: block;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                    Belum ada mutasi tabungan tercatat pada rekening ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── RIGHT: ACTIONS & COUNTER GUIDE ── --}}
    <div class="content-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div class="card-header-bar">
                <h2 class="card-heading">Layanan Cepat & Loket</h2>
            </div>
            <div class="card-body-content" style="padding: 18px 20px;">
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 26px; height: 26px; border-radius: 6px; background: #ECFDF5; color: #0D5C46; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">1</div>
                        <div style="font-size: 12.5px; color: var(--text-body); line-height: 1.5;">
                            <strong>Setor Tunai:</strong> Kunjungi loket kasir teller di Bank Mini Sekolah dengan menyebutkan Nomor Rekening Anda.
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 26px; height: 26px; border-radius: 6px; background: #ECFDF5; color: #0D5C46; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">2</div>
                        <div style="font-size: 12.5px; color: var(--text-body); line-height: 1.5;">
                            <strong>Tarik Tunai:</strong> Penarikan dapat dilayani pada jam istirahat dan operasional kasir sekolah.
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 26px; height: 26px; border-radius: 6px; background: #ECFDF5; color: #0D5C46; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">3</div>
                        <div style="font-size: 12.5px; color: var(--text-body); line-height: 1.5;">
                            <strong>Cetak Slip Resmi:</strong> Teller akan mencetak slip tanda terima resmi ber-stempel bank mini untuk setiap transaksi.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: 16px 20px; border-top: 1px solid var(--border-subtle); background-color: #FAFAFA; border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <a href="{{ route('nasabah.saldo') }}" class="btn-ui btn-ui-outline btn-ui-sm" style="width: 100%;">
                    <span>Cetak Mutasi</span>
                </a>
                <a href="{{ route('nasabah.angsuran') }}" class="btn-ui btn-ui-primary btn-ui-sm" style="width: 100%;">
                    <span>Kartu Cicilan</span>
                </a>
            </div>
        </div>
    </div>

</div>

<script>
    function copyRekening(noRek) {
        if (!noRek) return;
        navigator.clipboard.writeText(noRek).then(() => {
            const btn = document.getElementById('copy-status-text');
            if (btn) {
                const originalText = btn.textContent;
                btn.textContent = 'Tersalin!';
                btn.style.color = '#059669';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.color = '';
                }, 2000);
            }
        });
    }
</script>

@endsection
