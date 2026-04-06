# =============================================================================
# AMC - Apron Movement Control System
# Dockerfile: PHP 8.3 + Apache + Python 3.11 (via Debian Bookworm)
#
# Bookworm ships Python 3.11 which has pre-built wheels for numpy/pandas/sklearn.
# This avoids the 15-minute compilation freeze seen with Python 3.13.
# =============================================================================

FROM php:8.3-apache-bookworm

# ---------------------------------------------------------------------------
# 1. System packages
#    - libonig-dev   : required for PHP mbstring extension (fixes oniguruma error)
#    - python3-pip   : to install ML dependencies
#    - python3-venv  : to create an isolated Python environment
#    - default-mysql-client : for the healthcheck + DB import in entrypoint
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
        python3 \
        python3-pip \
        python3-venv \
        default-mysql-client \
        dos2unix \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# 2. PHP Extensions
#    Install mbstring AFTER libonig-dev is present (fixes the compile error).
# ---------------------------------------------------------------------------
RUN docker-php-ext-install pdo_mysql mbstring

# ---------------------------------------------------------------------------
# 3. Enable Apache mod_rewrite
#    Required for the custom router (.htaccess RewriteEngine On).
# ---------------------------------------------------------------------------
RUN a2enmod rewrite

# ---------------------------------------------------------------------------
# 4. Apache virtual host config
# ---------------------------------------------------------------------------
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# ---------------------------------------------------------------------------
# 5. Custom PHP settings
# ---------------------------------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/amc-custom.ini

# ---------------------------------------------------------------------------
# 6. Python virtual environment + ML dependencies
#    Using a venv keeps the system Python clean.
#    Bookworm's Python 3.11 downloads pre-built wheels → fast build.
# ---------------------------------------------------------------------------
RUN python3 -m venv /opt/venv

# Copy only requirements first (layer caching: re-installs only if requirements change)
COPY ml/requirements.txt /tmp/requirements.txt

RUN /opt/venv/bin/pip install --upgrade pip --quiet \
    && /opt/venv/bin/pip install --no-cache-dir -r /tmp/requirements.txt

# Add venv to PATH so "python3" works everywhere
ENV PATH="/opt/venv/bin:$PATH"

# ---------------------------------------------------------------------------
# 7. Copy application source
# ---------------------------------------------------------------------------
WORKDIR /var/www/html
COPY . .

# ---------------------------------------------------------------------------
# 8. Directory permissions
#    Apache (www-data) needs write access to cache, storage, and logs.
# ---------------------------------------------------------------------------
RUN mkdir -p cache storage/cache logs \
    && chown -R www-data:www-data cache storage logs \
    && chmod -R 775 cache storage logs

# ---------------------------------------------------------------------------
# 9. Entrypoint
#    Strips BOMs, waits for DB, imports schema, then starts Apache.
# ---------------------------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN dos2unix /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
