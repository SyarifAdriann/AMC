# Machine Learning Model - AMC Parking Stand Prediction System

## Table of Contents
1. [Model Overview](#model-overview)
2. [Model Training Process](#model-training-process)
3. [Input Parameters](#input-parameters)
4. [Output Format](#output-format)
5. [Feature Engineering](#feature-engineering)
6. [Model Files](#model-files)
7. [How to Retrain the Model](#how-to-retrain-the-model)
8. [Model Evaluation Metrics](#model-evaluation-metrics)
9. [Prediction Flow](#prediction-flow)
10. [Caching Strategy](#caching-strategy)
11. [Troubleshooting](#troubleshooting)

---

## Model Overview

### Model Type
**Random Forest Classifier** (`RandomForestClassifier` from scikit-learn)

### Model Version
**Current**: RF_REDO (Random Forest with Feature Engineering)
**File**: `ml/parking_stand_model_rf_redo.pkl`
**Training Date**: November 2025
**Accuracy**: 80.15% (Top-3 accuracy)

### Purpose
Predicts the **Top-3 most suitable parking stands** for an incoming aircraft based on:
- Aircraft type (e.g., B 738, A 320)
- Operating airline (e.g., GARUDA, BATIK AIR)
- Category (Commercial, Cargo, Charter)

### Why Random Forest?
- **Handles categorical features well** (aircraft types, airlines, stands)
- **Robust to overfitting** (ensemble method)
- **Provides probability scores** (not just classifications)
- **Fast prediction** (~4 seconds including PHP overhead)
- **Interpretable** (feature importance analysis)

---

## Model Training Process

### Training Script Location
**File**: `ml/train_model.py`

### Training Data Source
**Encoded Dataset**: `data/parking_history_encoded.csv`

**Data Format**:
```csv
aircraft_type_enc,operator_airline_enc,category_enc,airline_category_enc,aircraft_airline_enc,aircraft_category_enc,parking_stand_enc
5,12,1,3,45,8,10
7,15,0,5,52,4,12
...
```

**Note**: Data must be pre-encoded using label encoders before training.

### Training Steps (Automated in train_model.py)

```python
# 1. Load encoded training data
df = pd.read_csv('data/parking_history_encoded.csv')

# 2. Define features and target
feature_cols = [
    'aircraft_type_enc',
    'operator_airline_enc',
    'category_enc',
    'airline_category_enc',
    'aircraft_airline_enc',
    'aircraft_category_enc'
]
target_col = 'parking_stand_enc'

X = df[feature_cols]
y = df[target_col]

# 3. Train/test split (80/20, stratified)
X_train, X_test, y_train, y_test = train_test_split(
    X, y,
    test_size=0.2,
    random_state=42,
    stratify=y
)

# 4. Grid search for hyperparameter tuning
param_grid = {
    'criterion': ['gini', 'entropy'],
    'max_depth': [None, 10, 15, 20, 30, 40, 50],
    'min_samples_split': [2, 3, 5],
    'min_samples_leaf': [1, 2, 3],
}

dt = DecisionTreeClassifier(random_state=42)
cv = GridSearchCV(estimator=dt, param_grid=param_grid, cv=5, scoring='accuracy', n_jobs=-1)
cv.fit(X_train, y_train)

# 5. Extract best model
best_model = cv.best_estimator_

# 6. Save model to disk
with open('ml/parking_stand_model.pkl', 'wb') as f:
    pickle.dump(best_model, f)

# 7. Generate evaluation reports
# (see Model Evaluation Metrics section)
```

### Training Command
```bash
cd C:\xampp\htdocs\amc
python ml\train_model.py
```

### Training Output Files
1. **Model File**: `ml/parking_stand_model.pkl` (trained model)
2. **Metrics**: `reports/phase5_metrics.json` (accuracy, precision, recall)
3. **Feature Importance**: `reports/phase5_feature_importance.csv`
4. **Classification Report**: `reports/phase5_classification_report.txt`
5. **Confusion Matrix**: `reports/phase5_confusion_matrix.csv`
6. **Grid Search Results**: `reports/phase5_gridsearch_results.csv`
7. **Top-3 Predictions Sample**: `reports/phase5_top3_predictions.json`

---

## Input Parameters

### Required Fields (JSON format)
```json
{
  "aircraft_type": "B 738",
  "operator_airline": "GARUDA",
  "category": "COMMERCIAL"
}
```

### Field Specifications

#### 1. aircraft_type (string, required)
**Description**: ICAO aircraft type code
**Examples**: `"B 738"`, `"A 320"`, `"C 208"`, `"G V"`
**Case**: Case-insensitive (converted to uppercase)
**Max Length**: 30 characters

**Common Values**:
- Commercial: `B 738`, `A 320`, `ATR 72`
- Cargo: `B 733`, `CN 235`
- Charter: `G V`, `CL 850`, `FAL 7X`, `C 208`

#### 2. operator_airline (string, required)
**Description**: Operating airline name
**Examples**: `"GARUDA"`, `"BATIK AIR"`, `"CITILINK"`, `"TRI MG"`
**Case**: Case-insensitive (converted to uppercase)
**Max Length**: 100 characters

**Common Values**:
- Commercial: `GARUDA`, `BATIK AIR`, `CITILINK`, `SUSI AIR`, `PELITA AIR`
- Cargo: `TRI MG`, `TRIGANA`, `BBN`, `JAYAWIJAYA`
- Charter: `JETSET`, `JIP`, `PREMI`, `BIOMANTARA`

#### 3. category (string, required)
**Description**: Aircraft operation category
**Allowed Values**: `"COMMERCIAL"`, `"CARGO"`, `"CHARTER"`
**Case**: Case-insensitive
**Aliases**:
- `"KOMERSIAL"` → `"COMMERCIAL"` (Indonesian)
- `"PRIVATE"` → `"CHARTER"`

### Optional Fields (not used in current model)
```json
{
  "origin": "CGK",
  "destination": "DPS"
}
```

**Note**: Origin and destination are accepted but not used in prediction (reserved for future model versions).

---

## Output Format

### Successful Prediction Response

```json
{
  "success": true,
  "input": {
    "aircraft_type": "B 738",
    "operator_airline": "GARUDA",
    "category": "COMMERCIAL",
    "aircraft_size": "STANDARD",
    "airline_tier": "HIGH_FREQUENCY",
    "stand_zone": "RIGHT_COMMERCIAL"
  },
  "predictions": [
    {
      "stand": "C4",
      "probability": 0.45,
      "rank": 1
    },
    {
      "stand": "C3",
      "probability": 0.32,
      "rank": 2
    },
    {
      "stand": "C5",
      "probability": 0.15,
      "rank": 3
    }
  ],
  "metadata": {
    "model_path": "C:/xampp/htdocs/amc/ml/parking_stand_model_rf_redo.pkl",
    "encoder_versions": [
      "enc_aircraft_type.pkl",
      "enc_aircraft_size.pkl",
      "enc_operator_airline.pkl",
      "enc_airline_tier.pkl",
      "enc_category.pkl",
      "enc_stand_zone.pkl",
      "enc_parking_stand.pkl"
    ],
    "top_k_requested": 3
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": "aircraft_type, operator_airline, and category are required fields",
  "type": "ValueError"
}
```

### Response Fields

#### success (boolean)
- `true`: Prediction successful
- `false`: Error occurred

#### input (object)
**Original input fields plus engineered features**:
- `aircraft_type`: Input aircraft type (uppercase)
- `operator_airline`: Input airline (uppercase)
- `category`: Input category (normalized)
- `aircraft_size`: Engineered feature (`SMALL_A0_COMPATIBLE` or `STANDARD`)
- `airline_tier`: Engineered feature (`HIGH_FREQUENCY`, `MEDIUM_FREQUENCY`, `LOW_FREQUENCY`)
- `stand_zone`: Engineered feature (`LEFT_CARGO`, `RIGHT_COMMERCIAL`, `MIDDLE_CHARTER`)

#### predictions (array of objects)
**Top-K predictions ranked by probability**:
- `stand` (string): Predicted parking stand name (e.g., `"C4"`)
- `probability` (float): Prediction confidence (0.0 to 1.0)
- `rank` (integer): Ranking (1=best, 2=second-best, 3=third-best)

**Note**: Probabilities sum to ~1.0 across all classes, not just Top-K.

#### metadata (object)
- `model_path`: Full path to model file
- `encoder_versions`: List of encoder files used
- `top_k_requested`: Number of predictions requested (default: 3)

---

## Feature Engineering

### Engineered Features

The model uses **6 input features** (3 raw + 3 engineered):

#### 1. aircraft_size (engineered from aircraft_type)
**Function**: `determine_aircraft_size(aircraft_type)`
**Location**: `ml/predict.py` lines 81-98

**Logic**:
```python
A0_COMPATIBLE = [
    'C 152', 'C 172', 'C 182', 'C 185', 'C 206', 'C 208',
    'C 402', 'C 404', 'C 425', 'PC 6', 'PC 12',
    'CESSNA', 'PILATUS'
]

if aircraft_type in A0_COMPATIBLE:
    return 'SMALL_A0_COMPATIBLE'
else:
    return 'STANDARD'
```

**Purpose**: Small aircraft (A0-compatible) use different stands than large aircraft.

**⚠️ HARDCODED**: A0-compatible list is hardcoded. To add new types, edit `ml/predict.py` line 83.

#### 2. airline_tier (engineered from operator_airline)
**Function**: `determine_airline_tier(operator_airline)`
**Location**: `ml/predict.py` lines 100-114

**Logic**:
```python
HIGH_FREQ_AIRLINES = ['BATIK AIR', 'CITILINK', 'GARUDA', 'TRIGANA', 'TRI MG']
MEDIUM_FREQ_AIRLINES = ['PELITA', 'JETSET', 'KARISMA', 'JIP', 'PREMI', 'SUSI AIR']

if airline in HIGH_FREQ_AIRLINES:
    return 'HIGH_FREQUENCY'
elif airline in MEDIUM_FREQ_AIRLINES:
    return 'MEDIUM_FREQUENCY'
else:
    return 'LOW_FREQUENCY'
```

**Purpose**: High-frequency airlines get priority stands (more predictable patterns).

**⚠️ HARDCODED**: Airline tiers are hardcoded. To update, edit `ml/predict.py` lines 104-105.

#### 3. stand_zone (engineered from category)
**Function**: `get_stand_zone(category)`
**Location**: `ml/predict.py` lines 130-137

**Logic**:
```python
if category == 'COMMERCIAL':
    return 'RIGHT_COMMERCIAL'
elif category == 'CARGO':
    return 'LEFT_CARGO'
else:
    return 'MIDDLE_CHARTER'
```

**Purpose**: Different categories are typically assigned to different apron zones.

**⚠️ HARDCODED**: Zone mapping is hardcoded. To change, edit `ml/predict.py` line 132.

### Feature Vector Construction

**Function**: `build_feature_vector(payload)`
**Location**: `ml/predict.py` lines 139-162

**Process**:
```python
# 1. Extract raw inputs
aircraft_type = payload['aircraft_type'].strip().upper()
operator_airline = payload['operator_airline'].strip().upper()
category = payload['category'].strip().upper()

# 2. Normalize category
category_map = {'KOMERSIAL': 'COMMERCIAL', 'PRIVATE': 'CHARTER'}
category = category_map.get(category, category)

# 3. Engineer features
aircraft_size = determine_aircraft_size(aircraft_type)
airline_tier = determine_airline_tier(operator_airline)
stand_zone = get_stand_zone(category)

# 4. Return feature dictionary
return {
    'aircraft_type': aircraft_type,
    'operator_airline': operator_airline,
    'category': category,
    'aircraft_size': aircraft_size,
    'airline_tier': airline_tier,
    'stand_zone': stand_zone,
}
```

### Label Encoding

**All features must be encoded** to integer indices before prediction.

**Encoder Files**: `ml/encoders_redo.pkl` (contains all 7 encoders)

**Encoder Names**:
1. `aircraft_type` - Maps aircraft types to integers
2. `aircraft_size` - Maps 'SMALL_A0_COMPATIBLE'/'STANDARD' to integers
3. `operator_airline` - Maps airlines to integers
4. `airline_tier` - Maps 'HIGH_FREQUENCY'/'MEDIUM_FREQUENCY'/'LOW_FREQUENCY' to integers
5. `category` - Maps 'COMMERCIAL'/'CARGO'/'CHARTER' to integers
6. `stand_zone` - Maps 'LEFT_CARGO'/'RIGHT_COMMERCIAL'/'MIDDLE_CHARTER' to integers
7. `parking_stand` - Maps stand names to integers (for decoding predictions)

**Encoding Function**: `to_index(name, value)`
**Location**: `ml/predict.py` lines 60-71

**Logic**:
```python
encoder = get_encoder(name)
classes = encoder.classes_
lookup = {cls: idx for idx, cls in enumerate(classes)}

if value in lookup:
    return lookup[value]
elif '__UNKNOWN__' in lookup:
    return lookup['__UNKNOWN__']
else:
    return 0  # Fallback
```

**⚠️ IMPORTANT**: If a new aircraft type or airline is not in the encoder, it falls back to index 0. This can cause incorrect predictions.

### Decoding Predictions

**Function**: `decode_stand(index)`
**Location**: `ml/predict.py` lines 73-78

**Logic**:
```python
encoder = get_encoder('parking_stand')
classes = encoder.classes_

if 0 <= index < len(classes):
    return classes[index]
else:
    return classes[0]  # Fallback
```

---

## Model Files

### ⚠️ CRITICAL FILES (DO NOT DELETE)

#### 1. parking_stand_model_rf_redo.pkl
**Location**: `ml/parking_stand_model_rf_redo.pkl`
**Size**: ~500 KB
**Format**: Python pickle file (scikit-learn RandomForestClassifier)
**Purpose**: Trained Random Forest model

**⚠️ WARNING**: Deleting this file breaks all predictions. System will fail with `FileNotFoundError`.

#### 2. encoders_redo.pkl
**Location**: `ml/encoders_redo.pkl`
**Size**: ~50 KB
**Format**: Python pickle file (dictionary of LabelEncoders)
**Purpose**: Maps feature values to integer indices

**⚠️ WARNING**: Deleting this file breaks feature encoding. Predictions will fail with `KeyError`.

### Model Cache (In-Memory)

**File**: `ml/model_cache.py`
**Purpose**: Caches loaded model in Python process memory to avoid repeated disk reads

**Cache Duration**: 1 hour (3600 seconds)

**How It Works**:
```python
_model_cache = {
    'model': None,
    'encoders': {},
    'timestamp': 0
}

if cache is valid (< 1 hour old):
    return cached model
else:
    load from disk
    update cache
    return model
```

**⚠️ Note**: Cache is per-process. If Python process restarts, cache is cleared.

### Backup Strategy

**⚠️ ALWAYS backup model files before retraining**:
```bash
cd C:\xampp\htdocs\amc\ml
copy parking_stand_model_rf_redo.pkl parking_stand_model_rf_redo.pkl.backup
copy encoders_redo.pkl encoders_redo.pkl.backup
```

---

## How to Retrain the Model

### Prerequisites
1. Python 3.13.5 installed
2. scikit-learn, numpy, pandas installed
3. Training dataset prepared (`data/parking_history_encoded.csv`)
4. Backup existing model files

### Step-by-Step Retraining Process

#### Step 1: Backup Current Model
```bash
cd C:\xampp\htdocs\amc\ml
copy parking_stand_model_rf_redo.pkl parking_stand_model_rf_redo_backup_%date%.pkl
copy encoders_redo.pkl encoders_redo_backup_%date%.pkl
```

#### Step 2: Prepare Training Dataset

**Option A: Refresh from Database**
```bash
cd C:\xampp\htdocs\amc
python tools\refresh_dataset.py
```

This script:
1. Connects to `amc` database
2. Exports `aircraft_movements` + `aircraft_details` JOIN
3. Encodes features using existing encoders
4. Saves to `data/parking_history_encoded.csv`

**Option B: Manual CSV Preparation**

Create `data/parking_history_encoded.csv` with columns:
```
aircraft_type_enc,operator_airline_enc,category_enc,airline_category_enc,aircraft_airline_enc,aircraft_category_enc,parking_stand_enc
```

#### Step 3: Run Training Script
```bash
cd C:\xampp\htdocs\amc
python ml\train_model.py
```

**Expected Output**:
```
Model training complete.
{
  "timestamp": "2025-11-24T10:00:00Z",
  "train_accuracy": 0.95,
  "test_accuracy": 0.82,
  "precision_macro": 0.80,
  "recall_macro": 0.78,
  "top3_accuracy": 0.8015,
  "baseline_accuracy": 0.25,
  "best_params": {...},
  "train_size": 1600,
  "test_size": 400
}
```

#### Step 4: Verify Model Files Created
```bash
dir ml\parking_stand_model.pkl
dir reports\phase5_metrics.json
dir reports\phase5_feature_importance.csv
```

#### Step 5: Test Prediction
```bash
cd C:\xampp\htdocs\amc
python ml\test_predict.py
```

**Test Input** (edit `ml/test_predict.py`):
```python
payload = {
    "aircraft_type": "B 738",
    "operator_airline": "GARUDA",
    "category": "COMMERCIAL"
}
```

**Expected Output**:
```json
{
  "success": true,
  "predictions": [
    {"stand": "C4", "probability": 0.45, "rank": 1},
    {"stand": "C3", "probability": 0.32, "rank": 2},
    {"stand": "C5", "probability": 0.15, "rank": 3}
  ]
}
```

#### Step 6: Update Model Version in Database
```sql
INSERT INTO ml_model_versions (
    version_number,
    model_filename,
    accuracy_score,
    training_date,
    notes,
    is_active,
    created_by
) VALUES (
    3,  -- Increment version
    'parking_stand_model_rf_redo.pkl',
    0.8015,  -- From training output
    NOW(),
    'Random Forest with updated training data',
    1,  -- Set as active
    1   -- Admin user ID
);

-- Deactivate old version
UPDATE ml_model_versions SET is_active = 0 WHERE version_number < 3;
```

#### Step 7: Deploy to Production

**Option A: Same server (XAMPP)**
- Model is already in place (`ml/parking_stand_model_rf_redo.pkl`)
- Clear Python model cache by restarting Apache
- Clear PHP cache:
  ```bash
  php tools\cleanup_cache.php
  ```

**Option B: Different server**
```bash
# Copy model files to production server
scp ml/parking_stand_model_rf_redo.pkl user@prod:/path/to/amc/ml/
scp ml/encoders_redo.pkl user@prod:/path/to/amc/ml/

# Restart web server
sudo systemctl restart apache2
```

---

## Model Evaluation Metrics

### Metrics Calculated During Training

**File**: `reports/phase5_metrics.json`

```json
{
  "timestamp": "2025-11-10T15:00:00Z",
  "train_accuracy": 0.9500,
  "test_accuracy": 0.8200,
  "precision_macro": 0.8000,
  "recall_macro": 0.7800,
  "top3_accuracy": 0.8015,
  "baseline_accuracy": 0.2500,
  "best_params": {
    "criterion": "gini",
    "max_depth": 20,
    "min_samples_split": 2,
    "min_samples_leaf": 1
  },
  "n_splits": 5,
  "train_size": 1600,
  "test_size": 400
}
```

### Metric Definitions

#### train_accuracy
**Definition**: Accuracy on training set
**Formula**: `correct_predictions / total_predictions`
**Current**: 95.00%
**Interpretation**: Model fits training data very well (possible overfitting if too high)

#### test_accuracy
**Definition**: Accuracy on test set (unseen data)
**Formula**: `correct_predictions / total_predictions`
**Current**: 82.00%
**Interpretation**: Top-1 prediction accuracy (single best stand)

#### top3_accuracy (⚠️ MOST IMPORTANT)
**Definition**: Accuracy if any of Top-3 predictions is correct
**Formula**: `(predictions where true_label in top_3) / total_predictions`
**Current**: 80.15%
**Interpretation**: **System success rate** (user chooses from Top-3)

**⚠️ TARGET**: Keep above 75% for user satisfaction

#### precision_macro
**Definition**: Average precision across all stand classes
**Formula**: `average(TP / (TP + FP) for each class)`
**Current**: 80.00%
**Interpretation**: How many predicted stands are correct

#### recall_macro
**Definition**: Average recall across all stand classes
**Formula**: `average(TP / (TP + FN) for each class)`
**Current**: 78.00%
**Interpretation**: How many actual stands are predicted

#### baseline_accuracy
**Definition**: Accuracy if always predicting most common stand
**Current**: 25.00%
**Interpretation**: Model is 3.3x better than baseline

### Feature Importance

**File**: `reports/phase5_feature_importance.csv`

**Example**:
```csv
feature,importance
operator_airline_enc,0.35
aircraft_type_enc,0.30
category_enc,0.15
airline_tier_enc,0.10
aircraft_size_enc,0.05
stand_zone_enc,0.05
```

**Interpretation**:
- **operator_airline** (35%): Most important feature (airline preference)
- **aircraft_type** (30%): Second most important (aircraft size compatibility)
- **category** (15%): Third most important (zone assignment)

### Real-Time Performance Tracking

**Database**: `ml_prediction_log` table

**Query for Current Model Accuracy**:
```sql
SELECT
    COUNT(*) AS total_predictions,
    SUM(was_prediction_correct) AS correct_predictions,
    (SUM(was_prediction_correct) / COUNT(*)) * 100 AS accuracy_percentage
FROM ml_prediction_log
WHERE was_prediction_correct IS NOT NULL
  AND model_version = 2;  -- Current model version
```

**Expected Result**:
```
total_predictions: 150
correct_predictions: 120
accuracy_percentage: 80.00
```

**⚠️ Note**: Only includes predictions with feedback (actual stand assigned).

---

## Prediction Flow

### End-to-End Flow Diagram

```
User Action: Click "Recommend Stand" button in modal
    ↓
Frontend JS: Collect input (aircraft_type, operator_airline, category)
    ↓
Frontend JS: POST /api/apron/recommend with JSON payload
    ↓
PHP ApronController::recommend()
    ↓
Check PHP FileCache (5-minute TTL)
    ├─ Cache HIT → Return cached predictions (skip Python)
    └─ Cache MISS → Continue to Python execution
        ↓
    Build JSON payload
        ↓
    Execute: proc_open("python ml/predict.py --top_k 3")
        ↓
    Pass JSON to Python via STDIN
        ↓
    Python: ml/predict.py
        ↓
    Load model from cache or disk (1-hour cache)
        ├─ Cache HIT → Use cached model
        └─ Cache MISS → Load from parking_stand_model_rf_redo.pkl
            ↓
    Load encoders from encoders_redo.pkl
        ↓
    Build feature vector (raw + engineered features)
        ↓
    Encode features to integer indices
        ↓
    Run prediction: model.predict_proba()
        ↓
    Get Top-K predictions (sorted by probability)
        ↓
    Decode stand indices to stand names
        ↓
    Build JSON response
        ↓
    Output to STDOUT
        ↓
PHP: Capture STDOUT
    ↓
PHP: Decode JSON response
    ↓
PHP: Store in FileCache (5-minute TTL)
    ↓
PHP: Log to ml_prediction_log table
    ↓
PHP: Return JSON response to frontend
    ↓
Frontend JS: Display recommendations in modal
```

### Prediction Timing Breakdown

**Total Time**: ~4 seconds (including PHP overhead)

**Breakdown**:
1. PHP processing: ~0.5 seconds
2. Python startup: ~1.0 seconds
3. Model loading (first time): ~1.5 seconds
4. Model loading (cached): ~0.1 seconds
5. Feature engineering: ~0.1 seconds
6. Prediction: ~0.5 seconds
7. JSON encoding/decoding: ~0.2 seconds
8. Cache storage: ~0.1 seconds

**⚠️ Performance Notes**:
- First prediction after server restart: ~4 seconds (cold start)
- Subsequent predictions (cached): ~0.5 seconds (cache hit)
- Python model cache expires after 1 hour
- PHP cache expires after 5 minutes

---

## Caching Strategy

### Two-Level Caching

#### Level 1: PHP FileCache (5-minute TTL)
**Location**: `cache/` directory
**Purpose**: Avoid repeated Python execution for identical inputs
**Key Format**: `md5(aircraft_type + operator_airline + category)`

**Example**:
```php
$cacheKey = 'ml_predict_' . md5($aircraftType . $operatorAirline . $category);
$cached = $cache->get($cacheKey);

if ($cached !== null) {
    return $cached;  // Cache hit
}

// Cache miss: execute Python
$predictions = executePythonPrediction(...);
$cache->set($cacheKey, $predictions, 300);  // 5-minute TTL
```

**⚠️ Cache Invalidation**:
- **Time-based**: Expires after 5 minutes
- **Manual**: Run `php tools/cleanup_cache.php`
- **Automatic**: Never (no model change detection)

**⚠️ PROBLEM**: If model is retrained, old predictions remain cached for 5 minutes. Solution: Clear cache after retraining.

#### Level 2: Python Model Cache (1-hour TTL)
**Location**: In-memory (Python process)
**Purpose**: Avoid repeated disk reads for model files
**File**: `ml/model_cache.py`

**Implementation**:
```python
_model_cache = {
    'model': None,
    'encoders': {},
    'timestamp': 0
}

CACHE_DURATION = 3600  # 1 hour

def load_model_and_encoders_from_cache():
    if _model_cache['model'] and (time.time() - _model_cache['timestamp']) < CACHE_DURATION:
        return _model_cache['model'], _model_cache['encoders']

    # Cache miss: load from disk
    with open('ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
        model = pickle.load(f)

    with open('ml/encoders_redo.pkl', 'rb') as f:
        encoders = pickle.load(f)

    _model_cache = {
        'model': model,
        'encoders': encoders,
        'timestamp': time.time()
    }

    return model, encoders
```

**⚠️ Cache Invalidation**:
- **Time-based**: Expires after 1 hour
- **Process restart**: Cleared when Python process exits
- **Manual**: Restart Apache (restarts PHP-FPM, which spawns new Python processes)

### Cache Clear Commands

**Clear PHP cache**:
```bash
php tools\cleanup_cache.php
```

**Clear Python cache** (restart Apache):
```bash
# Windows (XAMPP)
C:\xampp\apache\bin\httpd.exe -k restart

# Linux
sudo systemctl restart apache2
```

---

## Troubleshooting

### Common Issues

#### 1. "Model file not found"
**Error**: `FileNotFoundError: Model file not found at ml/parking_stand_model_rf_redo.pkl`

**Cause**: Model file deleted or wrong path

**Fix**:
1. Check if file exists:
   ```bash
   dir C:\xampp\htdocs\amc\ml\parking_stand_model_rf_redo.pkl
   ```
2. Restore from backup or retrain model

#### 2. "Encoder not found"
**Error**: `ValueError: Encoder aircraft_type not found in blended encoders`

**Cause**: Encoders file missing or corrupted

**Fix**:
1. Check if file exists:
   ```bash
   dir C:\xampp\htdocs\amc\ml\encoders_redo.pkl
   ```
2. Restore from backup or regenerate encoders

#### 3. "Python not found"
**Error**: `proc_open(): CreateProcess failed`

**Cause**: Python not in system PATH

**Fix**:
1. Verify Python installation:
   ```bash
   python --version
   ```
2. Add Python to PATH or hardcode path in ApronController.php:
   ```php
   $pythonPath = 'C:\\Python313\\python.exe';  // Full path
   ```

#### 4. "Prediction returns null"
**Error**: `predictions` field is `null` or empty

**Cause**: Python script crashed or returned invalid JSON

**Fix**:
1. Check error logs:
   ```bash
   type C:\xampp\htdocs\amc\storage\logs\error.log
   ```
2. Test Python script directly:
   ```bash
   echo {"aircraft_type":"B 738","operator_airline":"GARUDA","category":"COMMERCIAL"} | python ml\predict.py --top_k 3
   ```
3. Check stderr output in PHP:
   ```php
   error_log("Python stderr: " . $errors);
   ```

#### 5. "Prediction very slow"
**Symptom**: Prediction takes > 10 seconds

**Cause**: Model not cached, disk I/O slow, or Python startup slow

**Fix**:
1. Check if cache is working:
   ```python
   # Add debug logging in model_cache.py
   print("Loading model from cache..." if cached else "Loading model from disk...")
   ```
2. Verify cache TTL not expired
3. Check disk performance (SSD recommended)
4. Pre-warm cache by making a test prediction after deployment

#### 6. "Incorrect predictions"
**Symptom**: Predictions don't match expected stands

**Cause**: Model not trained on latest data or feature engineering logic changed

**Fix**:
1. Check model version:
   ```sql
   SELECT * FROM ml_model_versions WHERE is_active = 1;
   ```
2. Check training date (model may be outdated)
3. Retrain model with latest data
4. Verify feature engineering logic matches training data

#### 7. "Unknown aircraft type"
**Symptom**: Prediction fails for new aircraft type not in training data

**Cause**: New aircraft type not in encoder

**Fix**:
1. Add to training dataset
2. Retrain model
3. Or: Update encoder to handle unknown values:
   ```python
   if value not in encoder.classes_:
       return 0  # Fallback to most common encoding
   ```

---

## Advanced Topics

### Model Hyperparameters (from Grid Search)

**Best Parameters** (from `reports/phase5_metrics.json`):
```json
{
  "criterion": "gini",
  "max_depth": 20,
  "min_samples_split": 2,
  "min_samples_leaf": 1
}
```

**Hyperparameter Tuning**:
- **criterion**: Gini impurity (faster than entropy)
- **max_depth**: 20 levels (prevents overfitting)
- **min_samples_split**: 2 samples (allows fine-grained splits)
- **min_samples_leaf**: 1 sample (no minimum leaf size)

**To Change Hyperparameters**:
Edit `ml/train_model.py` line 60-65:
```python
param_grid = {
    'criterion': ['gini', 'entropy'],
    'max_depth': [None, 10, 15, 20, 30, 40, 50],
    'min_samples_split': [2, 3, 5],
    'min_samples_leaf': [1, 2, 3],
}
```

### Model Interpretability

**Feature Importance Analysis**:
```bash
type reports\phase5_feature_importance.csv
```

**Confusion Matrix Analysis**:
```bash
type reports\phase5_confusion_matrix.csv
```

**Classification Report**:
```bash
type reports\phase5_classification_report.txt
```

### Future Improvements

**Potential Enhancements**:
1. **Add temporal features**: Day of week, time of day, season
2. **Add flight route features**: Origin, destination, route popularity
3. **Add stand attributes**: Stand size, stand equipment, gate vs remote
4. **Use ensemble methods**: Combine Random Forest with XGBoost
5. **Implement online learning**: Update model incrementally with new data
6. **Add feedback loop**: Automatically retrain when accuracy drops
7. **A/B testing**: Deploy multiple models and compare performance

---

## Summary

**Model Type**: Random Forest Classifier
**Accuracy**: 80.15% (Top-3)
**Prediction Time**: ~4 seconds (cold start), ~0.5 seconds (cached)
**Input**: Aircraft type, airline, category
**Output**: Top-3 parking stand recommendations
**Caching**: Two-level (PHP 5-min, Python 1-hour)
**Retraining**: Manual, requires backup and verification

**⚠️ CRITICAL FILES**:
- `ml/parking_stand_model_rf_redo.pkl` - Trained model
- `ml/encoders_redo.pkl` - Label encoders
- `ml/predict.py` - Main prediction script

**⚠️ ALWAYS**:
- Backup model files before retraining
- Clear cache after retraining
- Test predictions after deployment
- Monitor real-time accuracy in `ml_prediction_log` table
