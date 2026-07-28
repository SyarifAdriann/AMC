# AMC Streamlit — Technical Implementation Specification

**Version:** 1.0  
**Date:** 2026-07-01  
**Companion to:** `STREAMLIT_PRD.md`  
**Purpose:** Exact how-to guide to minimize implementation errors and testing surprises.

---

## 0. Pre-Flight Checklist (Before Writing One Line of Code)

### Files to copy from this repo into `amc-streamlit/`

| Source (this repo) | Destination (new repo) | Notes |
|---|---|---|
| `ml/parking_stand_model_rf_redo.pkl` | `ml/parking_stand_model_rf_redo.pkl` | Binary — copy exactly |
| `ml/encoders_redo.pkl` | `ml/encoders_redo.pkl` | Binary — copy exactly |
| `ml/predict.py` | `ml/predict.py` | Do NOT call via subprocess — import as module |
| `storage/cache/historical_preferences.json` | `storage/cache/historical_preferences.json` | Used by ML preference scoring |
| `reports/phase5_metrics.json` | `reports/phase5_metrics.json` | Model accuracy metrics |
| `amc.sql` | *(used for migration only, not deployed)* | Used once to build Neon schema |

### `requirements.txt` — exact pinned versions

```
streamlit>=1.35.0
streamlit-authenticator==0.3.3
sqlalchemy>=2.0
psycopg2-binary>=2.9
pandas>=2.0
numpy>=1.26
scikit-learn>=1.4
plotly>=5.20
bcrypt>=4.1
PyYAML>=6.0
```

> ⚠️ `PyYAML` is a **required** but unlisted dependency of `streamlit-authenticator`. Without it, the app crashes on import. Pin it explicitly.

---

## 1. Project Entry Point — `streamlit_app.py`

### Role
- Login gate for the entire app
- Sets `st.session_state` auth fields once
- Redirects unauthenticated users

### Pattern

```python
import streamlit as st
import streamlit_authenticator as stauth
import yaml
from yaml.loader import SafeLoader

st.set_page_config(page_title="AMC Monitoring System", layout="wide", page_icon="✈️")

# Load credentials from Streamlit secrets
# In secrets.toml these are nested under [credentials] etc.
config = {
    'credentials': dict(st.secrets['credentials']),
    'cookie': dict(st.secrets['cookie']),
}

authenticator = stauth.Authenticate(
    config['credentials'],
    config['cookie']['name'],
    config['cookie']['key'],
    config['cookie']['expiry_days'],
)

name, auth_status, username = authenticator.login('AMC Login', 'main')

if auth_status is False:
    st.error('Incorrect username or password.')
    st.stop()
elif auth_status is None:
    st.warning('Please enter your credentials.')
    st.stop()
else:
    # Authenticated — extract role from config (library doesn't do this automatically)
    role = config['credentials']['usernames'][username].get('role', 'viewer')
    st.session_state['role'] = role
    st.session_state['username'] = username
    st.session_state['authenticated'] = True
    authenticator.logout('Logout', 'sidebar')
    st.switch_page("pages/1_Apron_Map.py")  # Redirect to main page
```

> ⚠️ **Critical gotcha**: `streamlit-authenticator` v0.3.x returns `role` as part of the user dict stored in `config['credentials']['usernames'][username]`, but does NOT automatically put it into `st.session_state`. You must read it manually after login (as shown above).

### Auth guard — put this at the top of EVERY page file

```python
# pages/1_Apron_Map.py  (and all other pages)
import streamlit as st

def require_auth(min_role=None):
    if not st.session_state.get('authenticated'):
        st.switch_page("streamlit_app.py")
    if min_role:
        role_order = {'viewer': 0, 'operator': 1, 'admin': 2}
        if role_order.get(st.session_state.get('role', 'viewer'), 0) < role_order[min_role]:
            st.error("You don't have permission to view this page.")
            st.stop()

require_auth()  # Any logged-in user
# require_auth('operator')  # operator + admin only
# require_auth('admin')     # admin only
```

---

## 2. Database Service — `services/db_service.py`

### Connection pattern (Neon + SQLAlchemy)

```python
import streamlit as st
from sqlalchemy import create_engine, text

@st.cache_resource
def get_engine():
    """Cached engine — created once per app session, reused across reruns."""
    url = st.secrets["database"]["url"]
    # Neon requires sslmode=require — already in the connection string
    engine = create_engine(
        url,
        pool_pre_ping=True,       # Detect stale connections (Neon sleeps)
        pool_recycle=300,         # Recycle connections every 5 min
        connect_args={"sslmode": "require"},
    )
    return engine

def run_query(sql: str, params: dict = None) -> list[dict]:
    """Execute a SELECT and return list of dicts."""
    engine = get_engine()
    with engine.connect() as conn:
        result = conn.execute(text(sql), params or {})
        return [dict(row._mapping) for row in result]

def run_write(sql: str, params: dict = None) -> int:
    """Execute INSERT/UPDATE/DELETE. Returns rowcount."""
    engine = get_engine()
    with engine.begin() as conn:  # auto-commits on success, rolls back on exception
        result = conn.execute(text(sql), params or {})
        return result.rowcount
```

### MySQL → PostgreSQL syntax differences to watch

| MySQL | PostgreSQL equivalent |
|---|---|
| `NOW()` | `NOW()` ✅ same |
| `DATE_FORMAT(col, '%d/%m/%Y')` | `TO_CHAR(col, 'DD/MM/YYYY')` |
| `CONCAT(a, b)` | `a \|\| b` or `CONCAT(a, b)` ✅ |
| `col NOT LIKE '%)%'` | `col NOT LIKE '%)%'` ✅ same |
| `AUTO_INCREMENT` | `GENERATED ALWAYS AS IDENTITY` |
| `tinyint(1)` | `BOOLEAN` — use `TRUE`/`FALSE` |
| `is_ron = 1` | `is_ron = TRUE` |
| `is_ron = 0` | `is_ron = FALSE` |
| Backtick identifiers `` `col` `` | Double quotes `"col"` |
| `LIMIT :n` with PDO | `LIMIT :n` ✅ SQLAlchemy supports this |
| `INSERT ... ON DUPLICATE KEY UPDATE` | `INSERT ... ON CONFLICT (col) DO UPDATE SET ...` |
| `LAST_INSERT_ID()` | `RETURNING id` — append to INSERT query |

> ⚠️ **The is_ron gotcha**: The PHP code stores `is_ron = 1/0` as integers. In PostgreSQL with `BOOLEAN` column, comparisons must use `TRUE`/`FALSE`. When reading, `row['is_ron']` will be a Python `bool`, not an `int`. Use `if row['is_ron']:` not `if row['is_ron'] == 1:`.

### Getting last inserted ID in PostgreSQL

```python
# Append RETURNING id to the INSERT statement
sql = """
    INSERT INTO aircraft_movements (registration, parking_stand, movement_date, ...)
    VALUES (:reg, :stand, :date, ...)
    RETURNING id
"""
engine = get_engine()
with engine.begin() as conn:
    result = conn.execute(text(sql), params)
    new_id = result.scalar()  # Gets the returned id
```

---

## 3. Authentication Secrets Structure

### `.streamlit/secrets.toml` (local only — gitignored)

```toml
[credentials.usernames.admin]
name = "admin"
password = "$2b$12$REPLACE_WITH_ACTUAL_BCRYPT_HASH"
role = "admin"

[credentials.usernames.AMC]
name = "AMC"
password = "$2b$12$REPLACE_WITH_ACTUAL_BCRYPT_HASH"
role = "operator"

[credentials.usernames.ATC]
name = "ATC"
password = "$2b$12$REPLACE_WITH_ACTUAL_BCRYPT_HASH"
role = "viewer"

[cookie]
expiry_days = 1
key = "amc_auth_key_2026"
name = "amc_session"

[database]
url = "postgresql://user:pass@ep-xxx.neon.tech/amc?sslmode=require"
```

### Generating bcrypt hashes (run once locally)

```python
import bcrypt
passwords = ["supervisor", "mikecharlie", "tangowhiskey"]
for p in passwords:
    hashed = bcrypt.hashpw(p.encode(), bcrypt.gensalt()).decode()
    print(f"{p}: {hashed}")
```

Paste the output hashes into `secrets.toml`.

---

## 4. Apron Map — `pages/1_Apron_Map.py`

### Key principle
The map HTML is a self-contained document injected into an iframe via `st.components.v1.html()`. Python injects aircraft data as a `<script>` block into the HTML before rendering.

### Apron injector pattern — `components/apron_injector.py`

```python
import json
from services.db_service import run_query

# Stand pixel coordinates — extracted from PHP apron/index.php
STANDS_VIEW_A = {
    'A0': (1785, 923), 'A1': (1712, 923), 'A2': (1621, 923), 'A3': (1518, 923),
    'B1': (1414, 923), 'B2': (1321, 923), 'B3': (1229, 923), 'B4': (1136, 923),
    'B5': (1043, 923), 'B6': (950, 923),  'B7': (859, 923),  'B8': (768, 923),
    'B9': (673, 923),  'B10': (577, 923), 'B11': (483, 923), 'B12': (394, 923), 'B13': (306, 923),
    'SA01': (152, 125), 'SA02': (365, 125), 'SA03': (578, 125), 'SA04': (791, 125),
    'SA05': (1004, 125), 'SA06': (1218, 125), 'SA07': (87, 250), 'SA08': (210, 250),
    'SA09': (300, 250), 'SA10': (423, 250), 'SA11': (514, 250), 'SA12': (635, 250),
    'SA13': (726, 250), 'SA14': (849, 250), 'SA15': (940, 250), 'SA16': (1062, 250),
    'SA17': (1153, 250), 'SA18': (1275, 250), 'SA19': (87, 399), 'SA20': (208, 399),
    'SA21': (300, 399), 'SA22': (421, 399), 'SA23': (513, 399), 'SA24': (635, 399),
    'SA25': (726, 399), 'SA26': (848, 399), 'SA27': (939, 399), 'SA28': (1061, 399),
    'SA29': (1153, 399), 'SA30': (1275, 399),
    'NSA01': (1460, 146), 'NSA02': (1520, 146), 'NSA03': (1584, 146), 'NSA04': (1643, 146),
    'NSA05': (1702, 146), 'NSA06': (1761, 146), 'NSA07': (1819, 146), 'NSA08': (1883, 180),
    'NSA09': (1883, 293), 'NSA10': (1520, 328), 'NSA11': (1584, 328), 'NSA12': (1643, 328),
    'NSA13': (1702, 328), 'NSA14': (1761, 328), 'NSA15': (1819, 328),
    'WR01': (115, 627), 'WR02': (115, 784), 'WR03': (115, 941),
    'RE01': (703, 700), 'RE02': (637, 700), 'RE03': (568, 700), 'RE04': (499, 700),
    'RE05': (431, 700), 'RE06': (363, 700), 'RE07': (296, 700),
    'RW01': (1647, 700), 'RW02': (1580, 700), 'RW03': (1513, 700), 'RW04': (1446, 700),
    'RW05': (1379, 700), 'RW06': (1307, 700), 'RW07': (1241, 700), 'RW08': (1173, 700),
    'RW09': (1107, 700), 'RW10': (1039, 700), 'RW11': (970, 700),
    'C1': (0, 0), 'C2': (0, 0), 'C3': (0, 0),  # Not shown on apron map
}

STANDS_VIEW_B = {
    'A0': (1875, 903), 'A1': (1818, 903), 'A2': (1761, 903), 'A3': (1704, 903),
    'B1': (1647, 903), 'B2': (1590, 903), 'B3': (1533, 903), 'B4': (1476, 903),
    'B5': (1419, 903), 'B6': (1362, 903), 'B7': (1305, 903), 'B8': (1248, 903),
    'B9': (1191, 903), 'B10': (1134, 903), 'B11': (1077, 903), 'B12': (1020, 903), 'B13': (963, 903),
    'RW01': (1760, 773), 'RW02': (1705, 773), 'RW03': (1650, 773), 'RW04': (1595, 773),
    'RW05': (1540, 773), 'RW06': (1485, 773), 'RW07': (1430, 773), 'RW08': (1375, 773),
    'RW09': (1320, 773), 'RW10': (1265, 773), 'RW11': (1210, 773),
    'RE01': (1150, 773), 'RE02': (1100, 773), 'RE03': (1050, 773), 'RE04': (1000, 773),
    'RE05': (950, 773),  'RE06': (900, 773),  'RE07': (850, 773),
    'SA01': (42, 93),   'SA02': (185, 93),  'SA03': (328, 93),  'SA04': (463, 93),
    'SA05': (600, 93),  'SA06': (731, 93),
    'SA07': (0, 195),   'SA08': (80, 195),  'SA09': (141, 195), 'SA10': (228, 195),
    'SA11': (289, 195), 'SA12': (366, 195), 'SA13': (427, 195), 'SA14': (504, 195),
    'SA15': (565, 195), 'SA16': (642, 195), 'SA17': (703, 195), 'SA18': (777, 195),
    'SA19': (0, 297),   'SA20': (80, 297),  'SA21': (141, 297), 'SA22': (228, 297),
    'SA23': (289, 297), 'SA24': (366, 297), 'SA25': (427, 297), 'SA26': (504, 297),
    'SA27': (565, 297), 'SA28': (642, 297), 'SA29': (703, 297), 'SA30': (777, 297),
    'NSA01': (943, 195), 'NSA02': (1015, 195), 'NSA03': (1087, 195), 'NSA04': (1159, 195),
    'NSA05': (1231, 195), 'NSA06': (1303, 195), 'NSA07': (1376, 195),
    'NSA08': (1448, 231), 'NSA09': (1448, 307),
    'NSA10': (1015, 343), 'NSA11': (1087, 343), 'NSA12': (1159, 343),
    'NSA13': (1231, 343), 'NSA14': (1303, 343), 'NSA15': (1376, 343),
    'WR01': (760, 680),  'WR02': (760, 770),  'WR03': (760, 860),
}

def get_current_movements() -> list[dict]:
    return run_query("""
        SELECT registration, parking_stand, off_block_time, is_ron, ron_complete, category, operator_airline
        FROM aircraft_movements
        WHERE movement_date = CURRENT_DATE
        ORDER BY parking_stand
    """)

def build_stand_status(movements: list[dict]) -> dict:
    """Return dict of {stand_code: status} where status is 'occupied', 'ron', or 'available'."""
    status = {}
    for m in movements:
        stand = (m.get('parking_stand') or '').upper().strip()
        if not stand:
            continue
        if m['is_ron'] and not m['ron_complete']:
            status[stand] = 'ron'
        elif not m.get('off_block_time'):
            status[stand] = 'occupied'
    return status

def build_apron_html(view: str, movements: list[dict]) -> str:
    """Builds the full self-contained HTML string for the apron map."""
    stands = STANDS_VIEW_A if view == 'a' else STANDS_VIEW_B
    stand_status = build_stand_status(movements)

    # Build registration labels per stand
    reg_map = {}
    for m in movements:
        stand = (m.get('parking_stand') or '').upper().strip()
        if stand and (not m.get('off_block_time') or (m['is_ron'] and not m['ron_complete'])):
            reg_map[stand] = m.get('registration', '')

    stand_divs = []
    for code, (x, y) in stands.items():
        status = stand_status.get(code, 'available')
        reg = reg_map.get(code, '')
        color = {'occupied': '#dc2626', 'ron': '#d97706', 'available': '#1e3a5f'}.get(status, '#1e3a5f')
        label = f"{code}<br><small style='font-size:7px'>{reg}</small>" if reg else code
        stand_divs.append(
            f'<div style="position:absolute; left:{x}px; top:{y}px; '
            f'background:{color}; color:white; border-radius:4px; '
            f'padding:3px 6px; font-size:10px; font-weight:700; '
            f'min-width:40px; text-align:center; cursor:default;" '
            f'title="{code}: {status.upper()}">{label}</div>'
        )

    return f"""<!DOCTYPE html>
<html><head><meta charset="utf-8">
<style>body{{margin:0;padding:0;background:#0f172a;overflow:hidden;}}</style>
</head>
<body>
<div style="position:relative; width:1920px; height:1080px; background:#1e293b; border-radius:8px;">
  {''.join(stand_divs)}
</div>
</body></html>"""
```

### Rendering in Streamlit

```python
import streamlit as st
import streamlit.components.v1 as components
from components.apron_injector import get_current_movements, build_apron_html

view = st.radio("Map View", ["View A (Stylized)", "View B (Real Coords)"],
                horizontal=True, label_visibility="collapsed")
view_key = 'a' if 'A' in view else 'b'

movements = get_current_movements()
html = build_apron_html(view_key, movements)

# Height must be explicitly set — iframe will not auto-size
components.html(html, height=600, scrolling=True)
```

> ⚠️ **iframe sandboxing**: The iframe cannot communicate back to Python. All interactive actions (save movement, ML recommendation) must use Streamlit widgets OUTSIDE the iframe — not inside the HTML.

---

## 5. ML Service — `services/ml_service.py`

### Model loading

```python
import pickle
import streamlit as st

@st.cache_resource
def load_model():
    with open('ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
        model = pickle.load(f)
    with open('ml/encoders_redo.pkl', 'rb') as f:
        encoders = pickle.load(f)
    return model, encoders
```

> ⚠️ `@st.cache_resource` means the model loads ONCE on first use and is shared across all users and reruns. Do not use `@st.cache_data` for model objects — they can't be serialized.

### Importing predict.py functions

```python
# predict.py uses sys.stdin for input when called as a script
# When imported as a module, only its functions are available
# Check which functions exist in predict.py:
import sys
sys.path.insert(0, 'ml/')
import predict as ml_predict

# Use ml_predict.build_feature_vector(), ml_predict.determine_aircraft_size() etc.
# DO NOT call ml_predict.main() — that reads from stdin
```

### Full prediction + scoring chain

```python
from services.db_service import run_query
import json, os

def get_airline_preferences(airline: str, category: str, aircraft_type: str, available_stands: list) -> dict:
    """Returns {stand: score} preference dict. Mirrors PHP applyBusinessRules preference chain."""
    airline = airline.upper().strip()
    category = normalize_category(category)

    # Step 1: Check airline_preferences table
    rows = run_query("""
        SELECT stand_name, priority_score FROM airline_preferences
        WHERE active = TRUE
          AND (airline_name = :airline OR airline_name LIKE :airline_like)
          AND airline_category = :category
        ORDER BY priority_score DESC
    """, {'airline': airline, 'airline_like': f"{airline}%", 'category': category})

    if rows:
        return {r['stand_name'].upper(): float(r['priority_score']) for r in rows}

    # Step 2: Historical frequency from aircraft_movements
    cache_file = 'storage/cache/historical_preferences.json'
    if os.path.exists(cache_file):
        with open(cache_file) as f:
            cache = json.load(f)
        if category in cache.get('preferences', {}):
            return {k: float(v.get('score', 0)) for k, v in cache['preferences'][category].items()}

    # Step 3: DB historical query
    rows = run_query("""
        SELECT UPPER(am.parking_stand) AS stand, COUNT(*) AS usage_count
        FROM aircraft_movements am
        LEFT JOIN aircraft_details ad ON am.registration = ad.registration
        WHERE am.parking_stand IS NOT NULL AND am.parking_stand != ''
          AND UPPER(COALESCE(ad.category, 'CHARTER')) = :category
        GROUP BY stand ORDER BY usage_count DESC
    """, {'category': category})

    if rows:
        max_count = max(r['usage_count'] for r in rows)
        return {r['stand']: round((r['usage_count'] / max_count) * 100, 2) for r in rows}

    # Step 4: Fallback — equal scores across available stands
    total = len(available_stands)
    step = int(100 / (total - 1)) if total > 1 else 0
    scores = {}
    current = 100
    for stand in available_stands:
        scores[stand.upper()] = max(10, current)
        current -= step
    return scores


def normalize_category(cat: str) -> str:
    cat = cat.upper().strip()
    mapping = {
        'COMMERCIAL': 'COMMERCIAL', 'KOMERSIAL': 'COMMERCIAL',
        'DOMESTIC': 'COMMERCIAL', 'DOMESTIK': 'COMMERCIAL',
        'CARGO': 'CARGO', 'FREIGHT': 'CARGO',
        'CHARTER': 'CHARTER', 'VIP': 'CHARTER', 'GA': 'CHARTER',
    }
    return mapping.get(cat, cat)


def is_small_aircraft(aircraft_type: str) -> bool:
    at = aircraft_type.upper().replace(' ', '')
    small = ['C152', 'C172', 'C182', 'C185', 'C206', 'C208', 'C402', 'C404',
             'C425', 'PC6', 'PC12', 'CESSNA', 'PILATUS']
    return any(p in at for p in small)


def get_available_stands() -> dict:
    """Returns {'available': [...], 'occupied': [...]}"""
    # Default stand list (mirrors PHP getDefaultStandCodes)
    all_stands = [
        'A0', 'A1', 'A2', 'A3',
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
    # Override with DB if stands table exists
    try:
        rows = run_query("SELECT name FROM stands WHERE is_active = TRUE")
        if rows:
            all_stands = [r['name'].upper() for r in rows]
    except Exception:
        pass

    # Check current occupancy
    movements = run_query("""
        SELECT parking_stand, off_block_time, is_ron, ron_complete
        FROM aircraft_movements
        WHERE movement_date = CURRENT_DATE
    """)
    occupied = set()
    for m in movements:
        stand = (m.get('parking_stand') or '').upper().strip()
        if not stand:
            continue
        if not m.get('off_block_time') or (m['is_ron'] and not m['ron_complete']):
            occupied.add(stand)

    available = [s for s in all_stands if s not in occupied]
    return {'available': available, 'occupied': list(occupied)}


def get_recommendations(aircraft_type: str, operator_airline: str, category: str) -> dict:
    """Full recommendation chain. Returns top-3 ranked stands."""
    model, encoders = load_model()
    availability = get_available_stands()
    available = availability['available']

    # Call model
    import sys
    sys.path.insert(0, 'ml/')
    import predict as ml_predict
    raw = ml_predict.predict(
        model, encoders,
        aircraft_type=aircraft_type,
        operator_airline=operator_airline,
        category=category,
    )
    # raw is list of {'stand': ..., 'probability': ...}

    is_small = is_small_aircraft(aircraft_type)
    prefs = get_airline_preferences(operator_airline, category, aircraft_type, available)

    candidates = []
    for row in raw:
        stand = row['stand'].upper()
        if stand not in available:
            continue
        if stand == 'A0' and not is_small:
            continue
        prob = float(row['probability'])
        pref = float(prefs.get(stand, 0.0))
        norm_pref = max(0.0, min(1.0, pref / 100))
        score = (0.6 * prob) + (0.4 * norm_pref)
        candidates.append({'stand': stand, 'probability': prob,
                           'preference_score': pref, 'composite_score': score})

    # Sort by composite score
    candidates.sort(key=lambda x: x['composite_score'], reverse=True)

    # Pad to 3 if needed
    existing = {c['stand'] for c in candidates}
    for stand in available:
        if len(candidates) >= 3:
            break
        if stand in existing:
            continue
        if stand == 'A0' and not is_small:
            continue
        pref = float(prefs.get(stand, 0.0))
        candidates.append({'stand': stand, 'probability': None,
                           'preference_score': pref, 'composite_score': pref / 100})

    results = candidates[:3]
    for i, r in enumerate(results):
        r['rank'] = i + 1

    return {
        'results': results,
        'availability': availability,
        'source': 'model' if candidates else 'fallback',
    }
```

### Saving prediction to log + outcome marking

```python
def record_prediction_log(input_data: dict, results: list, username: str) -> int:
    import secrets as sec
    token = sec.token_hex(16)
    sql = """
        INSERT INTO ml_prediction_log
            (prediction_token, aircraft_type, operator_airline, category,
             predicted_stands, recommendation_payload, requested_by_user)
        VALUES (:token, :aircraft_type, :operator_airline, :category,
                :predicted, :payload, (SELECT id FROM users WHERE username = :username LIMIT 1))
        RETURNING id
    """
    import json as _json
    engine = get_engine()
    with engine.begin() as conn:
        row = conn.execute(text(sql), {
            'token': token,
            'aircraft_type': input_data['aircraft_type'].upper(),
            'operator_airline': input_data['operator_airline'].upper(),
            'category': input_data['category'].upper(),
            'predicted': _json.dumps(results),
            'payload': _json.dumps(input_data),
            'username': username,
        })
        return row.scalar()

def mark_prediction_outcome(log_id: int, actual_stand: str):
    """Called when a movement is saved with a stand that was in the recommendation."""
    rows = run_query("SELECT predicted_stands FROM ml_prediction_log WHERE id = :id", {'id': log_id})
    if not rows:
        return
    import json as _json
    predicted = _json.loads(rows[0]['predicted_stands'] or '[]')
    top_stands = [p['stand'].upper() for p in predicted[:3] if p.get('stand')]
    was_correct = actual_stand.upper() in top_stands
    run_write("""
        UPDATE ml_prediction_log
        SET actual_stand_assigned = :stand,
            was_prediction_correct = :correct,
            actual_recorded_at = NOW()
        WHERE id = :id
    """, {'stand': actual_stand.upper(), 'correct': was_correct, 'id': log_id})
```

---

## 6. RON Service — `services/ron_service.py`

### PostgreSQL-adapted queries

```python
from services.db_service import run_query, run_write
import re

def carry_over_active_ron() -> int:
    """
    Marks past open movements as RON.
    Appends date suffix to on_block_time if not already present.
    PostgreSQL version of RonService::carryOverActiveRon()
    """
    return run_write("""
        UPDATE aircraft_movements
        SET is_ron = TRUE,
            on_block_time = CASE
                WHEN on_block_time NOT LIKE '%)%'
                THEN on_block_time || ' (' || TO_CHAR(movement_date, 'DD/MM/YYYY') || ')'
                ELSE on_block_time
            END
        WHERE (off_block_time IS NULL OR off_block_time = '')
          AND on_block_time IS NOT NULL
          AND on_block_time != ''
          AND movement_date < CURRENT_DATE
          AND (is_ron = FALSE OR is_ron IS NULL)
    """)

def set_ron_for_open_movements(user_id: int) -> int:
    """Manual 'Set RON' button action."""
    carry_over_active_ron()
    today_str = __import__('datetime').date.today().strftime('%d/%m/%Y')
    rows = run_query("""
        SELECT id, on_block_time FROM aircraft_movements
        WHERE (off_block_time IS NULL OR off_block_time = '')
          AND on_block_time IS NOT NULL
          AND is_ron = FALSE
    """)
    count = 0
    for row in rows:
        on_block = row['on_block_time'] or ''
        if '(' not in on_block:
            normalized = normalize_ron_time(on_block)
            formatted_time = f"{normalized} ({today_str})" if normalized else f"{on_block} ({today_str})"
            run_write("""
                UPDATE aircraft_movements
                SET is_ron = TRUE, on_block_time = :t, user_id_updated = :uid
                WHERE id = :id
            """, {'t': formatted_time, 'uid': user_id, 'id': row['id']})
        else:
            run_write("""
                UPDATE aircraft_movements SET is_ron = TRUE, user_id_updated = :uid WHERE id = :id
            """, {'uid': user_id, 'id': row['id']})
        count += 1
    return count

def normalize_ron_time(value: str) -> str:
    value = (value or '').strip()
    if re.match(r'^\d{3,4}$', value):
        value = value.zfill(4)
        return f"{value[:2]}:{value[2:]}"
    return value
```

> ⚠️ **RON session guard**: Run `carry_over_active_ron()` once per session per page that needs it. Use this pattern:
> ```python
> if 'ron_carried_over' not in st.session_state:
>     from services.ron_service import carry_over_active_ron
>     carry_over_active_ron()
>     st.session_state['ron_carried_over'] = True
> ```

---

## 7. Master Table — `pages/2_Master_Table.py`

### st.data_editor pattern

```python
import streamlit as st
import pandas as pd
from services.db_service import run_query, run_write

# Load data
def load_movements(filters: dict, page: int, per_page: int = 75) -> pd.DataFrame:
    conditions = ["1=1"]
    params = {}
    if filters.get('date_from'):
        conditions.append("movement_date >= :date_from")
        params['date_from'] = filters['date_from']
    if filters.get('date_to'):
        conditions.append("movement_date <= :date_to")
        params['date_to'] = filters['date_to']
    if filters.get('category'):
        conditions.append("UPPER(category) = :category")
        params['category'] = filters['category'].upper()
    if filters.get('airline'):
        conditions.append("operator_airline ILIKE :airline")
        params['airline'] = f"%{filters['airline']}%"
    if filters.get('flight_no'):
        conditions.append("(flight_no_arr ILIKE :fno OR flight_no_dep ILIKE :fno)")
        params['fno'] = f"%{filters['flight_no']}%"

    where = " AND ".join(conditions)
    offset = (page - 1) * per_page
    params.update({'limit': per_page, 'offset': offset})

    rows = run_query(f"""
        SELECT id, movement_date, registration, aircraft_type, parking_stand,
               on_block_time, off_block_time, flight_no_arr, flight_no_dep,
               from_location, to_location, operator_airline, category,
               is_ron, ron_complete, remarks
        FROM aircraft_movements
        WHERE {where}
        ORDER BY movement_date DESC, on_block_time DESC
        LIMIT :limit OFFSET :offset
    """, params)
    return pd.DataFrame(rows) if rows else pd.DataFrame()


# Render editable table
st.subheader("Aircraft Movements")
df = load_movements(filters, page)

# Store original for change detection
if 'original_df' not in st.session_state:
    st.session_state['original_df'] = df.copy()

edited_df = st.data_editor(
    df,
    num_rows="dynamic",  # Allows adding rows
    use_container_width=True,
    key="movements_editor",
    column_config={
        "id": st.column_config.NumberColumn("ID", disabled=True),
        "movement_date": st.column_config.DateColumn("Date"),
        "is_ron": st.column_config.CheckboxColumn("RON"),
        "ron_complete": st.column_config.CheckboxColumn("RON Done"),
        "category": st.column_config.SelectboxColumn("Category",
            options=["Commercial", "Cargo", "Charter"]),
    },
    hide_index=True,
)

if st.button("💾 Save All Changes"):
    save_changes(df, edited_df)
```

### Save logic (batch upsert)

```python
def save_changes(original: pd.DataFrame, edited: pd.DataFrame):
    original_ids = set(original['id'].dropna().astype(int).tolist())
    
    for _, row in edited.iterrows():
        row_id = row.get('id')
        if pd.isna(row_id) or row_id == '':
            # New row — INSERT
            run_write("""
                INSERT INTO aircraft_movements
                (movement_date, registration, aircraft_type, parking_stand,
                 on_block_time, off_block_time, flight_no_arr, flight_no_dep,
                 from_location, to_location, operator_airline, category, is_ron, remarks)
                VALUES (:date, :reg, :type, :stand, :on, :off, :arr, :dep,
                        :from, :to, :airline, :cat, :ron, :remarks)
            """, {
                'date': row.get('movement_date'), 'reg': row.get('registration'),
                'type': row.get('aircraft_type'), 'stand': row.get('parking_stand'),
                'on': row.get('on_block_time'), 'off': row.get('off_block_time'),
                'arr': row.get('flight_no_arr'), 'dep': row.get('flight_no_dep'),
                'from': row.get('from_location'), 'to': row.get('to_location'),
                'airline': row.get('operator_airline'), 'cat': row.get('category'),
                'ron': bool(row.get('is_ron')), 'remarks': row.get('remarks'),
            })
        else:
            # Existing row — UPDATE
            run_write("""
                UPDATE aircraft_movements SET
                    movement_date = :date, registration = :reg, aircraft_type = :type,
                    parking_stand = :stand, on_block_time = :on, off_block_time = :off,
                    flight_no_arr = :arr, flight_no_dep = :dep,
                    from_location = :from, to_location = :to,
                    operator_airline = :airline, category = :cat,
                    is_ron = :ron, remarks = :remarks
                WHERE id = :id
            """, {'id': int(row_id), 'date': row.get('movement_date'), ...})
    
    st.success("All changes saved.")
    st.rerun()
```

> ⚠️ **data_editor gotcha**: When `num_rows="dynamic"`, new rows have `None` as the `id`. Deleted rows are not tracked by the widget — you need to compare sets of IDs to detect deletions if needed.

---

## 8. Dashboard — `pages/3_Dashboard.py`

### Stand Usage Gantt Chart (Plotly)

```python
import plotly.express as px
import pandas as pd
from services.db_service import run_query

def render_gantt(date: str):
    rows = run_query("""
        SELECT parking_stand AS stand, on_block_time AS start, off_block_time AS finish,
               registration, category
        FROM aircraft_movements
        WHERE movement_date = :date
          AND parking_stand IS NOT NULL AND parking_stand != ''
          AND on_block_time IS NOT NULL AND on_block_time != ''
        ORDER BY parking_stand
    """, {'date': date})

    if not rows:
        st.info("No movement data for selected date.")
        return

    df = pd.DataFrame(rows)
    # Convert HHMM time strings to datetime for Plotly
    base = pd.Timestamp(date)
    df['Start'] = df['start'].apply(lambda t: parse_time(base, t))
    df['Finish'] = df['finish'].apply(lambda t: parse_time(base, t) if t else base + pd.Timedelta(hours=23, minutes=59))

    color_map = {'Commercial': '#38bdf8', 'Cargo': '#fb923c', 'Charter': '#a78bfa'}
    fig = px.timeline(df, x_start='Start', x_end='Finish', y='stand',
                      color='category', color_discrete_map=color_map,
                      hover_data=['registration'], title=f'Stand Usage — {date}')
    fig.update_yaxes(autorange="reversed")
    fig.update_layout(
        plot_bgcolor='#1e293b', paper_bgcolor='#0f172a',
        font_color='#e2e8f0', height=600,
    )
    st.plotly_chart(fig, use_container_width=True)

def parse_time(base: pd.Timestamp, t: str) -> pd.Timestamp:
    """Parse HHMM or HH:MM or 'HHMM (date)' format into Timestamp."""
    import re
    t = re.sub(r'\s*\(.*\)', '', str(t or '')).strip()
    t = t.replace(':', '')
    if len(t) >= 4 and t[:4].isdigit():
        h, m = int(t[:2]), int(t[2:4])
        return base + pd.Timedelta(hours=h, minutes=m)
    return base
```

### Snapshot Manager

```python
def render_snapshot_manager():
    st.subheader("📸 Daily Snapshot Archive")
    
    col1, col2 = st.columns([2, 1])
    with col1:
        snap_date = st.date_input("Snapshot Date", value=pd.Timestamp.today())
    with col2:
        if st.button("Create Snapshot", type="primary"):
            create_snapshot(str(snap_date))

    # List snapshots
    snaps = run_query("""
        SELECT ds.id, ds.snapshot_date, u.username AS created_by, ds.created_at
        FROM daily_snapshots ds
        LEFT JOIN users u ON ds.created_by_user_id = u.id
        ORDER BY ds.snapshot_date DESC LIMIT 20
    """)
    if snaps:
        df = pd.DataFrame(snaps)
        st.dataframe(df[['id', 'snapshot_date', 'created_by', 'created_at']],
                     use_container_width=True, hide_index=True)

def create_snapshot(date: str):
    # Collect data and upsert
    movements = run_query("SELECT * FROM aircraft_movements WHERE movement_date = :d", {'d': date})
    import json
    data = json.dumps({'movements': movements, 'snapshot_date': date})
    run_write("""
        INSERT INTO daily_snapshots (snapshot_date, snapshot_data, created_by_user_id)
        VALUES (:date, :data, (SELECT id FROM users WHERE username = :u LIMIT 1))
        ON CONFLICT (snapshot_date) DO UPDATE SET snapshot_data = :data
    """, {'date': date, 'data': data, 'u': st.session_state.get('username')})
    st.success(f"Snapshot for {date} saved.")
```

---

## 9. Admin Page — `pages/4_Admin.py`

### User management (full CRUD)

```python
def render_user_list():
    users = run_query("""
        SELECT id, username, full_name, email, role, status, created_at
        FROM users ORDER BY created_at DESC
    """)
    df = pd.DataFrame(users)
    
    # Filter controls
    col1, col2, col3 = st.columns(3)
    role_filter = col1.selectbox("Role", ["All", "admin", "operator", "viewer"])
    status_filter = col2.selectbox("Status", ["All", "active", "suspended"])
    search = col3.text_input("Search username/name")
    
    if role_filter != "All":
        df = df[df['role'] == role_filter]
    if status_filter != "All":
        df = df[df['status'] == status_filter]
    if search:
        df = df[df['username'].str.contains(search, case=False) |
                df['full_name'].str.contains(search, case=False)]
    
    st.dataframe(df, use_container_width=True, hide_index=True)

def create_user(username, email, full_name, role, password):
    """Last-admin guard + duplicate check."""
    if run_query("SELECT id FROM users WHERE username = :u", {'u': username}):
        st.error("Username already exists.")
        return
    import bcrypt
    hashed = bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()
    run_write("""
        INSERT INTO users (username, email, full_name, role, status, password_hash)
        VALUES (:u, :e, :n, :r, 'active', :h)
    """, {'u': username, 'e': email, 'n': full_name, 'r': role, 'h': hashed})
    st.success(f"User '{username}' created.")

def guard_last_admin(user_id: int, current_role: str):
    """Prevents removing the last admin. Call before any role change or delete."""
    if current_role != 'admin':
        return True
    count = run_query("""
        SELECT COUNT(*) AS cnt FROM users
        WHERE role = 'admin' AND status = 'active' AND id != :id
    """, {'id': user_id})
    if count[0]['cnt'] < 1:
        st.error("Cannot modify the last active administrator account.")
        return False
    return True
```

---

## 10. Audit Service — `services/audit_service.py`

```python
from services.db_service import run_write
import json

def log_action(username: str, action: str, table_name: str,
               record_id=None, changes: dict = None):
    """
    Silent audit logger. Fails silently if table doesn't exist.
    IP is always 'streamlit-cloud' (Streamlit server IP, not end user).
    """
    try:
        user_rows = run_query("SELECT id FROM users WHERE username = :u LIMIT 1",
                              {'u': username})
        user_id = user_rows[0]['id'] if user_rows else None
        run_write("""
            INSERT INTO audit_log (user_id, action, table_name, record_id, changes, ip_address)
            VALUES (:uid, :action, :table, :rid, :changes, 'streamlit-cloud')
        """, {
            'uid': user_id, 'action': action, 'table': table_name,
            'rid': record_id, 'changes': json.dumps(changes or {}),
        })
    except Exception:
        pass  # Never let audit logging crash the app
```

---

## 11. Theme — `.streamlit/config.toml`

```toml
[theme]
base = "dark"
primaryColor = "#38bdf8"
backgroundColor = "#0f172a"
secondaryBackgroundColor = "#1e293b"
textColor = "#e2e8f0"
font = "sans serif"
```

### Custom CSS injection (put in `streamlit_app.py` after authentication)

```python
st.markdown("""
<style>
/* Nav sidebar styling */
[data-testid="stSidebar"] {
    background-color: #0f172a !important;
    border-right: 1px solid #1e3a5f;
}
/* Metric card styling */
[data-testid="stMetric"] {
    background-color: #1e293b;
    border: 1px solid #334155;
    border-radius: 8px;
    padding: 16px;
}
/* Data editor header */
[data-testid="stDataFrameResizable"] thead {
    background-color: #1e3a5f !important;
}
/* Primary buttons */
.stButton > button[kind="primary"] {
    background: linear-gradient(135deg, #1e3a5f, #38bdf8);
    border: none;
    color: white;
    font-weight: 700;
    letter-spacing: 0.5px;
}
</style>
""", unsafe_allow_html=True)
```

---

## 12. Known Gotchas & Testing Checklist

### Gotchas

| Area | Gotcha | Fix |
|---|---|---|
| `streamlit-authenticator` | `role` field not auto-extracted | Read from `config['credentials']['usernames'][username]['role']` manually |
| Neon cold start | First query after sleep takes 1-2s | `pool_pre_ping=True` in engine, show spinner on first load |
| `st.data_editor` | New rows have `id = None`, not `id = 0` | Use `pd.isna(row_id) or row_id == ''` to detect new rows |
| PostgreSQL booleans | `is_ron = 1` invalid in PostgreSQL | Use `is_ron = TRUE` in all SQL |
| `RETURNING id` | MySQL `LAST_INSERT_ID()` doesn't exist in PostgreSQL | Append `RETURNING id` to all INSERT statements |
| `ON CONFLICT` | MySQL `ON DUPLICATE KEY UPDATE` doesn't exist | Use `ON CONFLICT (col) DO UPDATE SET ...` |
| `predict.py` import | It reads from `sys.stdin` when run as script | Import as module, only call specific functions |
| `@st.cache_resource` | If model file changes, cache is stale | Add a button in admin to clear cache: `st.cache_resource.clear()` |
| `components.html` height | If too small, map is cut off | Start with `height=650`, adjust after seeing real render |
| Date format | PHP stores `movement_date` as `Y-m-d` | Neon stores as `date` type — Python returns `datetime.date`, not string |
| Time format | `on_block_time` is stored as a string (e.g., `"0730"` or `"07:30"`) | It is NOT a proper `TIME` column — treat as `VARCHAR` in PostgreSQL |

### Page-by-page testing checklist

**Login page:**
- [ ] All 3 users can log in with correct passwords
- [ ] Wrong password shows error, not crash
- [ ] Session persists across page navigation

**Apron Map:**
- [ ] Map renders without blank white iframe
- [ ] Occupied stands show red
- [ ] RON stands show amber
- [ ] Available stands show navy
- [ ] View A / View B toggle works
- [ ] RON carryover fires once per session
- [ ] Viewer role cannot see Save Roster button

**ML Recommendation:**
- [ ] Registration lookup fills airline + category
- [ ] Flight number lookup fills origin/destination
- [ ] "Get Recommendation" returns 3 stands
- [ ] Occupied stands are excluded from results
- [ ] A0 not recommended for non-small aircraft
- [ ] Prediction saved to `ml_prediction_log`
- [ ] Saving a movement marks `was_prediction_correct`

**Master Table:**
- [ ] Loads today's movements by default
- [ ] Date range filter works
- [ ] Category/airline/flight_no filter works
- [ ] Inline edit works
- [ ] Save All Changes persists to Neon
- [ ] New row can be added
- [ ] RON sub-table is separate from main table
- [ ] "Set RON" button works

**Dashboard:**
- [ ] Metrics load without error on empty day
- [ ] Hourly chart renders
- [ ] Category breakdown renders
- [ ] Stand Usage Gantt renders for selected date
- [ ] CSV export downloads
- [ ] Monthly Charter Report generates
- [ ] Snapshot Archive opens
- [ ] Create Snapshot works

**Admin (admin user only):**
- [ ] User list visible
- [ ] Create user works
- [ ] Reset password works
- [ ] Last-admin guard prevents removing last admin
- [ ] Aircraft Details form saves to DB
- [ ] Flight Reference form saves to DB
- [ ] Operator user cannot see Admin page
- [ ] Viewer user cannot see Admin page

---

## 13. Database Migration Quick Reference

### Step 1 — Convert `amc.sql` before importing to Neon

Run these sed-style replacements on the SQL file:
1. Remove `ENGINE=InnoDB ...;` from end of each `CREATE TABLE`
2. Replace `AUTO_INCREMENT` → `GENERATED ALWAYS AS IDENTITY`
3. Replace `tinyint(1)` → `BOOLEAN`
4. Replace `int(11)` → `INTEGER`
5. Replace `` `column` `` → `"column"` (backticks → double quotes)
6. Replace `current_timestamp()` → `CURRENT_TIMESTAMP`
7. Replace `ON UPDATE CURRENT_TIMESTAMP` → *(remove — handled in app layer)*
8. Replace `ENUM('a','b')` → `VARCHAR(20) CHECK (col IN ('a','b'))`
9. Replace `INSERT INTO ... VALUES (1,...)` bool values: replace `,0,` and `,1,` where they're boolean fields

### Step 2 — Add `UNIQUE` constraint for `ON CONFLICT` to work

```sql
-- For daily_snapshots upsert
ALTER TABLE daily_snapshots ADD CONSTRAINT uq_snapshot_date UNIQUE (snapshot_date);

-- For airline_preferences upsert (if applicable)
-- Check existing constraints first
```

### Step 3 — Verify tables created

```sql
SELECT table_name FROM information_schema.tables WHERE table_schema = 'public';
```

Expected: 12 tables matching the schema in `docs/DATABASE.md`.
