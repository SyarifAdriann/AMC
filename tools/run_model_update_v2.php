<?php
/**
 * Execute SQL to update ML model version to v2.0
 * Run this script via: php tools/run_model_update_v2.php
 */

require __DIR__ . '/../bootstrap/legacy.php';

echo "=================================================\n";
echo "ML MODEL VERSION UPDATE - v2.0 (Random Forest)\n";
echo "=================================================\n\n";

try {
    $app = legacy_app();
    $pdo = $app->make(PDO::class);

    echo "[1/3] Deactivating previous model versions...\n";
    $pdo->exec("UPDATE ml_model_versions SET is_active = 0 WHERE is_active = 1");
    echo "✓ Previous versions deactivated\n\n";

    echo "[2/3] Inserting new model version v2.0...\n";
    $stmt = $pdo->prepare("
        INSERT INTO ml_model_versions
            (version_number, training_date, training_samples, test_accuracy, top3_accuracy, model_file_path, notes, is_active, created_by, created_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        'v2.0',
        '2025-10-30',
        4152,
        0.3613,
        0.8015,
        'ml/parking_stand_model_rf_redo.pkl',
        'Random Forest (100 trees) with 6 engineered features: aircraft_type, aircraft_size, operator_airline, airline_tier, category, stand_zone. Achieved 80.15% Top-3 accuracy (target: 80%). Upgraded from Decision Tree v1.0 (61.57% Top-3). Key improvement: Stand Zone feature (37.58% importance).',
        1,
        1
    ]);
    echo "✓ Model version v2.0 inserted\n\n";

    echo "[3/3] Verifying database update...\n";
    $result = $pdo->query("
        SELECT
            version_number,
            training_date,
            training_samples,
            CONCAT(ROUND(test_accuracy * 100, 2), '%') AS test_accuracy,
            CONCAT(ROUND(top3_accuracy * 100, 2), '%') AS top3_accuracy,
            model_file_path,
            is_active,
            created_at
        FROM ml_model_versions
        ORDER BY created_at DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "\nModel Versions in Database:\n";
    echo str_repeat("-", 120) . "\n";
    printf("%-10s | %-15s | %-10s | %-15s | %-15s | %-8s | %s\n",
        "Version", "Training Date", "Samples", "Top-1 Accuracy", "Top-3 Accuracy", "Active", "Model File");
    echo str_repeat("-", 120) . "\n";

    foreach ($result as $row) {
        printf("%-10s | %-15s | %-10d | %-15s | %-15s | %-8s | %s\n",
            $row['version_number'],
            $row['training_date'],
            $row['training_samples'],
            $row['test_accuracy'],
            $row['top3_accuracy'],
            $row['is_active'] ? 'YES' : 'no',
            basename($row['model_file_path'])
        );
    }
    echo str_repeat("-", 120) . "\n";

    echo "\n✅ SUCCESS: Model version v2.0 is now active!\n";
    echo "\nNext steps:\n";
    echo "  1. Verify predict.py is using: ml/parking_stand_model_rf_redo.pkl\n";
    echo "  2. Test the API endpoint: /api/apron/recommend\n";
    echo "  3. Check dashboard displays updated accuracy (80.15%)\n";
    echo "\n=================================================\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
