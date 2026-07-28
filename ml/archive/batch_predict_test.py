#!/usr/bin/env python3
"""
Thesis Validation Tool -- Batch Prediction CLI Test
====================================================
Runs a representative set of test-case inputs against the same predict.py
that the web UI calls via PHP proc_open(). Results can be compared directly
against the web UI's recommendation panel to prove model consistency.

Usage:
    python ml/batch_predict_test.py

Output:
    A formatted table printed to stdout. Copy-paste into your thesis table.
    Raw JSON is also saved to ml/batch_predict_results.json.

Author note:
    The web UI calls this exact model and encoder pair
    (parking_stand_model_rf_redo.pkl + encoders_redo.pkl) via
    ApronController::callPythonPredictor(). Running this script is therefore
    equivalent to what the web UI does -- same code, same .pkl files.
"""
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
ROOT       = Path(__file__).resolve().parent   # ml/ directory
PREDICT_PY = ROOT / 'predict.py'
PYTHON     = sys.executable                    # Use the same interpreter running this

# ---------------------------------------------------------------------------
# Test set
# Representative samples covering all 3 categories and airline frequency tiers.
# Replace or extend with your actual test-set rows for the thesis comparison.
# ---------------------------------------------------------------------------
TEST_CASES = [
    # --- Commercial / HIGH frequency ---
    {"label": "TC-01", "aircraft_type": "ATR 72",  "operator_airline": "BATIK AIR", "category": "Komersial"},
    {"label": "TC-02", "aircraft_type": "A320",    "operator_airline": "CITILINK",  "category": "Komersial"},
    {"label": "TC-03", "aircraft_type": "ATR 72",  "operator_airline": "GARUDA",    "category": "Komersial"},
    # --- Commercial / MEDIUM frequency ---
    {"label": "TC-04", "aircraft_type": "C 208",   "operator_airline": "PELITA",    "category": "Komersial"},
    # --- Charter ---
    {"label": "TC-05", "aircraft_type": "C 208",   "operator_airline": "SUSI AIR",  "category": "Charter"},
    {"label": "TC-06", "aircraft_type": "PC 12",   "operator_airline": "KARISMA",   "category": "Charter"},
    {"label": "TC-07", "aircraft_type": "B737",    "operator_airline": "JIP",       "category": "Charter"},
    # --- Cargo ---
    {"label": "TC-08", "aircraft_type": "B737F",   "operator_airline": "TRI MG",    "category": "Cargo"},
    {"label": "TC-09", "aircraft_type": "ATR 72",  "operator_airline": "TRIGANA",   "category": "Cargo"},
    {"label": "TC-10", "aircraft_type": "B737F",   "operator_airline": "BBN",       "category": "Cargo"},
]

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def run_prediction(payload: dict) -> dict:
    """Call predict.py exactly as PHP does: JSON via stdin, JSON via stdout."""
    try:
        result = subprocess.run(
            [PYTHON, str(PREDICT_PY), '--top_k', '3'],
            input=json.dumps(payload),
            capture_output=True,
            text=True,
            timeout=15,
            cwd=str(ROOT.parent),  # project root so relative .pkl paths resolve
        )
        raw = result.stdout.strip()
        if not raw:
            return {'success': False, 'error': result.stderr.strip() or 'No output from script'}
        return json.loads(raw)
    except subprocess.TimeoutExpired:
        return {'success': False, 'error': 'Timeout (>15s)'}
    except json.JSONDecodeError as exc:
        return {'success': False, 'error': 'JSON parse error: ' + str(exc)}


def fmt_pct(prob: float) -> str:
    return '{:.1f}%'.format(prob * 100)


def print_table(results: list) -> None:
    """Print a fixed-width table suitable for thesis copy-paste."""
    col_w = [6, 10, 16, 12, 8, 8, 8, 8, 8, 8]
    headers = ['Label', 'Type', 'Airline', 'Category',
               'Rank 1', 'Rank 2', 'Rank 3', 'Prob 1', 'Prob 2', 'Prob 3']

    def row_str(cells):
        return ' | '.join(str(c).ljust(col_w[i]) for i, c in enumerate(cells))

    sep = '-+-'.join('-' * w for w in col_w)
    total_w = sum(col_w) + len(col_w) * 3 - 3

    print('\n' + '=' * total_w)
    print('  AMC PARKING STAND -- TOP-3 PREDICTION RESULTS (CLI)')
    print('  Same model called by the web UI (ApronController::callPythonPredictor)')
    print('=' * total_w)
    print(row_str(headers))
    print(sep)

    for r in results:
        tc = r['test_case']
        if not r.get('success'):
            row = [tc['label'], tc['aircraft_type'][:9], tc['operator_airline'][:15],
                   tc['category'][:11], 'ERROR', '--', '--',
                   r.get('error', '?')[:7], '', '']
        else:
            preds  = r['predictions']
            stands = [p['stand'] for p in preds]
            probs  = [p['probability'] for p in preds]
            while len(stands) < 3: stands.append('--')
            while len(probs)  < 3: probs.append(0.0)
            row = [tc['label'],
                   tc['aircraft_type'][:9],
                   tc['operator_airline'][:15],
                   tc['category'][:11],
                   stands[0], stands[1], stands[2],
                   fmt_pct(probs[0]), fmt_pct(probs[1]), fmt_pct(probs[2])]
        print(row_str(row))

    print(sep)
    print('\n  Model : parking_stand_model_rf_redo.pkl')
    print('  Enc   : encoders_redo.pkl')
    print('  Script: ' + str(PREDICT_PY))
    print()


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> None:
    print('\n[INFO] Python  : ' + PYTHON)
    print('[INFO] Script  : ' + str(PREDICT_PY))
    print('[INFO] Cases   : ' + str(len(TEST_CASES)) + '\n')

    results = []
    for tc in TEST_CASES:
        payload = {
            'aircraft_type':    tc['aircraft_type'],
            'operator_airline': tc['operator_airline'],
            'category':         tc['category'],
        }
        label_str = '  {:6s} {:10s} / {:15s} / {:12s} ... '.format(
            tc['label'], tc['aircraft_type'], tc['operator_airline'], tc['category']
        )
        sys.stdout.write(label_str)
        sys.stdout.flush()

        response = run_prediction(payload)
        if response.get('success'):
            preds = response.get('predictions', [])
            top3  = ', '.join(
                '{} ({})'.format(p['stand'], fmt_pct(p['probability']))
                for p in preds
            )
            print('OK => ' + top3)
            results.append({'test_case': tc, 'success': True, 'predictions': preds})
        else:
            err = response.get('error', 'Unknown error')
            print('FAIL => ' + err)
            results.append({'test_case': tc, 'success': False, 'error': err})

    print_table(results)

    out_path = ROOT / 'batch_predict_results.json'
    with out_path.open('w', encoding='utf-8') as fh:
        json.dump(results, fh, indent=2, ensure_ascii=False)
    print('[INFO] Raw JSON saved to: ' + str(out_path))


if __name__ == '__main__':
    main()
