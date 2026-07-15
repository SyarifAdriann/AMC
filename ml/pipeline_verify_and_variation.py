#!/usr/bin/env python3
"""
ml/pipeline_verify_and_variation.py
====================================
Verifikasi pipeline yang benar (dengan SMOTE) dan jalankan eksperimen
variasi ukuran data training pada 4 ukuran berbeda.

Pipeline yang direplikasi (dari ml_process.md):
  1. Load DATASET_AMC_fields_used.csv
  2. Feature engineering (3 raw -> 6 features)
  3. Label encoding, drop invalid rows -> 5.190 records
  4. Train/test split 80/20 stratified (random_state=42)
  5. SMOTE pada training set saja
  6. Fit RandomForest dengan hyperparameter optimal dari results_summary_redo.json
  7. Evaluate on test set

Output: ml/data_variation_results_v2.json
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
from sklearn.preprocessing import LabelEncoder

warnings.filterwarnings('ignore')

ROOT         = Path(__file__).resolve().parents[1]
DATASET_PATH = ROOT / 'DATASET_AMC_fields_used.csv'
ENCODER_PATH = ROOT / 'ml' / 'encoders_redo.pkl'
OUTPUT_JSON  = ROOT / 'ml' / 'data_variation_results_v2.json'

# ── Best hyperparameters from GridSearchCV (from results_summary_redo.json) ──
BEST_PARAMS = {
    'n_estimators': 200,
    'max_depth': None,
    'min_samples_leaf': 5,
    'min_samples_split': 2,
    'class_weight': 'balanced_subsample',
    'random_state': 42,
    'n_jobs': -1,
}

RANDOM_STATE = 42
TEST_SIZE    = 0.2

# ── Feature engineering (exact copy from ml/predict.py) ─────────────────────
A0_COMPATIBLE = [
    'C 152', 'C 172', 'C 182', 'C 185', 'C 206', 'C 208',
    'C 402', 'C 404', 'C 425', 'PC 6', 'PC 12',
    'C152', 'C172', 'C182', 'C185', 'C206', 'C208',
    'C402', 'C404', 'C425', 'PC6', 'PC12',
    'CESSNA', 'PILATUS',
]

def determine_aircraft_size(aircraft_type: str) -> str:
    ac = str(aircraft_type).strip().upper().replace(' ', '')
    for c in A0_COMPATIBLE:
        if c.replace(' ', '') in ac or ac in c.replace(' ', ''):
            return 'SMALL_A0_COMPATIBLE'
    return 'STANDARD'

def determine_airline_tier(operator_airline: str) -> str:
    HIGH   = ['BATIK AIR', 'CITILINK', 'GARUDA', 'TRIGANA', 'TRI MG']
    MEDIUM = ['PELITA', 'JETSET', 'KARISMA', 'JIP', 'PREMI', 'SUSI AIR']
    ao = str(operator_airline).strip().upper()
    if ao in HIGH:
        return 'HIGH_FREQUENCY'
    elif ao in MEDIUM:
        return 'MEDIUM_FREQUENCY'
    return 'LOW_FREQUENCY'

def get_stand_zone(category: str) -> str:
    if category == 'COMMERCIAL':
        return 'RIGHT_COMMERCIAL'
    elif category == 'CARGO':
        return 'LEFT_CARGO'
    return 'MIDDLE_CHARTER'

CATEGORY_MAP = {
    'KOMERSIAL': 'COMMERCIAL', 'komersial': 'COMMERCIAL', 'Komersial': 'COMMERCIAL',
    'PRIVATE': 'CHARTER', 'private': 'CHARTER',
    'cargo': 'CARGO', 'Cargo': 'CARGO', 'CARGO': 'CARGO',
    'CHARTER': 'CHARTER', 'Charter': 'CHARTER',
    'COMMERCIAL': 'COMMERCIAL', 'Commercial': 'COMMERCIAL',
}

def normalize_category(cat: str) -> str:
    s = str(cat).strip()
    return CATEGORY_MAP.get(s, s.upper())

def build_features(df: pd.DataFrame) -> pd.DataFrame:
    out = df.copy()
    out['aircraft_type']    = out['aircraft_type'].str.strip().str.upper()
    out['operator_airline'] = out['operator_airline'].str.strip().str.upper()
    out['category']         = out['category'].apply(normalize_category)
    out['aircraft_size']    = out['aircraft_type'].apply(determine_aircraft_size)
    out['airline_tier']     = out['operator_airline'].apply(determine_airline_tier)
    out['stand_zone']       = out['category'].apply(get_stand_zone)
    return out

def safe_encode(enc, values):
    classes = list(enc.classes_)
    lookup  = {c: i for i, c in enumerate(classes)}
    return np.array([lookup.get(v, 0) for v in values], dtype=np.int64)

def encode_df(df_feat: pd.DataFrame, encoders: dict):
    X = np.column_stack([
        safe_encode(encoders['aircraft_type'],    df_feat['aircraft_type'].values),
        safe_encode(encoders['aircraft_size'],    df_feat['aircraft_size'].values),
        safe_encode(encoders['operator_airline'], df_feat['operator_airline'].values),
        safe_encode(encoders['airline_tier'],     df_feat['airline_tier'].values),
        safe_encode(encoders['category'],         df_feat['category'].values),
        safe_encode(encoders['stand_zone'],       df_feat['stand_zone'].values),
    ])
    stand_classes = list(encoders['parking_stand'].classes_)
    stand_lookup  = {c: i for i, c in enumerate(stand_classes)}
    y_raw = df_feat['parking_stand'].str.strip().str.upper().values
    y = np.array([stand_lookup.get(v, -1) for v in y_raw], dtype=np.int64)
    return X, y

def compute_top_k_acc(y_true, proba, k, classes):
    nc = 0
    for i, yt in enumerate(y_true):
        top_k = list(np.argsort(proba[i])[::-1][:k])
        top_k_classes = [classes[j] for j in top_k]
        if yt in top_k_classes:
            nc += 1
    return nc / len(y_true)


def run_one_size(X_tr, y_tr, X_test, y_test, label, encoders):
    print(f'  Training on {label} samples...')
    # Apply SMOTE to training subset
    try:
        smote = SMOTE(random_state=RANDOM_STATE, k_neighbors=min(5, min(np.bincount(y_tr)) - 1))
        X_res, y_res = smote.fit_resample(X_tr, y_tr)
        print(f'    SMOTE: {len(X_tr)} -> {len(X_res)} samples')
    except Exception as e:
        print(f'    SMOTE skipped ({e}), using original')
        X_res, y_res = X_tr, y_tr

    rf = RandomForestClassifier(**BEST_PARAMS)
    rf.fit(X_res, y_res)

    y_pred  = rf.predict(X_test)
    y_proba = rf.predict_proba(X_test)
    rf_cls  = list(rf.classes_)

    top1 = accuracy_score(y_test, y_pred)
    top3 = compute_top_k_acc(y_test, y_proba, 3, rf_cls)
    top5 = compute_top_k_acc(y_test, y_proba, 5, rf_cls)
    prec = precision_score(y_test, y_pred, average='macro', zero_division=0)
    rec  = recall_score(y_test, y_pred, average='macro', zero_division=0)
    f1   = f1_score(y_test, y_pred, average='macro', zero_division=0)

    print(f'    Top-1={top1*100:.2f}%  Top-3={top3*100:.2f}%  Top-5={top5*100:.2f}%  '
          f'MacroP={prec*100:.2f}%  MacroR={rec*100:.2f}%  MacroF1={f1*100:.2f}%')

    return {
        'training_size_label': label,
        'train_rows_before_smote': int(len(X_tr)),
        'train_rows_after_smote': int(len(X_res)),
        'test_rows': int(len(X_test)),
        'top1_accuracy': round(top1, 4),
        'top3_accuracy': round(top3, 4),
        'top5_accuracy': round(top5, 4),
        'macro_precision': round(prec, 4),
        'macro_recall': round(rec, 4),
        'macro_f1': round(f1, 4),
    }


def main():
    divider = '=' * 72
    print(divider)
    print('  PIPELINE VERIFICATION + DATA VARIATION EXPERIMENT (with SMOTE)')
    print(divider)

    # Load encoders
    with open(ENCODER_PATH, 'rb') as f:
        encoders = pickle.load(f)
    valid_stands = set(encoders['parking_stand'].classes_)

    # Load dataset
    df_raw = pd.read_csv(DATASET_PATH)
    df_raw.columns = ['aircraft_type', 'operator_airline', 'category', 'parking_stand']
    df_raw = df_raw.dropna(subset=['aircraft_type', 'operator_airline', 'category', 'parking_stand'])

    # Feature engineering
    df_feat = build_features(df_raw)

    # Filter to known stands only
    df_feat = df_feat[
        df_feat['parking_stand'].str.strip().str.upper().isin(valid_stands)
    ].reset_index(drop=True)

    FULL_SIZE = len(df_feat)
    print(f'\n[LOAD] Dataset: {FULL_SIZE} valid records after cleaning & filtering')

    # Encode
    X_full, y_full = encode_df(df_feat, encoders)
    valid_mask = y_full >= 0
    X_full = X_full[valid_mask]
    y_full = y_full[valid_mask]
    print(f'[LOAD] Encoded: X={X_full.shape}, unique y classes={len(np.unique(y_full))}')

    # Fixed test split
    X_train_full, X_test, y_train_full, y_test = train_test_split(
        X_full, y_full, test_size=TEST_SIZE, random_state=RANDOM_STATE, stratify=y_full
    )
    print(f'[SPLIT] Train={len(X_train_full)}, Test={len(X_test)}\n')

    # Verify full pipeline first
    print(divider)
    print('  STEP 1: PIPELINE VERIFICATION (full dataset + SMOTE)')
    print(divider)
    full_result = run_one_size(X_train_full, y_train_full, X_test, y_test,
                               f'{len(X_train_full)} (full)', encoders)

    ref_top3 = 0.8034682080924855
    got_top3 = full_result['top3_accuracy']
    diff     = abs(got_top3 - ref_top3)
    status   = 'VERIFIED' if diff < 0.05 else 'CLOSE' if diff < 0.10 else 'MISMATCH'
    print(f'\n  Reference Top-3 (results_summary_redo.json): {ref_top3*100:.2f}%')
    print(f'  Reproduced Top-3: {got_top3*100:.2f}%')
    print(f'  Difference: {diff*100:.2f} pp  -> [{status}]')

    # Data variation runs
    print()
    print(divider)
    print('  STEP 2: DATA VARIATION EXPERIMENT (1000, 2000, 3000, full)')
    print(divider)
    print()

    SIZES = [1000, 2000, 3000, len(X_train_full)]
    results = []

    for sz in SIZES:
        if sz >= len(X_train_full):
            X_tr = X_train_full
            y_tr = y_train_full
            lbl  = f'{len(X_train_full):,} (penuh)'
        else:
            rng = np.random.RandomState(RANDOM_STATE)
            idx = rng.choice(len(X_train_full), size=sz, replace=False)
            X_tr = X_train_full[idx]
            y_tr = y_train_full[idx]
            lbl  = f'{sz:,}'
        rec = run_one_size(X_tr, y_tr, X_test, y_test, lbl, encoders)
        results.append(rec)

    # Print table
    print()
    print(divider)
    print('  TABEL PERBANDINGAN HASIL EKSPERIMEN')
    print(divider)
    print()
    hdr = f"{'Jumlah Data':^16} | {'Top-1':^7} | {'Top-3':^7} | {'Top-5':^7} | {'MacroP':^7} | {'MacroR':^7} | {'MacroF1':^7}"
    sep = '-' * len(hdr)
    print('  ' + hdr)
    print('  ' + sep)
    for r in results:
        row = (f"{r['training_size_label']:^16} | "
               f"{r['top1_accuracy']*100:^6.2f}% | "
               f"{r['top3_accuracy']*100:^6.2f}% | "
               f"{r['top5_accuracy']*100:^6.2f}% | "
               f"{r['macro_precision']*100:^6.2f}% | "
               f"{r['macro_recall']*100:^6.2f}% | "
               f"{r['macro_f1']*100:^6.2f}%")
        print('  ' + row)
    print()

    # Chart data
    print(divider)
    print('  DATA GRAFIK GARIS')
    print(divider)
    print()
    print(f"  {'X':^12}  Top1%   Top3%   Top5%   MacroP  MacroR  MacroF1")
    print(f"  {'---':^12}  {'---':^6}  {'---':^6}  {'---':^6}  {'---':^6}  {'---':^6}  {'---':^6}")
    for r in results:
        lbl_x = r['training_size_label'].replace(',','').replace(' (penuh)','').strip()
        print(f"  {lbl_x:^12}  "
              f"{r['top1_accuracy']*100:<6.2f}  "
              f"{r['top3_accuracy']*100:<6.2f}  "
              f"{r['top5_accuracy']*100:<6.2f}  "
              f"{r['macro_precision']*100:<6.2f}  "
              f"{r['macro_recall']*100:<6.2f}  "
              f"{r['macro_f1']*100:<6.2f}")
    print()

    # Save JSON
    output = {
        'pipeline': 'SMOTE + RF (matching production pipeline from ml_process.md)',
        'best_params': BEST_PARAMS,
        'smote': 'SMOTE(random_state=42, k_neighbors=min(5,min_class_count-1))',
        'test_size': TEST_SIZE,
        'random_state': RANDOM_STATE,
        'full_dataset_rows': FULL_SIZE,
        'train_rows': int(len(X_train_full)),
        'test_rows': int(len(X_test)),
        'pipeline_verification': {
            'reference_top3': ref_top3,
            'reproduced_top3': got_top3,
            'difference_pp': round(diff * 100, 2),
            'status': status,
        },
        'results': results,
        'chart_data': {
            'x_labels': [r['training_size_label'] for r in results],
            'top1_accuracy': [r['top1_accuracy'] for r in results],
            'top3_accuracy': [r['top3_accuracy'] for r in results],
            'top5_accuracy': [r['top5_accuracy'] for r in results],
            'macro_precision': [r['macro_precision'] for r in results],
            'macro_recall': [r['macro_recall'] for r in results],
            'macro_f1': [r['macro_f1'] for r in results],
        }
    }

    with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=2, ensure_ascii=False, default=str)

    print(f'  [SAVED] {OUTPUT_JSON}')
    print()
    print(divider)

    return output


if __name__ == '__main__':
    main()
