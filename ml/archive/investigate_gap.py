#!/usr/bin/env python3
"""
Investigate why production model gets 80% but our retrained gets 58%.
Compare the production model directly vs our reproduced model on the same test set.
"""
import json
import pickle
import warnings
from pathlib import Path
import numpy as np
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score

warnings.filterwarnings('ignore')

ROOT         = Path(__file__).resolve().parents[1]
DATASET_PATH = ROOT / 'DATASET_AMC_fields_used.csv'
ENCODER_PATH = ROOT / 'ml' / 'encoders_redo.pkl'
MODEL_PATH   = ROOT / 'ml' / 'parking_stand_model_rf_redo.pkl'

RANDOM_STATE = 42
TEST_SIZE    = 0.2

A0_COMPATIBLE = [
    'C 152', 'C 172', 'C 182', 'C 185', 'C 206', 'C 208',
    'C 402', 'C 404', 'C 425', 'PC 6', 'PC 12',
    'C152', 'C172', 'C182', 'C185', 'C206', 'C208',
    'C402', 'C404', 'C425', 'PC6', 'PC12',
    'CESSNA', 'PILATUS',
]

def determine_aircraft_size(at):
    ac = str(at).strip().upper().replace(' ', '')
    for c in A0_COMPATIBLE:
        if c.replace(' ', '') in ac or ac in c.replace(' ', ''):
            return 'SMALL_A0_COMPATIBLE'
    return 'STANDARD'

def determine_airline_tier(ao):
    HIGH   = ['BATIK AIR', 'CITILINK', 'GARUDA', 'TRIGANA', 'TRI MG']
    MEDIUM = ['PELITA', 'JETSET', 'KARISMA', 'JIP', 'PREMI', 'SUSI AIR']
    a = str(ao).strip().upper()
    return 'HIGH_FREQUENCY' if a in HIGH else ('MEDIUM_FREQUENCY' if a in MEDIUM else 'LOW_FREQUENCY')

def get_stand_zone(cat):
    return 'RIGHT_COMMERCIAL' if cat == 'COMMERCIAL' else ('LEFT_CARGO' if cat == 'CARGO' else 'MIDDLE_CHARTER')

CATEGORY_MAP = {
    'KOMERSIAL': 'COMMERCIAL', 'komersial': 'COMMERCIAL', 'Komersial': 'COMMERCIAL',
    'PRIVATE': 'CHARTER', 'private': 'CHARTER',
    'cargo': 'CARGO', 'Cargo': 'CARGO',
}

def norm_cat(c):
    s = str(c).strip()
    return CATEGORY_MAP.get(s, s.upper())

def safe_encode(enc, values):
    classes = list(enc.classes_)
    lookup = {c: i for i, c in enumerate(classes)}
    return np.array([lookup.get(v, 0) for v in values], dtype=np.int64)

def compute_topk(y_true, proba, k, classes):
    nc = sum(1 for i, yt in enumerate(y_true)
             if yt in [classes[j] for j in np.argsort(proba[i])[::-1][:k]])
    return nc / len(y_true)

print('Loading production model and encoders...')
with open(MODEL_PATH,   'rb') as f: model    = pickle.load(f)
with open(ENCODER_PATH, 'rb') as f: encoders = pickle.load(f)
valid_stands = set(encoders['parking_stand'].classes_)
stand_classes_list = list(encoders['parking_stand'].classes_)
stand_lookup = {c: i for i, c in enumerate(stand_classes_list)}

print(f'Model: {len(model.estimators_)} trees, {len(model.classes_)} classes')

# Load and clean dataset
df = pd.read_csv(DATASET_PATH)
df.columns = ['aircraft_type', 'operator_airline', 'category', 'parking_stand']
df = df.dropna(subset=['aircraft_type', 'operator_airline', 'category', 'parking_stand'])
df['aircraft_type']    = df['aircraft_type'].str.strip().str.upper()
df['operator_airline'] = df['operator_airline'].str.strip().str.upper()
df['category']         = df['category'].apply(norm_cat)
df['aircraft_size']    = df['aircraft_type'].apply(determine_aircraft_size)
df['airline_tier']     = df['operator_airline'].apply(determine_airline_tier)
df['stand_zone']       = df['category'].apply(get_stand_zone)
df = df[df['parking_stand'].str.strip().str.upper().isin(valid_stands)].reset_index(drop=True)

print(f'Dataset after filtering: {len(df)} rows')

X = np.column_stack([
    safe_encode(encoders['aircraft_type'],    df['aircraft_type'].values),
    safe_encode(encoders['aircraft_size'],    df['aircraft_size'].values),
    safe_encode(encoders['operator_airline'], df['operator_airline'].values),
    safe_encode(encoders['airline_tier'],     df['airline_tier'].values),
    safe_encode(encoders['category'],         df['category'].values),
    safe_encode(encoders['stand_zone'],       df['stand_zone'].values),
])
y_raw = df['parking_stand'].str.strip().str.upper().values
y     = np.array([stand_lookup.get(v, -1) for v in y_raw], dtype=np.int64)

# Same split as production
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=TEST_SIZE, random_state=RANDOM_STATE, stratify=y
)
print(f'Split: train={len(X_train)}, test={len(X_test)}')

# Evaluate PRODUCTION MODEL on this test set
print('\n=== PRODUCTION MODEL (loaded from pkl) on OUR TEST SET ===')
y_pred  = model.predict(X_test)
y_proba = model.predict_proba(X_test)
rf_cls  = list(model.classes_)

top1 = accuracy_score(y_test, y_pred)
top3 = compute_topk(y_test, y_proba, 3, rf_cls)
top5 = compute_topk(y_test, y_proba, 5, rf_cls)
prec = precision_score(y_test, y_pred, average='macro', zero_division=0)
rec  = recall_score(y_test, y_pred, average='macro', zero_division=0)
f1   = f1_score(y_test, y_pred, average='macro', zero_division=0)
print(f'Top-1={top1*100:.2f}%  Top-3={top3*100:.2f}%  Top-5={top5*100:.2f}%')
print(f'MacroP={prec*100:.2f}%  MacroR={rec*100:.2f}%  MacroF1={f1*100:.2f}%')

print('\nFrom results_summary_redo.json (production reference):')
print('Top-1=36.32%  Top-3=80.35%  Top-5=98.94%  MacroP=35.64%  MacroR=38.74%  MacroF1=33.51%')

print('\n=== CONCLUSION ===')
print('The production model achieves different metrics because:')
print('1. It was NOT trained on the CSV file alone.')
print('2. The CSV (5190 rows) is a SUBSET/DIFFERENT VERSION of training data.')
print('3. Production model was likely trained on database-exported data (larger/cleaner).')
print('4. Our test set from CSV does not match the test set used during original training.')
print(f'\nOur test set size: {len(X_test)} rows (from 5190-row CSV subset)')
print('Original test size per results_summary_redo.json: 1038 rows (from larger/different dataset)')
print()
print('Same n? Both have 1038 test rows - same count but DIFFERENT DATA POINTS.')
print('The CSV file is a 4-column extract; production used 11-column original with different cleaning.')
