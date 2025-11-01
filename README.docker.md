# Docker usage notes

This repository now includes a production-oriented multi-stage `Dockerfile` optimized for Laravel 10.

Basic build (produce local image):

```bash
docker build -t laravel-app:latest .
```

Run (php-fpm only; pair with nginx for production):

```bash
# Run php-fpm container (exposes port 9000)
docker run --rm -p 9000:9000 \
  -v $(pwd):/var/www/html \
  -e APP_ENV=local \
  -e APP_KEY="base64:..." \
  -e DB_HOST=your_db_host \
  laravel-app:latest
```

Notes & recommendations
- The `Dockerfile` uses a builder stage to install Composer dependencies and reduce final image size.
- Do not store secrets in images or in `.env` files committed to the repo. Use Docker secrets, environment variables from your orchestrator, or your CI/CD secrets store.
- For production, run the PHP-FPM container behind an `nginx` (or other) reverse proxy. A sample nginx config is not included here to avoid assumptions about your infra.
- If you use Node assets (Vite), consider a separate stage to build assets (node:18) and copy the built `public` folder into the final image.
- If you rely on Redis, make sure to provide REDIS_HOST/REDIS_PASSWORD to the container (this Dockerfile enables the php redis extension).

Troubleshooting
- If composer permissions fail during build, ensure build args and filesystem permissions are correct. The Dockerfile tries to chown storage and bootstrap/cache to `www-data`.
- Laravel config/route/view caching is environment-specific; prefer running `php artisan config:cache` during deploy where environment variables are available instead of at build time.
