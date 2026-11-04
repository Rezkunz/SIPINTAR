@extends('layouts.app')

@section('title', 'Buka Rekening Baru — Otorisasi Teller')
@section('breadcrumb', 'Buka Rekening')

@section('content')

{{-- ── ALERT NOTIFIKASI SUKSES / ERROR ── --}}
@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #065f46;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <div style="font-size: 14px; font-weight: 600;">{{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #991b1b;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div style="font-size: 14px; font-weight: 600;">{{ session('error') }}</div>
    </div>
@endif

@if ($errors->any())
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; color: #991b1b;">
        <div style="font-weight: 700; margin-bottom: 4px;">Terjadi Kesalahan Validasi:</div>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ── HERO GREETING BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Otorisasi Pembukaan Rekening Baru</h1>
        <p>Verifikasi identitas fisik calon nasabah (Kartu Pelajar / KTP Pegawai), terima uang setoran awal tunai di loket, dan aktifkan buku tabungan nasabah.</p>
    </div>
</div>

{{-- ── METRIC CARDS ── --}}
<div class="metric-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="metric-card card-amber">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Permohonan Pending (Belum Aktif)</span>
        </div>
        <div class="metric-value">{{ $totalPending }}</div>
        <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Menunggu verifikasi fisik & setoran awal di loket</div>
    </div>

    <div class="metric-card card-emerald">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Total Rekening Aktif</span>
        </div>
        <div class="metric-value">{{ $totalAktif }}</div>
        <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Nasabah resmi terdaftar & dapat bertransaksi</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-watermark">
            <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
        </div>
        <div class="metric-header">
            <span class="metric-title">Ketentuan Setoran Awal Minimum</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>10.000</div>
        <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Masuk langsung ke saldo awal & kas brankas loket</div>
    </div>
</div>

{{-- ── TABEL ANTREAN PEMBUKAAN REKENING ── --}}
<div class="content-card">
    <div class="card-header-bar" style="flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="card-heading">Daftar Antrean Pembukaan & Aktivasi Rekening</h2>
            <div style="font-size: 12px; color: var(--text-muted);">Nasabah yang telah mendaftar mandiri via web dan hadir di loket untuk validasi fisik</div>
        </div>

        {{-- Form Pencarian --}}
        <form action="{{ route('tabungan.buka-rekening') }}" method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS/NIP, no rek..." class="form-input" style="padding: 8px 14px; font-size: 13px; border-radius: 8px; border: 1px solid var(--border-color); width: 260px;">
            <button type="submit" class="btn-ui btn-ui-primary btn-ui-sm">Cari</button>
            @if ($search)
                <a href="{{ route('tabungan.buka-rekening') }}" class="btn-ui btn-ui-outline btn-ui-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>No. Registrasi / Rekening</th>
                    <th>Nama Calon Nasabah</th>
                    <th>Kategori / Kelas</th>
                    <th>Nomor Induk (NIS/NIP)</th>
                    <th>Kontak WhatsApp</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center;">Tindakan Teller</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingRekening as $rek)
                    <tr>
                        <td>
                            <span style="font-family: ui-monospace, SFMono-Regular, monospace; font-weight: 700; color: #0d5c46; background: #e6f4ea; padding: 4px 8px; border-radius: 6px; font-size: 12px;">
                                {{ $rek->no_rek }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--text-heading);">{{ $rek->nama_nasabah }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $rek->email ?? 'Akun Terdaftar' }}</div>
                        </td>
                        <td>
                            <span class="badge-pill {{ $rek->jenis_nasabah === 'siswa' ? 'green' : 'blue' }}">
                                {{ ucfirst($rek->jenis_nasabah) }}
                            </span>
                            <span style="font-size: 12px; color: var(--text-muted); margin-left: 6px;">{{ $rek->kelas_unit ?? '-' }}</span>
                        </td>
                        <td style="font-weight: 600; color: var(--text-heading); font-size: 13px;">
                            {{ $rek->no_induk ?? '-' }}
                        </td>
                        <td style="font-size: 13px; color: var(--text-muted);">
                            {{ $rek->nasabah_no_hp ?? '-' }}
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-pill amber" style="font-size: 11px;">
                                Pending Setoran
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" 
                                    class="btn-ui btn-ui-primary btn-ui-sm"
                                    onclick="openAktivasiModal('{{ $rek->rekening_id }}', '{{ $rek->nama_nasabah }}', '{{ $rek->no_rek }}', '{{ $rek->no_induk }}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>Verifikasi & Buka</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-placeholder" style="text-align: center; padding: 40px 20px;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 12px; color: var(--text-faint); display: block;"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                <div style="font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Tidak Ada Antrean Pembukaan Rekening</div>
                                <div style="font-size: 13px; color: var(--text-muted);">Semua calon nasabah yang mendaftar online telah diproses dan diaktifkan oleh Teller.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pendingRekening->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border-color);">
            {{ $pendingRekening->links() }}
        </div>
    @endif
</div>

{{-- ── MODAL OTORISASI & PEMBUKAAN REKENING ── --}}
<div id="aktivasiModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; animation: modalFadeIn 0.2s ease;">
        <div style="background: #0d5c46; padding: 20px 24px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; padding: 4px;">
                    <img src="{{ asset('images/sipintar_mark.svg') }}" alt="SIPINTAR" style="width: 24px; height: 24px; object-fit: contain;">
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 17px; font-weight: 700;">Otorisasi Pembukaan Rekening</h3>
                    <div style="font-size: 12px; opacity: 0.85;">Pusat Validasi Loket Teller SIPINTAR</div>
                </div>
            </div>
            <button type="button" onclick="closeAktivasiModal()" style="background: none; border: none; color: #ffffff; cursor: pointer; padding: 4px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="formAktivasiRekening" method="POST" action="" style="padding: 24px;">
            @csrf
            
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                    <div>
                        <span style="color: #64748b; display: block; font-size: 11px;">Nama Calon Nasabah:</span>
                        <strong id="modalNamaNasabah" style="color: #0f172a; font-size: 14px;">-</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; display: block; font-size: 11px;">Nomor Induk (NIS/NIP):</span>
                        <strong id="modalNoInduk" style="color: #0f172a; font-size: 14px;">-</strong>
                    </div>
                    <div style="grid-column: span 2; border-top: 1px dashed #cbd5e1; padding-top: 8px;">
                        <span style="color: #64748b; font-size: 11px;">Nomor Rekening Baru:</span>
                        <span id="modalNoRek" style="font-family: ui-monospace, monospace; font-weight: 700; color: #0d5c46; margin-left: 6px;">-</span>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px;">
                    Nominal Uang Setoran Awal Tunai (Wajib Minimum Rp 10.000):
                </label>
                <div style="position: relative; display: flex; align-items: center;">
                    <span style="position: absolute; left: 14px; font-weight: 700; color: #64748b; font-size: 14px;">Rp</span>
                    <input type="number" 
                           name="setoran_awal" 
                           id="inputSetoranAwal"
                           value="10000" 
                           min="10000" 
                           step="1000" 
                           required 
                           style="width: 100%; padding: 10px 14px 10px 42px; font-size: 16px; font-weight: 700; border: 2px solid #cbd5e1; border-radius: 8px; color: #0f172a;">
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 5px;">
                    * Uang kas tunai fisik langsung dibukukan ke Kas Brankas Bank Mini Sekolah dan saldo awal rekening nasabah.
                </div>
            </div>

            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; margin-bottom: 24px; font-size: 12px; color: #166534;">
                <div style="display: flex; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                        <strong>Pemeriksaan Fisik Teller:</strong> Teller memastikan calon nasabah hadir langsung, kartu identitas asli telah diperiksa, dan uang setoran tunai telah dihitung.
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeAktivasiModal()" class="btn-ui btn-ui-outline">Batal</button>
                <button type="submit" class="btn-ui btn-ui-primary" style="background: #0d5c46; border-color: #0d5c46; color: #ffffff;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Setujui & Buka Rekening</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAktivasiModal(rekId, nama, noRek, noInduk) {
        document.getElementById('modalNamaNasabah').innerText = nama;
        document.getElementById('modalNoRek').innerText = noRek;
        document.getElementById('modalNoInduk').innerText = noInduk || '-';
        document.getElementById('formAktivasiRekening').action = "{{ url('tabungan/buka-rekening/aktivasi') }}/" + rekId;
        
        var modal = document.getElementById('aktivasiModal');
        modal.style.display = 'flex';
    }

    function closeAktivasiModal() {
        document.getElementById('aktivasiModal').style.display = 'none';
    }

    // Tutup jika klik di luar modal
    window.onclick = function(event) {
        var modal = document.getElementById('aktivasiModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

@endsection
