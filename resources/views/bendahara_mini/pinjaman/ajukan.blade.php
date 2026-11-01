@extends('layouts.app')

@section('title', 'Form Pengajuan Pinjaman')
@section('breadcrumb', 'Pengajuan Pinjaman')

@section('content')

<div style="max-width: 860px; margin: 0 auto;">
    {{-- Header Halaman Bersih --}}
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 22px; font-weight: 800; color: var(--text-heading); margin-bottom: 4px; letter-spacing: -0.02em;">
            Form Permohonan Pinjaman Pegawai
        </h1>
        <p style="font-size: 13.5px; color: var(--text-muted); margin: 0;">
            Pencatatan permohonan pinjaman dana untuk Guru dan Tenaga Kependidikan SMK Negeri 1 Lemahabang
        </p>
    </div>

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

    <form method="POST" action="{{ route('bbm.pinjaman.store') }}" id="formPinjamanBbm">
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

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="nasabah_id" style="display:block; font-size:13px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
                        Pilih Guru / Karyawan <span class="required-mark" style="color:var(--color-danger, #DC2626);">*</span>
                    </label>
                    <select name="nasabah_id" id="nasabah_id" class="form-select" required onchange="onPegawaiChanged()"
                            style="width:100%; padding:10px 14px; font-size:14px; border:1px solid var(--border-medium, #CBD5E1); border-radius:8px; background-color:#FFFFFF; color:var(--text-heading);">
                        <option value="">-- Pilih Guru atau Tenaga Kependidikan --</option>
                        @foreach ($pegawaiList as $p)
                            @php
                                $namaParts = explode(' ', trim($p->nama_nasabah));
                                $initials = '';
                                foreach (array_slice($namaParts, 0, 2) as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                            @endphp
                            <option value="{{ $p->nasabah_id }}" 
                                    data-nama="{{ $p->nama_nasabah }}"
                                    data-unit="{{ $p->kelas_unit ?? 'Pegawai SMKN 1 Lemahabang' }}"
                                    data-gaji="{{ (float)$p->gaji_pokok }}" 
                                    data-tunjangan="{{ (float)$p->tunjangan }}"
                                    data-nohp="{{ $p->no_hp ?? '-' }}"
                                    data-initials="{{ $initials }}"
                                    {{ $p->pinjaman_aktif ? 'disabled style=color:#94A3B8;background:#F1F5F9;' : '' }}
                                    {{ old('nasabah_id') == $p->nasabah_id ? 'selected' : '' }}>
                                {{ $p->nama_nasabah }} — {{ $p->kelas_unit ?? 'Pegawai' }} {{ $p->pinjaman_aktif ? '⛔ (Pinjaman Aktif Sedang Berjalan #' . $p->pinjaman_aktif->pinjaman_id . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- LIVE CARD: PROFIL PENGHASILAN PEGAWAI --}}
                <div id="pegawaiInfoCard" style="display:none; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px; margin-top: 14px;">
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                        <div id="dispInitials" style="width: 44px; height: 44px; border-radius: 50%; background: #0D5C46; color: #FFFFFF; font-weight: 800; font-size: 15px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            PG
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div id="dispNama" style="font-size: 15px; font-weight: 700; color: var(--text-heading); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                Nama Pegawai
                            </div>
                            <div id="dispUnit" style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
                                Unit / Jabatan Pegawai
                            </div>
                        </div>
                    </div>

                    {{-- Peringatan jika data gaji pegawai baru masih 0 --}}
                    <div id="salaryZeroNotice" style="display:none; background:#FEF3C7; border:1.5px solid #FDE68A; border-radius:8px; padding:12px 14px; margin-bottom:12px; color:#92400E; font-size:12px; line-height:1.45;">
                        <div style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:3px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Data Gaji & Tunjangan Pegawai Belum Tercatat (Rp 0)
                        </div>
                        Masukkan referensi gaji & tunjangan tetap di bawah ini untuk menghitung kuota angsuran dan rasio DSR.
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;">
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Gaji Pokok</div>
                            <div id="dispGaji" style="font-size: 13.5px; font-weight: 700; color: var(--text-heading); margin-top: 2px;">Rp 0</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tunjangan</div>
                            <div id="dispTunjangan" style="font-size: 13.5px; font-weight: 700; color: var(--text-heading); margin-top: 2px;">Rp 0</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Penghasilan</div>
                            <div id="dispTotalGaji" style="font-size: 13.5px; font-weight: 800; color: var(--brand-primary, #0D5C46); margin-top: 2px;">Rp 0</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: #334155; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px;">
                        <span>Kapasitas Angsuran Aman (Batas 40%):</span>
                        <strong id="dispLimit40" style="color: #065F46; font-size: 13px;">Rp 0 / bln</strong>
                    </div>

                    {{-- SEKSI INPUT & PERBARUI DATA PENGHASILAN TERVERIFIKASI --}}
                    <div style="background: #FFFFFF; border: 1px dashed #CBD5E1; border-radius: 8px; padding: 12px 14px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:12px; font-weight:700; color:#0F172A;">Perbarui Data Penghasilan Terverifikasi</span>
                            <span style="font-size:10.5px; color:#64748B;">Otomatis tersimpan ke profil pegawai</span>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="display:block; font-size:11px; font-weight:600; color:#475569; margin-bottom:3px;">Gaji Pokok (Rp)</label>
                                <div style="position:relative;">
                                    <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-weight:700; font-size:12px; color:#64748B;">Rp</span>
                                    <input type="number" name="gaji_pokok" id="inputGaji" min="0" step="50000" placeholder="Contoh: 4000000" oninput="onCustomSalaryChange()"
                                           style="width:100%; padding:7px 10px 7px 32px; font-size:13px; font-weight:700; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box;">
                                </div>
                            </div>
                            <div>
                                <label style="display:block; font-size:11px; font-weight:600; color:#475569; margin-bottom:3px;">Tunjangan Rutin (Rp)</label>
                                <div style="position:relative;">
                                    <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-weight:700; font-size:12px; color:#64748B;">Rp</span>
                                    <input type="number" name="tunjangan" id="inputTunjangan" min="0" step="50000" placeholder="Contoh: 1000000" oninput="onCustomSalaryChange()"
                                           style="width:100%; padding:7px 10px 7px 32px; font-size:13px; font-weight:700; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="pegawaiEmptyNotice" style="background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 8px; padding: 14px; text-align: center; color: var(--text-muted); font-size: 12.5px;">
                    Silakan pilih nama guru atau karyawan di atas untuk memeriksa profil penghasilan dan kapasitas kredit.
                </div>
            </div>

            {{-- KARTU 2: NOMINAL & TENOR PINJAMAN --}}
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
                               min="100000" 
                               step="50000" 
                               value="{{ old('nominal_pinjaman', 3000000) }}" 
                               required 
                               oninput="calculateSimulation()"
                               style="width: 100%; padding: 12px 14px 12px 46px; font-size: 16px; font-weight: 700; border: 1.5px solid var(--border-medium, #CBD5E1); border-radius: 8px; color: var(--text-heading); letter-spacing: 0.02em;">
                    </div>
                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">Minimal pinjaman Rp 100.000</div>

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
                <div style="margin-bottom: 6px;">
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
                                   min="1" max="60" 
                                   value="{{ old('tenor', 10) }}" 
                                   required 
                                   oninput="onCustomTenorInput()"
                                   style="padding: 6px 36px 6px 12px; font-size: 13.5px; font-weight: 700; border: 1px solid var(--border-medium); border-radius: 6px; width: 100%;">
                            <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--text-muted); font-weight: 600;">Bulan</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU 3: SIMULASI PEMBIAYAAN & BEBAN ANGSURAN (MENYATU TERINTEGRASI) --}}
            <div class="content-card" style="padding: 24px; background: #FFFFFF; border: 1.5px solid #0D5C46; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div style="font-size: 14px; font-weight: 800; color: #0D5C46; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #0D5C46; color: #FFFFFF; font-size: 12px; font-weight: 800;">3</span>
                        Simulasi Pembiayaan & Analisis Beban Angsuran
                    </div>
                    <span style="background: #ECFDF5; color: #065F46; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; border: 1px solid #A7F3D0;">
                        Jasa 2,0% Flat / Bln
                    </span>
                </div>

                {{-- 3 Box Utama: Pokok, Jasa, Total --}}
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 16px;">
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 14px;">
                        <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Angsuran Pokok / Bln</div>
                        <div id="simPokok" style="font-size: 15px; font-weight: 800; color: var(--text-heading); margin-top: 3px;">Rp 0</div>
                    </div>
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 14px;">
                        <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Jasa Bank Mini / Bln</div>
                        <div id="simJasa" style="font-size: 15px; font-weight: 800; color: #047857; margin-top: 3px;">Rp 0</div>
                    </div>
                    <div style="background: #ECFDF5; border: 1.5px solid #A7F3D0; border-radius: 8px; padding: 12px 14px;">
                        <div style="font-size: 11px; color: #065F46; font-weight: 700; text-transform: uppercase;">Total Cicilan Bulanan</div>
                        <div id="simTotalBulan" style="font-size: 18px; font-weight: 800; color: #0D5C46; margin-top: 2px;">Rp 0</div>
                    </div>
                </div>

                {{-- DSR Capacity Gauge --}}
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--text-heading);">Beban Angsuran terhadap Penghasilan (DSR):</span>
                        <span id="dsrBadge" style="font-size: 11.5px; font-weight: 700; padding: 2px 10px; border-radius: 4px; background: #E2E8F0; color: #475569;">
                            Menunggu Peminjam
                        </span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #E2E8F0; border-radius: 999px; overflow: hidden; margin-bottom: 6px;">
                        <div id="dsrBar" style="width: 0%; height: 100%; background: #0D5C46; transition: width 0.3s ease, background-color 0.3s ease;"></div>
                    </div>
                    <div id="dsrNote" style="font-size: 11.5px; color: var(--text-muted); line-height: 1.4;">
                        Pilih peminjam di atas untuk memeriksa persentase cicilan terhadap penghasilan bulanan.
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; padding-top: 10px; border-top: 1px dashed #CBD5E1;">
                    <span style="color: var(--text-muted);">Total Akumulasi Pembayaran (Pokok + Jasa Selama Tenor):</span>
                    <strong id="simTotalSemua" style="color: var(--text-heading); font-size: 14px;">Rp 0</strong>
                </div>
            </div>

            {{-- KARTU 4: METODE PENGEMBALIAN & TANGGAL EFEKTIF (PATEN HARI INI) --}}
            <div class="content-card" style="padding: 24px; background: #FFFFFF; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="font-size: 14px; font-weight: 700; color: var(--text-heading); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--brand-primary-light, #ECFDF5); color: var(--brand-primary, #0D5C46); font-size: 12px; font-weight: 800;">4</span>
                    Metode Pengembalian & Tanggal Efektif
                </div>

                {{-- Metode Pembayaran Cards --}}
                <div style="margin-bottom: 18px;">
                    <label class="form-label" style="display:block; font-size:13px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
                        Pilihan Metode Pengembalian Angsuran <span class="required-mark" style="color:var(--color-danger, #DC2626);">*</span>
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        {{-- Option 1: Potong Payroll --}}
                        <label class="metode-card" id="card-potong" style="border: 1.5px solid var(--brand-primary, #0D5C46); background: #F0FDF4; border-radius: 10px; padding: 16px; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; transition: all 0.15s ease;">
                            <input type="radio" name="metode_bayar" id="metode_potong" value="potong_gaji" {{ old('metode_bayar', 'potong_gaji') === 'potong_gaji' ? 'checked' : '' }} onchange="updateMetodeVisual()" style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: 14px; color: var(--text-heading);">Potong Gaji / Tunjangan</div>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                                    Dipotong otomatis dari slip gaji/tunjangan bulanan oleh Bendahara Sekolah.
                                </div>
                                <span style="display: inline-block; font-size: 10.5px; font-weight: 700; color: #065F46; background: #D1FAE5; padding: 2px 8px; border-radius: 4px; margin-top: 8px;">Direkomendasikan</span>
                            </div>
                        </label>

                        {{-- Option 2: Tunai Loket --}}
                        <label class="metode-card" id="card-tunai" style="border: 1.5px solid var(--border-medium, #CBD5E1); background: #FFFFFF; border-radius: 10px; padding: 16px; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; transition: all 0.15s ease;">
                            <input type="radio" name="metode_bayar" id="metode_tunai" value="tunai" {{ old('metode_bayar') === 'tunai' ? 'checked' : '' }} onchange="updateMetodeVisual()" style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: 14px; color: var(--text-heading);">Setor Tunai di Loket</div>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                                    Peminjam membayar tunai mandiri ke kasir teller Bank Mini setiap jatuh tempo.
                                </div>
                                <span style="display: inline-block; font-size: 10.5px; font-weight: 700; color: #475569; background: #F1F5F9; padding: 2px 8px; border-radius: 4px; margin-top: 8px;">Mandiri</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Tanggal Efektif (Paten Hari Ini) --}}
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 11.5px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tanggal Efektif Pengajuan</div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-heading); margin-top: 2px;">
                            {{ date('d F Y') }} <span style="font-size: 11.5px; font-weight: 600; color: #0D5C46; background: #ECFDF5; padding: 2px 8px; border-radius: 4px; margin-left: 6px;">Hari Ini</span>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Sistem mencatat tanggal transaksi secara otomatis.
                    </div>
                </div>
            </div>

            {{-- TOMBOL AKSI DI PALING BAWAH --}}
            <div style="display: flex; gap: 14px; justify-content: flex-end; align-items: center; padding: 14px 0;">
                <a href="{{ route('bbm.pinjaman.index') }}" class="btn-ui btn-ui-outline" style="padding: 12px 24px; font-size: 14px; font-weight: 600;">
                    Batal
                </a>
                <button type="button" class="btn-ui btn-ui-primary" style="padding: 12px 32px; font-weight: 800; font-size: 14.5px; background: #0D5C46; border-color: #0D5C46;" onclick="konfirmasiSimpanPengajuan()">
                    Simpan Pengajuan Pinjaman
                </button>
            </div>

        </div>
    </form>
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
.chip-btn.active {
    background: #0D5C46;
    border-color: #0D5C46;
    color: #FFFFFF;
}
.tenor-pill:hover {
    border-color: #0D5C46 !important;
    background: #F0FDF4;
}
.tenor-pill.active {
    border-color: #0D5C46 !important;
    background: #0D5C46 !important;
    color: #FFFFFF !important;
}
</style>

<script>
let currentIncome = 0;

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function setNominal(val) {
    document.getElementById('nominal_pinjaman').value = val;
    highlightActiveChip(val);
    calculateSimulation();
}

function highlightActiveChip(val) {
    const chips = document.querySelectorAll('.chip-btn');
    chips.forEach(c => {
        const text = c.innerText.replace('Rp ', '').replace(' Jt', '');
        const nominal = parseInt(text) * 1000000;
        if (nominal === val) {
            c.classList.add('active');
        } else {
            c.classList.remove('active');
        }
    });
}

function setTenor(val) {
    document.getElementById('tenor').value = val;
    highlightTenorPill(val);
    calculateSimulation();
}

function highlightTenorPill(val) {
    document.querySelectorAll('.tenor-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    const activeLabel = document.getElementById('tenor-label-' + val);
    if (activeLabel) {
        activeLabel.classList.add('active');
        const radio = activeLabel.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }
}

function onCustomTenorInput() {
    const tenorInput = document.getElementById('tenor');
    const val = parseInt(tenorInput.value) || 0;
    highlightTenorPill(val);
    calculateSimulation();
}

function onPegawaiChanged() {
    const sel = document.getElementById('nasabah_id');
    const opt = sel.options[sel.selectedIndex];
    const infoCard = document.getElementById('pegawaiInfoCard');
    const emptyNotice = document.getElementById('pegawaiEmptyNotice');

    if (sel.value && opt) {
        const nama = opt.dataset.nama || '-';
        const unit = opt.dataset.unit || '-';
        const gaji = parseFloat(opt.dataset.gaji) || 0;
        const tunjangan = parseFloat(opt.dataset.tunjangan) || 0;
        const initials = opt.dataset.initials || 'PG';

        document.getElementById('dispInitials').innerText = initials;
        document.getElementById('dispNama').innerText = nama;
        document.getElementById('dispUnit').innerText = unit;

        document.getElementById('inputGaji').value = gaji > 0 ? gaji : '';
        document.getElementById('inputTunjangan').value = tunjangan > 0 ? tunjangan : '';

        updateSalaryDisplay(gaji, tunjangan);

        infoCard.style.display = 'block';
        emptyNotice.style.display = 'none';
    } else {
        currentIncome = 0;
        infoCard.style.display = 'none';
        emptyNotice.style.display = 'block';
    }

    calculateSimulation();
}

function onCustomSalaryChange() {
    const gaji = parseFloat(document.getElementById('inputGaji').value) || 0;
    const tunjangan = parseFloat(document.getElementById('inputTunjangan').value) || 0;
    updateSalaryDisplay(gaji, tunjangan);
    calculateSimulation();
}

function updateSalaryDisplay(gaji, tunjangan) {
    const total = gaji + tunjangan;
    const limit40 = total * 0.40;
    currentIncome = total;

    document.getElementById('dispGaji').innerText = formatRupiah(gaji);
    document.getElementById('dispTunjangan').innerText = formatRupiah(tunjangan);
    document.getElementById('dispTotalGaji').innerText = formatRupiah(total);
    document.getElementById('dispLimit40').innerText = formatRupiah(limit40) + ' / bln';

    const zeroNotice = document.getElementById('salaryZeroNotice');
    if (total === 0) {
        zeroNotice.style.display = 'block';
    } else {
        zeroNotice.style.display = 'none';
    }
}

function updateMetodeVisual() {
    const isPotong = document.getElementById('metode_potong').checked;
    const cardPotong = document.getElementById('card-potong');
    const cardTunai = document.getElementById('card-tunai');

    if (isPotong) {
        cardPotong.style.borderColor = '#0D5C46';
        cardPotong.style.backgroundColor = '#F0FDF4';
        cardTunai.style.borderColor = '#CBD5E1';
        cardTunai.style.backgroundColor = '#FFFFFF';
    } else {
        cardTunai.style.borderColor = '#0D5C46';
        cardTunai.style.backgroundColor = '#F0FDF4';
        cardPotong.style.borderColor = '#CBD5E1';
        cardPotong.style.backgroundColor = '#FFFFFF';
    }
}

function calculateSimulation() {
    const plafon = parseFloat(document.getElementById('nominal_pinjaman').value) || 0;
    const tenor = parseInt(document.getElementById('tenor').value) || 1;

    highlightActiveChip(plafon);

    if (plafon > 0 && tenor > 0) {
        const pokokBulan = Math.floor(plafon / tenor);
        const jasaBulan = Math.round((plafon * 2.0) / 100);
        const totalBulan = pokokBulan + jasaBulan;
        const totalSemua = plafon + (jasaBulan * tenor);

        document.getElementById('simPokok').innerText = formatRupiah(pokokBulan);
        document.getElementById('simJasa').innerText = formatRupiah(jasaBulan);
        document.getElementById('simTotalBulan').innerText = formatRupiah(totalBulan);
        document.getElementById('simTotalSemua').innerText = formatRupiah(totalSemua);

        const dsrBadge = document.getElementById('dsrBadge');
        const dsrBar = document.getElementById('dsrBar');
        const dsrNote = document.getElementById('dsrNote');

        if (currentIncome > 0) {
            const dsr = (totalBulan / currentIncome) * 100;
            const dsrPct = Math.round(dsr);
            const barWidth = Math.min(dsrPct, 100);

            dsrBar.style.width = barWidth + '%';

            if (dsrPct <= 30) {
                dsrBadge.innerText = 'Sangat Aman (' + dsrPct + '%)';
                dsrBadge.style.background = '#D1FAE5';
                dsrBadge.style.color = '#065F46';
                dsrBar.style.backgroundColor = '#059669';
                dsrNote.innerText = 'Angsuran menyerap ' + dsrPct + '% dari pendapatan bulanan. Sangat layak disetujui.';
            } else if (dsrPct <= 40) {
                dsrBadge.innerText = 'Wajar (' + dsrPct + '%)';
                dsrBadge.style.background = '#FEF3C7';
                dsrBadge.style.color = '#92400E';
                dsrBar.style.backgroundColor = '#D97706';
                dsrNote.innerText = 'Angsuran menyerap ' + dsrPct + '% dari pendapatan bulanan (dalam batas aman komite).';
            } else {
                dsrBadge.innerText = 'Beban Tinggi (' + dsrPct + '%)';
                dsrBadge.style.background = '#FEE2E2';
                dsrBadge.style.color = '#991B1B';
                dsrBar.style.backgroundColor = '#DC2626';
                dsrNote.innerText = 'Peringatan: Angsuran melebihi batas 40% penghasilan. Memerlukan evaluasi khusus komite.';
            }
        } else {
            dsrBadge.innerText = 'Menunggu Peminjam';
            dsrBadge.style.background = '#E2E8F0';
            dsrBadge.style.color = '#475569';
            dsrBar.style.width = '0%';
            dsrNote.innerText = 'Pilih guru/karyawan untuk memeriksa kapasitas beban angsuran.';
        }
    }
}

function konfirmasiSimpanPengajuan() {
    const sel = document.getElementById('nasabah_id');
    if (!sel.value) {
        alert('Silakan pilih Guru / Karyawan terlebih dahulu.');
        sel.focus();
        return;
    }

    const opt = sel.options[sel.selectedIndex];
    const nama = opt.dataset.nama || 'Pegawai';
    const plafon = parseFloat(document.getElementById('nominal_pinjaman').value) || 0;
    const tenor = document.getElementById('tenor').value;

    sipintarConfirm({
        title: 'Konfirmasi Pengajuan Pinjaman',
        message: `Simpan permohonan pinjaman atas nama <strong>${nama}</strong> sebesar <strong>${formatRupiah(plafon)}</strong> dengan tenor <strong>${tenor} Bulan</strong>?<br><br><span style="font-size:12px; color:#64748B;">Data akan langsung didaftarkan ke antrean Penilaian Kelayakan sebelum pencairan.</span>`,
        confirmText: 'Ya, Simpan Pengajuan',
        cancelText: 'Periksa Kembali',
        onConfirm: function() {
            document.getElementById('formPinjamanBbm').submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    onPegawaiChanged();
    const initialTenor = parseInt(document.getElementById('tenor').value) || 10;
    highlightTenorPill(initialTenor);
    updateMetodeVisual();
    calculateSimulation();
});
</script>

@endsection
