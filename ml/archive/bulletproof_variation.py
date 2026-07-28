#!/usr/bin/env python3
"""
ml/bulletproof_variation.py
============================
Bulletproof data variation experiment using the EXACT encoded data
that was used to train the production model.

Data source: data/parking_history_encoded_redo.csv
  - 5,190 rows, pre-encoded by the same encoders_redo.pkl
  - Sourced from: DATASET AMC 2.csv (4057 rows) + DATASET AMC.csv (1133 rows)

Strategy:
  - Fixed 80/20 stratified split (random_state=42) -> 4152 train / 1038 test
  - For each training size [1000, 2000, 3000]: sample from 4152 training rows
  - Apply SMOTE on each subset
  - Fit RF with best hyperparams from GridSearchCV (results_summary_redo.json)
  - Evaluate on the FIXED 1038-row test set
  - For the full dataset (4152) row: use official thesis numbers from sub-bab 4.2.3

GridSearch note: We use the KNOWN best params from the original GridSearch
(n_estimators=200, max_depth=None, min_samples_leaf=5, min_samples_split=2,
class_weight=balanced_subsample) for consistency across all runs. Running a
new GridSearch on each subset would change hyperparams per run, making the
comparison confounded by two variables instead of one.
"""

from __future__ import annotations

import json
import pickle
import warnings
from pathlib import Path

import numpy as np
import pandas as pd
from imblearn.over_sampling import SMOTE
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    f1_score,
    precision_score,
    recall_score,
)
from sklearn.model_selection import train_test_split

warnings.filterwarnings('ignore')

ROOT         = Path(__file__).resolve().parents[1]
ENCODED_CSV  = ROOT / 'data' / 'parking_history_encoded_redo.csv'
OUTPUT_JSON  = ROOT / 'ml'  / 'data_variation_results_v3.json'

RANDOM_STATE = 42
TEST_SIZE    = 0.20

# Exact best params from GridSearchCV (results_summary_redo.json)
BEST_PARAMS = {
    'n_estimators':     200,
    'max_depth':        None,
    'min_samples_leaf': 5,
    'min_samples_split': 2,
    'class_weight':     'balanced_subsample',
    'random_state':     RANDOM_STATE,
    'n_jobs':           -1,
}

# Official thesis numbers for the full-dataset row (from BAB 4.2.3)
THESIS_FULL = {
    'training_size_label':  '4.152 (penuh)',
    'train_rows_before_smote': 4152,
    'train_rows_after_smote':  'N/A (angka resmi dari thesis)',
    'test_rows':             1038,
    'top1_accuracy':         0.3613,   # 36.13%
    'top3_accuracy':         0.8015,   # 80.15%
    'top5_accuracy':         0.9894,   # 98.94%
    'macro_precision':       0.2317,   # 23.17%
    'macro_recall':          0.2798,   # 27.98%
    'macro_f1':              0.2220,   # 22.20%
    'source': 'Angka resmi dari sub-bab 4.2.3 skripsi (model produksi yang tersimpan)',
}

FEATURE_COLS = [
    'aircraft_type_enc',
    'aircraft_size_enc',
    'operator_airline_enc',
    'airline_tier_enc',
    'category_enc',
    'stand_zone_enc',
]
TARGET_COL = 'parking_stand_enc'

divider = '=' * 72


def compute_top_k_acc(y_true, proba, k):
    nc = sum(
        1 for i, yt in enumerate(y_true)
        if yt in list(np.argsort(proba[i])[::-1][:k])
    )
    return nc / len(y_true)


def run_one(X_tr, y_tr, X_test, y_test, label):
    print(f'  [{label}] Applying SMOTE...', end=' ', flush=True)

    # SMOTE: protect against k_neighbors > min class count
    min_class = int(np.bincount(y_tr).min())
    k_nbrs    = min(5, min_class - 1)
    if k_nbrs < 1:
        print('skipped (class too small), using raw data')
        X_res, y_res = X_tr, y_tr
    else:
        smote = SMOTE(random_state=RANDOM_STATE, k_neighbors=k_nbrs)
        X_res, y_res = smote.fit_resample(X_tr, y_tr)
        print(f'{len(X_tr)} -> {len(X_res)} samples')

    print(f'  [{label}] Fitting RF (n={BEST_PARAMS["n_estimators"]} trees)...', end=' ', flush=True)
    rf = RandomForestClassifier(**BEST_PARAMS)
    rf.fit(X_res, y_res)
    print('done')

    y_pred  = rf.predict(X_test)
    y_proba = rf.predict_proba(X_test)

    top1 = accuracy_score(y_test, y_pred)
    top3 = compute_top_k_acc(y_test, y_proba, 3)
    top5 = compute_top_k_acc(y_test, y_proba, 5)
    prec = precision_score(y_test, y_pred, average='macro', zero_division=0)
    rec  = recall_score(y_test,  y_pred, average='macro', zero_division=0)
    f1   = f1_score(y_test,     y_pred, average='macro', zero_division=0)

    print(f'  [{label}] Top-1={top1*100:.2f}%  Top-3={top3*100:.2f}%  '
          f'Top-5={top5*100:.2f}%  MacroP={prec*100:.2f}%  '
          f'MacroR={rec*100:.2f}%  MacroF1={f1*100:.2f}%')

    return {
        'training_size_label':  label,
        'train_rows_before_smote': int(len(X_tr)),
        'train_rows_after_smote':  int(len(X_res)),
        'test_rows':             int(len(X_test)),
        'top1_accuracy':         round(top1, 4),
        'top3_accuracy':         round(top3, 4),
        'top5_accuracy':         round(top5, 4),
        'macro_precision':       round(prec, 4),
        'macro_recall':          round(rec,  4),
        'macro_f1':              round(f1,   4),
        'source': 'Eksperimen (SMOTE + RF dengan best params dari GridSearchCV)',
    }


def main():
    print(divider)
    print('  BULLETPROOF DATA VARIATION EXPERIMENT')
    print('  Source: data/parking_history_encoded_redo.csv (EXACT training data)')
    print(divider)

    # Load pre-encoded data (same encoding as production model)
    df = pd.read_csv(ENCODED_CSV)
    print(f'[LOAD] {len(df)} rows, {len(df.columns)} columns')

    X = df[FEATURE_COLS].values.astype(np.int64)
    y = df[TARGET_COL].values.astype(np.int64)
    print(f'[DATA] X={X.shape}, unique classes={len(np.unique(y))}, '
          f'stand range={y.min()}-{y.max()}')

    # Fixed 80/20 stratified split — same as production
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=TEST_SIZE, random_state=RANDOM_STATE, stratify=y
    )
    print(f'[SPLIT] Train={len(X_train)} ({len(X_train)/len(X)*100:.0f}%), '
          f'Test={len(X_test)} ({len(X_test)/len(X)*100:.0f}%)')
    print()

    results = []

    # ── 1k, 2k, 3k ─────────────────────────────────────────────────────────
    print(divider)
    print('  INCREMENTAL RUNS (1k / 2k / 3k)')
    print(divider)
    print()

    for size in [1000, 2000, 3000]:
        label = f'{size:,}'
        rng = np.random.RandomState(RANDOM_STATE)
        idx = rng.choice(len(X_train), size=size, replace=False)
        rec = run_one(X_train[idx], y_train[idx], X_test, y_test, label)
        results.append(rec)
        print()

    # ── Full dataset: use official thesis numbers ─────────────────────────
    print(divider)
    print('  FULL DATASET ROW: Using official thesis numbers (BAB 4.2.3)')
    print(divider)
    print(f'  Top-1={THESIS_FULL["top1_accuracy"]*100:.2f}%  '
          f'Top-3={THESIS_FULL["top3_accuracy"]*100:.2f}%  '
          f'Top-5={THESIS_FULL["top5_accuracy"]*100:.2f}%  '
          f'MacroP={THESIS_FULL["macro_precision"]*100:.2f}%  '
          f'MacroR={THESIS_FULL["macro_recall"]*100:.2f}%  '
          f'MacroF1={THESIS_FULL["macro_f1"]*100:.2f}%')
    results.append(THESIS_FULL)
    print()

    # ── Summary table ──────────────────────────────────────────────────────
    print(divider)
    print('  TABEL PERBANDINGAN FINAL')
    print(divider)
    print()
    hdr = f"{'Data':^14} | {'Top-1':^7} | {'Top-3':^7} | {'Top-5':^7} | {'MacroP':^7} | {'MacroR':^7} | {'MacroF1':^7}"
    print('  ' + hdr)
    print('  ' + '-' * len(hdr))
    for r in results:
        row = (f"{r['training_size_label']:^14} | "
               f"{r['top1_accuracy']*100:^6.2f}% | "
               f"{r['top3_accuracy']*100:^6.2f}% | "
               f"{r['top5_accuracy']*100:^6.2f}% | "
               f"{r['macro_precision']*100:^6.2f}% | "
               f"{r['macro_recall']*100:^6.2f}% | "
               f"{r['macro_f1']*100:^6.2f}%")
        print('  ' + row)
    print()

    # ── Chart data ─────────────────────────────────────────────────────────
    print(divider)
    print('  DATA GRAFIK GARIS (untuk Excel)')
    print(divider)
    print()
    print(f"  {'X':^12}  Top1%   Top3%   Top5%   MacroP  MacroR  MacroF1")
    print(f"  {'-'*12}  {'------':^6}  {'------':^6}  {'------':^6}  {'------':^6}  {'------':^6}  {'-------':^7}")
    for r in results:
        lbl_x = r['training_size_label'].replace(',', '').replace(' (penuh)', '').strip()
        print(f"  {lbl_x:<12}  "
              f"{r['top1_accuracy']*100:<6.2f}  "
              f"{r['top3_accuracy']*100:<6.2f}  "
              f"{r['top5_accuracy']*100:<6.2f}  "
              f"{r['macro_precision']*100:<6.2f}  "
              f"{r['macro_recall']*100:<6.2f}  "
              f"{r['macro_f1']*100:<6.2f}")
    print()

    # ── Save JSON ──────────────────────────────────────────────────────────
    output = {
        'experiment': 'Bulletproof Data Variation (SMOTE + RF best params)',
        'data_source': str(ENCODED_CSV),
        'data_source_description': (
            'data/parking_history_encoded_redo.csv — 5190 baris, '
            'gabungan DATASET AMC 2.csv (4057 baris) + DATASET AMC.csv (1133 baris), '
            'sudah di-encode dengan encoders_redo.pkl'
        ),
        'best_params': BEST_PARAMS,
        'smote': 'SMOTE(random_state=42, k_neighbors=min(5,min_class_count-1))',
        'split': f'{int((1-TEST_SIZE)*100)}/{int(TEST_SIZE*100)} stratified, random_state={RANDOM_STATE}',
        'train_rows': int(len(X_train)),
        'test_rows':  int(len(X_test)),
        'results': results,
        'note_full_row': (
            'Baris 4.152 menggunakan angka resmi dari sub-bab 4.2.3 skripsi. '
            'Angka ini adalah hasil evaluasi model produksi yang dilatih dengan '
            'dataset dan pipeline yang sama, sebagaimana tercantum dalam results_summary_redo.json.'
        ),
        'chart_data': {
            'x_labels':       [r['training_size_label'] for r in results],
            'top1_accuracy':  [r['top1_accuracy']  for r in results],
            'top3_accuracy':  [r['top3_accuracy']  for r in results],
            'top5_accuracy':  [r['top5_accuracy']  for r in results],
            'macro_precision':[r['macro_precision'] for r in results],
            'macro_recall':   [r['macro_recall']   for r in results],
            'macro_f1':       [r['macro_f1']       for r in results],
        }
    }

    with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=2, ensure_ascii=False, default=str)

    print(f'[SAVED] {OUTPUT_JSON}')
    print()
    print(divider)
    print('  DONE')
    print(divider)

    return output


if __name__ == '__main__':
    main()
