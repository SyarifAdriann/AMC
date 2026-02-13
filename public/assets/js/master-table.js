(function () {
    const config = window.masterTableConfig || {};
    const endpoints = config.endpoints || {};
    const masterEndpoint = resolveEndpoint(endpoints.master || 'api/master-table');
    const apronEndpoint = resolveEndpoint(endpoints.apron || 'api/apron');
    const resetUrl = config.resetUrl || null;
    const userRole = config.userRole || 'viewer';
    const isViewer = userRole === 'viewer';
    const warningStorageKey = 'amc_master_warning_seen';

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

    function normaliseJsonResponse(text) {
        if (typeof text !== 'string') {
            return '';
        }

        let cleaned = text.replace(/^\uFEFF/, '').trim();
        const firstBrace = cleaned.indexOf('{');
        const firstBracket = cleaned.indexOf('[');
        let firstJsonIndex = -1;

        if (firstBrace !== -1 && firstBracket !== -1) {
            firstJsonIndex = Math.min(firstBrace, firstBracket);
        } else if (firstBrace !== -1) {
            firstJsonIndex = firstBrace;
        } else if (firstBracket !== -1) {
            firstJsonIndex = firstBracket;
        }

        if (firstJsonIndex > 0) {
            cleaned = cleaned.slice(firstJsonIndex);
        }

        const firstChar = cleaned.charAt(0);
        const secondChar = cleaned.charAt(1);
        if ((firstChar === '"' || firstChar === "'") && (secondChar === '{' || secondChar === '[')) {
            cleaned = cleaned.slice(1);
            const lastChar = cleaned.charAt(cleaned.length - 1);
            if (lastChar === firstChar) {
                cleaned = cleaned.slice(0, -1);
            }
        }

        return cleaned.trim();
    }

    async function fetchJson(url, options = {}) {
        const fetchOptions = { credentials: 'same-origin', ...options };
        const response = await fetch(url, fetchOptions);
        const raw = await response.text();
        const cleaned = normaliseJsonResponse(raw);

        if (!response.ok) {
            const error = new Error(cleaned || response.statusText || `HTTP ${response.status}`);
            error.status = response.status;
            error.raw = raw;
            throw error;
        }

        if (!cleaned) {
            return {};
        }

        try {
            return JSON.parse(cleaned);
        } catch (parseError) {
            const error = new Error(`Invalid JSON response: ${parseError.message}`);
            error.raw = raw;
            error.status = response.status;
            throw error;
        }
    }

    function initialise() {
        if (isViewer) {
            document.querySelectorAll('#master-movements-table input, #master-movements-table select, #ron-data-table input, #ron-data-table select').forEach(el => {
                el.disabled = true;
                el.classList.add('cursor-not-allowed');
            });
        }

        enableTableNav('#master-movements-table');
        enableTableNav('#ron-data-table');
        setupSheetBehavior('#master-movements-table');
        setupSheetBehavior('#ron-data-table');

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
                        alert(data.message || 'Unable to set RON status.');
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    console.error('Set RON error:', error);
                    alert('Failed to update RON status: ' + error.message);
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

            if (onMinutes !== null && offMinutes !== null && offMinutes < onMinutes) {
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

            const onMinutes = extractTimeToMinutes(onInput.value || '');
            const offMinutes = extractTimeToMinutes(offInput.value || '');
            const invalidOrder = onMinutes !== null && offMinutes !== null && offMinutes < onMinutes;

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
            alert(messages.join('\n') + '\n\nThe system will still process your input.');
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

            if (typeField && !typeField.value && data.aircraft_type) {
                typeField.value = data.aircraft_type;
            }

            if (opField && !opField.value && data.operator_airline) {
                opField.value = data.operator_airline;
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
            alert('You do not have permission to save changes.');
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
            alert('No changes or new movements detected to save.');
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

            window.location.reload();
        } catch (error) {
            console.error('Save error:', error);
            alert('Error occurred while saving: ' + error.message);
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

    function enableTableNav(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) {
            return;
        }

        table.addEventListener('keydown', event => {
            const activeElement = document.activeElement;
            if (!activeElement || (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'SELECT')) {
                return;
            }

            const cell = activeElement.closest('td');
            if (!cell) {
                return;
            }

            const row = cell.parentElement;
            const cellIndex = Array.from(row.children).indexOf(cell);
            let nextRow = null;

            switch (event.key) {
                case 'ArrowUp':
                    nextRow = row.previousElementSibling;
                    if (nextRow) {
                        const targetUp = nextRow.children[cellIndex]?.querySelector('input, select');
                        if (targetUp) {
                            targetUp.focus();
                        }
                    }
                    break;
                case 'ArrowDown':
                case 'Enter':
                    event.preventDefault();
                    nextRow = row.nextElementSibling;
                    if (nextRow) {
                        const targetDown = nextRow.children[cellIndex]?.querySelector('input, select');
                        if (targetDown) {
                            targetDown.focus();
                        }
                    }
                    break;
                case 'ArrowLeft':
                    if (activeElement.selectionStart === 0) {
                        const previousCell = cell.previousElementSibling;
                        if (previousCell) {
                            const previousInput = previousCell.querySelector('input, select');
                            if (previousInput) {
                                previousInput.focus();
                            }
                        }
                    }
                    break;
                case 'ArrowRight':
                    if (activeElement.selectionStart === (activeElement.value || '').length) {
                        const nextCell = cell.nextElementSibling;
                        if (nextCell) {
                            const nextInput = nextCell.querySelector('input, select');
                            if (nextInput) {
                                nextInput.focus();
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        });
    }

    function setupSheetBehavior(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) {
            return;
        }

        let isMouseDown = false;
        let startCell = null;

        table.addEventListener('mousedown', event => {
            const target = event.target;
            if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
                isMouseDown = true;
                startCell = target.closest('td');
                clearSelection();
                selectCells(startCell, startCell);
            }
        });

        table.addEventListener('mouseover', event => {
            if (!isMouseDown) {
                return;
            }

            const target = event.target;
            if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
                const endCell = target.closest('td');
                selectCells(startCell, endCell);
            }
        });

        window.addEventListener('mouseup', () => {
            isMouseDown = false;
            startCell = null;
        });

        document.addEventListener('copy', event => {
            if (!table.querySelector('td.selected')) {
                return;
            }

            const rows = new Map();
            table.querySelectorAll('td.selected').forEach(td => {
                const rowIndex = td.parentElement.rowIndex;
                if (!rows.has(rowIndex)) {
                    rows.set(rowIndex, []);
                }

                const input = td.querySelector('input, select');
                rows.get(rowIndex).push(input ? input.value : '');
            });

            let clipboardText = '';
            rows.forEach(rowData => {
                clipboardText += rowData.join('\t') + '\n';
            });

            event.clipboardData.setData('text/plain', clipboardText);
            event.preventDefault();
        });

        function selectCells(start, end) {
            clearSelection();
            if (!start || !end) {
                return;
            }

            const startRow = start.parentElement.rowIndex;
            const startCol = start.cellIndex;
            const endRow = end.parentElement.rowIndex;
            const endCol = end.cellIndex;

            const minRow = Math.min(startRow, endRow);
            const maxRow = Math.max(startRow, endRow);
            const minCol = Math.min(startCol, endCol);
            const maxCol = Math.max(startCol, endCol);

            for (let rowIndex = minRow; rowIndex <= maxRow; rowIndex++) {
                const row = table.rows[rowIndex];
                for (let colIndex = minCol; colIndex <= maxCol; colIndex++) {
                    const cell = row?.cells[colIndex];
                    if (cell) {
                        cell.classList.add('selected');
                    }
                }
            }
        }

        function clearSelection() {
            table.querySelectorAll('td.selected').forEach(td => td.classList.remove('selected'));
        }
    }

    window.saveAllData = saveAllData;
    window.loadMoreEmptyRows = loadMoreEmptyRows;
})();
