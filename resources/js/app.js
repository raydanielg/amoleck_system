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
