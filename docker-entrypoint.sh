#!/bin/sh
set -e

# Move to app directory
cd /var/www/html || exit 1

# Wait a short time for dependencies like DB to be available (optional)
# You can uncomment a sleep if your DB needs time
# sleep 2

# Run migrations and seeders if artisan exists
if [ -f artisan ]; then
  echo "[entrypoint] Running migrations and seeders (if needed)..."
  # Print DB env and available PDO drivers for debugging
  echo "[entrypoint] DB_CONNECTION=${DB_CONNECTION:-}"
  echo "[entrypoint] DB_HOST=${DB_HOST:-}"
  php -r 'echo implode(",",PDO::getAvailableDrivers());' || true

  # If DB_CONNECTION is not pgsql but a Postgres host/port is provided, prefer pgsql
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

  # Run seeders only if explicitly enabled (safer for production)
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
fi

# Exec the CMD from the Dockerfile (artisan serve)
exec "$@"
