#!/usr/bin/env python3
"""
ml/nb_thesis_pipeline.py
=========================
Runs GaussianNB on the thesis pipeline and appends results to experiment.md.

Pipeline (identical to production RF):
  - Data    : data/parking_history_encoded_redo.csv
  - Encoders: ml/encoders_redo.pkl  (no MinMaxScaler)
  - Split   : 80/20 stratified, random_state=42
  - Resample: SMOTE on training set only
  - Model   : GaussianNB (no GridSearch, var_smoothing=1e-9 default)
  - Metrics : Top-1/3/5, Macro Prec/Rec/F1, MCC, ROC-AUC (macro OvR)
"""

import sys, pickle, warnings
warnings.filterwarnings('ignore')

if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

from pathlib import Path
import numpy as np
import pandas as pd
from imblearn.over_sampling import SMOTE
from sklearn.model_selection import train_test_split
from sklearn.naive_bayes import GaussianNB
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    matthews_corrcoef, roc_auc_score,
)

ROOT       = Path(__file__).resolve().parents[1]
DATA_CSV   = ROOT / 'data' / 'parking_history_encoded_redo.csv'
ENC_PKL    = ROOT / 'ml'   / 'encoders_redo.pkl'
EXPERIMENT = ROOT / 'experiment.md'

FEATURE_COLS = [
    'aircraft_type_enc', 'aircraft_size_enc', 'operator_airline_enc',
    'airline_tier_enc',  'category_enc',       'stand_zone_enc',
]
TARGET_COL   = 'parking_stand_enc'
RANDOM_STATE = 42

def topk(proba, y_true, model_classes, k):
    correct = 0
    for i, yt in enumerate(y_true):
        top_idx = np.argsort(proba[i])[::-1][:k]
        if yt in [model_classes[j] for j in top_idx]:
            correct += 1
    return correct / len(y_true)

print("=" * 60)
print("  Naive Bayes — Thesis Pipeline")
print("=" * 60)

# Load
df = pd.read_csv(DATA_CSV)
X  = df[FEATURE_COLS].values
y  = df[TARGET_COL].values

with open(ENC_PKL, 'rb') as f:
    encoders = pickle.load(f)
N_CLASSES = len(encoders['parking_stand'].classes_)

print(f"  Rows: {len(df)}, Classes: {N_CLASSES}")

# Split
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.20, random_state=RANDOM_STATE, stratify=y
)
print(f"  Train: {len(X_train)}, Test: {len(X_test)}")

# SMOTE
smote = SMOTE(random_state=RANDOM_STATE)
X_sm, y_sm = smote.fit_resample(X_train, y_train)
print(f"  After SMOTE: {len(X_sm)}")

# Train GaussianNB
nb = GaussianNB()
nb.fit(X_sm, y_sm)

# Evaluate
y_pred = nb.predict(X_test)
proba  = nb.predict_proba(X_test)
mc     = list(nb.classes_)

top1 = accuracy_score(y_test, y_pred)
top3 = topk(proba, y_test, mc, 3)
top5 = topk(proba, y_test, mc, 5)
prec = precision_score(y_test, y_pred, average='macro', zero_division=0)
rec  = recall_score(y_test, y_pred,    average='macro', zero_division=0)
f1   = f1_score(y_test, y_pred,        average='macro', zero_division=0)
mcc  = matthews_corrcoef(y_test, y_pred)

# ROC-AUC
full_proba = np.zeros((len(y_test), N_CLASSES))
for ci, cls in enumerate(mc):
    if 0 <= cls < N_CLASSES:
        full_proba[:, cls] = proba[:, ci]
try:
    auc = roc_auc_score(y_test, full_proba, multi_class='ovr', average='macro')
except Exception as e:
    print(f"  [WARNING] AUC failed: {e}")
    auc = float('nan')

print("\n" + "=" * 60)
print("  RESULTS — GaussianNB (Thesis Pipeline)")
print("=" * 60)
print(f"  Top-1 Accuracy  : {top1*100:.2f}%")
print(f"  Top-3 Accuracy  : {top3*100:.2f}%")
print(f"  Top-5 Accuracy  : {top5*100:.2f}%")
print(f"  Macro Precision : {prec*100:.2f}%")
print(f"  Macro Recall    : {rec*100:.2f}%")
print(f"  Macro F1        : {f1*100:.2f}%")
print(f"  MCC             : {mcc:.4f}")
print(f"  ROC-AUC         : {auc:.4f}  (macro OvR)")
print("=" * 60)

# ── Append row 16 to the table in experiment.md ──────────────────────────────
text = EXPERIMENT.read_text(encoding='utf-8')

OLD_ROW15 = '| 15 | **MLP (10,10,10)** *Sahadevan* | SMOTE | 23.04% | 57.16% | 72.65% | 10.67% | 21.92% | 12.66% | 0.1842 | 0.7534 |'
NEW_ROW16 = (
    f"| 16 | **Naive Bayes (GaussianNB)** *Thesis Pipeline* | SMOTE | "
    f"{top1*100:.2f}% | {top3*100:.2f}% | {top5*100:.2f}% | "
    f"{prec*100:.2f}% | {rec*100:.2f}% | {f1*100:.2f}% | "
    f"{mcc:.4f} | {auc:.4f} |"
)

if OLD_ROW15 in text:
    text = text.replace(OLD_ROW15, OLD_ROW15 + '\n' + NEW_ROW16)
    EXPERIMENT.write_text(text, encoding='utf-8')
    print(f"\n  Row 16 inserted after row 15 in experiment.md")
else:
    # Fallback: append a section at the bottom
    block = f"""
---

## Naive Bayes — Thesis Pipeline (tambahan)

| Metrik | Nilai |
|--------|-------|
| Top-1 Accuracy | {top1*100:.2f}% |
| Top-3 Accuracy | {top3*100:.2f}% |
| Top-5 Accuracy | {top5*100:.2f}% |
| Macro Precision | {prec*100:.2f}% |
| Macro Recall | {rec*100:.2f}% |
| Macro F1 | {f1*100:.2f}% |
| MCC | {mcc:.4f} |
| ROC-AUC (macro OvR) | {auc:.4f} |
"""
    with open(EXPERIMENT, 'a', encoding='utf-8') as fh:
        fh.write(block)
    print(f"\n  Could not find row 15 anchor — appended section at bottom of experiment.md")
