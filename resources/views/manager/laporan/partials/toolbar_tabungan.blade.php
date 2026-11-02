{{-- ── TOOLBAR FILTER & EKSPOR ── --}}
@php
    // Buku Kas memuat semua kategori sekaligus, jadi filter kategori & pencarian disembunyikan
    $filterNasabah   = $filterNasabah ?? true;
    $kategori        = $kategori ?? null;
    $cari            = $cari ?? '';
    $periodeSebelum  = $awalBulan->copy()->subMonth();
    $periodeSesudah  = $awalBulan->copy()->addMonth();
    $paramPeriode    = fn ($tgl) => array_filter(['kategori' => $kategori, 'cari' => $cari, 'bulan' => $tgl->month, 'tahun' => $tgl->year]);
    $paramAktif      = $paramPeriode($awalBulan);
@endphp

{{-- Tab jenis laporan; filter yang sedang aktif ikut dibawa saat berpindah tab --}}
<nav class="lap-tabs" aria-label="Jenis Laporan Tabungan">
    <a href="{{ route('laporan.tabungan', $paramAktif) }}" class="lap-tab {{ $routeLaporan === 'laporan.tabungan' ? 'active' : '' }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="9" y1="4" x2="9" y2="22"/><line x1="15" y1="4" x2="15" y2="22"/></svg>
        Rekap Harian
    </a>
    <a href="{{ route('laporan.tabungan.bulanan', $paramAktif) }}" class="lap-tab {{ $routeLaporan === 'laporan.tabungan.bulanan' ? 'active' : '' }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Rekap Tabungan &amp; Jasa Bulanan
    </a>
    <a href="{{ route('laporan.tabungan.kas', $paramAktif) }}" class="lap-tab {{ $routeLaporan === 'laporan.tabungan.kas' ? 'active' : '' }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        Buku Kas Harian
    </a>
</nav>

<form method="GET" action="{{ route($routeLaporan) }}" class="lap-toolbar">
    @if ($filterNasabah)
    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Kategori Tabungan
        </span>
        <div class="lap-segmented" role="radiogroup" aria-label="Kategori Tabungan">
            <input type="radio" name="kategori" id="kategoriSiswa" value="siswa" {{ $kategori === 'siswa' ? 'checked' : '' }} onchange="this.form.submit()">
            <label for="kategoriSiswa">Siswa</label>
            <input type="radio" name="kategori" id="kategoriGuru" value="guru" {{ $kategori === 'guru' ? 'checked' : '' }} onchange="this.form.submit()">
            <label for="kategoriGuru">Guru / Karyawan</label>
        </div>
    </div>
    @endif

    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Periode Laporan
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

    @if ($filterNasabah)
    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Cari Nasabah
        </span>
        <div class="lap-search">
            <input type="search" name="cari" value="{{ $cari }}" placeholder="Nama nasabah / no rekening...">
            <button type="submit" title="Cari">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </div>
    </div>
    @endif

    <a href="{{ route($routeCsv, array_filter(['kategori' => $kategori, 'bulan' => $bulan, 'tahun' => $tahun, 'cari' => $cari])) }}"
       class="lap-btn-export">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Unduh CSV</span>
    </a>
</form>
