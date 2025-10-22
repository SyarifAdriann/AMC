<?php
require_once 'dbconnection.php';
session_start();
require_once 'auth_check.php';

// CSRF token generation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Allow periodic apron status refresh via AJAX for all roles
if (isset($_GET['action']) && $_GET['action'] == 'refresh_apron') {
    header('Content-Type: application/json');
    echo json_encode(getApronStatus($pdo));
    exit;
}

// Prevent viewers from accessing dashboard page
if ($user_role === 'viewer') {
    header('Location: index.php');
    exit;
}

function getMovementsToday($pdo, $date) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(ad.category, 'charter') AS category,
                SUM(CASE WHEN am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON' 
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0) THEN 1 ELSE 0 END) AS arrivals,
                SUM(CASE WHEN am.off_block_time IS NOT NULL AND am.off_block_time != '' 
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0) THEN 1 ELSE 0 END) AS departures
            FROM aircraft_movements am
            LEFT JOIN aircraft_details ad ON am.registration = ad.registration
            WHERE am.movement_date = ?
            GROUP BY category
        ");
        $stmt->execute([$date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $categories = ['commercial' => ['arrivals' => 0, 'departures' => 0],
                       'cargo' => ['arrivals' => 0, 'departures' => 0],
                       'charter' => ['arrivals' => 0, 'departures' => 0]];
        foreach ($results as $row) {
            $cat = strtolower($row['category']);
            if (isset($categories[$cat])) {
                $categories[$cat]['arrivals'] = (int)$row['arrivals'];
                $categories[$cat]['departures'] = (int)$row['departures'];
            }
        }
        return $categories;
    } catch (PDOException $e) {
        error_log("Error in getMovementsToday: " . $e->getMessage());
        return ['commercial' => ['arrivals' => 0, 'departures' => 0], 'cargo' => ['arrivals' => 0, 'departures' => 0], 'charter' => ['arrivals' => 0, 'departures' => 0]];
    }
}

function getMovementsByHour($pdo, $date) {
    try {
        $sql = "
            SELECT
                CONCAT(LPAD(FLOOR(HOUR(on_block_time)/2)*2,2,'0'), ':00-', 
                       LPAD(FLOOR(HOUR(on_block_time)/2)*2+1,2,'0'), ':59') AS time_range,
                SUM(CASE WHEN on_block_time IS NOT NULL AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0) THEN 1 ELSE 0 END) AS Arrivals,
                SUM(CASE WHEN off_block_time IS NOT NULL AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0) THEN 1 ELSE 0 END) AS Departures
            FROM aircraft_movements
            WHERE movement_date = :date
                AND (on_block_time IS NOT NULL OR off_block_time IS NOT NULL)
            GROUP BY FLOOR(HOUR(on_block_time)/2)
            ORDER BY time_range
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hourlyStats = [];
        for ($i = 0; $i < 12; $i++) {
            $start = $i * 2;
            $end = $start + 1;
            $time_range = sprintf('%02d:00-%02d:59', $start, $end);
            $hourlyStats[$time_range] = ['time_range' => $time_range, 'Arrivals' => 0, 'Departures' => 0];
        }
        foreach ($results as $row) {
            $hourlyStats[$row['time_range']] = $row;
        }
        return array_values($hourlyStats);
    } catch (PDOException $e) {
        error_log("Error in getMovementsByHour: " . $e->getMessage());
        return [];
    }
}

$today = date('Y-m-d');
$apronStatus = getApronStatus($pdo);
$movementsToday = getMovementsToday($pdo, $today);
$movementsByHour = getMovementsByHour($pdo, $today);
$peakHourData = $movementsByHour;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (in_array($_POST['action'], ['generate', 'export_csv'])) {
            $reportType = $_POST['report_type'] ?? '';
            $dateFrom = $_POST['date_from'] ?? $today;
            $dateTo = $_POST['date_to'] ?? $today;

            $query = "SELECT * FROM aircraft_movements WHERE movement_date BETWEEN ? AND ?";
            $params = [$dateFrom, $dateTo];
            if ($reportType === 'charter_log') {
                $query .= " AND operator_airline LIKE ?";
                $params[] = '%Charter%';
            } elseif ($reportType === 'ron_report') {
                $query .= " AND is_ron = 1";
            } elseif ($reportType === 'daily_log_am') {
                $query .= " AND HOUR(STR_TO_DATE(on_block_time, '%H%i')) BETWEEN 0 AND 11";
            } elseif ($reportType === 'daily_log_pm') {
                $query .= " AND HOUR(STR_TO_DATE(on_block_time, '%H%i')) BETWEEN 12 AND 23";
            } elseif ($reportType === 'monthly_summary') {
                $query = "SELECT COUNT(*) as total, DATE(movement_date) as date FROM aircraft_movements WHERE movement_date BETWEEN ? AND ? GROUP BY DATE(movement_date)";
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($_POST['action'] === 'export_csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="report_' . $reportType . '_' . date('Ymd') . '.csv"');
                $output = fopen('php://output', 'w');
                if ($reportType === 'monthly_summary') {
                    fputcsv($output, ['Date', 'Total Movements']);
                    foreach ($reportData as $row) {
                        fputcsv($output, [$row['date'], $row['total']]);
                    }
                } else {
                    fputcsv($output, ['ID', 'Registration', 'Type', 'On Block', 'Off Block', 'Stand', 'From', 'To', 'Arr Flight', 'Dep Flight', 'Operator', 'Remarks', 'RON', 'Date']);
                    foreach ($reportData as $row) {
                        fputcsv($output, [$row['id'], $row['registration'], $row['aircraft_type'], $row['on_block_time'], $row['off_block_time'], $row['parking_stand'], $row['from_location'], $row['to_location'], $row['flight_no_arr'], $row['flight_no_dep'], $row['operator_airline'], $row['remarks'], $row['is_ron'] ? 'Yes' : 'No', $row['movement_date']]);
                    }
                }
                fclose($output);
                exit;
            } else {
                $reportOutput = "<div class='report-output'><h3>Generated Report: " . htmlspecialchars($reportType) . "</h3>";
                if ($reportType === 'monthly_summary') {
                    $reportOutput .= "<table border='1'><tr><th>Date</th><th>Total Movements</th></tr>";
                    foreach ($reportData as $row) {
                        $reportOutput .= "<tr><td>" . htmlspecialchars($row['date']) . "</td><td>" . htmlspecialchars($row['total']) . "</td></tr>";
                    }
                } else {
                    $reportOutput .= "<table border='1'><tr><th>ID</th><th>Registration</th><th>Type</th><th>On Block</th><th>Off Block</th><th>Stand</th><th>From</th><th>To</th><th>Arr Flight</th><th>Dep Flight</th><th>Operator</th><th>Remarks</th><th>RON</th><th>Date</th></tr>";
                    foreach ($reportData as $row) {
                        $reportOutput .= "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['registration']) . "</td><td>" . htmlspecialchars($row['aircraft_type']) . "</td><td>" . htmlspecialchars($row['on_block_time']) . "</td><td>" . htmlspecialchars($row['off_block_time']) . "</td><td>" . htmlspecialchars($row['parking_stand']) . "</td><td>" . htmlspecialchars($row['from_location']) . "</td><td>" . htmlspecialchars($row['to_location']) . "</td><td>" . htmlspecialchars($row['flight_no_arr']) . "</td><td>" . htmlspecialchars($row['flight_no_dep']) . "</td><td>" . htmlspecialchars($row['operator_airline']) . "</td><td>" . htmlspecialchars($row['remarks']) . "</td><td>" . ($row['is_ron'] ? 'Yes' : 'No') . "</td><td>" . htmlspecialchars($row['movement_date']) . "</td></tr>";
                    }
                }
                $reportOutput .= "</table></div>";
            }
        } elseif ($_POST['action'] === 'manage_aircraft') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $reportOutput = "<p style='color: red;'>Invalid security token. Please refresh and try again.</p>";
            } else {
                requireRole(['admin', 'operator']);
                $stmt = $pdo->prepare("INSERT INTO aircraft_details (registration, aircraft_type, operator_airline, category, notes) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE aircraft_type = VALUES(aircraft_type), operator_airline = VALUES(operator_airline), category = VALUES(category), notes = VALUES(notes)");
                $stmt->execute([$_POST['registration'], $_POST['aircraft_type'], $_POST['operator_airline'], $_POST['category'], $_POST['notes']]);
                $reportOutput = "<p style='color: green;'>Aircraft details saved successfully.</p>";
            }
        } elseif ($_POST['action'] === 'manage_flight_reference') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $reportOutput = "<p style='color: red;'>Invalid security token. Please refresh and try again.</p>";
            } else {
                requireRole(['admin', 'operator']);
                $stmt = $pdo->prepare("INSERT INTO flight_references (flight_no, default_route) VALUES (?, ?) ON DUPLICATE KEY UPDATE default_route = VALUES(default_route)");
                $stmt->execute([$_POST['flight_no'], $_POST['default_route']]);
                $reportOutput = "<p style='color: green;'>Flight reference saved successfully.</p>";
            }
        } elseif ($_POST['action'] === 'monthly_charter_report') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $reportOutput = "<p style='color: red;'>Invalid security token. Please refresh and try again.</p>";
            } else {
                requireRole(['admin', 'operator']);
                $month = $_POST['month'] ?? date('m');
                $year = $_POST['year'] ?? date('Y');
                $startDate = "$year-$month-01";
                $endDate   = date('Y-m-t', strtotime($startDate));

                $sql = "
                  SELECT m.*, d.aircraft_type, d.operator_airline 
                  FROM aircraft_movements AS m
                  JOIN aircraft_details AS d ON m.registration = d.registration
                  WHERE d.category = 'charter'
                    AND movement_date BETWEEN :start AND :end
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':start' => $startDate, ':end' => $endDate]);
                $charterMovements = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $reportOutput = "<h3>Monthly Charter Report: " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . "</h3><table class='master-table'><tr><th>Date</th><th>Registration</th><th>Type</th><th>Origin</th><th>Destination</th><th>Operator</th><th>On Block</th><th>Off Block</th></tr>";
                foreach ($charterMovements as $row) {
                    $reportOutput .= "<tr><td>" . htmlspecialchars($row['movement_date']) . "</td><td>" . htmlspecialchars($row['registration']) . "</td><td>" . htmlspecialchars($row['aircraft_type']) . "</td><td>" . htmlspecialchars($row['from_location']) . "</td><td>" . htmlspecialchars($row['to_location']) . "</td><td>" . htmlspecialchars($row['operator_airline']) . "</td><td>" . htmlspecialchars($row['on_block_time']) . "</td><td>" . htmlspecialchars($row['off_block_time']) . "</td></tr>";
                }
                $reportOutput .= "</table>";
            }
        }
    } catch (PDOException $e) {
        $reportOutput = "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AMC MONITORING SYSTEM</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body id="dashboard-page">
<div class="container">

    <!-- HEADER -->
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <div class="header">
        <div class="logo" onclick="window.location.href='index.php'">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
            <span>AMC MONITORING</span>
        </div>
        <div class="nav-buttons">
            <button class="nav-btn <?= ($current_page == 'index.php') ? 'active' : '' ?>" onclick="window.location.href='index.php'">Apron Map</button>
            <button class="nav-btn <?= ($current_page == 'master-table.php') ? 'active' : '' ?>" onclick="window.location.href='master-table.php'">Master Table</button>
            <button class="nav-btn <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" onclick="window.location.href='dashboard.php'">Dashboard</button>
            <button class="nav-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <h1>Aircraft Movement Control Dashboard</h1>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- Live Summary KPIs -->
        <div class="dashboard-card kpi-card">
            <div class="card-header">Live Apron Status</div>
            <div class="card-content kpi-grid">
                <div class="kpi-item">
                    <div class="kpi-value" id="apron-total"><?= htmlspecialchars($apronStatus['total']) ?></div>
                    <div class="kpi-label">Total Stands</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-value text-success" id="apron-available"><?= htmlspecialchars($apronStatus['available']) ?></div>
                    <div class="kpi-label">Available</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-value text-danger" id="apron-occupied"><?= htmlspecialchars($apronStatus['occupied']) ?></div>
                    <div class="kpi-label">Occupied</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-value text-warning" id="apron-ron"><?= htmlspecialchars($apronStatus['ron']) ?></div>
                    <div class="kpi-label">Live RON</div>
                </div>
            </div>
        </div>
        
        <!-- Movement Type Counters -->
        <div class="dashboard-card kpi-card">
            <div class="card-header">Movements Today</div>
            <div class="card-content movements-content">
                <div class="movements-column arrivals">
                    <h3>Arrivals</h3>
                    <div class="movements-values">
                        <div class="movement-item commercial">
                            <span class="value"><?= $movementsToday['commercial']['arrivals'] ?></span>
                            <span class="label">Commercial</span>
                        </div>
                        <div class="movement-item cargo">
                            <span class="value"><?= $movementsToday['cargo']['arrivals'] ?></span>
                            <span class="label">Cargo</span>
                        </div>
                        <div class="movement-item charter">
                            <span class="value"><?= $movementsToday['charter']['arrivals'] ?></span>
                            <span class="label">Charter</span>
                        </div>
                    </div>
                </div>
                <div class="separator"></div>
                <div class="movements-column departures">
                    <h3>Departures</h3>
                    <div class="movements-values">
                        <div class="movement-item commercial">
                            <span class="value"><?= $movementsToday['commercial']['departures'] ?></span>
                            <span class="label">Commercial</span>
                        </div>
                        <div class="movement-item cargo">
                            <span class="value"><?= $movementsToday['cargo']['departures'] ?></span>
                            <span class="label">Cargo</span>
                        </div>
                        <div class="movement-item charter">
                            <span class="value"><?= $movementsToday['charter']['departures'] ?></span>
                            <span class="label">Charter</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apron Movement by Hour -->
        <div class="dashboard-card span-2">
            <div class="card-header">Apron Movement by Hour</div>
            <div class="card-content">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th>TIME</th>
                            <th>Arrivals</th>
                            <th>Departures</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movementsByHour as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['time_range']) ?></td>
                                <td><?= htmlspecialchars($row['Arrivals']) ?></td>
                                <td><?= htmlspecialchars($row['Departures']) ?></td>
                                <td><?= htmlspecialchars($row['Arrivals'] + $row['Departures']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Peak Hour Analysis Full Width -->
        <div class="dashboard-section">
            <div class="dashboard-card">
                <div class="card-header">
                    <span>Peak Hour Analysis - Movement Distribution</span>
                    <span style="font-size: 0.8em; font-weight: normal; color: #666;">
                        Hourly breakdown of arrivals and departures for operational planning
                    </span>
                </div>
                <div class="card-content">
                    <!-- Custom Bar Chart -->
                    <div id="customPeakChart" style="padding: 20px 0;">
                        <div class="chart-container" style="position: relative; height: 400px; overflow-x: auto;">
                            <div class="chart-content" style="display: flex; align-items: end; height: 100%; min-width: 800px; gap: 2px; padding: 0 10px;">
                                <?php 
                                $maxMovements = max(array_map(function($h) { return $h['Arrivals'] + $h['Departures']; }, $peakHourData)) ?: 1;
                                foreach ($peakHourData as $index => $hour): 
                                    $arrivalHeight = $maxMovements > 0 ? ($hour['Arrivals'] / $maxMovements) * 300 : 0;
                                    $departureHeight = $maxMovements > 0 ? ($hour['Departures'] / $maxMovements) * 300 : 0;
                                    $totalHeight = $maxMovements > 0 ? (($hour['Arrivals'] + $hour['Departures']) / $maxMovements) * 300 : 0;
                                    $shortLabel = substr($hour['time_range'], 0, 2) . '-' . substr($hour['time_range'], -5, 2);
                                ?>
                                <div class="hour-bar-group" style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative;">
                                    <div style="display: flex; gap: 1px; align-items: end; height: 300px; margin-bottom: 5px;">
                                        <div class="arrival-bar" 
                                             style="width: 12px; background: linear-gradient(to top, #36A2EB, #5BC0DE); height: <?= $arrivalHeight ?>px; border-radius: 2px 2px 0 0;"
                                             title="<?= $hour['time_range'] ?> - Arrivals: <?= $hour['Arrivals'] ?>">
                                        </div>
                                        <div class="departure-bar" 
                                             style="width: 12px; background: linear-gradient(to top, #FF6384, #FF8A80); height: <?= $departureHeight ?>px; border-radius: 2px 2px 0 0;"
                                             title="<?= $hour['time_range'] ?> - Departures: <?= $hour['Departures'] ?>">
                                        </div>
                                    </div>
                                    <?php if (($hour['Arrivals'] + $hour['Departures']) > 0): ?>
                                    <div class="total-point" 
                                         style="position: absolute; bottom: <?= 5 + $totalHeight ?>px; left: 50%; transform: translateX(-50%); width: 6px; height: 6px; background: #4BC0C0; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"
                                         title="<?= $hour['time_range'] ?> - Total: <?= $hour['Arrivals'] + $hour['Departures'] ?>">
                                    </div>
                                    <?php endif; ?>
                                    <div style="font-size: 10px; color: #666; text-align: center; writing-mode: vertical-rl; text-orientation: mixed; height: 40px; display: flex; align-items: center;">
                                        <?= $shortLabel ?>
                                    </div>
                                    <?php if (($hour['Arrivals'] + $hour['Departures']) > 0): ?>
                                    <div style="font-size: 9px; color: #333; font-weight: bold; margin-top: 2px;">
                                        <?= $hour['Arrivals'] + $hour['Departures'] ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: center; gap: 20px; margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 12px; height: 12px; background: linear-gradient(to top, #36A2EB, #5BC0DE); border-radius: 2px;"></div>
                                <span style="font-size: 12px; color: #333;">Arrivals</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 12px; height: 12px; background: linear-gradient(to top, #FF6384, #FF8A80); border-radius: 2px;"></div>
                                <span style="font-size: 12px; color: #333;">Departures</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="width: 6px; height: 6px; background: #4BC0C0; border-radius: 50%; border: 2px solid white;"></div>
                                <span style="font-size: 12px; color: #333;">Total Movements</span>
                            </div>
                        </div>
                    </div>
                    <div id="peakHoursSummary" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #112D4E;">
                        <div style="font-weight: bold; margin-bottom: 10px; color: #112D4E;">Peak Hours Summary</div>
                        <div id="peakHoursContent" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporting Suite Full Width -->
        <div class="dashboard-section">
            <div class="dashboard-card">
                <div class="card-header">Automated Reporting Suite</div>
                <div class="card-content reporting-card">
                    <form method="POST">
                        <div class="report-controls">
                            <div class="report-group">
                                <label for="report-type">Report Type</label>
                                <select id="report-type" name="report_type">
                                    <option value="daily_log_am">Daily Log (AM Shift)</option>
                                    <option value="daily_log_pm">Daily Log (PM Shift)</option>
                                    <option value="charter_log">Charter/VVIP Flight Log</option>
                                    <option value="ron_report">Daily RON Report</option>
                                    <option value="monthly_summary">Monthly Movement Summary</option>
                                    <option value="logbook_narrative">Logbook AMC Narrative</option>
                                </select>
                            </div>
                            <div class="report-group">
                                <label for="report-date-from">From</label>
                                <input type="date" id="report-date-from" name="date_from" value="<?= htmlspecialchars($today) ?>">
                            </div>
                            <div class="report-group">
                                <label for="report-date-to">To</label>
                                <input type="date" id="report-date-to" name="date_to" value="<?= htmlspecialchars($today) ?>">
                            </div>
                        </div>
                        <div class="report-actions">
                            <button type="submit" name="action" value="generate" class="report-btn generate">Generate Report</button>
                            <button type="submit" name="action" value="export_csv" class="report-btn export-csv">Export to CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Report Output Section -->
        <?php if (isset($reportOutput) && !empty($reportOutput)): ?>
        <div class="report-output-section" id="report-output-section">
            <?= $reportOutput ?>
        </div>
        <?php endif; ?>

        <!-- Administrative Controls Full Width -->
        <?php if (hasRole(['admin', 'operator'])) : ?>
        <div class="dashboard-section">
            <div class="dashboard-card">
                <div class="card-header">Administrative Controls</div>
                <div class="card-content admin-controls">
                    <?php if (hasRole('admin')): ?>
                    <button class="admin-btn" data-modal-target="accountsModalBg">Manage Accounts</button>
                    <?php endif; ?>
                    <button class="admin-btn" data-modal-target="aircraftModalBg">Manage Aircraft Details</button>
                    <button class="admin-btn" data-modal-target="flightRefModalBg">Manage Flight References</button>
                    <button class="admin-btn" id="monthly-charter-btn" data-modal-target="charterModalBg">Monthly Charter Report</button>
            <!-- NEW: Daily Snapshot Archive Button -->
            <button class="admin-btn" data-modal-target="snapshotModalBg">Daily Snapshot Archive</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div> <!-- end .dashboard-grid -->
</div> <!-- end .container -->

<?php if (hasRole(['admin', 'operator'])) : ?>
<!-- Aircraft Details Modal -->
<div class="modal-backdrop" id="aircraftModalBg" style="display: none;">
    <div class="modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2>Aircraft Details Management</h2>
        <form method="POST" id="aircraft-form">
            <input type="hidden" name="action" value="manage_aircraft">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <table class="form-table">
                <tr><th><label for="aircraft-registration">Registration</label></th><td><input id="aircraft-registration" name="registration" required></td></tr>
                <tr><th><label for="aircraft-type">Aircraft Type</label></th><td><input id="aircraft-type" name="aircraft_type"></td></tr>
                <tr><th><label for="operator-airline">Operator/Airline</label></th><td><input id="operator-airline" name="operator_airline"></td></tr>
                <tr><th><label for="category">Category</label></th><td><select id="category" name="category"><option value="Commercial">Commercial</option><option value="Cargo">Cargo</option><option value="Charter">Charter</option></select></td></tr>
                <tr><th><label for="notes">Notes</label></th><td><textarea id="notes" name="notes"></textarea></td></tr>
            </table>
            <button type="submit" class="modal-btn">Save</button>
        </form>
    </div>
</div>

<!-- Flight Reference Modal -->
<div class="modal-backdrop" id="flightRefModalBg" style="display: none;">
    <div class="modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2>Flight Reference Management</h2>
        <form method="POST" id="flight-ref-form">
            <input type="hidden" name="action" value="manage_flight_reference">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <table class="form-table">
                <tr><th><label for="flight-no">Flight Number</label></th><td><input id="flight-no" name="flight_no" required></td></tr>
                <tr><th><label for="default-route">Default Route</label></th><td><input id="default-route" name="default_route"></td></tr>
            </table>
            <button type="submit" class="modal-btn">Save</button>
        </form>
    </div>
</div>

<!-- Charter Report Modal -->
<div class="modal-backdrop" id="charterModalBg" style="display: none;">
    <div class="modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2>Monthly Charter Report</h2>
        <form method="POST" id="charter-report-form">
            <input type="hidden" name="action" value="monthly_charter_report">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="form-group">
                <label for="charter-month">Month</label>
                <input type="month" id="charter-month" name="month-year" value="<?= date('Y-m') ?>">
                <input type="hidden" name="month" id="charter-month-hidden">
                <input type="hidden" name="year" id="charter-year-hidden">
            </div>
            <button type="submit" class="modal-btn">Generate</button>
        </form>
    </div>
</div>

<!-- Manage Accounts Modal -->
<?php if (hasRole('admin')): ?>
<div class="modal-backdrop" id="accountsModalBg" style="display: none;">
    <div class="modal accounts-modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2>Manage User Accounts</h2>
        
        <!-- Search and filters -->
        <div class="accounts-controls">
            <div class="search-group">
                <input type="text" id="user-search" placeholder="Search users..." />
                <select id="role-filter">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="operator">Operator</option>
                    <option value="viewer">Viewer</option>
                </select>
                <select id="status-filter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
                <button id="refresh-users" class="modal-btn">Refresh</button>
            </div>
            <button id="new-user-btn" class="modal-btn primary">+ New User</button>
        </div>

        <!-- Users table -->
        <div class="accounts-table-container">
            <table id="users-table" class="accounts-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <!-- Users will be loaded here -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="accounts-pagination"></div>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div class="modal-backdrop" id="userFormModalBg" style="display: none;">
    <div class="modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2 id="user-form-title">Create User</h2>
        <form id="user-form">
            <input type="hidden" id="user-id" name="id">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <table class="form-table">
                <tr><th><label for="user-full-name">Full Name</label></th><td><input id="user-full-name" name="full_name" required></td></tr>
                <tr><th><label for="user-username">Username</label></th><td><input id="user-username" name="username" required></td></tr>
                <tr><th><label for="user-email">Email</label></th><td><input type="email" id="user-email" name="email" required></td></tr>
                <tr><th><label for="user-role">Role</label></th><td>
                    <select id="user-role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="operator">Operator</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </td></tr>
                <tr><th><label for="user-status">Status</label></th><td>
                    <select id="user-status" name="status" required>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </td></tr>
                <tr id="password-row"><th><label for="user-password">Password</label></th><td><input type="password" id="user-password" name="password" placeholder="Leave blank to auto-generate"></td></tr>
            </table>
            <div class="modal-actions">
                <button type="submit" class="modal-btn primary">Save User</button>
                <button type="button" class="modal-btn" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Reset Password Modal -->
<div class="modal-backdrop" id="resetPasswordModalBg" style="display: none;">
    <div class="modal confirm-modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2>Reset Password for <strong id="reset-username"></strong></h2>
        <form id="reset-password-form">
            <input type="hidden" id="reset-user-id" name="id">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <table class="form-table">
                <tr>
                    <th><label for="new-password">New Password</label></th>
                    <td><input type="password" id="new-password" name="password" required></td>
                </tr>
            </table>
            <div class="modal-actions">
                <button type="submit" class="modal-btn danger">Set New Password</button>
                <button type="button" class="modal-btn" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
// Add this script section to the bottom of dashboard.php, replacing the existing script
const peakHourData = <?= json_encode($peakHourData) ?>;

// Auto-refresh apron status
setInterval(() => {
    fetch('dashboard.php?action=refresh_apron')
        .then(response => response.json())
        .then(data => {
            document.querySelector('#apron-total').textContent = data.total;
            document.querySelector('#apron-available').textContent = data.available;
            document.querySelector('#apron-occupied').textContent = data.occupied;
            document.querySelector('#apron-ron').textContent = data.ron;
        });
}, 5000);

// Peak hours summary
function updatePeakHoursSummary() {
    const dataWithTotals = peakHourData.map(h => ({ 
        ...h, 
        Arrivals: parseInt(h.Arrivals) || 0,
        Departures: parseInt(h.Departures) || 0,
        total: (parseInt(h.Arrivals) || 0) + (parseInt(h.Departures) || 0)
    }));

    const sortedByTotal = [...dataWithTotals].sort((a, b) => b.total - a.total);
    const peakPeriod = sortedByTotal[0] || { time_range: 'N/A', total: 0 };
    const quietPeriod = [...dataWithTotals].sort((a, b) => a.total - b.total).find(h => h.total > 0) || sortedByTotal[sortedByTotal.length - 1] || { time_range: 'N/A', total: 0 };
    
    const totalMovements = dataWithTotals.reduce((sum, h) => sum + h.total, 0);
    const totalArrivals = dataWithTotals.reduce((sum, h) => sum + h.Arrivals, 0);
    const totalDepartures = dataWithTotals.reduce((sum, h) => sum + h.Departures, 0);
    
    let busiestPeriod = { start: 0, total: 0 };
    for (let i = 0; i < dataWithTotals.length - 1; i++) {
        const windowTotal = dataWithTotals[i].total + dataWithTotals[i + 1].total;
        if (windowTotal > busiestPeriod.total) {
            busiestPeriod = { start: i, total: windowTotal };
        }
    }
    
    let busiestStart = "00:00-01:59";
    let busiestEnd = "02:00-03:59";
    if (dataWithTotals.length > 1 && busiestPeriod.start < dataWithTotals.length - 1) {
        busiestStart = dataWithTotals[busiestPeriod.start].time_range;
        busiestEnd = dataWithTotals[busiestPeriod.start + 1].time_range;
    }
    
    const summaryHTML = `
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #dc3545;">Peak 2-Hour Period:</strong>
            <span>${peakPeriod.time_range} (${peakPeriod.total} movements)</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #28a745;">Quietest 2-Hour Period:</strong>
            <span>${quietPeriod.time_range} (${quietPeriod.total} movements)</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #007bff;">Busiest 4-Hour Window:</strong>
            <span>${busiestStart.split(':')[0]}:00-${busiestEnd.split('-')[1]} (${busiestPeriod.total} movements)</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <strong style="color: #6c757d;">Today's Total:</strong>
            <span>${totalMovements} movements (${totalArrivals} arr, ${totalDepartures} dep)</span>
        </div>
    `;
    
    document.getElementById('peakHoursContent').innerHTML = summaryHTML;
}

// Modal Management System
class ModalManager {
    constructor() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Modal trigger buttons
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-modal-target')) {
                e.preventDefault();
                const modalId = e.target.getAttribute('data-modal-target');
                this.openModal(modalId);
            }
            
            if (e.target.hasAttribute('data-modal-close')) {
                e.preventDefault();
                this.closeModal(e.target.closest('.modal-backdrop'));
            }
        });

        // Close modal when clicking backdrop
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                this.closeModal(e.target);
            }
        });

        // ESC key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal-backdrop[style*="block"]');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Auto-scroll to top and show modal
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => {
                modal.style.display = 'flex';
                modal.style.alignItems = 'flex-start';
                modal.style.paddingTop = '50px';
                
                // Special handling for different modals
                if (modalId === 'accountsModalBg') {
                    this.loadUsers();
                } else if (modalId === 'snapshotModalBg') {
                    SnapshotManager.loadSnapshots();
                }
            }, 300);
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            // Reset forms
            const forms = modal.querySelectorAll('form');
            forms.forEach(form => form.reset());
        }
    }

    // User management functions
    loadUsers(page = 1) {
        const query = document.getElementById('user-search')?.value || '';
        const role = document.getElementById('role-filter')?.value || '';
        const status = document.getElementById('status-filter')?.value || '';

        const params = new URLSearchParams({
            action: 'list',
            query: query,
            role: role,
            status: status,
            page: page,
            per_page: 25
        });

        fetch(`admin-users.php?${params}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text(); // Get text first to debug
            })
            .then(text => {
                console.log('Raw response:', text); // Debug line - remove after fixing
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        this.renderUsersTable(data.data);
                        this.renderPagination(data);
                    } else {
                        this.showToast(data.message, 'error');
                    }
                } catch (e) {
                    this.showToast('Invalid JSON response from server', 'error');
                    console.error('JSON parse error:', e, 'Response:', text);
                }
            })
            .catch(error => {
                this.showToast('Error loading users: ' + error.message, 'error');
            });
    }

    renderUsersTable(users) {
        const tbody = document.getElementById('users-tbody');
        if (!tbody) return;

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${this.escapeHtml(user.full_name || '')}</td>
                <td>${this.escapeHtml(user.username)}</td>
                <td>${this.escapeHtml(user.email || '')}</td>
                <td><span class="role-badge role-${user.role}">${user.role}</span></td>
                <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                <td>${user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : 'Never'}</td>
                <td class="actions">
                    <button onclick="modalManager.editUser(${user.id})" class="action-btn edit">Edit</button>
                    <button onclick="modalManager.resetPassword(${user.id}, '${this.escapeHtml(user.username)}')" class="action-btn reset">Reset PW</button>
                    <button onclick="modalManager.toggleStatus(${user.id}, '${user.status}')" class="action-btn ${user.status === 'active' ? 'suspend' : 'activate'}">
                        ${user.status === 'active' ? 'Suspend' : 'Activate'}
                    </button>
                </td>
            </tr>
        `).join('');
    }

    renderPagination(data) {
        const container = document.getElementById('accounts-pagination');
        if (!container) return;

        const totalPages = Math.ceil(data.total / data.per_page);
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<div class="pagination">';
        
        if (data.page > 1) {
            paginationHTML += `<button onclick="modalManager.loadUsers(${data.page - 1})" class="page-btn">« Previous</button>`;
        }
        
        for (let i = Math.max(1, data.page - 2); i <= Math.min(totalPages, data.page + 2); i++) {
            paginationHTML += `<button onclick="modalManager.loadUsers(${i})" class="page-btn ${i === data.page ? 'active' : ''}">${i}</button>`;
        }
        
        if (data.page < totalPages) {
            paginationHTML += `<button onclick="modalManager.loadUsers(${data.page + 1})" class="page-btn">Next »</button>`;
        }
        
        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    }

    editUser(userId) {
        // Fetch user data and populate form
        fetch(`admin-users.php?action=list`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.data.find(u => u.id == userId);
                    if (user) {
                        document.getElementById('user-form-title').textContent = 'Edit User';
                        document.getElementById('user-id').value = user.id;
                        document.getElementById('user-full-name').value = user.full_name || '';
                        document.getElementById('user-username').value = user.username;
                        document.getElementById('user-email').value = user.email || '';
                        document.getElementById('user-role').value = user.role;
                        document.getElementById('user-status').value = user.status;
                        document.getElementById('password-row').style.display = 'none';
                        this.openModal('userFormModalBg');
                    }
                }
            });
    }

    resetPassword(userId, username) {
        document.getElementById('reset-username').textContent = username;
        document.getElementById('reset-user-id').value = userId;
        document.getElementById('reset-password-form').reset();
        this.openModal('resetPasswordModalBg');
    }

    toggleStatus(userId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
        const action = newStatus === 'suspended' ? 'suspend' : 'activate';
        
        if (confirm(`Are you sure you want to ${action} this user?`)) {
            const formData = new FormData();
            formData.append('action', 'set_status');
            formData.append('id', userId);
            formData.append('status', newStatus);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            fetch('admin-users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.loadUsers();
                } else {
                    this.showToast(data.message, 'error');
                }
            });
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        if (type === 'success') toast.style.backgroundColor = '#28a745';
        else if (type === 'error') toast.style.backgroundColor = '#dc3545';
        else toast.style.backgroundColor = '#17a2b8';

        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Snapshot Management Functions
const SnapshotManager = {
    loadSnapshots: function(page = 1) {
        const tbody = document.getElementById('snapshots-tbody');
        const loading = document.getElementById('snapshots-loading');
        
        if (loading) loading.style.display = 'block';
        if (tbody) tbody.innerHTML = '';

        const params = new URLSearchParams({
            action: 'list',
            page: page,
            per_page: 20
        });

        fetch(`snapshot-manager.php?${params}`)
            .then(response => response.json())
            .then(data => {
                if (loading) loading.style.display = 'none';
                
                if (data.success) {
                    this.renderSnapshotsTable(data.data);
                    this.renderSnapshotsPagination(data);
                } else {
                    modalManager.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                if (loading) loading.style.display = 'none';
                modalManager.showToast('Error loading snapshots: ' + error.message, 'error');
            });
    },

    renderSnapshotsTable: function(snapshots) {
        const tbody = document.getElementById('snapshots-tbody');
        if (!tbody) return;

        if (snapshots.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="snapshot-empty">No snapshots found</td></tr>';
            return;
        }

        tbody.innerHTML = snapshots.map(snapshot => `
            <tr>
                <td>${new Date(snapshot.snapshot_date).toLocaleDateString()}</td>
                <td>${modalManager.escapeHtml(snapshot.created_by_username || 'Unknown')}</td>
                <td>${new Date(snapshot.created_at).toLocaleString()}</td>
                <td class="actions">
                    <button onclick="SnapshotManager.viewSnapshot(${snapshot.id})" class="action-btn edit">View</button>
                    <button onclick="SnapshotManager.printSnapshot(${snapshot.id})" class="action-btn edit">Print</button>
                    ${hasRole('admin') ? `<button onclick="SnapshotManager.deleteSnapshot(${snapshot.id})" class="action-btn suspend">Delete</button>` : ''}
                </td>
            </tr>
        `).join('');
    },

    renderSnapshotsPagination: function(data) {
        const container = document.getElementById('snapshots-pagination');
        if (!container) return;

        const totalPages = Math.ceil(data.total / data.per_page);
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<div class="pagination">';
        
        if (data.page > 1) {
            paginationHTML += `<button onclick="SnapshotManager.loadSnapshots(${data.page - 1})" class="page-btn">« Previous</button>`;
        }
        
        for (let i = Math.max(1, data.page - 2); i <= Math.min(totalPages, data.page + 2); i++) {
            paginationHTML += `<button onclick="SnapshotManager.loadSnapshots(${i})" class="page-btn ${i === data.page ? 'active' : ''}">${i}</button>`;
        }
        
        if (data.page < totalPages) {
            paginationHTML += `<button onclick="SnapshotManager.loadSnapshots(${data.page + 1})" class="page-btn">Next »</button>`;
        }
        
        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    },

    viewSnapshot: function(snapshotId) {
        fetch(`snapshot-manager.php?action=view&id=${snapshotId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderSnapshotView(data.data, false);
                    modalManager.openModal('viewSnapshotModalBg');
                } else {
                    modalManager.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                modalManager.showToast('Error loading snapshot: ' + error.message, 'error');
            });
    },

    printSnapshot: function(snapshotId) {
        fetch(`snapshot-manager.php?action=view&id=${snapshotId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderSnapshotView(data.data, true);
                    document.title = `AMCReport(${data.data.snapshot_date})`;
                    
                    document.body.classList.add('is-printing');
                    window.print();
                    document.body.classList.remove('is-printing');

                } else {
                    modalManager.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                modalManager.showToast('Error loading snapshot for printing: ' + error.message, 'error');
            });
    },

    renderSnapshotView: function(snapshot, isPrinting) {
        const title = document.getElementById('snapshot-title');
        const content = document.getElementById('snapshot-content');
        
        if (title) {
            title.innerHTML = `Daily Snapshot - ${new Date(snapshot.snapshot_date).toLocaleDateString()}`;
        }

        if (!content) return;

        const data = snapshot.snapshot_data;
        let html = '';

        // Staff Roster Section
        if (data.staff_roster && data.staff_roster.length > 0) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Staff Roster</div>
                    <div class="snapshot-section-content">
            `;
            data.staff_roster.forEach(roster => {
                html += `
                    <div style="margin-bottom: 15px;">
                        <h4>${modalManager.escapeHtml(roster.shift)} - ${new Date(roster.roster_date).toLocaleDateString()}</h4>
                        <p><strong>Day Shift:</strong> ${[roster.day_shift_staff_1, roster.day_shift_staff_2, roster.day_shift_staff_3].filter(s => s).join(', ') || 'Not assigned'}</p>
                        <p><strong>Night Shift:</strong> ${[roster.night_shift_staff_1, roster.night_shift_staff_2, roster.night_shift_staff_3].filter(s => s).join(', ') || 'Not assigned'}</p>
                    </div>
                `;
            });
            html += `
                    </div>
                </div>
            `;
        }

        // Metrics Section
        if (data.daily_metrics) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Daily Metrics</div>
                    <div class="snapshot-section-content">
                        <div class="snapshot-metrics">
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.total_arrivals}</div>
                                <div class="snapshot-metric-label">Total Arrivals</div>
                            </div>
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.total_departures}</div>
                                <div class="snapshot-metric-label">Total Departures</div>
                            </div>
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.new_ron}</div>
                                <div class="snapshot-metric-label">New RON</div>
                            </div>
                            <div class="snapshot-metric">
                                <div class="snapshot-metric-value">${data.daily_metrics.active_ron}</div>
                                <div class="snapshot-metric-label">Active RON</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Peak Hour Analysis Section
        if (data.daily_metrics && data.daily_metrics.hourly_movements) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Peak Hour Analysis</div>
                    <div class="snapshot-section-content">
                        ${this.renderPeakHourChart(data.daily_metrics.hourly_movements)}
                        ${this.renderPeakHourSummary(data.daily_metrics.hourly_movements)}
                    </div>
                </div>
            `;
        }

        // Movements Section
        if (data.movements && data.movements.length > 0) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">Aircraft Movements</div>
                    <div class="snapshot-section-content">
                        <table class="snapshot-table">
                            <thead>
                                <tr>
                                    <th>Registration</th>
                                    <th>Type</th>
                                    <th>On Block</th>
                                    <th>Off Block</th>
                                    <th>Stand</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Category</th>
                                    <th>RON</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            data.movements.forEach(movement => {
                html += `
                    <tr>
                        <td>${modalManager.escapeHtml(movement.registration || '')}</td>
                        <td>${modalManager.escapeHtml(movement.aircraft_type || '')}</td>
                        <td>${modalManager.escapeHtml(movement.on_block_time || '')}</td>
                        <td>${modalManager.escapeHtml(movement.off_block_time || '')}</td>
                        <td>${modalManager.escapeHtml(movement.parking_stand || '')}</td>
                        <td>${modalManager.escapeHtml(movement.from_location || '')}</td>
                        <td>${modalManager.escapeHtml(movement.to_location || '')}</td>
                        <td>${modalManager.escapeHtml(movement.category || '')}</td>
                        <td>${movement.is_ron ? 'Yes' : 'No'}</td>
                    </tr>
                `;
            });
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } else {
            html += '<div class="snapshot-empty">No movements recorded for this date</div>';
        }

        // RON Section
        if (data.ron_data && data.ron_data.length > 0) {
            html += `
                <div class="snapshot-section">
                    <div class="snapshot-section-header">RON Aircraft</div>
                    <div class="snapshot-section-content">
                        <table class="snapshot-table">
                            <thead>
                                <tr>
                                    <th>Registration</th>
                                    <th>Type</th>
                                    <th>Stand</th>
                                    <th>Operator</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            data.ron_data.forEach(ron => {
                html += `
                    <tr>
                        <td>${modalManager.escapeHtml(ron.registration || '')}</td>
                        <td>${modalManager.escapeHtml(ron.aircraft_type || '')}</td>
                        <td>${modalManager.escapeHtml(ron.parking_stand || '')}</td>
                        <td>${modalManager.escapeHtml(ron.operator_airline || '')}</td>
                        <td>${modalManager.escapeHtml(ron.category || '')}</td>
                    </tr>
                `;
            });
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        content.innerHTML = html;

        if (isPrinting) {
            const printableContent = document.getElementById('viewSnapshotModalBg');
            if (printableContent) {
                printableContent.style.display = 'block';
            }
        }
    },

    renderPeakHourChart: function(peakHourData) {
        let chartHTML = '';
        const maxMovements = Math.max(...peakHourData.map(h => (parseInt(h.Arrivals) || 0) + (parseInt(h.Departures) || 0))) || 1;

        chartHTML += '<div class="chart-container" style="position: relative; height: 400px; overflow-x: auto;">';
        chartHTML += '<div class="chart-content" style="display: flex; align-items: end; height: 100%; min-width: 800px; gap: 2px; padding: 0 10px;">';

        peakHourData.forEach(hour => {
            const arrivalHeight = (parseInt(hour.Arrivals) / maxMovements) * 300;
            const departureHeight = (parseInt(hour.Departures) / maxMovements) * 300;
            const totalHeight = ((parseInt(hour.Arrivals) + parseInt(hour.Departures)) / maxMovements) * 300;
            const shortLabel = hour.time_range.substring(0, 2) + '-' + hour.time_range.substring(6, 8);

            chartHTML += `
                <div class="hour-bar-group" style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative;">
                    <div style="display: flex; gap: 1px; align-items: end; height: 300px; margin-bottom: 5px;">
                        <div class="arrival-bar" 
                             style="width: 12px; background: linear-gradient(to top, #36A2EB, #5BC0DE); height: ${arrivalHeight}px; border-radius: 2px 2px 0 0;"
                             title="${hour.time_range} - Arrivals: ${hour.Arrivals}">
                        </div>
                        <div class="departure-bar" 
                             style="width: 12px; background: linear-gradient(to top, #FF6384, #FF8A80); height: ${departureHeight}px; border-radius: 2px 2px 0 0;"
                             title="${hour.time_range} - Departures: ${hour.Departures}">
                        </div>
                    </div>
                    ${(parseInt(hour.Arrivals) + parseInt(hour.Departures)) > 0 ? `
                    <div class="total-point" 
                         style="position: absolute; bottom: ${5 + totalHeight}px; left: 50%; transform: translateX(-50%); width: 6px; height: 6px; background: #4BC0C0; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"
                         title="${hour.time_range} - Total: ${parseInt(hour.Arrivals) + parseInt(hour.Departures)}">
                    </div>
                    ` : ''}
                    <div style="font-size: 10px; color: #666; text-align: center; writing-mode: vertical-rl; text-orientation: mixed; height: 40px; display: flex; align-items: center;">
                        ${shortLabel}
                    </div>
                    ${(parseInt(hour.Arrivals) + parseInt(hour.Departures)) > 0 ? `
                    <div style="font-size: 9px; color: #333; font-weight: bold; margin-top: 2px;">
                        ${parseInt(hour.Arrivals) + parseInt(hour.Departures)}
                    </div>
                    ` : ''}
                </div>
            `;
        });

        chartHTML += '</div></div>';
        return chartHTML;
    },

    renderPeakHourSummary: function(peakHourData) {
        const dataWithTotals = peakHourData.map(h => ({ 
            ...h, 
            Arrivals: parseInt(h.Arrivals) || 0,
            Departures: parseInt(h.Departures) || 0,
            total: (parseInt(h.Arrivals) || 0) + (parseInt(h.Departures) || 0)
        }));

        const sortedByTotal = [...dataWithTotals].sort((a, b) => b.total - a.total);
        const peakPeriod = sortedByTotal[0] || { time_range: 'N/A', total: 0 };
        const quietPeriod = [...dataWithTotals].sort((a, b) => a.total - b.total).find(h => h.total > 0) || sortedByTotal[sortedByTotal.length - 1] || { time_range: 'N/A', total: 0 };
        
        const totalMovements = dataWithTotals.reduce((sum, h) => sum + h.total, 0);
        const totalArrivals = dataWithTotals.reduce((sum, h) => sum + h.Arrivals, 0);
        const totalDepartures = dataWithTotals.reduce((sum, h) => sum + h.Departures, 0);
        
        let busiestPeriod = { start: 0, total: 0 };
        for (let i = 0; i < dataWithTotals.length - 1; i++) {
            const windowTotal = dataWithTotals[i].total + dataWithTotals[i + 1].total;
            if (windowTotal > busiestPeriod.total) {
                busiestPeriod = { start: i, total: windowTotal };
            }
        }
        
        let busiestStart = "00:00-01:59";
        let busiestEnd = "02:00-03:59";
        if (dataWithTotals.length > 1 && busiestPeriod.start < dataWithTotals.length - 1) {
            busiestStart = dataWithTotals[busiestPeriod.start].time_range;
            busiestEnd = dataWithTotals[busiestPeriod.start + 1].time_range;
        }
        
        return `
            <div id="peakHoursSummary" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #112D4E;">
                <div style="font-weight: bold; margin-bottom: 10px; color: #112D4E;">Peak Hours Summary</div>
                <div id="peakHoursContent" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #dc3545;">Peak 2-Hour Period:</strong>
                        <span>${peakPeriod.time_range} (${peakPeriod.total} movements)</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #28a745;">Quietest 2-Hour Period:</strong>
                        <span>${quietPeriod.time_range} (${quietPeriod.total} movements)</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #007bff;">Busiest 4-Hour Window:</strong>
                        <span>${busiestStart.split(':')[0]}:00-${busiestEnd.split('-')[1]} (${busiestPeriod.total} movements)</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <strong style="color: #6c757d;">Day's Total:</strong>
                        <span>${totalMovements} movements (${totalArrivals} arr, ${totalDepartures} dep)</span>
                    </div>
                </div>
            </div>
        `;
    },

    deleteSnapshot: function(snapshotId) {
        if (!confirm('Are you sure you want to delete this snapshot? This action cannot be undone.')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', snapshotId);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        fetch('snapshot-manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalManager.showToast(data.message, 'success');
                this.loadSnapshots();
            } else {
                modalManager.showToast(data.message, 'error');
            }
        })
        .catch(error => {
            modalManager.showToast('Error deleting snapshot: ' + error.message, 'error');
        });
    }
};

// Initialize modal manager
const modalManager = new ModalManager();

// Form submissions
document.addEventListener('DOMContentLoaded', function() {
    updatePeakHoursSummary();

    // User form submission
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const isEdit = formData.get('id');
            formData.append('action', isEdit ? 'update' : 'create');

            fetch('admin-users.php', {  // Make sure this points to the right file
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalManager.showToast(data.message, 'success');
                    if (data.temp_password) {
                        modalManager.showToast(`Temporary password: ${data.temp_password}`, 'info');
                    }
                    modalManager.closeModal(document.getElementById('userFormModalBg'));
                    modalManager.loadUsers();
                } else {
                    modalManager.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                modalManager.showToast('Network error: ' + error.message, 'error');
            });
        });
    }

    // New user button
    const newUserBtn = document.getElementById('new-user-btn');
    if (newUserBtn) {
        newUserBtn.addEventListener('click', function() {
            document.getElementById('user-form-title').textContent = 'Create User';
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('password-row').style.display = 'table-row';
            modalManager.closeModal(document.getElementById('accountsModalBg'));
            modalManager.openModal('userFormModalBg');
        });
    }

    // Search and filter handlers
    const userSearch = document.getElementById('user-search');
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const refreshBtn = document.getElementById('refresh-users');

    if (userSearch) {
        let searchTimeout;
        userSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => modalManager.loadUsers(), 500);
        });
    }

    if (roleFilter) {
        roleFilter.addEventListener('change', () => modalManager.loadUsers());
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', () => modalManager.loadUsers());
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => modalManager.loadUsers());
    }

    // Copy password functionality
    const copyBtn = document.getElementById('copy-password');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const passwordInput = document.getElementById('temp-password-value');
            passwordInput.select();
            document.execCommand('copy');
            modalManager.showToast('Password copied to clipboard', 'success');
        });
    }

    // Charter form date handling
    const charterForm = document.getElementById('charter-report-form');
    if (charterForm) {
        charterForm.addEventListener('submit', function(e) {
            const monthYearInput = document.getElementById('charter-month');
            const [year, month] = monthYearInput.value.split('-');
            document.getElementById('charter-month-hidden').value = month;
            document.getElementById('charter-year-hidden').value = year;
        });
    }

    // Password reset form submission
    const resetPasswordForm = document.getElementById('reset-password-form');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'reset_password');

            fetch('admin-users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalManager.showToast(data.message, 'success');
                    modalManager.closeModal(document.getElementById('resetPasswordModalBg'));
                } else {
                    modalManager.showToast(data.message, 'error');
                }
            });
        });
    }

    // Create snapshot form submission
    const createSnapshotForm = document.getElementById('create-snapshot-form');
    if (createSnapshotForm) {
       createSnapshotForm.addEventListener('submit', function(e) {
           e.preventDefault();
           const formData = new FormData(this);
           formData.append('action', 'create');

           fetch('snapshot-manager.php', {
               method: 'POST',
               body: formData
           })
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   modalManager.showToast(data.message, 'success');
                   this.reset();
                   SnapshotManager.loadSnapshots();
               } else {
                   modalManager.showToast(data.message, 'error');
               }
           })
           .catch(error => {
               modalManager.showToast('Error creating snapshot: ' + error.message, 'error');
           });
       });
    }
});

// Add role checking function for JavaScript
function hasRole(role) {
   const userRole = '<?php echo $user_role; ?>';
   if (Array.isArray(role)) {
       return role.includes(userRole);
   }
   return userRole === role;
}
</script>

<!-- Daily Snapshot Archive Modal -->
<?php if (hasRole(['admin', 'operator'])) : ?>
<div class="modal-backdrop" id="snapshotModalBg" style="display: none;">
    <div class="modal snapshot-modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2>Daily Snapshot Archive</h2>
        
        <!-- Create Snapshot Section -->
        <div class="snapshot-controls">
            <div class="snapshot-group">
                <h3>Create New Snapshot</h3>
                <form id="create-snapshot-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="form-group">
                        <label for="snapshot-date">Date:</label>
                        <input type="date" id="snapshot-date" name="snapshot_date" value="<?= date('Y-m-d') ?>" required>
                        <button type="submit" class="modal-btn primary">Create Snapshot</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Snapshots List -->
        <div class="snapshots-list-container">
            <h3>Existing Snapshots</h3>
            <div id="snapshots-loading" style="display: none;">Loading snapshots...</div>
            <table id="snapshots-table" class="accounts-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="snapshots-tbody">
                    <!-- Snapshots will be loaded here -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="snapshots-pagination"></div>
    </div>
</div>

<!-- View Snapshot Modal -->
<div class="modal-backdrop printable-snapshot" id="viewSnapshotModalBg" style="display: none;">
    <div class="modal view-snapshot-modal">
        <span class="close-btn" data-modal-close>&times;</span>
        <h2 id="snapshot-title">Daily Snapshot - Loading...</h2>
        <div id="snapshot-content">
            <!-- Snapshot content will be loaded here -->
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>