@extends('layouts.app')

@section('title', 'Buku Kas Tunai Harian')
@section('breadcrumb', 'Kas Harian')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Buku Kas Tunai Harian (Cash)</h1>
        <p>Pencatatan mutasi kas tunai fisik di loket/brankas Bank Mini, penerimaan angsuran tunai, pencairan dana, dan pengeluaran operasional.</p>
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

<style>
.btn-filter-mode {
    border: none;
    background: transparent;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-filter-mode:hover {
    color: #0D5C46;
}
.btn-filter-mode.active {
    background: #FFFFFF;
    color: #0D5C46;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>

{{-- ── FILTER PERIODE LAPORAN KAS (HARIAN, BULANAN, TAHUNAN, SEMUA) ── --}}
<div class="content-card" style="margin-bottom: 20px; padding: 12px 18px;">
    <form method="GET" action="{{ route('kas.harian') }}" id="formFilterKas" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D5C46" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span style="font-size:12.5px; font-weight:700; color:var(--text-heading);">Filter Periode:</span>
            </div>
            
            {{-- Mode Switcher Pills --}}
            <div style="display:flex; background:#F1F5F9; border-radius:8px; padding:3px; gap:2px;">
                <button type="button" class="btn-filter-mode {{ ($filter['type'] ?? 'harian') === 'harian' ? 'active' : '' }}" onclick="pilihFilterMode('harian')">Per Hari</button>
                <button type="button" class="btn-filter-mode {{ ($filter['type'] ?? '') === 'bulanan' ? 'active' : '' }}" onclick="pilihFilterMode('bulanan')">Per Bulan</button>
                <button type="button" class="btn-filter-mode {{ ($filter['type'] ?? '') === 'tahunan' ? 'active' : '' }}" onclick="pilihFilterMode('tahunan')">Per Tahun</button>
                <button type="button" class="btn-filter-mode {{ ($filter['type'] ?? '') === 'semua' ? 'active' : '' }}" onclick="pilihFilterMode('semua')">Semua</button>
            </div>
            <input type="hidden" name="filter_type" id="filter_type" value="{{ $filter['type'] ?? 'harian' }}">

            {{-- Input Form Harian (Pilih Tanggal) --}}
            <div id="wrapperInputHarian" style="display: {{ ($filter['type'] ?? 'harian') === 'harian' ? 'inline-block' : 'none' }};">
                <input type="date" name="tanggal" value="{{ $filter['tanggal'] ?? date('Y-m-d') }}" class="form-input" style="padding:5px 10px; font-size:12.5px; border-radius:6px; height:32px;" onchange="this.form.submit()">
            </div>

            {{-- Input Form Bulanan (Pilih Bulan & Tahun) --}}
            <div id="wrapperInputBulanan" style="display: {{ ($filter['type'] ?? '') === 'bulanan' ? 'flex' : 'none' }}; gap:6px; align-items:center;">
                <select name="bulan" class="form-select" style="padding:5px 10px; font-size:12.5px; border-radius:6px; height:32px;" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        @php $mStr = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                        <option value="{{ $mStr }}" {{ (int)($filter['bulan'] ?? date('m')) === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(2026, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <select name="tahun" id="selectTahunBulan" class="form-select" style="padding:5px 10px; font-size:12.5px; border-radius:6px; height:32px;" onchange="this.form.submit()">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ (int)($filter['tahun'] ?? date('Y')) === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Input Form Tahunan (Pilih Tahun) --}}
            <div id="wrapperInputTahunan" style="display: {{ ($filter['type'] ?? '') === 'tahunan' ? 'inline-block' : 'none' }};">
                <select name="{{ ($filter['type'] ?? '') === 'tahunan' ? 'tahun' : 'tahun_tahunan' }}" id="selectTahunTahunan" class="form-select" style="padding:5px 10px; font-size:12.5px; border-radius:6px; height:32px;" onchange="syncTahunan(this)">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ (int)($filter['tahun'] ?? date('Y')) === (int)$y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <span class="badge-pill blue" style="font-size:12px; padding:4px 10px; font-weight:700;">
                Periode: {{ $filter['label'] ?? 'Hari Ini' }}
            </span>
            @if(($filter['type'] ?? 'harian') !== 'harian' || ($filter['tanggal'] ?? '') !== date('Y-m-d'))
                <a href="{{ route('kas.harian') }}" class="btn-ui btn-ui-outline btn-ui-sm" style="font-size:11.5px; padding:3px 8px;">
                    Reset Hari Ini
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── 4 FINANCIAL METRICS CARDS ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header">
            <span class="metric-title">Posisi Saldo Kas Tunai (Brankas)</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($kasFisik->saldo, 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Uang tunai fisik siap edar di kasir Bank Mini</div>
    </div>

    <div class="metric-card card-blue">
        <div class="metric-header">
            <span class="metric-title">Kas Masuk ({{ $filter['label'] ?? 'Hari Ini' }})</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($masukHariIni, 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Penerimaan angsuran loket &amp; setoran kas</div>
    </div>

    <div class="metric-card card-amber">
        <div class="metric-header">
            <span class="metric-title">Kas Keluar ({{ $filter['label'] ?? 'Hari Ini' }})</span>
        </div>
        <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($keluarHariIni, 0, ',', '.') }}</div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Pencairan pinjaman tunai &amp; operasional</div>
    </div>

    <div class="metric-card card-purple">
        <div class="metric-header">
            <span class="metric-title">Aktivitas Mutasi</span>
        </div>
        <div class="metric-value">{{ $totalMutasiHariIni }} <span style="font-size:14px; font-weight:600; color:var(--text-muted);">Mutasi</span></div>
        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Periode: {{ $filter['label'] ?? date('d F Y') }}</div>
    </div>
</div>

{{-- ── TABEL JURNAL MUTASI KAS HARIAN (FULL WIDTH 100%) ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Jurnal Mutasi Kas Harian (Fisik)</h2>
            <div style="font-size:12px; color:var(--text-muted);">Audit ledger keluar-masuk uang tunai loket &bull; Periode: <strong>{{ $filter['label'] ?? 'Hari Ini' }}</strong> (maksimal 10 baris per halaman)</div>
        </div>

        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="width:260px; position:relative;">
                <input type="text" class="form-input" placeholder="Cari uraian, petugas, nominal..." 
                       oninput="filterTableRows(this, 'tableMutasiHarian')"
                       style="padding:7px 14px; font-size:13px; border-radius:8px; width:100%;">
            </div>
            
            <button type="button" class="btn-ui btn-ui-primary" onclick="bukaModalKasManual()" style="font-weight:700; padding:8px 16px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Catat Mutasi Manual</span>
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableMutasiHarian">
            <thead>
                <tr>
                    <th style="min-width: 120px;">Tgl Mutasi</th>
                    <th style="min-width: 280px;">Keterangan / Uraian Mutasi</th>
                    <th style="min-width: 140px;">Petugas Kasir</th>
                    <th style="text-align: right; min-width: 160px;">Masuk (Debet)</th>
                    <th style="text-align: right; min-width: 160px;">Keluar (Kredit)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mutasiList as $m)
                    <tr>
                        <td style="font-size:13px; font-weight:600; color:var(--text-heading);">
                            {{ \Carbon\Carbon::parse($m->tgl_mutasi)->format('d/m/Y') }}
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading); font-size:13.5px; line-height:1.4;">
                                {{ $m->uraian }}
                            </div>
                            <div style="margin-top:4px;">
                                @if ($m->pencairan_id)
                                    <span class="badge-pill blue" style="font-size:10.5px; padding:2px 8px;">Pencairan Pinjaman</span>
                                @elseif ($m->angsuran_id)
                                    @if ($m->arah === 'keluar')
                                        <span class="badge-pill red" style="font-size:10.5px; padding:2px 8px;">Koreksi / Reversal</span>
                                    @else
                                        <span class="badge-pill green" style="font-size:10.5px; padding:2px 8px;">Angsuran Masuk</span>
                                    @endif
                                @else
                                    <span class="badge-pill gray" style="font-size:10.5px; padding:2px 8px;">Kas Operasional Manual</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600; font-size:13px; color:var(--text-heading);">{{ $m->nama_user }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">Bendahara Bank Mini</div>
                        </td>
                        <td style="text-align: right; font-weight:800; color:#0D5C46; font-size:14px;">
                            @if ($m->arah === 'masuk')
                                + Rp {{ number_format($m->nominal, 0, ',', '.') }}
                            @else
                                <span style="font-size:12px; font-weight:normal; color:var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight:800; color:#DC2626; font-size:14px;">
                            @if ($m->arah === 'keluar')
                                - Rp {{ number_format($m->nominal, 0, ',', '.') }}
                            @else
                                <span style="font-size:12px; font-weight:normal; color:var(--text-muted);">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                Belum ada transaksi mutasi kas tunai yang tercatat.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($mutasiList->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
            {{ $mutasiList->links() }}
        </div>
    @endif
</div>

{{-- ── POP-UP MODAL: FORM INPUT MUTASI KAS TUNAI MANUAL ── --}}
<div id="modalInputKasTunai" class="sipintar-modal-backdrop" style="display:none; z-index:9999; align-items:center; justify-content:center;" onclick="tutupModalKasManual(event)">
    <div class="sipintar-modal-card" style="max-width:500px; background:#FFFFFF; border-radius:14px; overflow:hidden; border:1px solid #E2E8F0; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" onclick="event.stopPropagation()">
        
        <div style="padding:18px 24px; background:#0D5C46; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#A7F3D0; letter-spacing:0.04em;">Loket Kasir Bank Mini</div>
                <h3 style="margin:2px 0 0 0; font-size:16px; font-weight:800; color:#FFFFFF;">Catat Mutasi Kas Tunai Manual</h3>
            </div>
            <button type="button" onclick="tutupModalKasManual()" style="background:rgba(255,255,255,0.15); border:none; color:#FFFFFF; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:18px;">&times;</button>
        </div>

        <form id="formMutasiKas" method="POST" action="{{ route('kas.harian.simpan') }}" style="padding:22px 24px;">
            @csrf

            {{-- Tanggal Transaksi --}}
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" for="tgl_mutasi" style="display:block; font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:6px;">
                    Tanggal Transaksi <span style="color:#DC2626;">*</span>
                </label>
                <input type="date" name="tgl_mutasi" id="tgl_mutasi" class="form-input" value="{{ date('Y-m-d') }}" required
                       style="width:100%; padding:9px 12px; font-size:13.5px; border:1px solid #CBD5E1; border-radius:8px; box-sizing:border-box;">
            </div>

            {{-- Arus Kas --}}
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="display:block; font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
                    Arus Kas Tunai <span style="color:#DC2626;">*</span>
                </label>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <label id="pill-masuk" style="border:1.5px solid #0D5C46; background:#F0FDF4; border-radius:8px; padding:12px; cursor:pointer; text-align:center; transition:all 0.15s ease;">
                        <input type="radio" name="arah" id="arah_masuk" value="masuk" checked onchange="updateArahVisual()" style="display:none;">
                        <div style="font-weight:800; font-size:13.5px; color:#0D5C46;">+ Kas Masuk (Debet)</div>
                        <div style="font-size:11px; color:#047857; margin-top:2px;">Menambah saldo kasir</div>
                    </label>

                    <label id="pill-keluar" style="border:1.5px solid #CBD5E1; background:#FFFFFF; border-radius:8px; padding:12px; cursor:pointer; text-align:center; transition:all 0.15s ease;">
                        <input type="radio" name="arah" id="arah_keluar" value="keluar" onchange="updateArahVisual()" style="display:none;">
                        <div style="font-weight:800; font-size:13.5px; color:#DC2626;">- Kas Keluar (Kredit)</div>
                        <div style="font-size:11px; color:#991B1B; margin-top:2px;">Mengurangi saldo kasir</div>
                    </label>
                </div>
            </div>

            {{-- Nominal --}}
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" for="nominal" style="display:block; font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:6px;">
                    Nominal Uang (Rp) <span style="color:#DC2626;">*</span>
                </label>
                <div style="position:relative;">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-weight:800; font-size:14px; color:#64748B;">Rp</span>
                    <input type="number" name="nominal" id="nominal" class="form-input" min="1000" step="500" placeholder="Contoh: 250000" required
                           style="width:100%; padding:9px 12px 9px 40px; font-size:15px; font-weight:700; border:1px solid #CBD5E1; border-radius:8px; box-sizing:border-box;">
                </div>
                {{-- Quick Chips --}}
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                    <button type="button" class="btn-ui btn-ui-sm btn-ui-outline" onclick="setNominalKas(50000)" style="font-size:11px; padding:3px 8px;">50 Rb</button>
                    <button type="button" class="btn-ui btn-ui-sm btn-ui-outline" onclick="setNominalKas(100000)" style="font-size:11px; padding:3px 8px;">100 Rb</button>
                    <button type="button" class="btn-ui btn-ui-sm btn-ui-outline" onclick="setNominalKas(250000)" style="font-size:11px; padding:3px 8px;">250 Rb</button>
                    <button type="button" class="btn-ui btn-ui-sm btn-ui-outline" onclick="setNominalKas(500000)" style="font-size:11px; padding:3px 8px;">500 Rb</button>
                    <button type="button" class="btn-ui btn-ui-sm btn-ui-outline" onclick="setNominalKas(1000000)" style="font-size:11px; padding:3px 8px;">1 Jt</button>
                </div>
            </div>

            {{-- Keterangan / Uraian --}}
            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" for="uraian" style="display:block; font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:6px;">
                    Keterangan / Uraian Transaksi <span style="color:#DC2626;">*</span>
                </label>
                <textarea name="uraian" id="uraian" class="form-textarea" rows="2" required
                          placeholder="Contoh: Beli alat tulis kantor & buku kasir Bank Mini"
                          style="width:100%; padding:9px 12px; font-size:13px; border:1px solid #CBD5E1; border-radius:8px; box-sizing:border-box;"></textarea>
            </div>

            {{-- Tombol Aksi --}}
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-ui btn-ui-outline" onclick="tutupModalKasManual()">Batal</button>
                <button type="button" class="btn-ui btn-ui-primary" onclick="konfirmasiSimpanKas()" style="font-weight:700;">
                    Simpan Mutasi Kas
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function pilihFilterMode(mode) {
    document.getElementById('filter_type').value = mode;
    const selectTahunBulan = document.getElementById('selectTahunBulan');
    const selectTahunTahunan = document.getElementById('selectTahunTahunan');

    if (mode === 'tahunan') {
        if (selectTahunBulan) selectTahunBulan.disabled = true;
        if (selectTahunTahunan) selectTahunTahunan.name = 'tahun';
    } else {
        if (selectTahunBulan) selectTahunBulan.disabled = false;
        if (selectTahunTahunan) selectTahunTahunan.name = 'tahun_tahunan';
    }

    document.getElementById('formFilterKas').submit();
}

function syncTahunan(el) {
    const selectTahunBulan = document.getElementById('selectTahunBulan');
    if (selectTahunBulan) selectTahunBulan.disabled = true;
    el.name = 'tahun';
    el.form.submit();
}

function bukaModalKasManual() {
    const modal = document.getElementById('modalInputKasTunai');
    modal.style.display = 'flex';
    modal.classList.add('open');
}

function tutupModalKasManual(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('modalInputKasTunai');
    modal.style.display = 'none';
    modal.classList.remove('open');
}

function updateArahVisual() {
    const isMasuk = document.getElementById('arah_masuk').checked;
    const pMasuk = document.getElementById('pill-masuk');
    const pKeluar = document.getElementById('pill-keluar');

    if (isMasuk) {
        pMasuk.style.borderColor = '#0D5C46';
        pMasuk.style.background = '#F0FDF4';
        pKeluar.style.borderColor = '#CBD5E1';
        pKeluar.style.background = '#FFFFFF';
    } else {
        pKeluar.style.borderColor = '#DC2626';
        pKeluar.style.background = '#FEF2F2';
        pMasuk.style.borderColor = '#CBD5E1';
        pMasuk.style.background = '#FFFFFF';
    }
}

function setNominalKas(val) {
    document.getElementById('nominal').value = val;
}

function konfirmasiSimpanKas() {
    const nominal = parseFloat(document.getElementById('nominal').value) || 0;
    const uraian = document.getElementById('uraian').value.trim();
    const arah = document.querySelector('input[name="arah"]:checked').value;

    if (nominal < 1000) {
        alert('Nominal minimal adalah Rp 1.000');
        return;
    }
    if (uraian.length < 3) {
        alert('Keterangan transaksi wajib diisi.');
        return;
    }

    const labelArah = arah === 'masuk' ? 'Kas Masuk (+)' : 'Kas Keluar (-)';
    const formattedRp = 'Rp ' + Math.round(nominal).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    sipintarConfirm({
        title: `Simpan Transaksi ${labelArah}?`,
        message: `Catat mutasi kas sebesar ${formattedRp} untuk: "${uraian}"? Saldo kas tunai akan diperbarui otomatis.`,
        confirmText: 'Ya, Simpan',
        cancelText: 'Periksa Kembali',
        confirmColor: arah === 'masuk' ? '#0D5C46' : '#DC2626',
        onConfirm: () => {
            document.getElementById('formMutasiKas').submit();
        }
    });
}
</script>

@endsection
