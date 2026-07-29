<?php

/**
 * Self-check for MovementVersionService.
 *
 * Save -> wipe -> restore must return aircraft_movements to byte-identical
 * state, including original row IDs.
 *
 * Run inside the app container:
 *   docker exec amc_app php /var/www/html/tests/movement_versions_check.php
 *
 * Safe to run on live data: it saves the current state first and restores it
 * at the end, and it cleans up the versions it creates.
 */

use App\Services\MovementVersionService;

/** @var \App\Core\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';

$pdo = $app->make(PDO::class);
$service = $app->make(MovementVersionService::class);

// Plain checks, not assert(): assert() is compiled out when zend.assertions=-1,
// which would make this script silently pass on a production ini.
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$snapshot = static function () use ($pdo): array {
    return $pdo->query('SELECT * FROM aircraft_movements ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
};

$created = [];
$before = $snapshot();
echo 'Starting state: ' . count($before) . " movements\n";

try {
    $created[] = $baseline = $service->saveVersion('SELF-CHECK baseline', null);
    echo "Saved baseline (version {$baseline})\n";

    $wipe = $service->restoreTo(null, null);
    $created[] = $wipe['auto_save_id'];
    $afterWipe = $snapshot();
    $check($afterWipe === [], 'Wipe must leave zero movements, got ' . count($afterWipe));
    echo "Wipe left 0 movements\n";

    $restore = $service->restoreTo($baseline, null);
    $created[] = $restore['auto_save_id'];
    $after = $snapshot();

    $check(count($after) === count($before), 'Row count changed: ' . count($before) . ' -> ' . count($after));
    $check(
        array_column($after, 'id') === array_column($before, 'id'),
        'Row IDs were not preserved across restore'
    );
    $check($after == $before, 'Restored rows differ from the originals');

    echo 'Restore returned ' . count($after) . " movements, IDs and values identical\n";
    echo "PASS\n";
    $exit = 0;
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    $exit = 1;
}

foreach ($created as $id) {
    if ($id) {
        $service->deleteVersion((int) $id);
    }
}
echo "Cleaned up self-check versions\n";

exit($exit);
