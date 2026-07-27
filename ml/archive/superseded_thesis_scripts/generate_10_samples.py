#!/usr/bin/env python3
"""
ml/generate_10_samples.py
Generate 10 varied samples from the dataset with full prediction details.
"""
import json, pickle, warnings
from pathlib import Path
import numpy as np
import pandas as pd

warnings.filterwarnings('ignore')

ROOT = Path(__file__).resolve().parents[1]

with open(ROOT/'ml/encoders_redo.pkl','rb') as f: encoders = pickle.load(f)
with open(ROOT/'ml/parking_stand_model_rf_redo.pkl','rb') as f: model = pickle.load(f)

valid_stands = set(encoders['parking_stand'].classes_)
stand_classes = list(encoders['parking_stand'].classes_)

CATEGORY_MAP = {
    'KOMERSIAL': 'COMMERCIAL', 'komersial': 'COMMERCIAL', 'Komersial': 'COMMERCIAL',
    'PRIVATE': 'CHARTER', 'cargo': 'CARGO', 'Cargo': 'CARGO',
}
A0_COMPAT = ['C 152','C 172','C 182','C 185','C 206','C 208','C 402','C 404','C 425','PC 6','PC 12',
             'C152','C172','C182','C185','C206','C208','C402','C404','C425','PC6','PC12','CESSNA','PILATUS']

def norm_cat(c):
    s = str(c).strip()
    return CATEGORY_MAP.get(s, s.upper())

def det_size(at):
    ac = str(at).strip().upper().replace(' ','')
    for c in A0_COMPAT:
        if c.replace(' ','') in ac or ac in c.replace(' ',''): return 'SMALL_A0_COMPATIBLE'
    return 'STANDARD'

def det_tier(ao):
    H = ['BATIK AIR','CITILINK','GARUDA','TRIGANA','TRI MG']
    M = ['PELITA','JETSET','KARISMA','JIP','PREMI','SUSI AIR']
    a = str(ao).strip().upper()
    return 'HIGH_FREQUENCY' if a in H else ('MEDIUM_FREQUENCY' if a in M else 'LOW_FREQUENCY')

def get_zone(cat):
    return 'RIGHT_COMMERCIAL' if cat=='COMMERCIAL' else ('LEFT_CARGO' if cat=='CARGO' else 'MIDDLE_CHARTER')

def safe_enc(enc, v):
    cls = list(enc.classes_)
    lk = {c:i for i,c in enumerate(cls)}
    return int(lk.get(v, 0))

def predict_sample(at, ao, cat):
    at2 = str(at).strip().upper()
    ao2 = str(ao).strip().upper()
    cat2 = norm_cat(cat)
    sz = det_size(at2)
    tier = det_tier(ao2)
    zone = get_zone(cat2)
    vec = [
        safe_enc(encoders['aircraft_type'], at2),
        safe_enc(encoders['aircraft_size'], sz),
        safe_enc(encoders['operator_airline'], ao2),
        safe_enc(encoders['airline_tier'], tier),
        safe_enc(encoders['category'], cat2),
        safe_enc(encoders['stand_zone'], zone),
    ]
    X = np.array(vec, dtype=np.int64).reshape(1,-1)
    proba = model.predict_proba(X)[0]
    top3_idx = np.argsort(proba)[::-1][:3]
    top3 = []
    for rank, idx in enumerate(top3_idx, 1):
        stand_int = int(model.classes_[idx])
        stand_name = stand_classes[stand_int] if 0 <= stand_int < len(stand_classes) else '?'
        top3.append({'rank': rank, 'stand': stand_name, 'prob': round(float(proba[idx]), 4)})
    return {
        'aircraft_type': at2, 'operator_airline': ao2,
        'category': cat2, 'aircraft_size': sz, 'airline_tier': tier, 'stand_zone': zone,
        'encoded_vector': vec, 'top3': top3
    }

# Load dataset
df = pd.read_csv(ROOT/'DATASET_AMC_fields_used.csv')
df.columns = ['aircraft_type','operator_airline','category','parking_stand']
df = df.dropna()
df['aircraft_type'] = df['aircraft_type'].str.strip().str.upper()
df['operator_airline'] = df['operator_airline'].str.strip().str.upper()
df['category_norm'] = df['category'].apply(norm_cat)
df = df[df['parking_stand'].str.strip().str.upper().isin(valid_stands)]
df = df[df['aircraft_type'].isin(set(encoders['aircraft_type'].classes_))]
df = df[df['operator_airline'].isin(set(encoders['operator_airline'].classes_))]
df['airline_tier'] = df['operator_airline'].apply(det_tier)
df = df[df['category_norm'].isin(['COMMERCIAL','CARGO','CHARTER'])].reset_index(drop=True)

print(f'Filtered dataset: {len(df)} rows')
print(f'Categories: {df["category_norm"].value_counts().to_dict()}')
print(f'Tiers: {df["airline_tier"].value_counts().to_dict()}')

# Pick 10 diverse samples
# 1. First sample: ATR 72 / PELITA / COMMERCIAL (already in rev1)
# Need: 1 HIGH_FREQ, 1 MEDIUM_FREQ, several LOW_FREQ
# Need: at least 1 CARGO, 1 CHARTER, rest COMMERCIAL
# Need: different aircraft types

selected_indices = []

def pick_row(cat, tier, exclude_idx=None, exclude_at=None):
    mask = (df['category_norm'] == cat) & (df['airline_tier'] == tier)
    if exclude_idx: mask = mask & ~df.index.isin(exclude_idx)
    if exclude_at:  mask = mask & ~df['aircraft_type'].isin(exclude_at)
    sub = df[mask]
    if len(sub) == 0:
        mask2 = df['category_norm'] == cat
        if exclude_idx: mask2 = mask2 & ~df.index.isin(exclude_idx)
        sub = df[mask2]
    if len(sub) > 0:
        return sub.iloc[0]
    return None

samples_raw = []

# Sample 1 (index 0): ATR 72 / PELITA / COMMERCIAL — already determined
s1_row = df[(df['aircraft_type']=='ATR 72') & (df['operator_airline']=='PELITA')].iloc[0]
samples_raw.append(s1_row)
selected_indices.append(s1_row.name)
used_at = {'ATR 72'}

# Sample 2: COMMERCIAL + HIGH_FREQ (e.g. GARUDA)
r = pick_row('COMMERCIAL','HIGH_FREQUENCY', selected_indices, used_at)
if r is not None:
    samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])

# Sample 3: COMMERCIAL + HIGH_FREQ different airline (BATIK AIR)
r2 = df[(df['category_norm']=='COMMERCIAL') & (df['operator_airline']=='BATIK AIR') & ~df.index.isin(selected_indices)]
if len(r2) > 0:
    r = r2.iloc[0]; samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])
else:
    r = pick_row('COMMERCIAL','HIGH_FREQUENCY', selected_indices, used_at)
    if r is not None: samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])

# Sample 4: COMMERCIAL + LOW_FREQ
r = pick_row('COMMERCIAL','LOW_FREQUENCY', selected_indices, used_at)
if r is not None: samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])

# Sample 5: COMMERCIAL + MEDIUM_FREQ different from PELITA
r2 = df[(df['category_norm']=='COMMERCIAL') & (df['airline_tier']=='MEDIUM_FREQUENCY')
         & ~df.index.isin(selected_indices) & (df['operator_airline']!='PELITA')]
if len(r2) > 0:
    r = r2.iloc[0]; samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])
else:
    r = pick_row('COMMERCIAL','MEDIUM_FREQUENCY', selected_indices, used_at)
    if r is not None: samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])

# Sample 6: CARGO + HIGH_FREQ
r = pick_row('CARGO','HIGH_FREQUENCY', selected_indices)
if r is None: r = pick_row('CARGO','LOW_FREQUENCY', selected_indices)
if r is not None: samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])

# Sample 7: CARGO + different
r = pick_row('CARGO','LOW_FREQUENCY', selected_indices, {samples_raw[5]['aircraft_type'] if len(samples_raw)>5 else ''})
if r is None: r = pick_row('CARGO','MEDIUM_FREQUENCY', selected_indices)
if r is None: r = pick_row('CARGO', 'HIGH_FREQUENCY', selected_indices)
if r is not None: samples_raw.append(r); selected_indices.append(r.name)

# Sample 8: CHARTER + MEDIUM_FREQ
r = pick_row('CHARTER','MEDIUM_FREQUENCY', selected_indices)
if r is None: r = pick_row('CHARTER','LOW_FREQUENCY', selected_indices)
if r is not None: samples_raw.append(r); selected_indices.append(r.name); used_at.add(r['aircraft_type'])

# Sample 9: CHARTER + LOW_FREQ, different aircraft
r = pick_row('CHARTER','LOW_FREQUENCY', selected_indices, {s['aircraft_type'] for s in samples_raw[7:]})
if r is None: r = pick_row('CHARTER','LOW_FREQUENCY', selected_indices)
if r is not None: samples_raw.append(r); selected_indices.append(r.name)

# Sample 10: CHARTER + LOW_FREQ, yet another aircraft
r2 = df[(df['category_norm']=='CHARTER') & ~df.index.isin(selected_indices)]
if len(r2) > 0:
    r = r2.iloc[0]; samples_raw.append(r); selected_indices.append(r.name)

# Trim to 10
samples_raw = samples_raw[:10]

print(f'\nSelected {len(samples_raw)} samples:')

output_samples = []
for i, row in enumerate(samples_raw, 1):
    at     = row['aircraft_type']
    ao     = row['operator_airline']
    cat    = row['category']
    actual = row['parking_stand'].strip().upper()
    res    = predict_sample(at, ao, cat)
    in_top3 = any(t['stand'] == actual for t in res['top3'])
    rec = {
        'no': i,
        'aircraft_type': at,
        'operator_airline': ao,
        'category_raw': cat,
        'category_norm': res['category'],
        'actual_stand': actual,
        'aircraft_size': res['aircraft_size'],
        'airline_tier': res['airline_tier'],
        'stand_zone': res['stand_zone'],
        'encoded_vector': res['encoded_vector'],
        'top3': res['top3'],
        'in_top3': in_top3,
    }
    output_samples.append(rec)
    top3_str = ', '.join(f"{t['stand']}({t['prob']*100:.1f}%)" for t in res['top3'])
    check = 'BENAR' if in_top3 else 'SALAH'
    print(f"  {i:2d}. {at:<12} {ao:<15} {res['category']:<12} actual={actual:<6} "
          f"top3=[{top3_str}]  [{check}]")

with open(ROOT/'ml/samples_10_output.json','w', encoding='utf-8') as f:
    json.dump(output_samples, f, indent=2, ensure_ascii=False)

print(f'\nSaved to ml/samples_10_output.json')
