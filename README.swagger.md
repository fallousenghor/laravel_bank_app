## Swagger — personnaliser l'URL affichée

Ce projet utilise `l5-swagger` pour générer l'API documentation (Swagger UI). Par défaut la documentation utilise `APP_URL`, mais vous pouvez surcharger uniquement l'URL affichée dans l'interface (changement visuel uniquement) avec la variable d'environnement suivante :

- `SWAGGER_BASE_URL` — URL de production affichée dans Swagger UI (ex. `https://example.com/fallou-senghor`)
- `SWAGGER_DEV_BASE_URL` — URL de développement affichée dans Swagger UI (ex. `http://127.0.0.1:8002`)

Exemple (Render / production) :

1. Dans le panneau d'administration de Render, ajoutez la variable d'environnement `SWAGGER_BASE_URL` avec la valeur souhaitée.
2. Redéployez le service ou exécutez :

```bash
php artisan config:clear
php artisan l5-swagger:generate
```

Remarques :
- Ceci n'affecte pas les routes réelles de l'API — c'est uniquement une valeur affichée dans la doc. Si vous voulez que les routes commencent réellement par `/fallou-senghor`, il faut ajouter un préfixe aux routes (modifier `routes/api.php`).
- Ne commitez jamais votre fichier `.env` vers le dépôt : il contient des secrets (DB, APP_KEY, ...).
