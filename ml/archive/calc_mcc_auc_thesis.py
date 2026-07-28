#!/usr/bin/env python3
"""
ml/calc_mcc_auc_thesis.py
==========================
Loads the production RF model (parking_stand_model_rf_redo.pkl) using
the original thesis pipeline:
  - Data: data/parking_history_encoded_redo.csv  (same file as training)
  - Encoders: ml/encoders_redo.pkl
  - Split: 80/20 stratified, random_state=42
  - Resampling: SMOTE on training set only
  - Model: parking_stand_model_rf_redo.pkl (loaded as-is, NOT retrained)

Outputs MCC and ROC-AUC (macro OvR) on the test set to console,
then appends them to experiment.md.
"""

import pickle
import sys
import warnings
warnings.filterwarnings('ignore')

from pathlib import Path
import numpy as np
import pandas as pd
from imblearn.over_sampling import SMOTE
from sklearn.model_selection import train_test_split
from sklearn.metrics import matthews_corrcoef, roc_auc_score

ROOT       = Path(__file__).resolve().parents[1]
DATA_CSV   = ROOT / 'data' / 'parking_history_encoded_redo.csv'
ENC_PKL    = ROOT / 'ml' / 'encoders_redo.pkl'
MODEL_PKL  = ROOT / 'ml' / 'parking_stand_model_rf_redo.pkl'
EXPERIMENT = ROOT / 'experiment.md'

FEATURE_COLS = [
    'aircraft_type_enc', 'aircraft_size_enc', 'operator_airline_enc',
    'airline_tier_enc',  'category_enc',       'stand_zone_enc',
]
TARGET_COL   = 'parking_stand_enc'
RANDOM_STATE = 42

print("=" * 60)
print("  MCC & ROC-AUC — Thesis RF Pipeline (Production Model)")
print("=" * 60)

# ── Load encoded dataset ──────────────────────────────────────────────────────
df = pd.read_csv(DATA_CSV)
print(f"\n  Dataset : {DATA_CSV.name}  ({len(df)} rows)")

X = df[FEATURE_COLS].values
y = df[TARGET_COL].values
print(f"  Features: {FEATURE_COLS}")
print(f"  Classes : {len(np.unique(y))} unique stands")

# ── Load encoders (for stand name decoding) ───────────────────────────────────
with open(ENC_PKL, 'rb') as f:
    encoders = pickle.load(f)
stand_classes = encoders['parking_stand'].classes_   # string names, index = encoded int
N_CLASSES = len(stand_classes)

# ── 80/20 stratified split (identical to training) ───────────────────────────
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.20, random_state=RANDOM_STATE, stratify=y
)
print(f"\n  Train   : {len(X_train)} rows")
print(f"  Test    : {len(X_test)}  rows")

# ── SMOTE on training set only ────────────────────────────────────────────────
smote = SMOTE(random_state=RANDOM_STATE)
X_train_sm, y_train_sm = smote.fit_resample(X_train, y_train)
print(f"  After SMOTE: {len(X_train_sm)} train rows")

# ── Load production RF model (do NOT retrain) ─────────────────────────────────
with open(MODEL_PKL, 'rb') as f:
    model = pickle.load(f)
print(f"\n  Model loaded: {MODEL_PKL.name}")
print(f"  Model type  : {type(model).__name__}")

# ── Predict on test set ───────────────────────────────────────────────────────
y_pred  = model.predict(X_test)
proba   = model.predict_proba(X_test)   # shape: (n_test, n_model_classes)

# ── MCC ───────────────────────────────────────────────────────────────────────
mcc = matthews_corrcoef(y_test, y_pred)

# ── ROC-AUC macro OvR ─────────────────────────────────────────────────────────
# model.classes_ may not cover all N_CLASSES — pad probability matrix to full size
model_classes = list(model.classes_)
full_proba = np.zeros((len(y_test), N_CLASSES))
for col_idx, cls in enumerate(model_classes):
    if 0 <= cls < N_CLASSES:
        full_proba[:, cls] = proba[:, col_idx]

try:
    auc = roc_auc_score(y_test, full_proba, multi_class='ovr', average='macro')
except Exception as e:
    print(f"  [WARNING] ROC-AUC failed: {e}")
    auc = float('nan')

# ── Print results ─────────────────────────────────────────────────────────────
print("\n" + "=" * 60)
print("  RESULTS")
print("=" * 60)
print(f"  MCC      : {mcc:.4f}")
print(f"  ROC-AUC  : {auc:.4f}  (macro OvR)")
print("=" * 60)

# ── Append to experiment.md ───────────────────────────────────────────────────
append_block = f"""
---

## MCC & ROC-AUC — RF Penelitian Ini (Pipeline Asli)

Dihitung dengan pipeline produksi asli (data: `parking_history_encoded_redo.csv`,
model: `parking_stand_model_rf_redo.pkl`, split 80/20 stratified random_state=42, SMOTE).
Model **dimuat langsung** — tidak dilatih ulang.

| Metrik | Nilai |
|--------|-------|
| **MCC** | **{mcc:.4f}** |
| **ROC-AUC (macro OvR)** | **{auc:.4f}** |

*Test set: {len(X_test)} sampel ({len(np.unique(y_test))} kelas terwakili)*
"""

with open(EXPERIMENT, 'a', encoding='utf-8') as f:
    f.write(append_block)

print(f"\n  Appended to: {EXPERIMENT.name}")
