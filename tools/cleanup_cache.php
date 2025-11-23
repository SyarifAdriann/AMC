#!/usr/bin/env php
<?php
/**
 * Cache Cleanup Script
 *
 * This script removes expired cache entries to prevent storage bloat.
 *
 * Run daily via cron/task scheduler:
 * Windows: schtasks /create /tn "AMC Cache Cleanup" /tr "php C:\xampp\htdocs\amc\tools\cleanup_cache.php" /sc daily /st 03:00
 * Linux: 0 3 * * * /usr/bin/php /path/to/amc/tools/cleanup_cache.php
 */

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Cache\FileCache;

$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting cache cleanup...\n";

$totalCleared = 0;

try {
    // Clean aircraft details cache
    $aircraftCache = new FileCache(__DIR__ . '/../storage/cache/aircraft_details');
    $cleared = $aircraftCache->clearExpired();
    echo "  Aircraft details cache: cleared $cleared expired entries\n";
    $totalCleared += $cleared;

    // Clean any other cache directories as needed
    // Add more cache cleanup here if you have other caches

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "[" . date('Y-m-d H:i:s') . "] Completed in {$elapsed}s\n";
    echo "Total cleared: $totalCleared entries\n";

} catch (Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
