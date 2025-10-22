(function () {
    const config = window.masterTableConfig || {};
    const endpoints = config.endpoints || {};
    const masterEndpoint = endpoints.master || 'api/master-table';
    const apronEndpoint = endpoints.apron || 'api/apron';
    const userRole = config.userRole || 'viewer';
    const isViewer = userRole === 'viewer';


document.addEventListener('DOMContentLoaded', () => {
    if (userRole === 'viewer') {
        document.querySelectorAll('#master-movements-table input, #master-movements-table select, #ron-data-table input, #ron-data-table select').forEach(el => {
            el.disabled = true;
        });
    }

    enableTableNav('#master-movements-table');
    setupSheetBehavior('#master-movements-table');
    enableTableNav('#ron-data-table');
    setupSheetBehavior('#ron-data-table');

    document.getElementById('reset-filters').addEventListener('click', () => {
        window.location.href = 'master-table.php';
    });

    const setRonBtn = document.getElementById('set-ron-btn');
    if (setRonBtn) {
        setRonBtn.addEventListener('click', () => {
            if (confirm('Set all current movements as RON?')) {
                fetch(masterEndpoint, { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ action: 'setRON' }) 
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                }).catch(() => alert('Network error'));
            }
        });
    }
    
    // Use event delegation for autofill
    const masterTable = document.querySelector('#master-movements-table tbody');
    if(masterTable) {
        masterTable.addEventListener('change', function(e) {
            const input = e.target;
            const field = input.dataset.field;
            if (field === 'registration') {
                handleRegistrationAutofill(input);
            } else if (field === 'flight_no_arr') {
                handleFlightAutofill(input, true);
            } else if (field === 'flight_no_dep') {
                handleFlightAutofill(input, false);
            }
        });
    }
});

async function saveAllData() {
    if (userRole === 'viewer') {
        alert('You do not have permission to save changes.');
        return;
    }

    const changes = [];
    const newMovements = [];
    const tables = ['#master-movements-table', '#ron-data-table'];

    tables.forEach(tableSelector => {
        document.querySelectorAll(`${tableSelector} tbody tr[data-id]:not([data-id="new"])`).forEach(row => {
            const id = row.getAttribute('data-id');
            if (!id || id === '0') return;
            row.querySelectorAll('input[data-field], select[data-field]').forEach(input => {
                const original = input.getAttribute('data-original') || '';
                const current = input.value || '';
                if (original !== current) {
                    changes.push({ id: id, field: input.getAttribute('data-field'), value: current });
                }
            });
        });

        if (tableSelector === '#master-movements-table') {
            document.querySelectorAll(`${tableSelector} tbody tr[data-id="new"]`).forEach(row => {
                const registrationInput = row.querySelector('input[data-field="registration"]');
                const registration = registrationInput ? registrationInput.value.trim() : '';
                if (registration) {
                    const newMovement = { registration: registration };
                    row.querySelectorAll('input[data-field], select[data-field]').forEach(input => {
                        const field = input.getAttribute('data-field');
                        if (field !== 'registration') {
                            newMovement[field] = input.value || '';
                        }
                    });
                    newMovements.push(newMovement);
                }
            });
        }
    });

    const totalOperations = changes.length + newMovements.length;
    console.log('Changes to save:', changes);
    console.log('New movements to create:', newMovements);
    if (totalOperations === 0) {
        alert('No changes or new movements detected to save.');
        return;
    }

    const saveButton = document.querySelector('.table-header-actions button[onclick^="saveAllData"]');
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    try {
        if (changes.length > 0) {
            const response = await fetch(masterEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=save_all_changes&changes=${encodeURIComponent(JSON.stringify(changes))}`
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to save existing changes.');
            }
        }

        for (const movement of newMovements) {
            const formData = new URLSearchParams();
            formData.append('action', 'create_new_movement');
            Object.keys(movement).forEach(key => formData.append(key, movement[key]));

            const response = await fetch(masterEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || `Failed to create new movement for ${movement.registration}.`);
            }
        }

        window.location.reload();

    } catch (error) {
        alert('Error occurred while saving: ' + error.message);
        console.error('Save error:', error);
    } finally {
        saveButton.textContent = 'Save';
        saveButton.disabled = false;
    }
}

function handleRegistrationAutofill(registrationInput) {
    const registration = registrationInput.value;
    if (!registration || registration.length < 3) return;
    const row = registrationInput.closest('tr');
    if (!row) return;

    fetch(apronEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getAircraftDetails', registration: registration })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const typeField = row.querySelector('input[data-field="aircraft_type"]');
            const opField = row.querySelector('input[data-field="operator_airline"]');
            if (typeField && !typeField.value && data.aircraft_type) typeField.value = data.aircraft_type;
            if (opField && !opField.value && data.operator_airline) opField.value = data.operator_airline;
        }
    })
    .catch(error => console.log('Autofill lookup failed (normal if not in database):', error));
}

function handleFlightAutofill(flightInput, isArrival) {
    const flightNo = flightInput.value;
    if (!flightNo || flightNo.length < 2) return;
    const row = flightInput.closest('tr');
    if (!row) return;

    fetch(apronEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getFlightRoute', flight_no: flightNo })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.default_route) {
            const targetField = isArrival ? 'from_location' : 'to_location';
            const field = row.querySelector(`input[data-field="${targetField}"]`);
            if (field && !field.value) field.value = data.default_route;
        }
    })
    .catch(error => console.log('Flight route lookup failed (normal if not in database):', error));
}

function loadMoreEmptyRows() {
    const table = document.querySelector('#master-movements-table tbody');
    const currentRows = table.querySelectorAll('tr').length;
    const nextRowNumber = currentRows + 1;
    
    for (let i = 0; i < 25; i++) {
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-id', 'new');
        newRow.className = 'empty-row';
        newRow.setAttribute('data-new-index', currentRows + i);
        
        newRow.innerHTML = `
            newRow.innerHTML = `
            <td><input readonly value="${nextRowNumber + i}"></td>
            <td><input value="" data-field="registration" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="aircraft_type" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="on_block_time" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="off_block_time" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="parking_stand" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="from_location" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="to_location" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="flight_no_arr" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="flight_no_dep" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="operator_airline" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="remarks" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td>
                ${!isViewer ? `
                    <select data-field="is_ron" data-original="0">
                        <option value="0" selected>No</option>
                        <option value="1">Yes</option>
                    </select>
                ` : 'No'}
            </td>
        `;
        `;
        
        table.appendChild(newRow);
    }
}

// ... (rest of JS functions: enableTableNav, setupSheetBehavior, etc. are assumed to be here and unchanged) ...
function enableTableNav(tableSelector) {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    table.addEventListener('keydown', function(e) {
        const activeEl = document.activeElement;
        if (activeEl.tagName !== 'INPUT' && activeEl.tagName !== 'SELECT') return;

        const cell = activeEl.closest('td');
        if (!cell) return;

        const row = cell.parentElement;
        const cellIndex = Array.from(row.children).indexOf(cell);
        let nextRow, nextCell;

        switch (e.key) {
            case 'ArrowUp':
                nextRow = row.previousElementSibling;
                if (nextRow) {
                    nextCell = nextRow.children[cellIndex].querySelector('input, select');
                    if (nextCell) nextCell.focus();
                }
                break;
            case 'ArrowDown':
            case 'Enter':
                e.preventDefault();
                nextRow = row.nextElementSibling;
                if (nextRow) {
                    nextCell = nextRow.children[cellIndex].querySelector('input, select');
                    if (nextCell) nextCell.focus();
                }
                break;
            case 'ArrowLeft':
                if (activeEl.selectionStart === 0) {
                    const prevCell = cell.previousElementSibling;
                    if (prevCell) {
                        const prevInput = prevCell.querySelector('input, select');
                        if (prevInput) prevInput.focus();
                    }
                }
                break;
            case 'ArrowRight':
                if (activeEl.selectionStart === activeEl.value.length) {
                    const nextCell = cell.nextElementSibling;
                    if (nextCell) {
                        const nextInput = nextCell.querySelector('input, select');
                        if (nextInput) nextInput.focus();
                    }
                }
                break;
        }
    });
}

function setupSheetBehavior(tableSelector) {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    let isMouseDown = false;
    let startCell = null;
    let endCell = null;

    table.addEventListener('mousedown', function(e) {
        const target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
            isMouseDown = true;
            startCell = target.closest('td');
            clearSelection();
            selectCells(startCell, startCell);
        }
    });

    table.addEventListener('mouseover', function(e) {
        if (isMouseDown) {
            const target = e.target;
            if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
                endCell = target.closest('td');
                selectCells(startCell, endCell);
            }
        }
    });

    window.addEventListener('mouseup', function() {
        isMouseDown = false;
        startCell = null;
        endCell = null;
    });

    document.addEventListener('copy', function(e) {
        const selectedTds = table.querySelectorAll('td.selected');
        if (selectedTds.length > 0) {
            const rows = new Map();
            selectedTds.forEach(td => {
                const rowIndex = td.parentElement.rowIndex;
                if (!rows.has(rowIndex)) {
                    rows.set(rowIndex, []);
                }
                const input = td.querySelector('input, select');
                rows.get(rowIndex).push(input ? input.value : '');
            });

            let clipboardData = '';
            rows.forEach(rowData => {
                clipboardData += rowData.join('\t') + '\n';
            });

            e.clipboardData.setData('text/plain', clipboardData);
            e.preventDefault();
        }
    });

    function selectCells(start, end) {
        clearSelection();
        if (!start || !end) return;

        const startRow = start.parentElement.rowIndex;
        const startCol = start.cellIndex;
        const endRow = end.parentElement.rowIndex;
        const endCol = end.cellIndex;

        const minRow = Math.min(startRow, endRow);
        const maxRow = Math.max(startRow, endRow);
        const minCol = Math.min(startCol, endCol);
        const maxCol = Math.max(startCol, endCol);

        for (let i = minRow; i <= maxRow; i++) {
            const row = table.rows[i];
            for (let j = minCol; j <= maxCol; j++) {
                const cell = row.cells[j];
                if (cell) {
                    cell.classList.add('selected');
                }
            }
        }
    }

    function clearSelection() {
        table.querySelectorAll('td.selected').forEach(td => {
            td.classList.remove('selected');
        });
    }
}
</script>
    

document.addEventListener('DOMContentLoaded', () => {
    if (userRole === 'viewer') {
        document.querySelectorAll('#master-movements-table input, #master-movements-table select, #ron-data-table input, #ron-data-table select').forEach(el => {
            el.disabled = true;
        });
    }

    enableTableNav('#master-movements-table');
    setupSheetBehavior('#master-movements-table');
    enableTableNav('#ron-data-table');
    setupSheetBehavior('#ron-data-table');

    document.getElementById('reset-filters').addEventListener('click', () => {
        window.location.href = 'master-table.php';
    });

    const setRonBtn = document.getElementById('set-ron-btn');
    if (setRonBtn) {
        setRonBtn.addEventListener('click', () => {
            if (confirm('Set all current movements as RON?')) {
                fetch(masterEndpoint, { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ action: 'setRON' }) 
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                }).catch(() => alert('Network error'));
            }
        });
    }
    
    // Use event delegation for autofill
    const masterTable = document.querySelector('#master-movements-table tbody');
    if(masterTable) {
        masterTable.addEventListener('change', function(e) {
            const input = e.target;
            const field = input.dataset.field;
            if (field === 'registration') {
                handleRegistrationAutofill(input);
            } else if (field === 'flight_no_arr') {
                handleFlightAutofill(input, true);
            } else if (field === 'flight_no_dep') {
                handleFlightAutofill(input, false);
            }
        });
    }
});

async function saveAllData() {
    if (userRole === 'viewer') {
        alert('You do not have permission to save changes.');
        return;
    }

    const changes = [];
    const newMovements = [];
    const tables = ['#master-movements-table', '#ron-data-table'];

    tables.forEach(tableSelector => {
        document.querySelectorAll(`${tableSelector} tbody tr[data-id]:not([data-id="new"])`).forEach(row => {
            const id = row.getAttribute('data-id');
            if (!id || id === '0') return;
            row.querySelectorAll('input[data-field], select[data-field]').forEach(input => {
                const original = input.getAttribute('data-original') || '';
                const current = input.value || '';
                if (original !== current) {
                    changes.push({ id: id, field: input.getAttribute('data-field'), value: current });
                }
            });
        });

        if (tableSelector === '#master-movements-table') {
            document.querySelectorAll(`${tableSelector} tbody tr[data-id="new"]`).forEach(row => {
                const registrationInput = row.querySelector('input[data-field="registration"]');
                const registration = registrationInput ? registrationInput.value.trim() : '';
                if (registration) {
                    const newMovement = { registration: registration };
                    row.querySelectorAll('input[data-field], select[data-field]').forEach(input => {
                        const field = input.getAttribute('data-field');
                        if (field !== 'registration') {
                            newMovement[field] = input.value || '';
                        }
                    });
                    newMovements.push(newMovement);
                }
            });
        }
    });

    const totalOperations = changes.length + newMovements.length;
    console.log('Changes to save:', changes);
    console.log('New movements to create:', newMovements);
    if (totalOperations === 0) {
        alert('No changes or new movements detected to save.');
        return;
    }

    const saveButton = document.querySelector('button[onclick^="saveAllData"]');
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    try {
        if (changes.length > 0) {
            const response = await fetch(masterEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=save_all_changes&changes=${encodeURIComponent(JSON.stringify(changes))}`
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to save existing changes.');
            }
        }

        for (const movement of newMovements) {
            const formData = new URLSearchParams();
            formData.append('action', 'create_new_movement');
            Object.keys(movement).forEach(key => formData.append(key, movement[key]));

            const response = await fetch(masterEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || `Failed to create new movement for ${movement.registration}.`);
            }
        }

        window.location.reload();

    } catch (error) {
        alert('Error occurred while saving: ' + error.message);
        console.error('Save error:', error);
    } finally {
        saveButton.textContent = 'Save';
        saveButton.disabled = false;
    }
}

function handleRegistrationAutofill(registrationInput) {
    const registration = registrationInput.value;
    if (!registration || registration.length < 3) return;
    const row = registrationInput.closest('tr');
    if (!row) return;

    fetch(apronEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getAircraftDetails', registration: registration })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const typeField = row.querySelector('input[data-field="aircraft_type"]');
            const opField = row.querySelector('input[data-field="operator_airline"]');
            if (typeField && !typeField.value && data.aircraft_type) typeField.value = data.aircraft_type;
            if (opField && !opField.value && data.operator_airline) opField.value = data.operator_airline;
        }
    })
    .catch(error => console.log('Autofill lookup failed (normal if not in database):', error));
}

function handleFlightAutofill(flightInput, isArrival) {
    const flightNo = flightInput.value;
    if (!flightNo || flightNo.length < 2) return;
    const row = flightInput.closest('tr');
    if (!row) return;

    fetch(apronEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getFlightRoute', flight_no: flightNo })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.default_route) {
            const targetField = isArrival ? 'from_location' : 'to_location';
            const field = row.querySelector(`input[data-field="${targetField}"]`);
            if (field && !field.value) field.value = data.default_route;
        }
    })
    .catch(error => console.log('Flight route lookup failed (normal if not in database):', error));
}

function loadMoreEmptyRows() {
    const table = document.querySelector('#master-movements-table tbody');
    const currentRows = table.querySelectorAll('tr').length;
    const nextRowNumber = currentRows + 1;
    
    for (let i = 0; i < 25; i++) {
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-id', 'new');
        newRow.className = 'empty-row';
        newRow.setAttribute('data-new-index', currentRows + i);
        
        newRow.innerHTML = `
            <td><input readonly value="${nextRowNumber + i}"></td>
            <td><input value="" data-field="registration" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="aircraft_type" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="on_block_time" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="off_block_time" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="parking_stand" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="from_location" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="to_location" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="flight_no_arr" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="flight_no_dep" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="operator_airline" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td><input value="" data-field="remarks" data-original="" ${isViewer ? 'readonly' : ''}></td>
            <td>
                ${!isViewer ? `
                    <select data-field="is_ron" data-original="0">
                        <option value="0" selected>No</option>
                        <option value="1">Yes</option>
                    </select>
                ` : 'No'}
            </td>
        `;
        
        table.appendChild(newRow);
    }
}

// ... (rest of JS functions: enableTableNav, setupSheetBehavior, etc. are assumed to be here and unchanged) ...
function enableTableNav(tableSelector) {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    table.addEventListener('keydown', function(e) {
        const activeEl = document.activeElement;
        if (activeEl.tagName !== 'INPUT' && activeEl.tagName !== 'SELECT') return;

        const cell = activeEl.closest('td');
        if (!cell) return;

        const row = cell.parentElement;
        const cellIndex = Array.from(row.children).indexOf(cell);
        let nextRow, nextCell;

        switch (e.key) {
            case 'ArrowUp':
                nextRow = row.previousElementSibling;
                if (nextRow) {
                    nextCell = nextRow.children[cellIndex].querySelector('input, select');
                    if (nextCell) nextCell.focus();
                }
                break;
            case 'ArrowDown':
            case 'Enter':
                e.preventDefault();
                nextRow = row.nextElementSibling;
                if (nextRow) {
                    nextCell = nextRow.children[cellIndex].querySelector('input, select');
                    if (nextCell) nextCell.focus();
                }
                break;
            case 'ArrowLeft':
                if (activeEl.selectionStart === 0) {
                    const prevCell = cell.previousElementSibling;
                    if (prevCell) {
                        const prevInput = prevCell.querySelector('input, select');
                        if (prevInput) prevInput.focus();
                    }
                }
                break;
            case 'ArrowRight':
                if (activeEl.selectionStart === activeEl.value.length) {
                    const nextCell = cell.nextElementSibling;
                    if (nextCell) {
                        const nextInput = nextCell.querySelector('input, select');
                        if (nextInput) nextInput.focus();
                    }
                }
                break;
        }
    });
}

function setupSheetBehavior(tableSelector) {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    let isMouseDown = false;
    let startCell = null;
    let endCell = null;

    table.addEventListener('mousedown', function(e) {
        const target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
            isMouseDown = true;
            startCell = target.closest('td');
            clearSelection();
            selectCells(startCell, startCell);
        }
    });

    table.addEventListener('mouseover', function(e) {
        if (isMouseDown) {
            const target = e.target;
            if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
                endCell = target.closest('td');
                selectCells(startCell, endCell);
            }
        }
    });

    window.addEventListener('mouseup', function() {
        isMouseDown = false;
        startCell = null;
        endCell = null;
    });

    document.addEventListener('copy', function(e) {
        const selectedTds = table.querySelectorAll('td.selected');
        if (selectedTds.length > 0) {
            const rows = new Map();
            selectedTds.forEach(td => {
                const rowIndex = td.parentElement.rowIndex;
                if (!rows.has(rowIndex)) {
                    rows.set(rowIndex, []);
                }
                const input = td.querySelector('input, select');
                rows.get(rowIndex).push(input ? input.value : '');
            });

            let clipboardData = '';
            rows.forEach(rowData => {
                clipboardData += rowData.join('\t') + '\n';
            });

            e.clipboardData.setData('text/plain', clipboardData);
            e.preventDefault();
        }
    });

    function selectCells(start, end) {
        clearSelection();
        if (!start || !end) return;

        const startRow = start.parentElement.rowIndex;
        const startCol = start.cellIndex;
        const endRow = end.parentElement.rowIndex;
        const endCol = end.cellIndex;

        const minRow = Math.min(startRow, endRow);
        const maxRow = Math.max(startRow, endRow);
        const minCol = Math.min(startCol, endCol);
        const maxCol = Math.max(startCol, endCol);

        for (let i = minRow; i <= maxRow; i++) {
            const row = table.rows[i];
            for (let j = minCol; j <= maxCol; j++) {
                const cell = row.cells[j];
                if (cell) {
                    cell.classList.add('selected');
                }
            }
        }
    }

    function clearSelection() {
        table.querySelectorAll('td.selected').forEach(td => {
            td.classList.remove('selected');
        });
    }
}
})();
