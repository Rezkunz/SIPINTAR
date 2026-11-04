{{-- Tombol unduh laporan; $routeEkspor = nama route dasar (mis. 'laporan.tabungan'), $paramEkspor = filter aktif --}}
<div class="lap-unduh" role="group" aria-label="Unduh laporan">
    <span class="lap-unduh-label">Unduh</span>
    <a href="{{ route($routeEkspor . '.xlsx', $paramEkspor) }}" class="lap-unduh-btn excel" title="Unduh Excel (.xlsx)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="19"/><line x1="16" y1="13" x2="8" y2="19"/></svg>
        Excel
    </a>
    <a href="{{ route($routeEkspor . '.pdf', $paramEkspor) }}" class="lap-unduh-btn pdf" title="Unduh PDF siap cetak">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="15" x2="16" y2="15"/><line x1="8" y1="18" x2="13" y2="18"/></svg>
        PDF
    </a>
    <a href="{{ route($routeEkspor . '.csv', $paramEkspor) }}" class="lap-unduh-btn csv" title="Unduh CSV">
        CSV
    </a>
</div>
