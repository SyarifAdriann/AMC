<?php
$canManageUsers = ($user_role ?? '') === 'admin';
?>
<div id="accountsModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-6 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-lg shadow-2xl w-full max-w-5xl mx-4 my-6">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Manage User Accounts</h2>
                <p class="text-xs text-slate-500">Search, filter, and maintain operator access for the AMC platform.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close accounts modal">
                <span class="text-xl leading-none">&times;</span>
            </button>
        </div>

        <div class="px-4 py-3 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
                <div class="flex flex-col">
                    <label for="user-search" class="text-xs font-medium text-slate-600 mb-1">Search</label>
                    <input id="user-search" type="text" class="text-xs rounded border border-slate-300 px-2 py-1.5 focus:ring-1 focus:ring-blue-500 focus:outline-none" placeholder="Name, username, email">
                </div>
                <div class="flex flex-col">
                    <label for="role-filter" class="text-xs font-medium text-slate-600 mb-1">Role</label>
                    <select id="role-filter" class="text-xs rounded border border-slate-300 px-2 py-1.5 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="">All roles</option>
                        <option value="admin">Admin</option>
                        <option value="operator">Operator</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label for="status-filter" class="text-xs font-medium text-slate-600 mb-1">Status</label>
                    <select id="status-filter" class="text-xs rounded border border-slate-300 px-2 py-1.5 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="">All accounts</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="flex items-end gap-1.5">
                    <button id="refresh-users" type="button" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-2 py-1.5 rounded text-xs transition-colors duration-200">Refresh</button>
                    <?php if ($canManageUsers): ?>
                    <button id="new-user-btn" type="button" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-2 py-1.5 rounded text-xs transition-colors duration-200">New User</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase tracking-wide text-xs">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Name</th>
                            <th class="px-3 py-2 text-left font-semibold">Username</th>
                            <th class="px-3 py-2 text-left font-semibold">Email</th>
                            <th class="px-3 py-2 text-left font-semibold">Role</th>
                            <th class="px-3 py-2 text-left font-semibold">Status</th>
                            <th class="px-3 py-2 text-left font-semibold">Last Login</th>
                            <?php if ($canManageUsers): ?>
                            <th class="px-3 py-2 text-left font-semibold">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="users-tbody" class="divide-y divide-slate-200 bg-white">
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-slate-500">Use the filters above to load user accounts.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="accounts-pagination" class="flex justify-center pt-1"></div>
        </div>
    </div>
</div>
