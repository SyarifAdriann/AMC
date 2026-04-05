# AMC System — Complete Function Checklist

Every function in the codebase, organized by layer and class.
Use [ ] to track, [x] when verified/tested.

---

## CONTROLLERS

---

### ApronController
**File:** `app/Controllers/ApronController.php`
> Main controller for the live Apron view and all aircraft parking operations.

- [ ] `__construct($app)` — public
  Injects AuthManager, RonService, ApronStatusService, and all repositories.

- [ ] `show()` — public
  Renders the Apron index page with current user, apron status, movements, and hangar records.

- [ ] `handle()` — public
  POST dispatcher. Routes to: saveroster, setron, savemovement, getaircraftdetails, getflightroute.

- [ ] `status()` — public
  Returns JSON apron status: total, available, occupied, and RON stand counts.

- [ ] `movements()` — public
  Returns JSON list of all current apron movements with a timestamp.

- [ ] `recommend()` — public
  Entry point for ML stand recommendation. Validates input, calls Python, returns top-3 ranked stands.

- [ ] `mlMetrics()` — public
  Returns JSON of ML model performance metrics: accuracy, training stats, and last 5 predictions.

- [ ] `mlPredictionLog()` — public
  Returns paginated and filtered prediction log (hit/miss/pending) with stand rank breakdown.

- [ ] `parsePayload(Request $request)` — protected
  Reads JSON or form-encoded payload from the request body.

- [ ] `saveRoster(array $data, int $userId)` — protected
  Validates and upserts a day/night staff roster for a given date and aerodrome.

- [ ] `saveMovement(array $data, int $userId)` — protected
  Saves or updates an aircraft movement. Triggers prediction outcome marking and aircraft detail upsert.

- [ ] `lookupAircraftDetails(array $data)` — protected
  Looks up aircraft type and operator airline by registration number.

- [ ] `lookupFlightRoute(array $data)` — protected
  Looks up the default route for a given flight number from the reference table.

- [ ] `getCurrentMovements()` — protected
  Triggers RON carryover then fetches all current apron movements.

- [ ] `getHangarRecords()` — protected
  Fetches movements where to_location = 'HGR'.

- [ ] `hasRole($roles)` — protected
  Checks if the currently logged-in user has one of the specified roles.

- [ ] `forbidden(string $message)` — protected
  Returns a 403 JSON error response.

- [ ] `validateRecommendationInput(array $payload)` — protected
  Validates aircraft_type, operator_airline, and category fields for a recommendation request.

- [ ] `getStandRecommendations(array $input)` — protected
  Orchestrates the full recommendation flow: Python call → availability → preferences → business rules → logging.

- [ ] `callPythonPredictor(array $payload, int $timeoutSeconds)` — protected
  Spawns the Python predictor via proc_open, passes JSON via stdin, and parses the JSON output.

- [ ] `resolvePythonBinary()` — protected
  Finds the correct Python binary (python, python3, py -3, or a configured path).

- [ ] `commandExists(string $command)` — protected
  Checks via where/which if a given command binary exists on the system.

- [ ] `applyBusinessRules(array $predictions, array $availability, array $preferences, string $aircraftType)` — protected
  Filters ML predictions by availability, applies A0 small-aircraft rule, scores by composite, enforces 3-result minimum.

- [ ] `isSmallAircraft(string $aircraftType)` — protected
  Returns true if the aircraft type matches A0-compatible patterns (Cessna, Pilatus, C152–C425, PC6, PC12).

- [ ] `getModelPerformanceSummary()` — protected
  Reads reports/phase5_metrics.json and returns top-3 accuracy as a formatted string.

- [ ] `getActiveModelVersion()` — protected
  Queries ml_model_versions for the most recent active model version. Result cached in a property.

- [ ] `recordPredictionLog(array $input, array $recommendation, array $modelInfo, ?int $userId)` — protected
  Inserts a new record into ml_prediction_log with input, predicted stands, and full payload JSON.

- [ ] `markPredictionOutcome(int $logId, string $actualStand, int $userId)` — protected
  Updates a prediction log entry with the actually assigned stand and correctness (hit or miss).

- [ ] `generatePredictionToken()` — protected
  Generates a 32-hex-char token using random_bytes. Falls back to uniqid.

- [ ] `getAvailableStands()` — protected
  Builds lists of available and occupied stands from current movements and RON status.

- [ ] `getAirlinePreferences(string $airline, string $category, string $aircraftType, array $available)` — protected
  Returns per-stand preference scores. Cascades: airline table → historical data → availability fallback.

- [ ] `queryAirlinePreferences(string $airline, string $categoryCode, string $aircraftType)` — protected
  Queries airline_preferences table for exact or partial airline name matches.

- [ ] `fetchHistoricalPreferences(string $categoryCode)` — protected
  Loads precomputed preference cache file or falls back to a DB query on historical aircraft_movements usage.

- [ ] `buildAvailabilityFallbackScores(array $available)` — protected
  Assigns evenly distributed scores (100 down to 10) across available stands when no preference data exists.

- [ ] `normalizePreferenceCategory(string $category)` — protected
  Maps Indonesian and variant category names to canonical COMMERCIAL, CHARTER, or CARGO.

- [ ] `getDefaultStandCodes()` — protected
  Returns the hardcoded list of all known stand codes (A0, A1–A3, SA01–SA30, NSA01–NSA15, WR01–WR03, RE01–RE07, RW01–RW11, C1–C3, HGR).

- [ ] `rankStandsByPreference(array $candidates, array $preferences)` — protected
  Sorts candidates by composite_score DESC then probability DESC. Returns top 3 with rank.

- [ ] `getFallbackStands(array $available, array $predictions, array $occupied, string $aircraftType)` — protected
  Builds a 3-item fallback list from available, then predicted, then occupied stands (with A0 rule enforcement).

---

### AuthController
**File:** `app/Controllers/AuthController.php`
> Handles user login, logout, and login form rendering.

- [ ] `__construct($app)` — public
  Injects AuthManager, UserRepository, LoginThrottler, AuditLogger, and PDO.

- [ ] `showLoginForm()` — public
  Renders the login page. Redirects if already logged in. Shows session-timeout message if ?timeout is present.

- [ ] `login()` — public
  Processes login: throttle check → user lookup → password verify → session creation → audit log.

- [ ] `logout()` — public
  Audit-logs the logout, destroys the session, and redirects to the login page.

- [ ] `renderLogin(Request $request, string $errorMessage, bool $showLockout)` — protected
  Renders the auth/login view with an error message and lockout flag.

- [ ] `canAuthenticate(?User $user, string $password)` — protected
  Returns true only if the user exists, is active, and the password hash matches.

---

### DashboardController
**File:** `app/Controllers/DashboardController.php`
> Handles the analytics dashboard, reports, aircraft management, and charter reports.

- [ ] `__construct($app)` — public
  Injects AuthManager, ApronStatusService, movement/detail/reference repositories, ReportService, and CsrfManager.

- [ ] `show()` — public
  Renders the dashboard or returns a JSON apron refresh. Viewer role is redirected to index.

- [ ] `movementMetrics()` — public
  Returns JSON of today's category breakdown and 12-bucket hourly breakdown.

- [ ] `handle()` — public
  POST dispatcher: generate, export_csv, manage_aircraft, manage_flight_reference, monthly_charter_report.

- [ ] `handleReport(Request $request, bool $export)` — protected
  Fetches report data and either renders an HTML table or returns a CSV download response.

- [ ] `handleManageAircraft(Request $request)` — protected
  CSRF-validated upsert of aircraft details: registration, type, airline, category, notes.

- [ ] `handleManageFlightReference(Request $request)` — protected
  CSRF-validated upsert of flight number to default route reference.

- [ ] `handleMonthlyCharterReport(Request $request)` — protected
  CSRF-validated monthly charter report for a given month and year.

- [ ] `renderDashboard(array $overrides)` — protected
  Assembles all dashboard data (apron status, movements, hourly breakdown) and renders the view.

- [ ] `buildCategoryBreakdown(string $date)` — protected
  Builds commercial/cargo/charter arrivals and departures counts. Maps Indonesian category names to English.

- [ ] `buildHourlyBreakdown(string $date)` — protected
  Formats movement data into 12 two-hour time buckets (00:00–01:59 through 22:00–23:59).

- [ ] `normalizeDate($value, string $fallback)` — protected
  Validates Y-m-d date string format or returns the fallback.

- [ ] `normalizeMonth($value, string $fallback)` — protected
  Validates 1–12 integer month, zero-pads it, or returns the fallback.

- [ ] `normalizeYear($value, string $fallback)` — protected
  Validates a 4-digit year string or returns the fallback.

- [ ] `hasRole($roles)` — protected
  Role check against the current session user.

- [ ] `requireRole(array $roles, string $message)` — protected
  Returns a 403 redirect JavaScript response if the role check fails, else returns null.

---

### MasterTableController
**File:** `app/Controllers/MasterTableController.php`
> Handles the paginated master log of all aircraft movements.

- [ ] `__construct($app)` — public
  Injects AuthManager, RonService, and AircraftMovementRepository.

- [ ] `show()` — public
  Triggers RON carryover, collects filters, paginates main and RON movements, finds duplicates, and renders the view.

- [ ] `handle()` — public
  POST dispatcher: save_all_changes, create_new_movement, setron.

- [ ] `collectFilters(Request $request)` — protected
  Extracts date_from, date_to, category, airline, and flight_no from the query string.

- [ ] `parsePayload(Request $request)` — protected
  Reads JSON or form-encoded body payload.

- [ ] `saveAllChanges(array $payload, int $userId)` — protected
  Bulk-updates field-level changes. Evaluates and returns warnings and duplicate flights.

- [ ] `createMovement(array $payload, int $userId)` — protected
  Validates registration, creates a new movement record, and returns validation warnings.

- [ ] `fetchMasterMovements(array $filters, int $page)` — protected
  Paginates active movements via the repository.

- [ ] `fetchRonMovements(array $filters, int $page)` — protected
  Paginates completed RON movements via the repository.

- [ ] `findDuplicateFlights()` — protected
  Returns a list of duplicate flight numbers found in today's records.

- [ ] `hasRole($roles)` — protected
  Role check against the current session user.

- [ ] `forbidden(string $message)` — protected
  Returns a 403 JSON error response.

---

### Admin\UserController
**File:** `app/Controllers/Admin/UserController.php`
> Admin-only user management API controller.

- [ ] `__construct($app)` — public
  Injects AuthManager, CsrfManager, and UserAdminService.

- [ ] `handle()` — public
  Verifies admin role. Dispatches to: list, create, update, reset_password, set_status, delete.

- [ ] `list(Request $request)` — protected
  Returns a paginated, filterable list of users by query, role, and status.

- [ ] `create(Request $request)` — protected
  CSRF-validated. Creates a new user. Returns an auto-generated temp password if none is provided.

- [ ] `update(Request $request)` — protected
  CSRF-validated. Updates email, full_name, role, and status for a user.

- [ ] `resetPassword(Request $request)` — protected
  CSRF-validated. Resets a user's password. Returns temp password if auto-generated.

- [ ] `setStatus(Request $request)` — protected
  CSRF-validated. Sets a user's status to active or suspended.

- [ ] `delete(Request $request)` — protected
  CSRF-validated. Deletes a user by ID.

- [ ] `validateCsrf(Request $request)` — protected
  Returns a 400 error response if CSRF token is invalid. Returns null if valid.

---

### Api\SnapshotController
**File:** `app/Controllers/Api/SnapshotController.php`
> API controller for daily snapshot management.

- [ ] `__construct($app)` — public
  Injects AuthManager, SnapshotService, CsrfManager, and AuditLogger.

- [ ] `handle()` — public
  Auth and role check. Dispatches to: create, list, view, delete.

- [ ] `createSnapshot(Request $request)` — protected
  CSRF-validated. Collects snapshot data for a date and upserts it. Logs an audit event.

- [ ] `listSnapshots(Request $request)` — protected
  Returns a paginated list of snapshot headers with the creator's username.

- [ ] `viewSnapshot(Request $request)` — protected
  Returns the full decoded snapshot JSON data by ID.

- [ ] `deleteSnapshot(Request $request)` — protected
  Admin-only. CSRF-validated. Deletes a snapshot. Logs an audit event.

- [ ] `resolveAction(Request $request)` — protected
  Reads the action value from POST body first, then from GET query string.

- [ ] `hasRole($roles)` — protected
  Role check against the session user.

- [ ] `error(string $message, int $status)` — protected
  Returns a standardized JSON error response.

- [ ] `forbidden(string $message)` — protected
  Returns a 403 JSON error response.

---

## SERVICES

---

### ApronStatusService
**File:** `app/Services/ApronStatusService.php`

- [ ] `__construct(StandRepository $stands, AircraftMovementRepository $movements)` — public
  Injects stand and movement repositories.

- [ ] `getStatus()` — public
  Returns total, available, occupied, and RON stand counts. Returns safe fallback defaults on any error.

---

### AuditLogger
**File:** `app/Services/AuditLogger.php`

- [ ] `__construct(PDO $pdo)` — public
  Injects PDO connection.

- [ ] `log(int $userId, string $actionType, string $targetTable, ?int $targetId, ?array $newValues, ?array $oldValues)` — public
  Inserts a complete audit trail row into audit_log with before and after JSON payloads.

---

### ReportService
**File:** `app/Services/ReportService.php`

- [ ] `__construct(PDO $pdo)` — public
  Injects PDO connection.

- [ ] `fetchReportData(string $type, string $dateFrom, string $dateTo)` — public
  Queries movements by date range and type. Supported types: charter_log, ron_report, daily_log_am, daily_log_pm, monthly_summary, logbook_narrative.

- [ ] `buildHtml(string $type, array $data)` — public
  Renders report data as an HTML table.

- [ ] `buildCsv(string $type, array $data)` — public
  Renders report data as a downloadable CSV string.

- [ ] `fetchMonthlyCharterData(string $month, string $year)` — public
  Fetches charter movements joined with aircraft details for a specific month and year.

- [ ] `buildMonthlyCharterHtml(array $data, string $month, string $year)` — public
  Renders monthly charter data as a formatted HTML table with a title.

---

### RonService
**File:** `app/Services/RonService.php`
> Manages Remain Overnight (RON) aircraft logic.

- [ ] `__construct(PDO $pdo)` — public
  Injects PDO connection.

- [ ] `carryOverActiveRon()` — public
  Marks all open previous-day movements as RON=1 and appends a date suffix to on_block_time.

- [ ] `setRonForOpenMovements(int $userId, ?string $date)` — public
  Manually marks all currently open movements as RON. Formats on_block_time with a date suffix.

- [ ] `markCompletion(int $movementId, ?string $offBlockTime)` — public
  Sets ron_complete = 1 for a movement when off_block_time is provided.

- [ ] `normalizeRonTime(?string $value)` — private
  Converts 3–4 digit time strings (e.g. 830) to HH:MM format (e.g. 08:30).

---

### SnapshotService
**File:** `app/Services/SnapshotService.php`

- [ ] `__construct(...)` — public
  Injects DailySnapshotRepository, DailyStaffRosterRepository, AircraftMovementRepository, ApronStatusService, and RonService.

- [ ] `collectSnapshotData(string $date)` — public
  Triggers RON carryover. Assembles staff roster, movements, RON data, and daily metrics.

- [ ] `upsertSnapshot(string $date, int $userId, array $data)` — public
  Delegates snapshot upsert to DailySnapshotRepository.

- [ ] `snapshotExistsForDate(string $date)` — public
  Returns true if a snapshot record already exists for the given date.

- [ ] `paginateSnapshots(int $page, int $perPage)` — public
  Returns a paginated array of snapshot data mapped to plain arrays.

- [ ] `findSnapshotById(int $id)` — public
  Returns a single snapshot as a plain array, or null if not found.

- [ ] `deleteSnapshot(int $id)` — public
  Deletes a snapshot and returns its date. Used for audit logging.

- [ ] `buildDailyMetrics(string $date)` — protected
  Aggregates total arrivals, departures, new RON, active RON, hourly data, category breakdown, and apron status.

- [ ] `modelsToArray(array $models)` — protected
  Maps an array of Model objects to plain associative arrays via toArray().

- [ ] `snapshotToArray(DailySnapshot $snapshot)` — protected
  Converts a snapshot model to an array and adds the decoded snapshot_data key.

---

### UserAdminService
**File:** `app/Services/UserAdminService.php`

- [ ] `__construct(UserRepository $userRepository, AuditLogger $auditLogger)` — public
  Injects the user repository and audit logger.

- [ ] `list(array $filters, int $page, int $perPage)` — public
  Returns a paginated user list with total count.

- [ ] `create(array $payload, int $adminId)` — public
  Validates fields, checks uniqueness, hashes password, creates user, and logs an audit event.

- [ ] `update(int $id, array $payload, int $adminId)` — public
  Validates changed fields, guards the last admin, updates fields, and logs before/after values.

- [ ] `resetPassword(int $id, string $password, int $adminId)` — public
  Hashes the new password or generates a temp one. Forces must_change_password flag. Logs audit.

- [ ] `setStatus(int $id, string $status, int $adminId)` — public
  Validates status, guards last-admin suspension, updates status, and logs audit.

- [ ] `delete(int $id, int $adminId)` — public
  Guards self-delete and last-admin deletion. Deletes user. Logs audit.

- [ ] `guardLastAdmin(int $id, string $currentRole, string $newRole, string $currentStatus, int $adminId)` — protected
  Throws if the change would result in zero active admins or if an admin is downgrading themselves.

- [ ] `guardStatusChange(int $id, string $status, int $actorId, string $userRole)` — protected
  Throws if suspending the last admin or if a user is suspending their own account.

- [ ] `guardDelete(int $id, int $actorId, string $userRole)` — protected
  Throws if self-deleting or deleting the last active admin.

- [ ] `validateUsername(string $username)` — protected
  Enforces 3–32 char alphanumeric + dot + hyphen + underscore pattern.

- [ ] `validateEmail(string $email)` — protected
  Uses PHP FILTER_VALIDATE_EMAIL and lowercases the result.

- [ ] `validateRole(string $role)` — protected
  Allows only: admin, operator, viewer.

- [ ] `validateStatus(string $status)` — protected
  Allows only: active, suspended.

- [ ] `generateTempPassword(int $length)` — protected
  Generates a 16-char cryptographically random password from a mixed character set.

- [ ] `hashPassword(string $password)` — protected
  Hashes with PASSWORD_ARGON2ID (fallback PASSWORD_DEFAULT). Throws RuntimeException on failure.

---

## REPOSITORIES

---

### AircraftMovementRepository
**File:** `app/Repositories/AircraftMovementRepository.php`

- [ ] `findByDateWithDetails(string $date)` — public
  Fetches all movements for a date joined with aircraft_details category field.

- [ ] `findRonByDate(string $date)` — public
  Fetches RON movements (is_ron=1) for a specific date.

- [ ] `countArrivalsAndDepartures(string $date)` — public
  Returns total arrivals and departures for a date. Excludes EX RON entries and invalid stands.

- [ ] `countNewRon(string $date)` — public
  Counts movements marked as RON on a specific date.

- [ ] `countActiveRon()` — public
  Counts all movements where is_ron=1 AND ron_complete=0.

- [ ] `hourlyBreakdown(string $date)` — public
  Groups movements into 2-hour time buckets with arrival and departure counts.

- [ ] `categoryBreakdown(string $date)` — public
  Groups movements by category (commercial, cargo, charter) with arrival and departure counts.

- [ ] `findCurrentApronMovements()` — public
  Returns all movements on the apron today (no off-block) or active RON movements.

- [ ] `findHangarMovements()` — public
  Returns all movements going to hangar (to_location = 'HGR').

- [ ] `saveMovement(array $attributes, int $userId)` — public
  INSERTs or UPDATEs a movement. Handles off_block date formatting and RON completion flag.

- [ ] `bulkUpdate(array $changes, int $userId)` — public
  Transactionally updates multiple field-level changes. Has special handling for off_block_time.

- [ ] `paginateActiveMovements(array $filters, int $page, int $perPage)` — public
  Returns paginated active movements (today + active RON + closed RON today) with filter support.

- [ ] `paginateCompletedRonMovements(array $filters, int $page, int $perPage)` — public
  Returns paginated completed RON movements.

- [ ] `findDuplicateFlights(string $date)` — public
  Finds arrival and departure flight numbers appearing more than once in a single day.

- [ ] `findById(int $id)` — public
  Fetches a single movement by ID as a raw associative array.

- [ ] `evaluateInputWarnings(array $movement, ?int $excludeId, ?string $movementDate)` — public
  Checks for off-block-before-on-block timing issues and duplicate flight numbers.

- [ ] `countOccupiedStands()` — public
  Counts distinct stands currently in use (on-block without off-block, or active RON).

- [ ] `countActiveRonStands()` — public
  Counts distinct stand names with active RON aircraft.

- [ ] `isOffBlockEarlierThanOnBlock(string $onBlockTime, string $offBlockTime)` — private
  Returns true if the off_block time is earlier in the day than on_block.

- [ ] `extractTimeToMinutes(string $value)` — private
  Parses a time string (HH:MM) to total minutes for numeric comparison.

- [ ] `buildFilterClause(array $filters)` — private
  Builds a SQL WHERE clause string and params array from date, category, airline, and flight_no filters.

- [ ] `executeCountQuery(string $sql, array $params)` — private
  Executes a COUNT query and returns the integer result.

- [ ] `executeListQuery(string $sql, array $params, int $limit, int $offset)` — private
  Executes a paginated SELECT query and returns an array of associative rows.

---

### AircraftDetailRepository
**File:** `app/Repositories/AircraftDetailRepository.php`

- [ ] `getCache()` — protected
  Lazily initializes and returns the FileCache instance for aircraft details. TTL is 10 minutes.

- [ ] `findByRegistration(string $registration)` — public
  Cache-first lookup by registration. NULL results cached for 1 minute. Hits cached for 10 minutes.

- [ ] `upsert(string $registration, array $attributes)` — public
  Inserts or updates aircraft type, operator, category, and notes. Invalidates cache after write.

---

### DailySnapshotRepository
**File:** `app/Repositories/DailySnapshotRepository.php`

- [ ] `upsert(string $date, int $userId, array $data)` — public
  Inserts or updates snapshot JSON for a date using ON DUPLICATE KEY UPDATE.

- [ ] `existsForDate(string $date)` — public
  Returns true if a snapshot row exists for the given date.

- [ ] `paginate(int $page, int $perPage)` — public
  Returns paginated snapshots joined with the creator's username from the users table.

- [ ] `findById(int $id)` — public
  Fetches and decodes a single snapshot by ID.

- [ ] `deleteById(int $id)` — public
  Finds the snapshot, deletes it, and returns the snapshot model for audit use.

- [ ] `countAll()` — protected
  Returns the total count of snapshot rows.

- [ ] `mapSnapshot(array $row)` — protected
  Decodes the snapshot_data JSON string and wraps the result in a DailySnapshot model.

---

### DailyStaffRosterRepository
**File:** `app/Repositories/DailyStaffRosterRepository.php`

- [ ] `findByDate(string $date, ?string $aerodromeCode)` — public
  Returns all roster entries for a date. Optionally filtered by aerodrome code.

- [ ] `upsertRoster(string $date, string $aerodromeCode, array $payload, int $userId)` — public
  Creates or updates a roster for the specified date and aerodrome. Returns 'created' or 'updated'.

---

### FlightReferenceRepository
**File:** `app/Repositories/FlightReferenceRepository.php`

- [ ] `findByFlightNumber(string $flightNumber)` — public
  Fetches a FlightReference model by flight number.

- [ ] `upsert(string $flightNumber, string $defaultRoute)` — public
  Inserts or updates a flight number to default route mapping.

---

### StandRepository
**File:** `app/Repositories/StandRepository.php`

- [ ] `countActive()` — public
  Counts all stands where capacity > 0.

- [ ] `listActive()` — public
  Returns all active stands ordered by name as Stand model objects.

---

### UserRepository
**File:** `app/Repositories/UserRepository.php`

- [ ] `findForAuthentication(string $identifier)` — public
  Finds a user by username or email for the login flow.

- [ ] `findById(int $id)` — public
  Finds a user by ID and returns a User model.

- [ ] `findByUsername(string $username)` — public
  Finds a user by exact username.

- [ ] `ensureSystemUser()` — public
  Finds or creates the built-in system user used for automated operations.

- [ ] `search(array $filters, int $limit, int $offset)` — public
  Queries users with optional query, role, and status filters. Returns raw arrays.

- [ ] `countByFilters(array $filters)` — public
  Returns the count of users matching the filter criteria.

- [ ] `usernameExists(string $username, ?int $excludeId)` — public
  Checks if a username is already taken. Optionally excludes a specific user ID.

- [ ] `emailExists(string $email, ?int $excludeId)` — public
  Checks if an email is already taken. Optionally excludes a specific user ID.

- [ ] `create(array $attributes)` — public
  Inserts a new user record and returns the new database ID.

- [ ] `update(int $id, array $attributes)` — public
  Updates specified column values for a user by ID.

- [ ] `updatePassword(int $id, string $hash, bool $mustChange)` — public
  Updates the password hash and must_change_password flag.

- [ ] `updateStatus(int $id, string $status)` — public
  Updates the user's status field only.

- [ ] `delete(int $id)` — public
  Permanently deletes a user by ID.

- [ ] `countActiveAdminsExcluding(?int $excludeId)` — public
  Counts active admin users. Optionally excludes one ID. Used for last-admin guard.

- [ ] `fetchRawById(int $id)` — public
  Fetches all user columns as a raw associative array.

- [ ] `buildFilters(array $filters)` — private
  Builds WHERE clauses and params for query, role, and status filters.

---

## CORE FRAMEWORK

---

### Application
**File:** `app/Core/Application.php`
> Extends Container. The application bootstrap and config hub.

- [ ] `__construct(string $basePath, array $config)` — public
  Sets base path and config. Initializes Router. Registers self in the DI container.

- [ ] `basePath(string $path)` — public
  Returns an absolute path by appending a relative path to the configured base.

- [ ] `config(string $key, $default)` — public
  Reads a config value using dot-notation traversal (e.g. 'db.host').

- [ ] `mergeConfig(array $config)` — public
  Deep-merges new config values into the existing configuration array.

- [ ] `router()` — public
  Returns the Router instance.

---

### AuthManager
**File:** `app/Core/Auth/AuthManager.php`

- [ ] `__construct(Application $app)` — public
  Stores the app reference.

- [ ] `check()` — public
  Returns true if $_SESSION['user_id'] is set.

- [ ] `id()` — public
  Returns the current user ID from the session, or null if not logged in.

- [ ] `role()` — public
  Returns the current user role from the session, or null.

- [ ] `user()` — public
  Returns the session user array {id, username, role}, or null.

- [ ] `login(array $user)` — public
  Regenerates session ID and writes user data and last_activity timestamp to session.

- [ ] `logout()` — public
  Clears all session data, destroys the session, and expires the session cookie.

- [ ] `ensureSession()` — public
  Starts the PHP session if it is not already started.

---

### FileCache
**File:** `app/Core/Cache/FileCache.php`
> File-based key-value cache with TTL expiry.

- [ ] `__construct(string $cacheDir, int $defaultTtl)` — public
  Sets the cache directory (auto-creates if missing) and the default TTL in seconds.

- [ ] `get(string $key, $default)` — public
  Reads value from a cache file. Returns default if the file is missing or expired.

- [ ] `set(string $key, $value, ?int $ttl)` — public
  Writes a value to a cache file with an expiry timestamp.

- [ ] `has(string $key)` — public
  Returns true if the cache key exists and has not expired.

- [ ] `delete(string $key)` — public
  Deletes a single cache file by key.

- [ ] `clearExpired()` — public
  Scans the cache directory and removes all expired or malformed cache files. Returns the deleted count.

- [ ] `clear()` — public
  Deletes ALL cache files in the directory. Returns the deleted count.

- [ ] `getCacheFile(string $key)` — protected
  Returns the MD5-hashed .cache file path for a given key.

- [ ] `remember(string $key, callable $callback, ?int $ttl)` — public
  Returns the cached value if valid. Otherwise computes via callback, stores it, and returns it.

---

### Router
**File:** `app/Core/Routing/Router.php`

- [ ] `__construct(Application $app)` — public
  Stores the app reference.

- [ ] `add(string $method, string $uri, $action, array $middleware)` — public
  Registers a route with an HTTP method, URI pattern, action, and middleware.

- [ ] `get(string $uri, $action, array $middleware)` — public
  Shortcut to register a GET route.

- [ ] `post(string $uri, $action, array $middleware)` — public
  Shortcut to register a POST route.

- [ ] `put(string $uri, $action, array $middleware)` — public
  Shortcut to register a PUT route.

- [ ] `delete(string $uri, $action, array $middleware)` — public
  Shortcut to register a DELETE route.

- [ ] `match(array $methods, string $uri, $action, array $middleware)` — public
  Registers one route for multiple HTTP methods.

- [ ] `group(array $attributes, callable $callback)` — public
  Groups routes under shared prefix and/or middleware attributes.

- [ ] `dispatch(Request $request)` — public
  Matches the incoming request to a route and executes it. Returns 404 if not matched.

- [ ] `runRoute(array $route, Request $request, array $params)` — protected
  Runs the middleware pipeline then calls the final route action.

- [ ] `callAction($action, array $params)` — protected
  Resolves and calls the route action as a callable, 'Class@method' string, or [class, method] array.

- [ ] `runMiddlewarePipeline(array $middleware, Request $request, callable $destination)` — protected
  Builds a reversed middleware pipeline (onion pattern) and runs the request through it.

- [ ] `resolveMiddleware($middleware)` — protected
  Resolves a middleware to a callable. Accepts inline callables or classes implementing handle().

- [ ] `compileRoute(string $method, array $attributes)` — protected
  Converts a URI with {param} placeholders to named-capture-group regex.

- [ ] `extractParameters(array $names, array $matches)` — protected
  Pulls named capture groups from regex matches into a parameter array.

- [ ] `mergeGroupAttributes(array $routeAttributes)` — protected
  Applies accumulated group prefix and middleware to a route definition.

- [ ] `normalizeGroupAttributes(array $attributes)` — protected
  Normalizes prefix and middleware keys in a route group definition.

---

### Request
**File:** `app/Core/Http/Request.php`

- [ ] `capture()` — public static
  Factory method. Creates a Request from PHP superglobals.

- [ ] `__construct(string $method, string $uri, ...)` — public
  Stores method, URI, query, body, server, files, and cookies. Gathers HTTP headers.

- [ ] `gatherHeaders(array $server)` — protected
  Extracts HTTP_* server keys plus Content-Type and Content-Length into a normalized headers array.

- [ ] `method()` — public
  Returns the HTTP method in uppercase.

- [ ] `uri()` — public
  Returns the raw request URI string.

- [ ] `path()` — public
  Returns the normalized URL path. Strips base path, query string, and fragment.

- [ ] `normalizePath(string $path)` — protected
  Strips the base path and ensures the path has a leading slash.

- [ ] `stripBasePath(string $path)` — protected
  Removes the script's base directory prefix from the path.

- [ ] `determineBasePath()` — protected
  Extracts and caches the directory portion of SCRIPT_NAME as the base path.

- [ ] `query(string $key, $default)` — public
  Returns a specific GET query parameter. Returns all GET params if key is null.

- [ ] `input(string $key, $default)` — public
  Returns a POST body value. Falls back to GET. Returns all merged if key is null.

- [ ] `json(string $key, $default)` — public
  Decodes the JSON body. Content-Type must be application/json. Statically cached.

- [ ] `server(string $key, $default)` — public
  Returns a server variable by key.

- [ ] `header(string $key, $default)` — public
  Returns a gathered HTTP header by key.

- [ ] `files(string $key)` — public
  Returns uploaded file info by key, or all files.

- [ ] `cookies(string $key, $default)` — public
  Returns a cookie value by key.

---

## SECURITY

---

### CsrfManager
**File:** `app/Security/CsrfManager.php`

- [ ] `__construct(AuthManager $auth)` — public
  Injects AuthManager to ensure session availability before token access.

- [ ] `token()` — public
  Generates or retrieves the CSRF token stored in $_SESSION['_csrf_token'].

- [ ] `validate(?string $token)` — public
  Constant-time comparison of the submitted token against the stored session token.

- [ ] `regenerate()` — public
  Forces generation of a brand-new CSRF token, replacing the old one in session.

- [ ] `inputField(string $name)` — public
  Returns a rendered hidden input HTML element containing the CSRF token.

---

### LoginThrottler
**File:** `app/Security/LoginThrottler.php`

- [ ] `__construct(Application $app)` — public
  Stores the app reference for configuration access.

- [ ] `hasTooManyAttempts(PDO $pdo, string $ipAddress)` — public
  Returns true if the recent attempt count exceeds the configured max_attempts threshold.

- [ ] `recentAttemptCount(PDO $pdo, string $ipAddress)` — public
  Counts login attempts from an IP within the lockout time window.

- [ ] `hit(PDO $pdo, string $ipAddress, string $username)` — public
  Records a failed login attempt in the login_attempts table.

- [ ] `clear(PDO $pdo, string $ipAddress)` — public
  Clears all login attempt records for an IP address. Called on successful login.

- [ ] `maxAttempts()` — public
  Returns the configured max attempts value. Default is 5.

- [ ] `lockoutSeconds()` — public
  Returns the configured lockout duration in seconds. Default is 900 (15 minutes).

---

## MIDDLEWARE

---

### AuthMiddleware
**File:** `app/Middleware/AuthMiddleware.php`

- [ ] `handle(Request $request, callable $next)` — public
  Starts session. Checks if the user is logged in. Redirects to login.php?timeout=1 if session is missing or stale.

---

## ML / PYTHON

---

### predict.py
**File:** `ml/predict.py`
> Entry point for the Random Forest stand recommendation model.

- [ ] `load_all_encoders()`
  Loads all label encoders from encoders_redo.pkl into a global dict. Lazy and globally cached.

- [ ] `get_encoder(name)`
  Returns a specific named encoder from the loaded dict. Raises ValueError if not found.

- [ ] `to_index(name, value)`
  Encodes a string value to its integer class index. Falls back to __UNKNOWN__ or 0 for unseen values.

- [ ] `decode_stand(index)`
  Decodes an integer model class index back to a stand name string.

- [ ] `determine_aircraft_size(aircraft_type)`
  Returns SMALL_A0_COMPATIBLE or STANDARD based on Cessna and Pilatus pattern matching.

- [ ] `determine_airline_tier(operator_airline)`
  Returns HIGH_FREQUENCY, MEDIUM_FREQUENCY, or LOW_FREQUENCY based on hardcoded airline lists.

- [ ] `determine_category_from_airline(operator_airline)`
  Infers CARGO, COMMERCIAL, or CHARTER from airline name keywords when category is unknown.

- [ ] `get_stand_zone(category)`
  Maps category to stand zone: RIGHT_COMMERCIAL, LEFT_CARGO, or MIDDLE_CHARTER.

- [ ] `build_feature_vector(payload)`
  Normalizes the input payload, computes all 6 derived features, and returns the complete feature dict.

- [ ] `parse_args()`
  Sets up argparse. Accepts --top_k (default 3) for the number of predictions to return.

- [ ] `load_payload(args)`
  Reads stdin and parses it as JSON. Raises ValueError on empty or invalid JSON.

- [ ] `main()`
  Entry point. Loads model, encodes features, runs predict_proba, and outputs ranked predictions JSON to stdout.

---

### health_check.py
**File:** `ml/health_check.py`

- [ ] `run_health_check()`
  Verifies model and all encoder files exist. Loads them successfully. Runs a test prediction on a known sample.

---

## MODELS (Value Objects)

> Lightweight data-transfer objects.
> Each class implements `fromArray()` (static factory) and `toArray()`.

---

### AircraftDetail
**File:** `app/Models/AircraftDetail.php`

- [ ] `fromArray(array $data)` — static
- [ ] `toArray()` — public
- [ ] `registration()` — public
- [ ] `aircraftType()` — public
- [ ] `operatorAirline()` — public
- [ ] `category()` — public
- [ ] `notes()` — public

---

### AircraftMovement
**File:** `app/Models/AircraftMovement.php`

- [ ] `fromArray(array $data)` — static
- [ ] `toArray()` — public
- [ ] `id()` — public
- [ ] `registration()` — public
- [ ] `aircraftType()` — public
- [ ] `onBlockTime()` — public
- [ ] `offBlockTime()` — public
- [ ] `parkingStand()` — public
- [ ] `fromLocation()` — public
- [ ] `toLocation()` — public
- [ ] `flightNoArr()` — public
- [ ] `flightNoDep()` — public
- [ ] `operatorAirline()` — public
- [ ] `remarks()` — public
- [ ] `isRon()` — public
- [ ] `ronComplete()` — public
- [ ] `movementDate()` — public

---

### DailySnapshot
**File:** `app/Models/DailySnapshot.php`

- [ ] `fromArray(array $data)` — static
- [ ] `toArray()` — public
- [ ] `id()` — public
- [ ] `snapshotDate()` — public
- [ ] `data()` — public
- [ ] `createdByUserId()` — public
- [ ] `createdByUsername()` — public

---

### DailyStaffRoster
**File:** `app/Models/DailyStaffRoster.php`

- [ ] `fromArray(array $data)` — static
- [ ] `toArray()` — public
- [ ] `rosterDate()` — public
- [ ] `aerodromeCode()` — public
- [ ] `dayShiftStaff1()` — public
- [ ] `dayShiftStaff2()` — public
- [ ] `dayShiftStaff3()` — public
- [ ] `nightShiftStaff1()` — public
- [ ] `nightShiftStaff2()` — public
- [ ] `nightShiftStaff3()` — public

---

### FlightReference
**File:** `app/Models/FlightReference.php`

- [ ] `fromArray(array $data)` — static
- [ ] `toArray()` — public
- [ ] `flightNo()` — public
- [ ] `defaultRoute()` — public

---

### Stand
**File:** `app/Models/Stand.php`

- [ ] `fromArray(array $data)` — static
- [ ] `toArray()` — public
- [ ] `name()` — public
- [ ] `capacity()` — public
- [ ] `isActive()` — public

---

### User
**File:** `app/Models/User.php`

- [ ] `fromArray(array $data)` — static
- [ ] `toArray()` — public
- [ ] `id()` — public
- [ ] `username()` — public
- [ ] `email()` — public
- [ ] `fullName()` — public
- [ ] `role()` — public
- [ ] `status()` — public
- [ ] `passwordHash()` — public
- [ ] `mustChangePassword()` — public

---

## ROUTES REFERENCE

---

### Web Routes
**File:** `routes/web.php`

- GET  /login.php          → AuthController::showLoginForm
- POST /login.php          → AuthController::login
- GET  /logout.php         → AuthController::logout
- POST /logout.php         → AuthController::logout
- GET  /index.php          → ApronController::show
- POST /index.php          → ApronController::handle
- GET  /dashboard.php      → DashboardController::show
- POST /dashboard.php      → DashboardController::handle
- GET  /master-table.php   → MasterTableController::show
- POST /master-table.php   → MasterTableController::handle

---

### API Routes
**File:** `routes/api.php`

- POST     /api/recommend                    → ApronController::recommend
- GET      /api/movements                    → ApronController::movements
- GET      /api/apron-status                 → ApronController::status
- GET      /api/ml-metrics                   → ApronController::mlMetrics
- GET      /api/ml-prediction-log            → ApronController::mlPredictionLog
- GET      /api/dashboard/movement-metrics   → DashboardController::movementMetrics
- GET/POST /api/snapshots                    → Api\SnapshotController::handle
- GET/POST /api/admin/users                  → Admin\UserController::handle

---

## FUNCTION COUNT SUMMARY

- ApronController           38 functions
- AuthController             6 functions
- DashboardController       17 functions
- MasterTableController     12 functions
- Admin\UserController       9 functions
- Api\SnapshotController    10 functions
- ApronStatusService         2 functions
- AuditLogger                2 functions
- ReportService              6 functions
- RonService                 5 functions
- SnapshotService           10 functions
- UserAdminService          16 functions
- AircraftMovementRepository 23 functions
- AircraftDetailRepository   3 functions
- DailySnapshotRepository    7 functions
- DailyStaffRosterRepository 2 functions
- FlightReferenceRepository  2 functions
- StandRepository            2 functions
- UserRepository            16 functions
- Application (Core)         5 functions
- AuthManager (Core)         8 functions
- FileCache (Core)           9 functions
- Router (Core)             17 functions
- Request (Core)            16 functions
- CsrfManager (Security)     5 functions
- LoginThrottler (Security)  7 functions
- predict.py (ML)           12 functions
- health_check.py (ML)       1 function
- AuthMiddleware             1 function
- Models (7 classes)        ~55 methods

GRAND TOTAL: ~300 functions
