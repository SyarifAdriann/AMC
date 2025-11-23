<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AircraftDetailRepository;
use App\Repositories\AircraftMovementRepository;
use App\Repositories\FlightReferenceRepository;
use App\Security\CsrfManager;
use App\Services\ApronStatusService;
use App\Services\ReportService;
use DateTimeImmutable;
use InvalidArgumentException;
use PDOException;
use Throwable;

class DashboardController extends Controller
{
    protected AuthManager $auth;
    protected ApronStatusService $apronStatus;
    protected AircraftMovementRepository $movements;
    protected AircraftDetailRepository $aircraftDetails;
    protected FlightReferenceRepository $flightReferences;
    protected ReportService $reports;
    protected CsrfManager $csrf;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->apronStatus = $app->make(ApronStatusService::class);
        $this->movements = $app->make(AircraftMovementRepository::class);
        $this->aircraftDetails = $app->make(AircraftDetailRepository::class);
        $this->flightReferences = $app->make(FlightReferenceRepository::class);
        $this->reports = $app->make(ReportService::class);
        $this->csrf = $app->make(CsrfManager::class);
    }

    public function show(): Response
    {
        $request = $this->request();
        $action = strtolower((string) $request->query('action', ''));

        if ($action === 'refresh_apron') {
            return $this->json($this->apronStatus->getStatus());
        }

        $user = $this->auth->user() ?: [];

        if (($user['role'] ?? 'viewer') === 'viewer') {
            return Response::redirect('index.php');
        }

        return $this->renderDashboard();
    }

    public function movementMetrics(): Response
    {
        $today = date('Y-m-d');
        return Response::json([
            'success' => true,
            'snapshots' => $this->buildCategoryBreakdown($today),
            'hourly' => $this->buildHourlyBreakdown($today),
            'timestamp' => date('c')
        ]);
    }

    public function handle(): Response
    {
        $request = $this->request();
        $action = strtolower((string) $request->input('action', ''));

        try {
            switch ($action) {
                case 'generate':
                    return $this->handleReport($request, false);
                case 'export_csv':
                    return $this->handleReport($request, true);
                case 'manage_aircraft':
                    return $this->handleManageAircraft($request);
                case 'manage_flight_reference':
                    return $this->handleManageFlightReference($request);
                case 'monthly_charter_report':
                    return $this->handleMonthlyCharterReport($request);
                default:
                    return $this->renderDashboard([
                        'reportOutput' => "<p style='color: red;'>Invalid action.</p>",
                    ]);
            }
        } catch (InvalidArgumentException $e) {
            error_log('DashboardController validation error: ' . $e->getMessage());

            return $this->renderDashboard([
                'reportOutput' => "<p style='color: red;'>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>",
            ]);
        } catch (PDOException $e) {
            error_log('DashboardController database error: ' . $e->getMessage());

            return $this->renderDashboard([
                'reportOutput' => "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>",
            ]);
        } catch (Throwable $e) {
            error_log('DashboardController error: ' . $e->getMessage());

            return $this->renderDashboard([
                'reportOutput' => "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>",
            ]);
        }
    }

    protected function handleReport(Request $request, bool $export): Response
    {
        $today = date('Y-m-d');
        $type = trim((string) $request->input('report_type', ''));

        if ($type === '') {
            throw new InvalidArgumentException('Please select a report type.');
        }

        $dateFrom = $this->normalizeDate($request->input('date_from'), $today);
        $dateTo = $this->normalizeDate($request->input('date_to'), $today);

        $data = $this->reports->fetchReportData($type, $dateFrom, $dateTo);

        if ($export) {
            $csv = $this->reports->buildCsv($type, $data);
            $filename = sprintf('report_%s_%s.csv', $type, date('Ymd'));

            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $html = $this->reports->buildHtml($type, $data);

        return $this->renderDashboard([
            'reportOutput' => $html,
        ]);
    }

    protected function handleManageAircraft(Request $request): Response
    {
        if (!$this->csrf->validate($request->input('csrf_token'))) {
            return $this->renderDashboard([
                'reportOutput' => "<p style='color: red;'>Invalid security token. Please refresh and try again.</p>",
            ]);
        }

        if ($denied = $this->requireRole(['admin', 'operator'])) {
            return $denied;
        }

        $registration = trim((string) $request->input('registration', ''));

        if ($registration === '') {
            throw new InvalidArgumentException('Registration is required.');
        }

        $attributes = [
            'aircraft_type' => trim((string) $request->input('aircraft_type', '')),
            'operator_airline' => trim((string) $request->input('operator_airline', '')),
            'category' => trim((string) $request->input('category', '')),
            'notes' => trim((string) $request->input('notes', '')),
        ];

        $this->aircraftDetails->upsert($registration, $attributes);

        return $this->renderDashboard([
            'reportOutput' => "<p style='color: green;'>Aircraft details saved successfully.</p>",
        ]);
    }

    protected function handleManageFlightReference(Request $request): Response
    {
        if (!$this->csrf->validate($request->input('csrf_token'))) {
            return $this->renderDashboard([
                'reportOutput' => "<p style='color: red;'>Invalid security token. Please refresh and try again.</p>",
            ]);
        }

        if ($denied = $this->requireRole(['admin', 'operator'])) {
            return $denied;
        }

        $flight = trim((string) $request->input('flight_no', ''));
        if ($flight === '') {
            throw new InvalidArgumentException('Flight number is required.');
        }

        $route = trim((string) $request->input('default_route', ''));
        $this->flightReferences->upsert($flight, $route);

        return $this->renderDashboard([
            'reportOutput' => "<p style='color: green;'>Flight reference saved successfully.</p>",
        ]);
    }

    protected function handleMonthlyCharterReport(Request $request): Response
    {
        if (!$this->csrf->validate($request->input('csrf_token'))) {
            return $this->renderDashboard([
                'reportOutput' => "<p style='color: red;'>Invalid security token. Please refresh and try again.</p>",
            ]);
        }

        if ($denied = $this->requireRole(['admin', 'operator'])) {
            return $denied;
        }

        $month = $this->normalizeMonth($request->input('month'), date('m'));
        $year = $this->normalizeYear($request->input('year'), date('Y'));

        $data = $this->reports->fetchMonthlyCharterData($month, $year);
        $html = $this->reports->buildMonthlyCharterHtml($data, $month, $year);

        return $this->renderDashboard([
            'reportOutput' => $html,
        ]);
    }

    protected function renderDashboard(array $overrides = []): Response
    {
        $user = $this->auth->user() ?: [];
        $today = date('Y-m-d');
        $hourly = $this->buildHourlyBreakdown($today);

        return $this->view('dashboard/index', [
            'username' => $user['username'] ?? '',
            'user_role' => $user['role'] ?? 'viewer',
            'current_page' => 'dashboard.php',
            'csrf_token' => $this->csrf->token(),
            'today' => $today,
            'apronStatus' => $this->apronStatus->getStatus(),
            'movementsToday' => $this->buildCategoryBreakdown($today),
            'movementsByHour' => $hourly,
            'peakHourData' => $hourly,
            'reportOutput' => $overrides['reportOutput'] ?? '',
        ]);
    }

    protected function buildCategoryBreakdown(string $date): array
    {
        $defaults = [
            'commercial' => ['arrivals' => 0, 'departures' => 0],
            'cargo' => ['arrivals' => 0, 'departures' => 0],
            'charter' => ['arrivals' => 0, 'departures' => 0],
        ];

        foreach ($this->movements->categoryBreakdown($date) as $row) {
            $category = strtolower((string) ($row['category'] ?? 'charter'));

            // Map Indonesian category names to English
            $categoryMap = [
                'komersial' => 'commercial',
                'kargo' => 'cargo',
            ];

            $category = $categoryMap[$category] ?? $category;

            if (isset($defaults[$category])) {
                $defaults[$category]['arrivals'] = (int) ($row['arrivals'] ?? 0);
                $defaults[$category]['departures'] = (int) ($row['departures'] ?? 0);
            }
        }

        return $defaults;
    }

    protected function buildHourlyBreakdown(string $date): array
    {
        $data = [];

        foreach ($this->movements->hourlyBreakdown($date) as $row) {
            $label = (string) ($row['time_range'] ?? '');
            $data[$label] = [
                'time_range' => $label,
                'Arrivals' => (int) ($row['Arrivals'] ?? 0),
                'Departures' => (int) ($row['Departures'] ?? 0),
            ];
        }

        $result = [];
        for ($i = 0; $i < 12; $i++) {
            $start = $i * 2;
            $end = $start + 1;
            $label = sprintf('%02d:00-%02d:59', $start, $end);
            $result[] = $data[$label] ?? [
                'time_range' => $label,
                'Arrivals' => 0,
                'Departures' => 0,
            ];
        }

        return $result;
    }

    protected function normalizeDate($value, string $fallback): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dt && $dt->format('Y-m-d') === $value) {
            return $value;
        }

        return $fallback;
    }

    protected function normalizeMonth($value, string $fallback): string
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return $fallback;
        }

        $int = (int) $value;
        if ($int < 1 || $int > 12) {
            return $fallback;
        }

        return str_pad((string) $int, 2, '0', STR_PAD_LEFT);
    }

    protected function normalizeYear($value, string $fallback): string
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value) || strlen($value) !== 4) {
            return $fallback;
        }

        return $value;
    }

    protected function hasRole($roles): bool
    {
        $role = $this->auth->role();

        if (is_array($roles)) {
            return in_array($role, $roles, true);
        }

        return $role === $roles;
    }

    protected function requireRole(array $roles, string $message = 'Unauthorized access'): ?Response
    {
        if ($this->hasRole($roles)) {
            return null;
        }

        $escaped = addslashes($message);
        return Response::make("<script>alert('{$escaped}'); history.back();</script>", 403);
    }
}
