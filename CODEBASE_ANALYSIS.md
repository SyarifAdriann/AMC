# Codebase Analysis

## Overview
- Aircraft Movement Control (AMC) is a procedural PHP application (~8k LOC) that blends server-side HTML rendering, AJAX endpoints, and significant inline JavaScript to deliver live apron operations, reporting, and administration tools.
- The code base relies on a single PDO connection (`dbconnection.php`) and manual includes for shared concerns (`config.php`, `auth_check.php`), with business logic scattered across page controllers (`index.php`, `master-table.php`, `dashboard.php`).
- State is maintained through PHP sessions with role-based gating (`admin`, `operator`, `viewer`); database access centers on the `amc` schema defined in `amc.sql`.

## File Inventory
### PHP entry points & UI pages
- `login.php` - public authentication form with Tailwind styling, rate limiting (`login_attempts`), and audit logging.
- `index.php` - live apron map page; loads movements, staff roster, RON utilities, and exposes AJAX actions (`saveRoster`, `saveMovement`, `setRON`, `getAircraftDetails`, `getFlightRoute`).
- `master-table.php` - consolidated movements view with filtering, pagination, duplicate detection, and spreadsheet-like editing backed by AJAX (`save_all_changes`, `create_new_movement`, `setRON`).
- `dashboard.php` - analytics and administration hub (movement metrics, peak-hour visuals, report generation, monthly charter summaries, snapshot archive, user admin modals); viewers are redirected to `index.php`.
- `logout.php` - ends session and records a logout event.

### AJAX / service endpoints
- `api/admin/users` - JSON API guarded by `requireRole('admin')`; manages users (list/create/update/reset/set_status) with CSRF validation and audit logging.
- `api/snapshots` - JSON API for creating/listing/viewing/deleting `daily_snapshots`; accessible to admins and operators (delete restricted to admins).
- `api/admin/users` - legacy user API (still included by `dashboard` JS), uses a `password` column inconsistent with the current schema; shares duties with `api/admin/users`.
- `auth_check.php` - included by authenticated pages; enforces login, session timeout, and exposes `hasRole`/`requireRole` helpers for fine-grained authorization.

### Shared configuration & utilities
- `config.php` - session cookie hardening, role constants, metadata, error logging configuration.
- `dbconnection.php` - initializes `$pdo` (MySQL) and defines `getApronStatus()` used by `index.php`, `dashboard.php`, and background scripts.

### Automation / CLI
- `create-daily-snapshot.php` - cron-oriented script that re-implements snapshot helpers, ensures a system user, and writes daily snapshots if missing.

### Client assets
- `styles.css` (~1.9k lines) - primary styling for layout, tables, modals, apron map, responsive tweaks.
- `tailwind-custom.css` - Tailwind component extensions (gradients, responsive visibility, touch tweaks).
- `mobile-adaptations.js` - runtime adjustments for mobile/tablet (scaling, touch targets, modal behaviour, virtual keyboard handling).
- `tailwindplan.md` - notes describing the Tailwind integration approach already reflected in templates.
- `mobile-adaptations.js`, inline scripts in `index.php`, `master-table.php`, and `dashboard.php` manage most client interactions.

### Data & documentation assets
- `amc.sql`, `aircraft_movements_inserts.sql`, `dbstructure.md` - database schema and seed data.
- `amc_database_files_backup/` - raw InnoDB backups of core tables.
- `test-results/` - reference screenshots (desktop/tablet/mobile) for visual regression.
- `PROJECT_DOCUMENTATION.md`, `logic.md`, `revinstructions.md`, `mvc.md` - prior documentation and instructions.
- `logs/php_errors.log` - centralised PHP error log configured in `config.php`.
- `package.json`, `package-lock.json`, `node_modules/` - Node environment with Puppeteer dependency (used for automated screenshot capture).

## Function catalog
| File | Function | Purpose | Notes |
| --- | --- | --- | --- |
| `dbconnection.php` | `getApronStatus(PDO $pdo)` | Computes total/available/occupied stands and active RON counts via aggregated queries. | Core utility for live status panels. |
| `auth_check.php` | `hasRole($required)` | Checks current session role against string/array requirement. | Used server-side and echoed into JS. |
| `auth_check.php` | `requireRole($required, $message = 'Unauthorized access')` | Aborts request (JSON or script alert) when role requirement fails. | Gatekeeper for sensitive actions. |
| `index.php` | `carryOverActiveRON(PDO $pdo)` | Marks movements without off-block time as RON and appends date to `on_block_time`. | Duplicated in `master-table.php`; runs at page load and before queries. |
| `index.php` | `getCurrentMovements(PDO $pdo)` | Returns active movements (today + active RON). | Feeds `initialMovements` JS dataset. |
| `index.php` | `getAircraftDetails(PDO $pdo, string $registration)` | Looks up aircraft type/operator. | AJAX autofill; try/catch logging. |
| `index.php` | `getFlightRoute(PDO $pdo, string $flightNo)` | Fetches default route for a flight number. | AJAX autofill. |
| `index.php` | `updateRONStatus(PDO $pdo)` | Marks stale movements as RON. | Currently unused; candidate for consolidation. |
| `index.php` | `checkRONCompletion(PDO $pdo, int $movementId, $offBlockTime)` | Sets `ron_complete` when off-block recorded. | Unused at present. |
| `master-table.php` | `carryOverActiveRON(PDO $pdo)` | Same implementation as `index.php`. | Should be centralised. |
| `master-table.php` | `build_where_clause(array $filters)` | Builds SQL filter clauses/params for the main & RON tables. | Shared by multiple queries. |
| `dashboard.php` | `generateCSRFToken()` | Creates/stores CSRF token in session. | Same logic redefined in other endpoints. |
| `dashboard.php` | `getMovementsToday(PDO $pdo, string $date)` | Summarises arrivals/departures per category. | Used for dashboard cards. |
| `dashboard.php` | `getMovementsByHour(PDO $pdo, string $date)` | Produces two-hour buckets with arrival/departure counts. | Powers custom peak-hour chart. |
| `api/admin/users` | `generateCSRFToken()` | See above. | Duplicate implementation. |
| `api/admin/users` | `validateCSRFToken($token)` | Timing-safe token comparison. | Duplicate implementation. |
| `api/admin/users` | `hash_password_or_fail(string $plain)` | Hashes passwords (Argon2id fallback to default). | Ensures failure throws. |
| `api/admin/users` | `generateTempPassword(int $length = 16)` | Generates random temp password. | Used when admins omit password. |
| `api/snapshots` | `generateCSRFToken()` / `validateCSRFToken($token)` | Same as above. | Duplicate utilities. |
| `api/snapshots` | `getStaffRosterSnapshot(PDO $pdo, $date)` | Retrieves roster rows for a date. | Reimplemented in cron script. |
| `api/snapshots` | `getMovementsSnapshot(PDO $pdo, $date)` | Pulls movements (w/ joined aircraft category) for snapshot JSON. | |
| `api/snapshots` | `getRONSnapshot(PDO $pdo, $date)` | RON subset for snapshots. | |
| `api/snapshots` | `getDailyMetrics(PDO $pdo, $date)` | Aggregates totals, RON counts, and hourly stats for snapshot metadata. | |
| `create-daily-snapshot.php` | `getStaffRosterSnapshot`, `getMovementsSnapshot`, `getRONSnapshot`, `getDailyMetrics` | Cron equivalents of snapshot helpers, plus `getApronStatus` reuse. | Duplicated logic needing a shared service under MVC. |

> **JavaScript highlights** (not exhaustive): `MobileAdaptations` class (`mobile-adaptations.js`), numerous inline helpers in `index.php`/`master-table.php` (autofill, sheet navigation, selection), and `ModalManager`/`SnapshotManager` in `dashboard.php` orchestrating AJAX calls to PHP endpoints.

## Database schema & query patterns
### Tables in use
| Table | Key columns & relationships | Used by |
| --- | --- | --- |
| `aircraft_movements` | `id` PK; aircraft details, stand, on/off block times, `is_ron`, `ron_complete`, audit fields `user_id_created/updated`; indexes on date/stand/RON status. | `index.php`, `master-table.php`, `dashboard.php`, `api/snapshots`, `create-daily-snapshot.php`. |
| `aircraft_details` | `registration` PK; type, operator, category, notes. | Autofill (`index.php`, `master-table.php`), analytics (`dashboard.php`), snapshots, monthly charter report. |
| `stands` | Stand geometry & capacity metadata. | `getApronStatus()` occupancy logic, apron layout definitions. |
| `daily_staff_roster` | `id` PK; `roster_date`, shift staff names, updated metadata. | Roster form (`index.php`), snapshots, cron. |
| `daily_snapshots` | JSON `snapshot_data`, unique `snapshot_date`, `created_by_user_id`. | Managed via `api/snapshots`, displayed on dashboard. |
| `flight_references` | Flight number -> default route. | Autofill (`index.php`, `master-table.php`), dashboard admin forms. |
| `users` | Auth users with hashed password, role, status, timestamps. | Login/logout, user admin APIs, audit log FKs. |
| `login_attempts` | IP/user tracking for rate limiting. | `login.php`. |
| `audit_log` | Records actions, target table/id, JSON before/after values. | Login/logout, user admin, snapshot operations. |
| `narrative_logbook_amc` | Log entries (unused in current PHP). | Potential future MVC model. |
| `aircraft_movements_inserts.sql` | Historical insert set (used as data seed/reference). | Manual import only. |

### Query patterns & behaviours
- **RON management**: `carryOverActiveRON()` updates `is_ron` and appends dates; `setRON` actions iterate over open movements to toggle `is_ron` and optionally append formatted timestamps; off-block recordings append dates and flag `ron_complete`.
- **Apron status**: counts distinct stands with `capacity > 0`, determines occupancy via active movements or incomplete RON, and counts live RON separately.
- **Movement CRUD**: `saveMovement` (insert/update) sets `movement_date`, normalises off-block timestamps, tracks `user_id_*`; master table bulk updates iterate per field inside a transaction.
- **Roster persistence**: UPSERT style logic based on `roster_date` + `aerodrome_code`, updating staff names and `updated_by_user_id`.
- **Dashboard analytics**: category summaries, two-hour buckets for arrivals/departures, report generation with optional filters, CSV export using `fputcsv`.
- **Snapshots**: data assembly queries join `aircraft_details`, compute hourly metrics, RON counts, and store JSON payloads.
- **User management**: filtered paging queries, validation of role/status, uniqueness checks, Argon2id hashing, audit logging.
- **Authentication**: login attempts within 15 minutes, password verification, audit logging, session regeneration, lockout messaging.
- **Duplicate detection**: master table queries for arrivals/departures with duplicate flight numbers on the same date.

## Frontend component breakdown
### `login.php`
- Tailwind-driven responsive layout (`gradient-bg`, `container-bg`) with conditional alert styling for lockouts vs generic errors.
- Simple POST form (`username`/`password`) with CTA button.

### `index.php` (Apron map)
- Shared header/nav (Apron Map, Master Table, Dashboard links) with role-based visibility for Dashboard.
- **Staff roster card** (`#roster-table`): date picker, aerodrome input, day/night staff slots, `Save Roster` button disabled for viewers.
- **Live apron status panel** (`#live-apron-status-container`): displays totals, available, occupied, RON; includes `Set RON` and `Refresh` buttons.
- **Apron map canvas** (`#apron-wrapper` / `#apron-container`): absolute-positioned stand elements with hover/active effects; clicking opens the stand modal.
- **Stand modal** (`#standModalBg`): form fields for registration, flight numbers, operator, on/off block times, RON toggle, remarks; interacts with `saveMovement` endpoint.
- **Hangar modal** (`#hgrModalBg`): displays RON/hangar slots with read-only inputs; close button.
- **Inline JavaScript**: maintains `initialMovements`, `standData`, handles registering event listeners for modals, supports copy/paste selection, keyboard navigation, auto-fill via AJAX calls to `index.php`, and orchestrates DOM updates after saves.
- **Mobile support**: relies on `mobile-adaptations.js` to scale the apron, adjust modals, and tweak touch interactions.

### `master-table.php`
- Header identical to `index`.
- **Filter toolbar**: date range, category dropdown, airline, flight number inputs, `Apply Filters` triggers via GET; main & RON pagination via `main_page` / `ron_page` query strings.
- **Main movements table**: 75-row pages showing current-day movements, active RON, and RON completed today (per base condition); inline inputs/selects for edits, with duplicate flight numbers visually flagged.
- **RON history table**: separate paginated list of completed RONs with quick actions.
- **Bulk action buttons**: `Save All Changes`, `Add New Row`, `Set RON`, export controls.
- **JavaScript utilities**: shared autofill functions (duplicated from `index`), asynchronous saves to `master-table.php`, spreadsheet-like keyboard navigation (`Arrow`, `Tab`, `Enter`), multi-cell selection/highlight for copy, incremental blank-row loading.

### `dashboard.php`
- Header reused from other pages; viewers redirected to apron view.
- **Summary grid**: cards for apron capacity (AJAX refresh), arrivals/departures per category, active RON counts (derived from `movementsToday` and `movementsByHour`).
- **Peak hour visual**: custom bar chart & textual summary built by JS (`renderPeakHourChart`, `updatePeakHoursSummary`).
- **Report generator**: form for selecting report type/date range with buttons for on-page generation and CSV export; output area renders HTML tables.
- **Management modals** triggered by buttons: 
  - `AccountsModal` (user list with search/filter, paging, edit/reset actions hitting `api/admin/users`).
  - User form modal (create/update with CSRF token).
  - Password reset modal (invokes `api/admin/users`).
  - Flight reference modal (interacts with `dashboard.php?action=manage_flight_reference`).
  - Aircraft details modal (manage `aircraft_details`).
  - Snapshot archive modal (lists entries via `api/snapshots`, supports view/print/delete).
  - Snapshot view modal (renders snapshot JSON sections: metrics, roster, movements, RON).
  - Charter report modal (month-year selection to POST to dashboard handler).
- **JavaScript controllers**: `ModalManager` orchestrates UI state, AJAX calls, toast notifications; `SnapshotManager` handles snapshot CRUD; form handlers append CSRF tokens and route to appropriate endpoints.

### Shared assets & behaviour
- `styles.css` governs legacy layout, table styling, modals, selection behaviour, tooltips, toasts, responsive breakpoints down to 480px.
- `tailwind-custom.css` ensures Tailwind utility classes match bespoke gradients and responsive toggles (e.g., `.desktop-table`, `.mobile-cards`).
- `mobile-adaptations.js` continuously updates scaling and interaction on resize, focuses on apron map and modal comfort for touch devices.
- Test images in `test-results/` serve as visual baselines for index/dashboard/master pages at multiple breakpoints.

## Data flow
### Authentication & session establishment
```text
[Browser] --POST username/password--> [login.php]
    -> SELECT user + rate-limit checks
    -> On success: session_regenerate_id, set $_SESSION role/user
    -> INSERT audit_log (LOGIN_SUCCESS), clear login_attempts
<- 302 redirect to index.php
Subsequent page loads include `auth_check.php`, enforcing session timeout and exposing $user_role for templates & JS.
```

### Movement creation & update (apron map)
```text
[User selects stand] -> JS opens stand modal populated from `standData`
[Save] --fetch index.php {action: 'saveMovement', payload}--> [index.php]
    -> requireRole(['admin','operator'])
    -> INSERT/UPDATE aircraft_movements, format timestamps, set user_id_* fields
    -> JSON {success, id}
<- JS updates local caches, refreshes stand label & hangar table without full page reload.
```

### Master table bulk edits
```text
[User edits cells] -> JS records cell-level diffs
[Save All Changes] --POST master-table.php {action:'save_all_changes', changes[]}--> [master-table.php]
    -> requireRole(['admin','operator'])
    -> BEGIN transaction; iterate per change (field-specific handling for off_block_time)
    -> COMMIT; respond JSON success
<- JS shows toast, optionally refreshes table rows via fetch.
```

### Snapshot lifecycle
```text
[Dashboard modal submit] --fetch api/snapshots {action:'create', snapshot_date, csrf}--> [api/snapshots]
    -> requireRole(['admin','operator']); validate CSRF
    -> Collect roster/movements/RON metrics; UPSERT daily_snapshots JSON
    -> INSERT audit_log (UPSERT_SNAPSHOT)
<- JSON success; JS refreshes snapshot list via `SnapshotManager.loadSnapshots()`.

[View snapshot] --GET api/snapshots?action=view&id=...--> returns decoded JSON -> Modal renders metrics, roster, movements.
```

### User administration
```text
[Dashboard Accounts modal] --GET api/admin/users?action=list&filters--> [api/admin/users]
    -> requireRole('admin'); paginated SELECT
<- JSON {data, total}

[Create/update/reset/set_status] --POST api/admin/users {action:..., csrf_token, fields}--> updates `users` + audit_log, returns status for UI toast.
```

## Current routing & access control
| URL | Methods | Allowed roles | Parameters | Notes |
| --- | --- | --- | --- | --- |
| `login.php` | GET, POST | Public | `username`, `password`, `login` button | Rate limited via `login_attempts`; redirects authenticated users manually. |
| `logout.php` | GET | Authenticated | - | Logs audit entry, clears session. |
| `index.php` | GET | `viewer`+ | - | Renders apron map, roster, modals. |
| `index.php` | POST (JSON/form) | `admin`, `operator` for mutating actions; all roles for autofill | `action` in {`saveRoster`, `setRON`, `saveMovement`, `getAircraftDetails`, `getFlightRoute`} | Responds JSON; `saveRoster`/`saveMovement` require elevated roles. |
| `master-table.php` | GET | `viewer`+ | Filters via `date_from`, `date_to`, `category`, `airline`, `flight_no`; pagination via `main_page`, `ron_page` | Displays tables and duplicate highlights. |
| `master-table.php` | POST | `admin`, `operator` | `action` in {`save_all_changes`, `create_new_movement`, `setRON`} | JSON responses, transaction-wrapped saves. |
| `dashboard.php` | GET | `admin`, `operator` | - | Viewer role redirected to `index.php`. |
| `dashboard.php` | POST | `admin`, `operator` | `action` in {`generate`, `export_csv`, `manage_aircraft`, `manage_flight_reference`, `monthly_charter_report`}, `csrf_token` where applicable | Generates HTML/CSV, updates reference data. |
| `api/admin/users` | GET/POST | `admin` | Query/payload parameters described above | Pure JSON API with CSRF checks. |
| `api/snapshots` | GET/POST | `admin`, `operator` (delete restricted to `admin`) | `action` in {`create`, `list`, `view`, `delete`} with CSRF tokens on mutating calls | JSON API supporting dashboard modals. |
| `api/admin/users` | GET/POST | `admin` | Deprecated API endpoints (`list_users`, `update_user`, etc.) | Uses legacy schema (`password` column). |
| `create-daily-snapshot.php` | CLI | System | - | Should be executed by scheduler/cron; echoes status. |

Static assets (`styles.css`, `tailwind-custom.css`, `mobile-adaptations.js`, images, SQL dumps) are served directly without authentication when requested.

## Identified common patterns & duplication
- RON maintenance logic (`carryOverActiveRON`, `setRON`, off-block formatting) duplicated across `index.php`, `master-table.php`, and implicitly in snapshot scripts.
- CSRF helpers (`generateCSRFToken`, `validateCSRFToken`) redefined in multiple files instead of a shared utility.
- Snapshot data collectors exist in both `api/snapshots` and `create-daily-snapshot.php` with near-identical implementations.
- Autofill JavaScript (`handleRegistrationAutofill`, `handleFlightAutofill`, selection utilities) duplicated between `index.php` and `master-table.php`.
- `api/admin/users` overlaps with the newer `api/admin/users`, but targets an outdated database column (`password` vs `password_hash`).
- Inline scripts tightly coupled to DOM IDs/classes, blending concerns that will need separation during MVC migration.
- Hard-coded stand geometry arrays and lengthy HTML modals repeated across pages (e.g., nav header, roster layout).
- Non-ASCII artifacts (mixed encoding replacement characters) appear in several source comments/headings; clean them during migration.

## Additional observations & risks
- AJAX endpoints rely on shared `$_SESSION['csrf_token']`; concurrent tabs may overwrite tokens, so centralising CSRF management will be important.
- Error handling varies: some endpoints wrap in try/catch and JSON error output (`api/admin/users`, `api/snapshots`), others echo script tags on failure (`requireRole` fallback). Harmonising responses will simplify controller design.
- `master-table.php` bulk updates trust client-provided field names; future controllers should validate allowed columns before dynamic SQL.
- `node_modules` (Puppeteer) increases repo size; evaluate whether to keep in `vendor`-style directory under new structure.
- `narrative_logbook_amc` table is currently unused; confirm whether it should become part of the MVC scope.
- Visual baseline images (`test-results/`) should be retained to support regression testing during the refactor.
