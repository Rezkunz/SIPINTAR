<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Transaksi &mdash; {{ $logPeriodeLabel }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm 12mm; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; font-size: 12px; }
        .kop { text-align: center; border-bottom: 2px solid #0D5C46; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { margin: 0; font-size: 15px; color: #0D5C46; text-transform: uppercase; }
        .kop .sub { font-size: 11px; color: #475569; margin-top: 2px; }
        .meta { color: #334155; margin-bottom: 16px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #CBD5E1; padding: 7px 8px; font-size: 11px; }
        th { background: #0D5C46; color: #fff; text-align: left; }
        tfoot td { background: #F1F5F9; font-weight: 700; border-top: 2px solid #0D5C46; }
        .right { text-align: right; }
        .print-button { margin-bottom: 16px; padding: 8px 16px; cursor: pointer; background: #0D5C46; color: #fff; border: none; border-radius: 6px; font-weight: 600; }
        @media print {
            .print-button { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" type="button" onclick="window.print()">Cetak Log Transaksi</button>

    <div class="kop">
        <h1>Bank Mini Wiyata Cipta Niaga</h1>
        <div class="sub">SMK Negeri 1 Lemahabang &mdash; Log Transaksi {{ ucfirst($logPeriode) }}</div>
    </div>

    <div class="meta">
        <strong>Periode:</strong> {{ $logPeriodeLabel }}<br>
        <strong>Total transaksi:</strong> {{ $ringkasan['total_transaksi'] }}
        &bull; <strong>Total kredit:</strong> Rp {{ number_format($ringkasan['total_kredit'], 0, ',', '.') }}
        &bull; <strong>Total debit:</strong> Rp {{ number_format($ringkasan['total_debit'], 0, ',', '.') }}
        &bull; <strong>Total bunga:</strong> Rp {{ number_format($ringkasan['total_bunga'] ?? 0, 0, ',', '.') }}
        &bull; <strong>Koreksi:</strong> {{ $ringkasan['total_koreksi'] ?? 0 }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Rekening</th>
                <th>Nasabah</th>
                <th>Jenis</th>
                <th class="right">Debit</th>
                <th class="right">Kredit</th>
                <th class="right">Saldo Akhir</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transaksi as $item)
                <tr>
                    <td>{{ $item->tgl_transaksi }}</td>
                    <td>{{ $item->no_rek }}</td>
                    <td>{{ $item->nama_nasabah }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->nama_jenis)) }}</td>
                    <td class="right">Rp {{ number_format($item->debit, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->kredit, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->saldo_akhir, 0, ',', '.') }}</td>
                    <td>{{ $item->koreksi_id ? 'Dikoreksi' : 'Normal' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            @php
                $selisihBersih = (float) $ringkasan['total_kredit'] - (float) $ringkasan['total_debit'];
            @endphp
            <tr>
                <td colspan="4">TOTAL ({{ $ringkasan['total_transaksi'] }} transaksi)</td>
                <td class="right">Rp {{ number_format($ringkasan['total_debit'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($ringkasan['total_kredit'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($selisihBersih, 0, ',', '.') }}</td>
                <td>Selisih Bersih</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
