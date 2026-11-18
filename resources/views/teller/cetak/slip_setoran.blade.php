<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Setoran - {{ $trx->no_rek }}</title>
    <style>
        @page { size: 160mm 105mm; margin: 6mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; margin: 16px; font-size: 10pt; }
        .no-print { margin-bottom: 12px; display: flex; gap: 8px; }
        .btn-print { padding: 7px 14px; background: #0D5C46; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 12px; }
        .slip-sheet { width: 148mm; min-height: 93mm; padding: 3mm 4mm; border: 1px dashed #CBD5E1; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .brand { display: flex; align-items: center; gap: 8px; }
        .emblem { width: 34px; height: 34px; border: 2px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px; }
        .brand-text .main { font-size: 14pt; font-weight: 800; letter-spacing: 0.4px; line-height: 1.05; }
        .brand-text .sub1 { font-size: 7.5pt; font-weight: 700; }
        .brand-text .sub2 { font-size: 7.5pt; font-weight: 700; }
        .meta-right { font-size: 9pt; min-width: 48mm; }
        .meta-row { display: flex; gap: 4px; margin-bottom: 2px; }
        .meta-label { width: 16mm; }
        .meta-val { flex: 1; border-bottom: 1px dotted #000; padding-left: 2px; }
        .title-box { text-align: center; margin: 6px 0 8px; }
        .title-box span { display: inline-block; background: #000; color: #fff; font-weight: 700; font-size: 10.5pt; padding: 2px 18px; letter-spacing: 0.8px; }
        .field-line { display: flex; align-items: baseline; margin-bottom: 6px; font-size: 9.5pt; }
        .field-line .lbl { white-space: nowrap; margin-right: 6px; }
        .field-line .val { flex: 1; border-bottom: 1px dotted #000; padding: 0 4px 1px; font-weight: 600; }
        .terbilang-box { background: #D1D5DB; border: 1px solid #000; padding: 5px 8px; min-height: 8mm; margin: 6px 0 8px; font-style: italic; font-weight: 600; font-size: 9.5pt; }
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 8px; }
        .ttd-table { border-collapse: collapse; width: 78mm; }
        .ttd-table th, .ttd-table td { border: 1px solid #000; text-align: center; font-size: 8.5pt; }
        .ttd-table th { padding: 2px 4px; font-weight: 600; }
        .ttd-table td { height: 14mm; }
        .penyetor-box { width: 52mm; text-align: center; font-size: 9pt; }
        .penyetor-date { border-bottom: 1px dotted #000; padding-bottom: 1px; margin-bottom: 2px; }
        .penyetor-sign { margin-top: 12mm; border-bottom: 1px dotted #000; padding-bottom: 1px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            .slip-sheet { border: none; padding: 0; width: 100%; }
            .terbilang-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .title-box span { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" type="button" onclick="window.print()">Cetak Slip Setoran</button>
    </div>

    <div class="slip-sheet">
        <div class="header">
            <div class="brand">
                <div class="emblem">BM</div>
                <div class="brand-text">
                    <div class="main">BANK MINI</div>
                    <div class="sub1">WIYATA CIPTA NIAGA</div>
                    <div class="sub2">SMK NEGERI 1 LEMAHABANG</div>
                </div>
            </div>
            <div class="meta-right">
                <div class="meta-row"><span class="meta-label">No. Hp.</span><span>:</span><span class="meta-val">{{ $trx->no_hp ?? '-' }}</span></div>
                <div class="meta-row"><span class="meta-label">Kelas</span><span>:</span><span class="meta-val">{{ $trx->kelas_unit ?? '-' }}</span></div>
            </div>
        </div>

        <div class="title-box"><span>SLIP SETORAN</span></div>

        <div class="field-line">
            <span class="lbl">Telah terima dari</span>
            <span class="val">{{ $trx->nama_nasabah }}</span>
        </div>
        <div class="field-line">
            <span class="lbl">Uang sebesar Rp.</span>
            <span class="val">{{ number_format($nominal, 0, ',', '.') }},-</span>
        </div>

        <div class="terbilang-box"># {{ $terbilang }} #</div>

        <div class="field-line">
            <span class="lbl">Atas keuntungan rekening nomor</span>
            <span class="val">{{ $trx->no_rek }}</span>
        </div>

        <div class="footer">
            <table class="ttd-table">
                <thead>
                    <tr><th>Pembukuan</th><th>Kasir</th><th>Pengawas</th></tr>
                </thead>
                <tbody>
                    <tr><td></td><td style="vertical-align:bottom; font-size:7.5pt; padding-bottom:2px;">{{ $trx->nama_kasir ?? '' }}</td><td></td></tr>
                </tbody>
            </table>

            <div class="penyetor-box">
                <div class="penyetor-date">Lemahabang, {{ \Carbon\Carbon::parse($trx->tgl_transaksi)->translatedFormat('d F Y') }}</div>
                <div>Penyetor,</div>
                <div class="penyetor-sign">{{ $trx->nama_nasabah }}</div>
            </div>
        </div>
    </div>
</body>
</html>
