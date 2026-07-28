# CLAUDE CODE — AMC SETUP INSTRUCTIONS

Read this file top to bottom and execute every step in order.
Do not skip any step. Do not ask for confirmation unless a step explicitly says to.

---

## CONTEXT

You are setting up the AMC (Airport Movement Control) web application on a new Windows machine.
XAMPP has already been installed at C:\xampp and Apache + MySQL are running.
Your job is to do everything else automatically.

---

## STEP 1 — Verify XAMPP is ready

Run these and confirm both return OK:

```powershell
# Check Apache is responding
try { Invoke-WebRequest http://localhost -TimeoutSec 3 | Out-Null; Write-Host "Apache OK" } catch { Write-Host "Apache not running - user needs to start it in XAMPP Control Panel" }

# Check MySQL
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 'MySQL OK';" 2>&1
```

If Apache is not running, stop and tell the user to open XAMPP Control Panel and click Start next to Apache and MySQL, then re-run this setup.

---

## STEP 2 — Install Python (if not installed)

```powershell
python --version 2>&1
```

If Python is not found, download and install it silently:
```powershell
$installer = "$env:TEMP\python_installer.exe"
Invoke-WebRequest "https://www.python.org/ftp/python/3.11.9/python-3.11.9-amd64.exe" -OutFile $installer
Start-Process $installer -ArgumentList "/quiet InstallAllUsers=1 PrependPath=1" -Wait
Remove-Item $installer
```

Verify after install:
```powershell
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
python --version
```

---

## STEP 3 — Install Python packages

```powershell
pip install numpy pandas scikit-learn pymysql joblib
```

Verify:
```powershell
python -c "import numpy, pandas, sklearn, pymysql, joblib; print('All Python packages OK')"
```

---

## STEP 4 — Clone the repository

```powershell
if (-not (Test-Path "C:\xampp\htdocs\AMC")) {
    git clone https://github.com/SyarifAdriann/AMC.git C:\xampp\htdocs\AMC
} else {
    Write-Host "AMC folder already exists, pulling latest..."
    cd C:\xampp\htdocs\AMC
    git pull origin main
}
```

Verify the key ML files exist:
```powershell
Test-Path "C:\xampp\htdocs\AMC\ml\parking_stand_model_rf_redo.pkl"
Test-Path "C:\xampp\htdocs\AMC\ml\encoders_redo.pkl"
Test-Path "C:\xampp\htdocs\AMC\ml\predict.py"
```
All three must return True. If not, the repo clone failed — retry Step 4.

---

## STEP 5 — Create required directories

```powershell
$dirs = @(
    "C:\xampp\htdocs\AMC\storage\cache",
    "C:\xampp\htdocs\AMC\storage\logs",
    "C:\xampp\htdocs\AMC\storage\framework",
    "C:\xampp\htdocs\AMC\logs"
)
foreach ($d in $dirs) { New-Item -ItemType Directory -Force -Path $d | Out-Null }
Write-Host "Directories created OK"
```

---

## STEP 6 — Import the database

The database dump is already included in the repo at `C:\xampp\htdocs\AMC\amc.sql`.
Just create the database and import it:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS amc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root amc < C:\xampp\htdocs\AMC\amc.sql
Write-Host "Database import complete"
```

Verify tables exist:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root amc -e "SHOW TABLES;"
```
Expected output: should list tables including aircraft_movements, users, ml_prediction_log.

---

## STEP 7 — Configure Apache virtual host

Check if vhost config already has amc.local:
```powershell
Select-String -Path "C:\xampp\apache\conf\extra\httpd-vhosts.conf" -Pattern "amc.local" -Quiet
```

If not found, append the vhost config:
```powershell
$vhost = @"

<VirtualHost *:80>
    ServerName amc.local
    DocumentRoot "C:/xampp/htdocs/AMC/public"
    <Directory "C:/xampp/htdocs/AMC/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@
Add-Content -Path "C:\xampp\apache\conf\extra\httpd-vhosts.conf" -Value $vhost
Write-Host "Vhost config added"
```

Check that vhosts are included in main httpd.conf:
```powershell
Select-String -Path "C:\xampp\apache\conf\httpd.conf" -Pattern "httpd-vhosts.conf"
```
The line must NOT be commented out (no # at the start). If it is commented, fix it:
```powershell
(Get-Content "C:\xampp\apache\conf\httpd.conf") -replace '#Include conf/extra/httpd-vhosts.conf','Include conf/extra/httpd-vhosts.conf' | Set-Content "C:\xampp\apache\conf\httpd.conf"
```

Check mod_rewrite is enabled:
```powershell
Select-String -Path "C:\xampp\apache\conf\httpd.conf" -Pattern "^LoadModule rewrite_module"
```
If commented out, uncomment it:
```powershell
(Get-Content "C:\xampp\apache\conf\httpd.conf") -replace '#LoadModule rewrite_module','LoadModule rewrite_module' | Set-Content "C:\xampp\apache\conf\httpd.conf"
```

---

## STEP 8 — Add hosts entry

Check if amc.local already in hosts:
```powershell
Select-String -Path "C:\Windows\System32\drivers\etc\hosts" -Pattern "amc.local" -Quiet
```

If not found, add it (requires admin — run PowerShell as Administrator):
```powershell
Add-Content -Path "C:\Windows\System32\drivers\etc\hosts" -Value "`n127.0.0.1`tamc.local"
Write-Host "Hosts entry added"
```

---

## STEP 9 — Restart Apache

```powershell
C:\xampp\apache\bin\httpd.exe -k restart 2>&1
Start-Sleep -Seconds 3
Write-Host "Apache restarted"
```

If the restart command fails, tell the user to click Restart next to Apache in the XAMPP Control Panel.

---

## STEP 10 — Final verification

Run all checks:
```powershell
Write-Host "=== AMC SETUP VERIFICATION ==="

# 1. PHP
Write-Host "PHP:" (C:\xampp\php\php.exe --version | Select-Object -First 1)

# 2. Python packages
Write-Host "Python deps:" (python -c "import numpy, pandas, sklearn, pymysql; print('OK')" 2>&1)

# 3. ML model files
Write-Host "ML model:" (Test-Path "C:\xampp\htdocs\AMC\ml\parking_stand_model_rf_redo.pkl")
Write-Host "ML encoders:" (Test-Path "C:\xampp\htdocs\AMC\ml\encoders_redo.pkl")

# 4. Database
Write-Host "DB tables:" (C:\xampp\mysql\bin\mysql.exe -u root amc -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema='amc';" 2>&1 | Select-Object -Last 1)

# 5. ML prediction test
$testInput = '{"aircraft_type":"ATR 72","operator_airline":"BATIK AIR","category":"Komersial"}'
$result = $testInput | python C:\xampp\htdocs\AMC\ml\predict.py 2>&1
Write-Host "ML predict test:" ($result | python -c "import sys,json; d=json.load(sys.stdin); print('OK - top stand: ' + d['predictions'][0]['stand'])" 2>&1)

# 6. Web app
try {
    $r = Invoke-WebRequest http://amc.local -TimeoutSec 5
    Write-Host "Web app: OK (HTTP" $r.StatusCode ")"
} catch {
    Write-Host "Web app: FAILED - check Apache vhost config"
}

Write-Host "=== DONE ==="
Write-Host "Open http://amc.local in your browser"
Write-Host "Login: username=DOCKER  password=DOCKER"
```

---

## MINIMUM MANUAL STEPS REQUIRED FROM USER

1. Install XAMPP from https://www.apachefriends.org
2. Open XAMPP Control Panel and click Start for Apache and MySQL
3. That's it — Claude Code handles everything else above

