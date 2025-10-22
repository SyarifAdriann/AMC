<?php

use App\Console\Commands\GenerateDailySnapshot;

require_once __DIR__ . '/../bootstrap/legacy.php';

$app = legacy_app();

$commands = [
    'snapshot:generate' => GenerateDailySnapshot::class,
];

$commandName = $argv[1] ?? null;

if ($commandName === null || !isset($commands[$commandName])) {
    echo "Available commands:" . PHP_EOL;
    foreach ($commands as $name => $class) {
        echo "  - {$name}" . PHP_EOL;
    }

    exit(1);
}

$command = $app->make($commands[$commandName]);
$arguments = array_slice($argv, 2);

$date = $arguments[0] ?? null;

try {
    $exitCode = $command->handle($date);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    $exitCode = 1;
}

exit((int) $exitCode);
