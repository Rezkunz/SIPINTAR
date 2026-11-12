@extends('layouts.app')

@section('title', 'Log Transaksi Rekening')
@section('breadcrumb', 'Log Transaksi')

@section('content')
<div class="page-header">
    <h1 class="page-title">Log Transaksi Rekening</h1>
    <p class="page-subtitle">Lihat histori transaksi atau gunakan mode koreksi untuk transaksi yang salah.</p>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; margin-bottom: 20px;">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

<div class="content-card">
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.mutasi') }}" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px;">
                <label for="no_rek">Nomor rekening</label>
                <input id="no_rek" class="form-input" name="no_rek" value="{{ request('no_rek') }}" placeholder="Masukkan nomor rekening" required>
            </div>
            <input type="hidden" name="mode" value="{{ $mode }}">
            <button class="btn-ui btn-ui-primary" type="submit">Cari Log</button>
        </form>

        @if ($rekening)
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:20px;">
                <div style="flex:1;"><strong>{{ $rekening->nasabah->nama_nasabah ?? '-' }}</strong>
                    @if ($rekening->status_rekening === 'tutup')<span class="badge-pill red" dusk="status-rekening-tutup" style="margin-left: 6px;">Tertutup</span>@endif<div style="font-size:12px;color:var(--text-muted);">{{ $rekening->no_rek }} | Saldo Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</div></div>
                <a class="btn-ui btn-ui-outline btn-ui-sm" href="{{ route('tabungan.mutasi', ['no_rek' => $rekening->no_rek, 'mode' => 'lihat']) }}">Lihat Mutasi</a>
                @if ($rekening->status_rekening !== 'tutup')
                    <a class="btn-ui btn-ui-outline btn-ui-sm" href="{{ route('tabungan.mutasi', ['no_rek' => $rekening->no_rek, 'mode' => 'koreksi']) }}">Mode Koreksi</a>
                @endif
                <a class="btn-ui btn-ui-primary btn-ui-sm" target="_blank" href="{{ route('tabungan.mutasi.cetak', ['no_rek' => $rekening->no_rek]) }}">Cetak Log</a>
            </div>
        @endif
    </div>
</div>

@if ($rekening)
<div class="content-card" style="margin-top:20px;">
    <div class="card-header-bar"><h2 class="card-heading">{{ $mode === 'koreksi' ? 'Koreksi Transaksi' : 'Riwayat Mutasi' }}</h2></div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead><tr><th>Tanggal</th><th>Jenis</th><th style="text-align:right;">Debit</th><th style="text-align:right;">Kredit</th><th style="text-align:right;">Saldo</th>@if ($mode === 'koreksi')<th>Koreksi</th>@endif</tr></thead>
            <tbody>
                @forelse ($mutasi as $transaksi)
                    <tr>
                        <td>{{ $transaksi->tgl_transaksi }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $transaksi->nama_jenis)) }}</td>
                        <td style="text-align:right;">Rp {{ number_format($transaksi->debit, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($transaksi->kredit, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($transaksi->saldo_akhir, 0, ',', '.') }}</td>
                        @if ($mode === 'koreksi')
                            @php
                                $statusKoreksi = $sudahDikoreksi[(int) $transaksi->transaksi_id] ?? null;
                                $periodeTerkunci = ($transaksi->status_periode ?? null) === 'tutup';
                                $idBaris = $transaksi->transaksi_id;
                            @endphp
                            <td style="min-width: 160px;">
                                @if ($statusKoreksi === 'dikoreksi')
                                    <span class="badge-pill amber">Sudah dikoreksi</span>
                                @elseif ($statusKoreksi === 'pembalik')
                                    <span class="badge-pill amber">Transaksi pembalik</span>
                                @elseif ($statusKoreksi === 'hasil')
                                    <span class="badge-pill green">Hasil koreksi</span>
                                @else
                                    <button class="btn-ui btn-ui-outline btn-ui-sm" type="button" dusk="ajukan-koreksi-{{ $idBaris }}"
                                        onclick="document.getElementById('koreksi-{{ $idBaris }}').style.display = 'block'; this.style.display = 'none';">
                                        Ajukan Koreksi
                                    </button>

                                    @if ($periodeTerkunci)
                                        <div id="koreksi-{{ $idBaris }}" dusk="koreksi-terkunci-{{ $idBaris }}" style="display: none; padding: 10px 12px; border-radius: 8px; background: #FEF2F2; color: #991B1B; font-size: 12.5px; max-width: 320px;">
                                            {{ \App\Services\TellerCorrectionService::pesanPeriodeTerkunci((int) $transaksi->periode_bulan, (int) $transaksi->periode_tahun) }}
                                        </div>
                                    @else
                                        @php $bukaLagi = (int) old('transaksi_id') === (int) $idBaris; @endphp
                                        <form method="POST" action="{{ route('tabungan.koreksi.process', $idBaris) }}" id="koreksi-{{ $idBaris }}" dusk="form-koreksi-{{ $idBaris }}"
                                            style="display: {{ $bukaLagi ? 'grid' : 'none' }}; grid-template-columns: 140px 120px 190px auto; gap: 6px; min-width: 500px;">
                                            @csrf
                                            <input type="hidden" name="no_rek" value="{{ $rekening->no_rek }}">
                                            <input type="hidden" name="transaksi_id" value="{{ $idBaris }}">
                                            <select class="form-input" name="jenis_transaksi_id" required>
                                                @foreach ($types as $type)
                                                    <option value="{{ $type->jenis_transaksi_id }}" @selected((int) ($bukaLagi ? old('jenis_transaksi_id') : $transaksi->jenis_transaksi_id) === (int) $type->jenis_transaksi_id)>{{ ucfirst(str_replace('_', ' ', $type->nama_jenis)) }}</option>
                                                @endforeach
                                            </select>
                                            <input class="form-input" name="nominal" type="number" min="0.01" step="0.01" value="{{ $bukaLagi ? old('nominal') : ($transaksi->debit > 0 ? $transaksi->debit : $transaksi->kredit) }}" required>
                                            <input class="form-input" name="alasan" maxlength="500" placeholder="Alasan koreksi" value="{{ $bukaLagi ? old('alasan') : '' }}">
                                            <button class="btn-ui btn-ui-primary btn-ui-sm" type="submit">Simpan</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $mode === 'koreksi' ? 6 : 5 }}"><div class="empty-placeholder">Belum ada transaksi.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($mutasi instanceof \Illuminate\Pagination\LengthAwarePaginator)<div style="padding: 16px 20px;">{{ $mutasi->links() }}</div>@endif
</div>
@endif
@endsection
