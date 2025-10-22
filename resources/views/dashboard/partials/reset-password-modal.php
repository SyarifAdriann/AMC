<div id="resetPasswordModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-10 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold">Reset Password</h2>
                <p class="text-sm text-slate-500">Provide a new password for <span id="reset-username" class="font-semibold text-slate-700"></span>.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close reset password modal">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>
        <form id="reset-password-form" class="px-6 py-5 space-y-4" method="post" action="api/admin/users">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="reset-user-id" name="id" value="">

            <div class="flex flex-col">
                <label for="reset-password-input" class="text-sm font-medium text-slate-600 mb-1">New Password</label>
                <input type="password" id="reset-password-input" name="password" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>

            <div class="flex items-center gap-2">
                <input type="text" id="temp-password-value" class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 bg-slate-100" placeholder="Optional: paste generated password">
                <button type="button" id="copy-password" class="px-3 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200">Copy</button>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200" data-modal-close>Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200">Update Password</button>
            </div>
        </form>
    </div>
</div>
