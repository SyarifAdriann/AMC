// ============================================================================
// Reference data management: Aircraft Details + Flight References
//
// One implementation driven by a per-type config, because the two screens
// differ only in their columns and key field. Self-contained: hooks the
// existing [data-modal-target] buttons by delegation, does not touch
// ModalManager in dashboard.js.
// ============================================================================
(function () {
    const endpoint = (window.dashboardConfig && window.dashboardConfig.endpoints
        && window.dashboardConfig.endpoints.referenceData) || 'api/reference-data';

    const userRole = (window.dashboardConfig && window.dashboardConfig.userRole) || '';
    const isAdmin = userRole === 'admin';

    const fetchJson = (url, options) => window.AMC.fetchJson(url, options || {});
    const csrf = () => (document.querySelector('input[name="csrf_token"]') || {}).value || '';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    const TYPES = {
        aircraft: {
            modalId: 'aircraftModalBg',
            formId: 'aircraft-ref-form',
            clearId: 'aircraft-ref-clear',
            searchId: 'aircraft-ref-search',
            rowsId: 'aircraft-ref-rows',
            pagerId: 'aircraft-ref-pagination',
            statusId: 'aircraft-ref-status',
            columns: 5,
            keyField: 'registration',
            fields: {
                registration: 'manage-aircraft-registration',
                aircraft_type: 'manage-aircraft-type',
                operator_airline: 'manage-aircraft-operator',
                category: 'manage-aircraft-category',
                notes: 'manage-aircraft-notes'
            },
            cells: row => [
                '<td class="px-3 py-2 font-medium">' + escapeHtml(row.registration) + '</td>',
                '<td class="px-3 py-2">' + escapeHtml(row.aircraft_type || '') + '</td>',
                '<td class="px-3 py-2">' + escapeHtml(row.operator_airline || '') + '</td>',
                '<td class="px-3 py-2">' + escapeHtml(row.category || '') + '</td>'
            ].join(''),
            keyOf: row => row.registration
        },
        flight: {
            modalId: 'flightRefModalBg',
            formId: 'flight-ref-form',
            clearId: 'flight-ref-clear',
            searchId: 'flight-ref-search',
            rowsId: 'flight-ref-rows',
            pagerId: 'flight-ref-pagination',
            statusId: 'flight-ref-status',
            columns: 3,
            keyField: 'id',
            fields: {
                flight_no: 'manage-flight-number',
                default_route: 'manage-default-route'
            },
            cells: row => [
                '<td class="px-3 py-2 font-medium">' + escapeHtml(row.flight_no) + '</td>',
                '<td class="px-3 py-2">' + escapeHtml(row.default_route || '') + '</td>'
            ].join(''),
            keyOf: row => row.id
        }
    };

    const state = { aircraft: { page: 1, search: '' }, flight: { page: 1, search: '' } };

    function setStatus(type, message, ok) {
        const el = document.getElementById(TYPES[type].statusId);
        if (!el) return;
        el.textContent = message;
        el.className = 'text-sm rounded-md px-3 py-2 ' +
            (ok ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700');
        el.classList.remove('hidden');
    }

    function post(type, action, extra) {
        const body = new FormData();
        body.append('action', action);
        body.append('type', type);
        body.append('csrf_token', csrf());
        Object.keys(extra || {}).forEach(key => body.append(key, extra[key]));

        return fetchJson(endpoint, { method: 'POST', body }).then(data => {
            if (!data || data.success === false) {
                throw new Error((data && data.message) || 'Request failed.');
            }
            return data;
        });
    }

    function render(type, payload) {
        const cfg = TYPES[type];
        const tbody = document.getElementById(cfg.rowsId);
        const pager = document.getElementById(cfg.pagerId);
        if (!tbody) return;

        const rows = payload.data || [];

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="' + cfg.columns +
                '" class="px-3 py-6 text-center text-slate-500">No records found.</td></tr>';
            if (pager) pager.innerHTML = '';
            return;
        }

        tbody.innerHTML = rows.map(function (row) {
            const key = escapeHtml(cfg.keyOf(row));
            const del = isAdmin
                ? '<button type="button" data-ref-delete="' + key + '" data-ref-type="' + type + '" ' +
                  'class="px-2 py-1 rounded border border-red-200 text-red-600 text-xs hover:bg-red-50">Delete</button>'
                : '';
            return '<tr class="hover:bg-slate-50">' + cfg.cells(row) +
                '<td class="px-3 py-2 text-right whitespace-nowrap">' +
                    '<button type="button" data-ref-edit=\'' + escapeHtml(JSON.stringify(row)) + '\' data-ref-type="' + type + '" ' +
                        'class="px-2 py-1 rounded border border-slate-300 text-slate-600 text-xs hover:bg-slate-100 mr-1">Edit</button>' +
                    del +
                '</td></tr>';
        }).join('');

        if (pager) {
            const totalPages = Math.max(1, Math.ceil((payload.total || 0) / (payload.per_page || 15)));
            const current = payload.page || 1;
            let html = '';
            if (current > 1) {
                html += '<button type="button" data-ref-page="' + (current - 1) + '" data-ref-type="' + type +
                    '" class="px-3 py-1 rounded border border-slate-300 text-sm hover:bg-slate-100">Prev</button>';
            }
            html += '<span class="text-sm text-slate-500 px-2">Page ' + current + ' of ' + totalPages +
                ' (' + (payload.total || 0) + ' records)</span>';
            if (current < totalPages) {
                html += '<button type="button" data-ref-page="' + (current + 1) + '" data-ref-type="' + type +
                    '" class="px-3 py-1 rounded border border-slate-300 text-sm hover:bg-slate-100">Next</button>';
            }
            pager.innerHTML = html;
        }
    }

    function load(type) {
        const cfg = TYPES[type];
        const tbody = document.getElementById(cfg.rowsId);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="' + cfg.columns +
                '" class="px-3 py-6 text-center text-slate-500">Loading...</td></tr>';
        }

        const params = new URLSearchParams({
            action: 'list',
            type: type,
            page: String(state[type].page),
            search: state[type].search
        });

        fetchJson(endpoint + '?' + params.toString())
            .then(function (data) {
                if (!data || data.success === false) {
                    throw new Error((data && data.message) || 'Unable to load records.');
                }
                render(type, data);
            })
            .catch(function (error) {
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="' + cfg.columns +
                        '" class="px-3 py-6 text-center text-red-600">' + escapeHtml(error.message) + '</td></tr>';
                }
            });
    }

    function clearForm(type) {
        const cfg = TYPES[type];
        Object.keys(cfg.fields).forEach(function (name) {
            const el = document.getElementById(cfg.fields[name]);
            if (el) {
                el.value = '';
                el.readOnly = false;
            }
        });
    }

    function fillForm(type, row) {
        const cfg = TYPES[type];
        Object.keys(cfg.fields).forEach(function (name) {
            const el = document.getElementById(cfg.fields[name]);
            if (el) el.value = row[name] == null ? '' : row[name];
        });
    }

    // ── wiring ──────────────────────────────────────────────────────────
    Object.keys(TYPES).forEach(function (type) {
        const cfg = TYPES[type];

        document.addEventListener('submit', function (event) {
            if (!event.target.closest('#' + cfg.formId)) return;
            event.preventDefault();

            const payload = {};
            Object.keys(cfg.fields).forEach(function (name) {
                const el = document.getElementById(cfg.fields[name]);
                payload[name] = el ? el.value.trim() : '';
            });

            post(type, 'save', payload)
                .then(function (data) {
                    setStatus(type, data.message, true);
                    clearForm(type);
                    load(type);
                })
                .catch(function (error) { setStatus(type, error.message, false); });
        });

        let searchTimer = null;
        document.addEventListener('input', function (event) {
            if (event.target.id !== cfg.searchId) return;
            clearTimeout(searchTimer);
            const value = event.target.value.trim();
            searchTimer = setTimeout(function () {
                state[type].search = value;
                state[type].page = 1;
                load(type);
            }, 250);
        });
    });

    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-modal-target]');
        if (opener) {
            const target = opener.getAttribute('data-modal-target');
            const type = Object.keys(TYPES).find(t => TYPES[t].modalId === target);
            if (type) {
                const status = document.getElementById(TYPES[type].statusId);
                if (status) status.classList.add('hidden');
                state[type].page = 1;
                state[type].search = '';
                const search = document.getElementById(TYPES[type].searchId);
                if (search) search.value = '';
                clearForm(type);
                load(type);
            }
            return;
        }

        const clear = event.target.closest('[id$="-ref-clear"]');
        if (clear) {
            const type = clear.id === 'aircraft-ref-clear' ? 'aircraft' : 'flight';
            clearForm(type);
            return;
        }

        const edit = event.target.closest('[data-ref-edit]');
        if (edit) {
            const type = edit.getAttribute('data-ref-type');
            try {
                fillForm(type, JSON.parse(edit.getAttribute('data-ref-edit')));
                setStatus(type, 'Loaded into the form above. Change what you need, then Save.', true);
            } catch (e) {
                setStatus(type, 'Could not load that record.', false);
            }
            return;
        }

        const del = event.target.closest('[data-ref-delete]');
        if (del) {
            const type = del.getAttribute('data-ref-type');
            const key = del.getAttribute('data-ref-delete');
            if (!confirm('Delete "' + key + '"?\n\nThis only removes the reference record. Movement records are not affected.')) {
                return;
            }
            const payload = {};
            payload[TYPES[type].keyField] = key;
            del.disabled = true;
            post(type, 'delete', payload)
                .then(function (data) { setStatus(type, data.message, true); load(type); })
                .catch(function (error) { setStatus(type, error.message, false); del.disabled = false; });
            return;
        }

        const pageBtn = event.target.closest('[data-ref-page]');
        if (pageBtn) {
            const type = pageBtn.getAttribute('data-ref-type');
            state[type].page = parseInt(pageBtn.getAttribute('data-ref-page'), 10) || 1;
            load(type);
        }
    });
})();
