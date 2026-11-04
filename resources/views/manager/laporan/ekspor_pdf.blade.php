@php
    use App\Services\EksporLaporanService as Ekspor;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $dok['judul'] }}</title>
    <style>
        @page { margin: 18mm 12mm 16mm 12mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: {{ $dok['ukuran_font'] }}pt; color: #0F172A; }
        .kop { text-align: center; border-bottom: 2.5px double #0D5C46; padding-bottom: 6px; margin-bottom: 12px; }
        .kop .lembaga { font-size: 13pt; font-weight: bold; color: #0D5C46; letter-spacing: 0.5px; }
        .kop .sekolah { font-size: 10.5pt; font-weight: bold; }
        .kop .alamat { font-size: 7.5pt; color: #64748B; margin-top: 2px; }
        .judul { text-align: center; margin-bottom: 12px; }
        .judul .utama { font-size: 11.5pt; font-weight: bold; text-transform: uppercase; }
        .judul .sub { font-size: 9pt; color: #334155; margin-top: 2px; }
        .bagian-judul { font-weight: bold; color: #0D5C46; margin: 12px 0 4px; font-size: {{ $dok['ukuran_font'] + 0.5 }}pt; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.data th { background: #0D5C46; color: #FFFFFF; font-weight: bold; padding: 4px 3px; border: 0.6px solid #0D5C46; text-align: center; }
        table.data th.grup { background: #0A4A38; }
        table.data td { padding: 3px 3px; border: 0.6px solid #CBD5E1; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #F8FAFC; }
        table.data td.angka { text-align: right; white-space: nowrap; }
        table.data td.tengah { text-align: center; }
        table.data tr.grup td { background: #ECFDF5; font-weight: bold; color: #0D5C46; }
        table.data tr.subtotal td { font-weight: bold; border-top: 1px solid #94A3B8; }
        table.data tr.total td { font-weight: bold; background: #FFFBEB; border-top: 1.5px solid #0F172A; border-bottom: 2.5px double #0F172A; }
        .catatan { font-size: {{ $dok['ukuran_font'] - 1 }}pt; color: #64748B; font-style: italic; margin-bottom: 6px; }
        .ttd { width: 100%; margin-top: 16px; page-break-inside: avoid; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .ttd .nama { margin-top: 40px; }
        .kaki-halaman { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #94A3B8; text-align: right; }
    </style>
</head>
<body>
    <div class="kaki-halaman">Dicetak dari SIPINTAR pada {{ $dok['dicetak']->translatedFormat('d F Y H:i') }}</div>

    <div class="kop">
        <div class="lembaga">{{ Ekspor::NAMA_LEMBAGA }}</div>
        <div class="sekolah">{{ Ekspor::NAMA_SEKOLAH }}</div>
        <div class="alamat">{{ Ekspor::ALAMAT }}</div>
    </div>

    <div class="judul">
        <div class="utama">{{ $dok['judul'] }}</div>
        @if ($dok['subjudul'])
            <div class="sub">{{ $dok['subjudul'] }}</div>
        @endif
    </div>

    @foreach ($dok['bagian'] as $bagian)
        @if (!empty($bagian['judul']))
            <div class="bagian-judul">{{ $bagian['judul'] }}</div>
        @endif
        <table class="data">
            <thead>
                @if (!empty($bagian['grup_kolom']))
                    <tr>
                        @foreach ($bagian['grup_kolom'] as $grup)
                            <th class="grup" colspan="{{ $grup['span'] ?? 1 }}">{{ $grup['label'] }}</th>
                        @endforeach
                    </tr>
                @endif
                <tr>
                    @foreach ($bagian['kolom'] as $k)
                        <th>{{ $k['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if (empty($bagian['baris']))
                    <tr><td colspan="{{ count($bagian['kolom']) }}" class="tengah" style="color:#64748B; font-style:italic;">{{ Ekspor::TEKS_KOSONG }}</td></tr>
                @endif
                @foreach (array_merge($bagian['baris'], $bagian['kaki'] ?? []) as $r)
                    <tr class="{{ $r['gaya'] }}">
                        @foreach ($r['sel'] as $i => $nilai)
                            @php
                                $k = $bagian['kolom'][$i] ?? [];
                                $kelas = !empty($k['angka']) ? 'angka' : match ($k['align'] ?? null) {
                                    'center' => 'tengah',
                                    'right'  => 'angka',
                                    default  => '',
                                };
                            @endphp
                            <td class="{{ $kelas }}">{{ !empty($k['angka']) ? Ekspor::formatAngka($nilai) : $nilai }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if (!empty($bagian['catatan']))
            <div class="catatan">{{ $bagian['catatan'] }}</div>
        @endif
    @endforeach

    @if ($dok['tanda_tangan'])
        <table class="ttd">
            <tr>
                <td>&nbsp;<br>Manager</td>
                <td>Lemahabang, {{ $dok['dicetak']->translatedFormat('d F Y') }}<br>Bendahara</td>
            </tr>
            <tr>
                <td><div class="nama">(................................)</div></td>
                <td><div class="nama">(................................)</div></td>
            </tr>
        </table>
    @endif
</body>
</html>
