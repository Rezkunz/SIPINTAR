@extends('layouts.app')

@section('title', 'Hitung Bunga Tabungan')
@section('breadcrumb', 'Hitung Bunga Tabungan')

@section('content')
<div class="page-header">
    <h1 class="page-title">Kalkulator Bunga Tabungan</h1>
    <p class="page-subtitle">Pilih satu rekening untuk menghitung bunga dari saldo saat ini.</p>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; margin-bottom: 20px;">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; margin-bottom: 20px;">{{ $errors->first() }}</div>
@endif

<div class="content-card" style="max-width: 760px;">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Hitung Bunga</h2>
            <div style="font-size: 12px; color: var(--text-muted);">Siswa 0,1% per bulan, pegawai 0,2% per bulan.</div>
        </div>
    </div>
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.jasa') }}" style="display: flex; gap: 10px; align-items: end;">
            <div style="flex: 1;">
                <label for="no_rek">Rekening aktif</label>
                <select id="no_rek" name="no_rek" class="form-input" required>
                    <option value="">Pilih rekening</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->no_rek }}" @selected(request('no_rek') === $account->no_rek)>
                            {{ $account->no_rek }} - {{ $account->nama_nasabah }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-ui btn-ui-outline">Hitung</button>
        </form>

        @if ($selected)
            <div style="margin-top: 24px; padding: 18px; background: #F8FAFC; border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div><small>Nasabah</small><strong style="display:block;">{{ $selected->nama_nasabah }}</strong></div>
                    <div><small>Kategori</small><strong style="display:block;">{{ $selected->kategori_bunga === 'pegawai' ? 'Pegawai' : 'Siswa' }}</strong></div>
                    <div><small>Saldo saat ini</small><strong style="display:block;">Rp {{ number_format($selected->saldo, 0, ',', '.') }}</strong></div>
                    <div><small>Persentase bunga</small><strong style="display:block;">{{ number_format($selected->persen_bunga, 1) }}% / bulan</strong></div>
                    <div><small>Nominal bunga</small><strong style="display:block; color: #047857;">Rp {{ number_format($selected->nominal_bunga, 0, ',', '.') }}</strong></div>
                    <div><small>Saldo setelah bunga</small><strong style="display:block;">Rp {{ number_format($selected->saldo_setelah_bunga, 0, ',', '.') }}</strong></div>
                </div>
                <form method="POST" action="{{ route('tabungan.jasa.process', $selected->rekening_id) }}" style="margin-top: 20px;">
                    @csrf
                    <button type="submit" class="btn-ui btn-ui-primary">Proses Bunga Rekening Ini</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
