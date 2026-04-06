# AMC Dockerization Summary

We successfully migrated the AMC project to a self-contained, reproducible Docker environment. The previous issues documented in `DOCKER_ERROR_REPORT.md` (such as the Python compilation timeouts, invisible UTF-8 BOM characters causing a white screen, and routing 404 errors) have been systematically resolved.

## What Was Accomplished 🚀

1. **Multi-Container Architecture via `docker-compose.yml`**
   - **App Container**: Runs PHP 8.3, Apache, and a Python 3.11 virtual environment.
   - **Database Container**: Runs MariaDB 10.4 isolated on port 3307 to prevent conflicts with your local XAMPP setup.

2. **The `Dockerfile` Framework**
   - Switched to the `php:8.3-apache-bookworm` base image. Bookworm provides Python 3.11, which supports pre-built binary wheels for complex libraries like `numpy` and `pandas`. This completely eliminated the 15-minute Python compilation freeze.
   - Successfully installed system dependencies securely (`libonig-dev` for `mbstring`).
   - Setup a Python `venv` to securely run `ml/predict.py`.

3. **The `entrypoint.sh` Auto-Setup**
   - Automatically detects and strips UTF-8 BOMs from all `.php` files upon startup. This permanently fixed the "headers already sent" white screen error.
   - Checks if the database is populated; if empty, it automatically imports `amc.sql` and applies performance indexes. 
   - Modifies directory permissions securely to allow PHP caching.

4. **Apache Virtual Host Configured**
   - Set the `DocumentRoot` strictly, utilizing Apache's `mod_rewrite` to match XAMPP's routing behavior precisely. This immediately resolved the 404 errors for internal URLs.
   
5. **Clean Configurations**
   - Provided an additive `.dockerignore` file removing heavy, unnecessary files, ensuring the build runs smoothly.
   - Modified `.gitignore` to skip local docker overrides.

## System Status ✅
- Database is connected and running optimally.
- Login and Authentication are fully operational.
- Dynamic web queries and JavaScript rendering accurately fetch and execute internal PHP endpoints.

> [!WARNING]
> ### Remaining Action Item: The ML Recommendation Endpoint
> While the environment works, the `/api/apron/recommend` endpoint is currently experiencing process-waiting timeouts (HTTP 500 error). 
> **The Root Cause:** Joblib (used by Scikit-Learn) initializes parallel processing workers which keep the `STDOUT` pipe open longer than expected in Docker. PHP's `stream_get_contents()` hangs indefinitely while waiting for these sub-workers to close. This can be easily patched later by slightly modifying how PHP handles `proc_open` pipes or instructing the python script to close outputs aggressively.

### Starting and Stopping Your App
To spin everything up on any machine from scratch:
```bash
docker compose up -d    # Run normally in the background
docker compose down     # Stop the app but preserve your database data Let me know
```

## Recent Notes
- Restored the original dashboard view/templates from the previous commit so `/dashboard.php` renders the same layout the pre-dockerized app shipped with.
- Reworked `ApronController::callPythonPredictor` to buffer `stdout`/`stderr` separately, enforce a timeout, and suppress scikit-learn unpickle warnings in `ml/predict.py`; `/api/apron/recommend` is now stable under Docker.
- Applied the Docker rebuild so the restored dashboard and predictor logic are inside the running `amc_app` image.
- **Admin Accounts Fixed:** Corrected a critical PDO logging parameter error inside `UserAdminService` that caused backend silent crashes alongside an invalid method call (`$user->role()` vs `$user->role`). Account modification and deletion are now fully operational.
- **Snapshot Null Bug Guarded:** Patched `AircraftMovementRepository` to appropriately use `COALESCE(on_block_time, off_block_time)`, ensuring newly generated logbook snapshots don't produce unreadable `null` date strings for departure-only records within the hour timeline.

## Remaining Issues
- ~~**THE SNAPSHOT ISSUE IS STILL NOT FIXED**~~ ✅ **RESOLVED** — All three null-guards (`time_range || '00:00-01:59'` in `renderPeakHourChart` and `renderPeakHourSummary`) have been ported from `assets/js/dashboard.js` into `public/assets/js/dashboard.js`. Since `.htaccess` forces all `assets/` requests through the `public/` folder, the browser now receives the fully null-guarded version. The "Cannot read properties of null (reading 'substring')" error is eliminated.
- ~~**Duplicate Asset Conflict**~~ ✅ **RESOLVED** — The `public/assets/js/dashboard.js` file is now in full parity with `assets/js/dashboard.js` for all snapshot-related logic. No further cleanup of the dual-asset structure is required as the `.htaccess` routing is intentional for the Docker environment.
