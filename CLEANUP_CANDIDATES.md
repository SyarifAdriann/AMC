# Project Cleanup - Non-Functional Files

**Generated:** 2025-11-23
**Purpose:** List all files not required for application functionality

---

## 📋 CATEGORIES

### ✅ **MUST KEEP** (Functional Files)
- Core application code (app/, config/, routes/, bootstrap/)
- Views (resources/views/)
- Public assets (public/assets/)
- ML models (ml/*.pkl, ml/predict.py, ml/health_check.py)
- Database schema (amc.sql, database/migrations/)
- Configuration (package.json, tailwind.config.js, .gitignore)
- **DEPLOY.md** (deployment guide - just created)

### 🗑️ **SAFE TO DELETE** (Documentation/Development Only)

---

## 1. REVISION NOTES & DEVELOPMENT DOCS (Safe to Delete)

### **Root Level Documentation Files:**
```
❌ apron-experiment.html                    # Experimental HTML file
❌ blackboxtest.md                          # Testing notes
❌ CODEBASE_ANALYSIS.md                     # Analysis document
❌ COMPLETE_ANALYSIS_REPORT.md              # Report
❌ context.md                               # Context notes
❌ DASHBOARD_COUNTERS_FIX.md                # Fix documentation
❌ dblayout.md                              # Database layout notes
❌ dbstructure.md                           # Database structure notes
❌ explanation.md                           # Explanation document
❌ FINAL_REPORT.md                          # Report
❌ FIX_A0_BUSINESS_RULE.md                  # Fix documentation
❌ FIX_ALWAYS_3_RECOMMENDATIONS.md          # Fix documentation
❌ KDD CHECKLIST.md                         # KDD process checklist
❌ KDD PROCESS.md                           # KDD process notes
❌ KDD_REDO_COMPARISON_REPORT.md            # KDD comparison report
❌ logic.md                                 # Logic documentation
❌ manageusers.md                           # User management notes
❌ MODEL_V2_IMPLEMENTATION_SUMMARY.md       # Model implementation summary
❌ mvc.md                                   # MVC architecture notes
❌ mvp.md                                   # MVP planning
❌ outline.md                               # Project outline
❌ PENJELASAN_SISTEM_PREDIKSI.md            # Indonesian prediction explanation (detailed)
❌ PENJELASAN_SISTEM_PREDIKSI_RINGKAS.md    # Indonesian prediction explanation (brief)
❌ PERFORMANCE_IMPROVEMENTS_SUMMARY.md      # Performance improvements doc
❌ phase7fix.md                             # Phase 7 fix notes
❌ PROC_OPEN_TEST_SUMMARY.md                # Proc_open testing summary
❌ project.md                               # Project notes
❌ QUICK_REFERENCE_CARD.md                  # Quick reference
❌ randomforest1.md                         # Random forest notes
❌ REFACTORING_CHECKLIST.md                 # Refactoring checklist
❌ revinstructions.md                       # Revision instructions
❌ rules.md                                 # Business rules documentation
❌ STAND_MODAL_FIX_SUMMARY.md               # Stand modal fix summary
❌ tailwindplan.md                          # Tailwind planning
❌ testing.md                               # Testing notes
❌ TESTING_CHECKLIST.md                     # Testing checklist
❌ thesis bab 4.md                          # Thesis chapter 4 (Indonesian)
❌ UI_IMPROVEMENTS_SUMMARY.md               # UI improvements summary
```

**Note:** You may want to keep 1-2 key documents like `DEPLOY.md` (just created) or `PENJELASAN_SISTEM_PREDIKSI_RINGKAS.md` for documentation.

---

## 2. CLI & REVISION TRACKING (Safe to Delete)

### **CLI Directory (Entire folder can be deleted):**
```
❌ CLI/                                     # Entire directory
  ├── context.md                           # Context for AI
  ├── knowledge-base/
  │   └── project-kb-v1.md                 # Knowledge base
  └── revisions/
      ├── revision_checklist.md            # Revision checklist
      ├── revision1.md through revision21.md  # 21 revision files
      └── revision-template.md             # Template
```

**Total:** ~25 files in CLI directory

---

## 3. KDD SCREENSHOTS (Safe to Delete)

### **KDD SCREENSHOTS Directory:**
```
❌ KDD SCREENSHOTS/                         # Entire directory (19 PNG files)
  ├── phase01_business_understanding.png
  ├── phase02_dataset_overview.png
  ├── phase02_stand_distribution_pre_filter.png
  ├── phase03_cleaned_dataset_stats.png
  ├── phase03_missing_values.png
  ├── phase03_stand_distribution_post_filter.png
  ├── phase04_encoder_mappings.png
  ├── phase04_transformed_sample.png
  ├── phase05_feature_importance.png
  ├── phase05_hyperparameter_results.png
  ├── phase05_hyperparameter_results1.png
  ├── phase05_hyperparameter_results2.png
  ├── phase05_modeling_plan.png
  ├── phase06_baseline_comparison.png
  ├── phase06_confidence_distribution.png
  ├── phase06_cross_validation.png
  ├── phase06_error_analysis.png
  ├── phase06_test_metrics.png
  ├── phase06_top3_summary.png
  ├── phase07_predict_alternateirline.png
  ├── phase07_predict_cold_start.png
  ├── phase07_predict_py_cli.png
  └── phase11_06_dashboard_screenshot.png
```

**Purpose:** Screenshots for thesis/documentation
**Safe to Delete:** Yes (unless needed for presentation)

---

## 4. REPORTS DIRECTORY (Safe to Delete)

### **reports/ Directory:**
```
❌ reports/                                 # Entire directory (28 files)
  ├── admin_guide.md
  ├── future_enhancements.md
  ├── integration_testing.md
  ├── model_performance.md
  ├── model_retraining_playbook.md
  ├── performance.md
  ├── phase1_business_understanding.md
  ├── phase2_data_selection.md
  ├── phase2_stand_distribution_pre.csv
  ├── phase3_cleaned_stats.csv
  ├── phase3_data_preprocessing.md
  ├── phase3_missing_values.csv
  ├── phase4_data_transformation.md
  ├── phase5_additional_attempts.md
  ├── phase5_confusion_matrix.csv
  ├── phase5_data_mining.md
  ├── phase5_feature_importance.csv
  ├── phase5_feature_importance.png
  ├── phase5_gridsearch_results.csv
  ├── phase5_gridsearch_top10.csv
  ├── phase6_confidence_distribution.png
  ├── phase6_confusion_matrix_heatmap.png
  ├── phase6_pattern_evaluation.md
  ├── phase6_predictions.csv
  ├── phase7_deployment_integration.md
  ├── phase8_post_prediction_filtering.md
  ├── phase9_model_versioning.md
  ├── phase10_end_to_end_tests.md
  ├── phase10_uat_checklist.md
  ├── phase11_frontend_integration.md
  ├── phase12_workflow.md
  ├── phase12_workflow.png
  ├── security_review.md
  ├── troubleshooting.md
  ├── user_manual.md
  ├── thesis_accurate_apron_wireframe.png
  ├── thesis_apron_accurate_wireframe.png
  ├── thesis_architecture_diagram.png
  ├── thesis_crisp_dm_diagram.png
  ├── thesis_dashboard_accurate_wireframe.png
  ├── thesis_dashboard_wireframe.png
  ├── thesis_data_migration_flowchart.png
  ├── thesis_feature_engineering_sample.png
  ├── thesis_system_flowchart.png
  └── thesis_system_usecase_diagram.png
```

**Purpose:** Thesis documentation and KDD process reports
**Safe to Delete:** Yes (unless needed for thesis submission)

---

## 5. TEST RESULTS (Safe to Delete)

### **test-results/ Directory:**
```
❌ test-results/                            # Entire directory (10 PNG files)
  ├── dashboard-page-desktop.png
  ├── dashboard-page-mobile.png
  ├── dashboard-page-tablet.png
  ├── index-page-desktop.png
  ├── index-page-mobile.png
  ├── index-page-tablet.png
  ├── login-page.png
  ├── master-table-page-desktop.png
  ├── master-table-page-mobile.png
  └── master-table-page-tablet.png
```

**Purpose:** UI testing screenshots
**Safe to Delete:** Yes

---

## 6. DATA DIRECTORY (Partially Delete)

### **data/ Directory:**
```
⚠️ data/                                    # Some files needed, some not
  ├── airline_preferences_corrected.csv    # ❌ Delete (old version)
  ├── airline_preferences_corrected_redo.csv # ❌ Delete (old version)
  ├── archive/                             # ❌ Delete entire folder
  │   └── 20251027_102630/
  │       ├── parking_history.csv
  │       ├── parking_history_clean.csv
  │       ├── parking_history_encoded.csv
  │       └── parking_history_raw_snapshot.csv
  ├── blended_training_data.csv            # ⚠️ KEEP (if used for retraining)
  ├── parking_history.csv                  # ⚠️ KEEP (training data)
  ├── parking_history_clean.csv            # ❌ Delete (intermediate)
  ├── parking_history_encoded.csv          # ❌ Delete (intermediate)
  ├── parking_history_encoded_redo.csv     # ❌ Delete (intermediate)
  ├── parking_history_preprocessed.csv     # ❌ Delete (intermediate)
  ├── parking_history_preprocessed_redo.csv # ❌ Delete (intermediate)
  ├── parking_history_raw_snapshot.csv     # ❌ Delete (snapshot)
  └── synthetic_training_data.csv          # ⚠️ KEEP (if used for retraining)
```

**Recommendation:**
- **KEEP:** `parking_history.csv`, `blended_training_data.csv`, `synthetic_training_data.csv` (for model retraining)
- **DELETE:** All intermediate files (encoded, preprocessed, clean versions) and archive folder

---

## 7. ML DIRECTORY (Partially Delete)

### **ml/ Directory:**
```
⚠️ ml/                                      # Some files needed, some not
  ├── __init__.py                          # ✅ KEEP
  ├── predict.py                           # ✅ KEEP (core prediction)
  ├── health_check.py                      # ✅ KEEP (system health)
  ├── model_cache.py                       # ✅ KEEP (caching)
  ├── train_model.py                       # ⚠️ KEEP (for retraining)
  ├── test_predict.py                      # ❌ DELETE (unit test)
  ├── parking_stand_model_rf_redo.pkl      # ✅ KEEP (active model)
  ├── encoders_redo.pkl                    # ✅ KEEP (active encoders)
  ├── confusion_matrix_dt_blended.png      # ❌ DELETE (report image)
  ├── confusion_matrix_rf.png              # ❌ DELETE (report image)
  ├── confusion_matrix_rf_blended.png      # ❌ DELETE (report image)
  ├── confusion_matrix_rf_redo.png         # ❌ DELETE (report image)
  ├── confusion_matrix_xgb_blended.png     # ❌ DELETE (report image)
  ├── feature_importance_dt_blended.png    # ❌ DELETE (report image)
  ├── feature_importance_rf.png            # ❌ DELETE (report image)
  ├── feature_importance_rf_blended.png    # ❌ DELETE (report image)
  ├── feature_importance_rf_redo.png       # ❌ DELETE (report image)
  └── feature_importance_xgb_blended.png   # ❌ DELETE (report image)
```

**Recommendation:**
- **KEEP:** Python scripts, .pkl model files
- **DELETE:** All PNG files (10 images, used for thesis/reports)

---

## 8. TOOLS DIRECTORY (Partially Delete)

### **tools/ Directory:**
```
⚠️ tools/                                   # Development & maintenance tools
  ├── check_pdo.php                        # ❌ DELETE (diagnostic)
  ├── cleanup_cache.php                    # ⚠️ KEEP (maintenance)
  ├── console.php                          # ❌ DELETE (dev console)
  ├── generate_accurate_apron_wireframe.py # ❌ DELETE (thesis wireframe)
  ├── generate_both_wireframes.py          # ❌ DELETE (thesis wireframe)
  ├── generate_crisp_dm_and_usecase.py     # ❌ DELETE (thesis diagram)
  ├── generate_data_migration_diagram.py   # ❌ DELETE (thesis diagram)
  ├── generate_diagrams_2_3.py             # ❌ DELETE (thesis diagram)
  ├── generate_feature_engineering_screenshot.py # ❌ DELETE (thesis)
  ├── generate_proposal_sketches.py        # ❌ DELETE (thesis)
  ├── kdd_redo_step1_preprocess.py         # ❌ DELETE (KDD process)
  ├── kdd_redo_step2_train.py              # ❌ DELETE (KDD process)
  ├── measure_predict_perf.py              # ❌ DELETE (performance test)
  ├── precompute_preferences.php           # ✅ KEEP (cron job - CRITICAL!)
  ├── randomforest1_pipeline.py            # ❌ DELETE (old pipeline)
  ├── refresh_dataset.py                   # ⚠️ KEEP (data refresh utility)
  ├── render_workflow_diagram.py           # ❌ DELETE (thesis diagram)
  ├── run_kddtest1.py                      # ❌ DELETE (KDD test)
  ├── run_model_update_v2.php              # ⚠️ KEEP (model update utility)
  └── test_proc_open_integration.php       # ❌ DELETE (integration test)
```

**Recommendation:**
- **KEEP:** `precompute_preferences.php` (CRITICAL - used in cron jobs), `cleanup_cache.php`, `refresh_dataset.py`, `run_model_update_v2.php`
- **DELETE:** All thesis wireframe/diagram generators, KDD test scripts, development diagnostic tools

---

## 9. TESTS DIRECTORY (Check Contents)

### **tests/ Directory:**
```
⚠️ tests/                                   # Unit/integration tests
```

**Status:** Need to check contents
**Recommendation:** If these are PHPUnit tests that you want to keep for CI/CD, keep them. Otherwise, delete.

---

## 10. MISCELLANEOUS ROOT FILES (Partially Delete)

### **Root Level Files:**
```
❌ dashboard.png                            # Screenshot
❌ DATASET AMC .csv                         # Dataset (possibly duplicate)
❌ checkpoint.json                          # Training checkpoint (old?)
❌ temp_payload.json                        # Temporary test file
❌ tailwind-custom.css                      # Old CSS (now compiled into tailwind.css)
```

**Recommendation:** Delete all

---

## 11. OLD DASHBOARD (Safe to Delete)

### **Deleted in Previous Session:**
According to git status, these files were already deleted:
```
✓ old dashboard/admin-users.php
✓ old dashboard/dashboard.php
✓ old dashboard/user_management.php
```

**Status:** Already removed

---

## 12. DATABASE BACKUP (Check & Keep One)

### **amc_database_files_backup/ Directory:**
```
⚠️ amc_database_files_backup/               # Backup directory
```

**Recommendation:**
- Check if this is a duplicate of `amc.sql`
- If yes, delete the directory
- If no, keep the most recent backup and delete old ones

---

## 📊 CLEANUP SUMMARY

### **Files to Delete (Safe):**

| Category | File Count | Disk Space Est. |
|----------|------------|-----------------|
| Root MD files | 35+ files | ~5 MB |
| CLI directory | 25+ files | ~2 MB |
| KDD SCREENSHOTS | 23 PNG files | ~10 MB |
| reports/ directory | 40+ files | ~15 MB |
| test-results/ | 10 PNG files | ~5 MB |
| data/archive/ | 4+ CSV files | ~20 MB |
| data/intermediate | 6 CSV files | ~30 MB |
| ml/*.png | 10 PNG files | ~5 MB |
| tools/*.py (thesis) | 10+ files | ~1 MB |
| Misc root files | 5 files | ~2 MB |

**Total Estimated Cleanup:** ~95 MB, 150+ files

---

### **Files to Keep (Critical for Operation):**

```
✅ app/                     # Core application
✅ config/                  # Configuration
✅ routes/                  # Routing
✅ bootstrap/               # Bootstrap
✅ resources/views/         # Views
✅ public/                  # Public assets
✅ assets/                  # Source assets
✅ ml/predict.py            # ML prediction
✅ ml/health_check.py       # Health check
✅ ml/model_cache.py        # Caching
✅ ml/train_model.py        # Model training
✅ ml/*.pkl                 # Model files
✅ database/                # Migrations
✅ amc.sql                  # Database schema
✅ tools/precompute_preferences.php  # CRITICAL cron job
✅ tools/cleanup_cache.php  # Maintenance
✅ tools/refresh_dataset.py # Data refresh
✅ tools/run_model_update_v2.php # Model updates
✅ data/parking_history.csv # Training data
✅ data/blended_training_data.csv # Training data
✅ data/synthetic_training_data.csv # Training data
✅ package.json             # NPM config
✅ package-lock.json        # NPM lock
✅ tailwind.config.js       # Tailwind config
✅ .gitignore               # Git ignore
✅ DEPLOY.md                # Deployment guide
```

---

## 🚨 CRITICAL WARNING

### **DO NOT DELETE THESE:**
1. **`tools/precompute_preferences.php`** - Used in cron jobs for cache warming
2. **`ml/parking_stand_model_rf_redo.pkl`** - Active ML model (50MB)
3. **`ml/encoders_redo.pkl`** - Active encoders
4. **`amc.sql`** - Database schema
5. **`data/parking_history.csv`** - Training data for model retraining
6. **`public/assets/css/tailwind.css`** - Compiled CSS (production)

---

## 📋 RECOMMENDED CLEANUP COMMANDS

### **Step 1: Delete Safe Directories**
```bash
# WARNING: Review contents first!
rm -rf "CLI/"
rm -rf "KDD SCREENSHOTS/"
rm -rf "reports/"
rm -rf "test-results/"
rm -rf "data/archive/"
```

### **Step 2: Delete Documentation Files**
```bash
rm -f *.md # WARNING: This deletes ALL markdown files
# Better approach - delete specific files:
rm -f "blackboxtest.md"
rm -f "CODEBASE_ANALYSIS.md"
rm -f "COMPLETE_ANALYSIS_REPORT.md"
rm -f "context.md"
rm -f "DASHBOARD_COUNTERS_FIX.md"
# ... (continue for all MD files except DEPLOY.md)
```

### **Step 3: Delete ML Images**
```bash
cd ml/
rm -f *.png
cd ..
```

### **Step 4: Delete Tools (Thesis-related)**
```bash
cd tools/
rm -f generate_*.py
rm -f kdd_*.py
rm -f render_*.py
rm -f test_*.php
rm -f check_pdo.php
rm -f console.php
cd ..
```

### **Step 5: Delete Miscellaneous**
```bash
rm -f dashboard.png
rm -f "DATASET AMC .csv"
rm -f checkpoint.json
rm -f temp_payload.json
rm -f apron-experiment.html
rm -f tailwind-custom.css
```

---

## ✅ FINAL CHECKLIST

After cleanup, verify application still works:

- [ ] Login page loads
- [ ] Dashboard loads with metrics
- [ ] Apron page loads
- [ ] ML predictions work (test "Get AI Recommendations")
- [ ] CRUD operations work
- [ ] Check `ls storage/cache/historical_preferences.json` exists
- [ ] Run `php tools/precompute_preferences.php` successfully

---

**Document Version:** 1.0
**Generated:** 2025-11-23
**Total Cleanup:** ~150+ files, ~95 MB estimated
