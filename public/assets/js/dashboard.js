(function () {
    const config = window.dashboardConfig || {};
    const endpoints = config.endpoints || {};
    const refreshApronEndpoint = resolveEndpoint(endpoints.refreshApron || 'api/apron/status');
    const userEndpoint = resolveEndpoint(endpoints.userAdmin || 'api/admin/users');
    const snapshotEndpoint = resolveEndpoint(endpoints.snapshots || 'api/snapshots');
    const userRole = config.userRole || 'viewer';
    const peakHourData = Array.isArray(config.peakHourData) ? config.peakHourData : [];

    document.addEventListener('DOMContentLoaded', () => {
        updateLiveApronStatus();
        setInterval(updateLiveApronStatus, 5000);
        renderPeakHoursSummary();
        setupFormHandlers();
        const modalManager = new ModalManager();
        window.modalManager = modalManager;
        window.SnapshotManager = SnapshotManager;
    });

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

    async function updateLiveApronStatus() {
        try {
            const data = await fetchJson(refreshApronEndpoint);
            const mappings = {
                'apron-total': data.total,
                'apron-available': data.available,
                'apron-occupied': data.occupied,
                'apron-ron': data.ron
            };

            Object.entries(mappings).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element && typeof value !== 'undefined') {
                    element.textContent = value;
                }
            });
        } catch (error) {
            console.debug('Unable to refresh live apron status:', error);
        }
    }

    function renderPeakHoursSummary() {
        const container = document.getElementById('peakHoursContent');
        if (!container) {
            return;
        }

        const summary = buildPeakHoursSummary();
        container.innerHTML = summary.html;

        const summaryWrapper = document.getElementById('peakHoursSummary');
        if (summaryWrapper) {
            summaryWrapper.setAttribute('data-total-movements', summary.totals.total);
        }
    }

    function buildPeakHoursSummary() {
        const dataWithTotals = peakHourData.map(entry => ({
            ...entry,
            Arrivals: parseInt(entry.Arrivals, 10) || 0,
            Departures: parseInt(entry.Departures, 10) || 0
        })).map(entry => ({
            ...entry,
            total: entry.Arrivals + entry.Departures
        }));

        if (!dataWithTotals.length) {
            return {
                html: '<div class="text-sm text-gray-500">No movement data available for today.</div>',
                totals: { total: 0 }
            };
        }

        const sortedByTotal = [...dataWithTotals].sort((a, b) => b.total - a.total);
        const nonZero = sortedByTotal.filter(item => item.total > 0);
        const peakPeriod = sortedByTotal[0] || { time_range: 'N/A', total: 0 };
        const quietPeriod = nonZero[nonZero.length - 1] || sortedByTotal[sortedByTotal.length - 1] || { time_range: 'N/A', total: 0 };

        const totalMovements = dataWithTotals.reduce((sum, entry) => sum + entry.total, 0);
        const totalArrivals = dataWithTotals.reduce((sum, entry) => sum + entry.Arrivals, 0);
        const totalDepartures = dataWithTotals.reduce((sum, entry) => sum + entry.Departures, 0);

        let busiestWindow = { start: 0, total: 0 };
        for (let index = 0; index < dataWithTotals.length - 1; index++) {
            const windowTotal = dataWithTotals[index].total + dataWithTotals[index + 1].total;
            if (windowTotal > busiestWindow.total) {
                busiestWindow = { start: index, total: windowTotal };
            }
        }

        const startRange = dataWithTotals[busiestWindow.start]?.time_range || '00:00-01:59';
        const endRange = dataWithTotals[busiestWindow.start + 1]?.time_range || '02:00-03:59';

        const html = `
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-400">
                    <strong class="text-red-600">Peak 2-Hour Period</strong>
                    <span class="block text-sm text-gray-700">${peakPeriod.time_range} (${peakPeriod.total} movements)</span>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
                    <strong class="text-green-600">Quietest 2-Hour Period</strong>
                    <span class="block text-sm text-gray-700">${quietPeriod.time_range} (${quietPeriod.total} movements)</span>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
                    <strong class="text-blue-600">Busiest 4-Hour Window</strong>
                    <span class="block text-sm text-gray-700">${startRange.split(':')[0]}:00-${endRange.split('-')[1]} (${busiestWindow.total} movements)</span>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
                    <strong class="text-gray-700">Day's Total</strong>
                    <span class="block text-sm text-gray-700">${totalMovements} movements (${totalArrivals} arrivals / ${totalDepartures} departures)</span>
                </div>
            </div>
        `;

        return {
            html,
            totals: {
                total: totalMovements,
                arrivals: totalArrivals,
                departures: totalDepartures
            }
        };
    }

    class ModalManager {
        constructor() {
            this.userEndpoint = userEndpoint;
            this.setupEventListeners();
        }

        setupEventListeners() {
            document.addEventListener('click', event => {
                const target = event.target;

                if (target.hasAttribute('data-modal-target')) {
                    event.preventDefault();
                    const modalId = target.getAttribute('data-modal-target');
                    this.openModal(modalId);
                }

                if (target.hasAttribute('data-modal-close')) {
                    event.preventDefault();
                    const modal = target.closest('.modal-backdrop');
                    this.closeModal(modal);
                }

                if (target.classList.contains('modal-backdrop') && target.dataset.dismiss !== 'false') {
                    this.closeModal(target);
                }
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    const openModal = document.querySelector('.modal-backdrop[style*="display: flex"]');
                    if (openModal) {
                        this.closeModal(openModal);
                    }
                }
            });
        }

        openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => {
                modal.style.display = 'flex';
                modal.style.alignItems = 'flex-start';
                modal.style.paddingTop = '50px';

                if (modalId === 'accountsModalBg') {
                    this.loadUsers();
                } else if (modalId === 'snapshotModalBg') {
                    SnapshotManager.loadSnapshots();
                }
            }, 200);
        }

        closeModal(modal) {
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            modal.querySelectorAll('form').forEach(form => form.reset());
        }

        async loadUsers(page = 1) {
            const queryValue = document.getElementById('user-search')?.value || '';
            const roleValue = document.getElementById('role-filter')?.value || '';
            const statusValue = document.getElementById('status-filter')?.value || '';

            const params = new URLSearchParams({
                action: 'list',
                query: queryValue,
                role: roleValue,
                status: statusValue,
                page: String(page),
                per_page: '25'
            });

            try {
                const data = await fetchJson(`${this.userEndpoint}?${params.toString()}`);
                if (!data.success) {
                    this.showToast(data.message || 'Failed to load users.', 'error');
                    return;
                }

                this.renderUsersTable(data.data);
                this.renderPagination(data);
            } catch (error) {
                this.showToast('Error loading users: ' + error.message, 'error');
            }
        }

        renderUsersTable(users) {
            const tbody = document.getElementById('users-tbody');
            if (!tbody) {
                return;
            }

            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>${this.escapeHtml(user.full_name || '')}</td>
                    <td>${this.escapeHtml(user.username)}</td>
                    <td>${this.escapeHtml(user.email || '')}</td>
                    <td><span class="role-badge role-${user.role}">${this.escapeHtml(user.role)}</span></td>
                    <td><span class="status-badge status-${user.status}">${this.escapeHtml(user.status)}</span></td>
                    <td>${user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : 'Never'}</td>
                    <td class="actions">
                        <button type="button" onclick="modalManager.editUser(${user.id})" class="action-btn edit">Edit</button>
                        <button type="button" onclick="modalManager.resetPassword(${user.id}, '${this.escapeHtml(user.username)}')" class="action-btn reset">Reset PW</button>
                        <button type="button" onclick="modalManager.toggleStatus(${user.id}, '${this.escapeHtml(user.status)}')" class="action-btn ${user.status === 'active' ? 'suspend' : 'activate'}">
                            ${user.status === 'active' ? 'Suspend' : 'Activate'}
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        renderPagination(meta) {
            const container = document.getElementById('accounts-pagination');
            if (!container) {
                return;
            }

            const totalPages = Math.ceil(meta.total / meta.per_page);
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '<div class="pagination">';
            if (meta.page > 1) {
                html += `<button type="button" onclick="modalManager.loadUsers(${meta.page - 1})" class="page-btn">&laquo; Previous</button>`;
            }

            for (let page = Math.max(1, meta.page - 2); page <= Math.min(totalPages, meta.page + 2); page++) {
                html += `<button type="button" onclick="modalManager.loadUsers(${page})" class="page-btn ${page === meta.page ? 'active' : ''}">${page}</button>`;
            }

            if (meta.page < totalPages) {
                html += `<button type="button" onclick="modalManager.loadUsers(${meta.page + 1})" class="page-btn">Next &raquo;</button>`;
            }

            html += '</div>';
            container.innerHTML = html;
        }

        async editUser(userId) {
            try {
                const data = await fetchJson(`${this.userEndpoint}?action=list`);
                if (!data.success) {
                    this.showToast(data.message || 'Unable to load user details.', 'error');
                    return;
                }

                const user = (data.data || []).find(entry => Number(entry.id) === Number(userId));
                if (!user) {
                    this.showToast('User record not found.', 'error');
                    return;
                }

                document.getElementById('user-form-title').textContent = 'Edit User';
                document.getElementById('user-id').value = user.id;
                document.getElementById('user-full-name').value = user.full_name || '';
                document.getElementById('user-username').value = user.username || '';
                document.getElementById('user-email').value = user.email || '';
                document.getElementById('user-role').value = user.role || '';
                document.getElementById('user-status').value = user.status || '';
                document.getElementById('password-row').style.display = 'none';

                this.openModal('userFormModalBg');
            } catch (error) {
                this.showToast('Error loading user details: ' + error.message, 'error');
            }
        }

        resetPassword(userId, username) {
            const usernameLabel = document.getElementById('reset-username');
            const userField = document.getElementById('reset-user-id');
            const form = document.getElementById('reset-password-form');

            if (usernameLabel) {
                usernameLabel.textContent = username;
            }
            if (userField) {
                userField.value = userId;
            }
            if (form) {
                form.reset();
            }

            this.openModal('resetPasswordModalBg');
        }

        async toggleStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
            const actionLabel = newStatus === 'suspended' ? 'suspend' : 'activate';

            if (!confirm(`Are you sure you want to ${actionLabel} this user?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'set_status');
            formData.append('id', userId);
            formData.append('status', newStatus);
            formData.append('csrf_token', getCsrfToken());

            try {
                const data = await fetchJson(this.userEndpoint, {
                    method: 'POST',
                    body: formData
                });

                if (!data.success) {
                    this.showToast(data.message || 'Unable to update user status.', 'error');
                    return;
                }

                this.showToast(data.message, 'success');
                this.loadUsers();
            } catch (error) {
                this.showToast('Error updating user status: ' + error.message, 'error');
            }
        }

        escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 24px;
                border-radius: 4px;
                color: #fff;
                font-weight: 500;
                z-index: 10000;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            `;

            const colors = {
                success: '#28a745',
                error: '#dc3545',
                info: '#17a2b8'
            };
            toast.style.backgroundColor = colors[type] || colors.info;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }

    const SnapshotManager = {
        async loadSnapshots(page = 1) {
            const tbody = document.getElementById('snapshots-tbody');
            const loading = document.getElementById('snapshots-loading');

            if (loading) {
                loading.style.display = 'block';
            }
            if (tbody) {
                tbody.innerHTML = '';
            }

            const params = new URLSearchParams({ action: 'list', page: String(page), per_page: '20' });

            try {
                const data = await fetchJson(`${snapshotEndpoint}?${params.toString()}`);

                if (loading) {
                    loading.style.display = 'none';
                }

                if (!data.success) {
                    modalManager.showToast(data.message || 'Failed to load snapshots.', 'error');
                    return;
                }

                this.renderSnapshotsTable(data.data || []);
                this.renderSnapshotsPagination(data);
            } catch (error) {
                if (loading) {
                    loading.style.display = 'none';
                }
                modalManager.showToast('Error loading snapshots: ' + error.message, 'error');
            }
        },

        renderSnapshotsTable(snapshots) {
            const tbody = document.getElementById('snapshots-tbody');
            if (!tbody) {
                return;
            }

            if (!snapshots.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="snapshot-empty">No snapshots found</td></tr>';
                return;
            }

            tbody.innerHTML = snapshots.map(snapshot => `
                <tr>
                    <td>${new Date(snapshot.snapshot_date).toLocaleDateString()}</td>
                    <td>${modalManager.escapeHtml(snapshot.created_by_username || 'Unknown')}</td>
                    <td>${new Date(snapshot.created_at).toLocaleString()}</td>
                    <td class="actions">
                        <button type="button" onclick="SnapshotManager.viewSnapshot(${snapshot.id})" class="action-btn edit">View</button>
                        <button type="button" onclick="SnapshotManager.printSnapshot(${snapshot.id})" class="action-btn edit">Print</button>
                        ${hasRole('admin') ? `<button type="button" onclick="SnapshotManager.deleteSnapshot(${snapshot.id})" class="action-btn suspend">Delete</button>` : ''}
                    </td>
                </tr>
            `).join('');
        },

        renderSnapshotsPagination(meta) {
            const container = document.getElementById('snapshots-pagination');
            if (!container) {
                return;
            }

            const totalPages = Math.ceil(meta.total / meta.per_page);
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '<div class="pagination">';
            if (meta.page > 1) {
                html += `<button type="button" onclick="SnapshotManager.loadSnapshots(${meta.page - 1})" class="page-btn">&laquo; Previous</button>`;
            }

            for (let page = Math.max(1, meta.page - 2); page <= Math.min(totalPages, meta.page + 2); page++) {
                html += `<button type="button" onclick="SnapshotManager.loadSnapshots(${page})" class="page-btn ${page === meta.page ? 'active' : ''}">${page}</button>`;
            }

            if (meta.page < totalPages) {
                html += `<button type="button" onclick="SnapshotManager.loadSnapshots(${meta.page + 1})" class="page-btn">Next &raquo;</button>`;
            }

            html += '</div>';
            container.innerHTML = html;
        },

        async viewSnapshot(id) {
            try {
                const data = await fetchJson(`${snapshotEndpoint}?action=view&id=${id}`);
                if (!data.success) {
                    modalManager.showToast(data.message || 'Unable to load snapshot.', 'error');
                    return;
                }

                this.renderSnapshotView(data.data, false);
                modalManager.openModal('viewSnapshotModalBg');
            } catch (error) {
                modalManager.showToast('Error loading snapshot: ' + error.message, 'error');
            }
        },

        async printSnapshot(id) {
            try {
                const data = await fetchJson(`${snapshotEndpoint}?action=view&id=${id}`);
                if (!data.success) {
                    modalManager.showToast(data.message || 'Unable to load snapshot for printing.', 'error');
                    return;
                }

                this.renderSnapshotView(data.data, true);
                const originalTitle = document.title;
                document.title = `AMCReport(${data.data.snapshot_date})`;
                document.body.classList.add('is-printing');
                window.print();
                document.body.classList.remove('is-printing');
                document.title = originalTitle;
            } catch (error) {
                modalManager.showToast('Error preparing snapshot for printing: ' + error.message, 'error');
            }
        },

        renderSnapshotView(snapshot, isPrinting) {
            const title = document.getElementById('snapshot-title');
            const content = document.getElementById('snapshot-content');
            if (title) {
                title.textContent = `Daily Snapshot - ${new Date(snapshot.snapshot_date).toLocaleDateString()}`;
            }
            if (!content) {
                return;
            }

            const data = snapshot.snapshot_data || {};

            const rosterHtml = `
                <div class="snapshot-section">
                    <h3 class="snapshot-section-title">Daily Staff Roster</h3>
                    <table class="snapshot-table">
                        <thead>
                            <tr>
                                <th>Shift</th>
                                <th>Staff 1</th>
                                <th>Staff 2</th>
                                <th>Staff 3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Day</td>
                                <td>${modalManager.escapeHtml(data.roster?.day_shift_staff_1 || '')}</td>
                                <td>${modalManager.escapeHtml(data.roster?.day_shift_staff_2 || '')}</td>
                                <td>${modalManager.escapeHtml(data.roster?.day_shift_staff_3 || '')}</td>
                            </tr>
                            <tr>
                                <td>Night</td>
                                <td>${modalManager.escapeHtml(data.roster?.night_shift_staff_1 || '')}</td>
                                <td>${modalManager.escapeHtml(data.roster?.night_shift_staff_2 || '')}</td>
                                <td>${modalManager.escapeHtml(data.roster?.night_shift_staff_3 || '')}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;

            const movementsHtml = `
                <div class="snapshot-section">
                    <h3 class="snapshot-section-title">Aircraft Movements</h3>
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
                                <th>Arr</th>
                                <th>Dep</th>
                                <th>Operator</th>
                                <th>Remarks</th>
                                <th>RON</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(data.movements || []).map(m => `
                                <tr>
                                    <td>${modalManager.escapeHtml(m.registration)}</td>
                                    <td>${modalManager.escapeHtml(m.aircraft_type)}</td>
                                    <td>${modalManager.escapeHtml(m.on_block_time)}</td>
                                    <td>${modalManager.escapeHtml(m.off_block_time)}</td>
                                    <td>${modalManager.escapeHtml(m.parking_stand)}</td>
                                    <td>${modalManager.escapeHtml(m.from_location)}</td>
                                    <td>${modalManager.escapeHtml(m.to_location)}</td>
                                    <td>${modalManager.escapeHtml(m.flight_no_arr)}</td>
                                    <td>${modalManager.escapeHtml(m.flight_no_dep)}</td>
                                    <td>${modalManager.escapeHtml(m.operator_airline)}</td>
                                    <td>${modalManager.escapeHtml(m.remarks)}</td>
                                    <td>${m.is_ron ? 'Yes' : 'No'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            const ronHtml = `
                <div class="snapshot-section">
                    <h3 class="snapshot-section-title">Remain Over Night (RON)</h3>
                    <table class="snapshot-table">
                        <thead>
                            <tr>
                                <th>Registration</th>
                                <th>Type</th>
                                <th>Stand</th>
                                <th>Arrival Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(data.ron || []).map(r => `
                                <tr>
                                    <td>${modalManager.escapeHtml(r.registration)}</td>
                                    <td>${modalManager.escapeHtml(r.aircraft_type)}</td>
                                    <td>${modalManager.escapeHtml(r.parking_stand)}</td>
                                    <td>${new Date(r.arrival_date).toLocaleDateString()}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            content.innerHTML = rosterHtml + movementsHtml + ronHtml;
        },

        async createSnapshot() {
            if (!confirm('Are you sure you want to create a new daily snapshot? This will capture the current state of all movements and rosters.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('csrf_token', getCsrfToken());

            try {
                const data = await fetchJson(snapshotEndpoint, {
                    method: 'POST',
                    body: formData
                });

                if (!data.success) {
                    modalManager.showToast(data.message || 'Failed to create snapshot.', 'error');
                    return;
                }

                modalManager.showToast(data.message, 'success');
                this.loadSnapshots();
            } catch (error) {
                modalManager.showToast('Error creating snapshot: ' + error.message, 'error');
            }
        },

        async deleteSnapshot(id) {
            if (!confirm('Are you sure you want to permanently delete this snapshot? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('csrf_token', getCsrfToken());

            try {
                const data = await fetchJson(snapshotEndpoint, {
                    method: 'POST',
                    body: formData
                });

                if (!data.success) {
                    modalManager.showToast(data.message || 'Failed to delete snapshot.', 'error');
                    return;
                }

                modalManager.showToast(data.message, 'success');
                this.loadSnapshots();
            } catch (error) {
                modalManager.showToast('Error deleting snapshot: ' + error.message, 'error');
            }
        }
    };

    function setupFormHandlers() {
        const userForm = document.getElementById('user-form');
        if (userForm) {
            userForm.addEventListener('submit', async event => {
                event.preventDefault();
                const formData = new FormData(userForm);
                formData.append('action', 'create');
                formData.append('csrf_token', getCsrfToken());

                try {
                    const data = await fetchJson(userEndpoint, {
                        method: 'POST',
                        body: formData
                    });

                    if (!data.success) {
                        modalManager.showToast(data.message || 'Failed to save user.', 'error');
                        return;
                    }

                    modalManager.showToast(data.message, 'success');
                    modalManager.closeModal(userForm.closest('.modal-backdrop'));
                    modalManager.loadUsers();
                } catch (error) {
                    modalManager.showToast('Error saving user: ' + error.message, 'error');
                }
            });
        }

        const resetPasswordForm = document.getElementById('reset-password-form');
        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', async event => {
                event.preventDefault();
                const formData = new FormData(resetPasswordForm);
                formData.append('action', 'reset_password');
                formData.append('csrf_token', getCsrfToken());

                try {
                    const data = await fetchJson(userEndpoint, {
                        method: 'POST',
                        body: formData
                    });

                    if (!data.success) {
                        modalManager.showToast(data.message || 'Failed to reset password.', 'error');
                        return;
                    }

                    modalManager.showToast(data.message, 'success');
                    modalManager.closeModal(resetPasswordForm.closest('.modal-backdrop'));
                } catch (error) {
                    modalManager.showToast('Error resetting password: ' + error.message, 'error');
                }
            });
        }

        const userSearch = document.getElementById('user-search');
        if (userSearch) {
            userSearch.addEventListener('input', () => modalManager.loadUsers());
        }

        const roleFilter = document.getElementById('role-filter');
        if (roleFilter) {
            roleFilter.addEventListener('change', () => modalManager.loadUsers());
        }

        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => modalManager.loadUsers());
        }

        const createSnapshotBtn = document.getElementById('create-snapshot-btn');
        if (createSnapshotBtn) {
            createSnapshotBtn.addEventListener('click', () => SnapshotManager.createSnapshot());
        }

        const newUserBtn = document.getElementById('new-user-btn');
        if (newUserBtn) {
            newUserBtn.addEventListener('click', () => {
                document.getElementById('user-form-title').textContent = 'Create User';
                document.getElementById('user-form').reset();
                document.getElementById('user-id').value = '';
                document.getElementById('password-row').style.display = 'flex';
                modalManager.openModal('userFormModalBg');
            });
        }

        const refreshUsersBtn = document.getElementById('refresh-users');
        if (refreshUsersBtn) {
            refreshUsersBtn.addEventListener('click', () => modalManager.loadUsers());
        }
    }

    function getCsrfToken() {
        return document.querySelector('input[name="csrf_token"]')?.value || '';
    }

    function hasRole(role) {
        if (Array.isArray(role)) {
            return role.includes(userRole);
        }
        return userRole === role;
    }
})();