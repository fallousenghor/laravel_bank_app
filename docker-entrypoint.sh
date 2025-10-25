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
  # Try to run migrations; if they fail due to DB not being ready, show a message but continue to the server
  if php artisan migrate --force; then
    echo "[entrypoint] Migrations ran successfully."
  else
    echo "[entrypoint] Migrations failed or DB not ready. Continuing; check logs."
  fi

  if php artisan db:seed --force; then
    echo "[entrypoint] Seeders ran successfully."
  else
    echo "[entrypoint] Seeders failed or DB not ready. Continuing; check logs."
  fi

  # Cache config/routes/views for performance (ignore failures)
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

# Exec the CMD from the Dockerfile (artisan serve)
exec "$@"
