# AMC Streamlit Port — Product Requirements Document (PRD)

**Version:** 1.1  
**Date:** 2026-07-01  
**Status:** APPROVED — AWAITING IMPLEMENTATION  
**Primary Goal:** Host the AMC (Apron Movement Control) system on Streamlit Community Cloud as a live, interactive demo for academic defence and testing purposes.

---

## 1. Executive Summary

The AMC system is currently a PHP/MySQL web application running on a local XAMPP server. This PRD describes the requirements for porting it to a **standalone Streamlit Python application**, hosted for free on **Streamlit Community Cloud**, backed by a **Neon (serverless PostgreSQL)** database.

The Streamlit version is a **parallel build** — the existing PHP app is NOT touched. A new, self-contained repository will be created with everything the Streamlit app needs.

---

## 2. Confirmed Decisions (from Design Session)

| Decision | Choice |
|---|---|
| **Hosting Platform** | Streamlit Community Cloud (free tier) |
| **Database** | Neon — Serverless PostgreSQL (free, 500 MB, auto-wakes) |
| **Authentication** | `streamlit-authenticator` — username/password, 3 roles preserved |
| **Apron Map** | Exact visual replica embedded via `st.components.v1.html()` |
| **ML Prediction** | Real `.pkl` model loaded directly in Python (no subprocess) |
| **Master Table** | Full CRUD with `st.data_editor()` + Save to Neon |
| **Dashboard** | Full analytics (charts, metrics) using Streamlit native charts |
| **RON Logic** | Fully implemented in Python (same business rules as PHP) |
| **Audit Logging** | Silent background logging to `audit_log` table in Neon |
| **Code Location** | New standalone GitHub repository (duplicates all required files) |
| **Visual Design** | Dark theme matching PHP app — same colour palette, aviation feel |

---

## 3. System Architecture

```
New GitHub Repo: amc-streamlit/
├── streamlit_app.py              # Entry point + login gate
├── pages/
│   ├── 1_Apron_Map.py            # Apron map + ML recommendation sidebar
│   ├── 2_Master_Table.py         # Aircraft movements CRUD (main + RON sub-table)
│   ├── 3_Dashboard.py            # Analytics, charts, reports, snapshot manager
│   └── 4_Admin.py                # User management + aircraft/flight reference data (admin only)
├── ml/
│   ├── predict.py                # (copied from AMC/ml/predict.py — reused as-is)
│   ├── parking_stand_model_rf_redo.pkl  # (5.2 MB — copied)
│   └── encoders_redo.pkl                # (copied)
├── components/
│   ├── apron_map_a.html          # View A (stylized map) — extracted from PHP view
│   ├── apron_map_b.html          # View B (real-coords map) — extracted from PHP view
│   └── apron_injector.py         # Python helper that injects live aircraft data into HTML
├── services/
│   ├── auth_service.py           # Login, role management, session state
│   ├── db_service.py             # Neon/PostgreSQL connection (SQLAlchemy)
│   ├── ron_service.py            # RON carryover logic (ported from RonService.php)
│   ├── apron_status_service.py   # Stand occupancy counting
│   ├── audit_service.py          # Silent audit logging
│   ├── ml_service.py             # ML model loader + predictor + airline preferences
│   ├── snapshot_service.py       # Daily snapshot create/list/view/delete
│   └── user_admin_service.py     # User CRUD with last-admin guards
├── storage/
│   └── cache/
│       └── historical_preferences.json  # (copied — used by ML preference scoring)
├── reports/
│   └── phase5_metrics.json       # (copied — model top-3 accuracy data)
├── .streamlit/
│   ├── config.toml               # Theme configuration (dark, aviation colours)
│   └── secrets.toml              # LOCAL ONLY — gitignored (DB credentials + auth)
├── requirements.txt              # All Python dependencies
└── README.md                     # Deployment guide
```

---

## 4. Feature Specifications

### 4.1 Authentication (streamlit-authenticator)

**Library:** `streamlit-authenticator >= 0.3`

**Roles and Access Matrix:**

| Page | admin | operator | viewer |
|---|---|---|---|
| Apron Map (view) | ✅ | ✅ | ✅ |
| ML Recommendation Sidebar | ✅ | ✅ | ✅ |
| Master Table (view) | ✅ | ✅ | ❌ |
| Master Table (edit/save) | ✅ | ✅ | ❌ |
| Dashboard | ✅ | ✅ | ❌ |
| Admin (user management) | ✅ | ❌ | ❌ |

**Implementation Notes:**
- Credentials (hashed passwords + roles) stored in Streamlit Secrets Manager (not in GitHub)
- `st.session_state` carries `authenticated`, `username`, `role` across all pages
- Unauthenticated users see only the login page; all other routes redirect to login
- No brute-force lockout (known limitation — acceptable for academic demo context)
- Same user accounts as the PHP system can be replicated (admin, operator, viewer)

**Secrets structure (`.streamlit/secrets.toml` — gitignored):**

> ⚠️ Passwords below are stored as bcrypt hashes in the actual file. Plaintext shown here for reference only — never commit plaintext passwords to Git.

```toml
# Login: admin / supervisor
[credentials.usernames.admin]
name = "admin"
password = "$2b$12$<bcrypt-hash-of-supervisor>"
role = "admin"

# Login: AMC / mikecharlie
[credentials.usernames.AMC]
name = "AMC"
password = "$2b$12$<bcrypt-hash-of-mikecharlie>"
role = "operator"

# Login: ATC / tangowhiskey
[credentials.usernames.ATC]
name = "ATC"
password = "$2b$12$<bcrypt-hash-of-tangowhiskey>"
role = "viewer"

[cookie]
expiry_days = 1
key = "amc_auth_key"
name = "amc_session"

[database]
url = "postgresql://user:password@ep-xxx.neon.tech/amc?sslmode=require"
```

---

### 4.2 Database — Neon PostgreSQL

**Provider:** Neon (neon.tech) — Free tier  
**Storage:** 500 MB (AMC data is ~210 KB SQL dump — well within limits)  
**Connection:** SQLAlchemy via `st.connection()` with `psycopg2`

**Migration Plan (MySQL → PostgreSQL):**

The `amc.sql` dump requires these conversions before importing to Neon:

| MySQL Syntax | PostgreSQL Equivalent |
|---|---|
| `ENGINE=InnoDB` | Remove (not needed) |
| `AUTO_INCREMENT` | `SERIAL` or `GENERATED ALWAYS AS IDENTITY` |
| `tinyint(1)` for booleans | `BOOLEAN` |
| `current_timestamp()` | `CURRENT_TIMESTAMP` |
| `ON UPDATE current_timestamp()` | Trigger or handled in application layer |
| `` `backtick` `` identifiers | `"double_quote"` identifiers |
| `ENUM('a','b')` | `VARCHAR(20) CHECK (col IN ('a','b'))` |

**Tables to migrate (all 12 from the PHP system):**
- `aircraft_movements` (core — most critical)
- `aircraft_details`
- `stands`
- `daily_staff_roster`
- `flight_references`
- `airline_preferences`
- `ml_prediction_log`
- `ml_model_versions`
- `audit_log`
- `login_attempts` *(kept for schema completeness, no brute-force logic)*
- `users` *(schema only — credentials managed via streamlit-authenticator)*
- `daily_snapshots`

**Connection pooling:** Use `@st.cache_resource` to cache the SQLAlchemy engine across reruns to avoid connection overhead.

---

### 4.3 Apron Map (NON-NEGOTIABLE visual fidelity)

**Approach:** Embed the existing apron HTML/JS/CSS directly using `st.components.v1.html()`

**The Apron Map component works in two parts:**

**Part 1 — Static HTML Shell (extracted from `resources/views/apron/index.php`)**
- The SVG stand layouts (View A stylized, View B real coords) are extracted into `components/apron_map_a.html` and `apron_map_b.html`
- These contain all the stand positions, labels, colour logic, and CSS exactly as they exist in the PHP version

**Part 2 — Dynamic Data Injection (Python-side)**
- `apron_injector.py` queries Neon for today's active aircraft movements
- It programmatically injects aircraft registration labels, colours (occupied/available/RON), and stand status into the HTML template before passing it to `st.components.v1.html()`
- This replicates what PHP's `foreach` loop currently does in the view

**Map Interaction Limitation (known, accepted):**
- The map lives inside an iframe — clicking a stand cannot auto-populate the ML recommendation sidebar
- The ML recommendation sidebar uses Streamlit native widgets (`st.selectbox`, `st.text_input`) — the user manually inputs aircraft details
- This is visually and functionally equivalent for the demo panel (they input → click → get recommendation)

**View Toggle (A/B):**
- `st.radio` or `st.tabs` widget lets the user switch between View A and View B
- Re-renders the appropriate HTML component

**Auto-refresh:**
- `st.rerun()` can be triggered on a timer or a manual "Refresh Map" button can be provided

**Apron Map — Additional Sub-features (found in code review):**
- **Hangar records panel** — separate section showing aircraft currently in the hangar (`findHangarMovements()`), displayed alongside the map
- **Daily Roster display** — shows on-duty staff (supervisor + operators) for the current date, pulled from `daily_staff_roster` table
- **Save Roster** — form to save/update the day/night shift staff roster (admin/operator only)
- **Save Movement (from apron)** — quick-save a movement directly from the apron view (used when confirming a stand recommendation → saves to DB + updates `ml_prediction_log` with `actual_stand_assigned` and `was_prediction_correct`)
- **Flight route lookup** — when a flight number is typed, look up `flight_references` table and auto-fill origin/destination
- **Trigger RON** — "Set RON" button on the apron view flags all open (no off-block time) movements as RON for the day

---

### 4.4 ML Prediction Engine

**Approach:** Load `.pkl` files directly in Python — no subprocess, no `proc_open`

**Implementation:**
```python
# ml_service.py
import pickle
import streamlit as st

@st.cache_resource  # Load model ONCE, reuse across all sessions
def load_model():
    with open('ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
        model = pickle.load(f)
    with open('ml/encoders_redo.pkl', 'rb') as f:
        encoders = pickle.load(f)
    return model, encoders
```

**The `build_feature_vector()`, `determine_aircraft_size()`, `determine_airline_tier()`, `get_stand_zone()` functions** from `predict.py` are imported directly (or copied into `ml_service.py`).

**Business Rules (applyBusinessRules equivalent) — full chain found in code:**
1. Get ML top-K predictions (`predict_proba`)
2. Query Neon for currently occupied stands
3. Filter out occupied stands
4. Apply `A0-only-for-small-aircraft` constraint
5. Apply **airline preference scoring** — query `airline_preferences` table for stand priority scores; if empty, fall back to historical frequency from `aircraft_movements`; if still empty, use availability-based fallback scores
6. **Composite scoring** — `score = (0.6 × ML_probability) + (0.4 × normalized_preference_score)`
7. Rank by composite score; if fewer than 3 candidates, pad with additional available stands
8. Return exactly top 3 ranked stands
9. Record prediction to `ml_prediction_log` with a unique token
10. When operator confirms a stand (saves movement), mark `was_prediction_correct` in the log

**UI — ML Recommendation Sidebar:**
- `st.text_input("Registration")` — triggers aircraft lookup from `aircraft_details` table
- `st.selectbox("Category")` — COMMERCIAL / CARGO / CHARTER
- `st.text_input("Airline")` — auto-populated from registration lookup
- `st.button("Get Recommendation")` — triggers prediction
- Results displayed as styled `st.metric` cards or a highlighted `st.dataframe` showing:
  - Rank 1, 2, 3
  - Stand name
  - Probability percentage
  - Availability status (✅ Available / ❌ Occupied)

---

### 4.5 Master Table (CRUD)

**Streamlit Widget:** `st.data_editor()` (native editable dataframe)

**Features to implement:**

| Feature | PHP Implementation | Streamlit Implementation |
|---|---|---|
| Paginated list of movements | PHP pagination + SQL LIMIT/OFFSET | `st.data_editor` with filtered view + page buttons |
| Add new row | DOM template generation (JS) | `st.data_editor(num_rows="dynamic")` |
| Edit existing row inline | Contenteditable cells (JS) | `st.data_editor` inline editing |
| Bulk Save All | JS tracks changes + single POST | "💾 Save All Changes" button → batch upsert to Neon |
| Duplicate flight detection | PHP pre-check | Python pre-check before render |
| Date filter | PHP form | `st.date_input` filter widget |
| Registration autofill (airline + category) | AJAX fires on each keystroke | `st.selectbox` (searchable dropdown) + `on_change` callback — same result, different trigger |

**Save logic:**
```python
# On "Save All" click:
# 1. Get all rows from data_editor session state
# 2. Identify new rows (no `id`), updated rows (id exists + row changed)
# 3. Run batch INSERT / UPDATE in a single transaction on Neon
# 4. Show success/error toast
```

---

### 4.6 Dashboard Analytics

**Data source:** Live queries to Neon (`aircraft_movements` + `aircraft_details` tables)

**Components to build:**

| Component | Streamlit Widget |
|---|---|
| Total movements today | `st.metric()` |
| Occupied / Available / RON counts | `st.metric()` x3 |
| Category breakdown (Commercial/Cargo/Charter) | `st.bar_chart()` or `st.plotly_chart()` |
| Hourly movement traffic chart | `st.line_chart()` |
| **Stand Usage Gantt chart** (which stand was occupied when) | `st.plotly_chart()` with Gantt timeline — date-selectable |
| Report generator (date range + type selector) | `st.form()` + `st.selectbox()` |
| **Monthly Charter Report** (month/year picker → HTML table) | `st.form()` + rendered `st.dataframe()` |
| CSV export | `st.download_button()` |
| **Daily Snapshot Archive** (create, list, view, delete) | `st.expander()` panel — admin/operator only |
| **Aircraft Details management** (add/edit registration, type, airline, category, notes) | `st.form()` — admin/operator only |
| **Flight Reference management** (add/edit flight number → route) | `st.form()` — admin/operator only |

---

### 4.7 RON (Remain Over Night) Logic

**Port from:** `app/Services/RonService.php`

**Logic to replicate in `services/ron_service.py`:**

1. **RON Detection:** Query movements where `is_ron = TRUE` and `ron_complete = FALSE` for yesterday's date
2. **Carryover:** For each RON aircraft, check if a movement record already exists for today's date (prevent duplicates)
3. **Auto-create:** If no record exists for today, insert a new `aircraft_movements` row with today's `movement_date`, same `registration`, `parking_stand`, `is_ron = TRUE`, `ron_complete = FALSE`
4. **Trigger:** RON processing runs automatically when the app starts or when the user navigates to the Apron Map or Master Table (checked once per session via `st.session_state`)
5. **Master Table RON sub-table:** The Master Table page has two separate paginated sections — the main movements table AND a dedicated RON completed movements table (`paginateCompletedRonMovements()`). Both must be present.
6. **Set RON action:** "Set RON" button manually flags all currently open (no off-block time) movements as RON for the day

---

### 4.8 Audit Logging

**Port from:** `app/Services/AuditLogger.php`

**Implementation in `services/audit_service.py`:**
```python
def log_action(db, user_id, action, table_name, record_id, changes, ip_address=None):
    db.execute(
        "INSERT INTO audit_log (user_id, action, table_name, record_id, changes, ip_address) VALUES (:u, :a, :t, :r, :c, :ip)",
        {"u": user_id, "a": action, "t": table_name, "r": record_id, "c": json.dumps(changes), "ip": ip_address}
    )
```

**Logged events (mapped from codebase):**
- User login / logout
- Aircraft movement created / updated / deleted
- Snapshot created / deleted (`UPSERT_SNAPSHOT`, `DELETE_SNAPSHOT`)
- User created / updated / deleted / password reset / status changed (`CREATE_USER`, `UPDATE_USER`, `DELETE_USER`, `RESET_PASSWORD`, `SET_STATUS`)

**Note:** IP address in Streamlit Community Cloud will reflect the Streamlit server IP, not the end user's IP. This is a known limitation — log it as "streamlit-cloud" or leave null.

---

## 5. Visual Design

**Theme configuration (`.streamlit/config.toml`):**
```toml
[theme]
base = "dark"
primaryColor = "#38bdf8"              # Same sky-blue/teal accent as PHP app
backgroundColor = "#0f172a"           # Deep navy (same as PHP dark bg)
secondaryBackgroundColor = "#1e293b"  # Slightly lighter panels
textColor = "#e2e8f0"                 # Off-white text
font = "sans serif"
```

**Custom CSS injections** (via `st.markdown(unsafe_allow_html=True)`) will be used to:
- Style the navigation sidebar
- Style the ML recommendation results cards
- Apply consistent card/panel look to metrics

---

## 6. Repository Structure

**New repo name (suggested):** `amc-streamlit`

**Files duplicated from the PHP repo:**
- `ml/parking_stand_model_rf_redo.pkl`
- `ml/encoders_redo.pkl`
- `ml/predict.py` (used as a module import, not subprocess)
- Apron HTML/JS layout (extracted from `resources/views/apron/index.php`)
- `DATASET AMC .csv` (if needed for dashboard historical data)

**NOT copied:** PHP files, Apache configs, `.htaccess`, Tailwind config, `node_modules`, `package.json`

---

## 7. Python Dependencies (`requirements.txt`)

```
streamlit>=1.35.0
streamlit-authenticator>=0.3.3
sqlalchemy>=2.0
psycopg2-binary>=2.9
pandas>=2.0
numpy>=1.26
scikit-learn>=1.4         # Required to load .pkl Random Forest model
plotly>=5.20              # For dashboard charts
bcrypt>=4.1               # For password hashing (streamlit-authenticator dep)
```

**Total estimated memory usage on Streamlit Community Cloud:**
- Python runtime: ~100 MB
- scikit-learn + numpy + pandas: ~350 MB
- RF model in memory: ~50 MB (5.2 MB on disk expands ~10x)
- Available headroom: ~500 MB (within 1 GB free tier limit ✅)

---

## 8. Deployment Steps (High Level)

1. **Create Neon account** → Create a new project → Copy connection string
2. **Migrate database** → Convert `amc.sql` to PostgreSQL syntax → Import via Neon SQL editor
3. **Create new GitHub repo** → Push all Streamlit files
4. **Connect to Streamlit Community Cloud** → Link to the GitHub repo
5. **Configure Streamlit Secrets** → Paste DB connection string + user credentials into the Secrets Manager UI
6. **Deploy** → Streamlit auto-deploys on push to `main`

---

## 9. Known Limitations & Accepted Tradeoffs

| Limitation | Impact | Accepted? |
|---|---|---|
| No brute-force login protection | Security downgrade vs PHP | ✅ Yes (academic demo context) |
| Apron map inside iframe — stand clicks don't auto-fill sidebar | Minor UX friction | ✅ Yes (user inputs manually) |
| Streamlit app "sleeps" after inactivity (cold start ~5s) | Demo warmup needed | ✅ Yes (open app 30s before demo) |
| Neon DB sleeps but auto-wakes | First query ~1-2s slower | ✅ Yes (acceptable) |
| No real-time AJAX-style updates | Apron map needs manual refresh or timed rerun | ✅ Yes (Refresh button acceptable) |
| Audit IP will show Streamlit server IP, not user IP | Reduced audit precision | ✅ Yes (log as 'streamlit-cloud') |
| MySQL-specific features need conversion | Migration effort ~2-4 hours | ✅ Yes |

---

## 10. New Feature: 4_Admin Page (admin only)

This was underspecified in v1.0. The Admin page consolidates all admin-only management:

| Sub-feature | Source (PHP) | Streamlit Implementation |
|---|---|---|
| **User list** (filterable by role/status/search) | `UserController::list()` | `st.dataframe()` with filter widgets |
| **Create user** (username, email, full_name, role, password) | `UserController::create()` | `st.form()` |
| **Update user** (email, full_name, role, status) | `UserController::update()` | Inline edit form |
| **Reset password** | `UserController::resetPassword()` | `st.form()` |
| **Set status** (active/suspended) | `UserController::setStatus()` | `st.toggle()` |
| **Delete user** | `UserController::delete()` | `st.button()` with confirmation |
| **Guard: last admin protection** | `UserAdminService::guardLastAdmin()` | Python guard in `user_admin_service.py` |
| **Guard: no self-deletion/suspension** | `UserAdminService::guardDelete/Status()` | Python guard |
| **Aircraft Details management** | `DashboardController::handleManageAircraft()` | `st.form()` on Admin page |
| **Flight Reference management** | `DashboardController::handleManageFlightReference()` | `st.form()` on Admin page |

---

## 11. New Feature: ML Metrics & Prediction Log (on Dashboard)

Found in `ApronController::mlMetrics()` and `::mlPredictionLog()`:

| Sub-feature | Streamlit Implementation |
|---|---|
| **ML Model info panel** (version, training date, training samples, expected top-3 accuracy) | `st.metric()` cards |
| **Observed accuracy** (correct/total predictions in last 30 days) | `st.metric()` |
| **Prediction log table** (filterable: all/hit/miss/pending, searchable by aircraft/airline/category) | `st.dataframe()` with filter widgets |
| **Recent predictions** (last 5 entries) | `st.dataframe()` |

---

## 12. Master Table — Additional Filters Found in Code

`MasterTableController::collectFilters()` shows the table supports:
- `date_from` / `date_to` range filter
- `category` filter (Commercial/Cargo/Charter)
- `airline` filter
- `flight_no` filter

All five must be implemented as `st.sidebar` or top-of-page filter widgets on the Master Table page.

---

## 13. Out of Scope (Not Building)

- Narrative logbook (legacy table, not in active use)
- Daily snapshot cron job (manual snapshot button instead)
- Email notifications
- PDF report export (CSV only via `st.download_button`)
- `mobile-adaptations.js` responsive logic (Streamlit handles responsiveness natively)

---

## 11. Priority Order for Implementation

1. 🔴 **P0 — Apron Map + ML Prediction** (non-negotiable core)
2. 🟠 **P1 — Authentication (login + roles)**
3. 🟡 **P2 — Neon DB setup + migration**
4. 🟡 **P3 — Master Table (CRUD)**
5. 🟢 **P4 — Dashboard Analytics**
6. 🔵 **P5 — RON Logic**
7. 🔵 **P6 — Audit Logging**
8. ⚪ **P7 — Visual polish / theme**

---

*This document was produced from a structured design session. All decisions above were explicitly confirmed. No implementation has begun.*
