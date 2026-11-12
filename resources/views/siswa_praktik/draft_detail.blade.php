@extends('layouts.app')

@section('title', 'Detail Draft Transaksi')
@section('breadcrumb', 'Detail Draft')

@section('content')

<div class="content-card" style="max-width: 680px; margin: 0 auto;">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">Detail Draft Transaksi #{{ $draft->draft_id }}</h2>
            <div style="font-size: 12px; color: var(--text-muted);">Informasi lengkap draft simulasi perbankan</div>
        </div>
        <div>
            @if ($draft->status_verifikasi === 'disetujui')
                <span class="badge-pill green">Disetujui</span>
            @elseif ($draft->status_verifikasi === 'ditolak')
                <span class="badge-pill red">Ditolak</span>
            @else
                <span class="badge-pill amber">Pending</span>
            @endif
        </div>
    </div>

    <div style="padding: 24px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Nomor Rekening</div>
                <div style="font-size: 14px; font-weight: 700; color: var(--text-heading); font-family: ui-monospace, SFMono-Regular, monospace; margin-top: 3px;">
                    {{ $draft->rekening->no_rek ?? '-' }}
                </div>
            </div>

            <div>
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Nama Nasabah</div>
                <div style="font-size: 14px; font-weight: 700; color: var(--text-heading); margin-top: 3px;">
                    {{ $draft->rekening->nasabah->nama_nasabah ?? '-' }}
                </div>
            </div>

            <div>
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Jenis Transaksi</div>
                <div style="font-size: 13.5px; font-weight: 700; color: var(--text-heading); margin-top: 3px; text-transform: capitalize;">
                    {{ $draft->jenisTransaksi->nama_jenis ?? '-' }}
                </div>
            </div>

            <div>
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Nominal Transaksi</div>
                <div style="font-size: 16px; font-weight: 800; color: #0D5C46; font-family: ui-monospace, SFMono-Regular, monospace; margin-top: 3px;">
                    Rp {{ number_format($draft->nominal, 0, ',', '.') }}
                </div>
            </div>

            <div>
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Waktu Input</div>
                <div style="font-size: 13px; color: var(--text-body); margin-top: 3px;">
                    {{ $draft->tgl_input ? $draft->tgl_input->format('d/m/Y H:i:s') : '-' }} WIB
                </div>
            </div>

            <div>
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Petugas Teller</div>
                <div style="font-size: 13px; color: var(--text-body); margin-top: 3px;">
                    {{ $draft->teller->nama_user ?? 'Belum diverifikasi' }}
                </div>
            </div>
        </div>

        @if ($draft->status_verifikasi === 'ditolak')
            <div id="alasan-penolakan" style="margin-bottom: 24px; padding: 14px 16px; border-radius: 10px; background: #FEF2F2; border: 1px solid #FECACA;">
                <div style="font-size: 11px; color: #991B1B; font-weight: 700; text-transform: uppercase;">Alasan Penolakan</div>
                <div style="font-size: 13.5px; color: #7F1D1D; margin-top: 4px;">
                    {{ $draft->alasan_penolakan ?: 'Tidak ada alasan yang dicatat.' }}
                </div>
            </div>
        @endif

        <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border-subtle);">
            <a href="{{ route('tabungan.draft.riwayat') }}" class="btn-ui btn-ui-outline" style="text-decoration: none; display: inline-flex; align-items: center;">
                Kembali ke Riwayat
            </a>
            <a href="{{ route('tabungan.draft') }}" class="btn-ui btn-ui-primary" style="text-decoration: none; display: inline-flex; align-items: center;">
                Input Draft Baru
            </a>
        </div>
    </div>
</div>

@endsection
