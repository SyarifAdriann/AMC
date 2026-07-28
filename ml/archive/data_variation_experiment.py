#!/usr/bin/env python3
"""
ml/data_variation_experiment.py
AMC Thesis BAB 4.2.5 - Analisis Variasi Ukuran Data Training

Retrains the Random Forest at 4 different dataset sizes (1000, 2000, 3000, 4069)
using the EXACT same pipeline as the production model (same hyperparams,
same feature engineering, same encoding, NO GridSearch).

Results are saved to ml/data_variation_results.json and printed as a table.
"""

from __future__ import annotations

import json
import pickle
import warnings
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    top_k_accuracy_score,
)
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder

warnings.filterwarnings('ignore')

ROOT         = Path(__file__).resolve().parents[1]
DATASET_PATH = ROOT / 'DATASET_AMC_fields_used.csv'
ENCODER_PATH = ROOT / 'ml' / 'encoders_redo.pkl'
OUTPUT_JSON  = ROOT / 'ml' / 'data_variation_results.json'

# ── Best hyperparameters from GridSearch (hardcoded — from results_summary_redo.json) ──
BEST_PARAMS = {
    'n_estimators':    200,
    'max_depth':       None,
    'min_samples_leaf': 5,
    'min_samples_split': 2,
    'class_weight':    'balanced_subsample',
    'random_state':    42,
    'n_jobs':          -1,
}

RANDOM_STATE = 42
TEST_SIZE    = 0.2

# ── Feature engineering (exact copies from predict.py) ─────────────────────

A0_COMPATIBLE = [
    'C 152','C 172','C 182','C 185','C 206','C 208',
    'C 402','C 404','C 425','PC 6','PC 12',
    'C152','C172','C182','C185','C206','C208',
    'C402','C404','C425','PC6','PC12',
    'CESSNA','PILATUS',
]

def determine_aircraft_size(aircraft_type: str) -> str:
    aircraft_clean = str(aircraft_type).strip().upper().replace(' ', '')
    for c in A0_COMPATIBLE:
        if c.replace(' ', '') in aircraft_clean or aircraft_clean in c.replace(' ', ''):
            return 'SMALL_A0_COMPATIBLE'
    return 'STANDARD'

def determine_airline_tier(operator_airline: str) -> str:
    HIGH   = ['BATIK AIR','CITILINK','GARUDA','TRIGANA','TRI MG']
    MEDIUM = ['PELITA','JETSET','KARISMA','JIP','PREMI','SUSI AIR']
    airline_upper = operator_airline.upper()
    if airline_upper in HIGH:
        return 'HIGH_FREQUENCY'
    elif airline_upper in MEDIUM:
        return 'MEDIUM_FREQUENCY'
    return 'LOW_FREQUENCY'

def get_stand_zone(category: str) -> str:
    if category == 'COMMERCIAL':
        return 'RIGHT_COMMERCIAL'
    elif category == 'CARGO':
        return 'LEFT_CARGO'
    return 'MIDDLE_CHARTER'

CATEGORY_MAP = {
    'KOMERSIAL':'COMMERCIAL','komersial':'COMMERCIAL','Komersial':'COMMERCIAL',
    'PRIVATE':'CHARTER','private':'CHARTER',
    'cargo':'CARGO','Cargo':'CARGO',
}

def normalize_category(cat: str) -> str:
    s = str(cat).strip()
    return CATEGORY_MAP.get(s, s.upper())


def build_features_for_df(df: pd.DataFrame) -> pd.DataFrame:
    """Apply feature engineering to a dataframe with columns:
       aircraft_type, operator_airline, category, parking_stand
    """
    out = df.copy()
    out['aircraft_type']    = out['aircraft_type'].str.strip().str.upper()
    out['operator_airline'] = out['operator_airline'].str.strip().str.upper()
    out['category']         = out['category'].apply(normalize_category)
    out['aircraft_size']    = out['aircraft_type'].apply(determine_aircraft_size)
    out['airline_tier']     = out['operator_airline'].apply(determine_airline_tier)
    out['stand_zone']       = out['category'].apply(get_stand_zone)
    return out


def encode_features(df_feat: pd.DataFrame, encoders: dict) -> tuple:
    """Encode all 6 feature columns using the provided encoders.
    Returns (X as numpy array, y as numpy array).
    """
    def safe_encode(enc, values):
        classes = list(enc.classes_)
        lookup  = {c: i for i, c in enumerate(classes)}
        return np.array([lookup.get(v, 0) for v in values], dtype=np.int64)

    X = np.column_stack([
        safe_encode(encoders['aircraft_type'],    df_feat['aircraft_type'].values),
        safe_encode(encoders['aircraft_size'],    df_feat['aircraft_size'].values),
        safe_encode(encoders['operator_airline'], df_feat['operator_airline'].values),
        safe_encode(encoders['airline_tier'],     df_feat['airline_tier'].values),
        safe_encode(encoders['category'],         df_feat['category'].values),
        safe_encode(encoders['stand_zone'],       df_feat['stand_zone'].values),
    ])

    y_enc   = encoders['parking_stand']
    y_raw   = df_feat['parking_stand'].str.strip().str.upper().values
    y_valid = np.array([v for v in y_raw if v in set(y_enc.classes_)])
    # encode full y (rows with invalid stands will be masked)
    valid_mask = np.array([v in set(y_enc.classes_) for v in y_raw])
    stand_lookup = {c: i for i, c in enumerate(list(y_enc.classes_))}
    y = np.array([stand_lookup.get(v, -1) for v in y_raw], dtype=np.int64)

    return X, y, valid_mask


def compute_top_k_acc(y_true, proba, k: int, classes) -> float:
    """Top-k accuracy: fraction where true label is in top-k predictions."""
    n_classes = proba.shape[1]
    if k >= n_classes:
        return 1.0
    n_correct = 0
    for i, true_label in enumerate(y_true):
        top_k_idx = np.argsort(proba[i])[::-1][:k]
        top_k_classes = [classes[j] for j in top_k_idx]
        if true_label in top_k_classes:
            n_correct += 1
    return n_correct / len(y_true)


# ═══════════════════════════════════════════════════════════════════════════════
# MAIN
# ═══════════════════════════════════════════════════════════════════════════════

def main():
    divider = "=" * 72
    print(divider)
    print("  AMC THESIS — BAB 4.2.5 ANALISIS VARIASI UKURAN DATA TRAINING")
    print(divider)

    # ── Load & clean dataset ─────────────────────────────────────────────────
    print("\n[LOAD] Dataset …")
    df_raw = pd.read_csv(DATASET_PATH)
    df_raw.columns = ['aircraft_type','operator_airline','category','parking_stand']
    df_raw = df_raw.dropna(subset=['aircraft_type','operator_airline','category','parking_stand'])
    print(f"  Raw rows (after dropna): {len(df_raw)}")

    # Load encoders to know valid stand classes
    with open(ENCODER_PATH, 'rb') as f:
        encoders = pickle.load(f)
    valid_stands = set(encoders['parking_stand'].classes_)

    # Apply feature engineering
    df_feat = build_features_for_df(df_raw)

    # Keep only rows with valid parking stands
    df_feat = df_feat[df_feat['parking_stand'].str.strip().str.upper().isin(valid_stands)].reset_index(drop=True)
    print(f"  Rows after filtering to known stands: {len(df_feat)}")
    FULL_SIZE = len(df_feat)

    # Encode full dataset
    X_full, y_full, _ = encode_features(df_feat, encoders)
    print(f"  X shape: {X_full.shape}  |  unique y classes: {len(np.unique(y_full))}")

    # ── Split TEST SET from full data first (fixed for all runs) ─────────────
    print(f"\n[SPLIT] Creating fixed test set (20% of {FULL_SIZE} rows = {int(FULL_SIZE*TEST_SIZE)} rows) …")
    X_train_full, X_test, y_train_full, y_test = train_test_split(
        X_full, y_full, test_size=TEST_SIZE, random_state=RANDOM_STATE, stratify=y_full
    )
    print(f"  Full train size: {len(X_train_full)}  |  Test size: {len(X_test)}")

    # ── Training sizes ───────────────────────────────────────────────────────
    SIZES = [1000, 2000, 3000, FULL_SIZE]
    results_list = []
    full_train_n = len(X_train_full)

    print(f"\n[TRAIN] Training at sizes: {SIZES} …\n")

    for size in SIZES:
        label = f"{size:,}" if size < FULL_SIZE else f"{FULL_SIZE:,} (full)"
        print(f"  ─── Training size: {label} ───")

        if size >= full_train_n:
            X_tr = X_train_full
            y_tr = y_train_full
        else:
            # Sample N rows from training set (reproducible)
            rng = np.random.RandomState(RANDOM_STATE)
            idx = rng.choice(len(X_train_full), size=size, replace=False)
            X_tr = X_train_full[idx]
            y_tr = y_train_full[idx]

        # Check that test classes exist in train (needed for metrics)
        train_classes = set(np.unique(y_tr))
        test_classes  = set(np.unique(y_test))
        missing = test_classes - train_classes
        if missing:
            print(f"    [WARN] {len(missing)} test classes missing from training set. Metrics may be lower.")

        # Train RF (no GridSearch — use best hardcoded params)
        rf = RandomForestClassifier(**BEST_PARAMS)
        rf.fit(X_tr, y_tr)

        # Predict
        y_pred   = rf.predict(X_test)
        y_proba  = rf.predict_proba(X_test)
        rf_classes = list(rf.classes_)

        # Top-1 accuracy
        top1_acc = accuracy_score(y_test, y_pred)

        # Top-3 accuracy
        top3_acc = compute_top_k_acc(y_test, y_proba, 3, rf_classes)

        # Top-5 accuracy
        top5_acc = compute_top_k_acc(y_test, y_proba, 5, rf_classes)

        # Macro Precision, Recall, F1
        prec_macro = precision_score(y_test, y_pred, average='macro', zero_division=0)
        rec_macro  = recall_score(y_test, y_pred, average='macro', zero_division=0)
        f1_macro   = f1_score(y_test, y_pred, average='macro', zero_division=0)

        rec = {
            'training_size': int(size),
            'training_size_label': label,
            'actual_train_rows': int(len(X_tr)),
            'test_rows': int(len(X_test)),
            'top1_accuracy': round(top1_acc, 4),
            'top3_accuracy': round(top3_acc, 4),
            'top5_accuracy': round(top5_acc, 4),
            'macro_precision': round(prec_macro, 4),
            'macro_recall': round(rec_macro, 4),
            'macro_f1': round(f1_macro, 4),
        }
        results_list.append(rec)

        print(f"    Top-1  Acc : {top1_acc*100:.2f}%")
        print(f"    Top-3  Acc : {top3_acc*100:.2f}%")
        print(f"    Top-5  Acc : {top5_acc*100:.2f}%")
        print(f"    Macro  P   : {prec_macro*100:.2f}%")
        print(f"    Macro  R   : {rec_macro*100:.2f}%")
        print(f"    Macro  F1  : {f1_macro*100:.2f}%")
        print()

    # ── Print comparison table ───────────────────────────────────────────────
    print(divider)
    print("  TABEL PERBANDINGAN — ANALISIS VARIASI UKURAN DATA")
    print(divider)
    print()
    header = (
        f"{'Jumlah Data':^15} │ {'Top-1':^7} │ {'Top-3':^7} │ {'Top-5':^7} │ "
        f"{'Macro P':^8} │ {'Macro R':^8} │ {'Macro F1':^8}"
    )
    sep = "─" * len(header)
    print(f"  {header}")
    print(f"  {sep}")
    for r in results_list:
        row = (
            f"{r['training_size_label']:^15} │ "
            f"{r['top1_accuracy']*100:^6.2f}% │ "
            f"{r['top3_accuracy']*100:^6.2f}% │ "
            f"{r['top5_accuracy']*100:^6.2f}% │ "
            f"{r['macro_precision']*100:^7.2f}% │ "
            f"{r['macro_recall']*100:^7.2f}% │ "
            f"{r['macro_f1']*100:^7.2f}%"
        )
        print(f"  {row}")
    print()

    # ── Print chart data ─────────────────────────────────────────────────────
    print(divider)
    print("  DATA GRAFIK GARIS (untuk copy ke Excel / chart tool)")
    print(divider)
    print()
    print(f"  {'X (Jumlah Data)':<20} Top-1%    Top-3%    Top-5%    MacroP%   MacroR%   MacroF1%")
    print(f"  {'─'*20} {'─'*9} {'─'*9} {'─'*9} {'─'*9} {'─'*9} {'─'*9}")
    for r in results_list:
        print(
            f"  {r['training_size']:<20} "
            f"{r['top1_accuracy']*100:<9.2f} "
            f"{r['top3_accuracy']*100:<9.2f} "
            f"{r['top5_accuracy']*100:<9.2f} "
            f"{r['macro_precision']*100:<9.2f} "
            f"{r['macro_recall']*100:<9.2f} "
            f"{r['macro_f1']*100:<9.2f}"
        )
    print()

    # ── Save JSON ─────────────────────────────────────────────────────────────
    output = {
        'experiment_description': 'Data variation analysis: RF trained at 4 dataset sizes, same test set',
        'best_params_used': BEST_PARAMS,
        'test_size_fraction': TEST_SIZE,
        'random_state': RANDOM_STATE,
        'full_dataset_size': FULL_SIZE,
        'results': results_list,
        'chart_data': {
            'x_axis': [r['training_size'] for r in results_list],
            'top1_accuracy': [r['top1_accuracy'] for r in results_list],
            'top3_accuracy': [r['top3_accuracy'] for r in results_list],
            'top5_accuracy': [r['top5_accuracy'] for r in results_list],
            'macro_precision': [r['macro_precision'] for r in results_list],
            'macro_recall': [r['macro_recall'] for r in results_list],
            'macro_f1': [r['macro_f1'] for r in results_list],
        }
    }

    with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=2, ensure_ascii=False, default=str)

    print(f"  [SAVED] Results → {OUTPUT_JSON}")
    print()
    print(divider)

    return output


if __name__ == '__main__':
    main()
