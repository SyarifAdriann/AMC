# Coding Standards - AMC Parking Stand Prediction System

## Table of Contents
1. [Naming Conventions](#naming-conventions)
2. [File Organization](#file-organization)
3. [Code Style](#code-style)
4. [Database Conventions](#database-conventions)
5. [Error Handling](#error-handling)
6. [Security Practices](#security-practices)
7. [Documentation](#documentation)

---

## Naming Conventions

### PHP

#### Classes
**Convention**: PascalCase
**Examples**:
```php
class ApronController {}
class AircraftMovement {}
class UserRepository {}
class FileCache {}
```

**Rules**:
- One class per file
- File name matches class name: `ApronController.php`
- No abbreviations unless industry-standard (e.g., `RON`, `CSV`, `ML`)

#### Methods and Functions
**Convention**: camelCase
**Examples**:
```php
public function showApronView() {}
public function getMovementById($id) {}
protected function buildFeatureVector($payload) {}
private function validateInput($data) {}
```

**Rules**:
- Verbs for actions: `get`, `set`, `update`, `delete`, `create`, `find`, `build`
- Boolean methods start with `is`, `has`, `can`: `isActive()`, `hasRole()`, `canEdit()`

#### Variables
**Convention**: camelCase
**Examples**:
```php
$aircraftType = 'B 738';
$operatorAirline = 'GARUDA';
$movementDate = '2025-11-24';
$isRon = false;
```

**Rules**:
- Descriptive names (no single letters except loop counters)
- No Hungarian notation (`strName`, `intId`)
- Boolean variables start with `is`, `has`, `can`

#### Constants
**Convention**: SCREAMING_SNAKE_CASE
**Examples**:
```php
const MAX_ATTEMPTS = 5;
const SESSION_TIMEOUT = 1800;
const MODEL_PATH = '/path/to/model.pkl';
```

#### Namespaces
**Convention**: PascalCase (match directory structure)
**Examples**:
```php
namespace App\Controllers;
namespace App\Core\Database;
namespace App\Services;
```

**Rules**:
- Follow PSR-4 autoloading standard
- Namespace matches directory: `App\Controllers` → `app/Controllers/`

---

### Python

#### Functions and Variables
**Convention**: snake_case
**Examples**:
```python
def build_feature_vector(payload):
    aircraft_type = payload['aircraft_type']
    operator_airline = payload['operator_airline']
    return feature_dict
```

#### Classes
**Convention**: PascalCase (rare in this project)
**Examples**:
```python
class ModelCache:
    pass
```

#### Constants
**Convention**: SCREAMING_SNAKE_CASE
**Examples**:
```python
MODEL_PATH = 'ml/parking_stand_model_rf_redo.pkl'
CACHE_DURATION = 3600
A0_COMPATIBLE = ['C 152', 'C 172', ...]
```

---

### Database

#### Tables
**Convention**: snake_case, plural
**Examples**:
```sql
aircraft_movements
aircraft_details
daily_snapshots
ml_prediction_log
```

**Rules**:
- Plural for data tables: `movements`, `details`, `logs`
- Singular for lookup/config tables: `audit_log` (not plural)
- No abbreviations: `aircraft_movements` (not `ac_moves`)

#### Columns
**Convention**: snake_case
**Examples**:
```sql
movement_date
aircraft_type
operator_airline
is_ron
ron_complete
created_at
```

**Rules**:
- Boolean columns start with `is_`, `has_`, `can_`
- Foreign keys: `user_id`, `created_by_user_id`
- Timestamps: `created_at`, `updated_at`

#### Indexes
**Convention**: idx_column_name or custom
**Examples**:
```sql
idx_movement_date
idx_parking_stand
idx_movement_date_ron  (composite)
```

---

### JavaScript

#### Variables and Functions
**Convention**: camelCase
**Examples**:
```javascript
function fetchApronStatus() {}
const movementDate = '2025-11-24';
let isLoading = false;
```

#### Constants
**Convention**: SCREAMING_SNAKE_CASE
**Examples**:
```javascript
const API_BASE_URL = '/api';
const MAX_RETRIES = 3;
```

---

## File Organization

### PHP Files

#### Controllers
**Location**: `app/Controllers/`
**Pattern**: `{Entity}Controller.php`
**Example**:
```
app/Controllers/
├── ApronController.php
├── DashboardController.php
├── MasterTableController.php
├── AuthController.php
└── Admin/
    └── UserController.php
```

#### Models
**Location**: `app/Models/`
**Pattern**: `{Entity}.php`
**Example**:
```
app/Models/
├── AircraftMovement.php
├── AircraftDetail.php
├── User.php
└── Stand.php
```

#### Services
**Location**: `app/Services/`
**Pattern**: `{Name}Service.php`
**Example**:
```
app/Services/
├── ApronStatusService.php
├── RonService.php
├── ReportService.php
└── AuditLogger.php
```

#### Repositories
**Location**: `app/Repositories/`
**Pattern**: `{Entity}Repository.php`
**Example**:
```
app/Repositories/
├── AircraftMovementRepository.php
├── UserRepository.php
└── StandRepository.php
```

---

### Python Files

**Location**: `ml/` or `tools/`
**Pattern**: `snake_case.py`
**Example**:
```
ml/
├── predict.py
├── train_model.py
├── model_cache.py
└── __init__.py

tools/
├── refresh_dataset.py
├── measure_predict_perf.py
└── cleanup_cache.php
```

---

### Views (Templates)

**Location**: `resources/views/`
**Pattern**: `{entity}/{page}.php`
**Example**:
```
resources/views/
├── layouts/
│   └── app.php
├── partials/
│   └── nav.php
├── apron/
│   ├── index.php
│   └── partials/
│       └── stand-modal.php
└── dashboard/
    └── index.php
```

---

## Code Style

### PHP

#### Indentation
**Standard**: 4 spaces (NO TABS)

#### Braces
**Style**: K&R style (opening brace on same line)
```php
// Correct
if ($condition) {
    // code
} else {
    // code
}

// Incorrect
if ($condition)
{
    // code
}
```

#### Arrays
**Style**: Short array syntax
```php
// Correct
$array = ['a', 'b', 'c'];
$assoc = ['key' => 'value'];

// Incorrect (PHP 5.3 style)
$array = array('a', 'b', 'c');
```

#### Type Declarations
**Standard**: Use type hints
```php
// Correct
public function getMovement(int $id): ?AircraftMovement {
    // ...
}

// Incorrect (no types)
public function getMovement($id) {
    // ...
}
```

#### Null Coalescing
**Standard**: Use `??` operator
```php
// Correct
$value = $input['key'] ?? 'default';

// Incorrect
$value = isset($input['key']) ? $input['key'] : 'default';
```

#### String Concatenation
**Standard**: Use string interpolation for simple variables
```php
// Correct
$message = "Hello, {$username}!";
$message = "Value: {$array['key']}";

// Also correct for complex expressions
$message = "Hello, " . $user->getFullName() . "!";

// Incorrect
$message = 'Hello, ' . $username . '!';  // Use interpolation instead
```

#### SQL Queries
**Standard**: ALWAYS use prepared statements
```php
// Correct
$stmt = $pdo->prepare("SELECT * FROM aircraft_movements WHERE id = ?");
$stmt->execute([$id]);

// Incorrect (SQL injection risk)
$query = "SELECT * FROM aircraft_movements WHERE id = {$id}";
$result = $pdo->query($query);
```

---

### Python

#### Indentation
**Standard**: 4 spaces (PEP 8)

#### Function Definitions
```python
# Correct
def build_feature_vector(payload):
    """Build feature vector from input payload."""
    aircraft_type = payload['aircraft_type']
    return {
        'aircraft_type': aircraft_type,
    }

# Incorrect (missing docstring)
def build_feature_vector(payload):
    aircraft_type = payload['aircraft_type']
    return {'aircraft_type': aircraft_type}
```

#### Imports
**Standard**: One import per line, grouped
```python
# Correct
import argparse
import json
import sys
from pathlib import Path
from typing import Any, Dict, List

import numpy as np
import pandas as pd
import pickle

# Incorrect (multiple imports on one line)
import argparse, json, sys
```

#### String Formatting
**Standard**: Use f-strings (Python 3.6+)
```python
# Correct
message = f"Processing {aircraft_type} for {airline}"

# Also correct for complex formatting
message = "Error: {}".format(error_msg)

# Incorrect (old style)
message = "Processing %s for %s" % (aircraft_type, airline)
```

---

### JavaScript

#### Indentation
**Standard**: 2 spaces (web standard)

#### Variable Declarations
**Standard**: Use `const` by default, `let` when reassignment needed
```javascript
// Correct
const aircraftType = 'B 738';
let count = 0;

// Incorrect (var is deprecated)
var aircraftType = 'B 738';
```

#### Functions
**Standard**: Use arrow functions for short functions
```javascript
// Correct (arrow function)
const fetchData = () => {
    return fetch('/api/data').then(res => res.json());
};

// Also correct (traditional function)
function fetchData() {
    return fetch('/api/data').then(res => res.json());
}
```

#### Promises
**Standard**: Use async/await
```javascript
// Correct
async function fetchMovements() {
    try {
        const response = await fetch('/api/apron/movements');
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
    }
}

// Incorrect (promise chains for simple cases)
function fetchMovements() {
    return fetch('/api/apron/movements')
        .then(res => res.json())
        .then(data => data)
        .catch(err => console.error(err));
}
```

---

## Database Conventions

### Query Formatting
```sql
-- Correct (keywords uppercase, line breaks)
SELECT
    am.id,
    am.movement_date,
    ad.aircraft_type
FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration
WHERE am.movement_date = CURDATE()
ORDER BY am.on_block_time ASC;

-- Incorrect (hard to read)
select am.id,am.movement_date,ad.aircraft_type from aircraft_movements am left join aircraft_details ad on am.registration=ad.registration where am.movement_date=curdate() order by am.on_block_time asc;
```

### Table Aliases
**Standard**: Short, meaningful aliases
```sql
-- Correct
FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration

-- Incorrect (no aliases, repetitive)
FROM aircraft_movements
LEFT JOIN aircraft_details ON aircraft_movements.registration = aircraft_details.registration
```

---

## Error Handling

### PHP

#### Try-Catch Blocks
```php
// Correct
try {
    $movement = $this->movements->find($id);
    if (!$movement) {
        throw new InvalidArgumentException("Movement not found: {$id}");
    }
    return $this->json(['success' => true, 'movement' => $movement]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    return $this->json(['success' => false, 'error' => 'Database error'], 500);
} catch (InvalidArgumentException $e) {
    return $this->json(['success' => false, 'error' => $e->getMessage()], 404);
} catch (Throwable $e) {
    error_log("Unexpected error: " . $e->getMessage());
    return $this->json(['success' => false, 'error' => 'Internal error'], 500);
}
```

#### Error Logging
```php
// Correct
error_log("ML prediction failed: " . $error);

// Incorrect (no logging)
// Silent failure
```

---

### Python

#### Exception Handling
```python
# Correct
try:
    with open(MODEL_PATH, 'rb') as f:
        model = pickle.load(f)
except FileNotFoundError as e:
    error_response = {
        'success': False,
        'error': f'Model file not found: {MODEL_PATH}',
        'type': 'FileNotFoundError'
    }
    json.dump(error_response, sys.stdout)
    sys.exit(1)
except Exception as e:
    error_response = {
        'success': False,
        'error': str(e),
        'type': e.__class__.__name__
    }
    json.dump(error_response, sys.stdout)
    sys.exit(1)
```

---

## Security Practices

### Input Validation
```php
// Correct (validate and sanitize)
$registration = trim($request->input('registration'));
if (empty($registration) || strlen($registration) > 10) {
    return $this->json(['success' => false, 'error' => 'Invalid registration'], 400);
}
```

### Output Escaping
```php
// Correct (escape in view)
<h1>Welcome, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h1>

// Incorrect (XSS vulnerability)
<h1>Welcome, <?= $username ?></h1>
```

### CSRF Protection
```php
// Correct (validate token)
if (!$this->csrf->validate($request->input('csrf_token'))) {
    return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}
```

### SQL Injection Prevention
```php
// Correct (prepared statements)
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// Incorrect (vulnerable)
$query = "SELECT * FROM users WHERE username = '{$username}'";
```

### Password Hashing
```php
// Correct
$hash = password_hash($password, PASSWORD_BCRYPT);

// Verify
if (password_verify($password, $hash)) {
    // Login successful
}

// Incorrect (MD5/SHA1 not secure)
$hash = md5($password);
```

---

## Documentation

### PHP Docblocks
```php
/**
 * Get ML parking stand recommendations
 *
 * @param string $aircraftType ICAO aircraft type code
 * @param string $operatorAirline Operating airline name
 * @param string $category Aircraft category (commercial/cargo/charter)
 * @param int $topK Number of predictions to return (default: 3)
 * @return array Prediction results with stands and probabilities
 * @throws RuntimeException If Python execution fails
 */
public function getRecommendations(
    string $aircraftType,
    string $operatorAirline,
    string $category,
    int $topK = 3
): array {
    // Implementation
}
```

### Python Docstrings
```python
def build_feature_vector(payload):
    """Build feature vector from input payload.

    Args:
        payload (dict): Input dictionary with aircraft_type, operator_airline, category

    Returns:
        dict: Feature dictionary with engineered features

    Raises:
        ValueError: If required fields are missing
    """
    # Implementation
```

### Inline Comments
```php
// Use comments to explain WHY, not WHAT
// Correct
// RON aircraft should not be marked as departed
if ($movement['is_ron'] && $movement['off_block_time'] !== null) {
    throw new InvalidArgumentException('RON aircraft cannot have departure time');
}

// Incorrect (comment explains obvious code)
// Check if is_ron equals 1
if ($movement['is_ron'] == 1) {
    // ...
}
```

---

## Summary

**Key Principles**:
1. **Consistency**: Follow established patterns
2. **Clarity**: Write code for humans, not just machines
3. **Security**: Always validate input, escape output, use prepared statements
4. **Documentation**: Explain complex logic, not obvious code
5. **Error Handling**: Log errors, provide user-friendly messages
6. **Performance**: Use indexes, paginate queries, cache results

**Code Review Checklist**:
- [ ] Naming conventions followed
- [ ] Proper indentation (4 spaces PHP/Python, 2 spaces JS)
- [ ] Type hints used (PHP 8.3)
- [ ] Prepared statements for SQL
- [ ] Output escaped (htmlspecialchars)
- [ ] CSRF token validated
- [ ] Errors logged
- [ ] Comments explain WHY
- [ ] No hardcoded credentials
- [ ] No SQL injection risks

**⚠️ CRITICAL RULES**:
- **NEVER** use string concatenation for SQL queries
- **ALWAYS** escape output in views
- **ALWAYS** validate CSRF tokens
- **NEVER** commit credentials to version control
- **ALWAYS** use prepared statements for database queries
