#!/usr/bin/env python3
"""
ml/generate_10samples_v4.py
============================
FINAL FIXED VERSION.

Key insight from diagnostics:
- tree_.impurity & tree_.n_node_samples: 100% accurate (from bootstrap training)
- tree_.value: normalized weights (class_weight='balanced_subsample'), NOT raw counts
- class_dist_at_node via full dataset decision_path: gives correct stand names
  but n doesn't exactly match tree's bootstrap n (slight delta expected)

Strategy for thesis:
  - Gini values: from tree_.impurity (exact)
  - n_samples at each node: from tree_.n_node_samples (exact, bootstrap)
  - Class dist: from full-data decision_path (correct labels, approx counts)
  - For Gini formula: show the actual Gini from tree, with the formula
    "Gini = 1 - Σp²" verified against tree.impurity
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

df_enc = pd.read_csv(ROOT / 'data/parking_history_encoded_redo.csv')
FEATURE_COLS = ['aircraft_type_enc','aircraft_size_enc','operator_airline_enc',
                'airline_tier_enc','category_enc','stand_zone_enc']
TARGET_COL   = 'parking_stand_enc'
X_all        = df_enc[FEATURE_COLS].values.astype(np.int64)
y_all        = df_enc[TARGET_COL].values.astype(np.int64)

stand_classes = list(encoders['parking_stand'].classes_)
model_classes = list(model.classes_)
FEATURE_NAMES = ['aircraft_type','aircraft_size','operator_airline',
                 'airline_tier','category','stand_zone']
N_TREES = len(model.estimators_)

# ── Feature engineering ───────────────────────────────────────────────────────
A0=['C 152','C 172','C 182','C 185','C 206','C 208','C 402','C 404','C 425',
    'PC 6','PC 12','C152','C172','C182','C185','C206','C208','C402','C404',
    'C425','PC6','PC12','CESSNA','PILATUS']
CATMAP={'Komersial':'COMMERCIAL','komersial':'COMMERCIAL','KOMERSIAL':'COMMERCIAL',
        'cargo':'CARGO','Cargo':'CARGO','Charter':'CHARTER','charter':'CHARTER'}
def nc(c):  return CATMAP.get(str(c).strip(),str(c).strip().upper())
def sz(at):
    ac=str(at).strip().upper().replace(' ','')
    return 'SMALL_A0_COMPATIBLE' if any(x.replace(' ','') in ac or ac in x.replace(' ','') for x in A0) else 'STANDARD'
def tr(ao):
    H=['BATIK AIR','CITILINK','GARUDA','TRIGANA','TRI MG']
    M=['PELITA','JETSET','KARISMA','JIP','PREMI','SUSI AIR']
    a=str(ao).strip().upper()
    return 'HIGH_FREQUENCY' if a in H else ('MEDIUM_FREQUENCY' if a in M else 'LOW_FREQUENCY')
def zn(cat):
    return 'RIGHT_COMMERCIAL' if cat=='COMMERCIAL' else ('LEFT_CARGO' if cat=='CARGO' else 'MIDDLE_CHARTER')
def safe_enc(enc,v):
    cls=list(enc.classes_); lk={c:i for i,c in enumerate(cls)}; return int(lk.get(v,0))
def build_sample(at,ao,cat_raw):
    at2=str(at).strip().upper(); ao2=str(ao).strip().upper(); cat2=nc(cat_raw)
    s=sz(at2); ti=tr(ao2); z=zn(cat2)
    vec=[safe_enc(encoders['aircraft_type'],at2),
         safe_enc(encoders['aircraft_size'],s),
         safe_enc(encoders['operator_airline'],ao2),
         safe_enc(encoders['airline_tier'],ti),
         safe_enc(encoders['category'],cat2),
         safe_enc(encoders['stand_zone'],z)]
    return dict(aircraft_type=at2,operator_airline=ao2,category=cat2,
                aircraft_size=s,airline_tier=ti,stand_zone=z,vec=vec)

# ── Class distribution from full data at a given node ─────────────────────────
def class_dist_full(estimator, node_id):
    """
    Uses full training data to find which samples reach this node.
    """
    ni = estimator.decision_path(X_all)
    col = ni.getcol(node_id)
    mask = np.asarray(col.todense()).flatten().astype(bool)
    y_node = y_all[mask]
    n = len(y_node)
    if n == 0:
        return [], 0
    dist = []
    for se in sorted(set(y_node)):
        cnt = int(np.sum(y_node == se))
        dist.append({'stand': stand_classes[se], 'count': cnt, 'p': round(cnt/n,6)})
    dist.sort(key=lambda d: -d['count'])
    return dist, n

# ── Trace decision path ───────────────────────────────────────────────────────
def trace_path(estimator, x_vec):
    t = estimator.tree_
    X = np.array(x_vec, dtype=np.float64).reshape(1,-1)
    indicator = estimator.decision_path(X)
    node_ids  = list(indicator.indices)
    path = []
    for nid in node_ids:
        is_leaf = (t.children_left[nid] == _tree.TREE_LEAF)
        info = {'node_id': int(nid), 'is_leaf': is_leaf,
                'gini': round(float(t.impurity[nid]),4),
                'n_samples': int(t.n_node_samples[nid])}
        if not is_leaf:
            feat=int(t.feature[nid]); thr=float(t.threshold[nid])
            xval=int(x_vec[feat]); gl=xval<=thr
            info.update({'feature':FEATURE_NAMES[feat],'feature_index':feat,
                         'threshold':round(thr,4),'x_value':xval,
                         'direction':'LEFT' if gl else 'RIGHT',
                         'condition':f'{xval} {"<=" if gl else ">"} {thr:.4f}'})
        else:
            pred_ci=int(np.argmax(t.value[nid][0]))
            info['predicted_stand']=stand_classes[model_classes[pred_ci]]
        path.append(info)
    return path

# ── Votes ─────────────────────────────────────────────────────────────────────
def get_votes(x_2d):
    votes={}
    for est in model.estimators_:
        sn=stand_classes[int(est.predict(x_2d)[0])]
        votes[sn]=votes.get(sn,0)+1
    return dict(sorted(votes.items(),key=lambda kv:-kv[1]))

# ── Main ──────────────────────────────────────────────────────────────────────
SAMPLES=[
    ('ATR 72','PELITA',   'Komersial','A2'),
    ('B 738', 'GARUDA',   'Komersial','B2'),
    ('A 320', 'BATIK AIR','Komersial','B5'),
    ('ATR 72','CITILINK', 'Komersial','B2'),
    ('ATR 72','FLY JAYA', 'Komersial','B2'),
    ('G IV',  'JETSET',   'Charter',  'B7'),
    ('EMB 135','KARISMA', 'CHARTER',  'B4'),
    ('BBJ',   'JIP',      'Charter',  'B4'),
    ('B 733', 'TRI MG',   'cargo',    'B10'),
    ('B 734', 'B. B. N.','cargo',    'B11'),
]

results=[]
tree0=model.estimators_[0]

for idx,(at,ao,cat_raw,act_stand) in enumerate(SAMPLES,1):
    print(f'[{idx:02d}/10] {at} / {ao} / {nc(cat_raw)} ...', end=' ', flush=True)
    feat=build_sample(at,ao,cat_raw)
    x=np.array(feat['vec'],dtype=np.int64)
    x2d=x.reshape(1,-1)

    path=trace_path(tree0,x)
    root_node=path[0]; leaf_node=path[-1]

    # Class distributions from full training data
    root_dist, root_n_full = class_dist_full(tree0, root_node['node_id'])
    leaf_dist, leaf_n_full = class_dist_full(tree0, leaf_node['node_id'])

    # Gini from tree (exact, from bootstrap training)
    root_gini = root_node['gini']  # from tree_.impurity, e.g. 0.9412
    leaf_gini = leaf_node['gini']

    # Gini formula step using full-data proportions (approximate but shows correct stand names)
    if root_dist and root_n_full > 0:
        root_sq_sum = sum(d['p']**2 for d in root_dist)
        root_gini_approx = round(1 - root_sq_sum, 4)
    else:
        root_sq_sum = 0; root_gini_approx = root_gini

    if leaf_dist and leaf_n_full > 0:
        leaf_sq_sum = sum(d['p']**2 for d in leaf_dist)
        leaf_gini_approx = round(1 - leaf_sq_sum, 4)
    else:
        leaf_sq_sum = 0; leaf_gini_approx = leaf_gini

    # Votes
    vote_dict=get_votes(x2d)
    vote_list=[{'stand':s,'votes':v,'vote_prob':round(v/N_TREES,4)} for s,v in vote_dict.items()]

    # predict_proba
    proba=model.predict_proba(x2d)[0]
    top3i=np.argsort(proba)[::-1][:3]
    top3=[{'rank':r,'stand':stand_classes[model_classes[ci]],'prob':round(float(proba[ci]),4)}
          for r,ci in enumerate(top3i,1)]
    in_top3=any(t['stand']==act_stand for t in top3)

    rec={
        'no':idx,'aircraft_type':feat['aircraft_type'],
        'operator_airline':feat['operator_airline'],'category_norm':feat['category'],
        'category_raw':cat_raw,'actual_stand':act_stand,
        'aircraft_size':feat['aircraft_size'],'airline_tier':feat['airline_tier'],
        'stand_zone':feat['stand_zone'],'encoded_vector':feat['vec'],
        # Path info
        'tree0_path':path,
        'root_node_id':root_node['node_id'],
        'root_n_samples':root_node['n_samples'],   # from tree bootstrap
        'root_gini':root_gini,                      # from tree_.impurity
        'root_dist_full':root_dist,                 # from full data
        'root_n_full':root_n_full,
        'root_gini_approx':root_gini_approx,        # from full data proportions
        'leaf_node_id':leaf_node['node_id'],
        'leaf_n_samples':leaf_node['n_samples'],
        'leaf_gini':leaf_gini,
        'leaf_dist_full':leaf_dist,
        'leaf_n_full':leaf_n_full,
        'leaf_gini_approx':leaf_gini_approx,
        'leaf_predicted_stand':leaf_node.get('predicted_stand','?'),
        # Votes & proba
        'votes':vote_list,'top3':top3,'in_top3':in_top3,
    }
    results.append(rec)

    top3s=', '.join(f'{t["stand"]}({t["prob"]*100:.1f}%)' for t in top3)
    print(f'path_len={len(path)}  leaf={leaf_node["node_id"]}  top3=[{top3s}]  [{"BENAR" if in_top3 else "SALAH"}]')

out=ROOT/'ml/samples_v4_output.json'
with open(out,'w',encoding='utf-8') as f:
    json.dump(results,f,indent=2,ensure_ascii=False,default=str)
print(f'\nSaved -> {out}')
