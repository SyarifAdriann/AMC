# Phase 9 â€“ Model Retraining & Version Control

## Database Artefacts
- **ml_model_versions** tracks every artefact drop. Columns include `version_number`, `training_date`, `training_samples`, `test_accuracy`, `top3_accuracy`, `model_file_path`, `notes`, and `is_active`. Seed entry created for `v1.0` (2025-10-24, 1â€¯145 samples, top-3 accuracy 0.6157).
- **ml_prediction_log** stores each API invocation (`prediction_token`, payload snapshot, requester id, model version, and later the actual assignment outcome). `prediction_log_id` now bubbles through `/api/apron/recommend` so the front-end can persist it when an operator confirms a stand.

## PHP Integration
1. `ApronController::getStandRecommendations()` augments metadata with the active model version (queried from `ml_model_versions`) and records every prediction via `recordPredictionLog()`. Failures are logged but never break the response flow.
2. `ApronController::saveMovement()` accepts an optional `prediction_log_id`. When provided it calls `markPredictionOutcome()` to capture the assigned stand and flags whether it matched the top-3 predictions.
3. The API response now includes `prediction_log_id`, enabling the UI to tether subsequent actions to the original inference.

## Retraining Workflow (Delta)
1. Train a new model (per `model_retraining_playbook.md`), copy artefacts to `ml/`, and validate CLI/API smoke tests.
2. Run the SQL snippet below to register the version and deactivate previous entries:
   ```sql
   INSERT INTO ml_model_versions
       (version_number, training_date, training_samples, test_accuracy, top3_accuracy, model_file_path, notes, is_active, created_by)
   VALUES
       ('v1.1', CURDATE(), 1320, 0.5521, 0.7012, 'ml/parking_stand_model.pkl', 'New data through Q3.', 1, 1)
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
3. Update `reports/phase5_metrics.json`, `checkpoint.json`, and `KDD CHECKLIST.md` with the new metrics/date.

## UI / Dashboard Plan
The dashboard already shows apron and movement KPIs. Added a compact card titled \"ML Recommendation Health\" populated via /api/ml/metrics, which aggregates ml_prediction_log:
- **Latest Version**: model_version and 	raining_date pulled from ml_model_versions (entry flagged is_active = 1).
- **30-day Coverage**: COUNT(*) of prediction logs with prediction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY).
- **Observed Top-3 Accuracy**: SUM(was_prediction_correct)/COUNT(*) across the same window.
- **Recent Predictions**: latest five logs, each showing operator, aircraft type, model version, and whether the assigned stand landed inside the recommended top-3 (green badge for match, amber when missed/pending).

Hook: /api/ml/metrics now returns {version, training_date, top3_accuracy_expected, top3_accuracy_observed, sample_size, recent}. The dashboard renders expected vs observed accuracy and the rolling log list (green when observed ≥ expected, amber otherwise).
## Evidence
- `reports/phase9_tables_snapshot.txt` captures `DESCRIBE` output for the new tables plus the seeded `ml_model_versions` row.
- `kdd_execution.log` includes two new entries dated 2025-10-25T20:17+07 to show the completion of Phase 8 and the start of Phase 9 infrastructure.

