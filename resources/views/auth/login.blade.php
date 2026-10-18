<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SIPINTAR – Bank Mini Sekolah">
    <title>Masuk — SIPINTAR</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Clean Auth Stylesheet matching Image 3 --}}
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ time() }}">
</head>
<body class="auth-page-body">

    {{-- Circular Dot Loading Modal Overlay (Matching Reference Image) --}}
    <div class="auth-loading-overlay" id="auth-loading-overlay" aria-hidden="true">
        <div class="auth-loading-backdrop"></div>
        <div class="auth-loading-card">
            <div class="auth-spinner-wrapper">
                <div class="auth-spinner-halo"></div>
                <svg class="auth-circular-spinner-svg" viewBox="0 0 100 100">
                    <circle cx="74.0" cy="26.0" r="7.5" fill="currentColor" opacity="1.0" />
                    <circle cx="50.0" cy="16.0" r="6.6" fill="currentColor" opacity="0.85" />
                    <circle cx="26.0" cy="26.0" r="5.8" fill="currentColor" opacity="0.70" />
                    <circle cx="16.0" cy="50.0" r="5.0" fill="currentColor" opacity="0.55" />
                    <circle cx="26.0" cy="74.0" r="4.3" fill="currentColor" opacity="0.42" />
                    <circle cx="50.0" cy="84.0" r="3.7" fill="currentColor" opacity="0.30" />
                    <circle cx="74.0" cy="74.0" r="3.1" fill="currentColor" opacity="0.20" />
                    <circle cx="84.0" cy="50.0" r="2.5" fill="currentColor" opacity="0.12" />
                </svg>
            </div>
            <div class="auth-loading-title" id="auth-loading-title">Mengautentikasi...</div>
            <div class="auth-loading-desc" id="auth-loading-desc">Memverifikasi kredensial akun nasabah dengan aman</div>
            <div class="auth-loading-dots-progress">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    {{-- Interactive Bouncing Spring Grid Canvas (Grid melompat-lompat saat disentuh kursor) --}}
    <canvas id="auth-bouncing-grid" class="auth-bouncing-grid-canvas" aria-hidden="true"></canvas>

    {{-- Interactive Cursor Spotlight Grid (Timbul berkilau mengikuti kursor) --}}
    <div class="auth-interactive-grid" aria-hidden="true"></div>
    <div class="auth-cursor-glow" id="auth-cursor-glow" aria-hidden="true"></div>



    {{-- Ambient Decorative Lighting Orbs (Mengisi latar belakang kosong) --}}
    <div class="auth-ambient-orb auth-ambient-orb-1" aria-hidden="true"></div>
    <div class="auth-ambient-orb auth-ambient-orb-2" aria-hidden="true"></div>

    <div class="auth-card-container">
        {{-- ── LEFT ARTWORK PANEL: Dynamic Flowing Waves (Image 3) ── --}}
        <aside class="auth-artwork-panel" aria-hidden="true">
            {{-- Floating Organic Waves SVG with Oversized Canvas Coordinates to prevent edge clipping --}}
            <svg class="auth-artwork-waves" viewBox="0 0 500 700" preserveAspectRatio="none" fill="none">
                <path class="wave-layer wave-layer-1" d="M -60 -80 L 560 -80 L 560 210 C 350 90 150 140 -60 230 Z" fill="rgba(255,255,255,0.07)"/>
                <path class="wave-layer wave-layer-2" d="M -60 -80 L 560 -80 L 560 380 C 330 240 170 310 -60 300 Z" fill="rgba(255,255,255,0.05)"/>
                <path class="wave-layer wave-layer-3" d="M -60 780 L 560 780 L 560 440 C 310 590 150 510 -60 630 Z" fill="rgba(16,185,129,0.28)"/>
                <path class="wave-layer wave-layer-1" d="M -60 780 L 560 780 L 560 560 C 340 460 130 580 -60 510 Z" fill="rgba(255,255,255,0.06)"/>
            </svg>

            {{-- Floating Ambient Particles --}}
            <div class="auth-particles-layer">
                <span class="auth-particle"></span>
                <span class="auth-particle"></span>
                <span class="auth-particle"></span>
                <span class="auth-particle"></span>
            </div>

            <div></div>

            <div class="auth-brand-content">
                <div class="auth-brand-logo-badge">
                    <img src="{{ asset('images/sipintar_mark.svg') }}" alt="SIPINTAR" class="auth-brand-logo-img">
                </div>
                <h1 class="auth-brand-title">SIP<span>INTAR</span></h1>
                <div class="auth-brand-sub">Bank Mini Sekolah</div>
                <p class="auth-brand-slogan">Layanan perbankan dan koperasi sekolah yang transparan, cerdas, dan akuntabel.</p>
            </div>

            <div class="auth-artwork-footer">
                SIPINTAR &copy; {{ date('Y') }} &bull; Sistem Perbankan Sekolah
            </div>
        </aside>

        {{-- ── RIGHT FORM PANEL: Pill Controls (Image 3) ── --}}
        <main class="auth-form-panel">
            <div class="auth-form-container">
                <div class="auth-intro">
                    <h2 class="auth-heading">Selamat Datang</h2>
                    <p class="auth-subheading">Masuk ke akun Anda untuk melanjutkan transaksi.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" novalidate autocomplete="off">
                    @csrf

                    {{-- 1. Input Nomor Telepon / WhatsApp / NISN --}}
                    <div class="auth-form-group">
                        <label class="auth-form-label" for="login">Nomor Telepon / WhatsApp</label>
                        <div class="auth-input-wrapper">
                            <input
                                id="login"
                                name="login"
                                type="text"
                                class="auth-form-input @error('login') has-error @enderror"
                                placeholder="Contoh: 081234567890"
                                value="{{ old('login') }}"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                        @error('login')
                            <div class="auth-field-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 2. Input Kata Sandi --}}
                    <div class="auth-form-group">
                        <label class="auth-form-label" for="password">Kata Sandi</label>
                        <div class="auth-input-wrapper">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="auth-form-input auth-input-password @error('password') has-error @enderror"
                                placeholder="Masukkan kata sandi"
                                required
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                class="auth-btn-toggle-pwd"
                                id="toggle-pwd-btn"
                                onclick="toggleAuthPassword('password', this)"
                                aria-label="Tampilkan atau sembunyikan kata sandi"
                                title="Lihat kata sandi"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="auth-field-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-links-row">
                        <a href="javascript:void(0)" onclick="alert('Silakan hubungi staf administrator / teller sekolah untuk bantuan reset kata sandi Anda.')" class="auth-link">
                            Lupa kata sandi?
                        </a>
                    </div>

                    {{-- Pill Submit Button with Circular Dot Spinner --}}
                    <button type="submit" class="auth-btn-submit" id="btn-submit">
                        <span class="auth-btn-spinner" id="btn-circular-spinner" style="display: none;">
                            <svg class="auth-circular-spinner-svg small" viewBox="0 0 100 100">
                                <circle cx="74.0" cy="26.0" r="7.5" fill="currentColor" opacity="1.0" />
                                <circle cx="50.0" cy="16.0" r="6.6" fill="currentColor" opacity="0.85" />
                                <circle cx="26.0" cy="26.0" r="5.8" fill="currentColor" opacity="0.70" />
                                <circle cx="16.0" cy="50.0" r="5.0" fill="currentColor" opacity="0.55" />
                                <circle cx="26.0" cy="74.0" r="4.3" fill="currentColor" opacity="0.42" />
                                <circle cx="50.0" cy="84.0" r="3.7" fill="currentColor" opacity="0.30" />
                                <circle cx="74.0" cy="74.0" r="3.1" fill="currentColor" opacity="0.20" />
                                <circle cx="84.0" cy="50.0" r="2.5" fill="currentColor" opacity="0.12" />
                            </svg>
                        </span>
                        <span id="btn-submit-text">MASUK KE AKUN</span>
                    </button>

                    <div class="auth-footer-prompt">
                        Belum memiliki rekening nasabah?
                        <a href="{{ route('register') }}" onclick="event.preventDefault(); smoothNavigate('{{ route('register') }}', 'forward')">Buka Rekening</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    {{-- ── 3D POPUP NOTIFICATION MODAL (Image 2) ── --}}
    @include('partials.status_popup')

    <script>
        // ── INTERACTIVE BOUNCING SPRING GRID CANVAS ("Lompat-Lompat") ──
        (function initBouncingGrid() {
            const canvas = document.getElementById('auth-bouncing-grid');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let width, height;
            let nodes = [];
            const spacing = 36;
            let mouse = { x: -1000, y: -1000, vx: 0, vy: 0, lastX: -1000, lastY: -1000 };

            function resize() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
                nodes = [];
                const cols = Math.ceil(width / spacing) + 2;
                const rows = Math.ceil(height / spacing) + 2;

                for (let r = 0; r < rows; r++) {
                    for (let c = 0; c < cols; c++) {
                        const bx = c * spacing;
                        const by = r * spacing;
                        nodes.push({
                            baseX: bx,
                            baseY: by,
                            x: bx,
                            y: by,
                            vy: 0,
                            vx: 0,
                            scale: 1
                        });
                    }
                }
            }

            window.addEventListener('resize', resize);
            resize();

            window.addEventListener('pointermove', (e) => {
                document.documentElement.style.setProperty('--mouse-x', `${e.clientX}px`);
                document.documentElement.style.setProperty('--mouse-y', `${e.clientY}px`);

                mouse.vx = e.clientX - mouse.lastX;
                mouse.vy = e.clientY - mouse.lastY;
                mouse.lastX = mouse.x = e.clientX;
                mouse.lastY = mouse.y = e.clientY;

                const speed = Math.hypot(mouse.vx, mouse.vy);
                const radius = Math.min(140, 75 + speed * 1.6);

                for (let i = 0; i < nodes.length; i++) {
                    const n = nodes[i];
                    const dx = n.x - mouse.x;
                    const dy = n.y - mouse.y;
                    const dist = Math.hypot(dx, dy);

                    if (dist < radius && dist > 0) {
                        const force = (1 - dist / radius) * 16;
                        const angle = Math.atan2(dy, dx);
                        n.vy += Math.sin(angle) * force * 0.7 - Math.cos(dist * 0.08) * force * 0.5;
                        n.vx += Math.cos(angle) * force * 0.4;
                        n.scale = 1 + (1 - dist / radius) * 1.8;
                    }
                }
            });

            // Ripple wave on click to make nodes jump sequentially
            window.addEventListener('pointerdown', (e) => {
                const cx = e.clientX;
                const cy = e.clientY;
                for (let i = 0; i < nodes.length; i++) {
                    const n = nodes[i];
                    const dist = Math.hypot(n.x - cx, n.y - cy);
                    if (dist < 280) {
                        setTimeout(() => {
                            n.vy += (1 - dist / 280) * -18;
                            n.scale = 2.4;
                        }, dist * 1.8);
                    }
                }
            });

            const springK = 0.075;
            const damping = 0.83;

            function render() {
                ctx.clearRect(0, 0, width, height);

                for (let i = 0; i < nodes.length; i++) {
                    const n = nodes[i];
                    const dy = n.y - n.baseY;
                    const dx = n.x - n.baseX;

                    n.vy = (n.vy - springK * dy) * damping;
                    n.vx = (n.vx - springK * dx) * damping;
                    n.y += n.vy;
                    n.x += n.vx;
                    n.scale += (1 - n.scale) * 0.12;

                    const displacement = Math.abs(dy) + Math.abs(dx);
                    const isBouncing = displacement > 0.4 || n.scale > 1.1;

                    if (isBouncing) {
                        const alpha = Math.min(0.85, 0.2 + displacement * 0.06);
                        ctx.fillStyle = `rgba(16, 185, 129, ${alpha})`;
                        const size = 2.2 * n.scale;
                        ctx.beginPath();
                        ctx.arc(n.x, n.y, size, 0, Math.PI * 2);
                        ctx.fill();

                        if (displacement > 2) {
                            ctx.strokeStyle = `rgba(13, 92, 70, ${alpha * 0.75})`;
                            ctx.lineWidth = 1;
                            const arm = 3.5 * n.scale;
                            ctx.beginPath();
                            ctx.moveTo(n.x - arm, n.y);
                            ctx.lineTo(n.x + arm, n.y);
                            ctx.moveTo(n.x, n.y - arm);
                            ctx.lineTo(n.x, n.y + arm);
                            ctx.stroke();
                        }
                    } else {
                        ctx.fillStyle = 'rgba(15, 23, 42, 0.04)';
                        ctx.fillRect(n.x - 0.75, n.y - 0.75, 1.5, 1.5);
                    }
                }

                requestAnimationFrame(render);
            }
            render();
        })();



        // ── CIRCULAR DOT LOADING MODAL CONTROLLER ──
        function showAuthLoading(title, desc) {
            const overlay = document.getElementById('auth-loading-overlay');
            if (title) document.getElementById('auth-loading-title').textContent = title;
            if (desc) document.getElementById('auth-loading-desc').textContent = desc;
            if (overlay) overlay.classList.add('is-active');
        }

        function hideAuthLoading() {
            document.getElementById('auth-loading-overlay')?.classList.remove('is-active');
        }

        // ── 3D EXPLODED "BONGKAR PER ELEMEN" PAGE TRANSITION & CIRCULAR LOADER ──
        function smoothNavigate(url, direction = 'forward') {
            showAuthLoading('Memuat Halaman...', 'Menyiapkan portal pendaftaran nasabah baru...');
            const card = document.querySelector('.auth-card-container');
            if (card) {
                card.classList.add(direction === 'forward' ? 'is-disassembling' : 'is-disassembling-reverse');
                setTimeout(() => {
                    window.location.href = url;
                }, 380);
            } else {
                window.location.href = url;
            }
        }

        // ── SUBMIT LOADING ANIMATION (Circular Dot Spinner) ──
        document.querySelector('form')?.addEventListener('submit', function() {
            showAuthLoading('Mengautentikasi...', 'Memverifikasi data kredensial akun perbankan...');
            const spinner = document.getElementById('btn-circular-spinner');
            const text = document.getElementById('btn-submit-text');
            const btn = document.getElementById('btn-submit');
            if (spinner) spinner.style.display = 'inline-flex';
            if (text) text.textContent = 'MEMPROSES...';
            if (btn) btn.style.pointerEvents = 'none';
        });

        // ── PASSWORD EYE BLINK MORPH TOGGLE (No Bouncing) ──
        function toggleAuthPassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;

            // Trigger natural eyelid blink animation
            btn.classList.add('is-blinking');
            setTimeout(() => btn.classList.remove('is-blinking'), 280);

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword
                ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        }

        (function() {
            @if ($errors->any() || session('error'))
                const errorMsg = @json(session('error') ?? $errors->first() ?? 'Nomor telepon, identitas, atau kata sandi tidak sesuai.');
                function fireLoginError() {
                    showErrorPopup(errorMsg, 'GAGAL MASUK', 'COBA LAGI');
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fireLoginError);
                } else {
                    fireLoginError();
                }
            @endif
        })();
    </script>
</body>
</html>
