@extends('layouts.app')

@section('title', 'Mengarahkan ke Rencana Potongan...')

@section('content')
<div style="padding: 60px 20px; text-align: center;">
    <div style="display: inline-block; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 36px 48px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 520px; width: 100%;">
        <div style="width: 48px; height: 48px; border-radius: 50%; background: #ECFDF5; border: 1px solid #A7F3D0; color: #047857; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div style="font-size: 16px; font-weight: 800; color: var(--text-heading); margin-bottom: 6px;">
            Membuka Pengelolaan Potongan Pegawai
        </div>
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">
            Formulir pencatatan hasil kini terintegrasi langsung dalam floating banner popup. Mengarahkan otomatis...
        </div>
        <a href="{{ route('bendahara.potongan.index', ['open' => $rencana->rencana_potongan_id]) }}" class="btn-ui btn-ui-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <span>Buka Sekarang</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    </div>
</div>

<script>
    // Langsung redirect ke popup banner di halaman index
    window.location.replace("{{ route('bendahara.potongan.index', ['open' => $rencana->rencana_potongan_id]) }}");
</script>
@endsection
