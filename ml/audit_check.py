import json
from pathlib import Path

ROOT = Path('c:/xampp/htdocs/AMC')

files = [
    'CLI/revision/revisibab4.md',
    'ml/manual_calculation.py',
    'ml/data_variation_experiment.py',
    'ml/manual_calculation_output.json',
    'ml/manual_calc_sample.json',
    'ml/data_variation_results.json',
]

print('=== FILE VERIFICATION ===')
all_ok = True
for f in files:
    p = ROOT / f
    exists = p.exists()
    size = p.stat().st_size if exists else 0
    status = 'OK' if exists else 'MISSING'
    print(f'  [{status}] {f}  ({size:,} bytes)')
    if not exists:
        all_ok = False

print()
print('=== manual_calculation_output.json ===')
with open(ROOT / 'ml/manual_calculation_output.json') as f:
    data = json.load(f)

print(f'  Sample used: {data["step1_sample"]}')
print(f'  Encoded vector: {data["step3_encoded_vector"]}')
print(f'  Tree path nodes: {len(data["step4_tree0_path"])} nodes')
leaf = [n for n in data["step4_tree0_path"] if n["is_leaf"]][0]
print(f'  Leaf prediction: {leaf["predicted_stand"]}  (Gini={leaf["gini"]})')
print(f'  Votes: {data["step5_vote_counts"]}')
print(f'  Top-3: {data["step6_top3_predictions"]}')

print()
print('=== data_variation_results.json ===')
with open(ROOT / 'ml/data_variation_results.json') as f:
    var = json.load(f)

for r in var['results']:
    label = r['training_size_label']
    top3 = r['top3_accuracy'] * 100
    f1 = r['macro_f1'] * 100
    print(f'  {label:>16}: Top-3={top3:.2f}%  MacroF1={f1:.2f}%')

print()
if all_ok:
    print('ALL FILES OK — Task complete.')
else:
    print('WARNING: Some files missing.')
