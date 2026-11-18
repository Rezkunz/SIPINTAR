@extends('layouts.app')

@section('title', 'Koreksi Transaksi')
@section('breadcrumb', 'Koreksi Transaksi')

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

    .koreksi-modal-backdrop {
        position: fixed; inset: 0; z-index: 100; background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(3px); display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .koreksi-modal-backdrop.open { display: flex; }
    .koreksi-modal-card {
        background: #fff; border-radius: 14px; width: 100%; max-width: 480px;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2); overflow: hidden;
    }
    .koreksi-modal-header {
        padding: 16px 20px; border-bottom: 1px solid #E2E8F0;
        display: flex; align-items: center; justify-content: space-between;
    }
    .koreksi-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
    .koreksi-modal-footer {
        padding: 14px 20px; border-top: 1px solid #E2E8F0; background: #F8FAFC;
        display: flex; justify-content: flex-end; gap: 10px;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Koreksi Transaksi</h1>
    <p class="page-subtitle">Pilih rekening nasabah untuk mengoreksi jenis atau nominal transaksi secara aman melalui transaksi pembalik dan penyesuaian.</p>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; margin-bottom: 20px;">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

{{-- Bagian 1: Filter Pemilihan Rekening & Tanggal --}}
<div class="content-card" style="overflow: visible;">
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.koreksi') }}" id="filterKoreksiForm" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
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

            <div style="min-width: 170px;">
                <label for="filterTanggal">Tanggal Transaksi (Opsional)</label>
                <input id="filterTanggal" type="date" name="tanggal" class="form-input" value="{{ $tanggal }}">
            </div>

            <button class="btn-ui btn-ui-primary" type="submit">Tampilkan</button>
            @if ($rekening || $tanggal !== '')
                <a class="btn-ui btn-ui-outline" href="{{ route('tabungan.koreksi') }}">Reset</a>
            @endif
        </form>

        @if ($rekening)
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:20px; padding-top:16px; border-top:1px solid #E2E8F0;">
                <div>
                    <div style="font-size:15px; font-weight:700; color:var(--text-heading);">{{ $rekening->nasabah->nama_nasabah ?? '-' }}</div>
                    <div style="font-size:12.5px; color:var(--text-muted);">
                        No. Rekening: <strong>{{ $rekening->no_rek }}</strong> &bull;
                        {{ $rekening->nasabah?->jenis_nasabah === 'pegawai' ? 'Pegawai' : 'Siswa' }}{{ $rekening->nasabah?->kelas_unit ? ' / ' . $rekening->nasabah->kelas_unit : '' }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Saldo Aktif Saat Ini</div>
                    <div style="font-size:18px; font-weight:800; color:#0D5C46;">Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</div>
                </div>
            </div>
        @elseif (request('no_rek'))
            <div style="margin-top:16px; color:#991B1B; font-size:13px;">Rekening aktif dengan nomor <strong>{{ request('no_rek') }}</strong> tidak ditemukan.</div>
        @else
            <div style="margin-top:16px; padding:10px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; color:var(--text-muted); font-size:13px;">
                Silakan pilih rekening nasabah terlebih dahulu untuk menampilkan daftar transaksi yang dapat dikoreksi.
            </div>
        @endif
    </div>
</div>

{{-- Bagian 2: Daftar Transaksi & Aksi Koreksi --}}
@if ($rekening)
    <div class="content-card" style="margin-top:20px;">
        <div class="card-header-bar">
            <h2 class="card-heading">Daftar Transaksi Rekening {{ $rekening->no_rek }}</h2>
            @if ($tanggal !== '')
                <span class="badge-pill blue">Tanggal: {{ $tanggal }}</span>
            @endif
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
                        <th style="text-align:center;">Status Koreksi</th>
                        <th style="text-align:center; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksiList as $transaksi)
                        @php
                            $nominalAsal = (float) ($transaksi->debit > 0 ? $transaksi->debit : $transaksi->kredit);
                        @endphp
                        <tr>
                            <td>{{ $transaksi->tgl_transaksi }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $transaksi->nama_jenis)) }}</td>
                            <td style="text-align:right;">Rp {{ number_format($transaksi->debit, 0, ',', '.') }}</td>
                            <td style="text-align:right;">Rp {{ number_format($transaksi->kredit, 0, ',', '.') }}</td>
                            <td style="text-align:right;">Rp {{ number_format($transaksi->saldo_akhir, 0, ',', '.') }}</td>
                            <td style="text-align:center;">
                                @if ($transaksi->koreksi_id)
                                    <span class="badge-pill amber">Sudah Dikoreksi</span>
                                @else
                                    <span class="badge-pill green">Normal</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if ($transaksi->koreksi_id)
                                    <button type="button" class="btn-ui btn-ui-outline btn-ui-sm" disabled style="opacity:0.5; cursor:not-allowed;">Sudah Dikoreksi</button>
                                @else
                                    <button type="button"
                                            class="btn-ui btn-ui-outline btn-ui-sm btn-open-koreksi"
                                            data-action="{{ route('tabungan.koreksi.process', $transaksi->transaksi_id) }}"
                                            data-tanggal="{{ $transaksi->tgl_transaksi }}"
                                            data-jenis-id="{{ $transaksi->jenis_transaksi_id }}"
                                            data-jenis-label="{{ ucfirst(str_replace('_', ' ', $transaksi->nama_jenis)) }}"
                                            data-nominal="{{ (int) round($nominalAsal) }}">
                                        Koreksi
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-placeholder">Tidak ada transaksi ditemukan.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transaksiList instanceof \Illuminate\Pagination\LengthAwarePaginator && $transaksiList->hasPages())
            <div style="padding: 16px 20px;">{{ $transaksiList->links() }}</div>
        @endif
    </div>
@endif

{{-- Bagian 3: Riwayat Audit Koreksi Transaksi --}}
<div class="content-card" style="margin-top:20px;">
    <div class="card-header-bar">
        <h2 class="card-heading">Riwayat Koreksi Transaksi {{ $rekening ? '(' . $rekening->no_rek . ')' : '(Terbaru)' }}</h2>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Waktu Koreksi</th>
                    <th>No. Rekening / Nasabah</th>
                    <th>Detail Perubahan</th>
                    <th>Alasan Koreksi</th>
                    <th>Teller Pelaksana</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatKoreksi as $log)
                    <tr>
                        <td>{{ $log->waktu_koreksi }}</td>
                        <td>
                            <strong>{{ $log->no_rek }}</strong>
                            <div style="font-size:11.5px; color:var(--text-muted);">{{ $log->nama_nasabah }}</div>
                        </td>
                        <td>
                            <span style="color:#991B1B;">{{ $log->sebelum }}</span>
                            <span style="margin:0 4px;">&rarr;</span>
                            <strong style="color:#065F46;">{{ $log->sesudah }}</strong>
                        </td>
                        <td>{{ $log->alasan }}</td>
                        <td>{{ $log->nama_teller }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-placeholder">Belum ada riwayat koreksi transaksi.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Koreksi Transaksi --}}
<div id="koreksiModal" class="koreksi-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="koreksiModalTitle">
    <div class="koreksi-modal-card">
        <div class="koreksi-modal-header">
            <h3 id="koreksiModalTitle" style="margin:0; font-size:16px; font-weight:700;">Koreksi Transaksi</h3>
            <button type="button" id="koreksiModalClose" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748B;">&times;</button>
        </div>
        <form id="koreksiModalForm" method="POST" action="">
            @csrf
            <input type="hidden" name="no_rek" value="{{ $rekening->no_rek ?? '' }}">
            <input type="hidden" name="nominal" id="koreksiNominalValue" value="">

            <div class="koreksi-modal-body">
                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:10px 12px; font-size:12.5px; color:#334155;">
                    Transaksi asal: <strong id="koreksiInfoAsal">-</strong>
                </div>

                <div>
                    <label for="koreksiJenis">Jenis Transaksi Baru</label>
                    <select id="koreksiJenis" name="jenis_transaksi_id" class="form-input" required>
                        @foreach ($types as $type)
                            <option value="{{ $type->jenis_transaksi_id }}">{{ ucfirst(str_replace('_', ' ', $type->nama_jenis)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="koreksiNominalDisplay">Nominal Koreksi (Rp)</label>
                    <input id="koreksiNominalDisplay" type="text" inputmode="numeric" class="form-input" placeholder="Contoh: 500.000" required>
                </div>

                <div>
                    <label for="koreksiAlasan">Alasan Koreksi</label>
                    <textarea id="koreksiAlasan" name="alasan" class="form-input" rows="3" maxlength="500" placeholder="Jelaskan alasan koreksi transaksi..." required></textarea>
                </div>
            </div>

            <div class="koreksi-modal-footer">
                <button type="button" id="koreksiModalCancel" class="btn-ui btn-ui-outline btn-ui-sm">Batal</button>
                <button type="submit" class="btn-ui btn-ui-primary btn-ui-sm">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    // 1. Searchable Rekening Dropdown
    const filterForm = document.getElementById('filterKoreksiForm');
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
                        filterForm?.submit();
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

    // 2. Modal Koreksi & Format Ribuan tanpa ,00
    const modal = document.getElementById('koreksiModal');
    const modalForm = document.getElementById('koreksiModalForm');
    const infoAsal = document.getElementById('koreksiInfoAsal');
    const jenisSelect = document.getElementById('koreksiJenis');
    const nominalDisplay = document.getElementById('koreksiNominalDisplay');
    const nominalValue = document.getElementById('koreksiNominalValue');
    const alasanInput = document.getElementById('koreksiAlasan');

    const formatRibuan = (digits) => {
        const clean = String(digits).replace(/\D/g, '');
        if (!clean) return '';
        return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    const syncNominal = (raw) => {
        const digits = String(raw).replace(/\D/g, '');
        nominalValue.value = digits;
        nominalDisplay.value = formatRibuan(digits);
    };

    if (nominalDisplay) {
        nominalDisplay.addEventListener('input', (e) => syncNominal(e.target.value));
    }

    document.querySelectorAll('.btn-open-koreksi').forEach((btn) => {
        btn.addEventListener('click', () => {
            modalForm.action = btn.dataset.action;
            jenisSelect.value = btn.dataset.jenisId;
            syncNominal(btn.dataset.nominal);
            alasanInput.value = '';
            infoAsal.textContent = `${btn.dataset.tanggal} • ${btn.dataset.jenisLabel} • Rp ${formatRibuan(btn.dataset.nominal)}`;
            modal.classList.add('open');
        });
    });

    const closeModal = () => modal && modal.classList.remove('open');
    document.getElementById('koreksiModalClose')?.addEventListener('click', closeModal);
    document.getElementById('koreksiModalCancel')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
})();
</script>
@endsection
