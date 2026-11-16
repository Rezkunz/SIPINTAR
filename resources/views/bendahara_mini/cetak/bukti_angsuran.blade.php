@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $nomor = 'BA-' . str_pad($angsuran->angsuran_id, 5, '0', STR_PAD_LEFT);
    $total = (float) $angsuran->bayar_pokok + (float) $angsuran->bayar_bunga;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran Angsuran {{ $nomor }}</title>
    @include('bendahara_mini.cetak._gaya')
</head>
<body>
    <button class="tombol-cetak" type="button" onclick="window.print()">Cetak Bukti</button>

    <div class="kop">
        <div>
            <div class="kop-nama">SIPINTAR</div>
            <div class="kop-sub">Bank Mini Sekolah &bull; Unit Pinjaman Pegawai</div>
        </div>
        <div class="kop-dok">
            <div class="judul">Bukti Pembayaran Angsuran</div>
            <div class="nomor" id="nomorBukti">No. {{ $nomor }}</div>
        </div>
    </div>

    @if ($angsuran->koreksi_id)
        <div class="batal">TRANSAKSI INI SUDAH DIKOREKSI / DIBATALKAN</div>
    @endif

    <div class="meta">
        <div><span>Tanggal Bayar</span>: {{ \Carbon\Carbon::parse($angsuran->tgl_bayar)->format('d/m/Y') }}</div>
        <div><span>No. Pinjaman</span>: #PINJ-{{ str_pad($angsuran->pinjaman_id, 4, '0', STR_PAD_LEFT) }}</div>
        <div><span>Nama Peminjam</span>: {{ $angsuran->nama_nasabah }}</div>
        <div><span>Plafon / Tenor</span>: {{ $rp($angsuran->nominal_pinjaman) }} / {{ $angsuran->tenor }} bulan</div>
        <div><span>Unit Kerja</span>: {{ $angsuran->kelas_unit ?? '-' }}</div>
        <div><span>Metode</span>: {{ $angsuran->detail_potongan_id ? 'Potong Tunjangan' : 'Pembayaran Langsung (Tunai)' }}</div>
    </div>

    <table>
        <thead>
            <tr><th>Uraian</th><th class="kanan">Jumlah</th></tr>
        </thead>
        <tbody>
            <tr><td>Angsuran pokok ke-{{ $angsuran->angsuran_ke }} (jatuh tempo {{ \Carbon\Carbon::parse($angsuran->jatuh_tempo)->format('d/m/Y') }})</td><td class="kanan">{{ $rp($angsuran->bayar_pokok) }}</td></tr>
            <tr><td>Jasa pinjaman 2%</td><td class="kanan">{{ $rp($angsuran->bayar_bunga) }}</td></tr>
        </tbody>
        <tfoot>
            <tr><td>Total Diterima</td><td class="kanan" id="totalBukti">{{ $rp($total) }}</td></tr>
        </tfoot>
    </table>

    <div><strong>Sisa pokok piutang setelah pembayaran ini:</strong> <span id="sisaBukti">{{ $rp($sisaPiutang) }}</span></div>

    <div class="ttd">
        <div>Peminjam<div class="garis">{{ $angsuran->nama_nasabah }}</div></div>
        <div>Bendahara Bank Mini<div class="garis">{{ $angsuran->nama_petugas ?? '-' }}</div></div>
    </div>

    <div class="catatan">Dicetak {{ now()->format('d/m/Y H:i') }} dari SIPINTAR. Simpan bukti ini sebagai tanda pembayaran yang sah.</div>
</body>
</html>
