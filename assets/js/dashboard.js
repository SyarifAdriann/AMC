(function () {
    const config = window.dashboardConfig || {};
    const endpoints = config.endpoints || {};
    const userEndpoint = endpoints.userAdmin || 'api/admin/users';
    const snapshotEndpoint = endpoints.snapshots || 'api/snapshots';
    const refreshApronEndpoint = endpoints.refreshApron || 'api/apron/status';
    const userRole = config.userRole || 'viewer';
    const peakHourData = Array.isArray(config.peakHourData) ? config.peakHourData : [];

        const peakHourData = Array.isArray(config.peakHourData) ? config.peakHourData : [];

// Auto-refresh apron status
setInterval(() => {
    fetch(refreshApronEndpoint)
        .then(response => response.json())
        .then(data => {
            document.querySelector('#apron-total').textContent = data.total;
            document.querySelector('#apron-available').textContent = data.available;
            document.querySelector('#apron-occupied').textContent = data.occupied;
            document.querySelector('#apron-ron').textContent = data.ron;
        });
}, 5000);

// Peak hours summary
function updatePeakHoursSummary() {
    const dataWithTotals = peakHourData.map(h => ({ 
        ...h, 
        Arrivals: parseInt(h.Arrivals) || 0,
        Departures: parseInt(h.Departures) || 0,
        total: (parseInt(h.Arrivals) || 0) + (parseInt(h.Departures) || 0)
    }));

    const sortedByTotal = [...dataWithTotals].sort((a, b) => b.total - a.total);
    const peakPeriod = sortedByTotal[0] || { time_range: 'N/A', total: 0 };
    const quietPeriod = [...dataWithTotals].sort((a, b) => a.total - b.total).find(h => h.total > 0) || sortedByTotal[sortedByTotal.length - 1] || { time_range: 'N/A', total: 0 };
    
    const totalMovements = dataWithTotals.reduce((sum, h) => sum + h.total, 0);
    const totalArrivals = dataWithTotals.reduce((sum, h) => sum + h.Arrivals, 0);
    const totalDepartures = dataWithTotals.reduce((sum, h) => sum + h.Departures, 0);
    
    let busiestPeriod = { start: 0, total: 0 };
    for (let i = 0; i < dataWithTotals.length - 1; i++) {
        const windowTotal = dataWithTotals[i].total + dataWithTotals[i + 1].total;
        if (windowTotal > busiestPeriod.total) {
            busiestPeriod = { start: i, total: windowTotal };
        }
    }
    
    let busiestStart = "00:00-01:59";
    let busiestEnd = "02:00-03:59";
    if (dataWithTotals.length > 1 && busiestPeriod.start < dataWithTotals.length - 1) {
        busiestStart = dataWithTotals[busiestPeriod.start].time_range;
        busiestEnd = dataWithTotals[busiestPeriod.start + 1].time_range;
    }
    
    const summaryHTML = `
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #dc3545;">Peak 2-Hour Period:</strong>
            <span>${peakPeriod.time_range} (${peakPeriod.total} movements)</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #28a745;">Quietest 2-Hour Period:</strong>
            <span>${quietPeriod.time_range} (${quietPeriod.total} movements)</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #007bff;">Busiest 4-Hour Window:</strong>
            <span>${busiestStart.split(':')[0]}:00-${busiestEnd.split('-')[1]} (${busiestPeriod.total} movements)</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #6c757d;">Today's Total:</strong>
            <span>${totalMovements} movements (${totalArrivals} arr, ${totalDepartures} dep)</span>
        </div>
    `;
    
    document.getElementById('peakHoursContent').innerHTML = summaryHTML;
}

// Modal Management System
class ModalManager {
    constructor() {
        this.userEndpoint = userEndpoint;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Modal trigger buttons
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-modal-target')) {
                e.preventDefault();
                const modalId = e.target.getAttribute('data-modal-target');
                this.openModal(modalId);
            }
            
            if (e.target.hasAttribute('data-modal-close')) {
                e.preventDefault();
                this.closeModal(e.target.closest('.modal-backdrop'));
            }
        });

        // Close modal when clicking backdrop
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                this.closeModal(e.target);
            }
        });

        // ESC key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal-backdrop[style*="block"]');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Auto-scroll to top and show modal
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => {
                modal.style.display = 'flex';
                modal.style.alignItems = 'flex-start';
                modal.style.paddingTop = '50px';
                
                // Special handling for different modals
                if (modalId === 'accountsModalBg') {
                    this.loadUsers();
                } else if (modalId === 'snapshotModalBg') {
                    SnapshotManager.loadSnapshots();
                }
            }, 300);
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            // Reset forms
            const forms = modal.querySelectorAll('form');
            forms.forEach(form => form.reset());
        }
    }

    // User management functions
    loadUsers(page = 1) {
        const query = document.getElementById('user-search')?.value || '';
        const role = document.getElementById('role-filter')?.value || '';
        const status = document.getElementById('status-filter')?.value || '';

        const params = new URLSearchParams({
            action: 'list',
            query: query,
            role: role,
            status: status,
            page: page,
            per_page: 25
        });

        fetch(`${this.userEndpoint}?${params}`)
            .then(response => response.json().catch(() => {
                throw new Error('Invalid JSON response from server');
            }))
            .then(data => {
                if (data.success) {
                    this.renderUsersTable(data.data);
                    this.renderPagination(data);
                } else {
                    this.showToast(data.message || 'Failed to load users.', 'error');
                }
            })
            .catch(error => {
                this.showToast('Error loading users: ' + error.message, 'error');
            });
    }

    renderUsersTable(users) {
        const tbody = document.getElementById('users-tbody');
        if (!tbody) return;

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${this.escapeHtml(user.full_name || '')}</td>
                <td>${this.escapeHtml(user.username)}</td>
                <td>${this.escapeHtml(user.email || '')}</td>
                <td><span class="role-badge role-${user.role}">${user.role}</span></td>
                <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                <td>${user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : 'Never'}</td>
                <td class="actions">
                    <button onclick="modalManager.editUser(${user.id})" class="action-btn edit">Edit</button>
                    <button onclick="modalManager.resetPassword(${user.id}, '${this.escapeHtml(user.username)}')" class="action-btn reset">Reset PW</button>
                    <button onclick="modalManager.toggleStatus(${user.id}, '${user.status}')" class="action-btn ${user.status === 'active' ? 'suspend' : 'activate'}">
                        ${user.status === 'active' ? 'Suspend' : 'Activate'}
                    </button>
                    ${userRole === 'admin' ? `<button onclick="modalManager.deleteUser(${user.id}, '${this.escapeHtml(user.username)}')" class="action-btn delete">Delete</button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    renderPagination(data) {
        const container = document.getElementById('accounts-pagination');
        if (!container) return;

        const totalPages = Math.ceil(data.total / data.per_page);
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<div class="pagination">';
        
        if (data.page > 1) {
            paginationHTML += `<button onclick="modalManager.loadUsers(${data.page - 1})" class="page-btn">Â&laquo; Previous</button>`;
        }
        
        for (let i = Math.max(1, data.page - 2); i <= Math.min(totalPages, data.page + 2); i++) {
            paginationHTML += `<button onclick="modalManager.loadUsers(${i})" class="page-btn ${i === data.page ? 'active' : ''}">${i}</button>`;
        }
        
        if (data.page < totalPages) {
            paginationHTML += `<button onclick="modalManager.loadUsers(${data.page + 1})" class="page-btn">Next Â&raquo;</button>`;
        }
        
        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    }

    editUser(userId) {
        // Fetch user data and populate form
        fetch(`${this.userEndpoint}?action=list`)
            .then(response => response.json().catch(() => {
                throw new Error('Invalid JSON response from server');
            }))
            .then(data => {
                if (!data.success) {
                    this.showToast(data.message || 'Unable to load user details.', 'error');
                    return;
                }

                const user = data.data.find(u => u.id == userId);
                if (user) {
                    document.getElementById('user-form-title').textContent = 'Edit User';
                    document.getElementById('user-id').value = user.id;
                    document.getElementById('user-full-name').value = user.full_name || '';
                    document.getElementById('user-username').value = user.username;
                    // FIX: Disable username field in edit mode
                    document.getElementById('user-username').disabled = true;
                    document.getElementById('user-email').value = user.email || '';
                    document.getElementById('user-role').value = user.role;
                    document.getElementById('user-status').value = user.status;
                    document.getElementById('password-row').style.display = 'none';
                    this.openModal('userFormModalBg');
                } else {
                    this.showToast('User record not found.', 'error');
                }
            })
            .catch(error => {
                this.showToast('Error loading user details: ' + error.message, 'error');
            });
    }

    resetPassword(userId, username) {
        document.getElementById('reset-username').textContent = username;
        document.getElementById('reset-user-id').value = userId;
        document.getElementById('reset-password-form').reset();
        this.openModal('resetPasswordModalBg');
    }

    toggleStatus(userId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
        const action = newStatus === 'suspended' ? 'suspend' : 'activate';
        
        if (confirm(`Are you sure you want to ${action} this user?`)) {
            const formData = new FormData();
            formData.append('action', 'set_status');
            formData.append('id', userId);
            formData.append('status', newStatus);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            fetch(this.userEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().catch(() => {
                throw new Error('Invalid JSON response from server');
            }))
            .then(data => {
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.loadUsers();
                } else {
                    this.showToast(data.message || 'Unable to update user status.', 'error');
                }
            })
            .catch(error => {
                this.showToast('Error updating user status: ' + error.message, 'error');
            });
        }
    }

    deleteUser(userId, username) {
        if (!confirm(`Are you sure you want to delete user ${username}? This action cannot be undone.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', userId);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        fetch(this.userEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json().catch(() => {
            throw new Error('Invalid JSON response from server');
        }))
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.loadUsers();
            } else {
                this.showToast(data.message || 'Unable to delete user.', 'error');
            }
        })
        .catch(error => {
            this.showToast('Error deleting user: ' + error.message, 'error');
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        if (type === 'success') toast.style.backgroundColor = '#28a745';
        else if (type === 'error') toast.style.backgroundColor = '#dc3545';
        else toast.style.backgroundColor = '#17a2b8';

        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Snapshot Management Functions
const SnapshotManager = {
    loadSnapshots: function(page = 1) {
        const tbody = document.getElementById('snapshots-tbody');
        const loading = document.getElementById('snapshots-loading');
        
        if (loading) loading.style.display = 'block';
        if (tbody) tbody.innerHTML = '';

        const params = new URLSearchParams({
            action: 'list',
            page: page,
            per_page: 20
        });

        fetch(`${snapshotEndpoint}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (loading) loading.style.display = 'none';
                
                if (data.success) {
                    this.renderSnapshotsTable(data.data);
                    this.renderSnapshotsPagination(data);
                } else {
                    modalManager.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                if (loading) loading.style.display = 'none';
                modalManager.showToast('Error loading snapshots: ' + error.message, 'error');
            });
    },

    renderSnapshotsTable: function(snapshots) {
        const tbody = document.getElementById('snapshots-tbody');
        if (!tbody) return;

        if (snapshots.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="snapshot-empty">No snapshots found</td></tr>';
            return;
        }

        tbody.innerHTML = snapshots.map(snapshot => `
            <tr>
                <td>${new Date(snapshot.snapshot_date).toLocaleDateString()}</td>
                <td>${modalManager.escapeHtml(snapshot.created_by_username || 'Unknown')}</td>
                <td>${new Date(snapshot.created_at).toLocaleString()}</td>
                <td class="actions">
                    <button onclick="SnapshotManager.viewSnapshot(${snapshot.id})" class="action-btn edit">View</button>
                    <button onclick="SnapshotManager.printSnapshot(${snapshot.id})" class="action-btn edit">Print</button>
                    ${hasRole('admin') ? `<button onclick="SnapshotManager.deleteSnapshot(${snapshot.id})" class="action-btn suspend">Delete</button>` : ''}
                </td>
            </tr>
        `).join('');
    },

    renderSnapshotsPagination: function(data) {
        const container = document.getElementById('snapshots-pagination');
        if (!container) return;

        const totalPages = Math.ceil(data.total / data.per_page);
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<div class="pagination">';
        
        if (data.page > 1) {
            paginationHTML += `<button onclick="SnapshotManager.loadSnapshots(${data.page - 1})" class="page-btn">Â&laquo; Previous</button>`;
        }
        
        for (let i = Math.max(1, data.page - 2); i <= Math.min(totalPages, data.page + 2); i++) {
            paginationHTML += `<button onclick="SnapshotManager.loadSnapshots(${i})" class="page-btn ${i === data.page ? 'active' : ''}">${i}</button>`;
        }
        
        if (data.page < totalPages) {
            paginationHTML += `<button onclick="SnapshotManager.loadSnapshots(${data.page + 1})" class="page-btn">Next Â&raquo;</button>`;
        }
        
        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    },

    viewSnapshot: function(snapshotId) {
        fetch(`${snapshotEndpoint}?action=view&id=${snapshotId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderSnapshotView(data.data, false);
                    modalManager.openModal('viewSnapshotModalBg');
                } else {
                    modalManager.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                modalManager.showToast('Error loading snapshot: ' + error.message, 'error');
            });
    },

    printSnapshot: function(snapshotId) {
        fetch(`${snapshotEndpoint}?action=view&id=${snapshotId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderSnapshotView(data.data, true);
                    document.title = `AMCReport(${data.data.snapshot_date})`;
                    
                    document.body.classList.add('is-printing');
                    window.print();
                    document.body.classList.remove('is-printing');

                } else {
                    modalManager.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                modalManager.showToast('Error loading snapshot for printing: ' + error.message, 'error');
            });
    },

    renderSnapshotView: function(snapshot, isPrinting) {
        const title = document.getElementById('snapshot-title');
        const content = document.getElementById('snapshot-content');
        
        if (title) {
            title.innerHTML = `Daily Snapshot - ${new Date(snapshot.snapshot_date).toLocaleDateString()}`;
        }

        if (!content) return;

        const data = snapshot.snapshot_data;
        let html = '';

        // Staff Roster Section
        if (data.staff_roster && data.staff_roster.length > 0) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Staff Roster</div>
                    <div class="snapshot-section-content">
            `;
            data.staff_roster.forEach(roster => {
                html += `
                    <div style="margin-bottom: 15px;">
                        <h4>${modalManager.escapeHtml(roster.shift)} - ${new Date(roster.roster_date).toLocaleDateString()}</h4>
                        <p><strong>Day Shift:</strong> ${[roster.day_shift_staff_1, roster.day_shift_staff_2, roster.day_shift_staff_3].filter(s => s).join(', ') || 'Not assigned'}</p>
                        <p><strong>Night Shift:</strong> ${[roster.night_shift_staff_1, roster.night_shift_staff_2, roster.night_shift_staff_3].filter(s => s).join(', ') || 'Not assigned'}</p>
                    </div>
                `;
            });
            html += `
                    </div>
                </div>
            `;
        }

        // Metrics Section
        if (data.daily_metrics) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Daily Metrics</div>
                    <div class="snapshot-section-content">
                        <div class="snapshot-metrics">
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.total_arrivals}</div>
                                <div class="snapshot-metric-label">Total Arrivals</div>
                            </div>
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.total_departures}</div>
                                <div class="snapshot-metric-label">Total Departures</div>
                            </div>
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.new_ron}</div>
                                <div class="snapshot-metric-label">New RON</div>
                            </div>
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.active_ron}</div>
                                <div class="snapshot-metric-label">Active RON</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Peak Hour Analysis Section
        if (data.daily_metrics && data.daily_metrics.hourly_movements) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Peak Hour Analysis</div>
                    <div class="snapshot-section-content">
                        ${this.renderPeakHourChart(data.daily_metrics.hourly_movements)}
                        ${this.renderPeakHourSummary(data.daily_metrics.hourly_movements)}
                    </div>
                </div>
            `;
        }

        // Movements Section
        if (data.movements && data.movements.length > 0) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Aircraft Movements</div>
                    <div class="snapshot-section-content">
                        <table class="snapshot-table">
                            <thead>
                                <tr>
                                    <th>Registration</th>
                                    <th>Type</th>
                                    <th>On Block</th>
                                    <th>Off Block</th>
                                    <th>Stand</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Category</th>
                                    <th>RON</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            data.movements.forEach(movement => {
                html += `
                    <tr>
                        <td>${modalManager.escapeHtml(movement.registration || '')}</td>
                        <td>${modalManager.escapeHtml(movement.aircraft_type || '')}</td>
                        <td>${modalManager.escapeHtml(movement.on_block_time || '')}</td>
                        <td>${modalManager.escapeHtml(movement.off_block_time || '')}</td>
                        <td>${modalManager.escapeHtml(movement.parking_stand || '')}</td>
                        <td>${modalManager.escapeHtml(movement.from_location || '')}</td>
                        <td>${modalManager.escapeHtml(movement.to_location || '')}</td>
                        <td>${modalManager.escapeHtml(movement.category || '')}</td>
                        <td>${movement.is_ron ? 'Yes' : 'No'}</td>
                    </tr>
                `;
            });
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } else {
            html += '<div class="snapshot-empty">No movements recorded for this date</div>';
        }

        // RON Section
        if (data.ron_data && data.ron_data.length > 0) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">RON Aircraft</div>
                    <div class="snapshot-section-content">
                        <table class="snapshot-table">
                            <thead>
                                <tr>
                                    <th>Registration</th>
                                    <th>Type</th>
                                    <th>Stand</th>
                                    <th>Operator</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            data.ron_data.forEach(ron => {
                html += `
                    <tr>
                        <td>${modalManager.escapeHtml(ron.registration || '')}</td>
                        <td>${modalManager.escapeHtml(ron.aircraft_type || '')}</td>
                        <td>${modalManager.escapeHtml(ron.parking_stand || '')}</td>
                        <td>${modalManager.escapeHtml(ron.operator_airline || '')}</td>
                        <td>${modalManager.escapeHtml(ron.category || '')}</td>
                    </tr>
                `;
            });
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        content.innerHTML = html;

        if (isPrinting) {
            const printableContent = document.getElementById('viewSnapshotModalBg');
            if (printableContent) {
                printableContent.style.display = 'block';
            }
        }
    },

    renderPeakHourChart: function(peakHourData) {
        let chartHTML = '';
        const maxMovements = Math.max(...peakHourData.map(h => (parseInt(h.Arrivals) || 0) + (parseInt(h.Departures) || 0))) || 1;

        chartHTML += '<div class="chart-container" style="position: relative; height: 400px; overflow-x: auto;">';
        chartHTML += '<div class="chart-content" style="display: flex; align-items: end; height: 100%; min-width: 800px; gap: 2px; padding: 0 10px;">';

        peakHourData.forEach(hour => {
            const arrivalHeight = (parseInt(hour.Arrivals) / maxMovements) * 300;
            const departureHeight = (parseInt(hour.Departures) / maxMovements) * 300;
            const totalHeight = ((parseInt(hour.Arrivals) + parseInt(hour.Departures)) / maxMovements) * 300;
            const shortLabel = hour.time_range.substring(0, 2) + '-' + hour.time_range.substring(6, 8);

            chartHTML += `
                <div class="hour-bar-group" style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative;">
                    <div style="display: flex; gap: 1px; align-items: end; height: 300px; margin-bottom: 5px;">
                        <div class="arrival-bar" 
                             style="width: 12px; background: linear-gradient(to top, #36A2EB, #5BC0DE); height: ${arrivalHeight}px; border-radius: 2px 2px 0 0;"
                             title="${hour.time_range} - Arrivals: ${hour.Arrivals}">
                        </div>
                        <div class="departure-bar" 
                             style="width: 12px; background: linear-gradient(to top, #FF6384, #FF8A80); height: ${departureHeight}px; border-radius: 2px 2px 0 0;"
                             title="${hour.time_range} - Departures: ${hour.Departures}">
                        </div>
                    </div>
                    ${(parseInt(hour.Arrivals) + parseInt(hour.Departures)) > 0 ? `
                    <div class="total-point" 
                         style="position: absolute; bottom: ${5 + totalHeight}px; left: 50%; transform: translateX(-50%); width: 6px; height: 6px; background: #4BC0C0; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"
                         title="${hour.time_range} - Total: ${parseInt(hour.Arrivals) + parseInt(hour.Departures)}">
                    </div>
                    ` : ''}
                    <div style="font-size: 10px; color: #666; text-align: center; writing-mode: vertical-rl; text-orientation: mixed; height: 40px; display: flex; align-items: center;">
                        ${shortLabel}
                    </div>
                    ${(parseInt(hour.Arrivals) + parseInt(hour.Departures)) > 0 ? `
                    <div style="font-size: 9px; color: #333; font-weight: bold; margin-top: 2px;">
                        ${parseInt(hour.Arrivals) + parseInt(hour.Departures)}
                    </div>
                    ` : ''}
                </div>
            `;
        });

        chartHTML += '</div></div>';
        return chartHTML;
    },

    renderPeakHourSummary: function(peakHourData) {
        const dataWithTotals = peakHourData.map(h => ({ 
            ...h, 
            Arrivals: parseInt(h.Arrivals) || 0,
            Departures: parseInt(h.Departures) || 0,
            total: (parseInt(h.Arrivals) || 0) + (parseInt(h.Departures) || 0)
        }));

        const sortedByTotal = [...dataWithTotals].sort((a, b) => b.total - a.total);
        const peakPeriod = sortedByTotal[0] || { time_range: 'N/A', total: 0 };
        const quietPeriod = [...dataWithTotals].sort((a, b) => a.total - b.total).find(h => h.total > 0) || sortedByTotal[sortedByTotal.length - 1] || { time_range: 'N/A', total: 0 };
        
        const totalMovements = dataWithTotals.reduce((sum, h) => sum + h.total, 0);
        const totalArrivals = dataWithTotals.reduce((sum, h) => sum + h.Arrivals, 0);
        const totalDepartures = dataWithTotals.reduce((sum, h) => sum + h.Departures, 0);
        
        let busiestPeriod = { start: 0, total: 0 };
        for (let i = 0; i < dataWithTotals.length - 1; i++) {
            const windowTotal = dataWithTotals[i].total + dataWithTotals[i + 1].total;
            if (windowTotal > busiestPeriod.total) {
                busiestPeriod = { start: i, total: windowTotal };
            }
        }
        
        let busiestStart = "00:00-01:59";
        let busiestEnd = "02:00-03:59";
        if (dataWithTotals.length > 1 && busiestPeriod.start < dataWithTotals.length - 1) {
            busiestStart = dataWithTotals[busiestPeriod.start].time_range;
            busiestEnd = dataWithTotals[busiestPeriod.start + 1].time_range;
        }
        
        return `
            <div id="peakHoursSummary" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #112D4E;">
                <div style="font-weight: bold; margin-bottom: 10px; color: #112D4E;">Peak Hours Summary</div>
                <div id="peakHoursContent" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #dc3545;">Peak 2-Hour Period:</strong>
                        <span>${peakPeriod.time_range} (${peakPeriod.total} movements)</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #28a745;">Quietest 2-Hour Period:</strong>
                        <span>${quietPeriod.time_range} (${quietPeriod.total} movements)</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #007bff;">Busiest 4-Hour Window:</strong>
                        <span>${busiestStart.split(':')[0]}:00-${busiestEnd.split('-')[1]} (${busiestPeriod.total} movements)</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #6c757d;">Day's Total:</strong>
                        <span>${totalMovements} movements (${totalArrivals} arr, ${totalDepartures} dep)</span>
                    </div>
                </div>
            </div>
        `;
    },

    deleteSnapshot: function(snapshotId) {
        if (!confirm('Are you sure you want to delete this snapshot? This action cannot be undone.')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', snapshotId);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        fetch(snapshotEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalManager.showToast(data.message, 'success');
                this.loadSnapshots();
            } else {
                modalManager.showToast(data.message, 'error');
            }
        })
        .catch(error => {
            modalManager.showToast('Error deleting snapshot: ' + error.message, 'error');
        });
    }
};

// Initialize modal manager
const modalManager = new ModalManager();

// Form submissions
document.addEventListener('DOMContentLoaded', function() {
    updatePeakHoursSummary();

    // User form submission
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const isEdit = formData.get('id');
            
            // FIX: Remove username from edit payload
            if (isEdit) {
                formData.delete('username');
            }
            
            formData.append('action', isEdit ? 'update' : 'create');

            fetch(modalManager.userEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().catch(() => {
                throw new Error('Invalid JSON response from server');
            }))
            .then(data => {
                if (data.success) {
                    modalManager.showToast(data.message, 'success');
                    if (data.temp_password) {
                        modalManager.showToast(`Temporary password: ${data.temp_password}`, 'info');
                    }
                    modalManager.closeModal(document.getElementById('userFormModalBg'));
                    modalManager.loadUsers();
                } else {
                    modalManager.showToast(data.message || 'Unable to save user.', 'error');
                }
            })
            .catch(error => {
                modalManager.showToast('Error saving user: ' + error.message, 'error');
            });
        });
    }

    // New user button
    const newUserBtn = document.getElementById('new-user-btn');
    if (newUserBtn) {
        newUserBtn.addEventListener('click', function() {
            document.getElementById('user-form-title').textContent = 'Create User';
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('password-row').style.display = 'table-row';
            modalManager.closeModal(document.getElementById('accountsModalBg'));
            modalManager.openModal('userFormModalBg');
        });
    }

    // Search and filter handlers
    const userSearch = document.getElementById('user-search');
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const refreshBtn = document.getElementById('refresh-users');

    if (userSearch) {
        let searchTimeout;
        userSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => modalManager.loadUsers(), 500);
        });
    }

    if (roleFilter) {
        roleFilter.addEventListener('change', () => modalManager.loadUsers());
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', () => modalManager.loadUsers());
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => modalManager.loadUsers());
    }

    // Copy password functionality
    const copyBtn = document.getElementById('copy-password');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const passwordInput = document.getElementById('temp-password-value');
            passwordInput.select();
            document.execCommand('copy');
            modalManager.showToast('Password copied to clipboard', 'success');
        });
    }

    // Charter form date handling
    const charterForm = document.getElementById('charter-report-form');
    if (charterForm) {
        charterForm.addEventListener('submit', function(e) {
            const monthYearInput = document.getElementById('charter-month');
            const [year, month] = monthYearInput.value.split('-');
            document.getElementById('charter-month-hidden').value = month;
            document.getElementById('charter-year-hidden').value = year;
        });
    }

    // Password reset form submission
    const resetPasswordForm = document.getElementById('reset-password-form');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'reset_password');

            fetch(modalManager.userEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().catch(() => {
                throw new Error('Invalid JSON response from server');
            }))
            .then(data => {
                if (data.success) {
                    modalManager.showToast(data.message, 'success');
                    modalManager.closeModal(document.getElementById('resetPasswordModalBg'));
                } else {
                    modalManager.showToast(data.message || 'Unable to reset password.', 'error');
                }
            })
            .catch(error => {
                modalManager.showToast('Error resetting password: ' + error.message, 'error');
            });
        });
    }

    // Create snapshot form submission
    const createSnapshotForm = document.getElementById('create-snapshot-form');
    if (createSnapshotForm) {
       createSnapshotForm.addEventListener('submit', function(e) {
           e.preventDefault();
           const formData = new FormData(this);
           formData.append('action', 'create');

           fetch(snapshotEndpoint, {
               method: 'POST',
               body: formData
           })
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   modalManager.showToast(data.message, 'success');
                   this.reset();
                   SnapshotManager.loadSnapshots();
               } else {
                   modalManager.showToast(data.message, 'error');
               }
           })
           .catch(error => {
               modalManager.showToast('Error creating snapshot: ' + error.message, 'error');
           });
       });
    }
});

// Add role checking function for JavaScript
function hasRole(role) {
   if (Array.isArray(role)) {
       return role.includes(userRole);
   }
   return userRole === role;
}
})();
