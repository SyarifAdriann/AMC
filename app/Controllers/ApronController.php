<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Security\CsrfManager;
use App\Services\ApronChangeBroadcaster;
use App\Services\ApronStatusService;
use App\Services\FreehandLayoutService;
use App\Services\RecommendationService;
use App\Services\RonService;
use App\Repositories\AircraftDetailRepository;
use App\Repositories\AircraftMovementRepository;
use App\Repositories\DailyStaffRosterRepository;
use App\Repositories\FlightReferenceRepository;
use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ApronController extends Controller
{
    protected AuthManager $auth;
    protected RonService $ronService;
    protected ApronStatusService $apronStatus;
    protected RecommendationService $recommendations;
    protected FreehandLayoutService $freehand;
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
        $this->recommendations = $app->make(RecommendationService::class);
        $this->freehand = $app->make(FreehandLayoutService::class);
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
            'csrf_token' => $this->app->make(CsrfManager::class)->token(),
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

        $writeActions = ['saveroster', 'setron', 'savemovement'];
        if (in_array($action, $writeActions, true) && !$this->verifyCsrf($request)) {
            return $this->csrfFailureResponse();
        }

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
                    $this->broadcaster()->bump('apron:setron');

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
                'message' => $this->app->config('app.debug')
                    ? 'Database error: ' . $e->getMessage()
                    : 'A database error occurred. Please try again or contact an administrator.',
            ], 500);
        } catch (Throwable $e) {
            error_log('ApronController error: ' . $e->getMessage());

            return Response::json([
                'success' => false,
                'message' => $this->app->config('app.debug')
                    ? 'Server error: ' . $e->getMessage()
                    : 'A server error occurred. Please try again or contact an administrator.',
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
            'category' => trim((string) ($data['category'] ?? '')),
        ];

        $predictionLogId = isset($data['prediction_log_id']) ? (int) $data['prediction_log_id'] : null;

        // Double-booking guard: warn (don't block) when the stand already has
        // an open movement. The client re-sends with confirm_conflict to override.
        $confirmConflict = filter_var($data['confirm_conflict'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$confirmConflict && $payload['parking_stand'] !== '') {
            $occupant = $this->movements->findOpenMovementOnStand(
                $payload['parking_stand'],
                $payload['id']
            );

            if ($occupant && strcasecmp((string) $occupant['registration'], $registration) !== 0) {
                return [
                    'success' => false,
                    'needs_confirmation' => true,
                    'conflict' => [
                        'stand' => strtoupper($payload['parking_stand']),
                        'registration' => $occupant['registration'],
                    ],
                    'message' => 'Stand ' . strtoupper($payload['parking_stand']) . ' is already occupied by '
                        . $occupant['registration'] . '.',
                ];
            }
        }

        $result = $this->movements->saveMovement($payload, $userId);
        $this->broadcaster()->bump('apron:savemovement');
        $validation = $this->movements->evaluateInputWarnings($payload);

        if ($predictionLogId && $payload['parking_stand'] !== '') {
            $this->recommendations->markPredictionOutcome($predictionLogId, $payload['parking_stand'], $userId);
        }

        if ($payload['category'] !== '') {
            try {
                $this->aircraftDetails->upsert($registration, [
                    'aircraft_type' => $payload['aircraft_type'] ?? null,
                    'operator_airline' => $payload['operator_airline'] ?? null,
                    'category' => $payload['category'],
                ]);
            } catch (Throwable $e) {
                error_log('ApronController::saveMovement category upsert warning: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => 'Movement saved successfully.',
            'id' => $result['id'],
            'is_new' => $result['is_new'],
            'prediction_log_id' => $predictionLogId,
            'warnings' => $validation['warnings'] ?? [],
            'duplicate_flights' => $validation['duplicate_flights'] ?? [],
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
            'category' => $detail->category() ?? '',
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

    public function movements(): Response
    {
        return Response::json([
            'success' => true,
            'movements' => $this->getCurrentMovements(),
            'freehand' => $this->freehand->all(),
            'timestamp' => date('c'),
        ]);
    }

    // ── Freehand positioning (event mode) ────────────────────────────

    public function freehandState(): Response
    {
        return Response::json([
            'success' => true,
            'freehand' => $this->freehand->all(),
        ]);
    }

    public function freehandUpdate(): Response
    {
        $request = $this->request();
        $data = $this->parsePayload($request);

        if (!$this->verifyCsrf($request)) {
            return $this->csrfFailureResponse();
        }

        if (!$this->hasRole(['admin', 'operator'])) {
            return $this->forbidden('Not authorized to change freehand positioning');
        }

        $view = strtolower(trim((string) ($data['view'] ?? 'a')));
        $action = strtolower(trim((string) ($data['freehand_action'] ?? '')));

        switch ($action) {
            case 'activate':
                $this->freehand->activate($view);
                break;
            case 'deactivate':
                $this->freehand->deactivate($view);
                break;
            case 'positions':
                $positions = $data['positions'] ?? [];
                $this->freehand->setPositions($view, is_array($positions) ? $positions : []);
                break;
            default:
                return Response::json(['success' => false, 'message' => 'Invalid freehand action.'], 400);
        }

        $this->broadcaster()->bump('apron:freehand');

        return Response::json([
            'success' => true,
            'freehand' => $this->freehand->all(),
        ]);
    }

    protected function broadcaster(): ApronChangeBroadcaster
    {
        return $this->app->make(ApronChangeBroadcaster::class);
    }

    /**
     * Server-Sent Events stream: pushes an `apron-update` event to every
     * connected browser tab whenever the apron data version changes.
     *
     * Streams directly and exits — it never returns through Response::send(),
     * because SSE requires incremental flushing which the buffered Response
     * class cannot do.
     */
    public function stream(): void
    {
        // Release the session lock immediately: PHP serialises requests that
        // share a session file, so holding it here would freeze every other
        // request (page loads, saves) from the same user until the stream ends.
        session_write_close();

        @set_time_limit(0);

        ignore_user_abort(false);

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Accel-Buffering: no');

        $broadcaster = $this->broadcaster();

        // Resume point: browser sends Last-Event-ID on reconnect, first
        // connections start from the current version.
        $lastEventId = $this->request()->header('Last-Event-Id');
        $knownVersion = is_numeric($lastEventId)
            ? (int) $lastEventId
            : $broadcaster->currentVersion();

        echo "retry: 3000\n\n";
        @flush();

        // End the stream after ~4 minutes (below common proxy/PHP timeouts);
        // EventSource reconnects automatically using the retry hint above.
        $deadline = time() + 240;
        $nextHeartbeat = time() + 15;

        while (time() < $deadline) {
            $current = $broadcaster->currentVersion();

            if ($current !== $knownVersion) {
                $knownVersion = $current;
                echo "id: {$current}\n";
                echo "event: apron-update\n";
                echo 'data: {"version":' . $current . '}' . "\n\n";
                @flush();
                $nextHeartbeat = time() + 15;
            } elseif (time() >= $nextHeartbeat) {
                // Comment line keeps the connection alive through proxies
                echo ": heartbeat\n\n";
                @flush();
                $nextHeartbeat = time() + 15;
            }

            if (connection_aborted()) {
                break;
            }

            usleep(1000000);
        }

        exit;
    }

    protected function forbidden(string $message): Response
    {
        return Response::json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    // ── ML recommendations (pipeline lives in RecommendationService) ─

    public function recommend(): Response
    {
        $request = $this->request();
        $payload = $this->parsePayload($request);

        if (!$this->verifyCsrf($request)) {
            return $this->csrfFailureResponse();
        }

        try {
            $input = $this->recommendations->validateInput($payload);
            $recommendation = $this->recommendations->recommend($input, $this->auth->id());

            return Response::json([
                'success' => true,
                'recommendations' => $recommendation['candidates'],
                'availability' => $recommendation['availability'],
                'raw_predictions' => $recommendation['raw_predictions'],
                'preferences' => $recommendation['preferences'],
                'metadata' => $recommendation['metadata'],
                'prediction_log_id' => $recommendation['prediction_log_id'] ?? null,
                'source' => $recommendation['source'],
                'notes' => $recommendation['notes'],
            ]);
        } catch (InvalidArgumentException $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (RuntimeException $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        } catch (Throwable $e) {
            error_log('ApronController::recommend error: ' . $e->getMessage());

            return Response::json([
                'success' => false,
                'message' => 'Unable to generate recommendations at this time.',
            ], 500);
        }
    }

    public function mlMetrics(): Response
    {
        $windowDays = 30;

        try {
            $model = $this->recommendations->getActiveModelVersion();

            /** @var PDO $pdo */
            $pdo = $this->app->make(PDO::class);

            $since = (new \DateTimeImmutable())->modify("-{$windowDays} days")->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare(
                'SELECT
                    COUNT(*) AS total_predictions,
                    SUM(CASE WHEN was_prediction_correct = 1 THEN 1 ELSE 0 END) AS correct_predictions,
                    MAX(prediction_date) AS last_prediction_at
                 FROM ml_prediction_log
                 WHERE prediction_date >= :since'
            );
            $stmt->execute([':since' => $since]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $recentStmt = $pdo->query(
                'SELECT prediction_token, prediction_date, aircraft_type, operator_airline, category, model_version, actual_stand_assigned, was_prediction_correct
                 FROM ml_prediction_log
                 ORDER BY prediction_date DESC
                 LIMIT 5'
            );
            $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $total = (int) ($row['total_predictions'] ?? 0);
            $correct = (int) ($row['correct_predictions'] ?? 0);
            $observed = $total > 0 ? round($correct / $total, 4) : null;

            return Response::json([
                'success' => true,
                'model' => [
                    'version' => $model['version_number'] ?? null,
                    'training_date' => $model['training_date'] ?? null,
                    'training_samples' => isset($model['training_samples']) ? (int) $model['training_samples'] : null,
                    'top3_accuracy_expected' => isset($model['top3_accuracy']) ? (float) $model['top3_accuracy'] : null,
                    'notes' => $model['notes'] ?? null,
                ],
                'observed' => [
                    'window_days' => $windowDays,
                    'total_predictions' => $total,
                    'correct_predictions' => $correct,
                    'observed_top3_accuracy' => $observed,
                    'last_prediction_at' => $row['last_prediction_at'] ?? null,
                ],
                'recent' => $recent,
            ]);
        } catch (Throwable $e) {
            error_log('ApronController::mlMetrics error: ' . $e->getMessage());

            return Response::json([
                'success' => true,
                'model' => [
                    'version' => null,
                    'training_date' => null,
                    'training_samples' => null,
                    'top3_accuracy_expected' => null,
                    'notes' => null,
                ],
                'observed' => [
                    'window_days' => $windowDays,
                    'total_predictions' => 0,
                    'correct_predictions' => 0,
                    'observed_top3_accuracy' => null,
                    'last_prediction_at' => null,
                ],
                'recent' => [],
                'message' => 'ML metrics are unavailable right now.',
            ]);
        }
    }

    public function mlPredictionLog(): Response
    {
        $request = $this->request();
        $limit = (int) $request->query('limit', 50);
        $limit = max(10, min(200, $limit));
        $filter = strtolower(trim((string) $request->query('result', 'all')));
        $search = trim((string) $request->query('search', ''));

        $conditions = [];
        $params = [];

        if ($filter === 'hit') {
            $conditions[] = 'was_prediction_correct = 1';
        } elseif ($filter === 'miss') {
            $conditions[] = 'was_prediction_correct = 0';
        } elseif ($filter === 'pending') {
            $conditions[] = 'was_prediction_correct IS NULL';
        }

        if ($search !== '') {
            $conditions[] = '(aircraft_type LIKE :term OR operator_airline LIKE :term OR category LIKE :term)';
            $params[':term'] = '%' . $search . '%';
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        try {
            /** @var PDO $pdo */
            $pdo = $this->app->make(PDO::class);
            $sql = "
                SELECT
                    id,
                    prediction_token,
                    prediction_date,
                    aircraft_type,
                    operator_airline,
                    category,
                    model_version,
                    predicted_stands,
                    actual_stand_assigned,
                    was_prediction_correct
                FROM ml_prediction_log
                {$whereSql}
                ORDER BY prediction_date DESC
                LIMIT :limit
            ";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $logs = [];

            foreach ($rows as $row) {
                $predictions = [];
                $decoded = json_decode($row['predicted_stands'] ?? '[]', true);
                if (is_array($decoded)) {
                    foreach ($decoded as $index => $prediction) {
                        if ($index >= 3) {
                            break;
                        }
                        $stand = strtoupper((string) ($prediction['stand'] ?? ''));
                        if ($stand === '') {
                            continue;
                        }
                        $predictions[] = [
                            'stand' => $stand,
                            'probability' => isset($prediction['probability']) ? (float) $prediction['probability'] : null,
                            'rank' => $index + 1,
                        ];
                    }
                }

                $wasCorrect = $row['was_prediction_correct'];
                $resultLabel = $wasCorrect === null ? 'pending' : ((int) $wasCorrect === 1 ? 'hit' : 'miss');

                $logs[] = [
                    'id' => (int) $row['id'],
                    'token' => $row['prediction_token'],
                    'prediction_date' => $row['prediction_date'],
                    'aircraft_type' => $row['aircraft_type'],
                    'operator_airline' => $row['operator_airline'],
                    'category' => $row['category'],
                    'model_version' => $row['model_version'],
                    'predictions' => $predictions,
                    'actual_stand' => $row['actual_stand_assigned'],
                    'result' => $resultLabel,
                ];
            }

            return Response::json([
                'success' => true,
                'logs' => $logs,
            ]);
        } catch (Throwable $e) {
            error_log('ApronController::mlPredictionLog error: ' . $e->getMessage());
            return Response::json([
                'success' => true,
                'logs' => [],
                'message' => 'Prediction log is unavailable right now.',
            ]);
        }
    }
}
