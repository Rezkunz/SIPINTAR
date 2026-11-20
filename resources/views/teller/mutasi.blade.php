@extends('layouts.app')

@section('title', 'Log & Mutasi Rekening')
@section('breadcrumb', 'Log & Mutasi Rekening')

@push('styles')
<style>
    .rek-search-wrap { position: relative; }
    .rek-input-box { position: relative; display: flex; align-items: center; }
    .rek-input-box .form-input { padding-right: 36px; cursor: pointer; }
    .rek-chevron-btn {
        position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
        background: none; border: none; padding: 4px 6px; cursor: pointer;
        color: #64748B; display: flex; align-items: center; justify-content: center;
    }
    .rek-dropdown {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 40;
        background: #fff; border: 1px solid var(--border-color, #CBD5E1);
        border-radius: 10px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        max-height: 260px; overflow-y: auto; margin-top: 4px; display: none;
    }
    .rek-dropdown.open { display: block; }
    .rek-option {
        padding: 10px 14px; font-size: 13px; cursor: pointer;
        border-bottom: 1px solid #F1F5F9; color: #0F172A;
    }
    .rek-option:last-child { border-bottom: none; }
    .rek-option:hover, .rek-option.active { background: #ECFDF5; color: #065F46; }
    .rek-empty { padding: 12px 14px; font-size: 13px; color: var(--text-muted); }

    .tab-bar { display: flex; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid var(--border-color, #E2E8F0); padding-bottom: 10px; }
    .tab-pill {
        padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
        text-decoration: none; color: var(--text-muted); background: #F8FAFC; border: 1px solid #E2E8F0;
    }
    .tab-pill.active { background: #0D5C46; color: #fff; border-color: #0D5C46; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Log &amp; Mutasi Rekening</h1>
    <p class="page-subtitle">Pantau mutasi per rekening, log transaksi loket, serta cetak laporan mutasi dan buku tabungan.</p>
</div>

<div class="tab-bar">
    <a href="{{ route('tabungan.mutasi', array_filter(['tab' => 'mutasi', 'no_rek' => request('no_rek')])) }}"
       class="tab-pill {{ $tab === 'mutasi' ? 'active' : '' }}">Mutasi Rekening</a>
    <a href="{{ route('tabungan.mutasi', ['tab' => 'harian', 'log_periode' => $logPeriode, 'tanggal' => $tanggal, 'bulan' => $bulan, 'tahun' => $tahun]) }}"
       class="tab-pill {{ $tab === 'harian' ? 'active' : '' }}">Log Transaksi</a>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; margin-bottom: 20px;">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

@if ($tab === 'harian')
    <div class="content-card" style="margin-bottom:20px;">
        <div class="card-body-content">
            <form method="GET" action="{{ route('tabungan.mutasi') }}" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
                <input type="hidden" name="tab" value="harian">
                <div style="min-width: 150px;">
                    <label for="logPeriodeSelect">Filter Periode</label>
                    <select id="logPeriodeSelect" name="log_periode" class="form-input">
                        <option value="harian" @selected($logPeriode === 'harian')>Harian</option>
                        <option value="bulanan" @selected($logPeriode === 'bulanan')>Bulanan</option>
                        <option value="tahunan" @selected($logPeriode === 'tahunan')>Tahunan</option>
                    </select>
                </div>
                <div id="wrapLogTanggal" style="min-width: 160px; display: {{ $logPeriode === 'harian' ? 'block' : 'none' }};">
                    <label for="tanggal_harian">Tanggal</label>
                    <input id="tanggal_harian" class="form-input" type="date" name="tanggal" value="{{ $tanggal }}">
                </div>
                <div id="wrapLogBulan" style="min-width: 160px; display: {{ $logPeriode === 'bulanan' ? 'block' : 'none' }};">
                    <label for="bulan_log">Bulan</label>
                    <input id="bulan_log" class="form-input" type="month" name="bulan" value="{{ $bulan }}">
                </div>
                <div id="wrapLogTahun" style="min-width: 120px; display: {{ $logPeriode === 'tahunan' ? 'block' : 'none' }};">
                    <label for="tahun_log">Tahun</label>
                    <input id="tahun_log" class="form-input" type="number" min="2020" max="2099" name="tahun" value="{{ $tahun }}">
                </div>
                <button class="btn-ui btn-ui-primary" type="submit">Tampilkan</button>
                <a class="btn-ui btn-ui-outline" target="_blank" href="{{ route('tabungan.log-transaksi.cetak', ['log_periode' => $logPeriode, 'tanggal' => $tanggal, 'bulan' => $bulan, 'tahun' => $tahun]) }}">Cetak Log</a>
            </form>
        </div>
    </div>

    @if ($ringkasanHarian)
        <div class="metric-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom:20px;">
            <div class="metric-card card-blue"><div class="metric-title">Jumlah Transaksi</div><div class="metric-value">{{ $ringkasanHarian['total_transaksi'] }}</div></div>
            <div class="metric-card card-emerald"><div class="metric-title">Total Kredit</div><div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasanHarian['total_kredit'], 0, ',', '.') }}</div></div>
            <div class="metric-card card-amber"><div class="metric-title">Total Debit</div><div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasanHarian['total_debit'], 0, ',', '.') }}</div></div>
            <div class="metric-card card-indigo"><div class="metric-title">Total Bunga</div><div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasanHarian['total_bunga'], 0, ',', '.') }}</div></div>
        </div>
    @endif

    <div class="content-card">
        <div class="card-header-bar">
            <h2 class="card-heading">Log Transaksi &mdash; {{ $logPeriodeLabel }}</h2>
            <span class="badge-pill blue">Koreksi: {{ $ringkasanHarian['total_koreksi'] ?? 0 }}</span>
        </div>
        <div class="table-responsive">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Rekening / Nasabah</th>
                        <th>Jenis</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Kredit</th>
                        <th style="text-align:right;">Saldo Akhir</th>
                        <th>Pencatat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksiHarian as $item)
                        <tr>
                            <td>{{ $item->tgl_transaksi }}</td>
                            <td><strong>{{ $item->no_rek }}</strong><div style="font-size:11px;color:var(--text-muted);">{{ $item->nama_nasabah }}</div></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $item->nama_jenis)) }}</td>
                            <td style="text-align:right;">Rp {{ number_format($item->debit, 0, ',', '.') }}</td>
                            <td style="text-align:right;">Rp {{ number_format($item->kredit, 0, ',', '.') }}</td>
                            <td style="text-align:right;">Rp {{ number_format($item->saldo_akhir, 0, ',', '.') }}</td>
                            <td>{{ $item->nama_pencatat ?? '-' }}</td>
                            <td>
                                @if ($item->koreksi_id)
                                    <span class="badge-pill amber">Dikoreksi</span>
                                @else
                                    <span class="badge-pill green">Normal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-placeholder">Tidak ada transaksi pada periode tersebut.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transaksiHarian instanceof \Illuminate\Pagination\LengthAwarePaginator && $transaksiHarian->hasPages())
            <div style="padding:16px 20px;">{{ $transaksiHarian->links() }}</div>
        @endif
    </div>
@else
    <div class="content-card" style="overflow: visible;">
        <div class="card-body-content">
            <form method="GET" action="{{ route('tabungan.mutasi') }}" id="filterMutasiForm" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="mutasi">

                <div style="flex: 2; min-width: 280px;" class="rek-search-wrap">
                    <label for="rekSearchInput">Rekening Nasabah</label>
                    <input type="hidden" name="no_rek" id="no_rek_value" value="{{ $rekening->no_rek ?? request('no_rek') }}">
                    <div class="rek-input-box">
                        <input id="rekSearchInput"
                               type="text"
                               class="form-input"
                               autocomplete="off"
                               placeholder="Pilih atau ketik nomor rekening / nama nasabah..."
                               value="{{ $selectedOption['label'] ?? request('no_rek') }}"
                               required>
                        <button type="button" id="rekChevronBtn" class="rek-chevron-btn" aria-label="Buka daftar rekening">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                    </div>
                    <div id="rekDropdown" class="rek-dropdown"></div>
                </div>

                <div style="min-width: 150px;">
                    <label for="periodeSelect">Filter Periode</label>
                    <select id="periodeSelect" name="periode" class="form-input">
                        <option value="semua" @selected($periode === 'semua')>Semua Periode</option>
                        <option value="harian" @selected($periode === 'harian')>Harian</option>
                        <option value="bulanan" @selected($periode === 'bulanan')>Bulanan</option>
                        <option value="tahunan" @selected($periode === 'tahunan')>Tahunan</option>
                    </select>
                </div>

                <div id="wrapTanggal" style="min-width: 160px; display: {{ $periode === 'harian' ? 'block' : 'none' }};">
                    <label for="filterTanggal">Tanggal</label>
                    <input id="filterTanggal" type="date" name="tanggal" class="form-input" value="{{ $tanggal }}">
                </div>

                <div id="wrapBulan" style="min-width: 160px; display: {{ $periode === 'bulanan' ? 'block' : 'none' }};">
                    <label for="filterBulan">Bulan</label>
                    <input id="filterBulan" type="month" name="bulan" class="form-input" value="{{ $bulan }}">
                </div>

                <div id="wrapTahun" style="min-width: 120px; display: {{ $periode === 'tahunan' ? 'block' : 'none' }};">
                    <label for="filterTahun">Tahun</label>
                    <input id="filterTahun" type="number" min="2020" max="2099" name="tahun" class="form-input" value="{{ $tahun }}">
                </div>

                <button class="btn-ui btn-ui-primary" type="submit">Tampilkan</button>
            </form>

            @if ($rekening)
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:20px; padding-top:16px; border-top:1px solid #E2E8F0;">
                    <div style="flex:1; min-width: 220px;">
                        <strong>{{ $rekening->nasabah->nama_nasabah ?? '-' }}</strong>
                        <div style="font-size:12px;color:var(--text-muted);">
                            {{ $rekening->no_rek }} &bull; {{ $rekening->nasabah?->jenis_nasabah === 'pegawai' ? 'Pegawai' : 'Siswa' }}{{ $rekening->nasabah?->kelas_unit ? ' / ' . $rekening->nasabah->kelas_unit : '' }}
                        </div>
                    </div>
                    <a class="btn-ui btn-ui-outline btn-ui-sm" target="_blank"
                       href="{{ route('tabungan.mutasi.cetak', ['no_rek' => $rekening->no_rek, 'periode' => $periode, 'tanggal' => $tanggal, 'bulan' => $bulan, 'tahun' => $tahun]) }}">Cetak Laporan Mutasi</a>
                    <a class="btn-ui btn-ui-outline btn-ui-sm" target="_blank"
                       href="{{ route('tabungan.cetak.buku-tabungan', ['no_rek' => $rekening->no_rek]) }}">Cetak Buku Tabungan</a>
                </div>
            @elseif (request('no_rek'))
                <div style="margin-top:16px; color:#991B1B; font-size:13px;">Rekening aktif dengan nomor <strong>{{ request('no_rek') }}</strong> tidak ditemukan.</div>
            @endif
        </div>
    </div>

    @if ($rekening)
        <div class="metric-grid" style="grid-template-columns: repeat(4, 1fr); margin-top:20px;">
            <div class="metric-card card-emerald">
                <div class="metric-title">Total Masuk</div>
                <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasanMutasi['total_masuk'], 0, ',', '.') }}</div>
            </div>
            <div class="metric-card card-amber">
                <div class="metric-title">Total Keluar</div>
                <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasanMutasi['total_keluar'], 0, ',', '.') }}</div>
            </div>
            <div class="metric-card card-blue">
                <div class="metric-title">Jumlah Mutasi</div>
                <div class="metric-value">{{ $ringkasanMutasi['jumlah_mutasi'] }}</div>
            </div>
            <div class="metric-card card-indigo">
                <div class="metric-title">Saldo Akhir</div>
                <div class="metric-value"><span class="curr-prefix">Rp</span>{{ number_format($ringkasanMutasi['saldo_akhir'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="content-card" style="margin-top:20px;">
            <div class="card-header-bar">
                <h2 class="card-heading">Riwayat Mutasi</h2>
            </div>
            <div class="table-responsive">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th style="text-align:right;">Debit</th>
                            <th style="text-align:right;">Kredit</th>
                            <th style="text-align:right;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutasi as $transaksi)
                            <tr>
                                <td>{{ $transaksi->tgl_transaksi }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $transaksi->nama_jenis)) }}</td>
                                <td style="text-align:right;">Rp {{ number_format($transaksi->debit, 0, ',', '.') }}</td>
                                <td style="text-align:right;">Rp {{ number_format($transaksi->kredit, 0, ',', '.') }}</td>
                                <td style="text-align:right;">Rp {{ number_format($transaksi->saldo_akhir, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-placeholder">Belum ada transaksi pada periode ini.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($mutasi instanceof \Illuminate\Pagination\LengthAwarePaginator && $mutasi->hasPages())
                <div style="padding: 16px 20px;">{{ $mutasi->links() }}</div>
            @endif
        </div>
    @endif
@endif
@endsection

@section('scripts')
<script>
(function () {
    // 1. Filter periode toggle (Mutasi & Log Transaksi)
    const periodeSelect = document.getElementById('periodeSelect');
    if (periodeSelect) {
        const togglePeriodeFields = () => {
            const val = periodeSelect.value;
            document.getElementById('wrapTanggal').style.display = val === 'harian' ? 'block' : 'none';
            document.getElementById('wrapBulan').style.display = val === 'bulanan' ? 'block' : 'none';
            document.getElementById('wrapTahun').style.display = val === 'tahunan' ? 'block' : 'none';
        };
        periodeSelect.addEventListener('change', togglePeriodeFields);
    }

    const logPeriodeSelect = document.getElementById('logPeriodeSelect');
    if (logPeriodeSelect) {
        const toggleLogPeriodeFields = () => {
            const val = logPeriodeSelect.value;
            document.getElementById('wrapLogTanggal').style.display = val === 'harian' ? 'block' : 'none';
            document.getElementById('wrapLogBulan').style.display = val === 'bulanan' ? 'block' : 'none';
            document.getElementById('wrapLogTahun').style.display = val === 'tahunan' ? 'block' : 'none';
        };
        logPeriodeSelect.addEventListener('change', toggleLogPeriodeFields);
    }

    // 2. Searchable Rekening Dropdown
    const filterMutasiForm = document.getElementById('filterMutasiForm');
    const searchInput = document.getElementById('rekSearchInput');
    const chevronBtn = document.getElementById('rekChevronBtn');
    const hiddenInput = document.getElementById('no_rek_value');
    const dropdown = document.getElementById('rekDropdown');
    if (searchInput && hiddenInput && dropdown) {
        const useServerSearch = @json($useServerSearch);
        const localOptions = @json($rekeningOptions);
        const searchUrl = @json(route('tabungan.rekening.cari'));
        let debounceTimer = null;

        const renderOptions = (items) => {
            dropdown.innerHTML = '';
            if (!items || items.length === 0) {
                dropdown.innerHTML = '<div class="rek-empty">Rekening tidak ditemukan.</div>';
            } else {
                const currentNoRek = hiddenInput.value;
                items.forEach((item) => {
                    const div = document.createElement('div');
                    div.className = 'rek-option' + (item.no_rek === currentNoRek ? ' active' : '');
                    div.textContent = item.label;
                    div.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        hiddenInput.value = item.no_rek;
                        searchInput.value = item.label;
                        dropdown.classList.remove('open');
                        filterMutasiForm?.submit();
                    });
                    dropdown.appendChild(div);
                });
            }
            dropdown.classList.add('open');
        };

        const doSearch = (q, showAll = false) => {
            const term = showAll ? '' : q.trim().toLowerCase();
            if (!useServerSearch) {
                const filtered = term === ''
                    ? localOptions
                    : localOptions.filter((o) =>
                        o.no_rek.toLowerCase().includes(term) ||
                        o.nama_nasabah.toLowerCase().includes(term) ||
                        o.label.toLowerCase().includes(term)
                    );
                renderOptions(filtered);
                return;
            }

            fetch(`${searchUrl}?q=${encodeURIComponent(showAll ? '' : q.trim())}`)
                .then((res) => res.json())
                .then((data) => renderOptions(data))
                .catch(() => renderOptions([]));
        };

        searchInput.addEventListener('focus', () => doSearch('', true));
        searchInput.addEventListener('click', () => doSearch('', true));
        chevronBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            if (dropdown.classList.contains('open')) {
                dropdown.classList.remove('open');
            } else {
                searchInput.focus();
                doSearch('', true);
            }
        });
        searchInput.addEventListener('input', () => {
            hiddenInput.value = searchInput.value.split(' - ')[0].trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => doSearch(searchInput.value, false), useServerSearch ? 220 : 80);
        });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.rek-search-wrap')) {
                dropdown.classList.remove('open');
            }
        });
    }
})();
</script>
@endsection
