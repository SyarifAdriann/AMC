<div id="movementVersionsModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-6 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-4xl mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold">Movement Data Versions</h2>
                <p class="text-sm text-slate-500">Save the current movement records, then restore any saved state later. Every restore saves the current state first, so nothing is lost.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close movement versions">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <div class="px-6 py-5 space-y-6">
            <form id="save-movement-version-form" class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                <div class="flex flex-col flex-1 w-full">
                    <label for="movement-version-label" class="text-sm font-medium text-slate-600 mb-1">Name this version</label>
                    <input type="text" id="movement-version-label" name="label" maxlength="120" required
                           placeholder="e.g. Dummy data before staff demo"
                           class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <button type="submit" class="px-5 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200 whitespace-nowrap">Save current state</button>
            </form>

            <div id="movement-version-status" class="text-sm hidden rounded-md px-3 py-2"></div>

            <div>
                <h3 class="text-sm font-semibold text-slate-600 mb-2">Saved versions</h3>
                <div id="movement-version-list" class="divide-y divide-slate-200 border border-slate-200 rounded-md max-h-96 overflow-y-auto">
                    <p class="text-sm text-slate-500 px-4 py-6 text-center">Loading...</p>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <button type="button" id="wipe-movements-btn"
                        class="w-full sm:w-auto px-5 py-2 rounded-md bg-red-600 text-white font-semibold hover:bg-red-700 transition-colors duration-200">
                    Wipe all movements &mdash; start clean
                </button>
                <p class="text-xs text-slate-500 mt-2">Saves the current state first, then clears every movement record so real operational data can be entered from scratch.</p>
            </div>
        </div>
    </div>
</div>
