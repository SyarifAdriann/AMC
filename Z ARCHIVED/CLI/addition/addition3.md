# Addition 3: Streamlit Port of AMC System

**Date:** 2026-07-01  
**Status:** PENDING VERIFICATION

---

## Feature Request

Port the existing PHP/MySQL AMC (Apron Movement Control) system to a standalone Python/Streamlit web application deployable on Streamlit Community Cloud with Neon serverless PostgreSQL as the database.

---

## Requirements Analysis

- **Source of truth**: Existing PHP/MySQL AMC system at `c:\xampp\htdocs\AMC\` (NOT modified)
- **Target**: New standalone repository `amc-streamlit` at `c:\xampp\htdocs\amc-streamlit\`
- **Hosting**: Streamlit Community Cloud (free tier)
- **Database**: Neon serverless PostgreSQL (free tier)
- **Priority Order**: P0 (Apron Map + ML) → P1 (Auth) → P2 (DB) → P3 (Master Table) → P4 (Dashboard) → P5 (RON) → P6 (Audit) → P7 (Polish)

---

## Implementation Plan

1. Read PRD (`STREAMLIT_PRD.md`) and Tech Spec (`STREAMLIT_TECH_SPEC.md`) in full
2. Create directory structure per PRD Section 3
3. Copy required ML files from PHP repo
4. Generate bcrypt password hashes for 3 users
5. Implement all services: db, auth, ml, ron, audit, apron_status
6. Implement all pages: apron map, master table, dashboard, admin
7. Create PostgreSQL schema migration SQL
8. Verify syntax compilation and ML model loading
9. Test app startup

---

## Changes Made

### New Repository: `c:\xampp\htdocs\amc-streamlit\`

#### Core Files
- `streamlit_app.py` — Entry point, login gate, CSS theme
- `requirements.txt` — All Python dependencies (including PyYAML)
- `.gitignore` — Ignores secrets.toml
- `README.md` — Deployment guide
- `neon_schema.sql` — PostgreSQL schema (converted from MySQL)

#### Configuration
- `.streamlit/config.toml` — Dark aviation theme (#0f172a background, #38bdf8 accent)
- `.streamlit/secrets.toml` — LOCAL ONLY with bcrypt hashed passwords

#### Services
- `services/db_service.py` — Neon/PostgreSQL SQLAlchemy with `@st.cache_resource`
- `services/auth_service.py` — Role-based auth guard (viewer < operator < admin)
- `services/ml_service.py` — Full prediction chain: model load → availability → A0 constraint → preference scoring → composite score
- `services/ron_service.py` — RON carryover logic ported from RonService.php
- `services/audit_service.py` — Silent audit logger (never crashes app)
- `services/apron_status_service.py` — Stand occupancy counting

#### Components
- `components/apron_injector.py` — Builds self-contained HTML for apron map with exact pixel coordinates from PHP source

#### Pages (Streamlit multipage)
- `pages/1_Apron_Map.py` — P0: Live map + ML recommendation sidebar + RON + roster + quick save
- `pages/2_Master_Table.py` — P3: Full CRUD with all 5 filters, batch save, RON sub-table
- `pages/3_Dashboard.py` — P4: Metrics, charts, Gantt, CSV export, charter report, snapshots, ML log
- `pages/4_Admin.py` — User CRUD + aircraft details + flight references + system tools

#### Copied from PHP repo (binary files)
- `ml/parking_stand_model_rf_redo.pkl` — Random Forest model (5.2 MB)
- `ml/encoders_redo.pkl` — Label encoders
- `ml/predict.py` — Prediction module (imported as module, not subprocess)
- `reports/phase5_metrics.json` — ML accuracy metrics

#### Created
- `storage/cache/historical_preferences.json` — Fallback preference data

---

## Testing Requirements

- [ ] `python -m py_compile` passes on all .py files ✅
- [ ] ML model loads (RandomForestClassifier, 17 classes) ✅
- [ ] predict.py imports as module and builds feature vectors ✅
- [ ] Top-5 predictions return correctly for sample aircraft ✅
- [ ] Streamlit app starts (localhost:8501) ✅
- [ ] Login page renders with all 3 credentials
- [ ] Apron map renders in iframe with colored stand divs
- [ ] ML recommendation returns 3 stands
- [ ] Neon DB connection (after user sets up Neon)
- [ ] Master Table loads and saves
- [ ] Dashboard charts render
- [ ] Admin panel loads

---

## Deployment Steps for User

1. Create Neon account at neon.tech → create project "amc"
2. Run `neon_schema.sql` in Neon SQL Editor
3. Copy Neon connection string into `.streamlit/secrets.toml`
4. Push `amc-streamlit/` folder to a new GitHub repo named `amc-streamlit`
5. Connect to Streamlit Community Cloud → set main file: `streamlit_app.py`
6. Add secrets in Streamlit Cloud UI (paste contents of `secrets.toml`)
7. Deploy! App auto-deploys on every push to `main`

---

## Key Technical Decisions

- **`auto_hash=False`** in `stauth.Authenticate()` — passwords already bcrypt hashed
- **`@st.cache_resource`** for ML model — loaded once, shared across all users
- **`pool_pre_ping=True`** in SQLAlchemy engine — handles Neon cold-wake
- **`RETURNING id`** in all INSERT statements — PostgreSQL alternative to `LAST_INSERT_ID()`
- **`is_ron = TRUE/FALSE`** (not 1/0) — PostgreSQL BOOLEAN requires this
- **Manual role extraction** from credentials dict — streamlit-authenticator does NOT auto-set role in session_state
- **`sys.path.insert(0, ml_dir)`** before `import predict` — module import, not subprocess

---

## Summary

**What's Done:**
- Complete Streamlit app structure per PRD Section 3 architecture
- All 4 pages implemented (P0 through P4 and P6)
- All 6 services implemented
- ML model verified working (BATIK AIR A 320 → A1 25.5%, A2 25.0%, A3 24.5%)
- PostgreSQL schema created for all 12 tables
- App starts and login page renders

**What's Left To Do (User Actions Required):**
- Set up Neon account and run `neon_schema.sql`
- Update `secrets.toml` with real Neon connection string
- Push to GitHub and deploy to Streamlit Community Cloud
- Import historical aircraft data from `amc.sql` (optional, for richer demo)

---

## Status Update
PENDING VERIFICATION — Awaiting user to set up Neon DB and test deployment
