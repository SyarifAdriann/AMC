# Phase 10 – User Acceptance Test Checklist

**Feature:** ML-powered parking stand recommendations  
**Prepared by:** Automation Agent (2025-10-26)  
**Model build:** `ml/parking_stand_model.pkl` (v1.0, training date 2025-10-01)  
**API endpoint:** `POST /api/apron/recommend`

## 1. Session Metadata _(to be completed by AMC operator)_
- Tester name: ______________________
- Date & shift: ______________________
- Environment (Prod/Test): ______________________
- Dataset snapshot: DATASET AMC.csv (2025-10 baseline)

## 2. Daily Operations Flow
| Step | Operator Action | Expected Behaviour | Status |
| --- | --- | --- | --- |
| 1 | Open apron view and launch the stand assignment modal | Modal loads with flight + AI sections | ☐ Pending |
| 2 | Enter flight number, aircraft type, airline, and category | Form accepts input, validation only on submit | ☐ Pending |
| 3 | Click `Get AI Recommendations` | Spinner + API call completes within 2–3 s | ☐ Pending |
| 4 | Review 3 ranked stands + probability bars | Cards reflect ML probability & preference score | ☐ Pending |
| 5 | Select a stand and save assignment | Modal closes, toast shows assigned stand + AI badge | ☐ Pending |

Developer note: Backend/API validated via automated tests in `reports/phase10_end_to_end_tests.md`. UI evidence still required from ops team.

## 3. Accuracy Sample (10-flight spot check)
| Flight | Aircraft | Airline | ML #1 | ML #2 | ML #3 | Actual Stand | In Top-3? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | | | | | | | ☐ |
| 2 | | | | | | | ☐ |
| 3 | | | | | | | ☐ |
| 4 | | | | | | | ☐ |
| 5 | | | | | | | ☐ |
| 6 | | | | | | | ☐ |
| 7 | | | | | | | ☐ |
| 8 | | | | | | | ☐ |
| 9 | | | | | | | ☐ |
| 10 | | | | | | | ☐ |
**Top-3 Accuracy (%):** ___________

## 4. Edge & Resilience Checks
- Unknown aircraft type returns fallback stands within 2–3 s. ☐
- Category left blank forces validation error before API call. ☐
- Occupied stands trigger fallback list with availability notes. ☐
- Python predictor failure recorded in logs and surfaces friendly error. ☐
- Route `/api/ml/metrics` shows 30-day accuracy widget on dashboard. ☐

## 5. Performance Benchmark
- API latency target: **< 2.0 s** across 10 consecutive calls (investigation required – current Windows sandbox sits above target).
- Latest automated run (2025-10-26): average **4.027 s**, P95 **4.716 s** using the predict CLI harness.  
  Evidence: `reports/phase10_performance_metrics.txt`
- Operator confirmation: ________________________________________

## 6. Overall Assessment
- Improves apron workflow: ✅ / ☐ Needs work
- Recommendation relevance: ✅ / ☐ Needs work
- Would you ship this version to production? ✅ / ☐ Hold
- Additional comments:
```
_______________________________________________________________
```
