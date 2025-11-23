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

use App\Repositories\StandRepository;

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

    protected DailyStaffRosterRepository $rosters;

    protected AircraftMovementRepository $movements;

    protected FlightReferenceRepository $flightReferences;

    protected AircraftDetailRepository $aircraftDetails;

    protected StandRepository $stands;

    protected array $airlinePreferenceCache = [];
    protected array $historicalPreferenceCache = [];
    protected ?array $activeModelVersion = null;



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

        $this->stands = $app->make(StandRepository::class);

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

        error_log('--- ApronController::saveMovement() called ---');

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



        $result = $this->movements->saveMovement($payload, $userId);

        if ($predictionLogId && $payload['parking_stand'] !== '') {
            $this->markPredictionOutcome($predictionLogId, $payload['parking_stand'], $userId);
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

    public function movements(): Response

    {

        return Response::json([
            'success' => true,
            'movements' => $this->getCurrentMovements(),
            'timestamp' => date('c')
        ]);

    }



    protected function forbidden(string $message): Response

    {

        return Response::json([

            'success' => false,

            'message' => $message,

        ], 403);

    }



    public function recommend(): Response

    {

        error_log('--- ApronController::recommend() called ---');

        $request = $this->request();

        $payload = $this->parsePayload($request);



        try {

            $input = $this->validateRecommendationInput($payload);

            $recommendation = $this->getStandRecommendations($input);



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



    protected function validateRecommendationInput(array $payload): array

    {

        $aircraftType = strtoupper(trim((string) ($payload['aircraft_type'] ?? '')));

        $operator = strtoupper(trim((string) ($payload['operator_airline'] ?? '')));

        $categoryRaw = trim((string) ($payload['category'] ?? ''));

        $category = $categoryRaw !== '' ? ucfirst(strtolower($categoryRaw)) : '';



        if ($aircraftType === '') {

            throw new InvalidArgumentException('Aircraft type is required.');

        }



        if ($operator === '') {

            throw new InvalidArgumentException('Operator airline is required.');

        }



        if ($category === '') {

            throw new InvalidArgumentException('Movement category is required.');

        }



        return [

            'aircraft_type' => $aircraftType,

            'operator_airline' => $operator,

            'category' => $category,

        ];

    }



    protected function getStandRecommendations(array $input): array

    {

        error_log('--- ApronController::getStandRecommendations() called ---');

        $predictor = $this->callPythonPredictor($input);



        if (empty($predictor['success'])) {

            $message = $predictor['error'] ?? 'Prediction script returned an error.';

            throw new RuntimeException($message);

        }



        $availability = $this->getAvailableStands();

        $preferences = $this->getAirlinePreferences(
            $input['operator_airline'],
            $input['category'],
            $input['aircraft_type'],
            $availability['available'] ?? []
        );

        $rules = $this->applyBusinessRules($predictor['predictions'] ?? [], $availability, $preferences, $input['aircraft_type']);
        $performance = $this->getModelPerformanceSummary();
        $modelInfo = $this->getActiveModelVersion();

        $notes = $rules['notes'];
        if (!empty($performance['top3_accuracy_percent'])) {
            $notes .= ' Latest evaluated top-3 accuracy: ' . $performance['top3_accuracy_percent'] . ' (target 70%).';
        }

        if (!empty($modelInfo['version_number'])) {
            $performance['model_version'] = $modelInfo['version_number'];
            if (!empty($modelInfo['training_date'])) {
                $performance['model_training_date'] = $modelInfo['training_date'];
            }
        }

        $metadata = array_merge($predictor['metadata'] ?? [], $performance);

        $response = [
            'candidates' => $rules['results'],
            'source' => $rules['source'],
            'notes' => $notes,
            'availability' => $availability,
            'raw_predictions' => $predictor['predictions'] ?? [],
            'preferences' => $preferences,
            'metadata' => $metadata,
        ];

        $logId = $this->recordPredictionLog(
            $input,
            $response,
            $modelInfo,
            $this->auth->id()
        );

        $response['prediction_log_id'] = $logId;

        return $response;

    }




    protected function callPythonPredictor(array $payload, int $timeoutSeconds = 6): array
    {
        $python = $this->resolvePythonBinary();
        $scriptPath = $this->app->basePath('ml/predict.py');

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        $command = sprintf(
            '%s %s 2>&1',
            $python,
            escapeshellarg($scriptPath)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start prediction process.');
        }

        fwrite($pipes[0], $jsonPayload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $returnVar = proc_close($process);

        if ($stdout === '' || $stdout === false) {
            $errorMsg = 'Predictor returned no output.';
            if ($stderr !== '' && $stderr !== false) {
                $errorMsg .= ' stderr: ' . $stderr;
            }
            throw new RuntimeException($errorMsg);
        }

        $response = json_decode($stdout, true);
        if (!is_array($response)) {
            throw new RuntimeException('Invalid response from predictor: ' . $stdout);
        }

        if ($returnVar !== 0 && empty($response['success'])) {
            $message = isset($response['error'])
                ? (string) $response['error']
                : 'Prediction script exited with an error.';
            if ($stderr !== '' && $stderr !== false) {
                $message .= ' stderr: ' . $stderr;
            }
            throw new RuntimeException($message);
        }

        return $response;
    }




    protected function resolvePythonBinary(): string

    {

        $configured = (string) $this->app->config('ml.python_path', '');

        $candidates = array_filter([

            $configured,

            'python',

            'python3',

            'py -3',

        ]);



        foreach ($candidates as $candidate) {

            $binary = trim($candidate);

            if ($binary === '') {

                continue;

            }



            // Allow commands with arguments (e.g. py -3)

            $parts = explode(' ', $binary);

            $executable = $parts[0];

            if (is_executable($executable) || $this->commandExists($executable)) {

                return $binary;

            }

        }



        return 'python';

    }



    protected function commandExists(string $command): bool

    {

        $utility = stripos(PHP_OS_FAMILY, 'Windows') === 0 ? 'where' : 'which';

        $result = shell_exec($utility . ' ' . escapeshellarg($command));



        return is_string($result) && trim($result) !== '';

    }



    protected function applyBusinessRules(array $predictions, array $availability, array $preferences, string $aircraftType = ''): array

    {

        $available = array_map('strtoupper', $availability['available'] ?? []);

        $occupied = array_map('strtoupper', $availability['occupied'] ?? []);

        $isSmall = $this->isSmallAircraft($aircraftType);



        $candidates = [];

        foreach ($predictions as $row) {

            $stand = strtoupper((string) ($row['stand'] ?? ''));

            if ($stand === '' || !in_array($stand, $available, true)) {

                continue;

            }

            // CRITICAL BUSINESS RULE: A0 only for small aircraft
            if ($stand === 'A0' && !$isSmall) {

                continue; // Skip A0 for standard aircraft

            }



            $probability = (float) ($row['probability'] ?? 0.0);

            $preference = (float) ($preferences[$stand] ?? 0.0);
            $normalizedPreference = max(0.0, min(1.0, $preference / 100));
            $score = (0.6 * $probability) + (0.4 * $normalizedPreference);



            $candidates[] = [

                'stand' => $stand,

                'probability' => $probability,

                'preference_score' => $preference,

                'composite_score' => $score,

            ];

        }



        if (empty($candidates)) {

            return [

                'source' => 'fallback',

                'results' => $this->getFallbackStands($available, $predictions, $occupied, $aircraftType),

                'notes' => 'Model predictions were filtered out by availability; provided fallback stands.',

            ];

        }



        $ranked = $this->rankStandsByPreference($candidates, $preferences);

        // ENSURE WE ALWAYS HAVE 3 RECOMMENDATIONS
        // If filtering reduced candidates to <3, fill with additional available stands
        if (count($ranked) < 3) {
            $existingStands = array_map(function($r) { return $r['stand']; }, $ranked);
            $additionalNeeded = 3 - count($ranked);

            foreach ($available as $stand) {
                if (in_array($stand, $existingStands, true)) {
                    continue; // Skip already recommended stands
                }

                // CRITICAL BUSINESS RULE: A0 only for small aircraft
                if ($stand === 'A0' && !$isSmall) {
                    continue; // Skip A0 for standard aircraft
                }

                // Add this stand with preference-based scoring
                $preference = (float) ($preferences[$stand] ?? 0.0);
                $ranked[] = [
                    'stand' => $stand,
                    'probability' => null, // No ML probability (wasn't in top-3)
                    'preference_score' => $preference,
                    'composite_score' => $preference / 100, // Use preference as score
                ];

                if (--$additionalNeeded <= 0) {
                    break;
                }
            }
        }

        return [

            'source' => 'model',

            'results' => array_slice($ranked, 0, 3), // Ensure exactly 3

            'notes' => 'Recommendations filtered by availability and airline preferences.',

        ];

    }



    /**
     * Check if aircraft is small enough for A0 stand
     * A0 can only accommodate small aircraft like Cessna, Pilatus, etc.
     */
    protected function isSmallAircraft(string $aircraftType): bool
    {
        $aircraftUpper = strtoupper(str_replace(' ', '', $aircraftType));

        // List of A0-compatible small aircraft
        $smallAircraftPatterns = [
            'C152', 'C172', 'C182', 'C185', 'C206', 'C208',
            'C402', 'C404', 'C425',
            'PC6', 'PC12',
            'CESSNA',
            'PILATUS',
        ];

        foreach ($smallAircraftPatterns as $pattern) {
            if (strpos($aircraftUpper, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function getModelPerformanceSummary(): array

    {

        $metricsPath = $this->app->basePath('reports/phase5_metrics.json');

        if (!is_file($metricsPath)) {

            return [];

        }



        try {

            $metrics = json_decode((string) file_get_contents($metricsPath), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($metrics)) {

                return [];

            }



            $top3 = isset($metrics['top3_accuracy']) ? (float) $metrics['top3_accuracy'] : null;

            return [

                'top3_accuracy' => $top3,

                'top3_accuracy_percent' => $top3 !== null ? number_format($top3 * 100, 1) . '%' : null,

                'model_timestamp' => $metrics['timestamp'] ?? null,

            ];

        } catch (Throwable $e) {

            error_log('ApronController::getModelPerformanceSummary warning: ' . $e->getMessage());

            return [];

        }

    }



    
    protected function getActiveModelVersion(): array

    {

        if ($this->activeModelVersion !== null) {

            return $this->activeModelVersion;

        }



        try {

            /** @var PDO $pdo */

            $pdo = $this->app->make(PDO::class);

            $stmt = $pdo->query(

                'SELECT id, version_number, training_date, training_samples, top3_accuracy, model_file_path, is_active
                 FROM ml_model_versions
                 ORDER BY is_active DESC, training_date DESC
                 LIMIT 1'

            );



            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (empty($row)) {

                $row = [];

            }



            return $this->activeModelVersion = $row;

        } catch (Throwable $e) {

            error_log('ApronController::getActiveModelVersion warning: ' . $e->getMessage());

            return $this->activeModelVersion = [];

        }

    }



    protected function recordPredictionLog(array $input, array $recommendation, array $modelInfo, ?int $userId): ?int

    {

        error_log('--- ApronController::recordPredictionLog() called ---');

        try {

            /** @var PDO $pdo */

            $pdo = $this->app->make(PDO::class);



            $predictedJson = json_encode($recommendation['raw_predictions'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($predictedJson === false) {

                $predictedJson = '[]';

            }



            $payloadJson = json_encode([

                'candidates' => $recommendation['candidates'] ?? [],

                'availability' => $recommendation['availability'] ?? [],

                'preferences' => $recommendation['preferences'] ?? [],

                'metadata' => $recommendation['metadata'] ?? [],

                'source' => $recommendation['source'] ?? '',

                'notes' => $recommendation['notes'] ?? '',

                'input' => $input,

            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($payloadJson === false) {

                $payloadJson = '{}';

            }



            $stmt = $pdo->prepare(

                'INSERT INTO ml_prediction_log
                    (prediction_token, aircraft_type, operator_airline, category, predicted_stands, recommendation_payload, model_version, requested_by_user)
                 VALUES
                    (:token, :aircraft_type, :operator_airline, :category, :predicted, :payload, :model_version, :requested_by_user)'

            );



            $stmt->execute([

                ':token' => $this->generatePredictionToken(),

                ':aircraft_type' => strtoupper($input['aircraft_type']),

                ':operator_airline' => strtoupper($input['operator_airline']),

                ':category' => strtoupper($input['category']),

                ':predicted' => $predictedJson,

                ':payload' => $payloadJson,

                ':model_version' => $modelInfo['version_number'] ?? null,

                ':requested_by_user' => $userId,

            ]);



            $lastInsertId = (int) $pdo->lastInsertId();
            error_log('--- Prediction log recorded with ID: ' . $lastInsertId . ' ---');
            return $lastInsertId;

        } catch (Throwable $e) {

            error_log('ApronController::recordPredictionLog warning: ' . $e->getMessage());

            return null;

        }

    }



    protected function markPredictionOutcome(int $logId, string $actualStand, int $userId): void

    {

        error_log('--- ApronController::markPredictionOutcome() called for log ID: ' . $logId . ' ---');

        $actualStand = strtoupper(trim($actualStand));



        if ($logId <= 0 || $actualStand === '') {

            error_log('--- markPredictionOutcome() exiting: Invalid logId or actualStand ---');
            return;

        }



        try {

            /** @var PDO $pdo */

            $pdo = $this->app->make(PDO::class);



            $stmt = $pdo->prepare('SELECT predicted_stands FROM ml_prediction_log WHERE id = :id');

            $stmt->execute([':id' => $logId]);

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {

                error_log('--- markPredictionOutcome() exiting: Log record not found for ID: ' . $logId . ' ---');
                return;

            }



            $predictions = json_decode($record['predicted_stands'] ?? '[]', true);

            $topStands = [];

            if (is_array($predictions)) {

                foreach ($predictions as $row) {

                    $stand = strtoupper((string) ($row['stand'] ?? ''));

                    if ($stand === '') {

                        continue;

                    }

                    $topStands[] = $stand;

                }

            }



            $wasCorrect = !empty($topStands) ? in_array($actualStand, $topStands, true) : null;
            error_log('--- markPredictionOutcome() - Stand: ' . $actualStand . ' | Top Stands: ' . implode(', ', $topStands) . ' | Correct: ' . ($wasCorrect ? 'yes' : 'no') . ' ---');


            $update = $pdo->prepare(

                'UPDATE ml_prediction_log
                 SET actual_stand_assigned = :stand,
                     was_prediction_correct = :correct,
                     actual_recorded_at = NOW(),
                     assigned_by_user = :user
                 WHERE id = :id'

            );



            $update->execute([

                ':stand' => $actualStand,

                ':correct' => $wasCorrect,

                ':user' => $userId,

                ':id' => $logId,

            ]);

            error_log('--- markPredictionOutcome() successfully updated log ID: ' . $logId . ' ---');

        } catch (Throwable $e) {

            error_log('ApronController::markPredictionOutcome warning: ' . $e->getMessage());

        }

    }




    protected function generatePredictionToken(): string

    {

        try {

            return bin2hex(random_bytes(16));

        } catch (Throwable $e) {

            return uniqid('pred_', true);

        }

    }



    public function mlMetrics(): Response

    {

        $windowDays = 30;

        try {

            $model = $this->getActiveModelVersion();

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

                'success' => false,

                'message' => 'Unable to fetch ML metrics at this time.',

            ], 500);

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
                'success' => false,
                'message' => 'Unable to fetch prediction logs.',
            ], 500);
        }
    }



    protected function getAvailableStands(): array

    {

        $standNames = [];

        try {

            foreach ($this->stands->listActive() as $stand) {

                $standNames[] = strtoupper($stand->name());

            }

        } catch (Throwable $e) {

            error_log('ApronController::getAvailableStands warning: ' . $e->getMessage());

        }

        if (empty($standNames)) {
            $standNames = $this->getDefaultStandCodes();
        }



        $occupancy = [];

        foreach ($this->movements->findCurrentApronMovements() as $movement) {

            $stand = strtoupper((string) ($movement['parking_stand'] ?? ''));

            if ($stand === '') {

                continue;

            }



            $offBlock = trim((string) ($movement['off_block_time'] ?? ''));

            $isRon = (int) ($movement['is_ron'] ?? 0);

            $ronComplete = (int) ($movement['ron_complete'] ?? 0);



            if ($offBlock === '' || ($isRon === 1 && $ronComplete === 0)) {

                $occupancy[$stand] = true;

            }

        }



        $occupied = array_keys($occupancy);

        $available = array_values(array_diff($standNames, $occupied));



        return [

            'available' => $available,

            'occupied' => $occupied,

            'timestamp' => date(DATE_ATOM),

        ];

    }



    protected function getAirlinePreferences(
        string $airline,
        string $category,
        string $aircraftType,
        array $available = []
    ): array {
        $airline = strtoupper(trim($airline));
        $categoryCode = $this->normalizePreferenceCategory($category);
        $aircraftType = strtoupper(trim($aircraftType));

        $cacheKey = implode('|', [
            $airline !== '' ? $airline : 'UNKNOWN',
            $categoryCode,
            $aircraftType !== '' ? $aircraftType : '*',
        ]);

        if (isset($this->airlinePreferenceCache[$cacheKey])) {
            return $this->airlinePreferenceCache[$cacheKey];
        }

        $preferences = $this->queryAirlinePreferences($airline, $categoryCode, $aircraftType);

        if (empty($preferences)) {
            $preferences = $this->fetchHistoricalPreferences($categoryCode);
        }

        if (empty($preferences) && $categoryCode !== 'CHARTER') {
            $preferences = $this->fetchHistoricalPreferences('CHARTER');
        }

        if (empty($preferences) && !empty($available)) {
            $preferences = $this->buildAvailabilityFallbackScores($available);
        }

        return $this->airlinePreferenceCache[$cacheKey] = $preferences;
    }



    protected function queryAirlinePreferences(string $airline, string $categoryCode, string $aircraftType): array
    {
        if ($airline === '') {
            return [];
        }

        try {
            /** @var PDO $pdo */
            $pdo = $this->app->make(PDO::class);

            $conditions = ['active = 1'];
            $params = [];

            $conditions[] = '(airline_name = :airline_exact OR airline_name LIKE :airline_like)';
            $params[':airline_exact'] = $airline;
            $params[':airline_like'] = $airline . '%';

            if ($categoryCode !== '') {
                $conditions[] = 'airline_category = :category';
                $params[':category'] = $categoryCode;
            }

            if ($aircraftType !== '') {
                $conditions[] = '(aircraft_type = :aircraft_type OR aircraft_type IS NULL OR aircraft_type = \'\')';
                $params[':aircraft_type'] = $aircraftType;
            }

            $sql = sprintf(
                'SELECT stand_name, priority_score FROM airline_preferences WHERE %s ORDER BY priority_score DESC, stand_name ASC',
                implode(' AND ', $conditions)
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $preferences = [];
            foreach ($rows as $row) {
                $stand = strtoupper((string) ($row['stand_name'] ?? ''));
                if ($stand === '') {
                    continue;
                }
                $preferences[$stand] = (float) ($row['priority_score'] ?? 0.0);
            }

            return $preferences;
        } catch (Throwable $e) {
            error_log('ApronController::queryAirlinePreferences warning: ' . $e->getMessage());

            return [];
        }
    }



    protected function fetchHistoricalPreferences(string $categoryCode): array
    {
        $categoryCode = $categoryCode !== '' ? $categoryCode : 'CHARTER';

        if (isset($this->historicalPreferenceCache[$categoryCode])) {
            return $this->historicalPreferenceCache[$categoryCode];
        }

        // Try to load from precomputed cache file first
        $cacheFile = __DIR__ . '/../../storage/cache/historical_preferences.json';
        if (file_exists($cacheFile)) {
            try {
                $cacheData = json_decode(file_get_contents($cacheFile), true);
                if (isset($cacheData['preferences'][$categoryCode])) {
                    $preferences = [];
                    foreach ($cacheData['preferences'][$categoryCode] as $stand => $data) {
                        $preferences[$stand] = (float) ($data['score'] ?? 0.0);
                    }
                    return $this->historicalPreferenceCache[$categoryCode] = $preferences;
                }
            } catch (Throwable $e) {
                error_log('ApronController: Failed to load preference cache: ' . $e->getMessage());
                // Fall through to database query
            }
        }

        // Fallback to database query if cache not available
        try {
            /** @var PDO $pdo */
            $pdo = $this->app->make(PDO::class);
            $stmt = $pdo->prepare(
                "SELECT UPPER(am.parking_stand) AS stand, COUNT(*) AS usage_count
                 FROM aircraft_movements am
                 LEFT JOIN aircraft_details ad ON am.registration = ad.registration
                 WHERE am.parking_stand IS NOT NULL
                   AND am.parking_stand != ''
                   AND UPPER(COALESCE(ad.category, 'CHARTER')) = :category
                 GROUP BY stand
                 HAVING usage_count > 0
                 ORDER BY usage_count DESC"
            );
            $stmt->execute([':category' => $categoryCode]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (empty($rows)) {
                return $this->historicalPreferenceCache[$categoryCode] = [];
            }

            $maxUsage = (float) max(array_column($rows, 'usage_count'));
            if ($maxUsage <= 0) {
                return $this->historicalPreferenceCache[$categoryCode] = [];
            }

            $preferences = [];
            foreach ($rows as $row) {
                $stand = strtoupper((string) ($row['stand'] ?? ''));
                if ($stand === '') {
                    continue;
                }
                $count = (float) ($row['usage_count'] ?? 0.0);
                $preferences[$stand] = (float) round(($count / $maxUsage) * 100, 2);
            }

            return $this->historicalPreferenceCache[$categoryCode] = $preferences;
        } catch (Throwable $e) {
            error_log('ApronController::fetchHistoricalPreferences warning: ' . $e->getMessage());

            return [];
        }
    }



    protected function buildAvailabilityFallbackScores(array $available): array
    {
        if (empty($available)) {
            return [];
        }

        $scores = [];
        $total = count($available);
        $step = $total > 1 ? (int) floor(100 / ($total - 1)) : 0;
        $current = 100;

        foreach ($available as $index => $stand) {
            $scores[strtoupper($stand)] = max(10, $current);
            $current -= $step;
        }

        return $scores;
    }



    protected function normalizePreferenceCategory(string $category): string
    {
        $normalized = strtoupper(trim($category));
        $map = [
            'COMMERCIAL' => 'COMMERCIAL',
            'KOMERSIAL' => 'COMMERCIAL',
            'DOMESTIC' => 'COMMERCIAL',
            'DOMESTIK' => 'COMMERCIAL',
            'PASSENGER' => 'COMMERCIAL',
            'PAX' => 'COMMERCIAL',
            'INTERNATIONAL' => 'COMMERCIAL',
            'CHARTER' => 'CHARTER',
            'VIP' => 'CHARTER',
            'GA' => 'CHARTER',
            'GENERAL AVIATION' => 'CHARTER',
            'CARGO' => 'CARGO',
            'FREIGHT' => 'CARGO',
            'LOGISTICS' => 'CARGO',
        ];

        if ($normalized === '') {
            return 'CHARTER';
        }

        return $map[$normalized] ?? $normalized;
    }



    protected function getDefaultStandCodes(): array
    {
        return [
            'A0', 'A1', 'A2', 'A3',
            'B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10', 'B11', 'B12', 'B13',
            'SA01', 'SA02', 'SA03', 'SA04', 'SA05', 'SA06', 'SA07', 'SA08', 'SA09', 'SA10', 'SA11', 'SA12',
            'SA13', 'SA14', 'SA15', 'SA16', 'SA17', 'SA18', 'SA19', 'SA20', 'SA21', 'SA22', 'SA23', 'SA24',
            'SA25', 'SA26', 'SA27', 'SA28', 'SA29', 'SA30',
            'NSA01', 'NSA02', 'NSA03', 'NSA04', 'NSA05', 'NSA06', 'NSA07', 'NSA08', 'NSA09', 'NSA10', 'NSA11',
            'NSA12', 'NSA13', 'NSA14', 'NSA15',
            'WR01', 'WR02', 'WR03',
            'RE01', 'RE02', 'RE03', 'RE04', 'RE05', 'RE06', 'RE07',
            'RW01', 'RW02', 'RW03', 'RW04', 'RW05', 'RW06', 'RW07', 'RW08', 'RW09', 'RW10', 'RW11',
            'C1', 'C2', 'C3',
            'HGR'
        ];
    }



    protected function rankStandsByPreference(array $candidates, array $preferences): array

    {

        usort($candidates, static function (array $left, array $right): int {

            $scoreComparison = $right['composite_score'] <=> $left['composite_score'];

            if ($scoreComparison !== 0) {

                return $scoreComparison;

            }



            return $right['probability'] <=> $left['probability'];

        });



        $ranked = [];

        foreach ($candidates as $index => $row) {

            $ranked[] = [

                'stand' => $row['stand'],

                'rank' => $index + 1,

                'probability' => $row['probability'],

                'preference_score' => $row['preference_score'],

                'composite_score' => $row['composite_score'],

            ];

        }



        return array_slice($ranked, 0, 3);

    }



    protected function getFallbackStands(array $available, array $predictions, array $occupied, string $aircraftType = ''): array

    {

        $fallback = [];

        $seen = [];

        $isSmall = $this->isSmallAircraft($aircraftType);



        foreach ($available as $stand) {

            // CRITICAL BUSINESS RULE: A0 only for small aircraft
            if ($stand === 'A0' && !$isSmall) {
                continue; // Skip A0 for standard aircraft
            }

            $fallback[] = [

                'stand' => $stand,

                'rank' => count($fallback) + 1,

                'probability' => null,

                'preference_score' => 0.0,

                'composite_score' => 0.0,

            ];

            $seen[$stand] = true;



            if (count($fallback) >= 3) {

                return $fallback;

            }

        }



        foreach ($predictions as $row) {

            $stand = strtoupper((string) ($row['stand'] ?? ''));

            if ($stand === '' || isset($seen[$stand])) {

                continue;

            }



            $fallback[] = [

                'stand' => $stand,

                'rank' => count($fallback) + 1,

                'probability' => (float) ($row['probability'] ?? 0.0),

                'preference_score' => 0.0,

                'composite_score' => (float) ($row['probability'] ?? 0.0),

            ];

            $seen[$stand] = true;



            if (count($fallback) >= 3) {

                break;

            }

        }



        if (count($fallback) < 3) {

            foreach ($occupied as $stand) {

                if (isset($seen[$stand])) {

                    continue;

                }



                $fallback[] = [

                    'stand' => $stand,

                    'rank' => count($fallback) + 1,

                    'probability' => null,

                    'preference_score' => 0.0,

                    'composite_score' => 0.0,

                ];

                $seen[$stand] = true;



                if (count($fallback) >= 3) {

                    break;

                }

            }

        }



        $fallback = array_slice($fallback, 0, 3);

        foreach ($fallback as $index => &$row) {

            $row['rank'] = $index + 1;

        }

        unset($row);



        return $fallback;

    }

}





