# Déploiement sur Render

Ce fichier décrit les étapes minimales pour déployer cette application Laravel sur Render en utilisant le `Dockerfile` fourni.

Étapes rapides
- Créez un nouveau service Web (ou importez le repo) sur Render, en choisissant "Docker".
- Avant le premier déploiement, créez les secrets listés ci-dessous dans la section "Environment" (ou via `render secrets create`).
- Lancer le déploiement. Le conteneur exécutera `entrypoint.sh` qui, si `RUN_MIGRATIONS_ON_START=true`, lancera les migrations, générera/écrira les clés Passport si besoin, et seedera les clients OAuth.

Variables d'environnement / Secrets importants (à créer dans Render)
- APP_KEY (secret) — la clé d'application Laravel (base64:...). Vous pouvez la générer localement et la coller, ou laisser vide et permettre `php artisan key:generate` sur le conteneur (non recommandé en prod).
- DATABASE_URL ou DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD — renseignez en fonction de la base gérée par Render ou d'une DB externe.
- OAUTH_PRIVATE_KEY (secret) — le contenu PEM de la clé privée Passport (si vous voulez fournir la clé plutôt que la générer).
- OAUTH_PUBLIC_KEY (secret) — le contenu PEM de la clé publique Passport.
- REDIS_HOST / REDIS_PASSWORD — si vous utilisez Redis pour la file d'attente.
- MAIL_* — paramètres SMTP pour l'envoi d'e-mails.

Remarques sur les clés OAuth (Passport)
- Le `entrypoint.sh` a été étendu pour :
  - écrire `storage/oauth-private.key` et `storage/oauth-public.key` à partir des variables d'environnement `OAUTH_PRIVATE_KEY`/`OAUTH_PUBLIC_KEY` si elles sont définies et si les fichiers n'existent pas encore ;
  - sinon, lancer `php artisan passport:keys` pour générer les clés.
- Cela évite de commiter des clés privées dans le repo. Préférez stocker les clés privées dans les secrets Render.

Sécurité
- Ne commitez jamais `.env` ni les clés privées. Utilisez les secrets Render.
- Si vous avez déjà commité `storage/oauth-private.key` dans le dépôt :
  - retirez-le de l'historique Git et ajoutez-le à `.gitignore` / `.renderignore`.

Rendering tips
- Worker: Le manifeste `render.yaml` propose un service `worker` exécutant `php artisan queue:work`.
- Scheduler: Un cron job Render exécute `php artisan schedule:run` toutes les 5 minutes.

Vérifications après déploiement
1. Dans Render -> Service -> Logs, regardez si `RUN_MIGRATIONS_ON_START` a exécuté les migrations correctement.
2. Vérifiez que `storage/oauth-private.key` existe dans le conteneur (via logs ou en ajoutant une route de debug temporaire si nécessaire).
3. Tester les endpoints API en production.

Commandes utiles (locales)
- Générer APP_KEY localement :

  php artisan key:generate --show

- Extraire les clés Passport locales (si vous voulez les fournir à Render) :

  cat storage/oauth-private.key
  cat storage/oauth-public.key
