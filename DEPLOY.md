# Aircraft Movement Control (AMC) - Deployment Guide

**Last Updated:** 2025-11-23
**Project:** AMC Monitoring System with ML-based Stand Recommendations
**Tech Stack:** PHP 8.0+, MySQL 8.0+, Python 3.7+, JavaScript (Vanilla + Tailwind CSS)

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Critical Security Issues](#critical-security-issues)
3. [Pre-Deployment Requirements](#pre-deployment-requirements)
4. [Free Hosting Options](#free-hosting-options)
5. [Environment Configuration](#environment-configuration)
6. [Deployment Checklist](#deployment-checklist)
7. [Step-by-Step Deployment](#step-by-step-deployment)
8. [Post-Deployment Tasks](#post-deployment-tasks)
9. [Monitoring & Maintenance](#monitoring-maintenance)
10. [Troubleshooting](#troubleshooting)

---

## Executive Summary

### What This System Does
- **Aircraft Movement Tracking:** Real-time apron monitoring with 92 parking stands
- **ML-Based Recommendations:** Random Forest model (80.15% accuracy) suggests optimal parking stands
- **User Management:** 3-tier role system (Admin, Operator, Viewer)
- **Real-time Dashboard:** Auto-refreshing metrics every 30 seconds

### System Requirements
- **PHP:** 8.0+ (requires match expressions, constructor property promotion)
- **Python:** 3.7+ (ML predictions via subprocess)
- **MySQL:** 8.0+ or MariaDB 10.4+
- **Node.js:** Only for CSS compilation (development)
- **Disk Space:** ~500MB (database + ML models + cache)
- **RAM:** Minimum 512MB, Recommended 1GB

### Current Status
⚠️ **NOT PRODUCTION-READY** - Contains critical security issues that must be fixed before deployment.

---

## Critical Security Issues

### 🔴 MUST FIX BEFORE DEPLOYMENT

#### 1. Weak Default Database Credentials
**File:** `config/database.php` (Lines 8-12)
**Issue:** Falls back to root user with empty password if environment variables not set

```php
// VULNERABLE CODE:
'username' => getenv('DB_USERNAME') ?: 'root',
'password' => getenv('DB_PASSWORD') ?: '',
```

**Fix Required:**
```php
'username' => getenv('DB_USERNAME') ?: throw new RuntimeException('DB_USERNAME not set'),
'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD not set'),
```

---

#### 2. Hardcoded Windows Paths
**Files:**
- `tools/precompute_preferences.php` (Line 10)
- `tools/cleanup_cache.php` (Line 9)

**Issue:** Documentation contains `C:\xampp\htdocs\amc\` which won't work on Linux/production

**Fix Required:**
- Use environment variable `APP_ROOT` or `__DIR__` relative paths
- Update all Windows-specific paths to Linux-compatible format

---

#### 3. Potential SQL Injection via Dynamic Field Names
**File:** `app/Repositories/AircraftMovementRepository.php` (Lines 339, 363, 391)

**Issue:** Field names interpolated directly without whitelist
```php
$sql = "UPDATE aircraft_movements SET `$field` = :value ...";
```

**Fix Required:**
```php
$allowedFields = ['on_block_time', 'off_block_time', 'parking_stand', 'remarks'];
if (!in_array($field, $allowedFields, true)) {
    throw new InvalidArgumentException("Invalid field: $field");
}
```

---

#### 4. Pickle Deserialization Risk
**Files:** `ml/predict.py`, `ml/health_check.py`, `ml/model_cache.py`

**Issue:** Loading untrusted pickle files can execute arbitrary code
```python
ALL_ENCODERS = pickle.load(handle)  # DANGEROUS if file is tampered
```

**Fix Required:**
- Add file integrity checks (SHA-256 hash verification)
- OR migrate to JSON serialization for encoders
- Store checksums in database or config file

---

#### 5. Missing HTTPS/Secure Session Configuration
**File:** `config/session.php` (Lines 7-12)

**Issue:** Session cookies not marked as secure
```php
'secure' => null,  // Should be true in production
```

**Fix Required:**
```php
'secure' => getenv('APP_ENV') === 'production',
'samesite' => 'Strict',  // Change from 'Lax'
```

---

#### 6. Debug Mode Exposure Risk
**File:** `config/app.php` (Line 6)

**Issue:** Debug mode could leak sensitive information in production

**Fix Required:**
```bash
# MUST set in production:
APP_DEBUG=false
APP_ENV=production
```

---

### 🟡 SHOULD FIX (High Priority)

#### 7. Shell Execution via shell_exec()
**Files:**
- `tools/test_proc_open_integration.php` (Line 259)
- `app/Controllers/ApronController.php` (Line 917)

**Recommendation:** Replace with PHP native functions where possible

#### 8. DOM-based XSS via innerHTML
**Files:** `assets/js/apron.js`, `assets/js/dashboard.js`

**Recommendation:** Use `textContent` for text, `createElement()` for dynamic HTML

#### 9. No Database User Isolation
**Recommendation:** Create limited privilege database user (see below)

---

## Pre-Deployment Requirements

### 1. Required PHP Extensions
```bash
# Check installed extensions:
php -m

# Required:
- pdo_mysql
- json
- mbstring
- openssl
- curl
- zip
```

### 2. Python Dependencies
**Create `requirements.txt` (currently missing!):**
```txt
numpy>=1.20.0,<2.0.0
pandas>=1.3.0,<2.0.0
scikit-learn>=1.0.0,<2.0.0
```

**Install:**
```bash
pip install -r requirements.txt
# OR create virtual environment:
python3 -m venv venv
source venv/bin/activate  # Linux/Mac
# venv\Scripts\activate   # Windows
pip install -r requirements.txt
```

### 3. Database Setup
```sql
-- Create database
CREATE DATABASE amc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create limited user (NOT root!)
CREATE USER 'amc_app'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT SELECT, INSERT, UPDATE, DELETE ON amc.* TO 'amc_app'@'localhost';
FLUSH PRIVILEGES;

-- Import schema
mysql -u amc_app -p amc < amc.sql

-- Apply performance indexes
mysql -u amc_app -p amc < database/migrations/add_performance_indexes.sql
```

### 4. Required Directory Structure
```bash
# Create with correct permissions:
mkdir -p storage/cache/aircraft_details
mkdir -p storage/cache
mkdir -p logs
mkdir -p reports

# Set permissions (Linux):
chmod 755 storage storage/cache logs reports
chmod 775 storage/cache/aircraft_details
chown -R www-data:www-data storage logs reports
```

### 5. Build Production CSS
```bash
npm install
npm run build  # Creates public/assets/css/tailwind.css (34KB)
```

---

## Free Hosting Options

### Comparison Table

| Provider | PHP | MySQL | Python | ML Support | Free Tier | Best For |
|----------|-----|-------|--------|------------|-----------|----------|
| **HelioHost** | ✅ Yes | ✅ Yes | ✅ Yes | ⚠️ Limited | Unlimited bandwidth | **RECOMMENDED** - All-in-one |
| **PythonAnywhere** | ❌ No | ✅ Yes | ✅ Yes | ✅ Good | Basic tier | Python-first apps |
| **Railway** | ✅ Yes | ✅ Yes | ✅ Yes | ⚠️ CPU only | $5/month trial | Easiest setup |
| **Render** | ❌ No | ⚠️ PostgreSQL only | ✅ Yes | ✅ Good | 750hrs/month | Python APIs |
| **InfinityFree** | ✅ Yes | ✅ Yes | ❌ No | ❌ No | Unlimited | PHP only (no ML) |

---

### Option 1: HelioHost (RECOMMENDED) ⭐

**Website:** https://heliohost.org/

**Pros:**
- ✅ Supports PHP 8.0+, Python 3.x, MySQL, and more
- ✅ SSH access (can install Python packages)
- ✅ Unlimited bandwidth
- ✅ Free domain (.helioho.st subdomain)
- ✅ All components work (PHP + Python + MySQL)

**Cons:**
- ⚠️ Sign-ups only open at specific times
- ⚠️ Account suspended if idle for 30 days
- ⚠️ Limited CPU time (may need to optimize ML predictions)

**Limitations:**
- CPU: ~10,000 CPU seconds/day
- RAM: Shared (no guarantee)
- Storage: 1GB-6GB depending on server
- Python subprocess execution supported

**Deployment Steps:**
1. Sign up during registration window
2. Choose server (Tommy recommended for Python)
3. Upload files via FTP/cPanel
4. Set up MySQL database via cPanel
5. Configure Python environment via SSH
6. Set environment variables in `.htaccess` or PHP

**Configuration:**
```apache
# .htaccess for environment variables
SetEnv DB_HOST localhost
SetEnv DB_DATABASE amc_username
SetEnv DB_USERNAME amc_username
SetEnv DB_PASSWORD your_password
SetEnv APP_ENV production
SetEnv APP_DEBUG false
```

---

### Option 2: PythonAnywhere (For Python-Heavy Workloads)

**Website:** https://www.pythonanywhere.com/

**Pros:**
- ✅ Excellent Python/ML support
- ✅ MySQL included
- ✅ Free tier with no time limit
- ✅ Built-in Jupyter notebooks

**Cons:**
- ❌ **No PHP support** (major issue for this project)
- ⚠️ Would require complete rewrite to Python (Flask/Django)

**Not Recommended** unless you migrate from PHP to Python framework.

---

### Option 3: Railway ($5/month, Easy Setup)

**Website:** https://railway.com/

**Pros:**
- ✅ Supports PHP, Python, MySQL
- ✅ GitHub integration
- ✅ Environment variable management
- ✅ Auto-scaling

**Cons:**
- ⚠️ Not free - $5/month minimum (trial gives $5 credit)
- ⚠️ Trial limited to 30 days
- ⚠️ Free tier resources: 0.5GB RAM, 1 vCPU

**Best For:** Quick MVP deployment, willing to pay $5/month

**Deployment:**
1. Connect GitHub repository
2. Add MySQL database service
3. Set environment variables
4. Deploy via dashboard
5. Railway auto-detects PHP/Python

---

### Option 4: Hybrid Approach (Split Services)

**Concept:** Split PHP frontend and Python ML backend

| Component | Service | Cost |
|-----------|---------|------|
| **PHP App + MySQL** | InfinityFree or 000webhost | Free |
| **Python ML API** | Render or Railway | Free tier |

**Setup:**
1. Deploy PHP app to InfinityFree (free PHP + MySQL hosting)
2. Deploy Python ML script as Flask API on Render (free 750hrs/month)
3. Configure PHP to call ML API via HTTP instead of subprocess

**Pros:**
- ✅ Both services free
- ✅ Better separation of concerns
- ✅ ML service can be restarted independently

**Cons:**
- ⚠️ Requires code modification (ML calls via HTTP not subprocess)
- ⚠️ Network latency between services
- ⚠️ More complex setup

---

### Option 5: VPS with Free Credits

**Providers:**
- **DigitalOcean:** $200 credit for 60 days (via GitHub Student Pack)
- **Linode (Akamai):** $100 credit for 60 days
- **Vultr:** $100 credit (various promotions)

**Pros:**
- ✅ Full control (root access)
- ✅ Can install anything
- ✅ SSH access
- ✅ Good performance

**Cons:**
- ⚠️ Not permanently free (credits expire)
- ⚠️ Requires server administration knowledge
- ⚠️ Security responsibility on you

**Recommended for:** Testing production deployment before going live

---

## Environment Configuration

### Create `.env.example`

**Create this file to document required variables:**

```bash
# Application
APP_NAME="Aircraft Movement Control"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=amc
DB_USERNAME=amc_app
DB_PASSWORD=your_secure_password_here

# Session
SESSION_LIFETIME=1800
SESSION_SECURE=true

# Python
PYTHON_PATH=/usr/bin/python3
```

### Create `.env` (NEVER COMMIT TO GIT!)

```bash
cp .env.example .env
# Edit .env with actual production values
chmod 600 .env  # Restrict permissions
```

### Update `config/database.php`

```php
<?php

// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            putenv(trim($line));
        }
    }
}

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: throw new RuntimeException('DB_HOST not set'),
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: throw new RuntimeException('DB_DATABASE not set'),
            'username' => getenv('DB_USERNAME') ?: throw new RuntimeException('DB_USERNAME not set'),
            'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD not set'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
```

---

## Deployment Checklist

### Pre-Deployment (Local Testing)

- [ ] **Security Fixes Applied**
  - [ ] Database credentials throw exception if missing
  - [ ] SQL field name whitelist implemented
  - [ ] Session secure flag set to true
  - [ ] Debug mode disabled (APP_DEBUG=false)
  - [ ] Pickle file integrity checks added
  - [ ] innerHTML replaced with safe DOM methods

- [ ] **Dependencies Installed**
  - [ ] Python packages: `pip install -r requirements.txt`
  - [ ] Node packages: `npm install`
  - [ ] Tailwind CSS built: `npm run build`

- [ ] **Database Ready**
  - [ ] Schema imported: `mysql < amc.sql`
  - [ ] Indexes applied: `mysql < database/migrations/add_performance_indexes.sql`
  - [ ] Test data verified
  - [ ] Limited user created (not root)

- [ ] **File Paths Fixed**
  - [ ] All `C:\xampp\` paths removed
  - [ ] Relative paths use `__DIR__` or config
  - [ ] Cache directories use environment variables

- [ ] **Environment Configuration**
  - [ ] `.env.example` created and documented
  - [ ] `.env` configured with production values
  - [ ] `.gitignore` includes `.env`

- [ ] **Testing Completed**
  - [ ] ML predictions work: `python ml/predict.py --payload '{"aircraft_type":"B737-800",...}'`
  - [ ] Login system works
  - [ ] CRUD operations work
  - [ ] Dashboard auto-refresh works
  - [ ] Apron updates work

---

### Deployment

- [ ] **File Upload**
  - [ ] All PHP files uploaded
  - [ ] Python scripts uploaded (ml/ folder)
  - [ ] Compiled CSS uploaded (public/assets/css/tailwind.css)
  - [ ] JavaScript files uploaded
  - [ ] ML model files uploaded (.pkl files ~50MB)

- [ ] **Directory Permissions**
  - [ ] `storage/cache/` writable (755 or 775)
  - [ ] `logs/` writable
  - [ ] `reports/` writable
  - [ ] Pickle files readable (644)

- [ ] **Database Setup**
  - [ ] Database created
  - [ ] User created with limited privileges
  - [ ] Schema imported
  - [ ] Indexes created
  - [ ] Connection tested

- [ ] **Environment Variables**
  - [ ] `.env` created on server
  - [ ] All required variables set
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_ENV=production`
  - [ ] Permissions set to 600

- [ ] **Web Server Configuration**
  - [ ] Document root set to project root (or public/ if restructured)
  - [ ] PHP version 8.0+ enabled
  - [ ] Python path configured
  - [ ] URL rewriting enabled (if using routing)

---

### Post-Deployment

- [ ] **Verification**
  - [ ] Site loads: https://your-domain.com
  - [ ] Login works
  - [ ] ML predictions return results
  - [ ] Dashboard updates automatically
  - [ ] No error messages in logs

- [ ] **Security**
  - [ ] HTTPS enabled (SSL certificate)
  - [ ] Debug mode disabled
  - [ ] Error logging reviewed
  - [ ] File permissions verified

- [ ] **Performance**
  - [ ] Cache precompute script scheduled: `tools/precompute_preferences.php`
  - [ ] Page load time < 2 seconds
  - [ ] ML predictions < 3 seconds

- [ ] **Monitoring**
  - [ ] Error logs monitored
  - [ ] ML accuracy tracked
  - [ ] Database backups scheduled

---

## Step-by-Step Deployment

### For HelioHost (Recommended)

#### Step 1: Sign Up & Account Setup

1. **Register Account:**
   - Visit https://heliohost.org/
   - Click "Sign Up" (only open at specific times)
   - Choose server: **Tommy** (best Python support)
   - Select subdomain: `yourproject.helioho.st`

2. **Wait for Account Activation:**
   - Usually takes 1-2 hours
   - Check email for confirmation

#### Step 2: Configure cPanel

1. **Login to cPanel:**
   - URL: https://tommy.heliohost.org:2083/
   - Username: your username
   - Password: your password

2. **Create MySQL Database:**
   - Navigate to **MySQL Databases**
   - Create database: `username_amc`
   - Create user: `username_amc_app`
   - Set strong password
   - Add user to database with ALL PRIVILEGES

3. **Import Database:**
   - Navigate to **phpMyAdmin**
   - Select database `username_amc`
   - Click **Import**
   - Upload `amc.sql`
   - Click **Go**
   - Repeat for `add_performance_indexes.sql`

#### Step 3: Upload Files

**Option A: FTP Upload**
```bash
# Use FileZilla or similar
Host: ftp.heliohost.org
Port: 21
Username: your_username
Password: your_password
```

**Option B: cPanel File Manager**
1. Navigate to **File Manager**
2. Go to `public_html/`
3. Upload all project files
4. Extract if zipped

**Files to Upload:**
- All PHP files (app/, config/, routes/, resources/)
- Python ML scripts (ml/)
- Compiled CSS (public/assets/css/tailwind.css)
- JavaScript files (assets/js/)
- ML model files (ml/*.pkl)

#### Step 4: SSH Configuration (Python Setup)

1. **Enable SSH:**
   - cPanel → **SSH Access**
   - Generate SSH key if needed

2. **Connect via SSH:**
   ```bash
   ssh username@tommy.heliohost.org
   ```

3. **Install Python Packages:**
   ```bash
   cd ~/public_html/amc

   # Create virtual environment
   python3 -m venv venv
   source venv/bin/activate

   # Install dependencies
   pip install numpy pandas scikit-learn

   # Test Python
   python ml/health_check.py
   ```

4. **Update Python Path in Code:**
   - Edit `app/Controllers/ApronController.php`
   - Update Python path to virtual environment:
   ```php
   $pythonPath = '/home/username/public_html/amc/venv/bin/python3';
   ```

#### Step 5: Environment Configuration

**Create `.env` file:**
```bash
# Upload via FTP or create in cPanel File Manager
# File: /public_html/amc/.env

APP_NAME="Aircraft Movement Control"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourproject.helioho.st

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_amc
DB_USERNAME=username_amc_app
DB_PASSWORD=your_database_password

SESSION_LIFETIME=1800
SESSION_SECURE=true

PYTHON_PATH=/home/username/public_html/amc/venv/bin/python3
```

**Set File Permissions:**
```bash
chmod 600 .env
chmod 755 storage storage/cache logs
chmod 775 storage/cache/aircraft_details
```

#### Step 6: Web Server Configuration

**Create `.htaccess` in root:**
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(env|log|sql|md|json)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### Step 7: Verify Deployment

1. **Test Website:**
   - Visit: https://yourproject.helioho.st
   - Should see login page

2. **Test Login:**
   - Default admin account (if in SQL dump)
   - Or create via database

3. **Test ML Predictions:**
   - Login → Apron page
   - Add movement → Click "Get AI Recommendations"
   - Should see 3 stand recommendations

4. **Check Error Logs:**
   - cPanel → **Errors**
   - Should be empty or only warnings

#### Step 8: Schedule Cache Precompute

**Using cPanel Cron Jobs:**
1. cPanel → **Cron Jobs**
2. Add new cron job:
   ```bash
   # Every day at 2 AM
   0 2 * * * /home/username/public_html/amc/venv/bin/python3 /home/username/public_html/amc/tools/precompute_preferences.php
   ```

#### Step 9: Monitor Performance

**First 24 Hours:**
- Check error logs every 2 hours
- Monitor ML prediction times
- Verify dashboard auto-refresh works
- Test CRUD operations

**First Week:**
- Review ML accuracy in `ml_prediction_log` table
- Check disk space usage
- Monitor CPU usage (HelioHost suspends if too high)
- Verify cache files are regenerating

---

## Post-Deployment Tasks

### 1. Create Initial Admin Account

```sql
-- If not in SQL dump, create manually:
INSERT INTO users (username, password_hash, full_name, role, created_at)
VALUES (
    'admin',
    '$2y$10$YourBcryptHashHere',  -- Use password_hash('YourPassword', PASSWORD_BCRYPT)
    'System Administrator',
    'admin',
    NOW()
);
```

**Generate hash:**
```php
<?php
echo password_hash('your_secure_password', PASSWORD_BCRYPT);
```

### 2. Configure Scheduled Tasks

**Precompute Historical Preferences (Daily):**
```bash
# Cron job (Linux):
0 2 * * * /path/to/php /path/to/tools/precompute_preferences.php >> /path/to/logs/cron.log 2>&1

# Windows Task Scheduler:
schtasks /create /tn "AMC Preference Cache" /tr "php C:\path\to\tools\precompute_preferences.php" /sc daily /st 02:00
```

**Cleanup Old Logs (Weekly):**
```bash
# Cron job:
0 3 * * 0 find /path/to/logs -name "*.log" -mtime +30 -delete
```

### 3. Set Up Database Backups

**Daily Backups:**
```bash
#!/bin/bash
# File: /path/to/scripts/backup_db.sh

DB_NAME="amc"
DB_USER="amc_app"
DB_PASS="your_password"
BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)

mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/amc_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "amc_*.sql.gz" -mtime +7 -delete
```

**Cron:**
```bash
0 1 * * * /path/to/scripts/backup_db.sh
```

### 4. Enable Error Monitoring

**Configure Error Logging:**
```php
// config/logging.php
return [
    'error_log' => '/path/to/logs/php_errors.log',
    'level' => 'error',  // production: error, development: debug
    'max_file_size' => 10485760,  // 10MB
];
```

**Monitor Errors:**
```bash
# Check recent errors:
tail -f /path/to/logs/php_errors.log

# Search for specific errors:
grep "CRITICAL" /path/to/logs/php_errors.log
```

### 5. Performance Optimization

**Enable OPcache (PHP):**
```ini
; php.ini or .user.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  ; Production only
```

**MySQL Query Cache:**
```sql
SET GLOBAL query_cache_size = 67108864;  -- 64MB
SET GLOBAL query_cache_type = 1;
```

### 6. Security Hardening

**File Permissions Checklist:**
```bash
# Application files
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Sensitive files
chmod 600 .env
chmod 600 config/*.php

# Executable scripts
chmod 755 ml/*.py

# Writable directories
chmod 775 storage/cache
chmod 775 logs
```

**Disable Dangerous PHP Functions:**
```ini
; php.ini or .user.ini
disable_functions = exec,passthru,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

**Note:** Be careful - `proc_open` is used for Python subprocess!

---

## Monitoring & Maintenance

### Key Metrics to Monitor

#### 1. Application Health
```sql
-- Check recent activity
SELECT COUNT(*) as movements_today
FROM aircraft_movements
WHERE movement_date = CURDATE();

-- Check ML prediction accuracy
SELECT
    COUNT(*) as total_predictions,
    SUM(was_prediction_correct) as correct,
    ROUND(SUM(was_prediction_correct) / COUNT(*) * 100, 2) as accuracy_pct
FROM ml_prediction_log
WHERE prediction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

#### 2. Performance Metrics
- **Page Load Time:** Should be < 2 seconds
- **ML Prediction Time:** Should be < 3 seconds
- **Database Query Time:** Should be < 200ms average

#### 3. Storage Usage
```bash
# Check disk usage
du -sh /path/to/amc
du -sh /path/to/amc/storage/cache
du -sh /path/to/amc/logs
```

### Weekly Maintenance Tasks

- [ ] Review error logs
- [ ] Check ML prediction accuracy
- [ ] Verify cache files are updating
- [ ] Review database size
- [ ] Check backup integrity

### Monthly Maintenance Tasks

- [ ] Update Python packages (if security updates)
- [ ] Review user accounts (disable inactive)
- [ ] Analyze slow queries
- [ ] Clean up old prediction logs (>90 days)
- [ ] Review access logs for suspicious activity

---

## Troubleshooting

### Issue 1: ML Predictions Return Error

**Symptoms:**
- "Get AI Recommendations" button returns error
- Console shows 500 Internal Server Error

**Causes & Solutions:**

1. **Python not found:**
   ```bash
   # Check Python path
   which python3
   # Update PYTHON_PATH in .env or ApronController.php
   ```

2. **Missing Python packages:**
   ```bash
   python3 -c "import numpy, pandas, sklearn; print('OK')"
   # If error, install: pip install numpy pandas scikit-learn
   ```

3. **Pickle file not found:**
   ```bash
   ls -lh ml/*.pkl
   # Should see: parking_stand_model_rf_redo.pkl, encoders_redo.pkl
   ```

4. **File permissions:**
   ```bash
   chmod 644 ml/*.pkl
   chmod 755 ml/*.py
   ```

---

### Issue 2: Dashboard Doesn't Update

**Symptoms:**
- Movement Snapshots shows 0
- Apron Movements by Hour not updating

**Causes & Solutions:**

1. **JavaScript not loading:**
   - Open browser console (F12)
   - Check for JavaScript errors
   - Verify `public/assets/js/dashboard.js` loaded

2. **API endpoint not found:**
   - Test: `curl https://your-site.com/api/dashboard/movements`
   - Should return JSON with snapshots and hourly data

3. **Category name mismatch:**
   ```sql
   -- Check category names in database
   SELECT DISTINCT LOWER(category) FROM aircraft_details;
   -- Should see: commercial, cargo, charter
   -- If Indonesian names (komersial, kargo), mapping should handle it
   ```

---

### Issue 3: Session Timeout Too Aggressive

**Symptoms:**
- Users logged out every few minutes

**Solution:**
```php
// config/app.php
'session_timeout' => 7200,  // 2 hours instead of 30 minutes
```

---

### Issue 4: Database Connection Failed

**Symptoms:**
- Error: "SQLSTATE[HY000] [1045] Access denied"

**Solutions:**

1. **Check credentials:**
   ```bash
   # Test MySQL connection
   mysql -h localhost -u amc_app -p
   # Enter password from .env
   ```

2. **Verify database exists:**
   ```sql
   SHOW DATABASES LIKE 'amc';
   ```

3. **Check user privileges:**
   ```sql
   SHOW GRANTS FOR 'amc_app'@'localhost';
   ```

---

### Issue 5: File Upload/Cache Write Errors

**Symptoms:**
- Error: "Failed to write to cache"
- Error: "Permission denied"

**Solutions:**
```bash
# Check directory ownership
ls -ld storage/cache logs

# Fix ownership (Linux)
chown -R www-data:www-data storage logs

# Fix permissions
chmod 775 storage/cache logs
```

---

### Issue 6: High CPU Usage on HelioHost

**Symptoms:**
- Account suspended for high load
- Error: "Your account has been suspended due to high server load"

**Causes:**
- ML predictions running too frequently
- Missing cache (hitting database too much)
- Infinite loops

**Solutions:**

1. **Optimize ML calls:**
   ```php
   // Add request throttling
   if (time() - $_SESSION['last_ml_call'] < 5) {
       return ['error' => 'Please wait before requesting again'];
   }
   $_SESSION['last_ml_call'] = time();
   ```

2. **Verify cache is working:**
   ```bash
   ls -lh storage/cache/historical_preferences.json
   # Should exist and be recent (< 24 hours old)
   ```

3. **Reduce polling frequency:**
   ```javascript
   // assets/js/dashboard.js
   setInterval(refreshDashboardMetrics, 60000); // 60s instead of 30s
   ```

---

### Issue 7: HTTPS Certificate Issues

**Symptoms:**
- Browser shows "Not Secure"
- Certificate error

**Solutions:**

1. **HelioHost:** Let's Encrypt SSL is automatic
   - Wait 24-48 hours after signup
   - Force HTTPS in `.htaccess`

2. **Self-hosted:**
   ```bash
   # Install certbot
   sudo apt-get install certbot python3-certbot-apache

   # Get certificate
   sudo certbot --apache -d yourdomain.com
   ```

---

## Risk Assessment

### High Risk (Must Address Before Production)

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **Weak database credentials** | CRITICAL | High | Use strong passwords, limited user |
| **Debug mode enabled** | HIGH | Medium | Set APP_DEBUG=false |
| **SQL injection** | HIGH | Low | Implement field whitelist |
| **Pickle tampering** | HIGH | Low | Add integrity checks |
| **HTTPS not enforced** | HIGH | Medium | Force HTTPS in .htaccess |

### Medium Risk (Should Address Soon)

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **XSS via innerHTML** | MEDIUM | Low | Use textContent/createElement |
| **No rate limiting** | MEDIUM | Medium | Implement request throttling |
| **File permission errors** | MEDIUM | High | Pre-create directories with correct permissions |
| **Cache not pre-warmed** | MEDIUM | Medium | Schedule precompute_preferences.php |

### Low Risk (Monitor)

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **Disk space exhaustion** | LOW | Low | Monitor logs/cache size |
| **ML model drift** | LOW | Medium | Track accuracy in ml_prediction_log |
| **Session hijacking** | LOW | Low | Use secure cookies + HTTPS |

---

## Deployment Timeline

### Phase 1: Pre-Production (1-2 weeks)

**Week 1:**
- [ ] Fix all CRITICAL security issues
- [ ] Create `.env` configuration
- [ ] Test on local environment
- [ ] Create `requirements.txt`
- [ ] Document all environment variables

**Week 2:**
- [ ] Choose hosting provider
- [ ] Sign up and configure
- [ ] Upload files
- [ ] Configure database
- [ ] Test basic functionality

### Phase 2: Initial Deployment (3-5 days)

**Day 1-2:**
- [ ] Deploy to production
- [ ] Configure web server
- [ ] Set up database
- [ ] Verify ML predictions work

**Day 3-5:**
- [ ] Performance testing
- [ ] Security hardening
- [ ] Set up monitoring
- [ ] Train users

### Phase 3: Stabilization (1-2 weeks)

**Week 1:**
- [ ] Monitor errors daily
- [ ] Fix issues as they arise
- [ ] Optimize slow queries
- [ ] Adjust cache settings

**Week 2:**
- [ ] Set up automated backups
- [ ] Configure scheduled tasks
- [ ] Document operational procedures
- [ ] Prepare maintenance plan

---

## Cost Estimate (If Not Free)

### Free Tier Limits

| Scenario | Provider | Monthly Cost | Limitations |
|----------|----------|--------------|-------------|
| **Best Case** | HelioHost | $0 | CPU limits, sign-up windows |
| **Hybrid** | InfinityFree + Render | $0 | Split architecture required |
| **Easiest** | Railway | $5 | Pay after trial, resource limits |
| **Full Control** | DigitalOcean ($5 droplet) | $5 | Requires server management |

### If Scaling Beyond Free Tier

| Resource | Cost/Month | When Needed |
|----------|------------|-------------|
| **VPS (1GB RAM)** | $5 | > 10,000 predictions/month |
| **VPS (2GB RAM)** | $12 | > 50,000 predictions/month |
| **Managed DB** | $15 | Need guaranteed uptime |
| **Domain (.com)** | $12/year | Professional presence |
| **SSL Certificate** | $0 | Let's Encrypt (free) |

---

## Support & Resources

### Official Documentation
- **PHP Manual:** https://www.php.net/manual/en/
- **Python Docs:** https://docs.python.org/3/
- **MySQL Docs:** https://dev.mysql.com/doc/
- **Tailwind CSS:** https://tailwindcss.com/docs

### Community Help
- **Stack Overflow:** https://stackoverflow.com/
- **HelioHost Forums:** https://heliohost.org/forum/
- **Railway Discord:** https://discord.gg/railway

### Security Resources
- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
- **PHP Security Guide:** https://phpsecurity.readthedocs.io/
- **MySQL Security:** https://dev.mysql.com/doc/refman/8.0/en/security.html

---

## Conclusion

This system **requires security fixes before production deployment**. The core functionality works well, but several critical issues must be addressed:

### Must Fix (Before ANY Public Access):
1. Database credentials (no defaults)
2. Debug mode disabled
3. HTTPS enforced
4. SQL injection protection
5. Pickle integrity checks

### Recommended Hosting: HelioHost
- ✅ Supports all technologies (PHP, Python, MySQL)
- ✅ Free forever
- ✅ SSH access for Python setup
- ⚠️ Limited CPU (optimize ML calls)

### Estimated Timeline:
- **Security fixes:** 2-3 days
- **Testing:** 3-5 days
- **Deployment:** 1-2 days
- **Stabilization:** 1 week
- **Total:** 2-3 weeks to production-ready

### Next Steps:
1. Apply security fixes from this document
2. Create `.env` configuration
3. Test thoroughly on local environment
4. Sign up for HelioHost (or alternative)
5. Deploy and monitor closely

---

**Document Version:** 1.0
**Last Updated:** 2025-11-23
**Prepared by:** Claude Code
**Status:** ⚠️ PRE-PRODUCTION (Security fixes required)
