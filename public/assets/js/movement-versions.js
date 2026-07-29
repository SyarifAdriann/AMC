// ============================================================================
// Movement Data Versions (admin only)
//
// Save / restore point-in-time states of aircraft_movements. Self-contained:
// hooks the existing [data-modal-target] button via event delegation, so it
// does not touch ModalManager in dashboard.js.
// ============================================================================
(function () {
    const endpoint = (window.dashboardConfig && window.dashboardConfig.endpoints
        && window.dashboardConfig.endpoints.movementVersions) || 'api/movement-versions';

    const listEl = () => document.getElementById('movement-version-list');
    const statusEl = () => document.getElementById('movement-version-status');
    const fetchJson = (url, options) => window.AMC.fetchJson(url, options || {});
    const csrf = () => (document.querySelector('input[name="csrf_token"]') || {}).value || '';

    function setStatus(message, ok) {
        const el = statusEl();
        if (!el) return;
        el.textContent = message;
        el.className = 'text-sm rounded-md px-3 py-2 ' +
            (ok ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700');
        el.classList.remove('hidden');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatDate(value) {
        const parsed = new Date(String(value).replace(' ', 'T'));
        return isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleString();
    }

    function post(action, extra) {
        const body = new FormData();
        body.append('action', action);
        body.append('csrf_token', csrf());
        Object.keys(extra || {}).forEach(key => body.append(key, extra[key]));

        return fetchJson(endpoint, { method: 'POST', body }).then(data => {
            if (!data || data.success === false) {
                throw new Error((data && data.message) || 'Request failed.');
            }
            return data;
        });
    }

    function render(versions) {
        const container = listEl();
        if (!container) return;

        if (!versions.length) {
            container.innerHTML = '<p class="text-sm text-slate-500 px-4 py-6 text-center">' +
                'No saved versions yet. Save the current state to create one.</p>';
            return;
        }

        container.innerHTML = versions.map(function (version) {
            const auto = Number(version.is_auto) === 1;
            const who = version.created_by_name ? ' &middot; ' + escapeHtml(version.created_by_name) : '';
            const label = escapeHtml(version.label);

            return '<div class="flex items-center justify-between gap-3 px-4 py-3 ' + (auto ? 'bg-slate-50' : '') + '">' +
                '<div class="min-w-0">' +
                    '<p class="font-medium truncate ' + (auto ? 'text-slate-500' : 'text-slate-800') + '">' + label + '</p>' +
                    '<p class="text-xs text-slate-500">' + escapeHtml(formatDate(version.created_at)) +
                        ' &middot; ' + Number(version.row_count).toLocaleString() + ' movements' + who + '</p>' +
                '</div>' +
                '<div class="flex gap-2 shrink-0">' +
                    '<button type="button" data-restore-id="' + version.id + '" data-label="' + label + '" ' +
                        'class="px-3 py-1.5 rounded-md bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Restore</button>' +
                    '<button type="button" data-delete-id="' + version.id + '" data-label="' + label + '" ' +
                        'class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-600 text-sm hover:bg-slate-100">Delete</button>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    function load() {
        const container = listEl();
        if (container) {
            container.innerHTML = '<p class="text-sm text-slate-500 px-4 py-6 text-center">Loading...</p>';
        }

        fetchJson(endpoint + '?action=list')
            .then(function (data) {
                if (!data || data.success === false) {
                    throw new Error((data && data.message) || 'Unable to load versions.');
                }
                render(data.data || []);
            })
            .catch(function (error) {
                if (container) {
                    container.innerHTML = '<p class="text-sm text-red-600 px-4 py-6 text-center">' +
                        escapeHtml(error.message) + '</p>';
                }
            });
    }

    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-modal-target="movementVersionsModalBg"]');
        if (opener) {
            const status = statusEl();
            if (status) status.classList.add('hidden');
            load();
            return;
        }

        const restore = event.target.closest('[data-restore-id]');
        if (restore) {
            const label = restore.getAttribute('data-label');
            if (!confirm('Restore "' + label + '"?\n\nThis replaces ALL current movement records. ' +
                'Your current state will be saved first as an auto-save, so this can be undone.')) {
                return;
            }
            restore.disabled = true;
            post('restore', { id: restore.getAttribute('data-restore-id') })
                .then(function (data) { setStatus(data.message, true); load(); })
                .catch(function (error) { setStatus(error.message, false); restore.disabled = false; });
            return;
        }

        const remove = event.target.closest('[data-delete-id]');
        if (remove) {
            if (!confirm('Delete the saved version "' + remove.getAttribute('data-label') + '"?\n\n' +
                'This only deletes the saved copy. Current movement records are not affected.')) {
                return;
            }
            remove.disabled = true;
            post('delete', { id: remove.getAttribute('data-delete-id') })
                .then(function (data) { setStatus(data.message, true); load(); })
                .catch(function (error) { setStatus(error.message, false); remove.disabled = false; });
            return;
        }

        const wipe = event.target.closest('#wipe-movements-btn');
        if (wipe) {
            if (!confirm('Wipe ALL movement records?\n\nYour current state will be saved first as an ' +
                'auto-save, so this can be undone from the list above.')) {
                return;
            }
            wipe.disabled = true;
            post('wipe', {})
                .then(function (data) { setStatus(data.message, true); load(); })
                .catch(function (error) { setStatus(error.message, false); })
                .then(function () { wipe.disabled = false; });
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('#save-movement-version-form');
        if (!form) return;

        event.preventDefault();
        const input = document.getElementById('movement-version-label');
        const label = (input ? input.value : '').trim();

        if (!label) {
            setStatus('Give this version a name first.', false);
            return;
        }

        post('save', { label: label })
            .then(function (data) {
                setStatus(data.message, true);
                if (input) input.value = '';
                load();
            })
            .catch(function (error) { setStatus(error.message, false); });
    });
})();
