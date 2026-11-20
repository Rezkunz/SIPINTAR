<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Penutupan Rekening {{ $rekening->no_rek }}</title>
    <style>
        @page { size: A5 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; font-size: 12.5px; }
        .kop { text-align: center; border-bottom: 2px solid #0D5C46; padding-bottom: 8px; margin-bottom: 16px; }
        .kop h1 { margin: 0; font-size: 15px; color: #0D5C46; text-transform: uppercase; }
        .kop .sub { font-size: 11px; color: #475569; margin-top: 2px; }
        .title { text-align: center; font-size: 14px; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.info td { padding: 6px 8px; border: 1px solid #CBD5E1; }
        table.info td.label { width: 35%; background: #F8FAFC; font-weight: 600; }
        .ttd { width: 100%; margin-top: 24px; page-break-inside: avoid; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .ttd .space { height: 50px; }
        .print-button { margin-bottom: 16px; padding: 8px 16px; cursor: pointer; background: #0D5C46; color: #fff; border: none; border-radius: 6px; font-weight: 600; }
        @media print { .print-button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="print-button" type="button" onclick="window.print()">Cetak Bukti Penutupan</button>

    <div class="kop">
        <h1>Bank Mini Wiyata Cipta Niaga</h1>
        <div class="sub">SMK Negeri 1 Lemahabang</div>
    </div>

    <div class="title">Bukti Penutupan Rekening Tabungan</div>

    <table class="info">
        <tr><td class="label">Nomor Rekening</td><td>{{ $rekening->no_rek }}</td></tr>
        <tr><td class="label">Nama Nasabah</td><td>{{ $rekening->nasabah->nama_nasabah ?? '-' }}</td></tr>
        <tr><td class="label">Peran / Kelas</td><td>{{ $rekening->nasabah?->jenis_nasabah === 'pegawai' ? 'Pegawai' : 'Siswa' }}{{ $rekening->nasabah?->kelas_unit ? ' / ' . $rekening->nasabah->kelas_unit : '' }}</td></tr>
        <tr><td class="label">Status Rekening</td><td><strong>TUTUP</strong></td></tr>
        <tr><td class="label">Sisa Saldo Dicairkan</td><td><strong>Rp {{ number_format((float) ($dataLama['saldo'] ?? session('saldo_dicairkan', 0)), 0, ',', '.') }}</strong></td></tr>
        <tr><td class="label">Alasan Penutupan</td><td>{{ $dataBaru['alasan'] ?? '-' }}</td></tr>
        <tr><td class="label">Tanggal Penutupan</td><td>{{ $audit->created_at ?? now()->format('Y-m-d H:i:s') }} WIB</td></tr>
        <tr><td class="label">Petugas Teller</td><td>{{ $audit->nama_teller ?? (auth()->user()->nama_user ?? 'Teller') }}</td></tr>
    </table>

    <table class="ttd">
        <tr>
            <td>
                <div>Nasabah,</div>
                <div class="space"></div>
                <div>( {{ $rekening->nasabah->nama_nasabah ?? '....................' }} )</div>
            </td>
            <td>
                <div>Lemahabang, {{ now()->translatedFormat('d F Y') }}</div>
                <div>Teller,</div>
                <div class="space"></div>
                <div>( {{ $audit->nama_teller ?? (auth()->user()->nama_user ?? 'Teller') }} )</div>
            </td>
        </tr>
    </table>
</body>
</html>
