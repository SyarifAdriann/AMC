<div id="hgrModalBg" class="modal-backdrop fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50 hidden justify-center items-center overflow-y-auto">
    <div id="hgrModal" class="bg-white rounded-lg p-4 lg:p-6 w-full max-w-6xl mx-4 my-4 max-h-screen overflow-y-auto relative shadow-xl">
        <button type="button" class="absolute top-3 right-5 text-2xl font-bold cursor-pointer text-gray-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 flex items-center justify-center rounded-full transition-colors duration-300" data-target="hgrModalBg" aria-label="Close hangar modal">&times;</button>
        <h2 class="text-center mb-6 text-xl lg:text-2xl text-amc-dark-blue font-bold">Hangar Movement Records</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse border border-gray-300" id="hgr-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">NO</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">REGISTRATION</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">TYPE</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">ON BLOCK</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">OFF BLOCK</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">PARKING STAND</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">FROM</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">TO</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">ARR</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">DEP</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">OPERATOR/AIRLINES</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">REMARKS</th>
                        <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hgrRecords as $i => $r): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" readonly value="<?= $i + 1 ?>"></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['registration'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['aircraft_type'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['on_block_time'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['off_block_time'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['parking_stand'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['from_location'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['to_location'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['flight_no_arr'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['flight_no_dep'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['operator_airline'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= htmlspecialchars($r['remarks'] ?? '') ?>" readonly></td>
                        <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs" value="<?= $r['is_ron'] ? 'RON' : '' ?>" readonly></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-center mt-6">
            <button type="button" data-target="hgrModalBg" class="nav-btn-gradient text-white px-6 py-2 rounded-md font-semibold transition-all duration-300 hover:-translate-y-1">Close</button>
        </div>
    </div>
</div>
