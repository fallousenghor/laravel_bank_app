#!/bin/sh
set -e

# Entrypoint for Render / Docker deployments
# - Runs migrations with retries
# - Optionally runs seeders if RUN_SEEDERS=true
# - Caches config/route/view
# - Launches the Laravel HTTP server bound to $PORT (default 10000)

# Move to app directory
cd /var/www/html || exit 1

# Ensure storage and cache directories exist and are writable by the PHP process.
WWW_USER=${WWW_USER:-www-data}
WWW_GROUP=${WWW_GROUP:-www-data}
echo "[entrypoint] Ensuring storage and bootstrap/cache directories exist and are writable (user=${WWW_USER}:${WWW_GROUP})"
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache || true
touch storage/logs/laravel.log || true
if [ "$(id -u)" = "0" ]; then
  if command -v chown >/dev/null 2>&1; then
    chown -R "${WWW_USER}:${WWW_GROUP}" storage bootstrap/cache || true
    echo "[entrypoint] chown applied to storage and bootstrap/cache"
  fi
else
  echo "[entrypoint] not running as root; skipping chown. Ensure host volumes have correct ownership/permissions."
fi
chmod -R ug+rwx storage bootstrap/cache || true

echo "[entrypoint] Running startup tasks (migrations, optional seeders, caches)..."

echo "[entrypoint] DB_CONNECTION=${DB_CONNECTION:-}"
echo "[entrypoint] DB_HOST=${DB_HOST:-}"
php -r 'echo implode(",",PDO::getAvailableDrivers());' || true

if [ "${DB_CONNECTION:-}" != "pgsql" ]; then
  if [ "${DB_PORT:-}" = "5432" ] || echo "${DB_HOST:-}" | grep -qi "neon"; then
    if php -r 'exit(in_array("pgsql", PDO::getAvailableDrivers()) ? 0 : 1);' ; then
      echo "[entrypoint] Forcing DB_CONNECTION=pgsql (Postgres detected and driver available)"
      export DB_CONNECTION=pgsql
    else
      echo "[entrypoint] Postgres driver (pdo_pgsql) not available in PHP. Migrations may fail."
    fi
  fi
fi

if [ "${MIGRATE_ON_STARTUP:-false}" = "true" ]; then
  MAX_RETRIES=${MAX_RETRIES:-10}
  SLEEP_SECONDS=${SLEEP_SECONDS:-5}
  attempt=1
  until php artisan migrate --force; do
    echo "[entrypoint] Migration attempt ${attempt} failed. Waiting ${SLEEP_SECONDS}s before retry..."
    attempt=$((attempt+1))
    if [ ${attempt} -gt ${MAX_RETRIES} ]; then
      echo "[entrypoint] Migrations failed after ${MAX_RETRIES} attempts. Continuing; check logs."
      break
    fi
    sleep ${SLEEP_SECONDS}
  done
else
  echo "[entrypoint] MIGRATE_ON_STARTUP not enabled; skipping automatic migrations. Set MIGRATE_ON_STARTUP=true to enable."
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  echo "[entrypoint] RUN_SEEDERS=true; running seeders..."
  if php artisan db:seed --force; then
    echo "[entrypoint] Seeders ran successfully."
  else
    echo "[entrypoint] Seeders failed. Continuing; check logs."
  fi
else
  echo "[entrypoint] RUN_SEEDERS not enabled; skipping seeders."
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

PORT=${PORT:-10000}
echo "[entrypoint] Preparing nginx configuration to listen on ${PORT}"

export PORT
if [ -f /etc/nginx/conf.d/app.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/conf.d/app.conf.template > /etc/nginx/conf.d/default.conf
  echo "[entrypoint] Wrote /etc/nginx/conf.d/default.conf"
else
  echo "[entrypoint] WARNING: nginx template not found; using default nginx config"
fi

echo "[entrypoint] Starting php-fpm..."
if php-fpm -D 2>/var/log/php-fpm-start.log; then
  echo "[entrypoint] php-fpm started (daemonized)"
else
  echo "[entrypoint] php-fpm -D failed or unsupported; falling back to background &"
  php-fpm >/var/log/php-fpm-start.log 2>&1 &
fi

sleep 0.5
echo "[entrypoint] Active TCP listeners (ss -ltn):"
ss -ltn 2>/dev/null || netstat -ltn 2>/dev/null || true
echo "[entrypoint] php-fpm processes (ps aux | grep php-fpm):"
ps aux | grep php-fpm || true

echo "[entrypoint] Starting nginx in foreground (will bind to ${PORT})"
if nginx -t 2>/tmp/nginx-test.err; then
  echo "[entrypoint] nginx configuration OK"
else
  echo "[entrypoint] nginx configuration test FAILED"
  cat /tmp/nginx-test.err || true
  exit 1
fi

exec nginx -g 'daemon off;'
