#!/usr/bin/env python3
"""Final audit for revisibab4_v2.md"""
from pathlib import Path
import json

ROOT = Path('c:/xampp/htdocs/AMC')
md_path = ROOT / 'CLI/revision/revisibab4_v2.md'
md = md_path.read_text(encoding='utf-8')

checks = {
    # BAGIAN A - pipeline verification
    'PIPELINE VERIFICATION': 'Section A pipeline report',
    'MISMATCH': 'Pipeline mismatch documented',
    'database MySQL': 'Database source explanation',
    'ml_process.md': 'ml_process.md referenced',
    # BAGIAN B - theory
    'Landasan Teori': 'Theory section header',
    'Random Forest': 'RF theory present',
    'Gini(t) = 1 - SUM': 'Gini formula present',
    'Feature Engineering': 'Feature engineering theory',
    'Label Encoding': 'Label encoding theory',
    'predict_proba': 'predict_proba explained',
    'Top-3': 'Top-3 rationale present',
    'Bahasa Indonesia': True,  # skip content check, just check file language
    'decision support system': 'Human-in-the-loop rationale',
    # BAGIAN C - 10 samples
    'Sampel 1': 'Sample 1 present',
    'Sampel 2': 'Sample 2 present',
    'Sampel 10': 'Sample 10 present',
    'ATR 72': 'ATR 72 sample',
    'B 738': 'B 738 sample',
    'A 320': 'A 320 sample',
    'CITILINK': 'CITILINK sample',
    'FLY JAYA': 'FLY JAYA sample',
    'G IV': 'G IV charter sample',
    'EMB 135': 'EMB 135 charter sample',
    'BBJ': 'BBJ charter sample',
    'TRI MG': 'TRI MG cargo sample',
    'B. B. N.': 'B.B.N. cargo sample',
    'BENAR': 'BENAR/SALAH verdict present',
    'SALAH': 'SALAH verdict present',
    '7/10': 'Accuracy summary (7/10)',
    # BAGIAN D - tree trace
    'Pohon #0': 'Tree trace header',
    '0.9412': 'Root node Gini',
    '0.6732': 'Leaf node Gini',
    '188': 'Vote count 188',
    '49.69%': 'Top-1 probability',
    '29.02%': 'Top-2 probability',
    '14.70%': 'Top-3 probability',
    # BAGIAN E - variation table
    '4.2.5': 'Section 4.2.5',
    '52.60%': '1000-row Top-3',
    '58.29%': '2000-row Top-3',
    '58.00%': '3000-row Top-3',
    '58.86%': 'Full-row Top-3',
    'SMOTE': 'SMOTE mentioned',
    '1.870': 'SMOTE expanded count',
    'diminishing returns': 'Diminishing returns analysis',
    # BAGIAN F - discrepancy
    'DATASET_AMC_fields_used.csv': 'CSV file referenced',
    '80.15%': 'Official accuracy referenced',
    'tren relatif': 'Relative trend explanation',
    # Chart data
    '26.20': 'Chart data Top-1 1000',
    # Files
    'pipeline_verify_and_variation.py': 'Script reference',
    'samples_10_output.json': 'JSON file reference',
    'data_variation_results_v2.json': 'Results v2 reference',
}

print('====================================================')
print('  FINAL AUDIT: revisibab4_v2.md')
print('====================================================')
print(f'  File size: {md_path.stat().st_size:,} bytes')
print(f'  Total lines: {len(md.splitlines())}')
print()

all_ok = True
for key, label in checks.items():
    if isinstance(label, bool):
        continue  # skip
    found = key in md
    if not found:
        all_ok = False
    print(f'  [{"OK" if found else "MISSING"}] {label}: "{key[:50]}"')

print()

# Also verify JSON outputs exist
json_files = [
    'ml/samples_10_output.json',
    'ml/data_variation_results_v2.json',
    'ml/manual_calculation_output.json',
]
for jf in json_files:
    p = ROOT / jf
    exists = p.exists()
    if not exists: all_ok = False
    print(f'  [{"OK" if exists else "MISSING"}] JSON: {jf}')

print()
print('RESULT:', 'ALL CHECKS PASSED' if all_ok else 'SOME CHECKS FAILED')
