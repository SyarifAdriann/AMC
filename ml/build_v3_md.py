#!/usr/bin/env python3
"""
ml/build_v3_md.py
=================
Reads samples_v3_output.json and revisibab4_v2.md,
outputs CLI/revision/revisibab4_v3_4-2-4only.md
"""
import json, re
from pathlib import Path

N_TREES = 200

ROOT = Path(__file__).resolve().parents[1]

with open(ROOT / 'ml/samples_v3_output.json', encoding='utf-8') as f:
    samples = json.load(f)

# read section E-F from v2 (lines from BAGIAN E onward)
v2_path = ROOT / 'CLI/revision/revisibab4_v2.md'
v2_text = v2_path.read_text(encoding='utf-8')

# Extract from "## BAGIAN E" to end
m = re.search(r'(## BAGIAN E — ANALISIS.*)', v2_text, re.DOTALL)
section_e_onward = m.group(1).rstrip() if m else '<!-- BAGIAN E not found -->'

# ── Helpers ──────────────────────────────────────────────────────────────────

FEAT_LABELS = {
    'aircraft_type':    ('aircraft_type',    'Jenis Pesawat'),
    'aircraft_size':    ('aircraft_size',    'Ukuran Pesawat'),
    'operator_airline': ('operator_airline', 'Maskapai'),
    'airline_tier':     ('airline_tier',     'Tier Maskapai'),
    'category':         ('category',         'Kategori'),
    'stand_zone':       ('stand_zone',       'Zona Stand'),
}
ENC_KEYS = [
    'aircraft_type', 'aircraft_size', 'operator_airline',
    'airline_tier',  'category',      'stand_zone',
]
ENC_LABELS = ['Jenis Pesawat', 'Ukuran Pesawat', 'Maskapai', 'Tier Maskapai', 'Kategori', 'Zona Stand']

TIER_REASONS = {
    'HIGH_FREQUENCY':   'maskapai frekuensi tinggi',
    'MEDIUM_FREQUENCY': 'maskapai frekuensi menengah',
    'LOW_FREQUENCY':    'maskapai frekuensi rendah',
}
SIZE_REASONS = {
    'SMALL_A0_COMPATIBLE': 'termasuk pesawat kecil yang kompatibel apron A0',
    'STANDARD':            'bukan jenis A0-compatible',
}
ZONE_REASONS = {
    'RIGHT_COMMERCIAL': 'Kategori COMMERCIAL → zona komersial (kanan)',
    'LEFT_CARGO':       'Kategori CARGO → zona kargo (kiri)',
    'MIDDLE_CHARTER':   'Kategori CHARTER → zona charter (tengah)',
}

def cat_raw_display(s):
    return {'Komersial': 'Komersial → COMMERCIAL',
            'komersial': 'Komersial → COMMERCIAL',
            'cargo':     'cargo → CARGO',
            'Charter':   'Charter → CHARTER',
            'CHARTER':   'CHARTER',
            'COMMERCIAL':'COMMERCIAL',
            'CARGO':     'CARGO'}.get(s, s)

def fmt_pct(v): return f'{v*100:.2f}%'

def gini_stepstr(class_dist, total):
    """Build the step string: p1² + p2² + ... """
    terms = [f'({d["count"]}/{total})²' for d in class_dist[:5]]
    if len(class_dist) > 5:
        terms.append(f'... (+ {len(class_dist)-5} kelas lainnya)')
    return ' + '.join(terms)

def build_sample_md(s):
    at   = s['aircraft_type']
    ao   = s['operator_airline']
    cat  = s['category_norm']
    cat_raw = s['category_raw']
    act  = s['actual_stand']
    vec  = s['encoded_vector']
    path = s['tree0_path']
    root = path[0]
    leaf = path[-1]
    top3 = s['top3']
    votes = s['votes']
    in_top3 = s['in_top3']
    verdict = 'BENAR ✓' if in_top3 else 'SALAH ✗'

    # Heading
    lines = [
        f'### Sampel {s["no"]} — {at} / {ao.title()} / {cat}',
        '',
    ]

    # ── KOMPONEN A: Input & Feature Engineering ──────────────────────────────
    lines += [
        '**Komponen A — Input Mentah & Rekayasa Fitur**',
        '',
        '| Kolom | Nilai |',
        '|-------|-------|',
        f'| Jenis Pesawat (aircraft_type) | {at} |',
        f'| Maskapai (operator_airline) | {ao.title()} |',
        f'| Kategori (category) | {cat_raw_display(cat_raw)} |',
        f'| Stand Aktual (parking_stand) | **{act}** |',
        '',
        '| Fitur Turunan | Derivasi | Nilai |',
        '|--------------|----------|-------|',
        f'| aircraft_size | {at} — {SIZE_REASONS[s["aircraft_size"]]} | {s["aircraft_size"]} |',
        f'| airline_tier | {ao.title()} — {TIER_REASONS[s["airline_tier"]]} | {s["airline_tier"]} |',
        f'| stand_zone | {ZONE_REASONS[s["stand_zone"]]} | {s["stand_zone"]} |',
        '',
    ]

    # ── KOMPONEN B: Encoding ─────────────────────────────────────────────────
    lines += ['**Komponen B — Label Encoding**', '']
    lines += [
        '| Urutan | Fitur | Nilai String | Kode Integer |',
        '|--------|-------|-------------|-------------|',
    ]
    feat_vals = [at, s['aircraft_size'], ao.title(),
                 s['airline_tier'], cat, s['stand_zone']]
    for i, (lbl, val, code) in enumerate(zip(ENC_LABELS, feat_vals, vec)):
        lines.append(f'| X[{i}] | {ENC_KEYS[i]} | {val} | **{code}** |')
    lines += [
        '',
        f'**Vektor X = {vec}**',
        '',
    ]

    # ── KOMPONEN C: Root Node Gini ───────────────────────────────────────────
    root_total = sum(d['count'] for d in root['class_dist'])
    root_sq    = s.get('root_sum_sq', 0)
    root_gini  = s.get('root_gini', root['gini'])
    root_feat  = root.get('feature', '—')
    root_thr   = root.get('threshold', 0)
    root_xval  = root.get('x_value', 0)
    root_dir   = root.get('direction', '—')

    lines += [
        '**Komponen C — Gini Impurity di Root Node (Tree #0)**',
        '',
        f'```',
        f'Root Node (Node 0):',
        f'  n_samples = {root_total:,}',
        f'  Fitur split: {root_feat} [X[{root.get("feature_index","?")}]] <= {root_thr}',
        f'  Nilai X pada fitur ini: {root_xval}  →  arah: {root_dir}',
        '',
        f'  Distribusi kelas di root (5 terbesar dari {len(root["class_dist"])} kelas):',
    ]
    for d in root['class_dist'][:5]:
        lines.append(f'    Stand {d["stand"]}: {d["count"]:>6} sampel  →  p = {d["count"]}/{root_total} = {d["p"]:.6f}')
    if len(root['class_dist']) > 5:
        rest = len(root['class_dist']) - 5
        lines.append(f'    ... ({rest} kelas lainnya)')
    lines += [
        '',
        f'  Gini(root) = 1 - Σ p(i|root)²',
        f'             = 1 - ({gini_stepstr(root["class_dist"], root_total)})',
        f'             = 1 - {root_sq:.6f}',
        f'             = {root_gini:.4f}',
        '```',
        '',
    ]

    # ── KOMPONEN D: Tree Path ────────────────────────────────────────────────
    lines += [
        '**Komponen D — Penelusuran Pohon Keputusan (Tree #0)**',
        '',
        '| Node | Fitur (Indeks) | Threshold | Nilai X | Kondisi | Arah | Gini | n |',
        '|------|---------------|-----------|---------|---------|------|------|---|',
    ]
    for node in path:
        nid = node['node_id']
        g   = f'{node["gini"]:.4f}'
        n   = f'{node["n_samples"]:,}'
        if node['is_leaf']:
            lines.append(f'| **{nid}** (LEAF) | — | — | — | — | — | {g} | {n} |')
        else:
            lines.append(
                f'| **{nid}** | {node["feature"]} [{node["feature_index"]}] | '
                f'{node["threshold"]:.4f} | {node["x_value"]} | '
                f'{node["condition"]} | {node["direction"]} | {g} | {n} |'
            )
    lines += ['']
    lines.append(f'**Prediksi Tree #0: {leaf["predicted_stand"]}**')
    lines += ['']

    # Leaf Gini
    leaf_total = sum(d['count'] for d in leaf['class_dist'])
    leaf_sq    = s.get('leaf_sum_sq', 0)
    leaf_gini  = s.get('leaf_gini', leaf['gini'])
    lines += [
        '```',
        f'Leaf Node (Node {leaf["node_id"]}):',
        f'  n_samples = {leaf_total}',
        f'  Distribusi kelas di leaf:',
    ]
    for d in leaf['class_dist']:
        p_val = d['count']/leaf_total if leaf_total > 0 else 0
        lines.append(f'    Stand {d["stand"]}: {d["count"]} sampel  →  p = {d["count"]}/{leaf_total} = {p_val:.6f}')
    lines += [
        '',
        f'  Gini(leaf) = 1 - ({gini_stepstr(leaf["class_dist"], leaf_total)})',
        f'             = 1 - {leaf_sq:.6f}',
        f'             = {leaf_gini:.4f}',
        '```',
        '',
    ]

    # ── KOMPONEN E: Voting ───────────────────────────────────────────────────
    total_votes = sum(v['votes'] for v in votes)
    lines += [
        '**Komponen E — Voting 200 Pohon**',
        '',
        f'Hasil voting {N_TREES} pohon untuk sampel ini:',
        '',
        '| Stand | Jumlah Suara | Perhitungan Probabilitas Voting |',
        '|-------|-------------|--------------------------------|',
    ]
    for v in votes:
        prob_pct = v['votes']/total_votes*100
        lines.append(f'| {v["stand"]} | {v["votes"]} | {v["votes"]} / {total_votes} = {v["vote_prob"]:.4f} ({prob_pct:.2f}%) |')
    lines += [
        f'| **Total** | **{total_votes}** | **{total_votes} / {total_votes} = 1.0000 (100%)** |',
        '',
    ]

    # ── KOMPONEN F: predict_proba ─────────────────────────────────────────────
    lines += [
        '**Komponen F — Hasil predict_proba dan Top-3 Final**',
        '',
        '*Catatan: predict_proba menggunakan rata-rata probabilitas daun seluruh pohon,*',
        '*bukan sekadar mayoritas suara — sehingga berbeda dari voting count di Komponen E.*',
        '',
        '| Rank | Stand | Probabilitas | Perhitungan |',
        '|------|-------|-------------|-------------|',
    ]
    for t in top3:
        act_marker = f' ← stand aktual' if t['stand'] == act else ''
        lines.append(
            f'| **{t["rank"]}** | **{t["stand"]}** | {t["prob"]:.4f} | '
            f'{t["prob"]:.4f} × 100 = {t["prob"]*100:.2f}%{act_marker} |'
        )
    lines += [
        '',
        f'**Top-3 Rekomendasi Sistem:** {top3[0]["stand"]}, {top3[1]["stand"]}, {top3[2]["stand"]}',
        f'**Stand Aktual di Dataset:** {act}',
        f'**Verifikasi:** Stand aktual {act} {"ADA" if in_top3 else "TIDAK ADA"} di Top-3 → '
        f'PREDIKSI **{verdict}**',
        '',
        '---',
        '',
    ]

    return '\n'.join(lines)


# ── Read 4.2.5 section from v2 ───────────────────────────────────────────────
def get_section_425():
    text = v2_path.read_text(encoding='utf-8')
    # Find the 4.2.5 section (BAGIAN E)
    m = re.search(r'(## BAGIAN E — ANALISIS VARIASI UKURAN DATA.*?)(\Z)', text, re.DOTALL)
    if m:
        raw = m.group(1).strip()
        # Remove trailing empty lines / orphan content
        return raw
    return '<!-- 4.2.5 section not found in v2 -->'


# ── Build full document ───────────────────────────────────────────────────────
doc_lines = [
    '# REVISI BAB 4.2.4 — v3',
    '## Perhitungan Manual Prediksi Model Random Forest',
    '',
    '**Status:** PENDING VERIFICATION',
    f'**Tanggal:** 2026-06-10',
    '**Versi:** 3 — teks dipersingkat, matematika aktual dari model',
    '',
    '---',
    '',
    '## BAGIAN A — TEORI PEMBUKA 4.2.4',
    '### (Maksimal 150 kata — Siap Copy ke Dokumen Word)',
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
    '',
    'Probabilitas voting setiap stand dihitung sebagai:',
    '',
    '```',
    'P_vote(stand_j) = jumlah pohon yang memprediksi stand_j / 200',
    '```',
    '',
    'Berikut adalah 10 sampel pembuktian dengan data nyata dari dataset historis AMC,',
    'masing-masing disertai vektor input, kalkulasi Gini, jalur pohon, voting,',
    'dan hasil predict_proba.',
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
    doc_lines.append(build_sample_md(s))

# Summary table
doc_lines += [
    '## RINGKASAN 10 SAMPEL',
    '',
    '| No | Pesawat | Maskapai | Kategori | Stand Aktual | Top-3 Prediksi | Hasil |',
    '|----|---------|----------|----------|-------------|----------------|-------|',
]
for s in samples:
    top3_str = ', '.join(
        f'**{t["stand"]}**' if t['stand'] == s['actual_stand'] else t['stand']
        for t in s['top3']
    )
    verdict = 'BENAR' if s['in_top3'] else 'SALAH'
    doc_lines.append(
        f'| {s["no"]} | {s["aircraft_type"]} | {s["operator_airline"].title()} | '
        f'{s["category_norm"]} | {s["actual_stand"]} | {top3_str} | {verdict} |'
    )

correct = sum(1 for s in samples if s['in_top3'])
doc_lines += [
    '',
    f'**Akurasi Top-3 pada 10 sampel ini: {correct}/10 = {correct*10}%**',
    '',
    f'> *Catatan: Akurasi 10 sampel ini ({correct*10}%) adalah ilustrasi terbatas dan tidak merepresentasikan',
    f'> akurasi resmi model. Akurasi resmi model yang dievaluasi pada 1.038 data uji adalah',
    f'> **80.15%** (Top-3 Accuracy), sebagaimana tercantum di sub-bab 4.2.3.*',
    '',
    '---',
    '',
    '## BAGIAN C — 4.2.5 ANALISIS VARIASI UKURAN DATA',
    '### (Salin persis dari revisibab4_v2.md — tidak ada perubahan)',
    '',
    '---',
    '',
]
doc_lines.append(get_section_425())

out_text = '\n'.join(doc_lines)

out_path = ROOT / 'CLI/revision/revisibab4_v3_4-2-4only.md'
out_path.write_text(out_text, encoding='utf-8')
print(f'Written: {out_path}')
print(f'Lines: {len(out_text.splitlines())}')
print(f'Bytes: {len(out_text.encode("utf-8"))}')
