<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi Rekening {{ $rekening->no_rek }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm 12mm; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; font-size: 12px; }
        .kop { text-align: center; border-bottom: 2px solid #0D5C46; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { margin: 0; font-size: 15px; color: #0D5C46; text-transform: uppercase; }
        .kop .sub { font-size: 11px; color: #475569; margin-top: 2px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 12px; color: #334155; }
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }
        .card { border: 1px solid #CBD5E1; border-radius: 6px; padding: 10px; background: #F8FAFC; }
        .card .label { font-size: 10px; color: #64748B; text-transform: uppercase; }
        .card .val { font-size: 14px; font-weight: 700; margin-top: 4px; color: #0F172A; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #CBD5E1; padding: 7px 8px; font-size: 11.5px; }
        th { background: #0D5C46; color: #fff; text-align: left; }
        tfoot td { background: #F1F5F9; font-weight: 700; border-top: 2px solid #0D5C46; }
        .right { text-align: right; }
        .ttd { width: 100%; margin-top: 24px; page-break-inside: avoid; border: none; }
        .ttd td { border: none; width: 50%; text-align: center; vertical-align: top; padding: 0; }
        .ttd .space { height: 54px; }
        .print-button { margin-bottom: 16px; padding: 8px 16px; cursor: pointer; background: #0D5C46; color: #fff; border: none; border-radius: 6px; font-weight: 600; }
        @media print {
            .print-button { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" type="button" onclick="window.print()">Cetak Laporan Mutasi</button>

    <div class="kop">
        <h1>Bank Mini Wiyata Cipta Niaga</h1>
        <div class="sub">SMK Negeri 1 Lemahabang &mdash; Laporan Mutasi Rekening Tabungan</div>
    </div>

    <div class="meta">
        <div>
            <strong>Nasabah:</strong> {{ $rekening->nasabah->nama_nasabah ?? '-' }}<br>
            <strong>No. Rekening:</strong> {{ $rekening->no_rek }}<br>
            <strong>Peran / Kelas:</strong> {{ $rekening->nasabah?->jenis_nasabah === 'pegawai' ? 'Pegawai' : 'Siswa' }}{{ $rekening->nasabah?->kelas_unit ? ' / ' . $rekening->nasabah->kelas_unit : '' }}
        </div>
        <div style="text-align: right;">
            <strong>Periode:</strong> {{ $periodeLabel }}<br>
            <strong>Dicetak:</strong> {{ now()->translatedFormat('d F Y H:i') }} WIB
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="label">Total Masuk</div>
            <div class="val">Rp {{ number_format($ringkasanMutasi['total_masuk'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Total Keluar</div>
            <div class="val">Rp {{ number_format($ringkasanMutasi['total_keluar'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Jumlah Mutasi</div>
            <div class="val">{{ $ringkasanMutasi['jumlah_mutasi'] }}</div>
        </div>
        <div class="card">
            <div class="label">Saldo Akhir</div>
            <div class="val">Rp {{ number_format($ringkasanMutasi['saldo_akhir'], 0, ',', '.') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th class="right">Debit</th>
                <th class="right">Kredit</th>
                <th class="right">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mutasi as $transaksi)
                <tr>
                    <td>{{ $transaksi->tgl_transaksi }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $transaksi->nama_jenis)) }}</td>
                    <td class="right">Rp {{ number_format($transaksi->debit, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($transaksi->kredit, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($transaksi->saldo_akhir, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;">Belum ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="right">Rp {{ number_format($ringkasanMutasi['total_keluar'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($ringkasanMutasi['total_masuk'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($ringkasanMutasi['saldo_akhir'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="ttd">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div>Pengawas / Kepala Bank Mini</div>
                <div class="space"></div>
                <div>( _______________________ )</div>
            </td>
            <td>
                <div>Lemahabang, {{ now()->translatedFormat('d F Y') }}</div>
                <div>Teller,</div>
                <div class="space"></div>
                <div>( {{ auth()->user()->nama_user ?? 'Teller' }} )</div>
            </td>
        </tr>
    </table>
</body>
</html>
