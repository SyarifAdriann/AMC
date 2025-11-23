"""
KDD PROCESS REDO - Step 2: Feature Encoding & Model Training
This script trains a Random Forest model with GridSearchCV and evaluates performance
"""

import pandas as pd
import numpy as np
import pickle
import sys
import io
from sklearn.preprocessing import LabelEncoder
from sklearn.model_selection import train_test_split, GridSearchCV
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
import matplotlib.pyplot as plt
import seaborn as sns
import warnings

warnings.filterwarnings('ignore')

# Fix Windows console encoding for Unicode characters
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

print("="*80)
print("KDD PROCESS REDO - STEP 2: FEATURE ENCODING & MODEL TRAINING")
print("="*80)

# ============================================================================
# 1. LOAD PREPROCESSED DATA
# ============================================================================
print("\n[1/7] Loading preprocessed data...")
df_processed = pd.read_csv('data/parking_history_preprocessed_redo.csv')
print(f"✓ Loaded {len(df_processed)} rows")
print(f"  Columns: {df_processed.columns.tolist()}")

# ============================================================================
# 2. ENCODE FEATURES
# ============================================================================
print("\n[2/7] Encoding categorical features...")

encoders = {}

features_to_encode = [
    'aircraft_type',
    'aircraft_size',
    'operator_airline',
    'airline_tier',
    'category',
    'stand_zone'
]

# Encode features
for feature in features_to_encode:
    enc = LabelEncoder()
    df_processed[f'{feature}_enc'] = enc.fit_transform(df_processed[feature])
    encoders[feature] = enc

    print(f"\n  {feature}:")
    print(f"    Unique values: {len(enc.classes_)}")
    if len(enc.classes_) <= 10:
        print(f"    Classes: {enc.classes_.tolist()}")

# Encode target
enc_stand = LabelEncoder()
df_processed['parking_stand_enc'] = enc_stand.fit_transform(df_processed['parking_stand'])
encoders['parking_stand'] = enc_stand

print(f"\n  parking_stand (TARGET):")
print(f"    Unique values: {len(enc_stand.classes_)}")
print(f"    Classes: {enc_stand.classes_.tolist()}")

# Save all encoders
with open('ml/encoders_redo.pkl', 'wb') as f:
    pickle.dump(encoders, f)

print("\n✓ All encoders saved to ml/encoders_redo.pkl")

# Save encoded data
df_processed.to_csv('data/parking_history_encoded_redo.csv', index=False)
print("✓ Encoded data saved to data/parking_history_encoded_redo.csv")

# ============================================================================
# 3. PREPARE TRAIN/TEST SPLIT
# ============================================================================
print("\n[3/7] Preparing train/test split...")

feature_cols = [
    'aircraft_type_enc',
    'aircraft_size_enc',
    'operator_airline_enc',
    'airline_tier_enc',
    'category_enc',
    'stand_zone_enc'
]

X = df_processed[feature_cols]
y = df_processed['parking_stand_enc']

# Stratified split to preserve class distribution
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)

print(f"✓ Training set: {len(X_train)} samples ({len(X_train)/len(X)*100:.1f}%)")
print(f"✓ Test set: {len(X_test)} samples ({len(X_test)/len(X)*100:.1f}%)")

# ============================================================================
# 4. TRAIN RANDOM FOREST WITH GRID SEARCH
# ============================================================================
print("\n[4/7] Training Random Forest with GridSearchCV...")
print("  This may take several minutes...")

# Define parameter grid
param_grid = {
    'n_estimators': [100, 200],
    'max_depth': [None, 20, 30],
    'min_samples_leaf': [2, 5, 10],
    'min_samples_split': [5, 10],
    'class_weight': ['balanced', 'balanced_subsample']
}

# Base model
rf_base = RandomForestClassifier(random_state=42, n_jobs=-1)

# Grid search with cross-validation
grid_search = GridSearchCV(
    rf_base,
    param_grid,
    cv=5,
    scoring='accuracy',
    verbose=1,
    n_jobs=-1
)

print("\n  Starting GridSearchCV...")
grid_search.fit(X_train, y_train)

print("\n✓ Best parameters found:")
for param, value in grid_search.best_params_.items():
    print(f"  {param}: {value}")
print(f"\n✓ Best CV accuracy: {grid_search.best_score_:.4f}")

# Train final model with best parameters
model = grid_search.best_estimator_

# ============================================================================
# 5. EVALUATE MODEL - BASIC ACCURACY
# ============================================================================
print("\n[5/7] Evaluating model performance...")

train_acc = model.score(X_train, y_train)
test_acc = model.score(X_test, y_test)

print(f"\n✓ Training accuracy: {train_acc:.4f}")
print(f"✓ Test accuracy (Top-1): {test_acc:.4f}")

# ============================================================================
# 6. CALCULATE TOP-K ACCURACY
# ============================================================================
print("\n[6/7] Calculating Top-K accuracy...")

def calculate_top_k_accuracy(model, X, y_true, k=3):
    """Calculate top-k accuracy"""
    # Get probability predictions for all classes
    y_proba = model.predict_proba(X)

    correct = 0
    for i, true_label in enumerate(y_true):
        # Get top-k predictions
        top_k_indices = np.argsort(y_proba[i])[-k:][::-1]

        if true_label in top_k_indices:
            correct += 1

    return correct / len(y_true)

# Calculate top-k accuracies
top1_acc = calculate_top_k_accuracy(model, X_test, y_test, k=1)
top3_acc = calculate_top_k_accuracy(model, X_test, y_test, k=3)
top5_acc = calculate_top_k_accuracy(model, X_test, y_test, k=5)

print("="*80)
print("🎯 TOP-K ACCURACY RESULTS (REDO)")
print("="*80)
print(f"Top-1 Accuracy (exact match):     {top1_acc:.2%}")
print(f"Top-3 Accuracy (TARGET METRIC):  {top3_acc:.2%}")
print(f"Top-5 Accuracy:                   {top5_acc:.2%}")
print("="*80)

if top3_acc >= 0.80:
    print("\n✅ SUCCESS: Model meets 80% top-3 accuracy threshold!")
elif top3_acc >= 0.70:
    print("\n⚠️  CLOSE: Model at 70-79%. Consider synthetic data augmentation.")
else:
    print("\n❌ BELOW TARGET: Consider hybrid rules-based approach.")

# ============================================================================
# 7. DETAILED EVALUATION
# ============================================================================
print("\n[7/7] Generating detailed evaluation metrics...")

# Performance by airline tier
print("\n📊 Performance by Airline Tier:")
print("-" * 60)

for tier in ['HIGH_FREQUENCY', 'MEDIUM_FREQUENCY', 'LOW_FREQUENCY']:
    # Get test samples for this tier
    tier_mask = df_processed.loc[X_test.index, 'airline_tier'] == tier

    if tier_mask.sum() == 0:
        continue

    X_tier = X_test[tier_mask]
    y_tier = y_test[tier_mask]

    tier_top3 = calculate_top_k_accuracy(model, X_tier, y_tier, k=3)

    print(f"  {tier:20s}: {tier_top3:.2%} (n={len(X_tier)} samples)")

# A0 Small Aircraft Rule Validation
print("\n✈️  A0-Compatible Aircraft Performance:")
print("-" * 60)

a0_mask = df_processed.loc[X_test.index, 'aircraft_size'] == 'SMALL_A0_COMPATIBLE'

if a0_mask.sum() > 0:
    X_a0 = X_test[a0_mask]
    y_a0 = y_test[a0_mask]

    # Get predictions
    y_a0_proba = model.predict_proba(X_a0)

    # Check if A0 appears in top-3 for these aircraft
    if 'A0' in encoders['parking_stand'].classes_:
        a0_stand_idx = list(encoders['parking_stand'].classes_).index('A0')

        a0_in_top3 = 0
        for proba in y_a0_proba:
            top3_indices = np.argsort(proba)[-3:][::-1]
            if a0_stand_idx in top3_indices:
                a0_in_top3 += 1

        a0_recommendation_rate = a0_in_top3 / len(y_a0_proba)

        print(f"  A0 appears in top-3 for small aircraft: {a0_recommendation_rate:.2%}")
        print(f"  Total small aircraft test samples: {len(y_a0)}")
    else:
        print("  A0 stand not found in encoders")
else:
    print("  No small aircraft in test set")

# Feature Importance
print("\n📊 Feature Importance Rankings:")
print("-" * 60)

importances = model.feature_importances_
feature_names = [
    'Aircraft Type',
    'Aircraft Size',
    'Airline',
    'Airline Tier',
    'Category',
    'Stand Zone'
]

importance_df = pd.DataFrame({
    'Feature': feature_names,
    'Importance': importances
}).sort_values('Importance', ascending=False)

print(importance_df.to_string(index=False))

# Plot feature importance
plt.figure(figsize=(10, 6))
plt.barh(importance_df['Feature'], importance_df['Importance'], color='skyblue')
plt.xlabel('Importance Score')
plt.title('Feature Importance in Stand Prediction (REDO)')
plt.gca().invert_yaxis()
plt.tight_layout()
plt.savefig('ml/feature_importance_rf_redo.png', dpi=300)
print("\n✓ Feature importance chart saved to ml/feature_importance_rf_redo.png")

# Confusion Matrix
print("\n📋 Generating confusion matrix...")
y_pred = model.predict(X_test)
cm = confusion_matrix(y_test, y_pred)
stand_names = encoders['parking_stand'].classes_

plt.figure(figsize=(16, 14))
sns.heatmap(
    cm,
    annot=True,
    fmt='d',
    cmap='Blues',
    xticklabels=stand_names,
    yticklabels=stand_names,
    cbar_kws={'label': 'Count'}
)
plt.title('Confusion Matrix - Actual vs Predicted Stand (REDO)', fontsize=16)
plt.xlabel('Predicted Stand', fontsize=12)
plt.ylabel('Actual Stand', fontsize=12)
plt.tight_layout()
plt.savefig('ml/confusion_matrix_rf_redo.png', dpi=300)
print("✓ Confusion matrix saved to ml/confusion_matrix_rf_redo.png")

# Classification Report
print("\n📋 Classification Report:")
print(classification_report(y_test, y_pred, target_names=stand_names, zero_division=0))

# ============================================================================
# 8. SAVE MODEL
# ============================================================================
print("\n" + "="*80)
print("SAVING MODEL")
print("="*80)

with open('ml/parking_stand_model_rf_redo.pkl', 'wb') as f:
    pickle.dump(model, f)

print("✓ Model saved to ml/parking_stand_model_rf_redo.pkl")

# Save summary results
results_summary = {
    'top1_accuracy': float(top1_acc),
    'top3_accuracy': float(top3_acc),
    'top5_accuracy': float(top5_acc),
    'best_params': grid_search.best_params_,
    'best_cv_score': float(grid_search.best_score_),
    'train_accuracy': float(train_acc),
    'test_accuracy': float(test_acc),
    'feature_importance': {name: float(imp) for name, imp in zip(feature_names, importances)}
}

import json
with open('ml/results_summary_redo.json', 'w') as f:
    json.dump(results_summary, f, indent=2)

print("✓ Results summary saved to ml/results_summary_redo.json")

# ============================================================================
# FINAL SUMMARY
# ============================================================================
print("\n" + "="*80)
print("TRAINING COMPLETE - FINAL SUMMARY")
print("="*80)
print(f"✓ Model: Random Forest")
print(f"✓ Features: 6 (engineered)")
print(f"✓ Train samples: {len(X_train)}")
print(f"✓ Test samples: {len(X_test)}")
print(f"✓ Top-1 Accuracy: {top1_acc:.2%}")
print(f"✓ Top-3 Accuracy: {top3_acc:.2%} {'✅ MEETS TARGET!' if top3_acc >= 0.80 else '❌ BELOW TARGET'}")
print(f"✓ Top-5 Accuracy: {top5_acc:.2%}")
print(f"✓ Best CV Score: {grid_search.best_score_:.4f}")
print("\n✓ Output files:")
print(f"  - ml/parking_stand_model_rf_redo.pkl")
print(f"  - ml/encoders_redo.pkl")
print(f"  - ml/feature_importance_rf_redo.png")
print(f"  - ml/confusion_matrix_rf_redo.png")
print(f"  - ml/results_summary_redo.json")
print("="*80)
