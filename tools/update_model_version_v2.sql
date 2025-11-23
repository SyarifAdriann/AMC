-- Update ML Model Version to v2.0 (Random Forest with 80.15% Top-3 Accuracy)
-- Run this after deploying the new Random Forest model

USE amc;

-- Deactivate all previous versions
UPDATE ml_model_versions SET is_active = 0 WHERE is_active = 1;

-- Insert new model version v2.0
INSERT INTO ml_model_versions
    (version_number, training_date, training_samples, test_accuracy, top3_accuracy, model_file_path, notes, is_active, created_by, created_at)
VALUES
    (
        'v2.0',
        '2025-10-30',
        4152,
        0.3613,
        0.8015,
        'ml/parking_stand_model_rf_redo.pkl',
        'Random Forest (100 trees) with 6 engineered features: aircraft_type, aircraft_size, operator_airline, airline_tier, category, stand_zone. Achieved 80.15% Top-3 accuracy (target: 80%). Upgraded from Decision Tree v1.0 (61.57% Top-3). Key improvement: Stand Zone feature (37.58% importance).',
        1,
        1,
        NOW()
    );

-- Verify the update
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
ORDER BY created_at DESC;
