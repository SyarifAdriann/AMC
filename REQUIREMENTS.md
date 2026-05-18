# AMC — System Requirements & Setup Guide
# For use with Claude Code: read this file and execute all checks/installs

---

## OVERVIEW

This is a PHP + Python web application for airport movement and parking stand management.
It runs on XAMPP (Apache + PHP + MariaDB) and uses a Python ML model for stand recommendations.
There is NO Docker, NO Composer, NO Node.js required.

---

## STEP 1 — XAMPP (Apache + PHP + MariaDB)

### Required XAMPP version
- XAMPP >= 8.2 (includes PHP 8.2, Apache 2.4, MariaDB 10.4)
- Download: https://www.apachefriends.org/download.html
- Install to default path: C:\xampp (Windows) or /opt/lampp (Linux/Mac)

### Check if XAMPP is installed
```
# Windows (PowerShell)
Test-Path "C:\xampp\xampp-control.exe"
C:\xampp\php\php.exe --version

# Linux/Mac
/opt/lampp/bin/php --version
```

### Required PHP version
- PHP >= 8.2.x
- Tested on: PHP 8.2.12

### Required PHP extensions (must all be enabled in php.ini)
The following extensions are required. Verify with:
```
# Windows
C:\xampp\php\php.exe -m

# Linux/Mac
/opt/lampp/bin/php -m
```

Required extensions:
- pdo
- pdo_mysql
- mysqli
- mbstring
- json
- session
- openssl
- curl
- hash
- filter
- xml

### Enable extensions (if missing)
Edit `C:\xampp\php\php.ini` (Windows) or `/opt/lampp/etc/php.ini` (Linux/Mac).
Uncomment or add each line (remove the leading semicolon):
```
extension=pdo_mysql
extension=mysqli
extension=mbstring
extension=openssl
extension=curl
```
Then restart Apache via XAMPP Control Panel.

---

## STEP 2 — DATABASE (MariaDB / MySQL)

### Required version
- MariaDB >= 10.4.32  OR  MySQL >= 8.0
- Tested on: MariaDB 10.4.32-MariaDB (bundled with XAMPP)

### Check if DB is running
```
# Windows
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT VERSION();"

# Linux/Mac
/opt/lampp/bin/mysql -u root -e "SELECT VERSION();"
```

### Database credentials (default XAMPP)
- Host:     localhost
- Port:     3306
- Username: root
- Password: (empty string — no password by default)
- Database: amc

### Import the database
After exporting the database as a .sql dump file:
```
# Windows
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS amc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root amc < path\to\amc_dump.sql

# Linux/Mac
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS amc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/opt/lampp/bin/mysql -u root amc < /path/to/amc_dump.sql
```

### Verify tables exist after import
```
C:\xampp\mysql\bin\mysql.exe -u root amc -e "SHOW TABLES;"
```
Expected tables include: aircraft_movements, aircraft_details, users, ml_prediction_log, airline_preferences

---

## STEP 3 — APACHE VIRTUAL HOST

### Place the project
Clone or copy the AMC folder to:
- Windows: C:\xampp\htdocs\AMC
- Linux/Mac: /opt/lampp/htdocs/AMC

### Configure Apache vhost
Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf` and add:

```apache
<VirtualHost *:80>
    ServerName amc.local
    DocumentRoot "C:/xampp/htdocs/AMC/public"
    <Directory "C:/xampp/htdocs/AMC/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

For Linux/Mac, adjust paths accordingly.

### Enable mod_rewrite
In `C:\xampp\apache\conf\httpd.conf`, ensure this line is uncommented:
```
LoadModule rewrite_module modules/mod_rewrite.so
```

### Add hosts entry (Windows)
Edit `C:\Windows\System32\drivers\etc\hosts` (run as Administrator) and add:
```
127.0.0.1   amc.local
```

For Linux/Mac edit `/etc/hosts`:
```
127.0.0.1   amc.local
```

### Restart Apache
Use XAMPP Control Panel or:
```
# Windows
C:\xampp\apache\bin\httpd.exe -k restart

# Linux/Mac
sudo /opt/lampp/lampp restart
```

### Verify app loads
Open browser: http://amc.local
Login with: username=DOCKER, password=DOCKER

---

## STEP 4 — PYTHON

### Required version
- Python >= 3.11
- Tested on: Python 3.14.3
- Do NOT use Python 3.9 or below (f-string and type hint syntax used)

### Check if Python is installed
```
python --version
# or
python3 --version
```

### Install Python
Download from: https://www.python.org/downloads/
During install on Windows: CHECK "Add Python to PATH"

### Required Python packages
Install all at once:
```
pip install numpy pandas scikit-learn pymysql joblib
```

Individual packages and minimum versions:
```
numpy>=1.26.0
pandas>=2.0.0
scikit-learn>=1.4.0
pymysql>=1.1.0
joblib>=1.3.0
```

### Verify packages are installed
```
python -c "import numpy, pandas, sklearn, pymysql, joblib; print('All packages OK')"
```

### Check Python path is accessible from PHP
The web app calls Python via proc_open(). PHP needs to be able to find `python` or `python3`.
Test this:
```
# Windows (from command prompt, NOT PowerShell)
python ml/predict.py
```
If it fails, set the Python path in `config/ml.php`:
```php
// config/ml.php
'python_path' => 'C:/Python314/python.exe',   // adjust to your actual path
```
Find your Python path with:
```
# Windows
where python
# Linux/Mac
which python3
```

---

## STEP 5 — PROJECT FILES

### Clone from GitHub
```
git clone https://github.com/SyarifAdriann/AMC.git C:\xampp\htdocs\AMC
```
Or on Linux/Mac:
```
git clone https://github.com/SyarifAdriann/AMC.git /opt/lampp/htdocs/AMC
```

### Required files that are NOT in the repository
The following are excluded from git for size/security reasons.
You must obtain these separately:

1. **Database dump** — export from the source machine:
   ```
   C:\xampp\mysql\bin\mysqldump.exe -u root amc > amc_dump.sql
   ```
   Then import on the new machine (see Step 2).

2. **ML model files** (already included in repo):
   - `ml/parking_stand_model_rf_redo.pkl`
   - `ml/encoders_redo.pkl`
   These are committed to the repo and should already be present after cloning.

### Verify ML model files exist
```
# Windows
Test-Path "C:\xampp\htdocs\AMC\ml\parking_stand_model_rf_redo.pkl"
Test-Path "C:\xampp\htdocs\AMC\ml\encoders_redo.pkl"
```

### Create required directories (if missing)
```
# Windows
New-Item -ItemType Directory -Force -Path "C:\xampp\htdocs\AMC\storage\cache"
New-Item -ItemType Directory -Force -Path "C:\xampp\htdocs\AMC\storage\logs"
New-Item -ItemType Directory -Force -Path "C:\xampp\htdocs\AMC\logs"
```

---

## STEP 6 — FULL VERIFICATION CHECKLIST

Run these commands to verify the setup is complete:

```
# 1. PHP is reachable
C:\xampp\php\php.exe --version

# 2. Required PHP extensions loaded
C:\xampp\php\php.exe -m | Select-String "pdo_mysql|mbstring|openssl|json"

# 3. MariaDB is running and amc database exists
C:\xampp\mysql\bin\mysql.exe -u root amc -e "SHOW TABLES;" 2>&1

# 4. Python is reachable
python --version

# 5. Python packages installed
python -c "import numpy, pandas, sklearn, pymysql; print('Python deps OK')"

# 6. ML model test (run from project root)
echo '{"aircraft_type":"ATR 72","operator_airline":"BATIK AIR","category":"Komersial"}' | python ml/predict.py

# 7. Full pipeline test
python ml/predictbatch.py
```

Expected output of test #6: JSON with `"success": true` and 3 stand predictions.
Expected output of test #7: Table with 10 test cases, all rows showing OK.

---

## QUICK REFERENCE — DEFAULT CREDENTIALS

| Service    | Username | Password |
|------------|----------|----------|
| App login  | DOCKER   | DOCKER   |
| DB (root)  | root     | (empty)  |

---

## ENVIRONMENT SUMMARY

| Component    | Version Tested   | Notes                              |
|--------------|------------------|------------------------------------|
| XAMPP        | 8.2              | Includes Apache 2.4, PHP 8.2, MariaDB 10.4 |
| PHP          | 8.2.12           | Must be >= 8.2                     |
| MariaDB      | 10.4.32          | Or MySQL >= 8.0                    |
| Apache       | 2.4              | mod_rewrite required               |
| Python       | 3.14.3           | Must be >= 3.11                    |
| numpy        | 2.4.2            | Must be >= 1.26                    |
| pandas       | 3.0.0            | Must be >= 2.0                     |
| scikit-learn | 1.7.2            | Must be >= 1.4                     |
| pymysql      | 1.1.3            | Must be >= 1.1                     |
| joblib       | 1.5.3            | Installed with scikit-learn        |
| Git          | 2.53.0           | For cloning the repo               |
