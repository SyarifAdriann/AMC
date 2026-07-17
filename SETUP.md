# AMC — Apron Movement Control (Dockerized)

Production-ready deployment of the Apron Movement Control system:
PHP 8.3 + Apache, MariaDB 10.4, and a Python 3.11 ML service, all via Docker Compose.

This `DOCKER` branch contains **only what the program needs to run** — no docs,
datasets, thesis material, experiments, or the Google Apps Script tooling.

---

## Requirements

- Docker Engine 20+ and Docker Compose v2 (`docker compose`, not `docker-compose`)
- Ports **80** (app) and **3307** (MariaDB, host-side) free

## Quick start

```bash
# 1. From the repo root (DOCKER branch):
docker compose up --build          # first run — builds the image

# 2. Open the app:
#    http://localhost
```

That's it. On first boot the container:
1. Waits for MariaDB to become healthy.
2. Imports `amc.sql` (schema + seed data) — only if the DB is empty (idempotent).
3. Applies performance indexes from `database/migrations/`.
4. Starts Apache.

Subsequent runs:

```bash
docker compose up -d               # detached
docker compose down                # stop (keeps data)
docker compose down -v             # stop + WIPE all data volumes
```

---

## Configuration

Defaults work out of the box for local/testing. For real hosting, create a
`.env` file next to `docker-compose.yml` — **do not commit it**:

```env
DB_PASSWORD=change-me-to-a-strong-password
APP_URL=https://amc.example.com
APP_ENV=production
APP_DEBUG=false
```

| Variable      | Default              | Notes                                                        |
|---------------|----------------------|--------------------------------------------------------------|
| `DB_PASSWORD` | `root`               | Used by both the app and the MariaDB root user. **Change it.** |
| `APP_URL`     | `http://localhost`   | Public URL of the app.                                       |
| `APP_ENV`     | `docker`             | Set to `production` when hosting.                            |
| `APP_DEBUG`   | `false`              | **Keep false in production** — true leaks DB error details.  |

> Security note: `APP_DEBUG=true` returns raw database/server error messages to
> the browser. It must stay `false` anywhere real users can reach the app.

## HTTPS

TLS is not terminated inside the container. Put it behind a reverse proxy
(nginx / Caddy / Traefik / a cloud load balancer) that handles certificates and
forwards to port 80. Once served over HTTPS:

- The session cookie automatically switches to `Secure`.
- Uncomment the HTTPS-redirect block in `.htaccess` to force HTTPS.

---

## Services

| Service      | Container       | Purpose                                             |
|--------------|-----------------|-----------------------------------------------------|
| `app`        | `amc_app`       | PHP 8.3 + Apache + Python ML. Serves the app on :80.|
| `db`         | `amc_db`        | MariaDB 10.4. Host port **3307** → container 3306.  |
| `db-backup`  | `amc_db_backup` | Daily `mysqldump` into `./backups/`, 14-day retention. |

## Database access & backups

```bash
# Connect from the host:
mysql -h 127.0.0.1 -P 3307 -u root -p amc

# Manual backup:
docker exec amc_db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines amc' > backups/manual.sql

# Restore a dump:
docker exec -i amc_db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" amc' < backups/amc_<timestamp>.sql
```

Automated dumps land in `./backups/` on the host (created automatically, gitignored).

---

## Default accounts

Seeded from `amc.sql`. **Change these passwords immediately after first login**
(Dashboard → Manage Accounts). Roles: `admin`, `operator`, `viewer`.

## ML recommendations

The Python service (`ml/predict.py` + the bundled `.pkl` models) is invoked by
the app for stand recommendations. Dependencies install into an isolated venv at
build time from `ml/requirements.txt` — no host Python needed.

---

## Troubleshooting

| Symptom                              | Fix                                                                 |
|--------------------------------------|---------------------------------------------------------------------|
| Port 80 already in use               | Stop the other web server, or remap `ports:` in `docker-compose.yml`.|
| Port 3307 already in use             | Change the host side of the `db` port mapping.                      |
| App loads but DB errors              | `docker compose logs db` — wait for the healthcheck, then retry.    |
| Blank page / 500                     | `docker compose logs app`; temporarily set `APP_DEBUG=true` to diagnose. |
| Changed code not showing             | Rebuild: `docker compose up --build`.                               |

## Updating

```bash
git pull origin DOCKER
docker compose up --build -d
```
