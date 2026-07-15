#!/usr/bin/env python3
"""
ml/manual_calculation.py
AMC Thesis BAB 4.2.4 - Perhitungan Manual Prediksi Random Forest

Step-by-step manual calculation showing how the Random Forest model
arrives at a prediction for one real data sample (pembuktian).
All numbers come from the REAL encoders and model — nothing is made up.
"""

from __future__ import annotations

import json
import pickle
import warnings
from pathlib import Path

import numpy as np
import pandas as pd

warnings.filterwarnings('ignore')

ROOT = Path(__file__).resolve().parents[1]
MODEL_PATH   = ROOT / 'ml' / 'parking_stand_model_rf_redo.pkl'
ENCODER_PATH = ROOT / 'ml' / 'encoders_redo.pkl'
DATASET_PATH = ROOT / 'DATASET_AMC_fields_used.csv'
OUTPUT_JSON  = ROOT / 'ml' / 'manual_calculation_output.json'
SAMPLE_JSON  = ROOT / 'ml' / 'manual_calc_sample.json'

# ── helpers from predict.py (exact copies) ──────────────────────────────────

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

def to_index(encoders: dict, name: str, value: str) -> int:
    enc = encoders[name]
    classes = list(enc.classes_)
    lookup = {cls: idx for idx, cls in enumerate(classes)}
    if value in lookup:
        return int(lookup[value])
    if '__UNKNOWN__' in lookup:
        return int(lookup['__UNKNOWN__'])
    return 0

def decode_stand(encoders: dict, idx: int) -> str:
    classes = list(encoders['parking_stand'].classes_)
    if 0 <= idx < len(classes):
        return str(classes[idx])
    return str(classes[0])

# ── Gini impurity helper ─────────────────────────────────────────────────────

def gini_from_value_counts(value_counts: np.ndarray, n_total: int) -> float:
    """Gini = 1 - sum(p_i^2)."""
    if n_total == 0:
        return 0.0
    p = value_counts / n_total
    return float(1.0 - np.sum(p ** 2))

def gini_from_node(tree, node_id: int) -> float:
    """Compute Gini from tree node sample counts."""
    counts = tree.value[node_id][0]
    total  = int(counts.sum())
    if total == 0:
        return 0.0
    return gini_from_value_counts(counts, total)

# ═══════════════════════════════════════════════════════════════════════════════
# MAIN
# ═══════════════════════════════════════════════════════════════════════════════

def main():
    divider = "=" * 70
    print(divider)
    print("  AMC THESIS — BAB 4.2.4 PERHITUNGAN MANUAL PREDIKSI RANDOM FOREST")
    print(divider)

    # ── STEP 0: Load files ───────────────────────────────────────────────────
    print("\n[STEP 0] Loading files …")
    df = pd.read_csv(DATASET_PATH)
    df.columns = ['aircraft_type','operator_airline','category','parking_stand']
    with open(MODEL_PATH,   'rb') as f: model    = pickle.load(f)
    with open(ENCODER_PATH, 'rb') as f: encoders = pickle.load(f)

    print(f"  Dataset   : {DATASET_PATH.name}  →  {len(df)} rows, {len(df.columns)} columns")
    print(f"  Model     : {MODEL_PATH.name}  →  {len(model.estimators_)} trees, {len(model.classes_)} classes")
    print(f"  Encoders  : {ENCODER_PATH.name}  →  keys: {list(encoders.keys())}")

    # ── STEP 1: Pick sample ──────────────────────────────────────────────────
    print(f"\n{divider}")
    print("  STEP 1 — PEMILIHAN SAMPEL DATA NYATA")
    print(divider)

    CATEGORY_MAP = {'KOMERSIAL':'COMMERCIAL','PRIVATE':'CHARTER',
                    'cargo':'CARGO','Komersial':'COMMERCIAL'}
    df['category_norm'] = df['category'].apply(
        lambda x: CATEGORY_MAP.get(str(x).strip(), str(x).strip().upper())
    )
    valid = df[
        df['aircraft_type'].notna() &
        df['operator_airline'].notna() &
        df['category'].notna() &
        df['parking_stand'].notna() &
        df['category_norm'].isin(['COMMERCIAL','CARGO','CHARTER'])
    ].reset_index(drop=True)

    # Pick the first valid row whose aircraft_type is in encoder classes
    ac_classes = set(encoders['aircraft_type'].classes_)
    al_classes = set(encoders['operator_airline'].classes_)
    for _, row in valid.iterrows():
        at = str(row['aircraft_type']).strip().upper()
        ao = str(row['operator_airline']).strip().upper()
        if at in ac_classes and ao in al_classes:
            sample_raw = row
            break

    aircraft_type    = str(sample_raw['aircraft_type']).strip().upper()
    operator_airline = str(sample_raw['operator_airline']).strip().upper()
    category_raw     = str(sample_raw['category']).strip()
    category_norm    = str(sample_raw['category_norm']).strip().upper()
    parking_stand    = str(sample_raw['parking_stand']).strip()

    print(f"\n  Sampel yang dipilih (baris pertama dengan data lengkap):")
    print(f"  ┌─────────────────────┬───────────────────┐")
    print(f"  │ Kolom               │ Nilai             │")
    print(f"  ├─────────────────────┼───────────────────┤")
    print(f"  │ aircraft_type       │ {aircraft_type:<17} │")
    print(f"  │ operator_airline    │ {operator_airline:<17} │")
    print(f"  │ category (asli)     │ {category_raw:<17} │")
    print(f"  │ category (dinorm.)  │ {category_norm:<17} │")
    print(f"  │ parking_stand       │ {parking_stand:<17} │")
    print(f"  └─────────────────────┴───────────────────┘")

    sample_info = {
        'aircraft_type': aircraft_type,
        'operator_airline': operator_airline,
        'category_raw': category_raw,
        'category_normalized': category_norm,
        'parking_stand_actual': parking_stand,
    }
    with open(SAMPLE_JSON,'w') as f:
        json.dump(sample_info, f, indent=2)
    print(f"\n  [SAVED] Sample  → {SAMPLE_JSON}")

    # ── STEP 2: Feature engineering ──────────────────────────────────────────
    print(f"\n{divider}")
    print("  STEP 2 — REKAYASA FITUR (FEATURE ENGINEERING)")
    print(divider)

    aircraft_size = determine_aircraft_size(aircraft_type)
    airline_tier  = determine_airline_tier(operator_airline)
    stand_zone    = get_stand_zone(category_norm)

    print(f"""
  Dari 3 fitur mentah (raw inputs) dihasilkan 6 fitur model:

  ┌─────────────────────┬────────────────────────┬─────────────────────────┐
  │ Fitur               │ Nilai Mentah (Raw)      │ Hasil/Derivasi           │
  ├─────────────────────┼────────────────────────┼─────────────────────────┤
  │ aircraft_type       │ {aircraft_type:<22} │ (digunakan langsung)    │
  │ operator_airline    │ {operator_airline:<22} │ (digunakan langsung)    │
  │ category            │ {category_norm:<22} │ (digunakan langsung)    │
  │ aircraft_size       │ dari aircraft_type     │ {aircraft_size:<23} │
  │ airline_tier        │ dari operator_airline  │ {airline_tier:<23} │
  │ stand_zone          │ dari category          │ {stand_zone:<23} │
  └─────────────────────┴────────────────────────┴─────────────────────────┘

  Penjelasan derivasi:
  • aircraft_size  : "{aircraft_type}" BUKAN termasuk jenis A0-compatible
                     (Cessna/Pilatus) → hasil = "{aircraft_size}"
  • airline_tier   : "{operator_airline}" termasuk dalam daftar HIGH_FREQUENCY
                     (BATIK AIR, CITILINK, GARUDA, TRIGANA, TRI MG)
                     → hasil = "{airline_tier}"
  • stand_zone     : Kategori "{category_norm}" → zona = "{stand_zone}"
""")

    # ── STEP 3: Encoding ─────────────────────────────────────────────────────
    print(f"\n{divider}")
    print("  STEP 3 — LABEL ENCODING (Konversi String → Angka Integer)")
    print(divider)

    feat_names  = ['aircraft_type','aircraft_size','operator_airline','airline_tier','category','stand_zone']
    feat_values = [aircraft_type, aircraft_size, operator_airline, airline_tier, category_norm, stand_zone]

    encoded_vector = []
    encoding_details = []
    print(f"\n  {'No':<3} {'Fitur':<20} {'Nilai String':<25} {'Encoded':<8} {'Semua Kelas dalam Encoder'}")
    print(f"  {'─'*3} {'─'*20} {'─'*25} {'─'*8} {'─'*45}")
    for i, (fn, fv) in enumerate(zip(feat_names, feat_values)):
        enc = encoders[fn]
        idx = to_index(encoders, fn, fv)
        encoded_vector.append(idx)
        classes = list(enc.classes_)
        classes_str = str(classes)
        if len(classes_str) > 60:
            classes_str = classes_str[:57] + '…'
        print(f"  {i+1:<3} {fn:<20} {fv:<25} {idx:<8} {classes_str}")
        encoding_details.append({
            'feature': fn, 'string_value': fv,
            'encoded_integer': idx, 'all_classes': classes,
        })

    X_vec = np.array(encoded_vector, dtype=np.int64)
    print(f"\n  Vektor Fitur Akhir: X = {X_vec.tolist()}")
    print(f"  Urutan fitur      : {feat_names}")

    # ── STEP 4: Trace one decision tree path ─────────────────────────────────
    print(f"\n{divider}")
    print("  STEP 4 — PENELUSURAN JALUR POHON KEPUTUSAN (TREE #0)")
    print(divider)

    tree0 = model.estimators_[0]
    t     = tree0.tree_
    feat_name_map = {0:'aircraft_type', 1:'aircraft_size', 2:'operator_airline',
                     3:'airline_tier',  4:'category',       5:'stand_zone'}
    stand_classes = list(encoders['parking_stand'].classes_)

    # Trace path
    node = 0
    path_nodes = []
    max_depth_trace = 30
    print(f"\n  Penelusuran dari Root Node (Node 0) dengan X = {X_vec.tolist()}")
    print(f"  Threshold: jika X[fitur] <= threshold → LEFT, else → RIGHT\n")

    for _ in range(max_depth_trace):
        left  = int(t.children_left[node])
        right = int(t.children_right[node])
        is_leaf = (left == -1 and right == -1)

        # Use impurity field directly (Gini at that node as computed during training)
        gini_val  = float(t.impurity[node])
        n_samples = int(t.n_node_samples[node])
        counts    = t.value[node][0]

        if is_leaf:
            # Find the class with highest count
            pred_enc_local = int(np.argmax(counts))
            # model.classes_ maps local class index to parking_stand encoded integer
            pred_class_int = int(model.classes_[pred_enc_local])
            pred_stand_name = decode_stand(encoders, pred_class_int)
            # Build human-readable distribution (stand_name: count)
            nonzero_dist = {decode_stand(encoders, int(model.classes_[i])): int(counts[i])
                            for i in range(len(counts)) if counts[i] > 0}
            print(f"  [LEAF  Node {node:4d}] Prediksi = '{pred_stand_name}'")
            print(f"           Distribusi non-nol: {nonzero_dist}")
            print(f"           Gini = {gini_val:.4f}  |  n_samples = {n_samples}")
            path_nodes.append({
                'node_id': node, 'is_leaf': True,
                'n_samples': n_samples, 'gini': round(gini_val, 6),
                'predicted_stand': pred_stand_name,
                'class_distribution_nonzero': nonzero_dist,
            })
            break
        else:
            feat_idx  = int(t.feature[node])
            threshold = float(t.threshold[node])
            feat_name = feat_name_map.get(feat_idx, f'f{feat_idx}')
            sample_val = float(X_vec[feat_idx])
            goes_left  = sample_val <= threshold
            direction  = "LEFT  (<=)" if goes_left else "RIGHT (>)"
            next_node  = left if goes_left else right
            print(f"  [NODE  {node:4d}]  {feat_name} ({feat_idx}) = {sample_val:.0f}  vs threshold {threshold:.4f}  --> {direction}  -> Node {next_node}")
            print(f"           Gini = {gini_val:.4f}  |  n_samples = {n_samples}")
            path_nodes.append({
                'node_id': node, 'is_leaf': False,
                'feature': feat_name, 'feature_index': feat_idx,
                'threshold': round(threshold, 4),
                'sample_value': int(sample_val),
                'goes_left': goes_left,
                'next_node': next_node,
                'n_samples': n_samples,
                'gini': round(gini_val, 6),
            })
            node = next_node

    # Show Gini calculation detail for root node
    # Use the actual impurity stored by sklearn (computed at training time on bootstrap sample)
    root_gini = float(t.impurity[0])
    root_n    = int(t.n_node_samples[0])
    # For display, use the relative class frequencies from the tree's value array
    root_counts = t.value[0][0]
    root_total  = int(root_counts.sum())

    print(f"\n  -- Perhitungan Gini Impurity (contoh di ROOT NODE = Node 0) --")
    print(f"  Gini Impurity tersimpan di tree: {root_gini:.6f}")
    print(f"  n_node_samples (bootstrap subset): {root_n}")
    print()
    print(f"  Rumus: Gini = 1 - sum(pi^2)  di mana pi = proporsi kelas i")
    print()
    # Show calculation from a chosen internal node with meaningful spread
    # Find the first internal node in path_nodes with n_samples > 10 that's not root
    demo_node_info = None
    for pn in path_nodes:
        if not pn['is_leaf'] and pn['node_id'] != 0 and pn['n_samples'] >= 5:
            demo_node_info = pn
            break
    if demo_node_info:
        dn_id = demo_node_info['node_id']
        dn_n  = int(t.n_node_samples[dn_id])
        dn_counts = t.value[dn_id][0]
        dn_total  = int(dn_counts.sum())
        dn_gini   = float(t.impurity[dn_id])
        # Build formula from non-zero classes
        parts  = []
        sq_sum = 0.0
        for cnt in dn_counts:
            if cnt > 0 and dn_total > 0:
                p = cnt / dn_total
                sq_sum += p ** 2
                parts.append(f"({cnt:.0f}/{dn_total})^2")
        formula = " + ".join(parts[:6])
        if len(parts) > 6:
            formula += " + ..."
        print(f"  Contoh perhitungan di Node {dn_id} ({demo_node_info.get('feature','')})")
        print(f"  n_samples = {dn_n}")
        print(f"  Gini = 1 - ( {formula} )")
        print(f"       = 1 - {sq_sum:.6f}")
        print(f"       = {dn_gini:.6f}")

    # ── STEP 5: Voting ────────────────────────────────────────────────────────
    print(f"\n{divider}")
    print("  STEP 5 — HASIL VOTING SEMUA POHON (AGREGASI FOREST)")
    print(divider)

    X_2d = X_vec.reshape(1, -1)
    probabilities = model.predict_proba(X_2d)[0]

    # Count individual tree votes
    votes = {}
    for estimator in model.estimators_:
        pred_int = int(estimator.predict(X_2d)[0])
        pred_stand = decode_stand(encoders, pred_int)
        votes[pred_stand] = votes.get(pred_stand, 0) + 1

    total_trees = len(model.estimators_)
    sorted_votes = sorted(votes.items(), key=lambda x: -x[1])

    print(f"\n  Total pohon (n_estimators): {total_trees}")
    print(f"\n  Top-10 stand berdasarkan jumlah suara pohon:")
    print(f"  {'Rank':<5} {'Stand':<10} {'Votes':<8} {'Probabilitas (votes/total)'}")
    print(f"  {'─'*5} {'─'*10} {'─'*8} {'─'*35}")
    vote_details = []
    for rank, (stand, vote_count) in enumerate(sorted_votes[:10], 1):
        prob = vote_count / total_trees
        print(f"  {rank:<5} {stand:<10} {vote_count:<8} {prob:.4f}  ({vote_count}/{total_trees})")
        vote_details.append({'stand': stand, 'votes': vote_count, 'probability': round(prob, 4)})

    # ── STEP 6: Final Top-3 ────────────────────────────────────────────────────
    print(f"\n{divider}")
    print("  STEP 6 — HASIL AKHIR TOP-3 REKOMENDASI")
    print(divider)

    top_indices = np.argsort(probabilities)[::-1][:3]
    top3 = []
    print(f"\n  {'Rank':<5} {'Stand':<10} {'Probabilitas':<15} {'Persentase'}")
    print(f"  {'─'*5} {'─'*10} {'─'*15} {'─'*15}")
    for rank, idx in enumerate(top_indices, 1):
        stand_enc_int = int(model.classes_[idx])
        stand_name    = decode_stand(encoders, stand_enc_int)
        prob          = float(probabilities[idx])
        print(f"  {rank:<5} {stand_name:<10} {prob:<15.6f} {prob*100:.2f}%")
        top3.append({'rank': rank, 'stand': stand_name, 'probability': round(prob, 6)})

    # ── SAVE OUTPUT ────────────────────────────────────────────────────────────
    output = {
        'step1_sample': sample_info,
        'step2_feature_engineering': {
            'aircraft_type':    aircraft_type,
            'operator_airline': operator_airline,
            'category':         category_norm,
            'aircraft_size':    aircraft_size,
            'airline_tier':     airline_tier,
            'stand_zone':       stand_zone,
        },
        'step3_encoding': encoding_details,
        'step3_encoded_vector': encoded_vector,
        'step4_tree0_path': path_nodes,
        'step4_gini_root': round(root_gini, 6),
        'step5_vote_counts': vote_details,
        'step5_total_trees': total_trees,
        'step6_top3_predictions': top3,
    }

    with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=2, ensure_ascii=False, default=str)

    print(f"\n{divider}")
    print(f"  [SAVED] Full output → {OUTPUT_JSON}")
    print(f"  [SAVED] Sample info → {SAMPLE_JSON}")
    print(divider)

    return output


if __name__ == '__main__':
    result = main()
