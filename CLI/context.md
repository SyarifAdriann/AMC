# Project Context

## Current Project State
- Project name: Aircraft Movement Control (AMC) System
- Current version/branch: main
- Main functionality: A web-based system for monitoring and managing aircraft movements on the apron, including a master table for historical data and a visual apron map.
- Current working features:
    - Apron map is stable.
    - Master table with direct record creation and editing is functional. A new "+ New Record" button allows for spreadsheet-style inline record creation.
    - Duplicate flight number detection (non-blocking, with highlighting) is implemented.
    - All dashboard analytics are functional.
    - Comprehensive Role-Based Authentication System is implemented and fully functional.
    - **Daily Snapshot Archive:** Admins/Operators can create, view, print to PDF, and delete read-only snapshots of daily operational data with simplified metrics. The staff roster is now displayed at the top of the snapshot view. The PDF output is optimized for printing, with improved layout and multi-page support.

## Architecture Overview
- Key files and their purposes:
    - `index.php`: Main entry point, displays the apron map and handles movement saving logic.
    - `master-table.php`: Displays a master table of all aircraft movements with filtering and direct editing/creation capabilities.
    - `dashboard.php`: Displays analytics, summary data, and provides access to administrative features.
    - `snapshot-manager.php`: Backend endpoint for all snapshot-related actions (create, list, view, delete).
    - `create-daily-snapshot.php`: Optional standalone script for automated/cron-based snapshot creation.
    - `dbconnection.php`: Handles the database connection.
    - `styles.css`: Main stylesheet for the application.
- Main dependencies: PHP, MySQL.
- Database/configuration details: The application uses a MySQL database.
    - **Tables:** `aircraft_movements`, `users`, `login_attempts`, `daily_snapshots`.
- API endpoints/routes: The application uses POST/GET requests to PHP files to handle actions like saving movements, managing users, and managing snapshots.

## Recent Changes
- Last modification date: 2025-09-07
- What was last changed:
    - Fixed a regression bug in the "Manage Accounts" feature that prevented the user list from loading and broke the functionality of the action buttons.
    - Fixed a bug that caused an HTTP 500 error when generating the Monthly Charter Report.
    - Improved the print functionality for daily snapshots to ensure the entire content is printed across multiple pages if necessary.

## Known Issues & Technical Debt
- Active bugs: None identified at the moment.
- Performance issues: None identified at the moment.
- Code that needs refactoring:
    - Long-term plan to refactor the entire project into a cleaner, more scalable MVP structure.
    - Long-term plan to port the project to a Laravel framework.

## Next Priority Actions
- Immediate tasks: Awaiting user direction. The "Print to PDF" function for snapshots was requested but halted.
- Planned features:
    - Implement Live Real-Time Updates (WebSockets).
    - Enhance Reports & Analytics in the dashboard.
    - Overhaul CSS for responsiveness and mobile-first design.
- Technical improvements needed:
    - Back up and refactor the project for scalability.
    - Port the project to Laravel with Laravel Breeze.