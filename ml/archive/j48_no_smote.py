#!/usr/bin/env python3
"""
ml/j48_no_smote.py
==================
J48 (DecisionTreeClassifier, entropy) — NO SMOTE, same split as RF.
Output: ml/j48_no_smote_results.txt
"""

import pickle, warnings
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.tree import DecisionTreeClassifier
from sklearn.metrics import classification_report
from sklearn.model_selection import train_test_split

warnings.filterwarnings('ignore')

ROOT     = Path(__file__).resolve().parents[1]
OUT_FILE = ROOT / 'ml/j48_no_smote_results.txt'

lines = []
def log(s=''):
    print(s)
    lines.append(s)

# ── Data ──────────────────────────────────────────────────────────────────────
log("Loading data...")
df = pd.read_csv(ROOT / 'data/parking_history_encoded_redo.csv')
log(f"  Total rows : {len(df)}")

FEATURE_COLS = [
    'aircraft_type_enc','aircraft_size_enc','operator_airline_enc',
    'airline_tier_enc', 'category_enc',    'stand_zone_enc',
]
TARGET_COL = 'parking_stand_enc'
X = df[FEATURE_COLS].values
y = df[TARGET_COL].values

with open(ROOT / 'ml/encoders_redo.pkl', 'rb') as f:
    encoders = pickle.load(f)
stand_classes = list(encoders['parking_stand'].classes_)

# ── Split (same as RF) ────────────────────────────────────────────────────────
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.20, random_state=42, stratify=y
)
log(f"  Train : {len(X_train)} rows  (NO SMOTE)")
log(f"  Test  : {len(X_test)}  rows")

# ── Train ─────────────────────────────────────────────────────────────────────
log("\nTraining DecisionTreeClassifier (criterion='entropy', no SMOTE)...")
dt = DecisionTreeClassifier(criterion='entropy', random_state=42)
dt.fit(X_train, y_train)
log(f"  Tree depth  : {dt.get_depth()}")
log(f"  Leaf nodes  : {dt.get_n_leaves()}")
log(f"  Total nodes : {dt.tree_.node_count}")

# ── Evaluate ─────────────────────────────────────────────────────────────────
model_classes = list(dt.classes_)
target_names  = [stand_classes[c] for c in model_classes]
y_pred = dt.predict(X_test)
proba  = dt.predict_proba(X_test)

def topk_acc(proba, y_true, k):
    topk = np.argsort(proba, axis=1)[:, -k:]
    return sum(
        1 for i, yt in enumerate(y_true)
        if yt in model_classes and model_classes.index(yt) in topk[i]
    ) / len(y_true)

top1 = topk_acc(proba, y_test, 1)
top3 = topk_acc(proba, y_test, 3)
top5 = topk_acc(proba, y_test, 5)

report = classification_report(
    y_test, y_pred,
    labels=model_classes, target_names=target_names,
    output_dict=True, zero_division=0,
)
macro    = report['macro avg']
weighted = report['weighted avg']

# ── Output ────────────────────────────────────────────────────────────────────
log('')
log('=' * 65)
log('  J48 NO-SMOTE (DecisionTreeClassifier, criterion=entropy)')
log('  Pipeline: parking_history_encoded_redo.csv | 80/20 | NO SMOTE')
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
    m = report.get(name, {})
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
log('COMPARISON TABLE')
log(f"{'Metrik':<20} {'J48 w/ SMOTE':>14} {'J48 no SMOTE':>14} {'Random Forest':>14}")
log(f"{'-'*20} {'-'*14} {'-'*14} {'-'*14}")
log(f"{'Top-1 Accuracy':<20} {'38.15%':>14} {top1*100:>13.2f}% {'36.22%':>13}")
log(f"{'Top-3 Accuracy':<20} {'79.67%':>14} {top3*100:>13.2f}% {'80.64%':>13}")
log(f"{'Top-5 Accuracy':<20} {'96.63%':>14} {top5*100:>13.2f}% {'98.84%':>13}")
log(f"{'Macro Precision':<20} {'39.75%':>14} {macro['precision']*100:>13.2f}% {'37.51%':>13}")
log(f"{'Macro Recall':<20} {'40.25%':>14} {macro['recall']*100:>13.2f}% {'38.85%':>13}")
log(f"{'Macro F1-Score':<20} {'35.35%':>14} {macro['f1-score']*100:>13.2f}% {'33.74%':>13}")
log('=' * 65)

OUT_FILE.write_text('\n'.join(lines), encoding='utf-8')
print(f"\nSaved -> {OUT_FILE}")
