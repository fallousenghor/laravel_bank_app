#!/bin/sh
set -e

# Entrypoint for Render / Docker deployments
# - Runs migrations with retries
# - Optionally runs seeders if RUN_SEEDERS=true
# - Caches config/route/view
# - Launches the Laravel HTTP server bound to $PORT (default 10000)

# Move to app directory
cd /var/www/html || exit 1

# If artisan missing, just exec passed command (or sleep to keep container alive)
if [ ! -f artisan ]; then
  echo "[entrypoint] artisan not found; executing provided command or sleeping..."
  if [ "$#" -gt 0 ]; then
    exec "$@"
  else
    # nothing to run - keep container alive for debugging
    tail -f /dev/null
  fi
fi

echo "[entrypoint] Running startup tasks (migrations, optional seeders, caches)..."

echo "[entrypoint] DB_CONNECTION=${DB_CONNECTION:-}"
echo "[entrypoint] DB_HOST=${DB_HOST:-}"
php -r 'echo implode(",",PDO::getAvailableDrivers());' || true

# If a Postgres host/port looks present and DB_CONNECTION not set to pgsql, prefer pgsql when driver exists
if [ "${DB_CONNECTION:-}" != "pgsql" ]; then
  if [ "${DB_PORT:-}" = "5432" ] || echo "${DB_HOST:-}" | grep -qi "neon"; then
    if php -r 'exit(in_array("pgsql", PDO::getAvailableDrivers()) ? 0 : 1);' ; then
      echo "[entrypoint] Forcing DB_CONNECTION=pgsql (Postgres detected and driver available)"
      export DB_CONNECTION=pgsql
    else
      echo "[entrypoint] Postgres driver (pdo_pgsql) not available in PHP. Migrations may fail.";
    fi
  fi
fi

# Retry migrations a few times in case DB is not yet ready (common in cloud deploys)
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

# Optional seeders
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

# Cache config/routes/views for performance (ignore failures)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

PORT=${PORT:-10000}
echo "[entrypoint] Preparing nginx configuration to listen on ${PORT}"

# Render nginx conf from template using envsubst (PORT must be exported)
export PORT
if [ -f /etc/nginx/conf.d/app.conf.template ]; then
  envsubst < /etc/nginx/conf.d/app.conf.template > /etc/nginx/conf.d/default.conf
  echo "[entrypoint] Wrote /etc/nginx/conf.d/default.conf"
else
  echo "[entrypoint] WARNING: nginx template not found; using default nginx config"
fi

echo "[entrypoint] Starting php-fpm..."
# Start php-fpm (daemonize) so nginx can connect to it
php-fpm || true

echo "[entrypoint] Starting nginx in foreground (will bind to ${PORT})"
# Start nginx in foreground (this will be PID 1)
exec nginx -g 'daemon off;'
