# Phase 2 - Data Selection

**Documented:** 2025-10-24 15:51:00Z

## Dataset Overview
- Source file: DATASET AMC.csv (project root)
- Snapshot copies: data/parking_history_raw_snapshot.csv, data/parking_history.csv
- Rows: 1295
- Columns: REGISTRATION, TYPE, ON BLOCK, OFF BLOCK, PARKING STAND, FROM, TO, ARR, DEP, OPERATOR / AIRLINES, CATEGORY
- Unique stands: 53
- Feature focus: TYPE -> aircraft_type, OPERATOR / AIRLINES -> operator_airline, CATEGORY -> category, PARKING STAND -> target

## Integrity Checks
- Verified expected columns present (TYPE, OPERATOR / AIRLINES, CATEGORY, PARKING STAND)
- Detected additional metadata columns (REGISTRATION, ON/OFF BLOCK, FROM/TO, ARR/DEP) retained for context
- Confirmed dataset size exceeds 1,200 minimum requirement

## Screenshot Plan
1. Dataset preview head (pandas head) — `phase02_dataset_overview.png`
2. Stand distribution before filtering (plot) — `phase02_stand_distribution_pre_filter.png`

## Next Actions
- Execute preprocessing filters for valid stands
- Assess missing values and normalize categories
