{{-- ── TOOLBAR FILTER & EKSPOR LAPORAN PINJAMAN ── --}}
@php
    // $filterHalaman: filter khusus tab aktif (mis. cari & kolektibilitas, atau sumber kas),
    // ikut dibawa saat berpindah periode dan saat unduh CSV
    $periodeSebelum = $awalBulan->copy()->subMonth();
    $periodeSesudah = $awalBulan->copy()->addMonth();
    $paramPeriode   = fn ($tgl) => array_filter(array_merge($filterHalaman, ['bulan' => $tgl->month, 'tahun' => $tgl->year]));
@endphp

{{-- Tab jenis laporan pinjaman; periode ikut dibawa saat berpindah tab --}}
<nav class="lap-tabs" aria-label="Jenis Laporan Pinjaman">
    <a href="{{ route('laporan.pinjaman', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="lap-tab {{ $routeLaporan === 'laporan.pinjaman' ? 'active' : '' }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
        Rekap Pinjaman &amp; Kolektibilitas
    </a>
    <a href="{{ route('laporan.pinjaman.kas', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="lap-tab {{ $routeLaporan === 'laporan.pinjaman.kas' ? 'active' : '' }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        Buku Kas Bendahara &amp; Angsuran
    </a>
</nav>

<form method="GET" action="{{ route($routeLaporan) }}" class="lap-toolbar">
    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ $routeLaporan === 'laporan.pinjaman' ? 'Posisi Bulan' : 'Periode Laporan' }}
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

    @if ($routeLaporan === 'laporan.pinjaman')
        <div class="lap-field">
            <span class="lap-field-label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Kolektibilitas
            </span>
            <select name="kolektibilitas" class="lap-select" onchange="this.form.submit()" title="Saring Kolektibilitas" style="height:38px;">
                <option value="">Semua Kolektibilitas</option>
                @foreach (\App\Http\Controllers\Manager\LaporanPinjamanController::KOLEKTIBILITAS as $kode => $k)
                    <option value="{{ $kode }}" {{ ($filterHalaman['kolektibilitas'] ?? null) === $kode ? 'selected' : '' }}>{{ $k['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="lap-field">
            <span class="lap-field-label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Cari Peminjam
            </span>
            <div class="lap-search">
                <input type="search" name="cari" value="{{ $filterHalaman['cari'] ?? '' }}" placeholder="Nama / unit / no pinjaman...">
                <button type="submit" title="Cari">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </div>
        </div>
    @endif

    @if ($routeLaporan === 'laporan.pinjaman.kas')
        <div class="lap-field">
            <span class="lap-field-label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Sumber Kas
            </span>
            <div class="lap-segmented" role="radiogroup" aria-label="Sumber Kas">
                @foreach ($daftarSumber as $kode => $s)
                    <input type="radio" name="sumber" id="sumber-{{ $kode }}" value="{{ $kode }}" {{ ($filterHalaman['sumber'] ?? 'semua') === $kode ? 'checked' : '' }} onchange="this.form.submit()">
                    <label for="sumber-{{ $kode }}">{{ $s['label'] }}</label>
                @endforeach
            </div>
        </div>
    @endif

    @include('manager.laporan.partials.tombol_unduh', ['routeEkspor' => $routeLaporan, 'paramEkspor' => array_filter(array_merge($filterHalaman, ['bulan' => $bulan, 'tahun' => $tahun]))])
</form>
