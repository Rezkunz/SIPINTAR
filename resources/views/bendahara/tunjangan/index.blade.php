@extends('layouts.app')

@section('title', 'Data Tunjangan Pegawai')
@section('breadcrumb', 'Data Tunjangan')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $nilaiInput = fn ($d, $kolom) => $d && $d->$kolom !== null ? (int) $d->$kolom : '';
@endphp

<style>
    .tunj-input { width: 100%; min-width: 110px; padding: 7px 10px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 6px; text-align: right; font-variant-numeric: tabular-nums; }
    .tunj-input:focus { outline: 2px solid #A7F3D0; border-color: #0D5C46; }
    .tunj-ket { width: 100%; min-width: 140px; padding: 7px 10px; font-size: 12.5px; border: 1px solid #CBD5E1; border-radius: 6px; }
    .tunj-hint { font-size: 10.5px; color: var(--text-muted); margin-top: 2px; text-align: right; }
    .tunj-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .tunj-toolbar form { margin: 0; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .tunj-search { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding: 14px 20px; border-bottom: 1px solid var(--border-subtle); }
    .tunj-search input { flex: 1 1 260px; max-width: 360px; padding: 8px 12px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 6px; }
    .tunj-search input:focus { outline: 2px solid #A7F3D0; border-color: #0D5C46; }
    .tunj-search .tunj-search-info { font-size: 12px; color: var(--text-muted); }
</style>

<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Data Tunjangan Pegawai</h1>
        <p>Serahkan data gaji, tunjangan, dan potongan lain (mis. pajak) tiap bulan. Data ini dipakai Bendahara Bank Mini saat menilai kelayakan pinjaman dan menyusun rencana potongan tunjangan.</p>
    </div>
</div>

<div class="metric-grid metric-grid-3">
    <div class="metric-card card-blue">
        <div class="metric-header"><span class="metric-title">Pegawai Aktif</span></div>
        <div class="metric-value">{{ $pegawai->count() }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Guru & tenaga kependidikan nasabah Bank Mini</div>
    </div>
    <div class="metric-card card-emerald">
        <div class="metric-header"><span class="metric-title">Sudah Diisi ({{ $labelPeriode }})</span></div>
        <div class="metric-value" id="jumlahTerisi">{{ $jumlahTerisi }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Data tersimpan untuk bulan ini</div>
    </div>
    <div class="metric-card card-amber">
        <div class="metric-header"><span class="metric-title">Belum Diisi</span></div>
        <div class="metric-value">{{ max(0, $pegawai->count() - $jumlahTerisi) }}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Gunakan salin bulan lalu atau impor CSV</div>
    </div>
</div>

<div class="content-card" style="margin-bottom:20px;">
    <div class="card-header-bar tunj-toolbar">
        <form method="GET" action="{{ route('bendahara.tunjangan.index') }}" id="formPeriodeTunjangan">
            <span style="font-size:13px; font-weight:700;">Periode:</span>
            <select name="bulan" class="form-select" onchange="this.form.submit()" style="padding:6px 10px; font-size:13px; border-radius:6px;">
                @foreach ($namaBulan as $no => $nama)
                    <option value="{{ $no }}" @selected($no === $bulan)>{{ $nama }}</option>
                @endforeach
            </select>
            <select name="tahun" class="form-select" onchange="this.form.submit()" style="padding:6px 10px; font-size:13px; border-radius:6px;">
                @foreach (range(now()->year - 2, now()->year + 1) as $th)
                    <option value="{{ $th }}" @selected($th === $tahun)>{{ $th }}</option>
                @endforeach
            </select>
        </form>

        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <form method="POST" action="{{ route('bendahara.tunjangan.salin') }}" onsubmit="return confirm('Salin data bulan lalu untuk pegawai yang belum diisi pada {{ $labelPeriode }}?')">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <button type="submit" class="btn-ui btn-ui-outline" id="btnSalinBulanLalu" style="font-weight:600;">Salin dari Bulan Lalu</button>
            </form>
            <a href="{{ route('bendahara.tunjangan.template', ['tahun' => $tahun, 'bulan' => $bulan]) }}" class="btn-ui btn-ui-outline" id="btnTemplateCsv" style="text-decoration:none; font-weight:600;">Unduh Template CSV</a>
            <form method="POST" action="{{ route('bendahara.tunjangan.impor') }}" enctype="multipart/form-data" id="formImporTunjangan">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <input type="file" name="file" id="fileImporTunjangan" accept=".csv,text/csv" required style="font-size:12px; max-width:210px;">
                <button type="submit" class="btn-ui btn-ui-outline" id="btnImporCsv" style="font-weight:600;">Impor CSV</button>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('bendahara.tunjangan.simpan') }}" id="formDataTunjangan">
    @csrf
    <input type="hidden" name="tahun" value="{{ $tahun }}">
    <input type="hidden" name="bulan" value="{{ $bulan }}">

    <div class="content-card">
        <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 class="card-heading">Data Tunjangan {{ $labelPeriode }}</h2>
                <div style="font-size:12px; color:var(--text-muted);">Angka abu-abu di bawah isian adalah data bulan lalu. Tunjangan bersih = tunjangan − potongan lain, dibandingkan dengan angsuran potong gaji bulan ini.</div>
            </div>
            <button type="submit" class="btn-ui btn-ui-primary" id="btnSimpanTunjangan" style="font-weight:800;">Simpan Data Tunjangan</button>
        </div>

        <div class="tunj-search">
            <input type="search" id="cariTunjangan" placeholder="Cari nama pegawai atau unit kerja..." autocomplete="off" aria-label="Cari pegawai">
            <span class="tunj-search-info" id="infoCariTunjangan"></span>
        </div>

        <div class="table-responsive">
            <table class="financial-table" id="tabelTunjangan">
                <thead>
                    <tr>
                        <th>Pegawai</th>
                        <th style="text-align:right;">Gaji Pokok</th>
                        <th style="text-align:right;">Tunjangan</th>
                        <th style="text-align:right;">Potongan Lain (Pajak, dll.)</th>
                        <th style="text-align:right;">Tunjangan Bersih</th>
                        <th style="text-align:right;">Angsuran Potong Gaji</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pegawai as $p)
                        @php
                            $d = $dataBulanIni->get($p->nasabah_id);
                            $l = $dataBulanLalu->get($p->nasabah_id);
                            $angsuran = (float) ($angsuranBulanIni[$p->nasabah_id] ?? 0);
                        @endphp
                        <tr data-angsuran="{{ $angsuran }}" data-nasabah="{{ $p->nasabah_id }}"
                            data-cari="{{ \Illuminate\Support\Str::lower($p->nama_nasabah . ' ' . ($p->kelas_unit ?? 'Pegawai')) }}">
                            <td>
                                <div style="font-weight:700; color:var(--text-heading);">{{ $p->nama_nasabah }}</div>
                                <div style="font-size:11px; color:var(--text-muted);">{{ $p->kelas_unit ?? 'Pegawai' }}</div>
                                @if ($d)
                                    <span class="badge-pill green" style="font-size:10px;">Tersimpan</span>
                                @endif
                            </td>
                            <td>
                                <input type="number" min="0" step="1000" class="tunj-input" id="gaji-{{ $p->nasabah_id }}"
                                       name="data[{{ $p->nasabah_id }}][gaji_pokok]" value="{{ $nilaiInput($d, 'gaji_pokok') }}" oninput="hitungBersih({{ $p->nasabah_id }})">
                                @if ($l)<div class="tunj-hint">Bln lalu {{ $rp($l->gaji_pokok) }}</div>@endif
                            </td>
                            <td>
                                <input type="number" min="0" step="1000" class="tunj-input" id="tunjangan-{{ $p->nasabah_id }}"
                                       name="data[{{ $p->nasabah_id }}][tunjangan]" value="{{ $nilaiInput($d, 'tunjangan') }}" oninput="hitungBersih({{ $p->nasabah_id }})">
                                @if ($l)<div class="tunj-hint">Bln lalu {{ $rp($l->tunjangan) }}</div>@endif
                            </td>
                            <td>
                                <input type="number" min="0" step="1000" class="tunj-input" id="potongan-{{ $p->nasabah_id }}"
                                       name="data[{{ $p->nasabah_id }}][potongan_lain]" value="{{ $nilaiInput($d, 'potongan_lain') }}" oninput="hitungBersih({{ $p->nasabah_id }})">
                                @if ($l)<div class="tunj-hint">Bln lalu {{ $rp($l->potongan_lain) }}</div>@endif
                            </td>
                            <td style="text-align:right; font-weight:800;" id="bersih-{{ $p->nasabah_id }}">-</td>
                            <td style="text-align:right;">
                                {{ $angsuran > 0 ? $rp($angsuran) : '-' }}
                                <div id="status-{{ $p->nasabah_id }}" style="margin-top:3px;"></div>
                            </td>
                            <td>
                                <input type="text" maxlength="255" class="tunj-ket" id="ket-{{ $p->nasabah_id }}"
                                       name="data[{{ $p->nasabah_id }}][keterangan]" value="{{ $d->keterangan ?? '' }}" placeholder="Opsional">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><div class="empty-placeholder" style="padding:28px; text-align:center;">Belum ada nasabah pegawai aktif.</div></td>
                        </tr>
                    @endforelse
                    @if ($pegawai->isNotEmpty())
                        <tr id="tunjTidakDitemukan" style="display:none;">
                            <td colspan="7"><div class="empty-placeholder" style="padding:28px; text-align:center;">Tidak ada pegawai yang cocok dengan pencarian.</div></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <nav class="sipintar-pagination" id="paginasiTunjangan" aria-label="Halaman data tunjangan" style="display:none; padding:12px 20px 16px;">
            <div class="pagination-info" id="paginasiTunjanganInfo"></div>
            <ul class="pagination-links" id="paginasiTunjanganLinks"></ul>
        </nav>
    </div>
</form>

<script>
function formatRupiahTunjangan(n) {
    return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function hitungBersih(id) {
    const tunjangan = parseFloat(document.getElementById('tunjangan-' + id).value) || 0;
    const potongan = parseFloat(document.getElementById('potongan-' + id).value) || 0;
    const sel = document.getElementById('bersih-' + id);
    const status = document.getElementById('status-' + id);
    const angsuran = parseFloat(sel.closest('tr').dataset.angsuran) || 0;
    const kosong = document.getElementById('tunjangan-' + id).value === '';

    if (kosong) {
        sel.innerText = '-';
        status.innerHTML = '';
        return;
    }

    const bersih = Math.max(0, tunjangan - potongan);
    sel.innerText = formatRupiahTunjangan(bersih);

    if (angsuran <= 0) {
        status.innerHTML = '';
    } else if (bersih >= angsuran) {
        status.innerHTML = '<span class="badge-pill green" style="font-size:10px;">Cukup dipotong</span>';
    } else {
        status.innerHTML = '<span class="badge-pill red" style="font-size:10px;">Tidak cukup, sarankan bayar langsung</span>';
    }
}

// Pencarian + halaman (5 baris per halaman). Baris yang tidak tampil tetap berada di form,
// sehingga isian di halaman lain ikut tersimpan saat tombol Simpan ditekan.
const TUNJ_PER_HALAMAN = 5;
let tunjHalaman = 1;

function barisTunjangan() {
    return [...document.querySelectorAll('#tabelTunjangan tbody tr[data-nasabah]')];
}

function tombolHalaman(label, halaman, opsi = {}) {
    const li = document.createElement('li');
    li.className = 'page-item' + (opsi.aktif ? ' active' : '') + (opsi.nonaktif ? ' disabled' : '');
    const a = document.createElement(opsi.nonaktif || opsi.titik ? 'span' : 'a');
    a.className = 'page-link' + (opsi.titik ? ' dots' : '');
    a.textContent = label;
    if (!opsi.nonaktif && !opsi.titik) {
        a.href = '#';
        a.dataset.halaman = halaman;
        if (opsi.aria) a.setAttribute('aria-label', opsi.aria);
        a.addEventListener('click', e => { e.preventDefault(); tunjHalaman = halaman; tampilkanTunjangan(); });
    }
    li.appendChild(a);
    return li;
}

function tampilkanTunjangan() {
    const kata = document.getElementById('cariTunjangan').value.trim().toLowerCase();
    const semua = barisTunjangan();
    const cocok = semua.filter(tr => !kata || tr.dataset.cari.includes(kata));
    const totalHalaman = Math.max(1, Math.ceil(cocok.length / TUNJ_PER_HALAMAN));
    tunjHalaman = Math.min(Math.max(1, tunjHalaman), totalHalaman);
    const awal = (tunjHalaman - 1) * TUNJ_PER_HALAMAN;
    const tampil = new Set(cocok.slice(awal, awal + TUNJ_PER_HALAMAN));

    semua.forEach(tr => { tr.style.display = tampil.has(tr) ? '' : 'none'; });

    const kosong = document.getElementById('tunjTidakDitemukan');
    if (kosong) kosong.style.display = cocok.length === 0 ? '' : 'none';

    document.getElementById('infoCariTunjangan').textContent =
        kata ? cocok.length + ' dari ' + semua.length + ' pegawai cocok' : '';

    const nav = document.getElementById('paginasiTunjangan');
    const links = document.getElementById('paginasiTunjanganLinks');
    nav.style.display = cocok.length > 0 ? '' : 'none';
    document.getElementById('paginasiTunjanganInfo').innerHTML = cocok.length
        ? 'Menampilkan <strong>' + (awal + 1) + '–' + (awal + tampil.size) + '</strong> dari <strong>' + cocok.length + '</strong> pegawai'
        : '';

    links.innerHTML = '';
    if (totalHalaman <= 1) return;
    links.appendChild(tombolHalaman('‹', tunjHalaman - 1, { nonaktif: tunjHalaman === 1, aria: 'Sebelumnya' }));
    for (let h = 1; h <= totalHalaman; h++) {
        const dekat = h === 1 || h === totalHalaman || Math.abs(h - tunjHalaman) <= 1;
        if (dekat) {
            links.appendChild(tombolHalaman(String(h), h, { aktif: h === tunjHalaman }));
        } else if (h === 2 || h === totalHalaman - 1) {
            links.appendChild(tombolHalaman('…', h, { titik: true }));
        }
    }
    links.appendChild(tombolHalaman('›', tunjHalaman + 1, { nonaktif: tunjHalaman === totalHalaman, aria: 'Berikutnya' }));
}

document.addEventListener('DOMContentLoaded', function () {
    barisTunjangan().forEach(tr => hitungBersih(tr.dataset.nasabah));
    const cari = document.getElementById('cariTunjangan');
    cari.addEventListener('input', () => { tunjHalaman = 1; tampilkanTunjangan(); });
    // Enter di kotak cari jangan sampai mengirim form simpan
    cari.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
    tampilkanTunjangan();
});
</script>
@endsection
