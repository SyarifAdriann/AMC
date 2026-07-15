from pathlib import Path
import json

ROOT = Path('c:/xampp/htdocs/AMC')

print('======================================')
print(' FULL REQUIREMENT CHECKLIST AUDIT')
print('======================================')

# STEP 0
print()
print('[STEP 0] File paths found:')
checks = {
    'ml/parking_stand_model_rf_redo.pkl': 'Trained model',
    'ml/encoders_redo.pkl': 'Encoder file',
    'DATASET_AMC_fields_used.csv': 'Dataset CSV',
    'ml/archive/old_decision_tree_artifacts/train_model_old_dt.py': 'Training script (DT)',
    'ml/manual_calculation.py': 'Script 4.2.4',
    'ml/data_variation_experiment.py': 'Script 4.2.5',
    'ml/manual_calculation_output.json': 'Output JSON 4.2.4',
    'ml/manual_calc_sample.json': 'Sample JSON',
    'ml/data_variation_results.json': 'Results JSON 4.2.5',
    'CLI/revision/revisibab4.md': 'Main output file',
}
all_ok = True
for path, label in checks.items():
    p = ROOT / path
    exists = p.exists()
    if not exists:
        all_ok = False
    size = '{:,} bytes'.format(p.stat().st_size) if exists else 'N/A'
    status = 'OK' if exists else 'MISSING'
    print('  [{}] {}: {} ({})'.format(status, label, path, size))

# STEP 1
print()
print('[STEP 1] Manual calculation output:')
with open(ROOT / 'ml/manual_calculation_output.json') as f:
    data = json.load(f)

s = data['step1_sample']
print('  1a. Sample: {} / {} / {} -> stand {}'.format(
    s['aircraft_type'], s['operator_airline'], s['category_normalized'], s['parking_stand_actual']))

fe = data['step2_feature_engineering']
print('  1b. Feature engineering: {}'.format(fe))

enc = data['step3_encoding']
for e in enc:
    print('  1c. {} -> "{}" -> {}'.format(e['feature'], e['string_value'], e['encoded_integer']))
print('  1c. Final vector: {}'.format(data['step3_encoded_vector']))

path_nodes = data['step4_tree0_path']
internal = [n for n in path_nodes if not n['is_leaf']]
leaf_nodes = [n for n in path_nodes if n['is_leaf']]
leaf = leaf_nodes[0]
print('  1d. Tree path: {} nodes, root_gini={}, leaf_gini={}, leaf_pred={}'.format(
    len(path_nodes), internal[0]['gini'], leaf['gini'], leaf['predicted_stand']))

votes = data['step5_vote_counts']
total = data['step5_total_trees']
print('  1e. Votes (n_trees={}): {}'.format(total, [(v['stand'], v['votes']) for v in votes]))

top3 = data['step6_top3_predictions']
print('  1f. Top-3 predictions:')
for t in top3:
    print('      Rank {}: {} -> {:.2f}%'.format(t['rank'], t['stand'], t['probability']*100))

# STEP 2
print()
print('[STEP 2] Data variation experiment:')
with open(ROOT / 'ml/data_variation_results.json') as f:
    var = json.load(f)

print('  Hyperparams: n_estimators={n_estimators}, max_depth={max_depth}, '
      'min_samples_leaf={min_samples_leaf}'.format(**var['best_params_used']))
print('  Test fraction: {}, random_state: {}'.format(var['test_size_fraction'], var['random_state']))
print()
print('  {:>16}  {:>7}  {:>7}  {:>7}  {:>7}  {:>7}  {:>7}'.format(
    'Training Size', 'Top-1%', 'Top-3%', 'Top-5%', 'MacroP%', 'MacroR%', 'MacroF1'))
for r in var['results']:
    print('  {:>16}  {:>7.2f}  {:>7.2f}  {:>7.2f}  {:>7.2f}  {:>7.2f}  {:>7.2f}'.format(
        r['training_size_label'],
        r['top1_accuracy']*100,
        r['top3_accuracy']*100,
        r['top5_accuracy']*100,
        r['macro_precision']*100,
        r['macro_recall']*100,
        r['macro_f1']*100,
    ))

# revisibab4.md checks
print()
print('[OUTPUT] revisibab4.md content checks:')
md_path = ROOT / 'CLI/revision/revisibab4.md'
md = md_path.read_text(encoding='utf-8')
content_checks = [
    ('4.2.4', 'BAB 4.2.4 section present'),
    ('4.2.5', 'BAB 4.2.5 section present'),
    ('ATR 72', 'Sample aircraft type mentioned'),
    ('PELITA', 'Sample airline mentioned'),
    ('[4, 1, 24, 2, 2, 2]', 'Encoded feature vector'),
    ('Gini', 'Gini formula present'),
    ('0.9412', 'Root node Gini value (0.9412)'),
    ('188', 'Vote count 188/200'),
    ('49.69%', 'Top-1 probability A1'),
    ('29.02%', 'Top-2 probability A2'),
    ('14.70%', 'Top-3 probability A3'),
    ('52.79', '1000-row Top-3 result'),
    ('58.48', 'Full-data Top-3 result'),
    ('24.92', 'Macro Precision 1000-row'),
    ('STEP 0', 'Step 0 findings documented'),
    ('manual_calc_sample.json', 'Sample JSON file reference'),
    ('data_variation_results.json', 'Results JSON file reference'),
    ('chart', 'Chart data section present'),
]
for key, label in content_checks:
    found = key in md
    if not found:
        all_ok = False
    print('  [{}] {}: "{}"'.format('OK' if found else 'MISSING', label, key))

print()
print('Total lines in revisibab4.md: {}'.format(len(md.splitlines())))
print('Total bytes: {:,}'.format(len(md.encode('utf-8'))))
print()
if all_ok:
    print('RESULT: ALL CHECKS PASSED -- Task fully complete.')
else:
    print('RESULT: SOME CHECKS FAILED -- Needs attention.')
