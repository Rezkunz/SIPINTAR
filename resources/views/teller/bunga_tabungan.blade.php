@extends('layouts.app')

@section('title', 'Kalkulator Bunga Tabungan')
@section('breadcrumb', 'Kalkulator Bunga Tabungan')

@section('content')
<div class="page-header">
    <h1 class="page-title">Kalkulator Bunga Tabungan</h1>
    <p class="page-subtitle">Simulasi perhitungan bunga tabungan bulanan dan pemantauan status pembukuan rekening.</p>
</div>

<div class="content-card" style="padding: 14px 18px; background: #F0F9FF; border: 1px solid #BAE6FD; color: #0369A1; margin-bottom: 20px; border-radius: var(--radius-md);">
    <div style="display: flex; align-items: flex-start; gap: 10px;">
        <svg class="nav-svg-icon" style="flex-shrink: 0; margin-top: 2px; stroke: #0284C7;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <div style="font-size: 13px; line-height: 1.5;">
            <strong>Informasi Pembukuan Otomatis:</strong> Kredit bunga tabungan diproses secara otomatis oleh sistem terjadwal pada akhir setiap bulan. Teller dapat menggunakan halaman ini untuk melakukan simulasi kalkulasi dan memeriksa status pembukuan bunga rekening.
        </div>
    </div>
</div>

@if (session('success'))
    <div class="content-card" style="padding: 14px; color: #065F46; background: #ECFDF5; border: 1px solid #A7F3D0; margin-bottom: 20px; border-radius: var(--radius-md);">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="content-card" style="padding: 14px; color: #991B1B; background: #FEF2F2; border: 1px solid #FECACA; margin-bottom: 20px; border-radius: var(--radius-md);">
        {{ $errors->first() }}
    </div>
@endif

<div class="content-card" style="max-width: 820px;">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Kalkulasi Bunga</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                Tarif bulanan: Siswa atau non-pegawai 0,1% per bulan, Pegawai 0,2% per bulan.
            </div>
        </div>
    </div>
    <div class="card-body-content">
        <form method="GET" action="{{ route('tabungan.jasa') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 260px;">
                <label for="no_rek" style="display: block; font-weight: 500; margin-bottom: 6px;">Pilih Rekening Aktif</label>
                <select id="no_rek" name="no_rek" class="form-input" style="min-height: 44px; width: 100%;" required>
                    <option value="">-- Pilih nomor rekening --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->no_rek }}" @selected(request('no_rek') === $account->no_rek)>
                            {{ $account->no_rek }} - {{ $account->nama_nasabah }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-ui btn-ui-primary" style="min-height: 44px; min-width: 100px; display: inline-flex; align-items: center; justify-content: center;">
                Hitung
            </button>
        </form>

        @if ($selected)
            <div style="margin-top: 28px; padding: 20px; background: #F8FAFC; border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border-subtle);">
                    <div>
                        <span style="font-size: 13px; color: var(--text-muted); display: block;">Status Pembukuan Periode {{ $selected->periode_label }}</span>
                        <strong style="font-size: 15px; color: var(--text-main);">
                            {{ $selected->status_bunga_periode ? 'Bunga telah dibukukan' : 'Bunga belum dibukukan' }}
                        </strong>
                    </div>
                    <div>
                        @if ($selected->status_bunga_periode)
                            <span class="badge-pill green" style="padding: 6px 12px; font-weight: 600;">Sudah Dibukukan</span>
                        @else
                            <span class="badge-pill amber" style="padding: 6px 12px; font-weight: 600;">Belum Dibukukan</span>
                        @endif
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                    <div>
                        <small style="color: var(--text-muted); display: block;">Nama Nasabah</small>
                        <strong style="display: block; font-size: 14px; margin-top: 2px;">{{ $selected->nama_nasabah }}</strong>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); display: block;">Nomor Rekening</small>
                        <strong style="display: block; font-size: 14px; margin-top: 2px; font-family: monospace;">{{ $selected->no_rek }}</strong>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); display: block;">Kategori Nasabah</small>
                        <strong style="display: block; font-size: 14px; margin-top: 2px;">{{ $selected->kategori_label }}</strong>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); display: block;">Tarif Bunga</small>
                        <strong style="display: block; font-size: 14px; margin-top: 2px;">{{ number_format($selected->persen_bunga, 1, ',', '.') }}% / bulan</strong>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); display: block;">Saldo Saat Ini</small>
                        <strong style="display: block; font-size: 15px; margin-top: 2px;">Rp {{ number_format($selected->saldo, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); display: block;">Nominal Bunga Bulan Ini</small>
                        <strong style="display: block; font-size: 15px; margin-top: 2px; color: #047857;">+ Rp {{ number_format($selected->nominal_bunga, 0, ',', '.') }}</strong>
                    </div>
                    <div style="grid-column: 1 / -1; padding-top: 12px; border-top: 1px dashed var(--border-subtle);">
                        <small style="color: var(--text-muted); display: block;">Proyeksi Saldo Setelah Bunga</small>
                        <strong style="display: block; font-size: 18px; margin-top: 2px; color: #0F766E;">Rp {{ number_format($selected->saldo_setelah_bunga, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div style="margin-top: 16px; font-size: 12px; color: var(--text-muted);">
                    * Nominal bunga dihitung dari saldo saat ini dikalikan tarif bulanan kategori dengan pembulatan ke rupiah terdekat.
                </div>
            </div>
        @elseif (request('no_rek'))
            <div style="margin-top: 24px; padding: 24px; text-align: center; background: #FEF2F2; border: 1px solid #FECACA; border-radius: var(--radius-md); color: #991B1B;">
                <p style="margin: 0; font-weight: 500;">Rekening dengan nomor "{{ request('no_rek') }}" tidak ditemukan atau tidak berstatus aktif.</p>
            </div>
        @else
            <div style="margin-top: 24px; padding: 32px 20px; text-align: center; background: #F8FAFC; border: 1px dashed var(--border-subtle); border-radius: var(--radius-md); color: var(--text-muted);">
                <svg class="nav-svg-icon" style="width: 36px; height: 36px; stroke: var(--text-muted); margin: 0 auto 10px;" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                <p style="margin: 0; font-size: 14px; font-weight: 500; color: var(--text-main);">Belum ada rekening yang dipilih</p>
                <p style="margin: 4px 0 0; font-size: 13px;">Silakan pilih salah satu rekening aktif dari daftar di atas, kemudian klik tombol <strong>Hitung</strong>.</p>
            </div>
        @endif
    </div>
</div>
@endsection
