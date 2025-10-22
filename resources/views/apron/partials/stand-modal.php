<div id="standModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-start pt-4 lg:items-center lg:pt-0 overflow-y-auto">
    <div id="standModal" class="bg-white rounded-lg p-4 lg:p-6 w-full max-w-4xl mx-4 my-4 lg:my-0 max-h-screen overflow-y-auto relative shadow-xl">
        <button type="button" class="absolute top-3 right-5 text-2xl font-bold cursor-pointer text-gray-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 flex items-center justify-center rounded-full transition-colors duration-300" data-target="standModalBg" aria-label="Close stand modal">&times;</button>

        <h2 class="text-center mb-6 text-xl lg:text-2xl text-amc-dark-blue font-bold">Stand Details</h2>

        <div class="overflow-x-auto">
            <table class="w-full" id="standFormTable">
                <tbody>
                    <tr>
                        <th class="text-right pr-3 py-2 w-32 font-semibold text-sm lg:text-base">Parking Stand</th>
                        <td class="py-2 pr-4">
                            <input id="f-stand" placeholder="Parking Stand" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                        <th class="text-right pr-3 py-2 w-20 font-semibold text-sm lg:text-base">To</th>
                        <td class="py-2">
                            <input id="f-to" placeholder="Destination" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Registration</th>
                        <td class="py-2 pr-4">
                            <input id="f-reg" placeholder="Aircraft Registration" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Arr</th>
                        <td class="py-2">
                            <input id="f-arr" placeholder="Arrival Flight Number" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Type</th>
                        <td class="py-2 pr-4">
                            <input id="f-type" placeholder="Aircraft Type" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Dep</th>
                        <td class="py-2">
                            <input id="f-dep" placeholder="Departure Flight Number" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">On Block</th>
                        <td class="py-2 pr-4">
                            <input id="f-onblock" placeholder="On Block Time" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Operator/Airline</th>
                        <td class="py-2">
                            <input id="f-op" placeholder="Airline/Operator" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Off Block</th>
                        <td class="py-2 pr-4">
                            <input id="f-offblock" placeholder="Off Block Time" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Remarks</th>
                        <td class="py-2">
                            <input id="f-remarks" placeholder="Additional Notes" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">From</th>
                        <td class="py-2 pr-4">
                            <input id="f-from" placeholder="Origin" class="w-full text-sm lg:text-base border-2 border-amc-light rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                        </td>
                        <th class="text-right pr-3 py-2 font-semibold text-sm lg:text-base">Status RON</th>
                        <td class="py-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" id="f-ron" class="w-auto scale-125 <?php if ($user_role==='viewer') echo 'cursor-not-allowed'; ?>">
                                <span class="font-semibold text-amc-dark-blue text-sm lg:text-base">Remain Overnight (RON)</span>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col lg:flex-row justify-end gap-3 mt-6">
            <button type="button" data-target="standModalBg" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 lg:px-6 lg:py-2 rounded-md font-semibold transition-colors duration-300 order-2 lg:order-1">Cancel</button>
            <button type="button" id="save-stand" class="nav-btn-gradient text-white px-4 py-2 lg:px-6 lg:py-2 rounded-md font-semibold transition-all duration-300 hover:-translate-y-1 order-1 lg:order-2 <?php if ($user_role==='viewer') echo 'bg-gray-400 cursor-not-allowed'; ?>">Save Changes</button>
        </div>
    </div>
</div>
