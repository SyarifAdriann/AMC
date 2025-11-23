<div id="standModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-center overflow-y-auto">
    <div id="standModal" class="bg-white rounded-lg p-3 w-full max-w-6xl mx-4 my-3 max-h-[96vh] overflow-y-auto relative shadow-2xl">
        <!-- Close Button -->
        <button type="button" class="absolute top-3 right-3 text-xl font-bold cursor-pointer text-gray-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110" data-target="standModalBg" aria-label="Close stand modal">&times;</button>

        <!-- Modal Title -->
        <h2 class="text-center mb-2 text-lg text-amc-dark-blue font-bold tracking-tight">Stand Details</h2>

        <!-- Form Container -->
        <div class="space-y-3" id="standFormTable">

            <!-- Section 1: Stand & Aircraft + Flights & Routes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <!-- Stand & Aircraft Section -->
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded p-2.5 space-y-2 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 pb-1 border-b border-slate-300">Stand &amp; Aircraft</h3>
                    <div class="space-y-1.5">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Parking Stand</span>
                            <input id="f-stand" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Registration</span>
                            <input id="f-reg" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Aircraft Type</span>
                            <input id="f-type" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Airline / Operator</span>
                            <input id="f-op" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                    </div>
                </div>

                <!-- Flights & Routes Section -->
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded p-2.5 space-y-2 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 pb-1 border-b border-slate-300">Flights &amp; Routes</h3>
                    <div class="space-y-1.5">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">From (Origin)</span>
                            <input id="f-from" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">To (Destination)</span>
                            <input id="f-to" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Arrival Flight</span>
                            <input id="f-arr" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Departure Flight</span>
                            <input id="f-dep" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-slate-200"></div>

            <!-- Section 2: Timing & Category + Remarks -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <!-- Timing & Category Section -->
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded p-2.5 space-y-2 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 pb-1 border-b border-slate-300">Timing &amp; Category</h3>
                    <div class="space-y-1.5">
                        <div class="grid grid-cols-2 gap-1.5">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-700 mb-0.5 block">On Block</span>
                                <input id="f-onblock" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Off Block</span>
                                <input id="f-offblock" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Movement Category <span class="text-purple-600">*</span></span>
                            <select id="f-category" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>">
                                <option value="">Select Category</option>
                                <option value="Komersial" selected>Komersial</option>
                                <option value="Cargo">Cargo</option>
                                <option value="Charter">Charter</option>
                            </select>
                            <p class="text-xs text-purple-600 mt-0.5 font-medium">Required for AI recommendations</p>
                        </label>
                        <label class="flex items-center gap-1.5 p-1.5 bg-white border border-slate-300 rounded hover:border-yellow-400 transition-colors cursor-pointer">
                            <input type="checkbox" id="f-ron" class="w-3.5 h-3.5 text-yellow-500 border border-slate-300 rounded focus:ring-1 focus:ring-yellow-400 <?php if ($user_role==='viewer') echo 'cursor-not-allowed'; ?>">
                            <span class="text-xs font-semibold text-slate-700">Mark as Remain Overnight (RON)</span>
                        </label>
                    </div>
                </div>

                <!-- Remarks Section -->
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded p-2.5 space-y-2 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 pb-1 border-b border-slate-300">Remarks</h3>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700 mb-0.5 block">Additional Notes</span>
                        <textarea id="f-remarks" rows="3" class="w-full text-xs border border-slate-300 rounded px-2.5 py-1 focus:border-amc-blue focus:ring-1 focus:ring-amc-blue focus:ring-opacity-20 focus:bg-white transition-all duration-200 resize-none <?php if ($user_role==='viewer') echo 'bg-slate-200 text-slate-600 cursor-not-allowed'; ?>"></textarea>
                    </label>
                    <button type="button" id="ml-recommend-btn" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-3 py-1.5 rounded font-bold text-xs transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 flex items-center justify-center gap-1.5 shadow-md <?php if ($user_role==='viewer') echo 'opacity-50 cursor-not-allowed'; ?>">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Get AI Recommendations
                    </button>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-slate-200"></div>

            <!-- AI Recommendations Section -->
            <div id="ml-recommendation-panel" class="bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-200 rounded p-2.5 space-y-2 shadow-sm">
                <div class="flex items-center gap-1.5 mb-1">
                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <h3 class="text-xs font-bold text-purple-900">AI Stand Recommendations</h3>
                </div>
                <div id="ml-recommendation-list" class="grid grid-cols-1 md:grid-cols-3 gap-2"></div>
                <input type="hidden" id="ml-recommendation-version">
            </div>
        </div>

        <input type="hidden" id="f-prediction-log-id">

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end gap-1.5 mt-3 pt-2 border-t border-slate-200">
            <button type="button" data-target="standModalBg" class="bg-slate-500 hover:bg-slate-600 text-white px-4 py-1.5 rounded font-bold text-xs transition-all duration-300 hover:shadow-lg order-2 sm:order-1">Cancel</button>
            <button type="button" id="save-stand" class="bg-gradient-to-r from-amc-blue to-amc-dark-blue hover:from-blue-600 hover:to-blue-800 text-white px-4 py-1.5 rounded font-bold text-xs transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 order-1 sm:order-2 <?php if ($user_role==='viewer') echo 'opacity-50 cursor-not-allowed'; ?>">Save Changes</button>
        </div>
    </div>
</div>
