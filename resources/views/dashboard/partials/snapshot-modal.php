<div id="snapshotModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-6 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-5xl mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-xl font-semibold">Daily Snapshot Archive</h2>
                <p class="text-sm text-slate-500">Review historical apron activity and generate new end-of-day snapshots.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close snapshot archive">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>
        <div class="px-6 py-5 space-y-6">
            <form id="create-snapshot-form" class="flex flex-col lg:flex-row items-start lg:items-end gap-4" method="post" action="api/snapshots">
                <div class="flex flex-col">
                    <label for="snapshot-date-input" class="text-sm font-medium text-slate-600 mb-1">Snapshot Date</label>
                    <input type="date" id="snapshot-date-input" name="snapshot_date" value="<?= htmlspecialchars($today ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-slate-600 mb-1">&nbsp;</label>
                    <button type="submit" class="px-5 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-200">Create Snapshot</button>
                </div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="create">
            </form>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-slate-600 uppercase tracking-wide text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Snapshot Date</th>
                            <th class="px-4 py-3 text-left">Created By</th>
                            <th class="px-4 py-3 text-left">Created At</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="snapshots-tbody" class="divide-y divide-slate-200">
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">Loading snapshots...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="snapshots-loading" class="text-sm text-slate-500 hidden">Fetching snapshots...</div>
            <div id="snapshots-pagination" class="flex justify-center pt-2"></div>
        </div>
    </div>
</div>
