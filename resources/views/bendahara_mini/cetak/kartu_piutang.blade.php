@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kode = '#PINJ-' . str_pad($pinjaman->pinjaman_id, 4, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Piutang {{ $kode }} - {{ $pinjaman->nama_nasabah }}</title>
    @include('bendahara_mini.cetak._gaya')
</head>
<body>
    <button class="tombol-cetak" type="button" onclick="window.print()">Cetak Kartu Piutang</button>

    <div class="kop">
        <div>
            <div class="kop-nama">SIPINTAR</div>
            <div class="kop-sub">Bank Mini Sekolah &bull; Unit Pinjaman Pegawai</div>
        </div>
        <div class="kop-dok">
            <div class="judul">Kartu Piutang Individu</div>
            <div class="nomor">{{ $kode }} &bull; Status: {{ strtoupper($pinjaman->status_pinjaman) }}</div>
        </div>
    </div>

    <div class="meta">
        <div><span>Nama Peminjam</span>: {{ $pinjaman->nama_nasabah }}</div>
        <div><span>Tanggal Cair</span>: {{ $pinjaman->tgl_cair ? \Carbon\Carbon::parse($pinjaman->tgl_cair)->format('d/m/Y') : '-' }}</div>
        <div><span>Unit Kerja</span>: {{ $pinjaman->kelas_unit ?? '-' }}</div>
        <div><span>Metode Bayar</span>: {{ $pinjaman->metode_bayar === 'potong_gaji' ? 'Potong Tunjangan' : 'Pembayaran Langsung' }}</div>
        <div><span>Plafon</span>: {{ $rp($pinjaman->nominal_pinjaman) }}</div>
        <div><span>Tenor / Jasa</span>: {{ $pinjaman->tenor }} bulan / {{ rtrim(rtrim(number_format($pinjaman->bunga_persen, 2, ',', '.'), '0'), ',') }}% per bulan</div>
    </div>

    <div class="ringkas">
        <div><span>Pokok Terbayar</span><strong>{{ $rp($totalPokokTerbayar) }}</strong></div>
        <div><span>Jasa Diterima</span><strong>{{ $rp($totalJasaTerbayar) }}</strong></div>
        <div><span>Sisa Pokok Piutang</span><strong id="sisaKartuCetak">{{ $rp($sisaPokokPiutang) }}</strong></div>
    </div>

    <h3>Jadwal Angsuran</h3>
    <table id="tabelJadwalCetak">
        <thead>
            <tr><th class="tengah">Ke</th><th>Jatuh Tempo</th><th class="kanan">Pokok</th><th class="kanan">Jasa</th><th class="kanan">Total</th><th class="tengah">Status</th></tr>
        </thead>
        <tbody>
            @foreach ($jadwalList as $j)
                <tr>
                    <td class="tengah">{{ $j->angsuran_ke }}</td>
                    <td>{{ \Carbon\Carbon::parse($j->jatuh_tempo)->format('d/m/Y') }}</td>
                    <td class="kanan">{{ $rp($j->pokok) }}</td>
                    <td class="kanan">{{ $rp($j->bunga) }}</td>
                    <td class="kanan">{{ $rp($j->pokok + $j->bunga) }}</td>
                    <td class="tengah">{{ $j->status_bayar === 'lunas' ? 'Lunas' : 'Belum Bayar' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Riwayat Pembayaran</h3>
    <table>
        <thead>
            <tr><th>Tgl Bayar</th><th class="tengah">Angs.</th><th>Metode</th><th class="kanan">Pokok</th><th class="kanan">Jasa</th><th class="kanan">Total</th></tr>
        </thead>
        <tbody>
            @forelse ($riwayatAngsuran as $r)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->tgl_bayar)->format('d/m/Y') }}</td>
                    <td class="tengah">{{ $r->angsuran_ke }}</td>
                    <td>{{ $r->detail_potongan_id ? 'Potong Tunjangan' : 'Langsung' }}</td>
                    <td class="kanan">{{ $rp($r->bayar_pokok) }}</td>
                    <td class="kanan">{{ $rp($r->bayar_bunga) }}</td>
                    <td class="kanan">{{ $rp($r->bayar_pokok + $r->bayar_bunga) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="tengah">Belum ada pembayaran.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="ttd">
        <div>Mengetahui,<br>Manajer Bank Mini<div class="garis">&nbsp;</div></div>
        <div>Bendahara Bank Mini<div class="garis">{{ auth()->user()->nama_user }}</div></div>
    </div>

    <div class="catatan">Dicetak {{ now()->format('d/m/Y H:i') }} dari SIPINTAR.</div>
</body>
</html>
