@extends('layouts.app')

@section('title', 'Cetak Bukti')
@section('breadcrumb', 'Cetak Bukti')

@section('content')
<div class="page-header"><h1 class="page-title">Bukti Transaksi</h1><p class="page-subtitle">Bukti dapat dicetak ulang dari halaman ini.</p></div>
<div class="receipt-preview-box">
    <div class="receipt-header"><div class="receipt-title">SIPINTAR</div><div>Bank Mini Sekolah</div><div>{{ $bukti->no_bukti }}</div></div>
    <div class="receipt-item-row"><span>Nasabah</span><span>{{ $bukti->nama_nasabah }}</span></div>
    <div class="receipt-item-row"><span>Rekening</span><span>{{ $bukti->no_rek }}</span></div>
    <div class="receipt-item-row"><span>Jenis</span><span>{{ ucfirst($bukti->nama_jenis) }}</span></div>
    <div class="receipt-item-row"><span>Waktu</span><span>{{ $bukti->tgl_transaksi }}</span></div>
    <div class="receipt-total-row"><span>Total</span><span>Rp {{ number_format($bukti->nama_jenis === 'penarikan' ? $bukti->debit : $bukti->kredit, 0, ',', '.') }}</span></div>
</div>
<div style="text-align:center;"><button class="btn-ui btn-ui-primary" type="button" onclick="window.print()">Cetak Bukti</button></div>
@endsection