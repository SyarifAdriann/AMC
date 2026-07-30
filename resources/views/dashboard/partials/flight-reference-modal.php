<div id="flightRefModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-10 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold">Manage Flight References</h2>
                <p class="text-sm text-slate-500">Search, edit, add or remove flight number to route mappings.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close flight reference modal">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <div class="px-6 py-5 space-y-5">
            <form id="flight-ref-form" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="flex flex-col">
                    <label for="manage-flight-number" class="text-sm font-medium text-slate-600 mb-1">Flight Number</label>
                    <input type="text" id="manage-flight-number" name="flight_no" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none uppercase" placeholder="GA412" required>
                </div>
                <div class="flex flex-col">
                    <label for="manage-default-route" class="text-sm font-medium text-slate-600 mb-1">Default Route</label>
                    <input type="text" id="manage-default-route" name="default_route" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="CGK - DPS">
                </div>
                <div class="sm:col-span-2 flex justify-end gap-3">
                    <button type="button" id="flight-ref-clear" class="px-4 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200">Clear</button>
                    <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200">Save Reference</button>
                </div>
            </form>

            <div id="flight-ref-status" class="text-sm hidden rounded-md px-3 py-2"></div>

            <div class="border-t border-slate-200 pt-4 space-y-3">
                <input type="text" id="flight-ref-search" placeholder="Search flight number or route..." class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <div class="overflow-x-auto border border-slate-200 rounded-md">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Flight Number</th>
                                <th class="px-3 py-2 text-left font-semibold">Default Route</th>
                                <th class="px-3 py-2 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="flight-ref-rows" class="divide-y divide-slate-200">
                            <tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="flight-ref-pagination" class="flex items-center justify-center gap-2"></div>
            </div>
        </div>
    </div>
</div>
