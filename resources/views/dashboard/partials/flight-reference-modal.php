<div id="flightRefModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-10 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 class="text-xl font-semibold">Manage Flight References</h2>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close flight reference modal">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>
        <form class="px-6 py-5 space-y-4" method="post" action="dashboard.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="manage_flight_reference">

            <div class="flex flex-col">
                <label for="manage-flight-number" class="text-sm font-medium text-slate-600 mb-1">Flight Number</label>
                <input type="text" id="manage-flight-number" name="flight_no" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none uppercase" placeholder="GA412" required>
            </div>
            <div class="flex flex-col">
                <label for="manage-default-route" class="text-sm font-medium text-slate-600 mb-1">Default Route</label>
                <input type="text" id="manage-default-route" name="default_route" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="CGK - DPS" required>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200" data-modal-close>Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200">Save Reference</button>
            </div>
        </form>
    </div>
</div>
