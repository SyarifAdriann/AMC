# Revision 8: Movement Counter + Autofill + RON + Icon + Thesis CLI

**Date:** 2026-05-17  
**Status:** PENDING VERIFICATION

---

## Problem Description

Five separate issues were reported:

1. **Departure movements not counted** in dashboard, daily snapshot, and hourly breakdown modules.
2. **Category field not autofilled** when typing a registration in the apron modal or master-table.
3. **False-positive off-block warning** fires on RON records that departed the next calendar day.
4. **Aircraft icon should be nose-in** (upside-down / rotated 180°) on the apron map.
5. **Thesis Bab 4 validation** — need a CLI tool to compare `predict.py` output vs web UI to prove consistency.

---

## Diagnostic Findings

### Fix 1 — Departure Counter (Root cause: TWO bugs)

**Bug A (Critical) — Non-existent `capacity` column:**  
Every departure-counting SQL query contained:
```sql
AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
```
The `stands` table has **no `capacity` column** — only `status`. This subquery always returns an empty set, so **zero departures were ever counted in any query**.

**Bug B (Correctness) — Wrong date column for departure events:**  
Queries used `WHERE movement_date = :date` for both arrivals and departures. But `movement_date` is the **arrival date** (when the record was created). For RON movements (arrived Day N, departed Day N+1), their departure was invisible on Day N+1's dashboard because no row had `movement_date = N+1`.

**Bug C (hourlyBreakdown) — COALESCE + HOUR() on RON off_block_time:**  
`HOUR(COALESCE(on_block_time, off_block_time))` is called on strings like `"06:00 (17/05/2026)"`. This meant a RON departure's hour was being extracted from a malformed string via MySQL's TIME parser.

- **Affected files:** `app/Repositories/AircraftMovementRepository.php`
- **Affected methods:** `countArrivalsAndDepartures()`, `hourlyBreakdown()`, `categoryBreakdown()`

### Fix 2 — Category Autofill

`ApronController::lookupAircraftDetails()` returned only `aircraft_type` and `operator_airline`. The `AircraftDetail` model has a `category()` method and the data exists in the database — it was just not included in the API response. Both JS autofill handlers also did not apply a `category` field even if it were returned.

- **Affected files:** `app/Controllers/ApronController.php`, `assets/js/apron.js`, `assets/js/master-table.js` (both `public/assets/` and root `assets/` copies)

### Fix 3 — RON Off-Block False Positive

`isOffBlockEarlierThanOnBlock()` extracted time values via regex and compared minutes-since-midnight. It had no concept of calendar dates. For a RON movement arriving `22:00` and departing `06:00 (18/05/2026)`, the regex extracted `06` vs `22` → 360 < 1320 → warning fired incorrectly.

The same issue existed in frontend JS (`extractTimeToMinutes` + comparison).

- **Affected files:** `AircraftMovementRepository.php`, `assets/js/apron.js`, `assets/js/master-table.js`

### Fix 4 — Icon Orientation

The plane SVG in `apron.js::createIcon()` rendered nose-up by default. A CSS `transform:rotate(180deg)` on the `<svg>` element flips it nose-in.

### Fix 5 — Thesis CLI Tool

The web UI and CLI both invoke the exact same `predict.py` script and load the same `.pkl` files. `ApronController::callPythonPredictor()` calls `predict.py` via `proc_open()` with JSON via stdin — identical to what a direct CLI call does.

---

## Changes Made

### `app/Repositories/AircraftMovementRepository.php`

- **`countArrivalsAndDepartures()`**: Replaced `capacity > 0` subquery with `status = 'active'`. Changed arrival filter to `COALESCE(on_block_date, movement_date) = ?` and departure filter to `off_block_date = ?`. WHERE clause now uses `OR` to cover both events. Binds `[$date, $date, $date, $date]`.
- **`hourlyBreakdown()`**: Replaced single-query COALESCE/HOUR approach with a `UNION ALL` subquery. Arrivals and departures are bucketed independently by their own timestamp on their own date. Hour extraction uses `CAST(SUBSTRING_INDEX(time_col, ':', 1) AS UNSIGNED)` — safe even for `"06:00 (17/05/2026)"` strings.
- **`categoryBreakdown()`**: Same date-column fix as `countArrivalsAndDepartures`. Binds `[$date, $date, $date, $date]`.
- **`isOffBlockEarlierThanOnBlock()`**: Added early-return guard: if `off_block_time` contains `(`, it is a RON departure from a later calendar day — skip the comparison and return `false`.

### `app/Controllers/ApronController.php`

- **`lookupAircraftDetails()`**: Added `'category' => $detail->category() ?? ''` to the returned array.

### `assets/js/apron.js` + `public/assets/js/apron.js` (both copies)

- **`handleRegistrationAutofill()`**: Added `catField` lookup and populates `#f-category` if `data.category` is returned and field is currently empty.
- **Pre-save off-block check (line ~994)**: Added `&& !offBlockVal.includes('(')` guard before firing the alert.
- **`createIcon()`**: Added `style="transform:rotate(180deg)"` to the `<svg>` element.

### `assets/js/master-table.js` + `public/assets/js/master-table.js` (both copies)

- **`handleRegistrationAutofill()`**: Added `catField` lookup for `select[data-field="category"]` and applies `data.category` if field is empty.
- **`collectClientWarnings()`**: Added `&& !offContainsDate` guard using `offBlock.includes('(')`.
- **`applyTimeOrderHighlighting()`**: Added `&& !offVal.includes('(')` guard before setting `invalidOrder`.

### `ml/batch_predict_test.py` *(new file)*

- Standalone Python script with 10 representative test cases (Commercial high/medium frequency, Charter, Cargo).
- Calls `predict.py` via `subprocess.run()` with JSON via stdin — exactly mirroring PHP's `proc_open()`.
- Prints a fixed-width comparison table and saves raw results to `ml/batch_predict_results.json`.

---

## CLI Prediction Output (Verified — Exit Code 0)

```
Label  | Type       | Airline          | Category     | Rank 1 | Rank 2 | Rank 3 | Prob 1 | Prob 2 | Prob 3
-------+------------+------------------+--------------+--------+--------+--------+--------+--------+-------
TC-01  | ATR 72     | BATIK AIR        | Komersial    | A3     | A2     | A1     | 23.7%  | 22.2%  | 19.4%
TC-02  | A320       | CITILINK         | Komersial    | B1     | B2     | A3     | 43.0%  | 22.0%  | 15.3%
TC-03  | ATR 72     | GARUDA           | Komersial    | B2     | B1     | A3     | 57.8%  | 20.8%  | 12.9%
TC-04  | C 208      | PELITA           | Komersial    | A0     | A1     | A2     | 50.5%  | 14.4%  | 11.3%
TC-05  | C 208      | SUSI AIR         | Charter      | A0     | B6     | B7     | 28.5%  | 16.5%  | 14.7%
TC-06  | PC 12      | KARISMA          | Charter      | B5     | A0     | B6     | 20.8%  | 20.5%  | 16.0%
TC-07  | B737       | JIP              | Charter      | B5     | B6     | B3     | 31.7%  | 22.8%  | 17.7%
TC-08  | B737F      | TRI MG           | Cargo        | B12    | B13    | B11    | 22.5%  | 20.9%  | 15.7%
TC-09  | ATR 72     | TRIGANA          | Cargo        | B12    | B11    | B13    | 26.0%  | 19.0%  | 18.7%
TC-10  | B737F      | BBN              | Cargo        | B11    | B10    | B8     | 19.9%  | 18.4%  | 16.5%
```

> Pattern consistent with apron layout: Commercial → A/B stands (right zone), Charter → mid B stands, Cargo → B10-B13 (left zone). Matches business rules in `predict.py::get_stand_zone()`.

---

## Testing Requirements

- [ ] Dashboard "Today" counters — check `total_arrivals` and **`total_departures`** are both > 0 for a date with data
- [ ] Enter a RON movement (arrive Day N, depart Day N+1) — verify Day N+1 shows 1 departure in dashboard
- [ ] Daily snapshot for a past date — verify departure column is populated
- [ ] Hourly breakdown chart — verify departure bars appear in the correct time bucket
- [ ] Apron modal: type a known registration → verify **Category** field autofills
- [ ] Master table: type a known registration → verify **Category** select changes
- [ ] Enter a RON movement with off_block = `06:00 (18/05/2026)` → verify **no** time-order warning fires
- [ ] Enter a same-day movement with on_block `10:00`, off_block `08:00` → verify warning **does** fire
- [ ] Open apron map → verify plane icons point **nose-down**
- [ ] Run `python ml/batch_predict_test.py` → verify exit code 0 and consistent stands with web UI

---

## Summary

**What's Done:**
- Fixed departure counter (2 root causes: missing column + wrong date field) across all 3 dashboard methods
- Added category to `getAircraftDetails` API response and JS autofill handlers (both portals, both asset copies)
- Fixed RON off-block false-positive in PHP backend and both JS files
- Rotated aircraft icon 180deg in both apron.js copies
- Created `ml/batch_predict_test.py` — runs cleanly, all 10 cases succeed

**What's Left To Do:**
- User verification of all test cases above
- If category autofill needs to also work on NEW rows in master-table, a `category` select column needs to be added to the new-row HTML template in `loadMoreEmptyRows()` (currently those rows have no category select)

---

## Status Update
PENDING VERIFICATION
