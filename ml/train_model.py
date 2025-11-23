#!/usr/bin/env python3
"""Train Decision Tree model for AMC parking stand recommendations."""

import json
import pickle
from datetime import datetime
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.metrics import (
    accuracy_score,
    classification_report,
    confusion_matrix,
    precision_score,
    recall_score,
    top_k_accuracy_score,
)
from sklearn.model_selection import GridSearchCV, train_test_split
from sklearn.tree import DecisionTreeClassifier

ROOT = Path(__file__).resolve().parents[1]
DATA_ENCODED = ROOT / "data" / "parking_history_encoded.csv"
MODEL_PATH = ROOT / "ml" / "parking_stand_model.pkl"
FEATURE_IMPORTANCE_CSV = ROOT / "reports" / "phase5_feature_importance.csv"
METRICS_JSON = ROOT / "reports" / "phase5_metrics.json"
CLASSIFICATION_REPORT_TXT = ROOT / "reports" / "phase5_classification_report.txt"
CONFUSION_MATRIX_CSV = ROOT / "reports" / "phase5_confusion_matrix.csv"
CV_RESULTS_CSV = ROOT / "reports" / "phase5_gridsearch_results.csv"
TOP3_PROBS_JSON = ROOT / "reports" / "phase5_top3_predictions.json"

MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)
FEATURE_IMPORTANCE_CSV.parent.mkdir(parents=True, exist_ok=True)

if not DATA_ENCODED.exists():
    raise FileNotFoundError(f"Encoded dataset missing at {DATA_ENCODED}")

df = pd.read_csv(DATA_ENCODED)
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

X_train, X_test, y_train, y_test = train_test_split(
    X,
    y,
    test_size=0.2,
    random_state=42,
    stratify=y,
)

param_grid = {
    'criterion': ['gini', 'entropy'],
    'max_depth': [None, 10, 15, 20, 30, 40, 50],
    'min_samples_split': [2, 3, 5],
    'min_samples_leaf': [1, 2, 3],
}

dt = DecisionTreeClassifier(random_state=42)

cv = GridSearchCV(
    estimator=dt,
    param_grid=param_grid,
    cv=5,
    scoring='accuracy',
    n_jobs=-1,
    verbose=1,
)

cv.fit(X_train, y_train)

best_model: DecisionTreeClassifier = cv.best_estimator_

train_pred = best_model.predict(X_train)
test_pred = best_model.predict(X_test)
probabilities = best_model.predict_proba(X_test)

train_accuracy = accuracy_score(y_train, train_pred)
test_accuracy = accuracy_score(y_test, test_pred)
precision_macro = precision_score(y_test, test_pred, average='macro', zero_division=0)
recall_macro = recall_score(y_test, test_pred, average='macro', zero_division=0)
top3_accuracy = top_k_accuracy_score(y_test, probabilities, k=3)

majority_class = pd.Series(y_train).mode()[0]
baseline_accuracy = float((y_test == majority_class).mean())

report = classification_report(y_test, test_pred, zero_division=0)
labels = np.unique(y)
cm = confusion_matrix(y_test, test_pred, labels=labels)
cm_df = pd.DataFrame(cm, index=labels, columns=labels)

with open(MODEL_PATH, 'wb') as f:
    pickle.dump(best_model, f)

cm_df.to_csv(CONFUSION_MATRIX_CSV)
pd.DataFrame(cv.cv_results_).to_csv(CV_RESULTS_CSV, index=False)

with open(CLASSIFICATION_REPORT_TXT, 'w') as f:
    f.write(report)

feature_importance = pd.DataFrame({
    'feature': feature_cols,
    'importance': best_model.feature_importances_,
}).sort_values(by='importance', ascending=False)
feature_importance.to_csv(FEATURE_IMPORTANCE_CSV, index=False)

metrics = {
    'timestamp': datetime.utcnow().isoformat() + 'Z',
    'train_accuracy': float(train_accuracy),
    'test_accuracy': float(test_accuracy),
    'precision_macro': float(precision_macro),
    'recall_macro': float(recall_macro),
    'top3_accuracy': float(top3_accuracy),
    'baseline_accuracy': baseline_accuracy,
    'best_params': cv.best_params_,
    'n_splits': cv.n_splits_,
    'train_size': int(len(X_train)),
    'test_size': int(len(X_test)),
}

with open(METRICS_JSON, 'w') as f:
    json.dump(metrics, f, indent=2)

labels_sorted = best_model.classes_
top3_indices = np.argsort(-probabilities, axis=1)[:, :3]
top3 = []
for row_idx, indices in enumerate(top3_indices):
    entry = {
        'actual': int(y_test.iloc[row_idx]),
        'top3': []
    }
    for idx in indices:
        entry['top3'].append({
            'stand_enc': int(labels_sorted[idx]),
            'probability': float(probabilities[row_idx, idx])
        })
    top3.append(entry)

with open(TOP3_PROBS_JSON, 'w') as f:
    json.dump(top3[:50], f, indent=2)

print('Model training complete.')
print(json.dumps(metrics, indent=2))
