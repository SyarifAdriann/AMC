Apron Monitoring System - Compressed Knowledge Base
System Overview
Purpose: Aircraft movement tracking with RON (Remain Overnight) status management

Stack: PHP/MySQL backend, JavaScript frontend, responsive apron map interface

Key Files:

index.php (apron map interface)

master-table.php (data management)

dbconnection.php (database connection)

structure.sql (database schema)

Core Data Model
Primary Table: aircraft_movements
Key Fields:

id, registration, aircraft_type, parking_stand

on_block_time, off_block_time, movement_date, on_block_date, off_block_date

is_ron (boolean), ron_complete (boolean)

from_location, to_location, flight_no_arr, flight_no_dep, operator_airline, remarks

RON Logic:

is_ron=1 + ron_complete=0 = active overnight

ron_complete=1 = completed overnight

Date Formatting:

RON timestamps include (dd/mm/yyyy) format (e.g., 12:22 (12/04/2024))

Non-RON movements use plain timestamps

Support Tables
aircraft_details: Registration lookup for type/operator

flight_references: Flight number route defaults

daily_staff_roster: Staff scheduling

RON Logic Implementation
Status Transitions
Occupied Stand: Movement with on_block_time but no off_block_time

RON Trigger:

Automatic: Occupied beyond current day (detected via carryOverActiveRON())

Manual: "SET RON" button activation

RON Active: is_ron=1, ron_complete=0

RON Complete: off_block_time added → ron_complete=1

Critical Timestamp Handling
On Block RON: Appends (dd/mm/yyyy) format automatically

Off Block RON: Appends date only for RON movements

Duplicate Prevention: Checks for existing ( before adding date

Interface Logic
index.php (Apron Map)
Display Logic:

(movement_date = CURDATE() AND (off_block_time IS NULL OR off_block_time = '')) OR (is_ron=1 AND ron_complete=0)

Key Features:

Stand visualization (70+ hardcoded positions)

Modal editing

"SET RON" button

Registration/flight autofill

master-table.php (Data Management)
Master Table Display:
- **Default View (No Filters):** `(am.movement_date = CURDATE()) OR (am.is_ron = 1 AND am.ron_complete = 0) OR (am.is_ron = 1 AND am.ron_complete = 1 AND am.off_block_date = CURDATE())`. This shows a "live" view of the current day's activity.
- **Filtered View:** When filters are applied, the default base condition is dropped entirely. The `WHERE` clause is constructed only from the user's filter criteria, allowing it to fetch any matching records from history, regardless of date or RON status.

RON Table Display: `am.is_ron = 1 AND am.ron_complete = 1`. This logic remains unchanged, with filters being appended with `AND`.

Features:

Bulk editing

Pagination (75 records/page)

Dynamic Filtering (date range, airline, flight number)

Critical Fixes Implemented
Master Table RON Fetch:

Added completed RONs with today's off-block date to base condition

Removed default date filters to prevent exclusion of active RONs

Edit Synchronization:

Date appending only for RON movements:

php
if ($currentRecord['is_ron'] == 1) {
    if (strpos($value, '(') === false) {
        $formatted_time = $value . ' (' . date('d/m/Y') . ')';
    }
}
Parking Stand Restrictions:

Removed readonly constraint

Maintained autofill on stand clicks

Key Functions
Backend PHP
carryOverActiveRON($pdo):

Auto-marks overdue movements as RON

Appends date to on_block_time

getCurrentMovements($pdo):

Fetches apron display data

saveMovement:

Handles CRUD with RON logic

updateRONStatus($pdo):

Batch RON conversion

Frontend JavaScript
Stand click handlers with modal editing

Registration/flight autofill

Spreadsheet-like navigation (arrows, tab, enter)

Copy/paste functionality

Configuration Details
Database Schema:

RON fields: is_ron, ron_complete

Date fields: movement_date, on_block_date, off_block_date

Indexes on: movement_date, is_ron, parking_stand

UI Components:

Responsive scaling: 5% shrink on 1920x1080 base

Stand positions: Hardcoded pixel coordinates

Color coding:

Yellow: Planned

Red: Current

RON: Highlighted

Critical Business Rules
Daily Reset: Master table shows current day + active RONs

Persistence: Off-blocked records remain visible until daily reset

RON Completion:

off_block_time sets ron_complete=1

Date appended only for RON movements

Stand Occupancy: One active movement per stand

Data Integrity: Real-time sync between interfaces

Testing Requirements
RON Carryover: Verify active RONs appear after date change

Cross-Interface Editing: Validate sync between index/master

Stand Conflicts: Test assignment restrictions

Date Formatting: Confirm RON/non-RON timestamp handling

Off-block Visibility: Verify completed RONs appear in both tables

Current Status
All core RON logic operational

Master table displays active/completed RONs correctly

Date formatting consistent for RON/non-RON

Real-time sync between interfaces confirmed

**Note:** Final user verification is pending for the master table fixes as of 2025-08-16.


## Dashboard Status (Updated 2025-09-07)
- **Current State:** The `dashboard.php` file's features are functional, including the Peak Hour Analysis, Reporting Suite, and Administrative modals.
- **Next Steps:**
    - Restore the daily snapshot feature.

## Reporting Suite (Updated 2025-09-07)
- **Monthly Charter Report:** The monthly charter report is functional.
- **UI/UX:** The report output has a "Close" button that hides the report and resets the form filters via JavaScript without a page reload.
- **Spreadsheet Functionality:** The report table has spreadsheet-like behavior, allowing users to select a range of cells with the mouse and copy the data to the clipboard in a tab-separated format (compatible with Excel/Google Sheets) using Ctrl+C.
