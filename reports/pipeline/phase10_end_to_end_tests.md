# Phase 10 – System Testing & Validation Evidence

_Generated: 2025-10-26 (local)._  
This log consolidates the artefacts created while executing the Section 10 test plan.

## Automated Coverage
- **Python unit tests** – `python -m unittest ml.test_predict -v`  
  Evidence: `reports/phase10_python_unittest.txt`
- **PHP integration harness** – `php tests/ApronControllerTest.php`  
  Evidence: `reports/phase10_php_integration_tests.txt`

## Scenario Matrix
| Scenario | Validation Goal | Execution Notes | Result |
| --- | --- | --- | --- |
| Normal recommendation flow | Ensure ML + preference pipeline returns ranked candidates with metadata | Exercised via `testStandRecommendation` to confirm composite score ordering, metadata injection, and note enrichment. | PASS |
| No availability fallback | Confirm business rules surface fallback stands when every ML stand is blocked | `testFallbackWhenNoAvailability` forces zero-availability fixture and checks the source flag + ordering of fallback stands. | PASS |
| Prediction logging | Verify ApronController attaches user context + log id to each request | `testPredictionLoggingCapturesUserId` inspects the captured payload snapshot and user id propagation. | PASS |
| Predictor CLI sanity | Validate CLI handles sorting, top_k parameter, and unknown aircraft types | `ml.test_predict` suite runs CLI through standard, top_k=4, and cold-start permutations. | PASS |

## Follow‑Up Items
- Capture UI screenshots once the dashboard exposes the `ml_prediction_log` viewer (pending Section 11).
- Extend fixtures with live DB-backed repositories before release (placeholder stubs documented in `tests/ApronControllerTest.php`).
