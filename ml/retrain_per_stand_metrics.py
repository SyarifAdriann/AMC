#!/usr/bin/env python3
"""
ml/retrain_per_stand_metrics.py
================================
Retrain using the VERIFIED pipeline (same as production):
  - Data: data/parking_history_encoded_redo.csv (5,190 rows)
  - Split: 80/20 stratified, random_state=42
  - SMOTE on training set
  - RandomForest with optimal hyperparams from GridSearchCV
  - Output: per-stand Precision, Recall, F1, Support table
"""

import json, pickle, warnings
from pathlib import Path

import numpy as np
import pandas as pd
from imblearn.over_sampling import SMOTE
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report
from sklearn.model_selection import train_test_split

warnings.filterwarnings('ignore')

ROOT = Path(__file__).resolve().parents[1]

# ── Load data ─────────────────────────────────────────────────────────────────
print("Loading data...")
df = pd.read_csv(ROOT / 'data/parking_history_encoded_redo.csv')
print(f"  Rows: {len(df)}")

FEATURE_COLS = ['aircraft_type_enc','aircraft_size_enc','operator_airline_enc',
                'airline_tier_enc','category_enc','stand_zone_enc']
TARGET_COL   = 'parking_stand_enc'

X = df[FEATURE_COLS].values
y = df[TARGET_COL].values
print(f"  Features: {FEATURE_COLS}")
print(f"  Unique stands: {len(np.unique(y))}")

# ── Train/test split ──────────────────────────────────────────────────────────
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.20, random_state=42, stratify=y
)
print(f"\nTrain: {len(X_train)} rows  |  Test: {len(X_test)} rows")

# ── SMOTE ─────────────────────────────────────────────────────────────────────
print("Applying SMOTE...")
smote = SMOTE(random_state=42)
X_train_sm, y_train_sm = smote.fit_resample(X_train, y_train)
print(f"  After SMOTE: {len(X_train_sm)} rows")

# ── Retrain with optimal hyperparams ─────────────────────────────────────────
print("\nTraining RandomForest...")
rf = RandomForestClassifier(
    n_estimators      = 200,
    max_depth         = None,
    min_samples_leaf  = 5,
    min_samples_split = 2,
    class_weight      = 'balanced_subsample',
    random_state      = 42,
    n_jobs            = -1,
)
rf.fit(X_train_sm, y_train_sm)
print("  Done.")

# ── Load encoders to get stand names ─────────────────────────────────────────
with open(ROOT / 'ml/encoders_redo.pkl', 'rb') as f:
    encoders = pickle.load(f)
stand_classes = list(encoders['parking_stand'].classes_)   # index → stand name
model_classes = list(rf.classes_)                          # RF class order

# ── Top-1/3/5 Accuracy ───────────────────────────────────────────────────────
proba    = rf.predict_proba(X_test)
top1     = np.sum(np.argmax(proba, axis=1) == [list(rf.classes_).index(y) if y in rf.classes_ else -1 for y in y_test]) / len(y_test)

def topk_acc(proba, y_true, k):
    topk = np.argsort(proba, axis=1)[:, -k:]
    correct = 0
    for i, yt in enumerate(y_true):
        if yt in rf.classes_:
            ci = list(rf.classes_).index(yt)
            if ci in topk[i]:
                correct += 1
    return correct / len(y_true)

top1_acc = topk_acc(proba, y_test, 1)
top3_acc = topk_acc(proba, y_test, 3)
top5_acc = topk_acc(proba, y_test, 5)

y_pred = rf.predict(X_test)

print(f"\n{'='*60}")
print(f"  Top-1 Accuracy : {top1_acc*100:.2f}%")
print(f"  Top-3 Accuracy : {top3_acc*100:.2f}%")
print(f"  Top-5 Accuracy : {top5_acc*100:.2f}%")
print(f"{'='*60}\n")

# ── Per-stand classification report ──────────────────────────────────────────
# Map encoded ints → stand names for labels
target_names = [stand_classes[c] for c in model_classes]
report_dict = classification_report(
    y_test, y_pred,
    labels=model_classes,
    target_names=target_names,
    output_dict=True,
    zero_division=0
)

# Build table
rows = []
for stand_name in target_names:
    m = report_dict.get(stand_name, {})
    rows.append({
        'Parking Stand': stand_name,
        'Precision':     round(m.get('precision', 0), 4),
        'Recall':        round(m.get('recall', 0), 4),
        'F1-Score':      round(m.get('f1-score', 0), 4),
        'Support':       int(m.get('support', 0)),
    })

df_metrics = pd.DataFrame(rows)

# Macro averages
macro = report_dict.get('macro avg', {})
weighted = report_dict.get('weighted avg', {})

# ── Print table ───────────────────────────────────────────────────────────────
print(f"{'Parking Stand':<15} {'Precision':>10} {'Recall':>10} {'F1-Score':>10} {'Support':>9}")
print(f"{'-'*15} {'-'*10} {'-'*10} {'-'*10} {'-'*9}")
for _, row in df_metrics.iterrows():
    print(f"{row['Parking Stand']:<15} {row['Precision']:>10.4f} {row['Recall']:>10.4f} {row['F1-Score']:>10.4f} {row['Support']:>9}")
print(f"{'-'*15} {'-'*10} {'-'*10} {'-'*10} {'-'*9}")
print(f"{'Macro Avg':<15} {macro.get('precision',0):>10.4f} {macro.get('recall',0):>10.4f} {macro.get('f1-score',0):>10.4f} {int(macro.get('support',0)):>9}")
print(f"{'Weighted Avg':<15} {weighted.get('precision',0):>10.4f} {weighted.get('recall',0):>10.4f} {weighted.get('f1-score',0):>10.4f} {int(weighted.get('support',0)):>9}")

# ── Save JSON ─────────────────────────────────────────────────────────────────
out = {
    'top1_acc': round(top1_acc*100, 2),
    'top3_acc': round(top3_acc*100, 2),
    'top5_acc': round(top5_acc*100, 2),
    'macro_precision': round(macro.get('precision',0)*100, 2),
    'macro_recall':    round(macro.get('recall',0)*100, 2),
    'macro_f1':        round(macro.get('f1-score',0)*100, 2),
    'per_stand': rows,
}
out_path = ROOT / 'ml/per_stand_metrics.json'
with open(out_path, 'w', encoding='utf-8') as f:
    json.dump(out, f, indent=2)
print(f"\nSaved -> {out_path}")
