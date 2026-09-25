/* Kalyan Matrimony front-end helpers (vanilla JS + Bootstrap 5) */
(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    // JSON fetch wrapper that sends the CSRF token and parses errors.
    window.api = async function (url, options = {}) {
        const res = await fetch(url, {
            method: options.method || 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
            },
            body: options.body instanceof FormData ? options.body : (options.body ? JSON.stringify(options.body) : undefined),
        });
        let data = {};
        try { data = await res.json(); } catch (e) { /* non-JSON */ }
        if (!res.ok) {
            const msg = data.message || (data.errors && Object.values(data.errors)[0][0]) || 'Something went wrong.';
            const err = new Error(msg); err.status = res.status; err.data = data; throw err;
        }
        return data;
    };

    // Lightweight toast notifications.
    window.toast = function (message, type = 'success') {
        let wrap = document.getElementById('toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'toast-wrap';
            wrap.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            wrap.style.zIndex = 1090;
            document.body.appendChild(wrap);
        }
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0`;
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        el.querySelector('.toast-body').textContent = message;
        wrap.appendChild(el);
        const t = new bootstrap.Toast(el, { delay: 4000 });
        t.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    };

    // Dependent dropdowns: <select data-dependent="#child" data-source="/api/states/{id}/cities">
    function bindDependent(parent) {
        const child = document.querySelector(parent.dataset.dependent);
        if (!child) return;
        parent.addEventListener('change', async () => {
            const placeholder = child.dataset.placeholder || 'Select';
            child.innerHTML = child.multiple ? '' : `<option value="">${placeholder}</option>`;
            if (!parent.value) return;
            child.disabled = true;
            try {
                const { data } = await api(parent.dataset.source.replace('{id}', parent.value));
                data.forEach(item => child.add(new Option(item.name, item.id)));
            } catch (e) { toast(e.message, 'error'); }
            child.disabled = false;
        });
    }
    document.querySelectorAll('select[data-dependent]').forEach(bindDependent);

    // Send interest / shortlist / generic AJAX action buttons: <button data-ajax="url" data-method="POST">
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-ajax]');
        if (!btn) return;
        e.preventDefault();
        if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
        btn.disabled = true;
        try {
            const data = await api(btn.dataset.ajax, { method: btn.dataset.method || 'POST' });
            toast(data.message || 'Done');
            if (btn.dataset.toggle === 'favorite') {
                const icon = btn.querySelector('i');
                icon.className = data.favorited ? 'bi bi-star-fill text-warning' : 'bi bi-star';
                btn.title = data.favorited ? 'Remove from shortlist' : 'Add to shortlist';
            } else if (btn.dataset.done) {
                btn.innerHTML = btn.dataset.done;
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                return; // keep disabled
            }
            if (btn.dataset.reload) window.location.reload();
        } catch (err) {
            toast(err.message, 'error');
            if (err.status === 402 && err.data.upgrade_url) setTimeout(() => window.location = err.data.upgrade_url, 1500);
        }
        btn.disabled = false;
    });

    // Notification bell dropdown.
    const bell = document.getElementById('notifBell');
    if (bell) {
        bell.addEventListener('show.bs.dropdown', async () => {
            const list = document.getElementById('notifList');
            try {
                const data = await api(bell.dataset.url);
                if (!data.items.length) { list.innerHTML = '<div class="p-4 text-center text-muted small">No notifications yet</div>'; return; }
                list.innerHTML = '';
                data.items.forEach(n => {
                    const a = document.createElement('a');
                    a.href = n.url;
                    a.className = 'dropdown-item py-2 border-bottom text-wrap' + (n.read ? '' : ' unread');
                    a.innerHTML = `<div class="d-flex gap-2"><i class="bi ${n.icon} text-brand mt-1"></i><div><div class="fw-semibold small"></div><div class="small text-muted msg"></div><div class="text-muted" style="font-size:.7rem">${n.time}</div></div></div>`;
                    a.querySelector('.fw-semibold').textContent = n.title;
                    a.querySelector('.msg').textContent = n.message;
                    list.appendChild(a);
                });
            } catch (e) { list.innerHTML = '<div class="p-3 text-danger small">Could not load notifications</div>'; }
        });
    }

    // Image previews for file inputs: <input type="file" data-preview="#target">
    document.querySelectorAll('input[type=file][data-preview]').forEach(input => {
        input.addEventListener('change', () => {
            const target = document.querySelector(input.dataset.preview);
            target.innerHTML = '';
            [...input.files].forEach(f => {
                if (!f.type.startsWith('image/')) return;
                const img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                img.className = 'rounded me-2 mb-2';
                img.style.cssText = 'width:90px;height:110px;object-fit:cover';
                target.appendChild(img);
            });
        });
    });

    // Bootstrap client-side validation styling.
    document.querySelectorAll('form.needs-validation').forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
            form.classList.add('was-validated');
        });
    });

    // Enable tooltips.
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
})();
