# =============================================================================
# AMC database backup (XAMPP / Windows)
#
# Dumps the `amc` database to backups\amc_<timestamp>.sql and deletes dumps
# older than 14 days.
#
# Run manually:
#   powershell -ExecutionPolicy Bypass -File scripts\backup-db.ps1
#
# Schedule daily at 02:00 (run once from an elevated PowerShell):
#   schtasks /Create /TN "AMC DB Backup" /SC DAILY /ST 02:00 `
#     /TR "powershell -ExecutionPolicy Bypass -File c:\xampp\htdocs\AMC\scripts\backup-db.ps1"
# =============================================================================

$ErrorActionPreference = 'Stop'

$mysqldump = 'c:\xampp\mysql\bin\mysqldump.exe'
$database  = 'amc'
$user      = 'root'
$password  = $env:AMC_DB_PASSWORD      # empty for default XAMPP root
$backupDir = Join-Path (Split-Path $PSScriptRoot -Parent) 'backups'
$keepDays  = 14

if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
}

$stamp   = Get-Date -Format 'yyyy-MM-dd_HHmm'
$outFile = Join-Path $backupDir "amc_$stamp.sql"

$args = @('-u', $user, '--single-transaction', '--routines', $database)
if ($password) { $args = @("-p$password") + $args }

& $mysqldump @args | Out-File -FilePath $outFile -Encoding utf8

if ((Get-Item $outFile).Length -lt 1024) {
    Write-Error "Backup file suspiciously small — dump probably failed: $outFile"
}

# Retention: delete dumps older than $keepDays days
Get-ChildItem $backupDir -Filter 'amc_*.sql' |
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$keepDays) } |
    Remove-Item -Force

Write-Output "Backup complete: $outFile"
