## Phase 12 – Complete System Workflow Diagram

- **Source file:** `reports/phase12_workflow.mmd` (Mermaid syntax; render via VS Code Mermaid preview or mermaid.live and export as PNG/SVG).  
- **Intended output:** `reports/phase12_workflow.png` (export once rendered) plus a screenshot for Checklist Screenshot 43 if desired.

### Flow Coverage
1. **Operator Journey (Modal)**
   - Opens stand assignment modal, fills fields, clicks *Get AI Recommendations*.
   - Reviews the three ranked cards, chooses a stand, and saves the assignment (toast indicates AI rank or manual override).
2. **PHP Backend**
   - `ApronController@recommend` validates and normalizes payloads.
   - `callPythonPredictor` streams JSON into `ml/predict.py` with timeout safeguards.
   - `applyBusinessRules` merges availability + `airline_preferences` scores and keeps a fallback path.
   - `recordPredictionLog` inserts rows into `ml_prediction_log` before UI response.
   - `saveMovement` persists the final assignment and flags whether it matched a top-3 pick.
3. **Python Runtime**
   - `ml/predict.py` loads the serialized model/encoders, builds the feature vector, and returns the top‑k probabilities.
4. **Database Touchpoints**
   - `aircraft_movements`, `airline_preferences`, `ml_prediction_log`, and `ml_model_versions` tables are highlighted as data sources/sinks.
5. **Monitoring**
   - `/api/ml/metrics` consumes the log + version data to feed the dashboard widgets and the new prediction logbook.

### Next Actions
1. Render `reports/phase12_workflow.mmd` to PNG/SVG and store alongside the source (e.g., `reports/phase12_workflow.png`).
2. Capture **Screenshot 43** using the rendered diagram plus the UI workflow (modal → recommendations → save).
3. Reference this diagram in Section 12 of `KDD CHECKLIST.md` and any handoff documents.
