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

    // Multi-selects: replace the native list box with a checkbox dropdown.
    // The original <select> stays in the form (hidden) and remains the source of truth.
    function enhanceMultiSelect(select) {
        const placeholder = select.dataset.placeholder || 'Any';
        const wrap = document.createElement('div');
        wrap.className = 'dropdown multi-select';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'form-select text-start text-truncate'
            + (select.classList.contains('form-select-sm') ? ' form-select-sm' : '')
            + (select.classList.contains('is-invalid') ? ' is-invalid' : '');
        btn.setAttribute('data-bs-toggle', 'dropdown');
        btn.setAttribute('data-bs-auto-close', 'outside');
        btn.setAttribute('aria-expanded', 'false');
        const menu = document.createElement('div');
        menu.className = 'dropdown-menu w-100 p-0';
        wrap.append(btn, menu);
        select.classList.add('d-none');
        select.after(wrap);

        const label = select.id && document.querySelector(`label[for="${select.id}"]`);
        if (label) label.addEventListener('click', e => { e.preventDefault(); btn.focus(); });

        function updateButton() {
            const chosen = [...select.selectedOptions].map(o => o.text);
            btn.textContent = !chosen.length ? placeholder
                : chosen.length <= 2 ? chosen.join(', ')
                : `${chosen.slice(0, 2).join(', ')} +${chosen.length - 2} more`;
            btn.classList.toggle('text-muted', !chosen.length);
        }

        function render() {
            menu.innerHTML = '';
            const opts = [...select.options];
            if (!opts.length) {
                menu.innerHTML = '<div class="px-3 py-2 small text-muted">No options available</div>';
                updateButton();
                return;
            }
            const head = document.createElement('div');
            head.className = 'd-flex gap-2 align-items-center p-2 border-bottom';
            if (opts.length > 8) {
                const search = document.createElement('input');
                search.type = 'search';
                search.className = 'form-control form-control-sm';
                search.placeholder = 'Search...';
                search.addEventListener('input', () => {
                    const q = search.value.toLowerCase();
                    list.querySelectorAll('label').forEach(l => l.classList.toggle('d-none', !l.textContent.toLowerCase().includes(q)));
                });
                head.appendChild(search);
            }
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'btn btn-link btn-sm text-decoration-none ms-auto text-nowrap';
            clear.textContent = `Clear (${placeholder})`;
            clear.addEventListener('click', () => {
                opts.forEach(o => { o.selected = false; });
                list.querySelectorAll('input').forEach(cb => { cb.checked = false; });
                updateButton();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            head.appendChild(clear);

            const list = document.createElement('div');
            list.className = 'multi-select-list py-1';
            opts.forEach(o => {
                const item = document.createElement('label');
                item.className = 'dropdown-item d-flex align-items-center gap-2';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'form-check-input m-0';
                cb.checked = o.selected;
                cb.addEventListener('change', () => {
                    o.selected = cb.checked;
                    updateButton();
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                const span = document.createElement('span');
                span.textContent = o.text;
                item.append(cb, span);
                list.appendChild(item);
            });
            menu.append(head, list);
            updateButton();
        }

        render();
        // Re-render when options are replaced (e.g. castes reloaded for a new religion) or the select is disabled.
        new MutationObserver(() => { btn.disabled = select.disabled; render(); })
            .observe(select, { childList: true, attributes: true, attributeFilter: ['disabled'] });
    }
    document.querySelectorAll('select[multiple]').forEach(enhanceMultiSelect);

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
