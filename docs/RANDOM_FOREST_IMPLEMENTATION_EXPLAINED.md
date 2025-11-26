# How Random Forest Works in AMC Parking Stand Prediction System

## Overview

This document explains **exactly** how the Random Forest machine learning model is implemented in the AMC system, focusing on the specifics of training, feature engineering, and prediction—not general Random Forest theory.

---

## 1. Training Data Preparation

### 1.1 Data Sources
**Location**: `tools/randomforest1_pipeline.py:40-68`

The system combines two datasets:
- `DATASET AMC.csv`
- `DATASET AMC 2.csv`

These contain historical parking records with columns:
- `TYPE` (aircraft type)
- `OPERATOR / AIRLINES`
- `CATEGORY` (Commercial/Charter/Cargo)
- `PARKING STAND` (the target we want to predict)

```python
# Combined dataset: typically ~1000-2000 historical parking records
df = pd.concat([df1, df2], ignore_index=True)
```

### 1.2 Data Cleaning
**Location**: `tools/randomforest1_pipeline.py:71-87`

The system filters and cleans the data:

```python
# Only keep valid stands (A0-A3, B1-B13)
VALID_STANDS = ["A0", "A1", "A2", "A3"] + [f"B{i}" for i in range(1, 14)]
df_clean = df[df["parking_stand"].isin(VALID_STANDS)].copy()

# Fill missing categories with "Charter" as default
df_clean["category"] = df_clean["category"].fillna("Charter")

# Remove records with missing critical fields
df_clean = df_clean.dropna(subset=["aircraft_type", "operator_airline", "parking_stand"])

# Only keep stands with at least 10 historical samples
MIN_SAMPLES = 10
stand_counts = df_clean["parking_stand"].value_counts()
valid_stands = stand_counts[stand_counts >= MIN_SAMPLES].index.tolist()
df_final = df_clean[df_clean["parking_stand"].isin(valid_stands)]
```

**Result**: A cleaned dataset with sufficient samples per parking stand.

---

## 2. Feature Engineering

### 2.1 Simple Features (Basic Pipeline)
**Location**: `tools/randomforest1_pipeline.py:90-111`

The basic pipeline uses 3 simple features:
- `aircraft_type` (e.g., "ATR 72", "B737")
- `operator_airline` (e.g., "GARUDA", "BATIK AIR")
- `category` (e.g., "COMMERCIAL", "CHARTER", "CARGO")

### 2.2 Advanced Features (Redo Pipeline)
**Location**: `tools/kdd_redo_step2_train.py:43-56`

The improved system engineers 6 features:

#### **Feature 1-3: Base Features**
- `aircraft_type_enc`: Aircraft type identifier
- `operator_airline_enc`: Airline operator
- `category_enc`: Movement category

#### **Feature 4: Aircraft Size**
**Location**: `ml/predict.py:81-98`

```python
def determine_aircraft_size(aircraft_type):
    """Determines if aircraft can use stand A0 (small aircraft only)"""
    A0_COMPATIBLE = [
        'C152', 'C172', 'C182', 'C206', 'C208',  # Cessna models
        'PC6', 'PC12',                            # Pilatus models
        'CESSNA', 'PILATUS'
    ]

    # Check if aircraft type matches any small aircraft pattern
    if any(pattern in aircraft_type.upper() for pattern in A0_COMPATIBLE):
        return 'SMALL_A0_COMPATIBLE'

    return 'STANDARD'
```

**Purpose**: Enforces business rule that stand A0 can only accommodate small aircraft.

#### **Feature 5: Airline Tier**
**Location**: `ml/predict.py:100-114`

```python
def determine_airline_tier(operator_airline):
    """Categorizes airlines by frequency at this airport"""
    HIGH_FREQ_AIRLINES = ['BATIK AIR', 'CITILINK', 'GARUDA', 'TRIGANA', 'TRI MG']
    MEDIUM_FREQ_AIRLINES = ['PELITA', 'JETSET', 'KARISMA', 'JIP', 'SUSI AIR']

    if airline in HIGH_FREQ_AIRLINES:
        return 'HIGH_FREQUENCY'
    elif airline in MEDIUM_FREQ_AIRLINES:
        return 'MEDIUM_FREQUENCY'
    else:
        return 'LOW_FREQUENCY'
```

**Purpose**: High-frequency airlines may have preferred stands based on operational patterns.

#### **Feature 6: Stand Zone**
**Location**: `ml/predict.py:130-137`

```python
def get_stand_zone(category):
    """Assigns logical zones based on movement type"""
    if category == 'COMMERCIAL':
        return 'RIGHT_COMMERCIAL'    # Passenger terminal side
    elif category == 'CARGO':
        return 'LEFT_CARGO'           # Cargo area
    else:
        return 'MIDDLE_CHARTER'       # General aviation area
```

**Purpose**: Captures spatial organization of the apron—commercial flights tend to use right-side stands, cargo uses left side.

---

## 3. Label Encoding

### 3.1 Converting Categories to Numbers
**Location**: `tools/kdd_redo_step2_train.py:39-76`

Since Random Forest only works with numbers, all categorical features are converted:

```python
from sklearn.preprocessing import LabelEncoder

encoders = {}

# Encode each feature
for feature in ['aircraft_type', 'aircraft_size', 'operator_airline',
                'airline_tier', 'category', 'stand_zone']:
    encoder = LabelEncoder()
    df[f'{feature}_enc'] = encoder.fit_transform(df[feature])
    encoders[feature] = encoder

# Encode target (parking stands)
encoder_stand = LabelEncoder()
df['parking_stand_enc'] = encoder_stand.fit_transform(df['parking_stand'])
encoders['parking_stand'] = encoder_stand

# Save all encoders for later use in predictions
with open('ml/encoders_redo.pkl', 'wb') as f:
    pickle.dump(encoders, f)
```

**Example encoding**:
- "ATR 72" → 0
- "B737" → 1
- "C208" → 2
- ...

- "A0" → 0
- "A1" → 1
- "B1" → 2
- ...

**Critical**: The same encoders must be used during training AND prediction, otherwise numbers won't match!

---

## 4. Random Forest Training with GridSearchCV

### 4.1 Train/Test Split
**Location**: `tools/kdd_redo_step2_train.py:84-105`

```python
X = df[['aircraft_type_enc', 'aircraft_size_enc', 'operator_airline_enc',
        'airline_tier_enc', 'category_enc', 'stand_zone_enc']]
y = df['parking_stand_enc']

# 80% training, 20% testing
# stratify=y ensures each stand appears proportionally in both sets
X_train, X_test, y_train, y_test = train_test_split(
    X, y,
    test_size=0.2,
    random_state=42,
    stratify=y
)
```

**Result**:
- Training set: ~800-1600 samples
- Test set: ~200-400 samples

### 4.2 Hyperparameter Grid Search
**Location**: `tools/kdd_redo_step2_train.py:114-136`

Instead of guessing the best Random Forest settings, the system tests **72 different combinations**:

```python
param_grid = {
    'n_estimators': [100, 200],              # Number of trees (2 options)
    'max_depth': [None, 20, 30],             # Tree depth (3 options)
    'min_samples_leaf': [2, 5, 10],          # Min samples in leaf (3 options)
    'min_samples_split': [5, 10],            # Min samples to split (2 options)
    'class_weight': ['balanced', 'balanced_subsample']  # (2 options)
}
# Total combinations: 2 × 3 × 3 × 2 × 2 = 72

grid_search = GridSearchCV(
    RandomForestClassifier(random_state=42, n_jobs=-1),
    param_grid,
    cv=5,              # 5-fold cross-validation
    scoring='accuracy',
    n_jobs=-1,         # Use all CPU cores
    verbose=1
)

grid_search.fit(X_train, y_train)
best_model = grid_search.best_estimator_
```

### 4.3 How GridSearchCV Works

For **each of the 72 combinations**, GridSearchCV performs **5-fold cross-validation**:

```
Training Data (100%)
├── Fold 1: Train on 80%, validate on 20%
├── Fold 2: Train on 80%, validate on 20%  (different split)
├── Fold 3: Train on 80%, validate on 20%  (different split)
├── Fold 4: Train on 80%, validate on 20%  (different split)
└── Fold 5: Train on 80%, validate on 20%  (different split)

Average accuracy across 5 folds = Cross-validation score
```

**Total training runs**: 72 combinations × 5 folds = **360 training runs**

The combination with the **highest average accuracy** is selected as the best model.

**Typical best parameters found**:
```python
{
    'n_estimators': 200,
    'max_depth': None,
    'min_samples_leaf': 2,
    'min_samples_split': 5,
    'class_weight': 'balanced_subsample'
}
```

### 4.4 Why Use `class_weight='balanced'`?

**Problem**: The dataset is imbalanced. Stand B1 might have 200 samples, but stand A0 only has 15.

**Solution**: `balanced_subsample` makes the model pay more attention to rare stands during training, so it doesn't just predict the most common stands.

### 4.5 What Actually Gets Trained

The final model is a **Random Forest with ~200 decision trees**. Each tree:
1. Takes a random subset of training samples
2. At each split, considers a random subset of features
3. Builds a decision path from root to leaf

**Example of one decision tree's logic**:
```
If category_enc == 2 (COMMERCIAL):
    If operator_airline_enc == 5 (GARUDA):
        If aircraft_type_enc == 10 (B737):
            → Predict stand B5
        Else:
            → Predict stand B3
    Else:
        → Predict stand B7
Else if category_enc == 0 (CARGO):
    → Predict stand A1
...
```

The Random Forest's final prediction is the **majority vote** (or average probability) across all 200 trees.

---

## 5. Model Evaluation

### 5.1 Top-K Accuracy Metric
**Location**: `tools/kdd_redo_step2_train.py:162-180`

The system uses **Top-3 Accuracy** as the primary metric:

```python
def calculate_top_k_accuracy(model, X, y_true, k=3):
    """Returns True if correct stand is in top-k predictions"""
    y_proba = model.predict_proba(X)  # Get probabilities for all stands

    correct = 0
    for i, true_label in enumerate(y_true):
        # Get indices of top-k highest probabilities
        top_k_indices = np.argsort(y_proba[i])[-k:][::-1]

        if true_label in top_k_indices:
            correct += 1

    return correct / len(y_true)
```

**Example**:
- Actual stand: B5
- Top-3 predictions: [B5, B3, B7]
- Result: ✅ **Hit** (B5 is in top-3)

**Target**: ≥70% Top-3 accuracy (ideally ≥80%)

### 5.2 Feature Importance Analysis
**Location**: `tools/kdd_redo_step2_train.py:252-281`

After training, the model reveals which features matter most:

```python
importances = model.feature_importances_
# Typical results:
# Stand Zone:       0.35  (35% importance) ← Most important
# Category:         0.25  (25%)
# Airline:          0.18  (18%)
# Aircraft Type:    0.12  (12%)
# Airline Tier:     0.07  (7%)
# Aircraft Size:    0.03  (3%)
```

**Interpretation**: Stand zone (spatial organization) is the strongest predictor, followed by category and airline.

---

## 6. Making Predictions in Production

### 6.1 Prediction Pipeline
**Location**: `app/Controllers/ApronController.php:792-852`, `ml/predict.py`

When a user requests a stand recommendation:

```
User Input (PHP)
    ↓
ApronController::callPythonPredictor()
    ↓
proc_open() executes: python ml/predict.py
    ↓ (via stdin)
JSON: {"aircraft_type": "B737", "operator_airline": "GARUDA", "category": "COMMERCIAL"}
    ↓
Python: Feature Engineering
    ↓
Python: Load model + encoders
    ↓
Python: Encode features to numbers
    ↓
Random Forest: Predict probabilities
    ↓
Python: Return top-3 predictions
    ↓ (via stdout)
JSON: {"success": true, "predictions": [...]}
    ↓
PHP: Apply business rules + availability filtering
    ↓
Display to user
```

### 6.2 Feature Vector Construction
**Location**: `ml/predict.py:139-162`

```python
def build_feature_vector(payload):
    aircraft_type = payload['aircraft_type'].upper()      # "B737"
    operator_airline = payload['operator_airline'].upper() # "GARUDA"
    category = payload['category'].upper()                 # "COMMERCIAL"

    # Engineer derived features
    aircraft_size = determine_aircraft_size(aircraft_type)     # "STANDARD"
    airline_tier = determine_airline_tier(operator_airline)    # "HIGH_FREQUENCY"
    stand_zone = get_stand_zone(category)                      # "RIGHT_COMMERCIAL"

    return {
        'aircraft_type': aircraft_type,
        'aircraft_size': aircraft_size,
        'operator_airline': operator_airline,
        'airline_tier': airline_tier,
        'category': category,
        'stand_zone': stand_zone
    }
```

### 6.3 Encoding and Prediction
**Location**: `ml/predict.py:182-232`

```python
# Load model and encoders
with open('ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
    model = pickle.load(f)

encoders = load_all_encoders()  # Load from ml/encoders_redo.pkl

# Convert features to encoded integers
vector = np.array([
    to_index('aircraft_type', 'B737'),           # → 10
    to_index('aircraft_size', 'STANDARD'),        # → 1
    to_index('operator_airline', 'GARUDA'),       # → 5
    to_index('airline_tier', 'HIGH_FREQUENCY'),   # → 0
    to_index('category', 'COMMERCIAL'),           # → 1
    to_index('stand_zone', 'RIGHT_COMMERCIAL'),   # → 2
])
# Result: [10, 1, 5, 0, 1, 2]

# Get probabilities for all stands
probabilities = model.predict_proba(vector.reshape(1, -1))[0]
# Result: [0.02, 0.03, 0.15, 0.25, 0.35, 0.08, 0.12, ...]
#         [A0,   A1,   A2,   A3,   B1,   B2,   B3,   ...]

# Get top-3 highest probabilities
top_indices = np.argsort(probabilities)[::-1][:3]
# Result: [4, 3, 2] → stands [B1, A3, A2]

# Convert back to stand names
predictions = [
    {'stand': 'B1', 'probability': 0.35, 'rank': 1},
    {'stand': 'A3', 'probability': 0.25, 'rank': 2},
    {'stand': 'A2', 'probability': 0.15, 'rank': 3}
]
```

### 6.4 How Probabilities Are Calculated

Inside the Random Forest (with 200 trees):

```
Tree 1 predicts: B1
Tree 2 predicts: B1
Tree 3 predicts: A3
Tree 4 predicts: B1
Tree 5 predicts: B2
...
Tree 200 predicts: B1

Votes:
B1: 70 votes  → probability = 70/200 = 0.35
A3: 50 votes  → probability = 50/200 = 0.25
A2: 30 votes  → probability = 30/200 = 0.15
B7: 25 votes  → probability = 25/200 = 0.125
...
```

---

## 7. Business Rules and Post-Processing

### 7.1 Availability Filtering
**Location**: `app/Controllers/ApronController.php:927-1042`

The raw ML predictions are filtered by real-time stand availability:

```php
// ML says: [B1, A3, A2]
// But B1 is occupied

$available = ['A0', 'A2', 'A3', 'B2', 'B3'];  // Currently empty stands
$candidates = [];

foreach ($predictions as $pred) {
    if (in_array($pred['stand'], $available)) {
        $candidates[] = $pred;  // Keep only available stands
    }
}
// Result: [A3, A2]
```

### 7.2 A0 Size Restriction
**Location**: `app/Controllers/ApronController.php:1051-1071`

```php
// CRITICAL BUSINESS RULE: A0 only for small aircraft
if ($stand === 'A0' && !$this->isSmallAircraft($aircraftType)) {
    continue;  // Skip A0 for standard aircraft
}
```

Even if the ML model predicts A0 with high probability for a B737, this rule **overrides** the prediction.

### 7.3 Airline Preferences
**Location**: `app/Controllers/ApronController.php:1689-1724`

The system blends ML predictions with airline preferences:

```php
// ML prediction: B1 with 35% probability
// Airline preference for B1: 80/100

$composite_score = (0.6 × 0.35) + (0.4 × 0.80)
                 = 0.21 + 0.32
                 = 0.53

// 60% weight on ML, 40% weight on airline preferences
```

This ensures the system respects airline preferences while still learning from historical data.

---

## 8. Model Performance Tracking

### 8.1 Prediction Logging
**Location**: `app/Controllers/ApronController.php:1177-1272`

Every prediction is logged to the database:

```sql
INSERT INTO ml_prediction_log (
    aircraft_type,
    operator_airline,
    category,
    predicted_stands,           -- Top-3 predictions
    model_version,
    requested_by_user
) VALUES (...)
```

### 8.2 Outcome Tracking
**Location**: `app/Controllers/ApronController.php:1276-1379`

When the user assigns a stand, the system records whether it matched the prediction:

```php
// Predicted: [B1, A3, A2]
// User selected: A3

$wasCorrect = in_array('A3', ['B1', 'A3', 'A2']);  // TRUE

UPDATE ml_prediction_log
SET actual_stand_assigned = 'A3',
    was_prediction_correct = 1,  -- Hit!
    actual_recorded_at = NOW()
WHERE id = $predictionLogId
```

This data is used to monitor real-world accuracy and decide when to retrain the model.

---

## 9. Model Retraining Workflow

When new parking data accumulates:

1. **Export** new parking records from production database
2. **Combine** with existing training data
3. **Run** `tools/kdd_redo_step2_train.py`
   - Cleans and preprocesses data
   - Engineers features
   - Runs GridSearchCV (tests 72 combinations)
   - Evaluates Top-3 accuracy
   - Saves new model: `ml/parking_stand_model_rf_redo.pkl`
   - Saves new encoders: `ml/encoders_redo.pkl`
4. **Update** model version in database
5. **Deploy** new model files to production

**Typical retraining schedule**: Every 3-6 months or when Top-3 accuracy drops below 70%.

---

## 10. Key Differences from General Random Forest

| Aspect | General Random Forest | This Implementation |
|--------|----------------------|---------------------|
| **Target Metric** | Accuracy | **Top-3 Accuracy** (user picks from 3 options) |
| **Output** | Single prediction | **Top-3 ranked predictions with probabilities** |
| **Feature Engineering** | Manual | **Automated** (aircraft size, airline tier, stand zone) |
| **Class Imbalance** | Often ignored | **Handled** via `class_weight='balanced'` |
| **Hyperparameters** | Fixed | **Optimized** via GridSearchCV (72 combinations) |
| **Post-Processing** | None | **Business rules**: availability, size restrictions, airline preferences |
| **Performance Tracking** | One-time evaluation | **Continuous**: logs predictions and actual outcomes |

---

## Summary: Complete Training Flow

```
Historical Data (CSV files)
    ↓
[STEP 1] Combine & Clean
    - Filter valid stands
    - Remove missing data
    - Keep stands with ≥10 samples
    ↓
[STEP 2] Feature Engineering
    - Base: aircraft_type, operator_airline, category
    - Derived: aircraft_size, airline_tier, stand_zone
    ↓
[STEP 3] Label Encoding
    - Convert all strings to integers
    - Save encoders for later use
    ↓
[STEP 4] Train/Test Split
    - 80% training, 20% testing
    - Stratified to preserve class distribution
    ↓
[STEP 5] GridSearchCV
    - Test 72 hyperparameter combinations
    - 5-fold cross-validation for each
    - Total: 360 training runs
    - Select best combination
    ↓
[STEP 6] Train Final Model
    - Random Forest with best parameters
    - Typically: 200 trees, balanced class weights
    ↓
[STEP 7] Evaluate
    - Top-3 Accuracy on test set
    - Target: ≥70% (ideally ≥80%)
    - Feature importance analysis
    ↓
[STEP 8] Save Model
    - ml/parking_stand_model_rf_redo.pkl
    - ml/encoders_redo.pkl
    ↓
[PRODUCTION] Make Predictions
    - Load model + encoders
    - Engineer features from user input
    - Get top-3 probabilities
    - Apply business rules
    - Return recommendations
    ↓
[FEEDBACK LOOP] Track Performance
    - Log predictions
    - Record actual outcomes
    - Monitor Top-3 accuracy
    - Retrain when needed
```

---

## Conclusion

This Random Forest implementation is **highly customized** for aircraft parking stand prediction:

1. **Domain-specific features**: Aircraft size, airline tier, stand zone
2. **Top-3 predictions**: Not just one answer, but 3 ranked options
3. **Business rule integration**: ML predictions are filtered by availability and size constraints
4. **Continuous learning**: Predictions are logged and performance is monitored
5. **Automated hyperparameter tuning**: GridSearchCV finds the best model configuration

The training process involves testing hundreds of combinations to find the optimal configuration, and the resulting model provides probabilistic recommendations that are further refined by real-time business rules before being presented to the user.
