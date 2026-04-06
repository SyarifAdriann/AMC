<?php
$roles = ['admin' => 'Administrator', 'operator' => 'Operator', 'viewer' => 'Viewer'];
$statuses = ['active' => 'Active', 'suspended' => 'Suspended'];
?>
<div id="userFormModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-6 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 id="user-form-title" class="text-xl font-semibold">Create User</h2>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close user form modal">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>
        <form id="user-form" class="px-6 py-5 space-y-4" method="post" action="api/admin/users">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id" id="user-id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <label for="user-full-name" class="text-sm font-medium text-slate-600 mb-1">Full Name</label>
                    <input type="text" id="user-full-name" name="full_name" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>
                <div class="flex flex-col">
                    <label for="user-username" class="text-sm font-medium text-slate-600 mb-1">Username</label>
                    <input type="text" id="user-username" name="username" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>
                <div class="flex flex-col">
                    <label for="user-email" class="text-sm font-medium text-slate-600 mb-1">Email</label>
                    <input type="email" id="user-email" name="email" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex flex-col">
                    <label for="user-role" class="text-sm font-medium text-slate-600 mb-1">Role</label>
                    <select id="user-role" name="role" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                        <option value="" disabled selected>Select role</option>
                        <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label for="user-status" class="text-sm font-medium text-slate-600 mb-1">Status</label>
                    <select id="user-status" name="status" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                        <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="password-row" class="flex flex-col">
                <label for="user-password" class="text-sm font-medium text-slate-600 mb-1">Password</label>
                <input type="password" id="user-password" name="password" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Leave blank to auto-generate">
                <p class="text-xs text-slate-500 mt-1">If left blank, a secure temporary password will be generated automatically.</p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200" data-modal-close>Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200">Save User</button>
            </div>
        </form>
    </div>
</div>
