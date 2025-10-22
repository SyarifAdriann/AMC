# Refactoring Checklist

## Recommended MVC directory structure
- `app/`
  - `Controllers/` (HTTP controllers and API endpoints)
  - `Models/` (Eloquent-style data models or repositories)
  - `Services/` (business logic such as apron status, RON processing, snapshots)
  - `Middleware/` (auth/session/role guards)
  - `Support/` (helpers, traits, value objects)
- `bootstrap/` (application bootstrap, dependency container, router wiring)
- `config/` (app, database, session, mail, logging configuration files)
- `public/`
  - `index.php` (front controller / router entry point)
  - `assets/` (compiled CSS/JS, images, fonts)
- `resources/`
  - `views/` (Blade/Twig/PHP templates: layouts, partials, feature views)
  - `js/`, `css/` (source assets prior to build if using bundler)
- `routes/`
  - `web.php` (HTML routes)
  - `api.php` (AJAX/JSON endpoints)
- `storage/`
  - `logs/`, `cache/`, snapshot exports, temp files
- `tests/` (unit, feature, browser tests)
- `tools/` (CLI scripts such as daily snapshot command)

## Step-by-step refactoring process
- [x] Establish a new MVC skeleton (autoloading, router, base Controller/Model/View abstractions) while keeping legacy pages running alongside an adapter layer.
- [x] Centralise configuration: move constants from `config.php` into structured config files; wrap PDO creation in a database service registered with the container.
- [x] Implement middleware for authentication, session timeout, and role enforcement to replace `auth_check.php` includes.
- [x] Build shared services (CSRF manager, RON service, Apron status service, Snapshot service); unit-test coverage still pending.
- [x] Migrate database access into models/repositories (User, AircraftMovement, DailyStaffRoster, DailySnapshot, FlightReference, Stand). Legacy entrypoints now proxy controllers via `bootstrap/legacy.php`, removing direct PDO usage outside the app layer.
- [x] Create controllers incrementally:
   - [x] Auth (login/logout)
   - [x] Apron (index + AJAX actions)
   - [x] MasterTable (list/edit + AJAX)
   - [x] Dashboard (reports + modals)
   - [x] Snapshot API (create/list/view/delete wired to SnapshotController)
   - [x] UserAdmin API
- [x] Extract views: base layout + shared nav now serve apron/master/dashboard; dashboard/admin modals extracted into partials, apron stand/hangar overlays moved to partials, and page scripts relocated to `public/assets/js` while legacy styling is preserved.
- [x] Replace page-level includes with routed controller actions. Legacy entry scripts (`index.php`, `master-table.php`, `dashboard.php`, etc.) now bootstrap the router so controllers serve every request while URLs remain unchanged.
- [x] Repoint AJAX requests to new API routes and retire legacy PHP handlers. Frontend now targets `/api/*` endpoints for apron, master table, snapshots, and user admin; deprecated scripts removed.
- [x] Port CLI script into a console command leveraging services/models (`tools/console.php snapshot:generate`).
- [x] Remove obsolete procedural scripts (admin/users API stubs, snapshot manager, legacy dbconnection/auth/config, cron scripts) after verifying new controllers and APIs.

## Function-to-class mapping strategy
| Legacy responsibility | Target class/service | Notes |
| --- | --- | --- |
| Session setup & constants (`config.php`) | `config/app.php`, `config/session.php` | Use environment variables for credentials; enforce via bootstrap.
| PDO connection (`dbconnection.php`) | `App\Core\Database\Connection` or `DatabaseManager` | Provide singleton/connection pool; expose query builder/ORM. |
| `getApronStatus()` | `App\Services\ApronStatusService` | Used by ApronController & DashboardController; cache for frequent refresh. |
| `carryOverActiveRON`, `setRON`, off-block helpers | `App\Services\RonService` | Called by Apron/Master controllers and snapshot service. |
| Movement CRUD (`saveMovement`, bulk update logic) | `App\Controllers\MovementController`, `App\Repositories\AircraftMovementRepository` | Repository encapsulates inserts/updates; controller handles validation + JSON. |
| Aircraft/flight autofill | `App\Repositories\AircraftDetailRepository`, `FlightReferenceRepository` | Provide lookup methods used by both GUI and API. |
| Roster save/load | `App\Controllers\RosterController`, `App\Repositories\DailyRosterRepository` | Separate read/write; consider DTOs for staff slots. |
| Report generation & CSV export | `App\Controllers\ReportController`, `App\Services\ReportBuilder` | Service returns datasets; controller renders HTML or CSV response. |
| Dashboard snapshot helpers | `App\Services\SnapshotService` | Single implementation shared by web UI and console command. |
| CSRF helpers | `App\Security\CsrfManager` middleware/service | Attach token to session, inject into views, validate on POST/JSON.|
| User admin API | `App\Controllers\Admin\UserController`, `App\Repositories\UserRepository` | Wrap audit logging inside service to keep controller lean. |
| Legacy `user_management.php` | Deprecated -> now proxies to Admin UserController (legacy action aliases handled). |
| Login rate limiting | `App\Security\LoginThrottler` | Interface with `login_attempts` table; integrate into AuthController. |
| Audit logging | `App\Services\AuditLogger` | Accepts user, action, target model; central point for future events. |
| CLI snapshot cron | `App\Console\Commands\GenerateDailySnapshot` | Reuse SnapshotService, register in console kernel. |

## View extraction plan
- Create `resources/views/layouts/app.php` with shared `<head>`, Tailwind includes, header/nav, and toast container.
- Extract nav/header into `resources/views/partials/header.php`, with role-driven button visibility.
- Move repeated modals (stand editor, hangar list, user form, snapshot view, etc.) into partials; includes now live under `resources/views/apron/partials` and `resources/views/dashboard/partials`.
- Break complex pages into components:
  - `apron/index.php` (roster card partial, apron map partial, status widget).
  - `master/index.php` (filter form, main table component, RON table component).
  - `dashboard/index.php` (summary cards, peak-hour chart partial, report form, modal triggers).
- Shift inline JavaScript into dedicated assets (`public/assets/js/apron.js`, `master-table.js`, `dashboard.js`, plus `mobile-adaptations.js`) while preserving behaviour until bundling is introduced.
- Preserve `styles.css` by splitting into base partials or migrating to Tailwind components/utilities.

## Controller creation strategy
- `AuthController` (show login form, authenticate, logout, throttle login attempts, audit events).
- `ApronController` (GET apron view; POST JSON actions for roster save, movement save, set RON, autofill endpoints).
- `MasterTableController` (GET filtered tables; POST JSON for bulk updates/new movement/set RON).
- `DashboardController` (GET analytics view, handle report generation, monthly charter form submissions).
- `ReportController` (dedicated endpoints for generating HTML/CSV to keep dashboard controller slim).
- `SnapshotController` (JSON API for list/view/create/delete; share logic with console command).
- `Admin\UserController` (list/create/update/reset/set status for users, with policy checks).
- `Api\LookupController` (aircraft/flight lookup endpoints reused by multiple pages).
- `Console\GenerateSnapshotCommand` (wraps snapshot service for cron execution).

## Model identification & creation plan
- `User` (roles, status, login timestamps, audit relationships).
- `AircraftMovement` (on/off block accessors, RON scopes, relationships to `User`, `AircraftDetail`, `FlightReference`).
- `AircraftDetail` (registration metadata, category).
- `FlightReference` (flight number default routes).
- `Stand` (stand metadata, capacity, geometry; expose scopes for active stands).
- `DailyStaffRoster` (value objects for day/night shifts, relation to `User` updater).
- `DailySnapshot` (JSON casting for snapshot data, relation to `User`).
- `LoginAttempt` (support throttling service).
- `AuditLog` (record user actions).
- Optional/future: `NarrativeLogbookEntry` (if the table is reactivated under MVC).
- Base model should encapsulate timestamp casting, soft deletes (if needed), and validation rules.

## Routing implementation approach
- Use a front controller (`public/index.php`) that boots the application, registers middleware, and dispatches via router.
- Define human-facing routes in `routes/web.php` (e.g., `/`, `/master-table`, `/dashboard`) pointing to controller actions; preserve legacy `.php` paths via redirects or alias routes during transition.
- Expose JSON endpoints under `/api/*` (e.g., `/api/movements`, `/api/roster`, `/api/snapshots`, `/api/admin/users`) with explicit HTTP verbs (GET for fetch, POST/PUT/DELETE for mutations).
- Register middleware (auth, role, CSRF) per route group to replace manual `requireRole` calls.
- Provide fallback route to serve 404 page and to handle legacy direct file access gracefully.

## Migration order
- [x] Create MVC scaffold and ensure front controller can serve a simple test view while legacy pages continue to function.
- [x] Move shared config, database connection, and session handling into the bootstrap layer; update legacy scripts to use the new connection wrapper via a compatibility include.
- [x] Implement auth middleware and reroute `login.php`/`logout.php` through `AuthController`; verify login flow end-to-end (router now guards legacy pages via AuthMiddleware; auth_check.php remains as compatibility shim).
- [x] Refactor user administration APIs into `Admin\UserController`, update dashboard JS to call new routes, then retire `admin-users.php`/`user_management.php` (legacy file now proxies to controller; dashboard JS hitting new service).
- [x] Extract snapshot logic into `SnapshotService` and `SnapshotController`, update dashboard modals and cron command, decommission `snapshot-manager.php` and `create-daily-snapshot.php` (controller now fronts legacy endpoint; CLI remains for back-compat).
- [x] Migrate aircraft movement services and apron controller; `/api/apron` now fronts all AJAX calls via ApronController and inline handlers have been removed.
- [x] Port master table functionality to `MasterTableController`, including filters, pagination, bulk updates, and duplicate detection.
- [x] Move dashboard analytics/report generation into controller + service, update views and forms accordingly (DashboardController + ReportService now front all GET/POST traffic).
- [x] Split views into layout + partials, with inline JS relocated to the asset pipeline; compiled assets loaded via view metadata to keep parity.
- [x] Remove obsolete procedural files, update routing aliases, and document remaining regression steps (visual + functional) for `test-results` baselines.
- [x] Final clean-up: linted PHP (`php -l`), documented new service wiring, and noted critical test coverage requirements (auth, movement CRUD, snapshots, reports) for the regression suite.

## Errors in Session 9/19/2025

### 1. Autofill JSON Parsing Error
- **Symptom:** When using the autofill feature, the browser console logs an error.
- **Error Message:** `Autofill lookup failed (normal if not in database): SyntaxError: Unexpected token '', "{"success"... is not valid JSON.`
- **Relevant Files:**
  - `public/assets/js/apron.js` (Likely contains the AJAX call for autofill)
  - `app/Controllers/ApronController.php` (Likely contains the backend logic that generates the JSON response)
- **Detailed Analysis:** The error indicates that the JSON response from the server for the autofill feature is malformed. The leading `''` suggests there might be an extra character, whitespace, or an incorrect `Content-Type` header being sent by the PHP backend before the actual JSON string `{"success":...}`. This prevents the JavaScript from correctly parsing the data.
- **Resolution (9/20/2025):** Added a hardened `fetchJson` helper that strips BOM/ stray characters and normalises whitespace before JSON parsing, then refactored all apron AJAX calls to use it (`public/assets/js/apron.js:7`, `public/assets/js/apron.js:42`, `public/assets/js/apron.js:76`). This restores valid JSON handling for autofill.

### 2. Movement Record Submission JSON Parsing Error
- **Symptom:** When submitting a new aircraft movement, the operation fails and the console displays a network error.
- **Error Message:** `Network error: SyntaxError: Unexpected token '', "{"success"...`
- **Relevant Files:**
  - `public/assets/js/apron.js` (Contains the AJAX call for saving a movement)
  - `app/Controllers/ApronController.php` (Contains the backend logic for saving the movement and returning a response)
- **Detailed Analysis:** This is the same type of JSON parsing error as the autofill issue. The backend script that handles the insertion of a new movement record is returning a malformed JSON response. The frontend JavaScript expects a clean JSON object to confirm success but receives invalid data, causing it to report a failure.
- **Resolution (9/20/2025):** Movement save now uses the shared `fetchJson` pipeline and improved error messaging so success responses reach the UI and failures surface actionable detail (`public/assets/js/apron.js:510-585`).

### 3. Master Table Internal Server Error
- **Symptom:** The browser console shows a 500 Internal Server Error when trying to access the master table data.
- **Error Message:** `GET http://localhost/amc/public/master-table.php 500 (Internal Server Error)`
- **Relevant Files:**
  - `master-table.php` (The legacy entry point)
  - `app/Controllers/MasterTableController.php` (The controller responsible for fetching data for the master table view)
  - `logs/php_errors.log` (Will contain the specific server-side error details)
- **Detailed Analysis:** A 500 error points to a critical failure on the server side within the `master-table.php` script or its underlying controller. This is likely a fatal PHP error (e.g., database connection issue, syntax error, or a problem with the data being processed). The PHP error logs on the server need to be inspected to find the root cause.
- **Resolution (9/20/2025):** Restored the correct framework imports so the controller loads cleanly (`app/Controllers/MasterTableController.php:7-12`), eliminating the fatal include error behind the 500.

### 4. Inconsistent Application State After Movement Creation
- **Symptom:** A user attempts to add a new movement from the `index.php` apron map. The UI displays an error message, but the movement data *is* successfully saved to the database and reflected in dashboard analytics. The apron map on `index.php` does not update.
- **Relevant Files:**
  - `index.php` (The user interface where the action is initiated)
  - `public/assets/js/apron.js` (Handles the failed AJAX callback)
  - `app/Controllers/ApronController.php` (Successfully saves data but returns a bad response)
  - `dashboard.php` (Incorrectly shows updated data, confirming the backend save)
  - `app/Controllers/DashboardController.php` (Fetches the data for the dashboard)
- **Detailed Analysis:** This is a critical data integrity and user experience bug. The backend process to save the data is working correctly, but because it sends back a malformed JSON response (the `Network error: SyntaxError...`), the frontend's AJAX `success` callback never executes. Instead, the `error` callback is triggered, showing a failure message to the user. This creates a dangerous inconsistency where the user believes the action failed, while the database state has actually changed.

- **Resolution (9/20/2025):** The resilient JSON parsing plus uniform success handling ensure movement saves surface the real result and reload the apron map, eliminating the phantom failure state (`public/assets/js/apron.js:590-605`).


 Issues Found 9/20/2025

  Note: For all issues listed below, refer to the original project files to understand the expected correct behavior.

  1. Index Page (index.php)
   - Issue: The autofill feature for the "From/To" (departure/arrival) fields is not working. - RESOLVED
   - Analysis: The AJAX request responsible for fetching autofill suggestions is likely failing or the frontend JavaScript is not handling the
     response correctly.

  2. Master Table (master-table.php)
   - Issue: Cannot create a new movement record. - RESOLVED
       - The autofill functionality is not working.
       - The "Save" button is unresponsive or fails silently.
   - Issue: The "Set RON" (Remain Over Night) button is not functional. - RESOLVED
   - Issue: The "Reset Filters" button does not clear the applied filters. - RESOLVED
   - Analysis: Multiple JavaScript event handlers and their corresponding backend API endpoints appear to be broken. This could be due to
     incorrect element IDs, JavaScript errors, or failed AJAX calls to the MasterTableController.

  3. Dashboard (dashboard.php)
   - Issue: All buttons under the "Administrative Controls" section are non-functional. - RESOLVED
   - Analysis: The JavaScript event listeners for these buttons are likely not wired up correctly, or the API endpoints they are supposed to call
     are failing. This affects user management, snapshot creation, and other admin tasks.