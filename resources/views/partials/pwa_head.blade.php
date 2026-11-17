{{-- Progressive Web App: manifest, ikon, warna tema, dan pendaftaran service worker --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#0D5C46">
<meta name="application-name" content="SIPINTAR">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="SIPINTAR">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script src="{{ asset('js/pwa.js') }}?v={{ filemtime(public_path('js/pwa.js')) }}" defer></script>
<style>
    [data-pwa-install][hidden] { display: none !important; }
    .auth-btn-install {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; margin-top: 14px;
        padding: 11px 16px; border: 1.5px solid #0D5C46; border-radius: 10px; background: #FFFFFF; color: #0D5C46;
        font: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer;
    }
    .auth-btn-install:hover { background: #ECFDF5; }
    .auth-btn-install:focus-visible { outline: 3px solid #A7F3D0; outline-offset: 2px; }
</style>
