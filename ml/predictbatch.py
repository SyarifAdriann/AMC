#!/usr/bin/env python3
"""
AMC Full Pipeline CLI — Thesis Comparison Tool
===============================================
Replicates the EXACT same recommendation pipeline the web UI runs:
  1. Queries the DB for currently occupied / available stands
  2. Calls predict.py for raw model probabilities   (same .pkl, same script)
  3. Queries airline preference scores from DB
  4. Applies business rules (filter occupied, A0 rule, 60/40 blend)
  5. Ranks and returns Top-3 recommendations

Run this at the same moment you use the web UI with the same inputs.
Results MUST be identical — proving CLI and web UI share one pipeline.

Usage:
    python ml/full_pipeline_compare.py

Edit TEST_CASES below to match what you type into the web UI.
"""
from __future__ import annotations

import json
import os
import subprocess
import sys
from datetime import datetime
from pathlib import Path

import pymysql
import pymysql.cursors

# ---------------------------------------------------------------------------
# Configuration — mirrors config/database.php
# ---------------------------------------------------------------------------
DB_HOST = os.getenv('DB_HOST',     'localhost')
DB_PORT = int(os.getenv('DB_PORT', '3306'))
DB_NAME = os.getenv('DB_DATABASE', 'amc')
DB_USER = os.getenv('DB_USERNAME', 'root')
DB_PASS = os.getenv('DB_PASSWORD', '')

ROOT       = Path(__file__).resolve().parent.parent   # project root
PREDICT_PY = ROOT / 'ml' / 'predict.py'
PYTHON     = sys.executable

# Business-rule weight (must match ApronController::applyBusinessRules)
PROB_WEIGHT = 0.6
PREF_WEIGHT = 0.4

# A0-compatible aircraft types (must match predict.py::determine_aircraft_size)
A0_COMPATIBLE_KEYWORDS = [
    'C152','C172','C182','C185','C206','C208','C402','C404','C425',
    'PC6','PC12','CESSNA','PILATUS',
]

# Default stand codes (must match ApronController::getDefaultStandCodes)
DEFAULT_STANDS = [
    'A0','A1','A2','A3',
    'B1','B2','B3','B4','B5','B6','B7','B8','B9','B10','B11','B12','B13',
    'SA01','SA02','SA03','SA04','SA05','SA06','SA07','SA08','SA09','SA10',
    'SA11','SA12','SA13','SA14','SA15','SA16','SA17','SA18','SA19','SA20',
    'SA21','SA22','SA23','SA24','SA25','SA26','SA27','SA28','SA29','SA30',
    'NSA01','NSA02','NSA03','NSA04','NSA05','NSA06','NSA07','NSA08','NSA09',
    'NSA10','NSA11','NSA12','NSA13','NSA14','NSA15',
    'WR01','WR02','WR03',
    'RE01','RE02','RE03','RE04','RE05','RE06','RE07',
    'RW01','RW02','RW03','RW04','RW05','RW06','RW07','RW08','RW09','RW10','RW11',
    'C1','C2','C3','HGR',
]

# ---------------------------------------------------------------------------
# TEST CASES — edit these to match what you enter in the web UI
# ---------------------------------------------------------------------------
TEST_CASES = [
    # Commercial / HIGH frequency
    {"label": "TC-01", "registration": "PK-LUO",  "aircraft_type": "A 320",   "operator_airline": "BATIK AIR",   "category": "Komersial"},
    {"label": "TC-02", "registration": "PK-GJT",  "aircraft_type": "ATR 72",  "operator_airline": "CITILINK",    "category": "Komersial"},
    {"label": "TC-03", "registration": "PK-GFM",  "aircraft_type": "B 738",   "operator_airline": "GARUDA",      "category": "Komersial"},
    # Commercial / MEDIUM frequency
    {"label": "TC-04", "registration": "PK-PAH",  "aircraft_type": "ATR 72",  "operator_airline": "PELITA",      "category": "Komersial"},
    # Charter
    {"label": "TC-05", "registration": "PK-VVM",  "aircraft_type": "C 208",   "operator_airline": "SUSI AIR",    "category": "Komersial"},
    {"label": "TC-06", "registration": "PK-BON",  "aircraft_type": "G IV",    "operator_airline": "JETSET",      "category": "Charter"},
    {"label": "TC-07", "registration": "T7-777",  "aircraft_type": "BBJ",     "operator_airline": "JIP",         "category": "Charter"},
    # Cargo
    {"label": "TC-08", "registration": "PK-JRB",  "aircraft_type": "B 733",   "operator_airline": "JAYAWIJAYA",  "category": "Cargo"},
    {"label": "TC-09", "registration": "PK-YST",  "aircraft_type": "B 733",   "operator_airline": "TRIGANA",     "category": "Cargo"},
    {"label": "TC-10", "registration": "PK-BBN",  "aircraft_type": "B737F",   "operator_airline": "BBN",         "category": "Cargo"},
]

# ---------------------------------------------------------------------------
# DB helpers
# ---------------------------------------------------------------------------

def get_db():
    return pymysql.connect(
        host=DB_HOST, port=DB_PORT, db=DB_NAME,
        user=DB_USER, password=DB_PASS,
        charset='utf8mb4', cursorclass=pymysql.cursors.DictCursor,
        autocommit=True,
    )


def get_occupied_stands(conn) -> set:
    """
    Mirrors ApronController::getAvailableStands() + findCurrentApronMovements().
    A stand is occupied if:
      - Aircraft arrived today and has no off_block_time, OR
      - Aircraft is RON and ron_complete = 0
    """
    sql = """
        SELECT am.parking_stand, am.off_block_time, am.is_ron, am.ron_complete
        FROM aircraft_movements am
        WHERE (
            (am.movement_date = CURDATE()
             AND (am.off_block_time IS NULL OR am.off_block_time = ''))
            OR
            (am.is_ron = 1 AND am.ron_complete = 0)
        )
        AND am.parking_stand IS NOT NULL AND am.parking_stand != ''
    """
    with conn.cursor() as cur:
        cur.execute(sql)
        rows = cur.fetchall()

    occupied = set()
    for row in rows:
        stand     = (row['parking_stand'] or '').strip().upper()
        off_block = (row['off_block_time'] or '').strip()
        is_ron    = int(row['is_ron']    or 0)
        ron_done  = int(row['ron_complete'] or 0)
        if stand and (off_block == '' or (is_ron == 1 and ron_done == 0)):
            occupied.add(stand)
    return occupied


def normalize_category(category: str) -> str:
    """Mirrors ApronController::normalizePreferenceCategory()."""
    cat = category.strip().upper()
    mapping = {
        'COMMERCIAL': 'COMMERCIAL', 'KOMERSIAL': 'COMMERCIAL',
        'DOMESTIC': 'COMMERCIAL',   'DOMESTIK': 'COMMERCIAL',
        'PASSENGER': 'COMMERCIAL',  'PAX': 'COMMERCIAL',
        'INTERNATIONAL': 'COMMERCIAL',
        'CHARTER': 'CHARTER', 'VIP': 'CHARTER',
        'GA': 'CHARTER',      'GENERAL AVIATION': 'CHARTER',
        'CARGO': 'CARGO',     'FREIGHT': 'CARGO', 'LOGISTICS': 'CARGO',
    }
    return mapping.get(cat, cat) if cat else 'CHARTER'


def get_airline_preferences(conn, airline: str, category: str, aircraft_type: str) -> dict:
    """
    Mirrors ApronController::getAirlinePreferences().
    1. Try airline_preferences table (specific)
    2. Fallback: historical usage by category from aircraft_movements
    Returns dict {STAND -> score (0–100)}
    """
    airline_upper = airline.strip().upper()
    cat_code      = normalize_category(category)
    ac_upper      = aircraft_type.strip().upper()

    # --- Step 1: airline_preferences table ---
    try:
        sql = """
            SELECT stand_name, priority_score
            FROM airline_preferences
            WHERE active = 1
              AND (airline_name = %s OR airline_name LIKE %s)
              AND airline_category = %s
              AND (aircraft_type = %s OR aircraft_type IS NULL OR aircraft_type = '')
            ORDER BY priority_score DESC, stand_name ASC
        """
        with conn.cursor() as cur:
            cur.execute(sql, (airline_upper, airline_upper + '%', cat_code, ac_upper))
            rows = cur.fetchall()
        if rows:
            return {r['stand_name'].upper(): float(r['priority_score']) for r in rows if r['stand_name']}
    except Exception:
        pass  # table may not exist — fall through

    # --- Step 2: historical usage by category ---
    try:
        sql = """
            SELECT UPPER(am.parking_stand) AS stand, COUNT(*) AS usage_count
            FROM aircraft_movements am
            LEFT JOIN aircraft_details ad ON am.registration = ad.registration
            WHERE am.parking_stand IS NOT NULL AND am.parking_stand != ''
              AND UPPER(COALESCE(ad.category, 'CHARTER')) = %s
            GROUP BY stand
            HAVING usage_count > 0
            ORDER BY usage_count DESC
        """
        with conn.cursor() as cur:
            cur.execute(sql, (cat_code,))
            rows = cur.fetchall()
        if rows:
            max_usage = max(float(r['usage_count']) for r in rows)
            if max_usage > 0:
                return {r['stand']: round((float(r['usage_count']) / max_usage) * 100, 2)
                        for r in rows if r['stand']}
    except Exception:
        pass

    return {}


def is_small_aircraft(aircraft_type: str) -> bool:
    """Mirrors predict.py::determine_aircraft_size() — True if A0-compatible."""
    ac = aircraft_type.strip().upper().replace(' ', '')
    for kw in A0_COMPATIBLE_KEYWORDS:
        kw_clean = kw.replace(' ', '')
        if kw_clean in ac or ac in kw_clean:
            return True
    return False


def apply_business_rules(raw_preds: list, available: list, occupied: set,
                          preferences: dict, aircraft_type: str) -> list:
    """
    Mirrors ApronController::applyBusinessRules() + rankStandsByPreference().
    - Filter out occupied stands
    - Filter out A0 for non-small aircraft
    - Composite score = 0.6 * probability + 0.4 * (preference/100)
    - Sort descending by composite_score, then probability
    - Return top 3
    """
    small    = is_small_aircraft(aircraft_type)
    avail_up = {s.upper() for s in available}

    candidates = []
    for row in raw_preds:
        stand = (row.get('stand') or '').upper()
        if not stand or stand not in avail_up:
            continue
        if stand == 'A0' and not small:
            continue
        prob  = float(row.get('probability', 0.0))
        pref  = float(preferences.get(stand, 0.0))
        norm_pref = max(0.0, min(1.0, pref / 100.0))
        composite = PROB_WEIGHT * prob + PREF_WEIGHT * norm_pref
        candidates.append({
            'stand':           stand,
            'probability':     prob,
            'preference_score': pref,
            'composite_score': composite,
        })

    # If no candidates passed filter, do availability-only fallback
    if not candidates:
        existing = set()
        for i, stand in enumerate(available):
            su = stand.upper()
            if su == 'A0' and not small:
                continue
            total = len(available)
            step  = int(100 / (total - 1)) if total > 1 else 0
            score = max(10, 100 - i * step)
            candidates.append({
                'stand':           su,
                'probability':     None,
                'preference_score': float(score),
                'composite_score': score / 100.0,
            })

    # Sort: composite desc, then probability desc
    candidates.sort(key=lambda x: (-(x['composite_score']), -(x['probability'] or 0)))
    ranked = candidates[:3]

    # Fill up to 3 from remaining available stands if filtered candidates < 3
    # Mirrors PHP: ApronController::applyBusinessRules() fill-to-3 block
    if len(ranked) < 3:
        existing_stands = {c['stand'] for c in ranked}
        for stand in available:
            if len(ranked) >= 3:
                break
            su = stand.upper()
            if su in existing_stands:
                continue
            if su == 'A0' and not small:
                continue
            pref  = float(preferences.get(su, 0.0))
            score = pref / 100.0
            ranked.append({
                'stand':           su,
                'probability':     None,   # no ML probability — wasn't in top-3
                'preference_score': pref,
                'composite_score': score,
            })

    result = []
    for i, c in enumerate(ranked, start=1):
        result.append({
            'rank':            i,
            'stand':           c['stand'],
            'probability':     c['probability'],
            'preference_score': c['preference_score'],
            'composite_score': c['composite_score'],
        })
    return result


# ---------------------------------------------------------------------------
# Prediction call — identical to how PHP calls it
# ---------------------------------------------------------------------------

def run_raw_prediction(payload: dict) -> dict:
    """Call predict.py with JSON via stdin, parse JSON from stdout."""
    try:
        result = subprocess.run(
            [PYTHON, str(PREDICT_PY), '--top_k', '3'],
            input=json.dumps(payload),
            capture_output=True, text=True, timeout=15,
            cwd=str(ROOT),
        )
        raw = result.stdout.strip()
        if not raw:
            return {'success': False, 'error': result.stderr.strip() or 'No output'}
        return json.loads(raw)
    except subprocess.TimeoutExpired:
        return {'success': False, 'error': 'Timeout'}
    except json.JSONDecodeError as exc:
        return {'success': False, 'error': str(exc)}


# ---------------------------------------------------------------------------
# Formatting
# ---------------------------------------------------------------------------

def fmt_pct(v) -> str:
    if v is None:
        return '  --   '
    return f'{v*100:5.1f}%'

def fmt_score(v) -> str:
    if v is None:
        return '  --  '
    return f'{v:.3f}'


def print_result_table(results: list) -> None:
    w = [6, 9, 12, 16, 11, 22, 22, 22]
    hdr = ['Label', 'Reg', 'Type', 'Airline', 'Category',
           'Rank 1 (stand/prob/score)',
           'Rank 2 (stand/prob/score)',
           'Rank 3 (stand/prob/score)']

    def row_str(cells):
        return ' | '.join(str(c).ljust(w[i]) for i, c in enumerate(cells))

    sep_line = '-+-'.join('-' * wi for wi in w)
    total_w = sum(w) + len(w) * 3 - 3

    print('\n' + '=' * total_w)
    print('  AMC FULL PIPELINE -- CLI RESULTS (mirrors web UI exactly)')
    print(f'  Snapshot time : {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}')
    print('  Score formula : 0.6 x model_probability + 0.4 x preference_score/100')
    print('=' * total_w)
    print(row_str(hdr))
    print(sep_line)

    for r in results:
        tc    = r['test_case']
        final = r.get('final', [])

        def fmt_rank(rank_list, n):
            if n > len(rank_list):
                return 'N/A'
            p = rank_list[n - 1]
            return f"{p['stand']} {fmt_pct(p['probability'])} s={fmt_score(p['composite_score'])}"

        if not r.get('success'):
            cells = [tc['label'], tc.get('registration','')[:8],
                     tc['aircraft_type'][:11], tc['operator_airline'][:15],
                     tc['category'][:10], r.get('error','?')[:21], '', '']
        else:
            cells = [
                tc['label'],
                tc.get('registration', '')[:8],
                tc['aircraft_type'][:11],
                tc['operator_airline'][:15],
                tc['category'][:10],
                fmt_rank(final, 1),
                fmt_rank(final, 2),
                fmt_rank(final, 3),
            ]
        print(row_str(cells))

    print(sep_line)
    print()


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> None:
    print(f'\n[INFO] Python     : {PYTHON}')
    print(f'[INFO] Script     : {PREDICT_PY}')
    print(f'[INFO] DB         : {DB_USER}@{DB_HOST}:{DB_PORT}/{DB_NAME}')
    print(f'[INFO] Test cases : {len(TEST_CASES)}\n')

    # --- Connect to DB once ---
    try:
        conn = get_db()
        print('[DB] Connected successfully.')
    except Exception as exc:
        print(f'[DB] Connection failed: {exc}')
        sys.exit(1)

    # --- Get current occupancy snapshot ---
    occupied = get_occupied_stands(conn)
    all_stands_up = [s.upper() for s in DEFAULT_STANDS]
    available = [s for s in all_stands_up if s not in occupied]

    print(f'[DB] Occupied stands ({len(occupied)}): {", ".join(sorted(occupied)) or "none"}')
    print(f'[DB] Available stands: {len(available)} of {len(all_stands_up)}\n')

    results = []
    for tc in TEST_CASES:
        payload = {
            'aircraft_type':    tc['aircraft_type'],
            'operator_airline': tc['operator_airline'],
            'category':         tc['category'],
        }
        label_str = f"  {tc['label']:6s} [{tc.get('registration','?'):8s}] {tc['aircraft_type']:10s} / {tc['operator_airline']:15s} / {tc['category']:11s} ... "
        sys.stdout.write(label_str)
        sys.stdout.flush()

        # Step 1: raw model prediction
        resp = run_raw_prediction(payload)
        if not resp.get('success'):
            err = resp.get('error', 'Unknown')
            print(f'FAIL (predict.py) => {err}')
            results.append({'test_case': tc, 'success': False, 'error': err})
            continue

        raw_preds = resp.get('predictions', [])

        # Step 2: airline preferences from DB
        prefs = get_airline_preferences(conn, tc['operator_airline'], tc['category'], tc['aircraft_type'])

        # Step 3: apply business rules
        final = apply_business_rules(raw_preds, available, occupied, prefs, tc['aircraft_type'])

        top3_str = ', '.join(
            f"{r['stand']} ({fmt_pct(r['probability']).strip()})" for r in final
        )
        print(f'OK => {top3_str}')
        results.append({'test_case': tc, 'success': True,
                         'raw_predictions': raw_preds, 'final': final,
                         'occupied_snapshot': sorted(occupied),
                         'preferences': prefs})

    conn.close()

    print_result_table(results)

    out_path = ROOT / 'ml' / 'full_pipeline_results.json'
    with out_path.open('w', encoding='utf-8') as fh:
        json.dump(results, fh, indent=2, ensure_ascii=False, default=str)
    print(f'[INFO] Full JSON saved to: {out_path}')
    print('[INFO] Now open the web UI and enter the same inputs to compare.')


if __name__ == '__main__':
    main()
