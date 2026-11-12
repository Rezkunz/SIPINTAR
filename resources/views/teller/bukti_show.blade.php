@extends('layouts.app')

@section('title', 'Cetak Bukti')
@section('breadcrumb', 'Cetak Bukti')

@section('content')
<div class="page-header"><h1 class="page-title">Bukti Transaksi</h1><p class="page-subtitle">Bukti dapat dicetak ulang dari halaman ini.</p></div>
<div class="receipt-preview-box">
    <div class="receipt-header"><div class="receipt-title">SIPINTAR</div><div>Bank Mini Sekolah</div><div>{{ $bukti->no_bukti }}</div></div>
    @php
        $nominalBukti = (float) $bukti->debit > 0 ? (float) $bukti->debit : (float) $bukti->kredit;
        $jenisBukti = ['setoran' => 'Setoran', 'penarikan' => 'Penarikan', 'jasa_tabungan' => 'Jasa Tabungan'][$bukti->nama_jenis] ?? ucfirst($bukti->nama_jenis);
    @endphp
    <div class="receipt-item-row"><span>No. Transaksi</span><span dusk="bukti-no-transaksi">#{{ $bukti->transaksi_id }}</span></div>
    <div class="receipt-item-row"><span>Tanggal</span><span dusk="bukti-tanggal">{{ \Illuminate\Support\Carbon::parse($bukti->tgl_transaksi)->format('d/m/Y H:i:s') }} WIB</span></div>
    <div class="receipt-item-row"><span>Nasabah</span><span>{{ $bukti->nama_nasabah }}</span></div>
    <div class="receipt-item-row"><span>Rekening</span><span dusk="bukti-rekening">{{ $bukti->no_rek }}</span></div>
    <div class="receipt-item-row"><span>Jenis</span><span dusk="bukti-jenis">{{ $jenisBukti }} ({{ (float) $bukti->debit > 0 ? 'Debit' : 'Kredit' }})</span></div>
    <div class="receipt-item-row"><span>Nominal</span><span dusk="bukti-nominal">Rp {{ number_format($nominalBukti, 0, ',', '.') }}</span></div>
    <div class="receipt-total-row"><span>Saldo Akhir</span><span dusk="bukti-saldo-akhir">Rp {{ number_format((float) $bukti->saldo_akhir, 0, ',', '.') }}</span></div>
</div>
<div style="text-align:center;"><button class="btn-ui btn-ui-primary" type="button" id="btn-cetak-bukti" onclick="window.print()">Cetak Bukti</button></div>
@endsection