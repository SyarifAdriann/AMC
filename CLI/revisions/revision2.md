# Revision 2 - Fix Master Table RON Display Logic - 20250816

## 1. PROBLEM ANALYSIS
- **Issue description:** The `master-table.php` is not correctly displaying active "Remain Overnight" (RON) aircraft from previous days, nor is it showing RON aircraft that were completed on the current day.
- **Root cause:** The core issue lies in the default date filters in `master-table.php`. The `date_from` and `date_to` filters are automatically set to the current date. This is applied to the `on_block_date` column. Active RON records have an `on_block_date` from a *previous* day, so they are incorrectly excluded by this filter. Similarly, when a RON is completed, its `on_block_date` remains in the past, causing it to be filtered out of the completed RONs view. The base query logic is correct, but the default filters override it.
- **Affected files/systems:** `master-table.php`
- **Risk assessment:** Medium. The core data integrity is not at risk (records are saved correctly), but the user-facing display in the master table is misleading, which impacts operational decisions.

## 2. IMPLEMENTATION PLAN FOR GEMINI CLI
### Pre-execution Checklist:
- [ ] Verify current project state matches `CLI/context.md`.
- [ ] Confirm all dependencies are installed.

### Step-by-Step Execution:
To resolve this, we will change the default date filters to be empty, so they are not applied unless the user manually specifies a date range.

1.  **Update Default Filter Values in `master-table.php`**
    -   **Action:** Modify the default values for `$filters['date_from']` and `$filters['date_to']` to be empty strings.
    -   **File to modify:** `master-table.php`
    -   **Details:** Change `?? date('Y-m-d')` to `?? ''` for both date filters. This prevents the `build_where_clause` function from adding a date condition by default.
    -   **Verification:** After the change, load `master-table.php` without any URL parameters. The date input fields in the filter form should be empty.

2.  **Add `carryOverActiveRON` function to `master-table.php` for Consistency**
    -   **Action:** Copy the `carryOverActiveRON` function from `index.php` and paste it into `master-table.php`. Then, call this function at the top of the script.
    -   **File to modify:** `master-table.php`
    -   **Details:**
        -   Copy the entire `carryOverActiveRON` function definition from `index.php`.
        -   Paste it into `master-table.php` (e.g., after the `dbconnection.php` include).
        -   Add the line `carryOverActiveRON($pdo);` immediately after the include to execute it on page load.
    -   **Verification:** This ensures that if `master-table.php` is the first page visited on a new day, the RON statuses are correctly updated before any data is queried for display.

### Testing Protocol:
- [ ] **Simulate New Day/Active RON Carryover:**
    -   Ensure a test record exists with a `movement_date` and `on_block_date` from a previous day (e.g., '2025-08-15'), with `is_ron=1` and `ron_complete=0`.
    -   Load `master-table.php` (on the current date, '2025-08-16'). The record should appear in the main master table.
- [ ] **Test Off-Block on Index Showing in RON Table:**
    -   Using the `index.php` page, add an `off_block_time` to the active RON record.
    -   Reload `master-table.php`. The record should now appear in the "RON Table" section.
- [ ] **Apply Manual Date Filter:**
    -   In the filter form on `master-table.php`, set the `date_from` and `date_to` to the previous day ('2025-08-15').
    -   Submit the filter. The table should now correctly filter and show records based on that `on_block_date`.

### Success Criteria:
- Active RONs from previous days appear by default in the `master-table.php` main view.
- RONs that are completed (off-blocked) today appear in the `master-table.php` RON table view.
- Manual date filtering continues to work as expected.

## 3. EXECUTION LOG (Updated by Gemini CLI)
### Completed Steps:
- [x] Step 1: Modified default date filters in `master-table.php` to be empty strings. Result: Success.
- [x] Step 2: Copied `carryOverActiveRON` function from `index.php` to `master-table.php` and added a call to it. Result: Success.

### Issues Encountered:
- [Document any problems, errors, or unexpected behaviors]

### Current Status:
- [Exact state of the system after execution]

### Files Modified:
- [List all files that were actually changed]

### Next Actions Required:
- [What needs to happen next]
