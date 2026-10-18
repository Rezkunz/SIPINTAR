{{-- ── SIPINTAR 3D POPUP NOTIFICATION MODAL (Image 2 Design) ── --}}
<div class="sipintar-popup-backdrop" id="sipintar-popup-backdrop" onclick="closeStatusPopup()"></div>
<div class="sipintar-popup-modal" id="sipintar-popup-modal" role="dialog" aria-modal="true" aria-labelledby="sipintar-popup-title" onclick="if(event.target===this) closeStatusPopup()">
    <div class="sipintar-popup-card success" id="sipintar-popup-card">
        <button type="button" class="sipintar-popup-close" onclick="closeStatusPopup()" aria-label="Tutup Notifikasi">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        {{-- 3D Avatar Face with soft floor shadow --}}
        <div class="sipintar-popup-avatar-wrap">
            <div class="sipintar-popup-avatar-circle" id="sipintar-popup-avatar">
                {{-- Dynamic SVG (Smile or Sad face) injected via JS --}}
                <svg width="68" height="68" viewBox="0 0 100 100" fill="none" id="avatar-svg-success">
                    <circle cx="50" cy="50" r="48" fill="#FFFFFF"/>
                    <circle cx="34" cy="42" r="6" fill="#334155"/>
                    <circle cx="66" cy="42" r="6" fill="#334155"/>
                    <path d="M35 62 Q50 78 65 62" stroke="#334155" stroke-width="5" stroke-linecap="round" fill="none"/>
                </svg>
            </div>
            <div class="sipintar-popup-avatar-shadow"></div>
        </div>

        <h3 class="sipintar-popup-title" id="sipintar-popup-title">SUCCESS !</h3>
        <p class="sipintar-popup-desc" id="sipintar-popup-desc">That worked out well</p>

        <div class="sipintar-popup-actions">
            <button type="button" class="sipintar-popup-btn" id="sipintar-popup-btn" onclick="confirmStatusPopup()">
                DONE
            </button>
        </div>
    </div>
</div>

<script>
    // ── GLOBAL SIPINTAR 3D POPUP CONTROLLER ──
    window._popupCallback = null;

    const SVG_SMILE = `<svg width="68" height="68" viewBox="0 0 100 100" fill="none">
        <circle cx="50" cy="50" r="48" fill="#FFFFFF"/>
        <circle cx="34" cy="42" r="6" fill="#334155"/>
        <circle cx="66" cy="42" r="6" fill="#334155"/>
        <path d="M35 62 Q50 78 65 62" stroke="#334155" stroke-width="5" stroke-linecap="round" fill="none"/>
    </svg>`;

    const SVG_SAD = `<svg width="68" height="68" viewBox="0 0 100 100" fill="none">
        <circle cx="50" cy="50" r="48" fill="#FFFFFF"/>
        <circle cx="34" cy="46" r="6" fill="#334155"/>
        <circle cx="66" cy="46" r="6" fill="#334155"/>
        <path d="M35 70 Q50 54 65 70" stroke="#334155" stroke-width="5" stroke-linecap="round" fill="none"/>
    </svg>`;

    window.showSuccessPopup = function(message, title = 'SUCCESS !', btnText = 'DONE', onDone = null) {
        const modal = document.getElementById('sipintar-popup-modal');
        const backdrop = document.getElementById('sipintar-popup-backdrop');
        const card = document.getElementById('sipintar-popup-card');
        const avatar = document.getElementById('sipintar-popup-avatar');
        const titleEl = document.getElementById('sipintar-popup-title');
        const descEl = document.getElementById('sipintar-popup-desc');
        const btn = document.getElementById('sipintar-popup-btn');

        if (!modal || !backdrop) return;

        card.className = 'sipintar-popup-card success';
        avatar.innerHTML = SVG_SMILE;
        titleEl.textContent = title;
        descEl.textContent = message || 'Operasi berhasil dilakukan dengan baik.';
        btn.textContent = btnText || 'DONE';
        window._popupCallback = onDone;

        backdrop.classList.add('is-open');
        modal.classList.add('is-open');
    };

    window.showErrorPopup = function(message, title = 'ERROR', btnText = 'RETRY', onRetry = null) {
        const modal = document.getElementById('sipintar-popup-modal');
        const backdrop = document.getElementById('sipintar-popup-backdrop');
        const card = document.getElementById('sipintar-popup-card');
        const avatar = document.getElementById('sipintar-popup-avatar');
        const titleEl = document.getElementById('sipintar-popup-title');
        const descEl = document.getElementById('sipintar-popup-desc');
        const btn = document.getElementById('sipintar-popup-btn');

        if (!modal || !backdrop) return;

        card.className = 'sipintar-popup-card error';
        avatar.innerHTML = SVG_SAD;
        titleEl.textContent = title;
        descEl.textContent = message || 'Terjadi kendala saat memproses permintaan.';
        btn.textContent = btnText || 'RETRY';
        window._popupCallback = onRetry;

        backdrop.classList.add('is-open');
        modal.classList.add('is-open');
    };

    window.closeStatusPopup = function() {
        const modal = document.getElementById('sipintar-popup-modal');
        const backdrop = document.getElementById('sipintar-popup-backdrop');
        if (modal) modal.classList.remove('is-open');
        if (backdrop) backdrop.classList.remove('is-open');
        if (typeof window._popupCallback === 'function') {
            const cb = window._popupCallback;
            window._popupCallback = null;
            cb();
        }
    };

    window.confirmStatusPopup = function() {
        closeStatusPopup();
    };

    // Auto-display flash session notifications if present
    (function() {
        function triggerFlashNotification() {
            @if (session('success'))
                showSuccessPopup(@json(session('success')), 'SUCCESS !', 'DONE');
            @elseif (session('error'))
                showErrorPopup(@json(session('error')), 'ERROR', 'RETRY');
            @elseif (session('status'))
                showSuccessPopup(@json(session('status')), 'SUCCESS !', 'DONE');
            @endif
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', triggerFlashNotification);
        } else {
            triggerFlashNotification();
        }
    })();
</script>
