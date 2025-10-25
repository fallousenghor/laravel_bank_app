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

  # Run seeders (DatabaseSeeder is idempotent now and will skip if users exist)
  if php artisan db:seed --force; then
    echo "[entrypoint] Seeders ran successfully."
  else
    echo "[entrypoint] Seeders failed. Continuing; check logs."
  fi

  # Cache config/routes/views for performance (ignore failures)
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

# Exec the CMD from the Dockerfile (artisan serve)
exec "$@"
