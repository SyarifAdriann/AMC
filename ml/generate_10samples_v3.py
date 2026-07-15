#!/usr/bin/env python3
"""
ml/generate_10samples_v3.py
============================
Extract full mathematical detail for 10 samples:
  A. Input + Feature Engineering
  B. Label Encoding + Vector X
  C. Root Node Gini (actual class distribution from Tree #0)
  D. Decision Tree Path Tree #0 + Leaf Node Gini
  E. Vote counts from all 200 trees
  F. predict_proba Top-3

Output: ml/samples_v3_output.json
"""

import json, pickle, warnings
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.tree import _tree

warnings.filterwarnings('ignore')

ROOT = Path(__file__).resolve().parents[1]

with open(ROOT / 'ml/encoders_redo.pkl', 'rb') as f:
    encoders = pickle.load(f)
with open(ROOT / 'ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
    model = pickle.load(f)

stand_classes  = list(encoders['parking_stand'].classes_)   # string names
model_classes  = list(model.classes_)                       # integer indices
FEATURE_NAMES  = ['aircraft_type', 'aircraft_size', 'operator_airline',
                  'airline_tier', 'category', 'stand_zone']
N_TREES = len(model.estimators_)

# ── Feature engineering helpers (exact copy from predict.py) ─────────────────
A0 = ['C 152','C 172','C 182','C 185','C 206','C 208','C 402','C 404','C 425',
      'PC 6','PC 12','C152','C172','C182','C185','C206','C208','C402','C404',
      'C425','PC6','PC12','CESSNA','PILATUS']
CATMAP = {'Komersial':'COMMERCIAL','komersial':'COMMERCIAL','KOMERSIAL':'COMMERCIAL',
          'cargo':'CARGO','Cargo':'CARGO','Charter':'CHARTER','charter':'CHARTER'}

def nc(c):  return CATMAP.get(str(c).strip(), str(c).strip().upper())
def sz(at):
    ac = str(at).strip().upper().replace(' ','')
    return 'SMALL_A0_COMPATIBLE' if any(
        x.replace(' ','') in ac or ac in x.replace(' ','') for x in A0
    ) else 'STANDARD'
def tr(ao):
    H=['BATIK AIR','CITILINK','GARUDA','TRIGANA','TRI MG']
    M=['PELITA','JETSET','KARISMA','JIP','PREMI','SUSI AIR']
    a=str(ao).strip().upper()
    return 'HIGH_FREQUENCY' if a in H else ('MEDIUM_FREQUENCY' if a in M else 'LOW_FREQUENCY')
def zn(cat):
    return 'RIGHT_COMMERCIAL' if cat=='COMMERCIAL' else ('LEFT_CARGO' if cat=='CARGO' else 'MIDDLE_CHARTER')

def safe_enc(enc, v):
    cls=list(enc.classes_); lk={c:i for i,c in enumerate(cls)}; return int(lk.get(v, 0))

def build_sample(at, ao, cat_raw):
    at2  = str(at).strip().upper()
    ao2  = str(ao).strip().upper()
    cat2 = nc(cat_raw)
    s    = sz(at2); t = tr(ao2); z = zn(cat2)
    vec  = [
        safe_enc(encoders['aircraft_type'],    at2),
        safe_enc(encoders['aircraft_size'],    s),
        safe_enc(encoders['operator_airline'], ao2),
        safe_enc(encoders['airline_tier'],     t),
        safe_enc(encoders['category'],         cat2),
        safe_enc(encoders['stand_zone'],       z),
    ]
    return dict(aircraft_type=at2, operator_airline=ao2,
                category=cat2, aircraft_size=s, airline_tier=t, stand_zone=z,
                vec=vec)

# ── Tree path tracer ──────────────────────────────────────────────────────────
def trace_tree(tree_obj, x_vec):
    """Trace decision path through one sklearn decision tree estimator."""
    t      = tree_obj.tree_
    node   = 0
    path   = []
    while True:
        feat    = t.feature[node]
        thr     = t.threshold[node]
        gini    = float(t.impurity[node])
        n_node  = int(t.n_node_samples[node])
        val_arr = t.value[node][0]          # shape (n_classes,)
        total   = val_arr.sum()

        # Class distribution (sorted by count desc)
        class_dist = []
        for ci, cnt in enumerate(val_arr):
            if cnt > 0:
                stand_int  = model_classes[ci]
                stand_name = stand_classes[stand_int]
                class_dist.append({'stand': stand_name,
                                   'count': int(cnt),
                                   'p': round(float(cnt/total), 6)})
        class_dist.sort(key=lambda d: -d['count'])

        is_leaf = (feat == _tree.TREE_UNDEFINED)
        node_info = {
            'node_id':    node,
            'is_leaf':    is_leaf,
            'gini':       round(gini, 6),
            'n_samples':  n_node,
            'class_dist': class_dist,
        }
        if not is_leaf:
            feat_name = FEATURE_NAMES[feat]
            val_x     = int(x_vec[feat])
            go_left   = val_x <= thr
            node_info.update({
                'feature':       feat_name,
                'feature_index': int(feat),
                'threshold':     round(float(thr), 4),
                'x_value':       val_x,
                'condition':     f'{val_x} {"<=" if go_left else ">"} {thr:.4f}',
                'direction':     'LEFT' if go_left else 'RIGHT',
            })
            path.append(node_info)
            node = t.children_left[node] if go_left else t.children_right[node]
        else:
            node_info['predicted_stand'] = class_dist[0]['stand'] if class_dist else '?'
            path.append(node_info)
            break
    return path

# ── Gini manual calculator ────────────────────────────────────────────────────
def compute_gini(class_dist):
    total = sum(d['count'] for d in class_dist)
    if total == 0:
        return 0.0
    sq_sum = sum((d['count'] / total) ** 2 for d in class_dist)
    return round(1.0 - sq_sum, 6)

# ── Vote counter from all 200 trees ──────────────────────────────────────────
def get_votes(x_2d):
    """x_2d: shape (1, 6). Returns dict stand_name -> vote_count."""
    votes = {}
    for est in model.estimators_:
        pred_int   = int(est.predict(x_2d)[0])
        stand_name = stand_classes[pred_int]
        votes[stand_name] = votes.get(stand_name, 0) + 1
    return dict(sorted(votes.items(), key=lambda kv: -kv[1]))

# ── Main processing ───────────────────────────────────────────────────────────
SAMPLES = [
    ('ATR 72', 'PELITA',    'Komersial', 'A2'),
    ('B 738',  'GARUDA',    'Komersial', 'B2'),
    ('A 320',  'BATIK AIR', 'Komersial', 'B5'),
    ('ATR 72', 'CITILINK',  'Komersial', 'B2'),
    ('ATR 72', 'FLY JAYA',  'Komersial', 'B2'),
    ('G IV',   'JETSET',    'Charter',   'B7'),
    ('EMB 135','KARISMA',   'CHARTER',   'B4'),
    ('BBJ',    'JIP',       'Charter',   'B4'),
    ('B 733',  'TRI MG',    'cargo',     'B10'),
    ('B 734',  'B. B. N.', 'cargo',     'B11'),
]

results = []

for idx, (at, ao, cat_raw, actual_stand) in enumerate(SAMPLES, 1):
    print(f'[{idx:02d}/10] {at} / {ao} / {nc(cat_raw)} ...', end=' ', flush=True)

    feat   = build_sample(at, ao, cat_raw)
    x_vec  = np.array(feat['vec'], dtype=np.int64)
    x_2d   = x_vec.reshape(1, -1)

    # Tree #0 path
    tree0  = model.estimators_[0]
    path   = trace_tree(tree0, x_vec)
    root   = path[0]
    leaf   = path[-1]

    # Gini calculations
    root_gini_manual  = compute_gini(root['class_dist'])
    leaf_gini_manual  = compute_gini(leaf['class_dist'])

    # Votes from all 200 trees
    vote_dict = get_votes(x_2d)
    vote_list = [{'stand': s, 'votes': v,
                  'vote_prob': round(v / N_TREES, 6)}
                 for s, v in vote_dict.items()]

    # predict_proba
    proba    = model.predict_proba(x_2d)[0]
    top3_idx = np.argsort(proba)[::-1][:3]
    top3     = []
    for rank, ci in enumerate(top3_idx, 1):
        stand_int  = model_classes[ci]
        stand_name = stand_classes[stand_int]
        top3.append({'rank': rank, 'stand': stand_name,
                     'prob': round(float(proba[ci]), 6)})

    in_top3 = any(t['stand'] == actual_stand for t in top3)

    # Gini step-by-step string for root (all 17 classes, with p²)
    root_total = sum(d['count'] for d in root['class_dist'])
    if root_total > 0:
        sq_terms = [round((d['count']/root_total)**2, 8) for d in root['class_dist']]
    else:
        sq_terms = [0.0 for d in root['class_dist']]
    sum_sq     = round(sum(sq_terms), 8)
    gini_check = round(1 - sum_sq, 6)

    leaf_total  = sum(d['count'] for d in leaf['class_dist'])
    if leaf_total > 0:
        leaf_sq = [round((d['count']/leaf_total)**2, 8) for d in leaf['class_dist']]
    else:
        leaf_sq = [0.0 for d in leaf['class_dist']]
    leaf_sum_sq = round(sum(leaf_sq), 8)
    leaf_gini_check = round(1 - leaf_sum_sq, 6)

    rec = {
        'no':              idx,
        'aircraft_type':   feat['aircraft_type'],
        'operator_airline':feat['operator_airline'],
        'category_raw':    cat_raw,
        'category_norm':   feat['category'],
        'actual_stand':    actual_stand,
        'aircraft_size':   feat['aircraft_size'],
        'airline_tier':    feat['airline_tier'],
        'stand_zone':      feat['stand_zone'],
        'encoded_vector':  feat['vec'],
        'tree0_path':      path,
        'root_n_samples':  root['n_samples'],
        'root_gini':       root['gini'],
        'root_gini_manual':root_gini_manual,
        'root_class_dist': root['class_dist'],
        'root_sq_terms':   sq_terms,
        'root_sum_sq':     sum_sq,
        'leaf_node_id':    leaf['node_id'],
        'leaf_n_samples':  leaf['n_samples'],
        'leaf_gini':       leaf['gini'],
        'leaf_gini_manual':leaf_gini_manual,
        'leaf_class_dist': leaf['class_dist'],
        'leaf_sq_terms':   leaf_sq,
        'leaf_sum_sq':     leaf_sum_sq,
        'leaf_pred_stand': leaf['predicted_stand'],
        'votes':           vote_list,
        'top3':            top3,
        'in_top3':         in_top3,
    }
    results.append(rec)

    top3_str = ', '.join(f"{t['stand']}({t['prob']*100:.1f}%)" for t in top3)
    check    = 'BENAR' if in_top3 else 'SALAH'
    print(f'X={feat["vec"]}  top3=[{top3_str}]  [{check}]')

out_path = ROOT / 'ml/samples_v3_output.json'
with open(out_path, 'w', encoding='utf-8') as f:
    json.dump(results, f, indent=2, ensure_ascii=False, default=str)

print(f'\nSaved -> {out_path}')
