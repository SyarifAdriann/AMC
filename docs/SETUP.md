# Setup & Installation Guide - AMC Parking Stand Prediction System

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Python Environment Setup](#python-environment-setup)
5. [Configuration](#configuration)
6. [Running the Development Server](#running-the-development-server)
7. [Post-Installation](#post-installation)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements
- **Operating System**: Windows 10/11 or Windows Server 2019+
- **CPU**: 2 cores (4 recommended)
- **RAM**: 4 GB (8 GB recommended)
- **Disk Space**: 2 GB for application + 500 MB for database
- **Network**: Intranet or localhost (no internet required for operation)

### Software Requirements

#### PHP
- **Version**: PHP 8.3.25 or later
- **Required Extensions**:
  - `pdo_mysql` (PDO MySQL driver)
  - `mbstring` (Multibyte string support)
  - `json` (JSON support)
  - `session` (Session support)
  - `openssl` (For password hashing)

#### Database
- **Version**: MariaDB 10.4.32 or MySQL 8.0+
- **Storage**: InnoDB engine

#### Python
- **Version**: Python 3.13.5 or later
- **Required Packages**:
  - `numpy >= 1.26.0`
  - `pandas >= 2.1.0`
  - `scikit-learn >= 1.3.0`

#### Web Server
- **Recommended**: Apache 2.4+ with `mod_rewrite`
- **Alternative**: XAMPP (includes Apache, PHP, MySQL/MariaDB)

#### Development Tools (Optional)
- **Node.js 18+** (for Tailwind CSS compilation)
- **Git** (for version control)
- **Composer** (PHP package manager, future use)

---

## Installation Steps

### Step 1: Install XAMPP

**⚠️ RECOMMENDED**: Use XAMPP for quick setup on Windows.

1. Download XAMPP from https://www.apachefriends.org/
2. Run installer and select components:
   - ✅ Apache
   - ✅ MySQL (MariaDB)
   - ✅ PHP
   - ❌ FileZilla (optional)
   - ❌ Mercury (not needed)
   - ❌ Tomcat (not needed)

3. Install to `C:\xampp` (default location)

4. Start XAMPP Control Panel
5. Start Apache and MySQL services

**Verify Installation**:
- Open browser: http://localhost
- Should see XAMPP dashboard

---

### Step 2: Clone/Extract Project

**Option A: From Git Repository**
```bash
cd C:\xampp\htdocs
git clone <repository-url> amc
cd amc
```

**Option B: From ZIP Archive**
```bash
# Extract amc.zip to C:\xampp\htdocs\amc
```

**Verify Directory Structure**:
```bash
dir C:\xampp\htdocs\amc
```

Should see:
```
app/
ml/
resources/
public/
config/
bootstrap/
routes/
database/
tools/
index.php
amc.sql
...
```

---

### Step 3: Set File Permissions

**Windows (XAMPP)**:
1. Right-click `cache/` folder → Properties → Security
2. Grant "Full Control" to "Everyone" (or Apache user)
3. Repeat for `storage/` folder

**Linux** (if not using XAMPP):
```bash
chmod -R 755 /var/www/html/amc
chmod -R 775 /var/www/html/amc/cache
chmod -R 775 /var/www/html/amc/storage
chown -R www-data:www-data /var/www/html/amc
```

---

## Database Setup

### Step 1: Create Database

**Option A: Using phpMyAdmin** (XAMPP)
1. Open http://localhost/phpmyadmin
2. Click "New" in left sidebar
3. Database name: `amc`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

**Option B: Using MySQL CLI**
```bash
mysql -u root -p
```
```sql
CREATE DATABASE amc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

---

### Step 2: Import Database Schema

**Option A: Using phpMyAdmin**
1. Select `amc` database
2. Click "Import" tab
3. Choose file: `C:\xampp\htdocs\amc\amc.sql`
4. Click "Go"
5. Wait for import to complete

**Option B: Using MySQL CLI**
```bash
cd C:\xampp\htdocs\amc
mysql -u root -p amc < amc.sql
```

**Verify Import**:
```sql
USE amc;
SHOW TABLES;
```

Should see 12 tables:
```
aircraft_details
aircraft_movements
airline_preferences
audit_log
daily_snapshots
daily_staff_roster
flight_references
login_attempts
ml_model_versions
ml_prediction_log
narrative_logbook_amc
stands
users
```

---

### Step 3: Apply Performance Indexes

```bash
cd C:\xampp\htdocs\amc
mysql -u root -p amc < database\migrations\add_performance_indexes.sql
```

**⚠️ IMPORTANT**: Performance indexes are critical for system performance. Do not skip this step.

---

## Python Environment Setup

### Step 1: Install Python

**Download**: https://www.python.org/downloads/
**Version**: Python 3.13.5 or later

**Installation Options**:
- ✅ Add Python to PATH (IMPORTANT!)
- ✅ Install pip
- ✅ Install for all users (optional)

**Verify Installation**:
```bash
python --version
pip --version
```

---

### Step 2: Install Python Packages

**Open Command Prompt**:
```bash
cd C:\xampp\htdocs\amc
pip install numpy pandas scikit-learn
```

**Verify Installation**:
```bash
python -c "import numpy, pandas, sklearn; print('All packages installed')"
```

Should output: `All packages installed`

---

### Step 3: Test ML Prediction

```bash
cd C:\xampp\htdocs\amc
echo {"aircraft_type":"B 738","operator_airline":"GARUDA","category":"COMMERCIAL"} | python ml\predict.py --top_k 3
```

**Expected Output**:
```json
{
  "success": true,
  "predictions": [
    {"stand": "C4", "probability": 0.45, "rank": 1},
    ...
  ]
}
```

**If Error**: See [Troubleshooting](#troubleshooting) section

---

## Configuration

### Step 1: Database Configuration

**File**: `config/database.php`

**Default Configuration** (XAMPP):
```php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'amc',
            'username' => 'root',
            'password' => '',  // Empty for XAMPP
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
```

**⚠️ FOR PRODUCTION**: Change password and create dedicated database user.

**Production Example**:
```php
'username' => 'amc_user',
'password' => 'strong_password_here',
```

**Create Production User**:
```sql
CREATE USER 'amc_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON amc.* TO 'amc_user'@'localhost';
FLUSH PRIVILEGES;
```

---

### Step 2: Application Configuration

**File**: `config/app.php`

**Default Configuration**:
```php
return [
    'name' => 'Aircraft Movement Control',
    'env' => 'production',  // Change to 'development' for debugging
    'debug' => false,       // Set to true for debugging
    'url' => 'http://localhost',
    'roles' => ['admin', 'operator', 'viewer'],
    'session_timeout' => 1800,  // 30 minutes
    'login' => [
        'max_attempts' => 5,
        'lockout_seconds' => 900,  // 15 minutes
    ],
];
```

**⚠️ PRODUCTION SETTINGS**:
- `env` → `'production'`
- `debug` → `false`
- `url` → Your actual domain (e.g., `'http://amc.airport.local'`)

---

### Step 3: Session Configuration

**File**: `config/session.php`

**Default Configuration**:
```php
return [
    'lifetime' => 120,  // 120 minutes
    'expire_on_close' => false,
    'cookie_httponly' => true,
    'cookie_secure' => false,  // Set to true if using HTTPS
    'cookie_samesite' => 'Lax',
];
```

**⚠️ FOR HTTPS**: Set `'cookie_secure' => true`

---

### Step 4: Python Path Configuration

**⚠️ CRITICAL**: Ensure Python is in system PATH

**Verify**:
```bash
where python
```

Should output: `C:\Python313\python.exe` (or similar)

**If Not in PATH**:
1. Search "Environment Variables" in Windows
2. Edit "System Environment Variables"
3. Edit "Path" variable
4. Add Python installation directory (e.g., `C:\Python313\`)
5. Restart Command Prompt and Apache

**Alternative**: Hardcode Python path in `ApronController.php`:
```php
// Line ~800
$pythonPath = 'C:\\Python313\\python.exe';  // Full path
```

---

## Running the Development Server

### Step 1: Start Services

**XAMPP**:
1. Open XAMPP Control Panel
2. Start Apache
3. Start MySQL

**Verify Services**:
- Apache: http://localhost (should see XAMPP dashboard)
- MySQL: http://localhost/phpmyadmin (should see phpMyAdmin)

---

### Step 2: Access Application

**Main URL**: http://localhost/amc

**Login Credentials**:
- **Username**: admin
- **Password**: (Check `users` table or create new user)

**Create Admin User** (if none exists):
```sql
INSERT INTO users (username, email, password, role, full_name, is_active)
VALUES (
    'admin',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: 'password'
    'admin',
    'System Administrator',
    1
);
```

**Password**: `password` (change after first login)

---

### Step 3: Verify Functionality

**Checklist**:
- [ ] Login works
- [ ] Apron view loads
- [ ] Movements display
- [ ] ML recommendations work (click "Recommend Stand" in movement modal)
- [ ] Dashboard loads (admin/operator only)
- [ ] Master table works

**If Issues**: See [Troubleshooting](#troubleshooting)

---

## Post-Installation

### Step 1: Change Default Passwords

**Admin User**:
1. Login as admin
2. Go to Dashboard → Accounts
3. Click "Reset Password"
4. Enter new password

**Database User** (Production):
```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'new_strong_password';
FLUSH PRIVILEGES;
```

---

### Step 2: Configure Automatic Daily Snapshot

**Option A: Windows Task Scheduler**
1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Daily at 23:59
4. Action: Start Program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\amc\tools\console.php daily_snapshot`
5. Save

**Option B: Manual Execution**
```bash
php C:\xampp\htdocs\amc\tools\console.php daily_snapshot
```

---

### Step 3: Setup Cache Cleanup (Optional)

**Windows Task Scheduler**:
1. Create task: "AMC Cache Cleanup"
2. Trigger: Daily at 03:00
3. Action: Start Program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\amc\tools\cleanup_cache.php`

---

### Step 4: Setup Backup Strategy

**Database Backup** (daily):
```bash
mkdir C:\backups
C:\xampp\mysql\bin\mysqldump.exe -u root -p amc > C:\backups\amc_%date:~-4,4%%date:~-7,2%%date:~-10,2%.sql
```

**Model Files Backup** (after retraining):
```bash
copy C:\xampp\htdocs\amc\ml\parking_stand_model_rf_redo.pkl C:\backups\
copy C:\xampp\htdocs\amc\ml\encoders_redo.pkl C:\backups\
```

---

## Deployment

### Production Deployment Checklist

**Pre-Deployment**:
- [ ] Backup current database
- [ ] Backup current model files
- [ ] Test on staging environment
- [ ] Verify Python environment

**Configuration**:
- [ ] Set `env` to `'production'` in `config/app.php`
- [ ] Set `debug` to `false` in `config/app.php`
- [ ] Update `url` in `config/app.php`
- [ ] Change database password
- [ ] Enable HTTPS (set `cookie_secure` to `true`)

**Security**:
- [ ] Change all default passwords
- [ ] Restrict database access (bind to localhost only)
- [ ] Configure firewall (only allow HTTP/HTTPS ports)
- [ ] Enable Apache `mod_security` (optional)
- [ ] Set proper file permissions

**Performance**:
- [ ] Apply all database indexes (`add_performance_indexes.sql`)
- [ ] Enable PHP OpCache
- [ ] Configure cache directory permissions
- [ ] Test ML prediction performance

**Monitoring**:
- [ ] Setup error logging
- [ ] Monitor disk space
- [ ] Monitor database size
- [ ] Monitor prediction accuracy (check `ml_prediction_log`)

---

## Troubleshooting

### Issue 1: "Cannot connect to database"

**Symptoms**: White page or "SQLSTATE[HY000] [2002]" error

**Causes**:
- MySQL service not running
- Wrong database credentials
- Database does not exist

**Solutions**:
1. Check MySQL service:
   ```bash
   # XAMPP Control Panel → MySQL → Start
   ```
2. Verify database exists:
   ```sql
   SHOW DATABASES LIKE 'amc';
   ```
3. Check credentials in `config/database.php`

---

### Issue 2: "Python not found"

**Symptoms**: ML predictions fail with "CreateProcess failed"

**Causes**:
- Python not installed
- Python not in PATH

**Solutions**:
1. Verify Python installation:
   ```bash
   python --version
   ```
2. Add Python to PATH (see [Configuration](#step-4-python-path-configuration))
3. Or hardcode path in `ApronController.php`

---

### Issue 3: "Model file not found"

**Symptoms**: ML predictions fail with "FileNotFoundError"

**Causes**:
- Model files missing
- Wrong file path

**Solutions**:
1. Verify model files exist:
   ```bash
   dir C:\xampp\htdocs\amc\ml\parking_stand_model_rf_redo.pkl
   dir C:\xampp\htdocs\amc\ml\encoders_redo.pkl
   ```
2. Restore from backup or retrain model

---

### Issue 4: "Permission denied" on cache directory

**Symptoms**: Slow predictions, cache errors in logs

**Causes**:
- Cache directory not writable

**Solutions**:
1. Check permissions on `cache/` folder
2. Grant write permissions to Apache user
3. Create cache directory if missing:
   ```bash
   mkdir C:\xampp\htdocs\amc\cache
   ```

---

### Issue 5: "Login throttler blocked"

**Symptoms**: Cannot login after 5 failed attempts

**Causes**:
- Too many failed login attempts

**Solutions**:
1. Wait 15 minutes
2. Or manually clear attempts:
   ```sql
   DELETE FROM login_attempts WHERE ip_address = 'your_ip';
   ```

---

### Issue 6: "CSRF token mismatch"

**Symptoms**: Forms fail with "Invalid CSRF token"

**Causes**:
- Session expired
- Token not submitted

**Solutions**:
1. Refresh page (generates new token)
2. Check session configuration in `config/session.php`
3. Verify `csrf_token` field in form

---

### Issue 7: "Slow apron view loading"

**Symptoms**: Apron view takes > 5 seconds to load

**Causes**:
- Missing database indexes
- Large movement history

**Solutions**:
1. Apply performance indexes:
   ```bash
   mysql -u root -p amc < database\migrations\add_performance_indexes.sql
   ```
2. Optimize tables:
   ```sql
   OPTIMIZE TABLE aircraft_movements, ml_prediction_log;
   ```

---

## Summary

**Installation Time**: 30-60 minutes
**Critical Steps**: Database setup, Python installation, performance indexes
**Default URL**: http://localhost/amc
**Default User**: admin / password (change after login)

**⚠️ PRODUCTION CHECKLIST**:
1. Change all default passwords
2. Apply security configuration
3. Enable HTTPS
4. Setup automatic backups
5. Monitor logs and performance
