# Database Schema - AMC Parking Stand Prediction System

## Table of Contents
1. [Database Overview](#database-overview)
2. [Table Categories](#table-categories)
3. [Complete Schema](#complete-schema)
4. [Table Relationships](#table-relationships)
5. [Critical Queries](#critical-queries)
6. [Data Validation Rules](#data-validation-rules)
7. [Performance Indexes](#performance-indexes)
8. [Sample Data Structure](#sample-data-structure)

---

## Database Overview

**Database Name**: `amc`
**DBMS**: MariaDB 10.4.32 (MySQL-compatible)
**Character Set**: utf8mb4
**Collation**: utf8mb4_unicode_ci
**Engine**: InnoDB (all tables)
**Total Tables**: 12

### Database Connection (config/database.php)
```php
[
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'amc',
    'username' => 'root',
    'password' => '',  // Default empty for XAMPP
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]
```

---

## Table Categories

### Core Operational Tables (Real-time Data)
1. **aircraft_movements** - Aircraft movement tracking (arrivals, departures, RON)
2. **stands** - Parking stand master data
3. **daily_staff_roster** - Staff assignments

### Master Data Tables (Reference Data)
4. **aircraft_details** - Aircraft registration and details
5. **flight_references** - Flight number references
6. **airline_preferences** - Airline stand preferences

### ML System Tables (Machine Learning)
7. **ml_prediction_log** - ML prediction history and feedback
8. **ml_model_versions** - Model version tracking

### Audit and Security Tables
9. **audit_log** - System activity audit trail
10. **login_attempts** - Login throttling and security
11. **users** - User accounts and authentication

### Snapshot Tables (Historical Data)
12. **daily_snapshots** - Daily operational snapshots

### Legacy Tables (Not in Active Use)
13. **narrative_logbook_amc** - Narrative log entries (legacy)

---

## Complete Schema

### 1. aircraft_details (Master Data)

**Purpose**: Master data for aircraft registrations and operator information

```sql
CREATE TABLE `aircraft_details` (
  `registration` varchar(10) NOT NULL,
  `aircraft_type` varchar(30) DEFAULT NULL,
  `operator_airline` varchar(100) DEFAULT NULL,
  `category` varchar(20) NOT NULL COMMENT 'Commercial, Cargo, Charter',
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`registration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `registration` (PK) - Aircraft registration number (e.g., PK-GFA, N-227GV)
- `aircraft_type` - ICAO aircraft type code (e.g., B 738, A 320, C 208)
- `operator_airline` - Operating airline name (e.g., GARUDA, BATIK AIR, CITILINK)
- `category` - Aircraft category: 'commercial', 'cargo', 'charter' (case-insensitive)
- `notes` - Free-text notes
- `updated_at` - Auto-updated on row change

**Indexes**:
- PRIMARY KEY on `registration`

**Sample Record**:
```
registration: PK-GFA
aircraft_type: B 738
operator_airline: GARUDA
category: commercial
notes: NULL
updated_at: 2025-10-26 06:31:41
```

**⚠️ Critical Notes**:
- Registration is PRIMARY KEY (must be unique)
- Category field is case-insensitive ('Komersial' = 'commercial', 'Charter' = 'charter')
- Used extensively in ML predictions for feature engineering
- NO foreign keys (standalone master table)

---

### 2. aircraft_movements (Core Operational)

**Purpose**: ⚠️ MOST CRITICAL TABLE - Tracks all aircraft movements (arrivals, departures, RON status)

```sql
CREATE TABLE `aircraft_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movement_date` date NOT NULL,
  `registration` varchar(10) NOT NULL,
  `flight_no` varchar(20) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `on_block_time` time DEFAULT NULL,
  `on_block_date` date DEFAULT NULL,
  `off_block_time` time DEFAULT NULL,
  `off_block_date` date DEFAULT NULL,
  `parking_stand` varchar(10) DEFAULT NULL,
  `is_ron` tinyint(1) DEFAULT 0,
  `ron_complete` tinyint(1) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `user_id_created` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `user_id_updated` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aircraft_movements_registration_index` (`registration`),
  KEY `aircraft_movements_movement_date_index` (`movement_date`),
  KEY `aircraft_movements_parking_stand_index` (`parking_stand`),
  KEY `aircraft_movements_user_id_created_foreign` (`user_id_created`),
  KEY `aircraft_movements_user_id_updated_foreign` (`user_id_updated`),
  KEY `idx_on_block_date` (`on_block_date`),
  KEY `idx_off_block_date` (`off_block_date`),
  KEY `idx_ron_complete` (`ron_complete`),
  KEY `idx_movement_date_ron` (`movement_date`,`is_ron`,`ron_complete`),
  CONSTRAINT `aircraft_movements_user_id_created_foreign` FOREIGN KEY (`user_id_created`) REFERENCES `users` (`id`),
  CONSTRAINT `aircraft_movements_user_id_updated_foreign` FOREIGN KEY (`user_id_updated`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT) - Unique movement ID
- `movement_date` - Date of movement (YYYY-MM-DD)
- `registration` - Aircraft registration (links to aircraft_details, but NO FK)
- `flight_no` - Flight number (e.g., GA101, ID6231)
- `origin` - Origin airport code (e.g., CGK, SUB)
- `destination` - Destination airport code
- `on_block_time` / `on_block_date` - Arrival time and date
- `off_block_time` / `off_block_date` - Departure time and date
- `parking_stand` - Assigned parking stand (e.g., A1, B2, C4)
- `is_ron` - Remain Overnight flag (0 or 1)
- `ron_complete` - RON completion status (0 or 1)
- `remarks` - Free-text remarks
- `user_id_created` / `user_id_updated` - Audit trail (FK to users table)
- `created_at` / `updated_at` - Timestamps

**Indexes** (⚠️ CRITICAL FOR PERFORMANCE):
- PRIMARY KEY on `id`
- Index on `registration` (for aircraft lookup)
- Index on `movement_date` (for date range queries)
- Index on `parking_stand` (for stand occupancy)
- Index on `user_id_created`, `user_id_updated` (for audit)
- Index on `on_block_date`, `off_block_date` (for arrival/departure queries)
- Index on `ron_complete` (for RON status)
- **Composite Index** on `(movement_date, is_ron, ron_complete)` (for apron status queries)

**Foreign Keys**:
- `user_id_created` → `users.id`
- `user_id_updated` → `users.id`

**Sample Record**:
```
id: 1
movement_date: 2025-11-24
registration: PK-GFA
flight_no: GA101
origin: CGK
destination: DPS
on_block_time: 08:30:00
on_block_date: 2025-11-24
off_block_time: NULL
off_block_date: NULL
parking_stand: C4
is_ron: 0
ron_complete: 0
remarks: NULL
user_id_created: 1
created_at: 2025-11-24 08:00:00
user_id_updated: NULL
updated_at: NULL
```

**⚠️ Critical Notes**:
- This table is the heart of the system - DO NOT DELETE ROWS
- RON logic: `is_ron=1` means aircraft will remain overnight; `ron_complete=1` means RON is done
- `parking_stand` can be NULL (aircraft not yet assigned)
- **NO foreign key to aircraft_details** (allows orphaned movements, but more flexible)
- Performance degrades if indexes are missing
- Used in: ApronController, DashboardController, ReportService, ApronStatusService

---

### 3. stands (Master Data)

**Purpose**: Parking stand master data

```sql
CREATE TABLE `stands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stand_name` varchar(10) NOT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stands_stand_name_unique` (`stand_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `stand_name` (UNIQUE) - Stand identifier (e.g., A1, B2, C4, H1-HANGAR)
- `zone` - Stand zone (e.g., 'LEFT_CARGO', 'RIGHT_COMMERCIAL', 'MIDDLE_CHARTER')
- `status` - Stand status: 'active', 'inactive', 'maintenance'
- `notes` - Free-text notes

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE KEY on `stand_name`

**Sample Record**:
```
id: 1
stand_name: C4
zone: RIGHT_COMMERCIAL
status: active
notes: NULL
```

**⚠️ Critical Notes**:
- Stand names must be unique
- Hangar stands typically have "HANGAR" in the name (e.g., H1-HANGAR)
- Zone assignment affects ML predictions
- Status='inactive' stands should not be assigned

---

### 4. daily_staff_roster (Core Operational)

**Purpose**: Daily staff roster and on-duty tracking

```sql
CREATE TABLE `daily_staff_roster` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `duty_date` date NOT NULL,
  `shift` enum('DAY','NIGHT') NOT NULL DEFAULT 'DAY',
  `supervisor` varchar(100) DEFAULT NULL,
  `operator_1` varchar(100) DEFAULT NULL,
  `operator_2` varchar(100) DEFAULT NULL,
  `operator_3` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `duty_date` - Date of roster (YYYY-MM-DD)
- `shift` - Shift type: 'DAY' or 'NIGHT'
- `supervisor` - Supervisor name
- `operator_1`, `operator_2`, `operator_3` - Operator names
- `remarks` - Free-text remarks
- `created_at` / `updated_at` - Timestamps

**Indexes**:
- PRIMARY KEY on `id`

**Sample Record**:
```
id: 1
duty_date: 2025-11-24
shift: DAY
supervisor: John Doe
operator_1: Jane Smith
operator_2: Bob Johnson
operator_3: Alice Brown
remarks: NULL
created_at: 2025-11-24 00:00:00
updated_at: NULL
```

**⚠️ Critical Notes**:
- One roster per shift per day (but no UNIQUE constraint - potential duplicates)
- Should ideally have UNIQUE constraint on (duty_date, shift)
- Used in apron view header to show on-duty staff

---

### 5. flight_references (Master Data)

**Purpose**: Flight number reference data

```sql
CREATE TABLE `flight_references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flight_no` varchar(20) NOT NULL,
  `airline` varchar(100) DEFAULT NULL,
  `route` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_references_flight_no_index` (`flight_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `flight_no` - Flight number (e.g., GA101, ID6231)
- `airline` - Operating airline
- `route` - Flight route (e.g., CGK-DPS)

**Indexes**:
- PRIMARY KEY on `id`
- Index on `flight_no` (for lookup)

**Sample Record**:
```
id: 1
flight_no: GA101
airline: GARUDA
route: CGK-DPS
```

**⚠️ Critical Notes**:
- Used for autofill in movement forms
- NO foreign keys (standalone reference table)

---

### 6. airline_preferences (Master Data)

**Purpose**: Airline stand preferences for ML training

```sql
CREATE TABLE `airline_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `airline_name` varchar(100) NOT NULL,
  `airline_category` varchar(50) DEFAULT NULL,
  `aircraft_type` varchar(30) DEFAULT NULL,
  `stand_name` varchar(10) NOT NULL,
  `preference_score` int(11) DEFAULT 1,
  `historical_count` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_preference` (`airline_name`,`aircraft_type`,`stand_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `airline_name` - Airline name
- `airline_category` - Airline category (e.g., 'commercial', 'cargo')
- `aircraft_type` - Aircraft type
- `stand_name` - Preferred stand
- `preference_score` - Preference weight (higher = more preferred)
- `historical_count` - Historical usage count
- `active` - Active flag (0 or 1)

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE KEY on `(airline_name, aircraft_type, stand_name)`

**Sample Record**:
```
id: 1
airline_name: GARUDA
airline_category: commercial
aircraft_type: B 738
stand_name: C4
preference_score: 10
historical_count: 50
active: 1
```

**⚠️ Critical Notes**:
- Used in ML training (not real-time predictions)
- Can be precomputed from aircraft_movements history
- Unique constraint prevents duplicates

---

### 7. ml_prediction_log (ML System)

**Purpose**: ⚠️ CRITICAL - ML prediction history and feedback loop

```sql
CREATE TABLE `ml_prediction_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prediction_token` varchar(64) NOT NULL,
  `prediction_date` datetime NOT NULL,
  `aircraft_type` varchar(30) DEFAULT NULL,
  `operator_airline` varchar(100) DEFAULT NULL,
  `category` varchar(20) DEFAULT NULL,
  `predicted_stand` varchar(10) DEFAULT NULL,
  `actual_stand` varchar(10) DEFAULT NULL,
  `was_prediction_correct` tinyint(1) DEFAULT NULL,
  `prediction_score` decimal(5,4) DEFAULT NULL,
  `model_version` int(11) DEFAULT NULL,
  `requested_by_user` int(11) DEFAULT NULL,
  `assigned_by_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ml_prediction_log_token_unique` (`prediction_token`),
  KEY `ml_prediction_log_model_version_index` (`model_version`),
  KEY `ml_prediction_log_requested_by_foreign` (`requested_by_user`),
  KEY `ml_prediction_log_assigned_by_foreign` (`assigned_by_user`),
  CONSTRAINT `ml_prediction_log_assigned_by_foreign` FOREIGN KEY (`assigned_by_user`) REFERENCES `users` (`id`),
  CONSTRAINT `ml_prediction_log_requested_by_foreign` FOREIGN KEY (`requested_by_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `prediction_token` (UNIQUE) - Unique prediction identifier (MD5 hash)
- `prediction_date` - When prediction was made
- `aircraft_type`, `operator_airline`, `category` - Input features
- `predicted_stand` - Top-1 predicted stand
- `actual_stand` - Actually assigned stand (for feedback)
- `was_prediction_correct` - Feedback flag (1=correct, 0=incorrect, NULL=no feedback)
- `prediction_score` - Top-1 prediction probability (0.0000 to 1.0000)
- `model_version` - ML model version used
- `requested_by_user` - User who requested prediction (FK to users)
- `assigned_by_user` - User who assigned actual stand (FK to users)

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE KEY on `prediction_token`
- Index on `model_version` (for model performance analysis)
- Index on `requested_by_user`, `assigned_by_user` (for audit)

**Foreign Keys**:
- `requested_by_user` → `users.id`
- `assigned_by_user` → `users.id`

**Sample Record**:
```
id: 1
prediction_token: 5d41402abc4b2a76b9719d911017c592
prediction_date: 2025-11-24 10:30:00
aircraft_type: B 738
operator_airline: GARUDA
category: commercial
predicted_stand: C4
actual_stand: C4
was_prediction_correct: 1
prediction_score: 0.4500
model_version: 2
requested_by_user: 1
assigned_by_user: 1
```

**⚠️ Critical Notes**:
- Used for ML model performance tracking
- `was_prediction_correct` is set when actual stand is assigned
- Prediction token prevents duplicate logging
- Used in: ApronController::recommend(), Dashboard ML metrics

---

### 8. ml_model_versions (ML System)

**Purpose**: ML model version tracking

```sql
CREATE TABLE `ml_model_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version_number` int(11) NOT NULL,
  `model_filename` varchar(255) NOT NULL,
  `accuracy_score` decimal(5,4) DEFAULT NULL,
  `training_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ml_model_versions_version_number_unique` (`version_number`),
  KEY `ml_model_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `ml_model_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `version_number` (UNIQUE) - Model version number (e.g., 1, 2, 3)
- `model_filename` - Model file name (e.g., parking_stand_model_rf_redo.pkl)
- `accuracy_score` - Model accuracy (0.0000 to 1.0000)
- `training_date` - When model was trained
- `notes` - Free-text notes
- `is_active` - Active flag (1=currently deployed)
- `created_by` - User who created version (FK to users)
- `created_at` - Creation timestamp

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE KEY on `version_number`
- Index on `created_by`

**Foreign Keys**:
- `created_by` → `users.id`

**Sample Record**:
```
id: 2
version_number: 2
model_filename: parking_stand_model_rf_redo.pkl
accuracy_score: 0.8015
training_date: 2025-11-10 15:00:00
notes: Random Forest with feature engineering
is_active: 1
created_by: 1
created_at: 2025-11-10 15:00:00
```

**⚠️ Critical Notes**:
- Only one model should have `is_active=1` at a time
- Used for model versioning and rollback capability
- Currently not fully integrated (manual management)

---

### 9. audit_log (Audit)

**Purpose**: System activity audit trail

```sql
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `changes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_log_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `user_id` - User who performed action (FK to users)
- `action` - Action performed (e.g., 'CREATE', 'UPDATE', 'DELETE')
- `table_name` - Affected table
- `record_id` - Affected record ID
- `changes` - JSON of changes
- `ip_address` - User IP address
- `created_at` - Timestamp

**Indexes**:
- PRIMARY KEY on `id`
- Index on `user_id`

**Foreign Keys**:
- `user_id` → `users.id`

**Sample Record**:
```
id: 1
user_id: 1
action: UPDATE
table_name: aircraft_movements
record_id: 123
changes: {"parking_stand": {"old": "C3", "new": "C4"}}
ip_address: 192.168.1.100
created_at: 2025-11-24 10:30:00
```

**⚠️ Critical Notes**:
- Used by AuditLogger service
- Should not be deleted (permanent audit trail)
- Can grow large over time (implement archival strategy)

---

### 10. login_attempts (Security)

**Purpose**: Login throttling and brute force protection

```sql
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `ip_address` - Attacker IP address
- `username` - Attempted username
- `attempt_time` - When attempt was made

**Indexes**:
- PRIMARY KEY on `id`
- **Composite Index** on `(ip_address, attempt_time)` (for throttle queries)

**Sample Record**:
```
id: 1
ip_address: 192.168.1.100
username: admin
attempt_time: 2025-11-24 10:30:00
```

**⚠️ Critical Notes**:
- Used by LoginThrottler service
- Throttling rules (config/app.php):
  - Max 5 attempts per IP
  - 15-minute lockout period
- Should be cleaned periodically (old attempts)

---

### 11. users (Security)

**Purpose**: User accounts and authentication

```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','viewer') NOT NULL DEFAULT 'viewer',
  `full_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `username` (UNIQUE) - Login username
- `email` (UNIQUE) - User email
- `password` - Bcrypt hashed password
- `role` - User role: 'admin', 'operator', 'viewer'
- `full_name` - Full name
- `is_active` - Active flag (0=disabled, 1=active)
- `created_at` / `updated_at` - Timestamps

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE KEY on `username`
- UNIQUE KEY on `email`
- Index on `role` (for role-based queries)

**Sample Record**:
```
id: 1
username: admin
email: admin@example.com
password: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
role: admin
full_name: System Administrator
is_active: 1
created_at: 2025-11-01 00:00:00
updated_at: NULL
```

**⚠️ Critical Notes**:
- Passwords are **bcrypt hashed** (never store plain text)
- Roles define access control (config/app.php):
  - `admin` - Full access
  - `operator` - Can manage movements, no user management
  - `viewer` - Read-only access (redirected from dashboard)
- Used in: AuthManager, AuthMiddleware, UserController

---

### 12. daily_snapshots (Snapshot)

**Purpose**: Daily operational snapshots for historical analysis

```sql
CREATE TABLE `daily_snapshots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `snapshot_date` date NOT NULL,
  `total_movements` int(11) DEFAULT 0,
  `total_commercial` int(11) DEFAULT 0,
  `total_cargo` int(11) DEFAULT 0,
  `total_charter` int(11) DEFAULT 0,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_snapshot_date` (`snapshot_date`),
  KEY `daily_snapshots_created_by_user_id_foreign` (`created_by_user_id`),
  CONSTRAINT `daily_snapshots_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Fields**:
- `id` (PK, AUTO_INCREMENT)
- `snapshot_date` (UNIQUE) - Date of snapshot
- `total_movements` - Total movements for the day
- `total_commercial`, `total_cargo`, `total_charter` - Category breakdowns
- `created_by_user_id` - User who created snapshot (FK to users)
- `created_at` - Creation timestamp

**Indexes**:
- PRIMARY KEY on `id`
- UNIQUE KEY on `snapshot_date` (one snapshot per day)
- Index on `created_by_user_id`

**Foreign Keys**:
- `created_by_user_id` → `users.id`

**Sample Record**:
```
id: 1
snapshot_date: 2025-11-23
total_movements: 45
total_commercial: 30
total_cargo: 10
total_charter: 5
created_by_user_id: 1
created_at: 2025-11-23 23:59:00
```

**⚠️ Critical Notes**:
- Automated creation via cron job (tools/console.php daily_snapshot)
- Should run daily at 23:59
- One snapshot per day (enforced by UNIQUE constraint)

---

### 13. narrative_logbook_amc (Legacy)

**Purpose**: Narrative log entries (legacy, not actively used)

```sql
CREATE TABLE `narrative_logbook_amc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_date` date DEFAULT NULL,
  `log_entry` text DEFAULT NULL,
  `entered_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `narrative_logbook_amc_entered_by_user_id_foreign` (`entered_by_user_id`),
  CONSTRAINT `narrative_logbook_amc_entered_by_user_id_foreign` FOREIGN KEY (`entered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**⚠️ Note**: This table exists but is not actively used in current system.

---

## Table Relationships

### Entity-Relationship Diagram (Text)

```
users (1) ----< (N) aircraft_movements [user_id_created, user_id_updated]
users (1) ----< (N) audit_log [user_id]
users (1) ----< (N) daily_snapshots [created_by_user_id]
users (1) ----< (N) ml_model_versions [created_by]
users (1) ----< (N) ml_prediction_log [requested_by_user, assigned_by_user]
users (1) ----< (N) narrative_logbook_amc [entered_by_user_id]

aircraft_details (1) ---<< (N) aircraft_movements [registration] (NO FK - soft link)
stands (1) ---<< (N) aircraft_movements [parking_stand] (NO FK - soft link)
flight_references (1) ---<< (N) aircraft_movements [flight_no] (NO FK - soft link)

ml_model_versions (1) ---<< (N) ml_prediction_log [model_version] (NO FK - soft link)

airline_preferences (standalone, no relationships)
login_attempts (standalone, no relationships)
daily_staff_roster (standalone, no relationships)
```

### Foreign Key Relationships

**Enforced Foreign Keys** (ON DELETE/UPDATE constraints):

1. `aircraft_movements.user_id_created` → `users.id`
2. `aircraft_movements.user_id_updated` → `users.id`
3. `audit_log.user_id` → `users.id`
4. `daily_snapshots.created_by_user_id` → `users.id`
5. `ml_model_versions.created_by` → `users.id`
6. `ml_prediction_log.requested_by_user` → `users.id`
7. `ml_prediction_log.assigned_by_user` → `users.id`
8. `narrative_logbook_amc.entered_by_user_id` → `users.id` (ON DELETE SET NULL)

**⚠️ Soft Relationships (NO Foreign Keys)**:

1. `aircraft_movements.registration` → `aircraft_details.registration` (soft link, allows orphans)
2. `aircraft_movements.parking_stand` → `stands.stand_name` (soft link)
3. `aircraft_movements.flight_no` → `flight_references.flight_no` (soft link)
4. `ml_prediction_log.model_version` → `ml_model_versions.version_number` (soft link)

**Why Soft Links?**:
- Flexibility: Allows movements for unregistered aircraft
- Data integrity: Prevents cascading deletes
- Performance: No FK constraint checking overhead

---

## Critical Queries

### 1. Current Movements (Apron View)

**Purpose**: Get all current movements for today's apron view

```sql
SELECT
    am.id,
    am.movement_date,
    am.registration,
    am.flight_no,
    am.origin,
    am.destination,
    am.on_block_time,
    am.on_block_date,
    am.off_block_time,
    am.off_block_date,
    am.parking_stand,
    am.is_ron,
    am.ron_complete,
    am.remarks,
    ad.aircraft_type,
    ad.operator_airline,
    ad.category
FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration
WHERE am.movement_date = CURDATE()
ORDER BY am.on_block_time ASC;
```

**⚠️ Performance**: Uses `movement_date` index. Fast for single-day queries.

### 2. Stand Availability

**Purpose**: Calculate available stands (not currently occupied)

```sql
SELECT s.stand_name
FROM stands s
WHERE s.status = 'active'
  AND s.stand_name NOT IN (
      SELECT am.parking_stand
      FROM aircraft_movements am
      WHERE am.movement_date = CURDATE()
        AND am.parking_stand IS NOT NULL
        AND (am.off_block_time IS NULL OR (am.is_ron = 1 AND am.ron_complete = 0))
  );
```

**⚠️ Logic**:
- Stand is occupied if: arrival recorded AND (no departure OR RON not complete)
- Uses subquery (can be slow with many movements)

### 3. ML Prediction Accuracy

**Purpose**: Calculate ML model accuracy

```sql
SELECT
    COUNT(*) AS total_predictions,
    SUM(was_prediction_correct) AS correct_predictions,
    (SUM(was_prediction_correct) / COUNT(*)) * 100 AS accuracy_percentage
FROM ml_prediction_log
WHERE was_prediction_correct IS NOT NULL;
```

**⚠️ Note**: Only includes predictions with feedback (actual stand assigned)

### 4. RON Movements

**Purpose**: Get all RON movements (overnight aircraft)

```sql
SELECT
    am.registration,
    am.parking_stand,
    am.on_block_date,
    am.ron_complete,
    ad.aircraft_type,
    ad.operator_airline
FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration
WHERE am.is_ron = 1
  AND am.ron_complete = 0
ORDER BY am.on_block_date DESC;
```

**⚠️ Performance**: Uses composite index `(movement_date, is_ron, ron_complete)`

### 5. Movement History (Date Range)

**Purpose**: Get movements for date range (reports)

```sql
SELECT
    am.*,
    ad.aircraft_type,
    ad.operator_airline,
    ad.category
FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration
WHERE am.movement_date BETWEEN ? AND ?
ORDER BY am.movement_date DESC, am.on_block_time DESC;
```

**⚠️ Performance**:
- Uses `movement_date` index
- Limit date ranges to 1 month to avoid slow queries

---

## Data Validation Rules

### aircraft_details
- `registration` - Required, max 10 chars, unique
- `category` - Required, must be 'commercial', 'cargo', or 'charter' (case-insensitive)
- `aircraft_type` - Optional, max 30 chars
- `operator_airline` - Optional, max 100 chars

### aircraft_movements
- `movement_date` - Required, valid date
- `registration` - Required, max 10 chars
- `parking_stand` - Optional (NULL if not assigned), max 10 chars
- `is_ron` - Boolean (0 or 1)
- `ron_complete` - Boolean (0 or 1)
- `on_block_time` / `off_block_time` - Valid time (HH:MM:SS)
- `on_block_date` / `off_block_date` - Valid date (YYYY-MM-DD)

**Business Rules**:
- Cannot mark RON complete if not RON (`is_ron=0`)
- Cannot mark RON if already departed (`off_block_time` set)
- `on_block_date` should be <= `off_block_date`

### users
- `username` - Required, max 100 chars, unique
- `email` - Required, max 100 chars, unique, valid email format
- `password` - Required, bcrypt hash (60 chars)
- `role` - Required, must be 'admin', 'operator', or 'viewer'
- `is_active` - Boolean (0 or 1)

**Business Rules**:
- Password must be hashed with `password_hash($password, PASSWORD_BCRYPT)`
- Role determines access level (enforced in AuthMiddleware)

### ml_prediction_log
- `prediction_token` - Required, max 64 chars, unique
- `prediction_date` - Required, valid datetime
- `predicted_stand` - Required, max 10 chars
- `prediction_score` - Optional, decimal(5,4), range 0.0000 to 1.0000
- `was_prediction_correct` - Optional, boolean (0, 1, or NULL)

**Business Rules**:
- `was_prediction_correct` set only when `actual_stand` is assigned
- `prediction_token` generated as MD5 hash of input features

---

## Performance Indexes

### Critical Indexes (DO NOT REMOVE)

**aircraft_movements**:
```sql
-- Date-based queries (most common)
KEY `aircraft_movements_movement_date_index` (`movement_date`)

-- Registration lookup
KEY `aircraft_movements_registration_index` (`registration`)

-- Stand occupancy queries
KEY `aircraft_movements_parking_stand_index` (`parking_stand`)

-- RON status queries (composite index)
KEY `idx_movement_date_ron` (`movement_date`,`is_ron`,`ron_complete`)

-- Arrival/departure queries
KEY `idx_on_block_date` (`on_block_date`)
KEY `idx_off_block_date` (`off_block_date`)
```

**users**:
```sql
-- Login queries
UNIQUE KEY `users_username_unique` (`username`)

-- Role-based access
KEY `idx_role` (`role`)
```

**login_attempts**:
```sql
-- Throttle queries (composite index)
KEY `idx_ip_time` (`ip_address`,`attempt_time`)
```

**ml_prediction_log**:
```sql
-- Prediction lookup
UNIQUE KEY `ml_prediction_log_token_unique` (`prediction_token`)

-- Model performance analysis
KEY `ml_prediction_log_model_version_index` (`model_version`)
```

### Additional Performance Indexes (from migrations)

**File**: `database/migrations/add_performance_indexes.sql`

```sql
-- Aircraft details category index
ALTER TABLE aircraft_details ADD INDEX idx_category (category);
ALTER TABLE aircraft_details ADD INDEX idx_category_operator (category, operator_airline);

-- Aircraft movements availability index
ALTER TABLE aircraft_movements ADD INDEX idx_availability (movement_date, off_block_time, is_ron, ron_complete);

-- Aircraft movements occupancy index
ALTER TABLE aircraft_movements ADD INDEX idx_occupancy (movement_date, is_ron, ron_complete, parking_stand);

-- Aircraft movements parking stand registration index
ALTER TABLE aircraft_movements ADD INDEX idx_parking_stand_registration (parking_stand, registration);

-- ML prediction log indexes
ALTER TABLE ml_prediction_log ADD INDEX idx_prediction_date (prediction_date);
ALTER TABLE ml_prediction_log ADD INDEX idx_prediction_accuracy (prediction_date, was_prediction_correct);
ALTER TABLE ml_prediction_log ADD INDEX idx_requested_by (requested_by_user);

-- Airline preferences index
ALTER TABLE airline_preferences ADD INDEX idx_airline_category_active (airline_category, active, airline_name);
```

**⚠️ To Apply Performance Indexes**:
```sql
mysql -u root -p amc < database/migrations/add_performance_indexes.sql
```

---

## Sample Data Structure

### Complete Movement Record Example

```json
{
  "id": 1,
  "movement_date": "2025-11-24",
  "registration": "PK-GFA",
  "flight_no": "GA101",
  "origin": "CGK",
  "destination": "DPS",
  "on_block_time": "08:30:00",
  "on_block_date": "2025-11-24",
  "off_block_time": null,
  "off_block_date": null,
  "parking_stand": "C4",
  "is_ron": 0,
  "ron_complete": 0,
  "remarks": null,
  "user_id_created": 1,
  "created_at": "2025-11-24 08:00:00",
  "user_id_updated": null,
  "updated_at": null,
  "aircraft_details": {
    "aircraft_type": "B 738",
    "operator_airline": "GARUDA",
    "category": "commercial"
  }
}
```

### ML Prediction Record Example

```json
{
  "id": 1,
  "prediction_token": "5d41402abc4b2a76b9719d911017c592",
  "prediction_date": "2025-11-24 10:30:00",
  "aircraft_type": "B 738",
  "operator_airline": "GARUDA",
  "category": "commercial",
  "predicted_stand": "C4",
  "actual_stand": "C4",
  "was_prediction_correct": 1,
  "prediction_score": 0.4500,
  "model_version": 2,
  "requested_by_user": 1,
  "assigned_by_user": 1
}
```

---

## Database Maintenance

### Regular Maintenance Tasks

1. **Clean old login attempts** (weekly):
```sql
DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

2. **Clean old audit logs** (monthly):
```sql
DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

3. **Optimize tables** (monthly):
```sql
OPTIMIZE TABLE aircraft_movements, ml_prediction_log, audit_log, login_attempts;
```

4. **Check orphaned movements** (quarterly):
```sql
SELECT am.registration
FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration
WHERE ad.registration IS NULL
GROUP BY am.registration;
```

5. **Backup database** (daily):
```bash
mysqldump -u root -p amc > backup_amc_$(date +%Y%m%d).sql
```

---

## Critical Database Notes

**⚠️ TOP RISKS**:

1. **Missing indexes** - Slow queries, timeouts on apron view
2. **Orphaned movements** - Movements with no aircraft_details record (soft link)
3. **NULL parking_stands** - Breaks availability calculations
4. **Large date ranges** - Slow report generation
5. **No database migrations** - Schema changes are manual

**⚠️ DATA INTEGRITY CHECKS**:

1. Check for NULL parking_stands in current movements:
```sql
SELECT COUNT(*) FROM aircraft_movements
WHERE movement_date = CURDATE()
  AND parking_stand IS NULL
  AND on_block_time IS NOT NULL;
```

2. Check for orphaned movements:
```sql
SELECT COUNT(*) FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration
WHERE ad.registration IS NULL;
```

3. Check for duplicate stand assignments (same stand, same time):
```sql
SELECT parking_stand, COUNT(*) as cnt
FROM aircraft_movements
WHERE movement_date = CURDATE()
  AND off_block_time IS NULL
GROUP BY parking_stand
HAVING cnt > 1;
```

---

## Backup and Recovery

### Backup Strategy
- **Daily**: Full database dump
- **Before schema changes**: Manual backup
- **Before model retraining**: Backup ml_prediction_log table

### Recovery Commands
```bash
# Full restore
mysql -u root -p amc < backup_amc_20251124.sql

# Single table restore
mysql -u root -p amc < backup_aircraft_movements.sql
```

---

## Summary

**Total Tables**: 12 (+ 1 legacy)
**Total Foreign Keys**: 8
**Critical Tables**: `aircraft_movements`, `aircraft_details`, `ml_prediction_log`
**Performance-Critical Indexes**: 15+
**Storage Engine**: InnoDB (ACID compliant)
**Character Set**: utf8mb4 (full Unicode support)

**⚠️ MOST IMPORTANT RULE**:
**NEVER delete rows from `aircraft_movements` or `ml_prediction_log` - these are historical data**
