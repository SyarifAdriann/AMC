<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\RonService;
use App\Repositories\AircraftMovementRepository;
use PDOException;
use Throwable;

class MasterTableController extends Controller
{
    protected AuthManager $auth;
    protected RonService $ronService;
    protected AircraftMovementRepository $movements;
    protected int $perPage = 75;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->ronService = $app->make(RonService::class);
        $this->movements = $app->make(AircraftMovementRepository::class);
    }

    public function show(): Response
    {
        $this->ronService->carryOverActiveRon();

        $request = $this->request();
        $filters = $this->collectFilters($request);

        $mainPage = max(1, (int) $request->query('main_page', 1));
        $ronPage = max(1, (int) $request->query('ron_page', 1));

        $mainData = $this->fetchMasterMovements($filters, $mainPage);
        $ronData = $this->fetchRonMovements($filters, $ronPage);
        $duplicateFlights = $this->findDuplicateFlights();

        $user = $this->auth->user() ?: [];

        return $this->view('master-table/index', [
            'username' => $user['username'] ?? '',
            'user_role' => $user['role'] ?? 'viewer',
            'csrf_token' => $this->app->make(\App\Security\CsrfManager::class)->token(),
            'current_page' => 'master-table.php',
            'filters' => $filters,
            'movements_data' => $mainData['records'],
            'main_page' => $mainPage,
            'main_total_pages' => $mainData['total_pages'],
            'main_total_results' => $mainData['total_results'],
            'main_offset' => $mainData['offset'],
            'ron_data' => $ronData['records'],
            'ron_page' => $ronPage,
            'ron_total_pages' => $ronData['total_pages'],
            'ron_total_results' => $ronData['total_results'],
            'ron_offset' => $ronData['offset'],
            'duplicate_flights' => $duplicateFlights,
        ]);
    }

    public function handle(): Response
    {
        $request = $this->request();
        $payload = $this->parsePayload($request);
        $action = strtolower((string) ($payload['action'] ?? ''));

        $user = $this->auth->user() ?: [];
        $userId = (int) ($user['id'] ?? 0);

        if (!$this->verifyCsrf($request)) {
            return $this->csrfFailureResponse();
        }

        try {
            switch ($action) {
                case 'save_all_changes':
                    if (!$this->hasRole(['admin', 'operator'])) {
                        return $this->forbidden('Not authorized to save changes');
                    }

                    return Response::json($this->saveAllChanges($payload, $userId));

                case 'create_new_movement':
                    if (!$this->hasRole(['admin', 'operator'])) {
                        return $this->forbidden('Not authorized to create movements');
                    }

                    return Response::json($this->createMovement($payload, $userId));

                case 'setron':
                    if (!$this->hasRole(['admin', 'operator'])) {
                        return $this->forbidden('Not authorized to set RON status');
                    }

                    $updated = $this->ronService->setRonForOpenMovements($userId);
                    $this->broadcaster()->bump('master-table:setron');

                    return Response::json([
                        'success' => true,
                        'message' => "RON status set for {$updated} movements",
                    ]);

                default:
                    return Response::json([
                        'success' => false,
                        'message' => 'Invalid action.',
                    ], 400);
            }
        } catch (PDOException $e) {

            error_log('MasterTableController database error: ' . $e->getMessage());

            return Response::json([
                'success' => false,
                'message' => $this->app->config('app.debug')
                    ? 'Database error: ' . $e->getMessage()
                    : 'A database error occurred. Please try again or contact an administrator.',
            ], 500);
        } catch (Throwable $e) {

            error_log('MasterTableController error: ' . $e->getMessage());

            return Response::json([
                'success' => false,
                'message' => $this->app->config('app.debug')
                    ? 'Server error: ' . $e->getMessage()
                    : 'A server error occurred. Please try again or contact an administrator.',
            ], 500);
        }
    }

    protected function collectFilters(Request $request): array
    {
        return [
            'registration' => trim((string) $request->query('registration', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'category' => trim((string) $request->query('category', '')),
            'airline' => trim((string) $request->query('airline', '')),
            'flight_no' => trim((string) $request->query('flight_no', '')),
        ];
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

    protected function saveAllChanges(array $payload, int $userId): array
    {
        $changes = $payload['changes'] ?? null;

        if (is_string($changes)) {
            $changes = json_decode($changes, true);
        }

        if (!is_array($changes) || empty($changes)) {
            return ['success' => false, 'message' => 'No changes to save'];
        }

        $affectedIds = [];
        foreach ($changes as $change) {
            $id = (int) ($change['id'] ?? 0);
            if ($id > 0) {
                $affectedIds[$id] = true;
            }
        }

        $this->movements->bulkUpdate($changes, $userId);
        $this->broadcaster()->bump('master-table:bulk_update');

        $warnings = [];
        $duplicateFlights = [];

        foreach (array_keys($affectedIds) as $id) {
            $row = $this->movements->findById((int) $id);
            if (!$row) {
                continue;
            }

            $evaluation = $this->movements->evaluateInputWarnings($row);
            foreach (($evaluation['warnings'] ?? []) as $warning) {
                $warnings[] = "Row {$id}: {$warning}";
            }
            foreach (($evaluation['duplicate_flights'] ?? []) as $flightNo) {
                $duplicateFlights[] = $flightNo;
            }
        }

        $warnings = array_values(array_unique($warnings));
        $duplicateFlights = array_values(array_unique($duplicateFlights));

        return [
            'success' => true,
            'message' => 'All changes saved successfully',
            'warnings' => $warnings,
            'duplicate_flights' => $duplicateFlights,
        ];
    }

    protected function createMovement(array $payload, int $userId): array
    {
        $registration = trim((string) ($payload['registration'] ?? ''));

        if ($registration === '') {
            return ['success' => false, 'message' => 'Registration is required'];
        }

        $movementData = [
            'registration' => $registration,
            'aircraft_type' => trim((string) ($payload['aircraft_type'] ?? '')),
            'on_block_time' => trim((string) ($payload['on_block_time'] ?? '')),
            'off_block_time' => trim((string) ($payload['off_block_time'] ?? '')),
            'parking_stand' => trim((string) ($payload['parking_stand'] ?? '')),
            'from_location' => trim((string) ($payload['from_location'] ?? '')),
            'to_location' => trim((string) ($payload['to_location'] ?? '')),
            'flight_no_arr' => trim((string) ($payload['flight_no_arr'] ?? '')),
            'flight_no_dep' => trim((string) ($payload['flight_no_dep'] ?? '')),
            'operator_airline' => trim((string) ($payload['operator_airline'] ?? '')),
            'remarks' => trim((string) ($payload['remarks'] ?? '')),
            'is_ron' => filter_var($payload['is_ron'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];

        // Double-booking check runs BEFORE the insert so the new row itself
        // doesn't count as the occupant; reported as a warning, not a block.
        $conflictWarning = null;
        if ($movementData['parking_stand'] !== '') {
            $occupant = $this->movements->findOpenMovementOnStand($movementData['parking_stand']);
            if ($occupant && strcasecmp((string) $occupant['registration'], $registration) !== 0) {
                $conflictWarning = 'Stand ' . strtoupper($movementData['parking_stand'])
                    . ' is already occupied by ' . $occupant['registration'] . '.';
            }
        }

        $result = $this->movements->saveMovement($movementData, $userId);
        $this->broadcaster()->bump('master-table:create_movement');
        $validation = $this->movements->evaluateInputWarnings($movementData);

        $warnings = $validation['warnings'] ?? [];
        if ($conflictWarning) {
            $warnings[] = $conflictWarning;
        }

        return [
            'success' => true,
            'message' => 'Movement created successfully.',
            'id' => $result['id'],
            'warnings' => $warnings,
            'duplicate_flights' => $validation['duplicate_flights'] ?? [],
        ];
    }
    protected function fetchMasterMovements(array $filters, int $page): array
    {
        return $this->movements->paginateActiveMovements($filters, $page, $this->perPage);
    }
    protected function fetchRonMovements(array $filters, int $page): array
    {
        return $this->movements->paginateCompletedRonMovements($filters, $page, $this->perPage);
    }

    protected function findDuplicateFlights(): array
    {
        return $this->movements->findDuplicateFlights(date('Y-m-d'));
    }


    protected function broadcaster(): \App\Services\ApronChangeBroadcaster
    {
        return $this->app->make(\App\Services\ApronChangeBroadcaster::class);
    }

    protected function hasRole($roles): bool
    {
        $role = $this->auth->role();

        if (is_array($roles)) {
            return in_array($role, $roles, true);
        }

        return $role === $roles;
    }

    protected function forbidden(string $message): Response
    {
        return Response::json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
