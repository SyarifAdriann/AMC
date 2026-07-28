# Testing Procedures - AMC Parking Stand Prediction System

## Table of Contents
1. [Testing Overview](#testing-overview)
2. [ML Prediction Testing](#ml-prediction-testing)
3. [Database Operations Testing](#database-operations-testing)
4. [Critical User Flows](#critical-user-flows)
5. [Known Test Cases](#known-test-cases)
6. [Performance Benchmarks](#performance-benchmarks)
7. [Regression Testing](#regression-testing)

---

## Testing Overview

### Testing Philosophy
**Manual Testing Approach**: Currently no automated test suite. All testing is manual.

### Test Environments
1. **Development**: `http://localhost/amc` (XAMPP local)
2. **Staging**: (if available)
3. **Production**: (airport intranet)

### Testing Tools
- **Manual Testing**: Browser (Chrome/Firefox)
- **API Testing**: cURL, Postman
- **Database Testing**: phpMyAdmin, MySQL CLI
- **ML Testing**: Python scripts in `ml/` and `tools/`

---

## ML Prediction Testing

### Test Script: `ml/test_predict.py`
**Purpose**: Test ML predictions with sample inputs

**Usage**:
```bash
cd C:\xampp\htdocs\amc
python ml\test_predict.py
```

**Edit Test Cases** (`ml/test_predict.py`):
```python
test_cases = [
    {
        "aircraft_type": "B 738",
        "operator_airline": "GARUDA",
        "category": "COMMERCIAL"
    },
    {
        "aircraft_type": "A 320",
        "operator_airline": "BATIK AIR",
        "category": "COMMERCIAL"
    },
    {
        "aircraft_type": "C 208",
        "operator_airline": "SUSI AIR",
        "category": "CHARTER"
    }
]
```

**Expected Output**:
```json
{
  "success": true,
  "predictions": [
    {"stand": "C4", "probability": 0.45, "rank": 1},
    {"stand": "C3", "probability": 0.32, "rank": 2},
    {"stand": "C5", "probability": 0.15, "rank": 3}
  ]
}
```

**Validation**:
- ✅ `success` is `true`
- ✅ 3 predictions returned
- ✅ Probabilities between 0 and 1
- ✅ Ranks are 1, 2, 3
- ✅ Stand names are valid (match `stands` table)

---

### Performance Test: `tools/measure_predict_perf.py`
**Purpose**: Measure prediction performance and latency

**Usage**:
```bash
cd C:\xampp\htdocs\amc
python tools\measure_predict_perf.py
```

**Output**:
```
Running 100 predictions...
Average time: 0.52 seconds
Min time: 0.48 seconds
Max time: 0.65 seconds
95th percentile: 0.58 seconds
```

**Acceptance Criteria**:
- ✅ Average time < 1 second (Python only, excluding PHP overhead)
- ✅ 95th percentile < 2 seconds
- ✅ No errors during 100 predictions

---

### Integration Test: `tools/test_proc_open_integration.php`
**Purpose**: Test PHP → Python integration (proc_open)

**Usage**:
```bash
cd C:\xampp\htdocs\amc
php tools\test_proc_open_integration.php
```

**Expected Output**:
```
Testing PHP to Python integration...
Python path: python
Script path: C:\xampp\htdocs\amc\ml\predict.py
Return code: 0
Output: {"success":true,"predictions":[...]}
Success! Predictions received.
```

**Validation**:
- ✅ Return code is 0
- ✅ Output is valid JSON
- ✅ `success` is `true`
- ✅ No stderr output (errors)

---

### Cache Test
**Purpose**: Verify caching works correctly

**Steps**:
1. Clear cache:
   ```bash
   php tools\cleanup_cache.php
   ```
2. Make prediction via browser (note time)
3. Make same prediction again (should be faster)
4. Check cache directory:
   ```bash
   dir C:\xampp\htdocs\amc\cache\
   ```

**Validation**:
- ✅ First prediction: ~4 seconds
- ✅ Second prediction (cached): ~0.5 seconds
- ✅ Cache file created in `cache/` directory

---

## Database Operations Testing

### Test 1: Add Aircraft Movement
**Purpose**: Verify movement insertion works

**Steps**:
1. Login as operator
2. Go to Apron view
3. Click "Add Movement"
4. Fill form:
   - Registration: `PK-TEST`
   - Flight No: `TEST01`
   - Origin: `CGK`
   - Destination: `DPS`
   - On Block Time: `10:30`
   - Parking Stand: `A1`
5. Submit

**Validation**:
- ✅ Movement appears in table
- ✅ Database record created:
   ```sql
   SELECT * FROM aircraft_movements WHERE registration = 'PK-TEST';
   ```
- ✅ `user_id_created` set to current user
- ✅ `created_at` timestamp set

---

### Test 2: Update Aircraft Movement
**Purpose**: Verify movement update works

**Steps**:
1. Select existing movement
2. Click "Edit"
3. Change parking stand from `A1` to `A2`
4. Submit

**Validation**:
- ✅ Movement updated in table
- ✅ Database record updated:
   ```sql
   SELECT parking_stand FROM aircraft_movements WHERE id = X;
   ```
- ✅ `user_id_updated` set to current user
- ✅ `updated_at` timestamp updated

---

### Test 3: RON Toggle
**Purpose**: Verify RON status toggle works

**Steps**:
1. Select movement with no departure time
2. Click "Toggle RON"
3. Confirm

**Validation**:
- ✅ `is_ron` changes to `1`
- ✅ Movement highlighted as RON in UI
- ✅ Apron status shows RON count increased
- ✅ Database:
   ```sql
   SELECT is_ron FROM aircraft_movements WHERE id = X;
   ```

---

### Test 4: Delete Movement
**Purpose**: Verify movement deletion works (admin only)

**Steps**:
1. Login as admin
2. Select movement
3. Click "Delete"
4. Confirm

**Validation**:
- ✅ Movement removed from table
- ✅ Database record deleted:
   ```sql
   SELECT COUNT(*) FROM aircraft_movements WHERE id = X;
   ```
   Should return 0
- ✅ Audit log entry created:
   ```sql
   SELECT * FROM audit_log WHERE table_name = 'aircraft_movements' AND record_id = X;
   ```

---

### Test 5: Daily Snapshot Creation
**Purpose**: Verify snapshot generation works

**Steps**:
```bash
php tools\console.php daily_snapshot
```

**Validation**:
- ✅ Snapshot created in database:
   ```sql
   SELECT * FROM daily_snapshots WHERE snapshot_date = CURDATE();
   ```
- ✅ `total_movements` matches actual count
- ✅ Category breakdowns (commercial, cargo, charter) correct

---

## Critical User Flows

### Flow 1: Login → View Apron → Make Prediction → Assign Stand

**Steps**:
1. Navigate to http://localhost/amc
2. Login with valid credentials
3. Apron view should load with current movements
4. Click "Add Movement" or "Edit Movement"
5. In modal, click "Recommend Stand"
6. Wait for predictions (~4 seconds)
7. Review Top-3 recommendations
8. Select a stand (can override prediction)
9. Submit form

**Validation**:
- ✅ Login successful
- ✅ Apron view loads < 3 seconds
- ✅ Predictions return in < 5 seconds
- ✅ Top-3 stands displayed with probabilities
- ✅ Movement saved with selected stand
- ✅ Prediction logged in `ml_prediction_log` table

---

### Flow 2: Generate Report → Export CSV

**Steps**:
1. Login as admin or operator
2. Go to Dashboard
3. Select report type (e.g., "Daily Summary")
4. Select date range (e.g., last 7 days)
5. Click "Generate Report"
6. Review report HTML
7. Click "Export CSV"
8. Save CSV file

**Validation**:
- ✅ Report generates < 5 seconds (for 7-day range)
- ✅ Report data matches database
- ✅ CSV file downloads correctly
- ✅ CSV opens in Excel without encoding issues

---

### Flow 3: Manage Aircraft Master Data

**Steps**:
1. Login as operator
2. Go to Master Table
3. Click "Add Aircraft"
4. Fill form:
   - Registration: `PK-NEW`
   - Aircraft Type: `B 738`
   - Operator: `NEW AIRLINE`
   - Category: `Commercial`
5. Submit
6. Verify aircraft appears in table
7. Click "Edit" on aircraft
8. Change operator to `UPDATED AIRLINE`
9. Submit

**Validation**:
- ✅ Aircraft added to database
- ✅ Aircraft appears in autocomplete for movements
- ✅ Update reflected immediately
- ✅ Can use aircraft in predictions

---

### Flow 4: User Management (Admin Only)

**Steps**:
1. Login as admin
2. Go to Dashboard → Accounts
3. Click "Add User"
4. Fill form:
   - Username: `testuser`
   - Email: `test@example.com`
   - Password: `Test1234!`
   - Role: `Operator`
5. Submit
6. Logout
7. Login as `testuser` / `Test1234!`
8. Verify operator permissions (no user management access)

**Validation**:
- ✅ User created in database
- ✅ Password hashed with bcrypt
- ✅ Can login with new credentials
- ✅ Role permissions enforced
- ✅ Cannot access user management as operator

---

## Known Test Cases

### Test Case 1: Commercial Aircraft (High Frequency Airline)
**Input**:
```json
{
  "aircraft_type": "B 738",
  "operator_airline": "GARUDA",
  "category": "COMMERCIAL"
}
```

**Expected Predictions**:
- Stand zone: `RIGHT_COMMERCIAL`
- Top prediction: `C4`, `C3`, or `C5` (commercial zone)
- Probabilities: > 0.20 for top prediction

**Feature Engineering**:
- `aircraft_size`: `STANDARD`
- `airline_tier`: `HIGH_FREQUENCY`
- `stand_zone`: `RIGHT_COMMERCIAL`

---

### Test Case 2: Cargo Aircraft
**Input**:
```json
{
  "aircraft_type": "B 733",
  "operator_airline": "TRI MG",
  "category": "CARGO"
}
```

**Expected Predictions**:
- Stand zone: `LEFT_CARGO`
- Top prediction: `B1`, `B2`, or `B3` (cargo zone)
- Probabilities: > 0.15 for top prediction

**Feature Engineering**:
- `aircraft_size`: `STANDARD`
- `airline_tier`: `HIGH_FREQUENCY` (TRI MG is high frequency cargo)
- `stand_zone`: `LEFT_CARGO`

---

### Test Case 3: Charter Aircraft (Small)
**Input**:
```json
{
  "aircraft_type": "C 208",
  "operator_airline": "JETSET",
  "category": "CHARTER"
}
```

**Expected Predictions**:
- Stand zone: `MIDDLE_CHARTER`
- Top prediction: `A0`, `A1`, or remote stands
- Probabilities: > 0.10 for top prediction

**Feature Engineering**:
- `aircraft_size`: `SMALL_A0_COMPATIBLE` (Cessna 208 is A0-compatible)
- `airline_tier`: `MEDIUM_FREQUENCY`
- `stand_zone`: `MIDDLE_CHARTER`

---

### Test Case 4: Unknown Aircraft Type
**Input**:
```json
{
  "aircraft_type": "UNKNOWN",
  "operator_airline": "NEW AIRLINE",
  "category": "CHARTER"
}
```

**Expected Behavior**:
- Prediction succeeds (fallback to most common encoding)
- Stand zone: `MIDDLE_CHARTER`
- Probabilities: Lower confidence (< 0.30 for top prediction)

**Note**: Should still work but with lower accuracy

---

### Test Case 5: Edge Case - Empty/Invalid Input
**Input**:
```json
{
  "aircraft_type": "",
  "operator_airline": "",
  "category": ""
}
```

**Expected Behavior**:
- Prediction fails with error:
  ```json
  {
    "success": false,
    "error": "aircraft_type, operator_airline, and category are required fields",
    "type": "ValueError"
  }
  ```

---

## Performance Benchmarks

### Baseline Performance Targets

**Apron View Load Time**:
- **Target**: < 2 seconds
- **Acceptable**: < 3 seconds
- **Unacceptable**: > 5 seconds

**ML Prediction Time (Total)**:
- **Target**: < 4 seconds (including PHP overhead)
- **Acceptable**: < 6 seconds
- **Unacceptable**: > 10 seconds

**ML Prediction Time (Python Only)**:
- **Target**: < 1 second (cached model)
- **Acceptable**: < 2 seconds
- **Unacceptable**: > 3 seconds

**Dashboard Report Generation**:
- **7-day range**: < 3 seconds
- **30-day range**: < 10 seconds
- **90-day range**: < 30 seconds

**CSV Export**:
- **< 1000 rows**: < 2 seconds
- **< 10000 rows**: < 10 seconds
- **> 10000 rows**: Consider pagination

---

### Performance Testing Procedure

**Test 1: Apron View Load**
```bash
# Clear cache first
php tools\cleanup_cache.php

# Measure load time
curl -o /dev/null -s -w "%{time_total}\n" http://localhost/amc/apron
```

**Test 2: ML Prediction**
```bash
# Use tools/measure_predict_perf.py
python tools\measure_predict_perf.py
```

**Test 3: Database Query Performance**
```sql
-- Explain movement query
EXPLAIN SELECT am.*, ad.aircraft_type
FROM aircraft_movements am
LEFT JOIN aircraft_details ad ON am.registration = ad.registration
WHERE am.movement_date = CURDATE();

-- Should use movement_date index
-- Should NOT show "Using filesort" or "Using temporary"
```

---

## Regression Testing

### When to Run Regression Tests
- After any code changes
- After model retraining
- After database schema changes
- Before production deployment

### Regression Test Checklist

**Core Functionality**:
- [ ] Login/logout works
- [ ] Apron view loads
- [ ] Movements CRUD operations work
- [ ] ML predictions work
- [ ] RON toggle works
- [ ] Dashboard loads
- [ ] Reports generate correctly
- [ ] CSV export works
- [ ] Master data CRUD works

**Edge Cases**:
- [ ] Invalid login credentials rejected
- [ ] CSRF token validation works
- [ ] Login throttling works (after 5 failed attempts)
- [ ] Session timeout works (after 30 minutes)
- [ ] Role-based access enforced (viewer cannot access dashboard)
- [ ] Duplicate aircraft registration rejected
- [ ] Invalid date formats rejected

**Performance**:
- [ ] Apron view loads < 3 seconds
- [ ] ML predictions < 5 seconds
- [ ] Reports generate < 10 seconds (30-day range)
- [ ] No SQL query takes > 1 second (check slow query log)

**Data Integrity**:
- [ ] No NULL parking_stands for active movements
- [ ] No orphaned movements (registration not in aircraft_details)
- [ ] No duplicate daily snapshots (same date)
- [ ] All foreign keys valid

---

## Known Issues and Workarounds

### Issue 1: First prediction after restart is slow
**Expected**: ~4-6 seconds
**Workaround**: Pre-warm cache by making a test prediction after deployment

### Issue 2: Large date range reports timeout
**Expected**: > 30 seconds for 365-day range
**Workaround**: Limit date range to 90 days max

### Issue 3: Concurrent predictions may cache collide
**Expected**: Same input at same time may execute Python twice
**Workaround**: Use flock() for cache writes (future improvement)

---

## Summary

**Testing Approach**: Manual (no automated tests)
**Critical Tests**: ML predictions, database operations, user flows
**Performance Targets**: Apron load < 3s, predictions < 5s
**Regression Testing**: Before every deployment
**Known Issues**: 3 (documented with workarounds)

**⚠️ RECOMMENDATION**: Implement automated testing for critical paths (future improvement)
