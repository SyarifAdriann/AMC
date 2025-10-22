<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\ApronStatusService;
use App\Services\RonService;
use App\Repositories\AircraftDetailRepository;
use App\Repositories\AircraftMovementRepository;
use App\Repositories\DailyStaffRosterRepository;
use App\Repositories\FlightReferenceRepository;
use PDOException;
use InvalidArgumentException;
use Throwable;

class ApronController extends Controller
{
    protected AuthManager $auth;
    protected RonService $ronService;
    protected ApronStatusService $apronStatus;
    protected DailyStaffRosterRepository $rosters;
    protected AircraftMovementRepository $movements;
    protected FlightReferenceRepository $flightReferences;
    protected AircraftDetailRepository $aircraftDetails;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->ronService = $app->make(RonService::class);
        $this->apronStatus = $app->make(ApronStatusService::class);
        $this->rosters = $app->make(DailyStaffRosterRepository::class);
        $this->movements = $app->make(AircraftMovementRepository::class);
        $this->flightReferences = $app->make(FlightReferenceRepository::class);
        $this->aircraftDetails = $app->make(AircraftDetailRepository::class);
    }
    public function show(): Response
    {
        $user = $this->auth->user() ?: [];

        return $this->view('apron/index', [
            'username' => $user['username'] ?? '',
            'user_role' => $user['role'] ?? 'viewer',
            'current_page' => 'index.php',
            'apronStatus' => $this->apronStatus->getStatus(),
            'currentMovements' => $this->getCurrentMovements(),
            'hgrRecords' => $this->getHangarRecords(),
        ]);
    }

    public function handle(): Response
    {
        $request = $this->request();
        $data = $this->parsePayload($request);
        $action = strtolower((string) ($data['action'] ?? ''));
        $user = $this->auth->user() ?: [];
        $userId = (int) ($user['id'] ?? 0);

        try {
            switch ($action) {
                case 'saveroster':
                    if (!$this->hasRole(['admin', 'operator'])) {
                        return $this->forbidden('Not authorized to save roster');
                    }

                    return Response::json($this->saveRoster($data, $userId));

                case 'setron':
                    if (!$this->hasRole(['admin', 'operator'])) {
                        return $this->forbidden('Not authorized to set RON status');
                    }

                    $updatedCount = $this->ronService->setRonForOpenMovements($userId);

                    return Response::json([
                        'success' => true,
                        'message' => "RON status updated for {$updatedCount} movements.",
                        'updated_count' => $updatedCount,
                    ]);

                case 'savemovement':
                    if (!$this->hasRole(['admin', 'operator'])) {
                        return $this->forbidden('Not authorized to save movements');
                    }

                    return Response::json($this->saveMovement($data, $userId));

                case 'getaircraftdetails':
                    return Response::json($this->lookupAircraftDetails($data));

                case 'getflightroute':
                    return Response::json($this->lookupFlightRoute($data));

                default:
                    return Response::json([
                        'success' => false,
                        'message' => 'Invalid action.',
                    ], 400);
            }
        } catch (InvalidArgumentException $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (PDOException $e) {
            error_log('ApronController database error: ' . $e->getMessage());

            return Response::json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'sqlstate' => $e->getCode(),
            ], 500);
        } catch (Throwable $e) {
            error_log('ApronController error: ' . $e->getMessage());

            return Response::json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function parsePayload(Request $request): array
    {
        $json = $request->json();

        if (is_array($json) && !empty($json)) {
            return $json;
        }

        $input = $request->input();

        return is_array($input) ? $input : [];
    }

    protected function saveRoster(array $data, int $userId): array
    {
        $date = trim((string) ($data['date'] ?? ''));
        if ($date === '') {
            return ['success' => false, 'message' => 'Date is required for roster.'];
        }

        $aerodrome = trim((string) ($data['aerodrome'] ?? 'WIHH'));

        $status = $this->rosters->upsertRoster($date, $aerodrome, [
            'day_shift_staff_1' => trim((string) ($data['day_staff_1'] ?? '')),
            'day_shift_staff_2' => trim((string) ($data['day_staff_2'] ?? '')),
            'day_shift_staff_3' => trim((string) ($data['day_staff_3'] ?? '')),
            'night_shift_staff_1' => trim((string) ($data['night_staff_1'] ?? '')),
            'night_shift_staff_2' => trim((string) ($data['night_staff_2'] ?? '')),
            'night_shift_staff_3' => trim((string) ($data['night_staff_3'] ?? '')),
        ], $userId);

        $message = $status === 'updated' ? 'Roster updated successfully.' : 'Roster saved successfully.';

        return ['success' => true, 'message' => $message];
    }
    protected function saveMovement(array $data, int $userId): array
    {
        $id = $data['id'] ?? null;
        $isUpdate = !empty($id) && $id !== 'new';

        $registration = trim((string) ($data['registration'] ?? ''));
        if ($registration === '') {
            return ['success' => false, 'message' => 'Registration is required.'];
        }

        $payload = [
            'id' => $isUpdate ? (int) $id : null,
            'registration' => $registration,
            'aircraft_type' => trim((string) ($data['aircraft_type'] ?? '')),
            'on_block_time' => trim((string) ($data['on_block_time'] ?? '')),
            'off_block_time' => trim((string) ($data['off_block_time'] ?? '')),
            'parking_stand' => trim((string) ($data['parking_stand'] ?? '')),
            'from_location' => trim((string) ($data['from_location'] ?? '')),
            'to_location' => trim((string) ($data['to_location'] ?? '')),
            'flight_no_arr' => trim((string) ($data['flight_no_arr'] ?? '')),
            'flight_no_dep' => trim((string) ($data['flight_no_dep'] ?? '')),
            'operator_airline' => trim((string) ($data['operator_airline'] ?? '')),
            'remarks' => trim((string) ($data['remarks'] ?? '')),
            'is_ron' => filter_var($data['is_ron'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];

        $result = $this->movements->saveMovement($payload, $userId);

        return [
            'success' => true,
            'message' => 'Movement saved successfully.',
            'id' => $result['id'],
            'is_new' => $result['is_new'],
        ];
    }
    protected function lookupAircraftDetails(array $data): array
    {
        $registration = trim((string) ($data['registration'] ?? ''));
        if ($registration === '') {
            return ['success' => false, 'message' => 'Registration is required.'];
        }

        $detail = $this->aircraftDetails->findByRegistration($registration);

        if (!$detail) {
            return ['success' => false, 'message' => 'Aircraft not found.'];
        }

        return [
            'success' => true,
            'aircraft_type' => $detail->aircraftType() ?? '',
            'operator_airline' => $detail->operatorAirline() ?? '',
        ];
    }
    protected function lookupFlightRoute(array $data): array
    {
        $flightNo = trim((string) ($data['flight_no'] ?? ''));
        if ($flightNo === '') {
            return ['success' => false, 'message' => 'Flight number is required.'];
        }

        $reference = $this->flightReferences->findByFlightNumber($flightNo);

        if (!$reference) {
            return ['success' => false, 'message' => 'Flight route not found.'];
        }

        return [
            'success' => true,
            'default_route' => $reference->defaultRoute() ?? '',
        ];
    }
    protected function getCurrentMovements(): array
    {
        $this->ronService->carryOverActiveRon();

        return $this->movements->findCurrentApronMovements();
    }
    protected function getHangarRecords(): array
    {
        return $this->movements->findHangarMovements();
    }
    protected function hasRole($roles): bool
    {
        $role = $this->auth->role();

        if (is_array($roles)) {
            return in_array($role, $roles, true);
        }

        return $role === $roles;
    }

    public function status(): Response
    {
        return Response::json($this->apronStatus->getStatus());
    }

    protected function forbidden(string $message): Response
    {
        return Response::json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}

