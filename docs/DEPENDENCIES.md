# Dependencies - AMC Parking Stand Prediction System

## Table of Contents
1. [PHP Dependencies](#php-dependencies)
2. [Python Dependencies](#python-dependencies)
3. [JavaScript Dependencies](#javascript-dependencies)
4. [Database Requirements](#database-requirements)
5. [System Dependencies](#system-dependencies)
6. [Version Compatibility Matrix](#version-compatibility-matrix)

---

## PHP Dependencies

### PHP Version
**Required**: PHP 8.3.25 or later
**⚠️ DO NOT DOWNGRADE**: System uses PHP 8.3 features (typed properties, match expressions)

### Core PHP Extensions (Required)

#### 1. PDO MySQL
**Purpose**: Database connectivity
**Check**:
```bash
php -m | findstr pdo_mysql
```
**Enable in php.ini**:
```ini
extension=pdo_mysql
```

#### 2. mbstring
**Purpose**: Multibyte string support (for UTF-8 handling)
**Check**:
```bash
php -m | findstr mbstring
```
**Enable in php.ini**:
```ini
extension=mbstring
```

#### 3. JSON
**Purpose**: JSON encoding/decoding (usually enabled by default)
**Check**:
```bash
php -m | findstr json
```

#### 4. Session
**Purpose**: User session management (usually enabled by default)
**Check**:
```bash
php -m | findstr session
```

#### 5. OpenSSL
**Purpose**: Password hashing (bcrypt)
**Check**:
```bash
php -m | findstr openssl
```
**Enable in php.ini**:
```ini
extension=openssl
```

### PHP Configuration (php.ini)

**Required Settings**:
```ini
; Memory limit (minimum 128M, recommended 256M)
memory_limit = 256M

; Maximum execution time (minimum 30s, recommended 60s)
max_execution_time = 60

; Maximum input time (for large forms)
max_input_time = 60

; Upload limits (if file uploads needed)
upload_max_filesize = 10M
post_max_size = 10M

; Session settings
session.gc_maxlifetime = 1800  ; 30 minutes
session.cookie_httponly = 1

; Error reporting (development)
display_errors = On
error_reporting = E_ALL

; Error reporting (production)
; display_errors = Off
; error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

### PHP Composer Dependencies (Future Use)
**Currently NOT using Composer**, but for future reference:

**composer.json** (hypothetical):
```json
{
  "require": {
    "php": ">=8.3.0",
    "ext-pdo": "*",
    "ext-mbstring": "*",
    "ext-json": "*"
  }
}
```

---

## Python Dependencies

### Python Version
**Required**: Python 3.13.5 or later
**⚠️ CRITICAL**: Python must be in system PATH

**Verify**:
```bash
python --version
```

### Required Python Packages

#### 1. NumPy
**Version**: >= 1.26.0
**Purpose**: Numerical computing for ML model
**Install**:
```bash
pip install numpy
```

**Check**:
```bash
python -c "import numpy; print(numpy.__version__)"
```

#### 2. Pandas
**Version**: >= 2.1.0
**Purpose**: Data manipulation for ML training
**Install**:
```bash
pip install pandas
```

**Check**:
```bash
python -c "import pandas; print(pandas.__version__)"
```

#### 3. scikit-learn
**Version**: >= 1.3.0
**Purpose**: Machine learning model (Random Forest)
**Install**:
```bash
pip install scikit-learn
```

**Check**:
```bash
python -c "import sklearn; print(sklearn.__version__)"
```

### Python Requirements File

**requirements.txt**:
```txt
numpy>=1.26.0
pandas>=2.1.0
scikit-learn>=1.3.0
```

**Install All**:
```bash
pip install -r requirements.txt
```

### Python Package Compatibility

**⚠️ IMPORTANT**: Specific version combinations tested:
- Python 3.13.5 + NumPy 1.26.2 + Pandas 2.1.4 + scikit-learn 1.3.2 = ✅ Works
- Python 3.12.x + (any compatible versions) = ⚠️ Not tested
- Python 3.11.x + (any compatible versions) = ⚠️ Not tested

**⚠️ DO NOT MIX**: scikit-learn 1.3.x requires NumPy >= 1.21.0

---

## JavaScript Dependencies

### Frontend Libraries (No Build System)
**Currently**: Vanilla JavaScript (no frameworks)
**No npm packages** for runtime (plain JS in browser)

### Development Dependencies (Tailwind CSS)

**package.json**:
```json
{
  "name": "amc-system",
  "version": "1.0.0",
  "devDependencies": {
    "tailwindcss": "^3.3.0"
  },
  "scripts": {
    "build:css": "npx tailwindcss -i ./resources/css/tailwind.css -o ./public/css/output.css --minify"
  }
}
```

**Install**:
```bash
npm install
```

**Build CSS**:
```bash
npm run build:css
```

**⚠️ NOTE**: Tailwind CSS is **development dependency only**. Production uses compiled `public/css/output.css` (no Node.js runtime required).

### Browser Compatibility
**Tested On**:
- Chrome 120+
- Firefox 120+
- Edge 120+

**Required Features**:
- ES6 (Arrow functions, Promises, Fetch API)
- DOM Level 3
- CSS Grid (for Tailwind)

---

## Database Requirements

### Database Management System
**Required**: MariaDB 10.4.32 or MySQL 8.0+
**⚠️ CRITICAL**: Must support InnoDB engine

**Verify**:
```sql
SELECT VERSION();
SHOW ENGINES;
```

### Database Configuration

**my.ini/my.cnf**:
```ini
[mysqld]
# Character set
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci

# InnoDB settings
default-storage-engine=INNODB
innodb_buffer_pool_size=256M  ; Adjust based on RAM
innodb_log_file_size=64M

# Query cache (if enabled)
query_cache_type=1
query_cache_size=32M

# Max connections
max_connections=100

# Slow query log (for debugging)
slow_query_log=1
long_query_time=1  ; Log queries > 1 second
```

### Database User Permissions
**Production User**:
```sql
CREATE USER 'amc_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON amc.* TO 'amc_user'@'localhost';
FLUSH PRIVILEGES;
```

**⚠️ DO NOT GRANT**:
- DROP (prevent accidental table deletion)
- SUPER (not needed)
- FILE (not needed)

---

## System Dependencies

### Operating System
**Primary**: Windows 10/11 or Windows Server 2019+
**Secondary**: Linux (Debian/Ubuntu) - not officially tested

**File System**:
- NTFS (Windows)
- ext4 or XFS (Linux)

### Web Server
**Required**: Apache 2.4+ with mod_rewrite

**Apache Modules Required**:
```bash
# Check enabled modules
apache2ctl -M  # Linux
httpd -M       # Windows

# Required modules:
- mod_rewrite  (for URL routing)
- mod_php      (for PHP execution)
- mod_dir      (for DirectoryIndex)
```

**httpd.conf** (or apache2.conf):
```apache
# Enable mod_rewrite
LoadModule rewrite_module modules/mod_rewrite.so

# Allow .htaccess overrides
<Directory "C:/xampp/htdocs/amc">
    AllowOverride All
    Require all granted
</Directory>
```

**.htaccess** (in project root):
```apache
# Enable rewrite engine
RewriteEngine On

# Redirect to public/ directory (if not already there)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### System Tools (Optional but Recommended)

#### Git
**Version**: 2.40+
**Purpose**: Version control
**Install**: https://git-scm.com/downloads

#### Composer (Future Use)
**Version**: 2.5+
**Purpose**: PHP package manager
**Install**: https://getcomposer.org/download/

#### Node.js (Development Only)
**Version**: 18.x LTS
**Purpose**: Tailwind CSS compilation
**Install**: https://nodejs.org/

---

## Version Compatibility Matrix

### Tested Combinations (✅ Works)

| PHP       | Python    | MariaDB   | Apache  | OS           |
|-----------|-----------|-----------|---------|--------------|
| 8.3.25    | 3.13.5    | 10.4.32   | 2.4.58  | Windows 11   |
| 8.2.12    | 3.13.5    | 10.4.32   | 2.4.58  | Windows 10   |

### Compatible Versions (⚠️ Not Tested)

| PHP       | Python    | MariaDB   | MySQL    | Apache  |
|-----------|-----------|-----------|----------|---------|
| 8.3.x     | 3.13.x    | 10.4.x    | 8.0.x    | 2.4.x   |
| 8.2.x     | 3.12.x    | 10.5.x    | 8.1.x    | 2.4.x   |

### Incompatible Versions (❌ Will Not Work)

| Component | Version   | Reason                          |
|-----------|-----------|---------------------------------|
| PHP       | < 8.0     | Uses PHP 8.0+ features          |
| Python    | < 3.9     | scikit-learn requires 3.9+      |
| MariaDB   | < 10.2    | Missing utf8mb4 full support    |
| MySQL     | < 5.7     | Missing JSON functions          |

---

## Critical Version Notes

### ⚠️ PHP Version
**MUST BE 8.3.25 or later** for:
- Typed class properties (`protected string $table`)
- Constructor property promotion
- Match expressions
- Union types

**Downgrading to PHP 7.x will break the system.**

### ⚠️ Python Version
**MUST BE 3.9 or later** for:
- scikit-learn 1.3.0 compatibility
- Type hints support
- Match-case statements (if used)

**Python 2.x is NOT supported and will not work.**

### ⚠️ Database Version
**MUST BE MariaDB 10.2+ or MySQL 5.7+** for:
- Full utf8mb4 support (4-byte UTF-8)
- JSON data type support
- Better index support

**Older versions may have character encoding issues.**

---

## Dependency Update Strategy

### When to Update

**Security Updates**: Apply immediately
- PHP security patches
- Python security patches
- Database security patches

**Minor Updates**: Test before applying
- PHP 8.3.25 → 8.3.26 (patch version)
- Python 3.13.5 → 3.13.6 (patch version)
- NumPy 1.26.2 → 1.26.3 (patch version)

**Major Updates**: Test extensively before applying
- PHP 8.3.x → 8.4.x (minor version)
- Python 3.13.x → 3.14.x (minor version)
- scikit-learn 1.3.x → 1.4.x (minor version)

### Update Testing Checklist
- [ ] Backup database
- [ ] Backup model files
- [ ] Test on staging environment
- [ ] Test ML predictions
- [ ] Test CRUD operations
- [ ] Test authentication
- [ ] Run regression tests
- [ ] Monitor error logs for 24 hours
- [ ] Roll back if issues found

---

## Dependency Installation Summary

**Quick Install (Windows XAMPP)**:
```bash
# 1. Install XAMPP (includes PHP 8.3.25, MariaDB 10.4.32, Apache 2.4.58)
# Download from https://www.apachefriends.org/

# 2. Install Python 3.13.5
# Download from https://www.python.org/downloads/
# ✅ Check "Add Python to PATH"

# 3. Install Python packages
pip install numpy pandas scikit-learn

# 4. Verify installations
php --version
python --version
mysql --version

# 5. Test ML prediction
echo {"aircraft_type":"B 738","operator_airline":"GARUDA","category":"COMMERCIAL"} | python ml\predict.py --top_k 3
```

**Total Installation Time**: ~15 minutes

---

## Troubleshooting Dependencies

### "PHP version mismatch"
**Fix**: Upgrade PHP to 8.3.25 or later

### "Python module not found"
**Fix**:
```bash
pip install numpy pandas scikit-learn --upgrade
```

### "Apache module not loaded"
**Fix**: Enable mod_rewrite in httpd.conf

### "Database connection charset error"
**Fix**: Set `character-set-server=utf8mb4` in my.ini

---

## Summary

**Total Dependencies**: 15
- **PHP**: 1 version + 5 extensions
- **Python**: 1 version + 3 packages
- **JavaScript**: 0 runtime dependencies (Tailwind CSS is dev-only)
- **Database**: 1 DBMS
- **Web Server**: 1 server + 1 module
- **OS**: 1 OS type

**Critical Dependencies** (System will NOT work without):
1. PHP 8.3.25+
2. Python 3.13.5+
3. NumPy, Pandas, scikit-learn
4. MariaDB 10.4.32+ or MySQL 8.0+
5. Apache 2.4+ with mod_rewrite

**Optional Dependencies** (For development):
1. Node.js 18+ (Tailwind CSS compilation)
2. Git 2.40+ (Version control)
3. Composer 2.5+ (Future use)

**⚠️ DO NOT CHANGE** (Without extensive testing):
- PHP version (breaking changes between major versions)
- Python version (package compatibility issues)
- Database version (schema compatibility issues)
