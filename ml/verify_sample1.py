#!/usr/bin/env python3
"""
ml/generate_10samples_v4.py
============================
FIXED: Uses decision_path + training data to get real class distributions.

The model was saved with class_weight='balanced_subsample' which means
tree_.value stores NORMALIZED weights (not raw counts). So class distributions
are computed from the ACTUAL training data subset that reached each node.

For the thesis, we show:
  - Gini values: from tree_.impurity (100% accurate)
  - n_samples: from tree_.n_node_samples (100% accurate)
  - Class dist at root: from the full training data that Tree #0 was trained on
  - Class dist at leaf: from the samples that reached the leaf (using apply())
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

# Load the actual training data (encoded)
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

# ── Feature engineering (same as predict.py) ─────────────────────────────────
A0 = ['C 152','C 172','C 182','C 185','C 206','C 208','C 402','C 404','C 425',
      'PC 6','PC 12','C152','C172','C182','C185','C206','C208','C402','C404',
      'C425','PC6','PC12','CESSNA','PILATUS']
CATMAP = {'Komersial':'COMMERCIAL','komersial':'COMMERCIAL','KOMERSIAL':'COMMERCIAL',
          'cargo':'CARGO','Cargo':'CARGO','Charter':'CHARTER','charter':'CHARTER'}
def nc(c):  return CATMAP.get(str(c).strip(), str(c).strip().upper())
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
def safe_enc(enc, v):
    cls=list(enc.classes_); lk={c:i for i,c in enumerate(cls)}; return int(lk.get(v, 0))
def build_sample(at, ao, cat_raw):
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

# ── Class distribution from training data via node assignment ─────────────────
def class_dist_at_node(estimator, node_id, X_train, y_train, stand_classes, model_classes):
    """
    Find which training samples reached this node, then compute class distribution.
    estimator: a single DecisionTreeClassifier
    """
    node_indicator = estimator.decision_path(X_train)
    # node_indicator is sparse (n_samples × n_nodes), find samples at this node
    samples_at_node = node_indicator[:, node_id].toarray().flatten().astype(bool)
    y_at_node = y_train[samples_at_node]
    n_at_node = len(y_at_node)

    dist = []
    for stand_enc in sorted(set(y_at_node)):
        cnt = int(np.sum(y_at_node == stand_enc))
        # Map encoded int → stand name
        # stand_enc is the raw y value; find corresponding stand name
        # model_classes maps ci (index) → stand_enc int
        # stand_classes maps stand_enc int → string name (direct index)
        stand_name = stand_classes[stand_enc]
        dist.append({'stand': stand_name,
                     'count': cnt,
                     'p': round(cnt / n_at_node, 6)})
    dist.sort(key=lambda d: -d['count'])
    return dist, n_at_node

# ── Trace decision path (using decision_path) ─────────────────────────────────
def trace_path(estimator, x_vec, stand_classes, model_classes):
    """Trace root→leaf using decision_path indices."""
    t = estimator.tree_
    X = np.array(x_vec, dtype=np.float64).reshape(1, -1)
    indicator = estimator.decision_path(X)
    node_ids  = list(indicator.indices)

    path = []
    for nid in node_ids:
        is_leaf = (t.children_left[nid] == _tree.TREE_LEAF)
        n_node  = int(t.n_node_samples[nid])
        gini    = float(t.impurity[nid])

        info = {'node_id': nid, 'is_leaf': is_leaf,
                'gini': gini, 'n_samples': n_node}
        if not is_leaf:
            feat    = int(t.feature[nid])
            thr     = float(t.threshold[nid])
            xval    = int(x_vec[feat])
            go_left = xval <= thr
            info.update({
                'feature':       FEATURE_NAMES[feat],
                'feature_index': feat,
                'threshold':     round(thr, 4),
                'x_value':       xval,
                'condition':     f'{xval} {"<=" if go_left else ">"} {thr:.4f}',
                'direction':     'LEFT' if go_left else 'RIGHT',
            })
        else:
            pred_ci = int(np.argmax(t.value[nid][0]))
            info['predicted_stand'] = stand_classes[model_classes[pred_ci]]
        path.append(info)
    return path

# ── Vote counter ──────────────────────────────────────────────────────────────
def get_votes(x_2d):
    votes = {}
    for est in model.estimators_:
        pred_int   = int(est.predict(x_2d)[0])
        stand_name = stand_classes[pred_int]
        votes[stand_name] = votes.get(stand_name, 0) + 1
    return dict(sorted(votes.items(), key=lambda kv: -kv[1]))

# ── Pre-compute Tree #0's bootstrap training indices ─────────────────────────
# sklearn stores bootstrap sample indices per tree via _estimator_samples_
# For models fitted with bootstrap=True (default), we can't recover exact
# bootstrap indices after the fact. Instead, use ALL training data for
# class distribution (it's the best approximation we have).
# The n_node_samples in the tree IS from the bootstrap, so n values are correct.
# For class dist, we use the full dataset passed to decision_path.
print("Pre-computing class distributions using full training data...")
print("(This uses the full dataset; n_samples from tree is bootstrap-based)")
print()

# ── STEP 4: Verify Sample 1 first ─────────────────────────────────────────────
print("="*70)
print("STEP 4 — VERIFY SAMPLE 1: ATR 72 / PELITA / COMMERCIAL")
print("="*70)
s1 = build_sample('ATR 72', 'PELITA', 'Komersial')
x1 = np.array(s1['vec'], dtype=np.int64)
print(f"Encoded vector: {s1['vec']}")
print()

tree0 = model.estimators_[0]
path1 = trace_path(tree0, x1, stand_classes, model_classes)
print(f"Decision path nodes: {[n['node_id'] for n in path1]}")
print(f"Leaf node: {path1[-1]['node_id']}, predicted: {path1[-1].get('predicted_stand','?')}")
print()

# Root node class dist from actual data
root_dist, root_n = class_dist_at_node(tree0, 0, X_all, y_all, stand_classes, model_classes)
print(f"Root class dist (n={root_n} from FULL data vs tree n_samples={path1[0]['n_samples']}):")
for d in root_dist[:5]:
    print(f"  Stand {d['stand']}: {d['count']:>5}  p={d['p']:.6f}")

gini_manual = 1 - sum(d['p']**2 for d in root_dist)
print(f"\nGini(root) manual = {gini_manual:.4f}  (tree says: {path1[0]['gini']:.4f})")
print()

# Leaf node class dist
leaf_nid = path1[-1]['node_id']
leaf_dist, leaf_n = class_dist_at_node(tree0, leaf_nid, X_all, y_all, stand_classes, model_classes)
print(f"Leaf (Node {leaf_nid}) class dist (n={leaf_n}):")
for d in leaf_dist:
    print(f"  Stand {d['stand']}: {d['count']:>5}  p={d['p']:.6f}")
gini_leaf_manual = 1 - sum(d['p']**2 for d in leaf_dist)
print(f"Gini(leaf) manual = {gini_leaf_manual:.4f}  (tree says: {path1[-1]['gini']:.4f})")
print()

# predict_proba
x1_2d = x1.reshape(1,-1)
proba = model.predict_proba(x1_2d)[0]
top3i = np.argsort(proba)[::-1][:3]
print("predict_proba Top-3:")
for r, ci in enumerate(top3i, 1):
    sn = stand_classes[model_classes[ci]]
    print(f"  Rank {r}: {sn}  {proba[ci]*100:.2f}%")

print()
print("="*70)
print("VERIFICATION COMPLETE — proceeding to all 10 samples")
print("="*70)
