# Revision 10: Gantt Stand Toggle + Off-Block Warning Final Fix

**Date:** 2026-05-17
**Status:** PENDING VERIFICATION

---

## Problem Description
1. Gantt chart only showed stands with movements on the selected date (occupied stands only). User requested the ability to view all 36 stands.
2. Off-block timestamp warning still fired when submitting a new RON departure even with the RON checkbox ticked.
3. Previous Gantt rendering fix (rev 9) had a structural bug in `public/assets/js/dashboard.js` where the Gantt IIFE was placed OUTSIDE the main IIFE, making `fetchJson` out of scope (ReferenceError silently swallowed).

---

## Diagnostic Findings
- **Stand count:** 36 distinct stands in `aircraft_movements`. Only occupied stands were returned by API and rendered.
- **JS off-block warning:** Guard only checked `!offBlockVal.includes('(')` — this works for existing stored RON records but not for a *new* RON entry where the field hasn't been decorated yet. Fix: also check `document.getElementById('f-ron').checked`.
- **Gantt scope bug:** In `public/assets/js/dashboard.js`, the old Gantt IIFE was outside `})();` of the main IIFE (line 1380 vs line 1382). `fetchJson` not in scope → silent failure. Fixed by rewriting to use native `fetch()`.

---

## Implementation Plan
1. Add `getAllStands()` method to `AircraftMovementRepository`
2. Include `allStands` array in `stand_usage` API response via `DashboardController`
3. Add "Show All Stands" / "Occupied Only" toggle button to dashboard HTML
4. Rewrite Gantt JS IIFE with toggle state management:
   - `_lastMovements` and `_allStands` cached after each API call
   - `applyRender()` re-renders without a new API call when toggle changes
   - `updateToggleBtn()` manages button visual state
5. Fix JS off-block warning to check `f-ron` checkbox in both `apron.js` copies

---

## Changes Made

### File: `app/Repositories/AircraftMovementRepository.php`
- **Added:** `getAllStands()` method — returns `array<string>` of all distinct `parking_stand` values, sorted

### File: `app/Controllers/DashboardController.php`
- **Line 72-73:** `stand_usage` action now calls `getAllStands()` and includes result as `allStands` key in JSON response

### File: `resources/views/dashboard/index.php`
- **Lines 218-224:** Added `<button id="gantt-toggle-all">` before date picker in Gantt card header

### File: `assets/js/dashboard.js` + `public/assets/js/dashboard.js`
- **Gantt IIFE:** Fully rewritten. Key changes:
  - Uses native `fetch()` instead of inner-scoped `fetchJson` (eliminates scope issue)
  - Loads with `DOMContentLoaded` for the initial call
  - Caches `_lastMovements` and `_allStands` per load
  - `renderGantt(container, movements, standsToShow)` — when `standsToShow` is non-empty, renders all those stands; empty-stand rows shown in muted grey
  - Toggle button wired to `_showAll` flag; re-renders without API call

### File: `assets/js/apron.js` + `public/assets/js/apron.js`
- **Line ~1008-1015:** Added `isRonChecked` guard:
  ```js
  const isRonChecked = !!(document.getElementById('f-ron') && document.getElementById('f-ron').checked);
  if (onMinutes !== null && offMinutes !== null && offMinutes < onMinutes
          && !offBlockVal.includes('(') && !isRonChecked) { alert(...); }
  ```

---

## Testing Requirements
- [ ] Open dashboard → Gantt should auto-load occupied stands (default mode)
- [ ] Status bar shows "Showing X occupied stand(s) • YYYY-MM-DD"
- [ ] Click "Show All Stands" → button turns dark blue, all 36 stands visible (empty ones in grey)
- [ ] Status bar updates to "Showing all 36 stands • YYYY-MM-DD"
- [ ] Click "Occupied Only" → returns to compact view
- [ ] Change date → chart reloads correctly in current mode
- [ ] Input portal: Tick RON checkbox, enter off_block earlier than on_block → NO alert
- [ ] Input portal: Un-tick RON, enter off_block earlier than on_block → alert fires

---

## Summary
**Done:**
- Gantt "Show All Stands" toggle added (option B as requested)
- Off-block warning now correctly respects RON checkbox state
- Root cause of Gantt silent failure identified and permanently fixed (scope bug in public JS)

**Pending:** User verification
