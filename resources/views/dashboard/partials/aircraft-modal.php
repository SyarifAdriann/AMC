<div id="aircraftModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-10 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-xl mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 class="text-xl font-semibold">Manage Aircraft Details</h2>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close aircraft modal">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>
        <form class="px-6 py-5 space-y-4" method="post" action="dashboard.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="manage_aircraft">

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
            <div class="flex flex-col">
                <label for="manage-aircraft-notes" class="text-sm font-medium text-slate-600 mb-1">Notes</label>
                <textarea id="manage-aircraft-notes" name="notes" rows="3" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Optional remarks"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200" data-modal-close>Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200">Save Details</button>
            </div>
        </form>
    </div>
</div>
