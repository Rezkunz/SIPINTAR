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
            @include('manager.laporan.partials.tombol_unduh', ['routeEkspor' => $routeLaporan, 'paramEkspor' => array_filter(array_merge($filterHalaman, ['bulan' => $bulan, 'tahun' => $tahun]))])
</form>
