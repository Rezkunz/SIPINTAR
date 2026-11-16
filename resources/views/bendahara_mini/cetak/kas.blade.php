@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }} - {{ $filter['label'] }}</title>
    @include('bendahara_mini.cetak._gaya')
</head>
<body>
    <button class="tombol-cetak" type="button" onclick="window.print()">Cetak Laporan</button>

    <div class="kop">
        <div>
            <div class="kop-nama">SIPINTAR</div>
            <div class="kop-sub">Bank Mini Sekolah &bull; {{ $kas->nama_kas }}</div>
        </div>
        <div class="kop-dok">
            <div class="judul">{{ $judul }}</div>
            <div class="nomor" id="periodeCetak">Periode: {{ $filter['label'] }}</div>
        </div>
    </div>

    <div class="ringkas">
        <div><span>Total Masuk</span><strong id="totalMasukCetak">{{ $rp($totalMasuk) }}</strong></div>
        <div><span>Total Keluar</span><strong id="totalKeluarCetak">{{ $rp($totalKeluar) }}</strong></div>
        <div><span>Jumlah Mutasi</span><strong>{{ $mutasiList->count() }} transaksi</strong></div>
        <div><span>Saldo Saat Ini</span><strong id="saldoCetak">{{ $rp($kas->saldo) }}</strong></div>
    </div>

    <table id="tabelKasCetak">
        <thead>
            <tr><th>Tanggal</th><th>Uraian</th><th>Petugas</th><th class="kanan">Masuk</th><th class="kanan">Keluar</th></tr>
        </thead>
        <tbody>
            @forelse ($mutasiList as $m)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m->tgl_mutasi)->format('d/m/Y') }}</td>
                    <td>{{ $m->uraian }}</td>
                    <td>{{ $m->nama_user }}</td>
                    <td class="kanan">{{ $m->arah === 'masuk' ? $rp($m->nominal) : '-' }}</td>
                    <td class="kanan">{{ $m->arah === 'keluar' ? $rp($m->nominal) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="tengah">Tidak ada mutasi pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="3">Total</td><td class="kanan">{{ $rp($totalMasuk) }}</td><td class="kanan">{{ $rp($totalKeluar) }}</td></tr>
        </tfoot>
    </table>

    <div class="ttd">
        <div>Mengetahui,<br>Manajer Bank Mini<div class="garis">&nbsp;</div></div>
        <div>Bendahara Bank Mini<div class="garis">{{ auth()->user()->nama_user }}</div></div>
    </div>

    <div class="catatan">Dicetak {{ now()->format('d/m/Y H:i') }} dari SIPINTAR.</div>
</body>
</html>
