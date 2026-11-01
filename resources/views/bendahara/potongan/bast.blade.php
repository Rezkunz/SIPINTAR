<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Serah Terima Potongan Gaji - #RP-{{ str_pad($rencana->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
            background: #f1f5f9;
            margin: 0;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        .bast-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .header-kop {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 24px;
            gap: 20px;
        }
        .header-logo {
            width: 70px;
            height: 70px;
            background: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            border-radius: 8px;
        }
        .header-title h2 {
            margin: 0 0 4px 0;
            font-size: 18px;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-title h3 {
            margin: 0 0 4px 0;
            font-size: 14px;
            color: #334155;
            font-weight: 600;
        }
        .header-title p {
            margin: 0;
            font-size: 11px;
            color: #64748b;
        }
        .doc-title {
            text-align: center;
            margin: 24px 0;
        }
        .doc-title h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-title .doc-number {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            font-family: monospace;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .info-grid td.label {
            width: 200px;
            font-weight: 600;
            color: #475569;
        }
        .info-grid td.sep {
            width: 15px;
            text-align: center;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }
        .data-table th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 700;
            text-align: left;
        }
        .data-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 240px;
        }
        .signature-box .role {
            font-weight: 600;
            margin-bottom: 60px;
        }
        .signature-box .name {
            font-weight: 700;
            text-decoration: underline;
        }
        .signature-box .nip {
            font-size: 11px;
            color: #64748b;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background: #0369a1;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .bast-container {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Dokumen Berita Acara</button>
</div>

<div class="bast-container">
    <div class="header-kop">
        <div class="header-logo">BM</div>
        <div class="header-title">
            <h2>Bank Mini Wiyata Cipta Niaga</h2>
            <h3>SMK Negeri 1 Lemahabang — Cirebon</h3>
            <p>Jl. KH. Wahid Hasyim No. 9 Lemahabang, Kab. Cirebon, Jawa Barat • Telp: (0231) 635xxx</p>
        </div>
    </div>

    <div class="doc-title">
        <h1>Berita Acara Serah Terima (BAST)</h1>
        <div class="doc-title doc-number">Nomor: BAST-POT/{{ $rencana->tahun }}/{{ str_pad($rencana->bulan, 2, '0', STR_PAD_LEFT) }}/{{ str_pad($rencana->rencana_potongan_id, 4, '0', STR_PAD_LEFT) }}</div>
    </div>

    <p>Pada hari ini, tanggal <strong>{{ \Carbon\Carbon::parse($rencana->tgl_potongan ?? now())->translatedFormat('l, d F Y') }}</strong>, telah dilakukan serah terima penerimaan dana hasil pemotongan tunjangan/gaji pegawai untuk kewajiban angsuran pinjaman Bank Mini, antara:</p>

    <table class="info-grid">
        <tr>
            <td class="label">Pihak Pertama (Penyerah)</td>
            <td class="sep">:</td>
            <td><strong>Bendahara Sekolah (Pengelola Payroll & Tunjangan)</strong></td>
        </tr>
        <tr>
            <td class="label">Pihak Kedua (Penerima)</td>
            <td class="sep">:</td>
            <td><strong>Bendahara Bank Mini Wiyata Cipta Niaga</strong> ({{ $rencana->nama_pembuat_bbm ?? 'Petugas Bank Mini' }})</td>
        </tr>
        <tr>
            <td class="label">Periode Potongan</td>
            <td class="sep">:</td>
            <td><strong>Bulan {{ \Carbon\Carbon::createFromDate($rencana->tahun, $rencana->bulan, 1)->translatedFormat('F Y') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Metode Penyerahan Dana</td>
            <td class="sep">:</td>
            <td><strong>{{ strtoupper($rencana->metode_penyerahan ?? 'TUNAI') }}</strong> ({{ $rencana->metode_penyerahan === 'transfer' ? 'Kas Rekening Bank Giro' : 'Kas Harian Tunai' }})</td>
        </tr>
        <tr>
            <td class="label">Total Nominal Diserahkan</td>
            <td class="sep">:</td>
            <td><strong style="font-size:14px; color:#0f172a;">Rp {{ number_format($rencana->total_realisasi, 0, ',', '.') }}</strong></td>
        </tr>
        @if ($rencana->catatan_sekolah)
        <tr>
            <td class="label">Catatan Serah Terima</td>
            <td class="sep">:</td>
            <td><em>{{ $rencana->catatan_sekolah }}</em></td>
        </tr>
        @endif
    </table>

    <div style="font-weight:700; margin-bottom:8px;">Rincian Pegawai & Realisasi Pemotongan:</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px; text-align:center;">No</th>
                <th>Nama Pegawai</th>
                <th>Unit / Jabatan</th>
                <th style="text-align:center;">Tenor</th>
                <th style="text-align:right;">Pokok</th>
                <th style="text-align:right;">Jasa 2%</th>
                <th style="text-align:right;">Total Potongan</th>
                <th style="text-align:center;">Hasil Realisasi</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($detailList as $item)
                <tr>
                    <td style="text-align:center;">{{ $no++ }}</td>
                    <td><strong>{{ $item->nama_nasabah }}</strong></td>
                    <td>{{ $item->kelas_unit ?? '-' }}</td>
                    <td style="text-align:center;">Ke-{{ $item->angsuran_ke }}</td>
                    <td style="text-align:right;">Rp {{ number_format($item->pokok, 0, ',', '.') }}</td>
                    <td style="text-align:right;">Rp {{ number_format($item->bunga, 0, ',', '.') }}</td>
                    <td style="text-align:right; font-weight:700;">Rp {{ number_format($item->nominal_rencana, 0, ',', '.') }}</td>
                    <td style="text-align:center;">
                        @if ($item->hasil_potongan === 'berhasil')
                            <span style="color:#166534; font-weight:700;">✓ Berhasil</span>
                        @else
                            <span style="color:#991b1b; font-weight:700;">✕ Gagal</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#f8fafc; font-weight:800;">
                <td colspan="6" style="text-align:right;">TOTAL REALISASI DANA DITERIMA:</td>
                <td style="text-align:right; color:#166534;">Rp {{ number_format($rencana->total_realisasi, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <p style="font-size:12px; margin-top:20px;">Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya dan sah, untuk dipergunakan sebagai bukti pertanggungjawaban pembukuan kas dan pengurangan saldo piutang pegawai pada sistem SIPINTAR.</p>

    <div class="signatures">
        <div class="signature-box">
            <div class="role">Pihak Pertama (Penyerah)<br>Bendahara Sekolah,</div>
            <div class="name">( ............................................ )</div>
            <div class="nip">NIP. ........................................</div>
        </div>

        <div class="signature-box">
            <div class="role">Pihak Kedua (Penerima)<br>Bendahara Bank Mini,</div>
            <div class="name">{{ $rencana->nama_pembuat_bbm ?? '( ............................................ )' }}</div>
            <div class="nip">ID Staf: BBM-001</div>
        </div>
    </div>
</div>

</body>
</html>

