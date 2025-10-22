Inline Record Creation Implementation Guide
Overview
This guide will help you implement spreadsheet-style inline record creation in your master-table.php without disrupting existing functionality like RON handling, filtering, or the save system.
Step 1: Add the "New Record" Button
1.1 Modify the Table Header Section
In master-table.php, locate the table header section (around line with "Aircraft Movements Live Data") and update it:
php<div class="table-header">
    <span>Aircraft Movements Live Data</span>
    <div class="table-header-actions">
        <?php if ($user_role !== 'viewer'): ?>
        <button id="add-new-record-btn" class="new-record-btn">+ New Record</button>
        <button id="set-ron-btn">Set RON</button>
        <button onclick="saveAllData()">Save</button>
        <?php endif; ?>
        <button onclick="window.location.reload()">Refresh</button>
    </div>
</div>
1.2 Add CSS Styling
Add this CSS to your styles.css file:
css.new-record-btn {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 10px;
}

.new-record-btn:hover {
    background-color: #218838;
}

.new-record-row {
    background-color: #fff3cd !important;
    border: 2px solid #ffc107;
}

.new-record-row input, .new-record-row select {
    background-color: #fffbf0;
    border: 1px solid #ffc107;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #28a745;
    color: white;
    padding: 15px 20px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 1000;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}
Step 2: Backend Support for New Records
2.1 Update the Save Movement Handler
In index.php, modify the saveMovement action handler to better support new records. Locate the section around line 120 and ensure it handles empty IDs properly:
phpelseif (isset($data['action']) && $data['action'] === 'saveMovement') {
    requireRole(['admin','operator'], 'Not authorized to save movements');
    $id = $data['id'] ?? null;
    $is_update = !empty($id) && $id !== 'new';

    // ... existing parameter setup code ...

    if ($is_update) {
        // ... existing update logic ...
    } else {
        // Enhanced new record creation
        $params[':user_id_created'] = $current_user_id;
        $params[':on_block_date'] = !empty($params[':on_block_time']) ? date('Y-m-d') : null;
        $params[':movement_date'] = date('Y-m-d');
        $params[':is_ron'] = $is_ron_checkbox;
        
        // Handle off_block_time for new record
        if (!empty($off_block_time)) {
            if (strpos($off_block_time, '(') === false) {
                $params[':off_block_time'] = $off_block_time . ' (' . date('d/m/Y') . ')';
            } else {
                $params[':off_block_time'] = $off_block_time;
            }
            $params[':off_block_date'] = date('Y-m-d');
            $params[':ron_complete'] = $is_ron_checkbox ? 1 : 0;
        } else {
            $params[':off_block_time'] = null;
            $params[':off_block_date'] = null;
            $params[':ron_complete'] = 0;
        }

        $sql = "INSERT INTO aircraft_movements (
                    registration, aircraft_type, on_block_time, off_block_time, parking_stand, 
                    from_location, to_location, flight_no_arr, flight_no_dep, operator_airline, 
                    remarks, is_ron, ron_complete, movement_date, on_block_date, off_block_date, 
                    user_id_created, user_id_updated, created_at, updated_at
                ) VALUES (
                    :registration, :aircraft_type, :on_block_time, :off_block_time, :parking_stand, 
                    :from_location, :to_location, :flight_no_arr, :flight_no_dep, :operator_airline, 
                    :remarks, :is_ron, :ron_complete, :movement_date, :on_block_date, :off_block_date, 
                    :user_id_created, :user_id_updated, NOW(), NOW()
                )";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Return the new record ID for frontend handling
    $newId = $is_update ? $id : $pdo->lastInsertId();
    echo json_encode([
        'success' => true, 
        'message' => 'Movement saved successfully.',
        'id' => $newId,
        'is_new' => !$is_update
    ]);
    exit();
}
Step 3: Frontend JavaScript Implementation
3.1 Add the Core JavaScript Functions
Add this JavaScript code at the end of your existing script section in master-table.php:
javascript// Global variables for new record functionality
let isCreatingNewRecord = false;
let newRecordRow = null;

// Function to add a new record row
function addNewRecordRow() {
    if (isCreatingNewRecord) {
        alert('Please finish creating the current new record first.');
        return;
    }
    
    const tableBody = document.querySelector('#master-movements-table tbody');
    if (!tableBody) return;
    
    // Create new row with empty inputs
    const newRow = document.createElement('tr');
    newRow.className = 'new-record-row';
    newRow.setAttribute('data-id', 'new');
    
    // Get the current row number (should be 1 for top position)
    const currentRowNumber = 1;
    
    newRow.innerHTML = `
        <td><input readonly value="${currentRowNumber}" class="row-number"></td>
        <td><input value="" data-field="registration" placeholder="Registration" class="new-record-input"></td>
        <td><input value="" data-field="aircraft_type" placeholder="Aircraft Type" class="new-record-input"></td>
        <td><input value="" data-field="on_block_time" placeholder="On Block Time" class="new-record-input"></td>
        <td><input value="" data-field="off_block_time" placeholder="Off Block Time" class="new-record-input"></td>
        <td><input value="" data-field="parking_stand" placeholder="Parking Stand" class="new-record-input"></td>
        <td><input value="" data-field="from_location" placeholder="From" class="new-record-input"></td>
        <td><input value="" data-field="to_location" placeholder="To" class="new-record-input"></td>
        <td><input value="" data-field="flight_no_arr" placeholder="Arrival Flight" class="new-record-input"></td>
        <td><input value="" data-field="flight_no_dep" placeholder="Departure Flight" class="new-record-input"></td>
        <td><input value="" data-field="operator_airline" placeholder="Operator/Airline" class="new-record-input"></td>
        <td><input value="" data-field="remarks" placeholder="Remarks" class="new-record-input"></td>
        <td>
            <select data-field="is_ron" class="new-record-input">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        </td>
    `;
    
    // Insert at the top of the table
    tableBody.insertBefore(newRow, tableBody.firstChild);
    
    // Update row numbers for existing rows
    updateRowNumbers();
    
    // Set up event listeners for the new row
    setupNewRecordListeners(newRow);
    
    // Focus on the first input
    const firstInput = newRow.querySelector('input[data-field="registration"]');
    if (firstInput) {
        firstInput.focus();
    }
    
    isCreatingNewRecord = true;
    newRecordRow = newRow;
}

// Function to update row numbers
function updateRowNumbers() {
    const rows = document.querySelectorAll('#master-movements-table tbody tr');
    rows.forEach((row, index) => {
        const rowNumberInput = row.querySelector('.row-number, input[readonly]');
        if (rowNumberInput) {
            rowNumberInput.value = index + 1;
        }
    });
}

// Function to setup event listeners for new record row
function setupNewRecordListeners(row) {
    const inputs = row.querySelectorAll('.new-record-input');
    
    inputs.forEach((input, index) => {
        // Auto-save on Enter key
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveNewRecord();
            } else if (e.key === 'Tab') {
                // Allow normal tab behavior but save if on last field
                if (index === inputs.length - 1) {
                    setTimeout(() => saveNewRecord(), 100);
                }
            }
        });
        
        // Auto-save on blur (clicking away)
        input.addEventListener('blur', (e) => {
            // Only save if we're actually moving away from the new record row
            setTimeout(() => {
                if (!row.contains(document.activeElement)) {
                    saveNewRecord();
                }
            }, 100);
        });
        
        // Registration autofill
        if (input.getAttribute('data-field') === 'registration') {
            input.addEventListener('blur', function() {
                if (this.value.length >= 3) {
                    handleRegistrationAutofill(this.value, row);
                }
            });
        }
        
        // Flight number autofill
        if (input.getAttribute('data-field') === 'flight_no_arr') {
            input.addEventListener('blur', function() {
                if (this.value.length >= 2) {
                    handleFlightAutofill(this.value, true, row);
                }
            });
        }
        
        if (input.getAttribute('data-field') === 'flight_no_dep') {
            input.addEventListener('blur', function() {
                if (this.value.length >= 2) {
                    handleFlightAutofill(this.value, false, row);
                }
            });
        }
    });
}

// Function to save the new record
function saveNewRecord() {
    if (!isCreatingNewRecord || !newRecordRow) return;
    
    // Collect data from the new record row
    const movementData = {};
    const inputs = newRecordRow.querySelectorAll('.new-record-input');
    
    inputs.forEach(input => {
        const field = input.getAttribute('data-field');
        movementData[field] = input.value.trim();
    });
    
    // Validate required fields
    if (!movementData.registration || !movementData.parking_stand) {
        showNotification('Registration and Parking Stand are required.', 'error');
        return;
    }
    
    // Prepare payload
    const payload = {
        action: 'saveMovement',
        id: null, // Explicitly null for new records
        parking_stand: movementData.parking_stand,
        registration: movementData.registration,
        aircraft_type: movementData.aircraft_type,
        on_block_time: movementData.on_block_time,
        off_block_time: movementData.off_block_time,
        from_location: movementData.from_location,
        to_location: movementData.to_location,
        flight_no_arr: movementData.flight_no_arr,
        flight_no_dep: movementData.flight_no_dep,
        operator_airline: movementData.operator_airline,
        remarks: movementData.remarks,
        is_ron: movementData.is_ron === '1'
    };
    
    // Show saving indicator
    newRecordRow.style.opacity = '0.7';
    
    // Send to backend
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Convert new record row to regular row
            convertNewRecordToRegular(data.id);
            showNotification('Record saved successfully!', 'success');
            
            // Reset state
            isCreatingNewRecord = false;
            newRecordRow = null;
        } else {
            // Reset row appearance on error
            newRecordRow.style.opacity = '1';
            showNotification('Error saving record: ' + data.message, 'error');
        }
    })
    .catch(error => {
        newRecordRow.style.opacity = '1';
        showNotification('Network error occurred.', 'error');
        console.error('Save error:', error);
    });
}

// Function to convert new record row to regular row
function convertNewRecordToRegular(newId) {
    if (!newRecordRow) return;
    
    // Remove new record styling
    newRecordRow.classList.remove('new-record-row');
    newRecordRow.setAttribute('data-id', newId);
    
    // Convert inputs to have proper data attributes for existing save system
    const inputs = newRecordRow.querySelectorAll('.new-record-input');
    inputs.forEach(input => {
        input.classList.remove('new-record-input');
        input.setAttribute('data-original', input.value);
        
        // Remove placeholder
        input.removeAttribute('placeholder');
    });
    
    // Update row styling to match existing rows
    newRecordRow.style.opacity = '1';
    newRecordRow.style.backgroundColor = '';
    newRecordRow.style.border = '';
}

// Enhanced autofill functions for new records
function handleRegistrationAutofill(registration, targetRow = null) {
    if (!registration || registration.length < 3) return;
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'getAircraftDetails',
            registration: registration
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = targetRow || document;
            const typeField = row.querySelector('input[data-field="aircraft_type"]');
            const opField = row.querySelector('input[data-field="operator_airline"]');
            
            if (typeField && !typeField.value && data.aircraft_type) {
                typeField.value = data.aircraft_type;
            }
            if (opField && !opField.value && data.operator_airline) {
                opField.value = data.operator_airline;
            }
        }
    })
    .catch(error => {
        console.log('Autofill lookup failed:', error);
    });
}

function handleFlightAutofill(flightNo, isArrival, targetRow = null) {
    if (!flightNo || flightNo.length < 2) return;
    
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'getFlightRoute',
            flight_no: flightNo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.default_route) {
            const row = targetRow || document;
            const fieldName = isArrival ? 'from_location' : 'to_location';
            const targetField = row.querySelector(`input[data-field="${fieldName}"]`);
            
            if (targetField && !targetField.value) {
                targetField.value = data.default_route;
            }
        }
    })
    .catch(error => {
        console.log('Flight route lookup failed:', error);
    });
}

// Function to show notifications
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    if (type === 'error') {
        notification.style.backgroundColor = '#dc3545';
    }
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Function to cancel new record creation
function cancelNewRecord() {
    if (newRecordRow) {
        newRecordRow.remove();
        updateRowNumbers();
        isCreatingNewRecord = false;
        newRecordRow = null;
    }
}
3.2 Add Event Listener for the New Record Button
Add this to your existing DOMContentLoaded event listener:
javascriptdocument.addEventListener('DOMContentLoaded', () => {
    // ... existing code ...
    
    // New Record button functionality
    const addNewRecordBtn = document.getElementById('add-new-record-btn');
    if (addNewRecordBtn) {
        addNewRecordBtn.addEventListener('click', addNewRecordRow);
    }
    
    // Handle Escape key to cancel new record
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isCreatingNewRecord) {
            cancelNewRecord();
        }
    });
});
Step 4: Integration with Existing Save System
4.1 Modify the saveAllData() Function
Update your existing saveAllData() function to handle the new record properly:
javascriptfunction saveAllData() {
    if ('<?= $user_role ?>' === 'viewer') {
        alert('You do not have permission to save changes.');
        return;
    }
    
    // If there's a pending new record, save it first
    if (isCreatingNewRecord) {
        saveNewRecord();
        // Wait a bit for the new record to be processed
        setTimeout(() => {
            saveExistingChanges();
        }, 500);
    } else {
        saveExistingChanges();
    }
}

function saveExistingChanges() {
    const changes = [];
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
    });

    if (changes.length === 0) {
        if (!isCreatingNewRecord) {
            alert('No changes detected to save.');
        }
        return;
    }

    const saveButton = document.querySelector('.table-header-actions button[onclick^="saveAllData"]');
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;

    fetch('master-table.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=save_all_changes&changes=${encodeURIComponent(JSON.stringify(changes))}`
    })
    .then(response => response.json())
    .then(data => {
        saveButton.textContent = 'Save';
        saveButton.disabled = false;
        if (data.success) {
            showNotification('Changes saved successfully!', 'success');
            // Update data-original attributes
            updateOriginalAttributes();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        saveButton.textContent = 'Save';
        saveButton.disabled = false;
        alert('An error occurred while saving.');
        console.error('Save error:', error);
    });
}

function updateOriginalAttributes() {
    document.querySelectorAll('#master-movements-table input[data-field], #master-movements-table select[data-field]').forEach(input => {
        input.setAttribute('data-original', input.value);
    });
}
Step 5: Testing and Validation
5.1 Test the Feature

Basic Functionality: Click the "+ New Record" button and verify a highlighted row appears at the top
Data Entry: Enter data in the new row and verify autofill works for registration and flight numbers
Auto-Save: Test both Enter key saving and click-away saving
Integration: Verify the new record integrates properly with existing table functionality
RON Feature: Ensure the existing "Set RON" button still works correctly
Existing Save: Test that the regular "Save" button still handles existing record updates

5.2 Edge Cases to Test

Creating a new record when one already exists (should show alert)
Pressing Escape to cancel new record creation
Invalid data (missing required fields)
Network errors during save
Autofill functionality with new records

Step 6: Optional Enhancements
6.1 Keyboard Shortcuts
Add this for power users:
javascript// Add to DOMContentLoaded event listener
document.addEventListener('keydown', (e) => {
    // Ctrl+N or Cmd+N to add new record
    if ((e.ctrlKey || e.metaKey) && e.key === 'n' && !isCreatingNewRecord) {
        e.preventDefault();
        addNewRecordRow();
    }
});
6.2 Visual Feedback Improvements
Add loading states and better visual cues:
css.new-record-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.saving-row {
    opacity: 0.7;
    pointer-events: none;
}
Important Notes

Preserve Existing Functionality: This implementation doesn't modify any existing RON logic, filtering, or pagination
Role-Based Access: The new record feature respects the existing role system (viewers cannot add records)
Data Integrity: All validation and database constraints are preserved
Performance: The feature adds minimal overhead and doesn't affect page load times
User Experience: The interface remains familiar while adding powerful new functionality

Rollback Plan
If you need to rollback this feature:

Remove the + New Record button from the table header
Remove the new JavaScript functions
Remove the new CSS classes
The backend changes are backward compatible and won't break existing functionality

This implementation provides the exact workflow you described while maintaining all existing functionality and ensuring a smooth user experience.

Key Implementation Highlights:
Backend Changes (Minimal):

Enhanced the existing saveMovement action in index.php to better handle new records
Added proper ID handling and response data for frontend integration
All existing RON logic, filtering, and save functionality remains untouched

Frontend Features:

"+ New Record" button that creates a highlighted row at the top
Auto-save on Enter key or when clicking away
Autofill integration for registration and flight numbers
Visual feedback with notifications
Escape key to cancel new record creation
Proper integration with your existing save system

Preserved Functionality:

All RON handling remains exactly the same
Existing save system for modified records works unchanged
Filtering and pagination continue to work
Role-based permissions are respected
Your existing table navigation and copy/paste features remain intact

The implementation uses your existing backend endpoints and database structure, so there's minimal risk of breaking current functionality. The new record creation happens entirely within the existing table framework, making it feel like a natural extension of your current system.