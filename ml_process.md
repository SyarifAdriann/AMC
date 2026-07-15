# AMC Machine Learning: Chronological Training Process

This document traces the exact chronological steps of how the AMC Random Forest recommendation engine was built, from raw CSV data to the final production `.pkl` file. It serves to document the training methodology, allowing the entire pipeline to be reconstructed from scratch if necessary.

---

## 1. The Raw Dataset State
Before any machine learning occurred, the data was extracted from the operational history of the AMC.

*   **File Name:** `DATASET AMC .csv`
*   **Row Count:** 6,076 total lines (6,075 actual movement records after the header).
*   **Columns (11):** `REGISTRATION`, `TYPE`, `ON BLOCK`, `OFF BLOCK`, `PARKING STAND`, `FROM`, `TO`, `ARR`, `DEP`, `OPERATOR / AIRLINES`, `CATEGORY`.
*   **Data Types:** Purely categorical text strings (e.g., "ATR 72", "GARUDA") and timestamp strings. The raw data contained no numerical feature columns natively usable by a Machine Learning model.

---

## 2. Data Cleaning & Feature Engineering
Since Random Forest models cannot inherently process arbitrary strings or learn from hidden operational context, the raw data underwent **Feature Engineering**.

**Input Features Kept:**
From the raw dataset, only three columns were isolated to act as the primary inputs:
1.  `TYPE` (mapped to `aircraft_type`)
2.  `OPERATOR / AIRLINES` (mapped to `operator_airline`)
3.  `CATEGORY` (mapped to `category`)

**Derived Features Created:**
To give the AI operational context, the script programmatically derived three new features from the inputs:
1.  **`aircraft_size`**: The `aircraft_type` was cross-referenced against a hardcoded list of A0-compatible small aircraft (e.g., "C 152", "C 208", "PC 12", "CESSNA"). If a match occurred, this feature became `"SMALL_A0_COMPATIBLE"`; otherwise, it defaulted to `"STANDARD"`.
2.  **`airline_tier`**: The `operator_airline` was checked against operational frequency tiers. E.g., "GARUDA" and "BATIK AIR" were mapped to `"HIGH_FREQUENCY"`, while "SUSI AIR" was mapped to `"MEDIUM_FREQUENCY"`. The rest defaulted to `"LOW_FREQUENCY"`.
3.  **`stand_zone`**: Based on the `category`. Commercial flights mapped to `"RIGHT_COMMERCIAL"`, Cargo to `"LEFT_CARGO"`, and everything else to `"MIDDLE_CHARTER"`.

After creation, all 6 features (3 raw + 3 derived) were passed through Label Encoders (`encoders_redo.pkl`) to convert them into integer mappings that the Random Forest could mathematically process.

---

## 3. The Train-Test Split
The cleaned and encoded dataset (with invalid or empty rows dropped) contained exactly **5,190 usable records**.

*   **Train Size:** 4,152 rows
*   **Test Size:** 1,038 rows
*   **Split Ratio:** Exactly **80/20**
*   **Random State:** 42
*   **Why 80-20?** This is a standard Pareto distribution for ML. With over 5,000 rows, an 80% training set (4k+ rows) provided ample data for the trees to identify complex branching rules, while the 20% holdout (1k+ rows) was large enough to achieve a statistically significant evaluation of the Top-3 accuracy without risking overfitting. `stratify=y` was used to ensure that even the rarest parking stands appeared in both the training and testing sets.

---

## 4. Handling Class Imbalance with SMOTE
Of the 4,152 training rows, the distribution of the 17 target classes (Parking Stands) was highly skewed. Stands like B2 and B1 were heavily populated, while A2 and B10 were rare.

*   **SMOTE (Synthetic Minority Over-sampling Technique):** To prevent the model from ignoring rare stands, SMOTE was applied to the training split. While the exact post-SMOTE row count was not explicitly logged in the final artifact, it operated by interpolating new, mathematically synthetic data points for the minority classes.
*   This ensured that during the decision-tree building phase, the algorithm saw enough examples of rare stands to form rules for them, working alongside the `class_weight='balanced_subsample'` hyperparameter.

---

## 5. Hyperparameter Tuning via GridSearchCV
To find the absolute best version of the model, `GridSearchCV` was executed. Instead of guessing parameters, this function tested a massive "grid" of combinations across 5-fold cross-validation.

*   **Iteration Time:** The entire grid search process took exactly **50.0 seconds**.
*   **Optimal Configuration Found (`best_params`):**
    *   `n_estimators`: 200
    *   `min_samples_split`: 2
    *   `min_samples_leaf`: 5
    *   `max_depth`: null
    *   `class_weight`: "balanced_subsample"

---

## 6. The Final Trained Model
Upon completing the GridSearch, the model with the best cross-validated accuracy was saved to disk.

*   **Architecture:** Random Forest Classifier.
*   **Target Classes:** 17 unique parking stands.
*   **Forest Structure:** Composed of exactly 200 decision trees. Because `max_depth` was `null`, the trees grew infinitely deep until every final "leaf" node contained at least 5 historical flight records (`min_samples_leaf: 5`).
*   **Physical Artifact:** Saved as `parking_stand_model_rf_redo.pkl`.
*   **File Size:** **5,202,488 bytes** (~5.2 MB) of compressed Python object data.

---

## 7. Inference Time (`predict.py`)
At production time, the training phase is entirely bypassed. When an operator requests a recommendation, the following chronological sequence occurs:

1.  **JSON Ingestion:** The PHP backend uses `proc_open()` to spawn `predict.py`, feeding it a JSON payload via standard input (e.g., `{"aircraft_type": "B 738", "operator_airline": "GARUDA", "category": "Komersial"}`).
2.  **Feature Re-Creation:** `predict.py` executes `build_feature_vector()`, running the exact same feature engineering logic used in Step 2 to compute `aircraft_size`, `airline_tier`, and `stand_zone` in real-time.
3.  **Encoding:** It loads `encoders_redo.pkl` and transforms the 6 strings into a 1D NumPy integer array (e.g., `[14, 1, 56, 2, 0, 1]`).
4.  **Model Loading:** It loads `parking_stand_model_rf_redo.pkl` (approximately 5.2 MB) into RAM.
5.  **Prediction:** The script calls `model.predict_proba()` on the encoded vector, which commands all 200 decision trees to vote. The model returns 17 floating-point percentage scores summing to 1.0.
6.  **Decoding & Formatting:** The script sorts the probabilities, slices the Top 3 (or the number requested by `--top_k`), and decodes the winning integer indices back into string names (e.g., "B2") using `decode_stand()`.
7.  **Output:** A success JSON is printed to standard output, collected by the PHP application to be filtered by live business rules.
