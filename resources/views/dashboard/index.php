<?php
$title = 'Dashboard - AMC MONITORING SYSTEM';
$styles = [
    'assets/css/tailwind.css',
    'assets/css/styles.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];
$head = '';;
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
                dashboardMovements: 'api/dashboard/movements',
                mlMetrics: 'api/ml/metrics',
                mlLogs: 'api/ml/logs',
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

                <!-- KPI Row -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <!-- Live Apron Status -->
                    <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                            Live Apron Status
                        </div>
                        <div class="p-4 lg:p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Stands</p>
                                    <p class="text-3xl font-bold text-amc-dark-blue" id="apron-total"><?= htmlspecialchars($apronStatus['total']) ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Available</p>
                                    <p class="text-3xl font-bold text-green-600" id="apron-available"><?= htmlspecialchars($apronStatus['available']) ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Occupied</p>
                                    <p class="text-3xl font-bold text-red-500" id="apron-occupied"><?= htmlspecialchars($apronStatus['occupied']) ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Live RON</p>
                                    <p class="text-3xl font-bold text-amber-500" id="apron-ron"><?= htmlspecialchars($apronStatus['ron']) ?></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button id="set-ron-btn" class="flex-1 nav-btn-gradient text-white px-4 py-2 rounded-md text-sm font-semibold transition-all duration-300 hover:-translate-y-1 <?php if ($user_role==='viewer') echo 'bg-gray-400 cursor-not-allowed'; ?>" <?php if ($user_role==='viewer') echo 'disabled'; ?>>Set RON</button>
                                <button id="refresh-btn" class="flex-1 bg-white text-amc-blue border border-amc-light px-4 py-2 rounded-md text-sm font-semibold transition-colors duration-300 hover:bg-blue-50" onclick="window.location.reload()">Refresh</button>
                            </div>
                        </div>
                    </div>

                    <!-- Movements Snapshot -->
                    <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                            Movements Snapshot (Today)
                        </div>
                        <div class="p-4 lg:p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-4 border border-gray-100 shadow-inner">
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Arrivals</p>
                                    <div class="grid grid-cols-3 gap-3 mt-3 text-center">
                                        <div>
                                            <p class="text-2xl font-bold text-blue-600" data-category="commercial" data-metric="arrivals"><?= htmlspecialchars($movementsToday['commercial']['arrivals']) ?></p>
                                            <p class="text-xxs text-gray-500 mt-1">Commercial</p>
                                        </div>
                                        <div>
                                            <p class="text-2xl font-bold text-teal-600" data-category="cargo" data-metric="arrivals"><?= htmlspecialchars($movementsToday['cargo']['arrivals']) ?></p>
                                            <p class="text-xxs text-gray-500 mt-1">Cargo</p>
                                        </div>
                                        <div>
                                            <p class="text-2xl font-bold text-purple-600" data-category="charter" data-metric="arrivals"><?= htmlspecialchars($movementsToday['charter']['arrivals']) ?></p>
                                            <p class="text-xxs text-gray-500 mt-1">Charter</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-gray-100 shadow-inner">
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Departures</p>
                                    <div class="grid grid-cols-3 gap-3 mt-3 text-center">
                                        <div>
                                            <p class="text-2xl font-bold text-blue-600" data-category="commercial" data-metric="departures"><?= htmlspecialchars($movementsToday['commercial']['departures']) ?></p>
                                            <p class="text-xxs text-gray-500 mt-1">Commercial</p>
                                        </div>
                                        <div>
                                            <p class="text-2xl font-bold text-teal-600" data-category="cargo" data-metric="departures"><?= htmlspecialchars($movementsToday['cargo']['departures']) ?></p>
                                            <p class="text-xxs text-gray-500 mt-1">Cargo</p>
                                        </div>
                                        <div>
                                            <p class="text-2xl font-bold text-purple-600" data-category="charter" data-metric="departures"><?= htmlspecialchars($movementsToday['charter']['departures']) ?></p>
                                            <p class="text-xxs text-gray-500 mt-1">Charter</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 text-center">
                                Totals refresh every 30 seconds from live apron data.
                            </div>
                        </div>
                    </div>

                    <!-- Model Performance Snapshot -->
                    <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                            Model Performance Snapshot
                        </div>
                        <div class="p-4 lg:p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div>
                                    <p class="text-xxs font-semibold text-gray-500 uppercase tracking-wide">Model Version</p>
                                    <p class="text-2xl font-bold text-amc-dark-blue" id="ml-metrics-version">--</p>
                                </div>
                                <div>
                                    <p class="text-xxs font-semibold text-gray-500 uppercase tracking-wide">Training Date</p>
                                    <p class="text-2xl font-bold text-amc-dark-blue" id="ml-metrics-training">--</p>
                                </div>
                                <div>
                                    <p class="text-xxs font-semibold text-gray-500 uppercase tracking-wide">Expected Top-3</p>
                                    <p class="text-2xl font-bold text-emerald-600" id="ml-metrics-expected">--</p>
                                </div>
                                <div>
                                    <p class="text-xxs font-semibold text-gray-500 uppercase tracking-wide">Observed Top-3</p>
                                    <p class="text-2xl font-bold text-amber-500" id="ml-metrics-observed">--</p>
                                </div>
                            </div>
                            <div class="bg-white rounded-lg border border-gray-100 p-3 text-sm space-y-1 shadow-inner">
                                <p class="font-semibold text-amc-dark-blue">Predictions Logged</p>
                                <p class="text-2xl font-bold text-gray-900" id="ml-metrics-sample">0</p>
                                <p class="text-xs text-gray-500" id="ml-metrics-status">Awaiting model telemetry...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operational Insights -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="xl:col-span-2 bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light">
                            Apron Movement by Hour
                        </div>
                        <div class="p-4 lg:p-5 space-y-4">
                            <div class="overflow-x-auto">
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
                                        <tr class="hover:bg-gray-50" data-time-range="<?= htmlspecialchars($row['time_range']) ?>">
                                            <td class="border border-gray-300 px-3 py-2 text-center font-mono"><?= htmlspecialchars($row['time_range']) ?></td>
                                            <td class="border border-gray-300 px-3 py-2 text-center font-semibold text-blue-600" data-metric="arrivals"><?= htmlspecialchars($row['Arrivals']) ?></td>
                                            <td class="border border-gray-300 px-3 py-2 text-center font-semibold text-green-600" data-metric="departures"><?= htmlspecialchars($row['Departures']) ?></td>
                                            <td class="border border-gray-300 px-3 py-2 text-center font-bold text-amc-dark-blue" data-metric="total"><?= htmlspecialchars($row['Arrivals'] + $row['Departures']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div id="peakHoursSummary" class="bg-gray-50 rounded-lg border-l-4 border-amc-dark-blue p-4">
                                <div class="font-bold text-amc-dark-blue mb-2">Peak Hours Summary</div>
                                <div id="peakHoursContent" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light flex items-center justify-between">
                            <span>Recent Prediction Outcomes</span>
                            <button type="button" class="text-xs text-amc-blue hover:underline" id="ml-log-scroll">
                                View Logbook
                            </button>
                        </div>
                        <div class="p-4 lg:p-5">
                            <div id="ml-metrics-recent" class="space-y-3 text-sm">
                                <p class="text-xs text-gray-500">Loading recent logs...</p>
                            </div>
                        </div>
                    </div>
                </div>

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
                <!-- ML Prediction Logbook -->
                <div id="ml-logbook-card" class="bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 text-amc-dark-blue px-4 py-3 lg:px-5 lg:py-4 font-bold text-sm lg:text-base border-b border-amc-light flex items-center justify-between">
                        <span>ML Prediction Logbook</span>
                        <button type="button" id="ml-log-toggle" class="text-xs font-semibold text-amc-blue border border-amc-light bg-white px-3 py-2 rounded-md hover:bg-blue-50 transition-colors duration-300">
                            Show Logbook
                        </button>
                    </div>
                    <div id="ml-log-content" class="p-4 lg:p-5 space-y-4 hidden">
                        <div class="flex flex-col lg:flex-row gap-4">
                            <div class="flex flex-col lg:flex-row gap-3 flex-1">
                                <div class="flex flex-col">
                                    <label for="ml-log-filter" class="text-xs font-semibold text-gray-600">Result</label>
                                    <select id="ml-log-filter" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-amc-blue focus:shadow-sm">
                                        <option value="all">All</option>
                                        <option value="hit">Top-3 Hits</option>
                                        <option value="miss">Missed</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="flex flex-col flex-1">
                                    <label for="ml-log-search" class="text-xs font-semibold text-gray-600">Search</label>
                                    <input id="ml-log-search" type="text" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-amc-blue focus:shadow-sm" placeholder="Search airline, stand, or flight">
                                </div>
                            </div>
                            <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-end">
                                <div class="flex flex-col">
                                    <label for="ml-log-limit" class="text-xs font-semibold text-gray-600">Rows</label>
                                    <select id="ml-log-limit" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-amc-blue focus:shadow-sm">
                                        <option value="25">25</option>
                                        <option value="50" selected>50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <button type="button" id="ml-log-refresh" class="bg-white border border-amc-light text-amc-blue px-4 py-2 rounded-md text-sm font-semibold transition-colors duration-300 hover:bg-blue-50">
                                    Refresh
                                </button>
                            </div>
                        </div>
                        <p id="ml-log-status" class="text-xs text-gray-500">Logbook hidden. Click "Show Logbook" to load predictions.</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs lg:text-sm border-collapse border border-gray-200">
                                <thead class="bg-gray-50 text-gray-600 uppercase">
                                    <tr>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Logged At</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Aircraft</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Operator</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Category</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Model</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Predicted Top-3</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Assigned Stand</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Result</th>
                                    </tr>
                                </thead>
                                <tbody id="ml-log-rows">
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">Prediction data will appear here.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
