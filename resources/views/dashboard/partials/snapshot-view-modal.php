<div id="viewSnapshotModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-6 overflow-y-auto">
    <div class="bg-white text-slate-800 rounded-xl shadow-2xl w-full max-w-5xl mx-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 id="snapshot-title" class="text-xl font-semibold">Daily Snapshot</h2>
            <button type="button" class="text-slate-400 hover:text-slate-700 transition-colors duration-200" data-modal-close aria-label="Close snapshot view">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>
        <div id="snapshot-content" class="px-6 py-5 space-y-6 max-h-[70vh] overflow-y-auto print:max-h-none print:overflow-visible">
            <p class="text-sm text-slate-500">Select a snapshot from the archive to view its details here.</p>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200">
            <button type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors duration-200" data-modal-close>Close</button>
        </div>
    </div>
</div>
