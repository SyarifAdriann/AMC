<?php
$title = 'Dashboard - AMC MONITORING SYSTEM';
$styles = [
    'assets/css/styles.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
    'assets/css/tailwind-custom.css'
];
$head = <<<'HTML'
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'amc-blue': '#3F72AF',
              'amc-dark-blue': '#112D4E',
              'amc-light': '#DBE2EF',
              'amc-bg': '#F9F7F7'
            },
            fontFamily: {
              'sans': ['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'Arial', 'sans-serif']
            },
            screens: {
              'xs': '475px',
            }
          }
        }
      }
    </script>
HTML;
$bodyClass = 'gradient-bg min-h-screen font-sans';
$bodyAttributes = 'id="dashboard-page"';
$scripts = [
    'assets/js/mobile-adaptations.js',
    'assets/js/dashboard.js'
];
ob_start();
?>
<?php
$hasRole = function ($roles) use ($user_role) {
    if (is_array($roles)) {
        return in_array($user_role, $roles, true);
    }

    return $user_role === $roles;
};
?>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" data-role="csrf">    <script>
        window.dashboardConfig = {
            userRole: <?= json_encode($user_role) ?>,
            peakHourData: <?= json_encode($peakHourData) ?>,
            endpoints: {
                refreshApron: 'api/apron/status',
                userAdmin: 'api/admin/users',
                snapshots: 'api/snapshots'
            }
        };
    </script>

    <div class="p-4 lg:p-5 min-h-screen">
        <div class="max-w-7xl mx-auto container-bg rounded-xl p-4 lg:p-8 shadow-2xl">
            
            <?php require __DIR__ . '/../partials/nav.php'; ?>



            <h1 class="text-center mb-6 lg:mb-8 text-2xl lg:text-4xl font-bold text-amc-dark-blue tracking-wide" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1); letter-spacing: 2px;">Aircraft Movement Control Dashboard</h1>

            <!-- Dashboard Grid -->
            <div class="space-y-6">
                
                <!-- KPI Cards Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Live Apron Status Card -->
                    <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light flex flex-col overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                            Live Apron Status
                        </div>
                        <div class="p-4 lg:p-5 flex-grow flex items-center">
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 w-full">
                                <div class="text-center">
                                    <div class="text-2xl lg:text-3xl font-bold text-amc-dark-blue mb-1 flex items-center justify-center h-12 lg:h-16" id="apron-total"><?= htmlspecialchars($apronStatus['total']) ?></div>
                                    <div class="text-xs lg:text-sm text-gray-600 font-semibold">Total Stands</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl lg:text-3xl font-bold text-green-600 mb-1 flex items-center justify-center h-12 lg:h-16" id="apron-available"><?= htmlspecialchars($apronStatus['available']) ?></div>
                                    <div class="text-xs lg:text-sm text-gray-600 font-semibold">Available</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl lg:text-3xl font-bold text-red-600 mb-1 flex items-center justify-center h-12 lg:h-16" id="apron-occupied"><?= htmlspecialchars($apronStatus['occupied']) ?></div>
                                    <div class="text-xs lg:text-sm text-gray-600 font-semibold">Occupied</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl lg:text-3xl font-bold text-yellow-500 mb-1 flex items-center justify-center h-12 lg:h-16" id="apron-ron"><?= htmlspecialchars($apronStatus['ron']) ?></div>
                                    <div class="text-xs lg:text-sm text-gray-600 font-semibold">Live RON</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Movements Today Card -->
                    <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light flex flex-col overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                            Movements Today
                        </div>
                        <div class="p-4 lg:p-5 flex-grow">
                            <div class="flex flex-col lg:flex-row justify-around gap-6">
                                <div class="flex-1 text-center">
                                    <h3 class="text-base lg:text-lg font-bold text-blue-600 mb-3">Arrivals</h3>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div class="text-center">
                                            <span class="block text-xl lg:text-2xl font-bold text-blue-600"><?= $movementsToday['commercial']['arrivals'] ?></span>
                                            <span class="text-xs lg:text-sm text-gray-600">Commercial</span>
                                        </div>
                                        <div class="text-center">
                                            <span class="block text-xl lg:text-2xl font-bold text-blue-600"><?= $movementsToday['cargo']['arrivals'] ?></span>
                                            <span class="text-xs lg:text-sm text-gray-600">Cargo</span>
                                        </div>
                                        <div class="text-center">
                                            <span class="block text-xl lg:text-2xl font-bold text-blue-600"><?= $movementsToday['charter']['arrivals'] ?></span>
                                            <span class="text-xs lg:text-sm text-gray-600">Charter</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="w-px bg-gray-300 hidden lg:block self-stretch"></div>
                                
                                <div class="flex-1 text-center">
                                    <h3 class="text-base lg:text-lg font-bold text-green-600 mb-3">Departures</h3>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div class="text-center">
                                            <span class="block text-xl lg:text-2xl font-bold text-green-600"><?= $movementsToday['commercial']['departures'] ?></span>
                                            <span class="text-xs lg:text-sm text-gray-600">Commercial</span>
                                        </div>
                                        <div class="text-center">
                                            <span class="block text-xl lg:text-2xl font-bold text-green-600"><?= $movementsToday['cargo']['departures'] ?></span>
                                            <span class="text-xs lg:text-sm text-gray-600">Cargo</span>
                                        </div>
                                        <div class="text-center">
                                            <span class="block text-xl lg:text-2xl font-bold text-green-600"><?= $movementsToday['charter']['departures'] ?></span>
                                            <span class="text-xs lg:text-sm text-gray-600">Charter</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Apron Movement by Hour Table -->
                <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                        Apron Movement by Hour
                    </div>
                    <div class="p-4 lg:p-5 overflow-x-auto">
                        <table class="w-full text-xs lg:text-sm border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border border-gray-300 px-3 py-2 font-semibold text-gray-700 uppercase">TIME</th>
                                    <th class="border border-gray-300 px-3 py-2 font-semibold text-gray-700 uppercase">Arrivals</th>
                                    <th class="border border-gray-300 px-3 py-2 font-semibold text-gray-700 uppercase">Departures</th>
                                    <th class="border border-gray-300 px-3 py-2 font-semibold text-gray-700 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movementsByHour as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-3 py-2 text-center font-mono"><?= htmlspecialchars($row['time_range']) ?></td>
                                    <td class="border border-gray-300 px-3 py-2 text-center font-semibold text-blue-600"><?= htmlspecialchars($row['Arrivals']) ?></td>
                                    <td class="border border-gray-300 px-3 py-2 text-center font-semibold text-green-600"><?= htmlspecialchars($row['Departures']) ?></td>
                                    <td class="border border-gray-300 px-3 py-2 text-center font-bold text-amc-dark-blue"><?= htmlspecialchars($row['Arrivals'] + $row['Departures']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Peak Hour Analysis -->
                <?php /*
                <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                        <span class="block">Peak Hour Analysis - Movement Distribution</span>
                        <span class="text-xs font-normal text-gray-600 mt-1">Hourly breakdown of arrivals and departures for operational planning</span>
                    </div>
                    <div class="p-4 lg:p-5">
                        <!-- Custom Bar Chart -->
                        <div id="customPeakChart" class="py-4">
                            <div class="relative h-64 lg:h-96 overflow-x-auto bg-white rounded-lg border border-gray-200 p-4">
                                <div class="flex items-end h-full min-w-full lg:min-w-0 gap-1 px-2">
                                    <?php 
                                    $maxMovements = max(array_map(function($h) { return $h['Arrivals'] + $h['Departures']; }, $peakHourData)) ?: 1;
                                    foreach ($peakHourData as $index => $hour): 
                                        $arrivalHeight = $maxMovements > 0 ? ($hour['Arrivals'] / $maxMovements) * 240 : 0;
                                        $departureHeight = $maxMovements > 0 ? ($hour['Departures'] / $maxMovements) * 240 : 0;
                                        $totalHeight = $maxMovements > 0 ? (($hour['Arrivals'] + $hour['Departures']) / $maxMovements) * 240 : 0;
                                        $shortLabel = substr($hour['time_range'], 0, 2) . '-' . substr($hour['time_range'], -5, 2);
                                    ?>
                                    <div class="flex-1 flex flex-col items-center relative min-w-8">
                                        <div class="flex gap-px items-end h-60 mb-1">
                                            <div class="w-3 bg-gradient-to-t from-blue-500 to-blue-300 rounded-t-sm" style="height: <?= $arrivalHeight ?>px;" title="<?= $hour['time_range'] ?> - Arrivals: <?= $hour['Arrivals'] ?>"></div>
                                            <div class="w-3 bg-gradient-to-t from-green-500 to-green-300 rounded-t-sm" style="height: <?= $departureHeight ?>px;" title="<?= $hour['time_range'] ?> - Departures: <?= $hour['Departures'] ?>"></div>
                                        </div>
                                        <?php if (($hour['Arrivals'] + $hour['Departures']) > 0): ?>
                                        <div class="absolute w-2 h-2 bg-teal-500 rounded-full border-2 border-white shadow-md" style="bottom: <?= 20 + $totalHeight ?>px;" title="<?= $hour['time_range'] ?> - Total: <?= $hour['Arrivals'] + $hour['Departures'] ?>"></div>
                                        <?php endif; ?>
                                        <div class="text-xs text-gray-600 text-center transform -rotate-45 origin-bottom-left mt-2 min-w-12"><?= $shortLabel ?></div>
                                        <?php if (($hour['Arrivals'] + $hour['Departures']) > 0): ?>
                                        <div class="text-xs font-bold text-amc-dark-blue mt-1"><?= $hour['Arrivals'] + $hour['Departures'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="flex justify-center gap-6 mt-4 p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-gradient-to-t from-blue-500 to-blue-300 rounded-sm"></div>
                                    <span class="text-xs lg:text-sm text-gray-700">Arrivals</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-gradient-to-t from-green-500 to-green-300 rounded-sm"></div>
                                    <span class="text-xs lg:text-sm text-gray-700">Departures</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-teal-500 rounded-full border-2 border-white"></div>
                                    <span class="text-xs lg:text-sm text-gray-700">Total Movements</span>
                                </div>
                            </div>
                        </div>
                        <div id="peakHoursSummary" class="mt-5 p-4 bg-gray-50 rounded-lg border-l-4 border-amc-dark-blue">
                            <div class="font-bold mb-3 text-amc-dark-blue">Peak Hours Summary</div>
                            <div id="peakHoursContent" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            </div>
                        </div>
                    </div>
                </div>
                */ ?>

                <!-- Automated Reporting Suite -->
                <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                        Automated Reporting Suite
                    </div>
                    <div class="p-4 lg:p-5">
                        <form method="POST" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label for="report-type" class="text-sm font-semibold text-amc-dark-blue">Report Type</label>
                                    <select id="report-type" name="report_type" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                                        <option value="daily_log_am">Daily Log (AM Shift)</option>
                                        <option value="daily_log_pm">Daily Log (PM Shift)</option>
                                        <option value="charter_log">Charter/VVIP Flight Log</option>
                                        <option value="ron_report">Daily RON Report</option>
                                        <option value="monthly_summary">Monthly Movement Summary</option>
                                        <option value="logbook_narrative">Logbook AMC Narrative</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="report-date-from" class="text-sm font-semibold text-amc-dark-blue">From</label>
                                    <input type="date" id="report-date-from" name="date_from" value="<?= htmlspecialchars($today) ?>" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="report-date-to" class="text-sm font-semibold text-amc-dark-blue">To</label>
                                    <input type="date" id="report-date-to" name="date_to" value="<?= htmlspecialchars($today) ?>" class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                                </div>
                                <div class="flex flex-col gap-2 lg:col-span-2">
                                    <div class="flex flex-wrap gap-2 mt-6 lg:mt-0">
                                        <button type="submit" name="action" value="generate" class="flex-1 lg:flex-initial bg-amc-blue hover:bg-amc-dark-blue text-white px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-300">Generate Report</button>
                                        <button type="submit" name="action" value="export_csv" class="flex-1 lg:flex-initial bg-green-600 hover:bg-green-700 text-white px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-300">Export to CSV</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php if (!empty($reportOutput)): ?>
                        <div class="mt-4 bg-white rounded-md border border-gray-200 p-4 overflow-x-auto text-gray-800 text-sm">
                            <?= $reportOutput ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Administrative Controls -->
                <?php if ($hasRole(['admin', 'operator'])) : ?>
                <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                        Administrative Controls
                    </div>
                    <div class="p-4 lg:p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-w-4xl mx-auto">
                            <?php if ($hasRole('admin')): ?>
                            <button class="bg-gray-600 hover:bg-gray-700 text-white text-center p-4 rounded-lg font-semibold transition-colors duration-300" data-modal-target="accountsModalBg">Manage Accounts</button>
                            <?php endif; ?>
                            <button class="bg-gray-600 hover:bg-gray-700 text-white text-center p-4 rounded-lg font-semibold transition-colors duration-300" data-modal-target="aircraftModalBg">Manage Aircraft Details</button>
                            <button class="bg-gray-600 hover:bg-gray-700 text-white text-center p-4 rounded-lg font-semibold transition-colors duration-300" data-modal-target="flightRefModalBg">Manage Flight References</button>
                            <button class="bg-gray-600 hover:bg-gray-700 text-white text-center p-4 rounded-lg font-semibold transition-colors duration-300" id="monthly-charter-btn" data-modal-target="charterModalBg">Monthly Charter Report</button>
                            <button class="bg-gray-600 hover:bg-gray-700 text-white text-center p-4 rounded-lg font-semibold transition-colors duration-300" data-modal-target="snapshotModalBg">Daily Snapshot Archive</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php require __DIR__ . '/partials/accounts-modal.php'; ?>
    <?php require __DIR__ . '/partials/user-form-modal.php'; ?>
    <?php require __DIR__ . '/partials/reset-password-modal.php'; ?>
    <?php require __DIR__ . '/partials/snapshot-modal.php'; ?>
    <?php require __DIR__ . '/partials/snapshot-view-modal.php'; ?>
    <?php require __DIR__ . '/partials/charter-modal.php'; ?>
    <?php require __DIR__ . '/partials/aircraft-modal.php'; ?>
    <?php require __DIR__ . '/partials/flight-reference-modal.php'; ?>

    <!-- Keep all existing JavaScript -->
    
    
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
