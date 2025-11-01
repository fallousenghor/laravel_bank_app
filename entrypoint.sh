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

# After seeding, if PASSPORT_PASSWORD_CLIENT_ID/SECRET are not set we try to
# read them from the database and export them so the running PHP process (fpm
# or artisan serve) inherits them. This avoids having to manually copy the
# client id/secret to env on first deploy when the seed created clients.
if [ -z "${PASSPORT_PASSWORD_CLIENT_ID:-}" ] || [ -z "${PASSPORT_PASSWORD_CLIENT_SECRET:-}" ]; then
  echo "PASSPORT env vars missing - attempting to read password client from DB"
  # Evaluate a small PHP script to output export commands if a password client exists.
  # The output is executed in the current shell with eval so exports take effect for the exec below.
  attempt=0
  max_attempts=6
  PHPCMD=''
  # Create a small PHP helper script to query the DB for the password client.
  cat > /tmp/get_passport_client.php <<'PHP'
<?php
$dsn = null;
$user = null;
$pass = null;
$url = getenv('DATABASE_URL');
if ($url) {
    $parts = parse_url($url);
    $scheme = $parts['scheme'] ?? '';
    $host = $parts['host'] ?? '127.0.0.1';
    $port = $parts['port'] ?? null;
    $db = ltrim($parts['path'] ?? '', '/');
    $user = $parts['user'] ?? null;
    $pass = $parts['pass'] ?? null;
    if (strpos($scheme, 'mysql') !== false) {
        $dsn = "mysql:host={$host}" . ($port ? ";port={$port}" : '') . ";dbname={$db};charset=utf8mb4";
    } else {
        $dsn = "pgsql:host={$host}" . ($port ? ";port={$port}" : '') . ";dbname={$db}";
    }
} else {
    $driver = getenv('DB_CONNECTION') ?: 'mysql';
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: null;
    $db = getenv('DB_DATABASE') ?: null;
    $user = getenv('DB_USERNAME') ?: null;
    $pass = getenv('DB_PASSWORD') ?: null;
    if (in_array($driver, ['pgsql', 'postgres', 'postgresql'])) {
        $dsn = "pgsql:host={$host}" . ($port ? ";port={$port}" : '') . ";dbname={$db}";
    } else {
        $dsn = "mysql:host={$host}" . ($port ? ";port={$port}" : '') . ";dbname={$db};charset=utf8mb4";
    }
}
try {
    if (! $dsn) exit(0);
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("select id, secret from oauth_clients where password_client = 1 limit 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $id = addcslashes($row['id'], "\\\"\$\n\r");
        $secret = addcslashes($row['secret'], "\\\"\$\n\r");
        echo "export PASSPORT_PASSWORD_CLIENT_ID=\"{$id}\"; export PASSPORT_PASSWORD_CLIENT_SECRET=\"{$secret}\";";
    }
} catch (Throwable $e) {
    // silent
}
PHP

  while [ $attempt -lt $max_attempts ] && [ -z "$PHPCMD" ]; do
    PHPCMD=$(php /tmp/get_passport_client.php) || true
    if [ -n "$PHPCMD" ]; then
      break
    fi
    attempt=$((attempt+1))
    echo "Attempt $attempt/$max_attempts: DB not ready or no password client yet - retrying in 2s"
    sleep 2
  done

  if [ -n "$PHPCMD" ]; then
    # Execute the export commands in the current shell
    eval "$PHPCMD" || true
    echo "Exported PASSPORT_PASSWORD_CLIENT_ID/PASSPORT_PASSWORD_CLIENT_SECRET from DB"
  else
    echo "No password client found or DB unreachable after retries - PASSPORT env vars remain unset"
  fi
  rm -f /tmp/get_passport_client.php || true
fi

# If PORT is set (Render provides it), start the built-in PHP server so Render
# can detect an open HTTP port. Otherwise execute the default CMD (php-fpm).
if [ -n "${PORT:-}" ]; then
  echo "PORT is set to ${PORT} - starting php built-in server for HTTP on 0.0.0.0:${PORT}"
  # Use exec so the process inherits PID 1 and signals are forwarded
  exec php artisan serve --host 0.0.0.0 --port "${PORT}"
else
  # Execute the main process (php-fpm)
  exec "$@"
fi
