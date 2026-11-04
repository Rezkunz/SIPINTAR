<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Transaksi {{ $rekening->no_rek }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 32px; }
        h1 { margin-bottom: 4px; }
        .meta { color: #4B5563; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #D1D5DB; padding: 8px; font-size: 12px; }
        th { background: #F3F4F6; text-align: left; }
        .right { text-align: right; }
        .print-button { margin-bottom: 20px; padding: 8px 14px; cursor: pointer; }
        @media print { .print-button { display: none; } body { margin: 12px; } }
    </style>
</head>
<body>
    <button class="print-button" type="button" onclick="window.print()">Cetak Log Transaksi</button>
    <h1>Log Transaksi SIPINTAR</h1>
    <div class="meta">Nasabah: {{ $rekening->nasabah->nama_nasabah ?? '-' }}<br>Rekening: {{ $rekening->no_rek }}<br>Saldo saat ini: Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</div>
    <table>
        <thead><tr><th>Tanggal</th><th>Jenis</th><th class="right">Debit</th><th class="right">Kredit</th><th class="right">Saldo Akhir</th></tr></thead>
        <tbody>
            @forelse ($mutasi as $transaksi)
                <tr><td>{{ $transaksi->tgl_transaksi }}</td><td>{{ ucfirst(str_replace('_', ' ', $transaksi->nama_jenis)) }}</td><td class="right">Rp {{ number_format($transaksi->debit, 0, ',', '.') }}</td><td class="right">Rp {{ number_format($transaksi->kredit, 0, ',', '.') }}</td><td class="right">Rp {{ number_format($transaksi->saldo_akhir, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="5">Belum ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
