(function () {
    const config = window.masterTableConfig || {};
    const endpoints = config.endpoints || {};
    const masterEndpoint = resolveEndpoint(endpoints.master || 'api/master-table');
    const apronEndpoint = resolveEndpoint(endpoints.apron || 'api/apron');
    const resetUrl = config.resetUrl || null;
    const userRole = config.userRole || 'viewer';
    const csrfToken = config.csrfToken || '';
    const isViewer = userRole === 'viewer';
    const warningStorageKey = 'amc_master_warning_seen';
    const syncChannel = 'BroadcastChannel' in window ? new BroadcastChannel('amc-apron-sync') : null;

    // Tell open apron-map tabs (same browser) that movements changed
    function notifyApronChanged() {
        if (syncChannel) {
            try {
                syncChannel.postMessage({ type: 'apron-update', at: Date.now() });
            } catch (e) {
                // Channel closed — ignore
            }
        }
    }

    // ── Toast notifications (same look as the apron page) ──────────────
    function showToast(message, tone = 'info', duration = 4200) {
        let host = document.getElementById('apron-toast-host');
        if (!host) {
            host = document.createElement('div');
            host.id = 'apron-toast-host';
            host.className = 'apron-toast-container';
            document.body.appendChild(host);
        }
        const toast = document.createElement('div');
        toast.className = 'apron-toast' + (tone === 'success' ? ' apron-toast-success' : '');
        if (tone === 'error') {
            toast.style.borderLeft = '4px solid #dc2626';
        } else if (tone === 'warning') {
            toast.style.borderLeft = '4px solid #f59e0b';
        }
        const title = document.createElement('div');
        title.className = 'apron-toast-title';
        title.style.whiteSpace = 'pre-line';
        title.textContent = message;
        toast.appendChild(title);
        host.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('apron-toast-visible'));
        setTimeout(() => {
            toast.classList.add('apron-toast-fade');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, duration);
    }

    // Current wall-clock time as HH:MM. No date stamp — the backend adds one
    // only when the off block lands on a different day than the movement.
    function currentHhMm() {
        const now = new Date();
        return String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    }

    // Off block cells outside the two sheet-managed tables (the mobile card
    // layout) get the same double-click stamp. The sheet tables handle their
    // own, routed through applyBatch so undo works there.
    document.addEventListener('dblclick', event => {
        const input = event.target.closest('input[data-field="off_block_time"]');
        if (!input || input.readOnly || input.disabled) {
            return;
        }
        if (input.closest('#master-movements-table, #ron-data-table')) {
            return;
        }
        input.value = currentHhMm();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // ── Unsaved-changes tracking ────────────────────────────────────────
    let skipUnloadWarning = false;

    function hasUnsavedChanges() {
        const tables = ['#master-movements-table', '#ron-data-table'];
        for (const selector of tables) {
            const dirty = document.querySelector(`${selector} input.cell-dirty, ${selector} select.cell-dirty`);
            if (dirty) {
                return true;
            }
        }
        // New rows count as unsaved once a registration is typed
        const newRows = document.querySelectorAll('#master-movements-table tbody tr[data-id="new"] input[data-field="registration"]');
        for (const input of newRows) {
            if (input.value.trim() !== '') {
                return true;
            }
        }
        return false;
    }

    function refreshDirtyState(el) {
        if (!el || !el.matches || !el.matches('input[data-field], select[data-field]')) {
            return;
        }
        const original = el.getAttribute('data-original') ?? '';
        el.classList.toggle('cell-dirty', (el.value ?? '') !== original);
    }

    document.addEventListener('DOMContentLoaded', initialise);

    function resolveEndpoint(url) {
        if (!url) {
            return url;
        }

        try {
            return new URL(url, window.location.href).toString();
        } catch (error) {
            return url;
        }
    }

    // JSON fetch + CSRF header handling shared across pages (assets/js/amc-http.js)
    const fetchJson = (url, options = {}) => window.AMC.fetchJson(url, options);

    function initialise() {
        if (isViewer) {
            // readOnly (not disabled): disabled inputs swallow mouse events,
            // which would break range selection and copy for viewers.
            document.querySelectorAll('#master-movements-table input, #master-movements-table select, #ron-data-table input, #ron-data-table select').forEach(el => {
                if (el.tagName === 'SELECT') {
                    el.disabled = true;
                } else {
                    el.readOnly = true;
                }
                el.classList.add('cursor-not-allowed');
            });
        }

        setupExcelGrid('#master-movements-table', { canAddRows: true });
        setupExcelGrid('#ron-data-table');

        // Dirty-cell highlighting: mark cells that differ from their loaded value
        ['#master-movements-table', '#ron-data-table'].forEach(selector => {
            const body = document.querySelector(`${selector} tbody`);
            if (!body) {
                return;
            }
            body.addEventListener('input', event => refreshDirtyState(event.target));
            body.addEventListener('change', event => refreshDirtyState(event.target));
        });

        // ── Auto-sync: every committed edit saves itself (gsheets-style) ──
        // A committed change (leaving the cell, paste, delete, undo) queues the
        // cell; the queue flushes 600ms after the last change. New rows create
        // themselves once a registration is present.
        const pendingCells = new Map();   // "id:field" -> { el, id, field }
        const pendingNewRows = new Set(); // <tr data-id="new"> awaiting create
        let syncTimer = null;
        let syncChain = Promise.resolve();

        function scheduleSync() {
            clearTimeout(syncTimer);
            syncTimer = setTimeout(() => {
                syncChain = syncChain.then(flushSync);
            }, 600);
        }

        async function flushSync() {
            const cells = Array.from(pendingCells.values());
            pendingCells.clear();
            const creates = Array.from(pendingNewRows);
            pendingNewRows.clear();

            // Re-read values at flush time; skip cells already back at original
            const changes = [];
            cells.forEach(({ el, id, field }) => {
                const original = el.getAttribute('data-original') ?? '';
                if ((el.value ?? '') !== original) {
                    changes.push({ id, field, value: el.value ?? '', el });
                }
            });

            let synced = false;
            try {
                if (changes.length) {
                    const res = await fetchJson(masterEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'save_all_changes',
                            changes: changes.map(({ id, field, value }) => ({ id, field, value }))
                        })
                    });
                    if (res.success) {
                        changes.forEach(({ el, value }) => {
                            el.setAttribute('data-original', value);
                            refreshDirtyState(el);
                        });
                        applyDuplicateFlightHighlighting(res.duplicate_flights || []);
                        if ((res.warnings || []).length) {
                            showToast(res.warnings.join('\n'), 'warning', 6000);
                        }
                        synced = true;
                    } else {
                        showToast(res.message || 'Auto-sync failed — use Save to retry.', 'error', 6000);
                    }
                }

                for (const tr of creates) {
                    if (tr.dataset.id && tr.dataset.id !== 'new') {
                        continue; // already created by an earlier flush
                    }
                    const movement = {};
                    tr.querySelectorAll('input[data-field], select[data-field]').forEach(inp => {
                        movement[inp.dataset.field] = inp.value ?? '';
                    });
                    if (!String(movement.registration || '').trim()) {
                        continue; // not ready until a registration is typed
                    }
                    const res = await fetchJson(masterEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'create_new_movement', ...movement })
                    });
                    if (res.success && res.id) {
                        tr.dataset.id = res.id; // future edits are updates now
                        tr.querySelectorAll('input[data-field], select[data-field]').forEach(inp => {
                            inp.setAttribute('data-original', inp.value ?? '');
                            refreshDirtyState(inp);
                        });
                        if ((res.warnings || []).length) {
                            showToast(res.warnings.join('\n'), 'warning', 6000);
                        }
                        synced = true;
                    }
                }

                if (synced) {
                    showToast('✓ Synced', 'success', 1400);
                    notifyApronChanged();
                }
            } catch (err) {
                showToast('Auto-sync failed: ' + err.message + ' — use Save to retry.', 'error', 6000);
            }
        }

        if (!isViewer) {
            ['#master-movements-table', '#ron-data-table'].forEach(selector => {
                const body = document.querySelector(`${selector} tbody`);
                if (!body) {
                    return;
                }
                body.addEventListener('change', event => {
                    const el = event.target;
                    if (!el.matches || !el.matches('input[data-field], select[data-field]')) {
                        return;
                    }
                    const tr = el.closest('tr[data-id]');
                    if (!tr) {
                        return;
                    }
                    const id = tr.dataset.id;
                    if (id === 'new') {
                        pendingNewRows.add(tr);
                    } else if (id && id !== '0') {
                        pendingCells.set(id + ':' + el.dataset.field, { el, id, field: el.dataset.field });
                    } else {
                        return;
                    }
                    scheduleSync();
                });
            });
        }

        // Warn before leaving with unsaved edits (paste/bulk changes are easy to lose)
        window.addEventListener('beforeunload', event => {
            if (!skipUnloadWarning && !isViewer && hasUnsavedChanges()) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        // Ctrl+S saves the table instead of opening the browser save dialog
        document.addEventListener('keydown', event => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                if (!isViewer) {
                    saveAllData();
                }
            }
        });

        const refreshBtn = document.getElementById('refresh-master-table');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                window.location.reload();
            });
        }

        const resetFiltersBtn = document.getElementById('reset-filters');
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', () => {
                const destination = resetUrl ? resolveEndpoint(resetUrl) : window.location.pathname;
                window.location.href = destination;
            });
        }

        const setRonBtn = document.getElementById('set-ron-btn');
        if (setRonBtn && !isViewer) {
            setRonBtn.addEventListener('click', async () => {
                if (!confirm('Set all current movements as RON?')) {
                    return;
                }

                try {
                    const data = await fetchJson(masterEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'setron' })
                    });

                    if (!data.success) {
                        showToast(data.message || 'Unable to set RON status.', 'error');
                        return;
                    }

                    notifyApronChanged();
                    skipUnloadWarning = true;
                    window.location.reload();
                } catch (error) {
                    console.error('Set RON error:', error);
                    showToast('Failed to update RON status: ' + error.message, 'error');
                }
            });
        }

        const saveBtn = document.querySelector('[data-action="save-table"]');
        if (saveBtn && !isViewer) {
            saveBtn.addEventListener('click', saveAllData);
        }

        const loadMoreBtn = document.getElementById('load-more-rows');
        if (loadMoreBtn && !isViewer && !loadMoreBtn.dataset.bound) {
            loadMoreBtn.dataset.bound = 'true';
            loadMoreBtn.addEventListener('click', event => {
                event.preventDefault();
                loadMoreEmptyRows();
            });
        }

        const masterTableBody = document.querySelector('#master-movements-table tbody');
        if (masterTableBody && !isViewer) {
            masterTableBody.addEventListener('change', handleAutofillTriggers);
            masterTableBody.addEventListener('blur', handleAutofillTriggers, true);
            masterTableBody.addEventListener('input', event => {
                const field = event.target && event.target.dataset ? event.target.dataset.field : '';
                if (field === 'flight_no_arr' || field === 'flight_no_dep') {
                    applyDuplicateFlightHighlighting();
                    return;
                }
                if (field === 'on_block_time' || field === 'off_block_time') {
                    applyTimeOrderHighlighting();
                }
            });
        }

        const ronTableBody = document.querySelector('#ron-data-table tbody');
        if (ronTableBody && !isViewer) {
            ronTableBody.addEventListener('change', handleAutofillTriggers);
            ronTableBody.addEventListener('blur', handleAutofillTriggers, true);
        }

        applyDuplicateFlightHighlighting();
        applyTimeOrderHighlighting();
    }

    function extractTimeToMinutes(value) {
        if (typeof value !== 'string') {
            return null;
        }

        const match = value.match(/(\d{1,2}):(\d{2})/);
        if (!match) {
            return null;
        }

        const hour = Number.parseInt(match[1], 10);
        const minute = Number.parseInt(match[2], 10);
        if (Number.isNaN(hour) || Number.isNaN(minute) || hour < 0 || hour > 23 || minute < 0 || minute > 59) {
            return null;
        }

        return (hour * 60) + minute;
    }

    function getRowWarningKey(row, rowIndex) {
        const id = row.getAttribute('data-id');
        if (id && id !== 'new') {
            return `id:${id}`;
        }
        const newIndex = row.getAttribute('data-new-index');
        if (newIndex) {
            return `new:${newIndex}`;
        }
        return `row:${rowIndex}`;
    }

    function getSeenWarningKeys() {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(warningStorageKey) || '[]');
            return new Set(Array.isArray(parsed) ? parsed : []);
        } catch (error) {
            return new Set();
        }
    }

    function setSeenWarningKeys(keys) {
        sessionStorage.setItem(warningStorageKey, JSON.stringify(Array.from(keys)));
    }

    function collectClientWarnings() {
        const warnings = [];
        const flightFrequency = new Map();
        const warningKeys = [];

        document.querySelectorAll('#master-movements-table tbody tr[data-id]').forEach((row, rowIndex) => {
            const onBlock = row.querySelector('input[data-field="on_block_time"]')?.value || '';
            const offBlock = row.querySelector('input[data-field="off_block_time"]')?.value || '';
            const onMinutes = extractTimeToMinutes(onBlock);
            const offMinutes = extractTimeToMinutes(offBlock);
            const rowKey = getRowWarningKey(row, rowIndex);

            const offContainsDate = offBlock.includes('(');
            if (onMinutes !== null && offMinutes !== null && offMinutes < onMinutes && !offContainsDate) {
                warnings.push(`Row ${rowIndex + 1}: off block time is earlier than on block time.`);
                warningKeys.push(`time:${rowKey}`);
            }

            ['flight_no_arr', 'flight_no_dep'].forEach(field => {
                const value = (row.querySelector(`input[data-field="${field}"]`)?.value || '').trim().toUpperCase();
                if (!value) {
                    return;
                }
                flightFrequency.set(value, (flightFrequency.get(value) || 0) + 1);
            });
        });

        const duplicates = Array.from(flightFrequency.entries())
            .filter(([, count]) => count > 1)
            .map(([flight]) => flight);

        if (duplicates.length > 0) {
            warnings.push(`Duplicate flight number detected on this date: ${duplicates.join(', ')}.`);
        }

        duplicates.forEach(flight => warningKeys.push(`dup:${flight}`));

        return { warnings, duplicateFlights: duplicates, warningKeys: Array.from(new Set(warningKeys)) };
    }

    function applyDuplicateFlightHighlighting(explicitDuplicateFlights = null) {
        const duplicateSet = new Set(
            Array.isArray(explicitDuplicateFlights)
                ? explicitDuplicateFlights.map(value => String(value || '').trim().toUpperCase()).filter(Boolean)
                : collectClientWarnings().duplicateFlights
        );

        document.querySelectorAll('#master-movements-table tbody input[data-field="flight_no_arr"], #master-movements-table tbody input[data-field="flight_no_dep"]').forEach(input => {
            const value = String(input.value || '').trim().toUpperCase();
            const isDuplicate = value !== '' && duplicateSet.has(value);
            input.classList.toggle('duplicate-flight-warning', isDuplicate);
        });
    }

    function applyTimeOrderHighlighting() {
        document.querySelectorAll('#master-movements-table tbody tr[data-id]').forEach(row => {
            const onInput = row.querySelector('input[data-field="on_block_time"]');
            const offInput = row.querySelector('input[data-field="off_block_time"]');
            if (!onInput || !offInput) {
                return;
            }

            const onVal = onInput.value || '';
            const offVal = offInput.value || '';
            const onMinutes = extractTimeToMinutes(onVal);
            const offMinutes = extractTimeToMinutes(offVal);
            // Skip warning if off_block contains a date in parentheses (RON departure from a later day)
            const invalidOrder = onMinutes !== null && offMinutes !== null && offMinutes < onMinutes && !offVal.includes('(');

            onInput.classList.toggle('time-order-warning', invalidOrder);
            offInput.classList.toggle('time-order-warning', invalidOrder);
        });
    }

    function alertOnlyNewWarnings(messages, issueKeys) {
        if (!Array.isArray(messages) || !Array.isArray(issueKeys)) {
            return;
        }

        const activeKeys = new Set(issueKeys.filter(Boolean));
        const seenKeys = getSeenWarningKeys();
        let hasNewIssue = false;
        activeKeys.forEach(key => {
            if (!seenKeys.has(key)) {
                hasNewIssue = true;
                seenKeys.add(key);
            }
        });

        const prunedSeen = new Set(Array.from(seenKeys).filter(key => !key.startsWith('time:') && !key.startsWith('dup:') || activeKeys.has(key)));
        setSeenWarningKeys(prunedSeen);

        if (hasNewIssue && messages.length > 0) {
            showToast(messages.join('\n'), 'warning', 7000);
        }
    }

    function handleAutofillTriggers(event) {
        const target = event.target;
        if (!target || !target.dataset || !target.dataset.field) {
            return;
        }

        const field = target.dataset.field;
        if (field === 'registration') {
            handleRegistrationAutofill(target);
        } else if (field === 'flight_no_arr') {
            handleFlightAutofill(target, true);
        } else if (field === 'flight_no_dep') {
            handleFlightAutofill(target, false);
        }
    }

    async function handleRegistrationAutofill(input) {
        if (!input || isViewer) {
            return;
        }

        const registration = (input.value || '').trim();
        if (registration.length < 3) {
            return;
        }

        const row = input.closest('tr');
        if (!row) {
            return;
        }

        try {
            const data = await fetchJson(apronEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'getAircraftDetails', registration })
            });

            if (!data.success) {
                return;
            }

            const typeField = row.querySelector('input[data-field="aircraft_type"]');
            const opField = row.querySelector('input[data-field="operator_airline"]');
            const catField = row.querySelector('select[data-field="category"]');

            if (typeField && !typeField.value && data.aircraft_type) {
                typeField.value = data.aircraft_type;
            }

            if (opField && !opField.value && data.operator_airline) {
                opField.value = data.operator_airline;
            }

            // Autofill category -- normalize DB variants to match select option values
            // Note: do NOT guard with !catField.value — the select may have a default selected
            // value (truthy), blocking autofill. Always apply the DB value.
            if (catField && data.category) {
                const catMap = {
                    'komersial': 'Komersial', 'commercial': 'Komersial',
                    'charter':   'Charter',   'private':   'Charter',
                    'cargo':     'Cargo'
                };
                const normalized = catMap[(data.category || '').toLowerCase()] || data.category;
                catField.value = normalized;
            }
        } catch (error) {
            console.log('Registration autofill lookup failed:', error);
        }
    }

    async function handleFlightAutofill(input, isArrival) {
        if (!input || isViewer) {
            return;
        }

        const flightNo = (input.value || '').trim();
        if (flightNo.length < 2) {
            return;
        }

        const row = input.closest('tr');
        if (!row) {
            return;
        }

        try {
            const data = await fetchJson(apronEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'getFlightRoute', flight_no: flightNo })
            });

            if (!data.success || !data.default_route) {
                return;
            }

            const fieldName = isArrival ? 'from_location' : 'to_location';
            const field = row.querySelector(`input[data-field="${fieldName}"]`);
            if (field && !field.value) {
                field.value = data.default_route;
            }
        } catch (error) {
            console.log('Flight route lookup failed:', error);
        }
    }

    async function saveAllData() {
        if (isViewer) {
            showToast('You do not have permission to save changes.', 'error');
            return;
        }

        const changes = [];
        const newMovements = [];
        const tables = ['#master-movements-table', '#ron-data-table'];

        tables.forEach(selector => {
            document.querySelectorAll(`${selector} tbody tr[data-id]:not([data-id="new"])`).forEach(row => {
                const id = row.getAttribute('data-id');
                if (!id || id === '0') {
                    return;
                }

                row.querySelectorAll('input[data-field], select[data-field]').forEach(input => {
                    const field = input.dataset.field;
                    if (!field) {
                        return;
                    }

                    const original = input.getAttribute('data-original') ?? '';
                    const current = input.value ?? '';
                    if (original !== current) {
                        changes.push({ id, field, value: current });
                    }
                });
            });

            if (selector === '#master-movements-table') {
                document.querySelectorAll(`${selector} tbody tr[data-id="new"]`).forEach(row => {
                    const registrationInput = row.querySelector('input[data-field="registration"]');
                    const registration = registrationInput ? registrationInput.value.trim() : '';
                    if (!registration) {
                        return;
                    }

                    const movement = {};
                    row.querySelectorAll('input[data-field], select[data-field]').forEach(input => {
                        const field = input.dataset.field;
                        if (field) {
                            movement[field] = input.value ?? '';
                        }
                    });
                    movement.registration = registration;
                    newMovements.push(movement);
                });
            }
        });

        if (!changes.length && !newMovements.length) {
            showToast('No changes or new movements detected to save.');
            return;
        }

        const clientCheck = collectClientWarnings();
        alertOnlyNewWarnings([...clientCheck.warnings], [...clientCheck.warningKeys]);
        applyDuplicateFlightHighlighting(clientCheck.duplicateFlights);
        applyTimeOrderHighlighting();

        const saveButton = document.querySelector('[data-action="save-table"]');
        if (saveButton) {
            saveButton.dataset.originalText = saveButton.textContent;
            saveButton.textContent = 'Saving...';
            saveButton.disabled = true;
        }

        try {
            const serverWarnings = [];
            const serverDuplicates = new Set(clientCheck.duplicateFlights);

            if (changes.length) {
                const saveResponse = await fetchJson(masterEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_all_changes', changes })
                });
                (saveResponse.warnings || []).forEach(message => serverWarnings.push(message));
                (saveResponse.duplicate_flights || []).forEach(flight => serverDuplicates.add(String(flight || '').toUpperCase()));
            }

            for (const movement of newMovements) {
                const createResponse = await fetchJson(masterEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create_new_movement', ...movement })
                });
                (createResponse.warnings || []).forEach(message => serverWarnings.push(message));
                (createResponse.duplicate_flights || []).forEach(flight => serverDuplicates.add(String(flight || '').toUpperCase()));
            }

            applyDuplicateFlightHighlighting(Array.from(serverDuplicates));
            applyTimeOrderHighlighting();

            notifyApronChanged();
            skipUnloadWarning = true;
            window.location.reload();
        } catch (error) {
            console.error('Save error:', error);
            showToast('Error occurred while saving: ' + error.message, 'error', 7000);
        } finally {
            if (saveButton) {
                saveButton.textContent = saveButton.dataset.originalText || 'Save';
                saveButton.disabled = false;
                delete saveButton.dataset.originalText;
            }
        }
    }

    function loadMoreEmptyRows() {
        if (isViewer) {
            return;
        }

        const tableBody = document.querySelector('#master-movements-table tbody');
        if (!tableBody) {
            return;
        }

        const existingNewRows = tableBody.querySelectorAll('tr[data-id="new"]').length;
        const currentRows = tableBody.querySelectorAll('tr').length;
        const startRowNumber = currentRows + 1;

        for (let i = 0; i < 25; i++) {
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-id', 'new');
            newRow.setAttribute('data-new-index', existingNewRows + i);
            newRow.className = 'bg-blue-50 hover:bg-blue-100';

            const rowNumber = startRowNumber + i;
            newRow.innerHTML = `
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs text-center" readonly value="${rowNumber}"></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="registration" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="aircraft_type" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="on_block_time" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="off_block_time" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="parking_stand" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="from_location" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="to_location" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="flight_no_arr" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="flight_no_dep" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="operator_airline" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" data-field="remarks" data-original="" value=""></td>
                <td class="border border-gray-300 px-1 py-1">
                    <select class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" data-field="is_ron" data-original="0">
                        <option value="0" selected>No</option>
                        <option value="1">Yes</option>
                    </select>
                </td>
            `;

            tableBody.appendChild(newRow);
        }
    }

    // =========================================================================
    // Excel-like grid engine
    //
    // Two-mode model, like a real spreadsheet:
    //   NAV mode  — a cell is selected; arrows/Tab/Enter move the active cell,
    //               typing replaces the content, Delete clears the selection.
    //   EDIT mode — entered via double-click, F2 or typing; arrows move the
    //               text caret; Enter/Tab commit; Escape reverts the cell.
    //
    // Selection: drag, Shift+click, Shift+Arrows, Ctrl+Shift+Arrows, Ctrl+A,
    //            row-number click (row), header click (column).
    // Clipboard: Ctrl+C / Ctrl+X / Ctrl+V with real TSV — interoperates with
    //            Excel and Google Sheets. Paste fills/tiles like Excel and
    //            auto-appends empty rows when it overflows (master table).
    // Undo:      Ctrl+Z / Ctrl+Y for typing, paste, cut, delete and fills.
    // =========================================================================
    function setupExcelGrid(tableSelector, options = {}) {
        const table = document.querySelector(tableSelector);
        if (!table || !table.tBodies.length) {
            return;
        }

        const tbody = table.tBodies[0];
        const canAddRows = !!options.canAddRows;
        const UNDO_LIMIT = 100;

        table.classList.add('sheet-grid');

        const state = {
            anchor: null,      // {r, c} where selection started
            focus: null,       // {r, c} active cell
            mode: 'nav',
            editPrev: null,    // value before edit, for Escape
            dragging: false
        };
        const undoStack = [];
        const redoStack = [];
        // Last committed value per control, so undo knows the "before" state
        const committed = new WeakMap();
        let suppressChangeCapture = false;

        // --- geometry helpers (always read the live DOM: rows can be added) --
        const rowCount = () => tbody.rows.length;
        const colCount = () => (table.tHead && table.tHead.rows[0] ? table.tHead.rows[0].cells.length : (tbody.rows[0] ? tbody.rows[0].cells.length : 0));
        const cellAt = (r, c) => (tbody.rows[r] ? tbody.rows[r].cells[c] : null) || null;
        const controlAt = (r, c) => {
            const cell = cellAt(r, c);
            return cell ? cell.querySelector('input, select') : null;
        };
        const isEditableControl = el => {
            if (isViewer || !el || !el.dataset || !el.dataset.field) {
                return false;
            }
            return true;
        };

        function rectFromSelection() {
            if (!state.anchor || !state.focus) {
                return null;
            }
            return {
                r1: Math.min(state.anchor.r, state.focus.r),
                r2: Math.max(state.anchor.r, state.focus.r),
                c1: Math.min(state.anchor.c, state.focus.c),
                c2: Math.max(state.anchor.c, state.focus.c)
            };
        }

        function renderSelection() {
            table.querySelectorAll('td.selected, td.active-cell').forEach(td => {
                td.classList.remove('selected', 'active-cell');
            });
            const rect = rectFromSelection();
            if (!rect) {
                return;
            }
            for (let r = rect.r1; r <= rect.r2; r++) {
                for (let c = rect.c1; c <= rect.c2; c++) {
                    const cell = cellAt(r, c);
                    if (cell) {
                        cell.classList.add('selected');
                    }
                }
            }
            const active = cellAt(state.focus.r, state.focus.c);
            if (active) {
                active.classList.add('active-cell');
            }
        }

        function focusCell(r, c) {
            const cell = cellAt(r, c);
            if (!cell) {
                return;
            }
            const control = cell.querySelector('input, select');
            if (control) {
                if (control.tagName === 'INPUT' && !control.classList.contains('grid-editing')) {
                    control.readOnly = true;
                }
                control.focus({ preventScroll: true });
            } else {
                cell.tabIndex = -1;
                cell.focus({ preventScroll: true });
            }
            cell.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }

        function setSelection(anchor, focus) {
            state.anchor = anchor;
            state.focus = focus;
            renderSelection();
            focusCell(focus.r, focus.c);
        }

        function moveFocus(dr, dc, extend, toEdge) {
            if (!state.focus) {
                return;
            }
            let { r, c } = state.focus;
            if (toEdge) {
                r = dr === 0 ? r : (dr < 0 ? 0 : rowCount() - 1);
                c = dc === 0 ? c : (dc < 0 ? 0 : colCount() - 1);
            } else {
                r = Math.max(0, Math.min(rowCount() - 1, r + dr));
                c = Math.max(0, Math.min(colCount() - 1, c + dc));
            }
            const target = { r, c };
            setSelection(extend ? state.anchor : target, target);
        }

        // --- edit mode -------------------------------------------------------
        function beginEdit(clearFirst) {
            if (!state.focus || isViewer) {
                return false;
            }
            const control = controlAt(state.focus.r, state.focus.c);
            if (!isEditableControl(control) || control.tagName !== 'INPUT') {
                return false;
            }
            if (!committed.has(control)) {
                committed.set(control, control.value);
            }
            state.mode = 'edit';
            state.editPrev = control.value;
            control.readOnly = false;
            control.classList.add('grid-editing');
            if (clearFirst) {
                control.value = '';
            }
            control.focus();
            const end = control.value.length;
            try {
                control.setSelectionRange(end, end);
            } catch (e) { /* not all input types support it */ }
            return true;
        }

        function endEdit(revert) {
            if (state.mode !== 'edit') {
                return;
            }
            const control = controlAt(state.focus.r, state.focus.c);
            if (control && control.tagName === 'INPUT') {
                if (revert && state.editPrev !== null) {
                    control.value = state.editPrev;
                    dispatchValueEvents(control);
                } else if (control.value !== state.editPrev) {
                    recordUndo([{ el: control, before: state.editPrev, after: control.value }]);
                    committed.set(control, control.value);
                    // Fire the change hooks (autofill, warnings) exactly once
                    suppressChangeCapture = true;
                    control.dispatchEvent(new Event('change', { bubbles: true }));
                    suppressChangeCapture = false;
                }
                control.readOnly = true;
                control.classList.remove('grid-editing');
            }
            state.mode = 'nav';
            state.editPrev = null;
        }

        // --- mutations, events and undo --------------------------------------
        function dispatchValueEvents(el) {
            suppressChangeCapture = true;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
            suppressChangeCapture = false;
        }

        function normalizeForSelect(el, value) {
            const lowered = String(value).trim().toLowerCase();
            const truthy = ['1', 'yes', 'y', 'true'];
            const falsy = ['0', 'no', 'n', 'false', ''];
            if (truthy.includes(lowered)) {
                return '1';
            }
            if (falsy.includes(lowered)) {
                return '0';
            }
            return null; // unmappable — skip this cell
        }

        function applyBatch(items) {
            const applied = [];
            items.forEach(({ el, after }) => {
                let value = after;
                if (el.tagName === 'SELECT') {
                    value = normalizeForSelect(el, after);
                    if (value === null) {
                        return;
                    }
                }
                const before = committed.has(el) ? committed.get(el) : el.value;
                if (before === value) {
                    return;
                }
                el.value = value;
                committed.set(el, value);
                dispatchValueEvents(el);
                applied.push({ el, before, after: value });
            });
            if (applied.length) {
                recordUndo(applied);
            }
            return applied.length;
        }

        function recordUndo(itemsApplied) {
            undoStack.push(itemsApplied);
            if (undoStack.length > UNDO_LIMIT) {
                undoStack.shift();
            }
            redoStack.length = 0;
        }

        function undo() {
            const op = undoStack.pop();
            if (!op) {
                return;
            }
            op.forEach(({ el, before }) => {
                el.value = before;
                committed.set(el, before);
                dispatchValueEvents(el);
            });
            redoStack.push(op);
        }

        function redo() {
            const op = redoStack.pop();
            if (!op) {
                return;
            }
            op.forEach(({ el, after }) => {
                el.value = after;
                committed.set(el, after);
                dispatchValueEvents(el);
            });
            undoStack.push(op);
        }

        // Capture direct user edits (e.g. select dropdown changes) for undo
        tbody.addEventListener('change', event => {
            if (suppressChangeCapture) {
                return;
            }
            const el = event.target;
            if (!el.matches || !el.matches('input[data-field], select[data-field]')) {
                return;
            }
            const before = committed.has(el) ? committed.get(el) : (el.getAttribute('data-original') ?? '');
            if (before !== el.value) {
                recordUndo([{ el, before, after: el.value }]);
            }
            committed.set(el, el.value);
        });

        tbody.addEventListener('focusin', event => {
            const el = event.target;
            if (el.matches && el.matches('input[data-field], select[data-field]') && !committed.has(el)) {
                committed.set(el, el.value);
            }
        });

        // --- clipboard --------------------------------------------------------
        function selectionToTsv() {
            const rect = rectFromSelection();
            if (!rect) {
                return '';
            }
            const lines = [];
            for (let r = rect.r1; r <= rect.r2; r++) {
                const rowValues = [];
                for (let c = rect.c1; c <= rect.c2; c++) {
                    const control = controlAt(r, c);
                    rowValues.push(control ? control.value : (cellAt(r, c)?.textContent.trim() ?? ''));
                }
                lines.push(rowValues.join('\t'));
            }
            return lines.join('\n');
        }

        function clearSelectionValues() {
            const rect = rectFromSelection();
            if (!rect || isViewer) {
                return;
            }
            const items = [];
            for (let r = rect.r1; r <= rect.r2; r++) {
                for (let c = rect.c1; c <= rect.c2; c++) {
                    const control = controlAt(r, c);
                    if (isEditableControl(control)) {
                        items.push({ el: control, after: control.tagName === 'SELECT' ? '0' : '' });
                    }
                }
            }
            applyBatch(items);
        }

        function parseClipboardMatrix(text) {
            const rows = String(text).replace(/\r\n?/g, '\n').split('\n');
            while (rows.length && rows[rows.length - 1] === '') {
                rows.pop();
            }
            if (!rows.length) {
                return null;
            }
            return rows.map(line => line.split('\t'));
        }

        function ensureRowCapacity(neededRows) {
            if (!canAddRows || typeof loadMoreEmptyRows !== 'function') {
                return;
            }
            let guard = 0;
            while (rowCount() < neededRows && guard < 20) {
                loadMoreEmptyRows();
                guard += 1;
            }
        }

        function pasteMatrix(matrix) {
            const rect = rectFromSelection();
            if (!rect || isViewer || !matrix) {
                return;
            }

            const srcRows = matrix.length;
            const srcCols = Math.max(...matrix.map(row => row.length));

            // Excel rules: a range fills/tiles when it's an exact multiple of
            // the copied block, otherwise the block pastes once at the top-left.
            const selRows = rect.r2 - rect.r1 + 1;
            const selCols = rect.c2 - rect.c1 + 1;
            let repeatRows = 1;
            let repeatCols = 1;
            if (selRows % srcRows === 0 && selCols % srcCols === 0 && (selRows > srcRows || selCols > srcCols)) {
                repeatRows = selRows / srcRows;
                repeatCols = selCols / srcCols;
            }

            const totalRows = srcRows * repeatRows;
            const totalCols = srcCols * repeatCols;

            ensureRowCapacity(rect.r1 + totalRows);

            const items = [];
            let clippedBottom = false;
            for (let i = 0; i < totalRows; i++) {
                const r = rect.r1 + i;
                if (r >= rowCount()) {
                    clippedBottom = true;
                    break;
                }
                for (let j = 0; j < totalCols; j++) {
                    const c = rect.c1 + j;
                    if (c >= colCount()) {
                        continue; // clipped on the right
                    }
                    const control = controlAt(r, c);
                    if (!isEditableControl(control)) {
                        continue; // row-number column, role-locked, etc.
                    }
                    const srcRow = matrix[i % srcRows];
                    items.push({ el: control, after: srcRow[j % srcCols] ?? '' });
                }
            }

            applyBatch(items);

            // Select the pasted rectangle, Excel-style
            const lastRow = Math.min(rect.r1 + totalRows - 1, rowCount() - 1);
            const lastCol = Math.min(rect.c1 + totalCols - 1, colCount() - 1);
            setSelection({ r: rect.r1, c: rect.c1 }, { r: lastRow, c: lastCol });

            if (clippedBottom) {
                showToast('Pasted data was larger than the table and was clipped at the bottom.', 'warning');
            }
        }

        function fillFromEdge(direction) {
            // Ctrl+D fills down from the first row; Ctrl+R fills right
            const rect = rectFromSelection();
            if (!rect || isViewer) {
                return;
            }
            const items = [];
            if (direction === 'down') {
                for (let c = rect.c1; c <= rect.c2; c++) {
                    const source = controlAt(rect.r1, c);
                    if (!source) {
                        continue;
                    }
                    for (let r = rect.r1 + 1; r <= rect.r2; r++) {
                        const control = controlAt(r, c);
                        if (isEditableControl(control)) {
                            items.push({ el: control, after: source.value });
                        }
                    }
                }
            } else {
                for (let r = rect.r1; r <= rect.r2; r++) {
                    const source = controlAt(r, rect.c1);
                    if (!source) {
                        continue;
                    }
                    for (let c = rect.c1 + 1; c <= rect.c2; c++) {
                        const control = controlAt(r, c);
                        if (isEditableControl(control)) {
                            items.push({ el: control, after: source.value });
                        }
                    }
                }
            }
            applyBatch(items);
        }

        const gridHasFocus = () => table.contains(document.activeElement) && state.focus !== null;

        document.addEventListener('copy', event => {
            if (!gridHasFocus() || state.mode !== 'nav') {
                return;
            }
            event.clipboardData.setData('text/plain', selectionToTsv());
            event.preventDefault();
        });

        document.addEventListener('cut', event => {
            if (!gridHasFocus() || state.mode !== 'nav') {
                return;
            }
            event.clipboardData.setData('text/plain', selectionToTsv());
            event.preventDefault();
            clearSelectionValues();
        });

        document.addEventListener('paste', event => {
            if (!gridHasFocus() || state.mode !== 'nav' || isViewer) {
                return;
            }
            const text = event.clipboardData.getData('text/plain');
            if (!text) {
                return;
            }
            event.preventDefault();
            pasteMatrix(parseClipboardMatrix(text));
        });

        // --- mouse ------------------------------------------------------------
        tbody.addEventListener('mousedown', event => {
            const cell = event.target.closest('td');
            if (!cell || !tbody.contains(cell)) {
                return;
            }
            const r = cell.parentElement.sectionRowIndex;
            const c = cell.cellIndex;

            // Clicking the active cell while editing keeps native caret behavior
            if (state.mode === 'edit' && state.focus && state.focus.r === r && state.focus.c === c) {
                return;
            }
            endEdit(false);

            // Native <select> needs its own mousedown to open the dropdown
            if (event.target.tagName === 'SELECT') {
                setSelection({ r, c }, { r, c });
                return;
            }

            event.preventDefault();

            if (event.shiftKey && state.anchor) {
                setSelection(state.anchor, { r, c });
                return;
            }

            if (c === 0) {
                // Row-number column: select the whole row
                setSelection({ r, c: 0 }, { r, c: colCount() - 1 });
                return;
            }

            state.dragging = true;
            setSelection({ r, c }, { r, c });
        });

        tbody.addEventListener('mouseover', event => {
            if (!state.dragging) {
                return;
            }
            const cell = event.target.closest('td');
            if (!cell || !tbody.contains(cell)) {
                return;
            }
            setSelection(state.anchor, {
                r: cell.parentElement.sectionRowIndex,
                c: cell.cellIndex
            });
        });

        window.addEventListener('mouseup', () => {
            state.dragging = false;
        });

        tbody.addEventListener('dblclick', event => {
            const cell = event.target.closest('td');
            if (!cell) {
                return;
            }
            const r = cell.parentElement.sectionRowIndex;
            const c = cell.cellIndex;
            setSelection({ r, c }, { r, c });
            beginEdit(false);

            // Double-clicking an off block cell stamps the current time.
            // Routed through applyBatch so it queues the auto-save and lands on
            // the undo stack exactly like a typed edit — Ctrl+Z reverts it.
            const offBlock = cell.querySelector('input[data-field="off_block_time"]');
            if (offBlock && !offBlock.readOnly && !offBlock.disabled) {
                applyBatch([{ el: offBlock, after: currentHhMm() }]);
            }
        });

        // Header click selects the whole column
        if (table.tHead) {
            table.tHead.addEventListener('click', event => {
                const th = event.target.closest('th');
                if (!th || rowCount() === 0) {
                    return;
                }
                endEdit(false);
                const c = th.cellIndex;
                setSelection({ r: 0, c }, { r: rowCount() - 1, c });
            });
        }

        // --- keyboard -----------------------------------------------------------
        table.addEventListener('keydown', event => {
            if (!state.focus) {
                return;
            }

            const key = event.key;
            const ctrl = event.ctrlKey || event.metaKey;
            const arrows = {
                ArrowUp: [-1, 0],
                ArrowDown: [1, 0],
                ArrowLeft: [0, -1],
                ArrowRight: [0, 1]
            };

            if (state.mode === 'edit') {
                if (arrows[key]) {
                    // Always navigable: an arrow while typing commits the cell
                    // and moves on, exactly like typing in Excel.
                    event.preventDefault();
                    endEdit(false);
                    const [dr, dc] = arrows[key];
                    moveFocus(dr, dc, event.shiftKey, ctrl);
                } else if (key === 'Enter') {
                    event.preventDefault();
                    endEdit(false);
                    moveFocus(event.shiftKey ? -1 : 1, 0, false, false);
                } else if (key === 'Tab') {
                    event.preventDefault();
                    endEdit(false);
                    moveFocus(0, event.shiftKey ? -1 : 1, false, false);
                } else if (key === 'Escape') {
                    event.preventDefault();
                    endEdit(true);
                    focusCell(state.focus.r, state.focus.c);
                }
                return; // everything else is native text editing
            }

            // --- NAV mode ---

            if (arrows[key]) {
                event.preventDefault();
                const [dr, dc] = arrows[key];
                moveFocus(dr, dc, event.shiftKey, ctrl);
                return;
            }

            switch (key) {
                case 'Tab':
                    event.preventDefault();
                    moveFocus(0, event.shiftKey ? -1 : 1, false, false);
                    return;
                case 'Enter':
                    event.preventDefault();
                    moveFocus(event.shiftKey ? -1 : 1, 0, false, false);
                    return;
                case 'Home':
                    event.preventDefault();
                    if (ctrl) {
                        setSelection({ r: 0, c: 0 }, { r: 0, c: 0 });
                    } else {
                        setSelection({ r: state.focus.r, c: 0 }, { r: state.focus.r, c: 0 });
                    }
                    return;
                case 'End': {
                    event.preventDefault();
                    const lastC = colCount() - 1;
                    if (ctrl) {
                        const lastR = rowCount() - 1;
                        setSelection({ r: lastR, c: lastC }, { r: lastR, c: lastC });
                    } else {
                        setSelection({ r: state.focus.r, c: lastC }, { r: state.focus.r, c: lastC });
                    }
                    return;
                }
                case 'Delete':
                case 'Backspace':
                    if (!isViewer) {
                        event.preventDefault();
                        clearSelectionValues();
                    }
                    return;
                case 'F2':
                    event.preventDefault();
                    beginEdit(false);
                    return;
                case 'Escape':
                    event.preventDefault();
                    setSelection(state.focus, state.focus);
                    return;
                default:
                    break;
            }

            if (ctrl) {
                const lowered = key.toLowerCase();
                if (lowered === 'a') {
                    event.preventDefault();
                    setSelection({ r: 0, c: 0 }, { r: rowCount() - 1, c: colCount() - 1 });
                } else if (lowered === 'z' && !isViewer) {
                    event.preventDefault();
                    if (event.shiftKey) {
                        redo();
                    } else {
                        undo();
                    }
                } else if (lowered === 'y' && !isViewer) {
                    event.preventDefault();
                    redo();
                } else if (lowered === 'd' && !isViewer) {
                    event.preventDefault();
                    fillFromEdge('down');
                } else if (lowered === 'r' && !isViewer) {
                    event.preventDefault();
                    fillFromEdge('right');
                }
                // Ctrl+C/X/V are handled by the copy/cut/paste events above
                return;
            }

            // Typing a printable character replaces the cell and starts editing
            if (key.length === 1 && !event.altKey) {
                if (beginEdit(true)) {
                    // don't preventDefault: the keystroke lands in the input
                }
            }
        });

        // Commit the edit if focus leaves the grid entirely (e.g. clicking Save)
        table.addEventListener('focusout', () => {
            setTimeout(() => {
                if (!table.contains(document.activeElement)) {
                    endEdit(false);
                    state.dragging = false;
                }
            }, 0);
        });

        // Lock all editable inputs into NAV mode initially
        tbody.querySelectorAll('input[data-field]').forEach(input => {
            if (!isViewer) {
                input.readOnly = true;
            }
        });
    }

    window.saveAllData = saveAllData;
    window.loadMoreEmptyRows = loadMoreEmptyRows;
})();
