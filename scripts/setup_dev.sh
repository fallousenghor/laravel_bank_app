#!/usr/bin/env bash
set -euo pipefail

# setup_dev.sh
# Fix common permission issues for Laravel development environment.
# Usage:
#   ./scripts/setup_dev.sh          # chown storage and bootstrap/cache to current user
#   SUDO_USER=www-data ./scripts/setup_dev.sh  # chown to specific user

TARGET_USER="${SUDO_USER:-$(whoami)}"
TARGET_GROUP="${TARGET_GROUP:-$TARGET_USER}"

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
echo "Project root: $PROJECT_ROOT"

echo "Fixing ownership to: ${TARGET_USER}:${TARGET_GROUP}"
sudo chown -R ${TARGET_USER}:${TARGET_GROUP} "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"

echo "Setting directory permissions (ug+rwX) and file permissions (ug+rw)"
sudo chmod -R ug+rwX "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"

echo "Removing potential stale cache files"
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "Permissions fixed. Verify with: ls -ld storage bootstrap/cache && ls -ld storage/logs"

exit 0
