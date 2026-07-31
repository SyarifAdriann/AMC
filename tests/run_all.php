<?php

/**
 * Runs every self-check in this directory and reports one summary.
 *
 *   php tests/run_all.php
 *
 * Inside the container:
 *   docker exec amc_app php /var/www/html/tests/run_all.php
 *
 * Each check is run as its own process because they all bootstrap the
 * application, which cannot be done twice in one process.
 *
 * These are integration checks: they need a working database connection and
 * they exercise the real repositories. Every one of them creates its own
 * throwaway records and deletes them at the end, so they are safe to run
 * against live data.
 *
 * Exit code is 0 only when every check passes, so this is usable as a gate.
 */

$checks = [
    'movement_rules_check.php' => 'Off block date stamps + reposition',
    'reference_data_check.php' => 'Aircraft / flight reference CRUD',
    'movement_versions_check.php' => 'Movement save / wipe / restore',
];

$php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
$results = [];
$width = max(array_map('strlen', $checks));

echo str_repeat('=', $width + 14) . PHP_EOL;
echo 'AMC self-checks' . PHP_EOL;
echo str_repeat('=', $width + 14) . PHP_EOL . PHP_EOL;

foreach ($checks as $file => $label) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;

    if (!is_file($path)) {
        $results[$label] = 'MISSING';
        printf("%-{$width}s  MISSING (%s)\n", $label, $file);
        continue;
    }

    $output = [];
    $exitCode = 1;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);

    $results[$label] = $exitCode === 0 ? 'PASS' : 'FAIL';

    printf("%-{$width}s  %s\n", $label, $results[$label]);

    // Only the failing check's output is worth reading in full.
    if ($exitCode !== 0) {
        foreach ($output as $line) {
            echo '    ' . $line . PHP_EOL;
        }
    }
}

$failed = count(array_filter($results, static fn($r) => $r !== 'PASS'));

echo PHP_EOL . str_repeat('-', $width + 14) . PHP_EOL;

if ($failed === 0) {
    echo 'ALL ' . count($results) . " CHECKS PASSED\n";
    exit(0);
}

echo $failed . ' of ' . count($results) . " CHECKS FAILED\n";
exit(1);
