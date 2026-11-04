@extends('layouts.app')

@section('title', 'Ekspor Laporan')
@section('breadcrumb', 'Ekspor Laporan')

@section('content')

@php
    $bulanIni = now()->month;
    $tahunIni = now()->year;
    // field: daftar filter yang tersedia untuk laporan tersebut
    $grupLaporan = [
        'Laporan Tabungan' => [
            ['route' => 'laporan.tabungan',         'judul' => 'Rekap Harian Tabungan',         'ket' => 'Mutasi bersih per nasabah per tanggal dalam satu bulan.', 'field' => ['kategori', 'bulan', 'tahun']],
            ['route' => 'laporan.tabungan.bulanan', 'judul' => 'Rekap Tabungan & Jasa Bulanan', 'ket' => 'Saldo pokok akhir bulan, jasa tabungan, dan total.',      'field' => ['kategori', 'bulan', 'tahun']],
            ['route' => 'laporan.tabungan.kas',     'judul' => 'Buku Kas Harian Tabungan',      'ket' => 'Kas awal, penerimaan, pengeluaran, kas akhir per hari.',  'field' => ['bulan', 'tahun']],
        ],
        'Laporan Pinjaman' => [
            ['route' => 'laporan.pinjaman',     'judul' => 'Rekap Pinjaman & Kolektibilitas', 'ket' => 'Sisa pokok, tunggakan, kolektibilitas OJK, dan rasio NPL.',    'field' => ['bulan', 'tahun']],
            ['route' => 'laporan.pinjaman.kas', 'judul' => 'Kas Bendahara & Angsuran',        'ket' => 'Kas harian bendahara dan rekap penerimaan angsuran.',          'field' => ['sumber', 'bulan', 'tahun']],
        ],
        'Laporan Keuangan' => [
            ['route' => 'laporan.tahunan', 'judul' => 'Laporan Keuangan Tahunan', 'ket' => 'Laba rugi, dasar perhitungan per bulan, dan rincian kas manual.', 'field' => ['tahun']],
            ['route' => 'laporan.neraca',  'judul' => 'Neraca Saldo',             'ket' => 'Aktiva, kewajiban, dan ekuitas termasuk pos neraca manual.',      'field' => ['bulan', 'tahun']],
        ],
    ];
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Ekspor Laporan</h1>
        <p>Unduh seluruh laporan Bank Mini dalam satu tempat: Excel (.xlsx) untuk diolah, PDF siap cetak lengkap dengan kop dan kolom tanda tangan, atau CSV untuk audit data.</p>
    </div>
</div>

@foreach ($grupLaporan as $namaGrup => $daftar)
    <div class="ekspor-grup-judul">{{ $namaGrup }}</div>
    <div class="ekspor-grid">
        @foreach ($daftar as $lap)
            <form method="GET" action="{{ route($lap['route'] . '.xlsx') }}" class="ekspor-card">
                <div class="ekspor-card-head">
                    <div class="ekspor-card-judul">{{ $lap['judul'] }}</div>
                    <div class="ekspor-card-ket">{{ $lap['ket'] }}</div>
                </div>

                <div class="ekspor-card-field">
                    @if (in_array('kategori', $lap['field']))
                        <select name="kategori" class="lap-select" title="Kategori Tabungan">
                            <option value="siswa">Tabungan Siswa</option>
                            <option value="guru">Tabungan Guru / Karyawan</option>
                        </select>
                    @endif
                    @if (in_array('sumber', $lap['field']))
                        <select name="sumber" class="lap-select" title="Sumber Kas">
                            <option value="semua">Kas Tunai + Bank</option>
                            <option value="tunai">Kas Tunai</option>
                            <option value="bank">Kas Bank</option>
                        </select>
                    @endif
                    @if (in_array('bulan', $lap['field']))
                        <select name="bulan" class="lap-select" title="Bulan">
                            @for ($b = 1; $b <= 12; $b++)
                                <option value="{{ $b }}" {{ $b === $bulanIni ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(2026, $b, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    @endif
                    <select name="tahun" class="lap-select" title="Tahun">
                        @for ($y = $tahunIni - 3; $y <= $tahunIni; $y++)
                            <option value="{{ $y }}" {{ $y === $tahunIni ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div class="ekspor-card-aksi">
                    <button type="submit" formaction="{{ route($lap['route'] . '.xlsx') }}" class="lap-unduh-btn excel">Excel</button>
                    <button type="submit" formaction="{{ route($lap['route'] . '.pdf') }}" class="lap-unduh-btn pdf">PDF</button>
                    <button type="submit" formaction="{{ route($lap['route'] . '.csv') }}" class="lap-unduh-btn csv">CSV</button>
                    <button type="submit" formaction="{{ route($lap['route']) }}" class="ekspor-lihat" title="Buka laporan di layar">Lihat &rarr;</button>
                </div>
            </form>
        @endforeach
    </div>
@endforeach

@endsection
