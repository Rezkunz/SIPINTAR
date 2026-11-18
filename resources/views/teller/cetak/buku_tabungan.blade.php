<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Tabungan - {{ $rekening->no_rek }}</title>
    <style>
        @page { size: 135mm 195mm; margin: 8mm 5mm; }
        * { box-sizing: border-box; }
        body { font-family: "Courier New", Courier, monospace; color: #000; margin: 16px; font-size: 8.5pt; }
        .no-print { font-family: Arial, sans-serif; margin-bottom: 14px; padding: 10px 12px; background: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 8px; display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
        .no-print label { display: block; font-size: 11px; color: #475569; margin-bottom: 3px; font-weight: 600; }
        .no-print input, .no-print select { padding: 5px 8px; border: 1px solid #CBD5E1; border-radius: 5px; font-size: 12px; }
        .btn-print { padding: 7px 14px; background: #0D5C46; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 12px; }

        .passbook-sheet { width: 125mm; min-height: 179mm; border: 1px dashed #94A3B8; padding: 2mm; }
        .passbook-banner { background: #0D5C46; color: #fff; font-family: Arial, sans-serif; font-weight: 700; font-size: 8.5pt; padding: 3px 6px; display: flex; justify-content: space-between; letter-spacing: 0.3px; }
        .passbook-meta { font-family: Arial, sans-serif; font-size: 7.5pt; margin: 3px 0 4px; display: flex; justify-content: space-between; }

        table.passbook { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.passbook th, table.passbook td { padding: 1.2mm 1mm; font-size: 8pt; line-height: 1.2; overflow: hidden; white-space: nowrap; }
        table.passbook th { font-family: Arial, sans-serif; font-size: 7pt; border: 1px solid #000; text-align: center; background: #E0F2FE; }
        table.passbook td { border-left: 1px solid #000; border-right: 1px solid #000; height: 5.5mm; }
        table.passbook tbody tr:last-child td { border-bottom: 1px solid #000; }
        .col-tgl { width: 16%; text-align: center; }
        .col-sandi { width: 9%; text-align: center; }
        .col-debet { width: 19%; text-align: right; }
        .col-kredit { width: 19%; text-align: right; }
        .col-saldo { width: 22%; text-align: right; font-weight: 700; }
        .col-val { width: 15%; text-align: center; font-size: 7pt; }
        .sandi-legend { font-family: Arial, sans-serif; font-size: 6.5pt; margin-top: 4px; color: #334155; }
        .passbook-summary { width: 100%; border-collapse: collapse; margin-top: 6px; font-family: Arial, sans-serif; font-size: 7.5pt; }
        .passbook-summary th, .passbook-summary td { border: 1px solid #000; padding: 2px 5px; }
        .passbook-summary th { background: #F1F5F9; text-align: left; font-weight: 700; }
        .passbook-summary td { text-align: right; font-weight: 700; }

        /* Mode isi saja: sembunyikan header/border agar pas menimpa buku tabungan fisik pre-printed */
        .mode-isi .passbook-banner,
        .mode-isi .passbook-meta,
        .mode-isi table.passbook thead,
        .mode-isi .sandi-legend { visibility: hidden; }
        .mode-isi table.passbook td { border-color: transparent !important; }

        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            .passbook-sheet { border: none; padding: 0; width: 100%; }
            .passbook-banner, table.passbook th, .passbook-summary th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .mode-isi .passbook-summary { display: none; }
        }
    </style>
</head>
<body>
    <form class="no-print" method="GET" action="{{ route('tabungan.cetak.buku-tabungan') }}">
        <input type="hidden" name="no_rek" value="{{ $rekening->no_rek }}">
        <div>
            <label for="line_offset">Baris Awal (Offset)</label>
            <input id="line_offset" type="number" name="line_offset" min="1" max="60" value="{{ $lineOffset }}" style="width:80px;">
        </div>
        <div>
            <label for="dari_id">Mulai dari Transaksi</label>
            <select id="dari_id" name="dari_id">
                <option value="0">Semua Transaksi</option>
                @foreach ($semuaTransaksi as $opt)
                    <option value="{{ $opt->transaksi_id }}" @selected($dariId === (int) $opt->transaksi_id)>
                        #{{ $opt->transaksi_id }} ({{ \Carbon\Carbon::parse($opt->tgl_transaksi)->format('d/m/y') }} - Rp {{ number_format($opt->debit > 0 ? $opt->debit : $opt->kredit, 0, ',', '.') }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="mode_cetak">Mode Cetak</label>
            <select id="mode_cetak" name="mode_cetak">
                <option value="full" @selected($modeCetak === 'full')>Lengkap (Dengan Header)</option>
                <option value="isi" @selected($modeCetak === 'isi')>Isi Saja (Buku Fisik Pre-printed)</option>
            </select>
        </div>
        <button class="btn-print" type="submit" style="background:#475569;">Terapkan</button>
        <button class="btn-print" type="button" onclick="window.print()">Cetak Buku Tabungan</button>
    </form>

    <div class="passbook-sheet {{ $modeCetak === 'isi' ? 'mode-isi' : '' }}">
        <div class="passbook-banner">
            <span>SIMPANAN GURU &amp; KARYAWAN</span>
            <span>SIMPANAN SISWA</span>
        </div>
        <div class="passbook-meta">
            <span><strong>{{ $rekening->no_rek }}</strong> &mdash; {{ $rekening->nasabah->nama_nasabah ?? '-' }}</span>
            <span>Offset baris: {{ $lineOffset }}</span>
        </div>

        <table class="passbook">
            <thead>
                <tr>
                    <th rowspan="2" class="col-tgl">TANGGAL</th>
                    <th rowspan="2" class="col-sandi">SANDI</th>
                    <th colspan="2">MUTASI</th>
                    <th rowspan="2" class="col-saldo">SALDO</th>
                    <th rowspan="2" class="col-val">VALIDASI<br>(ID)</th>
                </tr>
                <tr>
                    <th class="col-debet">DEBIT</th>
                    <th class="col-kredit">KREDIT</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i < $lineOffset; $i++)
                    <tr>
                        <td class="col-tgl">&nbsp;</td>
                        <td class="col-sandi"></td>
                        <td class="col-debet"></td>
                        <td class="col-kredit"></td>
                        <td class="col-saldo"></td>
                        <td class="col-val"></td>
                    </tr>
                @endfor

                @if ($dariId === 0)
                    <tr>
                        <td class="col-tgl">{{ $rekening->tgl_buka ? \Carbon\Carbon::parse($rekening->tgl_buka)->format('d/m/y') : '-' }}</td>
                        <td class="col-sandi">-</td>
                        <td class="col-debet"></td>
                        <td class="col-kredit"></td>
                        <td class="col-saldo">0</td>
                        <td class="col-val">Saldo Awal</td>
                    </tr>
                @endif

                @forelse ($mutasi as $row)
                    <tr>
                        <td class="col-tgl">{{ \Carbon\Carbon::parse($row->tgl_transaksi)->format('d/m/y') }}</td>
                        <td class="col-sandi">{{ $row->sandi }}</td>
                        <td class="col-debet">{{ $row->debit > 0 ? number_format($row->debit, 0, ',', '.') : '' }}</td>
                        <td class="col-kredit">{{ $row->kredit > 0 ? number_format($row->kredit, 0, ',', '.') : '' }}</td>
                        <td class="col-saldo">{{ number_format($row->saldo_akhir, 0, ',', '.') }}</td>
                        <td class="col-val">{{ $row->kode_teller ?? ('#' . $row->transaksi_id) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">Tidak ada transaksi untuk dicetak.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="sandi-legend">
            Kode Sandi:
            {{ $sandiMap['setoran'] ?? 'S1' }}: Penyetoran |
            {{ $sandiMap['penarikan'] ?? 'S2' }}: Pengambilan |
            {{ $sandiMap['jasa_tabungan'] ?? 'S3' }}: Jasa Tabungan |
            {{ $sandiMap['koreksi'] ?? 'K1' }}: Koreksi
        </div>

        @php
            $totalDebit = (float) $mutasi->sum('debit');
            $totalKredit = (float) $mutasi->sum('kredit');
            $saldoAkhirRekening = (float) $rekening->saldo;
        @endphp
        <table class="passbook-summary">
            <tr>
                <th>Total Debit</th>
                <td>Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Kredit</th>
                <td>Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Keseluruhan (Saldo Akhir)</th>
                <td>Rp {{ number_format($saldoAkhirRekening, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
