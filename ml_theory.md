# AMC Machine Learning: Theoretical Deep Dive

This document provides a clear, technical explanation of the Machine Learning concepts utilized in the AMC (Apron Movement Control) project. It is intended for developers, academics, or stakeholders who need to understand the *why* and *how* of the predictive model without getting lost in generic textbook definitions.

---

## 1. Why Random Forest?

For the problem of assigning an aircraft to a parking stand, **Random Forest** was chosen over other algorithms (like Support Vector Machines, Neural Networks, or simple Logistic Regression) for three primary reasons:

1. **Non-Linear Realities:** Airport parking isn't a straight line. The rule "Garuda Indonesia usually parks at B3" might have a non-linear exception: "...unless the aircraft is a small Cessna, then it goes to A0." Random Forests excel at mapping these complex, branching "if-this-then-that" relationships.
2. **Native Probabilities:** We do not want the AI to dictate exactly one stand. We want a ranked list of choices. Random Forest naturally outputs prediction probabilities (`predict_proba`), allowing us to provide the human operator with a "Top 3" list.
3. **Robustness to Categorical Data:** Our entire dataset is categorical (Airline names, Aircraft types). Random Forests handle categorical structures very well once they are encoded.

---

## 2. How Random Forest Works (In Context)

Random Forest is an "Ensemble" model—meaning it isn't one giant algorithm, but a collection of many small ones working together. 

*   **Decision Trees:** At its core, the model builds flowcharts. A single decision tree might look like: *Is the category CARGO? (Yes) -> Is the airline TRI MG? (Yes) -> Predict Stand A1.* 
*   **Bagging (Bootstrap Aggregating):** If we only had one decision tree, it would just memorize the dataset. To prevent this, the model uses "Bagging." It creates 200 different decision trees (as seen in our `n_estimators=200` parameter), and gives each tree a slightly different, random subset of the historical aircraft movements to learn from.
*   **Ensemble Voting:** When a new prediction is requested (e.g., a Batik Air A320), all 200 trees make their own guess. 
*   **`predict_proba`:** Instead of just outputting the single winner, `predict_proba` tallies the votes. If 90 trees vote for stand B4, 60 for B5, and 50 for B6, the model returns a 45% probability for B4, 30% for B5, and 25% for B6.

---

## 3. Handling Class Imbalance (SMOTE + class_weight)

**The Problem:** The AMC dataset suffers from severe **Class Imbalance**. Some stands (the "majority class", like B3 for commercial jets) might be used 50 times a day. Other stands (the "minority class", like a remote apron stand A5) might be used only once a month. Left alone, the ML model becomes "lazy." It realizes it can achieve high accuracy simply by *always* guessing B3 and completely ignoring A5.

**The Solution:**
1.  **`class_weight='balanced_subsample'`**: This hyperparameter was passed to the Random Forest. It mathematically penalizes the model heavily if it gets a minority stand (like A5) wrong, forcing the decision trees to pay close attention to rare parking events.
2.  **SMOTE (Synthetic Minority Over-sampling Technique):** To give the model enough data to learn about rare stands, SMOTE was used during training. It looks at the few times an aircraft parked at A5 and generates "synthetic" (fake but mathematically plausible) historical logs that mimic those events. This balances the training data so the forest isn't blinded by high-frequency stands.

---

## 4. Evaluation Metrics (Why Top-3 Matters)

According to the `results_summary_redo.json` file, the model was evaluated using specific metrics:

*   **Top-1 Accuracy (36.3%):** How often the model's absolute #1 guess was exactly where the plane parked. 
*   **Top-3 Accuracy (80.3%):** How often the actual parking stand was within the model's top 3 recommendations. 
*   **Top-5 Accuracy (98.9%):** How often the actual stand was within the top 5.

**Why Top-3 is the Primary Metric:** In live apron operations, an absolute "Top 1" accuracy is an unfair metric. The absolute best stand might be physically occupied by a delayed flight, or undergoing maintenance. Therefore, the goal of the system isn't to guess the *exact* stand a plane took historically, but to provide the operator with a tight cluster of highly appropriate, operationally valid options. An 80.3% Top-3 accuracy proves the model successfully understands the operational groupings.

**Other Metrics:**
*   **Precision:** When the model predicts stand A1, how often is it actually A1?
*   **Recall:** Out of all the times an aircraft *actually* parked at A1, how many times did the model successfully find it?
*   **F1 Score:** The harmonic mean of Precision and Recall (balancing false positives and false negatives).
*   **Macro-averaged F1 (33.5%):** This averages the F1 score treating *every stand equally*, regardless of how often it's used. Because we have highly erratic, rare stands, a lower Macro F1 is expected, but the `balanced_subsample` helps pull this number up from the baseline.

---

## 5. Actual Inputs and Outputs

The Machine Learning model does not understand text; it only understands numbers. Here is the pipeline:

### 1. Raw Input (From the human operator)
*   `aircraft_type`: "C 208"
*   `operator_airline`: "SUSI AIR"
*   `category`: "CHARTER"

### 2. Derived Features (Generated by `predict.py`)
Before querying the model, Python analyzes the raw input to give the AI more context:
*   `aircraft_size`: "SMALL_A0_COMPATIBLE" (Recognizes the Cessna 208 is a small aircraft).
*   `airline_tier`: "MEDIUM_FREQUENCY"
*   `stand_zone`: "MIDDLE_CHARTER"

### 3. Encoding
These strings are passed through the Label Encoders (`encoders_redo.pkl`), turning them into an integer array (e.g., `[14, 1, 56, 2, 0, 1]`).

### 4. Output
The model runs `predict_proba` and `predict.py` formats the top 3 highest probabilities into JSON:
```json
{
  "success": true,
  "predictions": [
    {"stand": "A1", "probability": 0.65, "rank": 1},
    {"stand": "A2", "probability": 0.20, "rank": 2},
    {"stand": "A3", "probability": 0.08, "rank": 3}
  ]
}
```

---

## 6. GridSearchCV and Hyperparameter Tuning

**What it means:** A Random Forest has "dials" you can tune (Hyperparameters), like how many trees to build, or how deep those trees can grow. **GridSearchCV** is a brute-force approach. We gave the computer a "grid" of different dial settings and told it to test every single combination to find the optimal setup.

According to `results_summary_redo.json`, the grid search took **50.0 seconds** and landed on these `best_params`:

*   **`n_estimators`: 200** (The forest consists of 200 individual decision trees).
*   **`min_samples_split`: 2** (A node in the tree requires at least 2 historical planes to split into a more specific rule).
*   **`min_samples_leaf`: 5** (A final decision "leaf" must contain at least 5 historical movements to be considered valid—this prevents the model from memorizing one-off weird parking events).
*   **`max_depth`: null** (The trees are allowed to grow as deep and complex as they need to without being prematurely cut off).
*   **`class_weight`: "balanced_subsample"** (As explained above, adjusts tree weights on the fly to help minority stands).
