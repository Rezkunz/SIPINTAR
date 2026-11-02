{{-- ── TOOLBAR FILTER & EKSPOR LAPORAN PINJAMAN ── --}}
@php
    $periodeSebelum = $awalBulan->copy()->subMonth();
    $periodeSesudah = $awalBulan->copy()->addMonth();
    $paramPeriode   = fn ($tgl) => array_filter(['cari' => $cari, 'kolektibilitas' => $filterKolek, 'bulan' => $tgl->month, 'tahun' => $tgl->year]);
@endphp

{{-- Tab jenis laporan pinjaman --}}
<nav class="lap-tabs" aria-label="Jenis Laporan Pinjaman">
    <a href="{{ route('laporan.pinjaman', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="lap-tab {{ $routeLaporan === 'laporan.pinjaman' ? 'active' : '' }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
        Rekap Pinjaman &amp; Kolektibilitas
    </a>
</nav>

<form method="GET" action="{{ route($routeLaporan) }}" class="lap-toolbar">
    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Posisi Bulan
        </span>
        <div class="lap-periode">
            <a href="{{ route($routeLaporan, $paramPeriode($periodeSebelum)) }}" class="lap-periode-nav" title="Bulan sebelumnya ({{ $periodeSebelum->translatedFormat('F Y') }})">
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
            <a href="{{ route($routeLaporan, $paramPeriode($periodeSesudah)) }}" class="lap-periode-nav" title="Bulan berikutnya ({{ $periodeSesudah->translatedFormat('F Y') }})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
    </div>

    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Kolektibilitas
        </span>
        <select name="kolektibilitas" class="lap-select" onchange="this.form.submit()" title="Saring Kolektibilitas" style="height:38px;">
            <option value="">Semua Kolektibilitas</option>
            @foreach (\App\Http\Controllers\Manager\LaporanPinjamanController::KOLEKTIBILITAS as $kode => $k)
                <option value="{{ $kode }}" {{ $filterKolek === $kode ? 'selected' : '' }}>{{ $k['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Cari Peminjam
        </span>
        <div class="lap-search">
            <input type="search" name="cari" value="{{ $cari }}" placeholder="Nama / unit / no pinjaman...">
            <button type="submit" title="Cari">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </div>
    </div>

    <a href="{{ route($routeCsv, array_filter(['bulan' => $bulan, 'tahun' => $tahun, 'cari' => $cari, 'kolektibilitas' => $filterKolek])) }}"
       class="lap-btn-export">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Unduh CSV</span>
    </a>
</form>
