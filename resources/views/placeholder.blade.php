@extends('layouts.app')

@section('title', $title ?? 'Halaman')
@section('breadcrumb', $title ?? 'Halaman')

@section('content')

<div class="page-header">
    <h1 class="page-title">{{ $title ?? 'Halaman' }}</h1>
    <p class="page-subtitle">{{ $subtitle ?? 'Modul Bank Mini Sekolah' }}</p>
</div>

<div class="content-card">
    <div class="card-header-bar">
        <h2 class="card-heading">{{ $title ?? 'Data' }}</h2>
    </div>
    <div class="card-body-content">
        <div class="empty-placeholder" style="padding: 48px 16px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--brand-primary);margin-bottom:12px;"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4"/></svg>
            <div style="font-size: 14px; font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">
                {{ $title ?? 'Modul Sistem' }}
            </div>
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px;">
                {{ $subtitle ?? 'Modul Bank Mini Sekolah' }}
            </div>
            <div style="display:inline-block; font-size: 12.5px; font-weight: 600; color: #92400E; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 8px 14px; margin: 8px 0 20px;">
                Fitur ini belum tersedia dan masih dalam tahap pengembangan. Silakan kembali ke dashboard Anda.
            </div>
            <div>
                <a href="{{ route('dashboard') }}" class="btn-ui btn-ui-primary btn-ui-sm" style="text-decoration:none;">Kembali ke Dashboard {{ auth()->user()->roleName() }}</a>
            </div>
        </div>
    </div>
</div>

@endsection
