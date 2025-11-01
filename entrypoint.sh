#!/bin/sh
set -e

echo "Entrypoint: checking RUN_MIGRATIONS_ON_START=${RUN_MIGRATIONS_ON_START:-false}"

if [ "${RUN_MIGRATIONS_ON_START:-false}" = "true" ]; then
  echo "Running database migrations (force)..."
  php artisan migrate --force || echo "php artisan migrate failed"

  echo "Ensuring Passport keys and clients..."
  # Ensure storage folders and permissions exist
  mkdir -p storage framework storage/framework storage/logs bootstrap/cache || true
  chown -R $(id -u || echo 1000):$(id -g || echo 1000) storage bootstrap/cache || true

  # If OAUTH keys are provided via environment variables, write them (do not overwrite existing files)
  if [ -n "${OAUTH_PRIVATE_KEY:-}" ] && [ ! -f storage/oauth-private.key ]; then
    echo "Writing OAUTH_PRIVATE_KEY from environment to storage/oauth-private.key"
    printf '%s' "$OAUTH_PRIVATE_KEY" > storage/oauth-private.key
    chmod 600 storage/oauth-private.key || true
  fi

  if [ -n "${OAUTH_PUBLIC_KEY:-}" ] && [ ! -f storage/oauth-public.key ]; then
    echo "Writing OAUTH_PUBLIC_KEY from environment to storage/oauth-public.key"
    printf '%s' "$OAUTH_PUBLIC_KEY" > storage/oauth-public.key
    chmod 644 storage/oauth-public.key || true
  fi

  # Generate keys only if missing (do not overwrite existing files)
  if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "Passport keys missing - generating keys"
    php artisan passport:keys || echo "php artisan passport:keys failed"
  else
    echo "Passport keys present - skipping generation"
  fi

  # Seed minimal oauth clients if missing (use --force to avoid interactive confirmation in production)
  php artisan db:seed --class=Database\\Seeders\\PassportClientsSeeder --force || echo "seeding passport clients failed"
fi

# Execute the main process (php-fpm)
exec "$@"
