(function () {
    const config = window.apronConfig || {};
    const endpoints = config.endpoints || {};
    const apronEndpoint = endpoints.apron || 'api/apron';
    const refreshApronEndpoint = endpoints.refreshApron || 'api/apron/status';

    function normalizeJsonResponse(text) {
        if (typeof text !== 'string') {
            return '';
        }

        let cleaned = text.replace(/^﻿/, '').trim();
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
        if ((firstChar === "'" || firstChar === '"') && (secondChar === '{' || secondChar === '[')) {
            cleaned = cleaned.slice(1);
            const lastChar = cleaned.charAt(cleaned.length - 1);
            if (lastChar === firstChar) {
                cleaned = cleaned.slice(0, -1);
            }
        }

        return cleaned.trim();
    }

    function fetchJson(url, options = {}) {
        const fetchOptions = { credentials: 'same-origin', ...options };

        return fetch(url, fetchOptions).then(async response => {
            const raw = await response.text();
            const cleaned = normalizeJsonResponse(raw);

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
        });
    }

    const initialMovements = Array.isArray(config.initialMovements) ? config.initialMovements : [];

    // Autofill aircraft details when registration changes
    function handleRegistrationAutofill(registration) {
        if (!registration || registration.length < 3) return;
        
        fetchJson(apronEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'getAircraftDetails',
                registration: registration
            })
        })
        .then(data => {
            if (data.success) {
                const typeField = document.getElementById('f-type');
                const opField = document.getElementById('f-op');
                
                // Only autofill if fields are empty
                if (typeField && !typeField.value && data.aircraft_type) {
                    typeField.value = data.aircraft_type;
                }
                if (opField && !opField.value && data.operator_airline) {
                    opField.value = data.operator_airline;
                }
            }
        })
        .catch(error => {
            console.log('Autofill lookup failed (normal if not in database):', error);
        });
    }

    // Autofill route when flight number changes
    function handleFlightAutofill(flightNo, isArrival) {
        if (!flightNo || flightNo.length < 2) return;
        
        fetchJson(apronEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'getFlightRoute',
                flight_no: flightNo
            })
        })
        .then(data => {
            if (data.success && data.default_route) {
                if (isArrival) {
                    const fromField = document.getElementById('f-from');
                    // Only autofill if field is empty
                    if (fromField && !fromField.value) {
                        fromField.value = data.default_route;
                    }
                } else {
                    const toField = document.getElementById('f-to');
                    // Only autofill if field is empty
                    if (toField && !toField.value) {
                        toField.value = data.default_route;
                    }
                }
            }
        })
        .catch(error => {
            console.log('Flight route lookup failed (normal if not in database):', error);
        });
    }

    // Function to populate standData from database
    function loadMovementsFromDatabase() {
        console.log('loadMovementsFromDatabase called');
        initialMovements.forEach(movement => {
            const standCode = movement.parking_stand;
            if (!standCode) return;
            
            // Initialize stand data structure if not exists
            if (!standData[standCode]) {
                standData[standCode] = { current: null, planned: null };
            }
            
            // Determine if this is current (has on_block_time) or planned
            const isCurrentMovement = movement.on_block_time && movement.on_block_time.trim() !== '';
            
            // Map database fields to client-side structure
            const movementData = {
                id: movement.id, // Make sure ID is included
                registration: movement.registration || '',
                type: movement.aircraft_type || '',
                onblock: movement.on_block_time || '',
                offblock: movement.off_block_time || '',
                from: movement.from_location || '',
                to: movement.to_location || '',
                arr: movement.flight_no_arr || '',
                dep: movement.flight_no_dep || '',
                op: movement.operator_airline || '',
                remarks: movement.remarks || '',
                ron: movement.is_ron == 1
            };
            
            // Store in appropriate category
            if (isCurrentMovement) {
                standData[standCode].current = movementData;
            } else {
                standData[standCode].planned = movementData;
            }
        });
        
        // Render all stands after loading data
        Object.keys(standData).forEach(standCode => {
            renderStandIcons(standCode);
        });
    }

    // Call the function when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadMovementsFromDatabase();

        // Live refresh for apron status
        setInterval(() => {
            fetchJson(refreshApronEndpoint)
                .then(data => {
                    document.querySelector('#apron-total').textContent = data.total;
                    document.querySelector('#apron-available').textContent = data.available;
                    document.querySelector('#apron-occupied').textContent = data.occupied;
                    document.querySelector('#apron-ron').textContent = data.ron;
                })
                .catch(error => {
                    console.error('Failed to refresh apron status', error);
                });
        }, 5000); // Refresh every 5s
    });

    // ===== Responsive Apron Map Scaling (with 5% shrink) =====
    function resizeApron() {
        const wrapper = document.getElementById('apron-wrapper');
        const container = document.getElementById('apron-container');
        if (!wrapper || !container) return;
        
        const wrapperWidth = wrapper.clientWidth;
        let scale = wrapperWidth / 1920;
        scale = scale * 0.95; // shrink by additional 5%
        
        container.style.transform = `scale(${scale})`;
        container.style.transformOrigin = 'top left';
        wrapper.style.height = `${1080 * scale}px`;
    }
    
    window.addEventListener('load', resizeApron);
    window.addEventListener('resize', resizeApron);

    // ===== In-memory storage for stand data =====
    const standData = {};
    let editingStand = null;
    let editingType = null;
    let editingId = null;

    // ===== Render airplane icons for a stand =====
    function renderStandIcons(standCode) {
        console.log(`renderStandIcons called for ${standCode}`);
        const standEl = document.querySelector(`.stand-gradient[data-stand="${standCode}"]`);
        if (!standEl) return;

        const standLeft = parseFloat(standEl.style.left);
        const standTop = parseFloat(standEl.style.top);
        const standWidth = standEl.offsetWidth;

        // Remove existing icons for this stand
        document.querySelectorAll(`.plane-icon[data-stand="${standCode}"]`).forEach(el => el.remove());

        const data = standData[standCode] || {};

        // Function to create icon
        function createIcon(type) {
            console.log(`createIcon called for type ${type}`);
            const movement = data[type];
            if (!movement || !movement.registration) return;

            // Skip rendering if offblock is set for current movement
            if (type === 'current' && movement.offblock) return;

            const iconDiv = document.createElement('div');
            iconDiv.className = `plane-icon ${type}`;
            iconDiv.dataset.stand = standCode;
            iconDiv.dataset.type = type;

            const iconSpan = document.createElement('span');
            iconSpan.className = 'icon';
            const color = type === 'planned' ? 'yellow' : 'red';
            iconSpan.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24">
                <path fill="${color}" d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67
                10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
            </svg>`;
            iconDiv.appendChild(iconSpan);

            const labelSpan = document.createElement('span');
            labelSpan.className = 'label';
            const reg = movement.registration || '';
            const arr = movement.arr ? `Arr: ${movement.arr}` : '';
            const remarks = movement.remarks ? `<b>${movement.remarks}</b>` : '';
            labelSpan.innerHTML = [reg, arr, remarks].filter(Boolean).join('<br>');
            iconDiv.appendChild(labelSpan);

            // Position the icon and label
            const leftPos = standLeft + standWidth / 2;
            let iconTopPos, labelTopPos;
            const standHeight = standEl.offsetHeight;
            if (type === 'planned') {
                iconTopPos = standTop - 24; // Icon touches top of stand
                labelTopPos = iconTopPos - 40; // Label above icon
            } else {
                iconTopPos = standTop + standHeight; // Icon touches bottom of stand
                labelTopPos = iconTopPos + 24; // Label below icon
            }
            iconDiv.style.left = `${leftPos}px`;
            iconDiv.style.top = `${iconTopPos}px`;
            iconDiv.style.transform = 'translateX(-50%)';
            labelSpan.style.position = 'absolute';
            labelSpan.style.top = `${labelTopPos - iconTopPos}px`;
            labelSpan.style.left = '50%';
            labelSpan.style.transform = 'translateX(-50%)';
            labelSpan.style.whiteSpace = 'nowrap';

            document.getElementById('apron-container').appendChild(iconDiv);

            // Add click listener
            iconDiv.addEventListener('click', () => {
                openModalForEdit(standCode, type);
            });
        }

        createIcon('planned');
        createIcon('current');
    }

    // ===== Open modal for editing existing movement =====
    function openModalForEdit(standCode, type) {
        editingStand = standCode;
        editingType = type;
        const data = standData[standCode][type];
        editingId = data.id; // Store the ID of the item being edited
        if (data) {
            document.getElementById('f-stand').value = standCode;
            document.getElementById('f-reg').value = data.registration || '';
            document.getElementById('f-type').value = data.type || '';
            document.getElementById('f-onblock').value = data.onblock || '';
            document.getElementById('f-offblock').value = data.offblock || '';
            document.getElementById('f-from').value = data.from || '';
            document.getElementById('f-to').value = data.to || '';
            document.getElementById('f-arr').value = data.arr || '';
            document.getElementById('f-dep').value = data.dep || '';
            document.getElementById('f-op').value = data.op || '';
            document.getElementById('f-remarks').value = data.remarks || '';
            document.getElementById('f-ron').checked = data.ron || false;
            document.getElementById('standModalBg').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('f-reg').focus();
            }, 10);
        }
    }

    // ===== Show/hide modals when stands clicked =====
    document.querySelectorAll('.stand-gradient').forEach(el => {
        el.addEventListener('click', () => {
            console.log(`Stand ${el.dataset.stand} clicked`);
            const code = el.dataset.stand;
            if (code === 'HGR') {
                document.getElementById('hgrModalBg').style.display = 'flex';
                setTimeout(() => {
                    const firstInput = document.querySelector('#hgr-table input:not([readonly])');
                    if (firstInput) firstInput.focus();
                }, 10);
            } else {
                editingStand = null;
                editingType = null;
                editingId = null;
                
                // Pre-fill parking stand but allow editing
                document.getElementById('f-stand').value = code;
                
                // Clear other fields
                ['f-reg','f-type','f-onblock','f-offblock','f-from','f-to','f-arr','f-dep','f-op','f-remarks'].forEach(id => {
                    document.getElementById(id).value = '';
                });
                document.getElementById('f-ron').checked = false;
                document.getElementById('standModalBg').style.display = 'flex';
                setTimeout(() => {
                    document.getElementById('f-reg').focus();
                }, 10);
            }
        });
    });
    
    // Close buttons and Cancel
    document.querySelectorAll('.close-btn, button[data-target]').forEach(btn => {
        btn.addEventListener('click', () => {
            const tgt = btn.dataset.target;
            if (tgt) {
                document.getElementById(tgt).style.display = 'none';
            }
        });
    });

    // ===== Keyboard navigation for tables =====
    function enableTableNav(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        const inputs = Array.from(table.querySelectorAll('input'));
        const cols = table.rows[0].cells.length;
        
        table.addEventListener('keydown', e => {
            const idx = inputs.indexOf(e.target);
            if (idx < 0) return;
            
            let nextIdx = null;
            switch (e.key) {
                case 'ArrowRight': nextIdx = idx + 1; break;
                case 'ArrowLeft':  nextIdx = idx - 1; break;
                case 'ArrowDown':  nextIdx = idx + cols; break;
                case 'ArrowUp':    nextIdx = idx - cols; break;
                case 'Tab':
                case 'Enter':
                    e.preventDefault();
                    nextIdx = idx + 1;
                    break;
            }
            if (nextIdx !== null && inputs[nextIdx]) {
                inputs[nextIdx].focus();
            }
        });
    }
    
    window.addEventListener('DOMContentLoaded', () => {
        // Roster and HGR use default navigation
        enableTableNav('#roster-table');
        enableTableNav('#hgr-table');

        // Stand modal custom navigation:
        const orderIds = [
            'f-stand','f-reg','f-type','f-onblock','f-offblock','f-from',
            'f-to','f-arr','f-dep','f-op','f-remarks','f-ron'
        ];
        const inputs = orderIds.map(id => document.getElementById(id)).filter(e => e);
        const saveBtn = document.getElementById('save-stand');
        
        inputs.forEach((inp, idx) => {
            inp.addEventListener('keydown', e => {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    e.preventDefault();
                    // Move to next input down the column first, then right column:
                    const next = inputs[idx + 1] || saveBtn || inputs[0];
                    next.focus();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    const next = inputs[idx + 1] || saveBtn || inputs[0];
                    next.focus();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    const prev = inputs[idx - 1] || inputs[inputs.length - 1];
                    prev.focus();
                }
            });
        });
        
        // Make Save button focusable in sequence: after last input, Enter/Tab goes to Save.
        if (saveBtn) {
            saveBtn.addEventListener('keydown', e => {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    e.preventDefault();
                    // Cycle back to first input
                    if (inputs[0]) inputs[0].focus();
                }
            });
        }

        // Enhanced input event listeners with autofill
        const registrationField = document.getElementById('f-reg');
        const arrivalField = document.getElementById('f-arr');
        const departureField = document.getElementById('f-dep');

        // Registration autofill
        if (registrationField) {
            registrationField.addEventListener('blur', function() {
                handleRegistrationAutofill(this.value);
            });
            
            registrationField.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    setTimeout(() => {
                        handleRegistrationAutofill(this.value);
                    }, 50);
                }
            });
        }

        // Arrival flight autofill
        if (arrivalField) {
            arrivalField.addEventListener('blur', function() {
                handleFlightAutofill(this.value, true);
            });
            
            arrivalField.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    setTimeout(() => {
                        handleFlightAutofill(this.value, true);
                    }, 50);
                }
            });
        }

        // Departure flight autofill
        if (departureField) {
            departureField.addEventListener('blur', function() {
                handleFlightAutofill(this.value, false);
            });
            
            departureField.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' || e.key === 'Enter') {
                    setTimeout(() => {
                        handleFlightAutofill(this.value, false);
                    }, 50);
                }
            });
        }

        // Sheets-like behavior for all tables:
        setupSheetBehavior('#roster-table');
        setupSheetBehavior('#standFormTable');
        setupSheetBehavior('#hgr-table');

        // ===== Save Roster =====
        const sr = document.getElementById('save-roster');
        if (sr) sr.addEventListener('click', () => {
            const date = document.getElementById('roster-date').value;
            const aerodrome = document.getElementById('aerodrome-input').value;
            const dayStaff1 = document.getElementById('day-staff-1').value;
            const dayStaff2 = document.getElementById('day-staff-2').value;
            const dayStaff3 = document.getElementById('day-staff-3').value;
            const nightStaff1 = document.getElementById('night-staff-1').value;
            const nightStaff2 = document.getElementById('night-staff-2').value;
            const nightStaff3 = document.getElementById('night-staff-3').value;
            
            fetchJson(apronEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'saveRoster', 
                    date: date,
                    aerodrome: aerodrome,
                    day_staff_1: dayStaff1,
                    day_staff_2: dayStaff2,
                    day_staff_3: dayStaff3,
                    night_staff_1: nightStaff1,
                    night_staff_2: nightStaff2,
                    night_staff_3: nightStaff3
                })
            })
            .then(data => {
                if (data.success) {
                    alert('Roster saved successfully.');
                } else {
                    alert('Error saving roster: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error saving roster.');
                console.error(error);
            });
        });

        // ===== Set RON =====
        const setRonBtn = document.getElementById('set-ron-btn');
        if (setRonBtn) {
            setRonBtn.addEventListener('click', () => {
                if (confirm('Set all current movements as RON?')) {
                    fetchJson(apronEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'setRON' })
                    })
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('RON update failed: ' + (error.message || error));
                    });
                }
            });
        }
        
        // ===== Save Stand (Movement) =====
        const ss = document.getElementById('save-stand');
        if (ss) ss.addEventListener('click', () => {
            const standCode = document.getElementById('f-stand').value;
            
            const movementData = {
                registration: document.getElementById('f-reg').value,
                aircraft_type: document.getElementById('f-type').value,
                on_block_time: document.getElementById('f-onblock').value,
                off_block_time: document.getElementById('f-offblock').value,
                from_location: document.getElementById('f-from').value,
                to_location: document.getElementById('f-to').value,
                flight_no_arr: document.getElementById('f-arr').value,
                flight_no_dep: document.getElementById('f-dep').value,
                operator_airline: document.getElementById('f-op').value,
                remarks: document.getElementById('f-remarks').value,
                is_ron: document.getElementById('f-ron').checked
            };

            const payload = {
                action: 'saveMovement',
                parking_stand: standCode,
                ...movementData
            };

            // Include ID if editing existing movement
            if (editingId) {
                payload.id = editingId;
            }

            fetchJson(apronEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => {
                if (res.success) {
                    location.reload();
                } else {
                    alert('Error saving movement: ' + res.message);
                }
            })
            .catch(err => {
                alert('Network error: ' + (err.message || err));
            });

            document.getElementById('standModalBg').style.display = 'none';
            editingStand = null;
            editingType = null;
            editingId = null;
        });
    });

    // ===== Google Sheets-like selection & copy/paste =====
    const sheetData = {};
    let clipboard = null;
    function setupSheetBehavior(tableSelector) {
        // (existing selection/cut/copy/paste code from user file, unchanged)
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const map = {};
        const rows = Array.from(table.rows);
        rows.forEach((tr, rIdx) => {
            Array.from(tr.cells).forEach((cell, cIdx) => {
                const inp = cell.querySelector('input');
                if (inp) {
                    inp.dataset.row = rIdx;
                    inp.dataset.col = cIdx;
                    map[`${rIdx},${cIdx}`] = inp;
                }
            });
        });
        
        sheetData[tableSelector] = {
            map,
            selecting: false,
            startRow: null,
            startCol: null,
            selectedCells: new Set(),
            minRow: null,
            maxRow: null,
            minCol: null,
            maxCol: null
        };
        
        // Mouse selection
        table.addEventListener('mousedown', e => {
            if (e.target.tagName === 'INPUT') {
                const inp = e.target;
                const r = parseInt(inp.dataset.row, 10);
                const c = parseInt(inp.dataset.col, 10);
                const data = sheetData[tableSelector];
                clearSelection(tableSelector);
                data.selecting = true;
                data.startRow = r;
                data.startCol = c;
                updateSelection(tableSelector, r, c);
                inp.focus();
                e.preventDefault();
            }
        });
        
        table.addEventListener('mouseover', e => {
            const data = sheetData[tableSelector];
            if (data.selecting && e.target.tagName === 'INPUT') {
                const inp = e.target;
                const r = parseInt(inp.dataset.row, 10);
                const c = parseInt(inp.dataset.col, 10);
                updateSelection(tableSelector, r, c);
            }
        });
        
        document.addEventListener('mouseup', () => {
            const data = sheetData[tableSelector];
            if (data) data.selecting = false;
        });
        
        // Shift+click expand
        table.addEventListener('click', e => {
            if (e.target.tagName === 'INPUT' && e.shiftKey) {
                const inp = e.target;
                const r = parseInt(inp.dataset.row, 10);
                const c = parseInt(inp.dataset.col, 10);
                const data = sheetData[tableSelector];
                if (data.startRow !== null) {
                    updateSelection(tableSelector, r, c);
                    inp.focus();
                }
                e.preventDefault();
            }
        });
    }

    function clearSelection(tableSelector) {
        const data = sheetData[tableSelector];
        if (!data) return;
        data.selectedCells.forEach(key => {
            const inp = data.map[key];
            if (inp) inp.classList.remove('selected');
        });
        data.selectedCells.clear();
        data.minRow = data.maxRow = data.minCol = data.maxCol = null;
    }

    function updateSelection(tableSelector, r, c) {
        const data = sheetData[tableSelector];
        if (!data) return;
        const sr = data.startRow, sc = data.startCol;
        const minR = Math.min(sr, r), maxR = Math.max(sr, r);
        const minC = Math.min(sc, c), maxC = Math.max(sc, c);
        clearSelection(tableSelector);
        for (let rr = minR; rr <= maxR; rr++) {
            for (let cc = minC; cc <= maxC; cc++) {
                const key = `${rr},${cc}`;
                const inp = data.map[key];
                if (inp) {
                    inp.classList.add('selected');
                    data.selectedCells.add(key);
                }
            }
        }
        data.minRow = minR; data.maxRow = maxR;
        data.minCol = minC; data.maxCol = maxC;
    }

    document.addEventListener('keydown', e => {
        const active = document.activeElement;
        if (!active || active.tagName !== 'INPUT') return;
        const table = active.closest('table');
        if (!table) return;
        const tableSelector = '#' + table.id;
        const data = sheetData[tableSelector];
        
        if (!(e.ctrlKey || e.metaKey)) return;
        const key = e.key.toLowerCase();
        if (!['c','x','v'].includes(key)) return;
        if (!data) return;
        
        if (key === 'c' || key === 'x') {
            if (data.selectedCells.size > 0) {
                const rows = [];
                for (let rr = data.minRow; rr <= data.maxRow; rr++) {
                    const rowArr = [];
                    for (let cc = data.minCol; cc <= data.maxCol; cc++) {
                        const key2 = `${rr},${cc}`;
                        const inp = data.map[key2];
                        rowArr.push(inp ? inp.value : '');
                    }
                    rows.push(rowArr);
                }
                clipboard = rows;
                if (key === 'x') {
                    data.selectedCells.forEach(key2 => {
                        const inp = data.map[key2];
                        if (inp) inp.value = '';
                    });
                }
                e.preventDefault();
            } else {
                const val = active.value;
                clipboard = [[val]];
                if (key === 'x') {
                    active.value = '';
                }
                e.preventDefault();
            }
        } else if (key === 'v') {
            if (clipboard !== null) {
                const startRow = parseInt(active.dataset.row, 10);
                const startCol = parseInt(active.dataset.col, 10);
                for (let i = 0; i < clipboard.length; i++) {
                    const rowArr = clipboard[i];
                    for (let j = 0; j < rowArr.length; j++) {
                        const key2 = `${startRow + i},${startCol + j}`;
                        const inp = data.map[key2];
                        if (inp) {
                            inp.value = rowArr[j];
                        }
                    }
                }
                e.preventDefault();
            }
        }
    });
})();