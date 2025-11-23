#!/usr/bin/env php
<?php
/**
 * Pre-compute Historical Airline Preferences
 *
 * This script calculates historical preference scores for all categories
 * and caches them to avoid expensive queries during prediction requests.
 *
 * Run daily via cron/task scheduler:
 * Windows: schtasks /create /tn "AMC Preference Cache" /tr "php C:\xampp\htdocs\amc\tools\precompute_preferences.php" /sc daily /st 02:00
 * Linux: 0 2 * * * /usr/bin/php /path/to/amc/tools/precompute_preferences.php
 */

require_once __DIR__ . '/../bootstrap/app.php';

use App\Repositories\AircraftMovementRepository;
use PDO;

$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting historical preference computation...\n";

try {
    $pdo = $app->make(PDO::class);
    $movements = $app->make(AircraftMovementRepository::class);

    $categories = ['COMMERCIAL', 'CARGO', 'CHARTER'];
    $allPreferences = [];

    foreach ($categories as $category) {
        echo "  Processing category: $category\n";

        // Query historical preferences for this category
        $stmt = $pdo->prepare("
            SELECT
                UPPER(am.parking_stand) as stand,
                COUNT(*) as usage_count
            FROM aircraft_movements am
            LEFT JOIN aircraft_details ad ON am.registration = ad.registration
            WHERE am.parking_stand IS NOT NULL
              AND am.parking_stand != ''
              AND UPPER(COALESCE(ad.category, 'CHARTER')) = :category
            GROUP BY stand
            HAVING usage_count > 0
            ORDER BY usage_count DESC
        ");

        $stmt->execute([':category' => $category]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            echo "    No historical data found for $category\n";
            $allPreferences[$category] = [];
            continue;
        }

        // Calculate normalized scores
        $maxUsage = max(array_column($results, 'usage_count'));
        $preferences = [];

        foreach ($results as $row) {
            $stand = $row['stand'];
            $usageCount = (int) $row['usage_count'];
            $score = ($usageCount / $maxUsage) * 100;

            $preferences[$stand] = [
                'score' => round($score, 2),
                'usage_count' => $usageCount
            ];
        }

        $allPreferences[$category] = $preferences;
        echo "    Computed preferences for " . count($preferences) . " stands\n";
    }

    // Save to cache file
    $cacheDir = __DIR__ . '/../storage/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    $cacheFile = $cacheDir . '/historical_preferences.json';
    $cacheData = [
        'generated_at' => date('c'),
        'version' => 1,
        'preferences' => $allPreferences
    ];

    file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
    echo "  Cache saved to: $cacheFile\n";

    // Also save to database cache table (optional - for future use)
    // You could create a 'preference_cache' table if needed

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "[" . date('Y-m-d H:i:s') . "] Completed in {$elapsed}s\n";
    echo "Summary:\n";
    echo "  - COMMERCIAL: " . count($allPreferences['COMMERCIAL'] ?? []) . " stands\n";
    echo "  - CARGO: " . count($allPreferences['CARGO'] ?? []) . " stands\n";
    echo "  - CHARTER: " . count($allPreferences['CHARTER'] ?? []) . " stands\n";

} catch (Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
