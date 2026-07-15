#!/usr/bin/env python3
"""
ml/build_v4_md.py
Build CLI/revision/revisibab4_v4_fixed.md from samples_v4_output.json
"""
import json, re
from pathlib import Path

N_TREES = 200
ROOT = Path(__file__).resolve().parents[1]

with open(ROOT / 'ml/samples_v4_output.json', encoding='utf-8') as f:
    raw = json.load(f)

# Fix: is_leaf was serialized as string 'True'/'False' — convert back
def fix_path(path):
    for node in path:
        if isinstance(node.get('is_leaf'), str):
            node['is_leaf'] = node['is_leaf'].lower() == 'true'
    return path

samples = raw
for s in samples:
    s['tree0_path'] = fix_path(s['tree0_path'])

v2_path = ROOT / 'CLI/revision/revisibab4_v2.md'
v2_text = v2_path.read_text(encoding='utf-8')
m = re.search(r'(## BAGIAN E — ANALISIS VARIASI UKURAN DATA.*)', v2_text, re.DOTALL)
section_425 = m.group(1).strip() if m else '<!-- 4.2.5 not found -->'

# ── Helpers ───────────────────────────────────────────────────────────────────
ENC_KEYS   = ['aircraft_type','aircraft_size','operator_airline','airline_tier','category','stand_zone']
ENC_LABELS = ['Jenis Pesawat','Ukuran Pesawat','Maskapai','Tier Maskapai','Kategori','Zona Stand']
TIER_REASON = {'HIGH_FREQUENCY':'maskapai frekuensi tinggi',
               'MEDIUM_FREQUENCY':'maskapai frekuensi menengah',
               'LOW_FREQUENCY':'maskapai frekuensi rendah'}
SIZE_REASON = {'SMALL_A0_COMPATIBLE':'termasuk pesawat kecil A0-compatible',
               'STANDARD':'bukan jenis A0-compatible'}
ZONE_REASON = {'RIGHT_COMMERCIAL':'COMMERCIAL → zona komersial (kanan)',
               'LEFT_CARGO':'CARGO → zona kargo (kiri)',
               'MIDDLE_CHARTER':'CHARTER → zona charter (tengah)'}

def cat_display(raw):
    return {'Komersial':'Komersial → COMMERCIAL','komersial':'Komersial → COMMERCIAL',
            'cargo':'cargo → CARGO','Charter':'Charter → CHARTER',
            'CHARTER':'CHARTER','COMMERCIAL':'COMMERCIAL','CARGO':'CARGO'}.get(raw, raw)

def build_gini_step(dist, n, top_n=5):
    """Build the p1² + p2² + ... string from dist list."""
    terms = [f'({d["count"]}/{n})²' for d in dist[:top_n]]
    if len(dist) > top_n:
        terms.append(f'... ({len(dist)-top_n} kelas lainnya)')
    return ' + '.join(terms)

def build_sample_md(s):
    at   = s['aircraft_type']
    ao   = s['operator_airline']
    cat  = s['category_norm']
    act  = s['actual_stand']
    vec  = s['encoded_vector']
    path = s['tree0_path']
    top3 = s['top3']
    votes = s['votes']
    in_top3 = s['in_top3']
    verdict = 'BENAR ✓' if in_top3 else 'SALAH ✗'

    root_dist = s['root_dist_full']
    root_n    = s['root_n_samples']   # from bootstrap (exact)
    root_n_f  = s['root_n_full']      # from full data
    root_gini = s['root_gini']        # from tree_.impurity (exact)
    leaf_dist = s['leaf_dist_full']
    leaf_nid  = s['leaf_node_id']
    leaf_n    = s['leaf_n_samples']   # bootstrap
    leaf_gini = s['leaf_gini']        # exact
    leaf_pred = s['leaf_predicted_stand']
    leaf_path = path[-1]

    feat_vals = [at, s['aircraft_size'], ao.title(),
                 s['airline_tier'], cat, s['stand_zone']]

    lines = [f'### Sampel {s["no"]} — {at} / {ao.title()} / {cat}', '']

    # ── Komponen A+B (merged) ─────────────────────────────────────────────────
    lines += [
        '**Input & Encoding**',
        '',
        f'Input: **{at}** | **{ao.title()}** | **{cat}** → Stand Aktual: **{act}**',
        '',
        '| Urutan | Fitur | Nilai String | Kode |',
        '|--------|-------|-------------|------|',
    ]
    for i,(lbl,val,code) in enumerate(zip(ENC_LABELS, feat_vals, vec)):
        lines.append(f'| X[{i}] | {lbl} | {val} | **{code}** |')
    lines += ['', f'**Vektor X = {vec}**', '']

    # Rekayasa fitur note
    lines += [
        '*Rekayasa fitur:*',
        f'- `aircraft_size = {s["aircraft_size"]}` ({SIZE_REASON[s["aircraft_size"]]})',
        f'- `airline_tier = {s["airline_tier"]}` ({ao.title()} — {TIER_REASON[s["airline_tier"]]})',
        f'- `stand_zone = {s["stand_zone"]}` (kategori {ZONE_REASON[s["stand_zone"]]})',
        '',
    ]

    # ── Komponen C: Root Node Gini ────────────────────────────────────────────
    lines += ['**Komponen C — Gini Impurity Root Node (Tree #0, Node 0)**', '']

    # Build step string from full-data dist (use root_n_f for ratios)
    gini_step = build_gini_step(root_dist, root_n_f, top_n=5)
    sq_sum_approx = sum(d['p']**2 for d in root_dist)

    lines += ['```']
    lines += [f'Root Node (Node 0):']
    lines += [f'  n_samples = {root_n:,}  (sampel bootstrap Tree #0)']
    lines += [f'  Fitur split: {path[0].get("feature","?")} [X[{path[0].get("feature_index","?")}]] <= {path[0].get("threshold","?")}']
    lines += [f'  Nilai X = {path[0].get("x_value","?")}  →  arah: {path[0].get("direction","?")}']
    lines += ['']
    lines += [f'  Distribusi kelas di root (dari {root_n_f:,} baris dataset, 5 terbesar):']
    for d in root_dist[:5]:
        lines.append(f'    Stand {d["stand"]:>3}: {d["count"]:>5} sampel  →  p = {d["count"]}/{root_n_f} = {d["p"]:.6f}')
    if len(root_dist) > 5:
        rest = len(root_dist) - 5
        lines.append(f'    ... ({rest} kelas lainnya dengan distribusi lebih kecil)')
    lines += ['']
    lines += [f'  Gini(root) = 1 - ({gini_step})']
    lines += [f'             = 1 - {sq_sum_approx:.6f}']
    lines += [f'             ≈ {1-sq_sum_approx:.4f}']
    lines += [f'']
    lines += [f'  Nilai Gini dari model (bootstrap): {root_gini:.4f}']
    lines += ['```', '']

    # ── Komponen D: Tree Path ─────────────────────────────────────────────────
    lines += [f'**Komponen D — Jalur Pohon Keputusan Tree #0 → Prediksi: {leaf_pred}**', '']
    lines += ['| Node | Fitur | Thresh | X | Arah | Gini | n |',
              '|------|-------|--------|---|------|------|---|']
    for node in path:
        nid = node['node_id']
        g   = f'{node["gini"]:.4f}'
        n   = f'{node["n_samples"]:,}'
        if node['is_leaf']:
            lines.append(f'| **{nid}** (LEAF) | — | — | — | — | {g} | {n} |')
        else:
            lines.append(
                f'| **{nid}** | {node["feature"]} [{node["feature_index"]}] | '
                f'{node["threshold"]:.4f} | {node["x_value"]} | '
                f'{node["direction"]} | {g} | {n} |'
            )
    lines += ['']

    # Leaf Gini
    leaf_step = build_gini_step(leaf_dist, leaf_n if leaf_n > 0 else 1, top_n=4)
    leaf_sq   = sum(d['p']**2 for d in leaf_dist)
    lines += ['```',
              f'Leaf Node (Node {leaf_nid}):',
              f'  n_samples = {leaf_n}  (sampel bootstrap Tree #0)']
    if leaf_dist:
        lines.append(f'  Kelas di leaf:')
        for d in leaf_dist:
            lines.append(f'    Stand {d["stand"]:>3}: {d["count"]:>3} sampel  →  p = {d["p"]:.6f}')
        lines += [f'',
                  f'  Gini(leaf) = 1 - ({leaf_step})',
                  f'             = 1 - {leaf_sq:.6f}',
                  f'             ≈ {1-leaf_sq:.4f}',
                  f'  Nilai Gini dari model: {leaf_gini:.4f}']
    lines += ['```', '']

    # ── Komponen E: Voting ────────────────────────────────────────────────────
    total_v = sum(v['votes'] for v in votes)
    lines += ['**Komponen E — Voting 200 Pohon**', '',
              '| Stand | Suara | Probabilitas Voting |',
              '|-------|-------|---------------------|']
    for v in votes:
        lines.append(f'| {v["stand"]} | {v["votes"]} | {v["votes"]}/{total_v} = {v["vote_prob"]:.4f} ({v["votes"]/total_v*100:.2f}%) |')
    lines += [f'| **Total** | **{total_v}** | **1.0000 (100%)** |', '']

    # ── Komponen F: predict_proba ─────────────────────────────────────────────
    lines += ['**Komponen F — predict_proba Top-3 (rata-rata probabilitas daun)**', '',
              '| Rank | Stand | Probabilitas |',
              '|------|-------|-------------|']
    for t in top3:
        marker = ' ← aktual' if t['stand'] == act else ''
        lines.append(f'| **{t["rank"]}** | **{t["stand"]}** | {t["prob"]:.4f} ({t["prob"]*100:.2f}%){marker} |')
    lines += ['',
              f'**Top-3:** {top3[0]["stand"]}, {top3[1]["stand"]}, {top3[2]["stand"]}  |  '
              f'**Stand Aktual:** {act}  |  **{verdict}**',
              '', '---', '']
    return '\n'.join(lines)


# ── Build document ────────────────────────────────────────────────────────────
doc = [
    '# REVISI BAB 4.2.4 — v4 (FIXED)',
    '## Perhitungan Manual Prediksi — Angka Aktual dari Model',
    '',
    '**Status:** PENDING VERIFICATION',
    '**Tanggal:** 2026-06-10',
    '**Versi:** 4 — Gini & n_samples dari tree_.impurity (exact), class dist dari full data',
    '',
    '> **Catatan teknis:** Nilai Gini dan n_samples diambil langsung dari `tree_.impurity`',
    '> dan `tree_.n_node_samples` (menggunakan data bootstrap Tree #0).',
    '> Distribusi kelas per node diestimasi dari full dataset (5.190 baris) via `decision_path`.',
    '> Perbedaan kecil antara Gini manual (~) dan Gini model mungkin terjadi karena bootstrap sampling.',
    '',
    '---',
    '',
    '## BAGIAN A — TEORI PEMBUKA 4.2.4',
    '### (Siap Copy ke Dokumen Word — Maksimal 150 kata)',
    '',
    '---',
    '',
    '### 4.2.4 Perhitungan Manual Prediksi Model Random Forest',
    '',
    'Bagian ini menyajikan pembuktian matematis prediksi model *Random Forest* yang',
    'digunakan dalam sistem AMC Bandar Udara Halim Perdanakusuma. Proses prediksi',
    'berlangsung dalam tiga tahap berurutan: (1) rekayasa fitur (*feature engineering*)',
    'yang mengubah tiga input menjadi enam fitur representatif, (2) *label encoding*',
    'yang mengonversi nilai kategorikal menjadi vektor bilangan bulat, dan',
    '(3) inferensi ensemble 200 pohon keputusan yang menghasilkan distribusi probabilitas',
    'untuk setiap parking stand.',
    '',
    'Pemisahan (*split*) di setiap node pohon ditentukan menggunakan **Gini Impurity**:',
    '',
    '```',
    'Gini(t) = 1 - Σ [p(i|t)]²,  i = 1, 2, ..., C',
    '```',
    '',
    'di mana C = 17 (jumlah parking stand) dan p(i|t) = proporsi kelas i di node t.',
    'Probabilitas voting setiap stand dihitung sebagai:',
    '',
    '```',
    'P_vote(stand_j) = jumlah pohon yang memprediksi stand_j / 200',
    '```',
    '',
    'Berikut adalah 10 sampel pembuktian dengan data nyata dari dataset historis AMC,',
    'masing-masing disertai vektor input, kalkulasi Gini aktual, jalur pohon,',
    'voting 200 pohon, dan hasil predict_proba.',
    '',
    '---',
    '',
    '## BAGIAN B — 10 SAMPEL PERHITUNGAN MANUAL',
    '### (Siap Copy ke Dokumen Word)',
    '',
    '---',
    '',
]

for s in samples:
    doc.append(build_sample_md(s))

# Summary
correct = sum(1 for s in samples if s['in_top3'])
doc += [
    '## RINGKASAN 10 SAMPEL', '',
    '| No | Pesawat | Maskapai | Kategori | Stand Aktual | Top-3 Prediksi | Hasil |',
    '|----|---------|----------|----------|-------------|----------------|-------|',
]
for s in samples:
    t3 = ', '.join(f'**{t["stand"]}**' if t['stand']==s['actual_stand'] else t['stand'] for t in s['top3'])
    doc.append(f'| {s["no"]} | {s["aircraft_type"]} | {s["operator_airline"].title()} | '
               f'{s["category_norm"]} | {s["actual_stand"]} | {t3} | {"BENAR" if s["in_top3"] else "SALAH"} |')
doc += [
    '', f'**Akurasi Top-3 pada 10 sampel: {correct}/10 = {correct*10}%**', '',
    f'> *Akurasi resmi model pada 1.038 data uji: **80.15%** (Top-3), dari sub-bab 4.2.3.*',
    '', '---', '',
    '## BAGIAN C — 4.2.5 ANALISIS VARIASI UKURAN DATA',
    '### (Salin persis dari revisibab4_v2.md)', '', '---', '',
    section_425,
]

out_text = '\n'.join(doc)
out_path = ROOT / 'CLI/revision/revisibab4_v4_fixed.md'
out_path.write_text(out_text, encoding='utf-8')
print(f'Written: {out_path}')
print(f'Lines: {len(out_text.splitlines())}')
print(f'Bytes: {len(out_text.encode("utf-8"))}')
