# AMC — Complete Hosting Guide

A step-by-step guide to putting the Apron Movement Control system online, written
for someone hosting for the first time. Covers VPS (recommended) and Hostinger
shared hosting, plus domain, HTTPS, security, and backups.


## 0. First: pick your path

The app has three moving parts: PHP 8.3 + Apache, MySQL/MariaDB, and a Python ML
service. That ML service is the heart of the system — it's what produces the AI
stand recommendations the whole tool is built around. It runs as a real Python
process (the PHP app calls `ml/predict.py`), which means the host must let you
run Python and spawn processes.

**You need a VPS.** Only a VPS (or any server where you have root and can install
Docker/Python) can run the ML service. On a VPS the whole app runs from the
`DOCKER` branch with two commands — PHP, the database, and the Python ML libraries
are all set up for you inside the container.

Cost is about $5–7/month. Hostinger sells these as "VPS Hosting / KVM 1";
DigitalOcean, Vultr, and Linode all work equally well. Pick any of them with
**Ubuntu 24.04 LTS** and follow Path A below.

A note on shared hosting: plans like Hostinger's cheap hPanel shared hosting
**cannot run the AI recommendations** — they don't allow the Python process the
app needs. Since that's the core feature, shared hosting effectively runs a
crippled version of the program. It's documented in Path B only as a last resort
(e.g. a quick demo of the data-entry screens); it is not a real deployment option
for this tool. If AI recommendations matter — and they're the point — use a VPS.

Everything below assumes you already pushed the repo to GitHub (branches `main`
and `DOCKER`). The `DOCKER` branch is the trimmed, production-ready one.


---

# PATH A — VPS with Docker (recommended)

## A1. Buy and access the VPS

1. Buy a VPS (Hostinger KVM 1 or higher; or DigitalOcean / Vultr / Linode).
   Choose **Ubuntu 24.04 LTS** as the operating system.
2. You'll get an IP address and a root password (or SSH key) from the panel.
3. Connect from your PC's terminal (PowerShell or Terminal):

   ```bash
   ssh root@YOUR_SERVER_IP
   ```
   Type `yes` on first connect, then the password.


## A2. Install Docker

Paste these one block at a time:

```bash
apt update && apt upgrade -y
apt install -y git ca-certificates curl
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo $VERSION_CODENAME) stable" > /etc/apt/sources.list.d/docker.list
apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
docker --version && docker compose version
```

If both version lines print, Docker is ready.


## A3. Get the code

```bash
cd /opt
git clone -b DOCKER https://github.com/SyarifAdriann/AMC.git amc
cd amc
```

(`-b DOCKER` clones the lean production branch.)


## A4. Set your production secrets

Create a `.env` file next to `docker-compose.yml`:

```bash
nano .env
```

Paste this, change the password, then save with `Ctrl+O` `Enter` and `Ctrl+X`:

```env
DB_PASSWORD=CHANGE-ME-to-a-long-random-password
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false
```

`APP_DEBUG=false` is critical — `true` would show database errors to visitors.


## A5. Launch

```bash
docker compose up -d --build
```

First build takes a few minutes (it installs PHP, Python, and the ML libraries).
On first boot it auto-imports the database from `amc.sql`.

Check it's alive:

```bash
docker compose ps           # all services running / healthy
curl -I http://localhost    # should return HTTP 200 or 302
docker compose logs app     # watch for errors
```

Visit `http://YOUR_SERVER_IP` in a browser — you should see the login page.


## A6. Point your domain at the server

In your domain registrar (or Hostinger hPanel → Domains → DNS), add two records:

- An **A** record, name `@`, value = your server IP.
- An **A** record, name `www`, value = your server IP.

DNS takes anywhere from 5 minutes to a few hours to propagate. Test it with
`ping your-domain.com` — it should show your server IP.


## A7. Add HTTPS (free, automatic) with Caddy

The container serves plain HTTP on port 80. Put Caddy in front — it fetches and
renews a free Let's Encrypt certificate automatically, and correctly handles the
app's real-time streaming.

First, make the app listen only on localhost so Caddy owns the public ports.
Edit `docker-compose.yml`:

```bash
nano docker-compose.yml
```

Change the app port line from `- "80:80"` to:

```yaml
      - "127.0.0.1:8080:80"
```

Save, then run `docker compose up -d`.

Install Caddy:

```bash
apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list
apt update && apt install -y caddy
```

Configure it:

```bash
nano /etc/caddy/Caddyfile
```

Replace everything with (use your real domain):

```
your-domain.com, www.your-domain.com {
    reverse_proxy 127.0.0.1:8080 {
        # Real-time apron sync (Server-Sent Events) must not be buffered
        flush_interval -1
    }
}
```

Reload:

```bash
systemctl reload caddy
```

Wait about 30 seconds, then open `https://your-domain.com` — you have HTTPS.

The `flush_interval -1` line keeps the live apron map updating instantly. Without
it, real-time sync would lag or stall behind the proxy.


## A8. Firewall

```bash
ufw allow OpenSSH
ufw allow 80
ufw allow 443
ufw --force enable
ufw status
```

Port 3307 (the database) is deliberately not opened — the DB is only reachable
from inside the server. Keep it that way.


---

# PATH B — Hostinger shared hosting (hPanel)

Last resort only. Shared hosting cannot run the Python ML process, so the AI
stand recommendations — the core of this tool — will not work here. What's left
is a data-entry app: the apron map, master table, dashboard, reports, and
real-time sync still function, but the intelligence that the program exists for
does not. Use this only for a quick demo or if you genuinely don't need
recommendations. For real use, go back to Path A and get a VPS.


## B1. Create the database

1. hPanel → Databases → MySQL Databases.
2. Create a database (e.g. `amc`), a user, and a strong password.
3. Note the database name, username, password, and host (host is usually
   `localhost`). You'll need them in step B4.
4. Open phpMyAdmin → select your new database → Import → upload `amc.sql` from
   the repo → Go. This creates all tables and seed data.


## B2. Upload the files

Easiest: hPanel → Files → File Manager, go into `public_html`, and upload the
project (from the `DOCKER` branch — it's the lean one). Or use Git in hPanel's
Advanced → GIT if available, cloning the `DOCKER` branch.


## B3. Point the web root at `public/`

The app's real entry point is `public/index.php`. Two options:

- Best: in hPanel → Domains, set the domain's document root to
  `public_html/public`.
- If you can't change the root: the included root `.htaccess` already rewrites
  requests into the app, so serving from the project root also works.


## B4. Set the database credentials

Shared hosting can't set Docker env vars, so point the app at your DB directly.
Edit `config/database.php` and change the fallback values:

```php
'host'     => getenv('DB_HOST') ?: 'localhost',
'database' => getenv('DB_DATABASE') ?: 'YOUR_DB_NAME',
'username' => getenv('DB_USERNAME') ?: 'YOUR_DB_USER',
'password' => getenv('DB_PASSWORD') ?: 'YOUR_DB_PASSWORD',
```

Then edit `config/app.php` and set:

```php
'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),  // keep false
'url'   => getenv('APP_URL') ?: 'https://your-domain.com',
```


## B5. PHP version and extensions

hPanel → Advanced → PHP Configuration:

- PHP version 8.1 or newer (8.3 ideal).
- Enable extensions: `pdo_mysql` and `mbstring` (usually on by default).


## B6. HTTPS

hPanel → Security → SSL → install the free SSL for your domain (one click). Then
force HTTPS by uncommenting the redirect block at the bottom of the project's
root `.htaccess`.


---

# Post-deploy checklist (both paths)

Do these before letting real users in:

- Change every default account password. Log in, go to Dashboard → Manage
  Accounts, and reset all seeded users. The default logins from `amc.sql` are
  known — treat them as compromised until changed.
- Confirm `APP_DEBUG` is `false` (errors must not show raw DB details).
- Confirm HTTPS works and http:// redirects to https://.
- Firewall on (VPS): only ports 22, 80, 443 open; DB port closed.
- A backup has run and you know how to restore it (see below).
- Log in as each role (admin / operator / viewer) and click through the apron
  map, master table, and dashboard reports once.
- Open the apron map in two browsers and confirm an edit in one appears in the
  other within a second (real-time sync).


---

# Backups

## VPS (Docker)

A `db-backup` container already runs a daily dump into `/opt/amc/backups/`,
keeping 14 days. To also copy them off-server (recommended), download them to
your PC weekly, or add cloud sync (e.g. `rclone`).

Manual backup and restore:

```bash
# Backup now:
docker exec amc_db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines amc' > backups/manual.sql

# Restore:
docker exec -i amc_db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" amc' < backups/amc_YYYY-MM-DD_HHMM.sql
```

## Shared hosting

hPanel → Files → Backups for automatic account backups, and periodically
phpMyAdmin → Export your database to a `.sql` file you keep somewhere safe.


---

# Updating the app later

## VPS (Docker)

```bash
cd /opt/amc
git pull origin DOCKER
docker compose up -d --build
```

Your database persists across updates (it lives in a Docker volume). Only
`docker compose down -v` deletes data — don't run that unless you mean it.

## Shared hosting

Re-upload the changed files. Back up the DB first if the update includes schema
changes.


---

# Troubleshooting

**Blank page or HTTP 500.** VPS: `docker compose logs app`. Shared: check hPanel
error logs. Temporarily set `APP_DEBUG=true`, reload, read the message, then set
it back to `false`.

**"Database error" on login.** Wrong DB credentials (Path B, step B4), or the
database wasn't imported (`amc.sql`).

**Login page loads but nothing saves.** Open the browser console (F12). A 403
usually means the CSRF token — hard-refresh with Ctrl+F5.

**Real-time map not updating live.** VPS: confirm the Caddy `flush_interval -1`
line. Even without it, the 30-second fallback poll still refreshes.

**AI recommendation button errors.** Expected on shared hosting (no Python). On
VPS, check `docker compose logs app` for the Python error.

**Site not reachable by domain.** DNS hasn't propagated yet, or the A records are
wrong. Test with `ping your-domain.com`.

**HTTPS certificate not issued.** The domain's A record must point at the server
before Caddy can get a cert. Wait for DNS, then `systemctl reload caddy`.


---

# Quick reference

- Production branch: `DOCKER`
- App port (in container): 80
- DB port (host, VPS): 3307 → 3306, localhost only
- DB name: `amc`
- Required PHP: 8.3 (8.1+ on shared), with `pdo_mysql`, `mbstring`, `mod_rewrite`
- ML runtime: Python 3.11 + numpy / pandas / scikit-learn / joblib (Docker only)
- Web entry point: `public/index.php`
- DB seed: `amc.sql` (auto-imported in Docker)
- Must-set env: `DB_PASSWORD`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`

Full Docker specifics are in `SETUP.md` on the `DOCKER` branch.
