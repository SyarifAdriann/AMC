# Testing Checklist

## Authentication & session management
- Verify login success/failure paths (correct credentials, wrong password, inactive user, lockout after 5 attempts within 15 min).
- Confirm session regeneration on login and cookie attributes (HttpOnly, Secure when HTTPS, SameSite=Lax).
- Validate timeout behaviour (30-minute inactivity) redirects to login with timeout notice.
- Ensure logout invalidates session and records audit entry.

## Authorization & roles
- Confirm `viewer` can access Apron and Master Table pages but is redirected away from Dashboard, and cannot invoke restricted AJAX actions (`saveRoster`, `saveMovement`, `save_all_changes`, `setRON`, admin APIs).
- Verify `operator` has all operational rights but cannot manage users or delete snapshots.
- Verify `admin` can access all endpoints, including user management and snapshot deletion.

## Movement management (apron page)
- Create new movement via stand modal (with/without RON flag, with off-block time) and confirm database entries reflect formatted timestamps, `user_id_created/updated`, and `ron_complete` state.
- Update existing movement fields (registration change, stand reassignment, remarks) and ensure UI refreshes without reload and DB row updates.
- Test `Set RON` button: only applies to eligible movements, avoids double-appending dates, and updates counters.
- Validate aircraft and flight autofill performs lookups correctly and leaves existing user input untouched.
- Save roster data (new date, update existing) and ensure values persist in `daily_staff_roster` with correct `updated_by_user_id`.
- Trigger apron status refresh to confirm counts match `getApronStatus()` output.

## Master table operations
- Filter by date range, airline, flight number, and category; confirm pagination controls and totals update accordingly.
- Execute inline edits across multiple fields, then `Save All Changes`; verify transaction commits and audit behaviour where applicable.
- Add new row from master table and confirm it appears in main and RON views (depending on flags).
- Run Set RON from master table and confirm results mirror apron view.
- Validate duplicate flight highlighting appears for same-day collisions.

## Dashboard analytics & reports
- Confirm summary cards reflect live data (apron counts, arrivals/departures per category, RON counts) and refresh without full page reload.
- Validate peak-hour chart and summary use correct bucket aggregation.
- Generate each report type (daily log AM/PM, charter log, RON report, monthly summary) and verify datasets + formatting.
- Export CSV for each report type; inspect headers, row counts, timestamp formats.
- Submit monthly charter report modal (various months) and confirm results.

## Snapshot management
- Create snapshot for current/past date; verify JSON stored in `daily_snapshots`, audit log entry created, and list view refreshed.
- View snapshot details (roster, movements, RON, metrics) and ensure data matches source tables.
- Print/download snapshot view if applicable (check formatting, totals).
- Delete snapshot (admin only) and confirm removal + audit log entry + UI feedback.
- Run console `php tools/console.php snapshot:generate [YYYY-MM-DD]` for dates without snapshots and verify idempotency when rerun.

## User administration
- List users with search, role filter, status filter, and pagination; confirm totals and counts.
- Create new user (with/without manual password) and verify password hashing (Argon2id), role, status, and audit logging.
- Update user fields (email, role, status) and ensure rules enforced (cannot suspend last active admin, cannot suspend self, allowed roles only).
- Reset password via modal and confirm new hash stored and audit log updated.
- Toggle status (active/suspended) and ensure UI/DB reflect change, notifications emitted.
- Ensure `/api/admin/users` is invoked for user management requests and no legacy endpoints appear in the network log.

## Data integrity & database assertions
- Validate `aircraft_movements` records maintain coherent `movement_date`, `on_block_date`, `off_block_date`, and `ron_complete` states after edits.
- Ensure `daily_staff_roster` updates maintain single row per date/aerodrome combination.
- Inspect `audit_log` entries for login, logout, user CRUD, snapshot events to confirm context payload (old/new values) is accurate.
- Confirm `login_attempts` entries are created/cleared appropriately after success/failure.

## Security & CSRF
- Ensure CSRF token present on all form submissions and AJAX calls requiring it; tamper with token to confirm 403/JSON error.
- Attempt direct POST to restricted endpoints without session/auth to verify protection.
- Confirm role-based middleware prevents privilege escalation (e.g., viewer calling admin API via crafted request).

## Frontend behaviour & responsiveness
- Compare Apron, Master Table, Dashboard pages against `test-results/` screenshots for desktop (1920x1080), tablet (~768px), and mobile (~375px) breakpoints.
- Verify apron map scaling, touch interactions, and modals on iOS/Android (mobile-adaptations behaviour).
- Test keyboard navigation, copy/paste selection, and scroll performance on large tables.
- Check for layout regressions when modals open/close, including overflow handling and focus management.

## Performance & reliability
- Measure apron status and movement retrieval queries for acceptable latency; consider caching strategies if needed.
- Stress test bulk save endpoints with concurrent edits to ensure transaction safety and no race conditions.
- Validate snapshot creation handles large movement datasets without timeout.
- Monitor PHP error log (`logs/php_errors.log`) during tests for warnings/notices.

## CLI & automation
- Execute the scheduled snapshot command for backfilled dates; verify exit codes and console output.
- If new console kernel introduced, ensure command registration works and environment config respected.

## Regression & cross-browser coverage
- Run automated end-to-end suite (or manual smoke) in Chrome, Firefox, Edge, Safari (desktop + mobile) focusing on critical flows.
- Re-run visual comparison for key pages after template extraction.
- Maintain Puppeteer (or equivalent) scripts to generate updated baselines once refactor stabilises.
