<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/autoload.php';

use App\Controllers\ApronController;
use App\Core\Application;
use App\Core\Auth\AuthManager;
use App\Core\Routing\Router;

final class TestApplication extends Application
{
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->config = [
            'ml' => [
                'python_path' => 'python',
            ],
        ];
        $this->router = new Router($this);
    }

    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

final class FakeAuthManager extends AuthManager
{
    private ?int $userId;

    public function __construct(Application $app, ?int $userId = 9001)
    {
        parent::__construct($app);
        $this->userId = $userId;
    }

    public function check(): bool
    {
        return $this->userId !== null;
    }

    public function id(): ?int
    {
        return $this->userId;
    }

    public function user(): ?array
    {
        if ($this->userId === null) {
            return null;
        }

        return [
            'id' => $this->userId,
            'username' => 'integration_tester',
            'role' => 'admin',
        ];
    }
}

final class TestableApronController extends ApronController
{
    private array $fixturePredictions;
    private array $fixtureAvailability;
    private array $fixturePreferences;
    private array $loggedEntries = [];
    private int $nextLogId = 1;

    public function __construct(Application $app, AuthManager $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
        $this->fixturePredictions = [
            ['stand' => 'B8', 'probability' => 0.55],
            ['stand' => 'B9', 'probability' => 0.52],
            ['stand' => 'B10', 'probability' => 0.32],
            ['stand' => 'B12', 'probability' => 0.18],
        ];
        $this->fixtureAvailability = [
            'available' => ['B9', 'B8', 'B10', 'B11'],
            'occupied' => ['A1', 'A2'],
        ];
        $this->fixturePreferences = [
            'B9' => 95.0,
            'B8' => 80.0,
            'B10' => 40.0,
        ];
    }

    public function fetchRecommendations(array $input): array
    {
        return $this->getStandRecommendations($input);
    }

    public function setAvailability(array $availability): void
    {
        $this->fixtureAvailability = $availability;
    }

    public function setPredictions(array $predictions): void
    {
        $this->fixturePredictions = $predictions;
    }

    public function setPreferences(array $preferences): void
    {
        $this->fixturePreferences = $preferences;
    }

    public function getLoggedEntries(): array
    {
        return $this->loggedEntries;
    }

    protected function callPythonPredictor(array $payload, int $timeoutSeconds = 6): array
    {
        return [
            'success' => true,
            'predictions' => $this->fixturePredictions,
            'metadata' => [
                'latency_ms' => 135,
                'payload_operator' => $payload['operator_airline'] ?? 'UNKNOWN',
            ],
        ];
    }

    protected function getAvailableStands(): array
    {
        return $this->fixtureAvailability;
    }

    protected function getAirlinePreferences(
        string $airline,
        string $category,
        string $aircraftType,
        array $available = []
    ): array {
        $availableLookup = array_flip(array_map(static fn ($stand) => strtoupper($stand), $available));
        $filtered = array_intersect_key($this->fixturePreferences, $availableLookup);

        foreach ($available as $stand) {
            $upper = strtoupper($stand);
            if (!isset($filtered[$upper])) {
                $filtered[$upper] = 0.0;
            }
        }

        return $filtered;
    }

    protected function getModelPerformanceSummary(): array
    {
        return [
            'top3_accuracy_percent' => '61.5%',
        ];
    }

    protected function getActiveModelVersion(): array
    {
        return [
            'version_number' => 'v1.0',
            'training_date' => '2025-10-01',
        ];
    }

    protected function recordPredictionLog(array $input, array $recommendation, array $modelInfo, ?int $userId): ?int
    {
        $this->loggedEntries[] = [
            'input' => $input,
            'recommendation' => $recommendation,
            'model' => $modelInfo,
            'user_id' => $userId,
        ];

        return $this->nextLogId++;
    }
}

final class ApronControllerTestSuite
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__);
    }

    public function run(): int
    {
        $tests = [
            'testStandRecommendation',
            'testFallbackWhenNoAvailability',
            'testPredictionLoggingCapturesUserId',
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            try {
                $this->{$test}();
                $passed++;
                echo "[PASS] {$test}\n";
            } catch (Throwable $e) {
                $failed++;
                echo "[FAIL] {$test}: {$e->getMessage()}\n";
            }
        }

        echo "\nSummary: {$passed} passed, {$failed} failed.\n";

        return $failed;
    }

    private function makeController(): TestableApronController
    {
        $app = new TestApplication($this->basePath);
        $auth = new FakeAuthManager($app, 501);

        return new TestableApronController($app, $auth);
    }

    private function testStandRecommendation(): void
    {
        $controller = $this->makeController();
        $result = $controller->fetchRecommendations([
            'aircraft_type' => 'A320',
            'operator_airline' => 'BATIK AIR',
            'category' => 'Komersial',
        ]);

        $candidates = $result['candidates'];
        $this->assertCount(3, $candidates, 'Expected exactly 3 ranked candidates');
        $this->assertSame('B9', $candidates[0]['stand'], 'Top candidate should be B9 with highest composite score');
        $this->assertGreaterThan(
            $candidates[1]['composite_score'],
            $candidates[0]['composite_score'],
            'Rank 1 composite score should exceed rank 2'
        );
        $this->assertSame('model', $result['source'], 'Should indicate model-sourced recommendations');
        $this->assertStringContains(
            'Latest evaluated top-3 accuracy',
            $result['notes'],
            'Notes should include top-3 accuracy context'
        );
        $this->assertSame('v1.0', $result['metadata']['model_version'] ?? null, 'Metadata must include model version');
    }

    private function testFallbackWhenNoAvailability(): void
    {
        $controller = $this->makeController();
        $controller->setAvailability([
            'available' => [],
            'occupied' => ['B8', 'B9', 'B10'],
        ]);

        $result = $controller->fetchRecommendations([
            'aircraft_type' => 'A320',
            'operator_airline' => 'BATIK AIR',
            'category' => 'Komersial',
        ]);

        $this->assertSame('fallback', $result['source'], 'Should mark fallback source when predictions filtered out');
        $this->assertCount(3, $result['candidates'], 'Fallback should still provide 3 stands when possible');
        $stands = array_column($result['candidates'], 'stand');
        $this->assertSame(['B8', 'B9', 'B10'], $stands, 'Fallback should preserve original prediction ordering');
    }

    private function testPredictionLoggingCapturesUserId(): void
    {
        $controller = $this->makeController();
        $result = $controller->fetchRecommendations([
            'aircraft_type' => 'CL850',
            'operator_airline' => 'JETSET',
            'category' => 'Charter',
        ]);

        $this->assertNotEmpty($result['prediction_log_id'], 'Prediction log id should be returned');
        $entries = $controller->getLoggedEntries();
        $this->assertCount(1, $entries, 'Exactly one log entry expected');
        $this->assertSame(501, $entries[0]['user_id'], 'Log entry must capture authenticated user id');
        $this->assertSame('CL850', $entries[0]['input']['aircraft_type'], 'Aircraft type stored in log payload');
    }

    private function assertCount(int $expected, array $items, string $message): void
    {
        if (count($items) !== $expected) {
            throw new RuntimeException($message . sprintf(' (expected %d, got %d)', $expected, count($items)));
        }
    }

    private function assertSame($expected, $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(
                $message . sprintf(' (expected %s, got %s)', var_export($expected, true), var_export($actual, true))
            );
        }
    }

    private function assertGreaterThan($expectedLess, $actualGreater, string $message): void
    {
        if (!($actualGreater > $expectedLess)) {
            throw new RuntimeException($message . sprintf(' (%.4f !> %.4f)', $actualGreater, $expectedLess));
        }
    }

    private function assertNotEmpty($value, string $message): void
    {
        if (empty($value)) {
            throw new RuntimeException($message);
        }
    }

    private function assertStringContains(string $needle, string $haystack, string $message): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new RuntimeException($message);
        }
    }
}

$suite = new ApronControllerTestSuite();
$failures = $suite->run();

if ($failures > 0) {
    exit(1);
}
