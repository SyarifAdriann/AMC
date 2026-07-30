<?php

/**
 * Self-check for reference data listing, search, paging and delete.
 *
 * Run inside the app container:
 *   docker exec amc_app php /var/www/html/tests/reference_data_check.php
 *
 * Creates its own throwaway records with reserved keys and removes them at the
 * end. Existing reference data is read but never modified.
 */

use App\Repositories\AircraftDetailRepository;
use App\Repositories\FlightReferenceRepository;

/** @var \App\Core\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';

$pdo = $app->make(PDO::class);
$aircraft = $app->make(AircraftDetailRepository::class);
$flights = $app->make(FlightReferenceRepository::class);

const TEST_REG = 'ZZ-REFCHK';
const TEST_FLIGHT = 'ZZ9999';

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$cleanup = static function () use ($pdo): void {
    $pdo->prepare('DELETE FROM aircraft_details WHERE registration = :r')->execute([':r' => TEST_REG]);
    $pdo->prepare('DELETE FROM flight_references WHERE flight_no = :f')->execute([':f' => TEST_FLIGHT]);
};

$cleanup();
$failures = 0;

try {
    // ---- Aircraft: paging works at all (LIMIT/OFFSET binding) -------------
    $page1 = $aircraft->paginate(1, 5);
    $check(count($page1['data']) <= 5, 'Page size not respected: got ' . count($page1['data']));
    $check($page1['total'] > 0, 'Expected existing aircraft records.');
    echo "PASS  aircraft paginate: {$page1['total']} total, " . count($page1['data']) . " on page 1\n";

    $page2 = $aircraft->paginate(2, 5);
    if ($page1['total'] > 5) {
        $firstOfPage1 = $page1['data'][0]['registration'] ?? '';
        $firstOfPage2 = $page2['data'][0]['registration'] ?? '';
        $check($firstOfPage1 !== $firstOfPage2, 'Page 2 returned the same rows as page 1 — OFFSET is not applied.');
        echo "PASS  aircraft page 2 differs from page 1\n";
    }

    // ---- Aircraft: upsert, search, delete ---------------------------------
    $aircraft->upsert(TEST_REG, [
        'aircraft_type' => 'TESTPLANE',
        'operator_airline' => 'REFCHK AIR',
        'category' => 'CHARTER',
        'notes' => 'self-check',
    ]);

    $found = $aircraft->paginate(1, 10, TEST_REG);
    $check($found['total'] === 1, 'Search should find exactly the new record, got ' . $found['total']);
    $check(($found['data'][0]['aircraft_type'] ?? '') === 'TESTPLANE', 'Search returned the wrong record.');
    echo "PASS  aircraft search finds the new record\n";

    $check($aircraft->deleteByRegistration(TEST_REG), 'Delete should report success.');
    $check($aircraft->paginate(1, 10, TEST_REG)['total'] === 0, 'Record still present after delete.');
    $check($aircraft->findByRegistration(TEST_REG) === null, 'Cache was not invalidated on delete.');
    echo "PASS  aircraft delete removes the row and clears the cache\n";

    // ---- Flight references: same round trip -------------------------------
    $flightPage = $flights->paginate(1, 5);
    $check(count($flightPage['data']) <= 5, 'Flight page size not respected.');
    echo "PASS  flight paginate: {$flightPage['total']} total\n";

    $flights->upsert(TEST_FLIGHT, 'AAA - BBB');
    $foundFlight = $flights->paginate(1, 10, TEST_FLIGHT);
    $check($foundFlight['total'] >= 1, 'Search should find the new flight reference.');

    $newId = (int) $foundFlight['data'][0]['id'];
    $check($flights->deleteById($newId), 'Flight delete should report success.');
    $check($flights->paginate(1, 10, TEST_FLIGHT)['total'] === 0, 'Flight reference still present after delete.');
    echo "PASS  flight reference search and delete round trip\n";

    echo "\nALL CHECKS PASSED\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nFAIL: " . $e->getMessage() . "\n");
    $failures = 1;
}

$cleanup();
echo "Cleaned up self-check records\n";

exit($failures);
