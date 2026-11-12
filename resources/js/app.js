import * as bootstrap from 'bootstrap';
import 'admin-lte/dist/js/adminlte.min.js';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

window.bootstrap = bootstrap;
window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.app-toast').forEach((el, index) => {
        const toast = bootstrap.Toast.getOrCreateInstance(el, {
            delay: 4500 + index * 300,
        });

        el.classList.add('toast-in');
        toast.show();

        el.addEventListener('hide.bs.toast', (event) => {
            event.preventDefault();
            el.classList.remove('toast-in');
            el.classList.add('toast-out');
            el.addEventListener('animationend', () => el.remove(), { once: true });
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            }
        });
    });
});

// Password visibility toggles ([data-toggle-password] -> target input selector)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.querySelector(btn.dataset.togglePassword);
            if (! input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('hgi-view', ! show);
                icon.classList.toggle('hgi-view-off', show);
            }
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    // Password strength meter (input[data-strength] -> meter selector)
    document.querySelectorAll('input[data-strength]').forEach((input) => {
        const bar = document.querySelector(input.dataset.strength)?.querySelector('span');
        if (! bar) return;
        input.addEventListener('input', () => {
            const v = input.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (v.length >= 12 || (/[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v))) score++;
            if (/[^a-zA-Z0-9]/.test(v) && v.length >= 10) score++;
            if (v.length === 0) score = 0;
            else if (score === 0) score = 1;
            bar.dataset.score = score;
        });
    });
});

// PWA — register a minimal service worker (installable; nothing sensitive cached)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

// ============================================================================
// Session expiry — warn, re-authenticate in place, keep the current page/work
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    const S = window.APP_SESSION;
    if (! S) return;

    const WARN_BEFORE = Math.min(120, Math.max(30, S.lifetime - (S.warnAfter ?? 0)));
    const TICK_MS = 15000;

    let lastActivity = Date.now();
    let warnShown = false;
    let expiredShown = false;
    let freshCsrf = null;

    const warnModalEl = document.getElementById('sessionWarnModal');
    const expiredModalEl = document.getElementById('sessionExpiredModal');
    const warnModal = warnModalEl ? new bootstrap.Modal(warnModalEl) : null;
    const expiredModal = expiredModalEl ? new bootstrap.Modal(expiredModalEl) : null;
    const countdownEl = document.getElementById('sessionWarnCountdown');
    const reauthForm = document.getElementById('sessionReauthForm');

    const channel = 'BroadcastChannel' in window ? new BroadcastChannel('amoleck-auth') : null;
    if (channel) {
        channel.onmessage = (event) => {
            if (event.data === 'expired') showExpired(false);
            if (event.data === 'restored') { lastActivity = Date.now(); hideModals(); }
            if (event.data === 'signed-out') window.location.href = '/login';
        };
    }

    const remaining = () => S.lifetime - (Date.now() - lastActivity) / 1000;

    const fmt = (secs) => {
        const s = Math.max(0, Math.round(secs));
        return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    };

    function hideModals() {
        warnShown = false;
        expiredShown = false;
        warnModal?.hide();
        expiredModal?.hide();
        const err = document.getElementById('reauthError');
        err?.classList.add('d-none');
    }

    const csrfToken = () => freshCsrf || document.querySelector('meta[name="csrf-token"]')?.content;

    function applyCsrf(token) {
        freshCsrf = token;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.content = token;
        document.querySelectorAll('input[name="_token"]').forEach((input) => { input.value = token; });
    }

    // If the server session is already gone, fetch a fresh guest session +
    // CSRF token via /login so the reauth POST can pass CSRF verification.
    async function primeCsrf() {
        try {
            const res = await rawFetch('/', { headers: { Accept: 'text/html' } });
            if (! res.url.includes('/login')) return;
            const html = await res.text();
            const match = html.match(/name="csrf-token" content="([^"]+)"/);
            if (match) applyCsrf(match[1]);
        } catch { /* offline — modal still shown */ }
    }

    function showExpired(broadcast = true) {
        if (expiredShown) return;
        expiredShown = true;
        warnShown = false;
        warnModal?.hide();
        primeCsrf();
        expiredModal?.show();
        setTimeout(() => document.getElementById('reauthPassword')?.focus(), 450);
        if (broadcast) channel?.postMessage('expired');
    }

    function showWarn() {
        if (warnShown || expiredShown) return;
        warnShown = true;
        if (countdownEl) countdownEl.textContent = fmt(remaining());
        warnModal?.show();
    }

    // The server session is renewed by real requests only — so the idle clock
    // tracks fetches and form submits, not mere mouse movement.
    const rawFetch = window.fetch.bind(window);
    window.fetch = async (...args) => {
        const res = await rawFetch(...args);
        if (res.status === 401 || res.status === 419) {
            showExpired();
        } else if (! expiredShown) {
            lastActivity = Date.now();
        }
        return res;
    };
    document.addEventListener('submit', (event) => {
        if (event.target && event.target.id !== 'sessionReauthForm') {
            lastActivity = Date.now();
        }
    }, true);

    document.getElementById('sessionContinueBtn')?.addEventListener('click', async () => {
        try {
            const res = await window.fetch(S.statusUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json().catch(() => null);
            if (res.ok && data?.authenticated) {
                lastActivity = Date.now();
                warnShown = false;
                warnModal?.hide();
            } else {
                showExpired();
            }
        } catch { /* offline — leave the warning visible */ }
    });

    document.querySelectorAll('[data-session-signout]').forEach((btn) => {
        btn.addEventListener('click', () => {
            channel?.postMessage('signed-out');
            document.getElementById('sessionSignoutForm')?.submit();
        });
    });

    reauthForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const btn = document.getElementById('reauthSubmit');
        const err = document.getElementById('reauthError');
        const pwInput = document.getElementById('reauthPassword');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
        err?.classList.add('d-none');

        try {
            const res = await rawFetch(S.reauthUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    email: document.getElementById('reauthEmail').value,
                    password: pwInput.value,
                }),
            });

            if (res.ok) {
                const data = await res.json();
                if (data.csrf) applyCsrf(data.csrf);
                lastActivity = Date.now();
                channel?.postMessage('restored');
                hideModals();
            } else if (res.status === 419) {
                await primeCsrf();
                err.textContent = 'Session was refreshed. Please enter your password again.';
                err.classList.remove('d-none');
            } else {
                const data = await res.json().catch(() => null);
                const msg = data?.errors
                    ? Object.values(data.errors).flat()[0]
                    : (data?.message || 'Unable to verify. Please try again.');
                err.textContent = msg;
                err.classList.remove('d-none');
            }
        } catch {
            err.textContent = 'Network error. Check your connection and try again.';
            err.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Continue Session';
            pwInput.value = '';
            pwInput.focus();
        }
    });

    const check = () => {
        const r = remaining();
        if (countdownEl && warnShown && ! expiredShown) countdownEl.textContent = fmt(r);
        if (r <= 0) showExpired();
        else if (r <= WARN_BEFORE) showWarn();
    };
    setInterval(check, TICK_MS);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') check();
    });
});

// Sidebar menu filter
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('sidebarSearch');
    if (! input) return;
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        document.querySelectorAll('.sidebar-menu .nav-item').forEach((item) => {
            const text = item.textContent.trim().toLowerCase();
            item.classList.toggle('nav-hidden', q !== '' && ! text.includes(q));
        });
        document.querySelectorAll('.sidebar-menu .nav-header').forEach((header) => {
            header.classList.toggle('nav-hidden', q !== '');
        });
    });
});
