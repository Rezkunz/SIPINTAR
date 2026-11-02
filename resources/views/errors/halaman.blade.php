{{--
    Halaman pengganti error untuk semua role. Tidak memakai layouts.app agar tetap tampil
    walaupun error berasal dari layout / data dashboard. Variabel: $status, $judul, $pesan, $detail
--}}
@php
    $user = auth()->user();
    $ikon = match (true) {
        $status === 403 => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        in_array($status, [404, 405], true) => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/>',
        in_array($status, [419, 429], true) => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        default => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $judul }} — SIPINTAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: #0F172A;
            background: #F8FAFC;
        }
        .kartu {
            width: 100%;
            max-width: 480px;
            padding: 36px 32px 28px;
            text-align: center;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-top: 4px solid #0D5C46;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
        .ikon {
            width: 60px;
            height: 60px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0D5C46;
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            border-radius: 16px;
        }
        .kode {
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #B45309;
        }
        h1 {
            margin: 6px 0 10px;
            font-size: 21px;
            font-weight: 800;
        }
        p {
            margin: 0 0 8px;
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
        }
        .detail {
            margin: 14px 0 0;
            padding: 10px 14px;
            font-size: 13px;
            color: #92400E;
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: 10px;
        }
        .aksi {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 24px;
        }
        .tombol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            padding: 0 20px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 10px;
            cursor: pointer;
        }
        .tombol-utama { color: #FFFFFF; background: #0D5C46; border: 1px solid #0D5C46; }
        .tombol-utama:hover { background: #0A4A38; }
        .tombol-garis { color: #475569; background: #FFFFFF; border: 1px solid #CBD5E1; width: 100%; }
        .tombol-garis:hover { border-color: #94A3B8; }
        .kaki {
            margin-top: 20px;
            font-size: 11.5px;
            color: #94A3B8;
        }
    </style>
</head>
<body>
    <main class="kartu" role="main">
        <div class="ikon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $ikon !!}</svg>
        </div>
        <div class="kode">Kode {{ $status }}</div>
        <h1>{{ $judul }}</h1>
        <p>{{ $pesan }}</p>
        @if (!empty($detail))
            <div class="detail">{{ $detail }}</div>
        @endif

        <div class="aksi">
            @if ($user)
                <a href="{{ route('dashboard') }}" class="tombol tombol-utama">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Kembali ke Dashboard {{ $user->roleName() }}
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="tombol tombol-garis">Keluar dan masuk dengan akun lain</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="tombol tombol-utama">Masuk ke SIPINTAR</a>
            @endif
        </div>

        <div class="kaki">SIPINTAR &middot; Bank Mini Sekolah</div>
    </main>
</body>
</html>
