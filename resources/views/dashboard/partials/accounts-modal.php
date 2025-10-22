<?php
$canManageUsers = ($user_role ?? '') === 'admin';
?>
<div id="accountsModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-6 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-5xl mx-4 my-6">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Manage User Accounts</h2>
                <p class="text-sm text-slate-500">Search, filter, and maintain operator access for the AMC platform.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close accounts modal">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <div class="px-6 py-4 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="flex flex-col">
                    <label for="user-search" class="text-sm font-medium text-slate-600 mb-1">Search</label>
                    <input id="user-search" type="text" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Name, username, email">
                </div>
                <div class="flex flex-col">
                    <label for="role-filter" class="text-sm font-medium text-slate-600 mb-1">Role</label>
                    <select id="role-filter" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">All roles</option>
                        <option value="admin">Admin</option>
                        <option value="operator">Operator</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label for="status-filter" class="text-sm font-medium text-slate-600 mb-1">Status</label>
                    <select id="status-filter" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">All accounts</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button id="refresh-users" type="button" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-3 py-2 rounded-md transition-colors duration-200">Refresh</button>
                    <?php if ($canManageUsers): ?>
                    <button id="new-user-btn" type="button" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded-md transition-colors duration-200">New User</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-slate-600 uppercase tracking-wide text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Username</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Last Login</th>
                            <?php if ($canManageUsers): ?>
                            <th class="px-4 py-3 text-left">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="users-tbody" class="divide-y divide-slate-200">
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">Use the filters above to load user accounts.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="accounts-pagination" class="flex justify-center pt-2"></div>
        </div>
    </div>
</div>
