FROM php:8.2-cli

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www/html

# Copier les fichiers
COPY . .

# Installer les dépendances Laravel
RUN composer install

# Donner les permissions à Laravel
RUN chmod -R 777 storage bootstrap/cache

# Démarrer le serveur Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
