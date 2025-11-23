# Model Retraining Playbook

**Prepared:** 2025-10-24 17:27:03Z

This document outlines how to refresh the parking stand recommendation model once additional historical data becomes available. Follow these steps before restarting integration work to ensure consistency with the current project structure.

## 1. Data Refresh
1. Place the updated CSV (e.g., `DATASET AMC.csv`) in the project root.
2. Validate that required columns exist: `TYPE`, `OPERATOR / AIRLINES`, `CATEGORY`, `PARKING STAND`.
3. Archive the previous dataset in `data/archive/` if historical snapshots are required.

## 2. Re-run Phase 2 – Data Selection
- Copy the new dataset to `data/parking_history_raw_snapshot.csv` and `data/parking_history.csv`.
- Update `reports/phase2_data_selection.md` with new row counts and stand coverage.
- Capture replacement screenshots for dataset overview and pre-filter distribution.

## 3. Re-run Phase 3 – Data Preprocessing
- Execute preprocessing script (`python scripts/preprocess_data.py` once created) or replicate the currently documented steps:
  - Normalize strings, map categories, filter valid stands (A0-A3, B1-B13).
  - Enforce minimum 10-sample threshold per stand.
  - Write cleaned dataset to `data/parking_history_clean.csv`.
- Regenerate preprocessing reports (`reports/phase3_*`).
- Refresh Phase 3 screenshots (missing values, post-filter distribution, cleaned stats).

## 4. Re-run Phase 4 – Data Transformation
- Rebuild encoders and the encoded dataset (`data/parking_history_encoded.csv`) via the transformation script (`ml/build_encoders.py` once available) or the documented manual steps.
- Overwrite encoder pickle files in `ml/`.
- Update `reports/phase4_transformed_sample.txt` and `reports/phase4_encoder_mappings.json`.
- Re-capture encoding screenshots.

## 5. Re-run Phase 5 – Modeling
- Execute `python ml/train_model.py` with the refreshed data.
- Review and archive metrics in `reports/phase5_metrics.json` and related artefacts.
- If experimenting with alternative models, log every attempt in `reports/phase5_additional_attempts.md`.
- Re-capture Phase 5 screenshots (hyperparameter results, metrics, feature importance, training log).

## 6. Re-run Phase 6 – Evaluation
- Regenerate confusion matrix, baseline comparison, confidence histogram, and error analysis using the updated outputs.
- Refresh all Phase 6 screenshots and update `reports/phase6_pattern_evaluation.md`.

## 7. Integration Consistency
- The PHP integration (Phase 7 onward) reads model files from `ml/` and encoded data from `data/`. As long as file paths remain unchanged, replacing the model artefacts is sufficient.
- Update `ml_model_versions` table and `checkpoint.json` to reflect the retraining event.
- Document the new top-3 accuracy in `reports/phase6_top3_summary.txt` and in the dashboard widget once deployed.

### 7a. Model Version Tracking
After every successful training run:
1. Back up the previous artefacts:
   ```
   copy ml\parking_stand_model.pkl ml\parking_stand_model_v1_backup_%date:~10,4%-%date:~4,2%-%date:~7,2%.pkl
   copy ml\enc_aircraft_type.pkl ml\enc_aircraft_type_v1_backup_%date:~10,4%-%date:~4,2%-%date:~7,2%.pkl
   ```
2. Insert or update the active record in `ml_model_versions`:
   ```sql
   INSERT INTO ml_model_versions
       (version_number, training_date, training_samples, test_accuracy, top3_accuracy, model_file_path, notes, is_active, created_by)
   VALUES
       ('v1.1', CURDATE(), 1320, 0.5120, 0.7012, 'ml/parking_stand_model.pkl', 'Quarterly refresh with Oct-2025 traffic.', 1, 1)
   ON DUPLICATE KEY UPDATE
       training_date = VALUES(training_date),
       training_samples = VALUES(training_samples),
       test_accuracy = VALUES(test_accuracy),
       top3_accuracy = VALUES(top3_accuracy),
       model_file_path = VALUES(model_file_path),
       notes = VALUES(notes),
       is_active = VALUES(is_active);
   UPDATE ml_model_versions SET is_active = 0 WHERE version_number <> 'v1.1';
   ```
3. Record the same version label inside `reports/phase5_metrics.json` and `checkpoint.json` so downstream scripts show the correct metadata.

## 8. Logging & Checkpoints
- Append retraining events to `kdd_execution.log` with timestamps.
- Reset or version `checkpoint.json` if you intend to re-run phases sequentially.
- Tick/untick tasks in `KDD CHECKLIST.md` to reflect the new execution.
- Confirm that the `ml_prediction_log` table is capturing live traffic. After deployment, run:
  ```sql
  SELECT DATE(prediction_date) AS day,
         COUNT(*) AS total_predictions,
         SUM(was_prediction_correct) AS correct_predictions
  FROM ml_prediction_log
  WHERE prediction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  GROUP BY DATE(prediction_date);
  ```
  This provides an immediate view on whether the newly deployed version is outperforming the previous one.

## 9. Verification Before Integration
- Confirm top-3 accuracy versus the current target (>=70%).
- Ensure `ml/parking_stand_model.pkl` and all encoders exist and are loadable.
- Run unit/integration tests (Phase 10) after integration updates.

By following this playbook a future CLI agent can reproduce Phases 2–6 quickly with the new dataset while keeping the integration layers intact.

