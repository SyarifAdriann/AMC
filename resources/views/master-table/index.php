<?php
$title = 'Master Table - AMC MONITORING SYSTEM';
$styles = [
    'assets/css/tailwind.css',
    'assets/css/styles.css?v=1.6',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];
$head = '';
$bodyClass = 'gradient-bg min-h-screen font-sans';
$scripts = [
    'assets/js/mobile-adaptations.js',
    'assets/js/master-table.js'
];
ob_start();
?>
    <div class="p-4 lg:p-5 min-h-screen">
        <div class="max-w-7xl mx-auto container-bg rounded-xl p-4 lg:p-8 shadow-2xl">
            
            <?php require __DIR__ . '/../partials/nav.php'; ?>



            <!-- FILTER CONTAINER -->
            <div class="bg-amc-bg rounded-xl p-4 lg:p-6 mb-6 border border-amc-light shadow-lg">
                <h3 class="text-lg font-bold text-amc-dark-blue mb-4">Filter Data</h3>
                <form action="master-table.php" method="GET" id="filter-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                        <div class="flex flex-col gap-2">
                            <label for="filter-date-from" class="text-sm font-semibold text-gray-700">From Date</label>
                            <input type="date" id="filter-date-from" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="filter-date-to" class="text-sm font-semibold text-gray-700">To Date</label>
                            <input type="date" id="filter-date-to" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="filter-category" class="text-sm font-semibold text-gray-700">Category</label>
                            <select id="filter-category" name="category" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                                <option value="">All</option>
                                <option value="Commercial" <?= ($filters['category'] ?? '') == 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                                <option value="Cargo" <?= ($filters['category'] ?? '') == 'Cargo' ? 'selected' : '' ?>>Cargo</option>
                                <option value="Charter" <?= ($filters['category'] ?? '') == 'Charter' ? 'selected' : '' ?>>Charter</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="filter-airline" class="text-sm font-semibold text-gray-700">Airline/Operator</label>
                            <input type="text" id="filter-airline" name="airline" placeholder="Enter airline" value="<?= htmlspecialchars($filters['airline'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="filter-flight" class="text-sm font-semibold text-gray-700">Flight Number</label>
                            <input type="text" id="filter-flight" name="flight_no" placeholder="Enter flight number" value="<?= htmlspecialchars($filters['flight_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-amc-light">
                        <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md font-semibold transition-colors duration-300" id="reset-filters">Reset Filters</button>
                        <button type="submit" class="bg-amc-blue hover:bg-amc-dark-blue text-white px-4 py-2 rounded-md font-semibold transition-colors duration-300">Apply Filters</button>
                    </div>
                </form>
            </div>

            <!-- MASTER MOVEMENTS TABLE -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                <div class="table-header-gradient text-white px-4 py-3 lg:px-6 lg:py-4 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
                    <span class="text-sm lg:text-base font-semibold">Aircraft Movements Live Data</span>
                    <div class="flex flex-wrap gap-2">
                        <?php if ($user_role !== 'viewer'): ?>
                        <button type="button" id="set-ron-btn" class="bg-white text-amc-blue px-3 py-1 lg:px-4 lg:py-2 text-xs lg:text-sm rounded-md font-semibold hover:bg-gray-100 transition-colors duration-300">Set RON</button>
                        <button type="button" data-action="save-table" class="bg-white text-amc-blue px-3 py-1 lg:px-4 lg:py-2 text-xs lg:text-sm rounded-md font-semibold hover:bg-gray-100 transition-colors duration-300">Save</button>
                        <?php endif; ?>
                        <button type="button" id="refresh-master-table" class="bg-white text-amc-blue px-3 py-1 lg:px-4 lg:py-2 text-xs lg:text-sm rounded-md font-semibold hover:bg-gray-100 transition-colors duration-300">Refresh</button>
                    </div>
                </div>
                
                <!-- Mobile Card View (Hidden on Desktop) -->
                <div class="mobile-cards lg:hidden">
                    <?php 
                    $mobile_row_number = $main_offset + 1;
                    foreach ($movements_data as $movement): 
                        $card_classes = ['border-b', 'border-gray-200', 'p-4'];
                        if ($movement['is_ron']) {
                            $card_classes[] = 'bg-yellow-50';
                            $card_classes[] = 'border-l-4';
                            $card_classes[] = 'border-l-yellow-400';
                        }
                        if (!empty($movement['flight_no_arr']) && in_array($movement['flight_no_arr'], $duplicate_flights)) {
                            $card_classes[] = 'bg-orange-50';
                        }
                    ?>
                    <div class="<?= implode(' ', $card_classes) ?>" data-id="<?= $movement['id'] ?>">
                        <div class="flex justify-between items-start mb-3">
                            <div class="text-lg font-bold text-amc-dark-blue"><?= htmlspecialchars($movement['registration']) ?></div>
                            <div class="text-xs text-gray-500">#<?= $mobile_row_number++ ?></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                            <div>
                                <span class="font-semibold text-gray-600">Type:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($movement['aircraft_type']) ?>" data-field="aircraft_type" data-original="<?= htmlspecialchars($movement['aircraft_type']) ?>" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($movement['aircraft_type']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Stand:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($movement['parking_stand']) ?>" data-field="parking_stand" data-original="<?= htmlspecialchars($movement['parking_stand']) ?>" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($movement['parking_stand']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">On Block:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($movement['on_block_time']) ?>" data-field="on_block_time" data-original="<?= htmlspecialchars($movement['on_block_time']) ?>" class="w-full text-xs font-mono border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($movement['on_block_time']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Off Block:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($movement['off_block_time']) ?>" data-field="off_block_time" data-original="<?= htmlspecialchars($movement['off_block_time']) ?>" class="w-full text-xs font-mono border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($movement['off_block_time']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                            <div>
                                <span class="font-semibold text-gray-600">From:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($movement['from_location']) ?>" data-field="from_location" data-original="<?= htmlspecialchars($movement['from_location']) ?>" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($movement['from_location']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">To:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($movement['to_location']) ?>" data-field="to_location" data-original="<?= htmlspecialchars($movement['to_location']) ?>" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($movement['to_location']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                            <div class="text-xs">
                                <span class="font-semibold text-gray-600">Status:</span>
                                <?php if ($user_role !== 'viewer'): ?>
                                <select data-field="is_ron" data-original="<?= $movement['is_ron'] ?>" class="ml-2 text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <option value="0" <?= !$movement['is_ron'] ? 'selected' : '' ?>>Normal</option>
                                    <option value="1" <?= $movement['is_ron'] ? 'selected' : '' ?>>RON</option>
                                </select>
                                <?php else: ?>
                                <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $movement['is_ron'] ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= $movement['is_ron'] ? 'RON' : 'Normal' ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($movement['movement_date']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Add empty mobile cards for new entries if needed -->
                    <?php if ($user_role !== 'viewer'): ?>
                    <div class="p-4 bg-blue-50 border-b border-gray-200">
                        <div class="text-center text-sm text-gray-600 mb-3">Add New Movement</div>
                        <div class="grid grid-cols-2 gap-3 text-sm" data-id="new" data-new-index="0">
                            <div>
                                <span class="font-semibold text-gray-600">Registration:</span>
                                <input data-field="registration" data-original="" placeholder="Aircraft Registration" class="w-full mt-1 text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Type:</span>
                                <input data-field="aircraft_type" data-original="" placeholder="Aircraft Type" class="w-full mt-1 text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Stand:</span>
                                <input data-field="parking_stand" data-original="" placeholder="Parking Stand" class="w-full mt-1 text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">On Block:</span>
                                <input data-field="on_block_time" data-original="" placeholder="On Block Time" class="w-full mt-1 text-xs font-mono border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Desktop Table View (Hidden on Mobile) -->
                <div class="desktop-table hidden lg:block overflow-x-auto">
                    <table class="w-full text-xs border-collapse" id="master-movements-table">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-8">NO</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-20">REGISTRATION</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-20">TYPE</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-28">ON BLOCK</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-28">OFF BLOCK</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-16">PARKING STAND</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-16">FROM</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-16">TO</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-16">ARR FLIGHT</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-16">DEP. TIME</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-24">OPERATOR/AIRLINE</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-24">REMARKS</th>
                                <th class="border border-gray-300 px-1 py-1 font-semibold text-gray-700 uppercase text-xs w-16">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_number = $main_offset + 1;
                            foreach ($movements_data as $movement): 
                                $tr_classes = ['hover:bg-gray-50'];
                                if ($movement['is_ron']) {
                                    $tr_classes[] = 'bg-yellow-50';
                                }
                                if (!empty($movement['flight_no_arr']) && in_array($movement['flight_no_arr'], $duplicate_flights)) {
                                    $tr_classes[] = 'bg-orange-100';
                                }
                                if (!empty($movement['flight_no_dep']) && in_array($movement['flight_no_dep'], $duplicate_flights)) {
                                    $tr_classes[] = 'bg-orange-100';
                                }
                            ?>
                            <tr data-id="<?= $movement['id'] ?>" class="<?= implode(' ', $tr_classes) ?>">
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs text-center" readonly value="<?= $row_number++ ?>"></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['registration']) ?>" data-field="registration" data-original="<?= htmlspecialchars($movement['registration']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['aircraft_type']) ?>" data-field="aircraft_type" data-original="<?= htmlspecialchars($movement['aircraft_type']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1 whitespace-nowrap"><input class="w-full border-none bg-transparent text-xs font-mono focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['on_block_time']) ?>" data-field="on_block_time" data-original="<?= htmlspecialchars($movement['on_block_time']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1 whitespace-nowrap"><input class="w-full border-none bg-transparent text-xs font-mono focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['off_block_time']) ?>" data-field="off_block_time" data-original="<?= htmlspecialchars($movement['off_block_time']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['parking_stand']) ?>" data-field="parking_stand" data-original="<?= htmlspecialchars($movement['parking_stand']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['from_location']) ?>" data-field="from_location" data-original="<?= htmlspecialchars($movement['from_location']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['to_location']) ?>" data-field="to_location" data-original="<?= htmlspecialchars($movement['to_location']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['flight_no_arr']) ?>" data-field="flight_no_arr" data-original="<?= htmlspecialchars($movement['flight_no_arr']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['flight_no_dep']) ?>" data-field="flight_no_dep" data-original="<?= htmlspecialchars($movement['flight_no_dep']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['operator_airline']) ?>" data-field="operator_airline" data-original="<?= htmlspecialchars($movement['operator_airline']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($movement['remarks']) ?>" data-field="remarks" data-original="<?= htmlspecialchars($movement['remarks']) ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                        <select class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" data-field="is_ron" data-original="<?= $movement['is_ron'] ?>">
                                            <option value="0" <?= !$movement['is_ron'] ? 'selected' : '' ?>>No</option>
                                            <option value="1" <?= $movement['is_ron'] ? 'selected' : '' ?>>Yes</option>
                                        </select>
                                    <?php else: ?>
                                        <?= $movement['is_ron'] ? 'Yes' : 'No' ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Empty rows for new entries -->
                            <?php 
                            $empty_rows_needed = max(0, 25 - count($movements_data));
                            $next_row_number = $row_number;
                            for ($i = 0; $i < $empty_rows_needed; $i++): ?>
                            <tr data-id="new" class="bg-blue-50 hover:bg-blue-100" data-new-index="<?= $i ?>">
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs text-center" readonly value="<?= $next_row_number++ ?>"></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="registration" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="aircraft_type" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1 whitespace-nowrap"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs font-mono focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="on_block_time" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1 whitespace-nowrap"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs font-mono focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="off_block_time" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="parking_stand" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="from_location" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="to_location" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="flight_no_arr" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="flight_no_dep" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="operator_airline" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-dashed border border-gray-300 bg-transparent text-xs focus:bg-white focus:border-solid focus:border-amc-blue focus:shadow-sm" value="" data-field="remarks" data-original="" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                        <select class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" data-field="is_ron" data-original="0">
                                            <option value="0" selected>No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    <?php else: ?>
                                        No
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex justify-center py-4 border-t border-gray-200">
                    <div class="flex gap-1">
                        <?php for ($i = 1; $i <= $main_total_pages; $i++): ?>
                            <a href="?main_page=<?= $i ?>&<?= http_build_query($filters) ?>" class="px-3 py-1 text-sm border border-gray-300 hover:bg-gray-100 transition-colors duration-300 <?= ($i == $main_page) ? 'bg-amc-blue text-white border-amc-blue' : 'bg-white text-gray-700' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Load More Button -->
                <div class="text-center py-4 border-t border-gray-200">
<?php $loadMoreDisabled = ($user_role === 'viewer'); ?>
                    <button type="button" id="load-more-rows" class="px-4 py-2 rounded-md font-semibold transition-colors duration-300 <?= $loadMoreDisabled ? 'bg-gray-400 text-white cursor-not-allowed' : 'bg-amc-blue hover:bg-amc-dark-blue text-white' ?>" <?= $loadMoreDisabled ? 'disabled' : '' ?>>
                        Load More Empty Rows
                    </button>
                </div>
            </div>

            <!-- RON DATA TABLE -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-3 lg:px-6 lg:py-4">
                    <span class="text-sm lg:text-base font-semibold">RON (Remain Overnight) Aircraft Data</span>
                </div>
                
                <!-- Mobile RON Cards -->
                <div class="mobile-cards lg:hidden">
                    <?php 
                    $ron_mobile_number = $ron_offset + 1;
                    foreach ($ron_data as $ron_movement): ?>
                    <div class="border-b border-gray-200 p-4 bg-red-50" data-id="<?= $ron_movement['id'] ?? 0 ?>">
                        <div class="flex justify-between items-start mb-3">
                            <div class="text-lg font-bold text-red-700"><?= htmlspecialchars($ron_movement['registration'] ?? '') ?></div>
                            <div class="text-xs text-gray-500">#<?= $ron_mobile_number++ ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="font-semibold text-gray-600">Type:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($ron_movement['aircraft_type'] ?? '') ?>" data-field="aircraft_type" data-original="<?= htmlspecialchars($ron_movement['aircraft_type'] ?? '') ?>" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($ron_movement['aircraft_type'] ?? '') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Operator:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($ron_movement['operator_airline'] ?? '') ?>" data-field="operator_airline" data-original="<?= htmlspecialchars($ron_movement['operator_airline'] ?? '') ?>" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($ron_movement['operator_airline'] ?? '') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">On Block:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($ron_movement['on_block_time'] ?? '') ?>" data-field="on_block_time" data-original="<?= htmlspecialchars($ron_movement['on_block_time'] ?? '') ?>" class="w-full text-xs font-mono border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($ron_movement['on_block_time'] ?? '') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">Off Block:</span>
                                <div class="mt-1">
                                    <?php if ($user_role !== 'viewer'): ?>
                                    <input value="<?= htmlspecialchars($ron_movement['off_block_time'] ?? '') ?>" data-field="off_block_time" data-original="<?= htmlspecialchars($ron_movement['off_block_time'] ?? '') ?>" class="w-full text-xs font-mono border border-gray-300 rounded px-2 py-1 focus:border-amc-blue">
                                    <?php else: ?>
                                    <span class="text-gray-800"><?= htmlspecialchars($ron_movement['off_block_time'] ?? '') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Desktop RON Table -->
                <div class="desktop-table hidden lg:block overflow-x-auto">
                    <table class="w-full text-xs border-collapse" id="ron-data-table">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">NO</th>
                                <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">REGISTRATION</th>
                                <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">TYPE</th>
                                <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">OPERATOR/AIRLINE</th>
                                <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">ON BLOCK</th>
                                <th class="border border-gray-300 px-2 py-2 font-semibold text-gray-700 uppercase">OFF BLOCK</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $ron_row_number = $ron_offset + 1;
                            foreach ($ron_data as $ron_movement): ?>
                            <tr data-id="<?= $ron_movement['id'] ?? 0 ?>" class="hover:bg-gray-50">
                                                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs text-center" readonly value="<?= $ron_row_number++ ?>"></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($ron_movement['registration'] ?? '') ?>" data-field="registration" data-original="<?= htmlspecialchars($ron_movement['registration'] ?? '') ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($ron_movement['aircraft_type'] ?? '') ?>" data-field="aircraft_type" data-original="<?= htmlspecialchars($ron_movement['aircraft_type'] ?? '') ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1"><input class="w-full border-none bg-transparent text-xs focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($ron_movement['operator_airline'] ?? '') ?>" data-field="operator_airline" data-original="<?= htmlspecialchars($ron_movement['operator_airline'] ?? '') ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1 whitespace-nowrap"><input class="w-full border-none bg-transparent text-xs font-mono focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($ron_movement['on_block_time'] ?? '') ?>" data-field="on_block_time" data-original="<?= htmlspecialchars($ron_movement['on_block_time'] ?? '') ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                                <td class="border border-gray-300 px-1 py-1 whitespace-nowrap"><input class="w-full border-none bg-transparent text-xs font-mono focus:bg-white focus:border focus:border-amc-blue focus:shadow-sm" value="<?= htmlspecialchars($ron_movement['off_block_time'] ?? '') ?>" data-field="off_block_time" data-original="<?= htmlspecialchars($ron_movement['off_block_time'] ?? '') ?>" <?= ($user_role === 'viewer') ? 'readonly' : '' ?>></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- RON Pagination -->
                <div class="flex justify-center py-4 border-t border-gray-200">
                    <div class="flex gap-1">
                        <?php for ($i = 1; $i <= $ron_total_pages; $i++): ?>
                            <a href="?ron_page=<?= $i ?>&<?= http_build_query($filters) ?>" class="px-3 py-1 text-sm border border-gray-300 hover:bg-gray-100 transition-colors duration-300 <?= ($i == $ron_page) ? 'bg-red-500 text-white border-red-500' : 'bg-white text-gray-700' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    
    <script>
        window.masterTableConfig = {
            userRole: '<?= htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?>',
            resetUrl: 'master-table.php',
            endpoints: {
                master: 'api/master-table',
                apron: 'api/apron'
            }
        };
    </script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';

