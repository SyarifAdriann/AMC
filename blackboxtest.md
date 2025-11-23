C# AMC Blackbox Test Checklist

Comprehensive reference of every executable surface in the AMC aircraft movement control system. Use it as a �black box� driver: each row names the callable surface, where it lives, what it is supposed to do, and how to probe it. Page/UI features are paired with their backing controller/service/JS functions so nothing is missed when running the final acceptance test.

## Legend
- **Files & lines** use repo-relative paths (Windows separators preserved) with 1-based line numbers from `rg`.
- **Test focus** hints describe inputs/outputs or state changes to observe without cracking code again.
- JS functions are listed alongside the page they power; where the bundle contains duplicated definitions (e.g., `assets/js/master-table.js`), verify both copies behave identically or clean up before release.

---

## 1. Bootstrapping & Routing Surfaces
| Component | Function / Entry (path:line) | Purpose | Blackbox test focus |
| --- | --- | --- | --- |
| Front controller | `public/index.php` | Loads container, registers routes, dispatches via `Request::capture()` then sends response. | Hit `/` (authenticated) and ensure router resolves to Apron page; simulate missing route -> 404. |
| Legacy shims | `login.php`, `master-table.php`, `dashboard.php`, `logout.php` | Same as `public/index.php` but kept for legacy direct hits. | Directly request each PHP file to ensure it still funnels through router + middleware. |
| Autoloader | `bootstrap/autoload.php` � SPL closure & `loadConfig()` | Resolves `App�` classes, composes config array from `/config`. | Rename/move class temporarily to ensure autoloader errors as expected (dev check). |
| App bootstrap | `bootstrap/app.php` | Instantiates `Application`, loads config, registers providers. | Ensure config overrides are read (e.g., change `.env` style vars to see effect). |
| Providers | `bootstrap/providers.php` � numerous `$app->singleton(...)` closures plus `configureLogging()` and `configureSession()` | Wires PDO, repositories, services, CSRF, logging & session cookie hardening. | Confirm log/session ini values take effect; verify each singleton resolves exactly once. |
| Legacy helpers | `bootstrap/legacy.php` functions (`legacy_app`, `legacy_pdo`, `legacy_config`, `legacy_csrf_token`, `legacy_validate_csrf`, `legacy_regenerate_csrf`, `legacy_apron_status`, `legacy_ron_service`, `legacy_user_repository`, `legacy_daily_snapshot_repository`, `legacy_daily_staff_roster_repository`, `legacy_aircraft_movement_repository`, `legacy_flight_reference_repository`, `legacy_stand_repository`) | Provide procedural access for older scripts. | Call each helper while bootstrapped CLI runs to ensure it returns live instances (esp. CSRF token consistency). |
| Routing tables | `routes/web.php` � `$legacyPage` closure, grouped GET/POST routes; `routes/api.php` � authenticated API group | Maps `/login`, `/`, `/apron`, `/master-table`, `/dashboard` plus JSON APIs for apron, ML, master table, snapshot manager, admin users. | Verify each route enforces AuthMiddleware, honor both `.php` and pretty URIs, and API verbs (e.g., POST `/api/apron/recommend`). |

---

## 2. Core Runtime Stack
| Area | Function (path:line) | Purpose | Test focus |
| --- | --- | --- | --- |
| IoC container | `app/Core/Application.php:18` `__construct`, `basePath`, `config`, `mergeConfig`, `router`; `app/Core/Container.php:20` `bind`, `singleton`, `has`, `make`, `instance` | Keeps global state, resolves dependencies. | Ensure asking for unknown binding throws; singleton returns same instance; config dot-notation works. |
| HTTP layer | `Request` methods (capture, path, query/input/json/server/header/files/cookies) and helper `normalizePath` trio; `Response` factory/static helpers (`make`, `json`, `redirect`), mutators (`setStatus`, `header`), `send` | Normalizes inbound data + outputs. | Spoof AJAX headers to exercise JSON detection; send JSON responses and inspect headers. |
| Router | Methods `add/get/post/put/delete/match/group/dispatch`, `runRoute`, `callAction`, `runMiddlewarePipeline`, `resolveMiddleware`, `compileRoute`, `extractParameters`, `mergeGroupAttributes`, `normalizeGroupAttributes`. | Pattern match URIs, execute middleware chain, instantiate controllers. | Register dummy middleware to confirm order, test dynamic `{param}` extraction, invalid action errors. |
| MVC glue | `Controller::__construct/request/view/json`; `View::__construct/share/render/resolvePath`. | Provide per-request view rendering. | Render template with missing file to observe thrown exception; ensure shared view data surfaces (e.g., `appName`). |

---

## 3. Security & Session Control
| Component | Function (path:line) | Purpose | Blackbox focus |
| --- | --- | --- | --- |
| Auth manager | `AuthManager` methods `__construct`, `check`, `id`, `role`, `user`, `login`, `logout`, `ensureSession`. | Wraps `$_SESSION` for login state. | Attempt login/logout, ensure session regenerated, `ensureSession` autostarts session. |
| Middleware | `AuthMiddleware::__construct/handle/unauthenticated/timeoutResponse/expectsJson`. | Forces auth & idle timeout. | Simulate AJAX vs browser access to verify JSON vs redirect; set `last_activity` back in time to check timeout redirect. |
| CSRF | `CsrfManager::__construct/token/validate/regenerate/inputField`. | Issues/verifies anti-CSRF tokens + renders hidden field. | Submit form with stale token -> 400; ensure `regenerate` rotates value. |
| Login throttling | `LoginThrottler` methods (`hasTooManyAttempts`, `recentAttemptCount`, `hit`, `clear`, `maxAttempts`, `lockoutSeconds`). | Tracks attempts via DB table. | Fail login >5 times with same IP -> lockout message triggered; success clears attempt history. |
| Legacy helpers | `legacy_csrf_*`, `legacy_app` etc (see Section 1) | Provide same security context to older scripts. | Confirm legacy scripts still see CSRF token identical to SPA pages. |
---

## 4. Data Models & Persistence Layer
### Models
| Model | Methods (path:line) | Purpose | Test ideas |
| --- | --- | --- | --- |
| `Model` base | `__construct`, `fromArray`, `fill`, `get`, `toArray` | Shared attribute storage. | Instantiate with partial array, ensure `get` default works. |
| `AircraftDetail` | `registration`, `aircraftType`, `operatorAirline`, `category`, `notes` | Accessor wrappers. | Load known row, verify `null` when missing. |
| `AircraftMovement` | `id`, `movementDate`, `registration`, `parkingStand`, `onBlockTime`, `offBlockTime`, `isRon`, `ronComplete`. | Movement entity. | Convert DB row and confirm boolean casting for RON flags. |
| `DailySnapshot` | `id`, `snapshotDate`, `createdByUserId`, `createdByUsername`, `createdAt`, `data`. | Snapshot record with JSON blob. | Save & reload to confirm JSON decoding. |
| `DailyStaffRoster` | `id`, `date`, `shift`, `aerodromeCode`, `dayShiftStaff`, `nightShiftStaff`, `updatedByUserId`, `updatedAt`. | Roster entry. | Provide empty staff names -> arrays exclude blanks. |
| `FlightReference` | `id`, `flightNumber`, `defaultRoute`. | Standards for route autofill. | Query known flight number and confirm default route string. |
| `Stand` | `id`, `name`, `capacity`, `isActive`. | Parking stand metadata. | Confirm `isActive` respects DB ints. |
| `User` | `id`, `username`, `passwordHash`, `role`, `status`, `email`, `fullName`. | Auth user object. | Ensure `role` default `viewer`, status default `inactive`. |

### Repositories
| Repository | Functions (path:line) | Purpose | Test focus |
| --- | --- | --- | --- |
| `Repository` base | `__construct` | Stores PDO. | Dependency injection sanity check. |
| `AircraftDetailRepository` | `findByRegistration`, `upsert`. | Lookup/maintain aircraft metadata. | Save details then fetch to ensure UPSERT works. |
| `AircraftMovementRepository` | `findByDateWithDetails`, `findRonByDate`, `countArrivalsAndDepartures`, `countNewRon`, `countActiveRon`, `hourlyBreakdown`, `categoryBreakdown`, `findCurrentApronMovements`, `findHangarMovements`, `saveMovement`, `bulkUpdate`, `paginateActiveMovements`, `paginateCompletedRonMovements`, `findDuplicateFlights`, `countOccupiedStands`, `countActiveRonStands`, `buildFilterClause`, `executeCountQuery`, `executeListQuery`. | Heavyweight DAO powering Apron + Master Table dashboards. | Run through: create movement -> ensure `saveMovement` writes history, `bulkUpdate` applies array patches, pagination respects filters, duplicate detection finds repeated flights, RON counts accurate. |
| `DailySnapshotRepository` | `upsert`, `existsForDate`, `paginate`, `findById`, `deleteById`, `countAll`, `mapSnapshot`. | Storage for daily archives. | Insert snapshot, paginate results (ordering, username join). |
| `DailyStaffRosterRepository` | `findByDate`, `upsertRoster`. | Stores staff names per shift. | Upsert same date twice -> confirm status string. |
| `FlightReferenceRepository` | `findByFlightNumber`, `upsert`. | Stores standard route for flight numbers. | Use autopopulated autop-run from dashboard to verify route injection. |
| `StandRepository` | `countActive`, `listActive`. | Provides stand availability baseline. | Add inactive stands & ensure not counted. |
| `UserRepository` | `findForAuthentication`, `findById`, `findByUsername`, `ensureSystemUser`, `search`, `countByFilters`, `usernameExists`, `emailExists`, `create`, `update`, `updatePassword`, `updateStatus`, `delete`, `countActiveAdminsExcluding`, `fetchRawById`, `buildFilters`. | All user CRUD for admin API. | Validate unique constraints, `ensureSystemUser` autoseeding, filter combos, admin guard counts. |

---

## 5. Domain Services & Support
| Service | Function (path:line) | Purpose | Blackbox checks |
| --- | --- | --- | --- |
| `ApronStatusService` | `__construct`, `getStatus`. | Summaries of stand counts & live RON. | With sample DB, verify counts match actual occupancy. |
| `RonService` | `__construct`, `carryOverActiveRon`, `setRonForOpenMovements`, `normalizeRonTime` (private), `markCompletion`. | Maintains RON flags and formatting. | Run setRon, ensure on-block stamps appended with date, small times normalized. |
| `SnapshotService` | Constructor (multi injection), `collectSnapshotData`, `upsertSnapshot`, `snapshotExistsForDate`, `paginateSnapshots`, `findSnapshotById`, `deleteSnapshot`, `buildDailyMetrics`, `modelsToArray`, `snapshotToArray`. | Builds daily archive payload. | Trigger collect -> confirm sections (roster/movements/metrics) present; deleting returns info for audit. |
| `ReportService` | `__construct`, `fetchReportData`, `buildHtml`, `buildCsv`, `fetchMonthlyCharterData`, `buildMonthlyCharterHtml`. | Dashboard reporting/export. | Generate each report type ensuring filters applied. |
| `AuditLogger` | `__construct`, `log`. | Writes `audit_log` rows. | Create user/modify and confirm audit entries (JSON payload). |
| `UserAdminService` | Constructor, `list`, `create`, `update`, `resetPassword`, `setStatus`, `delete`, `guardLastAdmin`, `guardStatusChange`, `guardDelete`, `validateUsername`, `validateEmail`, `validateRole`, `validateStatus`, `generateTempPassword`, `hashPassword`. | Implements business rules for admin API (lifecycle). | Attempt to suspend/delete last admin -> expect validation error; create user w/out password -> temp password returned. |
---

## 6. Controllers & Route Entry Points

### Auth Flow (`app/Controllers/AuthController.php`)
| Function | Purpose | Test focus |
| --- | --- | --- |
| `__construct` | Binds repositories/throttler/audit. | Ensure dependencies resolve. |
| `showLoginForm` | Renders login, handles timeout message. | Access while logged in -> redirect to `/`; append `?timeout=1` -> warning shown. |
| `login` | Credential verification, throttling, audit logging. | Wrong credentials increments throttle & returns message; valid login sets session + audit record. |
| `logout` | Logs audit event, destroys session, redirects. | Ensure repeated logout safe. |
| `renderLogin` | View helper. | Input persistence for username field. |
| `canAuthenticate` | Role gating (status check + password verify). | Try suspended user -> false. |

### Apron operations (`app/Controllers/ApronController.php`)
| Function (line) | Purpose | Blackbox permutations |
| --- | --- | --- |
| `__construct` (69) | Injects auth/services/repositories. | Ensure container seeds all dependencies. |
| `show` (93) | Renders Apron view with roster, movements, hangar records. | Load page to confirm sections & preloaded JSON assigned to `window.apronConfig`. |
| `handle` (121) | Dispatches POST `action` (save roster/movement, getAircraftDetails, getFlightRoute). | Fire AJAX for each action and assert JSON structure + permissions enforced. |
| `parsePayload` (265) | Normalizes JSON/body input. | Send JSON vs form-encoded to ensure both accepted. |
| `saveRoster` (291) | Calls `DailyStaffRosterRepository::upsertRoster`. | Attempt viewer role -> expect 403 on controller before hitting. |
| `saveMovement` (335) | Upserts apron record, updates aircraft details, logs ML outcome if token provided. | Submit both create and update payloads; verify return includes `id`, `is_new`, `prediction_log_id`. |
| `lookupAircraftDetails` (431) | Return stored type/operator for registration. | Query known vs unknown registration. |
| `lookupFlightRoute` (469) | Return default route for flight number. | Provide invalid -> success false. |
| `getCurrentMovements` (505), `getHangarRecords` (517) | Data fetch helpers (trigger RON carryover). | Confirm results sorted/time-based. |
| `hasRole` (525) | Role helper. | Ensure viewer denied. |
| `status` (547) | GET `/api/apron/status` output. | Poll to see JSON values update. |
| `forbidden` (557) | Standard 403 JSON. | Validate message surfaces. |
| `recommend` (573) | Entry for `/api/apron/recommend`; orchestrates ML call + business rules. | Provide missing fields -> 422; valid payload -> `recommendations`, `metadata`, `prediction_log_id`. |
| `validateRecommendationInput` (655) | Input sanitization. | Check uppercase normalization, error strings. |
| `getStandRecommendations` (707) | Combines Python predictions, stand availability, airline preferences, fallback logic, logging. | Force python failure -> see 500 message; success -> ensures exactly 3 stands even with filtering. |
| `callPythonPredictor` (780), `resolvePythonBinary` (845), `commandExists` (899) | Proc open to `ml/predict.py`. | Temporarily rename Python binary to test fallback search. |
| `applyBusinessRules` (915) | Filters predictions by availability, A0 rule, preference weighting, fallback to `getFallbackStands`. | Simulate small vs large aircraft to confirm A0 gating. |
| `isSmallAircraft` (1039) | Detects A0-compatible types. | Provide `PC-12` vs `A320`. |
| `getModelPerformanceSummary` (1061) | Reads `reports/phase5_metrics.json`. | Remove file to ensure graceful fallback. |
| `getActiveModelVersion` (1112) | Reads `ml_model_versions` table. | Provide row flagged `is_active` to ensure caching. |
| `recordPredictionLog` (1165) | Inserts `ml_prediction_log` row. | After recommendation, confirm DB row created with payload JSON. |
| `markPredictionOutcome` (1264) | Called when movement saved with `prediction_log_id`. | Save new movement referencing log -> ensures actual stand recorded. |
| `generatePredictionToken` (1372) | Random token helper. | Called implicitly; verify fallback uses `uniqid`. |
| `mlMetrics` (1390) | `/api/ml/metrics` endpoint. | Without logs -> `observed_top3_accuracy` null; with logs -> computed stats + recent list. |
| `mlPredictionLog` (1490) | `/api/ml/logs` listing with filters & search. | Query `result=hit|miss|pending`, `search=airline`, `limit`. |
| `getAvailableStands` (1599) | Derives arrays of available/occupied stand codes. | Ensure default codes fallback if DB empty. |
| `getAirlinePreferences` (1677) | Combines history + DB table `airline_preferences`. | Provide limited availability array to see fallback weighting. |
| `queryAirlinePreferences` (1716), `fetchHistoricalPreferences` (1771) | DB lookups/caching. | Remove preference rows to see historical fallback. |
| `buildAvailabilityFallbackScores` (1825) | Generates weighting when predictions missing. | Provide `available` array to confirm descending scores. |
| `normalizePreferenceCategory` (1846) | Standardizes category keywords. | Input `Komersial` -> `COMMERCIAL`. |
| `getDefaultStandCodes` (1875) | Hard-coded list for fallback. | Confirm ordering matches map layout. |
| `rankStandsByPreference` (1895) | Sorts candidates by composite score. | Provide sample list to ensure stable sort & top 3. |
| `getFallbackStands` (1945) | Builds emergency list when predictions empty. | Feed `available` <3 to ensure fallback pulls from predictions then occupied. |
### Master Table (`app/Controllers/MasterTableController.php`)
| Function | Purpose | Tests |
| --- | --- | --- |
| `__construct` | Injects auth/RON repo. | � |
| `show` | Loads filters, paginates both live & RON tables, duplicate warnings. | Change query params to ensure they persist in view. |
| `handle` | Accepts actions `save_all_changes`, `create_new_movement`, `setron`. | Attempt unauthorized role -> 403; valid JSON -> expect success. |
| `collectFilters`, `parsePayload` | Filter sanitization. | Provide invalid dates -> ensures trimmed strings. |
| `saveAllChanges` | Bulk field patcher via repository. | Mutate multiple cells then submit to confirm changed rows updated only once. |
| `createMovement` | Quick create row. | Missing registration returns message. |
| `fetchMasterMovements`, `fetchRonMovements`, `findDuplicateFlights` | Data fetch for UI. | Provide data to highlight duplicates row. |
| `hasRole`, `forbidden` | Access gating. | Already covered above. |

### Dashboard & Admin (`app/Controllers/DashboardController.php`)
| Function | Purpose | Tests |
| --- | --- | --- |
| `__construct` | Injects status, repos, CSRF. | � |
| `show` | Redirects viewers to Apron, handles `refresh_apron` query. | Call `?action=refresh_apron` expecting JSON snippet. |
| `handle` | Dispatches form actions (generate/export report, aircraft maintenance, flight refs, monthly charter). | Submit each action with CSRF. |
| `handleReport` | Validates dates, fetches data, outputs HTML or CSV. | Provide invalid type -> validation error message. |
| `handleManageAircraft`, `handleManageFlightReference` | Upserts metadata via repositories. | Duplicate registration should rewrite row. |
| `handleMonthlyCharterReport` | Queries `ReportService` for month/year. | Provide month/year combos; check HTML inserted into `reportOutput`. |
| `renderDashboard` | Passes KPI data to view. | Confirm template receives `movementsToday`, `movementsByHour`. |
| `buildCategoryBreakdown`, `buildHourlyBreakdown` | Derive arrays for charts. | Provide day with missing data -> defaults to zeros. |
| `normalizeDate`, `normalizeMonth`, `normalizeYear` | Input sanitizers. | Provide invalid strings verifying fallback to today/current. |
| `hasRole`, `requireRole` | Extra gating for forms. | Attempt viewer to open admin modals -> script alerts via `requireRole`. |

### API & Admin Controllers
| Controller | Functions | Purpose | Tests |
| --- | --- | --- | --- |
| `App\Controllers\Admin\UserController` | `__construct`, `handle`, `list`, `create`, `update`, `resetPassword`, `setStatus`, `delete`, `validateCsrf`. | JSON admin endpoints for user CRUD with audit logging. | Hit `/api/admin/users` GET/POST with actions; ensure non-admin 403; CSRF failure returns 400. |
| `App\Controllers\Api\SnapshotController` | `__construct`, `handle`, `createSnapshot`, `listSnapshots`, `viewSnapshot`, `deleteSnapshot`, `resolveAction`, `hasRole`, `error`, `forbidden`. | `/api/snapshots` + `snapshot-manager.php` orchestration. | POST create with CSRF -> success; GET list w/ pagination; delete requires admin role. |

### Console Command
| Command | Functions | Purpose | Tests |
| --- | --- | --- | --- |
| `GenerateDailySnapshot` (`app/Console/Commands/GenerateDailySnapshot.php`) | `__construct`, `handle`, `normaliseDate`. | CLI job to auto-capture previous day snapshot. | Run via scheduled CLI to ensure duplicates skipped. |
---

## 7. Frontend Views & UI Feature Checklist

### Shared Layout & Navigation
- `resources/views/layouts/app.php`: ensures per-page styles/scripts injection and wraps content.
- `resources/views/partials/nav.php`: role-aware nav (Apron/Master Table always, Dashboard hidden for viewers, Logout button). Test on different roles.

### Login Page (`resources/views/auth/login.php`)
- Features: Tailwind-styled form, lockout warning banner, remembers username input.
- Blackbox: Submit empty form -> server side message; after lockout, expect yellow warning.

### Apron Monitoring Page (`resources/views/apron/index.php`)
- UI features: Staff roster form, live KPI cards, stand map (1920�1080), stand modal (edit movement), hangar records modal, AI recommendation panel, RON set button, refresh button. Ensure viewer role sees read-only inputs.
- Stand modal (`resources/views/apron/partials/stand-modal.php`): grouped sections (Stand & Aircraft, Flights, Times, Movement metadata, AI helper panel) with Save disabled for viewers.
- Hangar modal (`resources/views/apron/partials/hangar-modal.php`): read-only table of hangar movements.

#### Apron page JavaScript (`assets/js/apron.js`)
| Function (line) | Role | Tests |
| --- | --- | --- |
| `ensureToastHost` (10) | Creates toast container. | Trigger save to see toast DOM inserted once. |
| `showAssignmentToast` (21) | Displays success/toast with AI badges. | After saving stand from recommended list. |
| `normalizeJsonResponse` (50) | Cleans weird JSON responses before parsing. | Mock API returning BOM/quotes to ensure parse works. |
| `fetchJson` (85) | Fetch wrapper enforcing `same-origin`. | Simulate 500 -> ensure error thrown with raw text. |
| `findRecommendationMeta` (134) | Extracts metadata display strings. | Feed metadata object & verify text. |
| `updateRecommendationStatus` (151) | Updates status banner classes. | For warnings vs success. |
| `resetRecommendationPanel` (161) | Clears panel; optionally show note. | Change aircraft inputs -> panel resets. |
| `setRecommendationLoading` (178) | Toggles spinner + disabled state. | Click �Request Recommendation� -> spinner. |
| `highlightSelectedRecommendation` (186) | Visual cue when stand matches prediction. | Type stand code to match recommended. |
| `renderRecommendationCards` (203) | Builds gradient buttons for each result. | Check gradient order (#1 blue, #2 purple, #3 green). |
| `requestRecommendations` (264) | Validates inputs, POSTs to `/api/apron/recommend`, stores state. | Missing field alert; valid call populates panel + `prediction_log_id`. |
| `handleRegistrationAutofill` (349) | Requests `getAircraftDetails`. | Enter registration with known details -> type/operator auto fill. |
| `handleFlightAutofill` (380) | Requests `getFlightRoute`. | Input arrival vs departure to fill from/to. |
| `loadMovementsFromDatabase` (414) | Seeds `standData` with initial PHP array. | Inspect console to ensure map overlays match. |
| `when` (459) | Promise helper for DOM readiness. | Confirm watchers register after DOM loaded. |
| `resizeApron` (478) | Maintains aspect ratio for map container on small screens. | Resize window to ensure scaling. |
| `renderStandIcons` (502) & `createIcon` (517) | Draws stand elements with click handlers. | Click stand => modal opens with proper data. |
| `openModalForEdit` (580) | Loads selected stand record into modal fields, toggles hangar buttons. | Click different stands to verify. |
| `enableTableNav` (662) | Keyboard nav for roster table. | Use arrow keys to move between inputs. |
| `setupSheetBehavior` (928), `clearSelection` (1006), `updateSelection` (1017) | Spreadsheet-like selection + clipboard copy/paste. | Drag to select multiple cells, use Ctrl+C/V. |

### Master Table Page (`resources/views/master-table/index.php`)
- Features: Filter form, inline-edit tables, new-row block, Set RON button, pagination for main + RON tables, viewer read-only mode.

#### Master Table JS (`assets/js/master-table.js`)
| Function (line ranges) | Purpose | Tests |
| --- | --- | --- |
| `saveAllData` (61 & 436) | Collects edited cells & new rows, POSTs to API. | After editing, click save and ensure spinner text toggles & tables reload. |
| `handleRegistrationAutofill` (154 & 529) | Registry-based autofill. | Change registration field -> auto fill type/operator. |
| `handleFlightAutofill` (177 & 552) | Auto fill arrival vs departure route. | Try both columns. |
| `loadMoreEmptyRows` (199 & 574) | Appends blank rows. | Scroll to bottom to see rows injected. |
| `enableTableNav` (240 & 613)` / `setupSheetBehavior` (294 & 667)` / `selectCells` (351 & 724)` / `clearSelection` (376 & 749)` | Spreadsheet UX duplicates; ensure keyboard nav + drag select + clipboard copying works in both sections. |

### Dashboard Page (`resources/views/dashboard/index.php` + partials)
- Features: KPI cards, live apron status auto-refresh, movement category chart, hourly chart, ML metrics panel, ML logbook, admin cards launching modals (Accounts, Aircraft, Flight Reference, Monthly Charter, Snapshot Archive), multiple Tailwind modals and forms respecting roles.

#### Dashboard JavaScript (`assets/js/dashboard.js`)
| Function | Purpose | Tests |
| --- | --- | --- |
| `debounce` (20) | Input throttling for search boxes. | Type quickly and ensure API calls delay. |
| `sanitizeText` (28) | Escapes HTML for dynamic rows. | Feed strings with `<script>` to ensure safe rendering. |
| `fetchJson` (34) | JSON fetch with error messaging. | Force 500 to check thrown error. |
| `updatePeakHoursSummary` (66) | Aggregates `peakHourData`. | Provide sample data to verify top/quiet periods and 4-hour window text. |
| `loadMlMetrics` (119) | Calls `/api/ml/metrics`, populates panel & recent list. | Disconnect endpoint to see error placeholder. |
| `renderPredictionLogRows` (213)` / `loadMlPredictionLogs` (254)` | Manage ML log table & filters. | Use log filters to check badges and pagination limit. |
| `hasRole` (1216) | JS-side role helper (used by SnapshotManager). | Confirm admin-only buttons hide for operators. |

`ModalManager` class: `constructor`, `setupEventListeners`, `openModal`, `closeModal`, `loadUsers`, `renderUsersTable`, `renderPagination`, `prefillUserForm`, `openResetPassword`, `showToast`, `escapeHtml` � test each by triggering respective modals, editing users, copying temporary passwords.

`SnapshotManager` object: `loadSnapshots`, `renderSnapshotsTable`, `renderSnapshotsPagination`, `viewSnapshot`, `printSnapshot`, `renderSnapshotView`, `renderPeakHourChart`, `renderPeakHourSummary`, `deleteSnapshot` � use archive modal to verify listing, view, print, delete flows.

### Shared Mobile Tweaks (`assets/js/mobile-adaptations.js`)
- `initMobileAdaptations` ensures modals align for mobile, apron wrapper scrollable, chart container scroll. Test on width <1024 to confirm adjustments revert on desktop resize.
---

## 8. API & Background Features Summary
- **Apron JSON endpoints** (`/api/apron`, `/api/apron/status`, `/api/apron/recommend`, `/api/ml/metrics`, `/api/ml/logs`) tie directly to `ApronController` functions listed earlier.
- **Master Table API** (`/api/master-table`) uses `MasterTableController::handle`.
- **Admin User API** (`/api/admin/users`, `/admin-users.php`, `/user_management.php`) uses `Admin\UserController`.
- **Snapshot Manager** endpoints (`/api/snapshots`, `/snapshot-manager.php`) backed by `SnapshotController`.
- **Legacy snapshot runner** (`GenerateDailySnapshot` CLI) ensures there is a system-level fallback for data archival.

---

## 9. Machine Learning Pipeline (`ml/`)
| File | Function(s) | Purpose | Tests |
| --- | --- | --- | --- |
| `ml/predict.py` | `load_all_encoders`, `get_encoder`, `to_index`, `decode_stand`, `determine_aircraft_size`, `determine_airline_tier`, `determine_category_from_airline`, `get_stand_zone`, `build_feature_vector`, `parse_args`, `load_payload`, `main`. | CLI predictor invoked by PHP; loads pickled model/encoders, builds feature vector, outputs ranked stands. | Run script standalone piping sample JSON to ensure success path; feed invalid payload to trigger `success:false`. |
| `ml/health_check.py` | `run_health_check`. | Validates model + encoder files and executes smoke prediction. | Execute script; expect PASS message when assets present. |
| `ml/model_cache.py` | `load_model_and_encoders_from_cache`. | Optional caching helper (not currently imported). | Invoke to ensure caching toggles between disk and memory. |
| `ml/test_predict.py` | Unit test classes `BuildFeatureVectorTests` (`test_normalizes_required_fields`, `test_missing_required_fields_raise`) and `PredictCliTests` (`setUp`, `_run_predict`, `test_cli_smoke`, `test_predictions_are_sorted_and_bounded`, `test_top_k_flag_returns_requested_length`, `test_unknown_aircraft_type_uses_fallback`). | Provides automated validation of predictor script. | Run with `python -m ml.test_predict` (ensure data files exist). |
| `ml/train_model.py` | Top-level script retraining Decision Tree and exporting metrics/artifacts. | Re-run when dataset changes to refresh pickles. |
| Supporting assets | Pickled encoders/models, JSON summaries, confusion matrices referenced by controllers for metrics. | Ensure expected files exist when deploying. |

---

## 10. Tests
- `tests/ApronControllerTest.php` defines helper classes (`TestApplication`, `FakeAuthManager`, `TestableApronController`) plus suite `ApronControllerTestSuite` with methods `testStandRecommendation`, `testFallbackWhenNoAvailability`, `testPredictionLoggingCapturesUserId` and assertions (`assertCount`, `assertSame`, `assertGreaterThan`, `assertNotEmpty`, `assertStringContains`). Run `php tests/ApronControllerTest.php` to exercise business rules without hitting real DB.

---

## 11. Console / Background Jobs
- `app/Console/Commands/GenerateDailySnapshot` handles automated archival; ensure scheduled job calls `handle()` daily with optional date override, verifying snapshot dedupe (`snapshotExistsForDate`) and system user fallback.

---

## 12. Ancillary Directories
- `reports/`, `test-results/`, `KDD*`, `CLI/` directories contain documentation, diagrams, historical QA evidence. Review as supporting material but no executable code.
- `tools/` (PHP/Python scripts like `refresh_dataset.py`, `randomforest1_pipeline.py`, `run_model_update_v2.php`) are offline data science utilities. They largely contain top-level procedural code or simple helper functions specific to experimentation and are **not** invoked by the deployed web stack; execute manually if you need to reproduce ML experiments.

---

With this checklist you can now drive final QA by traversing every controller/service/JS function and every user-facing feature without spelunking into source again. Cross off each row as you confirm behavior in the running system (UI, REST, CLI, and ML scripts).
