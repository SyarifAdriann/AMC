# System Architecture - AMC Parking Stand Prediction System

## Table of Contents
1. [Project Structure](#project-structure)
2. [Integration Flow](#integration-flow)
3. [Critical Components](#critical-components)
4. [Fragile Areas (⚠️ CRITICAL)](#fragile-areas--critical)
5. [MVC Pattern Implementation](#mvc-pattern-implementation)
6. [File Naming Conventions](#file-naming-conventions)

---

## Project Structure

### Complete Directory Tree with Explanations

```
C:\xampp\htdocs\amc\
│
├── index.php                     # ⚠️ MAIN ENTRY POINT - Loads legacy bootstrap, routes, dispatches
├── login.php                     # Legacy login page (redirects to /login via router)
├── logout.php                    # Legacy logout page (redirects to /logout via router)
├── dashboard.php                 # Legacy dashboard page (redirects to /dashboard via router)
├── master-table.php              # Legacy master table page (redirects to /master-table via router)
│
├── app/                          # Application core - DO NOT MODIFY STRUCTURE
│   │
│   ├── Controllers/              # MVC Controllers (Action handlers)
│   │   ├── ApronController.php          # ⚠️ CRITICAL - Main apron view, ML predictions, RON operations
│   │   ├── DashboardController.php      # Dashboard metrics, reports, aircraft management
│   │   ├── MasterTableController.php    # Master data CRUD operations
│   │   ├── AuthController.php           # Login/logout authentication
│   │   └── Admin/
│   │       └── UserController.php       # User management (admin only)
│   │   └── Api/
│   │       └── SnapshotController.php   # Daily snapshot API
│   │
│   ├── Models/                   # Database models (ActiveRecord-style)
│   │   ├── Model.php                    # Base model class with query builder
│   │   ├── User.php                     # User model
│   │   ├── AircraftMovement.php         # Movement tracking
│   │   ├── AircraftDetail.php           # Aircraft master data
│   │   ├── DailySnapshot.php            # Daily operational snapshots
│   │   ├── DailyStaffRoster.php         # Staff roster
│   │   ├── FlightReference.php          # Flight reference data
│   │   └── Stand.php                    # Parking stand data
│   │
│   ├── Repositories/             # Data access layer (Repository pattern)
│   │   ├── Repository.php               # Base repository
│   │   ├── UserRepository.php
│   │   ├── AircraftMovementRepository.php  # ⚠️ Complex queries for movements
│   │   ├── AircraftDetailRepository.php
│   │   ├── DailySnapshotRepository.php
│   │   ├── DailyStaffRosterRepository.php
│   │   ├── FlightReferenceRepository.php
│   │   └── StandRepository.php
│   │
│   ├── Services/                 # Business logic layer
│   │   ├── ApronStatusService.php       # ⚠️ CRITICAL - Real-time apron status calculation
│   │   ├── RonService.php               # RON (Remain Overnight) operations
│   │   ├── ReportService.php            # Report generation and CSV export
│   │   ├── SnapshotService.php          # Daily snapshot creation
│   │   ├── UserAdminService.php         # User management business logic
│   │   └── AuditLogger.php              # Audit trail logging
│   │
│   ├── Core/                     # ⚠️ FRAMEWORK CORE - MODIFY WITH EXTREME CAUTION
│   │   ├── Application.php              # Dependency injection container
│   │   ├── Container.php                # IoC container implementation
│   │   ├── Controller.php               # Base controller class
│   │   ├── Routing/
│   │   │   └── Router.php               # Request router with middleware support
│   │   ├── Http/
│   │   │   ├── Request.php              # HTTP request abstraction
│   │   │   └── Response.php             # HTTP response abstraction
│   │   ├── View/
│   │   │   └── View.php                 # View renderer (PHP templates)
│   │   ├── Database/
│   │   │   └── DatabaseManager.php      # PDO database connection manager
│   │   ├── Auth/
│   │   │   └── AuthManager.php          # Session-based authentication
│   │   └── Cache/
│   │       └── FileCache.php            # ⚠️ File-based caching system (5-min TTL)
│   │
│   ├── Security/                 # Security components
│   │   ├── CsrfManager.php              # CSRF token generation and validation
│   │   └── LoginThrottler.php           # Brute force protection (5 attempts, 15-min lockout)
│   │
│   ├── Middleware/               # HTTP middleware
│   │   └── AuthMiddleware.php           # Authentication check middleware
│   │
│   └── Console/                  # CLI commands
│       └── Commands/
│           └── GenerateDailySnapshot.php  # Automated snapshot generation
│
├── ml/                           # ⚠️ MACHINE LEARNING MODULE - PYTHON
│   ├── predict.py                       # ⚠️ MAIN PREDICTION ENTRY POINT (called from PHP)
│   ├── train_model.py                   # Model training script
│   ├── test_predict.py                  # Prediction testing utility
│   ├── model_cache.py                   # Python in-memory model cache (1-hour TTL)
│   ├── health_check.py                  # ML system health check
│   ├── __init__.py                      # Python package marker
│   ├── parking_stand_model_rf_redo.pkl  # ⚠️ TRAINED MODEL FILE - DO NOT DELETE
│   └── encoders_redo.pkl                # ⚠️ LABEL ENCODERS - DO NOT DELETE
│
├── resources/                    # Frontend resources
│   └── views/                    # PHP view templates (NOT Blade, plain PHP)
│       ├── layouts/
│       │   └── app.php                  # Main layout template (header, nav, footer)
│       ├── partials/
│       │   └── nav.php                  # Navigation bar
│       ├── apron/
│       │   ├── index.php                # Apron view main page
│       │   └── partials/
│       │       ├── stand-modal.php      # Stand selection modal with ML predictions
│       │       └── hangar-modal.php     # Hangar management modal
│       ├── dashboard/
│       │   ├── index.php                # Dashboard main page
│       │   └── partials/
│       │       ├── aircraft-modal.php   # Aircraft details management
│       │       ├── charter-modal.php    # Charter report modal
│       │       ├── flight-reference-modal.php
│       │       ├── snapshot-modal.php   # Daily snapshot creation
│       │       ├── snapshot-view-modal.php
│       │       ├── user-form-modal.php  # User creation/edit
│       │       ├── reset-password-modal.php
│       │       └── accounts-modal.php   # User account management
│       ├── master-table/
│       │   └── index.php                # Master table page
│       └── auth/
│           └── login.php                # Login page template
│
├── public/                       # Public assets (CSS, JS, images)
│   ├── css/
│   │   └── output.css                   # Tailwind CSS compiled output
│   └── js/
│       └── [various JS files]
│
├── config/                       # ⚠️ CONFIGURATION FILES
│   ├── database.php                     # Database connection config (uses env vars)
│   ├── app.php                          # Application settings (roles, timeouts)
│   ├── session.php                      # Session configuration
│   └── logging.php                      # Logging configuration
│
├── bootstrap/                    # Application bootstrap
│   ├── app.php                          # ⚠️ Application initialization (DI container setup)
│   ├── autoload.php                     # PSR-4 autoloader implementation
│   ├── legacy.php                       # Helper functions for legacy code
│   └── providers.php                    # Service provider registration
│
├── routes/                       # ⚠️ ROUTE DEFINITIONS
│   ├── web.php                          # Web routes (Apron, Dashboard, MasterTable, Auth)
│   └── api.php                          # API routes (snapshots, recommendations, metrics)
│
├── database/                     # Database migrations
│   └── migrations/
│       └── add_performance_indexes.sql  # Performance optimization indexes
│
├── tools/                        # Utility scripts
│   ├── console.php                      # CLI command runner
│   ├── cleanup_cache.php                # Cache cleanup utility
│   ├── precompute_preferences.php       # Airline preference precomputation
│   ├── refresh_dataset.py               # ML dataset refresh
│   ├── measure_predict_perf.py          # ML prediction performance measurement
│   └── [various analysis/testing scripts]
│
├── reports/                      # Generated ML reports (metrics, feature importance)
├── cache/                        # File cache storage (auto-created)
├── storage/                      # Application storage
├── tests/                        # Test files
├── docs/                         # ⚠️ DOCUMENTATION (this folder)
│
├── amc.sql                       # ⚠️ MAIN DATABASE SCHEMA (12 tables + data)
├── aircraft_movements_inserts.sql       # Sample movement data
│
├── categorize_script.py          # Aircraft categorization utility
├── filter_script.py              # Data filtering utility
│
├── .claude/                      # Claude Code settings
├── node_modules/                 # NPM packages (Tailwind CSS)
├── package.json                  # NPM dependencies
├── tailwind.config.js            # Tailwind CSS configuration
└── README.md                     # Project README
```

---

## Integration Flow

### 2.1 PHP to Python ML Communication

**⚠️ CRITICAL INTEGRATION POINT**

The PHP backend communicates with the Python ML module using **command-line execution with JSON pipes**. This is a fragile integration that requires careful handling.

#### How It Works

**Location**: `app/Controllers/ApronController.php` (method: `recommend`)

**Flow Diagram**:
```
User Request (POST /api/apron/recommend)
    ↓
ApronController::recommend()
    ↓
Build JSON payload {aircraft_type, operator_airline, category}
    ↓
Execute Python via proc_open()
    ↓
python C:\xampp\htdocs\amc\ml\predict.py --top_k 3
    ↓
Pass JSON via STDIN (avoids shell quoting issues)
    ↓
Python predict.py reads JSON, loads model, predicts
    ↓
Python outputs JSON to STDOUT
    ↓
PHP captures STDOUT, decodes JSON
    ↓
Return predictions to frontend
```

#### Code Implementation (ApronController.php ~line 800-900)

```php
// Build input payload
$payload = [
    'aircraft_type' => $aircraftType,
    'operator_airline' => $operatorAirline,
    'category' => $category,
];

// Path to Python script
$pythonPath = 'python';  // Assumes Python in PATH
$scriptPath = __DIR__ . '/../../ml/predict.py';

// Use proc_open for better control
$descriptorspec = [
    0 => ["pipe", "r"],  // stdin
    1 => ["pipe", "w"],  // stdout
    2 => ["pipe", "w"]   // stderr
];

$process = proc_open(
    "$pythonPath \"$scriptPath\" --top_k 3",
    $descriptorspec,
    $pipes
);

// Send JSON to stdin
fwrite($pipes[0], json_encode($payload));
fclose($pipes[0]);

// Read stdout
$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);

// Read stderr
$errors = stream_get_contents($pipes[2]);
fclose($pipes[2]);

$returnCode = proc_close($process);

// Decode JSON response
$result = json_decode($output, true);
```

#### ⚠️ FRAGILE POINTS

1. **Python PATH Requirement**: Python must be in system PATH or hardcoded absolute path
2. **JSON Encoding/Decoding**: Must match exactly between PHP and Python
3. **File Paths**: Windows-style paths (`C:\xampp\...`) - breaks on Linux without modification
4. **Timeout Handling**: No explicit timeout in proc_open (can hang indefinitely)
5. **Error Propagation**: stderr must be captured and logged
6. **Model File Availability**: If `.pkl` files missing, Python crashes silently

### 2.2 Request Lifecycle

```
Client Browser
    ↓
HTTP Request → index.php (entry point)
    ↓
bootstrap/legacy.php (load application)
    ↓
bootstrap/app.php (initialize DI container)
    ↓
routes/web.php or routes/api.php (load routes)
    ↓
Router::dispatch(Request) (match route)
    ↓
AuthMiddleware::handle() (check authentication)
    ↓
Controller::method() (execute action)
    ↓
├── Repository (data access)
    ├── Model (ORM)
        └── PDO (database)
    ↓
├── Service (business logic)
    └── External Systems (ML, Cache, Audit)
    ↓
Controller returns Response
    ↓
Response::send() (output to client)
```

### 2.3 Database Query Flow

```
Controller
    ↓
calls Repository method
    ↓
Repository uses PDO connection
    ↓
Prepared statements (PDO::prepare)
    ↓
Bind parameters (PDO::bindValue)
    ↓
Execute query (PDOStatement::execute)
    ↓
Fetch results (fetch/fetchAll)
    ↓
Return to Controller
```

**Security Note**: All database queries use **prepared statements** to prevent SQL injection.

### 2.4 Authentication Flow

```
User submits login form
    ↓
POST /login → AuthController::login()
    ↓
LoginThrottler::check() (rate limiting)
    ↓
UserRepository::findByUsername()
    ↓
password_verify() (bcrypt hash verification)
    ↓
AuthManager::login() (set session)
    ↓
Redirect to /apron (main page)
```

**Session Data Stored**:
- `user_id`
- `username`
- `role` (admin, operator, viewer)
- `last_activity` (for timeout)

### 2.5 ML Prediction Flow

```
User clicks "Recommend Stand" in modal
    ↓
Frontend JS: POST /api/apron/recommend
    ↓
ApronController::recommend()
    ↓
Check cache: FileCache::get('ml_predict_...')
    ↓
If cached: return cached predictions
    ↓
If not cached:
    ├── Build JSON payload
    ├── Execute Python predict.py via proc_open
    ├── Python loads model from .pkl file (cached in memory for 1 hour)
    ├── Python performs feature engineering
    ├── Python runs Random Forest prediction
    ├── Python returns Top-3 predictions with probabilities
    ↓
Store in cache: FileCache::set('ml_predict_...', $predictions, 300)
    ↓
Log to ml_prediction_log table
    ↓
Return JSON response to frontend
    ↓
Frontend displays recommendations in modal
```

---

## Critical Components

### 3.1 Controllers

#### ApronController.php (`app/Controllers/ApronController.php`)
**Purpose**: Main controller for apron operations and ML predictions

**Critical Methods**:
- `show()` - Displays apron view with current movements
- `handle()` - Handles POST actions (saveRoster, addMovement, updateMovement, ronToggle, etc.)
- `recommend()` - ⚠️ CRITICAL - ML stand recommendation endpoint
- `status()` - Real-time apron status API
- `movements()` - Current movements API
- `mlMetrics()` - ML model performance metrics
- `mlPredictionLog()` - ML prediction history

**⚠️ Fragile Areas**:
- `recommend()` method depends on Python execution (breaks if Python not in PATH)
- Cache dependencies (FileCache must be available)
- Complex SQL queries in `getCurrentMovements()` method
- RON status calculations (affects availability)

**Key Dependencies**:
- `ApronStatusService` (real-time status calculation)
- `RonService` (RON operations)
- `AircraftMovementRepository` (movement data)
- `FileCache` (prediction caching)

#### DashboardController.php (`app/Controllers/DashboardController.php`)
**Purpose**: Dashboard analytics and reporting

**Critical Methods**:
- `show()` - Renders dashboard
- `handle()` - Handles actions (generate reports, export CSV, manage aircraft)
- `movementMetrics()` - Movement analytics API
- `handleReport()` - Report generation
- `handleManageAircraft()` - Aircraft CRUD operations

**⚠️ Fragile Areas**:
- Report generation can be slow with large date ranges
- CSV export depends on correct header formatting
- CSRF validation required on all forms

### 3.2 Services

#### ApronStatusService.php (`app/Services/ApronStatusService.php`)
**Purpose**: ⚠️ CRITICAL - Real-time apron status calculation

**What It Does**:
- Calculates total stands, occupied stands, available stands
- Determines RON count and active RON count
- Computes hourly movement statistics
- Aggregates category breakdowns (Commercial, Cargo, Charter)

**⚠️ LOAD-BEARING COMPONENT**: If this service breaks, the entire apron view fails

**Dependencies**:
- `AircraftMovementRepository` (for movement queries)
- `StandRepository` (for stand counts)
- Database queries must be optimized (uses indexes)

**Performance Note**: Called on every page load of apron view - **MUST BE FAST**

#### RonService.php (`app/Services/RonService.php`)
**Purpose**: RON (Remain Overnight) status management

**What It Does**:
- Toggles RON status on movements
- Calculates RON completion status
- Validates RON operations (can't mark departed aircraft as RON)

**⚠️ Critical Business Logic**:
- RON affects stand availability calculations
- RON completion affects occupancy metrics

#### ReportService.php (`app/Services/ReportService.php`)
**Purpose**: Report generation and CSV export

**Report Types**:
- `hourly` - Hourly movement breakdown
- `daily` - Daily summary
- `category` - Category-based analytics
- `monthly_charter` - Monthly charter report

**⚠️ Fragile Areas**:
- Large date ranges can cause memory issues
- CSV encoding must be UTF-8 with BOM for Excel compatibility

### 3.3 Core Framework Components

#### Application.php (`app/Core/Application.php`)
**Purpose**: Dependency Injection Container (IoC)

**What It Does**:
- Registers service bindings
- Resolves dependencies automatically
- Manages singletons
- Provides service location

**⚠️ FRAMEWORK CORE - DO NOT MODIFY UNLESS ABSOLUTELY NECESSARY**

#### Router.php (`app/Core/Routing/Router.php`)
**Purpose**: HTTP request routing with middleware support

**Features**:
- Route registration (GET, POST, match, group)
- Middleware execution
- Route parameter extraction
- Controller method resolution

**⚠️ Fragile Areas**:
- Route order matters (more specific routes must come first)
- Middleware execution order is critical (AuthMiddleware must run before controller)

#### FileCache.php (`app/Core/Cache/FileCache.php`)
**Purpose**: File-based caching system

**Configuration**:
- Default TTL: 300 seconds (5 minutes)
- Cache directory: `cache/` (auto-created)
- Storage format: JSON with expiration timestamp

**⚠️ Critical for Performance**:
- ML predictions are cached to avoid repeated Python execution
- Apron status can be cached (but currently not implemented)
- Cache invalidation on data changes is manual

**Methods**:
- `get($key, $default)` - Retrieve cached value
- `set($key, $value, $ttl)` - Store value
- `has($key)` - Check if key exists and not expired
- `remember($key, $callback, $ttl)` - Get or compute and store

### 3.4 Repositories

**Pattern**: Repository pattern for data access abstraction

**Base Repository** (`app/Repositories/Repository.php`):
- Provides base CRUD methods
- Handles PDO connection
- Uses prepared statements

**Critical Repositories**:

1. **AircraftMovementRepository** (`app/Repositories/AircraftMovementRepository.php`)
   - ⚠️ Complex queries for movement tracking
   - Joins with aircraft_details, stands, flight_references
   - Performance-sensitive (used in apron view)

2. **AircraftDetailRepository** (`app/Repositories/AircraftDetailRepository.php`)
   - Master aircraft data
   - Used in ML predictions for feature lookup

3. **StandRepository** (`app/Repositories/StandRepository.php`)
   - Parking stand master data
   - Used in availability calculations

### 3.5 Machine Learning Module

#### predict.py (`ml/predict.py`)
**Purpose**: ⚠️ MAIN ML PREDICTION ENTRY POINT

**Input** (JSON via stdin):
```json
{
  "aircraft_type": "B 738",
  "operator_airline": "GARUDA",
  "category": "COMMERCIAL"
}
```

**Output** (JSON via stdout):
```json
{
  "success": true,
  "input": {
    "aircraft_type": "B 738",
    "operator_airline": "GARUDA",
    "category": "COMMERCIAL",
    "aircraft_size": "STANDARD",
    "airline_tier": "HIGH_FREQUENCY",
    "stand_zone": "RIGHT_COMMERCIAL"
  },
  "predictions": [
    {"stand": "C4", "probability": 0.45, "rank": 1},
    {"stand": "C3", "probability": 0.32, "rank": 2},
    {"stand": "C5", "probability": 0.15, "rank": 3}
  ],
  "metadata": {
    "model_path": "C:/xampp/htdocs/amc/ml/parking_stand_model_rf_redo.pkl",
    "top_k_requested": 3
  }
}
```

**Feature Engineering** (lines 81-162):
- `determine_aircraft_size()` - A0-compatible vs Standard
- `determine_airline_tier()` - High/Medium/Low frequency
- `determine_category_from_airline()` - Category inference
- `get_stand_zone()` - Zone assignment (LEFT_CARGO, RIGHT_COMMERCIAL, MIDDLE_CHARTER)

**⚠️ Hardcoded Business Rules**:
- A0-compatible aircraft list (lines 83-88)
- High frequency airlines (line 104)
- Medium frequency airlines (line 105)
- Cargo keywords (line 118)
- Commercial keywords (line 119)

**⚠️ CRITICAL FILES**:
- `ml/parking_stand_model_rf_redo.pkl` - Trained model (DO NOT DELETE)
- `ml/encoders_redo.pkl` - Label encoders (DO NOT DELETE)

---

## Fragile Areas (⚠️ CRITICAL)

### 4.1 Most Error-Prone Components

#### 1. PHP to Python Integration (ApronController::recommend)
**File**: `app/Controllers/ApronController.php` (lines ~800-900)

**Why Fragile**:
- Depends on Python being in system PATH
- proc_open can hang if Python crashes
- JSON encoding/decoding must match exactly
- Windows file paths break on Linux
- No timeout handling (can block indefinitely)

**What Breaks It**:
- Python not installed or not in PATH
- Model files (.pkl) missing or corrupted
- Python package dependencies missing (numpy, pandas, scikit-learn)
- JSON format mismatch between PHP and Python
- File permissions on cache directory

**How to Detect Issues**:
- Check error logs for proc_open failures
- Check stderr output from Python
- Verify model files exist and are readable
- Test with: `php tools/test_proc_open_integration.php`

**Prevention**:
- Always verify Python PATH before deployment
- Keep model files backed up
- Add timeout to proc_open calls
- Log all Python stderr output

#### 2. FileCache System
**File**: `app/Core/Cache/FileCache.php`

**Why Fragile**:
- File system permissions can break caching
- Cache directory must be writable
- Concurrent writes can cause race conditions
- TTL expiration depends on system time

**What Breaks It**:
- Cache directory not writable (permission errors)
- Disk full (cache writes fail silently)
- System time changes (expiration calculations break)
- JSON encoding failures (returns false silently)

**How to Detect Issues**:
- Check if `cache/` directory exists and is writable
- Monitor error logs for file_put_contents failures
- Check cache hit rates (low hit rate = caching broken)

**Prevention**:
- Ensure cache/ directory has 755 permissions
- Add disk space monitoring
- Use try-catch around cache operations
- Implement cache warmup on deployment

#### 3. ApronStatusService (Real-time Calculations)
**File**: `app/Services/ApronStatusService.php`

**Why Fragile**:
- Complex SQL queries with multiple joins
- Performance degrades with large movement history
- RON status calculations can be inconsistent
- Called on every apron page load (performance-sensitive)

**What Breaks It**:
- Missing database indexes (slow queries)
- Incorrect RON status in database
- Orphaned movements (movement without aircraft_details record)
- NULL values in critical fields (movement_date, parking_stand)

**How to Detect Issues**:
- Slow page loads on apron view
- Incorrect stand counts
- RON counts don't match reality
- Check query execution time in logs

**Prevention**:
- Ensure performance indexes are applied (database/migrations/add_performance_indexes.sql)
- Validate data integrity (no NULL parking_stands for active movements)
- Add query result caching (currently not implemented)
- Monitor query performance

#### 4. Database Queries in AircraftMovementRepository
**File**: `app/Repositories/AircraftMovementRepository.php`

**Why Fragile**:
- Complex JOINs (aircraft_movements, aircraft_details, stands, flight_references)
- Multiple optional filters (date, category, operator, RON status)
- Performance-sensitive (used in apron view, dashboard, reports)

**What Breaks It**:
- Missing indexes on filter columns
- NULL values in JOIN columns
- Large date ranges (millions of rows scanned)
- Incorrect data types (string comparisons on dates)

**How to Detect Issues**:
- Slow query performance (> 1 second)
- Timeout errors on reports
- Incorrect result counts

**Prevention**:
- Always use indexes on: movement_date, parking_stand, category, operator_airline, is_ron
- Add EXPLAIN ANALYZE to slow queries
- Limit date ranges to 1 month max
- Add query result pagination

#### 5. Session Management and Authentication
**Files**: `app/Core/Auth/AuthManager.php`, `app/Middleware/AuthMiddleware.php`

**Why Fragile**:
- Session timeout (30 minutes inactivity)
- Session fixation vulnerabilities if not handled correctly
- Concurrent logins can cause session conflicts
- Session data can grow large (performance impact)

**What Breaks It**:
- Session directory not writable
- session_start() called multiple times (headers already sent)
- Session cookie not set (browser security settings)
- Session timeout too short (users logged out mid-work)

**How to Detect Issues**:
- Users randomly logged out
- "Headers already sent" errors
- Session data not persisting
- Login throttler not working

**Prevention**:
- Call session_start() only once (in bootstrap/app.php)
- Set session cookie parameters correctly (config/session.php)
- Monitor session storage size
- Implement session cleanup (garbage collection)

### 4.2 Load-Bearing Components (Remove = System Breaks)

**DO NOT DELETE OR RENAME THESE**:

1. **ml/parking_stand_model_rf_redo.pkl** - Trained ML model
   - Deleting this breaks all predictions
   - Retraining required if deleted

2. **ml/encoders_redo.pkl** - Label encoders
   - Deleting this breaks feature encoding
   - Must be regenerated with model retraining

3. **bootstrap/app.php** - Application initialization
   - Deleting this breaks the entire application
   - DI container won't be initialized

4. **routes/web.php and routes/api.php** - Route definitions
   - Deleting these makes all pages 404
   - Controllers won't be accessible

5. **app/Core/Database/DatabaseManager.php** - Database connection
   - Deleting this breaks all database operations
   - No queries will execute

6. **config/database.php** - Database configuration
   - Deleting this causes PDO connection failures
   - Must have valid credentials

### 4.3 Circular Dependencies and Tight Coupling

**⚠️ KNOWN CIRCULAR DEPENDENCIES**:

1. **ApronController → ApronStatusService → AircraftMovementRepository → PDO**
   - Changing repository interface breaks service
   - Changing service interface breaks controller

2. **AuthMiddleware → AuthManager → Session**
   - Session must be started before middleware runs
   - Middleware must run before controllers

3. **FileCache → Filesystem**
   - Cache directory must exist before FileCache instantiation
   - FileCache is used in controllers (tight coupling)

**⚠️ TIGHT COUPLING ISSUES**:

1. **Controllers directly call Python exec()**
   - Should be abstracted into a PredictionService
   - Currently hardcoded in ApronController

2. **Hardcoded file paths throughout codebase**
   - Windows-style paths (`C:\xampp\...`)
   - Should use path constants from config

3. **Direct $_SESSION access in multiple places**
   - Should be abstracted through AuthManager
   - Currently mixed with direct access

### 4.4 Hardcoded Values That Should Be Configurable

**⚠️ HARDCODED CONFIGURATION**:

1. **ML Feature Engineering Rules** (`ml/predict.py`)
   ```python
   # Lines 83-88: A0-compatible aircraft
   A0_COMPATIBLE = ['C 152', 'C 172', ...]

   # Lines 104-105: Airline tiers
   HIGH_FREQ_AIRLINES = ['BATIK AIR', 'CITILINK', 'GARUDA', ...]

   # Lines 118-119: Category keywords
   CARGO_KEYWORDS = ['CARGO', 'TRI MG', ...]
   ```
   **Should be**: Database table or config file

2. **Cache TTL** (`app/Core/Cache/FileCache.php`)
   ```php
   protected int $defaultTtl = 300;  // 5 minutes hardcoded
   ```
   **Should be**: config/cache.php

3. **Session Timeout** (`config/app.php`)
   ```php
   'session_timeout' => 1800,  // 30 minutes
   ```
   **Good**: Already in config (but could be env var)

4. **Login Throttling** (`config/app.php`)
   ```php
   'login' => [
       'max_attempts' => 5,
       'lockout_seconds' => 900,  // 15 minutes
   ]
   ```
   **Good**: Already in config

5. **Python Path** (`app/Controllers/ApronController.php`)
   ```php
   $pythonPath = 'python';  // Assumes in PATH
   ```
   **Should be**: config/ml.php or .env file

### 4.5 Technical Debt and Hacky Solutions

**⚠️ KNOWN TECHNICAL DEBT**:

1. **Legacy PHP files in root** (login.php, dashboard.php, etc.)
   - Should be removed (redundant with router)
   - Kept for backward compatibility
   - Cause confusion (which file is the entry point?)

2. **Mixed routing approaches**
   - Modern router in routes/ directory
   - Legacy files still accessible directly
   - Should standardize on router-only

3. **No automated testing**
   - Tests directory exists but minimal tests
   - No CI/CD pipeline
   - Manual testing only

4. **No database migrations system**
   - SQL migrations in database/migrations/ but not automated
   - Must be applied manually
   - No rollback capability

5. **Mixed ORM and raw SQL**
   - Models use query builder
   - Repositories use raw PDO
   - Inconsistent approach

6. **No environment configuration**
   - Config uses getenv() but no .env file support
   - Environment variables must be set in system
   - Should use vlucas/phpdotenv

7. **No logging framework**
   - Uses error_log() directly
   - No log levels (debug, info, error)
   - Should use Monolog

8. **Frontend has no build system**
   - Tailwind CSS requires manual build
   - No asset versioning
   - No minification

---

## MVC Pattern Implementation

### 5.1 Model Layer

**Pattern**: ActiveRecord-style models with repository support

**Base Model** (`app/Models/Model.php`):
- Provides query builder methods
- Handles table mapping
- Primary key management
- Timestamps (created_at, updated_at)

**Example Model** (`app/Models/AircraftMovement.php`):
```php
class AircraftMovement extends Model
{
    protected string $table = 'aircraft_movements';
    protected string $primaryKey = 'id';

    // Relationships
    public function aircraftDetail() { ... }
    public function stand() { ... }
    public function flightReference() { ... }
}
```

**⚠️ Important**: Models are used for simple queries. Complex queries use Repositories.

### 5.2 View Layer

**Pattern**: Plain PHP templates (NOT Blade, NOT Twig)

**Structure**:
```
resources/views/
├── layouts/app.php          # Main layout (includes header, nav, footer)
├── apron/index.php           # Apron view page
├── dashboard/index.php       # Dashboard page
└── partials/                 # Reusable components (modals, forms)
```

**Data Passing**:
```php
// In Controller
return $this->view('apron/index', [
    'username' => $user['username'],
    'apronStatus' => $apronStatus,
    'currentMovements' => $movements,
]);

// In View (resources/views/apron/index.php)
<h1>Welcome, <?= htmlspecialchars($username) ?></h1>
```

**⚠️ Security**: Always use `htmlspecialchars()` for output escaping

### 5.3 Controller Layer

**Pattern**: Fat controllers (business logic in controllers)

**Base Controller** (`app/Core/Controller.php`):
```php
abstract class Controller
{
    protected function view(string $name, array $data = []): Response
    protected function json($data, int $status = 200): Response
    protected function redirect(string $url): Response
    protected function request(): Request
    protected function hasRole(array $roles): bool
}
```

**Controller Responsibilities**:
1. Handle HTTP requests
2. Validate input
3. Call services/repositories
4. Return responses

**⚠️ Note**: Controllers contain business logic (should be refactored to services)

---

## File Naming Conventions

### 6.1 PHP Files

**Classes**:
- PascalCase: `ApronController.php`, `AircraftMovement.php`
- One class per file
- Namespace matches directory structure

**Views**:
- kebab-case: `apron/index.php`, `dashboard/partials/aircraft-modal.php`
- No uppercase letters

**Config**:
- lowercase: `database.php`, `app.php`, `session.php`

### 6.2 Python Files

**Scripts**:
- snake_case: `predict.py`, `train_model.py`, `model_cache.py`

**Model Files**:
- snake_case with suffix: `parking_stand_model_rf_redo.pkl`
- Encoders: `encoders_redo.pkl`

### 6.3 Database

**Tables**:
- snake_case: `aircraft_movements`, `daily_snapshots`, `ml_prediction_log`
- Plural names for data tables
- Singular for lookup/config tables

**Columns**:
- snake_case: `movement_date`, `off_block_time`, `operator_airline`
- No abbreviations unless standard (e.g., `id`, `pk`)

### 6.4 JavaScript/CSS

**JavaScript**:
- camelCase functions: `fetchApronStatus()`, `updateMovementTable()`
- Currently no module system (plain JS)

**CSS**:
- Tailwind utility classes (no custom CSS)
- Output file: `public/css/output.css`

---

## Summary of Critical Points

**⚠️ TOP 5 THINGS THAT BREAK EASILY**:

1. **Python execution in ApronController** - Most fragile, depends on PATH, model files, Python packages
2. **FileCache system** - Depends on filesystem permissions, can fail silently
3. **ApronStatusService queries** - Performance degrades without indexes, complex SQL
4. **Session management** - Timeout issues, concurrent login problems
5. **Database integrity** - NULL values, missing foreign key references

**⚠️ LOAD-BEARING FILES (DO NOT DELETE)**:
- `ml/parking_stand_model_rf_redo.pkl`
- `ml/encoders_redo.pkl`
- `bootstrap/app.php`
- `routes/web.php` and `routes/api.php`
- `config/database.php`

**⚠️ CRITICAL INTEGRATION POINTS**:
- PHP → Python (proc_open with JSON)
- PHP → Database (PDO with prepared statements)
- Frontend → Backend (AJAX with CSRF tokens)

**If you change X, Y might break**:
- Change model files → All predictions fail
- Change router → All pages 404
- Change session config → Users logged out
- Change database schema → Queries break (no migration rollback)
- Change cache directory → ML predictions slow down 10x
- Change Python PATH → Predictions fail with proc_open error
