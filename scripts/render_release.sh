#!/usr/bin/env bash
# Release script for Render deployments
set -euo pipefail

echo "Running Render release steps..."

# Clear & cache config so APP_URL / SWAGGER_BASE_URL are picked up
php artisan config:clear || true
php artisan config:cache || true

# Regenerate OpenAPI docs so Swagger UI reflects the production URL
php artisan l5-swagger:generate --no-interaction || true

echo "Render release steps completed."
