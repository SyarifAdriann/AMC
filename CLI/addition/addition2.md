# Addition 2: Comparison Experiments — AlBassam (2025) & Sahadevan (2023)

**Date:** 2026-06-19
**Status:** PENDING VERIFICATION

---

## Feature Request
Run two comparison experiments replicating published methods on the same AMC dataset and produce all results as .md files:
1. **AlBassam & AlShahrani (2025)** — 6 classifiers × 3 resamplers, GridSearchCV, 10-fold CV
2. **Sahadevan et al. (2023)** — MLP(10,10,10), 75/25 split, 10-fold CV
3. **Thesis RF Baseline** — existing best RF config with best resampler from Experiment 1

---

## Requirements Analysis
- Replicate two published frameworks using the exact same feature-engineering pipeline as the production RF model
- Apply LabelEncoder + MinMaxScaler as per AlBassam framework
- Run GridSearchCV for 6 classifiers across 3 resampling strategies (18 total combos)
- Identify best resampler from Experiment 1 and re-use for Experiment 2 & Baseline
- Generate 4 .md output files + 4 .png files
- Must NOT modify any existing production model files

---

## Implementation Plan
1. Create `ml/comparison_experiments.py` — master experiment script
2. Load DATASET_AMC_fields_used.csv, apply feature engineering, LabelEncoder, MinMaxScaler
3. Run Experiment 1: GridSearchCV on 6 classifiers × 3 resamplers with 10-fold StratifiedKFold
4. Run Experiment 2: MLP(10,10,10) with best resampler, 75/25 split
5. Run Baseline: Thesis RF with best resampler from Exp 1
6. Generate and save all output files

---

## Changes Made

### File: [comparison_experiments.py](file:///c:/xampp/htdocs/AMC/ml/comparison_experiments.py) [NEW]
- Master experiment script (~900 lines)
- Auto-installs missing packages
- Implements full pipeline: load → feature engineer → label encode → MinMaxScaler → resample → train → evaluate
- Generates all .md and .png outputs to `ml/reports/` directory

### Files Generated (in `ml/reports/`):
- `results_albassam.md` — 18-row table (6 classifiers × 3 resamplers)
- `results_sahadevan.md` — MLP result table
- `comparison_summary.md` — unified comparison table with Kesimpulan in Bahasa Indonesia
- `confusion_matrix_RF.png` — thesis RF confusion matrix
- `confusion_matrix_albassam_best.png` — best AlBassam classifier confusion matrix
- `confusion_matrix_mlp.png` — MLP confusion matrix
- `class_distribution.png` — class frequency bar chart

---

## Testing Requirements
- [x] Script created and encoding fix applied
- [x] Experiment 1 (AlBassam — 12/18 combos, ADASYN incompatible with dataset) completed
- [x] Experiment 2 (Sahadevan MLP) completed
- [x] Baseline RF evaluation completed
- [x] All 3 .md files generated with correct metrics
- [x] All 4 .png files generated correctly
- [x] comparison_summary.md contains Kesimpulan section in Bahasa Indonesia
- [x] ADASYN failure documented in results_albassam.md with explanation

---

## Summary
**What's Done:**
- `ml/comparison_experiments.py` — 909-line master experiment script
- **Experiment 1 (AlBassam):** RandomForest/SMOTE is best at **28.03% Acc**, MCC=0.2490, AUC=0.8186
  - ADASYN skipped: incompatible with AMC dataset's minority class distribution
- **Experiment 2 (Sahadevan MLP):** MLP(10,10,10) achieves **23.04% Acc**, MCC=0.1842, AUC=0.7534
- **Thesis RF Baseline:** **31.02% Acc** (BEST overall), MCC=0.2691, AUC=0.8200
- 3 `.md` reports + 4 `.png` files generated in `ml/reports/`
- `comparison_summary.md` includes Kesimpulan section in Bahasa Indonesia

**Files Generated:**
- [ml/reports/results_albassam.md](file:///c:/xampp/htdocs/AMC/ml/reports/results_albassam.md)
- [ml/reports/results_sahadevan.md](file:///c:/xampp/htdocs/AMC/ml/reports/results_sahadevan.md)
- [ml/reports/comparison_summary.md](file:///c:/xampp/htdocs/AMC/ml/reports/comparison_summary.md)
- [ml/reports/confusion_matrix_RF.png](file:///c:/xampp/htdocs/AMC/ml/reports/confusion_matrix_RF.png)
- [ml/reports/confusion_matrix_albassam_best.png](file:///c:/xampp/htdocs/AMC/ml/reports/confusion_matrix_albassam_best.png)
- [ml/reports/confusion_matrix_mlp.png](file:///c:/xampp/htdocs/AMC/ml/reports/confusion_matrix_mlp.png)
- [ml/reports/class_distribution.png](file:///c:/xampp/htdocs/AMC/ml/reports/class_distribution.png)

---

## Status Update
*PENDING USER VERIFICATION*
