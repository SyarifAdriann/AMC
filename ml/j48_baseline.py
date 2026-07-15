#!/usr/bin/env python3
"""
ml/j48_baseline.py
==================
Decision Tree (J48 equivalent) experiment using the EXACT same pipeline
as the production Random Forest model:
  - Data:   data/parking_history_encoded_redo.csv (5,190 rows)
  - Split:  80/20 stratified, random_state=42  -> 4,152 train / 1,038 test
  - SMOTE:  applied on training set only
  - Model:  DecisionTreeClassifier(criterion='entropy')  -- J48 equivalent
  - NO modification to any existing model files

Output: ml/j48_baseline_results.txt
"""

import json
import warnings
from pathlib import Path

import numpy as np
import pandas as pd
from imblearn.over_sampling import SMOTE
from sklearn.tree import DecisionTreeClassifier
from sklearn.metrics import classification_report
from sklearn.model_selection import train_test_split
import pickle

warnings.filterwarnings('ignore')

ROOT = Path(__file__).resolve().parents[1]
OUT_FILE = ROOT / 'ml/j48_baseline_results.txt'

lines = []
def log(s=''):
    print(s)
    lines.append(s)

# ── Load data (same source as RF) ────────────────────────────────────────────
log("Loading data...")
df = pd.read_csv(ROOT / 'data/parking_history_encoded_redo.csv')
log(f"  Total rows     : {len(df)}")

FEATURE_COLS = [
    'aircraft_type_enc', 'aircraft_size_enc', 'operator_airline_enc',
    'airline_tier_enc',  'category_enc',       'stand_zone_enc',
]
TARGET_COL = 'parking_stand_enc'

X = df[FEATURE_COLS].values
y = df[TARGET_COL].values
log(f"  Feature cols   : {FEATURE_COLS}")
log(f"  Unique stands  : {len(np.unique(y))}")

# ── Load encoders (for stand name mapping) ───────────────────────────────────
with open(ROOT / 'ml/encoders_redo.pkl', 'rb') as f:
    encoders = pickle.load(f)
stand_classes = list(encoders['parking_stand'].classes_)   # int index → name

# ── Train/test split (identical to RF pipeline) ──────────────────────────────
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.20, random_state=42, stratify=y
)
log(f"\nTrain : {len(X_train)} rows")
log(f"Test  : {len(X_test)}  rows")

# ── SMOTE (same as RF, on training set only) ─────────────────────────────────
log("\nApplying SMOTE...")
smote = SMOTE(random_state=42)
X_train_sm, y_train_sm = smote.fit_resample(X_train, y_train)
log(f"  After SMOTE: {len(X_train_sm)} rows")

# ── Fit Decision Tree (J48 = entropy criterion, no depth limit by default) ───
log("\nTraining DecisionTreeClassifier (criterion='entropy') ...")
dt = DecisionTreeClassifier(
    criterion    = 'entropy',    # J48 uses information gain (entropy)
    random_state = 42,
)
dt.fit(X_train_sm, y_train_sm)
log(f"  Tree depth : {dt.get_depth()}")
log(f"  Leaf nodes : {dt.get_n_leaves()}")

# ── Evaluate ─────────────────────────────────────────────────────────────────
model_classes = list(dt.classes_)                          # int stand enc
target_names  = [stand_classes[c] for c in model_classes] # string names

y_pred = dt.predict(X_test)
proba  = dt.predict_proba(X_test)

def topk_acc(proba, y_true, k):
    topk = np.argsort(proba, axis=1)[:, -k:]
    correct = sum(
        1 for i, yt in enumerate(y_true)
        if yt in model_classes and model_classes.index(yt) in topk[i]
    )
    return correct / len(y_true)

top1 = topk_acc(proba, y_test, 1)
top3 = topk_acc(proba, y_test, 3)
top5 = topk_acc(proba, y_test, 5)

report_dict = classification_report(
    y_test, y_pred,
    labels      = model_classes,
    target_names= target_names,
    output_dict = True,
    zero_division=0,
)

macro    = report_dict['macro avg']
weighted = report_dict['weighted avg']

# ── Build output text ─────────────────────────────────────────────────────────
log('')
log('=' * 65)
log('  J48 BASELINE (DecisionTreeClassifier, criterion=entropy)')
log('  Pipeline: parking_history_encoded_redo.csv | 80/20 | SMOTE')
log('=' * 65)
log('')
log('OVERALL METRICS')
log(f"  Top-1 Accuracy  : {top1*100:.2f}%")
log(f"  Top-3 Accuracy  : {top3*100:.2f}%")
log(f"  Top-5 Accuracy  : {top5*100:.2f}%")
log(f"  Macro Precision : {macro['precision']*100:.2f}%")
log(f"  Macro Recall    : {macro['recall']*100:.2f}%")
log(f"  Macro F1-Score  : {macro['f1-score']*100:.2f}%")
log('')
log('PER-STAND METRICS')
log(f"{'Parking Stand':<15} {'Precision':>10} {'Recall':>10} {'F1-Score':>10} {'Support':>9}")
log(f"{'-'*15} {'-'*10} {'-'*10} {'-'*10} {'-'*9}")
for name in target_names:
    m = report_dict.get(name, {})
    log(f"{name:<15} {m.get('precision',0):>10.4f} {m.get('recall',0):>10.4f} {m.get('f1-score',0):>10.4f} {int(m.get('support',0)):>9}")
log(f"{'-'*15} {'-'*10} {'-'*10} {'-'*10} {'-'*9}")
log(f"{'Macro Avg':<15} {macro['precision']:>10.4f} {macro['recall']:>10.4f} {macro['f1-score']:>10.4f} {int(macro['support']):>9}")
log(f"{'Weighted Avg':<15} {weighted['precision']:>10.4f} {weighted['recall']:>10.4f} {weighted['f1-score']:>10.4f} {int(weighted['support']):>9}")
log('')
log('TREE COMPLEXITY')
log(f"  Max Depth   : {dt.get_depth()}")
log(f"  Leaf Nodes  : {dt.get_n_leaves()}")
log(f"  Total Nodes : {dt.tree_.node_count}")
log('')
log('COMPARISON REFERENCE (Random Forest — from previous run)')
log(f"  Top-1 Accuracy  : 36.22%")
log(f"  Top-3 Accuracy  : 80.64%")
log(f"  Top-5 Accuracy  : 98.84%")
log(f"  Macro Precision : 37.51%")
log(f"  Macro Recall    : 38.85%")
log(f"  Macro F1-Score  : 33.74%")
log('')
log('=' * 65)

# ── Write to file ─────────────────────────────────────────────────────────────
OUT_FILE.write_text('\n'.join(lines), encoding='utf-8')
print(f"\nSaved -> {OUT_FILE}")
