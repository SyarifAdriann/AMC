# Revision 9: Category Autofill, Off-Block Warning, User Delete FK, Stand Gantt Chart

**Date:** 2026-05-17
**Status:** PENDING VERIFICATION

---

## Problem Description

Four issues reported:
1. Category autofill still not working after Rev 8 fix
2. Off-block warning still fires on RON movements
3. Manage Accounts: Edit/Delete throws FK constraint error
4. New feature: Stand Usage Gantt Chart on Dashboard

---

## Diagnostic Findings

### Issue A — Category Autofill
- **Root Cause:** `stand-modal.php` has `<option value="Komersial" selected>`. The select's `.value` is always `"Komersial"` (truthy) when the modal opens. The guard `!catField.value` always evaluates to `false`, completely blocking the autofill block from running.
- **Affected Files:** All 4 JS files (both apron.js copies, both master-table.js copies)

### Issue B — Off-Block Warning
- **Root Cause:** Two paths generate this warning: (a) frontend JS — fixed in Rev 8; (b) PHP backend `evaluateInputWarnings()` in `AircraftMovementRepository.php`. The PHP function receives `off_block_time = "06:00"` (raw user input, no date suffix yet). The `strpos(..., '(')` guard on `isOffBlockEarlierThanOnBlock` added in Rev 8 never triggers for new entries.
- **Real Fix:** Check the `is_ron` flag directly in `evaluateInputWarnings()` — if RON, skip the time comparison entirely.

### Issue C — User Delete FK Violation
- **Root Cause:** `audit_log.user_id` FK references `users.id` with no `ON DELETE` clause (defaults to RESTRICT). Any user who has performed actions has audit log entries that block deletion.
- **Fix Chosen:** PHP-level transaction — delete the user's audit_log entries first, then delete the user row. No schema change required.

### Issue D — Gantt Chart
- **New feature:** Stand Usage Gantt Chart showing per-stand occupancy across a 24-hour timeline. Date-picker enabled.

---

## Changes Made

### File: `assets/js/apron.js` (and `public/assets/js/apron.js`)
- Removed `!catField.value` guard in `handleRegistrationAutofill()`. Category now always written when `data.category` is returned from DB.

### File: `assets/js/master-table.js` (and `public/assets/js/master-table.js`)
- Same guard removal in the registration autofill handler.

### File: `app/Repositories/AircraftMovementRepository.php`
- `evaluateInputWarnings()`: Added `$isRon = !empty($movement['is_ron'])` check. Off-block time comparison is now skipped when the movement is marked as RON.
- Added new public method `standUsage(string $date): array` for Gantt data.

### File: `app/Repositories/UserRepository.php`
- `delete()`: Wrapped in a PDO transaction. Now `DELETE FROM audit_log WHERE user_id = ?` executes first, then `DELETE FROM users WHERE id = ?`. Atomic with rollback on failure.

### File: `app/Controllers/DashboardController.php`
- `movementMetrics()`: Added `action=stand_usage` branch. Validates date param and calls `$this->movements->standUsage($date)`.

### File: `resources/views/dashboard/index.php`
- Added "Metrik Penggunaan Stand" card with date picker, legend, and `#gantt-container` div.

### File: `assets/js/dashboard.js` (and `public/assets/js/dashboard.js`)
- Added Gantt chart IIFE at the end. Fetches from `api/dashboard/movements?action=stand_usage&date=...`, groups by stand, renders proportional CSS bars with hover tooltips.
- Color coding: Komersial=blue, Cargo=teal, Charter=purple, RON-overnight=amber.

---

## Testing Requirements

### Fix A — Category Autofill
- [ ] Open a movement in the Apron Map modal, type a known registration (e.g., one in `aircraft_details`), blur/press Enter
- [ ] Category select box should change from "Komersial" default to the DB value (e.g., "Charter")
- [ ] Repeat in the Master Table modal

### Fix B — Off-Block Warning
- [ ] On a RON movement, enter an off_block time earlier than on_block (e.g., on_block = 22:00, off_block = 06:00) and save
- [ ] Should save cleanly with NO warning popup

### Fix C — User Delete/Edit
- [ ] Go to Dashboard → Manage Accounts → Delete any non-admin user
- [ ] Should succeed without FK error message
- [ ] Try editing a user — should also work (edit doesn't actually trigger the FK, but verify it still saves correctly)

### Fix D — Gantt Chart
- [ ] Dashboard should show "Metrik Penggunaan Stand" section with a date picker
- [ ] Bars should appear for dates that have movement records
- [ ] Hover over a bar — tooltip should show registration, airline, category, time range
- [ ] Change the date to a different date and click Load — chart should update

---

## Summary

**Done:**
- Category autofill guard bug fixed (4 JS files)
- Off-block warning PHP backend guard fixed (1 PHP file)
- User delete FK violation fixed via transactional delete (1 PHP file)
- Stand Usage Gantt Chart implemented end-to-end (1 repository method, 1 API action, 1 HTML section, 2 JS files)

**What's Left:**
- User verification of all 4 fixes

---

## Status Update
PENDING VERIFICATION
