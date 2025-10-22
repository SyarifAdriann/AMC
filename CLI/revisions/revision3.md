# Revision 3 - Refine RON Display and Off-Block Logic - 20250816

## 1. PROBLEM ANALYSIS
- **Initial Issue:** 1) RON aircraft completed today disappeared from the master table. 2) `off_block_time` for non-RON flights was incorrectly date-stamped.
- **Status of Fix:** The fix for non-RON date stamping (Part 1, Step 2 & 3) is reportedly working. However, the primary issue of completed RONs disappearing persists.
- **Corrected Root Cause:** The issue persists because the master table's main query condition in `$main_base_condition` was not updated correctly in the previous attempt. The correct logic—to include RONs completed today—was only applied to the debug output query (`$debug_condition`) but not to the actual query that fetches the data for the table. When a RON is completed, it is correctly marked in the database but is then immediately filtered out of the master table's default view because it no longer matches the outdated query condition.
- **Affected files/systems:** `master-table.php`
- **Risk assessment:** Medium. The display logic is not showing a complete view of the day's operations.

## 2. IMPLEMENTATION PLAN FOR GEMINI CLI

### Part 1 (Completed)
- **Adjust Master Table Base Condition:** Attempted to extend the query to include RONs completed today. **Outcome: Failed. Change was only applied to debug variable.**
- **Prevent Date Append on `off_block_time`:** Modified save logic in `master-table.php` and `index.php` to only append dates for RON movements. **Outcome: Success.**

### Part 2: Corrective Action

1.  **Update Master Table Base Condition in `master-table.php`**
    -   **Action:** Apply the correct base condition to the `$main_base_condition` variable, which is used for the main data query.
    -   **File to modify:** `master-table.php`
    -   **Details:** Locate `$main_base_condition` (around line 110-115) and set it to: `(am.movement_date = CURDATE()) OR (am.is_ron = 1 AND am.ron_complete = 0) OR (am.is_ron = 1 AND am.ron_complete = 1 AND am.off_block_date = CURDATE())`
    -   **Verification:** A RON completed today will now remain visible in the master table.

### Testing Protocol:
- [ ] **RON Off-Block Visibility:** Verify a RON completed today remains in the master table and also in the RON table.
- [ ] **Simulate Next Day:** Verify the completed RON from the previous day disappears from the master table's default view but remains in the RON table.
- [ ] **Non-RON Behavior:** Confirm a non-RON flight completed today remains visible in the master table.

### Success Criteria:
- RONs completed today are visible in the master table for the entire day.
- All other functionality remains intact.

## 3. EXECUTION LOG (Updated by Gemini CLI)
### Completed Steps:
- [x] Step 1: Updated the `$main_base_condition` variable in `master-table.php` to correctly display RONs completed on the current day. Result: Success.

### Issues Encountered:
- [Document any problems, errors, or unexpected behaviors]

### Current Status:
- [Exact state of the system after execution]

### Files Modified:
- [List all files that were actually changed]

### Next Actions Required:
- [What needs to happen next]