#!/usr/bin/env php
<?php
/**
 * Integration Test: PHP proc_open() ↔ Python ML Prediction
 *
 * Tests actual communication between PHP and Python via proc_open()
 * Generates test logs for thesis Tabel 4.23
 *
 * Usage: php tools/test_proc_open_integration.php
 */

declare(strict_types=1);

class ProcOpenIntegrationTester
{
    private string $basePath;
    private string $pythonScript;
    private array $results = [];
    private string $logFile;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__);
        $this->pythonScript = $this->basePath . '/ml/predict.py';
        $this->logFile = $this->basePath . '/reports/proc_open_integration_test_log.txt';
    }

    public function run(): void
    {
        echo "=================================================\n";
        echo "  PHP-Python Integration Test (proc_open)\n";
        echo "=================================================\n\n";

        $testCases = $this->getTestCases();

        foreach ($testCases as $index => $testCase) {
            $testNum = $index + 1;
            echo "Test Case #{$testNum}: {$testCase['name']}\n";
            echo str_repeat('-', 50) . "\n";

            $result = $this->runTest($testCase);
            $this->results[] = $result;

            echo "Status: " . ($result['status'] === 'PASS' ? '✓ PASS' : '✗ FAIL') . "\n";
            echo "Execution Time: {$result['execution_time_ms']} ms\n";
            echo "\n";
        }

        $this->generateReport();
        $this->saveLogFile();
    }

    private function getTestCases(): array
    {
        return [
            [
                'name' => 'Valid Input - A320 Commercial Flight',
                'input' => [
                    'aircraft_type' => 'A320',
                    'operator_airline' => 'BATIK AIR',
                    'category' => 'Komersial',
                ],
                'expected_success' => true,
            ],
            [
                'name' => 'Valid Input - Unknown Aircraft Type (Fallback)',
                'input' => [
                    'aircraft_type' => 'UNKNOWN999',
                    'operator_airline' => 'TEST AIRLINE',
                    'category' => 'Charter',
                ],
                'expected_success' => true,
            ],
            [
                'name' => 'Missing Required Field - No operator_airline',
                'input' => [
                    'aircraft_type' => 'A320',
                    'category' => 'Komersial',
                ],
                'expected_success' => false,
            ],
            [
                'name' => 'Missing Required Field - No aircraft_type',
                'input' => [
                    'operator_airline' => 'BATIK AIR',
                    'category' => 'Komersial',
                ],
                'expected_success' => false,
            ],
            [
                'name' => 'Missing Required Field - No category',
                'input' => [
                    'aircraft_type' => 'A320',
                    'operator_airline' => 'BATIK AIR',
                ],
                'expected_success' => false,
            ],
            [
                'name' => 'Invalid JSON - Malformed Input',
                'input' => '{invalid json}',
                'expected_success' => false,
            ],
            [
                'name' => 'Empty JSON Object',
                'input' => [],
                'expected_success' => false,
            ],
            [
                'name' => 'Special Characters in Aircraft Type',
                'input' => [
                    'aircraft_type' => 'B 737-800',
                    'operator_airline' => 'GARUDA',
                    'category' => 'Komersial',
                ],
                'expected_success' => true,
            ],
            [
                'name' => 'Small Aircraft - C208 for A0 Stand',
                'input' => [
                    'aircraft_type' => 'C 208',
                    'operator_airline' => 'SUSI AIR',
                    'category' => 'Komersial',
                ],
                'expected_success' => true,
            ],
            [
                'name' => 'Cargo Flight - B733',
                'input' => [
                    'aircraft_type' => 'B 733',
                    'operator_airline' => 'TRIGANA',
                    'category' => 'Cargo',
                ],
                'expected_success' => true,
            ],
        ];
    }

    private function runTest(array $testCase): array
    {
        $startTime = microtime(true);

        try {
            $output = $this->callPythonPredictor($testCase['input']);
            $endTime = microtime(true);

            $executionTimeMs = round(($endTime - $startTime) * 1000, 2);

            $success = isset($output['success']) && $output['success'] === true;
            $status = ($success === $testCase['expected_success']) ? 'PASS' : 'FAIL';

            return [
                'test_name' => $testCase['name'],
                'input_json' => is_string($testCase['input'])
                    ? $testCase['input']
                    : json_encode($testCase['input'], JSON_UNESCAPED_UNICODE),
                'output_json' => json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'expected_success' => $testCase['expected_success'],
                'actual_success' => $success,
                'status' => $status,
                'execution_time_ms' => $executionTimeMs,
                'error_message' => $output['error'] ?? null,
                'predictions_count' => isset($output['predictions']) ? count($output['predictions']) : 0,
            ];
        } catch (Throwable $e) {
            $endTime = microtime(true);
            $executionTimeMs = round(($endTime - $startTime) * 1000, 2);

            return [
                'test_name' => $testCase['name'],
                'input_json' => is_string($testCase['input'])
                    ? $testCase['input']
                    : json_encode($testCase['input'], JSON_UNESCAPED_UNICODE),
                'output_json' => json_encode(['error' => $e->getMessage()]),
                'expected_success' => $testCase['expected_success'],
                'actual_success' => false,
                'status' => $testCase['expected_success'] ? 'FAIL' : 'PASS',
                'execution_time_ms' => $executionTimeMs,
                'error_message' => $e->getMessage(),
                'predictions_count' => 0,
            ];
        }
    }

    private function callPythonPredictor($input): array
    {
        $python = $this->resolvePythonBinary();

        if (!file_exists($this->pythonScript)) {
            throw new RuntimeException("Python script not found: {$this->pythonScript}");
        }

        // Handle malformed JSON test case
        if (is_string($input)) {
            $jsonPayload = $input;
        } else {
            $jsonPayload = json_encode($input, JSON_THROW_ON_ERROR);
        }

        $command = sprintf(
            '%s %s 2>&1',
            $python,
            escapeshellarg($this->pythonScript)
        );

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start Python process via proc_open()');
        }

        // Write JSON to stdin
        fwrite($pipes[0], $jsonPayload);
        fclose($pipes[0]);

        // Read stdout
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Read stderr
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // Close process
        $returnCode = proc_close($process);

        if ($stdout === '' || $stdout === false) {
            $errorMsg = 'Python script returned no output.';
            if ($stderr !== '' && $stderr !== false) {
                $errorMsg .= ' stderr: ' . $stderr;
            }
            throw new RuntimeException($errorMsg);
        }

        $response = json_decode($stdout, true);
        if (!is_array($response)) {
            throw new RuntimeException('Invalid JSON response from Python: ' . $stdout);
        }

        // If Python script returned error in JSON, it's still valid communication
        // Just return the error response
        return $response;
    }

    private function resolvePythonBinary(): string
    {
        $candidates = ['python', 'python3', 'py'];

        foreach ($candidates as $candidate) {
            $testCommand = stripos(PHP_OS_FAMILY, 'Windows') === 0
                ? "where {$candidate}"
                : "which {$candidate}";

            $result = shell_exec($testCommand);
            if (is_string($result) && trim($result) !== '') {
                return $candidate;
            }
        }

        // Default fallback
        return 'python';
    }

    private function generateReport(): void
    {
        echo "\n";
        echo "=================================================\n";
        echo "  TEST RESULTS SUMMARY\n";
        echo "=================================================\n\n";

        $totalTests = count($this->results);
        $passed = array_filter($this->results, fn($r) => $r['status'] === 'PASS');
        $failed = array_filter($this->results, fn($r) => $r['status'] === 'FAIL');

        echo "Total Test Cases: {$totalTests}\n";
        echo "Passed: " . count($passed) . " ✓\n";
        echo "Failed: " . count($failed) . " ✗\n";
        echo "Pass Rate: " . round((count($passed) / $totalTests) * 100, 1) . "%\n\n";

        // Calculate timing statistics
        $times = array_column($this->results, 'execution_time_ms');
        $avgTime = round(array_sum($times) / count($times), 2);
        $minTime = min($times);
        $maxTime = max($times);

        echo "Execution Time Statistics:\n";
        echo "  Average: {$avgTime} ms\n";
        echo "  Minimum: {$minTime} ms\n";
        echo "  Maximum: {$maxTime} ms\n\n";

        // Detailed results table
        echo "Detailed Results:\n";
        echo str_repeat('-', 100) . "\n";
        printf("%-5s %-50s %-10s %-12s\n", "No", "Test Case", "Status", "Time (ms)");
        echo str_repeat('-', 100) . "\n";

        foreach ($this->results as $index => $result) {
            printf(
                "%-5d %-50s %-10s %-12s\n",
                $index + 1,
                substr($result['test_name'], 0, 50),
                $result['status'],
                $result['execution_time_ms']
            );
        }
        echo str_repeat('-', 100) . "\n";
    }

    private function saveLogFile(): void
    {
        $logContent = $this->generateLogContent();

        file_put_contents($this->logFile, $logContent);

        echo "\n✓ Test log saved to: {$this->logFile}\n";
        echo "✓ Use this data for thesis Tabel 4.23\n";
    }

    private function generateLogContent(): string
    {
        $content = "================================================================\n";
        $content .= "  PHP-Python Integration Test Log (proc_open Communication)\n";
        $content .= "================================================================\n";
        $content .= "Test Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "Script: ml/predict.py\n";
        $content .= "Method: proc_open() with stdin/stdout pipes\n";
        $content .= "Total Test Cases: " . count($this->results) . "\n";
        $content .= "================================================================\n\n";

        foreach ($this->results as $index => $result) {
            $testNum = $index + 1;
            $content .= "TEST CASE #{$testNum}: {$result['test_name']}\n";
            $content .= str_repeat('-', 70) . "\n";
            $content .= "Input JSON:\n";
            $content .= $result['input_json'] . "\n\n";
            $content .= "Output JSON:\n";
            $content .= $result['output_json'] . "\n\n";
            $content .= "Expected Success: " . ($result['expected_success'] ? 'true' : 'false') . "\n";
            $content .= "Actual Success: " . ($result['actual_success'] ? 'true' : 'false') . "\n";
            $content .= "Status: {$result['status']}\n";
            $content .= "Execution Time: {$result['execution_time_ms']} ms\n";

            if ($result['error_message']) {
                $content .= "Error Message: {$result['error_message']}\n";
            }

            if ($result['predictions_count'] > 0) {
                $content .= "Predictions Returned: {$result['predictions_count']}\n";
            }

            $content .= "\n" . str_repeat('=', 70) . "\n\n";
        }

        // Summary statistics
        $content .= "\nSUMMARY STATISTICS\n";
        $content .= str_repeat('-', 70) . "\n";

        $passed = array_filter($this->results, fn($r) => $r['status'] === 'PASS');
        $failed = array_filter($this->results, fn($r) => $r['status'] === 'FAIL');

        $content .= "Total Tests: " . count($this->results) . "\n";
        $content .= "Passed: " . count($passed) . "\n";
        $content .= "Failed: " . count($failed) . "\n";
        $content .= "Pass Rate: " . round((count($passed) / count($this->results)) * 100, 1) . "%\n\n";

        $times = array_column($this->results, 'execution_time_ms');
        $content .= "Execution Time:\n";
        $content .= "  Average: " . round(array_sum($times) / count($times), 2) . " ms\n";
        $content .= "  Minimum: " . min($times) . " ms\n";
        $content .= "  Maximum: " . max($times) . " ms\n";

        return $content;
    }

    public function getResultsForThesis(): array
    {
        return array_map(function($result) {
            return [
                'test_case' => $result['test_name'],
                'input_json' => $result['input_json'],
                'output_json' => substr($result['output_json'], 0, 100) . '...',
                'execution_time' => $result['execution_time_ms'] . ' ms',
                'status' => $result['status'],
            ];
        }, $this->results);
    }
}

// Run the test suite
$tester = new ProcOpenIntegrationTester();
$tester->run();

echo "\n";
echo "To view detailed logs:\n";
echo "  cat reports/proc_open_integration_test_log.txt\n";
echo "\n";
