@extends('layouts.app')

@section('title', 'Form Permohonan Pinjaman Pegawai')
@section('breadcrumb', 'Pengajuan Pinjaman')

@section('content')

<div style="max-width: 860px; margin: 0 auto;">

    {{-- Header Halaman Bersih --}}
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 22px; font-weight: 800; color: var(--text-heading); margin-bottom: 4px; letter-spacing: -0.02em;">
            Form Permohonan Pinjaman Pegawai
        </h1>
        <p style="font-size: 13.5px; color: var(--text-muted); margin: 0;">
            Fasilitas pembiayaan lunak mandiri untuk Guru dan Tenaga Kependidikan SMK Negeri 1 Lemahabang
        </p>
    </div>

    @if (session('error'))
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:16px 20px; margin-bottom:24px; color:#991B1B; font-size:13.5px; display:flex; align-items:center; gap:10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:16px 20px; margin-bottom:24px; color:#991B1B; font-size:13.5px;">
            <div style="font-weight:700; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Terdapat kesalahan dalam pengisian formulir:
            </div>
            <ul style="margin:0; padding-left:26px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- KONDISI 1: JIKA NASABAH MEMILIKI PINJAMAN AKTIF (NOTIFIKASI TERKUNCI)       --}}
    {{-- ========================================================================= --}}
    @if ($pinjamanAktif)
        <div class="content-card" style="padding: 32px; background: #FFFFFF; border: 1.5px solid #FDE68A; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 24px;">
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px;">
                        <h2 style="font-size: 18px; font-weight: 800; color: #92400E; margin: 0;">
                            Fasilitas Pinjaman Baru Belum Dapat Diajukan
                        </h2>
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 3px 10px; border-radius: 999px; 
                            @if ($pinjamanAktif->status_pinjaman === 'cair') background: #DCFCE7; color: #166534;
                            @elseif ($pinjamanAktif->status_pinjaman === 'layak') background: #DBEAFE; color: #1E40AF;
                            @else background: #FEF3C7; color: #92400E; @endif">
                            Pinjaman Aktif (#PINJ-{{ $pinjamanAktif->pinjaman_id }}) • {{ strtoupper($pinjamanAktif->status_pinjaman) }}
                        </span>
                    </div>

                    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 18px 0;">
                        @if ($pinjamanAktif->status_pinjaman === 'cair')
                            Anda saat ini masih memiliki kewajiban pinjaman yang sedang berjalan. Sesuai dengan Peraturan Bank Mini SMK Negeri 1 Lemahabang dan ketentuan batas rasio kapasitas angsuran (DSR), setiap pegawai hanya diperkenankan memiliki <strong>1 (satu) fasilitas pinjaman aktif</strong>. Fasilitas pengajuan pinjaman baru akan terbuka kembali secara otomatis segera setelah pinjaman saat ini diselesaikan (lunas 100%).
                        @elseif ($pinjamanAktif->status_pinjaman === 'layak')
                            Permohonan pinjaman Anda sebelumnya telah dinyatakan <strong>LAYAK</strong> oleh Komite Bank Mini dan saat ini sedang menunggu antrean pencairan dana di loket Bank Mini.
                        @else
                            Permohonan pinjaman Anda sebelumnya saat ini sedang dalam antrean <strong>evaluasi & penilaian kelayakan</strong> oleh Bendahara Bank Mini.
                        @endif
                    </p>

                    {{-- Ringkasan Pinjaman yang Sedang Berjalan --}}
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px; margin-bottom: 22px;">
                        <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 12px;">
                            Ringkasan Fasilitas Pinjaman Berjalan Anda
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted);">Nomor Pinjaman</div>
                                <div style="font-weight: 800; font-size: 14px; color: var(--text-heading);">#PINJ-{{ $pinjamanAktif->pinjaman_id }}</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted);">Plafon Pinjaman</div>
                                <div style="font-weight: 800; font-size: 15px; color: var(--brand-primary, #0D5C46);">
                                    Rp {{ number_format($pinjamanAktif->nominal_pinjaman, 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted);">Tenor / Jangka Waktu</div>
                                <div style="font-weight: 700; font-size: 13.5px; color: var(--text-heading);">{{ $pinjamanAktif->tenor }} Bulan</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted);">Metode Pembayaran</div>
                                <div style="font-weight: 700; font-size: 13px; color: var(--text-heading);">
                                    {{ $pinjamanAktif->metode_bayar === 'potong_gaji' ? 'Potong Tunjangan / Payroll' : 'Setor Tunai di Loket' }}
                                </div>
                            </div>
                            @if ($pinjamanAktif->status_pinjaman === 'cair')
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted);">Sisa Kewajiban Angsuran</div>
                                <div style="font-weight: 800; font-size: 13.5px; color: #D97706;">
                                    {{ $sisaAngsuranBulan }} Bulan Belum Bayar
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tombol Navigasi Alternatif --}}
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="{{ route('nasabah.angsuran') }}" class="btn-ui btn-ui-primary" style="padding: 10px 20px; font-weight: 700; font-size: 13.5px; text-decoration: none;">
                            Lihat Jadwal Angsuran & Sisa Piutang
                        </a>
                        <a href="{{ route('nasabah.pinjaman') }}" class="btn-ui btn-ui-outline" style="padding: 10px 18px; font-weight: 600; font-size: 13.5px; text-decoration: none;">
                            Lihat Rincian Fasilitas Pinjaman
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn-ui btn-ui-outline" style="padding: 10px 18px; font-weight: 600; font-size: 13.5px; text-decoration: none;">
                            Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- KONDISI 2: TIDAK ADA PINJAMAN AKTIF (TAMPILKAN FORMULIR PENGAJUAN PINJAMAN) --}}
    {{-- ========================================================================= --}}
    @else
        <form method="POST" action="{{ route('nasabah.pinjaman.store') }}" id="formPinjamanNasabah">
            @csrf

            {{-- Hidden Tanggal Pengajuan (Paten Hari Ini) --}}
            <input type="hidden" name="tgl_pengajuan" id="tgl_pengajuan" value="{{ date('Y-m-d') }}">

            <div style="display: flex; flex-direction: column; gap: 22px;">
                
                {{-- KARTU 1: IDENTITAS PEMINJAM --}}
                <div class="content-card" style="padding: 24px; background: #FFFFFF; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                    <div style="font-size: 14px; font-weight: 700; color: var(--text-heading); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--brand-primary-light, #ECFDF5); color: var(--brand-primary, #0D5C46); font-size: 12px; font-weight: 800;">1</span>
                        Identitas Pegawai Peminjam
                    </div>

                    @php
                        $namaParts = explode(' ', trim($nasabah->nama_nasabah));
                        $initials = '';
                        foreach (array_slice($namaParts, 0, 2) as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        $gajiPokok = (float)($nasabah->gaji_pokok ?? 0);
                        $tunjangan = (float)($nasabah->tunjangan ?? 0);
                        $totalGaji = $gajiPokok + $tunjangan;
                        $limit40 = $totalGaji * 0.40;
                    @endphp

                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px;">
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                            <div style="width: 46px; height: 46px; border-radius: 50%; background: #0D5C46; color: #FFFFFF; font-weight: 800; font-size: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                {{ $initials ?: 'PG' }}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 15px; font-weight: 700; color: var(--text-heading); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $nasabah->nama_nasabah }}
                                </div>
                                <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
                                    Unit Kerja: {{ $nasabah->kelas_unit ?? 'Pegawai SMKN 1 Lemahabang' }} &bull; NIP: {{ $nasabah->nomor_identitas ?? '-' }}
                                </div>
                            </div>
                        </div>

                        {{-- Notifikasi jika data gaji masih 0 --}}
                        @if ($totalGaji <= 0)
                            <div style="background:#FEF3C7; border:1.5px solid #FDE68A; border-radius:8px; padding:12px 14px; margin-bottom:12px; color:#92400E; font-size:12px; line-height:1.45;">
                                <div style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:3px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    Data Penghasilan Belum Tercatat
                                </div>
                                Masukkan referensi gaji pokok dan tunjangan rutin Anda di bawah agar sistem dapat mengkalkulasi batas aman kapasitas angsuran (DSR).
                            </div>
                        @endif

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Gaji Pokok</div>
                                <div id="dispGaji" style="font-size: 13.5px; font-weight: 700; color: var(--text-heading); margin-top: 2px;">
                                    Rp {{ number_format($gajiPokok, 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tunjangan Rutin</div>
                                <div id="dispTunjangan" style="font-size: 13.5px; font-weight: 700; color: var(--text-heading); margin-top: 2px;">
                                    Rp {{ number_format($tunjangan, 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Penghasilan</div>
                                <div id="dispTotalGaji" style="font-size: 13.5px; font-weight: 800; color: var(--brand-primary, #0D5C46); margin-top: 2px;">
                                    Rp {{ number_format($totalGaji, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: #334155; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 8px 12px;">
                            <span>Kapasitas Angsuran Aman (Batas 40%):</span>
                            <strong id="dispLimit40" style="color: #065F46; font-size: 13px;">Rp {{ number_format($limit40, 0, ',', '.') }} / bln</strong>
                        </div>
                    </div>
                </div>

                {{-- KARTU 2: NOMINAL & TENOR PINJAMAN (DENGAN SIMULASI MENYATU) --}}
                <div class="content-card" style="padding: 24px; background: #FFFFFF; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                    <div style="font-size: 14px; font-weight: 700; color: var(--text-heading); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--brand-primary-light, #ECFDF5); color: var(--brand-primary, #0D5C46); font-size: 12px; font-weight: 800;">2</span>
                        Nominal & Tenor Pinjaman
                    </div>

                    {{-- Plafon Pinjaman --}}
                    <div style="margin-bottom: 20px;">
                        <label class="form-label" for="nominal_pinjaman" style="display:block; font-size:13px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
                            Plafon Pinjaman yang Diajukan (Rp) <span class="required-mark" style="color:var(--color-danger, #DC2626);">*</span>
                        </label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 800; font-size: 15px; color: var(--text-muted);">Rp</span>
                            <input type="number" name="nominal_pinjaman" id="nominal_pinjaman" 
                                   class="form-input" 
                                   placeholder="Contoh: 3000000" 
                                   min="500000" 
                                   max="30000000"
                                   step="50000" 
                                   value="{{ old('nominal_pinjaman', 3000000) }}" 
                                   required 
                                   oninput="calculateSimulation()"
                                   style="width: 100%; padding: 12px 14px 12px 46px; font-size: 16px; font-weight: 700; border: 1.5px solid var(--border-medium, #CBD5E1); border-radius: 8px; color: var(--text-heading); letter-spacing: 0.02em;">
                        </div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Minimal pinjaman Rp 500.000 &bull; Maksimal Rp 30.000.000</div>

                        {{-- Quick Chips --}}
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
                            <button type="button" class="chip-btn" onclick="setNominal(1000000)">Rp 1 Jt</button>
                            <button type="button" class="chip-btn" onclick="setNominal(3000000)">Rp 3 Jt</button>
                            <button type="button" class="chip-btn" onclick="setNominal(5000000)">Rp 5 Jt</button>
                            <button type="button" class="chip-btn" onclick="setNominal(10000000)">Rp 10 Jt</button>
                            <button type="button" class="chip-btn" onclick="setNominal(15000000)">Rp 15 Jt</button>
                            <button type="button" class="chip-btn" onclick="setNominal(20000000)">Rp 20 Jt</button>
                        </div>
                    </div>

                    {{-- Tenor Pinjaman --}}
                    <div style="margin-bottom: 20px;">
                        <label class="form-label" style="display:block; font-size:13px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
                            Jangka Waktu Cicilan (Tenor) <span class="required-mark" style="color:var(--color-danger, #DC2626);">*</span>
                        </label>
                        
                        {{-- Tenor Radio Pills --}}
                        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-bottom: 12px;">
                            @foreach ([3, 5, 6, 10, 12, 20] as $t)
                                <label class="tenor-pill" id="tenor-label-{{ $t }}" style="cursor: pointer; text-align: center; border: 1.5px solid var(--border-medium, #CBD5E1); border-radius: 8px; padding: 10px 4px; font-weight: 700; font-size: 13px; transition: all 0.15s ease;">
                                    <input type="radio" name="tenor_preset" value="{{ $t }}" {{ old('tenor', 10) == $t ? 'checked' : '' }} onchange="setTenor({{ $t }})" style="display: none;">
                                    <span>{{ $t }} Bln</span>
                                </label>
                            @endforeach
                        </div>

                        {{-- Manual Tenor Input Fallback --}}
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 12px; color: var(--text-muted);">Atau tentukan tenor kustom:</span>
                            <div style="position: relative; width: 140px;">
                                <input type="number" name="tenor" id="tenor" class="form-input" 
                                       min="1" max="36" 
                                       value="{{ old('tenor', 10) }}" 
                                       required 
                                       oninput="onCustomTenorInput()"
                                       style="padding: 6px 36px 6px 12px; font-size: 13.5px; font-weight: 700; border: 1px solid var(--border-medium); border-radius: 6px; width: 100%;">
                                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--text-muted); font-weight: 600;">Bulan</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── SIMULASI MENYATU (TERINTEGRASI LANGSUNG DI BAWAH TENOR) ── --}}
                    <div style="background: #F0FDF4; border: 1.5px solid #A7F3D0; border-radius: 12px; padding: 18px 20px; margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 28px; height: 28px; border-radius: 6px; background: #0D5C46; color: #FFFFFF; display: flex; align-items: center; justify-content: center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                                </div>
                                <div>
                                    <div style="font-size: 13.5px; font-weight: 800; color: #065F46;">Simulasi Cicilan Bulanan</div>
                                    <div style="font-size: 11.5px; color: #047857;">Jasa Flat 2% per bulan dari nominal pinjaman</div>
                                </div>
                            </div>
                            <span style="background: #0D5C46; color: #FFFFFF; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px;">
                                Jasa Flat 2,0% / Bln
                            </span>
                        </div>

                        {{-- 3-Box Breakdown --}}
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; background: #FFFFFF; border: 1px solid #A7F3D0; border-radius: 10px; padding: 14px; margin-bottom: 14px;">
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Angsuran Pokok / Bln</div>
                                <div id="simPokok" style="font-size: 15px; font-weight: 800; color: var(--text-heading); margin-top: 3px;">Rp 0</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Jasa 2% / Bln</div>
                                <div id="simJasa" style="font-size: 15px; font-weight: 800; color: #047857; margin-top: 3px;">Rp 0</div>
                            </div>
                            <div style="background: #ECFDF5; border-radius: 6px; padding: 4px 10px; border: 1px solid #A7F3D0;">
                                <div style="font-size: 11px; color: #065F46; font-weight: 800; text-transform: uppercase;">Total Tagihan / Bln</div>
                                <div id="simTotalBulan" style="font-size: 17px; font-weight: 800; color: #0D5C46; margin-top: 2px;">Rp 0</div>
                            </div>
                        </div>

                        {{-- DSR Capacity Meter --}}
                        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 700; color: var(--text-heading);">Perkiraan Rasio Beban Angsuran (DSR):</span>
                                <span id="dsrBadge" style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; background: #DCFCE7; color: #166534;">
                                    0% (Sangat Aman)
                                </span>
                            </div>
                            <div style="width: 100%; height: 8px; background: #E2E8F0; border-radius: 999px; overflow: hidden; margin-bottom: 6px;">
                                <div id="dsrBar" style="width: 0%; height: 100%; background: #0D5C46; transition: width 0.3s ease, background-color 0.3s ease;"></div>
                            </div>
                            <div id="dsrNote" style="font-size: 11px; color: var(--text-muted);">
                                Rasio beban angsuran bulanan terhadap kapasitas penghasilan rutin Anda.
                            </div>
                        </div>
                    </div>

                </div>

                {{-- KARTU 3: METODE PENGEMBALIAN & TANGGAL PENGAJUAN --}}
                <div class="content-card" style="padding: 24px; background: #FFFFFF; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                    <div style="font-size: 14px; font-weight: 700; color: var(--text-heading); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--brand-primary-light, #ECFDF5); color: var(--brand-primary, #0D5C46); font-size: 12px; font-weight: 800;">3</span>
                        Metode Pengembalian Angsuran & Tanggal Pengajuan
                    </div>

                    {{-- Info Tanggal Otomatis Hari Ini --}}
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:10px 14px; margin-bottom:16px;">
                        <span style="font-size:12.5px; color:var(--text-muted);">Tanggal Pengajuan Permohonan:</span>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span class="badge-pill green" style="font-size:11.5px; font-weight:700;">Hari Ini</span>
                            <strong style="color:var(--text-heading); font-size:13px;">{{ date('d F Y') }}</strong>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        {{-- Option 1: Potong Payroll --}}
                        <label class="metode-card" id="card-potong" style="border: 1.5px solid var(--brand-primary, #0D5C46); background: #F0FDF4; border-radius: 10px; padding: 16px; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; transition: all 0.15s ease;">
                            <input type="radio" name="metode_bayar" id="metode_potong" value="potong_gaji" {{ old('metode_bayar', 'potong_gaji') === 'potong_gaji' ? 'checked' : '' }} onchange="updateMetodeVisual()" style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: 14px; color: var(--text-heading);">Potong Tunjangan / Payroll</div>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                                    Dipotong otomatis setiap bulan oleh Bendahara Sekolah dari slip tunjangan/gaji mitra.
                                </div>
                                <span style="display: inline-block; font-size: 10.5px; font-weight: 700; color: #065F46; background: #D1FAE5; padding: 2px 8px; border-radius: 4px; margin-top: 8px;">Direkomendasikan</span>
                            </div>
                        </label>

                        {{-- Option 2: Tunai Loket --}}
                        <label class="metode-card" id="card-tunai" style="border: 1.5px solid var(--border-medium, #CBD5E1); background: #FFFFFF; border-radius: 10px; padding: 16px; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; transition: all 0.15s ease;">
                            <input type="radio" name="metode_bayar" id="metode_tunai" value="tunai" {{ old('metode_bayar') === 'tunai' ? 'checked' : '' }} onchange="updateMetodeVisual()" style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: 14px; color: var(--text-heading);">Setor Langsung di Loket</div>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                                    Dibayar mandiri tunai di loket kasir Bank Mini sebelum tanggal jatuh tempo.
                                </div>
                                <span style="display: inline-block; font-size: 10.5px; font-weight: 700; color: #475569; background: #F1F5F9; padding: 2px 8px; border-radius: 4px; margin-top: 8px;">Mandiri</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- ── FOOTER SUBMIT WITH IN-APP CONFIRMATION ── --}}
                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; padding: 20px 0; border-top: 1px solid var(--border-subtle);">
                    <a href="{{ route('dashboard') }}" class="btn-ui btn-ui-outline" style="padding: 10px 22px; font-weight: 600; font-size: 14px; text-decoration: none;">
                        Batal
                    </a>
                    <button type="button" class="btn-ui btn-ui-primary" onclick="konfirmasiKirimPinjaman()" style="padding: 11px 28px; font-weight: 800; font-size: 14.5px; background: #0D5C46; border-color: #0D5C46;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Kirim Permohonan Pinjaman
                    </button>
                </div>

            </div>
        </form>
    @endif

</div>

<style>
.chip-btn {
    border: 1px solid var(--border-medium, #CBD5E1);
    background: #FFFFFF;
    color: var(--text-heading, #0F172A);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.chip-btn:hover {
    border-color: #0D5C46;
    background: #ECFDF5;
    color: #0D5C46;
}
.tenor-pill.active {
    background: #0D5C46 !important;
    border-color: #0D5C46 !important;
    color: #FFFFFF !important;
}
</style>

@if (!$pinjamanAktif)
<script>
    const userIncome = {{ (float)(($nasabah->gaji_pokok ?? 0) + ($nasabah->tunjangan ?? 0)) }};

    function formatRupiah(num) {
        return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function setNominal(val) {
        document.getElementById('nominal_pinjaman').value = val;
        calculateSimulation();
    }

    function setTenor(val) {
        document.getElementById('tenor').value = val;
        
        document.querySelectorAll('.tenor-pill').forEach(el => el.classList.remove('active'));
        const activeLabel = document.getElementById('tenor-label-' + val);
        if (activeLabel) {
            activeLabel.classList.add('active');
            const radio = activeLabel.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }
        
        calculateSimulation();
    }

    function onCustomTenorInput() {
        const val = parseInt(document.getElementById('tenor').value) || 0;
        document.querySelectorAll('.tenor-pill').forEach(el => el.classList.remove('active'));
        const matchedLabel = document.getElementById('tenor-label-' + val);
        if (matchedLabel) {
            matchedLabel.classList.add('active');
            const radio = matchedLabel.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        } else {
            document.querySelectorAll('input[name="tenor_preset"]').forEach(r => r.checked = false);
        }
        calculateSimulation();
    }

    function updateMetodeVisual() {
        const potongRadio = document.getElementById('metode_potong');
        const cardPotong = document.getElementById('card-potong');
        const cardTunai = document.getElementById('card-tunai');

        if (potongRadio && potongRadio.checked) {
            cardPotong.style.borderColor = '#0D5C46';
            cardPotong.style.background = '#F0FDF4';
            cardTunai.style.borderColor = '#CBD5E1';
            cardTunai.style.background = '#FFFFFF';
        } else if (cardTunai) {
            cardTunai.style.borderColor = '#0D5C46';
            cardTunai.style.background = '#F0FDF4';
            cardPotong.style.borderColor = '#CBD5E1';
            cardPotong.style.background = '#FFFFFF';
        }
    }

    function calculateSimulation() {
        const nominalInput = document.getElementById('nominal_pinjaman');
        const tenorInput = document.getElementById('tenor');

        const plafon = parseFloat(nominalInput.value) || 0;
        const tenor = parseInt(tenorInput.value) || 0;

        if (plafon <= 0 || tenor <= 0) {
            document.getElementById('simPokok').innerText = 'Rp 0';
            document.getElementById('simJasa').innerText = 'Rp 0';
            document.getElementById('simTotalBulan').innerText = 'Rp 0';
            updateDsrGauge(0);
            return;
        }

        const pokokPerBulan = plafon / tenor;
        const jasaPerBulan = plafon * 0.02; // Jasa flat 2% per bulan dari pokok awal
        const totalPerBulan = pokokPerBulan + jasaPerBulan;

        document.getElementById('simPokok').innerText = formatRupiah(pokokPerBulan);
        document.getElementById('simJasa').innerText = formatRupiah(jasaPerBulan);
        document.getElementById('simTotalBulan').innerText = formatRupiah(totalPerBulan);

        let dsrPercent = 0;
        if (userIncome > 0) {
            dsrPercent = (totalPerBulan / userIncome) * 100;
        }
        updateDsrGauge(dsrPercent);
    }

    function updateDsrGauge(dsr) {
        const badge = document.getElementById('dsrBadge');
        const bar = document.getElementById('dsrBar');
        const note = document.getElementById('dsrNote');

        const dsrRound = Math.round(dsr);
        const cappedWidth = Math.min(Math.max(dsrRound, 0), 100);
        bar.style.width = cappedWidth + '%';

        if (userIncome <= 0) {
            badge.innerText = '0% Beban';
            badge.style.background = '#F1F5F9';
            badge.style.color = '#475569';
            bar.style.backgroundColor = '#94A3B8';
            note.innerText = 'Rasio beban angsuran terhadap penghasilan rutin bulanan Anda.';
            return;
        }

        if (dsrRound <= 30) {
            badge.innerText = dsrRound + '% (Sangat Aman <= 30%)';
            badge.style.background = '#DCFCE7';
            badge.style.color = '#166534';
            bar.style.backgroundColor = '#0D5C46';
            note.innerText = 'Kapasitas angsuran sangat sehat (jauh di bawah batas maksimal 40%).';
        } else if (dsrRound <= 40) {
            badge.innerText = dsrRound + '% (Wajar / Perhatian)';
            badge.style.background = '#FEF3C7';
            badge.style.color = '#92400E';
            bar.style.backgroundColor = '#D97706';
            note.innerText = 'Kapasitas angsuran mendekati ambang batas aman 40%.';
        } else {
            badge.innerText = dsrRound + '% (Beban Tinggi)';
            badge.style.background = '#FEE2E2';
            badge.style.color = '#991B1B';
            bar.style.backgroundColor = '#DC2626';
            note.innerText = 'Peringatan: Angsuran melebihi batas toleransi 40% penghasilan bulanan.';
        }
    }

    function konfirmasiKirimPinjaman() {
        const nominalInput = document.getElementById('nominal_pinjaman');
        const tenorInput = document.getElementById('tenor');
        const plafon = parseFloat(nominalInput.value) || 0;
        const tenor = parseInt(tenorInput.value) || 0;

        if (plafon < 500000) {
            alert('Nominal pinjaman minimal adalah Rp 500.000');
            return;
        }

        if (tenor <= 0) {
            alert('Tenor pinjaman harus dipilih');
            return;
        }

        const metodeBayar = document.querySelector('input[name="metode_bayar"]:checked').value;
        const metodeLabel = metodeBayar === 'potong_gaji' ? 'Potong Tunjangan / Payroll' : 'Setor Tunai di Loket';

        sipintarConfirm({
            title: 'Kirim Permohonan Pinjaman?',
            message: `Ajukan pinjaman sebesar ${formatRupiah(plafon)} dengan tenor ${tenor} bulan melalui metode ${metodeLabel}? Permohonan akan diteruskan ke Bendahara Bank Mini untuk dinilai kelayakannya.`,
            confirmText: 'Ya, Kirim Permohonan',
            cancelText: 'Periksa Kembali',
            confirmColor: '#0D5C46',
            onConfirm: () => {
                document.getElementById('formPinjamanNasabah').submit();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const initialTenor = {{ old('tenor', 10) }};
        setTenor(initialTenor);
        updateMetodeVisual();
    });
</script>
@endif

@endsection
