# Addition 1: J48 Baseline Experiment

**Date:** 2026-06-14
**Status:** PENDING VERIFICATION

---

## Feature Request
I need you to run a Decision Tree (J48 equivalent) experiment on my dataset using the exact same pipeline as my existing Random Forest model, so I can compare the two.
Replicate it exactly but swap in DecisionTreeClassifier(criterion='entropy') instead of RandomForestClassifier.
Save the full output to a text file called j48_baseline_results.txt. Do not touch or modify any existing model files.

---

## Requirements Analysis
- **Goal:** Run an offline experiment evaluating DecisionTreeClassifier (with `criterion='entropy'` to simulate J48) using the exact configuration/pipeline of the production Random Forest model.
- **Constraints:** Avoid modifying any existing production models or application code.
- **Metrics to extract:** Top-1/3/5 Accuracy, Macro Average Precision/Recall/F1, and class-specific metrics for all 17 stands.

---

## Implementation Plan
1. Locate or run [j48_baseline.py](file:///c:/xampp/htdocs/AMC/ml/j48_baseline.py) to train and evaluate `DecisionTreeClassifier`.
2. Generate results containing all target metrics.
3. Save the results to [j48_baseline_results.txt](file:///c:/xampp/htdocs/AMC/j48_baseline_results.txt) in the project root and [ml/j48_baseline_results.txt](file:///c:/xampp/htdocs/AMC/ml/j48_baseline_results.txt).

---

## Changes Made
### File: [j48_baseline_results.txt](file:///c:/xampp/htdocs/AMC/j48_baseline_results.txt)
- **Line 1-66:** Created the baseline results report file containing Top-1/3/5 accuracy, macro metrics, and stand-by-stand breakdown.

### File: [ml/j48_baseline_results.txt](file:///c:/xampp/htdocs/AMC/ml/j48_baseline_results.txt)
- Regenerated/run via [j48_baseline.py](file:///c:/xampp/htdocs/AMC/ml/j48_baseline.py) script.

---

## Testing Requirements
- [x] Run baseline script and check generated text files.
- [x] Verify that Top-1, Top-3, Top-5 accuracy are printed.
- [x] Verify macro and per-class precision, recall, F1 are correct.

---

## Summary
**What's Done:**
- Executed the J48 Decision Tree baseline experiment matching the Random Forest pipeline.
- Extracted and outputted Top-1/3/5 Accuracy and macro/per-class precision, recall, and F1.
- Saved full output report to `j48_baseline_results.txt` at the root and `ml/` directory.

**What's Left To Do:**
- Wait for user verification.

---

## Status Update
*Pending User Verification*
