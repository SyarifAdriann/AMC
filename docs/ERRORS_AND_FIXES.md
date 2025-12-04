# Common Errors and Fixes - AMC Parking Stand Prediction System

## Table of Contents
1. [Database Errors](#database-errors)
2. [ML Prediction Errors](#ml-prediction-errors)
3. [PHP Errors](#php-errors)
4. [Authentication Errors](#authentication-errors)
5. [Cache Errors](#cache-errors)
6. [Performance Issues](#performance-issues)

---

## Database Errors

### Error: "SQLSTATE[HY000] [2002] No connection could be made"
**Symptom**: White page or database connection error

**Cause**: MySQL service not running

**Fix**:
```bash
# Windows (XAMPP)
# Open XAMPP Control Panel → Start MySQL

# Linux
sudo systemctl start mysql
```

**Prevention**: Set MySQL to start automatically on boot

---

### Error: "SQLSTATE[42S02]: Base table or view not found: 1146 Table 'amc.aircraft_movements' doesn't exist"
**Symptom**: Application crashes when accessing apron view

**Cause**: Database schema not imported

**Fix**:
```bash
cd C:\xampp\htdocs\amc
mysql -u root -p amc < amc.sql
```

**Verification**:
```sql
USE amc;
SHOW TABLES;
```

**Prevention**: Always import schema during setup

---

### Error: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'PK-GFA' for key 'PRIMARY'"
**Symptom**: Cannot add aircraft with existing registration

**Cause**: Registration already exists in `aircraft_details` table

**Fix**:
1. Check if aircraft exists:
   ```sql
   SELECT * FROM aircraft_details WHERE registration = 'PK-GFA';
   ```
2. Either:
   - Update existing aircraft (instead of insert)
   - Use different registration
   - Delete old aircraft (if safe)

**Prevention**: Implement duplicate check before insert

---

### Error: "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails"
**Symptom**: Cannot insert movement with invalid user ID

**Cause**: Foreign key `user_id_created` references non-existent user

**Fix**:
1. Check user exists:
   ```sql
   SELECT * FROM users WHERE id = X;
   ```
2. Use valid user ID or set to NULL (if allowed)

**Prevention**: Always validate foreign keys before insert

---

### Error: "Query was empty" or "You have an error in your SQL syntax"
**Symptom**: Database query fails

**Cause**: Malformed SQL query, often from unescaped user input

**Fix**:
1. Check error logs for exact query
2. Use prepared statements (NOT string concatenation)

**Example** (WRONG):
```php
$query = "SELECT * FROM aircraft_movements WHERE registration = '" . $registration . "'";
```

**Example** (CORRECT):
```php
$stmt = $pdo->prepare("SELECT * FROM aircraft_movements WHERE registration = ?");
$stmt->execute([$registration]);
```

**Prevention**: ALWAYS use prepared statements

---

## ML Prediction Errors

### Error: "Python not found" or "CreateProcess failed"
**Symptom**: ML predictions fail with proc_open error

**Cause**: Python not in system PATH

**Fix**:
1. Verify Python installation:
   ```bash
   python --version
   ```
2. Add Python to PATH:
   - Windows: Search "Environment Variables" → Edit Path → Add Python directory
   - Or hardcode in `ApronController.php`:
     ```php
     $pythonPath = 'C:\\Python313\\python.exe';
     ```
3. Restart Apache

**Verification**:
```bash
where python  # Windows
which python  # Linux
```

**Prevention**: Document Python PATH requirement in deployment guide

---

### Error: "FileNotFoundError: Model file not found at ml/parking_stand_model_rf_redo.pkl"
**Symptom**: Predictions fail with file not found

**Cause**: Model file missing or wrong path

**Fix**:
1. Check if file exists:
   ```bash
   dir C:\xampp\htdocs\amc\ml\parking_stand_model_rf_redo.pkl
   ```
2. Restore from backup:
   ```bash
   copy backup\parking_stand_model_rf_redo.pkl ml\
   ```
3. Or retrain model:
   ```bash
   python ml\train_model.py
   ```

**Prevention**:
- Always backup model files before retraining
- Use version control for model files (Git LFS)

---

### Error: "ValueError: Encoder aircraft_type not found"
**Symptom**: Predictions fail with encoder error

**Cause**: Encoders file missing or corrupted

**Fix**:
1. Check if file exists:
   ```bash
   dir C:\xampp\htdocs\amc\ml\encoders_redo.pkl
   ```
2. Restore from backup or regenerate encoders

**Prevention**: Backup encoders with model

---

### Error: "Prediction returns null" or "success: false"
**Symptom**: No predictions displayed in modal

**Cause**: Python script crashed or returned invalid JSON

**Fix**:
1. Check error logs:
   ```bash
   type storage\logs\error.log
   ```
2. Test Python script directly:
   ```bash
   echo {"aircraft_type":"B 738","operator_airline":"GARUDA","category":"COMMERCIAL"} | python ml\predict.py --top_k 3
   ```
3. Check for stderr output in ApronController.php (add logging):
   ```php
   error_log("Python stderr: " . $errors);
   ```

**Prevention**: Add error handling in PHP for null predictions

---

### Error: "KeyError: '__UNKNOWN__'"
**Symptom**: Prediction fails for unknown aircraft type

**Cause**: New aircraft type not in encoder, no fallback

**Fix**:
1. Add aircraft type to training data
2. Retrain model
3. Or implement fallback in predict.py:
   ```python
   if value not in encoder.classes_:
       return 0  # Fallback to most common
   ```

**Prevention**: Handle unknown values gracefully in prediction script

---

## PHP Errors

### Error: "Headers already sent" warning
**Symptom**: Cannot redirect or set cookies

**Cause**: Output sent before headers (e.g., echo, whitespace)

**Fix**:
1. Find where output is sent (check line number in error)
2. Remove output before header() or setcookie() calls
3. Check for whitespace before `<?php` tags
4. Use output buffering:
   ```php
   ob_start();
   // Your code
   ob_end_flush();
   ```

**Prevention**:
- Never output before headers
- Use strict `<?php` tags (no closing `?>` in class files)

---

### Error: "Call to undefined function legacy_app()"
**Symptom**: Application crashes on startup

**Cause**: `bootstrap/legacy.php` not loaded

**Fix**:
1. Verify file exists:
   ```bash
   type bootstrap\legacy.php
   ```
2. Check `index.php` loads it:
   ```php
   require_once __DIR__ . '/bootstrap/legacy.php';
   ```

**Prevention**: Never delete bootstrap files

---

### Error: "Class 'App\Controllers\ApronController' not found"
**Symptom**: Route returns 404 or class not found error

**Cause**: Autoloader not working or class file missing

**Fix**:
1. Verify file exists:
   ```bash
   type app\Controllers\ApronController.php
   ```
2. Check namespace matches directory structure:
   ```php
   namespace App\Controllers;  // Must match folder path
   ```
3. Clear OpCache (if enabled):
   ```bash
   # Restart Apache
   ```

**Prevention**: Follow PSR-4 autoloading conventions

---

### Error: "Maximum execution time of 30 seconds exceeded"
**Symptom**: Long-running requests timeout

**Cause**: Query or process takes too long

**Fix**:
1. Increase timeout in `php.ini`:
   ```ini
   max_execution_time = 300  ; 5 minutes
   ```
2. Or in code:
   ```php
   set_time_limit(300);
   ```
3. Optimize slow queries (use EXPLAIN)

**Prevention**: Add query optimization and pagination

---

### Error: "Allowed memory size exhausted"
**Symptom**: PHP crashes with memory error

**Cause**: Large dataset processing

**Fix**:
1. Increase memory in `php.ini`:
   ```ini
   memory_limit = 256M  ; or 512M
   ```
2. Or in code:
   ```php
   ini_set('memory_limit', '256M');
   ```
3. Use pagination for large datasets

**Prevention**: Always paginate large result sets

---

## Authentication Errors

### Error: "Invalid credentials"
**Symptom**: Cannot login with correct username/password

**Cause**:
- Password hash mismatch
- User disabled (`is_active = 0`)
- Login throttler blocked IP

**Fix**:
1. Check user exists and is active:
   ```sql
   SELECT * FROM users WHERE username = 'admin';
   ```
2. Check login attempts:
   ```sql
   SELECT * FROM login_attempts WHERE ip_address = 'YOUR_IP' AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE);
   ```
3. Reset password:
   ```sql
   UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
   -- Password is now 'password'
   ```
4. Clear login attempts:
   ```sql
   DELETE FROM login_attempts WHERE ip_address = 'YOUR_IP';
   ```

**Prevention**: Document default credentials

---

### Error: "Session expired. Please log in again."
**Symptom**: Randomly logged out during work

**Cause**: Session timeout (default 30 minutes inactivity)

**Fix**:
1. Increase session timeout in `config/app.php`:
   ```php
   'session_timeout' => 3600,  // 1 hour
   ```
2. Or in `config/session.php`:
   ```php
   'lifetime' => 120,  // 120 minutes
   ```
3. Restart browser to clear old sessions

**Prevention**: Set appropriate timeout for use case

---

### Error: "CSRF token mismatch"
**Symptom**: Forms fail with CSRF error

**Cause**: Token expired or not submitted

**Fix**:
1. Refresh page (generates new token)
2. Check form has token field:
   ```html
   <input type="hidden" name="csrf_token" value="<?= legacy_csrf_token() ?>">
   ```
3. Check token validation in controller:
   ```php
   if (!$this->csrf->validate($request->input('csrf_token'))) {
       // Handle error
   }
   ```

**Prevention**: Always include CSRF token in forms

---

## Cache Errors

### Error: "Permission denied" writing to cache directory
**Symptom**: Slow predictions, cache not working

**Cause**: Cache directory not writable

**Fix**:
1. Check directory exists:
   ```bash
   dir C:\xampp\htdocs\amc\cache
   ```
2. Create if missing:
   ```bash
   mkdir C:\xampp\htdocs\amc\cache
   ```
3. Grant write permissions:
   - Windows: Right-click → Properties → Security → Grant "Full Control"
   - Linux: `chmod 775 cache/`

**Verification**:
```bash
# Try writing test file
echo "test" > cache\test.txt
```

**Prevention**: Set permissions during installation

---

### Error: "json_decode() failed" from cache
**Symptom**: Cache returns null or invalid data

**Cause**: Corrupted cache file or invalid JSON

**Fix**:
1. Clear cache:
   ```bash
   php tools\cleanup_cache.php
   ```
2. Delete cache directory:
   ```bash
   rd /s /q cache
   mkdir cache
   ```

**Prevention**: Add JSON validation in FileCache::get()

---

### Error: "Disk full" when writing cache
**Symptom**: Cache writes fail silently

**Cause**: Disk space exhausted

**Fix**:
1. Check disk space:
   ```bash
   # Windows
   dir C:\

   # Linux
   df -h
   ```
2. Clean up old cache files:
   ```bash
   php tools\cleanup_cache.php
   ```
3. Free disk space (delete logs, temp files)

**Prevention**: Monitor disk space, setup automatic cache cleanup

---

## Performance Issues

### Issue: Slow apron view loading (> 5 seconds)
**Symptom**: Apron page takes long to load

**Cause**: Missing database indexes or large movement history

**Fix**:
1. Apply performance indexes:
   ```bash
   mysql -u root -p amc < database\migrations\add_performance_indexes.sql
   ```
2. Optimize tables:
   ```sql
   OPTIMIZE TABLE aircraft_movements, aircraft_details;
   ```
3. Check slow query log:
   ```sql
   SHOW VARIABLES LIKE 'slow_query_log';
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 1;  -- Log queries > 1 second
   ```
4. Add EXPLAIN to slow queries:
   ```sql
   EXPLAIN SELECT am.*, ad.aircraft_type
   FROM aircraft_movements am
   LEFT JOIN aircraft_details ad ON am.registration = ad.registration
   WHERE am.movement_date = CURDATE();
   ```

**Prevention**: Always apply indexes, monitor query performance

---

### Issue: Slow ML predictions (> 10 seconds)
**Symptom**: Predictions take too long

**Cause**: Model not cached, Python startup slow, or disk I/O slow

**Fix**:
1. Pre-warm cache by making test prediction after restart
2. Use SSD instead of HDD
3. Check Python model cache:
   ```python
   # In ml/model_cache.py, verify cache working
   print("Loading from cache" if cached else "Loading from disk")
   ```
4. Reduce model size (retrain with fewer features or smaller ensemble)

**Prevention**: Pre-warm cache on deployment, use SSD

---

### Issue: High memory usage (> 1 GB)
**Symptom**: Server slows down, swap usage increases

**Cause**: Large result sets, memory leaks, or OpCache issues

**Fix**:
1. Paginate large queries (limit 100 rows at a time)
2. Restart Apache to clear memory:
   ```bash
   # XAMPP Control Panel → Stop Apache → Start Apache
   ```
3. Check PHP memory limit:
   ```php
   echo ini_get('memory_limit');  // Should be 128M or 256M
   ```
4. Disable OpCache if causing issues:
   ```ini
   ; In php.ini
   opcache.enable = 0
   ```

**Prevention**: Paginate queries, monitor memory usage

---

## Summary of Critical Issues

**Top 10 Most Common Errors**:
1. "Database connection failed" → MySQL not running
2. "Python not found" → Python not in PATH
3. "Model file not found" → Model files missing
4. "CSRF token mismatch" → Session expired
5. "Headers already sent" → Output before headers
6. "Permission denied" on cache → Folder not writable
7. "Duplicate entry" → Registration already exists
8. "Session expired" → Timeout too short
9. "Slow apron view" → Missing indexes
10. "Invalid credentials" → Login throttler blocked

**Error Prevention Checklist**:
- [ ] Python in system PATH
- [ ] Database indexes applied
- [ ] Cache directory writable
- [ ] Model files backed up
- [ ] Session timeout configured
- [ ] Error logging enabled
- [ ] Regular database optimization
- [ ] Monitor disk space
- [ ] Test predictions after deployment
- [ ] Document all configuration changes

**Debugging Tips**:
1. Check error logs first: `storage/logs/error.log`
2. Enable debug mode: `config/app.php` → `'debug' => true`
3. Use var_dump() and error_log() for debugging
4. Test components independently (Python, database, cache)
5. Check browser console for JavaScript errors
6. Use phpMyAdmin for database debugging
7. Test API endpoints with cURL/Postman
8. Monitor Apache error log: `C:\xampp\apache\logs\error.log`
