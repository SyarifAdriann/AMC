<?php

/**
 * Self-check for the off block date-stamp rule and the reposition feature.
 *
 * Run inside the app container:
 *   docker exec amc_app php /var/www/html/tests/movement_rules_check.php
 *
 * Creates its own throwaway movements with a reserved registration and deletes
 * every one of them at the end. It never reads or modifies real records.
 */

use App\Repositories\AircraftMovementRepository;

/** @var \App\Core\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';

$pdo = $app->make(PDO::class);
$repo = $app->make(AircraftMovementRepository::class);

const TEST_REG = 'ZZ-TEST';

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$cleanup = static function () use ($pdo): void {
    $pdo->prepare('DELETE FROM aircraft_movements WHERE registration = :reg')
        ->execute([':reg' => TEST_REG]);
};

$rowsFor = static function (string $stand) use ($pdo): array {
    $stmt = $pdo->prepare(
        'SELECT * FROM aircraft_movements WHERE registration = :reg AND parking_stand = :stand'
    );
    $stmt->execute([':reg' => TEST_REG, ':stand' => $stand]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
};

$cleanup();
$failures = 0;

try {
    // Two real stands to move between.
    $stands = $pdo->query('SELECT stand_name FROM stands ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
    $check(count($stands) === 2, 'Need at least two stands in the stands table to test.');
    [$origin, $target] = $stands;
    echo "Using stands: {$origin} -> {$target}\n";

    // ---- 1. Same-day off block must NOT get a date stamp -------------------
    $created = $repo->saveMovement([
        'registration' => TEST_REG,
        'aircraft_type' => 'B737',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '08:00',
    ], 1);

    $repo->saveMovement([
        'id' => $created['id'],
        'registration' => TEST_REG,
        'aircraft_type' => 'B737',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '08:00',
        'off_block_time' => '09:30',
    ], 1);

    $row = $rowsFor($origin)[0];
    $check(
        $row['off_block_time'] === '09:30',
        'Same-day off block should be plain "09:30", got: ' . var_export($row['off_block_time'], true)
    );
    echo "PASS  same-day off block has no date stamp\n";

    // ---- 2. Overnight off block MUST get a date stamp ----------------------
    $pdo->prepare('UPDATE aircraft_movements SET movement_date = :d WHERE id = :id')
        ->execute([':d' => date('Y-m-d', strtotime('-1 day')), ':id' => $created['id']]);
    $pdo->prepare("UPDATE aircraft_movements SET off_block_time = '' WHERE id = :id")
        ->execute([':id' => $created['id']]);

    $repo->saveMovement([
        'id' => $created['id'],
        'registration' => TEST_REG,
        'aircraft_type' => 'B737',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '08:00',
        'off_block_time' => '09:30',
    ], 1);

    $row = $rowsFor($origin)[0];
    $check(
        strpos($row['off_block_time'], '(') !== false,
        'Overnight off block should carry a date stamp, got: ' . var_export($row['off_block_time'], true)
    );
    echo "PASS  overnight off block keeps its date stamp ({$row['off_block_time']})\n";

    // ---- 3. Reposition creates exactly one follow-on movement --------------
    $cleanup();

    $repo->saveMovement([
        'registration' => TEST_REG,
        'aircraft_type' => 'A320',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '10:00',
        'to_location' => $target,
    ], 1);

    $openId = (int) $rowsFor($origin)[0]['id'];
    $check($rowsFor($target) === [], 'No reposition should exist before off block is set.');

    $result = $repo->saveMovement([
        'id' => $openId,
        'registration' => TEST_REG,
        'aircraft_type' => 'A320',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '10:00',
        'to_location' => $target,
        'off_block_time' => '11:15',
    ], 1);

    $repositioned = $rowsFor($target);
    $check(count($repositioned) === 1, 'Expected exactly 1 reposition, got ' . count($repositioned));
    $check(!empty($result['reposition_id']), 'saveMovement should report the new reposition id.');

    $new = $repositioned[0];
    $check($new['aircraft_type'] === 'A320', 'Reposition should carry aircraft type, got: ' . $new['aircraft_type']);
    $check($new['operator_airline'] === 'TEST AIR', 'Reposition should carry operator, got: ' . $new['operator_airline']);
    $check(trim((string) $new['on_block_time']) === '', 'Reposition must have no on block.');
    $check(empty($new['off_block_time']), 'Reposition must be open.');
    echo "PASS  reposition created at {$target} with type + operator, no on block\n";

    // ---- 4. Re-saving the closed record must NOT duplicate -----------------
    $repo->saveMovement([
        'id' => $openId,
        'registration' => TEST_REG,
        'aircraft_type' => 'A320',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '10:00',
        'to_location' => $target,
        'off_block_time' => '11:15',
    ], 1);

    $check(count($rowsFor($target)) === 1, 'Re-saving must not create a second reposition.');
    echo "PASS  re-saving a closed movement creates no duplicate\n";

    // ---- 5. A non-stand destination must NOT trigger a reposition ----------
    $cleanup();

    $flight = $repo->saveMovement([
        'registration' => TEST_REG,
        'aircraft_type' => 'ATR72',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '12:00',
        'to_location' => 'WAMM',
    ], 1);

    $repo->saveMovement([
        'id' => $flight['id'],
        'registration' => TEST_REG,
        'aircraft_type' => 'ATR72',
        'operator_airline' => 'TEST AIR',
        'parking_stand' => $origin,
        'on_block_time' => '12:00',
        'to_location' => 'WAMM',
        'off_block_time' => '13:00',
    ], 1);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM aircraft_movements WHERE registration = :reg');
    $stmt->execute([':reg' => TEST_REG]);
    $check((int) $stmt->fetchColumn() === 1, 'A real destination must not create a reposition.');
    echo "PASS  real destination (WAMM) creates no reposition\n";

    echo "\nALL CHECKS PASSED\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nFAIL: " . $e->getMessage() . "\n");
    $failures = 1;
}

$cleanup();
echo "Cleaned up test movements\n";

exit($failures);
