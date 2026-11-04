@extends('layouts.app')

@section('title', 'Neraca Saldo')
@section('breadcrumb', 'Neraca Saldo')

@section('content')

@php
    $rp = fn ($n) => number_format($n, 0, ',', '.');
    // Angka negatif ditulis dalam kurung sesuai kebiasaan laporan keuangan
    $rpAkuntansi = fn ($n) => $n < 0 ? '(' . number_format(abs($n), 0, ',', '.') . ')' : number_format($n, 0, ',', '.');
    $periodeSebelum = $awalBulan->copy()->subMonth();
    $periodeSesudah = $awalBulan->copy()->addMonth();
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Neraca Saldo</h1>
        <p>Posisi keuangan Bank Mini pada akhir bulan: aktiva (kas dan piutang pinjaman) serta pasiva (kewajiban tabungan nasabah dan ekuitas), ditambah pos manual seperti deposito, peralatan, dan modal.</p>
    </div>
</div>

@if (session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#166534; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if ($errors->any())
    <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:16px 20px; margin-bottom:20px; color:#991B1B; font-size:13.5px;">
        <div style="font-weight:700; margin-bottom:6px;">Pos manual belum tersimpan:</div>
        <ul style="margin:0; padding-left:22px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ── TOOLBAR PERIODE & EKSPOR ── --}}
<form method="GET" action="{{ route('laporan.neraca') }}" class="lap-toolbar">
    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Posisi Bulan
        </span>
        <div class="lap-periode">
            <a href="{{ route('laporan.neraca', ['bulan' => $periodeSebelum->month, 'tahun' => $periodeSebelum->year]) }}" class="lap-periode-nav" title="Bulan sebelumnya ({{ $periodeSebelum->translatedFormat('F Y') }})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <select name="bulan" class="lap-select" onchange="this.form.submit()" title="Pilih Bulan">
                @for ($b = 1; $b <= 12; $b++)
                    <option value="{{ $b }}" {{ $b == $bulan ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(2026, $b, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
            <select name="tahun" class="lap-select" onchange="this.form.submit()" title="Pilih Tahun">
                @for ($y = min(date('Y') - 3, $tahun); $y <= max(date('Y') + 1, $tahun); $y++)
                    <option value="{{ $y }}" {{ $y == $tahun ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <a href="{{ route('laporan.neraca', ['bulan' => $periodeSesudah->month, 'tahun' => $periodeSesudah->year]) }}" class="lap-periode-nav" title="Bulan berikutnya ({{ $periodeSesudah->translatedFormat('F Y') }})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
    </div>

    @include('manager.laporan.partials.tombol_unduh', ['routeEkspor' => 'laporan.neraca', 'paramEkspor' => ['bulan' => $bulan, 'tahun' => $tahun]])
</form>

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Total Aktiva</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($totalAktiva) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Kas dan piutang pinjaman</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Total Kewajiban</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ $rp($totalKewajiban) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Tabungan nasabah dan jasa terutang</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Total Ekuitas</span>
        </div>
        <div class="metric-value" style="color: {{ $totalEkuitas < 0 ? '#DC2626' : 'inherit' }};"><span class="curr-prefix">Rp</span>{{ $rpAkuntansi($totalEkuitas) }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Aktiva dikurangi kewajiban</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Rasio Kas terhadap Tabungan</span>
        </div>
        <div class="metric-value">{{ $rasioKas === null ? '-' : number_format($rasioKas, 2, ',', '.') }}<span style="font-size:16px;">{{ $rasioKas === null ? '' : '%' }}</span></div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Kemampuan kas menutup penarikan seluruh tabungan</div>
    </div>
</div>

{{-- ── NERACA DUA KOLOM ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Neraca (Balance Sheet)</h2>
            <div style="font-size:12px; color:var(--text-muted);">Bank Mini Wiyata Cipta Niaga &bull; per {{ $tanggalPosisi->translatedFormat('d F Y') }}</div>
        </div>
    </div>

    <div class="neraca-grid">
        {{-- AKTIVA --}}
        <div class="neraca-col">
            <table class="financial-table lap-income">
                <tbody>
                    <tr class="lap-income-group"><td colspan="2">Aktiva</td></tr>
                    <tr>
                        <td class="lap-income-item">Kas Tunai <span class="lap-income-note">Cash On Hand &bull; kas tunai Bank Mini</span></td>
                        <td class="lap-income-num">{{ $rpAkuntansi($aktiva['kas_tunai']) }}</td>
                    </tr>
                    <tr>
                        <td class="lap-income-item">Kas Bank <span class="lap-income-note">Cash In Bank &bull; rekening bank bendahara</span></td>
                        <td class="lap-income-num">{{ $rpAkuntansi($aktiva['kas_bank']) }}</td>
                    </tr>
                    <tr>
                        <td class="lap-income-item">Piutang Pinjaman Pegawai <span class="lap-income-note">Account Receivable &bull; sisa pokok pinjaman</span></td>
                        <td class="lap-income-num">{{ $rp($aktiva['piutang']) }}</td>
                    </tr>
                    @foreach ($posManual['aktiva'] as $p)
                        <tr>
                            <td class="lap-income-item">{{ $p->nama_pos }} <span class="neraca-manual-tag">input manual</span><span class="lap-income-note">{{ $p->keterangan ?: 'Berlaku sejak ' . \Carbon\Carbon::parse($p->tgl_berlaku)->format('d/m/Y') }}</span></td>
                            <td class="lap-income-num">{{ $rpAkuntansi($p->nilai) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <table class="financial-table lap-income neraca-total-table">
                <tbody>
                    <tr class="lap-income-total laba">
                        <td>Total Aktiva</td>
                        <td class="lap-income-num">{{ $rpAkuntansi($totalAktiva) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- PASIVA --}}
        <div class="neraca-col">
            <table class="financial-table lap-income">
                <tbody>
                    <tr class="lap-income-group"><td colspan="2">Kewajiban</td></tr>
                    <tr>
                        <td class="lap-income-item">Tabungan Siswa <span class="lap-income-note">Tapel Payable</span></td>
                        <td class="lap-income-num">{{ $rp($kewajiban['tabungan_siswa']) }}</td>
                    </tr>
                    <tr>
                        <td class="lap-income-item">Tabungan Guru / Karyawan <span class="lap-income-note">Takar Payable</span></td>
                        <td class="lap-income-num">{{ $rp($kewajiban['tabungan_guru']) }}</td>
                    </tr>
                    <tr>
                        <td class="lap-income-item">Jasa Tabungan Terutang <span class="lap-income-note">Interest Payable &bull; jasa tahun berjalan yang belum dibukukan ke rekening</span></td>
                        <td class="lap-income-num">{{ $rpAkuntansi($kewajiban['jasa_terutang']) }}</td>
                    </tr>
                    @foreach ($posManual['kewajiban'] as $p)
                        <tr>
                            <td class="lap-income-item">{{ $p->nama_pos }} <span class="neraca-manual-tag">input manual</span><span class="lap-income-note">{{ $p->keterangan ?: 'Berlaku sejak ' . \Carbon\Carbon::parse($p->tgl_berlaku)->format('d/m/Y') }}</span></td>
                            <td class="lap-income-num">{{ $rpAkuntansi($p->nilai) }}</td>
                        </tr>
                    @endforeach
                    <tr class="lap-income-subtotal">
                        <td>Total Kewajiban</td>
                        <td class="lap-income-num">{{ $rpAkuntansi($totalKewajiban) }}</td>
                    </tr>

                    <tr class="lap-income-group"><td colspan="2">Ekuitas</td></tr>
                    @foreach ($posManual['ekuitas'] as $p)
                        <tr>
                            <td class="lap-income-item">{{ $p->nama_pos }} <span class="neraca-manual-tag">input manual</span><span class="lap-income-note">{{ $p->keterangan ?: 'Berlaku sejak ' . \Carbon\Carbon::parse($p->tgl_berlaku)->format('d/m/Y') }}</span></td>
                            <td class="lap-income-num">{{ $rpAkuntansi($p->nilai) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="lap-income-item">Laba (Rugi) Tahun Berjalan <span class="lap-income-note">Retained Earnings &bull; sama dengan Laporan Tahunan s.d. {{ $tanggalPosisi->translatedFormat('F Y') }}</span></td>
                        <td class="lap-income-num">{{ $rpAkuntansi($ekuitas['laba_berjalan']) }}</td>
                    </tr>
                    <tr>
                        <td class="lap-income-item">Selisih Belum Terjelaskan <span class="lap-income-note">Penyeimbang &bull; mendekati nol bila seluruh pos manual (modal, deposito, peralatan, dll) sudah diinput</span></td>
                        <td class="lap-income-num">{{ $rpAkuntansi($ekuitas['modal_selisih']) }}</td>
                    </tr>
                    <tr class="lap-income-subtotal">
                        <td>Total Ekuitas</td>
                        <td class="lap-income-num">{{ $rpAkuntansi($totalEkuitas) }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="financial-table lap-income neraca-total-table">
                <tbody>
                    <tr class="lap-income-total laba">
                        <td>Total Kewajiban &amp; Ekuitas</td>
                        <td class="lap-income-num">{{ $rpAkuntansi($totalKewajiban + $totalEkuitas) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── POS NERACA MANUAL (khusus Manager) ── --}}
@php
    $isUbah = (bool) $posDiubah;
    $nilaiForm = fn ($kolom, $bawaan = '') => old($kolom, $isUbah ? $posDiubah->{$kolom} : $bawaan);
    $labelKelompok = ['aktiva' => 'Aktiva', 'kewajiban' => 'Kewajiban', 'ekuitas' => 'Ekuitas'];
    $saranNamaPos = ['Deposito BPR', 'Deposito BTN', 'Perlengkapan Kantor', 'Peralatan (Harga Perolehan)', 'Akumulasi Penyusutan Peralatan', 'Penyisihan Piutang Tak Tertagih', 'Cadangan Hadiah', 'Modal Bank Mini', 'Modal Hibah', 'Saldo Laba Tahun Lalu'];
@endphp
<div class="content-card" id="pos-manual">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Pos Neraca Manual</h2>
            <div style="font-size:12px; color:var(--text-muted);">Untuk pos yang tidak tercatat otomatis di sistem: deposito, perlengkapan, peralatan &amp; penyusutan, penyisihan piutang, cadangan hadiah, modal, dan hibah. Bila nilainya berubah, tambahkan entri baru dengan tanggal berlaku baru.</div>
        </div>
    </div>

    <form method="POST" action="{{ $isUbah ? route('laporan.neraca.pos.update', $posDiubah->pos_neraca_id) : route('laporan.neraca.pos.store') }}" class="pos-form">
        @csrf
        @if ($isUbah)
            @method('PUT')
        @endif
        <input type="hidden" name="kembali_bulan" value="{{ $bulan }}">
        <input type="hidden" name="kembali_tahun" value="{{ $tahun }}">

        <div class="pos-form-title">
            {{ $isUbah ? 'Ubah Pos: ' . $posDiubah->nama_pos : 'Tambah Pos Manual' }}
        </div>

        <div class="pos-form-grid">
            <div class="lap-field">
                <label class="lap-field-label" for="pos_kelompok">Kelompok</label>
                <select name="kelompok" id="pos_kelompok" class="lap-select" style="height:38px;" required>
                    @foreach ($labelKelompok as $kode => $label)
                        <option value="{{ $kode }}" {{ $nilaiForm('kelompok', 'aktiva') === $kode ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lap-field">
                <label class="lap-field-label" for="pos_nama">Nama Pos</label>
                <input type="text" name="nama_pos" id="pos_nama" class="pos-input" list="saranNamaPos" maxlength="100"
                       value="{{ $nilaiForm('nama_pos') }}" placeholder="mis. Deposito BPR" required>
                <datalist id="saranNamaPos">
                    @foreach (collect($saranNamaPos)->merge($riwayatPos->pluck('nama_pos'))->unique() as $nama)
                        <option value="{{ $nama }}">
                    @endforeach
                </datalist>
            </div>

            <div class="lap-field">
                <label class="lap-field-label" for="pos_nilai">Nilai (Rp)</label>
                <input type="number" name="nilai" id="pos_nilai" class="pos-input" step="1"
                       value="{{ $nilaiForm('nilai') !== '' ? (float) $nilaiForm('nilai') : '' }}" placeholder="mis. 100000000" required>
            </div>

            <div class="lap-field">
                <label class="lap-field-label" for="pos_tgl">Berlaku Sejak</label>
                <input type="date" name="tgl_berlaku" id="pos_tgl" class="pos-input"
                       value="{{ $nilaiForm('tgl_berlaku', $awalBulan->toDateString()) }}" required>
            </div>

            <div class="lap-field pos-form-wide">
                <label class="lap-field-label" for="pos_ket">Keterangan (opsional)</label>
                <input type="text" name="keterangan" id="pos_ket" class="pos-input" maxlength="255"
                       value="{{ $nilaiForm('keterangan') }}" placeholder="mis. Deposito BPR Sahabat Sejati no. 0100.1.05.000056.6">
            </div>
        </div>

        <div class="pos-form-hint">Isi nilai negatif untuk pos pengurang, misalnya Akumulasi Penyusutan atau Penyisihan Piutang.</div>

        <div class="pos-form-actions">
            @if ($isUbah)
                <a href="{{ route('laporan.neraca', ['bulan' => $bulan, 'tahun' => $tahun]) }}#pos-manual" class="btn-ui btn-ui-outline" style="text-decoration:none;">Batal</a>
            @endif
            <button type="submit" class="btn-ui btn-ui-primary" style="font-weight:700;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>{{ $isUbah ? 'Simpan Perubahan' : 'Tambah Pos Manual' }}</span>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="financial-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th style="min-width:100px;">Berlaku Sejak</th>
                    <th style="width:100px;">Kelompok</th>
                    <th style="min-width:220px;">Nama Pos</th>
                    <th style="text-align:right; min-width:130px;">Nilai</th>
                    <th style="min-width:110px;">Diinput Oleh</th>
                    <th style="width:150px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatPos as $p)
                    @php $dipakai = $posManual[$p->kelompok]->contains('pos_neraca_id', $p->pos_neraca_id); @endphp
                    <tr style="{{ $isUbah && $posDiubah->pos_neraca_id === $p->pos_neraca_id ? 'background:var(--brand-accent-light);' : '' }}">
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($p->tgl_berlaku)->format('d/m/Y') }}</td>
                        <td><span class="badge-pill {{ $p->kelompok === 'aktiva' ? 'green' : ($p->kelompok === 'kewajiban' ? 'amber' : 'blue') }}" style="font-size:10.5px; padding:2px 8px;">{{ $labelKelompok[$p->kelompok] }}</span></td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $p->nama_pos }}</div>
                            @if ($p->keterangan)
                                <div style="font-size:11px; color:var(--text-muted);">{{ $p->keterangan }}</div>
                            @endif
                            @if ($dipakai)
                                <div style="font-size:10.5px; font-weight:700; color:var(--brand-primary);">Dipakai di neraca per {{ $tanggalPosisi->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td style="text-align:right; white-space:nowrap; font-weight:700; color:{{ $p->nilai < 0 ? '#DC2626' : 'var(--text-heading)' }};">{{ $rpAkuntansi($p->nilai) }}</td>
                        <td style="font-size:12px; color:var(--text-muted);">{{ $p->nama_user ?? '-' }}</td>
                        <td style="white-space:nowrap; text-align:right;">
                            <a href="{{ route('laporan.neraca', ['bulan' => $bulan, 'tahun' => $tahun, 'edit' => $p->pos_neraca_id]) }}#pos-manual"
                               class="btn-ui btn-ui-sm btn-ui-outline" style="font-size:11.5px; padding:4px 10px; text-decoration:none;">Ubah</a>
                            <form method="POST" action="{{ route('laporan.neraca.pos.destroy', $p->pos_neraca_id) }}" style="display:inline;"
                                  onsubmit="return confirm(this.dataset.konfirmasi);"
                                  data-konfirmasi="Hapus pos {{ $p->nama_pos }} (berlaku {{ \Carbon\Carbon::parse($p->tgl_berlaku)->format('d/m/Y') }}) dari neraca?">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="kembali_bulan" value="{{ $bulan }}">
                                <input type="hidden" name="kembali_tahun" value="{{ $tahun }}">
                                <button type="submit" class="btn-ui btn-ui-sm btn-ui-outline" style="font-size:11.5px; padding:4px 10px; color:#DC2626; border-color:#FECACA;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:28px; text-align:center;">
                                Belum ada pos manual. Tambahkan deposito, peralatan, modal, atau pos lain yang belum tercatat di sistem.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
