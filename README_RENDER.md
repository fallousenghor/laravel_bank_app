Render deployment checklist and commands

1) Add custom domain in Render
   - In the Render dashboard, add `fallou.senghor.bank.com` to your service and follow DNS instructions.
   - Wait until Render validates the domain and issues TLS.

2) Environment variables (Render → Environment)
   - APP_URL=https://fallou.senghor.bank.com
   - SWAGGER_BASE_URL=https://fallou.senghor.bank.com

3) Release command (ensure this runs at deploy time)
   - Use the provided `.render.yaml` or set a Release Command in the Render UI:

```
php artisan config:clear
php artisan config:cache
php artisan l5-swagger:generate --no-interaction
```

Or call the helper script included in this repo:

```
./scripts/render_release.sh
```

4) Verify after deployment

```
curl -I https://fallou.senghor.bank.com/docs?api-docs.json
curl -I https://fallou.senghor.bank.com/api/v1/comptes?page=1&limit=1
```

5) Notes
   - `app/Http/Middleware/TrustProxies.php` in this repo is configured to trust all proxies by default. This ensures Laravel recognizes `X-Forwarded-*` headers and generates HTTPS URLs correctly.
   - Keep `APP_URL`/`SWAGGER_BASE_URL` to your production domain (https) in Render environment variables.
