# Complete Oracle Cloud Deployment Guide for AMC Application

**Aircraft Movement Control (AMC) - Production Deployment on Oracle Cloud Always Free Tier**

Last Updated: 2025-11-27
Estimated Setup Time: 2-3 hours
Difficulty: Intermediate

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Part 1: Oracle Cloud Account Setup](#part-1-oracle-cloud-account-setup)
4. [Part 2: VM Instance Creation](#part-2-vm-instance-creation)
5. [Part 3: Initial Server Configuration](#part-3-initial-server-configuration)
6. [Part 4: LAMP Stack Installation](#part-4-lamp-stack-installation)
7. [Part 5: Python and ML Dependencies](#part-5-python-and-ml-dependencies)
8. [Part 6: Application Deployment](#part-6-application-deployment)
9. [Part 7: Database Setup](#part-7-database-setup)
10. [Part 8: Security Hardening](#part-8-security-hardening)
11. [Part 9: SSL Certificate Setup](#part-9-ssl-certificate-setup)
12. [Part 10: Monitoring Setup](#part-10-monitoring-setup)
13. [Part 11: Backup Strategy](#part-11-backup-strategy)
14. [Part 12: Maintenance Procedures](#part-12-maintenance-procedures)
15. [Part 13: Troubleshooting](#part-13-troubleshooting)
16. [Part 14: Scaling and Optimization](#part-14-scaling-and-optimization)

---

## Overview

### What You'll Deploy

- **Application:** AMC (Aircraft Movement Control)
- **Stack:** PHP 8.0+ | MySQL 8.0+ | Python 3.7+ | Apache 2.4
- **Features:** Web dashboard, ML predictions, user authentication, audit logging
- **Resources:** 2 vCPUs, 1-12GB RAM, 100-200GB storage
- **Cost:** **$0/month (Always Free)**

### Oracle Cloud Always Free Resources

| Resource | Allocation | AMC Usage |
|----------|------------|-----------|
| **VM Instances** | 2x AMD VMs (1/8 OCPU, 1GB RAM each) OR 4x ARM VMs (1 OCPU, 6GB RAM each) | 1x ARM VM (4 OCPU, 24GB RAM) |
| **Block Storage** | 200GB total | 50GB (OS + App + DB + Backups) |
| **Bandwidth** | 10TB/month | < 100GB/month expected |
| **Load Balancer** | 1 instance, 10Mbps | Optional |
| **Public IP** | 2 reserved IPs | 1 IP for web access |
| **Database** | Not included in free tier | Self-hosted MySQL |

**Recommendation:** Use **1x ARM-based Ampere A1 instance** (4 OCPU, 24GB RAM) - best performance for free.

---

## Prerequisites

### Required Items

- [ ] Credit/debit card (for Oracle Cloud verification - **no charges on Always Free**)
- [ ] Valid email address
- [ ] Phone number for verification
- [ ] Domain name (optional, for SSL - can use DuckDNS for free)
- [ ] SSH client (Terminal on Mac/Linux, PuTTY on Windows)
- [ ] Basic Linux command line knowledge

### Your Local Machine Needs

- Git installed
- SSH key pair (we'll generate if needed)
- Text editor for configuration files

---

## Part 1: Oracle Cloud Account Setup

### Step 1.1: Create Oracle Cloud Account

1. **Go to Oracle Cloud Free Tier:**
   - Visit: https://www.oracle.com/cloud/free/
   - Click **"Start for free"**

2. **Fill Registration Form:**
   ```
   Account Type: Individual (unless you have a company)
   Country: [Your country]
   Email: [Your email]
   First/Last Name: [Your details]
   ```

3. **Verify Email:**
   - Check your email for verification link
   - Click the link to verify

4. **Complete Profile:**
   ```
   Cloud Account Name: Choose unique name (e.g., amc-prod-2025)
   Home Region: Choose closest region:
     - US: us-ashburn-1, us-phoenix-1
     - Europe: eu-frankfurt-1, uk-london-1
     - Asia: ap-singapore-1, ap-tokyo-1, ap-mumbai-1
   ```

   **IMPORTANT:** You **cannot change your home region** after selection!

5. **Add Payment Verification:**
   - Enter credit/debit card details
   - Oracle charges $1 for verification (refunded within 7 days)
   - Your card will **NOT be charged** for Always Free resources
   - You'll only be billed if you manually upgrade to paid services

6. **Wait for Account Provisioning:**
   - Takes 5-15 minutes
   - You'll receive email when ready

### Step 1.2: First Login

1. **Access Cloud Console:**
   - Go to: https://cloud.oracle.com/
   - Click **"Sign in to Cloud"**
   - Enter your Cloud Account Name
   - Use email and password to log in

2. **Navigate Dashboard:**
   - You'll see the main console
   - Click **"Create a VM instance"** or go to **Compute > Instances**

---

## Part 2: VM Instance Creation

### Step 2.1: Launch Instance Wizard

1. **Navigate to Compute:**
   ```
   Menu (☰) > Compute > Instances > Create Instance
   ```

2. **Basic Configuration:**
   ```
   Name: amc-production
   Compartment: (root) - default is fine
   Availability Domain: Select any (AD-1, AD-2, or AD-3)
   ```

### Step 2.2: Image and Shape Selection

**CRITICAL: Always Free Configuration**

1. **Click "Change Image":**
   ```
   Image: Oracle Linux 8 (or Ubuntu 22.04 LTS)
   Image build: Latest (e.g., 2024.11.28)
   ```

   **Recommended:** Ubuntu 22.04 LTS (easier for LAMP stack)

2. **Click "Change Shape":**
   ```
   Instance Type: Virtual Machine
   Shape Series: Ampere (ARM-based)
   Shape Name: VM.Standard.A1.Flex

   OCPU: 4 (maximum for free tier across all VMs)
   Memory: 24 GB (maximum for free tier)
   Network Bandwidth: 4 Gbps
   ```

   **Alternative (if ARM unavailable):**
   ```
   Shape Series: AMD
   Shape Name: VM.Standard.E2.1.Micro
   OCPU: 1/8
   Memory: 1 GB
   ```

   ⚠️ **Note:** ARM A1 instances may show "Out of capacity" in some regions. Try different availability domains or regions if needed.

### Step 2.3: Network Configuration

1. **Primary Network:**
   ```
   Virtual Cloud Network: Create new VCN
   VCN Name: amc-vcn
   Subnet: Create new public subnet
   Subnet Name: amc-public-subnet
   ```

2. **Public IP:**
   ```
   ✅ Assign a public IPv4 address
   ```

### Step 2.4: SSH Key Setup

**Option A: Generate New Key Pair (Recommended for beginners)**

1. Click **"Generate a key pair for me"**
2. Click **"Save Private Key"** - Downloads `ssh-key-YYYY-MM-DD.key`
3. Click **"Save Public Key"** - Downloads `ssh-key-YYYY-MM-DD.key.pub`
4. **IMPORTANT:** Save these files securely - you cannot recover them later!

**Option B: Use Existing SSH Key**

If you already have an SSH key:
```bash
# On your local machine (Linux/Mac)
cat ~/.ssh/id_rsa.pub

# On Windows (PowerShell)
type $env:USERPROFILE\.ssh\id_rsa.pub
```

Paste the public key content into the box.

**Option C: Generate Key Manually**

```bash
# Linux/Mac
ssh-keygen -t rsa -b 4096 -f ~/.ssh/oracle_amc_key
cat ~/.ssh/oracle_amc_key.pub  # Copy this content

# Windows (Git Bash or WSL)
ssh-keygen -t rsa -b 4096 -f /c/Users/YourName/.ssh/oracle_amc_key
cat /c/Users/YourName/.ssh/oracle_amc_key.pub
```

### Step 2.5: Boot Volume

```
Boot Volume: Default (50GB) - FREE
Custom Boot Volume Size: 100GB - STILL FREE (up to 200GB total)
```

**Recommended:** 100GB for AMC (OS + DB + ML models + logs + backups)

### Step 2.6: Review and Create

1. **Review Summary:**
   - Shape: VM.Standard.A1.Flex (4 OCPU, 24GB)
   - Image: Ubuntu 22.04
   - Boot Volume: 100GB
   - Public IP: Yes
   - Always Free Eligible: **Should show "Yes"**

2. **Click "Create"**

3. **Wait for Provisioning:**
   - Status: PROVISIONING → RUNNING (2-5 minutes)
   - Note down the **Public IP Address** (e.g., 130.61.45.123)

### Step 2.7: Configure Firewall Rules

**Oracle Cloud uses Security Lists (separate from VM firewall)**

1. **Navigate to Security List:**
   ```
   Menu > Networking > Virtual Cloud Networks
   Click your VCN (amc-vcn)
   Click "Security Lists" > "Default Security List"
   Click "Add Ingress Rules"
   ```

2. **Add HTTP Rule:**
   ```
   Stateless: No
   Source CIDR: 0.0.0.0/0
   IP Protocol: TCP
   Source Port Range: All
   Destination Port Range: 80
   Description: HTTP traffic
   ```

3. **Add HTTPS Rule:**
   ```
   Source CIDR: 0.0.0.0/0
   IP Protocol: TCP
   Destination Port Range: 443
   Description: HTTPS traffic
   ```

4. **Add SSH Rule (if not already present):**
   ```
   Source CIDR: 0.0.0.0/0  (or your IP for security)
   IP Protocol: TCP
   Destination Port Range: 22
   Description: SSH access
   ```

5. **Click "Add Ingress Rules"**

---

## Part 3: Initial Server Configuration

### Step 3.1: Connect via SSH

**Set Key Permissions (Linux/Mac only):**
```bash
chmod 600 ~/Downloads/ssh-key-*.key
```

**Connect to VM:**
```bash
# Linux/Mac
ssh -i ~/Downloads/ssh-key-2025-11-27.key ubuntu@130.61.45.123

# Windows (PowerShell with OpenSSH)
ssh -i C:\Users\YourName\Downloads\ssh-key-2025-11-27.key ubuntu@130.61.45.123

# Windows (PuTTY)
# 1. Convert .key to .ppk using PuTTYgen
# 2. Use PuTTY with Host: ubuntu@130.61.45.123, Auth: your .ppk file
```

**First Time Connection:**
```
The authenticity of host '130.61.45.123' can't be established.
ECDSA key fingerprint is SHA256:...
Are you sure you want to continue connecting (yes/no)? yes
```

Type `yes` and press Enter.

### Step 3.2: Update System

```bash
# Update package lists
sudo apt update

# Upgrade installed packages
sudo apt upgrade -y

# Install essential tools
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates
```

This may take 5-10 minutes.

### Step 3.3: Configure Firewall (Ubuntu UFW)

```bash
# Enable UFW firewall
sudo ufw --force enable

# Allow SSH (CRITICAL - do this first!)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Check status
sudo ufw status verbose
```

Expected output:
```
Status: active

To                         Action      From
--                         ------      ----
22/tcp                     ALLOW       Anywhere
80/tcp                     ALLOW       Anywhere
443/tcp                    ALLOW       Anywhere
```

### Step 3.4: Create Swap Space

Since we're running database + ML, let's add swap:

```bash
# Create 2GB swap file
sudo fallocate -l 2G /swapfile

# Set permissions
sudo chmod 600 /swapfile

# Make swap
sudo mkswap /swapfile

# Enable swap
sudo swapon /swapfile

# Make permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Verify
free -h
```

### Step 3.5: Set Timezone

```bash
# List available timezones
timedatectl list-timezones | grep -i YOUR_REGION

# Set timezone (example: Asia/Kuala_Lumpur)
sudo timedatectl set-timezone Asia/Kuala_Lumpur

# Verify
timedatectl
```

---

## Part 4: LAMP Stack Installation

### Step 4.1: Install Apache Web Server

```bash
# Install Apache
sudo apt install -y apache2

# Start and enable Apache
sudo systemctl start apache2
sudo systemctl enable apache2

# Check status
sudo systemctl status apache2
```

**Test:** Open browser and visit `http://YOUR_PUBLIC_IP` - you should see Apache default page.

### Step 4.2: Install MySQL 8.0

```bash
# Install MySQL Server
sudo apt install -y mysql-server

# Start and enable MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL installation
sudo mysql_secure_installation
```

**MySQL Secure Installation Prompts:**
```
VALIDATE PASSWORD COMPONENT? Y
Password validation policy: 2 (STRONG)
Set root password: [Choose a strong password - save in password manager!]
Remove anonymous users? Y
Disallow root login remotely? Y
Remove test database? Y
Reload privilege tables? Y
```

**Create dedicated MySQL user:**
```bash
sudo mysql -u root -p
```

In MySQL prompt:
```sql
-- Create database
CREATE DATABASE amc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with strong password
CREATE USER 'amc_user'@'localhost' IDENTIFIED BY 'your_strong_password_here_123!@#';

-- Grant privileges
GRANT ALL PRIVILEGES ON amc.* TO 'amc_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify
SHOW DATABASES;
SELECT user, host FROM mysql.user WHERE user = 'amc_user';

-- Exit
EXIT;
```

**Save these credentials:**
```
Database: amc
Username: amc_user
Password: your_strong_password_here_123!@#
Host: localhost
```

### Step 4.3: Install PHP 8.0+

```bash
# Add PHP repository
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Install PHP 8.2 and required extensions
sudo apt install -y php8.2 php8.2-cli php8.2-common php8.2-mysql \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml \
    php8.2-bcmath php8.2-json libapache2-mod-php8.2

# Verify PHP version
php -v
```

Expected output: `PHP 8.2.x`

### Step 4.4: Configure PHP

```bash
# Edit PHP configuration for Apache
sudo nano /etc/php/8.2/apache2/php.ini
```

**Find and modify these values** (use Ctrl+W to search):
```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
max_input_time = 300
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/error.log
date.timezone = Asia/Kuala_Lumpur
```

Save: `Ctrl+O`, Enter, `Ctrl+X`

**Create PHP log directory:**
```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

**Restart Apache:**
```bash
sudo systemctl restart apache2
```

**Test PHP:**
```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

Visit: `http://YOUR_PUBLIC_IP/info.php` - should show PHP info page.

**Remove test file (security):**
```bash
sudo rm /var/www/html/info.php
```

---

## Part 5: Python and ML Dependencies

### Step 5.1: Install Python 3

```bash
# Ubuntu 22.04 comes with Python 3.10
python3 --version

# Install pip and venv
sudo apt install -y python3-pip python3-venv python3-dev

# Verify pip
pip3 --version
```

### Step 5.2: Install ML Libraries System-Wide

**Option A: System-wide (easier for Apache to access)**

```bash
# Install NumPy, Pandas, Scikit-learn
sudo pip3 install numpy pandas scikit-learn

# Verify installations
python3 -c "import numpy; print('NumPy:', numpy.__version__)"
python3 -c "import pandas; print('Pandas:', pandas.__version__)"
python3 -c "import sklearn; print('Scikit-learn:', sklearn.__version__)"
```

**Option B: Virtual Environment (more isolated)**

```bash
# Create venv for AMC
sudo mkdir -p /opt/amc-ml
sudo python3 -m venv /opt/amc-ml/venv

# Activate and install
sudo /opt/amc-ml/venv/bin/pip install numpy pandas scikit-learn

# Make accessible to www-data
sudo chown -R www-data:www-data /opt/amc-ml
```

If using venv, you'll need to modify Python scripts to use:
`/opt/amc-ml/venv/bin/python3` instead of `python3`

**Recommended:** Use **Option A (system-wide)** for simplicity.

### Step 5.3: Install Node.js (for CSS build tools)

```bash
# Install Node.js 20.x LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify
node --version
npm --version
```

---

## Part 6: Application Deployment

### Step 6.1: Create Application Directory

```bash
# Create application root
sudo mkdir -p /var/www/amc
sudo chown -R $USER:$USER /var/www/amc

# Navigate
cd /var/www/amc
```

### Step 6.2: Clone Repository

**Option A: From GitHub (if you push your code)**

```bash
# Clone your repository
git clone https://github.com/YOUR_USERNAME/AMC.git .

# Or if already pushed to your branch
git clone -b claude/check-flyio-deployment-01NhATPPoJgsUeoDbaj6GrLc https://github.com/YOUR_USERNAME/AMC.git .
```

**Option B: Upload Files Manually (if no GitHub)**

On your local machine:
```bash
# Create tar archive
cd /path/to/your/AMC
tar -czf amc-app.tar.gz --exclude='.git' --exclude='node_modules' .

# Upload to server (from local machine)
scp -i ~/Downloads/ssh-key-*.key amc-app.tar.gz ubuntu@YOUR_PUBLIC_IP:/home/ubuntu/
```

On the server:
```bash
# Extract to web directory
cd /var/www/amc
tar -xzf /home/ubuntu/amc-app.tar.gz
rm /home/ubuntu/amc-app.tar.gz
```

### Step 6.3: Set Directory Permissions

```bash
cd /var/www/amc

# Set ownership to www-data (Apache user)
sudo chown -R www-data:www-data /var/www/amc

# Set directory permissions
sudo find /var/www/amc -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/amc -type f -exec chmod 644 {} \;

# Make Python scripts executable
sudo chmod +x /var/www/amc/ml/*.py

# Create writable directories for cache, logs, reports
sudo mkdir -p /var/www/amc/storage/cache
sudo mkdir -p /var/www/amc/logs
sudo mkdir -p /var/www/amc/reports

sudo chown -R www-data:www-data /var/www/amc/storage
sudo chown -R www-data:www-data /var/www/amc/logs
sudo chown -R www-data:www-data /var/www/amc/reports

sudo chmod -R 775 /var/www/amc/storage
sudo chmod -R 775 /var/www/amc/logs
sudo chmod -R 775 /var/www/amc/reports
```

### Step 6.4: Create Environment Configuration

```bash
# Create .env file
sudo nano /var/www/amc/.env
```

**Add configuration:**
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_PUBLIC_IP

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=amc
DB_USERNAME=amc_user
DB_PASSWORD=your_strong_password_here_123!@#

# Session
SESSION_SECURE_COOKIE=false
SESSION_LIFETIME=1800

# Logging
LOG_LEVEL=error
LOG_CHANNEL=file

# Python
PYTHON_PATH=/usr/bin/python3
```

**Set permissions:**
```bash
sudo chown www-data:www-data /var/www/amc/.env
sudo chmod 640 /var/www/amc/.env
```

### Step 6.5: Install Node Dependencies and Build CSS

```bash
cd /var/www/amc

# Install dependencies
sudo -u www-data npm install

# Build Tailwind CSS
sudo -u www-data npm run build:css

# Verify CSS file created
ls -lh /var/www/amc/public/assets/css/tailwind.css
```

### Step 6.6: Configure Apache Virtual Host

```bash
# Create virtual host configuration
sudo nano /etc/apache2/sites-available/amc.conf
```

**Add configuration:**
```apache
<VirtualHost *:80>
    ServerAdmin admin@yourdomain.com
    ServerName YOUR_PUBLIC_IP
    # ServerAlias www.yourdomain.com  # Uncomment when you have domain

    DocumentRoot /var/www/amc/public

    <Directory /var/www/amc/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/amc-error.log
    CustomLog ${APACHE_LOG_DIR}/amc-access.log combined

    # PHP settings
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
</VirtualHost>
```

Save and exit.

**Enable required Apache modules:**
```bash
# Enable mod_rewrite, headers
sudo a2enmod rewrite
sudo a2enmod headers

# Disable default site
sudo a2dissite 000-default.conf

# Enable AMC site
sudo a2ensite amc.conf

# Test configuration
sudo apache2ctl configtest
```

Expected: `Syntax OK`

**Restart Apache:**
```bash
sudo systemctl restart apache2
```

### Step 6.7: Verify Application Access

Visit: `http://YOUR_PUBLIC_IP`

You should see the AMC application (may show database connection errors until we import data).

---

## Part 7: Database Setup

### Step 7.1: Import Database Schema

```bash
# Import main schema
mysql -u amc_user -p amc < /var/www/amc/amc.sql

# Enter password when prompted
```

### Step 7.2: Import Aircraft Movement Data

```bash
# Import movement data
mysql -u amc_user -p amc < /var/www/amc/database/aircraft_movements_inserts.sql
```

### Step 7.3: Apply Performance Indexes

```bash
# Import performance indexes
mysql -u amc_user -p amc < /var/www/amc/database/migrations/add_performance_indexes.sql
```

### Step 7.4: Create Admin User

```bash
# Connect to database
mysql -u amc_user -p amc
```

In MySQL:
```sql
-- Create admin user (password: Admin123! - change this!)
INSERT INTO users (username, password, role, created_at)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Hash for 'Admin123!'
    'admin',
    NOW()
);

-- Verify
SELECT id, username, role, created_at FROM users;

-- Exit
EXIT;
```

**Generate new secure password hash:**

```bash
# On the server
php -r "echo password_hash('YourSecurePassword123!', PASSWORD_DEFAULT);"
```

Use the output hash to replace the password in the INSERT statement above.

### Step 7.5: Verify Database

```bash
mysql -u amc_user -p amc -e "
SELECT
    (SELECT COUNT(*) FROM users) as users_count,
    (SELECT COUNT(*) FROM aircraft_details) as aircraft_count,
    (SELECT COUNT(*) FROM aircraft_movements) as movements_count,
    (SELECT COUNT(*) FROM stands) as stands_count;
"
```

Expected output:
```
+-------------+----------------+-----------------+--------------+
| users_count | aircraft_count | movements_count | stands_count |
+-------------+----------------+-----------------+--------------+
|           1 |            XXX |             XXX |           92 |
+-------------+----------------+-----------------+--------------+
```

### Step 7.6: Configure MySQL for Performance

```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

**Add under [mysqld] section:**
```ini
# AMC Performance Tuning
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 50
query_cache_size = 64M
query_cache_type = 1
```

**Restart MySQL:**
```bash
sudo systemctl restart mysql
```

---

## Part 8: Security Hardening

### Step 8.1: Secure SSH Access

```bash
# Edit SSH configuration
sudo nano /etc/ssh/sshd_config
```

**Modify these settings:**
```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
Port 22
MaxAuthTries 3
ClientAliveInterval 300
ClientAliveCountMax 2
```

**Restart SSH:**
```bash
sudo systemctl restart sshd
```

### Step 8.2: Install Fail2Ban

```bash
# Install fail2ban
sudo apt install -y fail2ban

# Create local configuration
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Edit configuration
sudo nano /etc/fail2ban/jail.local
```

**Configure SSH and Apache protection:**
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
destemail = your-email@example.com
sendername = Fail2Ban

[sshd]
enabled = true
port = 22
logpath = %(sshd_log)s
maxretry = 3

[apache-auth]
enabled = true
port = http,https
logpath = %(apache_error_log)s

[apache-noscript]
enabled = true
port = http,https
logpath = %(apache_error_log)s

[apache-overflows]
enabled = true
port = http,https
logpath = %(apache_error_log)s
```

**Start Fail2Ban:**
```bash
sudo systemctl start fail2ban
sudo systemctl enable fail2ban

# Check status
sudo fail2ban-client status
```

### Step 8.3: Configure Application Security

```bash
# Update .env for production
sudo nano /var/www/amc/.env
```

**Ensure these are set:**
```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=false  # Will be true after SSL
```

### Step 8.4: Disable Sensitive Files Access

```bash
# Add to Apache config
sudo nano /etc/apache2/sites-available/amc.conf
```

**Add before </VirtualHost>:**
```apache
    # Block access to sensitive files
    <FilesMatch "^\.env">
        Require all denied
    </FilesMatch>

    <FilesMatch "\.(sql|log|ini|md)$">
        Require all denied
    </FilesMatch>

    <DirectoryMatch "/var/www/amc/(storage|logs|database|ml/archive|data)">
        Require all denied
    </DirectoryMatch>
```

**Reload Apache:**
```bash
sudo systemctl reload apache2
```

### Step 8.5: Setup Automated Security Updates

```bash
# Install unattended-upgrades
sudo apt install -y unattended-upgrades

# Enable automatic security updates
sudo dpkg-reconfigure -plow unattended-upgrades

# Verify configuration
sudo nano /etc/apt/apt.conf.d/50unattended-upgrades
```

**Ensure these are uncommented:**
```
"${distro_id}:${distro_codename}-security";
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "true";
Unattended-Upgrade::Automatic-Reboot-Time "02:00";
```

---

## Part 9: SSL Certificate Setup

### Option A: Free Domain with DuckDNS + Let's Encrypt (Recommended)

#### Step 9.1: Get Free Domain from DuckDNS

1. **Visit:** https://www.duckdns.org/
2. **Sign in** with Google/GitHub/Reddit
3. **Create subdomain:** `amc-prod` → becomes `amc-prod.duckdns.org`
4. **Set IP:** Enter your Oracle Cloud public IP
5. **Save token** - you'll need it for updates

#### Step 9.2: Update Domain IP Automatically

```bash
# Create update script
sudo nano /usr/local/bin/duckdns-update.sh
```

**Add:**
```bash
#!/bin/bash
echo url="https://www.duckdns.org/update?domains=amc-prod&token=YOUR_DUCKDNS_TOKEN&ip=" | curl -k -o /var/log/duckdns.log -K -
```

Replace `YOUR_DUCKDNS_TOKEN` with your actual token.

**Make executable:**
```bash
sudo chmod +x /usr/local/bin/duckdns-update.sh
```

**Add to crontab:**
```bash
sudo crontab -e
```

Add:
```
*/5 * * * * /usr/local/bin/duckdns-update.sh >/dev/null 2>&1
```

#### Step 9.3: Install Certbot for Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache -d amc-prod.duckdns.org --non-interactive --agree-tos --email your-email@example.com
```

**Certbot will:**
- Verify domain ownership
- Issue SSL certificate
- Auto-configure Apache for HTTPS
- Set up auto-renewal

#### Step 9.4: Update Application Configuration

```bash
sudo nano /var/www/amc/.env
```

**Update:**
```env
APP_URL=https://amc-prod.duckdns.org
SESSION_SECURE_COOKIE=true
```

**Reload Apache:**
```bash
sudo systemctl reload apache2
```

#### Step 9.5: Test SSL

Visit: `https://amc-prod.duckdns.org`

Check certificate:
```bash
sudo certbot certificates
```

**Auto-renewal test:**
```bash
sudo certbot renew --dry-run
```

### Option B: Use IP Address Only (No SSL)

If you don't need HTTPS, skip SSL setup and continue with HTTP only.

**Security note:** Login credentials will be transmitted in plaintext. Only use for testing or internal networks.

---

## Part 10: Monitoring Setup

### Step 10.1: Install Monitoring Tools

```bash
# Install htop, iotop for system monitoring
sudo apt install -y htop iotop iftop

# Install log monitoring
sudo apt install -y logwatch
```

### Step 10.2: Configure MySQL Slow Query Log

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

**Add:**
```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1
```

**Restart MySQL:**
```bash
sudo systemctl restart mysql
```

### Step 10.3: Setup Log Rotation

```bash
# Create log rotation config
sudo nano /etc/logrotate.d/amc
```

**Add:**
```
/var/www/amc/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    create 0644 www-data www-data
    sharedscripts
    postrotate
        systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}

/var/log/mysql/slow.log {
    weekly
    rotate 4
    compress
    delaycompress
    missingok
    create 0640 mysql adm
    sharedscripts
    postrotate
        systemctl reload mysql > /dev/null 2>&1 || true
    endscript
}
```

### Step 10.4: Create Monitoring Dashboard

```bash
# Install Netdata (optional - lightweight monitoring)
bash <(curl -Ss https://my-netdata.io/kickstart.sh) --dont-wait

# Access monitoring
# Visit: http://YOUR_IP:19999
```

**Secure Netdata (optional):**
```bash
# Edit netdata config
sudo nano /etc/netdata/netdata.conf
```

Find `[web]` section:
```ini
[web]
    bind to = 127.0.0.1
```

Restart:
```bash
sudo systemctl restart netdata
```

Now only accessible locally (use SSH tunnel to view):
```bash
# From your local machine
ssh -i ~/Downloads/ssh-key-*.key -L 19999:localhost:19999 ubuntu@YOUR_PUBLIC_IP

# Then visit: http://localhost:19999
```

### Step 10.5: Email Alerts Setup (Optional)

```bash
# Install mailutils
sudo apt install -y mailutils postfix

# Configure postfix (choose "Internet Site")

# Test email
echo "Test from AMC server" | mail -s "Test Email" your-email@example.com
```

**Create alert script:**
```bash
sudo nano /usr/local/bin/amc-health-check.sh
```

```bash
#!/bin/bash

# Check if Apache is running
if ! systemctl is-active --quiet apache2; then
    echo "Apache is down!" | mail -s "AMC ALERT: Apache Down" your-email@example.com
    systemctl restart apache2
fi

# Check if MySQL is running
if ! systemctl is-active --quiet mysql; then
    echo "MySQL is down!" | mail -s "AMC ALERT: MySQL Down" your-email@example.com
    systemctl restart mysql
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo "Disk usage is at ${DISK_USAGE}%" | mail -s "AMC ALERT: Low Disk Space" your-email@example.com
fi
```

**Make executable and schedule:**
```bash
sudo chmod +x /usr/local/bin/amc-health-check.sh

# Add to crontab
sudo crontab -e
```

Add:
```
*/10 * * * * /usr/local/bin/amc-health-check.sh
```

---

## Part 11: Backup Strategy

### Step 11.1: Create Backup Directory

```bash
sudo mkdir -p /backup/amc
sudo mkdir -p /backup/mysql
sudo chown -R ubuntu:ubuntu /backup
```

### Step 11.2: Database Backup Script

```bash
sudo nano /usr/local/bin/amc-backup-db.sh
```

**Add:**
```bash
#!/bin/bash

# Configuration
DB_NAME="amc"
DB_USER="amc_user"
DB_PASS="your_strong_password_here_123!@#"
BACKUP_DIR="/backup/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/amc_db_$DATE.sql.gz

# Remove old backups
find $BACKUP_DIR -name "amc_db_*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Log backup
echo "Database backup completed: amc_db_$DATE.sql.gz" >> /var/log/amc-backup.log
```

**Make executable:**
```bash
sudo chmod +x /usr/local/bin/amc-backup-db.sh
```

### Step 11.3: Application Files Backup Script

```bash
sudo nano /usr/local/bin/amc-backup-files.sh
```

**Add:**
```bash
#!/bin/bash

# Configuration
APP_DIR="/var/www/amc"
BACKUP_DIR="/backup/amc"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=14

# Create backup (exclude cache and large data files)
tar -czf $BACKUP_DIR/amc_files_$DATE.tar.gz \
    --exclude='storage/cache/*' \
    --exclude='node_modules' \
    --exclude='data/*.csv' \
    $APP_DIR

# Remove old backups
find $BACKUP_DIR -name "amc_files_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Log backup
echo "Files backup completed: amc_files_$DATE.tar.gz" >> /var/log/amc-backup.log
```

**Make executable:**
```bash
sudo chmod +x /usr/local/bin/amc-backup-files.sh
```

### Step 11.4: Schedule Automated Backups

```bash
sudo crontab -e
```

**Add:**
```
# Database backup - Daily at 2 AM
0 2 * * * /usr/local/bin/amc-backup-db.sh

# Files backup - Weekly on Sunday at 3 AM
0 3 * * 0 /usr/local/bin/amc-backup-files.sh
```

### Step 11.5: Test Backups

```bash
# Test database backup
sudo /usr/local/bin/amc-backup-db.sh

# Test files backup
sudo /usr/local/bin/amc-backup-files.sh

# Verify backups created
ls -lh /backup/mysql/
ls -lh /backup/amc/
```

### Step 11.6: Backup Restoration Procedure

**Restore Database:**
```bash
# List available backups
ls -lh /backup/mysql/

# Restore (replace DATE with actual backup date)
gunzip < /backup/mysql/amc_db_YYYYMMDD_HHMMSS.sql.gz | mysql -u amc_user -p amc
```

**Restore Application Files:**
```bash
# Stop Apache
sudo systemctl stop apache2

# Restore files
sudo tar -xzf /backup/amc/amc_files_YYYYMMDD_HHMMSS.tar.gz -C /

# Fix permissions
sudo chown -R www-data:www-data /var/www/amc

# Start Apache
sudo systemctl start apache2
```

### Step 11.7: Off-Site Backup (Optional)

**Option A: Sync to Another Cloud Storage**

```bash
# Install rclone
curl https://rclone.org/install.sh | sudo bash

# Configure rclone (follow prompts for Google Drive, Dropbox, etc.)
rclone config

# Create sync script
sudo nano /usr/local/bin/amc-backup-offsite.sh
```

```bash
#!/bin/bash
# Sync backups to cloud storage
rclone sync /backup remote:amc-backups --log-file=/var/log/rclone.log
```

**Option B: Upload to Oracle Object Storage (Free Tier Includes 20GB)**

See Oracle Cloud documentation for Object Storage setup.

---

## Part 12: Maintenance Procedures

### Step 12.1: Daily Maintenance Checklist

**Automated (via cron):**
- [x] Database backups (2 AM daily)
- [x] Log rotation
- [x] Health checks (every 10 minutes)
- [x] Security updates (automatic)

**Manual (weekly):**
```bash
# Check system resources
htop

# Check disk usage
df -h

# Check Apache logs for errors
sudo tail -n 100 /var/log/apache2/amc-error.log

# Check MySQL slow queries
sudo tail -n 50 /var/log/mysql/slow.log

# Check fail2ban status
sudo fail2ban-client status sshd
```

### Step 12.2: Weekly Maintenance Tasks

```bash
# Update package lists
sudo apt update

# Check for available updates
apt list --upgradable

# Upgrade packages (review first)
sudo apt upgrade -y

# Clean package cache
sudo apt autoremove -y
sudo apt autoclean
```

### Step 12.3: Monthly Maintenance Tasks

**Optimize Database:**
```bash
# Create optimization script
sudo nano /usr/local/bin/amc-optimize-db.sh
```

```bash
#!/bin/bash
mysql -u amc_user -p'your_strong_password_here_123!@#' amc <<EOF
-- Optimize tables
OPTIMIZE TABLE aircraft_details;
OPTIMIZE TABLE aircraft_movements;
OPTIMIZE TABLE ml_prediction_log;
OPTIMIZE TABLE daily_snapshots;
OPTIMIZE TABLE audit_log;

-- Analyze tables
ANALYZE TABLE aircraft_details;
ANALYZE TABLE aircraft_movements;

-- Show table sizes
SELECT
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'amc'
ORDER BY (data_length + index_length) DESC;
EOF
```

**Make executable:**
```bash
sudo chmod +x /usr/local/bin/amc-optimize-db.sh
```

**Review Application Logs:**
```bash
# Check PHP error logs
sudo tail -n 200 /var/log/php/error.log

# Check AMC application logs
sudo tail -n 200 /var/www/amc/logs/php_errors.log

# Check for failed login attempts in audit log
mysql -u amc_user -p amc -e "
SELECT * FROM login_attempts
WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY created_at DESC LIMIT 50;
"
```

**Clean Old Data:**
```bash
# Remove old cache files
sudo find /var/www/amc/storage/cache -type f -mtime +30 -delete

# Clean old reports
sudo find /var/www/amc/reports -type f -mtime +90 -delete

# Clean old logs (if not using logrotate)
sudo find /var/www/amc/logs -name "*.log" -mtime +60 -delete
```

### Step 12.4: Quarterly Maintenance Tasks

**Review ML Model Performance:**
```bash
# Check prediction accuracy
mysql -u amc_user -p amc -e "
SELECT
    DATE(prediction_date) as date,
    COUNT(*) as total_predictions,
    SUM(was_prediction_correct) as correct,
    ROUND(100 * SUM(was_prediction_correct) / COUNT(*), 2) as accuracy_pct
FROM ml_prediction_log
WHERE prediction_date > DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY DATE(prediction_date)
ORDER BY date DESC
LIMIT 30;
"
```

**Retrain ML Model (if needed):**
```bash
cd /var/www/amc/ml

# Backup current model
sudo -u www-data cp parking_stand_model_rf_redo.pkl parking_stand_model_rf_redo.pkl.backup

# Retrain with latest data
sudo -u www-data python3 train_model.py

# Test new model
sudo -u www-data python3 test_predict.py
```

**Security Audit:**
```bash
# Check for unauthorized users
sudo cat /etc/passwd | grep -v nologin

# Check listening ports
sudo netstat -tulpn

# Check fail2ban statistics
sudo fail2ban-client status

# Review SSH authentication logs
sudo grep "Failed password" /var/log/auth.log | tail -n 50

# Check for rootkits (install rkhunter)
sudo apt install -y rkhunter
sudo rkhunter --check --skip-keypress
```

### Step 12.5: System Update Procedure

**Before Major Updates:**
```bash
# Create full backup
sudo /usr/local/bin/amc-backup-db.sh
sudo /usr/local/bin/amc-backup-files.sh

# Verify backups
ls -lh /backup/mysql/
ls -lh /backup/amc/
```

**Perform Update:**
```bash
# Update package lists
sudo apt update

# Upgrade packages
sudo apt upgrade -y

# Upgrade distribution (if needed, e.g., 22.04 -> 24.04)
sudo do-release-upgrade

# Reboot if kernel updated
sudo reboot
```

**After Update:**
```bash
# Verify services running
sudo systemctl status apache2
sudo systemctl status mysql

# Test application
curl -I http://YOUR_DOMAIN

# Check logs for errors
sudo tail -n 50 /var/log/apache2/amc-error.log
```

### Step 12.6: Maintenance Schedule Summary

| Task | Frequency | Command/Script |
|------|-----------|----------------|
| Database backup | Daily 2 AM | `/usr/local/bin/amc-backup-db.sh` |
| Files backup | Weekly Sunday 3 AM | `/usr/local/bin/amc-backup-files.sh` |
| Health checks | Every 10 min | `/usr/local/bin/amc-health-check.sh` |
| Security updates | Automatic | `unattended-upgrades` |
| Package updates | Weekly | `sudo apt update && sudo apt upgrade` |
| Database optimization | Monthly | `/usr/local/bin/amc-optimize-db.sh` |
| Log review | Weekly | Manual review |
| Security audit | Quarterly | Manual review |
| ML model retraining | Quarterly | `python3 train_model.py` |
| SSL certificate renewal | Automatic | `certbot renew` (auto-runs) |

---

## Part 13: Troubleshooting

### Issue 1: Cannot Connect to Server via SSH

**Symptoms:**
```
ssh: connect to host YOUR_IP port 22: Connection refused
```

**Solutions:**

1. **Check Oracle Cloud Security List:**
   - Go to OCI Console > Networking > VCN > Security Lists
   - Verify port 22 ingress rule exists for 0.0.0.0/0

2. **Check instance is running:**
   - OCI Console > Compute > Instances
   - Status should be "Running"

3. **Check SSH key:**
   ```bash
   # Verify key permissions
   chmod 600 ~/path/to/key.key

   # Try verbose SSH
   ssh -v -i ~/path/to/key.key ubuntu@YOUR_IP
   ```

4. **Use Oracle Cloud Console Connection:**
   - In OCI Console, click instance name
   - Click "Console Connection" > "Create Local Connection"
   - Use Java/SSH-based console access

---

### Issue 2: Apache Not Serving Website

**Symptoms:**
- Browser shows "Unable to connect"
- Or shows Apache default page instead of AMC

**Solutions:**

1. **Check Apache status:**
   ```bash
   sudo systemctl status apache2
   ```

2. **Check Apache configuration:**
   ```bash
   sudo apache2ctl configtest
   ```

3. **Check virtual host enabled:**
   ```bash
   ls -l /etc/apache2/sites-enabled/
   ```

   Should show `amc.conf` symlink.

4. **Check DocumentRoot:**
   ```bash
   cat /etc/apache2/sites-enabled/amc.conf | grep DocumentRoot
   ```

   Should be `/var/www/amc/public`

5. **Check file permissions:**
   ```bash
   ls -la /var/www/amc/public/
   ```

   Should be owned by `www-data:www-data`

6. **Check Apache error log:**
   ```bash
   sudo tail -n 50 /var/log/apache2/amc-error.log
   ```

7. **Restart Apache:**
   ```bash
   sudo systemctl restart apache2
   ```

---

### Issue 3: Database Connection Errors

**Symptoms:**
- "SQLSTATE[HY000] [1045] Access denied"
- "SQLSTATE[HY000] [2002] No such file or directory"

**Solutions:**

1. **Check MySQL is running:**
   ```bash
   sudo systemctl status mysql
   ```

2. **Verify database credentials:**
   ```bash
   mysql -u amc_user -p amc
   # Enter password from .env file
   ```

3. **Check .env file:**
   ```bash
   sudo cat /var/www/amc/.env | grep DB_
   ```

   Verify credentials match what you created.

4. **Reset database password:**
   ```bash
   mysql -u root -p
   ```

   ```sql
   ALTER USER 'amc_user'@'localhost' IDENTIFIED BY 'new_password';
   FLUSH PRIVILEGES;
   EXIT;
   ```

   Update `.env` with new password.

5. **Check database exists:**
   ```bash
   mysql -u root -p -e "SHOW DATABASES;"
   ```

6. **Recreate database if needed:**
   ```bash
   mysql -u root -p
   ```

   ```sql
   DROP DATABASE IF EXISTS amc;
   CREATE DATABASE amc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON amc.* TO 'amc_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

   Re-import schema:
   ```bash
   mysql -u amc_user -p amc < /var/www/amc/amc.sql
   ```

---

### Issue 4: ML Predictions Not Working

**Symptoms:**
- Predictions fail with "Model file not found"
- Python errors in logs

**Solutions:**

1. **Check Python is installed:**
   ```bash
   python3 --version
   which python3
   ```

2. **Check ML libraries:**
   ```bash
   python3 -c "import sklearn; print(sklearn.__version__)"
   python3 -c "import numpy; print(numpy.__version__)"
   python3 -c "import pandas; print(pandas.__version__)"
   ```

3. **Check model files exist:**
   ```bash
   ls -lh /var/www/amc/ml/*.pkl
   ```

4. **Check Python script permissions:**
   ```bash
   ls -l /var/www/amc/ml/predict.py
   # Should be executable: -rwxr-xr-x
   ```

5. **Test prediction manually:**
   ```bash
   cd /var/www/amc/ml
   sudo -u www-data python3 predict.py --test
   ```

6. **Check Python path in .env:**
   ```bash
   cat /var/www/amc/.env | grep PYTHON_PATH
   ```

7. **Reinstall Python libraries:**
   ```bash
   sudo pip3 install --upgrade numpy pandas scikit-learn
   ```

---

### Issue 5: Slow Performance

**Symptoms:**
- Pages load slowly (> 5 seconds)
- High CPU usage
- Database queries timeout

**Solutions:**

1. **Check system resources:**
   ```bash
   htop
   ```

   Look for high CPU/memory usage.

2. **Check Apache processes:**
   ```bash
   ps aux | grep apache2 | wc -l
   ```

   If > 50 processes, Apache may be overwhelmed.

3. **Optimize Apache configuration:**
   ```bash
   sudo nano /etc/apache2/mods-enabled/mpm_prefork.conf
   ```

   ```apache
   <IfModule mpm_prefork_module>
       StartServers             5
       MinSpareServers          5
       MaxSpareServers         10
       MaxRequestWorkers       50
       MaxConnectionsPerChild   0
   </IfModule>
   ```

4. **Enable PHP OPcache:**
   ```bash
   sudo nano /etc/php/8.2/apache2/php.ini
   ```

   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.interned_strings_buffer=8
   opcache.max_accelerated_files=10000
   opcache.revalidate_freq=2
   ```

   Restart Apache:
   ```bash
   sudo systemctl restart apache2
   ```

5. **Check slow MySQL queries:**
   ```bash
   sudo tail -n 50 /var/log/mysql/slow.log
   ```

   Optimize slow queries.

6. **Run database optimization:**
   ```bash
   sudo /usr/local/bin/amc-optimize-db.sh
   ```

7. **Clear application cache:**
   ```bash
   sudo rm -rf /var/www/amc/storage/cache/*
   ```

---

### Issue 6: SSL Certificate Issues

**Symptoms:**
- "Your connection is not private" warning
- Certificate expired
- HTTPS not working

**Solutions:**

1. **Check certificate status:**
   ```bash
   sudo certbot certificates
   ```

2. **Renew certificate manually:**
   ```bash
   sudo certbot renew
   ```

3. **Check Apache SSL configuration:**
   ```bash
   sudo apache2ctl -S
   ```

   Look for SSL VirtualHost on port 443.

4. **Test SSL configuration:**
   ```bash
   sudo apache2ctl configtest
   ```

5. **Force renewal (if close to expiry):**
   ```bash
   sudo certbot renew --force-renewal
   ```

6. **Check DuckDNS IP is current:**
   ```bash
   nslookup amc-prod.duckdns.org
   curl ifconfig.me
   ```

   IPs should match.

---

### Issue 7: Out of Disk Space

**Symptoms:**
- "No space left on device"
- Cannot write logs or cache

**Solutions:**

1. **Check disk usage:**
   ```bash
   df -h
   ```

2. **Find largest directories:**
   ```bash
   sudo du -sh /* 2>/dev/null | sort -h
   ```

3. **Clean package cache:**
   ```bash
   sudo apt clean
   sudo apt autoclean
   sudo apt autoremove -y
   ```

4. **Clean old logs:**
   ```bash
   sudo journalctl --vacuum-time=7d
   sudo find /var/log -name "*.log" -mtime +30 -delete
   sudo find /var/log -name "*.gz" -mtime +30 -delete
   ```

5. **Clean old backups:**
   ```bash
   sudo find /backup -type f -mtime +30 -delete
   ```

6. **Clean application cache:**
   ```bash
   sudo rm -rf /var/www/amc/storage/cache/*
   sudo rm -rf /var/www/amc/reports/*.json
   ```

7. **Check large files:**
   ```bash
   sudo find / -type f -size +100M 2>/dev/null
   ```

---

### Issue 8: Cannot Login to Application

**Symptoms:**
- "Invalid credentials" even with correct password
- Login page redirects to itself

**Solutions:**

1. **Reset admin password:**
   ```bash
   # Generate new password hash
   php -r "echo password_hash('NewPassword123!', PASSWORD_DEFAULT);"
   ```

   Copy the hash, then:
   ```bash
   mysql -u amc_user -p amc
   ```

   ```sql
   UPDATE users
   SET password = '$2y$10$PASTE_HASH_HERE'
   WHERE username = 'admin';
   EXIT;
   ```

2. **Check session configuration:**
   ```bash
   cat /var/www/amc/config/session.php
   ```

3. **Clear sessions:**
   ```bash
   sudo rm -rf /var/www/amc/storage/sessions/*
   ```

4. **Check login attempts table:**
   ```bash
   mysql -u amc_user -p amc -e "SELECT * FROM login_attempts WHERE username = 'admin';"
   ```

   If locked out, clear:
   ```sql
   DELETE FROM login_attempts WHERE username = 'admin';
   ```

5. **Check PHP error logs:**
   ```bash
   sudo tail -n 50 /var/log/php/error.log
   ```

---

## Part 14: Scaling and Optimization

### Step 14.1: Performance Monitoring

**Install Performance Tools:**
```bash
# Install monitoring
sudo apt install -y sysstat iotop nethogs

# Enable sysstat
sudo systemctl enable sysstat
sudo systemctl start sysstat
```

**Monitor Performance:**
```bash
# CPU and memory history
sar -u 1 10  # CPU usage every 1 second, 10 samples
sar -r 1 10  # Memory usage

# Disk I/O
iotop

# Network usage by process
sudo nethogs
```

### Step 14.2: Caching Strategy

**Install Redis (optional):**
```bash
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf
```

Find and modify:
```
maxmemory 128mb
maxmemory-policy allkeys-lru
```

Restart:
```bash
sudo systemctl restart redis
```

**Install PHP Redis extension:**
```bash
sudo apt install -y php8.2-redis
sudo systemctl restart apache2
```

**Use Redis in Application:**
Modify your PHP code to use Redis for session storage and caching.

### Step 14.3: Database Query Optimization

**Enable Query Cache:**
Already configured in MySQL section.

**Add More Indexes:**
```sql
-- Connect to database
mysql -u amc_user -p amc

-- Add indexes for frequently queried columns
CREATE INDEX idx_movement_date_reg ON aircraft_movements(movement_date, registration);
CREATE INDEX idx_created_at ON audit_log(created_at);
CREATE INDEX idx_prediction_user ON ml_prediction_log(requested_by_user, prediction_date);

-- Show all indexes
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'amc'
ORDER BY TABLE_NAME, INDEX_NAME;
```

### Step 14.4: CDN for Static Assets (Optional)

**Use Cloudflare Free Tier:**

1. Sign up at https://www.cloudflare.com
2. Add your DuckDNS domain (works with CNAME)
3. Update DNS to Cloudflare nameservers
4. Enable "Auto Minify" for CSS/JS
5. Enable "Brotli" compression
6. Set cache rules for `/assets/*` to 1 month

**Result:** Faster asset loading, reduced bandwidth.

### Step 14.5: Upgrade to Larger Instance (If Needed)

**If ARM A1 is not enough (unlikely), scale up:**

Oracle Always Free includes up to 4 OCPUs and 24GB RAM total across all VMs.

You can reconfigure:
```
Current: 1 VM with 4 OCPU, 24GB RAM

Alternative:
- 2 VMs with 2 OCPU, 12GB RAM each (for load balancing)
- 1 VM with 2 OCPU, 12GB RAM + 1 VM with 2 OCPU, 12GB RAM (web + DB separation)
```

**To resize:**
1. Stop instance in OCI Console
2. Click "Edit" > "Shape"
3. Adjust OCPU/RAM allocation
4. Start instance

### Step 14.6: Load Balancing (Advanced)

Oracle Always Free includes 1 load balancer (10Mbps).

**Use Case:** If you deploy 2 VMs for high availability.

**Setup:**
1. Create 2 identical VMs with AMC
2. Deploy Oracle Load Balancer
3. Configure backend set with both VMs
4. Point domain to load balancer IP

**Note:** For most use cases, 1 VM with 4 OCPU and 24GB RAM is sufficient for AMC.

---

## Appendix A: Quick Reference Commands

### System Management
```bash
# Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql

# View service logs
sudo journalctl -u apache2 -n 50
sudo journalctl -u mysql -n 50

# Check disk space
df -h

# Check memory
free -h

# Check running processes
htop
```

### Application Management
```bash
# Navigate to app
cd /var/www/amc

# Update code from Git
git pull origin main

# Rebuild CSS
sudo -u www-data npm run build:css

# Clear cache
sudo rm -rf storage/cache/*

# View application logs
sudo tail -f logs/php_errors.log
```

### Database Management
```bash
# Connect to database
mysql -u amc_user -p amc

# Backup database
sudo /usr/local/bin/amc-backup-db.sh

# Restore database
gunzip < /backup/mysql/backup_file.sql.gz | mysql -u amc_user -p amc

# Optimize database
sudo /usr/local/bin/amc-optimize-db.sh
```

### Security
```bash
# Check fail2ban status
sudo fail2ban-client status

# Unban an IP
sudo fail2ban-client set sshd unbanip X.X.X.X

# Check SSL certificates
sudo certbot certificates

# Renew SSL
sudo certbot renew
```

---

## Appendix B: Environment Variables Reference

```env
# Application Configuration
APP_ENV=production          # Environment: production, development
APP_DEBUG=false            # Debug mode: true, false
APP_URL=https://your.domain.com  # Application URL

# Database Configuration
DB_CONNECTION=mysql        # Database driver
DB_HOST=localhost          # Database host
DB_PORT=3306              # Database port
DB_DATABASE=amc           # Database name
DB_USERNAME=amc_user      # Database username
DB_PASSWORD=your_password # Database password

# Session Configuration
SESSION_SECURE_COOKIE=true # Secure cookie (true with HTTPS)
SESSION_LIFETIME=1800      # Session timeout in seconds

# Logging
LOG_LEVEL=error           # Log level: debug, info, warning, error
LOG_CHANNEL=file          # Log channel: file, syslog

# Python
PYTHON_PATH=/usr/bin/python3  # Python executable path
```

---

## Appendix C: Maintenance Scripts Summary

All maintenance scripts created:

| Script | Location | Purpose |
|--------|----------|---------|
| `amc-backup-db.sh` | `/usr/local/bin/` | Daily database backup |
| `amc-backup-files.sh` | `/usr/local/bin/` | Weekly files backup |
| `amc-health-check.sh` | `/usr/local/bin/` | Service health monitoring |
| `amc-optimize-db.sh` | `/usr/local/bin/` | Monthly database optimization |
| `duckdns-update.sh` | `/usr/local/bin/` | DuckDNS IP update |

---

## Appendix D: Useful Oracle Cloud Commands

```bash
# Check instance metadata
curl http://169.254.169.254/opc/v1/instance/

# Get public IP from metadata
curl http://169.254.169.254/opc/v1/instance/ | jq -r '.metadata.public_ip'

# Get instance region
curl http://169.254.169.254/opc/v1/instance/ | jq -r '.metadata.region'
```

---

## Conclusion

You now have a complete, production-ready AMC application running on Oracle Cloud Always Free tier!

**What You've Accomplished:**
- ✅ Fully functional web application with ML capabilities
- ✅ Secure HTTPS with SSL certificate
- ✅ Automated backups (daily database, weekly files)
- ✅ System monitoring and health checks
- ✅ Security hardening (firewall, fail2ban, automated updates)
- ✅ Maintenance procedures and troubleshooting guides
- ✅ **$0/month cost** (completely free!)

**Next Steps:**
1. Customize the application for your specific needs
2. Set up email alerts for critical issues
3. Monitor performance and optimize as needed
4. Consider off-site backup for critical data
5. Review security logs regularly

**Need Help?**
- Oracle Cloud Documentation: https://docs.oracle.com/en-us/iaas/
- Ubuntu Server Guide: https://ubuntu.com/server/docs
- Apache Documentation: https://httpd.apache.org/docs/
- MySQL Documentation: https://dev.mysql.com/doc/

---

**Last Updated:** 2025-11-27
**Document Version:** 1.0
**Author:** Claude Code
**License:** MIT License

