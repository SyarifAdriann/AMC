# Project Context

## Current Project State
- Project name: AMC Monitoring System
- Current version/branch: main
- Main functionality: Aircraft movement tracking and apron management.
- Current working features:
    - Apron map with live aircraft positions.
    - Master table with filtering and bulk editing (under repair).
    - RON (Remain Overnight) aircraft management.
    - User authentication and role-based access.
    - Dashboard with KPIs and reporting.
    - Inline record creation in the master table (under repair).

## Architecture Overview
- Key files and their purposes:
    - `index.php`: Main apron map view.
    - `master-table.php`: Spreadsheet-like view of all movement data.
    - `dashboard.php`: KPIs, reports, and administrative functions.
    - `dbconnection.php`: Database connection handler.
    - `styles.css`: Primary stylesheet.
    - `structure.sql`: Database schema.
- Main dependencies: PHP, MySQL, JavaScript.
- Database/configuration details: `amc` database on localhost.
- API endpoints/routes:
    - `index.php` handles various AJAX actions for movement data.
    - `dashboard.php` handles report generation and data management.

## Recent Changes
- Last modification date: 2025-09-17
- What was last changed: Attempted Tailwind CSS integration and responsiveness overhaul. Encountered and fixed multiple PHP parse errors and JavaScript issues across `index.php` and `master-table.php`. Currently debugging Google Sheets-like behavior in `master-table.php`.
- Current development focus: Fixing remaining issues with master table functionality and ensuring all features work as expected after Tailwind integration.

## Known Issues & Technical Debt
- Google Sheets-like behavior (cell selection, copy/paste) is not working in `master-table.php`.
- New record creation and editing on `master-table.php` is not fully functional.
- The daily snapshot feature is missing from `dashboard.php`.

## Next Priority Actions
- Fix Google Sheets-like behavior in `master-table.php`.
- Ensure new record creation and editing works correctly in `master-table.php`.
- Restore the daily snapshot feature in `dashboard.php`.