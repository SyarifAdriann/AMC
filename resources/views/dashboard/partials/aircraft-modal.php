<div id="aircraftModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-10 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-4xl mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold">Manage Aircraft Details</h2>
                <p class="text-sm text-slate-500">Search, edit, add or remove aircraft reference records.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close aircraft modal">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <div class="px-6 py-5 space-y-5">
            <form id="aircraft-ref-form" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="flex flex-col">
                    <label for="manage-aircraft-registration" class="text-sm font-medium text-slate-600 mb-1">Registration</label>
                    <input type="text" id="manage-aircraft-registration" name="registration" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none uppercase" placeholder="PK-***" required>
                </div>
                <div class="flex flex-col">
                    <label for="manage-aircraft-type" class="text-sm font-medium text-slate-600 mb-1">Aircraft Type</label>
                    <input type="text" id="manage-aircraft-type" name="aircraft_type" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="B737-800">
                </div>
                <div class="flex flex-col">
                    <label for="manage-aircraft-operator" class="text-sm font-medium text-slate-600 mb-1">Operator / Airline</label>
                    <input type="text" id="manage-aircraft-operator" name="operator_airline" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Garuda Indonesia">
                </div>
                <div class="flex flex-col">
                    <label for="manage-aircraft-category" class="text-sm font-medium text-slate-600 mb-1">Category</label>
                    <input type="text" id="manage-aircraft-category" name="category" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Commercial / Charter / Cargo / GA">
                </div>
                <div class="flex flex-col sm:col-span-2">
                    <label for="manage-aircraft-notes" class="text-sm font-medium text-slate-600 mb-1">Notes</label>
                    <textarea id="manage-aircraft-notes" name="notes" rows="2" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Optional remarks"></textarea>
                </div>
                <div class="sm:col-span-2 flex justify-end gap-3">
                    <button type="button" id="aircraft-ref-clear" class="px-4 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200">Clear</button>
                    <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200">Save Details</button>
                </div>
            </form>

            <div id="aircraft-ref-status" class="text-sm hidden rounded-md px-3 py-2"></div>

            <div class="border-t border-slate-200 pt-4 space-y-3">
                <input type="text" id="aircraft-ref-search" placeholder="Search registration, type or operator..." class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <div class="overflow-x-auto border border-slate-200 rounded-md">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Registration</th>
                                <th class="px-3 py-2 text-left font-semibold">Type</th>
                                <th class="px-3 py-2 text-left font-semibold">Operator</th>
                                <th class="px-3 py-2 text-left font-semibold">Category</th>
                                <th class="px-3 py-2 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="aircraft-ref-rows" class="divide-y divide-slate-200">
                            <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="aircraft-ref-pagination" class="flex items-center justify-center gap-2"></div>
            </div>
        </div>
    </div>
</div>
