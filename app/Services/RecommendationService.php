<?php

namespace App\Services;

use App\Core\Application;
use App\Repositories\AircraftMovementRepository;
use App\Repositories\StandRepository;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * ML stand-recommendation pipeline, extracted from ApronController.
 *
 * Owns: the Python predictor bridge, business rules (A0 small-aircraft
 * restriction, availability filtering), airline preference scoring,
 * prediction logging and hit/miss outcome tracking.
 */
class RecommendationService
{
    protected Application $app;
    protected StandRepository $stands;
    protected AircraftMovementRepository $movements;

    protected array $airlinePreferenceCache = [];
    protected array $historicalPreferenceCache = [];
    protected ?array $activeModelVersion = null;

    public function __construct(Application $app, StandRepository $stands, AircraftMovementRepository $movements)
    {
        $this->app = $app;
        $this->stands = $stands;
        $this->movements = $movements;
    }

    protected function pdo(): PDO
    {
        return $this->app->make(PDO::class);
    }

    // ── Input validation ─────────────────────────────────────────────

    public function validateInput(array $payload): array
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

    // ── Main pipeline ────────────────────────────────────────────────

    public function recommend(array $input, ?int $userId): array
    {
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

        $response['prediction_log_id'] = $this->recordPredictionLog($input, $response, $modelInfo, $userId);

        return $response;
    }

    // ── Python predictor bridge ──────────────────────────────────────

    protected function callPythonPredictor(array $payload, int $timeoutSeconds = 6): array
    {
        $python = $this->resolvePythonBinary();
        $scriptPath = $this->app->basePath('ml/predict.py');

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        $command = sprintf('%s %s', $python, escapeshellarg($scriptPath));

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

        $stdout = '';
        $stderr = '';
        $returnVar = $this->collectProcessOutput($process, $pipes, $stdout, $stderr, $timeoutSeconds);

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

    protected function collectProcessOutput($process, array $pipes, string &$stdout, string &$stderr, int $timeoutSeconds): int
    {
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdoutOpen = true;
        $stderrOpen = true;
        $deadline = microtime(true) + max(1, $timeoutSeconds);

        while ($stdoutOpen || $stderrOpen) {
            $read = [];

            if ($stdoutOpen) {
                $read[] = $pipes[1];
            }

            if ($stderrOpen) {
                $read[] = $pipes[2];
            }

            if ($read === []) {
                break;
            }

            $secondsLeft = $deadline - microtime(true);
            if ($secondsLeft <= 0) {
                proc_terminate($process);
                $this->closeOpenPipes($pipes);
                proc_close($process);
                throw new RuntimeException('Prediction process timed out after ' . $timeoutSeconds . ' seconds.');
            }

            $sec = (int) floor($secondsLeft);
            $usec = (int) max(1, ($secondsLeft - $sec) * 1000000);
            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, $sec, $usec);

            if ($ready === false) {
                break;
            }

            if ($ready === 0) {
                continue;
            }

            foreach ($read as $stream) {
                $chunk = stream_get_contents($stream);
                if ($chunk !== false && $chunk !== '') {
                    if ($stream === $pipes[1]) {
                        $stdout .= $chunk;
                    } else {
                        $stderr .= $chunk;
                    }
                }

                if (feof($stream)) {
                    fclose($stream);
                    if ($stream === $pipes[1]) {
                        $stdoutOpen = false;
                    } else {
                        $stderrOpen = false;
                    }
                }
            }
        }

        $this->closeOpenPipes($pipes);

        return proc_close($process);
    }

    protected function closeOpenPipes(array $pipes): void
    {
        foreach ([1, 2] as $index) {
            if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                fclose($pipes[$index]);
            }
        }
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

    // ── Business rules ───────────────────────────────────────────────

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
                continue;
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

        $ranked = $this->rankStandsByPreference($candidates);

        // Ensure we always return 3 recommendations: top up from availability
        if (count($ranked) < 3) {
            $existingStands = array_map(static fn($r) => $r['stand'], $ranked);
            $additionalNeeded = 3 - count($ranked);

            foreach ($available as $stand) {
                if (in_array($stand, $existingStands, true)) {
                    continue;
                }

                if ($stand === 'A0' && !$isSmall) {
                    continue;
                }

                $preference = (float) ($preferences[$stand] ?? 0.0);
                $ranked[] = [
                    'stand' => $stand,
                    'probability' => null, // no ML probability (wasn't in top-3)
                    'preference_score' => $preference,
                    'composite_score' => $preference / 100,
                ];

                if (--$additionalNeeded <= 0) {
                    break;
                }
            }
        }

        return [
            'source' => 'model',
            'results' => array_slice($ranked, 0, 3),
            'notes' => 'Recommendations filtered by availability and airline preferences.',
        ];
    }

    /**
     * A0 can only accommodate small aircraft (Cessna, Pilatus, ...).
     */
    protected function isSmallAircraft(string $aircraftType): bool
    {
        $aircraftUpper = strtoupper(str_replace(' ', '', $aircraftType));

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

    protected function rankStandsByPreference(array $candidates): array
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
            if ($stand === 'A0' && !$isSmall) {
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

    // ── Availability & preferences ───────────────────────────────────

    public function getAvailableStands(): array
    {
        $standNames = [];

        try {
            foreach ($this->stands->listActive() as $stand) {
                $standNames[] = strtoupper($stand->name());
            }
        } catch (Throwable $e) {
            error_log('RecommendationService::getAvailableStands warning: ' . $e->getMessage());
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
            $pdo = $this->pdo();

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
            error_log('RecommendationService::queryAirlinePreferences warning: ' . $e->getMessage());

            return [];
        }
    }

    protected function fetchHistoricalPreferences(string $categoryCode): array
    {
        $categoryCode = $categoryCode !== '' ? $categoryCode : 'CHARTER';

        if (isset($this->historicalPreferenceCache[$categoryCode])) {
            return $this->historicalPreferenceCache[$categoryCode];
        }

        // Precomputed cache file first
        $cacheFile = $this->app->basePath('storage/cache/historical_preferences.json');
        if (file_exists($cacheFile)) {
            try {
                $cacheData = json_decode((string) file_get_contents($cacheFile), true);
                if (isset($cacheData['preferences'][$categoryCode])) {
                    $preferences = [];
                    foreach ($cacheData['preferences'][$categoryCode] as $stand => $data) {
                        $preferences[$stand] = (float) ($data['score'] ?? 0.0);
                    }
                    return $this->historicalPreferenceCache[$categoryCode] = $preferences;
                }
            } catch (Throwable $e) {
                error_log('RecommendationService: failed to load preference cache: ' . $e->getMessage());
                // fall through to database query
            }
        }

        try {
            $pdo = $this->pdo();
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
            error_log('RecommendationService::fetchHistoricalPreferences warning: ' . $e->getMessage());

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

        foreach ($available as $stand) {
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

    // ── Model metadata ───────────────────────────────────────────────

    public function getModelPerformanceSummary(): array
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
            error_log('RecommendationService::getModelPerformanceSummary warning: ' . $e->getMessage());

            return [];
        }
    }

    public function getActiveModelVersion(): array
    {
        if ($this->activeModelVersion !== null) {
            return $this->activeModelVersion;
        }

        try {
            $stmt = $this->pdo()->query(
                'SELECT id, version_number, training_date, training_samples, top3_accuracy, model_file_path, is_active
                 FROM ml_model_versions
                 ORDER BY is_active DESC, training_date DESC
                 LIMIT 1'
            );

            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            return $this->activeModelVersion = $row;
        } catch (Throwable $e) {
            error_log('RecommendationService::getActiveModelVersion warning: ' . $e->getMessage());

            return $this->activeModelVersion = [];
        }
    }

    // ── Prediction logging & outcomes ────────────────────────────────

    protected function recordPredictionLog(array $input, array $recommendation, array $modelInfo, ?int $userId): ?int
    {
        try {
            $pdo = $this->pdo();

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

            return (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            error_log('RecommendationService::recordPredictionLog warning: ' . $e->getMessage());

            return null;
        }
    }

    public function markPredictionOutcome(int $logId, string $actualStand, int $userId): void
    {
        $actualStand = strtoupper(trim($actualStand));

        if ($logId <= 0 || $actualStand === '') {
            return;
        }

        try {
            $pdo = $this->pdo();

            $stmt = $pdo->prepare('SELECT predicted_stands FROM ml_prediction_log WHERE id = :id');
            $stmt->execute([':id' => $logId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$record) {
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
        } catch (Throwable $e) {
            error_log('RecommendationService::markPredictionOutcome warning: ' . $e->getMessage());
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
}
