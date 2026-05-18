# Revision 11: Gantt Chart — Full Fix & Visual Polish

**Date:** 2026-05-17
**Status:** COMPLETED

---

## Problem Description
1. Gantt chart loaded but was stuck on "Loading…" indefinitely (init never fired)
2. Gantt only showed stands with movements ON the selected date — missed RON/currently-parked aircraft
3. Grid lines were too faint and all hours looked the same

---

## Diagnostic Findings

### Issue 1 — Gantt Init Not Firing
- **Root cause:** The Gantt IIFE registered `initGantt` via `document.readyState` check or
  `document.addEventListener('DOMContentLoaded', initGantt)` — neither fired in this environment
- **Evidence:** `gantt-container.innerHTML.length` = 94 (original static HTML comment, never cleared)
  and `gantt-status.textContent` = "Loading…" (static HTML default, `loadGantt` never ran)
- **Fix:** Exposed `initGantt` as `window._initGantt` from the Gantt IIFE, then called it from
  the **proven-working** main `DOMContentLoaded` listener (line ~1169) that already runs
  `refreshDashboardMetrics()` successfully

### Issue 2 — Missing RON/Currently-Parked Aircraft
- **Root cause:** `standUsage()` WHERE clause only matched:
  1. `on_block_date = selected_date` (arrived today)
  2. `off_block_date = selected_date` (departed today)
  - Missed aircraft that arrived on a PREVIOUS date and are still occupying the stand today
- **Fix:** Added Condition 3 to the WHERE clause in `AircraftMovementRepository::standUsage()`

### Issue 3 — Grid Lines / Visibility
- Minor cosmetic: all grid lines were the same opacity/weight, no hour zone distinction

---

## Changes Made

### `app/Repositories/AircraftMovementRepository.php` (lines 192–200)
- Added third OR condition to `standUsage()` WHERE clause:
  ```sql
  OR (
      COALESCE(am.on_block_date, am.movement_date) < ?   -- arrived before selected date
      AND am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON'
      AND (am.off_block_date IS NULL OR am.off_block_date = '' OR am.off_block_date > ?
           OR am.off_block_time IS NULL OR am.off_block_time = '')
  )
  ```
- Updated `$stmt->execute()` from 4 params to 6 params (added 2 × `$date` for new condition)
- Result: query now returns all stands occupied on the selected date (was 5, now 21 for today)

### `assets/js/dashboard.js` + `public/assets/js/dashboard.js`

**Init bridge (Gantt IIFE end):**
- Replaced broken `readyState` check with `window._initGantt = initGantt`

**Main DOMContentLoaded listener (line ~1372):**
- Added call to `window._initGantt()` inside the confirmed-working DCL listener

**renderGantt visual improvements:**
- Tick header border upgraded from `#cbd5e1` to `#94a3b8`
- Hours 00/06/12/18/24: bold, dark navy (`#1e3a5f`), left border tick mark
- Hours 02/04/08/10/14/16/20/22: smaller, muted grey
- Per-row zone shading: alternating slate/sky bands per 6-hour block (00–06, 06–12, 12–18, 18–24)
- Minor grid lines (every 2h, non-major): `rgba(203,213,225,0.5)` — subtle
- Major grid lines (every 6h): `rgba(71,85,105,0.4)` — clearly visible

---

## Testing Checklist
- [x] Gantt loads automatically on dashboard open (no more stuck "Loading…")
- [x] 21 occupied stands shown for today (including RON aircraft from previous dates)
- [x] Bars show correct time ranges:
  - RON still parked = full bar 00:00 → 24:00
  - RON departed today = 00:00 → departure time
  - Arrived today = on_block_time → off_block_time (or 24:00)
- [x] "Show All Stands" toggle works — expands to all 36 stands
- [x] Grid lines: major 6h lines clearly distinct from minor 2h lines
- [x] Zone shading visible and does not obscure aircraft bars

---

## Summary
**Done:** All three issues resolved. Gantt chart is fully functional.
**Status:** COMPLETED — user confirmed "works swell"
