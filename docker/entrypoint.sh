#!/bin/bash
# =============================================================================
# AMC Docker Entrypoint Script
#
# Runs every time the container starts, BEFORE Apache launches.
# Steps:
#   1. Strip UTF-8 BOMs from all PHP files (fixes white screen)
#   2. Create writable directories
#   3. Wait for MySQL to be ready
#   4. Import database schema + data if tables don't exist
#   5. Apply performance indexes
#   6. Start Apache in foreground
# =============================================================================

set -e

APP_DIR="/var/www/html"
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-amc}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"

echo "============================================"
echo "  AMC Container Starting"
echo "============================================"

# ---------------------------------------------------------------------------
# STEP 1: Strip UTF-8 BOM from all PHP files
#
# BOM (0xEF 0xBB 0xBF) at the start of PHP files causes PHP to output those
# bytes before any headers are set — this breaks session_start() and produces
# a completely blank white page. We strip them globally on every startup.
# This is safe to run multiple times (idempotent).
# ---------------------------------------------------------------------------
echo "[1/5] Stripping UTF-8 BOMs from PHP files..."

BOM_COUNT=0
while IFS= read -r -d '' PHP_FILE; do
    # Check if file starts with UTF-8 BOM (EF BB BF)
    FIRST_BYTES=$(head -c 3 "$PHP_FILE" | od -A n -t x1 | tr -d ' \n')
    if [ "$FIRST_BYTES" = "efbbbf" ]; then
        # Use sed to strip the BOM in-place
        sed -i '1s/^\xEF\xBB\xBF//' "$PHP_FILE"
        BOM_COUNT=$((BOM_COUNT + 1))
        echo "   Stripped BOM: $PHP_FILE"
    fi
done < <(find "$APP_DIR" -name "*.php" -print0)

echo "   Done. Stripped $BOM_COUNT files."

# ---------------------------------------------------------------------------
# STEP 2: Ensure writable directories exist with correct ownership
# ---------------------------------------------------------------------------
echo "[2/5] Ensuring writable directories..."

mkdir -p "$APP_DIR/cache"
mkdir -p "$APP_DIR/storage/cache"
mkdir -p "$APP_DIR/logs"

# Sessions live here (see session.save_path in docker/php/php.ini) so they
# survive a rebuild. If this directory is missing, session_start() fails and
# every page 500s — so it must be created before Apache starts.
mkdir -p "$APP_DIR/storage/sessions"

chown -R www-data:www-data "$APP_DIR/cache" "$APP_DIR/storage" "$APP_DIR/logs"
chmod -R 775 "$APP_DIR/cache" "$APP_DIR/storage" "$APP_DIR/logs"

echo "   Done."

# ---------------------------------------------------------------------------
# STEP 3: Wait for MySQL to be ready
#
# Even with depends_on + healthcheck, the DB container may need a moment
# after reporting healthy before accepting connections from the app container.
# ---------------------------------------------------------------------------
echo "[3/5] Waiting for MySQL at $DB_HOST:$DB_PORT..."

MAX_TRIES=30
TRIES=0
until mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge "$MAX_TRIES" ]; then
        echo "   ERROR: MySQL did not become ready after $MAX_TRIES attempts. Aborting."
        exit 1
    fi
    echo "   Attempt $TRIES/$MAX_TRIES — retrying in 2s..."
    sleep 2
done

echo "   MySQL is ready."

# ---------------------------------------------------------------------------
# STEP 4: Import database (only if the 'users' table doesn't exist)
#
# We check for the 'users' table as a proxy for "has the schema been imported".
# This makes the import idempotent — runs on first boot only.
# ---------------------------------------------------------------------------
echo "[4/5] Checking database..."

TABLE_EXISTS=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE' AND table_name='users';" \
    --skip-column-names 2>/dev/null || echo "0")

TABLE_EXISTS=$(echo "$TABLE_EXISTS" | tr -d '[:space:]')

if [ "$TABLE_EXISTS" = "0" ]; then
    echo "   Database is empty. Importing amc.sql..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$APP_DIR/amc.sql"
    echo "   Schema + data imported successfully."

    echo "   Applying performance indexes..."
    if [ -f "$APP_DIR/database/migrations/add_performance_indexes.sql" ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
            < "$APP_DIR/database/migrations/add_performance_indexes.sql" 2>/dev/null || true
        echo "   Performance indexes applied."
    else
        echo "   Performance indexes file not found, skipping."
    fi
else
    echo "   Database already populated (found users table). Skipping import."
fi

# ---------------------------------------------------------------------------
# STEP 5: Start Apache in foreground
# ---------------------------------------------------------------------------
echo "[5/5] Starting Apache..."
echo "============================================"
echo "  AMC is ready at http://localhost"
echo "============================================"

exec apache2-foreground
