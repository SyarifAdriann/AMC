# API Endpoints - AMC Parking Stand Prediction System

## Table of Contents
1. [Authentication Endpoints](#authentication-endpoints)
2. [Web Routes](#web-routes)
3. [API Routes](#api-routes)
4. [Request/Response Formats](#requestresponse-formats)
5. [Error Handling](#error-handling)

---

## Authentication Endpoints

### POST /login
**Purpose**: User login

**Handler**: `AuthController::login()`

**Request**:
```http
POST /login HTTP/1.1
Content-Type: application/x-www-form-urlencoded

username=admin&password=secret&csrf_token=abc123
```

**Response** (Success):
```http
HTTP/1.1 302 Found
Location: /apron
Set-Cookie: PHPSESSID=...
```

**Response** (Failure):
```http
HTTP/1.1 200 OK

<html>...Login failed: Invalid credentials...</html>
```

---

### GET /logout
**Purpose**: User logout

**Handler**: `AuthController::logout()`

**Request**:
```http
GET /logout HTTP/1.1
```

**Response**:
```http
HTTP/1.1 302 Found
Location: /login
Set-Cookie: PHPSESSID=deleted
```

---

## Web Routes

### GET /apron (or /)
**Purpose**: Main apron view

**Handler**: `ApronController::show()`

**Authentication**: Required

**Request**:
```http
GET /apron HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```http
HTTP/1.1 200 OK
Content-Type: text/html

<html>...Apron view page...</html>
```

---

### GET /dashboard
**Purpose**: Dashboard analytics

**Handler**: `DashboardController::show()`

**Authentication**: Required (admin/operator only)

**Request**:
```http
GET /dashboard HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```http
HTTP/1.1 200 OK
Content-Type: text/html

<html>...Dashboard page...</html>
```

---

### GET /master-table
**Purpose**: Master data management

**Handler**: `MasterTableController::show()`

**Authentication**: Required

**Request**:
```http
GET /master-table HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```http
HTTP/1.1 200 OK
Content-Type: text/html

<html>...Master table page...</html>
```

---

## API Routes

### POST /api/apron/recommend
**Purpose**: Get ML parking stand recommendations

**Handler**: `ApronController::recommend()`

**Authentication**: Required

**Request**:
```http
POST /api/apron/recommend HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "aircraft_type": "B 738",
  "operator_airline": "GARUDA",
  "category": "COMMERCIAL"
}
```

**Response** (Success):
```json
{
  "success": true,
  "predictions": [
    {"stand": "C4", "probability": 0.45, "rank": 1},
    {"stand": "C3", "probability": 0.32, "rank": 2},
    {"stand": "C5", "probability": 0.15, "rank": 3}
  ],
  "input": {
    "aircraft_type": "B 738",
    "operator_airline": "GARUDA",
    "category": "COMMERCIAL",
    "aircraft_size": "STANDARD",
    "airline_tier": "HIGH_FREQUENCY",
    "stand_zone": "RIGHT_COMMERCIAL"
  }
}
```

**Response** (Error):
```json
{
  "success": false,
  "error": "Python prediction failed",
  "details": "Model file not found"
}
```

---

### GET /api/apron/status
**Purpose**: Real-time apron status

**Handler**: `ApronController::status()`

**Authentication**: Required

**Request**:
```http
GET /api/apron/status HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```json
{
  "totalStands": 50,
  "occupiedStands": 30,
  "availableStands": 20,
  "ronCount": 5,
  "activeRonCount": 3,
  "movements": {
    "total": 45,
    "commercial": 30,
    "cargo": 10,
    "charter": 5
  },
  "hourlyStats": [
    {"hour": "00:00", "arrivals": 2, "departures": 1},
    {"hour": "01:00", "arrivals": 1, "departures": 0}
  ]
}
```

---

### GET /api/apron/movements
**Purpose**: Current movements list

**Handler**: `ApronController::movements()`

**Authentication**: Required

**Query Parameters**:
- `date` (optional): Date filter (YYYY-MM-DD), defaults to today

**Request**:
```http
GET /api/apron/movements?date=2025-11-24 HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```json
{
  "success": true,
  "movements": [
    {
      "id": 1,
      "movement_date": "2025-11-24",
      "registration": "PK-GFA",
      "flight_no": "GA101",
      "origin": "CGK",
      "destination": "DPS",
      "on_block_time": "08:30:00",
      "off_block_time": null,
      "parking_stand": "C4",
      "is_ron": 0,
      "ron_complete": 0,
      "aircraft_type": "B 738",
      "operator_airline": "GARUDA",
      "category": "commercial"
    }
  ]
}
```

---

### GET /api/ml/metrics
**Purpose**: ML model performance metrics

**Handler**: `ApronController::mlMetrics()`

**Authentication**: Required

**Request**:
```http
GET /api/ml/metrics HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```json
{
  "success": true,
  "total_predictions": 150,
  "correct_predictions": 120,
  "accuracy_percentage": 80.00,
  "model_version": 2,
  "last_prediction": "2025-11-24 10:30:00"
}
```

---

### GET /api/ml/logs
**Purpose**: ML prediction history

**Handler**: `ApronController::mlPredictionLog()`

**Authentication**: Required

**Query Parameters**:
- `limit` (optional): Max records, default 100
- `offset` (optional): Offset for pagination, default 0

**Request**:
```http
GET /api/ml/logs?limit=10&offset=0 HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```json
{
  "success": true,
  "logs": [
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
      "requested_by": "admin"
    }
  ],
  "total": 150,
  "limit": 10,
  "offset": 0
}
```

---

### GET /api/dashboard/movements
**Purpose**: Dashboard movement metrics

**Handler**: `DashboardController::movementMetrics()`

**Authentication**: Required

**Request**:
```http
GET /api/dashboard/movements HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```json
{
  "success": true,
  "snapshots": {
    "commercial": 30,
    "cargo": 10,
    "charter": 5
  },
  "hourly": [
    {"hour": "00:00", "count": 3},
    {"hour": "01:00", "count": 1}
  ],
  "timestamp": "2025-11-24T10:30:00+00:00"
}
```

---

### POST /api/apron
**Purpose**: Handle apron actions (add/update movement, toggle RON, etc.)

**Handler**: `ApronController::handle()`

**Authentication**: Required

**Actions**:
- `saveRoster`: Save daily staff roster
- `addMovement`: Add new aircraft movement
- `updateMovement`: Update existing movement
- `ronToggle`: Toggle RON status
- `deleteMovement`: Delete movement (admin only)

**Request Example** (Add Movement):
```http
POST /api/apron HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "action": "addMovement",
  "movement_date": "2025-11-24",
  "registration": "PK-GFA",
  "flight_no": "GA101",
  "origin": "CGK",
  "destination": "DPS",
  "on_block_time": "08:30:00",
  "parking_stand": "C4",
  "csrf_token": "abc123"
}
```

**Response** (Success):
```json
{
  "success": true,
  "message": "Movement added successfully",
  "movement_id": 123
}
```

**Response** (Error):
```json
{
  "success": false,
  "error": "Registration not found in aircraft_details"
}
```

---

### POST /api/master-table
**Purpose**: Master data CRUD operations

**Handler**: `MasterTableController::handle()`

**Authentication**: Required

**Actions**:
- `addAircraft`: Add aircraft details
- `updateAircraft`: Update aircraft details
- `deleteAircraft`: Delete aircraft (admin only)
- `addFlightRef`: Add flight reference
- `updateFlightRef`: Update flight reference
- `deleteFlightRef`: Delete flight reference

**Request Example** (Add Aircraft):
```http
POST /api/master-table HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "action": "addAircraft",
  "registration": "PK-GFA",
  "aircraft_type": "B 738",
  "operator_airline": "GARUDA",
  "category": "commercial",
  "csrf_token": "abc123"
}
```

**Response** (Success):
```json
{
  "success": true,
  "message": "Aircraft added successfully"
}
```

---

### GET /api/admin/users
**Purpose**: User management (list users)

**Handler**: `UserController::handle()`

**Authentication**: Required (admin only)

**Request**:
```http
GET /api/admin/users HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "role": "admin",
      "full_name": "System Administrator",
      "is_active": 1,
      "created_at": "2025-11-01 00:00:00"
    }
  ]
}
```

---

### POST /api/admin/users
**Purpose**: User management (create/update/delete)

**Handler**: `UserController::handle()`

**Authentication**: Required (admin only)

**Actions**:
- `create`: Create new user
- `update`: Update existing user
- `delete`: Delete user
- `reset_password`: Reset user password

**Request Example** (Create User):
```http
POST /api/admin/users HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "action": "create",
  "username": "operator1",
  "email": "operator1@example.com",
  "password": "secret",
  "role": "operator",
  "full_name": "Operator One",
  "csrf_token": "abc123"
}
```

**Response** (Success):
```json
{
  "success": true,
  "message": "User created successfully",
  "user_id": 2
}
```

---

### GET /api/snapshots
**Purpose**: Daily snapshots list

**Handler**: `SnapshotController::handle()`

**Authentication**: Required

**Request**:
```http
GET /api/snapshots HTTP/1.1
Cookie: PHPSESSID=...
```

**Response**:
```json
{
  "success": true,
  "snapshots": [
    {
      "id": 1,
      "snapshot_date": "2025-11-23",
      "total_movements": 45,
      "total_commercial": 30,
      "total_cargo": 10,
      "total_charter": 5,
      "created_by": "admin",
      "created_at": "2025-11-23 23:59:00"
    }
  ]
}
```

---

### POST /api/snapshots
**Purpose**: Create daily snapshot

**Handler**: `SnapshotController::handle()`

**Authentication**: Required

**Request**:
```http
POST /api/snapshots HTTP/1.1
Content-Type: application/json
Cookie: PHPSESSID=...

{
  "action": "create",
  "snapshot_date": "2025-11-24",
  "csrf_token": "abc123"
}
```

**Response** (Success):
```json
{
  "success": true,
  "message": "Snapshot created successfully",
  "snapshot_id": 2
}
```

---

## Request/Response Formats

### Common Request Headers
```http
Content-Type: application/json
Cookie: PHPSESSID=... (for authenticated routes)
```

### Common Response Headers
```http
Content-Type: application/json
Cache-Control: no-cache, private
```

### CSRF Token
All POST requests must include `csrf_token` field for CSRF protection.

**Get CSRF Token**:
```html
<input type="hidden" name="csrf_token" value="<?= legacy_csrf_token() ?>">
```

**Validate CSRF Token**:
```php
if (!$this->csrf->validate($request->input('csrf_token'))) {
    return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}
```

---

## Error Handling

### HTTP Status Codes
- **200 OK**: Request successful
- **302 Found**: Redirect (login/logout)
- **400 Bad Request**: Invalid input
- **401 Unauthorized**: Not authenticated
- **403 Forbidden**: Not authorized (wrong role)
- **404 Not Found**: Route not found
- **500 Internal Server Error**: Server error

### Error Response Format
```json
{
  "success": false,
  "error": "Error message",
  "details": "Additional details (optional)"
}
```

### Common Errors

#### 1. Invalid CSRF Token
```json
{
  "success": false,
  "error": "Invalid CSRF token"
}
```

#### 2. Unauthorized Access
```json
{
  "success": false,
  "error": "Unauthorized. Please log in."
}
```

#### 3. Forbidden Access
```json
{
  "success": false,
  "error": "Forbidden. Insufficient permissions."
}
```

#### 4. Validation Error
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "registration": "Registration is required",
    "movement_date": "Invalid date format"
  }
}
```

#### 5. Database Error
```json
{
  "success": false,
  "error": "Database error",
  "details": "Duplicate entry 'PK-GFA' for key 'PRIMARY'"
}
```

---

## API Testing

### Using cURL

**Login**:
```bash
curl -X POST http://localhost/amc/login \
  -d "username=admin&password=secret&csrf_token=abc123" \
  -c cookies.txt
```

**Get Apron Status**:
```bash
curl -X GET http://localhost/amc/api/apron/status \
  -b cookies.txt
```

**Get ML Recommendations**:
```bash
curl -X POST http://localhost/amc/api/apron/recommend \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "aircraft_type": "B 738",
    "operator_airline": "GARUDA",
    "category": "COMMERCIAL"
  }'
```

---

## Summary

**Total Endpoints**: 15+
**Authentication**: Required for all routes except /login
**CSRF Protection**: Required for all POST requests
**Response Format**: JSON for API routes, HTML for web routes
**Error Handling**: Consistent JSON error format
