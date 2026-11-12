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
